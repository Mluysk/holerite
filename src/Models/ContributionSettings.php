<?php

declare(strict_types=1);

namespace Holerite\Models;

use DateTimeImmutable;

final class ContributionSettings
{
    /**
     * @param array<int, array{limit: float|null, rate: float, deduction?: float}> $inssBrackets
     * @param array<int, array{limit: float|null, rate: float, deduction: float}> $irrfBrackets
     */
    public function __construct(
        private ?int $id,
        private float $fgtsRate,
        private array $inssBrackets,
        private array $irrfBrackets,
        private array $manualAllowances = [],
        private ?DateTimeImmutable $updatedAt = null,
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

    public function getFgtsRate(): float
    {
        return $this->fgtsRate;
    }

    public function getFgtsRatePercent(): float
    {
        return $this->fgtsRate * 100;
    }

    /**
     * @return array<int, array{limit: float|null, rate: float, deduction?: float}>
     */
    public function getInssBrackets(): array
    {
        return $this->inssBrackets;
    }

    /**
     * @return array<int, array{limit: float|null, rate: float, deduction: float}>
     */
    public function getIrrfBrackets(): array
    {
        return $this->irrfBrackets;
    }

    /**
     * @return string[]
     */
    public function getManualAllowances(): array
    {
        return $this->manualAllowances;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
