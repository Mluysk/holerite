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

$referenceValue = match ($payroll->getType()) {
    'vacation' => ($payroll->getVacationDays() ?? 0) . ' dias',
    'termination' => ($payroll->getWorkedDays() ?? 0) . ' dias',
    'thirteenth' => ($payroll->getThirteenthMonths() ?? 0) . ' meses' . ($thirteenthInstallmentLabel ? ' · ' . $thirteenthInstallmentLabel : ''),
    default => '1 mês',
};

$allowances[] = [
    'code' => sprintf('%03d', $code++),
    'description' => 'Salário Base',
    'reference' => $referenceValue,
    'amount' => $payroll->getBaseSalary(),
];

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

$maxRows = max(count($allowances), count($deductions));
$allowances = array_pad($allowances, $maxRows, null);
$deductions = array_pad($deductions, $maxRows, null);

$employeeCode = str_pad((string) ($employee?->getId() ?? 0), 5, '0', STR_PAD_LEFT);
$thirteenthAccrual = $payroll->getType() === 'thirteenth'
    ? $payroll->getThirteenthAccrual()
    : ($thirteenthItem?->getAmount() ?? $payroll->getThirteenthAccrual());
?>
<section>
    <div class="receipt">
        <div class="receipt-header">
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
        </div>

        <div class="employee-info">
            <div>
                <span><strong>Código</strong> <?= $employeeCode; ?></span>
                <span><strong>Nome</strong> <?= htmlspecialchars($employee?->getName() ?? ''); ?></span>
                <span><strong>Função</strong> <?= htmlspecialchars($employee?->getPosition() ?? ''); ?></span>
                <span><strong>Departamento</strong> <?= htmlspecialchars($employee?->getDepartment() ?? ''); ?></span>
            </div>
            <div>
                <span><strong>Admissão</strong> <?= $employee?->getHireDate()?->format('d/m/Y'); ?></span>
                <?php if ($payroll->getType() === 'vacation'): ?>
                    <span><strong>Dias de férias</strong> <?= $payroll->getVacationDays() ?? 0; ?></span>
                    <span><strong>1/3 Constitucional</strong> R$ <?= number_format($vacationBonusValue ?? ($payroll->getBaseSalary() / 3), 2, ',', '.'); ?></span>
                <?php elseif ($payroll->getType() === 'termination'): ?>
                    <span><strong>Dias trabalhados</strong> <?= $payroll->getWorkedDays() ?? 0; ?></span>
                    <span><strong>Meses 13º</strong> <?= $payroll->getThirteenthMonths() ?? 0; ?></span>
                    <span><strong>Justa causa</strong> <?= $payroll->isJustCause() ? 'Sim' : 'Não'; ?></span>
                <?php elseif ($payroll->getType() === 'thirteenth'): ?>
                    <span><strong>Meses pagos</strong> <?= $payroll->getThirteenthMonths() ?? 0; ?></span>
                    <?php if ($thirteenthInstallmentLabel !== null): ?>
                        <span><strong>Parcela</strong> <?= htmlspecialchars($thirteenthInstallmentLabel); ?></span>
                    <?php endif; ?>
                    <span><strong>Total bruto 13º</strong> R$ <?= number_format($payroll->getThirteenthAccrual(), 2, ',', '.'); ?></span>
                    <span><strong>Competência</strong> <?= htmlspecialchars($payroll->getReferenceMonth()); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <table class="items">
            <thead>
            <tr>
                <th style="width:10%;">Código</th>
                <th style="width:40%;">Descrição</th>
                <th style="width:15%;">Referência</th>
                <th style="width:17%;" class="text-right">Vencimentos</th>
                <th style="width:18%;" class="text-right">Descontos</th>
            </tr>
            </thead>
            <tbody>
            <?php for ($row = 0; $row < $maxRows; $row++): ?>
                <tr>
                    <?php $allowance = $allowances[$row]; ?>
                    <td><?= $allowance ? htmlspecialchars($allowance['code']) : '&nbsp;'; ?></td>
                    <td><?= $allowance ? htmlspecialchars($allowance['description']) : '&nbsp;'; ?></td>
                    <td><?= $allowance ? htmlspecialchars($allowance['reference']) : '&nbsp;'; ?></td>
                    <td class="text-right"><?= $allowance ? 'R$ ' . number_format((float) $allowance['amount'], 2, ',', '.') : '&nbsp;'; ?></td>
                    <?php $deduction = $deductions[$row]; ?>
                    <td class="text-right"><?= $deduction ? 'R$ ' . number_format((float) $deduction['amount'], 2, ',', '.') : '&nbsp;'; ?></td>
                </tr>
            <?php endfor; ?>
            </tbody>
            <tfoot>
            <tr>
                <th colspan="3" class="text-right">Totais</th>
                <th class="text-right">R$ <?= number_format($payroll->getBaseSalary() + $payroll->getTotalAllowances(), 2, ',', '.'); ?></th>
                <th class="text-right">R$ <?= number_format($payroll->getTotalDeductions(), 2, ',', '.'); ?></th>
            </tr>
            <tr>
                <th colspan="3" class="text-right">Valor líquido</th>
                <th colspan="2" class="text-right highlight">R$ <?= number_format($payroll->getNetSalary(), 2, ',', '.'); ?></th>
            </tr>
            </tfoot>
        </table>

        <div class="resume">
            <div>
                <span><strong>Total de vencimentos:</strong> R$ <?= number_format($payroll->getBaseSalary() + $payroll->getTotalAllowances(), 2, ',', '.'); ?></span>
                <span><strong>Total de descontos:</strong> R$ <?= number_format($payroll->getTotalDeductions(), 2, ',', '.'); ?></span>
                <span><strong>13º acumulado:</strong> R$ <?= number_format($thirteenthAccrual, 2, ',', '.'); ?></span>
            </div>
            <div>
                <span><strong>Base FGTS:</strong> R$ <?= number_format($payroll->getFgtsBase(), 2, ',', '.'); ?></span>
                <span><strong>FGTS do mês:</strong> R$ <?= number_format($payroll->getFgtsAmount(), 2, ',', '.'); ?></span>
            </div>
            <div>
                <span><strong>Base INSS:</strong> R$ <?= number_format($payroll->getInssBase(), 2, ',', '.'); ?></span>
                <span><strong>INSS:</strong> R$ <?= number_format($payroll->getInssAmount(), 2, ',', '.'); ?></span>
            </div>
            <div>
                <span><strong>Base IRRF:</strong> R$ <?= number_format($payroll->getIrrfBase(), 2, ',', '.'); ?></span>
                <span><strong>IRRF:</strong> R$ <?= number_format($payroll->getIrrfAmount(), 2, ',', '.'); ?></span>
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
