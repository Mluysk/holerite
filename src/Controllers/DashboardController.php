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

        $this->render('dashboard/index', [
            'title' => 'Dashboard',
            'totalEmployees' => count($employees),
            'totalPayrolls' => count($payrolls),
            'totalNet' => $totalNet,
            'lastPayrolls' => $lastPayrolls,
            'employees' => $employees,
        ]);
    }
}
