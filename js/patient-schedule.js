(() => {
    const form = document.querySelector('#patient-form');
    if (!form) return;
    const submitButtons = Array.from(form.querySelectorAll('button[type="submit"]'));
    const originalButtons = submitButtons.map((button) => ({ button, html: button.innerHTML, disabled: button.disabled }));
    let saving = false;
    form.addEventListener('submit', (event) => {
        if (saving) {
            event.preventDefault();
            return;
        }
        if (event.defaultPrevented || !form.checkValidity()) return;
        saving = true;
        form.setAttribute('aria-busy', 'true');
        submitButtons.forEach((button) => {
            button.disabled = true;
            button.textContent = 'Salvando…';
        });
    });
    window.addEventListener('pageshow', () => {
        saving = false;
        form.removeAttribute('aria-busy');
        originalButtons.forEach(({ button, html, disabled }) => {
            button.disabled = disabled;
            button.innerHTML = html;
        });
    });

    const panel = form.querySelector('[data-schedule-suggestions]');
    if (!panel) return;
    const fields = Object.fromEntries(['dia_semana', 'repeticao', 'data_inicio', 'horario_inicio', 'horario_termino'].map((name) => [name, form.elements.namedItem(name)]));
    const duration = panel.querySelector('[data-search-duration]');
    const from = panel.querySelector('[data-search-from]');
    const until = panel.querySelector('[data-search-until]');
    const search = panel.querySelector('[data-search-slots]');
    const feedback = panel.querySelector('[data-slots-feedback]');
    const list = panel.querySelector('[data-slots-list]');
    const previousConflict = panel.querySelector('[data-schedule-conflict]');
    const searchLabel = search.innerHTML;
    let sequence = 0;
    let pending = null;
    let applying = false;
    const minutes = (value) => /^\d{2}:\d{2}$/.test(value) ? Number(value.slice(0, 2)) * 60 + Number(value.slice(3, 5)) : NaN;
    const dateLabel = (value) => value.split('-').reverse().join('/');
    function syncDuration() {
        const total = minutes(fields.horario_termino.value) - minutes(fields.horario_inicio.value);
        if (total > 0 && total <= 720) duration.value = String(total);
    }
    function notify(code, message = null) {
        if (window.ClinicToast) window.ClinicToast.show(code, null, message);
    }
    function resetResults() {
        if (applying) return;
        sequence++;
        if (pending) pending.abort();
        pending = null;
        search.disabled = false;
        search.innerHTML = searchLabel;
        panel.removeAttribute('aria-busy');
        list.replaceChildren();
        feedback.textContent = 'Os dados da busca mudaram. Consulte novamente os horários livres.';
    }
    function changeSchedule() {
        if (!applying && previousConflict) previousConflict.hidden = true;
        resetResults();
    }
    Object.values(fields).forEach((field) => {
        field.addEventListener('input', () => {
            if (field === fields.horario_inicio || field === fields.horario_termino) syncDuration();
            changeSchedule();
        });
        field.addEventListener('change', changeSchedule);
    });
    [duration, from, until].forEach((field) => field.addEventListener('input', resetResults));
    syncDuration();
    search.addEventListener('click', async () => {
        if (!fields.dia_semana.value || !fields.repeticao.value || !fields.data_inicio.value || !duration.value || !duration.checkValidity()
            || !from.value || !until.value || minutes(until.value) - minutes(from.value) < Number(duration.value)) {
            feedback.textContent = 'Informe o dia, a repetição, a data inicial, a duração e uma faixa de busca válida.';
            notify('schedule.search_invalid');
            return;
        }
        resetResults();
        const current = sequence;
        const controller = new AbortController();
        pending = controller;
        const timeout = setTimeout(() => controller.abort(), 15000);
        search.disabled = true;
        search.textContent = 'Buscando horários…';
        panel.setAttribute('aria-busy', 'true');
        feedback.textContent = 'Conferindo as datas da repetição e os horários ocupados…';
        const body = new URLSearchParams({
            csrf_token: form.elements.namedItem('csrf_token').value,
            duracao: duration.value, busca_inicio: from.value, busca_fim: until.value
        });
        Object.entries(fields).forEach(([name, field]) => body.set(name, field.value));
        const patient = form.elements.namedItem('id_paciente');
        if (patient && patient.value) body.set('id_paciente', patient.value);
        try {
            const response = await fetch('/pacientes/horarios-livres', {
                method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json' }, body, signal: controller.signal
            });
            const result = await response.json();
            if (current !== sequence) return;
            if (!response.ok || !result.ok) {
                feedback.textContent = response.status === 401 ? 'Sua sessão expirou. Entre novamente para consultar os horários.' : 'Não foi possível consultar os horários. Confira os dados e tente novamente.';
                notify(result.toast_code || 'schedule.suggestions_failed');
                return;
            }
            if (result.motivo === 'domingo') {
                feedback.textContent = 'Não há atendimento aos domingos. Escolha outro dia da semana para buscar horários.';
                return;
            }
            const period = `${dateLabel(result.de)} a ${dateLabel(result.ate)}`;
            const currentMessage = result.atual ? (result.atual.livre ? 'O horário informado está livre nas datas conferidas. ' : (previousConflict && !previousConflict.hidden ? '' : `${result.atual.mensagem || 'O horário informado não tem consultas futuras neste período.'} `)) : '';
            feedback.textContent = currentMessage + (result.sugestoes.length
                ? `Horários livres para a repetição no período de ${period}. Selecione uma opção para preencher o início e o término.`
                : `Nenhum horário livre nessa faixa para todas as datas de ${period}. Amplie a faixa ou ajuste o dia e a repetição.`);
            result.sugestoes.forEach((slot) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn-outline px-4 py-3';
                button.textContent = `${slot.inicio} – ${slot.fim}`;
                const dates = slot.datas.map(dateLabel).join(', ');
                button.title = `Datas conferidas: ${dates}`;
                button.setAttribute('aria-label', `${slot.inicio} às ${slot.fim}. Datas: ${dates}`);
                button.setAttribute('aria-pressed', 'false');
                button.addEventListener('click', () => {
                    applying = true;
                    fields.horario_inicio.value = slot.inicio;
                    fields.horario_termino.value = slot.fim;
                    fields.horario_inicio.dispatchEvent(new Event('input', { bubbles: true }));
                    fields.horario_termino.dispatchEvent(new Event('input', { bubbles: true }));
                    applying = false;
                    if (previousConflict) previousConflict.hidden = true;
                    list.querySelectorAll('button').forEach((item) => {
                        item.setAttribute('aria-pressed', String(item === button));
                        item.classList.toggle('btn-solid', item === button);
                        item.classList.toggle('btn-outline', item !== button);
                    });
                    feedback.textContent = `Horário ${slot.inicio} às ${slot.fim} preenchido. Datas conferidas: ${dates}. Salve o paciente para confirmar.`;
                    notify('schedule.selected');
                });
                list.append(button);
            });
        } catch (error) {
            if (current === sequence) {
                feedback.textContent = 'Não foi possível consultar os horários livres. Tente novamente.';
                notify('schedule.suggestions_failed');
            }
        } finally {
            clearTimeout(timeout);
            if (current === sequence) {
                pending = null;
                search.disabled = false;
                search.innerHTML = searchLabel;
                panel.removeAttribute('aria-busy');
            }
        }
    });
    if (previousConflict) requestAnimationFrame(() => search.click());
})();
