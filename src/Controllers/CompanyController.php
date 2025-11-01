<?php

declare(strict_types=1);

namespace Holerite\Controllers;

use Holerite\Models\Company;
use Holerite\Models\ContributionSettings;
use Holerite\Models\User;
use Holerite\Repositories\CompanyRepository;
use Holerite\Repositories\ContributionSettingsRepository;
use Holerite\Repositories\UserRepository;
use Holerite\Services\ContributionSyncService;
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

    /**
     * @param array<string, mixed> $contributionApiConfig
     */
    public function __construct(
        private CompanyRepository $companyRepository,
        private UserRepository $userRepository,
        private ContributionSettingsRepository $contributionRepository,
        private ContributionSyncService $contributionSyncService,
        private array $contributionApiConfig = [],
    ) {
    }

    public function edit(): void
    {
        $company = $this->companyRepository->get();
        $contributions = $this->contributionRepository->get();
        $currentUser = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        $isAdmin = $this->isAdmin();
        $users = $isAdmin ? $this->userRepository->all() : [];
        $requestedTab = isset($_GET['tab']) ? strtolower((string) $_GET['tab']) : ($isAdmin ? 'company' : 'appearance');
        $allowedTabs = $isAdmin
            ? ['company', 'appearance', 'discounts', 'password', 'users', 'backups']
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
            'contributionSettings' => $contributions,
            'contributionApi' => $this->describeContributionApi(),
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
    public function updateContributions(array $data): void
    {
        $this->ensureAdmin();

        $current = $this->contributionRepository->get();
        $defaults = $this->contributionRepository->getDefault();

        try {
            $settings = $this->buildContributionSettings($current, $defaults, $data);
            $this->contributionRepository->save($settings);
            $this->flash('success', 'Tabela de descontos atualizada com sucesso.');
        } catch (RuntimeException $exception) {
            $this->flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            $this->flash('error', 'Não foi possível atualizar os descontos: ' . $exception->getMessage());
        }

        $this->redirect('?action=edit_company&tab=discounts');
    }

    public function syncContributions(): void
    {
        $this->ensureAdmin();

        try {
            $updatedSettings = $this->contributionSyncService->syncFromApi();
            $updatedAt = $updatedSettings->getUpdatedAt();
            if ($updatedAt !== null) {
                $formatted = $updatedAt->setTimezone(new \DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i');
                $this->flash('success', 'Tabelas atualizadas com sucesso pela API em ' . $formatted . '.');
            } else {
                $this->flash('success', 'Tabelas de descontos atualizadas com sucesso pela API.');
            }
        } catch (RuntimeException $exception) {
            $this->flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            $this->flash('error', 'Não foi possível atualizar via API: ' . $exception->getMessage());
        }

        $this->redirect('?action=edit_company&tab=discounts');
    }

    private function buildContributionSettings(
        ContributionSettings $current,
        ContributionSettings $defaults,
        array $data
    ): ContributionSettings {
        $fgtsPercent = $this->parseDecimal($data['fgts_rate'] ?? $current->getFgtsRatePercent());
        $fgtsRate = $this->clampRate($fgtsPercent / 100);

        $inss = $this->parseContributionBrackets($data['inss'] ?? [], false);
        if ($inss === []) {
            $inss = $current->getInssBrackets();
        }
        $inss = $this->finalizeContributionBrackets($inss, $defaults->getInssBrackets(), false);

        $irrf = $this->parseContributionBrackets($data['irrf'] ?? [], true);
        if ($irrf === []) {
            $irrf = $current->getIrrfBrackets();
        }
        $irrf = $this->finalizeContributionBrackets($irrf, $defaults->getIrrfBrackets(), true);

        return new ContributionSettings(
            $current->getId(),
            $fgtsRate,
            $inss,
            $irrf,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function describeContributionApi(): array
    {
        $endpoint = trim((string) ($this->contributionApiConfig['endpoint'] ?? ''));
        $provider = trim((string) ($this->contributionApiConfig['provider'] ?? ''));
        $hasToken = trim((string) ($this->contributionApiConfig['token'] ?? '')) !== '';
        $timeout = (int) ($this->contributionApiConfig['timeout'] ?? 10);
        if ($timeout <= 0) {
            $timeout = 10;
        }

        return [
            'enabled' => $endpoint !== '',
            'endpoint' => $endpoint,
            'provider' => $provider,
            'hasToken' => $hasToken,
            'timeout' => $timeout,
        ];
    }

    /**
     * @param array<int, mixed> $rows
     * @return array<int, array{limit: float|null, rate: float, deduction?: float}>
     */
    private function parseContributionBrackets(array $rows, bool $withDeduction): array
    {
        $brackets = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $hasLimit = isset($row['limit']) && trim((string) $row['limit']) !== '';
            $hasRate = isset($row['rate']) && trim((string) $row['rate']) !== '';

            if (!$hasLimit && !$hasRate) {
                continue;
            }

            $limit = null;
            if ($hasLimit) {
                $parsedLimit = $this->parseDecimal($row['limit']);
                if ($parsedLimit > 0) {
                    $limit = round($parsedLimit, 2);
                }
            }

            $ratePercent = $this->parseDecimal($row['rate'] ?? 0);
            $rate = $this->clampRate($ratePercent / 100);

            $bracket = [
                'limit' => $limit,
                'rate' => $rate,
            ];

            if ($withDeduction) {
                $deduction = $this->parseDecimal($row['deduction'] ?? 0);
                $bracket['deduction'] = round($deduction, 2);
            }

            $brackets[] = $bracket;
        }

        return $brackets;
    }

    /**
     * @param array<int, array{limit: float|null, rate: float, deduction?: float}> $input
     * @param array<int, array{limit: float|null, rate: float, deduction?: float}> $fallback
     * @return array<int, array{limit: float|null, rate: float, deduction?: float}>
     */
    private function finalizeContributionBrackets(array $input, array $fallback, bool $withDeduction): array
    {
        if ($input === []) {
            return $fallback;
        }

        usort($input, static function (array $a, array $b): int {
            $limitA = $a['limit'];
            $limitB = $b['limit'];

            if ($limitA === $limitB) {
                return 0;
            }

            if ($limitA === null) {
                return 1;
            }

            if ($limitB === null) {
                return -1;
            }

            return $limitA <=> $limitB;
        });

        $last = end($input);

        if ($last === false) {
            return $fallback;
        }

        if ($last['limit'] !== null) {
            $tail = $withDeduction
                ? ['limit' => null, 'rate' => $last['rate'], 'deduction' => $last['deduction'] ?? 0.0]
                : ['limit' => null, 'rate' => $last['rate']];

            $input[] = $tail;
        }

        return array_values($input);
    }

    private function parseDecimal(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $normalized = trim(str_replace(',', '.', $value));

            if ($normalized === '') {
                return 0.0;
            }

            return (float) $normalized;
        }

        return 0.0;
    }

    private function clampRate(float $rate): float
    {
        if ($rate < 0.0) {
            return 0.0;
        }

        if ($rate > 1.0) {
            return 1.0;
        }

        return round($rate, 6);
    }

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
