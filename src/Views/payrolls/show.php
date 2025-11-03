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

$advanceAmount = $payroll->getAdvanceAmount();
$remainingAmount = $payroll->getRemainingAmount();
$manualValeDeduction = $payroll->getManualValeDeduction();
$transportDeduction = $payroll->getTransportDeduction();
$transportTotalCost = $payroll->getTransportTotalCost();
$transportLegalLimit = $payroll->getTransportDeductionLimit();
$transportCompanyShare = max(0.0, round($transportTotalCost - $transportDeduction, 2));
$valeDeductionAmount = $payroll->getValeDeduction();
$transportDays = $payroll->getTransportDays();
$transportTrips = $payroll->getTransportTrips();
$transportTripCost = $payroll->getTransportTripCost();
$advanceRatioLabel = null;
$installmentTotal = $advanceAmount + max($remainingAmount, 0.0);

if ($advanceAmount > 0.0 && $installmentTotal > 0.0) {
    $percentage = round(($advanceAmount / $installmentTotal) * 100);

    if (abs($percentage - 40) <= 1) {
        $advanceRatioLabel = '40%';
    } elseif (abs($percentage - 50) <= 1) {
        $advanceRatioLabel = '50%';
    }
}

$referenceValue = match ($payroll->getType()) {
    'vacation' => ($payroll->getVacationDays() ?? 0) . ' dias',
    'termination' => ($payroll->getWorkedDays() ?? 0) . ' dias',
    'thirteenth' => ($payroll->getThirteenthMonths() ?? 0) . ' meses' . ($thirteenthInstallmentLabel ? ' · ' . $thirteenthInstallmentLabel : ''),
    default => '1 mês',
};

$items = [];
$code = 1;
$appendItem = static function (array &$items, int &$code, string $description, string $reference, ?float $allowance, ?float $deduction, ?string $note = null): void {
    $items[] = [
        'code' => sprintf('%03d', $code++),
        'description' => $description,
        'reference' => $reference,
        'allowance' => $allowance,
        'deduction' => $deduction,
        'note' => $note,
    ];
};

$formatPercentage = static function (float $ratio): string {
    return number_format($ratio, 2, ',', '.') . '%';
};

$grossEarningsBase = $payroll->getBaseSalary() + $payroll->getTotalAllowances();

$resolveDeductionReference = static function (string $description, float $amount) use ($payroll, $grossEarningsBase, $installmentTotal, $formatPercentage): string {
    if ($amount <= 0.0) {
        return '';
    }

    $normalized = strtolower($description);
    $base = 0.0;

    if (str_starts_with($normalized, 'inss')) {
        $base = $payroll->getInssBase();
    } elseif (str_starts_with($normalized, 'irrf')) {
        $base = $payroll->getIrrfBase();
    } elseif (str_contains($normalized, 'vale-transporte')) {
        $base = $payroll->getBaseSalary();
    } elseif (str_contains($normalized, 'adiantamento')) {
        $base = $installmentTotal;
    } elseif (str_contains($normalized, 'vale')) {
        $base = $grossEarningsBase;
    } else {
        $base = $grossEarningsBase;
    }

    if ($base <= 0.0) {
        return '';
    }

    return $formatPercentage(($amount / $base) * 100);
};

$appendItem($items, $code, 'Salário base', $referenceValue, $payroll->getBaseSalary(), null);

foreach ($payroll->getItems() as $item) {
    if ($item->getType() === 'deduction') {
        $reference = $resolveDeductionReference($item->getDescription(), $item->getAmount());
        $appendItem($items, $code, $item->getDescription(), $reference, null, $item->getAmount());
        continue;
    }

    $appendItem($items, $code, $item->getDescription(), '', $item->getAmount(), null);
}

if ($advanceAmount > 0.0) {
    $description = 'Adiantamento salarial';
    if ($advanceRatioLabel !== null) {
        $description .= ' (' . $advanceRatioLabel . ')';
    }

    $advanceReference = '';
    if ($installmentTotal > 0.0) {
        $advanceReference = $formatPercentage(($advanceAmount / $installmentTotal) * 100);
    }

    $appendItem($items, $code, $description, $advanceReference, null, $advanceAmount);
}

$grossTotal = $payroll->getBaseSalary() + $payroll->getTotalAllowances();
$displayTotalDeductions = $payroll->getTotalDeductions() + $advanceAmount;
$netWithAdvance = $grossTotal - $displayTotalDeductions;
$netOriginal = $payroll->getNetSalary();

