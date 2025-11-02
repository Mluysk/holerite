(function () {
    'use strict';

    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }

    function formatCnpj(value) {
        const digits = value.replace(/\D+/g, '').slice(0, 14);
        const parts = [];

        if (digits.length > 0) {
            parts.push(digits.slice(0, 2));
        }
        if (digits.length >= 3) {
            parts.push(digits.slice(2, 5));
        }
        if (digits.length >= 6) {
            parts.push(digits.slice(5, 8));
        }

        let formatted = '';

        if (parts.length > 0) {
            formatted = parts[0];
        }
        if (parts.length > 1) {
            formatted += '.' + parts[1];
        }
        if (parts.length > 2) {
            formatted += '.' + parts[2];
        }

        if (digits.length >= 9) {
            formatted += '/' + digits.slice(8, 12);
        }
        if (digits.length >= 13) {
            formatted += '-' + digits.slice(12, 14);
        }

        return formatted;
    }

    function getDigitsBeforeCaret(value, caretPosition) {
        let digits = 0;
        const limit = Math.min(value.length, caretPosition);
        for (let index = 0; index < limit; index += 1) {
            if (/\d/.test(value.charAt(index))) {
                digits += 1;
            }
        }
        return digits;
    }

    function resolveCaretPosition(formattedValue, digitsBeforeCaret) {
        if (digitsBeforeCaret <= 0) {
            return 0;
        }

        let digitsSeen = 0;
        for (let index = 0; index < formattedValue.length; index += 1) {
            if (/\d/.test(formattedValue.charAt(index))) {
                digitsSeen += 1;
                if (digitsSeen === digitsBeforeCaret) {
                    return index + 1;
                }
            }
        }

        return formattedValue.length;
    }

    function attachMask(input) {
        function updateValue() {
            const selectionStart = input.selectionStart ?? input.value.length;
            const digitsBeforeCaret = getDigitsBeforeCaret(input.value, selectionStart);
            const formatted = formatCnpj(input.value);
            input.value = formatted;
            const nextPosition = resolveCaretPosition(formatted, digitsBeforeCaret);
            window.requestAnimationFrame(function () {
                input.setSelectionRange(nextPosition, nextPosition);
            });
        }

        input.addEventListener('input', updateValue);
        input.addEventListener('blur', function () {
            input.value = formatCnpj(input.value);
        });

        updateValue();
    }

    onReady(function () {
        var cnpjInput = document.getElementById('document');
        if (!cnpjInput) {
            return;
        }

        attachMask(cnpjInput);
    });
})();
