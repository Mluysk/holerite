<?php

declare(strict_types=1);

use Holerite\Controllers\CompanyController;
use Holerite\Controllers\AuthController;
use Holerite\Controllers\DashboardController;
use Holerite\Controllers\EmployeeController;
use Holerite\Controllers\PayrollController;
use Holerite\Repositories\CompanyRepository;
use Holerite\Repositories\EmployeeRepository;
use Holerite\Repositories\PayrollRepository;
use Holerite\Repositories\UserRepository;
use Holerite\Services\EmployeeBenefitService;
use Holerite\Services\PayrollService;

require __DIR__ . '/../autoload.php';

session_start();

$companyRepository = new CompanyRepository();
$employeeRepository = new EmployeeRepository();
$payrollRepository = new PayrollRepository();
$userRepository = new UserRepository();
$employeeBenefitService = new EmployeeBenefitService();
$payrollService = new PayrollService($employeeRepository, $payrollRepository);

$activeCompany = $companyRepository->get();
$GLOBALS['holerite_company'] = $activeCompany;
$GLOBALS['holerite_appearance'] = [
    'themeMode' => $activeCompany->getThemeMode(),
    'colorPalette' => $activeCompany->getColorPalette(),
];

$dashboardController = new DashboardController($employeeRepository, $payrollRepository);
$companyController = new CompanyController($companyRepository, $userRepository);
$employeeController = new EmployeeController($employeeRepository, $payrollRepository, $employeeBenefitService);
$payrollController = new PayrollController($employeeRepository, $payrollRepository, $payrollService, $companyRepository);
$authController = new AuthController($userRepository);

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

    case 'list_employees':
        $employeeController->index();
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

    default:
        http_response_code(404);
        echo '<h1>Página não encontrada</h1>';
        break;
}
