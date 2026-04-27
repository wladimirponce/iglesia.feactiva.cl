(function () {
    const state = { loaded: false, familias: [], personas: [] };

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('familia-form').addEventListener('submit', createFamilia);
        document.getElementById('familia-persona-form').addEventListener('submit', addPersona);
        document.getElementById('reload-familias').addEventListener('click', loadAll);
    });

    async function load() {
        if (state.loaded) return;
        state.loaded = true;
        await loadAll();
    }

    async function loadAll() {
        try {
            Dashboard.clearGlobalError();
            setLoading('familias-list');
            const [familias, personas] = await Promise.all([
                AppApi.get('/crm/familias'),
                AppApi.get('/crm/personas?limit=100'),
            ]);
            state.familias = familias.data || [];
            state.personas = personas.data || [];
            renderFamilias();
            renderSelects();
        } catch (error) {
            Dashboard.showGlobalError(error.message);
        }
    }

    async function createFamilia(event) {
        event.preventDefault();
        const form = event.currentTarget;
        message('familia-message', '');
        try {
            await AppApi.post('/crm/familias', compact(new FormData(form)));
            form.reset();
            message('familia-message', 'Familia creada correctamente.');
            await loadAll();
        } catch (error) {
            message('familia-message', error.message, true);
        }
    }

    async function addPersona(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const data = compact(new FormData(form));
        const familiaId = data.familia_id;
        delete data.familia_id;
        message('familia-persona-message', '');
        try {
            await AppApi.post(`/crm/familias/${familiaId}/personas`, data);
            message('familia-persona-message', 'Persona agregada correctamente.');
            await showFamilia(familiaId);
        } catch (error) {
            message('familia-persona-message', error.message, true);
        }
    }

    async function showFamilia(id) {
        try {
            setLoading('familia-miembros');
            const response = await AppApi.get(`/crm/familias/${id}`);
            const familia = response.data || {};
            const miembros = familia.personas || familia.miembros || [];
            document.getElementById('familia-miembros').innerHTML = `
                <article class="list-card">
                    <strong>${esc(familia.nombre_familia || 'Familia')}</strong>
                    <p>${esc(familia.ciudad || '-')} · ${esc(familia.telefono_principal || '-')}</p>
                </article>
                ${miembros.length ? miembros.map((m) => `
                    <article class="list-card">
                        <strong>${esc(`${m.nombres || ''} ${m.apellidos || ''}`.trim() || `Persona ${m.persona_id}`)}</strong>
                        <p>${esc(m.tipo_relacion || '-')}</p>
                    </article>
                `).join('') : Dashboard.emptyText('Esta familia no tiene miembros registrados.')}
            `;
        } catch (error) {
            Dashboard.showGlobalError(error.message);
        }
    }

    function renderFamilias() {
        const node = document.getElementById('familias-list');
        if (state.familias.length === 0) {
            node.innerHTML = Dashboard.emptyText('No hay familias registradas.');
            return;
        }
        node.innerHTML = state.familias.map((familia) => `
            <article class="list-card">
                <div class="list-card-header">
                    <div>
                        <strong>${esc(familia.nombre_familia)}</strong>
                        <p>${esc(familia.ciudad || '-')} · ${esc(familia.telefono_principal || '-')}</p>
                    </div>
                    <button type="button" class="secondary-button" data-family-id="${familia.id}">Miembros</button>
                </div>
            </article>
        `).join('');
        node.querySelectorAll('[data-family-id]').forEach((button) => {
            button.addEventListener('click', () => showFamilia(button.dataset.familyId));
        });
    }

    function renderSelects() {
        options('familia-persona-familia', state.familias, 'Seleccione familia', 'nombre_familia');
        options('familia-persona-persona', state.personas, 'Seleccione persona', (p) => `${p.nombres} ${p.apellidos}`);
    }

    function reset() {
        state.loaded = false;
        state.familias = [];
        state.personas = [];
        ['familias-list', 'familia-miembros'].forEach((id) => { document.getElementById(id).innerHTML = ''; });
        message('familia-message', '');
        message('familia-persona-message', '');
    }

    function options(id, items, placeholder, label) {
        document.getElementById(id).innerHTML = `<option value="">${esc(placeholder)}</option>` + items.map((item) => {
            const text = typeof label === 'function' ? label(item) : item[label];
            return `<option value="${item.id}">${esc(text)}</option>`;
        }).join('');
    }

    function compact(formData) {
        const data = {};
        formData.forEach((value, key) => {
            const clean = String(value).trim();
            if (clean !== '') data[key] = clean;
        });
        return data;
    }

    function message(id, text, error = false) {
        const node = document.getElementById(id);
        node.hidden = !text;
        node.textContent = text;
        node.classList.toggle('error', error);
    }

    function setLoading(id) { document.getElementById(id).innerHTML = Dashboard.emptyText('Cargando...'); }
    function esc(value) { return Dashboard.escapeHtml(value ?? '-'); }

    window.Familias = { load, reset };
})();
