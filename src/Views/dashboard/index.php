<?php
/** @var int $totalEmployees */
/** @var int $totalPayrolls */
/** @var float $totalNet */
/** @var Holerite\Models\Payroll[] $lastPayrolls */
/** @var Holerite\Models\Employee[] $employees */
/** @var array<int, array{period: string, total: float, count: int}> $monthlyTotals */
/** @var array<int, array{period: string, total: float, count: int}> $yearlyTotals */
/** @var array{labels: array<int, string>, values: array<int, float>, percentages: array<int, float>, total: float} $monthlyChart */
/** @var array{labels: array<int, string>, values: array<int, float>, percentages: array<int, float>, total: float} $yearlyChart */
/** @var DateTimeImmutable $calendarMonth */
/** @var array<int, array<int, array{date: DateTimeImmutable|null, payrolls: Holerite\Models\Payroll[]}>> $calendarWeeks */
/** @var Holerite\Models\Payroll[] $paidMonthlyPayrolls */
/** @var Holerite\Models\Employee[] $pendingMonthlyEmployees */
/** @var array{overall: float, currentMonth: float} $valeTotals */

$employeeNames = [];
foreach ($employees as $employee) {
    $employeeNames[$employee->getId() ?? 0] = $employee->getName();
}

$typeLabels = [
    'regular' => 'Mensal',
    'vacation' => 'Férias',
    'termination' => 'Rescisão',
    'thirteenth' => '13º Salário',
];

$weekDays = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];

$hasCharts = $monthlyChart['labels'] !== [] || $yearlyChart['labels'] !== [];

if (!isset($pageScripts) || !is_array($pageScripts)) {
    $pageScripts = [];
}

