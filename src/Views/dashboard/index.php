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
/** @var array<int, array{period: string, manual: float, transport: float, total: float, transport_days: int, transport_trips: int}> $monthlyValeTotals */
/** @var array<int, array{period: string, manual: float, transport: float, total: float, transport_days: int, transport_trips: int}> $yearlyValeTotals */
/**
 * @var array{
 *     manual: array{current: array{period: string, value: float|null}|null, previous: array{period: string, value: float|null}|null, difference: float|null, percentage: float|null},
 *     transport: array{current: array{period: string, value: float|null}|null, previous: array{period: string, value: float|null}|null, difference: float|null, percentage: float|null}
 * } $monthlyValeComparisons
 */
/**
 * @var array{
 *     manual: array{current: array{period: string, value: float|null}|null, previous: array{period: string, value: float|null}|null, difference: float|null, percentage: float|null},
 *     transport: array{current: array{period: string, value: float|null}|null, previous: array{period: string, value: float|null}|null, difference: float|null, percentage: float|null}
 * } $yearlyValeComparisons
 */
/** @var array{labels: array<int, string>, values: array<int, float>, percentages: array<int, float>, total: float} $monthlyValeManualChart */
/** @var array{labels: array<int, string>, values: array<int, float>, percentages: array<int, float>, total: float} $monthlyValeTransportChart */
/** @var array{labels: array<int, string>, values: array<int, float>, percentages: array<int, float>, total: float} $yearlyValeManualChart */
/** @var array{labels: array<int, string>, values: array<int, float>, percentages: array<int, float>, total: float} $yearlyValeTransportChart */
/** @var DateTimeImmutable $calendarMonth */
/** @var array<int, array<int, array{date: DateTimeImmutable|null, payrolls: Holerite\Models\Payroll[], birthdays: Holerite\Models\Employee[], vacations: array<int, array{employee: Holerite\Models\Employee, available_from: DateTimeImmutable|null, concession_end: DateTimeImmutable|null, status: string}>, holidays: array<int, array{name: string, type: string, scope: string, date: DateTimeImmutable}>}>> $calendarWeeks */
/** @var Holerite\Models\Payroll[] $paidMonthlyPayrolls */
/** @var Holerite\Models\Employee[] $pendingMonthlyEmployees */
/** @var array<int, array{employee: Holerite\Models\Employee, available_from: DateTimeImmutable|null, concession_end: DateTimeImmutable|null, status: string, eligible: bool, notice_from: DateTimeImmutable|null}> $vacationAlerts */
/** @var array<int, array{employee: Holerite\Models\Employee, date: DateTimeImmutable}> $birthdayAlerts */
/** @var array<int, array{employee: Holerite\Models\Employee, available_from: DateTimeImmutable, notice_from: DateTimeImmutable, status: string, base_origin: string, base_origin_label: string, base_start: DateTimeImmutable|null}> $vacationPopupAlerts */
/** @var array<int, array{employee: Holerite\Models\Employee, date: DateTimeImmutable, years: int|null, hire_date: DateTimeImmutable|null}> $birthdayPopupAlerts */
/** @var DateTimeImmutable $today */
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
/** @var array{manual: float, transport: float, total: float, transport_days: int, transport_trips: int} $monthlyValeSummary */
/** @var array{manual: float, transport: float, total: float, transport_days: int, transport_trips: int} $yearlyValeSummary */
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
    'advance' => 'Adiantamento salarial',
    'birthday' => 'Aniversários',
    'vacation_plan' => 'Férias programadas',
    'holiday' => 'Feriados',
];

$calendarMarkerClasses = [
    'regular' => 'calendar-day__marker--salary',
    'thirteenth' => 'calendar-day__marker--thirteenth',
    'vacation' => 'calendar-day__marker--vacation',
    'termination' => 'calendar-day__marker--termination',
    'advance' => 'calendar-day__marker--advance',
    'birthday' => 'calendar-day__marker--birthday',
    'vacation_plan' => 'calendar-day__marker--vacation-plan',
    'holiday' => 'calendar-day__marker--holiday',
];

$markerLabelText = [
    'regular' => 'Pago',
    'thirteenth' => 'Pago',
    'vacation' => 'Pago',
    'termination' => 'Pago',
    'advance' => 'Adiant.',
    'birthday' => 'Aniversário',
    'vacation_plan' => 'Férias',
    'holiday' => 'Feriado',
];

$weekDays = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];

$hasCharts = $monthlyChart['labels'] !== []
    || $yearlyChart['labels'] !== []
    || $monthlyValeManualChart['labels'] !== []
    || $monthlyValeTransportChart['labels'] !== []
    || $yearlyValeManualChart['labels'] !== []
    || $yearlyValeTransportChart['labels'] !== [];

$emptyComparison = static fn (): array => [
    'current' => null,
    'previous' => null,
    'difference' => null,
    'percentage' => null,
];

