<?php

declare(strict_types=1);

namespace Holerite\Repositories;

use Holerite\Database\Connection;
use Holerite\Models\Company;
use PDO;

final class CompanyRepository
{
    private const ALLOWED_COLOR_PALETTES = [
        'blue',
        'emerald',
        'violet',
        'amber',
        'rose',
        'black',
        'gray',
        'red',
        'dark-red',
        'pink',
        'yellow',
        'gold',
        'rgb',
        'light-blue',
        'dark-blue',
        'wine',
    ];

    private PDO $pdo;

    /**
     * @var array<string, string>
     */
    private array $defaults;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
        $this->defaults = $this->loadDefaults();
    }

    public function get(): Company
    {
        $statement = $this->pdo->query('SELECT * FROM companies ORDER BY id ASC LIMIT 1');
        $row = $statement->fetch();

        if ($row === false) {
            $company = $this->createFromDefaults();
            $this->save($company);
            return $company;
        }

        $company = $this->hydrate($row);
        $palette = strtolower($company->getColorPalette());
        $defaultPalette = strtolower($this->defaults['color_palette']);
        $shouldPersist = false;

        if (!in_array($palette, self::ALLOWED_COLOR_PALETTES, true)) {
            $company->setColorPalette($defaultPalette);
            $shouldPersist = true;
        }

        if ($palette === 'blue' && $defaultPalette === 'dark-red') {
            $company->setColorPalette('dark-red');
            $shouldPersist = true;
        }

        if ($shouldPersist) {
            $this->save($company);
        }

        return $company;
    }

    public function save(Company $company): Company
    {
        if ($company->getId() === null) {
            $statement = $this->pdo->prepare('INSERT INTO companies (name, document, address, city, state, zip_code, phone, email, theme_mode, color_palette) VALUES (:name, :document, :address, :city, :state, :zip_code, :phone, :email, :theme_mode, :color_palette)');
            $statement->execute([
                'name' => $company->getName(),
                'document' => $company->getDocument(),
                'address' => $company->getAddress(),
                'city' => $company->getCity(),
                'state' => $company->getState(),
                'zip_code' => $company->getZipCode(),
                'phone' => $company->getPhone(),
                'email' => $company->getEmail(),
                'theme_mode' => $company->getThemeMode(),
                'color_palette' => $company->getColorPalette(),
            ]);

            $company->setId((int) $this->pdo->lastInsertId());
            return $company;
        }

        $statement = $this->pdo->prepare('UPDATE companies SET name = :name, document = :document, address = :address, city = :city, state = :state, zip_code = :zip_code, phone = :phone, email = :email, theme_mode = :theme_mode, color_palette = :color_palette WHERE id = :id');
        $statement->execute([
            'id' => $company->getId(),
            'name' => $company->getName(),
            'document' => $company->getDocument(),
            'address' => $company->getAddress(),
            'city' => $company->getCity(),
            'state' => $company->getState(),
            'zip_code' => $company->getZipCode(),
            'phone' => $company->getPhone(),
            'email' => $company->getEmail(),
            'theme_mode' => $company->getThemeMode(),
            'color_palette' => $company->getColorPalette(),
        ]);

        return $company;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Company
    {
        return new Company(
            isset($row['id']) ? (int) $row['id'] : null,
            (string) ($row['name'] ?? $this->defaults['name']),
            (string) ($row['document'] ?? $this->defaults['document']),
            (string) ($row['address'] ?? $this->defaults['address']),
            (string) ($row['city'] ?? $this->defaults['city']),
            (string) ($row['state'] ?? $this->defaults['state']),
            (string) ($row['zip_code'] ?? $this->defaults['zip_code']),
            (string) ($row['phone'] ?? $this->defaults['phone']),
            (string) ($row['email'] ?? $this->defaults['email']),
            (string) ($row['theme_mode'] ?? $this->defaults['theme_mode']),
            (string) ($row['color_palette'] ?? $this->defaults['color_palette']),
        );
    }

    private function createFromDefaults(): Company
    {
        return new Company(
            null,
            $this->defaults['name'],
            $this->defaults['document'],
            $this->defaults['address'],
            $this->defaults['city'],
            $this->defaults['state'],
            $this->defaults['zip_code'],
            $this->defaults['phone'],
            $this->defaults['email'],
            $this->defaults['theme_mode'],
            $this->defaults['color_palette'],
        );
    }

    /**
     * @return array<string, string>
     */
    private function loadDefaults(): array
    {
        $configPath = __DIR__ . '/../../config/config.php';
        $defaults = [
            'name' => 'Empresa não configurada',
            'document' => '00.000.000/0000-00',
            'address' => 'Rua não informada, 0',
            'city' => 'Cidade',
            'state' => 'UF',
            'zip_code' => '00000-000',
            'phone' => '(00) 0000-0000',
            'email' => 'contato@empresa.com',
            'theme_mode' => 'light',
            'color_palette' => 'dark-red',
        ];

        if (!file_exists($configPath)) {
            return $defaults;
        }

        /**
         * @var array{company?: array<string, string>} $config
         */
        $config = require $configPath;
        if (!isset($config['company']) || !is_array($config['company'])) {
            return $defaults;
        }

        return array_merge($defaults, $config['company']);
    }
}