if ($hasCharts) {
    $pageScripts[] = [
        'src' => 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
        'defer' => true,
        'integrity' => 'sha384-UCZpkU3iAFH4x63HzlOAAbZv9fhPjbjJQr9HFVLqN3XkHKXKjMR2D3mHmr18bHul',
        'crossorigin' => 'anonymous',
    ];
    $pageScripts[] = [
        'src' => 'js/dashboard.js',
        'defer' => true,
    ];
}
?>
<section class="dashboard">
    <div class="dashboard-hero">
        <div>
            <h2 class="dashboard-hero__title">Visão geral da folha</h2>
            <p class="muted">Acompanhe pagamentos, distribuição mensal e desempenho anual em um só lugar.</p>
        </div>
        <div class="dashboard-hero__highlight">
            <span>Total pago no ano</span>
            <span class="dashboard-hero__value">R$ <?= number_format($yearlyChart['total'], 2, ',', '.'); ?></span>
        </div>
        <div class="dashboard-hero__actions">
            <a class="button button-secondary" href="?action=dashboard_report&amp;scope=monthly">Relatório mensal</a>
            <a class="button" href="?action=dashboard_report&amp;scope=yearly">Relatório anual</a>
        </div>
    </div>

    <div class="dashboard-metrics">
        <article class="stats-card">
            <h3>Colaboradores ativos</h3>
            <strong><?= $totalEmployees; ?></strong>
            <p class="muted" style="margin:0.35rem 0 0 0;">Equipe cadastrada atualmente.</p>
        </article>
        <article class="stats-card">
            <h3>Holerites emitidos</h3>
            <strong><?= $totalPayrolls; ?></strong>
            <p class="muted" style="margin:0.35rem 0 0 0;">Documentos gerados até o momento.</p>
        </article>
        <article class="stats-card">
            <h3>Folha líquida acumulada</h3>
            <strong style="font-size:1.85rem;">R$ <?= number_format($totalNet, 2, ',', '.'); ?></strong>
            <p class="muted" style="margin:0.35rem 0 0 0;">Total já desembolsado em pagamentos líquidos.</p>
        </article>
        <article class="stats-card">
            <h3>Descontos de vale</h3>
            <strong>R$ <?= number_format($valeTotals['overall'], 2, ',', '.'); ?></strong>
            <p class="muted" style="margin:0.35rem 0 0 0;">No mês: <strong>R$ <?= number_format($valeTotals['currentMonth'], 2, ',', '.'); ?></strong></p>
        </article>
    </div>

    <div class="dashboard-schedule">
        <div class="card dashboard-calendar-card">
            <div class="dashboard-calendar-card__header">
                <div>
                    <h3 style="margin:0;">Calendário de pagamentos</h3>
                    <p class="muted" style="margin:0.35rem 0 0 0;">Pagamentos registrados em <?= htmlspecialchars($calendarMonth->format('m/Y')); ?>.</p>
                </div>
            </div>
            <div class="calendar-wrapper">
                <table class="calendar-grid">
                    <thead>
                    <tr>
                        <?php foreach ($weekDays as $weekDay): ?>
                            <th><?= htmlspecialchars($weekDay); ?></th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($calendarWeeks as $week): ?>
                        <tr>
                            <?php foreach ($week as $cell): ?>
                                <?php if ($cell['date'] === null): ?>
                                    <td class="calendar-day calendar-day--empty"></td>
                                <?php else: ?>
                                    <?php $cellDate = $cell['date']; ?>
                                    <?php $cellPayrolls = $cell['payrolls']; ?>
                                    <?php $hasPayments = $cellPayrolls !== []; ?>
                                    <td class="calendar-day<?= $hasPayments ? ' calendar-day--has-payments' : ''; ?>">
                                        <div class="calendar-day__date"><?= htmlspecialchars($cellDate->format('d')); ?></div>
                                        <?php if ($hasPayments): ?>
                                            <?php $paymentCount = count($cellPayrolls); ?>
                                            <?php $ariaLabel = $paymentCount === 1 ? '1 pagamento registrado' : sprintf('%d pagamentos registrados', $paymentCount); ?>
                                            <div class="calendar-day__markers" aria-label="<?= htmlspecialchars($ariaLabel); ?>">
                                                <?php for ($index = 0; $index < $paymentCount; $index++): ?>
                                                    <span class="calendar-day__marker" role="presentation"></span>
                                                <?php endfor; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="calendar-day__placeholder muted">Sem pagamentos</div>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dashboard-status">
            <div class="card dashboard-status__card">
                <h3 style="margin-top:0;">Pagamentos mensais efetuados</h3>
                <?php if ($paidMonthlyPayrolls === []): ?>
                    <p class="muted">Nenhum pagamento mensal registrado neste mês.</p>
                <?php else: ?>
                    <ul class="status-list">
                        <?php foreach ($paidMonthlyPayrolls as $payroll): ?>
                            <li>
                                <strong><?= htmlspecialchars($employeeNames[$payroll->getEmployeeId()] ?? 'Colaborador'); ?></strong>
                                <small class="muted"><?= htmlspecialchars($payroll->getPaymentDate()->format('d/m/Y')); ?> • R$ <?= number_format($payroll->getNetSalary(), 2, ',', '.'); ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="card dashboard-status__card">
                <h3 style="margin-top:0;">Pagamentos mensais pendentes</h3>
                <?php if ($pendingMonthlyEmployees === []): ?>
                    <p class="muted">Todos os colaboradores receberam este mês.</p>
                <?php else: ?>
                    <ul class="status-list status-list--pending">
                        <?php foreach ($pendingMonthlyEmployees as $pendingEmployee): ?>
                            <li>
                                <strong><?= htmlspecialchars($pendingEmployee->getName()); ?></strong>
                                <small class="muted">Salário base: R$ <?= number_format($pendingEmployee->getBaseSalary(), 2, ',', '.'); ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="dashboard-charts">
        <section class="dashboard-chart">
            <div>
                <h3 style="margin:0;">Distribuição mensal</h3>
                <p class="muted" style="margin:0.25rem 0 0 0;">Percentual de pagamentos por mês.</p>
            </div>
            <?php if ($monthlyChart['labels'] === []): ?>
                <p class="muted">Os valores mensais aparecerão após os primeiros pagamentos.</p>
            <?php else: ?>
                <canvas id="monthlyChart"></canvas>
                <div class="dashboard-legend">
                    <?php foreach ($monthlyTotals as $index => $month): ?>
                        <div class="dashboard-legend__item">
                            <small class="muted" style="display:block;"><?= htmlspecialchars($month['period']); ?></small>
                            <strong><?= $monthlyChart['percentages'][$index]; ?>%</strong>
                            <span style="font-size:0.9rem;">R$ <?= number_format($month['total'], 2, ',', '.'); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <section class="dashboard-chart">
            <div>
                <h3 style="margin:0;">Distribuição anual</h3>
                <p class="muted" style="margin:0.25rem 0 0 0;">Percentual de pagamentos por ano.</p>
            </div>
            <?php if ($yearlyChart['labels'] === []): ?>
                <p class="muted">Os totais anuais serão exibidos conforme a folha evoluir.</p>
            <?php else: ?>
                <canvas id="yearlyChart"></canvas>
                <div class="dashboard-legend">
                    <?php foreach ($yearlyTotals as $index => $year): ?>
                        <div class="dashboard-legend__item">
                            <small class="muted" style="display:block;"><?= htmlspecialchars($year['period']); ?></small>
                            <strong><?= $yearlyChart['percentages'][$index]; ?>%</strong>
                            <span style="font-size:0.9rem;">R$ <?= number_format($year['total'], 2, ',', '.'); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="dashboard-tables">
        <div class="card">
            <h3 style="margin-top:0;">Totais pagos por mês</h3>
            <?php if ($monthlyTotals === []): ?>
                <p class="muted">Os valores mensais aparecerão após os primeiros pagamentos.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                        <tr>
                            <th>Mês</th>
                            <th class="text-right">Valor líquido pago</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($monthlyTotals as $month): ?>
                            <tr>
                                <td><?= htmlspecialchars($month['period']); ?></td>
                                <td class="text-right"><strong>R$ <?= number_format($month['total'], 2, ',', '.'); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="card">
            <h3 style="margin-top:0;">Totais pagos por ano</h3>
            <?php if ($yearlyTotals === []): ?>
                <p class="muted">Os totais anuais serão exibidos conforme a folha evoluir.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                        <tr>
                            <th>Ano</th>
                            <th class="text-right">Valor líquido pago</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($yearlyTotals as $year): ?>
                            <tr>
                                <td><?= htmlspecialchars($year['period']); ?></td>
                                <td class="text-right"><strong>R$ <?= number_format($year['total'], 2, ',', '.'); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card dashboard-recent">
        <h3 style="margin-top:0;">Últimos holerites</h3>
        <?php if ($lastPayrolls === []): ?>
            <p class="muted">Ainda não há holerites registrados. Que tal gerar o primeiro?</p>
        <?php else: ?>
            <div class="table-responsive">
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
                            <td><?= htmlspecialchars($payroll->getPaymentDate()->format('d/m/Y')); ?></td>
                            <td class="text-right"><strong>R$ <?= number_format($payroll->getNetSalary(), 2, ',', '.'); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($hasCharts): ?>
        <script id="dashboard-data" type="application/json">
            <?= json_encode(['monthly' => $monthlyChart, 'yearly' => $yearlyChart], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
        </script>
    <?php endif; ?>
</section>
