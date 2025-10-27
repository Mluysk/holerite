<?php
/** @var Holerite\Models\Employee[] $employees */
/** @var string $title */
?>
<section>
    <header style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
        <div>
            <h2 style="margin:0;"><?= htmlspecialchars($title); ?></h2>
            <p class="muted">Mantenha o cadastro de colaboradores sempre atualizado.</p>
        </div>
        <a href="?action=create_employee" class="button">Novo colaborador</a>
    </header>

    <?php if ($employees === []): ?>
        <p class="muted">Nenhum colaborador cadastrado até o momento.</p>
    <?php else: ?>
        <table>
            <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Departamento</th>
                <th>Cargo</th>
                <th>Salário base</th>
                <th>Admissão</th>
                <th class="text-right">Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($employees as $employee): ?>
                <tr>
                    <td><?= htmlspecialchars($employee->getName()); ?></td>
                    <td><?= htmlspecialchars($employee->getEmail()); ?></td>
                    <td><?= htmlspecialchars($employee->getDepartment()); ?></td>
                    <td><?= htmlspecialchars($employee->getPosition()); ?></td>
                    <td>R$ <?= number_format($employee->getBaseSalary(), 2, ',', '.'); ?></td>
                    <td><?= $employee->getHireDate()->format('d/m/Y'); ?></td>
                    <td class="text-right actions">
                        <a href="?action=edit_employee&id=<?= $employee->getId(); ?>" class="button button-secondary">Editar</a>
                        <form method="post" action="?action=delete_employee&id=<?= $employee->getId(); ?>" onsubmit="return confirm('Deseja realmente remover este colaborador?');" style="display:inline;">
                            <button type="submit" class="button button-secondary" style="background:#dc2626;">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
