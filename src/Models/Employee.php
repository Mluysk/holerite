<?php

declare(strict_types=1);

namespace Holerite\Models;

use DateTimeImmutable;

final class Employee
{
    public function __construct(
        private ?int $id,
        private string $name,
        private string $cpf,
        private DateTimeImmutable $birthDate,
        private float $baseSalary,
        private string $department,
        private string $position,
        private DateTimeImmutable $hireDate,
        private ?DateTimeImmutable $vacationBaseDate = null,
        private ?DateTimeImmutable $terminationDate = null,
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCpf(): string
    {
        return $this->cpf;
    }

    public function setCpf(string $cpf): void
    {
        $this->cpf = $cpf;
    }

    public function getCpfFormatted(): string
    {
        $digits = preg_replace('/\D+/', '', $this->cpf);

        if (strlen($digits) !== 11) {
            return $this->cpf;
        }

        return sprintf(
            '%s%s%s.%s%s%s.%s%s%s-%s%s',
            $digits[0],
            $digits[1],
            $digits[2],
            $digits[3],
            $digits[4],
            $digits[5],
            $digits[6],
            $digits[7],
            $digits[8],
            $digits[9],
            $digits[10]
        );
    }

    public function getBaseSalary(): float
    {
        return $this->baseSalary;
    }

    public function getBirthDate(): DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(DateTimeImmutable $birthDate): void
    {
        $this->birthDate = $birthDate;
    }

    public function setBaseSalary(float $baseSalary): void
    {
        $this->baseSalary = $baseSalary;
    }

    public function getDepartment(): string
    {
        return $this->department;
    }

    public function setDepartment(string $department): void
    {
        $this->department = $department;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function setPosition(string $position): void
    {
        $this->position = $position;
    }

    public function getHireDate(): DateTimeImmutable
    {
        return $this->hireDate;
    }

    public function setHireDate(DateTimeImmutable $hireDate): void
    {
        $this->hireDate = $hireDate;
    }

    public function getVacationBaseDate(): ?DateTimeImmutable
    {
        return $this->vacationBaseDate;
    }

    public function setVacationBaseDate(?DateTimeImmutable $vacationBaseDate): void
    {
        $this->vacationBaseDate = $vacationBaseDate;
    }

    public function getTerminationDate(): ?DateTimeImmutable
    {
        return $this->terminationDate;
    }

    public function setTerminationDate(?DateTimeImmutable $terminationDate): void
    {
        $this->terminationDate = $terminationDate;
    }
}
