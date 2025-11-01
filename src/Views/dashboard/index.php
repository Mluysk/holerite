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
/** @var array<int, array{period: string, manual: float, transport: float, total: float}> $monthlyValeTotals */
/** @var array<int, array{period: string, manual: float, transport: float, total: float}> $yearlyValeTotals */
/** @var array{labels: array<int, string>, values: array<int, float>, percentages: array<int, float>, total: float} $monthlyValeManualChart */
/** @var array{labels: array<int, string>, values: array<int, float>, percentages: array<int, float>, total: float} $monthlyValeTransportChart */
/** @var array{labels: array<int, string>, values: array<int, float>, percentages: array<int, float>, total: float} $yearlyValeManualChart */
/** @var array{labels: array<int, string>, values: array<int, float>, percentages: array<int, float>, total: float} $yearlyValeTransportChart */
/** @var DateTimeImmutable $calendarMonth */
/** @var array<int, array<int, array{date: DateTimeImmutable|null, payrolls: Holerite\Models\Payroll[]}>> $calendarWeeks */
/** @var Holerite\Models\Payroll[] $paidMonthlyPayrolls */
/** @var Holerite\Models\Employee[] $pendingMonthlyEmployees */
/**
 * @var array{
 *     manual: array{overall: float, currentMonth: float, currentYear: float},
 *     transport: array{
 *         overall: float,
 *         currentMonth: float,
 *         currentYear: float,
 *         costOverall: float,
 *         costCurrentMonth: float,
 *         costCurrentYear: float,
 *         companyOverall: float,
 *         companyCurrentMonth: float,
 *         companyCurrentYear: float,
 *     }
 * } $valeTotals
 */
/** @var Holerite\Models\Payroll[] $thirteenthFirstInstallments */
/** @var Holerite\Models\Payroll[] $thirteenthSecondInstallments */

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

$calendarMarkerClasses = [
    'regular' => 'calendar-day__marker--salary',
    'thirteenth' => 'calendar-day__marker--thirteenth',
    'vacation' => 'calendar-day__marker--vacation',
    'termination' => 'calendar-day__marker--termination',
];

$weekDays = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];

$hasCharts = $monthlyChart['labels'] !== []
    || $yearlyChart['labels'] !== []
    || $monthlyValeManualChart['labels'] !== []
    || $monthlyValeTransportChart['labels'] !== []
    || $yearlyValeManualChart['labels'] !== []
    || $yearlyValeTransportChart['labels'] !== [];

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
}

