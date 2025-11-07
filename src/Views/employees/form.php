<?php
/** @var string $title */
/** @var string $action */
/** @var Holerite\Models\Employee|null $employee */

if (!isset($pageScripts) || !is_array($pageScripts)) {
    $pageScripts = [];
}

$pageScripts[] = [
    'src' => 'js/employee-form.js',
    'defer' => true,
];
?>
<section>
    <header style="margin-bottom:1.5rem;">
        <h2 style="margin:0 0 0.25rem 0;"><?= htmlspecialchars($title); ?></h2>
        <p class="muted">Informe os dados cadastrais do colaborador.</p>
    </header>

    <form method="post" action="?action=<?= htmlspecialchars($action); ?>">
        <div class="grid">
            <div>
                <label for="name">Nome completo</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($employee?->getName() ?? ''); ?>" required>
            </div>
            <div>
                <label for="cpf">CPF</label>
                <input type="text" id="cpf" name="cpf" inputmode="numeric" maxlength="14" pattern="\d{3}\.\d{3}\.\d{3}-\d{2}|\d{11}" value="<?= htmlspecialchars($employee?->getCpfFormatted() ?? ''); ?>" required>
                <small class="muted">Informe apenas números ou utilize o formato 000.000.000-00.</small>
            </div>
            <div>
                <label for="department">Departamento</label>
                <input type="text" id="department" name="department" value="<?= htmlspecialchars($employee?->getDepartment() ?? ''); ?>" required>
            </div>
            <div>
                <label for="position">Cargo</label>
                <input type="text" id="position" name="position" value="<?= htmlspecialchars($employee?->getPosition() ?? ''); ?>" required>
            </div>
            <div>
                <label for="base_salary">Salário base (R$)</label>
                <input type="number" min="0" step="0.01" id="base_salary" name="base_salary" value="<?= htmlspecialchars($employee?->getBaseSalary() !== null ? number_format($employee->getBaseSalary(), 2, '.', '') : ''); ?>" required>
            </div>
            <div>
                <label for="hire_date">Data de admissão</label>
                <input type="date" id="hire_date" name="hire_date" value="<?= htmlspecialchars($employee?->getHireDate()->format('Y-m-d') ?? date('Y-m-d')); ?>" required>
            </div>
            <div>
                <label for="termination_date">Data de desligamento</label>
                <input type="date" id="termination_date" name="termination_date" value="<?= htmlspecialchars($employee?->getTerminationDate()?->format('Y-m-d') ?? ''); ?>">
                <small class="muted">Preencha apenas se o vínculo já tiver sido encerrado.</small>
            </div>
        </div>

        <button type="submit" class="button">Salvar</button>
        <a href="?action=list_employees" class="button button-secondary" style="margin-left:0.5rem;">Cancelar</a>
    </form>
</section>
