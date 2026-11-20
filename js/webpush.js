'use strict';

window.ClinicWebPush = (() => {
    let config = null;
    let registration = null;
    let busy = false;

    function supported() {
        return window.isSecureContext && 'serviceWorker' in navigator
            && 'PushManager' in window && 'Notification' in window;
    }

    async function request(path, body) {
        const options = { credentials: 'same-origin', cache: 'no-store', redirect: 'error' };
        if (body !== undefined) {
            options.method = 'POST';
            options.headers = { 'Content-Type': 'application/json', 'X-CSRF-Token': config.csrfToken };
            options.body = JSON.stringify(body);
        }
        const response = await fetch(`/notificacoes/${path}`, options);
        const data = await response.json();
        if (!response.ok) {
            const error = new Error(data.error || 'Não foi possível atualizar os lembretes.');
            error.status = response.status;
            throw error;
        }
        return data;
    }

    async function setup() {
        config = await request('config');
        registration = supported() ? await navigator.serviceWorker.getRegistration('/') : null;
        if (supported() && config.enabled) {
            registration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
            await new Promise((resolve, reject) => {
                const worker = registration.active || registration.installing || registration.waiting;
                if (!worker) return reject(new Error('Service worker indisponível.'));
                if (worker.state === 'activated') return resolve();
                const timeout = setTimeout(() => reject(new Error('Tempo de ativação dos lembretes esgotado.')), 15000);
                worker.addEventListener('statechange', () => {
                    if (worker.state === 'activated') { clearTimeout(timeout); resolve(); }
                    if (worker.state === 'redundant') { clearTimeout(timeout); reject(new Error('Falha ao ativar os lembretes.')); }
                });
            });
        }
        return state();
    }

    async function state() {
        const subscription = registration ? await registration.pushManager.getSubscription() : null;
        return {
            supported: supported(), enabled: Boolean(config?.enabled),
            permission: 'Notification' in window ? Notification.permission : 'unsupported',
            subscribed: Boolean(subscription),
        };
    }

    function keyBytes(key) {
        const padded = key + '='.repeat((4 - key.length % 4) % 4);
        return Uint8Array.from(atob(padded.replace(/-/g, '+').replace(/_/g, '/')), (char) => char.charCodeAt(0));
    }

    async function sync() {
        if (!config) throw new Error('Inicialize os lembretes primeiro.');
        if (!config.enabled || !registration) return state();
        const subscription = await registration.pushManager.getSubscription();
        if (subscription && Notification.permission === 'granted') {
            const existingKey = subscription.options?.applicationServerKey;
            if (existingKey && Array.from(new Uint8Array(existingKey)).join(',') !== Array.from(keyBytes(config.publicKey)).join(',')) {
                throw new Error('As chaves dos lembretes mudaram. Ative os lembretes novamente neste dispositivo.');
            }
            await request('salvar-subscription', subscription.toJSON());
        }
        return state();
    }

    async function enable() {
        if (!supported() || !config?.enabled || !registration) throw new Error('Lembretes indisponíveis neste dispositivo.');
        if (busy) throw new Error('Aguarde a operação atual.');
        busy = true;
        try {
            // Deve ocorrer antes de qualquer await para preservar a ação do usuário no Safari.
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') throw new Error('Permissão de notificações não concedida.');
            let subscription = await registration.pushManager.getSubscription();
            if (subscription) {
                const currentKey = subscription.options?.applicationServerKey;
                if (currentKey && Array.from(new Uint8Array(currentKey)).join(',') !== Array.from(keyBytes(config.publicKey)).join(',')) {
                    await request('remover-subscription', { endpoint: subscription.endpoint });
                    if (!await subscription.unsubscribe()) throw new Error('Não foi possível renovar os lembretes.');
                    subscription = null;
                }
            }
            let created = false;
            if (!subscription) {
                subscription = await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyBytes(config.publicKey) });
                created = true;
            }
            try {
                await request('salvar-subscription', subscription.toJSON());
            } catch (error) {
                if (created) await subscription.unsubscribe().catch(() => false);
                throw error;
            }
            return state();
        } finally { busy = false; }
    }

    async function disable() {
        if (!config) throw new Error('Inicialize os lembretes primeiro.');
        if (busy) throw new Error('Aguarde a operação atual.');
        busy = true;
        try {
            // Permite remover a inscrição mesmo com WEBPUSH_ENABLED=false.
            const worker = registration || (supported() ? await navigator.serviceWorker.getRegistration('/') : null);
            const subscription = worker ? await worker.pushManager.getSubscription() : null;
            if (subscription) {
                await request('remover-subscription', { endpoint: subscription.endpoint });
                if (!await subscription.unsubscribe()) throw new Error('O servidor desativou os lembretes; tente remover a inscrição local novamente.');
            }
            return state();
        } finally { busy = false; }
    }

    return { init: setup, state, enable, sync, disable };
})();
