<?php

declare(strict_types=1);

namespace Holerite\Models;

use DateTimeImmutable;

final class Employee
{
    public function __construct(
        private ?int $id,
        private string $name,
        private string $email,
        private float $baseSalary,
        private string $department,
        private string $position,
        private DateTimeImmutable $hireDate,
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getBaseSalary(): float
    {
        return $this->baseSalary;
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
}
