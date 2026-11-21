(() => {
    const catalogElement = document.getElementById('clinic-toast-catalog');
    if (!catalogElement || window.ClinicToast) return;
    let catalog;
    try {
        catalog = JSON.parse(catalogElement.textContent);
    } catch {
        return;
    }

    const labels = { success: 'Sucesso', info: 'Informação', warning: 'Atenção', error: 'Erro' };
    const durations = { success: 4000, info: 5000, warning: 6000, error: 0 };
    const pending = [];
    const active = new Map();
    let visible = 0;

    function fallback(message, type, source) {
        if (!source) {
            const container = document.getElementById('clinic-toast-fallback');
            source = Array.from(container.children).find((element) => element.dataset.fallbackKey === type + ':' + message);
            if (!source) {
                source = document.createElement('p');
                source.dataset.fallbackKey = type + ':' + message;
                source.setAttribute('role', type === 'error' ? 'alert' : 'status');
                container.appendChild(source);
            }
        }
        source.textContent = message;
        source.hidden = false;
        return source;
    }

    function close(item) {
        if (item.closing) return;
        item.closing = true;
        clearTimeout(item.timer);
        if (item.toast.toastElement.contains(document.activeElement) && item.previousFocus?.isConnected) {
            item.previousFocus.focus();
        }
        item.toast.hideToast();
    }

    function place(item) {
        const dialog = document.querySelector('dialog[open]');
        const root = dialog || document.body;
        const element = item.toast.toastElement;
        if (element.parentNode !== root) {
            root.appendChild(element);
            window.Toastify.reposition();
        }
        if (dialog && item.layer !== dialog) {
            item.layer = dialog;
            dialog.addEventListener('close', () => {
                item.layer = null;
                if (element.parentNode === dialog && !item.closing) {
                    document.body.appendChild(element);
                    window.Toastify.reposition();
                }
            }, { once: true });
        }
    }

    function drain() {
        while (visible < 3 && pending.length) {
            const item = pending.shift();
            const content = document.createElement('div');
            content.className = 'clinic-toast__content';
            const text = document.createElement('div');
            text.className = 'clinic-toast__text';
            const title = document.createElement('strong');
            title.className = 'clinic-toast__title';
            title.textContent = labels[item.type];
            const message = document.createElement('span');
            message.textContent = item.message;
            text.append(title, message);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'clinic-toast__close';
            button.setAttribute('aria-label', 'Fechar notificação');
            button.textContent = '×';
            button.addEventListener('click', () => close(item));
            content.append(text, button);
            const dialog = document.querySelector('dialog[open]');
            item.previousFocus = document.activeElement;

            try {
                item.toast = window.Toastify({
                    node: content,
                    duration: -1,
                    selector: dialog || document.body,
                    className: 'clinic-toast clinic-toast--' + item.type,
                    gravity: 'top',
                    position: 'right',
                    offset: { x: 0, y: 0 },
                    ariaLive: item.type === 'error' ? 'assertive' : 'polite',
                    escapeMarkup: true,
                    callback() {
                        clearTimeout(item.timer);
                        active.delete(item.key);
                        visible--;
                        drain();
                    }
                }).showToast();
            } catch {
                item.source.hidden = false;
                active.delete(item.key);
                continue;
            }

            visible++;
            item.shown = true;
            item.source.hidden = true;
            item.remaining = durations[item.type];
            item.paused = new Set();

            function resume(reason) {
                item.paused.delete(reason);
                if (!item.remaining || item.paused.size || item.closing) return;
                item.started = Date.now();
                clearTimeout(item.timer);
                item.timer = setTimeout(() => close(item), item.remaining);
            }

            function pause(reason) {
                if (!item.paused.size && item.timer) {
                    item.remaining = Math.max(1, item.remaining - (Date.now() - item.started));
                    clearTimeout(item.timer);
                }
                item.paused.add(reason);
            }

            const element = item.toast.toastElement;
            element.addEventListener('mouseenter', () => pause('hover'));
            element.addEventListener('mouseleave', () => resume('hover'));
            element.addEventListener('focusin', () => pause('focus'));
            element.addEventListener('focusout', (event) => {
                if (!element.contains(event.relatedTarget)) resume('focus');
            });
            place(item);
            resume('start');
        }
    }

    function display(entry, source) {
        if (!entry || !labels[entry.type] || typeof entry.message !== 'string') return false;
        active.forEach((item) => {
            if (item.shown && !item.closing) place(item);
        });
        const key = entry.type + ':' + entry.message;
        const existing = active.get(key);
        if (existing) {
            if (source) source.hidden = Boolean(existing.shown);
            return true;
        }
        const providedSource = Boolean(source);
        source = fallback(entry.message, entry.type, source);
        if (typeof window.Toastify !== 'function') return false;
        if (!providedSource) source.hidden = true;
        const item = { ...entry, key, source };
        active.set(key, item);
        pending.push(item);
        drain();
        return active.has(key);
    }

    window.ClinicToast = Object.freeze({
        show(code, source = null, message = null) {
            const entry = Object.hasOwn(catalog, code) ? catalog[code] : null;
            return display(entry && typeof message === 'string' ? { ...entry, message } : entry, source);
        }
    });

    document.querySelectorAll('[data-toast-type]').forEach((source) => {
        if (source.hidden) return;
        const code = source.dataset.toastCode;
        const entry = code && Object.hasOwn(catalog, code)
            ? catalog[code]
            : { type: source.dataset.toastType, message: source.textContent.trim() };
        display(entry, source);
    });

    document.querySelectorAll('form[data-toast-validate]').forEach((form) => {
        form.noValidate = true;
        form.addEventListener('submit', (event) => {
            const confirmation = form.elements.namedItem('confirmar_senha');
            const password = form.elements.namedItem('senha');
            if (confirmation) {
                confirmation.setCustomValidity(confirmation.value && confirmation.value !== password.value ? 'As senhas não coincidem.' : '');
            }
            const fields = Array.from(form.elements).filter((field) => field.willValidate);
            const invalid = fields.filter((field) => !field.validity.valid);
            fields.forEach((field) => {
                field.classList.toggle('is-invalid', invalid.includes(field));
                if (invalid.includes(field)) field.setAttribute('aria-invalid', 'true');
                else field.removeAttribute('aria-invalid');
            });
            if (!invalid.length) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            const first = invalid[0];
            first.focus();
            let code = 'validation.fields';
            if (first.validity.valueMissing) code = 'validation.required';
            else if (first.type === 'email') code = 'validation.email';
            else if (first.name === 'codigo') code = 'validation.code';
            else if (first.type === 'password' && first.validity.tooShort) code = 'validation.password_length';
            else if (first === confirmation && first.validity.customError) code = 'validation.password_match';
            window.ClinicToast.show(code);
        }, true);
        form.addEventListener('input', (event) => {
            const field = event.target;
            if (!field.matches('input, select, textarea')) return;
            field.classList.remove('is-invalid');
            field.removeAttribute('aria-invalid');
            const confirmation = form.elements.namedItem('confirmar_senha');
            if (confirmation) confirmation.setCustomValidity('');
        });
    });
})();
