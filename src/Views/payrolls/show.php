<?php
/** @var Holerite\Models\Payroll $payroll */
/** @var Holerite\Models\Employee|null $employee */
?>
<section>
    <header style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
        <div>
            <h2 style="margin:0 0 0.25rem 0;">Holerite de <?= htmlspecialchars($employee?->getName() ?? 'Colaborador'); ?></h2>
            <p class="muted">Referente a <?= htmlspecialchars($payroll->getReferenceMonth()); ?> — pagamento em <?= $payroll->getPaymentDate()->format('d/m/Y'); ?></p>
        </div>
        <a href="javascript:window.print();" class="button">Imprimir</a>
    </header>

    <div class="grid">
        <div class="card">
            <h3 style="margin-top:0;">Dados do colaborador</h3>
            <p><strong>Nome:</strong> <?= htmlspecialchars($employee?->getName() ?? ''); ?></p>
            <p><strong>Departamento:</strong> <?= htmlspecialchars($employee?->getDepartment() ?? ''); ?></p>
            <p><strong>Cargo:</strong> <?= htmlspecialchars($employee?->getPosition() ?? ''); ?></p>
            <p><strong>Admissão:</strong> <?= $employee?->getHireDate()?->format('d/m/Y'); ?></p>
        </div>
        <div class="card">
            <h3 style="margin-top:0;">Resumo financeiro</h3>
            <p><strong>Salário base:</strong> R$ <?= number_format($payroll->getBaseSalary(), 2, ',', '.'); ?></p>
            <p><strong>Total de proventos:</strong> R$ <?= number_format($payroll->getTotalAllowances(), 2, ',', '.'); ?></p>
            <p><strong>Total de descontos:</strong> R$ <?= number_format($payroll->getTotalDeductions(), 2, ',', '.'); ?></p>
            <p><strong>Valor líquido:</strong> R$ <?= number_format($payroll->getNetSalary(), 2, ',', '.'); ?></p>
        </div>
    </div>

    <div class="grid" style="margin-top:1.5rem;">
        <div class="card">
            <h3 style="margin-top:0;">Proventos</h3>
            <table>
                <thead>
                <tr>
                    <th>Descrição</th>
                    <th class="text-right">Valor</th>
                </tr>
                </thead>
                <tbody>
                <?php $hasAllowance = false; ?>
                <?php foreach ($payroll->getItems() as $item): ?>
                    <?php if ($item->getType() !== 'allowance') { continue; } ?>
                    <?php $hasAllowance = true; ?>
                    <tr>
                        <td><?= htmlspecialchars($item->getDescription()); ?></td>
                        <td class="text-right">R$ <?= number_format($item->getAmount(), 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$hasAllowance): ?>
                    <tr>
                        <td colspan="2" class="muted">Nenhum provento informado.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card">
            <h3 style="margin-top:0;">Descontos</h3>
            <table>
                <thead>
                <tr>
                    <th>Descrição</th>
                    <th class="text-right">Valor</th>
                </tr>
                </thead>
                <tbody>
                <?php $hasDeduction = false; ?>
                <?php foreach ($payroll->getItems() as $item): ?>
                    <?php if ($item->getType() !== 'deduction') { continue; } ?>
                    <?php $hasDeduction = true; ?>
                    <tr>
                        <td><?= htmlspecialchars($item->getDescription()); ?></td>
                        <td class="text-right">R$ <?= number_format($item->getAmount(), 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$hasDeduction): ?>
                    <tr>
                        <td colspan="2" class="muted">Nenhum desconto informado.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($payroll->getNotes() !== ''): ?>
        <div class="card" style="margin-top:1.5rem;">
            <h3 style="margin-top:0;">Observações</h3>
            <p><?= nl2br(htmlspecialchars($payroll->getNotes())); ?></p>
        </div>
    <?php endif; ?>

    <div style="margin-top:1.5rem;">
        <a href="?action=list_payrolls" class="button button-secondary">Voltar</a>
    </div>
</section>
