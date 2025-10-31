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
        $requestedTab = isset($_GET['tab']) ? strtolower((string) $_GET['tab']) : ($isAdmin ? 'company' : 'password');
        $allowedTabs = $isAdmin
            ? ['company', 'appearance', 'password', 'users', 'backups']
            : ['password'];
        if (!in_array($requestedTab, $allowedTabs, true)) {
            $requestedTab = $isAdmin ? 'company' : 'password';
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

        $this->render('company/form', [
            'title' => 'Configurações',
            'company' => $company,
            'users' => $users,
            'currentUser' => $currentUser,
            'themeModes' => self::THEME_MODES,
            'colorPalettes' => self::COLOR_PALETTES,
            'userRoles' => self::USER_ROLES,
            'appearance' => [
                'themeMode' => $company->getThemeMode(),
                'colorPalette' => $company->getColorPalette(),
            ],
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
        $this->ensureAdmin();
        $company = $this->companyRepository->get();
        $themeMode = strtolower((string) ($data['theme_mode'] ?? ''));

        if (!array_key_exists($themeMode, self::THEME_MODES)) {
            $this->flash('error', 'Selecione um modo de exibição válido.');
            $this->redirect('?action=edit_company&tab=appearance');
        }

        try {
            $company->setThemeMode($themeMode);
            $this->companyRepository->save($company);
            $this->flash('success', 'Modo de exibição atualizado com sucesso.');
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
        $this->ensureAdmin();
        $company = $this->companyRepository->get();
        $colorPalette = strtolower((string) ($data['color_palette'] ?? ''));

        if (!array_key_exists($colorPalette, self::COLOR_PALETTES)) {
            $this->flash('error', 'Selecione uma paleta de cores válida.');
            $this->redirect('?action=edit_company&tab=appearance');
        }

        try {
            $company->setColorPalette($colorPalette);
            $this->companyRepository->save($company);
            $this->flash('success', 'Paleta de cores atualizada com sucesso.');
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
