-- Create employees table for hotel staff management
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL, -- Link to users table (optional)
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    position VARCHAR(100),
    department VARCHAR(100),
    hire_date DATE,
    hourly_rate DECIMAL(10,2) DEFAULT 0.00,
    hourly_rate_currency VARCHAR(3) DEFAULT 'USD',
    overtime_rate DECIMAL(10,2) DEFAULT 0.00,
    overtime_rate_currency VARCHAR(3) DEFAULT 'USD',
    weekly_hours INT DEFAULT 40,
    salary_type ENUM('hourly', 'monthly') DEFAULT 'hourly',
    monthly_salary DECIMAL(10,2) NULL,
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    address TEXT,
    tax_id VARCHAR(50),
    notes TEXT,
    status ENUM('active', 'inactive', 'terminated') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create time_clock table for employee time tracking
CREATE TABLE time_clock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    clock_in DATETIME NOT NULL,
    clock_out DATETIME NULL,
    break_start DATETIME NULL,
    break_end DATETIME NULL,
    total_break_minutes INT DEFAULT 0,
    total_hours DECIMAL(5,2) DEFAULT 0.00,
    overtime_hours DECIMAL(5,2) DEFAULT 0.00,
    notes TEXT,
    status ENUM('clocked_in', 'on_break', 'clocked_out') DEFAULT 'clocked_in',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Create payroll table for payroll management
CREATE TABLE payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    pay_period_start DATE NOT NULL,
    pay_period_end DATE NOT NULL,
    regular_hours DECIMAL(5,2) DEFAULT 0.00,
    overtime_hours DECIMAL(5,2) DEFAULT 0.00,
    regular_pay DECIMAL(10,2) DEFAULT 0.00,
    overtime_pay DECIMAL(10,2) DEFAULT 0.00,
    gross_pay DECIMAL(10,2) DEFAULT 0.00,
    deductions DECIMAL(10,2) DEFAULT 0.00,
    net_pay DECIMAL(10,2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'USD',
    pay_date DATE,
    status ENUM('draft', 'processed', 'paid') DEFAULT 'draft',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_employees_employee_id ON employees(employee_id);
CREATE INDEX idx_employees_department ON employees(department);
CREATE INDEX idx_employees_status ON employees(status);
CREATE INDEX idx_time_clock_employee ON time_clock(employee_id);
CREATE INDEX idx_time_clock_date ON time_clock(clock_in);
CREATE INDEX idx_payroll_employee ON payroll(employee_id);
CREATE INDEX idx_payroll_period ON payroll(pay_period_start, pay_period_end);

-- Insert sample employee data
INSERT INTO employees (
    employee_id, first_name, last_name, email, phone, position, department,
    hire_date, hourly_rate, hourly_rate_currency, overtime_rate, overtime_rate_currency,
    weekly_hours, salary_type
) VALUES 
('EMP001', 'John', 'Doe', 'john.doe@hotel.com', '555-0101', 'Front Desk Agent', 'Front Office', 
 '2024-01-15', 15.00, 'USD', 22.50, 'USD', 40, 'hourly'),
('EMP002', 'Jane', 'Smith', 'jane.smith@hotel.com', '555-0102', 'Housekeeper', 'Housekeeping', 
 '2024-02-01', 14.00, 'USD', 21.00, 'USD', 40, 'hourly'),
('EMP003', 'Mike', 'Johnson', 'mike.johnson@hotel.com', '555-0103', 'Maintenance', 'Maintenance', 
 '2024-01-10', 18.00, 'USD', 27.00, 'USD', 40, 'hourly'),
('EMP004', 'Sarah', 'Wilson', 'sarah.wilson@hotel.com', '555-0104', 'Assistant Manager', 'Management', 
 '2023-12-01', 25.00, 'USD', 37.50, 'USD', 40, 'hourly');