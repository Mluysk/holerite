<?php
/** @var string $title */
/** @var Holerite\Models\Employee[] $employees */
?>
<section>
    <header style="margin-bottom:1.5rem;">
        <h2 style="margin:0 0 0.25rem 0;"><?= htmlspecialchars($title); ?></h2>
        <p class="muted">Selecione o colaborador, defina o tipo de holerite e informe valores adicionais.</p>
    </header>

    <form method="post" action="?action=store_payroll" id="payroll-form">
        <div class="grid">
            <div>
                <label for="employee_id">Colaborador</label>
                <select name="employee_id" id="employee_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?= $employee->getId(); ?>" data-salary="<?= number_format($employee->getBaseSalary(), 2, '.', ''); ?>">
                            <?= htmlspecialchars($employee->getName()); ?> — R$ <?= number_format($employee->getBaseSalary(), 2, ',', '.'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="reference_month">Mês de referência</label>
                <input type="month" id="reference_month" name="reference_month" required>
            </div>
            <div>
                <label for="payment_date">Data de pagamento</label>
                <input type="date" id="payment_date" name="payment_date" value="<?= date('Y-m-d'); ?>" required>
            </div>
            <div>
                <label for="type">Tipo do holerite</label>
                <select name="type" id="type">
                    <option value="regular">Mensal</option>
                    <option value="vacation">Férias</option>
                    <option value="termination">Desligamento</option>
                </select>
            </div>
        </div>

        <div id="vacation-fields" class="card" style="margin-top:1.5rem; display:none;">
            <h3 style="margin-top:0;">Informações de férias</h3>
            <div class="grid">
                <div>
                    <label for="vacation_days">Dias de férias</label>
                    <input type="number" id="vacation_days" name="vacation_days" min="1" max="30" value="30">
                </div>
            </div>
            <p class="muted">O sistema calcula automaticamente o 1/3 constitucional sobre o valor proporcional aos dias de férias.</p>
        </div>

        <div id="termination-fields" class="card" style="margin-top:1.5rem; display:none;">
            <h3 style="margin-top:0;">Informações de desligamento</h3>
            <div class="grid">
                <div>
                    <label for="worked_days">Dias trabalhados no mês</label>
                    <input type="number" id="worked_days" name="worked_days" min="0" max="30" value="30">
                </div>
                <div>
                    <label for="thirteenth_months">Meses para cálculo do 13º</label>
                    <input type="number" id="thirteenth_months" name="thirteenth_months" min="0" max="12" value="12">
                </div>
            </div>
            <div>
                <label class="muted" style="display:flex;align-items:center;gap:0.5rem;">
                    <input type="checkbox" name="just_cause" id="just_cause" value="1">
                    Desligamento por justa causa (remove pagamento de 13º proporcional)
                </label>
            </div>
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
            <p class="muted">Descontos: <strong id="total-deductions">R$ 0,00</strong></p>
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

    function updateSummary() {
        const type = document.getElementById('type').value;
        const baseSalary = calculateBaseSalary(type);

        const allowanceInputs = document.querySelectorAll('input[name="allowance_amount[]"]');
        const deductionInputs = document.querySelectorAll('input[name="deduction_amount[]"]');

        const manualAllowances = sumInputs(allowanceInputs);
        const manualDeductions = sumInputs(deductionInputs);

        const autoItems = automaticAllowances(type, baseSalary);
        const autoAllowanceTotal = autoItems.reduce((total, item) => total + item.amount, 0);

        const netSalary = baseSalary + manualAllowances + autoAllowanceTotal - manualDeductions;

        document.getElementById('base-salary').textContent = formatter.format(baseSalary || 0);
        document.getElementById('auto-allowances').textContent = formatter.format(autoAllowanceTotal || 0);
        document.getElementById('total-allowances').textContent = formatter.format(manualAllowances || 0);
        document.getElementById('total-deductions').textContent = formatter.format(manualDeductions || 0);
        document.getElementById('net-salary').textContent = formatter.format(netSalary || 0);

        const automaticDescriptions = document.getElementById('automatic-descriptions');
        if (autoItems.length > 0) {
            automaticDescriptions.innerHTML = '<strong>Acréscimos automáticos</strong><ul style="margin:0.5rem 0 0 1.25rem;">' +
                autoItems.map(item => `<li>${item.label} — <strong>${formatter.format(item.amount)}</strong></li>`).join('') +
                '</ul>';
        } else {
            automaticDescriptions.textContent = '';
        }
    }

    function toggleTypeSections() {
        const type = document.getElementById('type').value;
        const vacationFields = document.getElementById('vacation-fields');
        const terminationFields = document.getElementById('termination-fields');

        vacationFields.style.display = type === 'vacation' ? 'block' : 'none';
        terminationFields.style.display = type === 'termination' ? 'block' : 'none';

        document.getElementById('vacation_days').required = type === 'vacation';
        document.getElementById('worked_days').required = type === 'termination';
        document.getElementById('thirteenth_months').required = type === 'termination';

        updateSummary();
    }

    document.getElementById('employee_id').addEventListener('change', updateSummary);
    document.getElementById('payroll-form').addEventListener('input', updateSummary);
    document.getElementById('type').addEventListener('change', toggleTypeSections);
    document.getElementById('just_cause').addEventListener('change', updateSummary);

    toggleTypeSections();

    // adiciona uma linha inicial em cada seção
    addRow('allowances', 'allowance');
    addRow('deductions', 'deduction');

    updateSummary();
</script>
