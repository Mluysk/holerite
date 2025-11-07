<?php

declare(strict_types=1);

use Holerite\Controllers\CompanyController;
use Holerite\Controllers\AuthController;
use Holerite\Controllers\DashboardController;
use Holerite\Controllers\EmployeeController;
use Holerite\Controllers\PayrollController;
use Holerite\Controllers\BackupController;
use Holerite\Repositories\CompanyRepository;
use Holerite\Repositories\ContributionSettingsRepository;
use Holerite\Repositories\EmployeeRepository;
use Holerite\Repositories\PayrollRepository;
use Holerite\Repositories\AuditLogRepository;
use Holerite\Repositories\UserRepository;
use Holerite\Services\EmployeeBenefitService;
use Holerite\Services\PayrollService;
use Holerite\Services\BackupService;
use Holerite\Services\ContributionSyncService;

require __DIR__ . '/../autoload.php';

$configPath = __DIR__ . '/../config/config.php';
$appConfig = file_exists($configPath) ? require $configPath : [];
if (!is_array($appConfig)) {
    $appConfig = [];
}
$contributionsApiConfig = $appConfig['services']['contributions_api'] ?? [];
if (!is_array($contributionsApiConfig)) {
    $contributionsApiConfig = [];
}

session_start();

$companyRepository = new CompanyRepository();
$contributionRepository = new ContributionSettingsRepository();
$employeeRepository = new EmployeeRepository();
$payrollRepository = new PayrollRepository();
$userRepository = new UserRepository();
$auditLogRepository = new AuditLogRepository();
$employeeBenefitService = new EmployeeBenefitService();
$payrollService = new PayrollService($employeeRepository, $payrollRepository, $contributionRepository);
$backupService = new BackupService();

$activeCompany = $companyRepository->get();
$GLOBALS['holerite_company'] = $activeCompany;
$appearance = [
    'themeMode' => $activeCompany->getThemeMode(),
    'colorPalette' => $activeCompany->getColorPalette(),
];

if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    $userTheme = strtolower((string) ($_SESSION['user']['theme_mode'] ?? ''));
    $userPalette = strtolower((string) ($_SESSION['user']['color_palette'] ?? ''));
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

    if (in_array($userTheme, $allowedThemes, true)) {
        $appearance['themeMode'] = $userTheme;
    }

    if (in_array($userPalette, $allowedPalettes, true)) {
        $appearance['colorPalette'] = $userPalette;
    }
}

$GLOBALS['holerite_appearance'] = $appearance;

$dashboardController = new DashboardController($employeeRepository, $payrollRepository);
$contributionSyncService = new ContributionSyncService($contributionRepository, $contributionsApiConfig);
$companyController = new CompanyController(
    $companyRepository,
    $userRepository,
    $contributionRepository,
    $contributionSyncService,
    $contributionsApiConfig,
    $auditLogRepository,
);
$employeeController = new EmployeeController($employeeRepository, $payrollRepository, $employeeBenefitService);
$payrollController = new PayrollController(
    $employeeRepository,
    $payrollRepository,
    $payrollService,
    $companyRepository,
    $userRepository,
    $auditLogRepository,
    $contributionRepository,
);
$authController = new AuthController($userRepository);
$backupController = new BackupController($backupService, $userRepository);

$action = $_GET['action'] ?? 'dashboard';
$isAuthenticated = isset($_SESSION['user']) && is_array($_SESSION['user']);
$publicActions = ['login', 'authenticate'];

if (!$isAuthenticated && !in_array($action, $publicActions, true)) {
    header('Location: ?action=login');
    exit;
}

if ($isAuthenticated && in_array($action, ['login', 'authenticate'], true)) {
    header('Location: ?action=dashboard');
    exit;
}

switch ($action) {
    case 'login':
        $authController->loginForm();
        break;

    case 'authenticate':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->authenticate($_POST);
        } else {
            $authController->loginForm();
        }
        break;

    case 'logout':
        $authController->logout();
        break;

    case 'dashboard':
        $dashboardController->index();
        break;

    case 'dashboard_report':
        $scope = is_string($_GET['scope'] ?? null) ? (string) $_GET['scope'] : 'monthly';
        $dashboardController->report($scope);
        break;

    case 'edit_company':
        $companyController->edit();
        break;

    case 'update_company':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $companyController->update($_POST);
        } else {
            $companyController->edit();
        }
        break;
    case 'update_theme_mode':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $companyController->updateThemeMode($_POST);
        } else {
            $companyController->edit();
        }
        break;
    case 'update_color_palette':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $companyController->updateColorPalette($_POST);
        } else {
            $companyController->edit();
        }
        break;
    case 'update_contributions':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $companyController->updateContributions($_POST);
        } else {
            $companyController->edit();
        }
        break;
    case 'sync_contributions':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $companyController->syncContributions();
        } else {
            $companyController->edit();
        }
        break;
    case 'update_password':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->updatePassword($_POST);
        } else {
            $companyController->edit();
        }
        break;
    case 'update_username':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->updateUsername($_POST);
        } else {
            $companyController->edit();
        }
        break;
    case 'create_user':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->createUser($_POST);
        } else {
            $companyController->edit();
        }
        break;

    case 'backup_database':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $backupController->downloadDatabase();
        } else {
            $companyController->edit();
        }
        break;

    case 'backup_configuration':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $backupController->downloadConfiguration();
        } else {
            $companyController->edit();
        }
        break;

    case 'backup_users':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $backupController->downloadUsers();
        } else {
            $companyController->edit();
        }
        break;

    case 'restore_database':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $backupController->restoreDatabase();
        } else {
            $companyController->edit();
        }
        break;

    case 'restore_configuration':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $backupController->restoreConfiguration();
        } else {
            $companyController->edit();
        }
        break;

    case 'restore_users':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $backupController->restoreUsers();
        } else {
            $companyController->edit();
        }
        break;

    case 'list_employees':
        $employeeController->index();
        break;

    case 'employee_report':
        $employeeController->report();
        break;

    case 'create_employee':
        $employeeController->create();
        break;

    case 'store_employee':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $employeeController->store($_POST);
        } else {
            $employeeController->create();
        }
        break;

    case 'edit_employee':
        $id = (int) ($_GET['id'] ?? 0);
        $employeeController->edit($id);
        break;

    case 'show_employee':
        $id = (int) ($_GET['id'] ?? 0);
        $employeeController->show($id);
        break;

    case 'update_employee':
        $id = (int) ($_GET['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $employeeController->update($id, $_POST);
        } else {
            $employeeController->edit($id);
        }
        break;

    case 'delete_employee':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_GET['id'] ?? 0);
            $employeeController->delete($id);
        } else {
            $employeeController->index();
        }
        break;

    case 'list_payrolls':
        $payrollController->index();
        break;

    case 'create_payroll':
        $payrollController->create();
        break;

    case 'store_payroll':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payrollController->store($_POST);
        } else {
            $payrollController->create();
        }
        break;

    case 'show_payroll':
        $id = (int) ($_GET['id'] ?? 0);
        $payrollController->show($id);
        break;

    case 'delete_payroll':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_GET['id'] ?? 0);
            $payrollController->delete($id, $_POST);
        } else {
            $payrollController->index();
        }
        break;

    default:
        http_response_code(404);
        echo '<h1>Página não encontrada</h1>';
        break;
}
