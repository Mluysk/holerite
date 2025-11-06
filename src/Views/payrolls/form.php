<?php
/** @var string $title */
/** @var Holerite\Models\Employee[] $employees */
/** @var array<string, mixed> $defaults */
/** @var string[]|null $allowanceOptions */
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
$defaultAdvanceRatioRaw = (string) ($defaults['advance_ratio'] ?? '');
$defaultAdvanceRatioCustomRaw = (string) ($defaults['advance_ratio_custom'] ?? '');
$defaultAdvanceReferenceId = (int) ($defaults['advance_reference_id'] ?? 0);
$linkedAdvanceRecord = isset($linkedAdvance) && $linkedAdvance instanceof Holerite\Models\Payroll ? $linkedAdvance : null;
if ($linkedAdvanceRecord !== null) {
    $defaultAdvanceReferenceId = $linkedAdvanceRecord->getId();
}
$selectedEmployee = null;
foreach ($employees as $employeeCandidate) {
    if ($employeeCandidate->getId() === $selectedEmployeeId) {
        $selectedEmployee = $employeeCandidate;
        break;
    }
}
$isAdvanceType = $selectedType === 'advance';
if ($isAdvanceType && $defaultAdvanceRatioRaw === '') {
    $defaultAdvanceRatioRaw = '0.4';
}
if ($isAdvanceType && $defaultAdvanceRatioRaw !== 'custom') {
    $defaultAdvanceRatioCustomRaw = '';
}
$linkedAdvanceAmountLabel = $linkedAdvanceRecord !== null
    ? 'R$ ' . number_format($linkedAdvanceRecord->getAdvanceAmount(), 2, ',', '.')
    : '';
$linkedAdvanceDateLabel = $linkedAdvanceRecord !== null
    ? $linkedAdvanceRecord->getPaymentDate()->format('d/m/Y')
    : '';
$linkedAdvanceReferenceLabel = $linkedAdvanceRecord !== null
    ? $linkedAdvanceRecord->getReferenceMonth()
    : '';
$defaultAdvanceRatioMode = '0.5';
$defaultAdvanceCustomPercent = '';

$normalizedRatio = null;
if ($defaultAdvanceRatioRaw !== '') {
    $candidate = (float) str_replace(',', '.', $defaultAdvanceRatioRaw);
    if ($candidate > 1.0) {
        $candidate /= 100.0;
    }

    if ($candidate > 0.0 && $candidate < 1.0) {
        $normalizedRatio = $candidate;
    } elseif (in_array($defaultAdvanceRatioRaw, ['40', '50'], true)) {
        $normalizedRatio = ((float) $defaultAdvanceRatioRaw) / 100.0;
    }
}

if ($normalizedRatio === null && $defaultAdvanceRatioRaw === 'custom' && $defaultAdvanceRatioCustomRaw !== '') {
    $customCandidate = (float) str_replace(',', '.', $defaultAdvanceRatioCustomRaw);
    if ($customCandidate > 1.0) {
        $customCandidate /= 100.0;
    }

    if ($customCandidate > 0.0 && $customCandidate < 1.0) {
        $normalizedRatio = $customCandidate;
    }
}

if ($normalizedRatio === null && $defaultAdvanceRatioCustomRaw !== '') {
    $customCandidate = (float) str_replace(',', '.', $defaultAdvanceRatioCustomRaw);
    if ($customCandidate > 1.0) {
        $customCandidate /= 100.0;
    }

    if ($customCandidate > 0.0 && $customCandidate < 1.0) {
        $normalizedRatio = $customCandidate;
        $defaultAdvanceRatioMode = 'custom';
    }
}

if ($normalizedRatio === null) {
    $advanceFloat = (float) ($defaults['advance_amount'] ?? 0);
    $remainingFloat = (float) ($defaults['remaining_amount'] ?? 0);
    $installmentTotal = $advanceFloat + $remainingFloat;

    if ($installmentTotal > 0.0) {
        $ratio = $advanceFloat / $installmentTotal;
        if ($ratio > 0.0 && $ratio < 1.0) {
            $normalizedRatio = $ratio;
        }
    }
}

