<?php

declare(strict_types=1);

namespace Holerite\Models;

final class User
{
    public const ROLE_ADMINISTRATOR = 'administrator';
    public const ROLE_OPERATOR = 'operator';

    public function __construct(
        private ?int $id,
        private string $username,
        private string $passwordHash,
        private string $role = self::ROLE_OPERATOR,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    /**
     * @return string[]
     */
    public static function allowedRoles(): array
    {
        return [
            self::ROLE_ADMINISTRATOR,
            self::ROLE_OPERATOR,
        ];
    }
}
