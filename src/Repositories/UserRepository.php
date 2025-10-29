<?php

declare(strict_types=1);

namespace Holerite\Repositories;

use Holerite\Database\Connection;
use Holerite\Models\User;
use PDO;

final class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function findByUsername(string $username): ?User
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $statement->execute(['username' => $username]);
        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): User
    {
        return new User(
            isset($row['id']) ? (int) $row['id'] : null,
            (string) $row['username'],
            (string) $row['password_hash'],
        );
    }
}
