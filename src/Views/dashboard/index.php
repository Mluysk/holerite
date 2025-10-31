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
/** @var array<int, array{period: string, total: float}> $monthlyValeTotals */
/** @var array<int, array{period: string, total: float}> $yearlyValeTotals */
/** @var array{labels: array<int, string>, values: array<int, float>, percentages: array<int, float>, total: float} $monthlyValeChart */
/** @var array{labels: array<int, string>, values: array<int, float>, percentages: array<int, float>, total: float} $yearlyValeChart */
/** @var DateTimeImmutable $calendarMonth */
/** @var array<int, array<int, array{date: DateTimeImmutable|null, payrolls: Holerite\Models\Payroll[]}>> $calendarWeeks */
/** @var Holerite\Models\Payroll[] $paidMonthlyPayrolls */
/** @var Holerite\Models\Employee[] $pendingMonthlyEmployees */
/** @var array{overall: float, currentMonth: float, currentYear: float} $valeTotals */

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

$hasCharts = $monthlyChart['labels'] !== []
    || $yearlyChart['labels'] !== []
    || $monthlyValeChart['labels'] !== []
    || $yearlyValeChart['labels'] !== [];

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
        <div class="dashboard-hero__intro section-heading section-heading--hero">
            <span class="icon-circle icon-circle--info" aria-hidden="true">
                <i class="bi bi-speedometer"></i>
            </span>
            <div>
                <h2 class="dashboard-hero__title">Visão geral da folha</h2>
                <p class="muted">Acompanhe pagamentos, distribuição mensal e desempenho anual em um só lugar.</p>
            </div>
        </div>
        <div class="dashboard-hero__highlight">
            <span class="icon-circle icon-circle--success" aria-hidden="true">
                <i class="bi bi-cash-stack"></i>
            </span>
            <div class="dashboard-hero__highlight-content">
                <span class="dashboard-hero__label">Total pago no ano</span>
                <span class="dashboard-hero__value">R$ <?= number_format($yearlyChart['total'], 2, ',', '.'); ?></span>
                <div class="dashboard-hero__sub">
                    <span>Total pago em <?= htmlspecialchars($currentMonthLabel); ?></span>
                    <strong>R$ <?= number_format($currentMonthNet, 2, ',', '.'); ?></strong>
                </div>
            </div>
        </div>
        <div class="dashboard-hero__actions">
            <a class="button button-secondary" href="?action=dashboard_report&amp;scope=monthly">
                <i class="bi bi-journal-text button__icon" aria-hidden="true"></i>
                <span>Relatório mensal</span>
            </a>
            <a class="button" href="?action=dashboard_report&amp;scope=yearly">
                <i class="bi bi-calendar-week button__icon" aria-hidden="true"></i>
                <span>Relatório anual</span>
            </a>
        </div>
    </div>

    <div class="dashboard-metrics">
        <article class="stats-card">
            <div class="stats-card__icon icon-circle icon-circle--info" aria-hidden="true">
                <i class="bi bi-people-fill"></i>
            </div>
            <h3>Colaboradores ativos</h3>
            <strong><?= $totalEmployees; ?></strong>
            <p class="muted" style="margin:0.35rem 0 0 0;">Equipe cadastrada atualmente.</p>
        </article>
        <article class="stats-card">
            <div class="stats-card__icon icon-circle icon-circle--primary" aria-hidden="true">
                <i class="bi bi-receipt"></i>
            </div>
            <h3>Holerites emitidos</h3>
            <strong><?= $totalPayrolls; ?></strong>
            <p class="muted" style="margin:0.35rem 0 0 0;">Documentos gerados até o momento.</p>
        </article>
        <article class="stats-card">
            <div class="stats-card__icon icon-circle icon-circle--accent" aria-hidden="true">
                <i class="bi bi-wallet2"></i>
            </div>
            <h3>Folha líquida acumulada</h3>
            <strong style="font-size:1.85rem;">R$ <?= number_format($totalNet, 2, ',', '.'); ?></strong>
            <p class="muted" style="margin:0.35rem 0 0 0;">Total já desembolsado em pagamentos líquidos.</p>
        </article>
        <article class="stats-card stats-card--vale">
            <div class="stats-card__icon icon-circle icon-circle--warning" aria-hidden="true">
                <i class="bi bi-cash-coin"></i>
            </div>
            <header>
                <span class="stats-card__subtitle">Descontos de vale</span>
                <h3>Total acumulado</h3>
            </header>
            <div class="stats-card__value">R$ <?= number_format($valeTotals['overall'], 2, ',', '.'); ?></div>
            <dl class="stats-card__details">
                <div>
                    <dt>Este mês</dt>
                    <dd>R$ <?= number_format($valeTotals['currentMonth'], 2, ',', '.'); ?></dd>
                </div>
                <div>
                    <dt>No ano</dt>
                    <dd>R$ <?= number_format($valeTotals['currentYear'], 2, ',', '.'); ?></dd>
                </div>
            </dl>
        </article>
    </div>

    <div class="dashboard-schedule">
        <div class="card dashboard-calendar-card">
            <header class="dashboard-calendar-card__header section-heading">
                <span class="icon-circle icon-circle--calendar" aria-hidden="true">
                    <i class="bi bi-calendar-event"></i>
                </span>
                <div>
                    <h3>Calendário de pagamentos</h3>
                    <p class="muted">Pagamentos registrados em <?= htmlspecialchars($calendarMonth->format('m/Y')); ?>.</p>
                </div>
            </header>
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
                <header class="section-heading section-heading--compact">
                    <span class="icon-circle icon-circle--success" aria-hidden="true">
                        <i class="bi bi-check-circle-fill"></i>
                    </span>
                    <div>
                        <h3>Pagamentos mensais efetuados</h3>
                        <p class="muted">Colaboradores com salário do mês confirmado.</p>
                    </div>
                </header>
                <?php if ($paidMonthlyPayrolls === []): ?>
                    <p class="muted">Nenhum pagamento mensal registrado neste mês.</p>
                <?php else: ?>
                    <ul class="status-list">
                        <?php foreach ($paidMonthlyPayrolls as $payroll): ?>
                            <li>
                                <span class="status-list__bullet status-list__bullet--success" aria-hidden="true">
                                    <i class="bi bi-check2"></i>
                                </span>
                                <div class="status-list__content">
                                    <strong><?= htmlspecialchars($employeeNames[$payroll->getEmployeeId()] ?? 'Colaborador'); ?></strong>
                                    <small class="muted"><?= htmlspecialchars($payroll->getPaymentDate()->format('d/m/Y')); ?> • R$ <?= number_format($payroll->getNetSalary(), 2, ',', '.'); ?></small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="card dashboard-status__card">
                <header class="section-heading section-heading--compact">
                    <span class="icon-circle icon-circle--warning" aria-hidden="true">
                        <i class="bi bi-hourglass-split"></i>
                    </span>
                    <div>
                        <h3>Pagamentos mensais pendentes</h3>
                        <p class="muted">Funcionários aguardando pagamento neste mês.</p>
                    </div>
                </header>
                <?php if ($pendingMonthlyEmployees === []): ?>
                    <p class="muted">Todos os colaboradores receberam este mês.</p>
                <?php else: ?>
                    <ul class="status-list status-list--pending">
                        <?php foreach ($pendingMonthlyEmployees as $pendingEmployee): ?>
                            <li>
                                <span class="status-list__bullet status-list__bullet--warning" aria-hidden="true">
                                    <i class="bi bi-exclamation"></i>
                                </span>
                                <div class="status-list__content">
                                    <strong><?= htmlspecialchars($pendingEmployee->getName()); ?></strong>
                                    <small class="muted">Salário base: R$ <?= number_format($pendingEmployee->getBaseSalary(), 2, ',', '.'); ?></small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="dashboard-charts">
        <section class="dashboard-chart">
            <header class="section-heading">
                <span class="icon-circle icon-circle--chart" aria-hidden="true">
                    <i class="bi bi-pie-chart-fill"></i>
                </span>
                <div>
                    <h3>Distribuição mensal</h3>
                    <p class="muted">Percentual de pagamentos por mês.</p>
                </div>
            </header>
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
            <header class="section-heading">
                <span class="icon-circle icon-circle--chart" aria-hidden="true">
                    <i class="bi bi-pie-chart"></i>
                </span>
                <div>
                    <h3>Distribuição anual</h3>
                    <p class="muted">Percentual de pagamentos por ano.</p>
                </div>
            </header>
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

    <section class="dashboard-vale">
        <header class="dashboard-vale__header section-heading section-heading--large">
            <span class="icon-circle icon-circle--vale" aria-hidden="true">
                <i class="bi bi-ticket-perforated"></i>
            </span>
            <div>
                <h2>Controle de vales</h2>
                <p class="muted">Acompanhe os descontos e o desempenho dos vales mês a mês e ano a ano.</p>
            </div>
        </header>
        <div class="dashboard-vale__content">
            <div class="dashboard-vale__charts">
                <section class="dashboard-chart dashboard-chart--wide">
                    <header class="section-heading">
                        <span class="icon-circle icon-circle--chart" aria-hidden="true">
                            <i class="bi bi-graph-up-arrow"></i>
                        </span>
                        <div>
                            <h3>Desempenho mensal de vales</h3>
                            <p class="muted">Tendência dos descontos ao longo dos meses.</p>
                        </div>
                    </header>
                    <?php if ($monthlyValeChart['labels'] === []): ?>
                        <p class="muted">Os descontos mensais de vale aparecerão conforme forem registrados.</p>
                    <?php else: ?>
                        <div class="chart-wrapper">
                            <canvas id="monthlyValePerformanceChart"></canvas>
                        </div>
                    <?php endif; ?>
                </section>
                <section class="dashboard-chart dashboard-chart--wide">
                    <header class="section-heading">
                        <span class="icon-circle icon-circle--chart" aria-hidden="true">
                            <i class="bi bi-graph-up"></i>
                        </span>
                        <div>
                            <h3>Desempenho anual de vales</h3>
                            <p class="muted">Comparativo dos descontos entre os anos.</p>
                        </div>
                    </header>
                    <?php if ($yearlyValeChart['labels'] === []): ?>
                        <p class="muted">Os totais anuais de vale aparecerão após os primeiros registros.</p>
                    <?php else: ?>
                        <div class="chart-wrapper">
                            <canvas id="yearlyValePerformanceChart"></canvas>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
            <div class="dashboard-vale__tables">
                <div class="card">
                    <header class="section-heading section-heading--compact">
                        <span class="icon-circle icon-circle--info" aria-hidden="true">
                            <i class="bi bi-calendar4-week"></i>
                        </span>
                        <div>
                            <h3>Resumo mensal de vales</h3>
                            <p class="muted">Totais descontados por mês.</p>
                        </div>
                    </header>
                    <?php if ($monthlyValeTotals === []): ?>
                        <p class="muted">Os valores aparecerão após registrar descontos de vale.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                <tr>
                                    <th>Mês</th>
                                    <th class="text-right">Valor descontado</th>
                                    <th class="text-right">Participação</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($monthlyValeTotals as $index => $month): ?>
                                    <?php $monthlyShare = $monthlyValeChart['total'] > 0 ? ($monthlyValeChart['percentages'][$index] ?? 0) : 0; ?>
                                    <tr>
                                        <td><?= htmlspecialchars($month['period']); ?></td>
                                        <td class="text-right"><strong>R$ <?= number_format($month['total'], 2, ',', '.'); ?></strong></td>
                                        <td class="text-right"><span class="tag tag--soft"><?= number_format($monthlyShare, 1, ',', '.'); ?>%</span></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card">
                    <header class="section-heading section-heading--compact">
                        <span class="icon-circle icon-circle--info" aria-hidden="true">
                            <i class="bi bi-calendar3"></i>
                        </span>
                        <div>
                            <h3>Resumo anual de vales</h3>
                            <p class="muted">Consolidação por exercício.</p>
                        </div>
                    </header>
                    <?php if ($yearlyValeTotals === []): ?>
                        <p class="muted">Os totais anuais aparecerão após os primeiros registros.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                <tr>
                                    <th>Ano</th>
                                    <th class="text-right">Valor descontado</th>
                                    <th class="text-right">Participação</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($yearlyValeTotals as $index => $year): ?>
                                    <?php $yearlyShare = $yearlyValeChart['total'] > 0 ? ($yearlyValeChart['percentages'][$index] ?? 0) : 0; ?>
                                    <tr>
                                        <td><?= htmlspecialchars($year['period']); ?></td>
                                        <td class="text-right"><strong>R$ <?= number_format($year['total'], 2, ',', '.'); ?></strong></td>
                                        <td class="text-right"><span class="tag tag--soft"><?= number_format($yearlyShare, 1, ',', '.'); ?>%</span></td>
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

    <div class="dashboard-tables">
        <div class="card">
            <header class="section-heading section-heading--compact">
                <span class="icon-circle icon-circle--info" aria-hidden="true">
                    <i class="bi bi-calendar2-month"></i>
                </span>
                <div>
                    <h3>Totais pagos por mês</h3>
                    <p class="muted">Resumo líquido por competência.</p>
                </div>
            </header>
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
            <header class="section-heading section-heading--compact">
                <span class="icon-circle icon-circle--info" aria-hidden="true">
                    <i class="bi bi-calendar2-year"></i>
                </span>
                <div>
                    <h3>Totais pagos por ano</h3>
                    <p class="muted">Visão geral consolidada por exercício.</p>
                </div>
            </header>
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
        <header class="section-heading section-heading--compact">
            <span class="icon-circle icon-circle--accent" aria-hidden="true">
                <i class="bi bi-clock-history"></i>
            </span>
            <div>
                <h3>Últimos holerites</h3>
                <p class="muted">Confira os pagamentos emitidos recentemente.</p>
            </div>
        </header>
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
            <?= json_encode([
                'monthly' => $monthlyChart,
                'yearly' => $yearlyChart,
                'monthlyVale' => $monthlyValeChart,
                'yearlyVale' => $yearlyValeChart,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
        </script>
    <?php endif; ?>
</section>
