<?php

declare(strict_types=1);

namespace Holerite\Database;

use PDO;
use PDOException;
use RuntimeException;

final class Connection
{
    private static ?PDO $pdo = null;

    public static function getInstance(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $configPath = __DIR__ . '/../../config/config.php';
        if (!file_exists($configPath)) {
            throw new RuntimeException('Arquivo de configuração não encontrado.');
        }

        /** @var array{db: array{host: string, port: int, name: string, user: string, password: string, charset: string}} $config */
        $config = require $configPath;
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $config['db']['host'], $config['db']['port'], $config['db']['name'], $config['db']['charset']);

        try {
            self::$pdo = new PDO($dsn, $config['db']['user'], $config['db']['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Não foi possível conectar ao banco de dados: ' . $exception->getMessage());
        }

        return self::$pdo;
    }
}
