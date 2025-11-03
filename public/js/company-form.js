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

    function openPrintWindow(contentHtml) {
        var printWindow = window.open('', '_blank', 'noopener');
        if (!printWindow) {
            return;
        }

        var now = new Date();
        var emittedAt = now.toLocaleString('pt-BR');

        var documentHtml = '' +
            '<!doctype html>' +
            '<html lang="pt-BR">' +
            '<head>' +
            '<meta charset="utf-8">' +
            '<title>Auditoria do sistema</title>' +
            '<style>' +
            'body{font-family:"Segoe UI",Arial,sans-serif;margin:24px;color:#111827;}' +
            'h1{margin:0 0 12px;font-size:20px;}' +
            'p.meta{margin:0 0 20px;font-size:12px;color:#6b7280;}' +
            'table{width:100%;border-collapse:collapse;font-size:12px;}' +
            'thead th{background:#eff6ff;}' +
            'th,td{border:1px solid #cbd5f5;padding:8px;text-align:left;}' +
            'tbody tr:nth-child(even){background:#f8fafc;}' +
            '</style>' +
            '</head>' +
            '<body>' +
            '<h1>Auditoria do sistema</h1>' +
            '<p class="meta">Emitido em ' + emittedAt + '</p>' +
            contentHtml +
            '</body>' +
            '</html>';

        printWindow.document.open();
        printWindow.document.write(documentHtml);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    }

    onReady(function () {
        var cnpjInput = document.getElementById('document');
        if (cnpjInput) {
            attachMask(cnpjInput);
        }

        var printButton = document.querySelector('[data-print-audit]');
        if (printButton) {
            printButton.addEventListener('click', function () {
                var targetId = printButton.getAttribute('data-print-target');
                if (!targetId) {
                    return;
                }

                var target = document.getElementById(targetId);
                if (!target) {
                    return;
                }

                openPrintWindow(target.innerHTML);
            });
        }
    });
})();
