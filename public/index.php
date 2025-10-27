<?php

declare(strict_types=1);

use Holerite\Controllers\DashboardController;
use Holerite\Controllers\EmployeeController;
use Holerite\Controllers\PayrollController;
use Holerite\Repositories\EmployeeRepository;
use Holerite\Repositories\PayrollRepository;
use Holerite\Services\PayrollService;

require __DIR__ . '/../autoload.php';

session_start();

$employeeRepository = new EmployeeRepository();
$payrollRepository = new PayrollRepository();
$payrollService = new PayrollService($employeeRepository, $payrollRepository);

$dashboardController = new DashboardController($employeeRepository, $payrollRepository);
$employeeController = new EmployeeController($employeeRepository);
$payrollController = new PayrollController($employeeRepository, $payrollRepository, $payrollService);

$action = $_GET['action'] ?? 'dashboard';

switch ($action) {
    case 'dashboard':
        $dashboardController->index();
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
