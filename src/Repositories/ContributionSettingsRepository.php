<?php

declare(strict_types=1);

namespace Holerite\Repositories;

use DateTimeImmutable;
use Holerite\Database\Connection;
use Holerite\Models\ContributionSettings;
use PDO;
use RuntimeException;

final class ContributionSettingsRepository
{
    private const DEFAULT_FGTS_RATE = 0.08;

    /**
     * @var array<int, array{limit: float|null, rate: float}>
     */
    private const DEFAULT_INSS = [
        ['limit' => 1320.00, 'rate' => 0.075],
        ['limit' => 2571.29, 'rate' => 0.09],
        ['limit' => 3856.94, 'rate' => 0.12],
        ['limit' => 7507.49, 'rate' => 0.14],
        ['limit' => null, 'rate' => 0.14],
    ];

    /**
     * @var array<int, array{limit: float|null, rate: float, deduction: float}>
     */
    private const DEFAULT_IRRF = [
        ['limit' => 1903.98, 'rate' => 0.0, 'deduction' => 0.0],
        ['limit' => 2826.65, 'rate' => 0.075, 'deduction' => 142.80],
        ['limit' => 3751.05, 'rate' => 0.15, 'deduction' => 354.80],
        ['limit' => 4664.68, 'rate' => 0.225, 'deduction' => 636.13],
        ['limit' => null, 'rate' => 0.275, 'deduction' => 869.36],
    ];

    /**
     * @var string[]
     */
    private const DEFAULT_ALLOWANCES = [
        'Horas extras',
        'Adicional noturno',
        'Comissões',
        'Bônus',
        'Gratificações',
    ];

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function get(): ContributionSettings
    {
        $statement = $this->pdo->query('SELECT * FROM contribution_settings ORDER BY id ASC LIMIT 1');
        $row = $statement ? $statement->fetch(PDO::FETCH_ASSOC) : false;

        if ($row === false) {
            return $this->createDefaultSettings();
        }

        return $this->hydrate($row);
    }

    public function save(ContributionSettings $settings): void
    {
        $id = $settings->getId() ?? 1;
        $payload = [
            'id' => $id,
            'fgts_rate' => $this->sanitizeRate($settings->getFgtsRate()),
            'inss_brackets' => json_encode($settings->getInssBrackets(), JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION),
            'irrf_brackets' => json_encode($settings->getIrrfBrackets(), JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION),
            'manual_allowances' => json_encode($this->normalizeAllowances($settings->getManualAllowances()), JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION),
        ];

        if ($payload['inss_brackets'] === false || $payload['irrf_brackets'] === false || $payload['manual_allowances'] === false) {
            throw new RuntimeException('Não foi possível preparar as faixas de contribuição para salvamento.');
        }

        $sql = 'INSERT INTO contribution_settings (id, fgts_rate, inss_brackets, irrf_brackets, manual_allowances) VALUES (:id, :fgts_rate, :inss_brackets, :irrf_brackets, :manual_allowances)
                ON DUPLICATE KEY UPDATE fgts_rate = VALUES(fgts_rate), inss_brackets = VALUES(inss_brackets), irrf_brackets = VALUES(irrf_brackets), manual_allowances = VALUES(manual_allowances)';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($payload);

        $settings->setId((int) $id);
    }

    public function getDefault(): ContributionSettings
    {
        return $this->createDefaultSettings();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): ContributionSettings
    {
        $fgtsRate = $this->sanitizeRate(isset($row['fgts_rate']) ? (float) $row['fgts_rate'] : self::DEFAULT_FGTS_RATE);
        $inssBrackets = $this->decodeInss($row['inss_brackets'] ?? null);
        $irrfBrackets = $this->decodeIrrf($row['irrf_brackets'] ?? null);

        $settings = new ContributionSettings(
            isset($row['id']) ? (int) $row['id'] : null,
            $fgtsRate,
            $inssBrackets,
            $irrfBrackets,
            $this->decodeManualAllowances($row['manual_allowances'] ?? null),
            $this->parseTimestamp($row['updated_at'] ?? null)
        );

        if ($settings->getId() === null) {
            $settings->setId(1);
        }

        return $settings;
    }

    private function createDefaultSettings(): ContributionSettings
    {
        return new ContributionSettings(
            1,
            self::DEFAULT_FGTS_RATE,
            self::DEFAULT_INSS,
            self::DEFAULT_IRRF,
            self::DEFAULT_ALLOWANCES,
            null
        );
    }

    private function sanitizeRate(float $rate): float
    {
        if ($rate < 0) {
            return 0.0;
        }

        if ($rate > 1) {
            return 1.0;
        }

        return round($rate, 6);
    }

