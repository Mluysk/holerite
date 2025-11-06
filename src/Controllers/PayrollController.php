<?php

declare(strict_types=1);

namespace Holerite\Controllers;

use Holerite\Models\User;
use Holerite\Repositories\AuditLogRepository;
use Holerite\Repositories\CompanyRepository;
use Holerite\Repositories\ContributionSettingsRepository;
use Holerite\Repositories\EmployeeRepository;
use Holerite\Repositories\PayrollRepository;
use Holerite\Repositories\UserRepository;
use Holerite\Services\PayrollService;
use RuntimeException;
use Throwable;

final class PayrollController extends Controller
{
    public function __construct(
        private EmployeeRepository $employeeRepository,
        private PayrollRepository $payrollRepository,
        private PayrollService $payrollService,
        private CompanyRepository $companyRepository,
        private UserRepository $userRepository,
        private AuditLogRepository $auditLogRepository,
        private ContributionSettingsRepository $contributionRepository,
    ) {
    }

    public function index(): void
    {
        $payrolls = $this->payrollRepository->all();

        $this->render('payrolls/index', [
            'title' => 'Holerites gerados',
            'payrolls' => $payrolls,
            'employees' => $this->employeeRepository->all(),
            'canDelete' => $this->isAdmin(),
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

        $defaults = [
            'employee_id' => (int) ($_GET['employee_id'] ?? 0),
            'type' => (string) ($_GET['type'] ?? 'regular'),
            'reference_month' => (string) ($_GET['reference_month'] ?? ''),
            'thirteenth_months' => (int) ($_GET['thirteenth_months'] ?? 12),
            'thirteenth_installment' => (string) ($_GET['thirteenth_installment'] ?? ''),
            'vacation_days' => (int) ($_GET['vacation_days'] ?? 30),
            'worked_days' => (int) ($_GET['worked_days'] ?? 30),
            'just_cause' => isset($_GET['just_cause']) && in_array(strtolower((string) $_GET['just_cause']), ['1', 'true', 'on', 'yes'], true),
            'vale_deduction' => (float) ($_GET['vale_deduction'] ?? 0),
            'advance_amount' => (float) ($_GET['advance_amount'] ?? 0),
            'remaining_amount' => (float) ($_GET['remaining_amount'] ?? 0),
            'advance_ratio' => (string) ($_GET['advance_ratio'] ?? ''),
            'advance_ratio_custom' => (string) ($_GET['advance_ratio_custom'] ?? ''),
            'use_transport' => isset($_GET['use_transport']) && in_array(strtolower((string) $_GET['use_transport']), ['1', 'true', 'on', 'yes'], true),
            'transport_days' => (int) ($_GET['transport_days'] ?? 0),
            'transport_trip_cost' => (float) ($_GET['transport_trip_cost'] ?? 6.0),
            'has_advance' => isset($_GET['has_advance'])
                ? in_array(strtolower((string) $_GET['has_advance']), ['1', 'true', 'on', 'yes'], true)
                : null,
        ];

        $this->render('payrolls/form', [
            'title' => 'Gerar holerite',
            'employees' => $employees,
            'company' => $this->companyRepository->get(),
            'defaults' => $defaults,
            'allowanceOptions' => $this->contributionRepository->get()->getManualAllowances(),
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
            'company' => $this->companyRepository->get(),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function delete(int $id, array $data): void
    {
        if (!$this->isAdmin()) {
            $this->flash('error', 'Apenas administradores podem excluir holerites.');
            $this->redirect('?action=list_payrolls');
            return;
        }

        $password = trim((string) ($data['password'] ?? ''));

        if (!$this->validateAdministratorPassword($password)) {
            $this->redirect('?action=list_payrolls');
            return;
        }

        $payroll = $this->payrollRepository->find($id);

        if ($payroll === null) {
            $this->flash('error', 'Holerite não encontrado.');
            $this->redirect('?action=list_payrolls');
            return;
        }

        try {
            $this->payrollRepository->delete($id);
        } catch (RuntimeException $exception) {
            $this->flash('error', $exception->getMessage());
            $this->redirect('?action=list_payrolls');
            return;
        }

        $employee = $this->employeeRepository->find($payroll->getEmployeeId());
        $employeeName = $employee?->getName() ?? ('Colaborador #' . $payroll->getEmployeeId());
        $userId = $this->currentUserId();

        $description = sprintf(
            'Holerite %d (%s) do colaborador %s excluído.',
            $payroll->getId(),
            $payroll->getReferenceMonth(),
            $employeeName,
        );

        $this->auditLogRepository->log(
            $userId,
            'delete_payroll',
            'payroll',
            $payroll->getId(),
            $description,
        );

        $this->flash('success', 'Holerite excluído com sucesso.');
        $this->redirect('?action=list_payrolls');
    }

    private function isAdmin(): bool
    {
        $user = $_SESSION['user'] ?? null;
        $role = is_array($user) ? ($user['role'] ?? null) : null;

        return $role === User::ROLE_ADMINISTRATOR;
    }

    private function currentUserId(): int
    {
        $user = $_SESSION['user'] ?? null;

        return is_array($user) ? (int) ($user['id'] ?? 0) : 0;
    }

    private function validateAdministratorPassword(string $password): bool
    {
        $user = $_SESSION['user'] ?? null;
        $userId = is_array($user) ? (int) ($user['id'] ?? 0) : 0;
        $role = is_array($user) ? ($user['role'] ?? null) : null;

        if ($role !== User::ROLE_ADMINISTRATOR || $userId <= 0) {
            $this->flash('error', 'Sessão inválida. Faça login novamente.');
            return false;
        }

        if ($password === '') {
            $this->flash('error', 'Informe sua senha de administrador para confirmar a exclusão.');
            return false;
        }

        $account = $this->userRepository->findById($userId);

        if ($account === null || !password_verify($password, $account->getPasswordHash())) {
            $this->flash('error', 'Senha de administrador incorreta.');
            return false;
        }

        return true;
    }
}
