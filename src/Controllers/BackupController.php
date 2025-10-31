<?php

declare(strict_types=1);

namespace Holerite\Controllers;

use DateTimeImmutable;
use DateTimeZone;
use Holerite\Models\User;
use Holerite\Services\BackupService;
use RuntimeException;

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

    public function restoreDatabase(): void
    {
        $this->assertAdministrator();
        $contents = $this->readUploadedBackup('database_backup', 'Selecione um arquivo JSON exportado do banco de dados.');

        if ($contents === null) {
            $this->redirect('?action=edit_company&tab=backups');
            return;
        }

        try {
            $this->backupService->restoreDatabaseBackup($contents);
            $this->flash('success', 'Backup do banco de dados restaurado com sucesso.');
        } catch (RuntimeException $exception) {
            $this->flash('error', $exception->getMessage());
        }

        $this->redirect('?action=edit_company&tab=backups');
    }

    public function restoreConfiguration(): void
    {
        $this->assertAdministrator();
        $contents = $this->readUploadedBackup('configuration_backup', 'Selecione um arquivo JSON de configuração para restaurar.');

        if ($contents === null) {
            $this->redirect('?action=edit_company&tab=backups');
            return;
        }

        try {
            $this->backupService->restoreConfigurationBackup($contents);
            $this->flash('success', 'Configurações restauradas com sucesso.');
        } catch (RuntimeException $exception) {
            $this->flash('error', $exception->getMessage());
        }

        $this->redirect('?action=edit_company&tab=backups');
    }

    public function restoreUsers(): void
    {
        $this->assertAdministrator();
        $contents = $this->readUploadedBackup('users_backup', 'Selecione um arquivo JSON de usuários para restaurar.');

        if ($contents === null) {
            $this->redirect('?action=edit_company&tab=backups');
            return;
        }

        try {
            $this->backupService->restoreUsersBackup($contents);
            $this->flash('success', 'Usuários restaurados com sucesso.');
        } catch (RuntimeException $exception) {
            $this->flash('error', $exception->getMessage());
        }

        $this->redirect('?action=edit_company&tab=backups');
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

    private function readUploadedBackup(string $inputName, string $emptyMessage): ?string
    {
        $file = $_FILES[$inputName] ?? null;

        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $this->flash('error', $emptyMessage);
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Não foi possível carregar o arquivo enviado.');
            return null;
        }

        $tmpName = $file['tmp_name'] ?? '';

        if (!is_string($tmpName) || $tmpName === '' || !file_exists($tmpName)) {
            $this->flash('error', 'Arquivo temporário inválido para restauração.');
            return null;
        }

        $contents = file_get_contents($tmpName);

        if ($contents === false || trim($contents) === '') {
            $this->flash('error', 'O arquivo de backup está vazio ou corrompido.');
            return null;
        }

        return $contents;
    }
}
