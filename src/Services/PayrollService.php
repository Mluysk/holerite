<?php

declare(strict_types=1);

namespace Holerite\Services;

use DateTimeImmutable;
use Holerite\Models\Payroll;
use Holerite\Models\PayrollItem;
use Holerite\Repositories\ContributionSettingsRepository;
use Holerite\Repositories\EmployeeRepository;
use Holerite\Repositories\PayrollRepository;
use RuntimeException;

final class PayrollService
{
    public function __construct(
        private EmployeeRepository $employeeRepository,
        private PayrollRepository $payrollRepository,
        private ContributionSettingsRepository $contributionRepository,
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

        $paymentDateInput = (string) ($data['payment_date'] ?? date('Y-m-d'));
        $paymentDate = new DateTimeImmutable($paymentDateInput);
        $notes = trim((string) ($data['notes'] ?? ''));

        $type = $this->normalizeType((string) ($data['type'] ?? 'regular'));
        $justCause = $this->normalizeBoolean($data['just_cause'] ?? false);
        $useTransport = $this->normalizeBoolean($data['use_transport'] ?? false);
        $transportDays = 0;
        $transportTripCost = 0.0;

        $vacationDays = null;
        $workedDays = null;
        $thirteenthMonths = null;
        $thirteenthInstallment = null;
        $baseSalaryAmount = $employee->getBaseSalary();

        $automaticAllowances = [];
        $automaticDeductions = [];
        $thirteenthAccrual = 0.0;
        $thirteenthTotalGross = 0.0;
        $contributions = $this->contributionRepository->get();
        $fgtsRate = $contributions->getFgtsRate();
        $inssBrackets = $contributions->getInssBrackets();
        $irrfBrackets = $contributions->getIrrfBrackets();

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
            $paymentDate = $this->resolveThirteenthPaymentDate($referenceMonth, $thirteenthInstallment);
        }

        if ($type === 'regular') {
            $thirteenthAccrual = $this->roundMoney($employee->getBaseSalary() / 12);
        }

        $manualAllowances = $this->createItems($data['allowance_description'] ?? [], $data['allowance_amount'] ?? [], 'allowance');
        $manualDeductions = $this->createItems($data['deduction_description'] ?? [], $data['deduction_amount'] ?? [], 'deduction');
        $valeDeduction = $this->roundMoney((float) ($data['vale_deduction'] ?? 0));
        if ($valeDeduction < 0) {
            $valeDeduction = 0.0;
        }

