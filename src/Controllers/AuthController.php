<?php

declare(strict_types=1);

namespace Holerite\Controllers;

use DateTimeImmutable;
use DateTimeZone;
use Holerite\Models\User;
use Holerite\Repositories\UserRepository;
use RuntimeException;
use Throwable;

final class AuthController extends Controller
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function loginForm(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('?action=dashboard');
        }

        $timezone = new DateTimeZone('America/Sao_Paulo');
        $currentHour = (int) (new DateTimeImmutable('now', $timezone))->format('H');
        $greeting = $currentHour < 12
            ? 'Bom dia'
            : ($currentHour < 18 ? 'Boa tarde' : 'Boa noite');

        $loginName = isset($_SESSION['login_username']) && $_SESSION['login_username'] !== ''
            ? (string) $_SESSION['login_username']
            : null;

        $this->render('auth/login', [
            'title' => 'Entrar',
            'layoutClass' => 'layout-content layout-content-auth',
            'greeting' => $greeting,
            'loginName' => $loginName,
            'loginUsername' => $loginName,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function authenticate(array $data): void
    {
        $username = isset($data['username']) ? trim((string) $data['username']) : '';
        $password = isset($data['password']) ? (string) $data['password'] : '';

        if ($username === '' || $password === '') {
            $this->flash('error', 'Informe usuário e senha.');
            $this->redirect('?action=login');
        }

        $user = $this->userRepository->findByUsername($username);

        $_SESSION['login_username'] = $username;

        if ($user !== null && password_verify($password, $user->getPasswordHash())) {
            $themeMode = strtolower($user->getThemeMode());
            $palette = strtolower($user->getColorPalette());
            $allowedThemes = ['light', 'dark'];
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

            if (!in_array($themeMode, $allowedThemes, true)) {
                $themeMode = 'light';
            }

            if (!in_array($palette, $allowedPalettes, true)) {
                $palette = 'blue';
            }

            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'role' => $user->getRole(),
                'theme_mode' => $themeMode,
                'color_palette' => $palette,
            ];

            unset($_SESSION['login_username']);

            $this->flash('success', 'Login realizado com sucesso.');
            $this->redirect('?action=dashboard');
        }

        $this->flash('error', 'Credenciais inválidas.');
        $this->redirect('?action=login');
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
        session_regenerate_id(true);
        $this->flash('success', 'Sessão encerrada.');
        $this->redirect('?action=login');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updatePassword(array $data): void
    {
        $userId = $this->currentUserId();
        $currentPassword = (string) ($data['current_password'] ?? '');
        $newPassword = (string) ($data['new_password'] ?? '');
        $confirmPassword = (string) ($data['confirm_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $this->flash('error', 'Preencha todos os campos para alterar a senha.');
            $this->redirect('?action=edit_company');
        }

        if ($newPassword !== $confirmPassword) {
            $this->flash('error', 'A confirmação da nova senha não confere.');
            $this->redirect('?action=edit_company');
        }

        if (strlen($newPassword) < 6) {
            $this->flash('error', 'A nova senha deve ter pelo menos 6 caracteres.');
            $this->redirect('?action=edit_company');
        }

        try {
            $user = $this->userRepository->findById($userId);

            if ($user === null || !password_verify($currentPassword, $user->getPasswordHash())) {
                throw new RuntimeException('Senha atual incorreta.');
            }

            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $this->userRepository->updatePassword($userId, $hash);
            $this->flash('success', 'Senha atualizada com sucesso.');
        } catch (RuntimeException $exception) {
            $this->flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            $this->flash('error', 'Não foi possível atualizar a senha: ' . $exception->getMessage());
        }

        $this->redirect('?action=edit_company&tab=password');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateUsername(array $data): void
    {
        $userId = $this->currentUserId();
        $username = trim((string) ($data['username'] ?? ''));

        if ($username === '') {
            $this->flash('error', 'Informe um nome de usuário.');
            $this->redirect('?action=edit_company');
        }

        if (strlen($username) < 3) {
            $this->flash('error', 'O nome de usuário deve ter pelo menos 3 caracteres.');
            $this->redirect('?action=edit_company');
        }

        try {
            $currentUser = $this->userRepository->findById($userId);

            if ($currentUser === null) {
                throw new RuntimeException('Usuário não encontrado.');
            }

            $existing = $this->userRepository->findByUsername($username);

            if ($existing !== null && $existing->getId() !== $currentUser->getId()) {
                throw new RuntimeException('Já existe um usuário com este nome.');
            }

            if ($currentUser->getUsername() !== $username) {
                $this->userRepository->updateUsername($userId, $username);
                $_SESSION['user']['username'] = $username;
            }

            $this->flash('success', 'Nome de usuário atualizado com sucesso.');
        } catch (RuntimeException $exception) {
            $this->flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            $this->flash('error', 'Não foi possível atualizar o usuário: ' . $exception->getMessage());
        }

        $this->redirect('?action=edit_company&tab=password');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createUser(array $data): void
    {
        $this->requireAdministrator();

        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $confirmPassword = (string) ($data['confirm_password'] ?? '');
        $roleInput = strtolower(trim((string) ($data['role'] ?? User::ROLE_OPERATOR)));
        $allowedRoles = User::allowedRoles();
        $role = in_array($roleInput, $allowedRoles, true) ? $roleInput : User::ROLE_OPERATOR;

        if ($username === '' || $password === '' || $confirmPassword === '') {
            $this->flash('error', 'Informe usuário e senha para criar uma nova conta.');
            $this->redirect('?action=edit_company&tab=users');
        }

        if (strlen($username) < 3) {
            $this->flash('error', 'O nome de usuário deve ter pelo menos 3 caracteres.');
            $this->redirect('?action=edit_company&tab=users');
        }

        if ($password !== $confirmPassword) {
            $this->flash('error', 'A confirmação da senha não confere.');
            $this->redirect('?action=edit_company&tab=users');
        }

        if (strlen($password) < 6) {
            $this->flash('error', 'A senha deve ter pelo menos 6 caracteres.');
            $this->redirect('?action=edit_company&tab=users');
        }

        try {
            if ($this->userRepository->findByUsername($username) !== null) {
                throw new RuntimeException('Já existe um usuário com este nome.');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $company = $GLOBALS['holerite_company'] ?? null;
            $defaultTheme = is_object($company) && method_exists($company, 'getThemeMode')
                ? strtolower((string) $company->getThemeMode())
                : 'light';
            $defaultPalette = is_object($company) && method_exists($company, 'getColorPalette')
                ? strtolower((string) $company->getColorPalette())
                : 'blue';

            if (!in_array($defaultTheme, ['light', 'dark'], true)) {
                $defaultTheme = 'light';
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

            if (!in_array($defaultPalette, $allowedPalettes, true)) {
                $defaultPalette = 'blue';
            }

            $this->userRepository->create($username, $hash, $role, $defaultTheme, $defaultPalette);
            $this->flash('success', 'Usuário criado com sucesso.');
        } catch (RuntimeException $exception) {
            $this->flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            $this->flash('error', 'Não foi possível criar o usuário: ' . $exception->getMessage());
        }

        $this->redirect('?action=edit_company&tab=users');
    }

    private function requireAdministrator(): void
    {
        $user = $_SESSION['user'] ?? null;
        $role = is_array($user) ? ($user['role'] ?? null) : null;

        if ($role === User::ROLE_ADMINISTRATOR) {
            return;
        }

        $this->flash('error', 'Apenas administradores podem gerenciar contas adicionais.');
        $this->redirect('?action=dashboard');
    }

    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user']) && is_array($_SESSION['user']);
    }

    private function currentUserId(): int
    {
        if (!$this->isAuthenticated()) {
            throw new RuntimeException('Sessão expirada.');
        }

        return (int) ($_SESSION['user']['id'] ?? 0);
    }
}
