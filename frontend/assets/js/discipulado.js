(function () {
    const state = { loaded: false, rutas: [], personas: [] };

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('disc-ruta-form').addEventListener('submit', createRuta);
        document.getElementById('disc-etapa-form').addEventListener('submit', createEtapa);
        document.getElementById('disc-asignar-form').addEventListener('submit', assignRuta);
        document.getElementById('disc-avance-form').addEventListener('submit', showAvance);
        document.getElementById('reload-discipulado').addEventListener('click', loadAll);
    });

    async function load() {
        if (state.loaded) return;
        state.loaded = true;
        await loadAll();
    }

    async function loadAll() {
        try {
            Dashboard.clearGlobalError();
            const [rutas, personas] = await Promise.all([
                AppApi.get('/discipulado/rutas'),
                AppApi.get('/crm/personas?limit=100'),
            ]);
            state.rutas = rutas.data || [];
            state.personas = personas.data || [];
            renderRutas();
            renderSelects();
        } catch (error) {
            Dashboard.showGlobalError(error.message);
        }
    }

    async function createRuta(event) {
        event.preventDefault();
        await submit(event.currentTarget, '/discipulado/rutas', 'disc-ruta-message', 'Ruta creada correctamente.');
    }

    async function createEtapa(event) {
        event.preventDefault();
        const data = compact(new FormData(event.currentTarget));
        const rutaId = data.ruta_id;
        delete data.ruta_id;
        message('disc-etapa-message', '');
        try {
            await AppApi.post(`/discipulado/rutas/${rutaId}/etapas`, data);
            message('disc-etapa-message', 'Etapa creada correctamente.');
            await loadAll();
        } catch (error) {
            message('disc-etapa-message', error.message, true);
        }
    }

    async function assignRuta(event) {
        event.preventDefault();
        const data = compact(new FormData(event.currentTarget));
        const personaId = data.persona_id;
        delete data.persona_id;
        message('disc-asignar-message', '');
        try {
            await AppApi.post(`/discipulado/personas/${personaId}/rutas`, data);
            message('disc-asignar-message', 'Ruta asignada correctamente.');
            await renderAvance(personaId);
        } catch (error) {
            message('disc-asignar-message', error.message, true);
        }
    }

    async function showAvance(event) {
        event.preventDefault();
        const data = compact(new FormData(event.currentTarget));
        await renderAvance(data.persona_id);
    }

    async function renderAvance(personaId) {
        try {
            document.getElementById('disc-avance-list').innerHTML = Dashboard.emptyText('Cargando avance...');
            const response = await AppApi.get(`/discipulado/personas/${personaId}/avance`);
            const rows = response.data || [];
            document.getElementById('disc-avance-list').innerHTML = rows.length ? rows.map((row) => `
                <article class="list-card">
                    <div class="list-card-header">
                        <div>
                            <strong>${esc(row.ruta_nombre)} · ${esc(row.etapa_nombre || 'Sin etapa')}</strong>
                            <p>${esc(row.ruta_estado)} · ${esc(row.etapa_estado || '-')} · ${esc(row.porcentaje_avance)}%</p>
                        </div>
                        ${row.persona_etapa_id && row.etapa_estado !== 'completada' ? `<button type="button" class="secondary-button" data-complete-stage="${row.persona_etapa_id}" data-persona="${personaId}">Completar</button>` : ''}
                    </div>
                </article>
            `).join('') : Dashboard.emptyText('La persona no tiene rutas asignadas.');
            document.querySelectorAll('[data-complete-stage]').forEach((button) => {
                button.addEventListener('click', () => completeEtapa(button.dataset.completeStage, button.dataset.persona));
            });
        } catch (error) {
            Dashboard.showGlobalError(error.message);
        }
    }

    async function completeEtapa(id, personaId) {
        try {
            await AppApi.post(`/discipulado/persona-etapas/${id}/completar`, {});
            await renderAvance(personaId);
        } catch (error) {
            Dashboard.showGlobalError(error.message);
        }
    }

    function renderRutas() {
        const node = document.getElementById('disc-rutas-list');
        node.innerHTML = state.rutas.length ? state.rutas.map((ruta) => `
            <article class="list-card">
                <strong>${esc(ruta.nombre)}</strong>
                <p>${esc(ruta.descripcion || 'Sin descripcion')} · ${ruta.es_activa ? 'Activa' : 'Inactiva'}</p>
            </article>
        `).join('') : Dashboard.emptyText('No hay rutas de discipulado.');
    }

    function renderSelects() {
        const rutas = '<option value="">Seleccione ruta</option>' + state.rutas.map((r) => `<option value="${r.id}">${esc(r.nombre)}</option>`).join('');
        ['disc-etapa-ruta', 'disc-ruta-asignar'].forEach((id) => { document.getElementById(id).innerHTML = rutas; });
        const personas = '<option value="">Seleccione persona</option>' + state.personas.map((p) => `<option value="${p.id}">${esc(`${p.nombres} ${p.apellidos}`)}</option>`).join('');
        ['disc-persona', 'disc-avance-persona'].forEach((id) => { document.getElementById(id).innerHTML = personas; });
    }

    async function submit(form, path, messageId, okText) {
        message(messageId, '');
        try {
            await AppApi.post(path, compact(new FormData(form)));
            form.reset();
            message(messageId, okText);
            await loadAll();
        } catch (error) {
            message(messageId, error.message, true);
        }
    }

    function reset() { state.loaded = false; state.rutas = []; state.personas = []; }
    function compact(formData) { const data = {}; formData.forEach((v, k) => { const c = String(v).trim(); if (c !== '') data[k] = c; }); return data; }
    function message(id, text, error = false) { const n = document.getElementById(id); n.hidden = !text; n.textContent = text; n.classList.toggle('error', error); }
    function esc(value) { return Dashboard.escapeHtml(value ?? '-'); }

    window.Discipulado = { load, reset };
})();