if ($normalizedRatio !== null) {
    if (abs($normalizedRatio - 0.4) <= 0.01) {
        $defaultAdvanceRatioMode = '0.4';
    } elseif (abs($normalizedRatio - 0.5) <= 0.01) {
        $defaultAdvanceRatioMode = '0.5';
    } else {
        $defaultAdvanceRatioMode = 'custom';
        $defaultAdvanceCustomPercent = number_format($normalizedRatio * 100, 2, '.', '');
    }
} elseif ($defaultAdvanceRatioRaw === 'custom') {
    $defaultAdvanceRatioMode = 'custom';
    if ($defaultAdvanceRatioCustomRaw !== '') {
        $defaultAdvanceCustomPercent = number_format((float) str_replace(',', '.', $defaultAdvanceRatioCustomRaw), 2, '.', '');
    }
} elseif (in_array($defaultAdvanceRatioRaw, ['0.4', '40'], true)) {
    $defaultAdvanceRatioMode = '0.4';
} elseif (in_array($defaultAdvanceRatioRaw, ['0.5', '50'], true)) {
    $defaultAdvanceRatioMode = '0.5';
}

if ($isAdvanceType && $selectedEmployee !== null) {
    $baseSalaryValue = $selectedEmployee->getBaseSalary();
    $advanceCalculated = round($baseSalaryValue * 0.4, 2);
    $remainingCalculated = round(max(0.0, $baseSalaryValue - $advanceCalculated), 2);
    $defaultAdvanceAmount = number_format($advanceCalculated, 2, '.', '');
    $defaultRemainingAmount = number_format($remainingCalculated, 2, '.', '');
}

if ($isAdvanceType) {
    if ($defaultAdvanceRatioMode === '0.5') {
        $defaultAdvanceRatioMode = '0.4';
    }
    if ($defaultAdvanceRatioMode !== 'custom') {
        $defaultAdvanceCustomPercent = '';
    }
}

$defaultHasAdvance = $defaults['has_advance'] ?? null;
if ($defaultHasAdvance === null) {
    $defaultHasAdvance = in_array($selectedType, ['regular', 'advance'], true);
}
$defaultHasAdvance = (bool) $defaultHasAdvance;
if ($isAdvanceType) {
    $defaultHasAdvance = true;
}
$advanceLocked = $defaultAdvanceReferenceId > 0 || $isAdvanceType;
$remainingLocked = $isAdvanceType;
$defaultUseTransport = !empty($defaults['use_transport']);
$defaultTransportDays = max(0, min(31, (int) ($defaults['transport_days'] ?? 22)));
$defaultTransportTripCost = number_format((float) ($defaults['transport_trip_cost'] ?? 6.0), 2, '.', '');

if (!isset($pageScripts) || !is_array($pageScripts)) {
    $pageScripts = [];
}

$manualAllowanceOptions = [];
if (isset($allowanceOptions) && is_array($allowanceOptions)) {
    foreach ($allowanceOptions as $option) {
        if (is_string($option)) {
            $label = trim($option);
            if ($label !== '' && !in_array($label, $manualAllowanceOptions, true)) {
                $manualAllowanceOptions[] = $label;
            }
        }
    }
}

