<?php
/** @var string $title */
/** @var Holerite\Models\Employee[] $employees */
/** @var array<string, mixed> $defaults */
$selectedEmployeeId = (int) ($defaults['employee_id'] ?? 0);
$selectedType = (string) ($defaults['type'] ?? 'regular');
$referenceMonth = (string) ($defaults['reference_month'] ?? '');
if ($referenceMonth === '') {
    $referenceMonth = date('Y-m');
}
$defaultThirteenthMonths = max(1, min(12, (int) ($defaults['thirteenth_months'] ?? 12)));
$defaultVacationDays = max(1, min(30, (int) ($defaults['vacation_days'] ?? 30)));
$defaultWorkedDays = max(0, min(30, (int) ($defaults['worked_days'] ?? 30)));
$defaultJustCause = !empty($defaults['just_cause']);
?>
<section>
    <header style="margin-bottom:1.5rem;">
        <h2 style="margin:0 0 0.25rem 0;"><?= htmlspecialchars($title); ?></h2>
        <p class="muted">Selecione o colaborador, defina o tipo de holerite e informe valores adicionais. O recibo exibirá os dados da empresa <strong><?= htmlspecialchars($company->getName()); ?></strong>.</p>
    </header>

    <form method="post" action="?action=store_payroll" id="payroll-form">
        <div class="grid">
            <div>
                <label for="employee_id">Colaborador</label>
                <select name="employee_id" id="employee_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?= $employee->getId(); ?>" data-salary="<?= number_format($employee->getBaseSalary(), 2, '.', ''); ?>" <?= $selectedEmployeeId === $employee->getId() ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($employee->getName()); ?> — R$ <?= number_format($employee->getBaseSalary(), 2, ',', '.'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="reference_month">Mês de referência</label>
                <input type="month" id="reference_month" name="reference_month" value="<?= htmlspecialchars($referenceMonth); ?>" required>
            </div>
            <div>
                <label for="payment_date">Data de pagamento</label>
                <input type="date" id="payment_date" name="payment_date" value="<?= date('Y-m-d'); ?>" required>
            </div>
            <div>
                <label for="type">Tipo do holerite</label>
                <select name="type" id="type">
                    <option value="regular" <?= $selectedType === 'regular' ? 'selected' : ''; ?>>Mensal</option>
                    <option value="vacation" <?= $selectedType === 'vacation' ? 'selected' : ''; ?>>Férias</option>
                    <option value="termination" <?= $selectedType === 'termination' ? 'selected' : ''; ?>>Desligamento</option>
                    <option value="thirteenth" <?= $selectedType === 'thirteenth' ? 'selected' : ''; ?>>13º salário</option>
                </select>
            </div>
        </div>

        <div id="vacation-fields" class="card" style="margin-top:1.5rem; display:none;">
            <h3 style="margin-top:0;">Informações de férias</h3>
            <div class="grid">
                <div>
                    <label for="vacation_days">Dias de férias</label>
                    <input type="number" id="vacation_days" name="vacation_days" min="1" max="30" value="<?= htmlspecialchars((string) $defaultVacationDays); ?>">
                </div>
            </div>
            <p class="muted">O sistema calcula automaticamente o 1/3 constitucional sobre o valor proporcional aos dias de férias.</p>
        </div>

        <div id="termination-fields" class="card" style="margin-top:1.5rem; display:none;">
            <h3 style="margin-top:0;">Informações de desligamento</h3>
            <div class="grid">
                <div>
                    <label for="worked_days">Dias trabalhados no mês</label>
                    <input type="number" id="worked_days" name="worked_days" min="0" max="30" value="<?= htmlspecialchars((string) $defaultWorkedDays); ?>">
                </div>
                <div>
                    <label for="thirteenth_months">Meses para cálculo do 13º</label>
                    <input type="number" id="thirteenth_months" name="thirteenth_months" min="0" max="12" value="<?= htmlspecialchars((string) $defaultThirteenthMonths); ?>">
                </div>
            </div>
            <div>
                <label class="muted" style="display:flex;align-items:center;gap:0.5rem;">
                    <input type="checkbox" name="just_cause" id="just_cause" value="1" <?= $defaultJustCause ? 'checked' : ''; ?>>
                    Desligamento por justa causa (remove pagamento de 13º proporcional)
                </label>
            </div>
        </div>

        <div id="thirteenth-fields" class="card" style="margin-top:1.5rem; display:none;">
            <h3 style="margin-top:0;">Informações do 13º salário</h3>
            <div class="grid">
                <div>
                    <label for="thirteenth_months_input">Meses acumulados</label>
                    <input type="number" id="thirteenth_months_input" min="1" max="12" value="<?= htmlspecialchars((string) $defaultThirteenthMonths); ?>">
                </div>
            </div>
            <p class="muted">O valor bruto será calculado automaticamente com base nos meses selecionados e no salário atual.</p>
        </div>

        <div class="grid" style="margin-top:1.5rem;">
            <div class="card">
                <h3 style="margin-top:0;">Proventos manuais</h3>
                <div id="allowances"></div>
                <button type="button" class="button" style="background:#10b981;margin-top:0.5rem;" onclick="addRow('allowances', 'allowance');">Adicionar provento</button>
            </div>
            <div class="card">
                <h3 style="margin-top:0;">Descontos</h3>
                <div id="deductions"></div>
                <button type="button" class="button" style="background:#f97316;margin-top:0.5rem;" onclick="addRow('deductions', 'deduction');">Adicionar desconto</button>
            </div>
        </div>

        <div style="margin-top:1.5rem;">
            <label for="notes">Observações</label>
            <textarea id="notes" name="notes" rows="3" placeholder="Informe observações importantes sobre este holerite..."></textarea>
        </div>

        <div class="card" style="margin-top:1.5rem;">
            <h3 style="margin:0 0 0.5rem 0;">Resumo</h3>
            <p class="muted">Salário base calculado: <strong id="base-salary">R$ 0,00</strong></p>
            <p class="muted">Proventos automáticos: <strong id="auto-allowances">R$ 0,00</strong></p>
            <p class="muted">Proventos manuais: <strong id="total-allowances">R$ 0,00</strong></p>
            <p class="muted">Descontos manuais: <strong id="total-deductions">R$ 0,00</strong></p>
            <p class="muted">INSS estimado: <strong id="inss-amount">R$ 0,00</strong> · Base: <strong id="inss-base">R$ 0,00</strong></p>
            <p class="muted">IRRF estimado: <strong id="irrf-amount">R$ 0,00</strong> · Base: <strong id="irrf-base">R$ 0,00</strong></p>
            <p class="muted">FGTS do mês: <strong id="fgts-amount">R$ 0,00</strong> · Base: <strong id="fgts-base">R$ 0,00</strong></p>
            <p class="muted">13º acumulado: <strong id="thirteenth-amount">R$ 0,00</strong></p>
            <p class="muted">Valor líquido estimado: <strong id="net-salary">R$ 0,00</strong></p>
            <div id="automatic-descriptions" class="muted" style="margin-top:0.75rem;"></div>
        </div>

        <button type="submit" class="button" style="margin-top:1.5rem;">Gerar holerite</button>
        <a href="?action=list_payrolls" class="button button-secondary" style="margin-left:0.5rem;">Cancelar</a>
    </form>
</section>

<script>
    const formatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

    function addRow(containerId, type) {
        const container = document.getElementById(containerId);
        const wrapper = document.createElement('div');
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
        amount.addEventListener('input', updateSummary);

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.textContent = 'Remover';
        remove.className = 'button button-secondary';
        remove.style.background = '#6b7280';
        remove.style.marginTop = '0.5rem';
        remove.onclick = () => {
            wrapper.remove();
            updateSummary();
        };

        wrapper.appendChild(description);
        wrapper.appendChild(amount);
        wrapper.appendChild(remove);
        container.appendChild(wrapper);
    }

    function sumInputs(inputs) {
        return Array.from(inputs).reduce((total, input) => total + parseFloat(input.value || '0'), 0);
    }

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function getEmployeeBaseSalary() {
        const employee = document.getElementById('employee_id');
        const option = employee.options[employee.selectedIndex];
        return option ? parseFloat(option.dataset.salary || '0') : 0;
    }

    function calculateBaseSalary(type) {
        const base = getEmployeeBaseSalary();

        if (type === 'vacation') {
            const days = clamp(parseFloat(document.getElementById('vacation_days').value || '30'), 1, 30);
            return base * (days / 30);
        }

        if (type === 'termination') {
            const days = clamp(parseFloat(document.getElementById('worked_days').value || '30'), 0, 30);
            return base * (days / 30);
        }

        if (type === 'thirteenth') {
            const monthsInput = document.getElementById('thirteenth_months_input');
            const months = clamp(parseFloat(monthsInput ? monthsInput.value || '1' : '1'), 1, 12);
            return base * (months / 12);
        }

        return base;
    }

    function automaticAllowances(type, baseSalary) {
        const items = [];

        if (type === 'vacation') {
            const bonus = baseSalary / 3;
            if (bonus > 0) {
                items.push({ label: '1/3 Constitucional de Férias', amount: bonus });
            }
        }

        if (type === 'termination') {
            const justCause = document.getElementById('just_cause').checked;
            const months = clamp(parseFloat(document.getElementById('thirteenth_months').value || '12'), 0, 12);
            const referenceBase = getEmployeeBaseSalary();
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
            const months = clamp(parseFloat(monthsInput ? monthsInput.value || '1' : '1'), 1, 12);
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
            { limit: 7507.49, rate: 0.14 },
        ];

        let remaining = base;
        let contribution = 0;
        let previous = 0;

        for (const range of ranges) {
            if (remaining <= 0) {
                break;
            }

            const span = Math.min(remaining, range.limit - previous);
            if (span > 0) {
                contribution += span * range.rate;
                remaining -= span;
            }

            previous = range.limit;
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
            { limit: 4664.68, rate: 0.225, deduction: 636.13 },
        ];

        for (const band of bands) {
            if (base <= band.limit) {
                return Math.max(0, base * band.rate - band.deduction);
            }
        }

        return Math.max(0, base * 0.275 - 869.36);
    }

    function updateSummary() {
        const type = document.getElementById('type').value;
        const baseSalary = calculateBaseSalary(type);

        const thirteenthHidden = document.getElementById('thirteenth_months');
        const thirteenthInput = document.getElementById('thirteenth_months_input');
        if (thirteenthHidden && thirteenthInput) {
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

        const autoItems = automaticAllowances(type, baseSalary);
        const autoAllowanceTotal = autoItems.reduce((total, item) => total + item.amount, 0);
        const contributionBase = baseSalary + autoAllowanceTotal;
        const inss = calculateInss(contributionBase);
        const irrfBase = Math.max(0, contributionBase - inss);
        const irrf = calculateIrrf(irrfBase);
        const fgts = contributionBase * 0.08;
        const thirteenthItem = autoItems.find(item => item.label.toLowerCase().includes('13'));
        const thirteenth = automaticThirteenth(type) || (thirteenthItem ? thirteenthItem.amount : 0);

        const totalAllowances = manualAllowances + autoAllowanceTotal;
        const automaticDeductions = inss + irrf;
        const totalDeductions = manualDeductions + automaticDeductions;
        const netSalary = baseSalary + totalAllowances - totalDeductions;

        document.getElementById('base-salary').textContent = formatter.format(baseSalary || 0);
        document.getElementById('auto-allowances').textContent = formatter.format(autoAllowanceTotal || 0);
        document.getElementById('total-allowances').textContent = formatter.format(manualAllowances || 0);
        document.getElementById('total-deductions').textContent = formatter.format(manualDeductions || 0);
        document.getElementById('inss-amount').textContent = formatter.format(inss || 0);
        document.getElementById('inss-base').textContent = formatter.format(contributionBase || 0);
        document.getElementById('irrf-amount').textContent = formatter.format(irrf || 0);
        document.getElementById('irrf-base').textContent = formatter.format(irrfBase || 0);
        document.getElementById('fgts-amount').textContent = formatter.format(fgts || 0);
        document.getElementById('fgts-base').textContent = formatter.format(contributionBase || 0);
        document.getElementById('thirteenth-amount').textContent = formatter.format(thirteenth || 0);
        document.getElementById('net-salary').textContent = formatter.format(netSalary || 0);

        const automaticDescriptions = document.getElementById('automatic-descriptions');
        const allowanceList = autoItems.length > 0
            ? '<strong>Acréscimos automáticos</strong><ul style="margin:0.5rem 0 0 1.25rem;">' +
                autoItems.map(item => `<li>${item.label} — <strong>${formatter.format(item.amount)}</strong></li>`).join('') +
                '</ul>'
            : '';
        const deductionTitle = type === 'thirteenth' ? 'Descontos automáticos estimados (13º)' : 'Descontos automáticos estimados';
        const deductionLine = `<p style="margin:0.75rem 0 0;">${deductionTitle}: INSS <strong>${formatter.format(inss || 0)}</strong> · IRRF <strong>${formatter.format(irrf || 0)}</strong></p>`;
        automaticDescriptions.innerHTML = allowanceList + deductionLine;
    }

    function toggleTypeSections() {
        const type = document.getElementById('type').value;
        const vacationFields = document.getElementById('vacation-fields');
        const terminationFields = document.getElementById('termination-fields');
        const thirteenthFields = document.getElementById('thirteenth-fields');

        vacationFields.style.display = type === 'vacation' ? 'block' : 'none';
        terminationFields.style.display = type === 'termination' ? 'block' : 'none';
        thirteenthFields.style.display = type === 'thirteenth' ? 'block' : 'none';

        document.getElementById('vacation_days').required = type === 'vacation';
        document.getElementById('worked_days').required = type === 'termination';
        document.getElementById('thirteenth_months').required = type === 'termination' || type === 'thirteenth';
        document.getElementById('thirteenth_months_input').required = type === 'thirteenth';

        if (type === 'thirteenth') {
            const thirteenthHidden = document.getElementById('thirteenth_months');
            const thirteenthInput = document.getElementById('thirteenth_months_input');
            if (thirteenthHidden && thirteenthInput) {
                thirteenthHidden.value = thirteenthInput.value || '1';
            }
        }

        updateSummary();
    }

    document.getElementById('employee_id').addEventListener('change', updateSummary);
    document.getElementById('payroll-form').addEventListener('input', updateSummary);
    document.getElementById('type').addEventListener('change', toggleTypeSections);
    document.getElementById('just_cause').addEventListener('change', updateSummary);
    document.getElementById('thirteenth_months_input').addEventListener('input', () => {
        document.getElementById('thirteenth_months').value = document.getElementById('thirteenth_months_input').value;
        updateSummary();
    });

    toggleTypeSections();

    // adiciona uma linha inicial em cada seção
    addRow('allowances', 'allowance');
    addRow('deductions', 'deduction');

    updateSummary();
</script>
