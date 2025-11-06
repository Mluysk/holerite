<?php
/** @var Holerite\Models\Employee $employee */
/** @var Holerite\Models\Payroll[] $payrolls */
/** @var array<string, mixed> $benefits */

$thirteenth = $benefits['thirteenth'] ?? [];
$vacations = $benefits['vacations'] ?? ['cycles' => []];
$thirteenthMonths = $thirteenth['months_breakdown'] ?? [];
$firstInstallmentPaid = (float) ($thirteenth['first_installment_paid'] ?? 0);
$secondInstallmentPaid = (float) ($thirteenth['second_installment_paid'] ?? 0);
$suggestedMonths = (int) ($thirteenth['suggested_months'] ?? 0);
if ($suggestedMonths <= 0) {
    $suggestedMonths = (int) ($thirteenth['months_accrued'] ?? 0);
}
$vacationCycles = $vacations['cycles'] ?? [];
$vacationNotices = $vacations['upcoming_notices'] ?? [];
$terminationDate = $employee->getTerminationDate();

$today = new DateTimeImmutable('today');
$tenureEnd = $terminationDate ?? $today;
$tenureInterval = $employee->getHireDate()->diff($tenureEnd);
$tenure = sprintf('%d ano(s) e %d mês(es)', $tenureInterval->y, $tenureInterval->m);

$typeLabels = [
    'regular' => 'Mensal',
    'vacation' => 'Férias',
    'termination' => 'Desligamento',
    'thirteenth' => '13º salário',
];

if (!isset($pageScripts) || !is_array($pageScripts)) {
    $pageScripts = [];
}

