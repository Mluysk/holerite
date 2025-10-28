<?php
/** @var Holerite\Models\Payroll $payroll */
/** @var Holerite\Models\Employee|null $employee */

$configurationPath = __DIR__ . '/../../../config/config.php';
$companyDefaults = [
    'name' => 'XYZ Ltda.',
    'document' => '00.000.000/0001-00',
    'address' => 'Rua Exemplo, 123 - Centro',
    'city' => 'São Paulo - SP',
    'phone' => '(11) 0000-0000',
];

if (file_exists($configurationPath)) {
    $loaded = require $configurationPath;
    if (is_array($loaded) && isset($loaded['company']) && is_array($loaded['company'])) {
        $companyDefaults = array_merge($companyDefaults, $loaded['company']);
    }
}

$typeLabels = [
    'regular' => 'Mensal',
    'vacation' => 'Férias',
    'termination' => 'Desligamento',
];

$referenceLabels = [
    'regular' => '1 mês',
    'vacation' => ($payroll->getVacationDays() ?? 0) . ' dias',
    'termination' => ($payroll->getWorkedDays() ?? 0) . ' dias',
];

$allowances = [];
$deductions = [];
$code = 1;
$thirteenthAmount = null;

$reference = $referenceLabels[$payroll->getType()] ?? '';
$allowances[] = [
    'code' => sprintf('%03d', $code++),
    'description' => 'Salário Base',
    'reference' => $reference,
    'amount' => $payroll->getBaseSalary(),
];

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

    if ($thirteenthAmount === null && $item->getType() === 'allowance') {
        $rawDescription = $item->getDescription();
        $description = function_exists('mb_strtolower') ? mb_strtolower($rawDescription) : strtolower($rawDescription);
        if (
            str_contains($description, '13º')
            || str_contains($description, '13o')
            || str_contains($description, 'décimo terceiro')
            || str_contains($description, 'decimo terceiro')
        ) {
            $thirteenthAmount = $item->getAmount();
        }
    }

    $allowances[] = $entry;
}

