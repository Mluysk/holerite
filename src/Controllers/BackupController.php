<?php

declare(strict_types=1);

namespace Holerite\Controllers;

use DateTimeImmutable;
use DateTimeZone;
use Holerite\Models\User;
use Holerite\Services\BackupService;

final class BackupController extends Controller
{
    public function __construct(
        private BackupService $backupService,
    ) {
    }

    public function downloadDatabase(): void
    {
        $this->assertAdministrator();
        $payload = $this->backupService->generateDatabaseBackup();
        $this->sendJsonFile('database', $payload);
    }

    public function downloadConfiguration(): void
    {
        $this->assertAdministrator();
        $payload = $this->backupService->generateConfigurationBackup();
        $this->sendJsonFile('configuration', $payload);
    }

    public function downloadUsers(): void
    {
        $this->assertAdministrator();
        $payload = $this->backupService->generateUsersBackup();
        $this->sendJsonFile('users', $payload);
    }

    private function assertAdministrator(): void
    {
        $user = $_SESSION['user'] ?? null;
        $role = is_array($user) ? ($user['role'] ?? null) : null;

        if ($role === User::ROLE_ADMINISTRATOR) {
            return;
        }

        $this->flash('error', 'Apenas administradores podem gerar backups.');
        $this->redirect('?action=edit_company&tab=backups');
    }

    private function sendJsonFile(string $type, string $payload): void
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
        $filename = sprintf('holerite-%s-backup-%s.json', $type, $now->format('Ymd-His'));

        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($payload));

        echo $payload;
        exit;
    }
}
