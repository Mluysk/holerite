(function () {
    'use strict';

    const formatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

    function clamp(value, min, max) {
        const numeric = Number.parseFloat(String(value));
        if (Number.isNaN(numeric)) {
            return min;
        }
        return Math.min(Math.max(numeric, min), max);
    }

    function getEmployeeBaseSalary() {
        const select = document.getElementById('employee_id');
        if (!(select instanceof HTMLSelectElement)) {
            return 0;
        }

        const option = select.options[select.selectedIndex];
        return option ? Number.parseFloat(option.dataset.salary || '0') || 0 : 0;
    }

    function getThirteenthInstallment() {
        const select = document.getElementById('thirteenth_installment');
        if (!(select instanceof HTMLSelectElement)) {
            return 'second';
        }

        return select.value === 'first' ? 'first' : 'second';
    }

    function getReferenceMonth() {
        const input = document.getElementById('reference_month');
        if (!(input instanceof HTMLInputElement)) {
            return '';
        }

        return input.value || '';
    }

    function resolveThirteenthPaymentDate(referenceMonth, installment) {
        if (typeof referenceMonth !== 'string' || !/^\d{4}-\d{2}$/.test(referenceMonth)) {
            return '';
        }

        const day = installment === 'first' ? '05' : '20';
        return `${referenceMonth}-${day}`;
    }

    function calculateBaseSalary(type) {
        const base = getEmployeeBaseSalary();

        if (type === 'vacation') {
            const vacationDays = document.getElementById('vacation_days');
            const days = vacationDays ? clamp(vacationDays.value, 1, 30) : 30;
            return base * (days / 30);
        }

        if (type === 'termination') {
            const workedDays = document.getElementById('worked_days');
            const days = workedDays ? clamp(workedDays.value, 0, 30) : 30;
            return base * (days / 30);
        }

        if (type === 'thirteenth') {
            const thirteenthMonthsInput = document.getElementById('thirteenth_months_input');
            const months = thirteenthMonthsInput ? clamp(thirteenthMonthsInput.value, 1, 12) : 1;
            const total = base * (months / 12);
            return total / 2;
        }

        return base;
    }

    function automaticAllowances(type, baseSalary) {
        const items = [];

        if (type === 'vacation') {
            const vacationBonus = baseSalary / 3;
            if (vacationBonus > 0) {
                items.push({ label: '1/3 Constitucional de Férias', amount: vacationBonus });
            }
        }

        if (type === 'termination') {
            const justCauseCheckbox = document.getElementById('just_cause');
            const monthsInput = document.getElementById('thirteenth_months');
            const referenceBase = getEmployeeBaseSalary();
            const justCause = justCauseCheckbox instanceof HTMLInputElement ? justCauseCheckbox.checked : false;
            const months = monthsInput ? clamp(monthsInput.value, 0, 12) : 12;

            if (!justCause && months > 0) {
                const thirteenth = referenceBase * (months / 12);
                if (thirteenth > 0) {
                    items.push({ label: '13º salário proporcional', amount: thirteenth });
                }
            }
        }

        return items;
    }

    function automaticThirteenth(type) {
        if (type === 'regular') {
            return getEmployeeBaseSalary() / 12;
        }

        if (type === 'thirteenth') {
            const monthsInput = document.getElementById('thirteenth_months_input');
            const months = monthsInput ? clamp(monthsInput.value, 1, 12) : 1;
            return getEmployeeBaseSalary() * (months / 12);
        }

        return 0;
    }

    function calculateInss(base) {
        if (base <= 0) {
            return 0;
        }

        const ranges = [
            { limit: 1320.0, rate: 0.075 },
            { limit: 2571.29, rate: 0.09 },
            { limit: 3856.94, rate: 0.12 },
            { limit: 7507.49, rate: 0.14 }
        ];

        let remaining = base;
        let contribution = 0;
        let previousLimit = 0;

        for (const range of ranges) {
            if (remaining <= 0) {
                break;
            }

            const span = Math.min(remaining, range.limit - previousLimit);
            if (span > 0) {
                contribution += span * range.rate;
                remaining -= span;
            }

            previousLimit = range.limit;
        }

        if (remaining > 0) {
            contribution += remaining * 0.14;
        }

        return contribution;
    }

    function calculateIrrf(base) {
        if (base <= 0) {
            return 0;
        }

        const bands = [
            { limit: 1903.98, rate: 0, deduction: 0 },
            { limit: 2826.65, rate: 0.075, deduction: 142.8 },
            { limit: 3751.05, rate: 0.15, deduction: 354.8 },
            { limit: 4664.68, rate: 0.225, deduction: 636.13 }
        ];

        for (const band of bands) {
            if (base <= band.limit) {
                return Math.max(0, base * band.rate - band.deduction);
            }
        }

        return Math.max(0, base * 0.275 - 869.36);
    }

    function sumInputs(nodeList) {
        return Array.from(nodeList).reduce((total, input) => {
            const value = Number.parseFloat(input.value || '0');
            return total + (Number.isFinite(value) ? value : 0);
        }, 0);
    }

    function ensurePlaceholder(container) {
        const label = container.dataset.emptyLabel;
        if (!label) {
            return;
        }

        const existing = container.querySelector('.dynamic-empty');
        const hasRows = container.querySelector('.dynamic-row');

        if (!hasRows && !existing) {
            const placeholder = document.createElement('p');
            placeholder.className = 'muted dynamic-empty';
            placeholder.textContent = label;
            container.appendChild(placeholder);
        } else if (hasRows && existing) {
            existing.remove();
        }
    }

    function addRow(container, type) {
        const wrapper = document.createElement('div');
        wrapper.className = 'dynamic-row';
        wrapper.style.marginBottom = '1rem';

        const description = document.createElement('input');
        description.type = 'text';
        description.name = `${type}_description[]`;
        description.placeholder = 'Descrição';
        description.required = true;
        description.style.marginBottom = '0.5rem';

        const amount = document.createElement('input');
        amount.type = 'number';
        amount.name = `${type}_amount[]`;
        amount.placeholder = 'Valor em R$';
        amount.step = '0.01';
        amount.min = '0';
        amount.required = true;

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'button button-secondary';
        removeButton.style.background = '#6b7280';
        removeButton.style.marginTop = '0.5rem';
        removeButton.textContent = 'Remover';

        removeButton.addEventListener('click', () => {
            wrapper.remove();
            ensurePlaceholder(container);
            updateSummary();
        });

        amount.addEventListener('input', updateSummary);
        description.addEventListener('input', updateSummary);

        wrapper.appendChild(description);
        wrapper.appendChild(amount);
        wrapper.appendChild(removeButton);

        const placeholder = container.querySelector('.dynamic-empty');
        if (placeholder) {
            placeholder.remove();
        }

        container.appendChild(wrapper);
        updateSummary();
    }

    function updateSummary() {
        const typeSelect = document.getElementById('type');
        if (!(typeSelect instanceof HTMLSelectElement)) {
            return;
        }

        const type = typeSelect.value;
        const baseSalary = calculateBaseSalary(type);

        const thirteenthHidden = document.getElementById('thirteenth_months');
        const thirteenthInput = document.getElementById('thirteenth_months_input');
        if (thirteenthHidden instanceof HTMLInputElement && thirteenthInput instanceof HTMLInputElement) {
            if (type === 'thirteenth') {
                thirteenthHidden.value = thirteenthInput.value || '1';
            } else if (type === 'termination') {
                thirteenthInput.value = thirteenthHidden.value || thirteenthInput.value || '1';
            }
        }

        const allowanceInputs = document.querySelectorAll('input[name="allowance_amount[]"]');
        const deductionInputs = document.querySelectorAll('input[name="deduction_amount[]"]');

        const manualAllowances = sumInputs(allowanceInputs);
        const manualDeductions = sumInputs(deductionInputs);

        const automaticItems = automaticAllowances(type, baseSalary);
        const automaticTotal = automaticItems.reduce((total, item) => total + item.amount, 0);
        let contributionBase = baseSalary + automaticTotal;
        let fgtsBase = contributionBase;
        let inss = 0;
        let irrfBase = 0;
        let irrf = 0;
        const thirteenthItem = automaticItems.find((item) => item.label.toLowerCase().includes('13'));
        const thirteenthTotal = automaticThirteenth(type) || (thirteenthItem ? thirteenthItem.amount : 0);

        if (type === 'thirteenth') {
            const installment = getThirteenthInstallment();
            if (installment === 'second') {
                contributionBase = thirteenthTotal;
                fgtsBase = contributionBase;
                inss = calculateInss(contributionBase);
                irrfBase = Math.max(0, contributionBase - inss);
                irrf = calculateIrrf(irrfBase);
            } else {
                contributionBase = baseSalary + automaticTotal;
                fgtsBase = contributionBase;
                inss = 0;
                irrfBase = 0;
                irrf = 0;
            }
        } else {
            inss = calculateInss(contributionBase);
            irrfBase = Math.max(0, contributionBase - inss);
            irrf = calculateIrrf(irrfBase);
        }

        const fgts = fgtsBase * 0.08;

        const totalAllowances = manualAllowances + automaticTotal;
        const automaticDeductions = inss + irrf;
        const totalDeductions = manualDeductions + automaticDeductions;
        const netSalary = baseSalary + totalAllowances - totalDeductions;

        const paymentInput = document.getElementById('payment_date');
        if (paymentInput instanceof HTMLInputElement) {
            if (type === 'thirteenth') {
                const resolvedDate = resolveThirteenthPaymentDate(getReferenceMonth(), getThirteenthInstallment());
                if (resolvedDate) {
                    paymentInput.value = resolvedDate;
                }
            }
        }

        const setText = (id, value) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = formatter.format(value || 0);
            }
        };

        setText('base-salary', baseSalary);
        setText('auto-allowances', automaticTotal);
        setText('total-allowances', manualAllowances);
        setText('total-deductions', manualDeductions);
        setText('inss-amount', inss);
        setText('inss-base', contributionBase);
        setText('irrf-amount', irrf);
        setText('irrf-base', irrfBase);
        setText('fgts-amount', fgts);
        setText('fgts-base', fgtsBase);
        setText('thirteenth-amount', thirteenthTotal);
        setText('net-salary', netSalary);

        const automaticDescriptions = document.getElementById('automatic-descriptions');
        if (automaticDescriptions) {
            const allowanceList = automaticItems.length > 0
                ? '<strong>Acréscimos automáticos</strong><ul style="margin:0.5rem 0 0 1.25rem;">' +
                    automaticItems.map((item) => `<li>${item.label} — <strong>${formatter.format(item.amount)}</strong></li>`).join('') +
                    '</ul>'
                : '';
            const deductionTitle = type === 'thirteenth'
                ? 'Descontos automáticos estimados (13º)'
                : 'Descontos automáticos estimados';
            const deductionLine = `<p style="margin:0.75rem 0 0;">${deductionTitle}: INSS <strong>${formatter.format(inss)}</strong> · IRRF <strong>${formatter.format(irrf)}</strong></p>`;
            automaticDescriptions.innerHTML = allowanceList + deductionLine;
        }
    }

    function toggleTypeSections() {
        const typeSelect = document.getElementById('type');
        if (!(typeSelect instanceof HTMLSelectElement)) {
            return;
        }

        const type = typeSelect.value;
        const vacationFields = document.getElementById('vacation-fields');
        const terminationFields = document.getElementById('termination-fields');
        const thirteenthFields = document.getElementById('thirteenth-fields');

        if (vacationFields) {
            vacationFields.style.display = type === 'vacation' ? 'block' : 'none';
        }
        if (terminationFields) {
            terminationFields.style.display = type === 'termination' ? 'block' : 'none';
        }
        if (thirteenthFields) {
            thirteenthFields.style.display = type === 'thirteenth' ? 'block' : 'none';
        }

        const vacationInput = document.getElementById('vacation_days');
        const workedInput = document.getElementById('worked_days');
        const thirteenthHidden = document.getElementById('thirteenth_months');
        const thirteenthInput = document.getElementById('thirteenth_months_input');
        const thirteenthInstallment = document.getElementById('thirteenth_installment');

        if (vacationInput instanceof HTMLInputElement) {
            vacationInput.required = type === 'vacation';
        }
        if (workedInput instanceof HTMLInputElement) {
            workedInput.required = type === 'termination';
        }
        if (thirteenthHidden instanceof HTMLInputElement) {
            thirteenthHidden.required = type === 'termination' || type === 'thirteenth';
        }
        if (thirteenthInput instanceof HTMLInputElement) {
            thirteenthInput.required = type === 'thirteenth';
        }
        if (thirteenthInstallment instanceof HTMLSelectElement) {
            thirteenthInstallment.required = type === 'thirteenth';
        }

        if (type === 'thirteenth' && thirteenthHidden instanceof HTMLInputElement && thirteenthInput instanceof HTMLInputElement) {
            thirteenthHidden.value = thirteenthInput.value || '1';
        }

        updateSummary();
    }

    function bootstrap() {
        const form = document.getElementById('payroll-form');
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const addButtons = form.querySelectorAll('.dynamic-add');
        addButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-target');
                const type = button.getAttribute('data-type');
                if (!targetId || !type) {
                    return;
                }

                const container = document.getElementById(targetId);
                if (container) {
                    addRow(container, type);
                }
            });
        });

        const dynamicLists = form.querySelectorAll('.dynamic-list');
        dynamicLists.forEach((list) => ensurePlaceholder(list));

        const employeeSelect = document.getElementById('employee_id');
        if (employeeSelect) {
            employeeSelect.addEventListener('change', updateSummary);
        }

        form.addEventListener('input', (event) => {
            if (event.target instanceof HTMLInputElement || event.target instanceof HTMLSelectElement) {
                updateSummary();
            }
        });

        const typeSelect = document.getElementById('type');
        if (typeSelect) {
            typeSelect.addEventListener('change', toggleTypeSections);
        }

        const justCause = document.getElementById('just_cause');
        if (justCause) {
            justCause.addEventListener('change', updateSummary);
        }

        const thirteenthInput = document.getElementById('thirteenth_months_input');
        if (thirteenthInput instanceof HTMLInputElement) {
            thirteenthInput.addEventListener('input', () => {
                const hidden = document.getElementById('thirteenth_months');
                if (hidden instanceof HTMLInputElement) {
                    hidden.value = thirteenthInput.value;
                }
                updateSummary();
            });
        }

        const thirteenthInstallment = document.getElementById('thirteenth_installment');
        if (thirteenthInstallment instanceof HTMLSelectElement) {
            thirteenthInstallment.addEventListener('change', updateSummary);
        }

        // Add an initial row to each container for convenience
        dynamicLists.forEach((list) => {
            const type = list.id === 'deductions' ? 'deduction' : 'allowance';
            addRow(list, type);
        });

        const defaultType = form.getAttribute('data-default-type');
        if (defaultType && typeSelect instanceof HTMLSelectElement) {
            typeSelect.value = defaultType;
        }

        toggleTypeSections();
        updateSummary();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
    } else {
        bootstrap();
    }
})();
