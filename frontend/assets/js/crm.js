(function () {
    let loaded = false;

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('reload-personas').addEventListener('click', loadPersonas);
        document.getElementById('persona-form').addEventListener('submit', createPersona);
    });

    async function load() {
        if (loaded) {
            return;
        }

        loaded = true;
        await loadPersonas();
    }

    async function loadPersonas() {
        try {
            Dashboard.clearGlobalError();
            const response = await AppApi.get('/crm/personas?limit=50');
            renderPersonas(response.data || []);
        } catch (error) {
            Dashboard.showGlobalError(error.message);
        }
    }

    async function createPersona(event) {
        event.preventDefault();
        const form = event.currentTarget;
        setMessage('');

        const data = compactFormData(new FormData(form));

        try {
            await AppApi.post('/crm/personas', data);
            form.reset();
            setMessage('Persona creada correctamente.');
            await loadPersonas();
        } catch (error) {
            setMessage(error.message, true);
        }
    }

    function renderPersonas(personas) {
        const tbody = document.getElementById('personas-table');

        if (personas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4">No hay personas registradas.</td></tr>';
            return;
        }

        tbody.innerHTML = personas.map((persona) => `
            <tr>
                <td>${Dashboard.escapeHtml(`${persona.nombres} ${persona.apellidos}`)}</td>
                <td>${Dashboard.escapeHtml(persona.email || '-')}</td>
                <td>${Dashboard.escapeHtml(persona.telefono || persona.whatsapp || '-')}</td>
                <td><span class="badge">${Dashboard.escapeHtml(persona.estado_persona || '-')}</span></td>
            </tr>
        `).join('');
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

    function setMessage(message, isError = false) {
        const node = document.getElementById('persona-message');
        node.hidden = !message;
        node.textContent = message;
        node.classList.toggle('error', isError);
    }

    function reset() {
        loaded = false;
        document.getElementById('personas-table').innerHTML = '';
        setMessage('');
    }

    window.CRM = {
        load,
        loadPersonas,
        reset,
    };
})();
