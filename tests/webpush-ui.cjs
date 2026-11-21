'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('js/webpush-ui.js', 'utf8');

class Element {
    constructor() { this.handlers = {}; this.disabled = false; this.hidden = false; this.textContent = ''; }
    addEventListener(name, handler) { this.handlers[name] = handler; }
    setAttribute(name, value) { this[name] = value; }
    click() { return this.handlers.click({ preventDefault() {} }); }
    showModal() { this.open = true; }
    close() { this.open = false; }
}
const tick = () => new Promise((resolve) => setImmediate(resolve));

async function setup(options = {}) {
    const elements = Object.fromEntries(['trigger', 'modal', 'status', 'enable', 'disable', 'retry', 'close'].map((key) => [key, new Element()]));
    const calls = [];
    const state = { supported: true, enabled: true, permission: 'default', subscribed: false, ...options.state };
    let finishInit;
    let failSync = options.failSync;
    const push = {
        init() { calls.push('init'); return new Promise((resolve, reject) => { finishInit = options.failInit ? () => reject(new Error('Configuração indisponível')) : resolve; }); },
        async sync() { calls.push('sync'); if (failSync) throw new Error('Falha de rede'); },
        async state() { return { ...state }; },
        enable() { calls.push('enable'); state.subscribed = true; return Promise.resolve(); },
        disable() { calls.push('disable'); if (options.failDisable) return Promise.reject(new Error('Falha ao desativar')); state.subscribed = false; return Promise.resolve(); },
    };
    elements.modal.querySelector = (selector) => elements[selector.match(/webpush-(\w+)/)[1]];
    let inserted = false;
    const document = {
        querySelectorAll: () => options.public ? [] : [elements.trigger],
        getElementById(id) { return inserted ? elements[id === 'webpush-modal' ? 'modal' : 'status'] : null; },
        body: { insertAdjacentHTML() { inserted = true; elements.enable.disabled = elements.disable.disabled = true; } },
    };
    vm.runInNewContext(source, { window: { ClinicWebPush: push }, document });
    return { elements, calls, state, resolve() { finishInit(); }, recover() { failSync = false; }, inserted };
}

(async () => {
    const ui = await setup();
    assert.deepEqual(ui.calls, ['init']);
    assert.equal(ui.elements.enable.disabled, true);
    ui.elements.trigger.click();
    assert.equal(ui.elements.modal.open, true);
    ui.resolve(); await tick();
    assert.deepEqual(ui.calls, ['init', 'sync']);
    assert.equal(ui.elements.enable.disabled, false);
    assert.equal(ui.elements.disable.disabled, true);
    ui.elements.enable.click();
    assert.equal(ui.calls.at(-1), 'enable', 'Ativação deve ser chamada sincronamente no clique');
    assert.equal(ui.elements.enable.disabled, true);
    assert.equal(ui.elements.disable.disabled, true);
    await tick();
    assert.equal(ui.elements.disable.disabled, false);
    assert.match(ui.elements.status.textContent, /continuam após sair/);
    ui.elements.disable.click(); await tick();
    assert.equal(ui.state.subscribed, false);
    ui.elements.close.click();
    assert.equal(ui.elements.modal.open, false);

    for (const state of [{ supported: false }, { enabled: false }, { permission: 'denied' }]) {
        const ui = await setup({ state }); ui.resolve(); await tick();
        assert.equal(ui.elements.enable.disabled, true);
        assert.equal(ui.calls.includes('enable'), false);
        if (state.permission) assert.match(ui.elements.status.textContent, /permissões deste site/);
    }
    const initFailure = await setup({ failInit: true });
    initFailure.resolve(); await tick();
    assert.equal(initFailure.elements.enable.disabled, true, 'Não ativar antes de init bem-sucedido');
    assert.equal(initFailure.elements.retry.hidden, false);
    assert.match(initFailure.elements.status.textContent, /Configuração indisponível/);

    const disabled = await setup({ state: { enabled: false, subscribed: true } });
    disabled.resolve(); await tick();
    assert.equal(disabled.elements.disable.disabled, false, 'Remoção disponível com servidor desabilitado');

    const failure = await setup({ failSync: true, state: { subscribed: true }, failDisable: true });
    failure.resolve(); await tick();
    assert.match(failure.elements.status.textContent, /Falha de rede/);
    assert.equal(failure.elements.enable.disabled, false, 'Permitir renovar após erro de sincronização');
    assert.equal(failure.elements.retry.hidden, false);
    failure.elements.disable.click(); await tick();
    assert.match(failure.elements.status.textContent, /Falha ao desativar/);
    assert.equal(failure.elements.disable.disabled, false, 'Permitir nova tentativa de remoção');
    failure.recover();
    failure.elements.retry.click(); failure.resolve(); await tick();
    assert.equal(failure.elements.retry.hidden, true);
    assert.equal((await setup({ public: true })).inserted, false, 'Sem interface em páginas públicas');
    console.log('Web Push UI: todos os testes passaram.');
})().catch((error) => { console.error(error); process.exitCode = 1; });
