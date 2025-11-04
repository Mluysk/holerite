<?php
/** @var Holerite\Models\Payroll[] $payrolls */
/** @var Holerite\Models\Employee[] $employees */
/** @var string $title */
/** @var bool $canDelete */
$employeeNames = [];
foreach ($employees as $employee) {
    $employeeNames[$employee->getId() ?? 0] = $employee->getName();
}

$canDelete = isset($canDelete) && $canDelete;

$typeLabels = [
    'regular' => 'Mensal',
    'vacation' => 'Férias',
    'termination' => 'Desligamento',
    'thirteenth' => '13º salário',
];
?>
<section class="payroll-list">
    <header class="page-head">
        <div>
            <h2><?= htmlspecialchars($title); ?></h2>
            <p class="page-head__subtitle">Controle os holerites gerados e seus respectivos valores líquidos.</p>
        </div>
        <div class="page-head__actions">
            <a href="?action=create_payroll" class="button button-primary">Gerar holerite</a>
        </div>
    </header>

    <?php if ($payrolls === []): ?>
        <div class="empty-state">
            <p class="muted">Nenhum holerite foi gerado até o momento.</p>
        </div>
    <?php else: ?>
        <div class="payroll-card">
            <div class="table-scroll">
                <table class="payroll-table">
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
                            <td class="payroll-table__employee"><?= htmlspecialchars($employeeNames[$payroll->getEmployeeId()] ?? 'Colaborador'); ?></td>
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
                            <td class="payroll-table__net">R$ <?= number_format($payroll->getNetSalary(), 2, ',', '.'); ?></td>
                            <td class="text-right payroll-table__actions">
                                <div class="action-stack">
                                    <a href="?action=show_payroll&id=<?= $payroll->getId(); ?>" class="button button-secondary button-sm">
                                        Visualizar
                                    </a>
                                    <?php if ($canDelete): ?>
                                        <form method="post" action="?action=delete_payroll&id=<?= $payroll->getId(); ?>" class="payroll-delete-form">
                                            <?php $inputId = 'delete-password-' . $payroll->getId(); ?>
                                            <label for="<?= htmlspecialchars($inputId); ?>" class="visually-hidden">Senha do administrador</label>
                                            <input type="password" name="password" id="<?= htmlspecialchars($inputId); ?>" placeholder="Senha admin" required>
                                            <button type="submit" class="button button-danger button-sm" onclick="return confirm('Confirmar a exclusão deste holerite?');">Excluir</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
