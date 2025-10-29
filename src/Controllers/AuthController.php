<?php

declare(strict_types=1);

namespace Holerite\Controllers;

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

        $this->render('auth/login', [
            'title' => 'Entrar',
            'layoutClass' => 'layout-content layout-content-auth',
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

        if ($user !== null && password_verify($password, $user->getPasswordHash())) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
            ];

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

        $this->redirect('?action=edit_company');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createUser(array $data): void
    {
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $confirmPassword = (string) ($data['confirm_password'] ?? '');

        if ($username === '' || $password === '' || $confirmPassword === '') {
            $this->flash('error', 'Informe usuário e senha para criar uma nova conta.');
            $this->redirect('?action=edit_company');
        }

        if (strlen($username) < 3) {
            $this->flash('error', 'O nome de usuário deve ter pelo menos 3 caracteres.');
            $this->redirect('?action=edit_company');
        }

        if ($password !== $confirmPassword) {
            $this->flash('error', 'A confirmação da senha não confere.');
            $this->redirect('?action=edit_company');
        }

        if (strlen($password) < 6) {
            $this->flash('error', 'A senha deve ter pelo menos 6 caracteres.');
            $this->redirect('?action=edit_company');
        }

        try {
            if ($this->userRepository->findByUsername($username) !== null) {
                throw new RuntimeException('Já existe um usuário com este nome.');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $this->userRepository->create($username, $hash);
            $this->flash('success', 'Usuário criado com sucesso.');
        } catch (RuntimeException $exception) {
            $this->flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            $this->flash('error', 'Não foi possível criar o usuário: ' . $exception->getMessage());
        }

        $this->redirect('?action=edit_company');
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
