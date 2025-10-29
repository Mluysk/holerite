<?php

declare(strict_types=1);

namespace Holerite\Controllers;

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

        $totalNet = array_reduce($payrolls, fn (float $carry, $payroll): float => $carry + $payroll->getNetSalary(), 0.0);
        $lastPayrolls = array_slice($payrolls, 0, 5);

        $monthlyTotals = [];
        $yearlyTotals = [];

        foreach ($payrolls as $payroll) {
            $paymentDate = $payroll->getPaymentDate();

            $monthKey = $paymentDate->format('Y-m');
            if (!isset($monthlyTotals[$monthKey])) {
                $monthlyTotals[$monthKey] = [
                    'period' => $paymentDate->format('m/Y'),
                    'total' => 0.0,
                ];
            }
            $monthlyTotals[$monthKey]['total'] += $payroll->getNetSalary();

            $yearKey = $paymentDate->format('Y');
            if (!isset($yearlyTotals[$yearKey])) {
                $yearlyTotals[$yearKey] = [
                    'period' => $yearKey,
                    'total' => 0.0,
                ];
            }
            $yearlyTotals[$yearKey]['total'] += $payroll->getNetSalary();
        }

        uksort($monthlyTotals, static fn (string $a, string $b): int => strcmp($b, $a));
        uksort($yearlyTotals, static fn (string $a, string $b): int => strcmp($b, $a));

        $monthlyTotals = array_values($monthlyTotals);
        $yearlyTotals = array_values($yearlyTotals);

        $monthlyChart = $this->buildChartPayload($monthlyTotals);
        $yearlyChart = $this->buildChartPayload($yearlyTotals);

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
}
