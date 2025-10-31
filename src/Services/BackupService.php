<?php

declare(strict_types=1);

namespace Holerite\Services;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Holerite\Database\Connection;
use PDO;
use RuntimeException;
use Throwable;

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
        $statement = $this->pdo->query('SELECT id, username, role, password_hash, created_at, theme_mode, color_palette FROM users ORDER BY username ASC');
        $rows = $statement ? $statement->fetchAll() : [];

        return $this->encode([
            'type' => 'users',
            'generated_at' => $this->timestamp(),
            'users' => array_map(
                static fn (array $row): array => [
                    'id' => (int) ($row['id'] ?? 0),
                    'username' => (string) ($row['username'] ?? ''),
                    'role' => (string) ($row['role'] ?? ''),
                    'password_hash' => (string) ($row['password_hash'] ?? ''),
                    'created_at' => $row['created_at'] ?? null,
                    'theme_mode' => (string) ($row['theme_mode'] ?? 'light'),
                    'color_palette' => (string) ($row['color_palette'] ?? 'blue'),
                ],
                $rows ?: []
            ),
        ]);
    }

    public function restoreDatabaseBackup(string $json): void
    {
        $payload = $this->decode($json);

        if (($payload['type'] ?? null) !== 'database') {
            throw new RuntimeException('O arquivo enviado não corresponde a um backup do banco de dados.');
        }

        $tables = $payload['tables'] ?? null;

        if (!is_array($tables)) {
            throw new RuntimeException('Backup de banco de dados inválido.');
        }

        $allowedTables = ['companies', 'employees', 'payrolls', 'payroll_items', 'users'];
        $clearOrder = ['payroll_items', 'payrolls', 'employees', 'companies', 'users'];

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        try {
            $this->pdo->beginTransaction();

            foreach ($clearOrder as $table) {
                if (!in_array($table, $allowedTables, true)) {
                    continue;
                }

                $wrapped = $this->wrapIdentifier($table);
                $this->pdo->exec(sprintf('DELETE FROM %s', $wrapped));
            }

            foreach (['companies', 'employees', 'payrolls', 'payroll_items', 'users'] as $table) {
                if (!in_array($table, $allowedTables, true)) {
                    continue;
                }

                $rows = $tables[$table] ?? [];

                if (!is_array($rows)) {
                    continue;
                }

                foreach ($rows as $row) {
                    if (!is_array($row) || $row === []) {
                        continue;
                    }

                    if ($table === 'users') {
                        $themeMode = isset($row['theme_mode']) ? strtolower((string) $row['theme_mode']) : 'light';
                        $palette = isset($row['color_palette']) ? strtolower((string) $row['color_palette']) : 'blue';

                        if (!in_array($themeMode, ['light', 'dark'], true)) {
                            $themeMode = 'light';
                        }

                        $allowedPalettes = [
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

                        if (!in_array($palette, $allowedPalettes, true)) {
                            $palette = 'blue';
                        }

                        $row['theme_mode'] = $themeMode;
                        $row['color_palette'] = $palette;
                    }

                    $this->insertRow($table, $row);
                }
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw new RuntimeException(
                'Não foi possível restaurar o backup do banco de dados: ' . $exception->getMessage(),
                0,
                $exception
            );
        } finally {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->seedDefaultAdministratorIfMissing();
    }

    public function restoreConfigurationBackup(string $json): void
    {
        $payload = $this->decode($json);

        if (($payload['type'] ?? null) !== 'configuration') {
            throw new RuntimeException('O arquivo enviado não corresponde a um backup de configuração.');
        }

        $configData = $payload['config_file'] ?? null;
        $companyData = $payload['company'] ?? null;

        if (!is_array($configData)) {
            throw new RuntimeException('Backup de configuração inválido.');
        }

        $this->writeConfigFile($configData);

        if (is_array($companyData) && $companyData !== []) {
            $filtered = $this->filterCompanyData($companyData);

            if ($filtered !== []) {
                $this->pdo->beginTransaction();

                try {
                    $columns = array_keys($filtered);
                    $placeholders = array_map(fn (string $column): string => ':' . $column, $columns);
                    $updates = array_map(
                        fn (string $column): string => sprintf('%s = VALUES(%s)', $this->wrapIdentifier($column), $this->wrapIdentifier($column)),
                        $columns
                    );

                    $sql = sprintf(
                        'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
                        $this->wrapIdentifier('companies'),
                        implode(', ', array_map([$this, 'wrapIdentifier'], $columns)),
                        implode(', ', $placeholders),
                        implode(', ', $updates)
                    );

                    $statement = $this->pdo->prepare($sql);
                    $statement->execute($filtered);

                    $this->pdo->commit();
                } catch (Throwable $exception) {
                    if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                    }

                    throw new RuntimeException(
                        'Não foi possível restaurar os dados da empresa: ' . $exception->getMessage(),
                        0,
                        $exception
                    );
                }
            }
        }
    }

    public function restoreUsersBackup(string $json): void
    {
        $payload = $this->decode($json);

        if (($payload['type'] ?? null) !== 'users') {
            throw new RuntimeException('O arquivo enviado não corresponde a um backup de usuários.');
        }

        $users = $payload['users'] ?? null;

        if (!is_array($users)) {
            throw new RuntimeException('Backup de usuários inválido.');
        }

        $this->pdo->beginTransaction();

        try {
            $this->pdo->exec('DELETE FROM users');

            foreach ($users as $user) {
                if (!is_array($user) || $user === []) {
                    continue;
                }

                $filtered = $this->filterUserData($user);

                if (!isset($filtered['username'], $filtered['password_hash'])) {
                    throw new RuntimeException('Backup de usuários inválido: senhas não encontradas.');
                }

                $this->insertRow('users', $filtered);
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw new RuntimeException(
                'Não foi possível restaurar o backup de usuários: ' . $exception->getMessage(),
                0,
                $exception
            );
        }

        $this->seedDefaultAdministratorIfMissing();
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

    /**
     * @return array<string, mixed>
     */
    private function decode(string $json): array
    {
        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Arquivo de backup inválido ou corrompido.');
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function insertRow(string $table, array $row): void
    {
        $columns = array_keys($row);
        $placeholders = array_map(fn (string $column): string => ':' . $column, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->wrapIdentifier($table),
            implode(', ', array_map([$this, 'wrapIdentifier'], $columns)),
            implode(', ', $placeholders)
        );

        $statement = $this->pdo->prepare($sql);

        foreach ($row as $column => $value) {
            if ($value === null) {
                $statement->bindValue(':' . $column, null, PDO::PARAM_NULL);
            } else {
                $statement->bindValue(':' . $column, $value);
            }
        }

        $statement->execute();
    }

    private function wrapIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function filterCompanyData(array $data): array
    {
        $allowed = [
            'id',
            'name',
            'document',
            'address',
            'city',
            'state',
            'zip_code',
            'phone',
            'email',
            'theme_mode',
            'color_palette',
            'created_at',
            'updated_at',
        ];

        $filtered = array_intersect_key($data, array_flip($allowed));

        if (!isset($filtered['id'])) {
            $filtered['id'] = 1;
        }

        return $filtered;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function filterUserData(array $data): array
    {
        $allowed = ['id', 'username', 'password_hash', 'role', 'created_at', 'theme_mode', 'color_palette'];

        $filtered = array_intersect_key($data, array_flip($allowed));

        $themeMode = isset($filtered['theme_mode']) ? strtolower((string) $filtered['theme_mode']) : 'light';
        if (!in_array($themeMode, ['light', 'dark'], true)) {
            $filtered['theme_mode'] = 'light';
        } else {
            $filtered['theme_mode'] = $themeMode;
        }

        $palette = isset($filtered['color_palette']) ? strtolower((string) $filtered['color_palette']) : 'blue';
        $allowedPalettes = [
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

        if (!in_array($palette, $allowedPalettes, true)) {
            $filtered['color_palette'] = 'blue';
        } else {
            $filtered['color_palette'] = $palette;
        }

        return $filtered;
    }

    private function writeConfigFile(array $config): void
    {
        if (!isset($config['db']) || !is_array($config['db'])) {
            throw new RuntimeException('Configuração de banco de dados ausente no backup.');
        }

        $path = __DIR__ . '/../../config/config.php';
        $export = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($config, true) . ";\n";

        if (file_put_contents($path, $export) === false) {
            throw new RuntimeException('Não foi possível atualizar o arquivo de configuração.');
        }
    }

    private function seedDefaultAdministratorIfMissing(): void
    {
        $statement = $this->pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'administrator'");
        $row = $statement ? $statement->fetch() : false;
        $count = $row !== false ? (int) ($row['total'] ?? 0) : 0;

        if ($count > 0) {
            return;
        }

        $seed = $this->pdo->prepare('INSERT INTO users (username, password_hash, role, theme_mode, color_palette) VALUES (:username, :password_hash, :role, :theme_mode, :color_palette)');
        $seed->execute([
            'username' => 'admin',
            'password_hash' => '$2y$12$xYtysTzDWmNVJUtrv3xcnO62KGT24U774wEy8RTIA7H5IsczQS9fu',
            'role' => 'administrator',
            'theme_mode' => 'light',
            'color_palette' => 'blue',
        ]);
    }
}
