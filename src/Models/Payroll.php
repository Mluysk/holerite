<?php

declare(strict_types=1);

namespace Holerite\Models;

use DateTimeImmutable;

final class Payroll
{
    /** @param PayrollItem[] $items */
    public function __construct(
        private ?int $id,
        private int $employeeId,
        private string $referenceMonth,
        private string $type,
        private float $baseSalary,
        private float $totalAllowances,
        private float $totalDeductions,
        private float $netSalary,
        private float $advanceAmount,
        private float $remainingAmount,
        private float $valeDeduction,
        private bool $usesTransport,
        private float $transportDeduction,
        private DateTimeImmutable $paymentDate,
        private bool $justCause,
        private ?int $vacationDays,
        private ?int $workedDays,
        private ?int $thirteenthMonths,
        private ?string $thirteenthInstallment,
        private float $thirteenthAccrual,
        private float $inssBase,
        private float $inssAmount,
        private float $irrfBase,
        private float $irrfAmount,
        private float $fgtsBase,
        private float $fgtsAmount,
        private string $notes,
        private array $items = [],
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getReferenceMonth(): string
    {
        return $this->referenceMonth;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getBaseSalary(): float
    {
        return $this->baseSalary;
    }

    public function getTotalAllowances(): float
    {
        return $this->totalAllowances;
    }

    public function getTotalDeductions(): float
    {
        return $this->totalDeductions;
    }

    public function getNetSalary(): float
    {
        return $this->netSalary;
    }

    public function getAdvanceAmount(): float
    {
        return $this->advanceAmount;
    }

    public function getRemainingAmount(): float
    {
        return $this->remainingAmount;
    }

    public function getValeDeduction(): float
    {
        return $this->valeDeduction + $this->transportDeduction;
    }

    public function getManualValeDeduction(): float
    {
        return $this->valeDeduction;
    }

    public function usesTransport(): bool
    {
        return $this->usesTransport;
    }

    public function getTransportDeduction(): float
    {
        return $this->transportDeduction;
    }

    public function getPaymentDate(): DateTimeImmutable
    {
        return $this->paymentDate;
    }

    public function isJustCause(): bool
    {
        return $this->justCause;
    }

    public function getVacationDays(): ?int
    {
        return $this->vacationDays;
    }

    public function getWorkedDays(): ?int
    {
        return $this->workedDays;
    }

    public function getThirteenthMonths(): ?int
    {
        return $this->thirteenthMonths;
    }

    public function getThirteenthInstallment(): ?string
    {
        return $this->thirteenthInstallment;
    }

    public function getThirteenthAccrual(): float
    {
        return $this->thirteenthAccrual;
    }

    public function getInssBase(): float
    {
        return $this->inssBase;
    }

    public function getInssAmount(): float
    {
        return $this->inssAmount;
    }

    public function getIrrfBase(): float
    {
        return $this->irrfBase;
    }

    public function getIrrfAmount(): float
    {
        return $this->irrfAmount;
    }

    public function getFgtsBase(): float
    {
        return $this->fgtsBase;
    }

    public function getFgtsAmount(): float
    {
        return $this->fgtsAmount;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    /**
     * @return PayrollItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param PayrollItem[] $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }
}
