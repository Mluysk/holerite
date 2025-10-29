<?php

declare(strict_types=1);

namespace Holerite\Controllers;

use DateTimeImmutable;
use Holerite\Models\Employee;
use Holerite\Models\Payroll;
use Holerite\Repositories\EmployeeRepository;
use Holerite\Repositories\PayrollRepository;
use Holerite\Services\EmployeeBenefitService;
use Throwable;

final class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeRepository $repository,
        private PayrollRepository $payrollRepository,
        private EmployeeBenefitService $benefitService,
    ) {
    }

    public function index(): void
    {
        $employees = $this->repository->all();

        $this->render('employees/index', [
            'title' => 'Colaboradores',
            'employees' => $employees,
        ]);
    }

    public function report(): void
    {
        $today = new DateTimeImmutable('today');
        $issuedAt = new DateTimeImmutable('now');
        $employees = $this->repository->all();

        $entries = [];
        $totalBaseSalary = 0.0;
        $totalNetPaid = 0.0;
        $totalAllowances = 0.0;
        $totalDeductions = 0.0;
        $totalPayrolls = 0;
        $activeEmployees = 0;
        $inactiveEmployees = 0;
        $employeesWithPayrolls = 0;

        foreach ($employees as $employee) {
            $totalBaseSalary += $employee->getBaseSalary();

            if ($this->isActive($employee, $today)) {
                $activeEmployees++;
            } else {
                $inactiveEmployees++;
            }

            $payrolls = $employee->getId() !== null
                ? $this->payrollRepository->findByEmployee($employee->getId())
                : [];

            $payrollSummary = $this->summarizePayrolls($payrolls);
            $benefits = $this->benefitService->summarize($employee, $payrolls, $today);

            $totalNetPaid += $payrollSummary['total_net'];
            $totalAllowances += $payrollSummary['total_allowances'];
            $totalDeductions += $payrollSummary['total_deductions'];
            $totalPayrolls += $payrollSummary['count'];

            if ($payrollSummary['count'] > 0) {
                $employeesWithPayrolls++;
            }

            $entries[] = [
                'employee' => $employee,
                'tenure' => $this->formatTenure($employee, $today),
                'status' => $this->resolveEmploymentStatus($employee, $today),
                'payrollSummary' => $payrollSummary,
                'benefits' => $benefits,
            ];
        }

        $employeeCount = count($employees);

        $summary = [
            'totalEmployees' => $employeeCount,
            'activeEmployees' => $activeEmployees,
            'inactiveEmployees' => $inactiveEmployees,
            'employeesWithPayrolls' => $employeesWithPayrolls,
            'averageSalary' => $employeeCount > 0 ? $totalBaseSalary / $employeeCount : 0.0,
            'totalNetPaid' => $totalNetPaid,
            'totalAllowances' => $totalAllowances,
            'totalDeductions' => $totalDeductions,
            'totalPayrolls' => $totalPayrolls,
        ];

        $this->render('employees/report', [
            'title' => 'Relatório de colaboradores',
            'issuedAt' => $issuedAt,
            'referenceDate' => $today,
            'entries' => $entries,
            'summary' => $summary,
        ]);
    }

    public function create(): void
    {
        $this->render('employees/form', [
            'title' => 'Novo colaborador',
            'action' => 'store_employee',
            'employee' => null,
        ]);
    }

    public function store(array $data): void
    {
        try {
            $employee = new Employee(
                null,
                trim((string) ($data['name'] ?? '')),
                trim((string) ($data['email'] ?? '')),
                (float) ($data['base_salary'] ?? 0),
                trim((string) ($data['department'] ?? '')),
                trim((string) ($data['position'] ?? '')),
                new DateTimeImmutable((string) ($data['hire_date'] ?? date('Y-m-d'))),
                $this->buildTerminationDate($data['termination_date'] ?? null)
            );

            $this->repository->create($employee);
            $this->flash('success', 'Colaborador cadastrado com sucesso.');
        } catch (Throwable $exception) {
            $this->flash('error', 'Não foi possível cadastrar o colaborador: ' . $exception->getMessage());
        }

        $this->redirect('?action=list_employees');
    }

    public function edit(int $id): void
    {
        $employee = $this->repository->find($id);

        if ($employee === null) {
            $this->flash('error', 'Colaborador não encontrado.');
            $this->redirect('?action=list_employees');
            return;
        }

        $this->render('employees/form', [
            'title' => 'Editar colaborador',
            'action' => 'update_employee&id=' . $employee->getId(),
            'employee' => $employee,
        ]);
    }

    public function update(int $id, array $data): void
    {
        $employee = $this->repository->find($id);

        if ($employee === null) {
            $this->flash('error', 'Colaborador não encontrado.');
            $this->redirect('?action=list_employees');
            return;
        }

        try {
            $employee->setName(trim((string) ($data['name'] ?? '')));
            $employee->setEmail(trim((string) ($data['email'] ?? '')));
            $employee->setBaseSalary((float) ($data['base_salary'] ?? 0));
            $employee->setDepartment(trim((string) ($data['department'] ?? '')));
            $employee->setPosition(trim((string) ($data['position'] ?? '')));
            $employee->setHireDate(new DateTimeImmutable((string) ($data['hire_date'] ?? date('Y-m-d'))));
            $employee->setTerminationDate($this->buildTerminationDate($data['termination_date'] ?? null));

            $this->repository->update($employee);
            $this->flash('success', 'Colaborador atualizado com sucesso.');
        } catch (Throwable $exception) {
            $this->flash('error', 'Não foi possível atualizar o colaborador: ' . $exception->getMessage());
        }

        $this->redirect('?action=list_employees');
    }

    public function delete(int $id): void
    {
        try {
            $this->repository->delete($id);
            $this->flash('success', 'Colaborador removido com sucesso.');
        } catch (Throwable $exception) {
            $this->flash('error', 'Não foi possível remover o colaborador: ' . $exception->getMessage());
        }

        $this->redirect('?action=list_employees');
    }

    public function show(int $id): void
    {
        $employee = $this->repository->find($id);

        if ($employee === null) {
            $this->flash('error', 'Colaborador não encontrado.');
            $this->redirect('?action=list_employees');
            return;
        }

        $payrolls = $this->payrollRepository->findByEmployee($id);
        $benefits = $this->benefitService->summarize($employee, $payrolls);

        $this->render('employees/show', [
            'title' => 'Colaborador · ' . $employee->getName(),
            'employee' => $employee,
            'payrolls' => $payrolls,
            'benefits' => $benefits,
        ]);
    }

    private function buildTerminationDate(mixed $value): ?DateTimeImmutable
    {
        $date = trim((string) ($value ?? ''));

        if ($date === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($date);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param Payroll[] $payrolls
     * @return array{
     *     count: int,
     *     total_net: float,
     *     total_gross: float,
     *     total_allowances: float,
     *     total_deductions: float,
     *     earliest_payment: ?DateTimeImmutable,
     *     latest_payment: ?DateTimeImmutable,
     *     types: array<int, array{key: string, label: string, count: int, net_total: float}>
     * }
     */
    private function summarizePayrolls(array $payrolls): array
    {
        $count = count($payrolls);
        $totalNet = 0.0;
        $totalGross = 0.0;
        $totalAllowances = 0.0;
        $totalDeductions = 0.0;
        $earliest = null;
        $latest = null;

        $types = [];
        $typeLabels = [
            'regular' => 'Mensal',
            'vacation' => 'Férias',
            'thirteenth' => '13º salário',
            'termination' => 'Rescisão',
        ];

        foreach ($payrolls as $payroll) {
            $gross = $payroll->getBaseSalary() + $payroll->getTotalAllowances();
            $totalGross += $gross;
            $totalAllowances += $payroll->getTotalAllowances();
            $totalDeductions += $payroll->getTotalDeductions();
            $totalNet += $payroll->getNetSalary();

            $typeKey = $payroll->getType();
            if (!isset($types[$typeKey])) {
                $types[$typeKey] = [
                    'key' => $typeKey,
                    'label' => $typeLabels[$typeKey] ?? ucfirst($typeKey),
                    'count' => 0,
                    'net_total' => 0.0,
                ];
            }

            $types[$typeKey]['count']++;
            $types[$typeKey]['net_total'] += $payroll->getNetSalary();

            $paymentDate = $payroll->getPaymentDate();
            if ($latest === null || $paymentDate > $latest) {
                $latest = $paymentDate;
            }
            if ($earliest === null || $paymentDate < $earliest) {
                $earliest = $paymentDate;
            }
        }

        usort($types, static function (array $a, array $b): int {
            $countComparison = $b['count'] <=> $a['count'];
            if ($countComparison !== 0) {
                return $countComparison;
            }

            return $b['net_total'] <=> $a['net_total'];
        });

        return [
            'count' => $count,
            'total_net' => round($totalNet, 2),
            'total_gross' => round($totalGross, 2),
            'total_allowances' => round($totalAllowances, 2),
            'total_deductions' => round($totalDeductions, 2),
            'earliest_payment' => $earliest,
            'latest_payment' => $latest,
            'types' => array_values($types),
        ];
    }

    private function formatTenure(Employee $employee, DateTimeImmutable $asOf): string
    {
        $end = $employee->getTerminationDate();
        if ($end === null || $end > $asOf) {
            $end = $asOf;
        }

        $start = $employee->getHireDate();
        if ($end < $start) {
            return '—';
        }

        $interval = $start->diff($end);

        $parts = [];

        if ($interval->y > 0) {
            $parts[] = sprintf('%d ano%s', $interval->y, $interval->y > 1 ? 's' : '');
        }

        if ($interval->m > 0) {
            $parts[] = sprintf('%d mês%s', $interval->m, $interval->m > 1 ? 'es' : '');
        }

        if ($interval->y === 0 && $interval->m === 0) {
            $days = max(0, $interval->d);
            $parts[] = sprintf('%d dia%s', $days, $days !== 1 ? 's' : '');
        }

        if ($parts === []) {
            return 'Menos de um mês';
        }

        return implode(' e ', $parts);
    }

    private function resolveEmploymentStatus(Employee $employee, DateTimeImmutable $asOf): string
    {
        $termination = $employee->getTerminationDate();

        if ($termination === null) {
            return 'Ativo';
        }

        if ($termination > $asOf) {
            return 'Ativo até ' . $termination->format('d/m/Y');
        }

        return 'Desligado em ' . $termination->format('d/m/Y');
    }

    private function isActive(Employee $employee, DateTimeImmutable $asOf): bool
    {
        $termination = $employee->getTerminationDate();

        return $termination === null || $termination > $asOf;
    }
}
