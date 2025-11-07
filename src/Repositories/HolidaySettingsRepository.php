<?php

declare(strict_types=1);

namespace Holerite\Repositories;

use Holerite\Database\Connection;
use Holerite\Models\HolidaySettings;
use PDO;

final class HolidaySettingsRepository
{
    private PDO $pdo;

    /**
     * @var array<string, mixed>
     */
    private array $defaults;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
        $this->defaults = $this->loadDefaults();
    }

    public function get(): HolidaySettings
    {
        $statement = $this->pdo->query('SELECT * FROM holiday_settings ORDER BY id ASC LIMIT 1');
        $row = $statement ? $statement->fetch() : false;

        if ($row === false) {
            $settings = $this->createFromDefaults();
            $this->save($settings);
            return $settings;
        }

        return $this->hydrate($row);
    }

    public function save(HolidaySettings $settings): HolidaySettings
    {
        $municipal = json_encode($settings->getMunicipalHolidays(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($settings->getId() === null) {
            $statement = $this->pdo->prepare('INSERT INTO holiday_settings (include_national, include_optional, include_municipal, state, city, municipal_holidays) VALUES (:include_national, :include_optional, :include_municipal, :state, :city, :municipal_holidays)');
            $statement->execute([
                'include_national' => $settings->includeNational() ? 1 : 0,
                'include_optional' => $settings->includeOptional() ? 1 : 0,
                'include_municipal' => $settings->includeMunicipal() ? 1 : 0,
                'state' => $settings->getState(),
                'city' => $settings->getCity(),
                'municipal_holidays' => $municipal,
            ]);

            $settings->setId((int) $this->pdo->lastInsertId());
            return $settings;
        }

        $statement = $this->pdo->prepare('UPDATE holiday_settings SET include_national = :include_national, include_optional = :include_optional, include_municipal = :include_municipal, state = :state, city = :city, municipal_holidays = :municipal_holidays WHERE id = :id');
        $statement->execute([
            'id' => $settings->getId(),
            'include_national' => $settings->includeNational() ? 1 : 0,
            'include_optional' => $settings->includeOptional() ? 1 : 0,
            'include_municipal' => $settings->includeMunicipal() ? 1 : 0,
            'state' => $settings->getState(),
            'city' => $settings->getCity(),
            'municipal_holidays' => $municipal,
        ]);

        return $settings;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): HolidaySettings
    {
        $municipal = [];
        if (isset($row['municipal_holidays'])) {
            $decoded = json_decode((string) $row['municipal_holidays'], true);
            if (is_array($decoded)) {
                $municipal = $decoded;
            }
        }

        return new HolidaySettings(
            isset($row['id']) ? (int) $row['id'] : null,
            isset($row['include_national']) ? ((int) $row['include_national'] !== 0) : (bool) $this->defaults['include_national'],
            isset($row['include_optional']) ? ((int) $row['include_optional'] !== 0) : (bool) $this->defaults['include_optional'],
            isset($row['include_municipal']) ? ((int) $row['include_municipal'] !== 0) : (bool) $this->defaults['include_municipal'],
            (string) ($row['state'] ?? $this->defaults['state']),
            (string) ($row['city'] ?? $this->defaults['city']),
            $municipal,
        );
    }

    private function createFromDefaults(): HolidaySettings
    {
        $municipal = [];
        $defaults = $this->defaults['municipal_holidays'] ?? [];
        if (is_array($defaults)) {
            $municipal = $defaults;
        }

        return new HolidaySettings(
            null,
            (bool) $this->defaults['include_national'],
            (bool) $this->defaults['include_optional'],
            (bool) $this->defaults['include_municipal'],
            (string) $this->defaults['state'],
            (string) $this->defaults['city'],
            $municipal,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function loadDefaults(): array
    {
        $configPath = __DIR__ . '/../../config/config.php';
        $defaults = [
            'include_national' => true,
            'include_optional' => true,
            'include_municipal' => true,
            'state' => 'SP',
            'city' => 'São Paulo',
            'municipal_holidays' => [
                ['name' => 'Aniversário da cidade', 'month' => 1, 'day' => 25],
            ],
        ];

        if (!is_file($configPath)) {
            return $defaults;
        }

        /**
         * @var array{holidays?: array<string, mixed>} $config
         */
        $config = require $configPath;
        if (!is_array($config)) {
            return $defaults;
        }

        $holidayConfig = $config['holidays'] ?? [];
        if (!is_array($holidayConfig)) {
            return $defaults;
        }

        return array_merge($defaults, $holidayConfig);
    }
}