$employeeCode = str_pad((string) ($employee?->getId() ?? 0), 5, '0', STR_PAD_LEFT);
$employeeDepartment = $employee?->getDepartment() ?? '';
$employeeFunction = $employee?->getPosition() ?? '';
$employeeCbo = $employeeDepartment !== '' ? $employeeDepartment : '-';

$messages = [];
if ($transportTotalCost > 0.0) {
    $transportDetails = [];
    if ($transportDays > 0) {
        $transportDetails[] = $transportDays . ' dia' . ($transportDays === 1 ? '' : 's');
    }
    if ($transportTrips > 0) {
        $transportDetails[] = $transportTrips . ' passagem' . ($transportTrips === 1 ? '' : 's');
    }
    if ($transportTripCost > 0.0) {
        $transportDetails[] = 'R$ ' . number_format($transportTripCost, 2, ',', '.') . ' por passagem';
    }

    $messageParts = [
        'custo total R$ ' . number_format($transportTotalCost, 2, ',', '.'),
        'desconto aplicado R$ ' . number_format($transportDeduction, 2, ',', '.'),
    ];

    if ($transportLegalLimit > 0.0) {
        $messageParts[] = 'limite legal (6%) R$ ' . number_format($transportLegalLimit, 2, ',', '.');
    }

    if ($transportCompanyShare > 0.0) {
        $messageParts[] = 'custeado pela empresa R$ ' . number_format($transportCompanyShare, 2, ',', '.');
    }

    if ($transportDetails !== []) {
        $messageParts[] = implode(' · ', $transportDetails);
    }

    $messages[] = 'Vale-transporte: ' . implode(' · ', $messageParts);
    $messages[] = 'O vale-transporte permite desconto de até 6% do salário base, conforme legislação brasileira.';
}
if ($manualValeDeduction > 0.0) {
    $messages[] = 'Vales de produtos: R$ ' . number_format($manualValeDeduction, 2, ',', '.');
}
if ($valeDeductionAmount > 0.0 && ($transportDeduction > 0.0 || $manualValeDeduction > 0.0)) {
    $messages[] = 'Total de vales aplicados: R$ ' . number_format($valeDeductionAmount, 2, ',', '.');
}
if ($payroll->getNotes() !== '') {
    $messages[] = nl2br(htmlspecialchars($payroll->getNotes()));
}
if ($typeLabel) {
    $messages[] = 'Tipo de folha: ' . htmlspecialchars($typeLabel);
}

if ($employeeDepartment !== '') {
    $messages[] = 'Departamento: ' . htmlspecialchars($employeeDepartment);
}

if ($employee?->getHireDate() !== null) {
    $messages[] = 'Admissão: ' . $employee->getHireDate()->format('d/m/Y');
}

if ($payroll->getType() === 'vacation') {
    $messages[] = 'Férias: ' . ($payroll->getVacationDays() ?? 0) . ' dias';
}

if ($payroll->getType() === 'termination') {
    $messages[] = 'Trabalhados: ' . ($payroll->getWorkedDays() ?? 0) . ' dias';
    $messages[] = '13º meses: ' . ($payroll->getThirteenthMonths() ?? 0);
}

