(function () {
    const state = {
        user: null,
    };

    async function login(email, password) {
        const response = await AppApi.post('/auth/login', { email, password });
        AppApi.setToken(response.data.token);
        state.user = await me();
        return state.user;
    }

    async function me() {
        const response = await AppApi.get('/auth/me');
        state.user = response.data;
        return state.user;
    }

    async function checkAuth() {
        if (!AppApi.token()) {
            state.user = null;
            return false;
        }

        try {
            await me();
            return true;
        } catch (error) {
            AppApi.setToken(null);
            state.user = null;
            return false;
        }
    }

    async function logout() {
        try {
            if (AppApi.token()) {
                await AppApi.post('/auth/logout', {});
            }
        } finally {
            AppApi.setToken(null);
            state.user = null;
        }
    }

    function clearSession() {
        AppApi.setToken(null);
        state.user = null;
    }

    window.Auth = {
        state,
        login,
        me,
        checkAuth,
        logout,
        clearSession,
    };

    window.checkAuth = checkAuth;
})();