$normalizeComparisons = static function (?array $data) use ($emptyComparison): array {
    $payload = is_array($data) ? $data : [];

    return [
        'manual' => array_merge($emptyComparison(), $payload['manual'] ?? []),
        'transport' => array_merge($emptyComparison(), $payload['transport'] ?? []),
    ];
};

$monthlyValeComparisons = $normalizeComparisons($monthlyValeComparisons ?? null);
$yearlyValeComparisons = $normalizeComparisons($yearlyValeComparisons ?? null);

$monthlyManualComparison = $monthlyValeComparisons['manual'];
$monthlyTransportComparison = $monthlyValeComparisons['transport'];
$yearlyManualComparison = $yearlyValeComparisons['manual'];
$yearlyTransportComparison = $yearlyValeComparisons['transport'];

$formatCurrencyValue = static function (?float $value): string {
    if ($value === null) {
        return '—';
    }

    return 'R$ ' . number_format($value, 2, ',', '.');
};

$formatDifferenceValue = static function (?float $value): string {
    if ($value === null) {
        return '—';
    }

    if ($value > 0.0) {
        return '+R$ ' . number_format($value, 2, ',', '.');
    }

    if ($value < 0.0) {
        return '-R$ ' . number_format(abs($value), 2, ',', '.');
    }

    return 'R$ ' . number_format(0.0, 2, ',', '.');
};

$formatPercentageValue = static function (?float $value): ?string {
    if ($value === null) {
        return null;
    }

    if ($value === 0.0) {
        return '0%';
    }

    $sign = $value > 0 ? '+' : '-';

    return $sign . number_format(abs($value), 1, ',', '.') . '%';
};

$getTrendModifier = static function (?float $value): string {
    if ($value === null) {
        return 'chart-card__delta--neutral';
    }

    if ($value > 0.0) {
        return 'chart-card__delta--up';
    }

    if ($value < 0.0) {
        return 'chart-card__delta--down';
    }

    return 'chart-card__delta--neutral';
};

