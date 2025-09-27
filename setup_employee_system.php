<?php
require_once 'includes/classes.php';

$database = new Database();
$connection = $database->getConnection();

echo "<h2>Setting up Employee Time Tracking System</h2>";

try {
    // Create employees table
    $employeesTableSql = "
        CREATE TABLE IF NOT EXISTS employees (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NULL,
            employee_id VARCHAR(20) UNIQUE NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) UNIQUE,
            phone VARCHAR(20),
            position ENUM('manager', 'receptionist', 'housekeeper', 'maintenance', 'security', 'kitchen', 'waiter', 'bartender', 'concierge', 'accountant', 'other') NOT NULL,
            department ENUM('front_desk', 'housekeeping', 'maintenance', 'food_beverage', 'security', 'management', 'accounting', 'other') NOT NULL,
            hire_date DATE NOT NULL,
            employment_status ENUM('active', 'inactive', 'terminated', 'on_leave') DEFAULT 'active',
            hourly_rate DECIMAL(8,2) NOT NULL,
            overtime_rate DECIMAL(8,2),
            weekly_hours INT DEFAULT 40,
            salary_type ENUM('hourly', 'salary', 'commission') DEFAULT 'hourly',
            monthly_salary DECIMAL(10,2) NULL,
            emergency_contact_name VARCHAR(255),
            emergency_contact_phone VARCHAR(20),
            address TEXT,
            tax_id VARCHAR(50),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ";
    
    $connection->exec($employeesTableSql);
    echo "<p>✅ Created 'employees' table</p>";
    
    // Create time_clock table for tracking clock in/out
    $timeClockTableSql = "
        CREATE TABLE IF NOT EXISTS time_clock (
            id INT PRIMARY KEY AUTO_INCREMENT,
            employee_id INT NOT NULL,
            clock_in DATETIME NOT NULL,
            clock_out DATETIME NULL,
            break_start DATETIME NULL,
            break_end DATETIME NULL,
            total_break_minutes INT DEFAULT 0,
            total_hours DECIMAL(4,2) NULL,
            overtime_hours DECIMAL(4,2) DEFAULT 0,
            shift_type ENUM('morning', 'afternoon', 'evening', 'night', 'split', 'other') DEFAULT 'other',
            location VARCHAR(100) DEFAULT 'hotel',
            notes TEXT,
            approved_by INT NULL,
            status ENUM('active', 'completed', 'approved', 'rejected') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
            FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
        )
    ";
    
    $connection->exec($timeClockTableSql);
    echo "<p>✅ Created 'time_clock' table</p>";
    
    // Create payroll table for wage calculations
    $payrollTableSql = "
        CREATE TABLE IF NOT EXISTS payroll (
            id INT PRIMARY KEY AUTO_INCREMENT,
            employee_id INT NOT NULL,
            pay_period_start DATE NOT NULL,
            pay_period_end DATE NOT NULL,
            regular_hours DECIMAL(6,2) DEFAULT 0,
            overtime_hours DECIMAL(6,2) DEFAULT 0,
            total_hours DECIMAL(6,2) DEFAULT 0,
            regular_pay DECIMAL(10,2) DEFAULT 0,
            overtime_pay DECIMAL(10,2) DEFAULT 0,
            bonus DECIMAL(10,2) DEFAULT 0,
            deductions DECIMAL(10,2) DEFAULT 0,
            gross_pay DECIMAL(10,2) DEFAULT 0,
            tax_deductions DECIMAL(10,2) DEFAULT 0,
            net_pay DECIMAL(10,2) DEFAULT 0,
            pay_date DATE NULL,
            payment_method ENUM('cash', 'bank_transfer', 'check', 'other') DEFAULT 'bank_transfer',
            payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
            notes TEXT,
            created_by INT,
            approved_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
        )
    ";
    
    $connection->exec($payrollTableSql);
    echo "<p>✅ Created 'payroll' table</p>";
    
    // Create employee_schedules table for shift planning
    $schedulesTableSql = "
        CREATE TABLE IF NOT EXISTS employee_schedules (
            id INT PRIMARY KEY AUTO_INCREMENT,
            employee_id INT NOT NULL,
            schedule_date DATE NOT NULL,
            shift_start TIME NOT NULL,
            shift_end TIME NOT NULL,
            break_duration INT DEFAULT 30,
            position_assigned VARCHAR(100),
            location VARCHAR(100) DEFAULT 'hotel',
            schedule_status ENUM('scheduled', 'confirmed', 'completed', 'no_show', 'cancelled') DEFAULT 'scheduled',
            notes TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            UNIQUE KEY unique_employee_schedule (employee_id, schedule_date, shift_start)
        )
    ";
    
    $connection->exec($schedulesTableSql);
    echo "<p>✅ Created 'employee_schedules' table</p>";
    
    // Add indexes for better performance
    $indexQueries = [
        "CREATE INDEX idx_employee_clock_date ON time_clock(employee_id, clock_in)",
        "CREATE INDEX idx_payroll_period ON payroll(employee_id, pay_period_start, pay_period_end)",
        "CREATE INDEX idx_schedule_date ON employee_schedules(schedule_date, employee_id)",
        "CREATE INDEX idx_employee_status ON employees(employment_status)",
        "CREATE INDEX idx_time_clock_status ON time_clock(status)"
    ];
    
    foreach ($indexQueries as $indexQuery) {
        try {
            $connection->exec($indexQuery);
            echo "<p>✅ Added database index</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                echo "<p>⚠️ Index creation warning: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Insert sample employee data
    echo "<h3>Adding Sample Employees:</h3>";
    
    $sampleEmployees = [
        ['EMP001', 'John', 'Manager', 'john.manager@hotel.com', '+1-555-1001', 'manager', 'management', '2024-01-15', 25.00, 37.50, 40, 'hourly'],
        ['EMP002', 'Maria', 'Rodriguez', 'maria.rodriguez@hotel.com', '+1-555-1002', 'receptionist', 'front_desk', '2024-02-01', 18.00, 27.00, 40, 'hourly'],
        ['EMP003', 'David', 'Smith', 'david.smith@hotel.com', '+1-555-1003', 'housekeeper', 'housekeeping', '2024-01-20', 16.00, 24.00, 35, 'hourly'],
        ['EMP004', 'Sarah', 'Johnson', 'sarah.johnson@hotel.com', '+1-555-1004', 'maintenance', 'maintenance', '2024-03-01', 22.00, 33.00, 40, 'hourly'],
        ['EMP005', 'Michael', 'Brown', 'michael.brown@hotel.com', '+1-555-1005', 'waiter', 'food_beverage', '2024-02-15', 15.00, 22.50, 30, 'hourly']
    ];
    
    $stmt = $connection->prepare("
        INSERT INTO employees (employee_id, first_name, last_name, email, phone, position, department, hire_date, hourly_rate, overtime_rate, weekly_hours, salary_type) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($sampleEmployees as $employee) {
        $stmt->execute($employee);
    }
    echo "<p>✅ Added " . count($sampleEmployees) . " sample employees</p>";
    
    // Insert sample time clock entries
    echo "<h3>Adding Sample Time Clock Entries:</h3>";
    
    $sampleTimeEntries = [
        [1, '2025-09-25 08:00:00', '2025-09-25 17:00:00', 8.5, 0, 'morning', 'completed'],
        [2, '2025-09-25 09:00:00', '2025-09-25 18:00:00', 8.5, 0, 'morning', 'completed'],
        [3, '2025-09-25 06:00:00', '2025-09-25 14:00:00', 7.5, 0, 'morning', 'completed'],
        [4, '2025-09-25 10:00:00', '2025-09-25 19:00:00', 8.5, 0, 'afternoon', 'completed'],
        [5, '2025-09-25 17:00:00', '2025-09-25 23:00:00', 5.5, 0, 'evening', 'completed']
    ];
    
    $stmt = $connection->prepare("
        INSERT INTO time_clock (employee_id, clock_in, clock_out, total_hours, overtime_hours, shift_type, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($sampleTimeEntries as $entry) {
        $stmt->execute($entry);
    }
    echo "<p>✅ Added " . count($sampleTimeEntries) . " sample time clock entries</p>";
    
    // Insert sample schedules for next week
    echo "<h3>Adding Sample Employee Schedules:</h3>";
    
    $sampleSchedules = [
        [1, '2025-09-26', '08:00:00', '17:00:00', 60, 'Manager', 'scheduled'],
        [2, '2025-09-26', '09:00:00', '18:00:00', 30, 'Front Desk', 'scheduled'],
        [3, '2025-09-26', '06:00:00', '14:00:00', 30, 'Housekeeping', 'scheduled'],
        [4, '2025-09-26', '10:00:00', '19:00:00', 45, 'Maintenance', 'scheduled'],
        [5, '2025-09-26', '17:00:00', '23:00:00', 30, 'Restaurant', 'scheduled']
    ];
    
    $stmt = $connection->prepare("
        INSERT INTO employee_schedules (employee_id, schedule_date, shift_start, shift_end, break_duration, position_assigned, schedule_status) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($sampleSchedules as $schedule) {
        $stmt->execute($schedule);
    }
    echo "<p>✅ Added " . count($sampleSchedules) . " sample employee schedules</p>";
    
    echo "<p><strong>✅ Employee Time Tracking System setup completed!</strong></p>";
    echo "<p><a href='employee_management.php'>→ Go to Employee Management</a></p>";
    echo "<p><a href='time_clock.php'>→ Go to Time Clock</a></p>";
    echo "<p><a href='payroll_management.php'>→ Go to Payroll Management</a></p>";
    
} catch (PDOException $e) {
    echo "<p>❌ Error setting up employee system: " . $e->getMessage() . "</p>";
}
?>