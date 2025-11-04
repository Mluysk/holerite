<?php

declare(strict_types=1);

namespace Holerite\Repositories;

use DateTimeImmutable;
use Holerite\Database\Connection;
use Holerite\Models\AuditLog;
use PDO;

final class AuditLogRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function log(?int $userId, string $action, string $entityType, ?int $entityId, string $description): void
    {
        $statement = $this->pdo->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, description) VALUES (:user_id, :action, :entity_type, :entity_id, :description)');
        $statement->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
        ]);
    }

    /**
     * @return AuditLog[]
     */
    public function latest(int $limit = 20): array
    {
        $statement = $this->pdo->prepare('SELECT al.*, u.username FROM audit_logs al LEFT JOIN users u ON u.id = al.user_id ORDER BY al.created_at DESC, al.id DESC LIMIT :limit');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        $rows = $statement->fetchAll();

        return array_map(function (array $row): AuditLog {
            $createdAtRaw = isset($row['created_at']) ? (string) $row['created_at'] : 'now';

            return new AuditLog(
                (int) $row['id'],
                $row['user_id'] !== null ? (int) $row['user_id'] : null,
                (string) $row['action'],
                (string) $row['entity_type'],
                $row['entity_id'] !== null ? (int) $row['entity_id'] : null,
                (string) ($row['description'] ?? ''),
                new DateTimeImmutable($createdAtRaw),
                isset($row['username']) ? (string) $row['username'] : null,
            );
        }, $rows ?: []);
    }
}

