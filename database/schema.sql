CREATE DATABASE IF NOT EXISTS holerite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE holerite;

CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    cpf VARCHAR(20) NOT NULL,
    base_salary DECIMAL(12,2) NOT NULL DEFAULT 0,
    department VARCHAR(150) NOT NULL,
    position VARCHAR(150) NOT NULL,
    hire_date DATE NOT NULL,
    termination_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    document VARCHAR(20) NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(120) NOT NULL,
    state VARCHAR(2) NOT NULL,
    zip_code VARCHAR(12) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    email VARCHAR(150) NOT NULL,
    theme_mode ENUM('light', 'dark') NOT NULL DEFAULT 'light',
    color_palette VARCHAR(20) NOT NULL DEFAULT 'dark-red',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contribution_settings (
    id INT PRIMARY KEY,
    fgts_rate DECIMAL(6,4) NOT NULL DEFAULT 0.0800,
    inss_brackets TEXT NOT NULL,
    irrf_brackets TEXT NOT NULL,
    manual_allowances TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO contribution_settings (id, fgts_rate, inss_brackets, irrf_brackets, manual_allowances)
VALUES (
    1,
    0.08,
    '[{"limit":1320,"rate":0.075},{"limit":2571.29,"rate":0.09},{"limit":3856.94,"rate":0.12},{"limit":7507.49,"rate":0.14},{"limit":null,"rate":0.14}]',
    '[{"limit":1903.98,"rate":0,"deduction":0},{"limit":2826.65,"rate":0.075,"deduction":142.8},{"limit":3751.05,"rate":0.15,"deduction":354.8},{"limit":4664.68,"rate":0.225,"deduction":636.13},{"limit":null,"rate":0.275,"deduction":869.36}]',
    '["Horas extras","Adicional noturno","Comissões","Bônus","Gratificações"]'
)
ON DUPLICATE KEY UPDATE
    fgts_rate = VALUES(fgts_rate),
    inss_brackets = VALUES(inss_brackets),
    irrf_brackets = VALUES(irrf_brackets),
    manual_allowances = VALUES(manual_allowances);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('administrator', 'operator') NOT NULL DEFAULT 'administrator',
    theme_mode ENUM('light', 'dark') NOT NULL DEFAULT 'light',
    color_palette VARCHAR(20) NOT NULL DEFAULT 'dark-red',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, password_hash, role, theme_mode, color_palette)
VALUES ('admin', '$2y$12$xYtysTzDWmNVJUtrv3xcnO62KGT24U774wEy8RTIA7H5IsczQS9fu', 'administrator', 'light', 'dark-red')
ON DUPLICATE KEY UPDATE username = VALUES(username), role = VALUES(role), theme_mode = VALUES(theme_mode), color_palette = VALUES(color_palette);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS payrolls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    reference_month VARCHAR(7) NOT NULL,
    type ENUM('regular', 'vacation', 'termination', 'thirteenth') NOT NULL DEFAULT 'regular',
    base_salary DECIMAL(12,2) NOT NULL,
    total_allowances DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_deductions DECIMAL(12,2) NOT NULL DEFAULT 0,
    net_salary DECIMAL(12,2) NOT NULL,
    advance_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    remaining_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    vale_deduction DECIMAL(12,2) NOT NULL DEFAULT 0,
    uses_transport TINYINT(1) NOT NULL DEFAULT 0,
    transport_deduction DECIMAL(12,2) NOT NULL DEFAULT 0,
    transport_days TINYINT UNSIGNED NOT NULL DEFAULT 0,
    transport_trip_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_date DATE NOT NULL,
    is_just_cause TINYINT(1) NOT NULL DEFAULT 0,
    vacation_days TINYINT UNSIGNED NULL,
    worked_days TINYINT UNSIGNED NULL,
    thirteenth_months TINYINT UNSIGNED NULL,
    thirteenth_installment ENUM('first', 'second') NULL,
    thirteenth_accrual DECIMAL(12,2) NOT NULL DEFAULT 0,
    inss_base DECIMAL(12,2) NOT NULL DEFAULT 0,
    inss_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    irrf_base DECIMAL(12,2) NOT NULL DEFAULT 0,
    irrf_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    fgts_base DECIMAL(12,2) NOT NULL DEFAULT 0,
    fgts_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
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
