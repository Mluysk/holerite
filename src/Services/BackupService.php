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

    public function runAutomaticBackups(string $directory, bool $enabled, int $intervalMinutes): void
    {
        if (!$enabled) {
            return;
        }

        if ($intervalMinutes < 60) {
            $intervalMinutes = 60;
        }

        if ($intervalMinutes > 10080) {
            $intervalMinutes = 10080;
        }

        $intervalSeconds = $intervalMinutes * 60;
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);

        if ($directory === '') {
            return;
        }

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return;
        }

        $metaFile = $directory . DIRECTORY_SEPARATOR . 'automatic_backups.json';
        $meta = [];

        if (is_file($metaFile)) {
            try {
                $decoded = json_decode((string) file_get_contents($metaFile), true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $meta = $decoded;
                }
            } catch (Throwable $exception) {
                $meta = [];
            }
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
        $timestamp = $now->getTimestamp();
        $updated = false;

        $backups = [
            'database' => ['method' => 'generateDatabaseBackup', 'title' => 'database'],
            'configuration' => ['method' => 'generateConfigurationBackup', 'title' => 'configuration'],
            'users' => ['method' => 'generateUsersBackup', 'title' => 'users'],
        ];

        foreach ($backups as $key => $spec) {
            $lastRun = isset($meta[$key]['last_run']) ? (int) $meta[$key]['last_run'] : 0;

            if ($timestamp - $lastRun < $intervalSeconds) {
                continue;
            }

            $method = $spec['method'];

            try {
                $payload = $this->{$method}();
            } catch (Throwable $exception) {
                continue;
            }

            $filename = sprintf(
                'backup_%s_%s_%s.json',
                $now->format('Y-m-d'),
                $now->format('H-i-s'),
                $spec['title']
            );

            $path = $directory . DIRECTORY_SEPARATOR . $filename;

            if (@file_put_contents($path, $payload) === false) {
                continue;
            }

            $meta[$key] = [
                'last_run' => $timestamp,
                'file' => $filename,
            ];
            $updated = true;
        }

        if ($updated) {
            try {
                file_put_contents(
                    $metaFile,
                    json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                );
            } catch (Throwable $exception) {
            }
        }
    }

    public function generateDatabaseBackup(): string
    {
        $tables = [
            'companies',
            'contribution_settings',
            'employees',
            'payrolls',
            'payroll_items',
            'users',
            'audit_logs',
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
        $contributions = $this->fetchSingle('SELECT * FROM contribution_settings ORDER BY id ASC LIMIT 1');

        return $this->encode([
            'type' => 'configuration',
            'generated_at' => $this->timestamp(),
            'config_file' => $config,
            'company' => $company,
            'contribution_settings' => $this->formatContributionForBackup($contributions),
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
                    'color_palette' => (string) ($row['color_palette'] ?? 'dark-red'),
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

        $allowedTables = ['companies', 'contribution_settings', 'employees', 'payrolls', 'payroll_items', 'users', 'audit_logs'];
        $clearOrder = ['payroll_items', 'payrolls', 'employees', 'audit_logs', 'companies', 'contribution_settings', 'users'];

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

            foreach (['companies', 'contribution_settings', 'employees', 'payrolls', 'payroll_items', 'users', 'audit_logs'] as $table) {
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
                        $palette = isset($row['color_palette']) ? strtolower((string) $row['color_palette']) : 'dark-red';

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
                            $palette = 'dark-red';
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
        $contributionData = $payload['contribution_settings'] ?? null;

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

        if (is_array($contributionData) && $contributionData !== []) {
            $filtered = $this->filterContributionData($contributionData);

            if ($filtered !== []) {
                $columns = array_keys($filtered);
                $placeholders = array_map(fn (string $column): string => ':' . $column, $columns);
                $updates = array_map(
                    fn (string $column): string => sprintf('%s = VALUES(%s)', $this->wrapIdentifier($column), $this->wrapIdentifier($column)),
                    $columns
                );

                $sql = sprintf(
                    'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
                    $this->wrapIdentifier('contribution_settings'),
                    implode(', ', array_map([$this, 'wrapIdentifier'], $columns)),
                    implode(', ', $placeholders),
                    implode(', ', $updates)
                );

                $statement = $this->pdo->prepare($sql);
                $statement->execute($filtered);
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
            'brand_name',
            'document',
            'address',
            'city',
            'state',
            'zip_code',
            'phone',
            'email',
            'header_logo_path',
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
     * @param array<string, mixed>|null $row
     * @return array<string, mixed>|null
     */
    private function formatContributionForBackup(?array $row): ?array
    {
        if ($row === null) {
            return null;
        }

        return [
            'id' => isset($row['id']) ? (int) $row['id'] : 1,
            'fgts_rate' => isset($row['fgts_rate']) ? (float) $row['fgts_rate'] : 0.08,
            'inss_brackets' => $this->decodeContributionField($row['inss_brackets'] ?? null),
            'irrf_brackets' => $this->decodeContributionField($row['irrf_brackets'] ?? null),
            'manual_allowances' => $this->normalizeManualAllowancesField($row['manual_allowances'] ?? []),
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function filterContributionData(array $data): array
    {
        $id = isset($data['id']) ? (int) $data['id'] : 1;
        $fgtsRate = isset($data['fgts_rate']) ? (float) $data['fgts_rate'] : 0.08;

        $inss = $this->normalizeContributionBrackets($data['inss_brackets'] ?? []);
        $irrf = $this->normalizeContributionBrackets($data['irrf_brackets'] ?? [], true);
        $allowances = $this->normalizeManualAllowancesField($data['manual_allowances'] ?? []);

        $inssJson = json_encode($inss, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        $irrfJson = json_encode($irrf, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        $allowancesJson = json_encode($allowances, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

        if ($inssJson === false || $irrfJson === false || $allowancesJson === false) {
            return [];
        }

        return [
            'id' => $id,
            'fgts_rate' => $this->clampRate($fgtsRate),
            'inss_brackets' => $inssJson,
            'irrf_brackets' => $irrfJson,
            'manual_allowances' => $allowancesJson,
        ];
    }

    /**
     * @return array<int, array{limit: float|null, rate: float, deduction?: float}>
     */
    private function normalizeContributionBrackets(mixed $raw, bool $withDeduction = false): array
    {
        $decoded = $this->decodeContributionField($raw);
        $normalized = [];

        foreach ($decoded as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $rate = $entry['rate'] ?? null;
            if (!is_numeric($rate)) {
                continue;
            }

            $limit = $entry['limit'] ?? null;
            $limitValue = null;
            if ($limit !== null && $limit !== '') {
                if (!is_numeric($limit)) {
                    continue;
                }

                $limitValue = round((float) $limit, 2);
            }

            $bracket = [
                'limit' => $limitValue,
                'rate' => $this->clampRate((float) $rate),
            ];

            if ($withDeduction) {
                $bracket['deduction'] = round((float) ($entry['deduction'] ?? 0), 2);
            }

            $normalized[] = $bracket;
        }

        return $normalized;
    }

    /**
     * @return array<int, mixed>
     */
    private function decodeContributionField(mixed $value): array
    {
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (is_array($value)) {
            return $value;
        }

        return [];
    }

    /**
     * @return string[]
     */
    private function normalizeManualAllowancesField(mixed $value): array
    {
        $decoded = $this->decodeContributionField($value);
        $normalized = [];

        foreach ($decoded as $entry) {
            if (!is_string($entry)) {
                continue;
            }

            $label = trim($entry);
            if ($label === '') {
                continue;
            }

            if (!in_array($label, $normalized, true)) {
                $normalized[] = $label;
            }
        }

        return array_values($normalized);
    }

    private function clampRate(float $rate): float
    {
        if ($rate < 0.0) {
            return 0.0;
        }

        if ($rate > 1.0) {
            return 1.0;
        }

        return round($rate, 6);
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

        $palette = isset($filtered['color_palette']) ? strtolower((string) $filtered['color_palette']) : 'dark-red';
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
            $filtered['color_palette'] = 'dark-red';
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
            'color_palette' => 'dark-red',
        ]);
    }
}