$maxRows = max(count($allowances), count($deductions));
$allowances = array_pad($allowances, $maxRows, null);
$deductions = array_pad($deductions, $maxRows, null);
?>
<section>
    <header style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
        <div>
            <h2 style="margin:0 0 0.25rem 0;">Holerite de <?= htmlspecialchars($employee?->getName() ?? 'Colaborador'); ?></h2>
            <p class="muted">Referência <?= htmlspecialchars($payroll->getReferenceMonth()); ?> · Pagamento em <?= $payroll->getPaymentDate()->format('d/m/Y'); ?> · <?= htmlspecialchars($typeLabels[$payroll->getType()] ?? ucfirst($payroll->getType())); ?></p>
        </div>
        <a href="javascript:window.print();" class="button">Imprimir</a>
    </header>

    <div class="payroll-slip">
        <div class="slip-header">
            <div>
                <h3><?= htmlspecialchars($companyDefaults['name']); ?></h3>
                <p><?= htmlspecialchars($companyDefaults['address']); ?></p>
                <p><?= htmlspecialchars($companyDefaults['city']); ?></p>
                <p><?= htmlspecialchars($companyDefaults['phone']); ?></p>
            </div>
            <div class="slip-header-right">
                <p><strong>CNPJ:</strong> <?= htmlspecialchars($companyDefaults['document']); ?></p>
                <p><strong>Competência:</strong> <?= htmlspecialchars($payroll->getReferenceMonth()); ?></p>
                <p><strong>Pagamento:</strong> <?= $payroll->getPaymentDate()->format('d/m/Y'); ?></p>
                <p><strong>Tipo:</strong> <?= htmlspecialchars($typeLabels[$payroll->getType()] ?? ucfirst($payroll->getType())); ?></p>
            </div>
        </div>

        <div class="slip-employee">
            <div>
                <p><strong>Código:</strong> <?= str_pad((string) ($employee?->getId() ?? 0), 5, '0', STR_PAD_LEFT); ?></p>
                <p><strong>Nome:</strong> <?= htmlspecialchars($employee?->getName() ?? ''); ?></p>
                <p><strong>Função:</strong> <?= htmlspecialchars($employee?->getPosition() ?? ''); ?></p>
            </div>
            <div>
                <p><strong>Departamento:</strong> <?= htmlspecialchars($employee?->getDepartment() ?? ''); ?></p>
                <p><strong>Admissão:</strong> <?= $employee?->getHireDate()?->format('d/m/Y'); ?></p>
                <?php if ($payroll->getType() === 'termination'): ?>
                    <p><strong>Dias trabalhados:</strong> <?= $payroll->getWorkedDays() ?? 0; ?></p>
                    <p><strong>Meses 13º:</strong> <?= $payroll->getThirteenthMonths() ?? 0; ?></p>
                    <p><strong>Justa causa:</strong> <?= $payroll->isJustCause() ? 'Sim' : 'Não'; ?></p>
                <?php elseif ($payroll->getType() === 'vacation'): ?>
                    <?php $vacationBonusDisplay = $payroll->getBaseSalary() > 0 ? $payroll->getBaseSalary() / 3 : 0; ?>
                    <p><strong>Dias de férias:</strong> <?= $payroll->getVacationDays() ?? 0; ?></p>
                    <p><strong>1/3 Constitucional:</strong> R$ <?= number_format($vacationBonusDisplay, 2, ',', '.'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <table class="slip-table">
            <thead>
            <tr>
                <th style="width:10%;">Código</th>
                <th style="width:40%;">Descrição</th>
                <th style="width:15%;">Referência</th>
                <th style="width:17%;" class="text-right">Vencimentos (R$)</th>
                <th style="width:18%;" class="text-right">Descontos (R$)</th>
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
                <th colspan="2" class="text-right">R$ <?= number_format($payroll->getNetSalary(), 2, ',', '.'); ?></th>
            </tr>
            </tfoot>
        </table>

        <div class="slip-summary">
            <div>
                <p><strong>Total de vencimentos:</strong> R$ <?= number_format($payroll->getBaseSalary() + $payroll->getTotalAllowances(), 2, ',', '.'); ?></p>
                <p><strong>Total de descontos:</strong> R$ <?= number_format($payroll->getTotalDeductions(), 2, ',', '.'); ?></p>
                <?php if ($thirteenthAmount !== null): ?>
                    <p><strong>13º proporcional:</strong> R$ <?= number_format($thirteenthAmount, 2, ',', '.'); ?></p>
                <?php endif; ?>
            </div>
            <div>
                <p><strong>Valor líquido recebido:</strong> <span>R$ <?= number_format($payroll->getNetSalary(), 2, ',', '.'); ?></span></p>
                <p>Declaro ter recebido a importância líquida discriminada neste recibo.</p>
            </div>
        </div>

        <?php if ($payroll->getNotes() !== ''): ?>
            <div class="slip-notes">
                <strong>Observações:</strong>
                <p><?= nl2br(htmlspecialchars($payroll->getNotes())); ?></p>
            </div>
        <?php endif; ?>

        <div class="slip-signature">
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

    <div style="margin-top:1.5rem;">
        <a href="?action=list_payrolls" class="button button-secondary">Voltar</a>
    </div>
</section>

<style>
    .payroll-slip {
        border: 1px solid #d1d5db;
        padding: 1.5rem;
        background: #fff;
        color: #111827;
        font-family: "Segoe UI", Arial, sans-serif;
    }

    .slip-header {
        display: flex;
        justify-content: space-between;
        border-bottom: 2px solid #111827;
        padding-bottom: 1rem;
        margin-bottom: 1rem;
    }

    .slip-header h3 {
        margin: 0 0 0.3rem 0;
    }

    .slip-header-right {
        text-align: right;
    }

    .slip-header-right p,
    .slip-header p {
        margin: 0.15rem 0;
        font-size: 0.95rem;
    }

    .slip-employee {
        display: flex;
        justify-content: space-between;
        border: 1px solid #d1d5db;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        font-size: 0.95rem;
    }

    .slip-employee p {
        margin: 0.2rem 0;
    }

    .slip-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.92rem;
    }

    .slip-table th,
    .slip-table td {
        border: 1px solid #d1d5db;
        padding: 0.5rem;
    }

    .slip-table thead th {
        background: #f3f4f6;
    }

    .slip-table tfoot th {
        background: #f9fafb;
    }

    .slip-summary {
        display: flex;
        justify-content: space-between;
        border: 1px solid #d1d5db;
        margin-top: 1rem;
        padding: 1rem;
        font-size: 0.95rem;
    }

    .slip-summary span {
        font-size: 1.1rem;
        font-weight: 700;
    }

    .slip-notes {
        border: 1px solid #d1d5db;
        margin-top: 1rem;
        padding: 0.75rem 1rem;
        background: #f9fafb;
        font-size: 0.95rem;
    }

    .slip-signature {
        display: flex;
        justify-content: space-between;
        margin-top: 2rem;
        font-size: 0.9rem;
        text-align: center;
    }

    .slip-signature span {
        display: block;
        margin-top: 0.3rem;
    }

    @media print {
        body {
            background: #fff;
        }

        body > header,
        body > header + main > section > header,
        body > header + main .button,
        body > header + main .button-secondary {
            display: none !important;
        }

        main {
            box-shadow: none;
            margin: 0;
            padding: 0;
        }

        .payroll-slip {
            border: 1px solid #111827;
            margin: 0;
            page-break-inside: avoid;
        }
    }
</style>
