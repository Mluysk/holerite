<?php
/** @var Holerite\Models\Payroll $payroll */
/** @var Holerite\Models\Employee|null $employee */
/** @var Holerite\Models\Company $company */

$referenceDate = \DateTimeImmutable::createFromFormat('Y-m-d', $payroll->getReferenceMonth() . '-01');
$monthNames = [
    '01' => 'janeiro',
    '02' => 'fevereiro',
    '03' => 'março',
    '04' => 'abril',
    '05' => 'maio',
    '06' => 'junho',
    '07' => 'julho',
    '08' => 'agosto',
    '09' => 'setembro',
    '10' => 'outubro',
    '11' => 'novembro',
    '12' => 'dezembro',
];
$referenceLabel = $referenceDate ? sprintf('%s-%s', $monthNames[$referenceDate->format('m')] ?? $referenceDate->format('m'), $referenceDate->format('y')) : $payroll->getReferenceMonth();
$paymentDate = $payroll->getPaymentDate()->format('d/m/Y');

$typeLabels = [
    'regular' => 'Mensal',
    'vacation' => 'Férias',
    'termination' => 'Desligamento',
    'thirteenth' => '13º salário',
];

$type = $payroll->getType();
$typeLabel = $typeLabels[$type] ?? ucfirst($type);
$thirteenthInstallmentLabel = null;
if ($type === 'thirteenth') {
    $installment = $payroll->getThirteenthInstallment();
    if ($installment === 'first') {
        $typeLabel .= ' (1ª parcela)';
        $thirteenthInstallmentLabel = '1ª parcela (adiantamento)';
    } elseif ($installment === 'second') {
        $typeLabel .= ' (2ª parcela)';
        $thirteenthInstallmentLabel = '2ª parcela (liquidação)';
    }
}

$allowances = [];
$deductions = [];
$code = 1;

$advanceAmount = $payroll->getAdvanceAmount();
$remainingAmount = $payroll->getRemainingAmount();
$valeDeductionAmount = $payroll->getValeDeduction();

$referenceValue = match ($payroll->getType()) {
    'vacation' => ($payroll->getVacationDays() ?? 0) . ' dias',
    'termination' => ($payroll->getWorkedDays() ?? 0) . ' dias',
    'thirteenth' => ($payroll->getThirteenthMonths() ?? 0) . ' meses' . ($thirteenthInstallmentLabel ? ' · ' . $thirteenthInstallmentLabel : ''),
    default => '1 mês',
};

$baseAllowance = [
    'code' => sprintf('%03d', $code++),
    'description' => 'Salário Base',
    'reference' => $referenceValue,
    'amount' => $payroll->getBaseSalary(),
];

if ($advanceAmount > 0.0) {
    $baseAllowance['note'] = 'Adiantamento aplicado — desconto listado ao lado.';
}

$allowances[] = $baseAllowance;

$thirteenthItem = null;
$vacationBonusValue = null;

foreach ($payroll->getItems() as $item) {
    $entry = [
        'code' => sprintf('%03d', $code++),
        'description' => $item->getDescription(),
        'reference' => '',
        'amount' => $item->getAmount(),
    ];

    if ($item->getType() === 'deduction') {
        $deductions[] = $entry;
        continue;
    }

    $normalizedDescription = function_exists('mb_strtolower') ? mb_strtolower($item->getDescription()) : strtolower($item->getDescription());
    if (
        $thirteenthItem === null
        && (
            str_contains($normalizedDescription, '13º')
            || str_contains($normalizedDescription, '13o')
            || str_contains($normalizedDescription, 'decimo terceiro')
            || str_contains($normalizedDescription, 'décimo terceiro')
        )
    ) {
        $thirteenthItem = $item;
    }

    if ($vacationBonusValue === null && str_contains($normalizedDescription, '1/3')) {
        $vacationBonusValue = $item->getAmount();
    }

    $allowances[] = $entry;
}

if ($advanceAmount > 0.0) {
    $advanceDeduction = [
        'code' => sprintf('%03d', $code++),
        'description' => 'Adiantamento salarial',
        'reference' => '',
        'amount' => $advanceAmount,
    ];

    array_unshift($deductions, $advanceDeduction);
}

$maxRows = max(count($allowances), count($deductions));
$allowances = array_pad($allowances, $maxRows, null);
$deductions = array_pad($deductions, $maxRows, null);

$grossTotal = $payroll->getBaseSalary() + $payroll->getTotalAllowances();
$displayTotalDeductions = $payroll->getTotalDeductions() + $advanceAmount;
$netWithAdvance = $grossTotal - $displayTotalDeductions;
$netOriginal = $payroll->getNetSalary();

