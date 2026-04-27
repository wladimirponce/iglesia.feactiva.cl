(function () {
    const API_BASE = window.FEACTIVA_API_BASE || 'https://iglesia.feactiva.cl/api/v1';

    function token() {
        return localStorage.getItem('feactiva_dev_token');
    }

    function setToken(value) {
        if (value) {
            localStorage.setItem('feactiva_dev_token', value);
            return;
        }

        localStorage.removeItem('feactiva_dev_token');
    }

    async function request(path, options = {}) {
        const headers = {
            Accept: 'application/json',
            ...(options.headers || {}),
        };

        if (options.body !== undefined) {
            headers['Content-Type'] = 'application/json';
        }

        const currentToken = token();
        if (currentToken) {
            headers.Authorization = `Bearer ${currentToken}`;
        }

        const response = await fetch(`${API_BASE}${path}`, {
            ...options,
            headers,
            body: options.body === undefined ? undefined : JSON.stringify(options.body),
        });

        const payload = await response.json().catch(() => null);

        if (!response.ok || !payload || payload.success === false) {
            const apiError = normalizeError(response.status, payload);
            if (response.status === 401 || apiError.code === 'UNAUTHENTICATED') {
                setToken(null);
                window.dispatchEvent(new CustomEvent('feactiva:auth-expired'));
            }
            throw apiError;
        }

        return payload;
    }

    function normalizeError(status, payload) {
        const error = payload?.error || {};
        const message = error.message || payload?.message || `Error HTTP ${status}`;
        const details = Array.isArray(error.details) ? error.details : [];
        const apiError = new Error(formatError(message, details));
        apiError.status = status;
        apiError.code = error.code || `HTTP_${status}`;
        apiError.details = details;
        return apiError;
    }

    function formatError(message, details) {
        if (Array.isArray(details) && details.length > 0) {
            const fields = details
                .filter((item) => item.field !== 'reason')
                .map((item) => item.message || item.field)
                .filter(Boolean)
                .join(' ');
            return `${message} ${fields}`.trim();
        }

        return message;
    }

    window.AppApi = {
        API_BASE,
        token,
        setToken,
        get: (path) => request(path),
        post: (path, body) => request(path, { method: 'POST', body }),
        patch: (path, body) => request(path, { method: 'PATCH', body }),
        delete: (path) => request(path, { method: 'DELETE' }),
    };
})();
