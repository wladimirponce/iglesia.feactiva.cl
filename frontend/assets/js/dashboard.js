(function () {
    const viewTitles = {
        dashboard: 'Dashboard',
        crm: 'CRM Personas',
        familias: 'Familias',
        finanzas: 'Finanzas',
        contabilidad: 'Contabilidad',
        discipulado: 'Discipulado',
        pastoral: 'Pastoral',
    };

    document.addEventListener('DOMContentLoaded', async () => {
        bindAuth();
        bindNavigation();
        bindAuthExpiry();
        await initAuthGate();
    });

    function bindAuth() {
        document.getElementById('login-form').addEventListener('submit', async (event) => {
            event.preventDefault();
            setLoginError('');

            const email = document.getElementById('login-email').value.trim();
            const password = document.getElementById('login-password').value;

            try {
                await Auth.login(email, password);
                await showApp();
            } catch (error) {
                setLoginError(error.message);
            }
        });

        document.getElementById('logout-button').addEventListener('click', async () => {
            closeMobileMenu();
            await Auth.logout();
            resetProtectedUi();
            showLogin();
        });
    }

    function bindNavigation() {
        document.querySelectorAll('[data-view]').forEach((button) => {
            button.addEventListener('click', () => {
                closeMobileMenu();
                showView(button.dataset.view);
            });
        });
    }

    function bindAuthExpiry() {
        window.addEventListener('feactiva:auth-expired', () => {
            Auth.clearSession();
            resetProtectedUi();
            showLogin();
            setLoginError('Sesion expirada. Inicia sesion nuevamente.');
        });
    }

    function closeMobileMenu() {
        const menuToggle = document.getElementById('menu-toggle');
        if (menuToggle) {
            menuToggle.checked = false;
        }
    }

    async function initAuthGate() {
        showLoading();
        const isAuthenticated = await checkAuth();

        if (isAuthenticated) {
            await showApp();
            return;
        }

        showLogin();
    }

    function showLoading() {
        document.getElementById('auth-loading-screen').hidden = false;
        document.getElementById('login-screen').hidden = true;
        document.getElementById('app-screen').hidden = true;
    }

    async function showApp() {
        document.getElementById('auth-loading-screen').hidden = true;
        document.getElementById('login-screen').hidden = true;
        document.getElementById('app-screen').hidden = false;
        renderUser();
        showView('dashboard');
        await refreshDashboard();
    }

    function showLogin() {
        document.getElementById('auth-loading-screen').hidden = true;
        document.getElementById('login-screen').hidden = false;
        document.getElementById('app-screen').hidden = true;
    }

    function showView(view) {
        if (!Auth.state.user) {
            showLogin();
            return;
        }

        document.querySelectorAll('[data-view]').forEach((button) => {
            button.classList.toggle('active', button.dataset.view === view);
        });

        document.querySelectorAll('.view').forEach((section) => {
            section.classList.toggle('active', section.id === `view-${view}`);
        });

        document.getElementById('view-title').textContent = viewTitles[view] || 'Dashboard';
        clearGlobalError();

        if (view === 'crm' && window.CRM) {
            CRM.load();
        }

        if (view === 'finanzas' && window.Finanzas) {
            Finanzas.load();
        }

        if (view === 'familias' && window.Familias) {
            Familias.load();
        }

        if (view === 'contabilidad' && window.Contabilidad) {
            Contabilidad.load();
        }

        if (view === 'discipulado' && window.Discipulado) {
            Discipulado.load();
        }

        if (view === 'pastoral' && window.Pastoral) {
            Pastoral.load();
        }
    }

    async function refreshDashboard() {
        try {
            const health = await AppApi.get('/health');
            document.getElementById('health-status').textContent = health.data.status;
        } catch (error) {
            document.getElementById('health-status').textContent = 'sin conexion';
        }

        renderUser();

        if (window.Finanzas) {
            try {
                const summary = await Finanzas.fetchResumen();
                document.getElementById('dashboard-finance-summary').innerHTML = Finanzas.summaryHtml(summary);
            } catch (error) {
                document.getElementById('dashboard-finance-summary').innerHTML = emptyText('Sin datos financieros.');
            }
        }
    }

    function renderUser() {
        const user = Auth.state.user;
        const email = user?.email || '-';
        document.getElementById('user-badge').textContent = email;
        document.getElementById('dashboard-user').textContent = user?.user_id || '-';
        document.getElementById('dashboard-tenant').textContent = user?.tenant_id || '-';
    }

    function resetProtectedUi() {
        clearGlobalError();
        document.querySelectorAll('[data-view]').forEach((button) => {
            button.classList.toggle('active', button.dataset.view === 'dashboard');
        });
        document.querySelectorAll('.view').forEach((section) => {
            section.classList.toggle('active', section.id === 'view-dashboard');
        });
        document.getElementById('view-title').textContent = 'Dashboard';
        document.getElementById('user-badge').textContent = '-';
        document.getElementById('dashboard-user').textContent = '-';
        document.getElementById('dashboard-tenant').textContent = '-';
        document.getElementById('health-status').textContent = '-';
        document.getElementById('dashboard-finance-summary').innerHTML = '';
        document.getElementById('personas-table').innerHTML = '';
        document.getElementById('movimientos-table').innerHTML = '';
        document.getElementById('finance-summary').innerHTML = '';
        document.getElementById('saldo-cuentas').innerHTML = '';
        setLoginError('');

        [window.CRM, window.Finanzas, window.Familias, window.Contabilidad, window.Discipulado, window.Pastoral]
            .forEach((module) => module?.reset?.());
    }

    function setLoginError(message) {
        const node = document.getElementById('login-error');
        node.hidden = !message;
        node.textContent = message;
    }

    function showGlobalError(message) {
        const node = document.getElementById('global-error');
        node.hidden = false;
        node.textContent = message;
    }

    function clearGlobalError() {
        const node = document.getElementById('global-error');
        node.hidden = true;
        node.textContent = '';
    }

    function emptyText(text) {
        return `<p class="form-message">${escapeHtml(text)}</p>`;
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        }[char]));
    }

    window.Dashboard = {
        showGlobalError,
        clearGlobalError,
        escapeHtml,
        emptyText,
        refreshDashboard,
        resetProtectedUi,
        showView,
    };
})();
