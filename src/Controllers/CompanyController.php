<?php

declare(strict_types=1);

namespace Holerite\Controllers;

use Holerite\Models\Company;
use Holerite\Models\User;
use Holerite\Repositories\CompanyRepository;
use Holerite\Repositories\UserRepository;
use RuntimeException;
use Throwable;

final class CompanyController extends Controller
{
    private const THEME_MODES = [
        'light' => 'Claro',
        'dark' => 'Escuro',
    ];

    private const COLOR_PALETTES = [
        'blue' => 'Azul',
        'emerald' => 'Esmeralda',
        'violet' => 'Violeta',
        'amber' => 'Âmbar',
        'rose' => 'Rosé',
        'black' => 'Preto',
        'gray' => 'Cinza',
        'red' => 'Vermelho',
        'dark-red' => 'Vermelho Escuro',
        'pink' => 'Rosa',
        'yellow' => 'Amarelo',
        'gold' => 'Gold',
        'rgb' => 'RGB',
        'light-blue' => 'Azul Claro',
        'dark-blue' => 'Azul Escuro',
        'wine' => 'Cor Vinho',
    ];

    private const USER_ROLES = [
        User::ROLE_ADMINISTRATOR => 'Administrador',
        User::ROLE_OPERATOR => 'Operador',
    ];

    public function __construct(
        private CompanyRepository $companyRepository,
        private UserRepository $userRepository,
    ) {
    }

