<?php
/** @var Holerite\Models\Payroll[] $payrolls */
/** @var Holerite\Models\Employee[] $employees */
/** @var string $title */
$employeeNames = [];
foreach ($employees as $employee) {
    $employeeNames[$employee->getId() ?? 0] = $employee->getName();
}

$typeLabels = [
    'regular' => 'Mensal',
    'vacation' => 'Férias',
    'termination' => 'Desligamento',
    'thirteenth' => '13º salário',
];
?>
<section>
    <header class="section-header">
        <div class="section-header__content">
            <h2><?= htmlspecialchars($title); ?></h2>
            <p class="muted">Controle os holerites gerados e seus respectivos valores líquidos.</p>
        </div>
        <div class="section-header__actions">
            <a href="?action=create_payroll" class="button">Gerar holerite</a>
        </div>
    </header>

    <?php if ($payrolls === []): ?>
        <p class="muted">Nenhum holerite foi gerado até o momento.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                <tr>
                    <th>Colaborador</th>
                    <th>Tipo</th>
                    <th>Mês de referência</th>
                    <th>Pagamento</th>
                    <th>Salário base</th>
                    <th>Proventos</th>
                    <th>Descontos</th>
                    <th>Valor líquido</th>
                    <th class="text-right">Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($payrolls as $payroll): ?>
                    <tr>
                        <td><?= htmlspecialchars($employeeNames[$payroll->getEmployeeId()] ?? 'Colaborador'); ?></td>
                        <?php
                        $type = $payroll->getType();
                        $label = $typeLabels[$type] ?? ucfirst($type);
                        if ($type === 'thirteenth') {
                            $installment = $payroll->getThirteenthInstallment();
                            if ($installment === 'first') {
                                $label .= ' (1ª parcela)';
                            } elseif ($installment === 'second') {
                                $label .= ' (2ª parcela)';
                            }
                        }
                        ?>
                        <td><?= htmlspecialchars($label); ?></td>
                        <td><?= htmlspecialchars($payroll->getReferenceMonth()); ?></td>
                        <td><?= $payroll->getPaymentDate()->format('d/m/Y'); ?></td>
                        <td>R$ <?= number_format($payroll->getBaseSalary(), 2, ',', '.'); ?></td>
                        <td>R$ <?= number_format($payroll->getTotalAllowances(), 2, ',', '.'); ?></td>
                        <td>R$ <?= number_format($payroll->getTotalDeductions(), 2, ',', '.'); ?></td>
                        <td><strong>R$ <?= number_format($payroll->getNetSalary(), 2, ',', '.'); ?></strong></td>
                        <td class="text-right actions">
                            <a href="?action=show_payroll&id=<?= $payroll->getId(); ?>" class="button button-secondary">Visualizar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
