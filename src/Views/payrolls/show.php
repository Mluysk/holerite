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
$valeDeductionAmount = $payroll->getValeDeduction();

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

$salaryNote = $advanceAmount > 0.0 ? 'Adiantamento habilitado — desconto listado ao lado.' : null;
$appendItem($items, $code, 'Salário base', $referenceValue, $payroll->getBaseSalary(), null, $salaryNote);

foreach ($payroll->getItems() as $item) {
    if ($item->getType() === 'deduction') {
        $appendItem($items, $code, $item->getDescription(), '', null, $item->getAmount());
        continue;
    }

    $appendItem($items, $code, $item->getDescription(), '', $item->getAmount(), null);
}

if ($advanceAmount > 0.0) {
    $appendItem($items, $code, 'Adiantamento salarial', '', null, $advanceAmount);
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
if ($advanceAmount > 0.0) {
    $messages[] = 'Adiantamento: R$ ' . number_format($advanceAmount, 2, ',', '.');
    $messages[] = 'Pagamento restante: R$ ' . number_format($remainingAmount, 2, ',', '.');
}
if ($transportDeduction > 0.0) {
    $messages[] = 'Vale-transporte (6%): R$ ' . number_format($transportDeduction, 2, ',', '.');
}
if ($manualValeDeduction > 0.0) {
    $messages[] = 'Vales informados: R$ ' . number_format($manualValeDeduction, 2, ',', '.');
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
