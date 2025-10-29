<?php

declare(strict_types=1);

namespace Holerite\Controllers;

use Holerite\Models\Company;
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
    ];

    public function __construct(
        private CompanyRepository $companyRepository,
        private UserRepository $userRepository,
    ) {
    }

    public function edit(): void
    {
        $company = $this->companyRepository->get();
        $users = $this->userRepository->all();
        $currentUser = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;

        $pageScripts = [
            [
                'src' => 'js/tabs.js',
                'defer' => true,
            ],
            [
                'src' => 'js/theme-preview.js',
                'defer' => true,
            ],
        ];

        $this->render('company/form', [
            'title' => 'Configurações',
            'company' => $company,
            'users' => $users,
            'currentUser' => $currentUser,
            'themeModes' => self::THEME_MODES,
            'colorPalettes' => self::COLOR_PALETTES,
            'appearance' => [
                'themeMode' => $company->getThemeMode(),
                'colorPalette' => $company->getColorPalette(),
            ],
            'pageScripts' => $pageScripts,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(array $data): void
    {
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
        $themeMode = strtolower((string) ($data['theme_mode'] ?? ''));
        $colorPalette = strtolower((string) ($data['color_palette'] ?? ''));

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

        if (!array_key_exists($themeMode, self::THEME_MODES)) {
            $themeMode = 'light';
        }

        if (!array_key_exists($colorPalette, self::COLOR_PALETTES)) {
            $colorPalette = 'blue';
        }

        $company->setThemeMode($themeMode);
        $company->setColorPalette($colorPalette);

        return $company;
    }
}
