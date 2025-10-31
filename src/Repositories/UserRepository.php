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

    public function findById(int $id): ?User
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * @return User[]
     */
    public function all(): array
    {
        $statement = $this->pdo->query('SELECT * FROM users ORDER BY username ASC');
        $rows = $statement->fetchAll();

        if ($rows === false) {
            return [];
        }

        return array_map(fn ($row) => $this->hydrate((array) $row), $rows);
    }

    public function create(string $username, string $passwordHash, string $role): User
    {
        $statement = $this->pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, :role)');
        $statement->execute([
            'username' => $username,
            'password_hash' => $passwordHash,
            'role' => $role,
        ]);

        return new User((int) $this->pdo->lastInsertId(), $username, $passwordHash, $role);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        $statement->execute([
            'id' => $id,
            'password_hash' => $passwordHash,
        ]);
    }

    public function updateUsername(int $id, string $username): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET username = :username WHERE id = :id');
        $statement->execute([
            'id' => $id,
            'username' => $username,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): User
    {
        $role = isset($row['role']) && in_array($row['role'], User::allowedRoles(), true)
            ? (string) $row['role']
            : User::ROLE_ADMINISTRATOR;

        return new User(
            isset($row['id']) ? (int) $row['id'] : null,
            (string) $row['username'],
            (string) $row['password_hash'],
            $role,
        );
    }
}