$employeeCode = str_pad((string) ($employee?->getId() ?? 0), 5, '0', STR_PAD_LEFT);
$thirteenthAccrual = $payroll->getType() === 'thirteenth'
    ? $payroll->getThirteenthAccrual()
    : ($thirteenthItem?->getAmount() ?? $payroll->getThirteenthAccrual());
?>
<section class="receipt-wrapper">
    <div class="receipt">
        <header class="receipt-header">
            <div class="receipt-employer">
                <span class="label">Empregador</span>
                <strong><?= htmlspecialchars($company->getName()); ?></strong>
                <span><?= htmlspecialchars($company->getAddress()); ?></span>
                <span><?= htmlspecialchars($company->getCity()) . ' - ' . htmlspecialchars($company->getState()) . ' · CEP ' . htmlspecialchars($company->getZipCode()); ?></span>
                <span>CNPJ: <?= htmlspecialchars($company->getDocument()); ?></span>
                <span>Telefone: <?= htmlspecialchars($company->getPhone()); ?></span>
            </div>
            <div class="receipt-period">
                <span class="label">Recibo de Pagamento de Salário</span>
                <span>Referente ao mês de <strong><?= htmlspecialchars($referenceLabel); ?></strong></span>
                <span>Competência: <?= htmlspecialchars($payroll->getReferenceMonth()); ?></span>
                <span>Pagamento: <?= $paymentDate; ?></span>
                <span>Tipo: <?= htmlspecialchars($typeLabel); ?></span>
            </div>
        </header>

        <div class="receipt-meta">
            <div>
                <span><strong>Código:</strong> <?= $employeeCode; ?></span>
                <span><strong>Nome do funcionário:</strong> <?= htmlspecialchars($employee?->getName() ?? ''); ?></span>
                <span><strong>Departamento:</strong> <?= htmlspecialchars($employee?->getDepartment() ?? ''); ?></span>
            </div>
            <div>
                <span><strong>Função:</strong> <?= htmlspecialchars($employee?->getPosition() ?? ''); ?></span>
                <span><strong>Admissão:</strong> <?= $employee?->getHireDate()?->format('d/m/Y'); ?></span>
                <?php if ($payroll->getType() === 'vacation'): ?>
                    <span><strong>Dias de férias:</strong> <?= $payroll->getVacationDays() ?? 0; ?></span>
                    <span><strong>1/3 Constitucional:</strong> R$ <?= number_format($vacationBonusValue ?? ($payroll->getBaseSalary() / 3), 2, ',', '.'); ?></span>
                <?php elseif ($payroll->getType() === 'termination'): ?>
                    <span><strong>Dias trabalhados:</strong> <?= $payroll->getWorkedDays() ?? 0; ?></span>
                    <span><strong>Meses 13º:</strong> <?= $payroll->getThirteenthMonths() ?? 0; ?></span>
                    <span><strong>Justa causa:</strong> <?= $payroll->isJustCause() ? 'Sim' : 'Não'; ?></span>
                <?php elseif ($payroll->getType() === 'thirteenth'): ?>
                    <span><strong>Meses pagos:</strong> <?= $payroll->getThirteenthMonths() ?? 0; ?></span>
                    <?php if ($thirteenthInstallmentLabel !== null): ?>
                        <span><strong>Parcela:</strong> <?= htmlspecialchars($thirteenthInstallmentLabel); ?></span>
                    <?php endif; ?>
                    <span><strong>Total bruto 13º:</strong> R$ <?= number_format($payroll->getThirteenthAccrual(), 2, ',', '.'); ?></span>
                    <span><strong>Competência:</strong> <?= htmlspecialchars($payroll->getReferenceMonth()); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <table class="items">
            <thead>
            <tr>
                <th style="width: 12%;">Código</th>
                <th style="width: 44%;">Descrição</th>
                <th style="width: 16%;">Referência</th>
                <th style="width: 14%;" class="text-right">Vencimentos</th>
                <th style="width: 14%;" class="text-right">Descontos</th>
            </tr>
            </thead>
            <tbody>
            <?php for ($row = 0; $row < $maxRows; $row++): ?>
                <tr>
                    <?php $allowance = $allowances[$row]; ?>
                    <td><?= $allowance ? htmlspecialchars($allowance['code']) : '&nbsp;'; ?></td>
                    <td>
                        <?php if ($allowance): ?>
                            <span><?= htmlspecialchars($allowance['description']); ?></span>
                            <?php if (!empty($allowance['note'])): ?>
                                <small class="item-note">&bull; <?= htmlspecialchars($allowance['note']); ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            &nbsp;
                        <?php endif; ?>
                    </td>
                    <td><?= $allowance ? htmlspecialchars($allowance['reference']) : '&nbsp;'; ?></td>
                    <td class="text-right"><?= $allowance ? 'R$ ' . number_format((float) $allowance['amount'], 2, ',', '.') : '&nbsp;'; ?></td>
                    <?php $deduction = $deductions[$row]; ?>
                    <td class="text-right"><?= $deduction ? 'R$ ' . number_format((float) $deduction['amount'], 2, ',', '.') : '&nbsp;'; ?></td>
                </tr>
            <?php endfor; ?>
            </tbody>
            <tfoot>
            <tr>
                <th colspan="3" class="text-right">Total de vencimentos</th>
                <th class="text-right">R$ <?= number_format($grossTotal, 2, ',', '.'); ?></th>
                <th class="text-right">&nbsp;</th>
            </tr>
            <tr>
                <th colspan="3" class="text-right">Total de descontos</th>
                <th class="text-right">&nbsp;</th>
                <th class="text-right">R$ <?= number_format($displayTotalDeductions, 2, ',', '.'); ?></th>
            </tr>
            <tr>
                <th colspan="3" class="text-right">Valor líquido a receber</th>
                <th colspan="2" class="text-right highlight">R$ <?= number_format($netWithAdvance, 2, ',', '.'); ?></th>
            </tr>
            </tfoot>
        </table>

        <div class="receipt-summary">
            <div>
                <span>Total de vencimentos</span>
                <strong>R$ <?= number_format($grossTotal, 2, ',', '.'); ?></strong>
            </div>
            <div>
                <span>Total de descontos</span>
                <strong>R$ <?= number_format($displayTotalDeductions, 2, ',', '.'); ?></strong>
            </div>
            <div class="highlight">
                <span>Importância líquida a receber</span>
                <strong>R$ <?= number_format($netWithAdvance, 2, ',', '.'); ?></strong>
            </div>
            <div>
                <span>Valor líquido do período</span>
                <strong>R$ <?= number_format($netOriginal, 2, ',', '.'); ?></strong>
            </div>
        </div>

        <div class="receipt-bases">
            <div>
                <span>Base FGTS</span>
                <strong>R$ <?= number_format($payroll->getFgtsBase(), 2, ',', '.'); ?></strong>
            </div>
            <div>
                <span>FGTS do mês</span>
                <strong>R$ <?= number_format($payroll->getFgtsAmount(), 2, ',', '.'); ?></strong>
            </div>
            <div>
                <span>Base INSS</span>
                <strong>R$ <?= number_format($payroll->getInssBase(), 2, ',', '.'); ?></strong>
            </div>
            <div>
                <span>INSS</span>
                <strong>R$ <?= number_format($payroll->getInssAmount(), 2, ',', '.'); ?></strong>
            </div>
            <div>
                <span>Base IRRF</span>
                <strong>R$ <?= number_format($payroll->getIrrfBase(), 2, ',', '.'); ?></strong>
            </div>
            <div>
                <span>IRRF</span>
                <strong>R$ <?= number_format($payroll->getIrrfAmount(), 2, ',', '.'); ?></strong>
            </div>
            <div>
                <span>13º acumulado</span>
                <strong>R$ <?= number_format($thirteenthAccrual, 2, ',', '.'); ?></strong>
            </div>
        </div>

        <div class="receipt-installments">
            <div>
                <span>Adiantamento (1ª parcela)</span>
                <strong>R$ <?= number_format($advanceAmount, 2, ',', '.'); ?></strong>
            </div>
            <div>
                <span>Pagamento restante</span>
                <strong>R$ <?= number_format($remainingAmount, 2, ',', '.'); ?></strong>
            </div>
            <div>
                <span>Desconto de vale</span>
                <strong>R$ <?= number_format($valeDeductionAmount, 2, ',', '.'); ?></strong>
            </div>
        </div>

        <?php if ($payroll->getNotes() !== ''): ?>
            <div class="notes">
                <strong>Observações:</strong>
                <p><?= nl2br(htmlspecialchars($payroll->getNotes())); ?></p>
            </div>
        <?php endif; ?>

        <div class="acknowledgment">
            <p>Declaro ter recebido a importância líquida discriminada neste recibo.</p>
            <div class="signatures">
                <div>
                    ___________________________________________<br>
                    <span>Assinatura do colaborador</span>
                </div>
                <div>
                    ___________________________________________<br>
                    <span>Assinatura da empresa</span>
                </div>
            </div>
        </div>
    </div>

    <div class="actions">
        <a href="javascript:window.print();" class="button">Imprimir</a>
        <a href="?action=list_payrolls" class="button button-secondary">Voltar</a>
    </div>
</section>
