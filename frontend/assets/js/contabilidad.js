(function () {
    const state = { loaded: false, cuentas: [], asientos: [] };

    document.addEventListener('DOMContentLoaded', () => {
        const today = new Date().toISOString().slice(0, 10);
        document.querySelector('#periodo-form input[name="fecha_inicio"]').value = `${today.slice(0, 4)}-01-01`;
        document.querySelector('#periodo-form input[name="fecha_fin"]').value = `${today.slice(0, 4)}-12-31`;
        document.querySelector('#asiento-form input[name="fecha_asiento"]').value = today;
        document.getElementById('periodo-form').addEventListener('submit', createPeriodo);
        document.getElementById('asiento-form').addEventListener('submit', createAsiento);
        document.getElementById('reload-contabilidad').addEventListener('click', loadAll);
        document.querySelectorAll('[data-acct-report]').forEach((button) => {
            button.addEventListener('click', () => loadReport(button.dataset.acctReport));
        });
    });

    async function load() {
        if (state.loaded) return;
        state.loaded = true;
        await loadAll();
    }

    async function loadAll() {
        try {
            Dashboard.clearGlobalError();
            const [cuentas, asientos] = await Promise.all([
                AppApi.get('/contabilidad/cuentas'),
                AppApi.get('/contabilidad/asientos'),
            ]);
            state.cuentas = cuentas.data || [];
            state.asientos = asientos.data || [];
            renderCuentas();
            renderAsientos();
            renderCuentaOptions();
        } catch (error) {
            Dashboard.showGlobalError(error.message);
        }
    }

    async function createPeriodo(event) {
        event.preventDefault();
        await submitForm(event.currentTarget, '/contabilidad/periodos', 'periodo-message', 'Periodo creado correctamente.');
    }

    async function createAsiento(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const data = compact(new FormData(form));
        const monto = Number(data.monto);
        const body = {
            numero: data.numero,
            fecha_asiento: data.fecha_asiento,
            descripcion: data.descripcion,
            origen: 'manual',
            lineas: [
                { cuenta_id: Number(data.cuenta_debe_id), debe: monto, haber: 0 },
                { cuenta_id: Number(data.cuenta_haber_id), debe: 0, haber: monto },
            ],
        };
        message('asiento-message', '');
        try {
            await AppApi.post('/contabilidad/asientos', body);
            message('asiento-message', 'Asiento creado correctamente.');
            await loadAll();
        } catch (error) {
            message('asiento-message', error.message, true);
        }
    }

    async function approveAsiento(id) {
        try {
            await AppApi.post(`/contabilidad/asientos/${id}/aprobar`, {});
            await loadAll();
        } catch (error) {
            Dashboard.showGlobalError(error.message);
        }
    }

    async function loadReport(name) {
        try {
            document.getElementById('acct-report-output').innerHTML = Dashboard.emptyText('Cargando reporte...');
            const response = await AppApi.get(`/contabilidad/reportes/${name}`);
            document.getElementById('acct-report-output').innerHTML = renderJson(response.data);
        } catch (error) {
            Dashboard.showGlobalError(error.message);
        }
    }

    function renderCuentas() {
        const tbody = document.getElementById('acct-cuentas-table');
        tbody.innerHTML = state.cuentas.length ? state.cuentas.map((cuenta) => `
            <tr><td>${esc(cuenta.codigo)}</td><td>${esc(cuenta.nombre)}</td><td>${esc(cuenta.tipo)}</td><td>${esc(cuenta.naturaleza)}</td></tr>
        `).join('') : '<tr><td colspan="4">No hay cuentas contables.</td></tr>';
    }

    function renderAsientos() {
        const tbody = document.getElementById('acct-asientos-table');
        tbody.innerHTML = state.asientos.length ? state.asientos.map((a) => `
            <tr>
                <td>${esc(a.numero)}</td><td>${esc(a.fecha_asiento)}</td><td>${esc(a.descripcion)}</td><td><span class="badge">${esc(a.estado)}</span></td>
                <td>${a.estado === 'borrador' ? `<button type="button" class="secondary-button" data-approve="${a.id}">Aprobar</button>` : '-'}</td>
            </tr>
        `).join('') : '<tr><td colspan="5">No hay asientos.</td></tr>';
        tbody.querySelectorAll('[data-approve]').forEach((button) => button.addEventListener('click', () => approveAsiento(button.dataset.approve)));
    }

    function renderCuentaOptions() {
        const html = '<option value="">Seleccione cuenta</option>' + state.cuentas.map((c) => `<option value="${c.id}">${esc(c.codigo)} · ${esc(c.nombre)}</option>`).join('');
        document.getElementById('asiento-cuenta-debe').innerHTML = html;
        document.getElementById('asiento-cuenta-haber').innerHTML = html;
    }

    async function submitForm(form, path, messageId, okText) {
        message(messageId, '');
        try {
            await AppApi.post(path, compact(new FormData(form)));
            message(messageId, okText);
            await loadAll();
        } catch (error) {
            message(messageId, error.message, true);
        }
    }

    function reset() { state.loaded = false; state.cuentas = []; state.asientos = []; }
    function renderJson(data) { return `<article class="list-card"><pre>${esc(JSON.stringify(data, null, 2))}</pre></article>`; }
    function compact(formData) { const data = {}; formData.forEach((v, k) => { const c = String(v).trim(); if (c !== '') data[k] = c; }); return data; }
    function message(id, text, error = false) { const n = document.getElementById(id); n.hidden = !text; n.textContent = text; n.classList.toggle('error', error); }
    function esc(value) { return Dashboard.escapeHtml(value ?? '-'); }

    window.Contabilidad = { load, reset };
})();
