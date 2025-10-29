<?php

declare(strict_types=1);

namespace Holerite\Controllers;

use DateTimeImmutable;
use Holerite\Models\Payroll;
use Holerite\Repositories\EmployeeRepository;
use Holerite\Repositories\PayrollRepository;

final class DashboardController extends Controller
{
    public function __construct(
        private EmployeeRepository $employeeRepository,
        private PayrollRepository $payrollRepository,
    ) {
    }

    public function index(): void
    {
        $employees = $this->employeeRepository->all();
        $payrolls = $this->payrollRepository->all();

        $currentMonth = new DateTimeImmutable('first day of this month');
        $currentMonthKey = $currentMonth->format('Y-m');
        $monthEnd = $currentMonth->modify('last day of this month');

        $totalNet = array_reduce($payrolls, fn (float $carry, $payroll): float => $carry + $payroll->getNetSalary(), 0.0);
        $lastPayrolls = array_slice($payrolls, 0, 5);

        $monthlyTotals = $this->aggregateTotals($payrolls, 'monthly');
        $yearlyTotals = $this->aggregateTotals($payrolls, 'yearly');

        $calendarEvents = [];
        $paidRegularPayrolls = [];

        foreach ($payrolls as $payroll) {
            $paymentDate = $payroll->getPaymentDate();

            $monthKey = $paymentDate->format('Y-m');

            if ($monthKey === $currentMonthKey) {
                $dateKey = $paymentDate->format('Y-m-d');
                if (!isset($calendarEvents[$dateKey])) {
                    $calendarEvents[$dateKey] = [];
                }
                $calendarEvents[$dateKey][] = $payroll;

                if ($payroll->getType() === 'regular') {
                    $paidRegularPayrolls[$payroll->getEmployeeId()] = $payroll;
                }
            }
        }

        $monthlyChart = $this->buildChartPayload($monthlyTotals);
        $yearlyChart = $this->buildChartPayload($yearlyTotals);

        $calendarWeeks = $this->buildCalendarWeeks($currentMonth, $calendarEvents);

        $employeesById = [];
        foreach ($employees as $employee) {
            $id = $employee->getId();
            if ($id !== null) {
                $employeesById[$id] = $employee;
            }
        }

        $paidMonthlyPayrolls = array_values($paidRegularPayrolls);
        usort($paidMonthlyPayrolls, function (Payroll $a, Payroll $b) use ($employeesById): int {
            $nameA = isset($employeesById[$a->getEmployeeId()])
                ? $employeesById[$a->getEmployeeId()]->getName()
                : '';
            $nameB = isset($employeesById[$b->getEmployeeId()])
                ? $employeesById[$b->getEmployeeId()]->getName()
                : '';

            return strcasecmp($nameA, $nameB);
        });

        $pendingMonthlyEmployees = [];
        foreach ($employees as $employee) {
            $id = $employee->getId();
            if ($id === null) {
                continue;
            }

            $terminationDate = $employee->getTerminationDate();
            if ($terminationDate !== null && $terminationDate < $currentMonth) {
                continue;
            }

            if ($employee->getHireDate() > $monthEnd) {
                continue;
            }

            if (!isset($paidRegularPayrolls[$id])) {
                $pendingMonthlyEmployees[] = $employee;
            }
        }

        usort($pendingMonthlyEmployees, static fn ($a, $b): int => strcasecmp($a->getName(), $b->getName()));

        $this->render('dashboard/index', [
            'title' => 'Dashboard',
            'totalEmployees' => count($employees),
            'totalPayrolls' => count($payrolls),
            'totalNet' => $totalNet,
            'lastPayrolls' => $lastPayrolls,
            'employees' => $employees,
            'monthlyTotals' => $monthlyTotals,
            'yearlyTotals' => $yearlyTotals,
            'monthlyChart' => $monthlyChart,
            'yearlyChart' => $yearlyChart,
            'calendarMonth' => $currentMonth,
            'calendarWeeks' => $calendarWeeks,
            'paidMonthlyPayrolls' => $paidMonthlyPayrolls,
            'pendingMonthlyEmployees' => $pendingMonthlyEmployees,
        ]);
    }