$pageScripts[] = [
    'src' => 'js/dashboard.js',
    'defer' => true,
];

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
                <i class="bi bi-bag-check"></i>
            </div>
            <header>
                <span class="stats-card__subtitle">Vales de produtos</span>
                <h3>Total acumulado</h3>
            </header>
            <div class="stats-card__value">R$ <?= number_format($valeTotals['manual']['overall'], 2, ',', '.'); ?></div>
            <dl class="stats-card__details">
                <div>
                    <dt>Este mês</dt>
                    <dd>R$ <?= number_format($valeTotals['manual']['currentMonth'], 2, ',', '.'); ?></dd>
                </div>
                <div>
                    <dt>No ano</dt>
                    <dd>R$ <?= number_format($valeTotals['manual']['currentYear'], 2, ',', '.'); ?></dd>
                </div>
            </dl>
        </article>
        <article class="stats-card stats-card--vale">
            <div class="stats-card__icon icon-circle icon-circle--info" aria-hidden="true">
                <i class="bi bi-bus-front"></i>
            </div>
            <header>
                <span class="stats-card__subtitle">Vale-transporte</span>
                <h3>Custo acumulado</h3>
            </header>
            <div class="stats-card__value">R$ <?= number_format($valeTotals['transport']['costOverall'], 2, ',', '.'); ?></div>
            <dl class="stats-card__details">
                <div>
                    <dt>Desconto (6%) no mês</dt>
                    <dd>R$ <?= number_format($valeTotals['transport']['currentMonth'], 2, ',', '.'); ?></dd>
                </div>
                <div>
                    <dt>Diferença coberta no mês</dt>
                    <dd>R$ <?= number_format($valeTotals['transport']['companyCurrentMonth'], 2, ',', '.'); ?></dd>
                </div>
                <div>
                    <dt>Desconto (6%) no ano</dt>
                    <dd>R$ <?= number_format($valeTotals['transport']['currentYear'], 2, ',', '.'); ?></dd>
                </div>
                <div>
                    <dt>Diferença coberta no ano</dt>
                    <dd>R$ <?= number_format($valeTotals['transport']['companyCurrentYear'], 2, ',', '.'); ?></dd>
                </div>
            </dl>
            <p class="stats-card__note">
                Desconto acumulado (6%): R$ <?= number_format($valeTotals['transport']['overall'], 2, ',', '.'); ?> ·
                Empresa custeou: R$ <?= number_format($valeTotals['transport']['companyOverall'], 2, ',', '.'); ?>
            </p>
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
                                            <?php
                                            $markerGroups = [];
                                            foreach ($cellPayrolls as $cellPayroll) {
                                                $groupType = $cellPayroll->getType();
                                                $markerGroups[$groupType]['type'] = $groupType;
                                                $markerGroups[$groupType]['payrolls'][] = $cellPayroll;
                                            }
                                            ?>
                                            <div class="calendar-day__markers" role="group" aria-label="Pagamentos do dia <?= htmlspecialchars($cellDate->format('d/m')); ?>">
                                                <?php foreach ($markerGroups as $group): ?>
                                                    <?php
                                                    $groupType = $group['type'];
                                                    $markerClass = $calendarMarkerClasses[$groupType] ?? 'calendar-day__marker--default';
                                                    $labelClass = 'calendar-day__marker-label--' . ($groupType ?? 'default');
                                                    $markerTitle = $typeLabels[$groupType] ?? 'Pagamento';
                                                    $infoItems = [];
                                                    $summaryParts = [];
                                                    foreach ($group['payrolls'] as $groupPayroll) {
                                                        $employeeName = $employeeNames[$groupPayroll->getEmployeeId()] ?? 'Colaborador';
                                                        $amount = 'R$ ' . number_format($groupPayroll->getNetSalary(), 2, ',', '.');
                                                        $details = '';
                                                        if ($groupType === 'thirteenth') {
                                                            $installment = $groupPayroll->getThirteenthInstallment();
                                                            if ($installment === 'first') {
                                                                $details = '1ª parcela';
                                                            } elseif ($installment === 'second') {
                                                                $details = '2ª parcela';
                                                            }
                                                        }
                                                        $infoItems[] = [
                                                            'name' => $employeeName,
                                                            'amount' => $amount,
                                                            'details' => $details,
                                                        ];
                                                        $summary = $employeeName . ' — ' . $amount;
                                                        if ($details !== '') {
                                                            $summary .= ' (' . $details . ')';
                                                        }
                                                        $summaryParts[] = $summary;
                                                    }
                                            $infoPayload = [
                                                'title' => $markerTitle,
                                                'date' => $cellDate->format('d/m/Y'),
                                                'items' => $infoItems,
                                                'category' => $groupType,
                                            ];
                                                    $infoJson = htmlspecialchars(json_encode($infoPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                                                    $ariaLabel = $markerTitle . ' em ' . $cellDate->format('d/m/Y') . ': ' . implode('; ', $summaryParts);
                                                    ?>
                                                    <span class="calendar-day__marker-group">
                                                        <button
                                                            type="button"
                                                            class="calendar-day__marker <?= $markerClass; ?>"
                                                            data-marker-info="<?= $infoJson; ?>"
                                                            aria-expanded="false"
                                                            aria-label="<?= htmlspecialchars($ariaLabel); ?>"
                                                        ></button>
                                                        <span class="calendar-day__marker-label <?= htmlspecialchars($labelClass); ?>">Pago</span>
                                                    </span>
                                                <?php endforeach; ?>
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
            <div class="calendar-legend">
                <span class="calendar-legend__item">
                    <span class="calendar-legend__marker calendar-day__marker calendar-day__marker--salary" aria-hidden="true"></span>
                    <small>Salário mensal</small>
                </span>
                <span class="calendar-legend__item">
                    <span class="calendar-legend__marker calendar-day__marker calendar-day__marker--thirteenth" aria-hidden="true"></span>
                    <small>13º salário</small>
                </span>
                <span class="calendar-legend__item">
                    <span class="calendar-legend__marker calendar-day__marker calendar-day__marker--vacation" aria-hidden="true"></span>
                    <small>Férias</small>
                </span>
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

    <div class="dashboard-thirteenth">
        <article class="card dashboard-thirteenth__card">
            <header class="section-heading section-heading--compact">
                <span class="icon-circle icon-circle--success" aria-hidden="true">
                    <i class="bi bi-gift"></i>
                </span>
                <div>
                    <h3>13º — 1ª parcela paga</h3>
                    <p class="muted">Adiantamentos registrados no mês corrente.</p>
                </div>
            </header>
            <?php if ($thirteenthFirstInstallments === []): ?>
                <p class="muted">Nenhum pagamento da 1ª parcela registrado neste mês.</p>
            <?php else: ?>
                <ul class="status-list">
                    <?php foreach ($thirteenthFirstInstallments as $payroll): ?>
                        <li>
                            <span class="status-list__bullet status-list__bullet--success" aria-hidden="true">
                                <i class="bi bi-cash-coin"></i>
                            </span>
                            <div class="status-list__content">
                                <strong><?= htmlspecialchars($employeeNames[$payroll->getEmployeeId()] ?? 'Colaborador'); ?></strong>
                                <small class="muted">Pagamento em <?= htmlspecialchars($payroll->getPaymentDate()->format('d/m/Y')); ?> • Ref. <?= htmlspecialchars($payroll->getReferenceMonth()); ?> • R$ <?= number_format($payroll->getNetSalary(), 2, ',', '.'); ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
        <article class="card dashboard-thirteenth__card">
            <header class="section-heading section-heading--compact">
                <span class="icon-circle icon-circle--primary" aria-hidden="true">
                    <i class="bi bi-gift-fill"></i>
                </span>
                <div>
                    <h3>13º — 2ª parcela paga</h3>
                    <p class="muted">Liquidações e descontos aplicados este mês.</p>
                </div>
            </header>
            <?php if ($thirteenthSecondInstallments === []): ?>
                <p class="muted">Nenhum pagamento da 2ª parcela registrado neste mês.</p>
            <?php else: ?>
                <ul class="status-list">
                    <?php foreach ($thirteenthSecondInstallments as $payroll): ?>
                        <li>
                            <span class="status-list__bullet status-list__bullet--info" aria-hidden="true">
                                <i class="bi bi-clipboard-check"></i>
                            </span>
                            <div class="status-list__content">
                                <strong><?= htmlspecialchars($employeeNames[$payroll->getEmployeeId()] ?? 'Colaborador'); ?></strong>
                                <small class="muted">Pagamento em <?= htmlspecialchars($payroll->getPaymentDate()->format('d/m/Y')); ?> • Ref. <?= htmlspecialchars($payroll->getReferenceMonth()); ?> • R$ <?= number_format($payroll->getNetSalary(), 2, ',', '.'); ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
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
                <i class="bi bi-graph-up-arrow"></i>
            </span>
            <div>
                <h2>Controle de vales e vale-transporte</h2>
                <p class="muted">Visualize os descontos separados por vales de produtos e vale-transporte, tanto mês a mês quanto ano a ano.</p>
            </div>
        </header>
        <div class="dashboard-vale__content">
            <div class="dashboard-vale__charts">
                <article class="card chart-card">
                    <header class="chart-card__header">
                        <span class="icon-circle icon-circle--accent" aria-hidden="true">
                            <i class="bi bi-activity"></i>
                        </span>
                        <div>
                            <h3>Desempenho mensal</h3>
                            <?php if ($monthlyValeManualChart['labels'] === [] && $monthlyValeTransportChart['labels'] === []): ?>
                                <p class="muted">Os descontos mensais aparecerão conforme forem registrados.</p>
                            <?php else: ?>
                                <p class="muted">Compare os descontos mensais de vales de produtos e vale-transporte.</p>
                            <?php endif; ?>
                        </div>
                    </header>
                    <div class="chart-card__canvas chart-card__canvas--stacked">
                        <div class="chart-card__canvas-item">
                            <h4>Vales de produtos</h4>
                            <canvas id="monthlyValeProductsChart" aria-label="Evolução mensal dos vales de produtos"></canvas>
                        </div>
                        <div class="chart-card__canvas-item">
                            <h4>Vale-transporte</h4>
                            <canvas id="monthlyValeTransportChart" aria-label="Evolução mensal do vale-transporte"></canvas>
                        </div>
                    </div>
                </article>
                <article class="card chart-card">
                    <header class="chart-card__header">
                        <span class="icon-circle icon-circle--primary" aria-hidden="true">
                            <i class="bi bi-bar-chart"></i>
                        </span>
                        <div>
                            <h3>Desempenho anual</h3>
                            <?php if ($yearlyValeManualChart['labels'] === [] && $yearlyValeTransportChart['labels'] === []): ?>
                                <p class="muted">Os totais anuais aparecerão após os primeiros registros.</p>
                            <?php else: ?>
                                <p class="muted">Soma anual dos descontos por categoria.</p>
                            <?php endif; ?>
                        </div>
                    </header>
                    <div class="chart-card__canvas chart-card__canvas--stacked">
                        <div class="chart-card__canvas-item">
                            <h4>Vales de produtos</h4>
                            <canvas id="yearlyValeProductsChart" aria-label="Evolução anual dos vales de produtos"></canvas>
                        </div>
                        <div class="chart-card__canvas-item">
                            <h4>Vale-transporte</h4>
                            <canvas id="yearlyValeTransportChart" aria-label="Evolução anual do vale-transporte"></canvas>
                        </div>
                    </div>
                </article>
            </div>
            <div class="dashboard-vale__tables">
                <article class="card">
                    <header class="section-heading section-heading--compact">
                        <span class="icon-circle icon-circle--accent" aria-hidden="true">
                            <i class="bi bi-table"></i>
                        </span>
                        <div>
                            <h3>Resumo mensal</h3>
                            <p class="muted">Totais separados por categoria para cada mês.</p>
                        </div>
                    </header>
                    <?php if ($monthlyValeTotals === []): ?>
                        <p class="muted">Nenhum desconto de vale foi registrado até o momento.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                <tr>
                                    <th>Mês</th>
                                    <th class="text-right">Vales de produtos</th>
                                    <th class="text-right">Vale-transporte</th>
                                    <th class="text-right">Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($monthlyValeTotals as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['period']); ?></td>
                                        <td class="text-right">R$ <?= number_format($row['manual'], 2, ',', '.'); ?></td>
                                        <td class="text-right">R$ <?= number_format($row['transport'], 2, ',', '.'); ?></td>
                                        <td class="text-right">R$ <?= number_format($row['total'], 2, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </article>
                <article class="card">
                    <header class="section-heading section-heading--compact">
                        <span class="icon-circle icon-circle--primary" aria-hidden="true">
                            <i class="bi bi-table"></i>
                        </span>
                        <div>
                            <h3>Resumo anual</h3>
                            <p class="muted">Totais consolidados por ano e categoria.</p>
                        </div>
                    </header>
                    <?php if ($yearlyValeTotals === []): ?>
                        <p class="muted">Ainda não há descontos registrados para este ano.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                <tr>
                                    <th>Ano</th>
                                    <th class="text-right">Vales de produtos</th>
                                    <th class="text-right">Vale-transporte</th>
                                    <th class="text-right">Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($yearlyValeTotals as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['period']); ?></td>
                                        <td class="text-right">R$ <?= number_format($row['manual'], 2, ',', '.'); ?></td>
                                        <td class="text-right">R$ <?= number_format($row['transport'], 2, ',', '.'); ?></td>
                                        <td class="text-right">R$ <?= number_format($row['total'], 2, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </article>
            </div>
        </div>
    </section>


    <div class="dashboard-tables">
        <div class="card">
            <header class="section-heading section-heading--compact">
                <span class="icon-circle icon-circle--info" aria-hidden="true">
                    <i class="bi bi-calendar-month"></i>
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
                    <i class="bi bi-calendar3"></i>
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
                'monthlyValeProducts' => $monthlyValeManualChart,
                'monthlyValeTransport' => $monthlyValeTransportChart,
                'yearlyValeProducts' => $yearlyValeManualChart,
                'yearlyValeTransport' => $yearlyValeTransportChart,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
        </script>
    <?php endif; ?>
</section>
