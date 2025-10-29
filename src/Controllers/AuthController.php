<?php

declare(strict_types=1);

namespace Holerite\Controllers;

use Holerite\Repositories\UserRepository;

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

    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user']) && is_array($_SESSION['user']);
    }
}