if ($payroll->getType() === 'thirteenth') {
    $messages[] = 'Meses 13º: ' . ($payroll->getThirteenthMonths() ?? 0);
}
?>
<section class="receipt-wrapper">
    <?php foreach ([
        'Via da empresa' => 'Via da empresa',
        'Via do funcionário' => 'Via do funcionário',
    ] as $copyLabel): ?>
        <div class="holerite">
            <div class="holerite-copy-label">
                <span><?= htmlspecialchars($copyLabel); ?></span>
                <span class="holerite-copy-label-subtitle">Recibo emitido em <?= htmlspecialchars($paymentDate); ?></span>
            </div>

            <table class="holerite-table holerite-header">
                <tr>
                    <td colspan="2" class="holerite-title">Empregador</td>
                    <td colspan="2" class="holerite-receipt-title">Recibo de Pagamento de Salário</td>
                </tr>
                <tr>
                    <td>Nome: <?= htmlspecialchars($company->getName()); ?></td>
                    <td>CNPJ: <?= htmlspecialchars($company->getDocument()); ?></td>
                    <td colspan="2">Referente ao mês/ano: <?= htmlspecialchars($referenceLabel); ?></td>
                </tr>
                <tr>
                    <td colspan="2">Endereço: <?= htmlspecialchars($company->getAddress()); ?></td>
                    <td>Competência: <?= htmlspecialchars($payroll->getReferenceMonth()); ?></td>
                    <td>Pagamento: <?= htmlspecialchars($paymentDate); ?></td>
                </tr>
            </table>

            <table class="holerite-table holerite-employee">
                <tr>
                    <td style="width: 16%;">CÓDIGO: <?= $employeeCode; ?></td>
                    <td style="width: 44%;">NOME FUNCIONÁRIO: <?= htmlspecialchars($employee?->getName() ?? ''); ?></td>
                    <td style="width: 20%;">CBO: <?= htmlspecialchars($employeeCbo); ?></td>
                    <td style="width: 20%;">FUNÇÃO: <?= htmlspecialchars($employeeFunction); ?></td>
                </tr>
            </table>

            <table class="holerite-table holerite-items">
                <thead>
                <tr>
                    <th style="width: 9%;">Cód.</th>
                    <th style="width: 43%;">Descrição</th>
                    <th style="width: 16%;">Referência</th>
                    <th style="width: 16%;">Proventos</th>
                    <th style="width: 16%;">Descontos</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $entry): ?>
                    <tr>
                        <td><?= htmlspecialchars($entry['code']); ?></td>
                        <td>
                            <?= htmlspecialchars($entry['description']); ?>
                            <?php if (!empty($entry['note'])): ?>
                                <div class="holerite-item-note">&#9679; <?= htmlspecialchars($entry['note']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($entry['reference']); ?></td>
                        <td class="text-right">
                            <?= $entry['allowance'] !== null ? 'R$ ' . number_format((float) $entry['allowance'], 2, ',', '.') : '&nbsp;'; ?>
                        </td>
                        <td class="text-right<?= ($entry['deduction'] === null || $entry['deduction'] == 0.0) ? ' holerite-empty' : ''; ?>">
                            <?= $entry['deduction'] !== null && $entry['deduction'] != 0.0 ? 'R$ ' . number_format((float) $entry['deduction'], 2, ',', '.') : '&nbsp;'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <table class="holerite-table holerite-totals">
                <tr>
                    <td class="holerite-messages" rowspan="2">
                        <strong>Obs.</strong>
                        <?php if ($messages === []): ?>
                            <div>-</div>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <div><?= $message; ?></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <td>Total dos vencimentos</td>
                    <td class="text-right">R$ <?= number_format($grossTotal, 2, ',', '.'); ?></td>
                    <td>Total dos descontos</td>
                    <td class="text-right">R$ <?= number_format($displayTotalDeductions, 2, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td colspan="2" class="holerite-liquid">Líquido a Receber -&gt;</td>
                    <td colspan="2" class="holerite-liquid text-right">R$ <?= number_format($netWithAdvance, 2, ',', '.'); ?></td>
                </tr>
            </table>

            <table class="holerite-table holerite-bases">
                <tr>
                    <td>Salário base<br><span>R$ <?= number_format($payroll->getBaseSalary(), 2, ',', '.'); ?></span></td>
                    <td>Base Cálc. FGTS<br><span>R$ <?= number_format($payroll->getFgtsBase(), 2, ',', '.'); ?></span></td>
                    <td>FGTS do mês<br><span>R$ <?= number_format($payroll->getFgtsAmount(), 2, ',', '.'); ?></span></td>
                    <td>Base Cálc. INSS<br><span>R$ <?= number_format($payroll->getInssBase(), 2, ',', '.'); ?></span></td>
                    <td>Base Cálc. IRRF<br><span>R$ <?= number_format($payroll->getIrrfBase(), 2, ',', '.'); ?></span></td>
                </tr>
            </table>

            <div class="holerite-signature">
                <div class="holerite-declaration">
                    DECLARO TER RECEBIDO A IMPORTÂNCIA LÍQUIDA DISCRIMINADA NESTE RECIBO.
                </div>
                <div class="holerite-signature-line">
                    ___________________________________________
                    <span>Assinatura do funcionário</span>
                </div>
                <div class="holerite-date">DATA: <?= htmlspecialchars($paymentDate); ?></div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="actions">
        <a href="javascript:window.print();" class="button"><i class="bi bi-printer"></i> Imprimir</a>
        <a href="?action=list_payrolls" class="button button-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>
</section>
