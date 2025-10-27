<?php
/** @var string $title */
/** @var Holerite\Models\Employee[] $employees */
?>
<section>
    <header style="margin-bottom:1.5rem;">
        <h2 style="margin:0 0 0.25rem 0;"><?= htmlspecialchars($title); ?></h2>
        <p class="muted">Selecione o colaborador, informe o mês de referência e detalhe proventos e descontos.</p>
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
        </div>

        <div class="grid" style="margin-top:1.5rem;">
            <div class="card">
                <h3 style="margin-top:0;">Proventos</h3>
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
            <p class="muted">Salário base: <strong id="base-salary">R$ 0,00</strong></p>
            <p class="muted">Total de proventos: <strong id="total-allowances">R$ 0,00</strong></p>
            <p class="muted">Total de descontos: <strong id="total-deductions">R$ 0,00</strong></p>
            <p class="muted">Valor líquido estimado: <strong id="net-salary">R$ 0,00</strong></p>
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

    function updateSummary() {
        const employee = document.getElementById('employee_id');
        const baseSalary = parseFloat(employee.options[employee.selectedIndex]?.dataset.salary || '0');
        const allowanceInputs = document.querySelectorAll('input[name="allowance_amount[]"]');
        const deductionInputs = document.querySelectorAll('input[name="deduction_amount[]"]');

        const totalAllowances = sumInputs(allowanceInputs);
        const totalDeductions = sumInputs(deductionInputs);
        const netSalary = baseSalary + totalAllowances - totalDeductions;

        document.getElementById('base-salary').textContent = formatter.format(baseSalary);
        document.getElementById('total-allowances').textContent = formatter.format(totalAllowances);
        document.getElementById('total-deductions').textContent = formatter.format(totalDeductions);
        document.getElementById('net-salary').textContent = formatter.format(netSalary);
    }

    document.getElementById('employee_id').addEventListener('change', updateSummary);
    document.getElementById('payroll-form').addEventListener('input', updateSummary);

    // adiciona uma linha inicial em cada seção
    addRow('allowances', 'allowance');
    addRow('deductions', 'deduction');
</script>
