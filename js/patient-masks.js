(() => {
    function digits(value) {
        return value.replace(/\D/g, '');
    }

    function format(value, type) {
        const number = digits(value).slice(0, 11);

        if (type === 'cpf') {
            let result = number.slice(0, 3);
            if (number.length > 3) result += '.' + number.slice(3, 6);
            if (number.length > 6) result += '.' + number.slice(6, 9);
            if (number.length > 9) result += '-' + number.slice(9);
            return result;
        }

        if (!number) return '';
        let result = '(' + number.slice(0, 2);
        if (number.length > 2) result += ') ';
        const prefixLength = number.length > 10 ? 7 : 6;
        result += number.slice(2, prefixLength);
        if (number.length > prefixLength) result += '-' + number.slice(prefixLength);
        return result;
    }

    function position(value, count) {
        if (count === 0) return 0;
        let found = 0;
        for (let index = 0; index < value.length; index++) {
            if (/\d/.test(value[index]) && ++found === count) return index + 1;
        }
        return value.length;
    }

    document.querySelectorAll('[data-mask]').forEach((field) => {
        function apply() {
            const start = digits(field.value.slice(0, field.selectionStart ?? field.value.length)).length;
            const end = digits(field.value.slice(0, field.selectionEnd ?? field.value.length)).length;
            const direction = field.selectionDirection || 'none';
            field.value = format(field.value, field.dataset.mask);
            if (document.activeElement === field) {
                field.setSelectionRange(position(field.value, start), position(field.value, end), direction);
            }
        }

        field.addEventListener('input', apply);
        field.addEventListener('change', apply);

        field.addEventListener('paste', (event) => {
            if (!event.clipboardData) return;
            event.preventDefault();
            const number = digits(field.value);
            const start = digits(field.value.slice(0, field.selectionStart)).length;
            const end = digits(field.value.slice(0, field.selectionEnd)).length;
            const pasted = digits(event.clipboardData.getData('text'));
            field.value = format(number.slice(0, start) + pasted + number.slice(end), field.dataset.mask);
            const cursor = position(field.value, start + pasted.length);
            field.setSelectionRange(cursor, cursor);
            field.dispatchEvent(new Event('input', { bubbles: true }));
        });

        field.addEventListener('beforeinput', (event) => {
            const backward = event.inputType === 'deleteContentBackward';
            if (!backward && event.inputType !== 'deleteContentForward') return;
            if (field.selectionStart !== field.selectionEnd) return;
            const cursor = field.selectionStart;
            const character = field.value[backward ? cursor - 1 : cursor];
            if (!character || /\d/.test(character)) return;

            const number = digits(field.value);
            const before = digits(field.value.slice(0, cursor)).length;
            const index = backward ? before - 1 : before;
            if (index < 0 || index >= number.length) return;
            event.preventDefault();
            field.value = format(number.slice(0, index) + number.slice(index + 1), field.dataset.mask);
            const nextCursor = position(field.value, backward ? before - 1 : before);
            field.setSelectionRange(nextCursor, nextCursor);
            field.dispatchEvent(new Event('input', { bubbles: true }));
        });

        apply();
    });
})();
