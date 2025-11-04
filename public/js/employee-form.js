(function () {
    'use strict';

    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }

    function formatCpf(value) {
        const digits = value.replace(/\D+/g, '').slice(0, 11);
        const parts = [];

        if (digits.length > 0) {
            parts.push(digits.slice(0, 3));
        }
        if (digits.length >= 4) {
            parts.push(digits.slice(3, 6));
        }
        if (digits.length >= 7) {
            parts.push(digits.slice(6, 9));
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

        if (digits.length >= 10) {
            formatted += '-' + digits.slice(9, 11);
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
            const formatted = formatCpf(input.value);
            input.value = formatted;
            const nextPosition = resolveCaretPosition(formatted, digitsBeforeCaret);
            window.requestAnimationFrame(function () {
                input.setSelectionRange(nextPosition, nextPosition);
            });
        }

        input.addEventListener('input', updateValue);
        input.addEventListener('blur', function () {
            input.value = formatCpf(input.value);
        });

        updateValue();
    }

    onReady(function () {
        var cpfInput = document.getElementById('cpf');
        if (!cpfInput) {
            return;
        }

        attachMask(cpfInput);
    });
})();