    public function report(string $scope): void
    {
        $normalizedScope = $scope === 'yearly' ? 'yearly' : 'monthly';

        $payrolls = $this->payrollRepository->all();
        $totals = $this->aggregateTotals($payrolls, $normalizedScope);

        $title = $normalizedScope === 'yearly'
            ? 'Relatório anual de pagamentos'
            : 'Relatório mensal de pagamentos';

        $description = $normalizedScope === 'yearly'
            ? 'Resumo anual dos pagamentos líquidos efetuados.'
            : 'Resumo mensal dos pagamentos líquidos efetuados.';

        $this->render('dashboard/report', [
            'title' => $title,
            'scope' => $normalizedScope,
            'totals' => $totals,
            'description' => $description,
        ]);
    }

    /**
     * @param array<int, array{period: string, total: float}> $totals
     * @return array{labels: array<int, string>, values: array<int, float>, percentages: array<int, float>, total: float}
     */
    private function buildChartPayload(array $totals): array
    {
        if ($totals === []) {
            return [
                'labels' => [],
                'values' => [],
                'percentages' => [],
                'total' => 0.0,
            ];
        }

        $labels = array_column($totals, 'period');
        $values = array_map(static fn (array $row): float => (float) $row['total'], $totals);
        $total = array_sum($values);

        $percentages = $total > 0.0
            ? array_map(function (float $value) use ($total): float {
                return round(($value / $total) * 100, 1);
            }, $values)
            : array_fill(0, count($values), 0.0);

        return [
            'labels' => $labels,
            'values' => $values,
            'percentages' => $percentages,
            'total' => $total,
        ];
    }

    /**
     * @param DateTimeImmutable $month
     * @param array<string, array<int, Payroll>> $events
     * @return array<int, array<int, array{date: DateTimeImmutable|null, payrolls: array<int, Payroll>}>>
     */
    private function buildCalendarWeeks(DateTimeImmutable $month, array $events): array
    {
        $weeks = [];
        $week = [];

        $firstWeekday = (int) $month->format('N');
        for ($i = 1; $i < $firstWeekday; $i++) {
            $week[] = ['date' => null, 'payrolls' => []];
        }

        $daysInMonth = (int) $month->format('t');
        $year = (int) $month->format('Y');
        $monthNumber = (int) $month->format('m');

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $month->setDate($year, $monthNumber, $day);
            $dateKey = $date->format('Y-m-d');

            $week[] = [
                'date' => $date,
                'payrolls' => $events[$dateKey] ?? [],
            ];

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
        }

        if ($week !== []) {
            while (count($week) < 7) {
                $week[] = ['date' => null, 'payrolls' => []];
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    /**
     * @param array<int, Payroll> $payrolls
     * @return array<int, array{period: string, total: float}>
     */
    private function aggregateTotals(array $payrolls, string $scope): array
    {
        $normalizedScope = $scope === 'yearly' ? 'yearly' : 'monthly';

        $totals = [];

        foreach ($payrolls as $payroll) {
            $paymentDate = $payroll->getPaymentDate();

            if ($normalizedScope === 'yearly') {
                $key = $paymentDate->format('Y');
                $label = $key;
            } else {
                $key = $paymentDate->format('Y-m');
                $label = $paymentDate->format('m/Y');
            }

            if (!isset($totals[$key])) {
                $totals[$key] = [
                    'period' => $label,
                    'total' => 0.0,
                ];
            }

            $totals[$key]['total'] += $payroll->getNetSalary();
        }

        if ($totals === []) {
            return [];
        }

        uksort($totals, static fn (string $a, string $b): int => strcmp($b, $a));

        return array_values($totals);
    }
}
