<?php
/** @var Holerite\Models\Employee[] $employees */
/** @var string $title */
?>
<section>
    <header class="section-header">
        <div class="section-header__content">
            <h2><?= htmlspecialchars($title); ?></h2>
            <p class="muted">Mantenha o cadastro de colaboradores sempre atualizado.</p>
        </div>
        <div class="section-header__actions">
            <a href="?action=employee_report" class="button button-secondary" target="_blank" rel="noreferrer noopener">Imprimir relatório</a>
            <a href="?action=create_employee" class="button">Novo colaborador</a>
        </div>
    </header>

    <?php if ($employees === []): ?>
        <p class="muted">Nenhum colaborador cadastrado até o momento.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Nascimento</th>
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
                        <td><?= htmlspecialchars($employee->getCpfFormatted()); ?></td>
                        <td><?= htmlspecialchars($employee->getBirthDate()->format('d/m/Y')); ?></td>
                        <td><?= htmlspecialchars($employee->getDepartment()); ?></td>
                        <td><?= htmlspecialchars($employee->getPosition()); ?></td>
                        <td>R$ <?= number_format($employee->getBaseSalary(), 2, ',', '.'); ?></td>
                        <td><?= $employee->getHireDate()->format('d/m/Y'); ?></td>
                        <td class="text-right actions">
                            <a href="?action=show_employee&id=<?= $employee->getId(); ?>" class="button button-secondary">Detalhes</a>
                            <a href="?action=edit_employee&id=<?= $employee->getId(); ?>" class="button button-secondary">Editar</a>
                            <form method="post" action="?action=delete_employee&id=<?= $employee->getId(); ?>" onsubmit="return confirm('Deseja realmente remover este colaborador?');" style="display:inline;">
                                <button type="submit" class="button button-secondary" style="background:#dc2626;">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
