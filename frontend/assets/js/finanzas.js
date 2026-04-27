(function () {
    const state = {
        loaded: false,
        cuentas: [],
        categorias: [],
        centros: [],
    };

    document.addEventListener('DOMContentLoaded', () => {
        const today = new Date().toISOString().slice(0, 10);
        const firstDay = `${today.slice(0, 8)}01`;

        document.querySelectorAll('input[name="fecha_movimiento"], input[name="fecha_contable"]').forEach((input) => {
            input.value = today;
        });

        document.getElementById('resumen-inicio').value = firstDay;
        document.getElementById('resumen-fin').value = today;
        document.getElementById('movimiento-tipo').addEventListener('change', renderCategorias);
        document.getElementById('reload-movimientos').addEventListener('click', loadMovimientos);
        document.getElementById('reload-resumen').addEventListener('click', loadReportes);
        document.getElementById('movimiento-form').addEventListener('submit', createMovimiento);
    });

    async function load() {
        try {
            Dashboard.clearGlobalError();

            if (!state.loaded) {
                await loadCatalogos();
                state.loaded = true;
            }

            await Promise.all([
                loadMovimientos(),
                loadReportes(),
            ]);
        } catch (error) {
            Dashboard.showGlobalError(error.message);
        }
    }

    async function loadCatalogos() {
        const [cuentas, categorias, centros] = await Promise.all([
            AppApi.get('/finanzas/cuentas'),
            AppApi.get('/finanzas/categorias'),
            AppApi.get('/finanzas/centros-costo'),
        ]);

        state.cuentas = cuentas.data || [];
        state.categorias = categorias.data || [];
        state.centros = centros.data || [];

        renderOptions('movimiento-cuenta', state.cuentas, 'Seleccione cuenta');
        renderOptions('movimiento-centro', state.centros, 'Sin centro de costo', true);
        renderCategorias();
    }

    async function loadMovimientos() {
        const response = await AppApi.get('/finanzas/movimientos?limit=50');
        renderMovimientos(response.data || []);
    }

    async function loadReportes() {
        const resumen = await fetchResumen();
        document.getElementById('finance-summary').innerHTML = summaryHtml(resumen);

        const saldos = await AppApi.get('/finanzas/reportes/saldo-cuentas');
        renderSaldoCuentas(saldos.data || []);
    }

    async function fetchResumen() {
        const inicio = document.getElementById('resumen-inicio')?.value || '';
        const fin = document.getElementById('resumen-fin')?.value || '';
        const qs = inicio && fin ? `?fecha_inicio=${encodeURIComponent(inicio)}&fecha_fin=${encodeURIComponent(fin)}` : '';
        const response = await AppApi.get(`/finanzas/reportes/resumen${qs}`);
        return response.data;
    }

    async function createMovimiento(event) {
        event.preventDefault();
        const form = event.currentTarget;
        setMessage('');

        const data = compactFormData(new FormData(form));
        data.monto = Number(data.monto);

        try {
            await AppApi.post('/finanzas/movimientos', data);
            const tipo = data.tipo;
            form.reset();
            document.getElementById('movimiento-tipo').value = tipo;
            const today = new Date().toISOString().slice(0, 10);
            form.elements.fecha_movimiento.value = today;
            form.elements.fecha_contable.value = today;
            renderCategorias();
            setMessage('Movimiento creado correctamente.');
            await Promise.all([loadMovimientos(), loadReportes(), Dashboard.refreshDashboard()]);
        } catch (error) {
            setMessage(error.message, true);
        }
    }

    function renderCategorias() {
        const tipo = document.getElementById('movimiento-tipo').value;
        const categorias = state.categorias.filter((categoria) => categoria.tipo === tipo);
        renderOptions('movimiento-categoria', categorias, 'Seleccione categoria');
    }

    function renderOptions(elementId, items, placeholder, allowEmpty = false) {
        const node = document.getElementById(elementId);
        const empty = allowEmpty ? `<option value="">${Dashboard.escapeHtml(placeholder)}</option>` : `<option value="">${Dashboard.escapeHtml(placeholder)}</option>`;
        node.innerHTML = empty + items.map((item) => (
            `<option value="${item.id}">${Dashboard.escapeHtml(item.nombre)}</option>`
        )).join('');
    }

    function renderMovimientos(movimientos) {
        const tbody = document.getElementById('movimientos-table');

        if (movimientos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6">No hay movimientos registrados.</td></tr>';
            return;
        }

        tbody.innerHTML = movimientos.map((movimiento) => {
            const amountClass = movimiento.tipo === 'ingreso' ? 'money-positive' : 'money-negative';
            return `
                <tr>
                    <td>${Dashboard.escapeHtml(movimiento.fecha_movimiento)}</td>
                    <td><span class="badge">${Dashboard.escapeHtml(movimiento.tipo)}</span></td>
                    <td>${Dashboard.escapeHtml(movimiento.descripcion)}</td>
                    <td>${Dashboard.escapeHtml(movimiento.cuenta_nombre || '-')}</td>
                    <td class="${amountClass}">${formatMoney(movimiento.monto)}</td>
                    <td>${Dashboard.escapeHtml(movimiento.estado)}</td>
                </tr>
            `;
        }).join('');
    }

    function renderSaldoCuentas(cuentas) {
        const node = document.getElementById('saldo-cuentas');

        if (cuentas.length === 0) {
            node.innerHTML = Dashboard.emptyText('No hay cuentas financieras.');
            return;
        }

        node.innerHTML = cuentas.map((cuenta) => `
            <div class="account-row">
                <div>
                    <strong>${Dashboard.escapeHtml(cuenta.nombre)}</strong>
                    <span>${Dashboard.escapeHtml(cuenta.tipo)} · ${Dashboard.escapeHtml(cuenta.moneda)}</span>
                </div>
                <strong>${formatMoney(cuenta.saldo_actual)}</strong>
            </div>
        `).join('');
    }

    function summaryHtml(summary) {
        return `
            <div class="summary-item">
                <span>Ingresos</span>
                <strong class="money-positive">${formatMoney(summary?.ingresos || 0)}</strong>
            </div>
            <div class="summary-item">
                <span>Egresos</span>
                <strong class="money-negative">${formatMoney(summary?.egresos || 0)}</strong>
            </div>
            <div class="summary-item">
                <span>Saldo neto</span>
                <strong>${formatMoney(summary?.saldo_neto || 0)}</strong>
            </div>
        `;
    }

    function compactFormData(formData) {
        const data = {};

        formData.forEach((value, key) => {
            const clean = String(value).trim();
            if (clean !== '') {
                data[key] = clean;
            }
        });

        return data;
    }

    function formatMoney(value) {
        return new Intl.NumberFormat('es-CL', {
            style: 'currency',
            currency: 'CLP',
            maximumFractionDigits: 0,
        }).format(Number(value || 0));
    }

    function setMessage(message, isError = false) {
        const node = document.getElementById('movimiento-message');
        node.hidden = !message;
        node.textContent = message;
        node.classList.toggle('error', isError);
    }

    function reset() {
        state.loaded = false;
        state.cuentas = [];
        state.categorias = [];
        state.centros = [];
        document.getElementById('movimientos-table').innerHTML = '';
        document.getElementById('finance-summary').innerHTML = '';
        document.getElementById('saldo-cuentas').innerHTML = '';
        setMessage('');
    }

    window.Finanzas = {
        load,
        fetchResumen,
        summaryHtml,
        reset,
    };
})();
