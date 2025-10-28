CREATE DATABASE IF NOT EXISTS holerite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE holerite;

CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    base_salary DECIMAL(12,2) NOT NULL DEFAULT 0,
    department VARCHAR(150) NOT NULL,
    position VARCHAR(150) NOT NULL,
    hire_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS payrolls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    reference_month VARCHAR(7) NOT NULL,
    type ENUM('regular', 'vacation', 'termination') NOT NULL DEFAULT 'regular',
    base_salary DECIMAL(12,2) NOT NULL,
    total_allowances DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_deductions DECIMAL(12,2) NOT NULL DEFAULT 0,
    net_salary DECIMAL(12,2) NOT NULL,
    payment_date DATE NOT NULL,
    is_just_cause TINYINT(1) NOT NULL DEFAULT 0,
    vacation_days TINYINT UNSIGNED NULL,
    worked_days TINYINT UNSIGNED NULL,
    thirteenth_months TINYINT UNSIGNED NULL,
    notes TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS payroll_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payroll_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    type ENUM('allowance', 'deduction') NOT NULL,
    FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE CASCADE
);
