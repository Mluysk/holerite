<?php

declare(strict_types=1);

namespace Holerite\Services;

use DateTimeImmutable;
use Holerite\Models\Payroll;
use Holerite\Models\PayrollItem;
use Holerite\Repositories\EmployeeRepository;
use Holerite\Repositories\PayrollRepository;
use RuntimeException;

final class PayrollService
{
    public function __construct(
        private EmployeeRepository $employeeRepository,
        private PayrollRepository $payrollRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createPayroll(int $employeeId, array $data): Payroll
    {
        $employee = $this->employeeRepository->find($employeeId);

        if ($employee === null) {
            throw new RuntimeException('Colaborador não encontrado.');
        }

        $referenceMonth = trim((string) ($data['reference_month'] ?? ''));
        if ($referenceMonth === '') {
            throw new RuntimeException('Informe o mês de referência.');
        }

        $paymentDate = new DateTimeImmutable((string) ($data['payment_date'] ?? date('Y-m-d')));
        $notes = trim((string) ($data['notes'] ?? ''));

        $allowances = $this->createItems($data['allowance_description'] ?? [], $data['allowance_amount'] ?? [], 'allowance');
        $deductions = $this->createItems($data['deduction_description'] ?? [], $data['deduction_amount'] ?? [], 'deduction');

        $totalAllowances = array_reduce($allowances, fn (float $carry, PayrollItem $item): float => $carry + $item->getAmount(), 0.0);
        $totalDeductions = array_reduce($deductions, fn (float $carry, PayrollItem $item): float => $carry + $item->getAmount(), 0.0);
        $netSalary = $employee->getBaseSalary() + $totalAllowances - $totalDeductions;

        $payroll = new Payroll(
            null,
            $employeeId,
            $referenceMonth,
            $employee->getBaseSalary(),
            $totalAllowances,
            $totalDeductions,
            $netSalary,
            $paymentDate,
            $notes,
            array_merge($allowances, $deductions)
        );

        return $this->payrollRepository->create($payroll);
    }

    /**
     * @param array<int, string> $descriptions
     * @param array<int, string|float> $amounts
     * @return PayrollItem[]
     */
    private function createItems(array $descriptions, array $amounts, string $type): array
    {
        $items = [];

        foreach ($descriptions as $index => $description) {
            $description = trim((string) $description);
            $amount = isset($amounts[$index]) ? (float) $amounts[$index] : 0.0;

            if ($description === '' || $amount <= 0) {
                continue;
            }

            $items[] = new PayrollItem(null, null, $description, $amount, $type);
        }

        return $items;
    }
}
