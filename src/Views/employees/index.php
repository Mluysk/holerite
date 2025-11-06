<?php
/** @var Holerite\Models\Employee[] $activeEmployees */
/** @var Holerite\Models\Employee[] $terminatedEmployees */
/** @var string $title */
/** @var string $activeTab */

$activeEmployees = $activeEmployees ?? [];
$terminatedEmployees = $terminatedEmployees ?? [];
$activeTab = in_array($activeTab ?? 'active', ['active', 'terminated'], true) ? $activeTab : 'active';
$todayValue = (new DateTimeImmutable('today'))->format('Y-m-d');
?>
<section>
    <header class="section-header">
        <div class="section-header__content">
            <h2><?= htmlspecialchars($title); ?></h2>
            <p class="muted">Mantenha o cadastro de colaboradores sempre atualizado.</p>
        </div>
        <div class="section-header__actions">
            <a href="?action=vacation_overview" class="button button-secondary">
                <i class="button__icon bi bi-umbrella-beach" aria-hidden="true"></i>
                <span>Planejar férias</span>
            </a>
            <a href="?action=employee_report" class="button button-secondary" target="_blank" rel="noreferrer noopener">
                <i class="button__icon bi bi-printer" aria-hidden="true"></i>
                <span>Imprimir relatório</span>
            </a>
            <a href="?action=create_employee" class="button">
                <i class="button__icon bi bi-person-plus-fill" aria-hidden="true"></i>
                <span>Novo colaborador</span>
            </a>
        </div>
    </header>

    <div class="tab-container">
        <div class="tab-nav">
            <button type="button" class="tab-button<?= $activeTab === 'active' ? ' active' : ''; ?>" data-tab="active">Colaboradores ativos</button>
            <button type="button" class="tab-button<?= $activeTab === 'terminated' ? ' active' : ''; ?>" data-tab="terminated">Colaboradores desligados</button>
        </div>

        <div class="tab-content<?= $activeTab === 'active' ? ' active' : ''; ?>" id="tab-active">
            <?php if ($activeEmployees === []): ?>
                <p class="muted">Nenhum colaborador ativo no momento.</p>
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
                        <?php foreach ($activeEmployees as $employee): ?>
                            <tr>
                                <td><?= htmlspecialchars($employee->getName()); ?></td>
                                <td><?= htmlspecialchars($employee->getCpfFormatted()); ?></td>
                                <td><?= htmlspecialchars($employee->getBirthDate()->format('d/m/Y')); ?></td>
                                <td><?= htmlspecialchars($employee->getDepartment()); ?></td>
                                <td><?= htmlspecialchars($employee->getPosition()); ?></td>
                                <td>R$ <?= number_format($employee->getBaseSalary(), 2, ',', '.'); ?></td>
                                <td><?= htmlspecialchars($employee->getHireDate()->format('d/m/Y')); ?></td>
                                <td class="text-right actions">
                                    <a href="?action=show_employee&id=<?= $employee->getId(); ?>" class="button button-secondary">Detalhes</a>
                                    <a href="?action=edit_employee&id=<?= $employee->getId(); ?>" class="button button-secondary">Editar</a>
                                    <form method="post" action="?action=terminate_employee&id=<?= $employee->getId(); ?>" onsubmit="return confirm('Confirmar desligamento deste colaborador?');" style="display:inline;">
                                        <input type="hidden" name="termination_date" value="<?= htmlspecialchars($todayValue); ?>">
                                        <button type="submit" class="button button-danger">
                                            Registrar desligamento
                                        </button>
                                    </form>
                                    <form method="post" action="?action=delete_employee&id=<?= $employee->getId(); ?>" onsubmit="return confirm('Deseja realmente remover este colaborador?');" style="display:inline;">
                                        <button type="submit" class="button button-danger">
                                            Excluir
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-content<?= $activeTab === 'terminated' ? ' active' : ''; ?>" id="tab-terminated">
            <?php if ($terminatedEmployees === []): ?>
                <p class="muted">Nenhum colaborador desligado cadastrado.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Departamento</th>
                            <th>Cargo</th>
                            <th>Admissão</th>
                            <th>Desligamento</th>
                            <th class="text-right">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($terminatedEmployees as $employee): ?>
                            <tr>
                                <td><?= htmlspecialchars($employee->getName()); ?></td>
                                <td><?= htmlspecialchars($employee->getCpfFormatted()); ?></td>
                                <td><?= htmlspecialchars($employee->getDepartment()); ?></td>
                                <td><?= htmlspecialchars($employee->getPosition()); ?></td>
                                <td><?= htmlspecialchars($employee->getHireDate()->format('d/m/Y')); ?></td>
                                <td><?= htmlspecialchars($employee->getTerminationDate()?->format('d/m/Y') ?? '-'); ?></td>
                                <td class="text-right actions">
                                    <a href="?action=show_employee&id=<?= $employee->getId(); ?>" class="button button-secondary">Detalhes</a>
                                    <a href="?action=edit_employee&id=<?= $employee->getId(); ?>" class="button button-secondary">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
