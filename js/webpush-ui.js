'use strict';

(() => {
    const triggers = document.querySelectorAll('[data-open-webpush]');
    if (!triggers.length || document.getElementById('webpush-modal')) return;

    document.body.insertAdjacentHTML('beforeend', `
        <dialog id="webpush-modal" class="clinic-modal" aria-labelledby="webpush-title" aria-describedby="webpush-description">
            <div class="p-7">
                <div class="d-flex align-items-center justify-content-between gap-4 mb-5">
                    <h2 id="webpush-title" class="text-lg fw-semibold m-0">Lembretes de consultas</h2>
                    <button type="button" class="btn-text" data-webpush-close aria-label="Fechar notificações">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <p id="webpush-description" class="text-sm">Receba dois avisos por consulta: um antes do horário e outro no horário marcado, mesmo com a tela fechada. A entrega depende da conexão e das configurações do dispositivo.</p>
                <p class="text-sm"><strong>Privacidade:</strong> a notificação mostra o nome do paciente e o horário da consulta, inclusive na tela bloqueada.</p>
                <p class="text-sm">Os lembretes continuam após sair da conta. Em dispositivo compartilhado, desative-os aqui antes de sair. Estes controles afetam somente este dispositivo.</p>
                <p id="webpush-status" class="text-sm" role="status" aria-live="polite" aria-atomic="true">Preparando lembretes…</p>
                <div class="d-flex flex-wrap gap-3" aria-label="Controles de lembretes">
                    <button type="button" class="btn-primary" data-webpush-enable disabled>Ativar lembretes neste dispositivo</button>
                    <button type="button" class="btn-outline" data-webpush-disable disabled>Desativar neste dispositivo</button>
                    <button type="button" class="btn-outline" data-webpush-retry hidden>Tentar novamente</button>
                </div>
                <p class="text-xs mt-5 mb-0">No iPhone/iPad compatível, use o site adicionado à Tela de Início. Se as opções não estiverem disponíveis, confira o suporte do navegador e a conexão HTTPS.</p>
            </div>
        </dialog>`);

    const modal = document.getElementById('webpush-modal');
    const status = document.getElementById('webpush-status');
    const enable = modal.querySelector('[data-webpush-enable]');
    const disable = modal.querySelector('[data-webpush-disable]');
    const retry = modal.querySelector('[data-webpush-retry]');
    let push;
    let ready = false;
    let busy = false;

    triggers.forEach((trigger) => trigger.addEventListener('click', (event) => {
        event.preventDefault();
        modal.showModal();
    }));
    modal.querySelector('[data-webpush-close]').addEventListener('click', () => modal.close());
    modal.addEventListener('click', (event) => {
        const bounds = modal.getBoundingClientRect();
        if (event.target === modal && (event.clientX < bounds.left || event.clientX > bounds.right
            || event.clientY < bounds.top || event.clientY > bounds.bottom)) modal.close();
    });

    function lock() {
        enable.disabled = disable.disabled = retry.disabled = true;
        status.setAttribute('aria-busy', 'true');
    }

    function render(state, error) {
        enable.disabled = busy || !ready || !state.supported || !state.enabled || state.permission === 'denied';
        disable.disabled = busy || !ready || !state.subscribed;
        retry.disabled = busy;
        retry.hidden = !error;
        status.setAttribute('aria-busy', String(busy));
        enable.textContent = state.subscribed ? 'Renovar ativação neste dispositivo' : 'Ativar lembretes neste dispositivo';
        const message = !state.supported ? 'Navegador incompatível ou conexão sem HTTPS.'
            : !state.enabled ? 'Os lembretes ainda não foram habilitados no servidor.'
            : state.subscribed ? 'Dispositivo inscrito. Os lembretes continuam após sair da conta.'
            : 'Lembretes não ativados neste dispositivo.';
        const permissionHelp = state.permission === 'denied'
            ? ' As notificações estão bloqueadas. Revise as permissões deste site nas configurações do navegador e tente novamente.' : '';
        status.textContent = (error ? error.message : message) + permissionHelp;
    }

    async function finish(error) {
        busy = false;
        try {
            render(await push.state(), error);
        } catch {
            ready = false;
            lock();
            retry.hidden = false;
            retry.disabled = false;
            status.setAttribute('aria-busy', 'false');
            status.textContent = 'Não foi possível consultar os lembretes. Tente novamente.';
        }
    }

    async function initialize() {
        if (busy) return;
        busy = true;
        ready = false;
        lock();
        status.textContent = 'Preparando lembretes…';
        let error;
        try {
            if (!window.ClinicWebPush) {
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = '/js/webpush.js';
                    script.onload = resolve;
                    script.onerror = () => {
                        script.remove();
                        reject(new Error('Não foi possível carregar os lembretes. Verifique a conexão e tente novamente.'));
                    };
                    document.head.appendChild(script);
                });
            }
            push = window.ClinicWebPush;
            await push.init();
            ready = true;
            // Não pede permissão; renova somente inscrições já existentes.
            await push.sync();
        } catch (failure) {
            error = failure;
        }
        if (push) await finish(error);
        else {
            busy = false;
            retry.hidden = false;
            retry.disabled = false;
            status.setAttribute('aria-busy', 'false');
            status.textContent = error.message;
        }
    }

    enable.addEventListener('click', () => {
        if (!ready || busy) return;
        // Chamar diretamente no clique, antes de qualquer await (Safari/iOS).
        const operation = push.enable();
        busy = true;
        lock();
        status.textContent = 'Ativando lembretes…';
        operation.then(() => finish(), (error) => finish(error));
    });
    disable.addEventListener('click', () => {
        if (!ready || busy) return;
        const operation = push.disable();
        busy = true;
        lock();
        status.textContent = 'Desativando lembretes…';
        operation.then(() => finish(), (error) => finish(error));
    });
    retry.addEventListener('click', initialize);
    void initialize();
})();
