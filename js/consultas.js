(() => {
    const generationForm = document.querySelector('[data-generate-consultations]');
    if (generationForm) {
        const button = generationForm.querySelector('button[type="submit"]');
        const label = button.querySelector('span');
        const initialLabel = label.textContent;
        generationForm.addEventListener('submit', event => {
            if (event.defaultPrevented) return;
            if (button.disabled) {
                event.preventDefault();
                return;
            }
            button.disabled = true;
            generationForm.setAttribute('aria-busy', 'true');
            label.textContent = 'Gerando consultas…';
        });
        window.addEventListener('pageshow', () => {
            button.disabled = false;
            generationForm.removeAttribute('aria-busy');
            label.textContent = initialLabel;
        });
    }
    const table = document.getElementById('tabela-consultas');
    if (!table) return;
    const feedback = document.querySelector('[data-status-feedback]');
    let catalog = {};
    try {
        catalog = JSON.parse(document.getElementById('clinic-toast-catalog')?.textContent || '{}');
    } catch {}
    let pending = Promise.resolve();

    function notify(code, message = null) {
        const entry = catalog[code] || catalog['operation.failed'];
        if (!entry) return;
        feedback.textContent = typeof message === 'string' ? message : entry.message;
        feedback.hidden = false;
        feedback.setAttribute('role', entry.type === 'error' ? 'alert' : 'status');
        window.ClinicToast?.show(catalog[code] ? code : 'operation.failed', feedback, message);
    }

    function setEditing(form, enabled, saving = false) {
        const link = form.closest('tr').querySelector('[data-consulta-edit]');
        link.classList.toggle('disabled', !enabled);
        link.title = saving ? 'Aguarde o salvamento do status.' : enabled ? 'Editar relatório' : catalog['report.not_completed']?.message || 'Conclua a consulta para editar o relatório.';
        if (enabled) {
            link.href = link.dataset.editUrl;
            link.removeAttribute('aria-disabled');
            link.removeAttribute('tabindex');
            link.removeAttribute('role');
        } else {
            link.removeAttribute('href');
            link.setAttribute('aria-disabled', 'true');
            link.setAttribute('tabindex', '-1');
            link.setAttribute('role', 'link');
        }
    }

    async function save(form, select, body) {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 15000);
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                credentials: 'same-origin',
                redirect: 'error',
                body,
                signal: controller.signal
            });
            const result = await response.json();
            if (!response.ok || result.ok !== true) {
                notify(response.status === 401 ? 'auth.required' : result.toast_code || 'consultation.save_failed', result.mensagem);
                return;
            }
            if (result.id !== Number(form.dataset.id)
                || !Array.from(select.options).some(option => !option.disabled && option.value === result.status)
                || result.pode_editar !== (result.status === 'Concluído')
                || !['mes', 'realizadas', 'canceladas'].every(key => Number.isInteger(result.indicadores?.[key]) && result.indicadores[key] >= 0)) {
                throw new Error('Resposta inválida.');
            }
            select.dataset.savedValue = result.status;
            const fields = { mes: 'consultas_esse_mes', realizadas: 'consultas_realizadas', canceladas: 'consultas_canceladas' };
            Object.entries(fields).forEach(([key, field]) => {
                const value = result.indicadores[key];
                document.querySelector('[data-field="' + field + '"]').textContent = value + (value === 1 ? ' Consulta' : ' Consultas');
            });
            notify(result.toast_code);
        } catch {
            notify('consultation.save_failed');
        } finally {
            clearTimeout(timeout);
            select.value = select.dataset.savedValue;
            select.disabled = false;
            form.removeAttribute('aria-busy');
            setEditing(form, select.dataset.savedValue === 'Concluído');
        }
    }

    table.querySelectorAll('[data-status-form]').forEach(form => {
        const select = form.elements.namedItem('status');
        function enqueue() {
            if (select.disabled || select.value === select.dataset.savedValue || !select.checkValidity()) return;
            const body = new URLSearchParams(new FormData(form));
            select.disabled = true;
            form.setAttribute('aria-busy', 'true');
            setEditing(form, false, true);
            pending = pending.then(() => save(form, select, body));
        }
        select.addEventListener('change', enqueue);
        form.addEventListener('submit', event => {
            event.preventDefault();
            enqueue();
        });
    });
})();
