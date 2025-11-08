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
$referenceLabel = $referenceDate
    ? sprintf('%s-%s', $monthNames[$referenceDate->format('m')] ?? $referenceDate->format('m'), $referenceDate->format('y'))
    : $payroll->getReferenceMonth();

$paymentDate = $payroll->getPaymentDate()->format('d/m/Y');
$baseSalary = $payroll->getBaseSalary();
$advanceAmount = $payroll->getAdvanceAmount();
$employeeCode = str_pad((string) ($employee?->getId() ?? 0), 5, '0', STR_PAD_LEFT);
$employeeDepartment = $employee?->getDepartment() ?? '';
$employeeFunction = $employee?->getPosition() ?? '';
$employeeCbo = $employeeDepartment !== '' ? $employeeDepartment : '-';

$advanceRatio = 0.0;
if ($baseSalary > 0.0) {
    $advanceRatio = round(($advanceAmount / $baseSalary) * 100, 2);
}

$advanceReference = $advanceRatio > 0.0
    ? number_format($advanceRatio, 2, ',', '.') . '% do salário base'
    : '';

$items = [
    [
        'code' => '001',
        'description' => 'Adiantamento salarial',
        'reference' => $advanceReference,
        'allowance' => $advanceAmount,
        'deduction' => null,
    ],
];

$grossTotal = $advanceAmount;
$deductionsTotal = 0.0;
$netTotal = $advanceAmount;

$messages = [];
if ($employeeDepartment !== '') {
    $messages[] = 'Departamento: ' . htmlspecialchars($employeeDepartment);
}

if ($employee?->getHireDate() !== null) {
    $messages[] = 'Admissão: ' . $employee->getHireDate()->format('d/m/Y');
}

$messages[] = 'Adiantamento correspondente a ' . ($advanceReference !== '' ? $advanceReference : 'valor acordado') . '.';
$messages[] = 'Salário base considerado: R$ ' . number_format($baseSalary, 2, ',', '.');

?>
<section class="receipt-wrapper">
    <header class="receipt-head">
        <div>
            <h2>Adiantamento salarial</h2>
            <p class="muted">Imprima esta via para comprovar o pagamento antecipado do colaborador.</p>
        </div>
        <div class="receipt-head__actions">
            <a href="javascript:window.print();" class="button"><i class="bi bi-printer"></i> Imprimir</a>
            <a href="?action=show_payroll&id=<?= $payroll->getId(); ?>" class="button button-secondary">
                <i class="bi bi-receipt"></i> Ver holerite mensal
            </a>
        </div>
    </header>

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
                    <td colspan="2" class="holerite-receipt-title">Recibo de Adiantamento Salarial</td>
                </tr>
                <tr>
                    <td>Nome: <?= htmlspecialchars($company->getBrandName()); ?></td>
                    <td>CNPJ: <?= htmlspecialchars($company->getDocument()); ?></td>
                    <td colspan="2">Referente ao mês/ano: <?= htmlspecialchars($referenceLabel); ?></td>
                </tr>
                <tr>
                    <td colspan="4">Endereço: <?= htmlspecialchars($company->getAddress()); ?></td>
                </tr>
            </table>

            <table class="holerite-table holerite-employee">
                <tr>
                    <td style="width: 15%;">CÓDIGO: <?= htmlspecialchars($employeeCode); ?></td>
                    <td style="width: 45%;">NOME FUNCIONÁRIO: <?= htmlspecialchars($employee?->getName() ?? ''); ?></td>
                    <td style="width: 20%;">CBO: <?= htmlspecialchars($employeeCbo); ?></td>
                    <td style="width: 20%;">FUNÇÃO: <?= htmlspecialchars($employeeFunction); ?></td>
                </tr>
            </table>

            <table class="holerite-table holerite-items">
                <thead>
                <tr>
                    <th style="width: 8%;">Cód.</th>
                    <th style="width: 40%;">Descrição</th>
                    <th style="width: 15%;">Referência</th>
                    <th style="width: 18%;">Proventos</th>
                    <th style="width: 18%;">Descontos</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['code']); ?></td>
                        <td><?= htmlspecialchars($item['description']); ?></td>
                        <td><?= htmlspecialchars($item['reference']); ?></td>
                        <td><?= $item['allowance'] !== null ? 'R$ ' . number_format((float) $item['allowance'], 2, ',', '.') : ''; ?></td>
                        <td><?= $item['deduction'] !== null ? 'R$ ' . number_format((float) $item['deduction'], 2, ',', '.') : ''; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <table class="holerite-table holerite-totals">
                <tr>
                    <td style="width: 30%;" class="holerite-messages" rowspan="2">
                        <strong>Mensagens</strong>
                        <ul>
                            <?php foreach ($messages as $message): ?>
                                <li><?= $message; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                    <td style="width: 19%;">Total dos Vencimentos</td>
                    <td style="width: 18%;">R$ <?= number_format($grossTotal, 2, ',', '.'); ?></td>
                    <td style="width: 19%;">Total dos Descontos</td>
                    <td style="width: 18%;">R$ <?= number_format($deductionsTotal, 2, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td colspan="2" class="holerite-liquid-note">Líquido a Receber -&gt;</td>
                    <td colspan="2" class="holerite-liquid-note">R$ <?= number_format($netTotal, 2, ',', '.'); ?></td>
                </tr>
            </table>

            <table class="holerite-table holerite-bases">
                <tr>
                    <td>Salário Base</td>
                    <td>Base Cálc. FGTS</td>
                    <td>FGTS do Mês</td>
                    <td>Base Cálc. INSS</td>
                    <td>Base Cálc. IRRF</td>
                </tr>
                <tr>
                    <td>R$ <?= number_format($baseSalary, 2, ',', '.'); ?></td>
                    <td>—</td>
                    <td>—</td>
                    <td>—</td>
                    <td>—</td>
                </tr>
            </table>

            <div class="holerite-footer">
                <div class="holerite-declaration">
                    <p>Declaro ter recebido o valor acima referente ao adiantamento salarial descrito neste recibo.</p>
                </div>
                <div class="holerite-signature">
                    <div class="holerite-date-fields">
                        <span><?= htmlspecialchars($company->getCity() ?? ''); ?>, ____/____/____</span>
                    </div>
                    <div class="holerite-signature-line">
                        <span>Assinatura do funcionário</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</section>