    public function edit(): void
    {
        $company = $this->companyRepository->get();
        $currentUser = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        $isAdmin = $this->isAdmin();
        $users = $isAdmin ? $this->userRepository->all() : [];
        $requestedTab = isset($_GET['tab']) ? strtolower((string) $_GET['tab']) : ($isAdmin ? 'company' : 'appearance');
        $allowedTabs = $isAdmin
            ? ['company', 'appearance', 'password', 'users', 'backups']
            : ['appearance', 'password'];
        if (!in_array($requestedTab, $allowedTabs, true)) {
            $requestedTab = $isAdmin ? 'company' : 'appearance';
        }

        $userThemeMode = is_array($currentUser) && isset($currentUser['theme_mode'])
            ? strtolower((string) $currentUser['theme_mode'])
            : $company->getThemeMode();
        $userColorPalette = is_array($currentUser) && isset($currentUser['color_palette'])
            ? strtolower((string) $currentUser['color_palette'])
            : $company->getColorPalette();

        if (!array_key_exists($userThemeMode, self::THEME_MODES)) {
            $userThemeMode = $company->getThemeMode();
        }

        if (!array_key_exists($userColorPalette, self::COLOR_PALETTES)) {
            $userColorPalette = $company->getColorPalette();
        }

        $pageScripts = [
            [
                'src' => 'js/tabs.js',
                'defer' => true,
            ],
            [
                'src' => 'js/theme-preview.js',
                'defer' => true,
            ],
            [
                'src' => 'js/company-form.js',
                'defer' => true,
            ],
        ];

        if ($isAdmin) {
            $pageScripts[] = [
                'src' => 'js/backup.js',
                'defer' => true,
            ];
        }

        $companyThemeMode = $company->getThemeMode();
        if (!array_key_exists($companyThemeMode, self::THEME_MODES)) {
            $companyThemeMode = 'light';
        }

        $companyColorPalette = $company->getColorPalette();
        if (!array_key_exists($companyColorPalette, self::COLOR_PALETTES)) {
            $companyColorPalette = 'blue';
        }

        $this->render('company/form', [
            'title' => 'Configurações',
            'company' => $company,
            'users' => $users,
            'currentUser' => $currentUser,
            'themeModes' => self::THEME_MODES,
            'colorPalettes' => self::COLOR_PALETTES,
            'userRoles' => self::USER_ROLES,
            'appearance' => [
                'themeMode' => $userThemeMode,
                'colorPalette' => $userColorPalette,
            ],
            'userThemeMode' => $userThemeMode,
            'userColorPalette' => $userColorPalette,
            'companyThemeMode' => $companyThemeMode,
            'companyColorPalette' => $companyColorPalette,
            'defaultTab' => $requestedTab,
            'availableTabs' => $allowedTabs,
            'isAdmin' => $isAdmin,
            'pageScripts' => $pageScripts,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(array $data): void
    {
        $this->ensureAdmin();
        $company = $this->companyRepository->get();

        try {
            $updated = $this->fillCompany($company, $data);
            $this->companyRepository->save($updated);
            $this->flash('success', 'Dados da empresa atualizados com sucesso.');
        } catch (RuntimeException $exception) {
            $this->flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            $this->flash('error', 'Não foi possível atualizar os dados: ' . $exception->getMessage());
        }

        $this->redirect('?action=edit_company');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateThemeMode(array $data): void
    {
        $user = $_SESSION['user'] ?? null;

        if (!is_array($user) || !isset($user['id'])) {
            $this->flash('error', 'Sessão expirada.');
            $this->redirect('?action=login');
        }

        $themeMode = strtolower((string) ($data['theme_mode'] ?? ''));

        if (!array_key_exists($themeMode, self::THEME_MODES)) {
            $this->flash('error', 'Selecione um modo de exibição válido.');
            $this->redirect('?action=edit_company&tab=appearance');
        }

        $currentPalette = strtolower((string) ($user['color_palette'] ?? 'blue'));

        if (!array_key_exists($currentPalette, self::COLOR_PALETTES)) {
            $currentPalette = 'blue';
        }

        try {
            $this->userRepository->updateAppearance((int) $user['id'], $themeMode, $currentPalette);
            $_SESSION['user']['theme_mode'] = $themeMode;
            $this->flash('success', 'Seu modo de exibição foi atualizado.');
        } catch (Throwable $exception) {
            $this->flash('error', 'Não foi possível atualizar o modo: ' . $exception->getMessage());
        }

        $this->redirect('?action=edit_company&tab=appearance');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateColorPalette(array $data): void
    {
        $user = $_SESSION['user'] ?? null;

        if (!is_array($user) || !isset($user['id'])) {
            $this->flash('error', 'Sessão expirada.');
            $this->redirect('?action=login');
        }

        $colorPalette = strtolower((string) ($data['color_palette'] ?? ''));

        if (!array_key_exists($colorPalette, self::COLOR_PALETTES)) {
            $this->flash('error', 'Selecione uma paleta de cores válida.');
            $this->redirect('?action=edit_company&tab=appearance');
        }

        $currentTheme = strtolower((string) ($user['theme_mode'] ?? 'light'));

        if (!array_key_exists($currentTheme, self::THEME_MODES)) {
            $currentTheme = 'light';
        }

        try {
            $this->userRepository->updateAppearance((int) $user['id'], $currentTheme, $colorPalette);
            $_SESSION['user']['color_palette'] = $colorPalette;
            $this->flash('success', 'Sua paleta de cores foi atualizada.');
        } catch (Throwable $exception) {
            $this->flash('error', 'Não foi possível atualizar a paleta: ' . $exception->getMessage());
        }

        $this->redirect('?action=edit_company&tab=appearance');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function fillCompany(Company $company, array $data): Company
    {
        $name = trim((string) ($data['name'] ?? ''));
        $document = trim((string) ($data['document'] ?? ''));
        $address = trim((string) ($data['address'] ?? ''));
        $city = trim((string) ($data['city'] ?? ''));
        $state = strtoupper(substr(trim((string) ($data['state'] ?? '')), 0, 2));
        $zip = trim((string) ($data['zip_code'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $themeModeInput = strtolower((string) ($data['theme_mode'] ?? ''));
        $colorPaletteInput = strtolower((string) ($data['color_palette'] ?? ''));

        $themeMode = array_key_exists($themeModeInput, self::THEME_MODES)
            ? $themeModeInput
            : $company->getThemeMode();

        $colorPalette = array_key_exists($colorPaletteInput, self::COLOR_PALETTES)
            ? $colorPaletteInput
            : $company->getColorPalette();

        if ($name === '' || $document === '' || $address === '' || $city === '' || $state === '' || $zip === '' || $phone === '' || $email === '') {
            throw new RuntimeException('Preencha todos os campos obrigatórios.');
        }

        $company->setName($name);
        $company->setDocument($document);
        $company->setAddress($address);
        $company->setCity($city);
        $company->setState($state);
        $company->setZipCode($zip);
        $company->setPhone($phone);
        $company->setEmail($email);

        $company->setThemeMode($themeMode);
        $company->setColorPalette($colorPalette);

        return $company;
    }

    private function isAdmin(): bool
    {
        $user = $_SESSION['user'] ?? null;
        $role = is_array($user) ? ($user['role'] ?? null) : null;

        return $role === User::ROLE_ADMINISTRATOR;
    }

    private function ensureAdmin(): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $this->flash('error', 'Acesso restrito aos administradores.');
        $this->redirect('?action=dashboard');
    }
}
