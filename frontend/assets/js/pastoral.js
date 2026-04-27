(function () {
    const state = { loaded: false, casos: [], oraciones: [], personas: [], activeCaseId: null };

    document.addEventListener('DOMContentLoaded', () => {
        const today = new Date().toISOString().slice(0, 10);
        document.querySelector('#past-caso-form input[name="fecha_apertura"]').value = today;
        document.getElementById('past-caso-form').addEventListener('submit', createCaso);
        document.getElementById('past-oracion-form').addEventListener('submit', createOracion);
        document.getElementById('past-sesion-form').addEventListener('submit', createSesion);
        document.getElementById('past-derivar-form').addEventListener('submit', derivarCaso);
        document.getElementById('reload-pastoral').addEventListener('click', loadAll);
    });

    async function load() {
        if (state.loaded) return;
        state.loaded = true;
        await loadAll();
    }

    async function loadAll() {
        try {
            Dashboard.clearGlobalError();
            const [casos, oraciones, personas] = await Promise.all([
                AppApi.get('/pastoral/casos'),
                AppApi.get('/pastoral/oracion'),
                AppApi.get('/crm/personas?limit=100'),
            ]);
            state.casos = casos.data || [];
            state.oraciones = oraciones.data || [];
            state.personas = personas.data || [];
            renderSelects();
            renderCasos();
            renderOraciones();
        } catch (error) {
            Dashboard.showGlobalError(error.message);
        }
    }

    async function createCaso(event) {
        event.preventDefault();
        const form = event.currentTarget;
        message('past-caso-message', '');
        try {
            const data = compact(new FormData(form));
            data.es_confidencial = true;
            await AppApi.post('/pastoral/casos', data);
            form.reset();
            document.querySelector('#past-caso-form input[name="fecha_apertura"]').value = new Date().toISOString().slice(0, 10);
            message('past-caso-message', 'Caso creado correctamente.');
            await loadAll();
        } catch (error) {
            message('past-caso-message', error.message, true);
        }
    }

    async function showCaso(id) {
        try {
            state.activeCaseId = id;
            document.getElementById('past-caso-detalle').innerHTML = Dashboard.emptyText('Cargando detalle...');
            const [detail, sessions] = await Promise.all([
                AppApi.get(`/pastoral/casos/${id}`),
                AppApi.get(`/pastoral/casos/${id}/sesiones`),
            ]);
            const caso = detail.data || {};
            const rows = sessions.data || [];
            document.getElementById('past-sesion-caso-id').value = id;
            document.getElementById('past-derivar-caso-id').value = id;
            document.getElementById('past-sesion-form').hidden = false;
            document.getElementById('past-derivar-form').hidden = false;
            document.getElementById('past-caso-detalle').innerHTML = `
                <article class="list-card">
                    <div class="list-card-header">
                        <div>
                            <strong>${esc(caso.titulo)}</strong>
                            <p>${esc(caso.estado)} · ${esc(caso.prioridad)} · ${esc(`${caso.nombres || ''} ${caso.apellidos || ''}`.trim())}</p>
                        </div>
                        <button type="button" class="secondary-button" data-close-case="${id}">Cerrar</button>
                    </div>
                    <p>${esc(caso.descripcion_general || 'Sin resumen')}</p>
                </article>
                ${rows.length ? rows.map((s) => `
                    <article class="list-card"><strong>${esc(s.fecha_sesion)} · ${esc(s.modalidad)}</strong><p>${esc(s.resumen || 'Sin resumen')}</p></article>
                `).join('') : Dashboard.emptyText('No hay sesiones registradas.')}
            `;
            document.querySelector('[data-close-case]')?.addEventListener('click', () => closeCaso(id));
        } catch (error) {
            Dashboard.showGlobalError(error.message);
        }
    }

    async function createSesion(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const data = compact(new FormData(form));
        const casoId = data.caso_id;
        delete data.caso_id;
        if (data.fecha_sesion) data.fecha_sesion = data.fecha_sesion.replace('T', ' ') + ':00';
        message('past-sesion-message', '');
        try {
            await AppApi.post(`/pastoral/casos/${casoId}/sesiones`, data);
            form.reset();
            document.getElementById('past-sesion-caso-id').value = casoId;
            message('past-sesion-message', 'Sesion creada correctamente.');
            await showCaso(casoId);
        } catch (error) {
            message('past-sesion-message', error.message, true);
        }
    }

    async function closeCaso(id) {
        try {
            await AppApi.post(`/pastoral/casos/${id}/cerrar`, {});
            await loadAll();
            await showCaso(id);
        } catch (error) {
            Dashboard.showGlobalError(error.message);
        }
    }

    async function derivarCaso(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const data = compact(new FormData(form));
        const casoId = data.caso_id;
        delete data.caso_id;
        message('past-derivar-message', '');
        try {
            await AppApi.post(`/pastoral/casos/${casoId}/derivar`, data);
            form.reset();
            document.getElementById('past-derivar-caso-id').value = casoId;
            message('past-derivar-message', 'Caso derivado correctamente.');
            await loadAll();
            await showCaso(casoId);
        } catch (error) {
            message('past-derivar-message', error.message, true);
        }
    }

    async function createOracion(event) {
        event.preventDefault();
        const form = event.currentTarget;
        message('past-oracion-message', '');
        try {
            await AppApi.post('/pastoral/oracion', compact(new FormData(form)));
            form.reset();
            message('past-oracion-message', 'Solicitud creada correctamente.');
            await loadAll();
        } catch (error) {
            message('past-oracion-message', error.message, true);
        }
    }

    function renderCasos() {
        const node = document.getElementById('past-casos-list');
        node.innerHTML = state.casos.length ? state.casos.map((caso) => `
            <article class="list-card">
                <div class="list-card-header">
                    <div>
                        <strong>${esc(caso.titulo)}</strong>
                        <p>${esc(caso.estado)} · ${esc(caso.prioridad)} · ${esc(caso.fecha_apertura)}</p>
                    </div>
                    <button type="button" class="secondary-button" data-show-case="${caso.id}">Ver</button>
                </div>
            </article>
        `).join('') : Dashboard.emptyText('No hay casos pastorales.');
        node.querySelectorAll('[data-show-case]').forEach((button) => button.addEventListener('click', () => showCaso(button.dataset.showCase)));
    }

    function renderOraciones() {
        const node = document.getElementById('past-oracion-list');
        node.innerHTML = state.oraciones.length ? state.oraciones.map((o) => `
            <article class="list-card">
                <strong>${esc(o.titulo)}</strong>
                <p>${esc(o.estado)} · ${esc(o.categoria || '-')} · ${esc(o.privacidad)}</p>
            </article>
        `).join('') : Dashboard.emptyText('No hay solicitudes de oracion.');
    }

    function renderSelects() {
        const options = '<option value="">Seleccione persona</option>' + state.personas.map((p) => `<option value="${p.id}">${esc(`${p.nombres} ${p.apellidos}`)}</option>`).join('');
        document.getElementById('past-caso-persona').innerHTML = options;
        document.getElementById('past-oracion-persona').innerHTML = '<option value="">Sin persona vinculada</option>' + options.replace('<option value="">Seleccione persona</option>', '');
    }

    function reset() {
        state.loaded = false;
        state.casos = [];
        state.oraciones = [];
        state.personas = [];
        state.activeCaseId = null;
        ['past-casos-list', 'past-oracion-list', 'past-caso-detalle'].forEach((id) => { document.getElementById(id).innerHTML = ''; });
        document.getElementById('past-sesion-form').hidden = true;
        document.getElementById('past-derivar-form').hidden = true;
    }
    function compact(formData) { const data = {}; formData.forEach((v, k) => { const c = String(v).trim(); if (c !== '') data[k] = c; }); return data; }
    function message(id, text, error = false) { const n = document.getElementById(id); n.hidden = !text; n.textContent = text; n.classList.toggle('error', error); }
    function esc(value) { return Dashboard.escapeHtml(value ?? '-'); }

    window.Pastoral = { load, reset };
})();