$getTrendIcon = static function (?float $value): string {
    if ($value === null) {
        return 'bi-dash-lg';
    }

    if ($value > 0.0) {
        return 'bi-arrow-up-right';
    }

    if ($value < 0.0) {
        return 'bi-arrow-down-right';
    }

    return 'bi-dash-lg';
};

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
            <?php
            $holidayCity = $holidaySettings->getCity();
            $holidayState = $holidaySettings->getState();
            if ($holidaySettings->includeMunicipal() && ($holidayCity !== '' || $holidayState !== '')):
            ?>
                <p class="calendar-legend__note muted">
                    Feriados municipais considerando <?= htmlspecialchars($holidayCity !== '' ? $holidayCity : 'sua cidade'); ?><?= $holidayState !== '' ? ' / ' . htmlspecialchars($holidayState) : ''; ?>.
                </p>
            <?php endif; ?>
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

    <div class="dashboard-alerts">
        <div class="card dashboard-alerts__card">
            <header class="section-heading section-heading--compact">
                <span class="icon-circle icon-circle--warning" aria-hidden="true">
                    <i class="bi bi-umbrella-fill"></i>
                </span>
                <div>
                    <h3>Férias previstas</h3>
                    <p class="muted">Acompanhe colaboradores com períodos liberados neste mês.</p>
                </div>
            </header>
            <?php $vacationPreview = array_slice($vacationAlerts, 0, 6); ?>
            <?php if ($vacationPreview === []): ?>
                <p class="muted">Nenhuma férias programada para o período atual.</p>
            <?php else: ?>
                <ul class="alert-list">
                    <?php foreach ($vacationPreview as $alert): ?>
                        <?php $available = $alert['available_from']; ?>
                        <?php $concessionEnd = $alert['concession_end'] ?? null; ?>
                        <li>
                            <div class="alert-list__content">
                                <strong><?= htmlspecialchars($alert['employee']->getName()); ?></strong>
                                <small class="muted">
                                    Disponível em <?= $available instanceof DateTimeImmutable ? htmlspecialchars($available->format('d/m')) : '—'; ?>
                                    <?php if ($concessionEnd instanceof DateTimeImmutable): ?>
                                        • Concessão até <?= htmlspecialchars($concessionEnd->format('d/m')); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <span class="status-pill <?= $alert['eligible'] ? 'status-pill--success' : 'status-pill--warning'; ?>">
                                <?= htmlspecialchars($alert['status'] ?: 'Em aquisição'); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card dashboard-alerts__card">
            <header class="section-heading section-heading--compact">
                <span class="icon-circle icon-circle--info" aria-hidden="true">
                    <i class="bi bi-cake2"></i>
                </span>
                <div>
                    <h3>Aniversariantes do mês</h3>
                    <p class="muted">Celebre os colaboradores que fazem aniversário neste período.</p>
                </div>
            </header>
            <?php $birthdayPreview = array_slice($birthdayAlerts, 0, 6); ?>
            <?php if ($birthdayPreview === []): ?>
                <p class="muted">Nenhum aniversário registrado para este mês.</p>
            <?php else: ?>
                <ul class="alert-list">
                    <?php foreach ($birthdayPreview as $alert): ?>
                        <li>
                            <div class="alert-list__content">
                                <strong><?= htmlspecialchars($alert['employee']->getName()); ?></strong>
                                <small class="muted">Dia <?= htmlspecialchars($alert['date']->format('d/m')); ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
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
        <article class="stats-card stats-card--transport">
            <div class="transport-card__summary">
                <div class="transport-card__header">
                    <span class="icon-circle icon-circle--info transport-card__icon" aria-hidden="true">
                        <i class="bi bi-bus-front"></i>
                    </span>
                    <div>
                        <span class="transport-card__eyebrow">Vale-transporte</span>
                        <h3>Custo acumulado</h3>
                    </div>
                </div>
                <div class="transport-card__summary-total">
                    <span class="transport-card__label">Total desembolsado</span>
                    <span class="transport-card__total">R$ <?= number_format($valeTotals['transport']['costOverall'], 2, ',', '.'); ?></span>
                </div>
                <dl class="transport-card__periods">
                    <div>
                        <dt>Gasto no mês</dt>
                        <dd>R$ <?= number_format($valeTotals['transport']['costCurrentMonth'], 2, ',', '.'); ?></dd>
                    </div>
                    <div>
                        <dt>Gasto no ano</dt>
                        <dd>R$ <?= number_format($valeTotals['transport']['costCurrentYear'], 2, ',', '.'); ?></dd>
                    </div>
                </dl>
                <p class="transport-card__note">
                    Dedução limitada a 6% do salário base. Descontos acumulados: R$ <?= number_format($valeTotals['transport']['overall'], 2, ',', '.'); ?> ·
                    Empresa custeou: R$ <?= number_format($valeTotals['transport']['companyOverall'], 2, ',', '.'); ?>
                </p>
            </div>
            <div class="transport-card__panels">
                <div class="transport-card__panel">
                    <div class="transport-card__panel-header">
                        <span class="icon-circle icon-circle--warning transport-card__panel-icon" aria-hidden="true">
                            <i class="bi bi-person-check"></i>
                        </span>
                        <div>
                            <h4>Colaboradores</h4>
                            <p>Desconto aplicado (6%)</p>
                        </div>
                    </div>
                    <div class="transport-card__panel-figure">
                        <span>Total descontado</span>
                        <strong>R$ <?= number_format($valeTotals['transport']['overall'], 2, ',', '.'); ?></strong>
                    </div>
                    <dl class="transport-card__panel-metrics">
                        <div>
                            <dt>No mês</dt>
                            <dd>R$ <?= number_format($valeTotals['transport']['currentMonth'], 2, ',', '.'); ?></dd>
                        </div>
                        <div>
                            <dt>No ano</dt>
                            <dd>R$ <?= number_format($valeTotals['transport']['currentYear'], 2, ',', '.'); ?></dd>
                        </div>
                    </dl>
                </div>
                <div class="transport-card__panel">
                    <div class="transport-card__panel-header">
                        <span class="icon-circle icon-circle--success transport-card__panel-icon" aria-hidden="true">
                            <i class="bi bi-building-check"></i>
                        </span>
                        <div>
                            <h4>Empresa</h4>
                            <p>Diferença custeada</p>
                        </div>
                    </div>
                    <div class="transport-card__panel-figure">
                        <span>Total custeado</span>
                        <strong>R$ <?= number_format($valeTotals['transport']['companyOverall'], 2, ',', '.'); ?></strong>
                    </div>
                    <dl class="transport-card__panel-metrics">
                        <div>
                            <dt>No mês</dt>
                            <dd>R$ <?= number_format($valeTotals['transport']['companyCurrentMonth'], 2, ',', '.'); ?></dd>
                        </div>
                        <div>
                            <dt>No ano</dt>
                            <dd>R$ <?= number_format($valeTotals['transport']['companyCurrentYear'], 2, ',', '.'); ?></dd>
                        </div>
                    </dl>
                </div>
            </div>
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
                                    <?php $cellBirthdays = $cell['birthdays']; ?>
                                    <?php $cellVacations = $cell['vacations']; ?>
                                    <?php $cellHolidays = $cell['holidays'] ?? []; ?>
                                    <?php $hasEvents = $cellPayrolls !== [] || $cellBirthdays !== [] || $cellVacations !== [] || $cellHolidays !== []; ?>
                                    <td class="calendar-day<?= $hasEvents ? ' calendar-day--has-events' : ''; ?>">
                                        <div class="calendar-day__date"><?= htmlspecialchars($cellDate->format('d')); ?></div>
                                        <?php if ($hasEvents): ?>
                                            <?php
                                            $markerGroups = [];
                                            if ($cellPayrolls !== []) {
                                                foreach ($cellPayrolls as $cellPayroll) {
                                                    $groupType = $cellPayroll->getType();
                                                    $markerGroups[$groupType]['type'] = $groupType;
                                                    $markerGroups[$groupType]['payrolls'][] = $cellPayroll;
                                                }
                                            }

                                            if ($cellBirthdays !== []) {
                                                $markerGroups['birthday'] = [
                                                    'type' => 'birthday',
                                                    'employees' => $cellBirthdays,
                                                ];
                                            }

                                            if ($cellVacations !== []) {
                                                $markerGroups['vacation_plan'] = [
                                                    'type' => 'vacation_plan',
                                                    'vacations' => $cellVacations,
                                                ];
                                            }

                                            if ($cellHolidays !== []) {
                                                $markerGroups['holiday'] = [
                                                    'type' => 'holiday',
                                                    'holidays' => $cellHolidays,
                                                ];
                                            }
                                            ?>
                                            <div class="calendar-day__markers" role="group" aria-label="Eventos do dia <?= htmlspecialchars($cellDate->format('d/m')); ?>">
                                                <?php foreach ($markerGroups as $group): ?>
                                                    <?php $groupType = $group['type']; ?>
                                                    <?php if ($groupType === 'birthday'): ?>
                                                        <?php
                                                        $markerClass = $calendarMarkerClasses['birthday'] ?? 'calendar-day__marker--default';
                                                        $markerTitle = $typeLabels['birthday'] ?? 'Aniversários';
                                                        $infoItems = [];
                                                        $summaryParts = [];
                                                        foreach ($group['employees'] as $birthdayEmployee) {
                                                            $employeeName = $birthdayEmployee->getName();
                                                            $infoItems[] = [
                                                                'name' => $employeeName,
                                                                'details' => 'Celebra hoje',
                                                            ];
                                                            $summaryParts[] = $employeeName;
                                                        }
                                                        $infoPayload = [
                                                            'title' => $markerTitle,
                                                            'date' => $cellDate->format('d/m/Y'),
                                                            'items' => $infoItems,
                                                            'category' => 'birthday',
                                                            'status' => 'Aniversário',
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
                                                            >
                                                                <i class="bi bi-cake2" aria-hidden="true"></i>
                                                                <span class="visually-hidden">Aniversários</span>
                                                            </button>
                                                        </span>
                                                    <?php elseif ($groupType === 'holiday'): ?>
                                                        <?php
                                                        $markerClass = $calendarMarkerClasses['holiday'] ?? 'calendar-day__marker--default';
                                                        $markerTitle = $typeLabels['holiday'] ?? 'Feriados';
                                                        $infoItems = [];
                                                        $summaryParts = [];
                                                        foreach ($group['holidays'] as $holiday) {
                                                            $holidayName = (string) ($holiday['name'] ?? 'Feriado');
                                                            $scope = ucfirst((string) ($holiday['scope'] ?? 'nacional'));
                                                            $infoItems[] = [
                                                                'name' => $holidayName,
                                                                'details' => $scope,
                                                            ];
                                                            $summaryParts[] = $holidayName;
                                                        }
                                                        $infoPayload = [
                                                            'title' => $markerTitle,
                                                            'date' => $cellDate->format('d/m/Y'),
                                                            'items' => $infoItems,
                                                            'category' => 'holiday',
                                                            'status' => 'Feriado',
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
                                                            >
                                                                <i class="bi bi-flag-fill" aria-hidden="true"></i>
                                                                <span class="visually-hidden">Feriados</span>
                                                            </button>
                                                        </span>
                                                    <?php elseif ($groupType === 'vacation_plan'): ?>
                                                        <?php
                                                        $markerClass = $calendarMarkerClasses['vacation_plan'] ?? 'calendar-day__marker--vacation';
                                                        $labelClass = 'calendar-day__marker-label--vacation';
                                                        $markerTitle = $typeLabels['vacation_plan'] ?? 'Férias programadas';
                                                        $labelText = $markerLabelText['vacation_plan'] ?? 'Férias';
                                                        $infoItems = [];
                                                        $summaryParts = [];
                                                        foreach ($group['vacations'] as $vacationInfo) {
                                                            /** @var Holerite\Models\Employee $vacationEmployee */
                                                            $vacationEmployee = $vacationInfo['employee'];
                                                            $employeeName = $vacationEmployee->getName();
                                                            $concessionEnd = $vacationInfo['concession_end'] ?? null;
                                                            $details = $concessionEnd instanceof DateTimeImmutable
                                                                ? 'Concessão até ' . $concessionEnd->format('d/m')
                                                                : 'Período liberado';
                                                            $infoItems[] = [
                                                                'name' => $employeeName,
                                                                'details' => $details,
                                                            ];
                                                            $summaryParts[] = $employeeName . ' — ' . $details;
                                                        }
                                                        $infoPayload = [
                                                            'title' => $markerTitle,
                                                            'date' => $cellDate->format('d/m/Y'),
                                                            'items' => $infoItems,
                                                            'category' => 'vacation_plan',
                                                            'status' => 'Planejado',
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
                                                            >
                                                                <i class="bi bi-umbrella-fill" aria-hidden="true"></i>
                                                                <span class="visually-hidden">Férias programadas</span>
                                                            </button>
                                                            <span class="calendar-day__marker-label <?= htmlspecialchars($labelClass); ?>"><?= htmlspecialchars($labelText); ?></span>
                                                        </span>
                                                    <?php else: ?>
                                                        <?php
                                                        $markerClass = $calendarMarkerClasses[$groupType] ?? 'calendar-day__marker--default';
                                                        $labelClass = 'calendar-day__marker-label--' . ($groupType ?? 'default');
                                                        $markerTitle = $typeLabels[$groupType] ?? 'Pagamento';
                                                        $labelText = $markerLabelText[$groupType] ?? 'Evento';
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
                                                            } elseif ($groupType === 'advance') {
                                                                $details = 'Adiantamento';
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
                                                        $statusText = $groupType === 'advance' ? 'Adiantamento' : 'Pago';
                                                        $infoPayload = [
                                                            'title' => $markerTitle,
                                                            'date' => $cellDate->format('d/m/Y'),
                                                            'items' => $infoItems,
                                                            'category' => $groupType,
                                                            'status' => $statusText,
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
                                                            >
                                                                <?php if ($groupType === 'holiday'): ?>
                                                                    <i class="bi bi-flag-fill" aria-hidden="true"></i>
                                                                    <span class="visually-hidden">Feriado</span>
                                                                <?php endif; ?>
                                                            </button>
                                                            <span class="calendar-day__marker-label <?= htmlspecialchars($labelClass); ?>"><?= htmlspecialchars($labelText); ?></span>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="calendar-day__placeholder muted">Sem eventos</div>
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
                <span class="calendar-legend__item">
                    <span class="calendar-legend__marker calendar-day__marker calendar-day__marker--advance" aria-hidden="true"></span>
                    <small>Adiantamento salarial</small>
                </span>
                <span class="calendar-legend__item">
                    <span class="calendar-legend__marker calendar-legend__marker--birthday" aria-hidden="true">
                        <i class="bi bi-cake2"></i>
                    </span>
                    <small>Aniversários</small>
                </span>
                <span class="calendar-legend__item">
                    <span class="calendar-legend__marker calendar-day__marker calendar-day__marker--holiday" aria-hidden="true">
                        <i class="bi bi-flag-fill"></i>
                    </span>
                    <small>Feriados</small>
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
                            <?php
                            $monthlyManualCurrent = $monthlyManualComparison['current'];
                            $monthlyManualPrevious = $monthlyManualComparison['previous'];
                            $monthlyManualDifference = $monthlyManualComparison['difference'];
                            $monthlyManualPercentage = $monthlyManualComparison['percentage'];
                            $monthlyManualCurrentLabel = $monthlyManualCurrent['period'] ?? null;
                            $monthlyManualPreviousLabel = $monthlyManualPrevious['period'] ?? null;
                            $monthlyManualCurrentText = $formatCurrencyValue($monthlyManualCurrent['value'] ?? null);
                            $monthlyManualPreviousText = $formatCurrencyValue($monthlyManualPrevious['value'] ?? null);
                            $monthlyManualDifferenceText = $formatDifferenceValue($monthlyManualDifference);
                            $monthlyManualPercentageText = $formatPercentageValue($monthlyManualPercentage);
                            $monthlyManualTrendClass = $getTrendModifier($monthlyManualDifference);
                            $monthlyManualTrendIcon = $getTrendIcon($monthlyManualDifference);
                            ?>
                            <dl class="chart-card__stats">
                                <div>
                                    <dt>Último mês<?= $monthlyManualCurrentLabel ? ' (' . htmlspecialchars($monthlyManualCurrentLabel) . ')' : ''; ?></dt>
                                    <dd><?= htmlspecialchars($monthlyManualCurrentText, ENT_QUOTES, 'UTF-8'); ?></dd>
                                </div>
                                <div>
                                    <dt>Mês anterior<?= $monthlyManualPreviousLabel ? ' (' . htmlspecialchars($monthlyManualPreviousLabel) . ')' : ''; ?></dt>
                                    <dd><?= htmlspecialchars($monthlyManualPreviousText, ENT_QUOTES, 'UTF-8'); ?></dd>
                                </div>
                                <div>
                                    <dt>Variação mensal</dt>
                                    <dd>
                                        <?php if ($monthlyManualDifference !== null): ?>
                                            <span class="chart-card__delta <?= htmlspecialchars($monthlyManualTrendClass, ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="bi <?= htmlspecialchars($monthlyManualTrendIcon, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                                                <?= htmlspecialchars($monthlyManualDifferenceText, ENT_QUOTES, 'UTF-8'); ?>
                                                <?php if ($monthlyManualPercentageText !== null): ?>
                                                    <small><?= htmlspecialchars($monthlyManualPercentageText, ENT_QUOTES, 'UTF-8'); ?></small>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="muted">Sem histórico</span>
                                        <?php endif; ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt>Total acumulado</dt>
                                    <dd><?= htmlspecialchars($formatCurrencyValue($valeTotals['manual']['overall'] ?? null), ENT_QUOTES, 'UTF-8'); ?></dd>
                                </div>
                                <div>
                                    <dt>No ano</dt>
                                    <dd><?= htmlspecialchars($formatCurrencyValue($valeTotals['manual']['currentYear'] ?? null), ENT_QUOTES, 'UTF-8'); ?></dd>
                                </div>
                            </dl>
                        </div>
                        <div class="chart-card__canvas-item">
                            <h4>Vale-transporte</h4>
                            <canvas id="monthlyValeTransportChart" aria-label="Evolução mensal do vale-transporte"></canvas>
                            <?php
                            $monthlyTransportCurrent = $monthlyTransportComparison['current'];
                            $monthlyTransportPrevious = $monthlyTransportComparison['previous'];
                            $monthlyTransportDifference = $monthlyTransportComparison['difference'];
                            $monthlyTransportPercentage = $monthlyTransportComparison['percentage'];
                            $monthlyTransportCurrentLabel = $monthlyTransportCurrent['period'] ?? null;
                            $monthlyTransportPreviousLabel = $monthlyTransportPrevious['period'] ?? null;
                            $monthlyTransportCurrentText = $formatCurrencyValue($monthlyTransportCurrent['value'] ?? null);
                            $monthlyTransportPreviousText = $formatCurrencyValue($monthlyTransportPrevious['value'] ?? null);
                            $monthlyTransportDifferenceText = $formatDifferenceValue($monthlyTransportDifference);
                            $monthlyTransportPercentageText = $formatPercentageValue($monthlyTransportPercentage);
                            $monthlyTransportTrendClass = $getTrendModifier($monthlyTransportDifference);
                            $monthlyTransportTrendIcon = $getTrendIcon($monthlyTransportDifference);
                            ?>
                            <dl class="chart-card__stats">
                                <div>
                                    <dt>Último mês<?= $monthlyTransportCurrentLabel ? ' (' . htmlspecialchars($monthlyTransportCurrentLabel) . ')' : ''; ?></dt>
                                    <dd><?= htmlspecialchars($monthlyTransportCurrentText, ENT_QUOTES, 'UTF-8'); ?></dd>
                                </div>
                                <div>
                                    <dt>Mês anterior<?= $monthlyTransportPreviousLabel ? ' (' . htmlspecialchars($monthlyTransportPreviousLabel) . ')' : ''; ?></dt>
                                    <dd><?= htmlspecialchars($monthlyTransportPreviousText, ENT_QUOTES, 'UTF-8'); ?></dd>
                                </div>
                                <div>
                                    <dt>Variação mensal</dt>
                                    <dd>
                                        <?php if ($monthlyTransportDifference !== null): ?>
                                            <span class="chart-card__delta <?= htmlspecialchars($monthlyTransportTrendClass, ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="bi <?= htmlspecialchars($monthlyTransportTrendIcon, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                                                <?= htmlspecialchars($monthlyTransportDifferenceText, ENT_QUOTES, 'UTF-8'); ?>
                                                <?php if ($monthlyTransportPercentageText !== null): ?>
                                                    <small><?= htmlspecialchars($monthlyTransportPercentageText, ENT_QUOTES, 'UTF-8'); ?></small>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="muted">Sem histórico</span>
                                        <?php endif; ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt>Desconto acumulado</dt>
                                    <dd><?= htmlspecialchars($formatCurrencyValue($valeTotals['transport']['overall'] ?? null), ENT_QUOTES, 'UTF-8'); ?></dd>
                                </div>
                                <div>
                                    <dt>Diferença custeada</dt>
                                    <dd><?= htmlspecialchars($formatCurrencyValue($valeTotals['transport']['companyOverall'] ?? null), ENT_QUOTES, 'UTF-8'); ?></dd>
                                </div>
                            </dl>
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
                            <?php
                            $yearlyManualCurrent = $yearlyManualComparison['current'];
                            $yearlyManualPrevious = $yearlyManualComparison['previous'];
                            $yearlyManualDifference = $yearlyManualComparison['difference'];
                            $yearlyManualPercentage = $yearlyManualComparison['percentage'];
                            $yearlyManualCurrentLabel = $yearlyManualCurrent['period'] ?? null;
                            $yearlyManualPreviousLabel = $yearlyManualPrevious['period'] ?? null;
                            $yearlyManualCurrentText = $formatCurrencyValue($yearlyManualCurrent['value'] ?? null);
                            $yearlyManualPreviousText = $formatCurrencyValue($yearlyManualPrevious['value'] ?? null);
                            $yearlyManualDifferenceText = $formatDifferenceValue($yearlyManualDifference);
                            $yearlyManualPercentageText = $formatPercentageValue($yearlyManualPercentage);
                            $yearlyManualTrendClass = $getTrendModifier($yearlyManualDifference);
                            $yearlyManualTrendIcon = $getTrendIcon($yearlyManualDifference);
                            ?>
                            <dl class="chart-card__stats">
                                <div>
                                    <dt>Último ano<?= $yearlyManualCurrentLabel ? ' (' . htmlspecialchars($yearlyManualCurrentLabel) . ')' : ''; ?></dt>
                                    <dd><?= htmlspecialchars($yearlyManualCurrentText, ENT_QUOTES, 'UTF-8'); ?></dd>
                                </div>
                                <div>
                                    <dt>Ano anterior<?= $yearlyManualPreviousLabel ? ' (' . htmlspecialchars($yearlyManualPreviousLabel) . ')' : ''; ?></dt>
                                    <dd><?= htmlspecialchars($yearlyManualPreviousText, ENT_QUOTES, 'UTF-8'); ?></dd>
                                </div>
                                <div>
                                    <dt>Variação anual</dt>
                                    <dd>
                                        <?php if ($yearlyManualDifference !== null): ?>
                                            <span class="chart-card__delta <?= htmlspecialchars($yearlyManualTrendClass, ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="bi <?= htmlspecialchars($yearlyManualTrendIcon, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                                                <?= htmlspecialchars($yearlyManualDifferenceText, ENT_QUOTES, 'UTF-8'); ?>
                                                <?php if ($yearlyManualPercentageText !== null): ?>
                                                    <small><?= htmlspecialchars($yearlyManualPercentageText, ENT_QUOTES, 'UTF-8'); ?></small>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="muted">Sem histórico</span>
                                        <?php endif; ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt>Total acumulado</dt>
                                    <dd><?= htmlspecialchars($formatCurrencyValue($valeTotals['manual']['overall'] ?? null), ENT_QUOTES, 'UTF-8'); ?></dd>
                                </div>
                            </dl>
                        </div>
                        <div class="chart-card__canvas-item">
                            <h4>Vale-transporte</h4>
                            <canvas id="yearlyValeTransportChart" aria-label="Evolução anual do vale-transporte"></canvas>
                            <?php
                            $yearlyTransportCurrent = $yearlyTransportComparison['current'];
                            $yearlyTransportPrevious = $yearlyTransportComparison['previous'];
                            $yearlyTransportDifference = $yearlyTransportComparison['difference'];
                            $yearlyTransportPercentage = $yearlyTransportComparison['percentage'];
                            $yearlyTransportCurrentLabel = $yearlyTransportCurrent['period'] ?? null;
                            $yearlyTransportPreviousLabel = $yearlyTransportPrevious['period'] ?? null;
                            $yearlyTransportCurrentText = $formatCurrencyValue($yearlyTransportCurrent['value'] ?? null);
                            $yearlyTransportPreviousText = $formatCurrencyValue($yearlyTransportPrevious['value'] ?? null);
                            $yearlyTransportDifferenceText = $formatDifferenceValue($yearlyTransportDifference);
                            $yearlyTransportPercentageText = $formatPercentageValue($yearlyTransportPercentage);
                            $yearlyTransportTrendClass = $getTrendModifier($yearlyTransportDifference);
                            $yearlyTransportTrendIcon = $getTrendIcon($yearlyTransportDifference);
                            ?>
                            <dl class="chart-card__stats">
                                <div>
                                    <dt>Último ano<?= $yearlyTransportCurrentLabel ? ' (' . htmlspecialchars($yearlyTransportCurrentLabel) . ')' : ''; ?></dt>
                                    <dd><?= htmlspecialchars($yearlyTransportCurrentText, ENT_QUOTES, 'UTF-8'); ?></dd>
                                </div>
                                <div>
                                    <dt>Ano anterior<?= $yearlyTransportPreviousLabel ? ' (' . htmlspecialchars($yearlyTransportPreviousLabel) . ')' : ''; ?></dt>
                                    <dd><?= htmlspecialchars($yearlyTransportPreviousText, ENT_QUOTES, 'UTF-8'); ?></dd>
                                </div>
                                <div>
                                    <dt>Variação anual</dt>
                                    <dd>
                                        <?php if ($yearlyTransportDifference !== null): ?>
                                            <span class="chart-card__delta <?= htmlspecialchars($yearlyTransportTrendClass, ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="bi <?= htmlspecialchars($yearlyTransportTrendIcon, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                                                <?= htmlspecialchars($yearlyTransportDifferenceText, ENT_QUOTES, 'UTF-8'); ?>
                                                <?php if ($yearlyTransportPercentageText !== null): ?>
                                                    <small><?= htmlspecialchars($yearlyTransportPercentageText, ENT_QUOTES, 'UTF-8'); ?></small>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="muted">Sem histórico</span>
                                        <?php endif; ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt>Desconto acumulado</dt>
                                    <dd><?= htmlspecialchars($formatCurrencyValue($valeTotals['transport']['overall'] ?? null), ENT_QUOTES, 'UTF-8'); ?></dd>
                                </div>
                                <div>
                                    <dt>Diferença custeada</dt>
                                    <dd><?= htmlspecialchars($formatCurrencyValue($valeTotals['transport']['companyOverall'] ?? null), ENT_QUOTES, 'UTF-8'); ?></dd>
                                </div>
                            </dl>
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
                                    <th class="text-right">Dias de VT</th>
                                    <th class="text-right">Passagens</th>
                                    <th class="text-right">Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($monthlyValeTotals as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['period']); ?></td>
                                        <td class="text-right">R$ <?= number_format($row['manual'], 2, ',', '.'); ?></td>
                                        <td class="text-right">R$ <?= number_format($row['transport'], 2, ',', '.'); ?></td>
                                        <td class="text-right"><?= number_format((int) $row['transport_days'], 0, ',', '.'); ?></td>
                                        <td class="text-right"><?= number_format((int) $row['transport_trips'], 0, ',', '.'); ?></td>
                                        <td class="text-right">R$ <?= number_format($row['total'], 2, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th class="text-right">R$ <?= number_format($monthlyValeSummary['manual'], 2, ',', '.'); ?></th>
                                    <th class="text-right">R$ <?= number_format($monthlyValeSummary['transport'], 2, ',', '.'); ?></th>
                                    <th class="text-right"><?= number_format($monthlyValeSummary['transport_days'], 0, ',', '.'); ?></th>
                                    <th class="text-right"><?= number_format($monthlyValeSummary['transport_trips'], 0, ',', '.'); ?></th>
                                    <th class="text-right">R$ <?= number_format($monthlyValeSummary['total'], 2, ',', '.'); ?></th>
                                </tr>
                                </tfoot>
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
                                    <th class="text-right">Dias de VT</th>
                                    <th class="text-right">Passagens</th>
                                    <th class="text-right">Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($yearlyValeTotals as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['period']); ?></td>
                                        <td class="text-right">R$ <?= number_format($row['manual'], 2, ',', '.'); ?></td>
                                        <td class="text-right">R$ <?= number_format($row['transport'], 2, ',', '.'); ?></td>
                                        <td class="text-right"><?= number_format((int) $row['transport_days'], 0, ',', '.'); ?></td>
                                        <td class="text-right"><?= number_format((int) $row['transport_trips'], 0, ',', '.'); ?></td>
                                        <td class="text-right">R$ <?= number_format($row['total'], 2, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th class="text-right">R$ <?= number_format($yearlyValeSummary['manual'], 2, ',', '.'); ?></th>
                                    <th class="text-right">R$ <?= number_format($yearlyValeSummary['transport'], 2, ',', '.'); ?></th>
                                    <th class="text-right"><?= number_format($yearlyValeSummary['transport_days'], 0, ',', '.'); ?></th>
                                    <th class="text-right"><?= number_format($yearlyValeSummary['transport_trips'], 0, ',', '.'); ?></th>
                                    <th class="text-right">R$ <?= number_format($yearlyValeSummary['total'], 2, ',', '.'); ?></th>
                                </tr>
                                </tfoot>
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
                        <tfoot>
                        <tr>
                            <th>Total</th>
                            <th class="text-right">R$ <?= number_format($monthlyTotalsSummary['total'], 2, ',', '.'); ?></th>
                        </tr>
                        </tfoot>
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
                        <tfoot>
                        <tr>
                            <th>Total</th>
                            <th class="text-right">R$ <?= number_format($yearlyTotalsSummary['total'], 2, ',', '.'); ?></th>
                        </tr>
                        </tfoot>
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

    <?php if ($birthdayPopupAlerts !== [] || $vacationPopupAlerts !== []): ?>
        <?php
        $birthdayPopupData = array_map(
            static function (array $alert): array {
                $years = isset($alert['years']) && is_int($alert['years']) ? $alert['years'] : null;
                $hireDate = $alert['hire_date'] ?? null;

                return [
                    'name' => $alert['employee']->getName(),
                    'date' => $alert['date']->format('Y-m-d'),
                    'label' => $alert['date']->format('d/m'),
                    'years' => $years,
                    'years_label' => $years !== null
                        ? ($years === 1 ? '1 ano' : sprintf('%d anos', $years))
                        : null,
                    'hire_label' => $hireDate instanceof DateTimeImmutable ? $hireDate->format('d/m/Y') : null,
                ];
            },
            $birthdayPopupAlerts
        );

        $vacationPopupData = array_map(
            static function (array $alert): array {
                $notice = $alert['notice_from'];
                $available = $alert['available_from'];
                $baseStart = $alert['base_start'] ?? null;

                return [
                    'name' => $alert['employee']->getName(),
                    'available_from' => $available->format('Y-m-d'),
                    'available_label' => $available->format('d/m'),
                    'notice_from' => $notice->format('Y-m-d'),
                    'notice_label' => $notice->format('d/m'),
                    'status' => $alert['status'],
                    'base_origin' => (string) ($alert['base_origin'] ?? 'admission'),
                    'base_origin_label' => (string) ($alert['base_origin_label'] ?? ''),
                    'base_label' => $baseStart instanceof DateTimeImmutable ? $baseStart->format('d/m/Y') : null,
                ];
            },
            $vacationPopupAlerts
        );
        ?>
        <script id="dashboard-popups" type="application/json">
            <?= json_encode([
                'birthdays' => $birthdayPopupData,
                'vacations' => $vacationPopupData,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
        </script>
    <?php endif; ?>
</section>
