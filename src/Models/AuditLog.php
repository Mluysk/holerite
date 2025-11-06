<?php

declare(strict_types=1);

namespace Holerite\Models;

use DateTimeImmutable;

final class AuditLog
{
    public function __construct(
        private int $id,
        private ?int $userId,
        private string $action,
        private string $entityType,
        private ?int $entityId,
        private string $description,
        private DateTimeImmutable $createdAt,
        private ?string $username = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }
}