$pageScripts[] = [
    'src' => 'js/tabs.js',
    'defer' => true,
];
?>
<section>
    <header class="section-header">
        <div class="section-header__content">
            <h2>Colaborador · <?= htmlspecialchars($employee->getName()); ?></h2>
            <p class="muted">Acompanhe a evolução de benefícios obrigatórios e acesse o histórico de holerites.</p>
        </div>
        <div class="section-header__actions">
            <a href="?action=list_employees" class="button button-secondary">Voltar</a>
            <a href="?action=edit_employee&id=<?= $employee->getId(); ?>" class="button">Editar cadastro</a>
        </div>
    </header>

    <?php if ($terminationDate !== null): ?>
        <div class="flash flash-warning" style="margin-bottom:1.5rem;">
            Colaborador desligado em <?= htmlspecialchars($terminationDate->format('d/m/Y')); ?>. Os cálculos abaixo consideram a data de término do vínculo.
        </div>
    <?php endif; ?>

    <div class="tab-container">
        <div class="tab-nav">
            <button type="button" class="tab-button active" data-tab="overview">Dados gerais</button>
            <button type="button" class="tab-button" data-tab="thirteenth">13º salário</button>
            <button type="button" class="tab-button" data-tab="vacation">Férias</button>
        </div>

        <div class="tab-content active" id="tab-overview">
            <?php if ($vacationNotices !== [] && $terminationDate === null): ?>
                <div class="flash flash-warning" style="margin-bottom:1.5rem;">
                    <strong>Atenção:</strong> este colaborador terá direito a férias em breve.
                    <ul class="muted" style="margin:0.5rem 0 0 1rem;">
                        <?php foreach ($vacationNotices as $notice): ?>
                            <li>Período aquisitivo #<?= (int) $notice['index']; ?> disponível a partir de <?= htmlspecialchars($notice['available_on']->format('d/m/Y')); ?>.</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="grid">
                <div class="card">
                    <h3 style="margin-top:0;">Informações cadastrais</h3>
                    <p><strong>CPF:</strong> <?= htmlspecialchars($employee->getCpfFormatted()); ?></p>
                    <p><strong>Nascimento:</strong> <?= htmlspecialchars($employee->getBirthDate()->format('d/m/Y')); ?></p>
                    <p><strong>Cargo:</strong> <?= htmlspecialchars($employee->getPosition()); ?></p>
                    <p><strong>Departamento:</strong> <?= htmlspecialchars($employee->getDepartment()); ?></p>
                    <p><strong>Salário base:</strong> R$ <?= number_format($employee->getBaseSalary(), 2, ',', '.'); ?></p>
                    <p><strong>Admissão:</strong> <?= htmlspecialchars($employee->getHireDate()->format('d/m/Y')); ?></p>
                    <p><strong>Tempo de casa:</strong> <?= htmlspecialchars($tenure); ?></p>
                    <p><strong>Status:</strong> <?= $terminationDate === null ? 'Ativo' : 'Desligado'; ?></p>
                </div>
                <div class="card">
                    <h3 style="margin-top:0;">Ações rápidas</h3>
                    <p class="muted">Gere novos holerites a partir das informações atuais.</p>
                    <a href="?action=create_payroll&employee_id=<?= $employee->getId(); ?>&type=regular&reference_month=<?= $today->format('Y-m'); ?>" class="button" style="display:block;margin-bottom:0.5rem;">Gerar holerite mensal</a>
                    <a href="?action=create_payroll&employee_id=<?= $employee->getId(); ?>&type=vacation" class="button button-secondary" style="display:block;margin-bottom:0.5rem;">Gerar férias</a>
                    <a href="?action=create_payroll&employee_id=<?= $employee->getId(); ?>&type=termination" class="button button-secondary" style="display:block;">Gerar desligamento</a>
                </div>
            </div>

            <div class="card" style="margin-top:1.5rem;">
                <h3 style="margin-top:0;">Histórico de holerites</h3>
                <?php if ($payrolls === []): ?>
                    <p class="muted">Nenhum holerite foi emitido para este colaborador.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Referência</th>
                                <th>Pagamento</th>
                                <th>Valor líquido</th>
                                <th class="text-right">Ações</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($payrolls as $payroll): ?>
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
                                <tr>
                                    <td><?= htmlspecialchars($label); ?></td>
                                    <td><?= htmlspecialchars($payroll->getReferenceMonth()); ?></td>
                                    <td><?= htmlspecialchars($payroll->getPaymentDate()->format('d/m/Y')); ?></td>
                                    <td>R$ <?= number_format($payroll->getNetSalary(), 2, ',', '.'); ?></td>
                                    <td class="text-right">
                                        <a href="?action=show_payroll&id=<?= $payroll->getId(); ?>" class="button button-secondary">Abrir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" id="tab-thirteenth">
            <div class="card">
                <h3 style="margin-top:0;">Resumo <?= htmlspecialchars((string) ($thirteenth['year'] ?? date('Y'))); ?></h3>
                <div class="grid" style="gap:1rem;">
                    <div>
                        <p><strong>Meses computados:</strong> <?= (int) ($thirteenth['months_accrued'] ?? 0); ?></p>
                        <p><strong>Meses pagos:</strong> <?= (int) ($thirteenth['months_paid'] ?? 0); ?></p>
                        <p><strong>Meses pendentes:</strong> <?= (int) ($thirteenth['months_pending'] ?? 0); ?></p>
                    </div>
                    <div>
                        <p><strong>Total bruto acumulado:</strong> R$ <?= number_format((float) ($thirteenth['gross_accrued'] ?? 0), 2, ',', '.'); ?></p>
                        <p><strong>1ª parcela paga:</strong> R$ <?= number_format($firstInstallmentPaid, 2, ',', '.'); ?></p>
                        <p><strong>2ª parcela paga (bruta):</strong> R$ <?= number_format($secondInstallmentPaid, 2, ',', '.'); ?></p>
                        <p><strong>Total bruto pago:</strong> R$ <?= number_format((float) ($thirteenth['gross_paid'] ?? 0), 2, ',', '.'); ?></p>
                        <p><strong>Líquido pago:</strong> R$ <?= number_format((float) ($thirteenth['net_paid'] ?? 0), 2, ',', '.'); ?></p>
                    </div>
                    <div>
                        <p><strong>Saldo a pagar:</strong> R$ <?= number_format((float) ($thirteenth['gross_pending'] ?? 0), 2, ',', '.'); ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars($thirteenth['status'] ?? ''); ?></p>
                        <?php if (($thirteenth['months_accrued'] ?? 0) > 0 && $firstInstallmentPaid <= 0): ?>
                            <a href="?action=create_payroll&employee_id=<?= $employee->getId(); ?>&type=thirteenth&reference_month=<?= htmlspecialchars($thirteenth['default_reference'] ?? $today->format('Y-m')); ?>&thirteenth_months=<?= max(1, $suggestedMonths); ?>&thirteenth_installment=first" class="button" style="margin-top:0.5rem;">Gerar 1ª parcela do 13º</a>
                        <?php endif; ?>
                        <?php if ($firstInstallmentPaid > 0 && ($thirteenth['months_pending'] ?? 0) > 0): ?>
                            <a href="?action=create_payroll&employee_id=<?= $employee->getId(); ?>&type=thirteenth&reference_month=<?= htmlspecialchars($thirteenth['default_reference'] ?? $today->format('Y-m')); ?>&thirteenth_months=<?= max(1, $suggestedMonths); ?>&thirteenth_installment=second" class="button button-secondary" style="margin-top:0.5rem;">Gerar 2ª parcela do 13º</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top:1.5rem;">
                <h3 style="margin-top:0;">Meses considerados</h3>
                <?php if ($thirteenthMonths === []): ?>
                    <p class="muted">Nenhum mês elegível encontrado para o ano selecionado.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                            <tr>
                                <th>Mês</th>
                                <th>Dias trabalhados</th>
                                <th>Conta para o 13º?</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($thirteenthMonths as $month): ?>
                                <tr>
                                    <td><?= htmlspecialchars($month['label']); ?></td>
                                    <td><?= (int) ($month['worked_days'] ?? 0); ?></td>
                                    <td><?= !empty($month['counted']) ? 'Sim' : 'Não'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" id="tab-vacation">
            <div class="card">
                <h3 style="margin-top:0;">Períodos aquisitivos</h3>
                <?php if ($vacationCycles === []): ?>
                    <p class="muted">Nenhum período registrado até o momento.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Início</th>
                                <th>Fim do período aquisitivo</th>
                                <th>Limite para gozo</th>
                                <th>Meses trabalhados</th>
                                <th>Dias acumulados</th>
                                <th>Status</th>
                                <th class="text-right">Ação</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($vacationCycles as $cycle): ?>
                                <tr>
                                    <td><?= (int) $cycle['index']; ?></td>
                                    <td><?= htmlspecialchars($cycle['acquisition_start']->format('d/m/Y')); ?></td>
                                    <td><?= htmlspecialchars($cycle['acquisition_end']->format('d/m/Y')); ?></td>
                                    <td><?= htmlspecialchars($cycle['concession_end']->format('d/m/Y')); ?></td>
                                    <td><?= (int) $cycle['worked_months']; ?></td>
                                    <td><?= number_format((float) $cycle['accrued_days'], 1, ',', '.'); ?></td>
                                    <td>
                                        <?= htmlspecialchars($cycle['status']); ?>
                                        <?php if (!empty($cycle['notice'])): ?>
                                            <span class="badge badge--soon">Disponível em breve</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right">
                                        <?php if (!empty($cycle['eligible'])): ?>
                                            <a href="?action=create_payroll&employee_id=<?= $employee->getId(); ?>&type=vacation&reference_month=<?= htmlspecialchars($cycle['suggested_reference'] ?? $today->format('Y-m')); ?>&vacation_days=30" class="button button-secondary">Gerar férias</a>
                                        <?php else: ?>
                                            <span class="muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