        $transportDeduction = 0.0;
        $transportTotalCost = 0.0;
        $transportLegalLimit = 0.0;
        if ($useTransport) {
            $transportDays = $this->sanitizeInt($data['transport_days'] ?? 0, 0, 31, 0);
            $transportTripCost = $this->roundMoney((float) ($data['transport_trip_cost'] ?? 6.0));
            if ($transportTripCost <= 0) {
                $transportTripCost = 6.0;
            }

            $transportTrips = $transportDays * 2;
            $transportTotalCost = $this->roundMoney($transportTrips * $transportTripCost);
            $transportLegalLimit = $this->roundMoney($baseSalaryAmount * 0.06);
            $transportDeduction = $this->roundMoney(min($transportTotalCost, $transportLegalLimit));

            if ($transportTotalCost > 0) {
                $descriptionParts = [sprintf('Vale-transporte (%d dias · %d passagens)', $transportDays, $transportTrips)];
                $automaticDeductions[] = new PayrollItem(
                    null,
                    null,
                    implode(' · ', $descriptionParts),
                    $transportDeduction,
                    'deduction'
                );
            }
        } else {
            $transportDays = 0;
            $transportTripCost = 0.0;
        }

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
                $inssAmount = $this->calculateInss($inssBase, $inssBrackets);
                $irrfBase = $this->roundMoney(max(0, $inssBase - $inssAmount));
                $irrfAmount = $this->calculateIrrf($irrfBase, $irrfBrackets);
            } else {
                $inssBase = $defaultContributionBase;
                $fgtsBase = 0.0;
            }
        } else {
            $inssAmount = $this->calculateInss($inssBase, $inssBrackets);
            $irrfBase = $this->roundMoney(max(0, $inssBase - $inssAmount));
            $irrfAmount = $this->calculateIrrf($irrfBase, $irrfBrackets);

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

        if ($valeDeduction > 0) {
            $automaticDeductions[] = new PayrollItem(null, null, 'Desconto de vale (informado)', $valeDeduction, 'deduction');
        }

        $fgtsAmount = $this->roundMoney($fgtsBase * $fgtsRate);

        $allDeductions = array_merge($automaticDeductions, $manualDeductions);

        $totalAllowances = array_reduce($allAllowances, fn (float $carry, PayrollItem $item): float => $carry + $item->getAmount(), 0.0);
        $totalDeductions = array_reduce($allDeductions, fn (float $carry, PayrollItem $item): float => $carry + $item->getAmount(), 0.0);
        $netSalary = $this->roundMoney($baseSalaryAmount + $totalAllowances - $totalDeductions);

        [$advanceAmount, $remainingAmount] = $this->resolveInstallments($data, $netSalary);

        $payroll = new Payroll(
            null,
            $employeeId,
            $referenceMonth,
            $type,
            $baseSalaryAmount,
            $totalAllowances,
            $totalDeductions,
            $netSalary,
            $advanceAmount,
            $remainingAmount,
            $valeDeduction,
            $useTransport,
            $transportDeduction,
            $transportDays,
            $transportTripCost,
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

    /**
     * @param array<string, mixed> $data
     * @return array{0: float, 1: float}
     */
    private function resolveInstallments(array $data, float $netSalary): array
    {
        if ($netSalary <= 0.0) {
            return [0.0, 0.0];
        }

        $type = isset($data['type']) ? $this->normalizeType((string) $data['type']) : 'regular';
        $hasAdvance = array_key_exists('has_advance', $data)
            ? $this->normalizeBoolean($data['has_advance'])
            : ($type === 'regular');
        $shouldSplit = $hasAdvance && $type === 'regular';

        $advanceAmount = $this->roundMoney(max(0.0, (float) ($data['advance_amount'] ?? 0)));
        $remainingAmount = $this->roundMoney(max(0.0, (float) ($data['remaining_amount'] ?? 0)));
        $advanceRatio = null;

        if (isset($data['advance_ratio'])) {
            $rawRatio = (string) $data['advance_ratio'];
            $parsedRatio = (float) $rawRatio;

            if ($parsedRatio > 0.0 && $parsedRatio < 1.0) {
                $advanceRatio = $parsedRatio;
            } elseif ($rawRatio === '40') {
                $advanceRatio = 0.4;
            } elseif ($rawRatio === '50') {
                $advanceRatio = 0.5;
            }
        }

        if (!$hasAdvance) {
            return [0.0, $this->roundMoney($netSalary)];
        }

        if ($advanceAmount === 0.0 && $remainingAmount === 0.0) {
            if ($shouldSplit) {
                $ratio = $advanceRatio ?? 0.5;
                $advanceAmount = $this->roundMoney($netSalary * $ratio);
                $remainingAmount = $this->roundMoney($netSalary - $advanceAmount);
            } else {
                $remainingAmount = $this->roundMoney($netSalary);
            }

            return [$advanceAmount, $remainingAmount];
        }

        if ($advanceAmount > $netSalary) {
            return [$netSalary, 0.0];
        }

        if ($remainingAmount > $netSalary) {
            $remainingAmount = $netSalary;
        }

        if ($advanceAmount > 0.0 && $remainingAmount === 0.0) {
            $remainingAmount = $this->roundMoney(max(0.0, $netSalary - $advanceAmount));

            return [$advanceAmount, $remainingAmount];
        }

        if ($remainingAmount > 0.0 && $advanceAmount === 0.0) {
            $advanceAmount = $this->roundMoney(max(0.0, $netSalary - $remainingAmount));
        }

        $total = $this->roundMoney($advanceAmount + $remainingAmount);
        if (abs($total - $netSalary) > 0.01) {
            $remainingAmount = $this->roundMoney(max(0.0, $netSalary - $advanceAmount));
        }

        return [$advanceAmount, $remainingAmount];
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

    private function resolveThirteenthPaymentDate(string $referenceMonth, string $installment): DateTimeImmutable
    {
        $day = $installment === 'first' ? '05' : '20';
        $date = DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%s-%s', $referenceMonth, $day));

        if ($date === false) {
            throw new RuntimeException('Mês de referência inválido para o 13º.');
        }

        return $date;
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

    /**
     * @param array<int, array{limit: float|null, rate: float}> $brackets
     */
    private function calculateInss(float $base, array $brackets): float
    {
        if ($base <= 0) {
            return 0.0;
        }

        if ($brackets === []) {
            $brackets = $this->contributionRepository->getDefault()->getInssBrackets();
        }

        $remaining = $base;
        $contribution = 0.0;
        $previousLimit = 0.0;
        $lastRate = 0.0;

        foreach ($brackets as $bracket) {
            $rate = isset($bracket['rate']) ? (float) $bracket['rate'] : 0.0;
            $limit = $bracket['limit'] ?? null;
            $lastRate = $rate;

            if ($remaining <= 0) {
                break;
            }

            if ($limit === null) {
                $contribution += max(0.0, $remaining) * $rate;
                $remaining = 0.0;
                break;
            }

            $rangeAmount = min($remaining, max(0.0, $limit - $previousLimit));
            if ($rangeAmount > 0) {
                $contribution += $rangeAmount * $rate;
                $remaining -= $rangeAmount;
            }

            $previousLimit = $limit;
        }

        if ($remaining > 0 && $lastRate > 0) {
            $contribution += $remaining * $lastRate;
        }

        return $this->roundMoney($contribution);
    }

    /**
     * @param array<int, array{limit: float|null, rate: float, deduction?: float}> $brackets
     */
    private function calculateIrrf(float $base, array $brackets): float
    {
        if ($base <= 0) {
            return 0.0;
        }

        if ($brackets === []) {
            $brackets = $this->contributionRepository->getDefault()->getIrrfBrackets();
        }

        foreach ($brackets as $bracket) {
            $limit = $bracket['limit'] ?? null;
            $rate = isset($bracket['rate']) ? (float) $bracket['rate'] : 0.0;
            $deduction = isset($bracket['deduction']) ? (float) $bracket['deduction'] : 0.0;

            if ($limit === null || $base <= $limit) {
                return $this->roundMoney(max(0.0, $base * $rate - $deduction));
            }
        }

        $last = end($brackets);
        if (is_array($last)) {
            $rate = isset($last['rate']) ? (float) $last['rate'] : 0.0;
            $deduction = isset($last['deduction']) ? (float) $last['deduction'] : 0.0;

            return $this->roundMoney(max(0.0, $base * $rate - $deduction));
        }

        return 0.0;
    }
}
