<?php
/** @var int $totalEmployees */
/** @var int $totalPayrolls */
/** @var float $totalNet */
/** @var Holerite\Models\Payroll[] $lastPayrolls */
/** @var Holerite\Models\Employee[] $employees */
$employeeNames = [];
foreach ($employees as $employee) {
    $employeeNames[$employee->getId() ?? 0] = $employee->getName();
}
?>
<section>
    <header style="margin-bottom:1.5rem;">
        <h2 style="margin:0 0 0.25rem 0;">Visão geral</h2>
        <p class="muted">Acompanhe os principais indicadores do seu departamento pessoal.</p>
    </header>

    <div class="grid">
        <div class="card">
            <h3>Total de colaboradores</h3>
            <p style="font-size:2rem;margin:0;"><?= $totalEmployees; ?></p>
        </div>
        <div class="card">
            <h3>Holerites gerados</h3>
            <p style="font-size:2rem;margin:0;"><?= $totalPayrolls; ?></p>
        </div>
        <div class="card">
            <h3>Folha líquida acumulada</h3>
            <p style="font-size:2rem;margin:0;">R$ <?= number_format($totalNet, 2, ',', '.'); ?></p>
        </div>
    </div>

    <div class="card" style="margin-top:1.5rem;">
        <h3 style="margin-top:0;">Últimos holerites</h3>
        <?php if ($lastPayrolls === []): ?>
            <p class="muted">Ainda não há holerites registrados. Que tal gerar o primeiro?</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Colaborador</th>
                    <th>Mês</th>
                    <th>Pagamento</th>
                    <th class="text-right">Valor líquido</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($lastPayrolls as $payroll): ?>
                    <tr>
                        <td><?= htmlspecialchars($employeeNames[$payroll->getEmployeeId()] ?? 'Colaborador'); ?></td>
                        <td><?= htmlspecialchars($payroll->getReferenceMonth()); ?></td>
                        <td><?= $payroll->getPaymentDate()->format('d/m/Y'); ?></td>
                        <td class="text-right"><strong>R$ <?= number_format($payroll->getNetSalary(), 2, ',', '.'); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
