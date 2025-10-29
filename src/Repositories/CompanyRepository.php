<?php

declare(strict_types=1);

namespace Holerite\Repositories;

use Holerite\Database\Connection;
use Holerite\Models\Company;
use PDO;

final class CompanyRepository
{
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

        return $this->hydrate($row);
    }

    public function save(Company $company): Company
    {
        if ($company->getId() === null) {
            $statement = $this->pdo->prepare('INSERT INTO companies (name, document, address, city, state, zip_code, phone, email) VALUES (:name, :document, :address, :city, :state, :zip_code, :phone, :email)');
            $statement->execute([
                'name' => $company->getName(),
                'document' => $company->getDocument(),
                'address' => $company->getAddress(),
                'city' => $company->getCity(),
                'state' => $company->getState(),
                'zip_code' => $company->getZipCode(),
                'phone' => $company->getPhone(),
                'email' => $company->getEmail(),
            ]);

            $company->setId((int) $this->pdo->lastInsertId());
            return $company;
        }

        $statement = $this->pdo->prepare('UPDATE companies SET name = :name, document = :document, address = :address, city = :city, state = :state, zip_code = :zip_code, phone = :phone, email = :email WHERE id = :id');
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
