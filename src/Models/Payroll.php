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
        private float $baseSalary,
        private float $totalAllowances,
        private float $totalDeductions,
        private float $netSalary,
        private DateTimeImmutable $paymentDate,
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

    public function getPaymentDate(): DateTimeImmutable
    {
        return $this->paymentDate;
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
