<?php

declare(strict_types=1);

namespace Holerite\Models;

use DateTimeImmutable;
use InvalidArgumentException;

final class HolidaySettings
{
    private ?int $id;
    private bool $includeNational;
    private bool $includeOptional;
    private bool $includeMunicipal;
    private string $state;
    private string $city;
    /**
     * @var array<int, array{name: string, month: int, day: int}>
     */
    private array $municipalHolidays;

    /**
     * @param array<int, array{name: string, month: int, day: int}> $municipalHolidays
     */
    public function __construct(
        ?int $id,
        bool $includeNational,
        bool $includeOptional,
        bool $includeMunicipal,
        string $state,
        string $city,
        array $municipalHolidays = []
    ) {
        $this->id = $id;
        $this->includeNational = $includeNational;
        $this->includeOptional = $includeOptional;
        $this->includeMunicipal = $includeMunicipal;
        $this->state = strtoupper(trim($state));
        $this->city = trim($city);
        $this->municipalHolidays = $this->normalizeMunicipalHolidays($municipalHolidays);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function includeNational(): bool
    {
        return $this->includeNational;
    }

    public function setIncludeNational(bool $includeNational): void
    {
        $this->includeNational = $includeNational;
    }

    public function includeOptional(): bool
    {
        return $this->includeOptional;
    }

    public function setIncludeOptional(bool $includeOptional): void
    {
        $this->includeOptional = $includeOptional;
    }

    public function includeMunicipal(): bool
    {
        return $this->includeMunicipal;
    }

    public function setIncludeMunicipal(bool $includeMunicipal): void
    {
        $this->includeMunicipal = $includeMunicipal;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = strtoupper(trim($state));
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = trim($city);
    }

    /**
     * @return array<int, array{name: string, month: int, day: int}>
     */
    public function getMunicipalHolidays(): array
    {
        return $this->municipalHolidays;
    }

    /**
     * @param array<int, array{name: string, month: int, day: int}> $municipalHolidays
     */
    public function setMunicipalHolidays(array $municipalHolidays): void
    {
        $this->municipalHolidays = $this->normalizeMunicipalHolidays($municipalHolidays);
    }

    public function addMunicipalHoliday(string $name, int $month, int $day): void
    {
        $records = $this->municipalHolidays;
        $records[] = $this->sanitizeMunicipalHoliday($name, $month, $day);
        $this->municipalHolidays = $records;
    }

    /**
     * @return array{name: string, month: int, day: int}
     */
    private function sanitizeMunicipalHoliday(string $name, int $month, int $day): array
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('O nome do feriado municipal não pode ficar vazio.');
        }

        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('O mês informado para o feriado municipal é inválido.');
        }

        if ($day < 1 || $day > 31) {
            throw new InvalidArgumentException('O dia informado para o feriado municipal é inválido.');
        }

        // validação simples usando DateTimeImmutable
        try {
            new DateTimeImmutable(sprintf('2024-%02d-%02d', $month, $day));
        } catch (\Exception $exception) {
            throw new InvalidArgumentException('A data informada para o feriado municipal é inválida.');
        }

        return [
            'name' => $name,
            'month' => $month,
            'day' => $day,
        ];
    }

    /**
     * @param array<int, array{name?: string, month?: int, day?: int}> $holidays
     * @return array<int, array{name: string, month: int, day: int}>
     */
    private function normalizeMunicipalHolidays(array $holidays): array
    {
        $normalized = [];

        foreach ($holidays as $holiday) {
            if (!is_array($holiday)) {
                continue;
            }

            $name = isset($holiday['name']) ? trim((string) $holiday['name']) : '';
            $month = isset($holiday['month']) ? (int) $holiday['month'] : 0;
            $day = isset($holiday['day']) ? (int) $holiday['day'] : 0;

            if ($name === '' || $month < 1 || $month > 12 || $day < 1 || $day > 31) {
                continue;
            }

            try {
                $normalized[] = $this->sanitizeMunicipalHoliday($name, $month, $day);
            } catch (InvalidArgumentException $exception) {
                continue;
            }
        }

        return $normalized;
    }
}