    /**
     * @param mixed $payload
     * @return string[]
     */
    private function decodeManualAllowances($payload): array
    {
        $decoded = [];

        if (is_string($payload) && $payload !== '') {
            $json = json_decode($payload, true);
            if (is_array($json)) {
                $decoded = $json;
            }
        } elseif (is_array($payload)) {
            $decoded = $payload;
        }

        $normalized = $this->normalizeAllowances($decoded);

        if ($normalized === []) {
            return self::DEFAULT_ALLOWANCES;
        }

        return $normalized;
    }

    /**
     * @param array<int, mixed> $values
     * @return string[]
     */
    private function normalizeAllowances(array $values): array
    {
        $sanitized = [];

        foreach ($values as $value) {
            if (!is_string($value)) {
                continue;
            }

            $label = trim($value);
            if ($label === '') {
                continue;
            }

            if (!in_array($label, $sanitized, true)) {
                $sanitized[] = $label;
            }
        }

        return array_values($sanitized);
    }

    /**
     * @return array<int, array{limit: float|null, rate: float}>
     */
    private function decodeInss(mixed $payload): array
    {
        $decoded = $this->decodeJson($payload);
        $normalized = [];

        foreach ($decoded as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $limit = $entry['limit'] ?? null;
            $rate = $entry['rate'] ?? null;

            if (!is_numeric($rate)) {
                continue;
            }

            $limitValue = null;
            if ($limit !== null && $limit !== '') {
                if (!is_numeric($limit)) {
                    continue;
                }

                $limitValue = round((float) $limit, 2);
            }

            $normalized[] = [
                'limit' => $limitValue,
                'rate' => $this->sanitizeRate((float) $rate),
            ];
        }

        if ($normalized === []) {
            $normalized = self::DEFAULT_INSS;
        }

        usort($normalized, static function (array $a, array $b): int {
            $limitA = $a['limit'];
            $limitB = $b['limit'];

            if ($limitA === $limitB) {
                return 0;
            }

            if ($limitA === null) {
                return 1;
            }

            if ($limitB === null) {
                return -1;
            }

            return $limitA <=> $limitB;
        });

        $last = end($normalized);
        $fallbackRate = self::DEFAULT_INSS[count(self::DEFAULT_INSS) - 1]['rate'];
        if ($last !== false) {
            $fallbackRate = $last['rate'];
        }

        if ($last === false || $last['limit'] !== null) {
            $normalized[] = ['limit' => null, 'rate' => $fallbackRate];
        }

        return array_values($normalized);
    }

    /**
     * @return array<int, array{limit: float|null, rate: float, deduction: float}>
     */
    private function decodeIrrf(mixed $payload): array
    {
        $decoded = $this->decodeJson($payload);
        $normalized = [];

        foreach ($decoded as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $limit = $entry['limit'] ?? null;
            $rate = $entry['rate'] ?? null;
            $deduction = $entry['deduction'] ?? 0;

            if (!is_numeric($rate)) {
                continue;
            }

            $limitValue = null;
            if ($limit !== null && $limit !== '') {
                if (!is_numeric($limit)) {
                    continue;
                }

                $limitValue = round((float) $limit, 2);
            }

            $normalized[] = [
                'limit' => $limitValue,
                'rate' => $this->sanitizeRate((float) $rate),
                'deduction' => round((float) $deduction, 2),
            ];
        }

        if ($normalized === []) {
            $normalized = self::DEFAULT_IRRF;
        }

        usort($normalized, static function (array $a, array $b): int {
            $limitA = $a['limit'];
            $limitB = $b['limit'];

            if ($limitA === $limitB) {
                return 0;
            }

            if ($limitA === null) {
                return 1;
            }

            if ($limitB === null) {
                return -1;
            }

            return $limitA <=> $limitB;
        });

        $last = end($normalized);
        $fallback = self::DEFAULT_IRRF[count(self::DEFAULT_IRRF) - 1];
        if ($last !== false) {
            $fallback = $last;
        }

        if ($last === false || $last['limit'] !== null) {
            $normalized[] = [
                'limit' => null,
                'rate' => $fallback['rate'],
                'deduction' => $fallback['deduction'],
            ];
        }

        return array_values($normalized);
    }

    /**
     * @return array<int, mixed>
     */
    private function decodeJson(mixed $payload): array
    {
        if (is_string($payload) && $payload !== '') {
            $decoded = json_decode($payload, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (is_array($payload)) {
            return $payload;
        }

        return [];
    }

    private function parseTimestamp(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);

        return $date ?: null;
    }
}
