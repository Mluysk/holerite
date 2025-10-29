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
        $thirteenthInstallment = null;
        $baseSalaryAmount = $employee->getBaseSalary();

        $automaticAllowances = [];
        $automaticDeductions = [];
        $thirteenthAccrual = 0.0;
        $thirteenthTotalGross = 0.0;

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

        if ($type === 'thirteenth') {
            $thirteenthMonths = $this->sanitizeInt($data['thirteenth_months'] ?? 0, 1, 12, 0);

            if ($thirteenthMonths <= 0) {
                throw new RuntimeException('Informe a quantidade de meses a considerar para o 13º.');
            }

            $thirteenthInstallment = $this->normalizeThirteenthInstallment((string) ($data['thirteenth_installment'] ?? 'second'));
            $thirteenthTotalGross = $this->roundMoney($employee->getBaseSalary() * $thirteenthMonths / 12);
            $baseSalaryAmount = $this->roundMoney($thirteenthTotalGross / 2);
            $thirteenthAccrual = $thirteenthTotalGross;
        }

        if ($type === 'regular') {
            $thirteenthAccrual = $this->roundMoney($employee->getBaseSalary() / 12);
        }

        $manualAllowances = $this->createItems($data['allowance_description'] ?? [], $data['allowance_amount'] ?? [], 'allowance');
        $manualDeductions = $this->createItems($data['deduction_description'] ?? [], $data['deduction_amount'] ?? [], 'deduction');

        $allAllowances = array_merge($automaticAllowances, $manualAllowances);
        $allowancesTotal = array_reduce($allAllowances, fn (float $carry, PayrollItem $item): float => $carry + $item->getAmount(), 0.0);

        $defaultContributionBase = $this->roundMoney($baseSalaryAmount + $allowancesTotal);
        $inssBase = $defaultContributionBase;
        $fgtsBase = $defaultContributionBase;

        $inssAmount = 0.0;
        $irrfBase = 0.0;
        $irrfAmount = 0.0;

        if ($type === 'thirteenth') {
            $withholdThirteenthTaxes = $thirteenthInstallment === 'second';
            if ($withholdThirteenthTaxes) {
                $inssBase = $this->roundMoney($thirteenthTotalGross);
                $fgtsBase = $inssBase;
                $inssAmount = $this->calculateInss($inssBase);
                $irrfBase = $this->roundMoney(max(0, $inssBase - $inssAmount));
                $irrfAmount = $this->calculateIrrf($irrfBase);
            } else {
                $inssBase = $defaultContributionBase;
                $fgtsBase = 0.0;
            }
        } else {
            $inssAmount = $this->calculateInss($inssBase);
            $irrfBase = $this->roundMoney(max(0, $inssBase - $inssAmount));
            $irrfAmount = $this->calculateIrrf($irrfBase);

            if ($inssAmount > 0) {
                $automaticDeductions[] = new PayrollItem(null, null, 'INSS', $inssAmount, 'deduction');
            }

            if ($irrfAmount > 0) {
                $automaticDeductions[] = new PayrollItem(null, null, 'IRRF', $irrfAmount, 'deduction');
            }
        }

        if ($type === 'thirteenth' && $thirteenthInstallment === 'second') {
            if ($inssAmount > 0) {
                $automaticDeductions[] = new PayrollItem(null, null, 'INSS 13º', $inssAmount, 'deduction');
            }

            if ($irrfAmount > 0) {
                $automaticDeductions[] = new PayrollItem(null, null, 'IRRF 13º', $irrfAmount, 'deduction');
            }
        }

        $fgtsAmount = $this->roundMoney($fgtsBase * 0.08);

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
            $thirteenthInstallment,
            $thirteenthAccrual,
            $inssBase,
            $inssAmount,
            $irrfBase,
            $irrfAmount,
            $fgtsBase,
            $fgtsAmount,
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
        $allowed = ['regular', 'vacation', 'termination', 'thirteenth'];
        return in_array($type, $allowed, true) ? $type : 'regular';
    }

    private function normalizeThirteenthInstallment(string $installment): string
    {
        $installment = strtolower(trim($installment));

        return in_array($installment, ['first', 'second'], true) ? $installment : 'second';
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

    private function calculateInss(float $base): float
    {
        if ($base <= 0) {
            return 0.0;
        }

        $ranges = [
            [1320.00, 0.075],
            [2571.29, 0.09],
            [3856.94, 0.12],
            [7507.49, 0.14],
        ];

        $remaining = $base;
        $contribution = 0.0;
        $previousLimit = 0.0;

        foreach ($ranges as [$limit, $rate]) {
            if ($remaining <= 0) {
                break;
            }

            $rangeAmount = min($remaining, $limit - $previousLimit);
            if ($rangeAmount > 0) {
                $contribution += $rangeAmount * $rate;
                $remaining -= $rangeAmount;
            }

            $previousLimit = $limit;
        }

        if ($remaining > 0) {
            $contribution += $remaining * 0.14;
        }

        return $this->roundMoney($contribution);
    }

    private function calculateIrrf(float $base): float
    {
        if ($base <= 0) {
            return 0.0;
        }

        $bands = [
            [1903.98, 0.0, 0.0],
            [2826.65, 0.075, 142.80],
            [3751.05, 0.15, 354.80],
            [4664.68, 0.225, 636.13],
        ];

        foreach ($bands as [$limit, $rate, $deduction]) {
            if ($base <= $limit) {
                return $this->roundMoney(max(0, $base * $rate - $deduction));
            }
        }

        return $this->roundMoney(max(0, $base * 0.275 - 869.36));
    }
}
