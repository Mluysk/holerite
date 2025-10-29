<?php

declare(strict_types=1);

namespace Holerite\Controllers;

use DateTimeImmutable;
use Holerite\Models\Employee;
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
}
