<?php
/** @var array<int, array{employee: Holerite\Models\Employee, next_cycle: ?array, vacations: array}> $entries */
/** @var DateTimeImmutable $today */
?>
<section>
    <header class="section-header">
        <div class="section-header__content">
            <h2>Planejamento de férias</h2>
            <p class="muted">Acompanhe os ciclos aquisitivos e ajuste a data base de cada colaborador.</p>
        </div>
        <div class="section-header__actions">
            <a href="?action=list_employees" class="button button-secondary">Voltar para colaboradores</a>
        </div>
    </header>

    <?php if ($entries === []): ?>
        <p class="muted">Nenhum colaborador cadastrado até o momento.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                <tr>
                    <th>Colaborador</th>
                    <th>Base atual</th>
                    <th>Próxima liberação</th>
                    <th>Concessão até</th>
                    <th>Status</th>
                    <th class="text-right">Ajustar contagem</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $entry): ?>
                    <?php $employee = $entry['employee']; ?>
                    <?php $cycle = $entry['next_cycle']; ?>
                    <?php $baseStart = $entry['vacations']['base_start'] ?? $employee->getHireDate(); ?>
                    <?php
                    $highlight = '';
                    if ($cycle !== null) {
                        $available = $cycle['available_from'];
                        $noticeFrom = $cycle['notice_from'];
                        if ($available instanceof DateTimeImmutable && $available->format('Y-m') === $today->format('Y-m')) {
                            $highlight = 'table-row--highlight';
                        } elseif ($noticeFrom instanceof DateTimeImmutable && $noticeFrom <= $today && $available instanceof DateTimeImmutable && $available > $today) {
                            $highlight = 'table-row--notice';
                        }
                    }
                    ?>
                    <tr class="<?= $highlight; ?>">
                        <td>
                            <strong><?= htmlspecialchars($employee->getName()); ?></strong><br>
                            <small class="muted">Admissão: <?= htmlspecialchars($employee->getHireDate()->format('d/m/Y')); ?></small>
                        </td>
                        <td><?= htmlspecialchars($baseStart instanceof DateTimeImmutable ? $baseStart->format('d/m/Y') : '—'); ?></td>
                        <td>
                            <?php if ($cycle !== null && $cycle['available_from'] instanceof DateTimeImmutable): ?>
                                <?= htmlspecialchars($cycle['available_from']->format('d/m/Y')); ?>
                            <?php else: ?>
                                <span class="muted">Sem previsão</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($cycle !== null && $cycle['concession_end'] instanceof DateTimeImmutable): ?>
                                <?= htmlspecialchars($cycle['concession_end']->format('d/m/Y')); ?>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($cycle !== null): ?>
                                <span class="status-pill <?= $cycle['eligible'] ? 'status-pill--success' : 'status-pill--warning'; ?>">
                                    <?= htmlspecialchars($cycle['status']); ?>
                                </span>
                            <?php else: ?>
                                <span class="status-pill">Em aquisição</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <form method="post" action="?action=update_vacation_base&id=<?= $employee->getId(); ?>" class="inline-form">
                                <input type="date" name="vacation_base_date" value="<?= htmlspecialchars($employee->getVacationBaseDate()?->format('Y-m-d') ?? ''); ?>">
                                <button type="submit" class="button button-secondary">Atualizar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
