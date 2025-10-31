<?php

declare(strict_types=1);

namespace Holerite\Services;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Holerite\Database\Connection;
use PDO;
use RuntimeException;

final class BackupService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function generateDatabaseBackup(): string
    {
        $tables = [
            'companies',
            'employees',
            'payrolls',
            'payroll_items',
            'users',
        ];

        $data = [];

        foreach ($tables as $table) {
            $statement = $this->pdo->query(sprintf('SELECT * FROM %s', $table));
            $rows = $statement ? $statement->fetchAll() : [];
            $data[$table] = $rows ?: [];
        }

        return $this->encode([
            'type' => 'database',
            'generated_at' => $this->timestamp(),
            'tables' => $data,
        ]);
    }

    public function generateConfigurationBackup(): string
    {
        $config = $this->loadConfig();
        $company = $this->fetchSingle('SELECT * FROM companies ORDER BY id ASC LIMIT 1');

        return $this->encode([
            'type' => 'configuration',
            'generated_at' => $this->timestamp(),
            'config_file' => $config,
            'company' => $company,
        ]);
    }

    public function generateUsersBackup(): string
    {
        $statement = $this->pdo->query('SELECT id, username, role, created_at FROM users ORDER BY username ASC');
        $rows = $statement ? $statement->fetchAll() : [];

        return $this->encode([
            'type' => 'users',
            'generated_at' => $this->timestamp(),
            'users' => array_map(
                static fn (array $row): array => [
                    'id' => (int) ($row['id'] ?? 0),
                    'username' => (string) ($row['username'] ?? ''),
                    'role' => (string) ($row['role'] ?? ''),
                    'created_at' => $row['created_at'] ?? null,
                ],
                $rows ?: []
            ),
        ]);
    }

    private function timestamp(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format(DateTimeInterface::ATOM);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadConfig(): array
    {
        $configPath = __DIR__ . '/../../config/config.php';

        if (!file_exists($configPath)) {
            throw new RuntimeException('Arquivo de configuração não encontrado para backup.');
        }

        $config = require $configPath;

        if (!is_array($config)) {
            throw new RuntimeException('Configuração inválida para backup.');
        }

        return $config;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchSingle(string $query): ?array
    {
        $statement = $this->pdo->query($query);
        $row = $statement ? $statement->fetch() : false;

        if ($row === false) {
            return null;
        }

        return (array) $row;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function encode(array $payload): string
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($encoded === false) {
            throw new RuntimeException('Não foi possível gerar o arquivo de backup.');
        }

        return $encoded;
    }
}
