<?php

declare(strict_types=1);

namespace Holerite\Models;

final class PayrollItem
{
    public function __construct(
        private ?int $id,
        private ?int $payrollId,
        private string $description,
        private float $amount,
        private string $type,
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

    public function getPayrollId(): ?int
    {
        return $this->payrollId;
    }

    public function setPayrollId(int $payrollId): void
    {
        $this->payrollId = $payrollId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