$allowanceOptionsJson = htmlspecialchars(json_encode($manualAllowanceOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');

$pageScripts[] = [
    'src' => 'js/payroll-form.js',
    'defer' => true,
];
?>
<section class="payroll-create">
    <div class="card payroll-create__hero">
        <div class="payroll-create__hero-icon" aria-hidden="true">
            <i class="bi bi-receipt-cutoff"></i>
        </div>
        <div class="payroll-create__hero-content">
            <h2><?= htmlspecialchars($title); ?></h2>
            <p class="muted">
                Selecione o colaborador, defina o tipo do holerite e informe valores adicionais. O recibo exibirá os dados da
                empresa <strong><?= htmlspecialchars($company->getName()); ?></strong> automaticamente.
            </p>
            <div class="payroll-create__hero-meta">
                <span><i class="bi bi-buildings"></i><?= htmlspecialchars($company->getName()); ?></span>
                <span><i class="bi bi-upc-scan"></i>CNPJ <?= htmlspecialchars($company->getDocument()); ?></span>
                <span><i class="bi bi-people"></i><?= count($employees); ?> colaboradores cadastrados</span>
            </div>
        </div>
    </div>

    <form method="post" action="?action=store_payroll" id="payroll-form" data-default-type="<?= htmlspecialchars($selectedType); ?>" class="payroll-create__form">
        <input type="hidden" name="advance_reference_id" value="<?= htmlspecialchars((string) $defaultAdvanceReferenceId); ?>">
        <?php if ($linkedAdvanceRecord !== null): ?>
            <div class="flash flash-info" style="margin-bottom:1.5rem;">
                Adiantamento registrado de <strong><?= htmlspecialchars($linkedAdvanceAmountLabel); ?></strong>
                pago em <?= htmlspecialchars($linkedAdvanceDateLabel); ?> (ref. <?= htmlspecialchars($linkedAdvanceReferenceLabel); ?>)
                será descontado automaticamente nesta folha mensal.
            </div>
        <?php endif; ?>
        <?php if ($isAdvanceType && $linkedAdvanceRecord === null): ?>
            <div class="flash flash-info" style="margin-bottom:1.5rem;">
                Este holerite gera a via de adiantamento salarial — por padrão com 40% do salário base —,
                mas você pode ajustar para 50% ou definir um percentual personalizado. Ao emitir o holerite mensal,
                o sistema abaterá automaticamente o valor já adiantado como segunda parcela.
            </div>
        <?php endif; ?>
        <div class="payroll-create__grid">
            <div class="payroll-create__main">
                <div class="card form-card" data-hide-when-advance="true" style="display: <?= $isAdvanceType ? 'none' : 'block'; ?>;">
                    <header class="form-card__header">
                        <span class="form-card__icon" aria-hidden="true"><i class="bi bi-clipboard-check"></i></span>
                        <div>
                            <h3>Dados do holerite</h3>
                            <p class="muted">Informações principais usadas para calcular e identificar esta folha de pagamento.</p>
                        </div>
                    </header>
                    <div class="form-card__body">
                        <div class="form-grid form-grid--auto">
                            <div class="form-field">
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
                            <div class="form-field">
                                <label for="reference_month">Mês de referência</label>
                                <input type="month" id="reference_month" name="reference_month" value="<?= htmlspecialchars($referenceMonth); ?>" required>
                            </div>
                            <div class="form-field">
                                <label for="payment_date">Data de pagamento</label>
                                <input type="date" id="payment_date" name="payment_date" value="<?= date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-field">
                                <label for="type">Tipo do holerite</label>
                                <select name="type" id="type">
                                    <option value="regular" <?= $selectedType === 'regular' ? 'selected' : ''; ?>>Mensal</option>
                                    <option value="advance" <?= $selectedType === 'advance' ? 'selected' : ''; ?>>Adiantamento</option>
                                    <option value="vacation" <?= $selectedType === 'vacation' ? 'selected' : ''; ?>>Férias</option>
                                    <option value="termination" <?= $selectedType === 'termination' ? 'selected' : ''; ?>>Desligamento</option>
                                    <option value="thirteenth" <?= $selectedType === 'thirteenth' ? 'selected' : ''; ?>>13º salário</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="vacation-fields" class="card form-card" style="display:none;">
                    <header class="form-card__header">
                        <span class="form-card__icon" aria-hidden="true"><i class="bi bi-umbrella"></i></span>
                        <div>
                            <h3>Informações de férias</h3>
                            <p class="muted">Informe os dias concedidos para calcular o adicional de 1/3 proporcional.</p>
                        </div>
                    </header>
                    <div class="form-card__body">
                        <div class="form-grid form-grid--two">
                            <div class="form-field">
                                <label for="vacation_days">Dias de férias</label>
                                <input type="number" id="vacation_days" name="vacation_days" min="1" max="30" value="<?= htmlspecialchars((string) $defaultVacationDays); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div id="termination-fields" class="card form-card" style="display:none;">
                    <header class="form-card__header">
                        <span class="form-card__icon" aria-hidden="true"><i class="bi bi-door-open"></i></span>
                        <div>
                            <h3>Informações de desligamento</h3>
                            <p class="muted">Defina dias trabalhados e meses para cálculo proporcional do 13º.</p>
                        </div>
                    </header>
                    <div class="form-card__body">
                        <div class="form-grid form-grid--two">
                            <div class="form-field">
                                <label for="worked_days">Dias trabalhados no mês</label>
                                <input type="number" id="worked_days" name="worked_days" min="0" max="30" value="<?= htmlspecialchars((string) $defaultWorkedDays); ?>">
                            </div>
                            <div class="form-field">
                                <label for="thirteenth_months">Meses para cálculo do 13º</label>
                                <input type="number" id="thirteenth_months" name="thirteenth_months" min="0" max="12" value="<?= htmlspecialchars((string) $defaultThirteenthMonths); ?>">
                            </div>
                        </div>
                        <label class="muted form-card__checkbox">
                            <input type="checkbox" name="just_cause" id="just_cause" value="1" <?= $defaultJustCause ? 'checked' : ''; ?>>
                            Desligamento por justa causa (remove pagamento de 13º proporcional)
                        </label>
                    </div>
                </div>

                <div id="thirteenth-fields" class="card form-card" style="display:none;">
                    <header class="form-card__header">
                        <span class="form-card__icon" aria-hidden="true"><i class="bi bi-gift"></i></span>
                        <div>
                            <h3>Informações do 13º salário</h3>
                            <p class="muted">Escolha a parcela e os meses acumulados para calcular automaticamente.</p>
                        </div>
                    </header>
                    <div class="form-card__body">
                        <div class="form-grid form-grid--two">
                            <div class="form-field">
                                <label for="thirteenth_months_input">Meses acumulados</label>
                                <input type="number" id="thirteenth_months_input" min="1" max="12" value="<?= htmlspecialchars((string) $defaultThirteenthMonths); ?>">
                            </div>
                            <div class="form-field">
                                <label for="thirteenth_installment">Parcela</label>
                                <select name="thirteenth_installment" id="thirteenth_installment">
                                    <option value="first" <?= $defaultThirteenthInstallment === 'first' ? 'selected' : ''; ?>>1ª parcela (adiantamento)</option>
                                    <option value="second" <?= $defaultThirteenthInstallment === 'second' ? 'selected' : ''; ?>>2ª parcela (liquidação)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card form-card" data-hide-when-advance="true" style="display: <?= $isAdvanceType ? 'none' : 'block'; ?>;">
                    <header class="form-card__header">
                        <span class="form-card__icon" aria-hidden="true"><i class="bi bi-cash-stack"></i></span>
                        <div>
                            <h3>Proventos manuais</h3>
                            <p class="muted">Selecione ganhos cadastrados na aba de ajustes, como horas extras, adicional noturno, comissões, bônus ou gratificações.</p>
                        </div>
                    </header>
                    <div class="form-card__body">
                        <div
                            id="allowances"
                            class="dynamic-list"
                            data-empty-label="Nenhum provento manual informado."
                            data-options="<?= $allowanceOptionsJson; ?>"
                        ></div>
                        <button type="button" class="button dynamic-add button-success" data-target="allowances" data-type="allowance">
                            <i class="bi bi-plus-circle"></i> Adicionar provento
                        </button>
                    </div>
                </div>

                <div class="card form-card" data-hide-when-advance="true" style="display: <?= $isAdvanceType ? 'none' : 'block'; ?>;">
                    <header class="form-card__header">
                        <span class="form-card__icon" aria-hidden="true"><i class="bi bi-bag-check"></i></span>
                        <div>
                            <h3>Vale de produtos &amp; descontos</h3>
                            <p class="muted">Organize descontos fixos da folha e registre outros abatimentos necessários.</p>
                        </div>
                    </header>
                    <div class="form-card__body">
                        <div class="form-grid form-grid--two">
                            <div class="form-subcard">
                                <h4>Vale de produtos</h4>
                                <p class="muted">Soma dos vales de compras, alimentação ou produtos abatidos neste holerite.</p>
                                <div class="form-field">
                                    <label for="vale_deduction">Total de vales de produtos</label>
                                    <input type="number" min="0" step="0.01" id="vale_deduction" name="vale_deduction" value="<?= htmlspecialchars($defaultValeDeduction); ?>" placeholder="0,00">
                                </div>
                            </div>
                            <div class="form-subcard">
                                <h4>Descontos adicionais</h4>
                                <p class="muted">Inclua faltas, atrasos, convênios ou outros descontos manuais.</p>
                                <div id="deductions" class="dynamic-list" data-empty-label="Nenhum desconto manual informado."></div>
                                <button type="button" class="button dynamic-add button-warning" data-target="deductions" data-type="deduction">
                                    <i class="bi bi-dash-circle"></i> Adicionar desconto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card form-card">
                    <header class="form-card__header">
                        <span class="form-card__icon" aria-hidden="true"><i class="bi bi-bus-front"></i></span>
                        <div>
                            <h3>Vale-transporte</h3>
                            <p class="muted">Calcule o benefício considerando dias úteis, ida e volta e limite legal de 6%.</p>
                        </div>
                    </header>
                    <div class="form-card__body">
                        <input type="hidden" name="use_transport" value="0">
                        <label class="muted form-card__checkbox" for="use_transport">
                            <input type="checkbox" id="use_transport" name="use_transport" value="1" <?= $defaultUseTransport ? 'checked' : ''; ?>>
                            Utilizar vale-transporte (ida e volta)
                        </label>
                        <div id="transport-fields" class="form-card__toggle" style="display: <?= $defaultUseTransport ? 'block' : 'none'; ?>;">
                            <div class="form-grid form-grid--two">
                                <div class="form-field">
                                    <label for="transport_days">Dias com ida e volta</label>
                                    <input type="number" min="0" max="31" step="1" id="transport_days" name="transport_days" value="<?= htmlspecialchars((string) $defaultTransportDays); ?>" data-default-value="<?= htmlspecialchars((string) $defaultTransportDays); ?>">
                                </div>
                                <div class="form-field">
                                    <label for="transport_trip_cost">Valor por passagem (R$)</label>
                                    <input type="number" min="0" step="0.01" id="transport_trip_cost" name="transport_trip_cost" value="<?= htmlspecialchars($defaultTransportTripCost); ?>" data-default-value="<?= htmlspecialchars($defaultTransportTripCost); ?>">
                                </div>
                            </div>
                            <div class="transport-highlights">
                                <p>Dias: <strong id="transport-day-count">0</strong></p>
                                <p>Passagens: <strong id="transport-trip-count">0</strong></p>
                                <p>Custo estimado: <strong id="transport-cost-display">R$ 0,00</strong></p>
                                <p>Desconto ao colaborador (até 6%): <strong id="transport-deduction-display">R$ 0,00</strong></p>
                                <p>Limite legal de desconto (6%): <strong id="transport-limit-display">R$ 0,00</strong></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card form-card">
                    <header class="form-card__header">
                        <span class="form-card__icon" aria-hidden="true"><i class="bi bi-journal-text"></i></span>
                        <div>
                            <h3>Observações gerais</h3>
                            <p class="muted">Registre comentários importantes que também serão exibidos no holerite impresso.</p>
                        </div>
                    </header>
                    <div class="form-card__body">
                        <div class="form-field">
                            <label for="notes">Observações</label>
                            <textarea id="notes" name="notes" rows="3" placeholder="Informe observações importantes sobre este holerite..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="payroll-create__aside">
                <div class="card payroll-summary">
                    <header class="form-card__header">
                        <span class="form-card__icon" aria-hidden="true"><i class="bi bi-graph-up"></i></span>
                        <div>
                            <h3>Resumo em tempo real</h3>
                            <p class="muted">Acompanhe automaticamente os cálculos conforme preencher a folha.</p>
                        </div>
                    </header>
                    <ul class="payroll-summary__list">
                        <li><span>Salário base calculado</span><strong id="base-salary">R$ 0,00</strong></li>
                        <li><span>Proventos automáticos</span><strong id="auto-allowances">R$ 0,00</strong></li>
                        <li><span>Proventos manuais</span><strong id="total-allowances">R$ 0,00</strong></li>
                        <li><span>Descontos manuais</span><strong id="total-deductions">R$ 0,00</strong></li>
                        <li><span>Vale-transporte — custo estimado</span><strong id="transport-cost-summary">R$ 0,00</strong></li>
                        <li><span>Vale-transporte — desconto aplicado</span><strong id="transport-deduction-summary">R$ 0,00</strong></li>
                        <li><span>Vales de produtos</span><strong id="vale-deduction-total">R$ 0,00</strong></li>
                        <li><span>Total de vales (produtos + transporte)</span><strong id="vale-deduction-sum">R$ 0,00</strong></li>
                        <li><span>INSS estimado</span><strong id="inss-amount">R$ 0,00</strong><small id="inss-base">Base: R$ 0,00</small></li>
                        <li><span>IRRF estimado</span><strong id="irrf-amount">R$ 0,00</strong><small id="irrf-base">Base: R$ 0,00</small></li>
                        <li><span>FGTS do mês</span><strong id="fgts-amount">R$ 0,00</strong><small id="fgts-base">Base: R$ 0,00</small></li>
                        <li><span>13º acumulado</span><strong id="thirteenth-amount">R$ 0,00</strong></li>
                        <li class="payroll-summary__highlight"><span>Valor líquido estimado</span><strong id="net-salary">R$ 0,00</strong></li>
                        <li><span>1ª parcela (adiantamento)</span><strong id="advance-display">R$ 0,00</strong></li>
                        <li><span>2ª parcela (restante)</span><strong id="remaining-display">R$ 0,00</strong></li>
                    </ul>
                    <p class="muted" id="advance-note" style="display: <?= $defaultHasAdvance || $isAdvanceType ? 'block' : 'none'; ?>;">
                        <?php if ($isAdvanceType): ?>
                            Este recibo registra a 1ª parcela do salário com base no percentual escolhido acima; o restante será abatido ao gerar o holerite mensal.
                        <?php else: ?>
                            Com o adiantamento habilitado, escolha antecipar 40%, 50% ou defina um percentual personalizado do bruto; o desconto aparecerá logo abaixo do salário base no holerite impresso.
                        <?php endif; ?>
                    </p>
                    <div id="automatic-descriptions" class="muted payroll-summary__messages"></div>
                </div>

                <div class="card form-card">
                    <header class="form-card__header">
                        <span class="form-card__icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                        <div>
                            <h3>Parcelamento do pagamento</h3>
                            <p class="muted">Controle a liberação do adiantamento e da segunda parcela de forma visual.</p>
                        </div>
                    </header>
                    <div class="form-card__body">
                        <input type="hidden" name="has_advance" value="<?= $isAdvanceType ? '1' : '0'; ?>">
                        <label class="muted form-card__checkbox" for="has_advance">
                            <input type="checkbox" id="has_advance" name="has_advance" value="1" <?= $defaultHasAdvance ? 'checked' : ''; ?> <?= $isAdvanceType ? 'disabled' : ''; ?>>
                            <?= $isAdvanceType ? 'Adiantamento de 40% aplicado automaticamente' : 'Registrar adiantamento salarial (1ª parcela)'; ?>
                        </label>
                        <div id="advance-fields" class="form-card__toggle" style="display: <?= ($defaultHasAdvance || $isAdvanceType) ? 'block' : 'none'; ?>;">
                            <div class="form-grid form-grid--thirds">
                                <div class="form-field">
                                    <label for="advance_ratio">Percentual do adiantamento</label>
                                    <select name="advance_ratio" id="advance_ratio" <?= $linkedAdvanceRecord !== null ? 'disabled' : ''; ?> data-locked="<?= $linkedAdvanceRecord !== null ? 'true' : 'false'; ?>">
                                        <option value="0.4" <?= $defaultAdvanceRatioMode === '0.4' ? 'selected' : ''; ?>>40% do bruto</option>
                                        <option value="0.5" <?= $defaultAdvanceRatioMode === '0.5' ? 'selected' : ''; ?>>50% do bruto</option>
                                        <option value="custom" <?= $defaultAdvanceRatioMode === 'custom' ? 'selected' : ''; ?>>Personalizar</option>
                                    </select>
                                </div>
                                <div class="form-field" id="advance_ratio_custom_wrapper" style="display: <?= $defaultAdvanceRatioMode === 'custom' ? 'block' : 'none'; ?>;">
                                    <label for="advance_ratio_custom">Percentual personalizado (%)</label>
                                    <input type="number" min="1" max="99" step="0.01" id="advance_ratio_custom" name="advance_ratio_custom" value="<?= htmlspecialchars($defaultAdvanceCustomPercent); ?>" placeholder="Ex.: 45,00" <?= $defaultAdvanceRatioMode === 'custom' && $linkedAdvanceRecord === null ? '' : 'disabled'; ?> data-locked="<?= $linkedAdvanceRecord !== null ? 'true' : 'false'; ?>" data-initial-value="<?= htmlspecialchars($defaultAdvanceCustomPercent); ?>">
                                </div>
                                <div class="form-field">
                                    <label for="advance_amount">Adiantamento (1ª parcela)</label>
                                    <input type="number" min="0" step="0.01" id="advance_amount" name="advance_amount" value="<?= htmlspecialchars($defaultAdvanceAmount); ?>" placeholder="0,00" data-locked="<?= $advanceLocked ? 'true' : 'false'; ?>" <?= $advanceLocked ? 'readonly' : ''; ?>>
                                </div>
                                <div class="form-field">
                                    <label for="remaining_amount">Pagamento restante (2ª parcela)</label>
                                    <input type="number" min="0" step="0.01" id="remaining_amount" name="remaining_amount" value="<?= htmlspecialchars($defaultRemainingAmount); ?>" placeholder="0,00" data-locked="<?= $remainingLocked ? 'true' : 'false'; ?>" <?= $remainingLocked ? 'readonly' : ''; ?>>
                                </div>
                            </div>
                            <p class="muted">O sistema ajusta os valores automaticamente para corresponder ao líquido calculado.</p>
                        </div>
                    </div>
                </div>

                <div class="payroll-create__actions">
                    <button type="submit" class="button button-primary"><i class="bi bi-journal-check"></i> Gerar holerite</button>
                    <a href="?action=list_payrolls" class="button button-secondary"><i class="bi bi-arrow-left-circle"></i> Cancelar</a>
                </div>
            </aside>
        </div>
    </form>
</section>
