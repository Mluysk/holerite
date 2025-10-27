<?php

declare(strict_types=1);

namespace Holerite\Controllers;

use Holerite\Repositories\EmployeeRepository;
use Holerite\Repositories\PayrollRepository;
use Holerite\Services\PayrollService;
use RuntimeException;
use Throwable;

final class PayrollController extends Controller
{
    public function __construct(
        private EmployeeRepository $employeeRepository,
        private PayrollRepository $payrollRepository,
        private PayrollService $payrollService,
    ) {
    }

    public function index(): void
    {
        $payrolls = $this->payrollRepository->all();

        $this->render('payrolls/index', [
            'title' => 'Holerites gerados',
            'payrolls' => $payrolls,
            'employees' => $this->employeeRepository->all(),
        ]);
    }

    public function create(): void
    {
        $employees = $this->employeeRepository->all();

        if ($employees === []) {
            $this->flash('warning', 'Cadastre um colaborador antes de gerar holerites.');
            $this->redirect('?action=list_employees');
            return;
        }

        $this->render('payrolls/form', [
            'title' => 'Gerar holerite',
            'employees' => $employees,
        ]);
    }

    public function store(array $data): void
    {
        $employeeId = (int) ($data['employee_id'] ?? 0);

        try {
            $payroll = $this->payrollService->createPayroll($employeeId, $data);
            $this->flash('success', 'Holerite gerado com sucesso.');
            $this->redirect('?action=show_payroll&id=' . $payroll->getId());
            return;
        } catch (RuntimeException $exception) {
            $this->flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            $this->flash('error', 'Não foi possível gerar o holerite: ' . $exception->getMessage());
        }

        $this->redirect('?action=create_payroll');
    }

    public function show(int $id): void
    {
        $payroll = $this->payrollRepository->find($id);

        if ($payroll === null) {
            $this->flash('error', 'Holerite não encontrado.');
            $this->redirect('?action=list_payrolls');
            return;
        }

        $employee = $this->employeeRepository->find($payroll->getEmployeeId());

        $this->render('payrolls/show', [
            'title' => 'Holerite - ' . ($employee?->getName() ?? 'Colaborador'),
            'payroll' => $payroll,
            'employee' => $employee,
        ]);
    }
}
