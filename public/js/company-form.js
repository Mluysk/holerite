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

    function escapeHtml(value) {
        if (typeof value !== 'string') {
            return '';
        }

        return value.replace(/[&<>"']/g, function (character) {
            switch (character) {
                case '&':
                    return '&amp;';
                case '<':
                    return '&lt;';
                case '>':
                    return '&gt;';
                case '"':
                    return '&quot;';
                case "'":
                    return '&#39;';
                default:
                    return character;
            }
        });
    }

    function buildPrintDocument(contentHtml, meta) {
        var payload = meta || {};
        var emittedAt = typeof payload.generatedAt === 'string' && payload.generatedAt !== ''
            ? payload.generatedAt
            : new Date().toLocaleString('pt-BR');
        var companyName = typeof payload.companyName === 'string' ? payload.companyName : '';
        var companyDocument = typeof payload.companyDocument === 'string' ? payload.companyDocument : '';

        var headerLines = '';
        if (companyName !== '' || companyDocument !== '') {
            headerLines += '<p class="meta-line">';
            if (companyName !== '') {
                headerLines += '<strong>' + escapeHtml(companyName) + '</strong>';
            }
            if (companyDocument !== '') {
                headerLines += (companyName !== '' ? ' • ' : '') + 'CNPJ: ' + escapeHtml(companyDocument);
            }
            headerLines += '</p>';
        }
        headerLines += '<p class="meta-line">Emitido em ' + escapeHtml(emittedAt) + '</p>';

        return '' +
            '<!doctype html>' +
            '<html lang="pt-BR">' +
            '<head>' +
            '<meta charset="utf-8">' +
            '<title>Auditoria do sistema</title>' +
            '<style>' +
            'body{font-family:"Segoe UI",Arial,sans-serif;margin:24px;color:#111827;background:#ffffff;}' +
            'h1{margin:0 0 8px;font-size:20px;}' +
            '.meta-line{margin:0;font-size:12px;color:#4b5563;}' +
            '.meta-line + .meta-line{margin-top:4px;}' +
            '.table-wrapper{margin-top:20px;}' +
            'table{width:100%;border-collapse:collapse;font-size:12px;}' +
            'thead th{background:#eff6ff;}' +
            'th,td{border:1px solid #cbd5f5;padding:8px;text-align:left;vertical-align:top;}' +
            'tbody tr:nth-child(even){background:#f8fafc;}' +
            '</style>' +
            '</head>' +
            '<body>' +
            '<h1>Auditoria do sistema</h1>' +
            headerLines +
            '<div class="table-wrapper">' + contentHtml + '</div>' +
            '</body>' +
            '</html>';
    }

    function triggerPrint(targetWindow, cleanup) {
        targetWindow.focus();
        targetWindow.print();

        if (typeof cleanup === 'function') {
            var cleanupCalled = false;

            function safeCleanup() {
                if (cleanupCalled) {
                    return;
                }
                cleanupCalled = true;
                cleanup();
            }

            targetWindow.addEventListener('afterprint', safeCleanup, { once: true });
            setTimeout(safeCleanup, 1500);
        }
    }

    function openPrintWindow(contentHtml, meta) {
        var documentHtml = buildPrintDocument(contentHtml, meta);
        var iframe = document.createElement('iframe');
        iframe.setAttribute('aria-hidden', 'true');
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        iframe.style.visibility = 'hidden';

        document.body.appendChild(iframe);

        var iframeWindow = iframe.contentWindow || window;
        var iframeDocument = iframeWindow.document;

        var cleanupIframe = function () {
            setTimeout(function () {
                if (iframe.parentNode) {
                    iframe.parentNode.removeChild(iframe);
                }
            }, 0);
        };

        var handleIframePrint = function () {
            triggerPrint(iframeWindow, cleanupIframe);
        };

        iframeDocument.open();
        iframeDocument.write(documentHtml);
        iframeDocument.close();

        if (iframeDocument.readyState === 'complete') {
            setTimeout(handleIframePrint, 50);
        } else {
            iframeDocument.addEventListener('DOMContentLoaded', function () {
                handleIframePrint();
            }, { once: true });
        }
    }

    onReady(function () {
        var cnpjInput = document.getElementById('document');
        if (cnpjInput) {
            attachMask(cnpjInput);
        }

        var logoInput = document.getElementById('header_logo');
        var logoPreview = document.querySelector('[data-header-logo-preview]');
        var logoDefaultSrc = '';
        var logoObjectUrl = null;
        var canUseObjectUrl = typeof URL !== 'undefined' && typeof URL.createObjectURL === 'function' && typeof URL.revokeObjectURL === 'function';

        if (logoPreview instanceof HTMLImageElement) {
            logoDefaultSrc = logoPreview.getAttribute('data-default-src') || logoPreview.getAttribute('src') || '';
        }

        function revokeLogoUrl() {
            if (logoObjectUrl && canUseObjectUrl) {
                URL.revokeObjectURL(logoObjectUrl);
                logoObjectUrl = null;
            }
        }

        if (logoInput instanceof HTMLInputElement && logoPreview instanceof HTMLImageElement) {
            logoInput.addEventListener('change', function () {
                revokeLogoUrl();

                if (logoInput.files && logoInput.files[0]) {
                    if (canUseObjectUrl) {
                        logoObjectUrl = URL.createObjectURL(logoInput.files[0]);
                        logoPreview.src = logoObjectUrl;
                    } else if (typeof FileReader !== 'undefined') {
                        var reader = new FileReader();
                        reader.addEventListener('load', function () {
                            if (typeof reader.result === 'string') {
                                logoPreview.src = reader.result;
                            }
                        });
                        reader.readAsDataURL(logoInput.files[0]);
                    }
                } else if (logoDefaultSrc) {
                    logoPreview.src = logoDefaultSrc;
                }
            });
        }

        window.addEventListener('pagehide', revokeLogoUrl);

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

                openPrintWindow(target.innerHTML, {
                    companyName: target.getAttribute('data-company-name') || '',
                    companyDocument: target.getAttribute('data-company-document') || '',
                    generatedAt: target.getAttribute('data-generated-at') || ''
                });
            });
        }

        var allowanceContainer = document.querySelector('[data-allowance-list]');
        var addAllowanceButton = document.querySelector('[data-add-allowance]');

        function buildRemoveButton() {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'button button-secondary';
            button.setAttribute('data-remove-allowance', '');

            var icon = document.createElement('i');
            icon.className = 'bi bi-x-circle';
            icon.setAttribute('aria-hidden', 'true');
            button.appendChild(icon);

            var label = document.createElement('span');
            label.textContent = ' Remover';
            button.appendChild(label);

            return button;
        }

        function addAllowanceRow(value) {
            if (!(allowanceContainer instanceof HTMLElement)) {
                return;
            }

            var row = document.createElement('div');
            row.className = 'allowance-settings__row';
            row.setAttribute('data-allowance-row', '');

            var input = document.createElement('input');
            input.type = 'text';
            input.name = 'manual_allowances[]';
            input.placeholder = 'Ex.: Hora extra 50%';
            input.value = typeof value === 'string' ? value : '';

            row.appendChild(input);
            row.appendChild(buildRemoveButton());

            allowanceContainer.appendChild(row);

            window.requestAnimationFrame(function () {
                input.focus();
            });
        }

        function ensureAllowanceRows() {
            if (!(allowanceContainer instanceof HTMLElement)) {
                return;
            }

            var rows = allowanceContainer.querySelectorAll('[data-allowance-row]');
            if (rows.length === 0) {
                addAllowanceRow('');
            }
        }

        if (allowanceContainer instanceof HTMLElement) {
            allowanceContainer.addEventListener('click', function (event) {
                var target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                if (target.hasAttribute('data-remove-allowance')) {
                    var row = target.closest('[data-allowance-row]');
                    if (row && row.parentNode === allowanceContainer) {
                        allowanceContainer.removeChild(row);
                        ensureAllowanceRows();
                    }
                    return;
                }

                var button = target.closest('[data-remove-allowance]');
                if (button instanceof HTMLElement) {
                    var rowNode = button.closest('[data-allowance-row]');
                    if (rowNode && rowNode.parentNode === allowanceContainer) {
                        allowanceContainer.removeChild(rowNode);
                        ensureAllowanceRows();
                    }
                }
            });

            if (addAllowanceButton instanceof HTMLElement) {
                addAllowanceButton.addEventListener('click', function () {
                    addAllowanceRow('');
                });
            }

            ensureAllowanceRows();
        }
    });
})();
