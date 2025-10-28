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

        $type = $this->normalizeType((string) ($data['type'] ?? 'regular'));
        $justCause = $this->normalizeBoolean($data['just_cause'] ?? false);

        $vacationDays = null;
        $workedDays = null;
        $thirteenthMonths = null;
        $baseSalaryAmount = $employee->getBaseSalary();

        $automaticAllowances = [];
        $automaticDeductions = [];

        if ($type === 'vacation') {
            $vacationDays = $this->sanitizeInt($data['vacation_days'] ?? 30, 1, 30, 30);
            $baseSalaryAmount = $this->roundMoney($employee->getBaseSalary() * $vacationDays / 30);
            $vacationBonus = $this->roundMoney($baseSalaryAmount / 3);

            if ($vacationBonus > 0) {
                $automaticAllowances[] = new PayrollItem(null, null, '1/3 Constitucional de Férias', $vacationBonus, 'allowance');
            }
        }

        if ($type === 'termination') {
            $workedDays = $this->sanitizeInt($data['worked_days'] ?? 30, 0, 30, 30);
            $baseSalaryAmount = $this->roundMoney($employee->getBaseSalary() * $workedDays / 30);
            $thirteenthMonths = $this->sanitizeInt($data['thirteenth_months'] ?? 12, 0, 12, 12);

            if ($justCause) {
                $thirteenthMonths = 0;
            }

            if (!$justCause && $thirteenthMonths > 0) {
                $thirteenthAmount = $this->roundMoney($employee->getBaseSalary() * $thirteenthMonths / 12);
                if ($thirteenthAmount > 0) {
                    $automaticAllowances[] = new PayrollItem(null, null, '13º salário proporcional', $thirteenthAmount, 'allowance');
                }
            }
        }

        $manualAllowances = $this->createItems($data['allowance_description'] ?? [], $data['allowance_amount'] ?? [], 'allowance');
        $manualDeductions = $this->createItems($data['deduction_description'] ?? [], $data['deduction_amount'] ?? [], 'deduction');

        $allAllowances = array_merge($automaticAllowances, $manualAllowances);
        $allDeductions = array_merge($automaticDeductions, $manualDeductions);

        $totalAllowances = array_reduce($allAllowances, fn (float $carry, PayrollItem $item): float => $carry + $item->getAmount(), 0.0);
        $totalDeductions = array_reduce($allDeductions, fn (float $carry, PayrollItem $item): float => $carry + $item->getAmount(), 0.0);
        $netSalary = $this->roundMoney($baseSalaryAmount + $totalAllowances - $totalDeductions);

        $payroll = new Payroll(
            null,
            $employeeId,
            $referenceMonth,
            $type,
            $baseSalaryAmount,
            $totalAllowances,
            $totalDeductions,
            $netSalary,
            $paymentDate,
            $justCause,
            $vacationDays,
            $workedDays,
            $thirteenthMonths,
            $notes,
            array_merge($allAllowances, $allDeductions)
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
            $amount = $this->roundMoney($amount);

            if ($description === '' || $amount <= 0) {
                continue;
            }

            $items[] = new PayrollItem(null, null, $description, $amount, $type);
        }

        return $items;
    }

    private function normalizeType(string $type): string
    {
        $allowed = ['regular', 'vacation', 'termination'];
        return in_array($type, $allowed, true) ? $type : 'regular';
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
        }

        return (bool) $value;
    }

    private function sanitizeInt(mixed $value, int $min, int $max, int $default): int
    {
        if (!is_numeric($value)) {
            return $default;
        }

        $value = (int) $value;

        if ($value < $min) {
            return $min;
        }

        if ($value > $max) {
            return $max;
        }

        return $value;
    }

    private function roundMoney(float $value): float
    {
        return round($value, 2);
    }
}
