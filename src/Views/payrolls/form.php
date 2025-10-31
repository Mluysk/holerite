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
$defaultThirteenthInstallment = $defaults['thirteenth_installment'] ?? '';
if (!in_array($defaultThirteenthInstallment, ['first', 'second'], true)) {
    $defaultThirteenthInstallment = 'second';
}
$defaultVacationDays = max(1, min(30, (int) ($defaults['vacation_days'] ?? 30)));
$defaultWorkedDays = max(0, min(30, (int) ($defaults['worked_days'] ?? 30)));
$defaultJustCause = !empty($defaults['just_cause']);
$defaultValeDeduction = number_format((float) ($defaults['vale_deduction'] ?? 0), 2, '.', '');
$defaultAdvanceAmount = number_format((float) ($defaults['advance_amount'] ?? 0), 2, '.', '');
$defaultRemainingAmount = number_format((float) ($defaults['remaining_amount'] ?? 0), 2, '.', '');
$defaultHasAdvance = $defaults['has_advance'] ?? null;
if ($defaultHasAdvance === null) {
    $defaultHasAdvance = $selectedType === 'regular';
}
$defaultHasAdvance = (bool) $defaultHasAdvance;

if (!isset($pageScripts) || !is_array($pageScripts)) {
    $pageScripts = [];
}

$pageScripts[] = [
    'src' => 'js/payroll-form.js',
    'defer' => true,
];
?>
<section>
    <header style="margin-bottom:1.5rem;">
        <h2 style="margin:0 0 0.25rem 0;"><?= htmlspecialchars($title); ?></h2>
        <p class="muted">Selecione o colaborador, defina o tipo de holerite e informe valores adicionais. O recibo exibirá os dados da empresa <strong><?= htmlspecialchars($company->getName()); ?></strong>.</p>
    </header>

    <form method="post" action="?action=store_payroll" id="payroll-form" data-default-type="<?= htmlspecialchars($selectedType); ?>">
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
                <div>
                    <label for="thirteenth_installment">Parcela</label>
                    <select name="thirteenth_installment" id="thirteenth_installment">
                        <option value="first" <?= $defaultThirteenthInstallment === 'first' ? 'selected' : ''; ?>>1ª parcela (adiantamento)</option>
                        <option value="second" <?= $defaultThirteenthInstallment === 'second' ? 'selected' : ''; ?>>2ª parcela (liquidação)</option>
                    </select>
                </div>
            </div>
            <p class="muted">O valor bruto será calculado automaticamente com base nos meses selecionados e no salário atual.</p>
        </div>

        <div class="grid" style="margin-top:1.5rem;">
            <div class="card">
                <h3 style="margin-top:0;">Proventos manuais</h3>
                <p class="muted" style="margin:0 0 0.75rem 0;">Registre aqui ganhos como horas extras, adicional noturno, comissões, bônus, gratificações e demais proventos que não sejam calculados automaticamente.</p>
                <div id="allowances" class="dynamic-list" data-empty-label="Nenhum provento manual informado."></div>
                <button type="button" class="button dynamic-add" data-target="allowances" data-type="allowance" style="background:#10b981;margin-top:0.5rem;">Adicionar provento</button>
            </div>
            <div class="card">
                <h3 style="margin-top:0;">Descontos</h3>
                <div style="display:flex;flex-direction:column;gap:0.35rem;margin-bottom:1rem;">
                    <label for="vale_deduction">Desconto de vale</label>
                    <input type="number" min="0" step="0.01" id="vale_deduction" name="vale_deduction" value="<?= htmlspecialchars($defaultValeDeduction); ?>" placeholder="0,00">
                    <p class="muted" style="margin:0;">Informe o total de vales abatidos neste holerite.</p>
                </div>
                <div id="deductions" class="dynamic-list" data-empty-label="Nenhum desconto manual informado."></div>
                <button type="button" class="button dynamic-add" data-target="deductions" data-type="deduction" style="background:#f97316;margin-top:0.5rem;">Adicionar desconto</button>
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
            <p class="muted">Desconto de vale: <strong id="vale-deduction-total">R$ 0,00</strong></p>
            <p class="muted">INSS estimado: <strong id="inss-amount">R$ 0,00</strong> · Base: <strong id="inss-base">R$ 0,00</strong></p>
            <p class="muted">IRRF estimado: <strong id="irrf-amount">R$ 0,00</strong> · Base: <strong id="irrf-base">R$ 0,00</strong></p>
            <p class="muted">FGTS do mês: <strong id="fgts-amount">R$ 0,00</strong> · Base: <strong id="fgts-base">R$ 0,00</strong></p>
            <p class="muted">13º acumulado: <strong id="thirteenth-amount">R$ 0,00</strong></p>
            <p class="muted">Valor líquido estimado: <strong id="net-salary">R$ 0,00</strong></p>
            <p class="muted">1ª parcela (adiantamento): <strong id="advance-display">R$ 0,00</strong></p>
            <p class="muted">2ª parcela (restante): <strong id="remaining-display">R$ 0,00</strong></p>
            <p class="muted" id="advance-note" style="display: <?= $defaultHasAdvance ? 'block' : 'none'; ?>; margin-top:0.5rem;">Com o adiantamento habilitado, o desconto aparecerá logo abaixo do salário base no holerite impresso.</p>
            <div id="automatic-descriptions" class="muted" style="margin-top:0.75rem;"></div>
        </div>

        <div class="card" style="margin-top:1.5rem;">
            <h3 style="margin-top:0;">Parcelamento do pagamento</h3>
            <input type="hidden" name="has_advance" value="0">
            <label class="muted" for="has_advance" style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;margin-bottom:0;">
                <input type="checkbox" id="has_advance" name="has_advance" value="1" <?= $defaultHasAdvance ? 'checked' : ''; ?>>
                Registrar adiantamento salarial (1ª parcela)
            </label>
            <div id="advance-fields" style="margin-top:1rem; display: <?= $defaultHasAdvance ? 'block' : 'none'; ?>;">
                <div class="grid">
                    <div>
                        <label for="advance_amount">Adiantamento (1ª parcela)</label>
                        <input type="number" min="0" step="0.01" id="advance_amount" name="advance_amount" value="<?= htmlspecialchars($defaultAdvanceAmount); ?>" placeholder="0,00">
                    </div>
                    <div>
                        <label for="remaining_amount">Pagamento restante (2ª parcela)</label>
                        <input type="number" min="0" step="0.01" id="remaining_amount" name="remaining_amount" value="<?= htmlspecialchars($defaultRemainingAmount); ?>" placeholder="0,00">
                    </div>
                </div>
            </div>
            <p class="muted" style="margin:0.75rem 0 0 0;">O sistema ajusta os valores automaticamente para corresponder ao líquido calculado.</p>
        </div>

        <button type="submit" class="button" style="margin-top:1.5rem;">Gerar holerite</button>
        <a href="?action=list_payrolls" class="button button-secondary" style="margin-left:0.5rem;">Cancelar</a>
    </form>
</section>
