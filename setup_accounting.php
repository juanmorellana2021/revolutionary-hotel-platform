<?php
// Database setup script for accounting system
require_once 'includes/classes.php';

try {
    $db = new Database();
    $connection = $db->getConnection();
    
    echo "<h2>Setting up Accounting System Database</h2>";
    
    // Create income table
    $incomeTableSql = "
        CREATE TABLE IF NOT EXISTS income (
            id INT PRIMARY KEY AUTO_INCREMENT,
            booking_id INT NULL,
            income_type ENUM('room_booking', 'extra_bed', 'food_beverage', 'laundry', 'spa', 'parking', 'wifi', 'minibar', 'conference', 'other') NOT NULL,
            description TEXT,
            amount DECIMAL(10,2) NOT NULL,
            payment_method ENUM('cash', 'credit_card', 'debit_card', 'bank_transfer', 'check', 'online', 'booking_com', 'other') DEFAULT 'cash',
            payment_status ENUM('pending', 'paid', 'refunded', 'cancelled') DEFAULT 'paid',
            transaction_date DATE NOT NULL,
            guest_name VARCHAR(255),
            guest_email VARCHAR(255),
            guest_phone VARCHAR(20),
            created_by INT,
            notes TEXT,
            receipt_number VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        )
    ";
    
    $connection->exec($incomeTableSql);
    echo "<p>✅ Created 'income' table</p>";
    
    // Create expenses table
    $expensesTableSql = "
        CREATE TABLE IF NOT EXISTS expenses (
            id INT PRIMARY KEY AUTO_INCREMENT,
            expense_category ENUM('utilities', 'maintenance', 'supplies', 'food_beverage', 'staff_salary', 'marketing', 'insurance', 'taxes', 'cleaning', 'laundry', 'internet', 'telephone', 'repairs', 'equipment', 'office_supplies', 'transportation', 'legal', 'accounting', 'other') NOT NULL,
            description TEXT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method ENUM('cash', 'credit_card', 'debit_card', 'bank_transfer', 'check', 'other') DEFAULT 'cash',
            vendor_name VARCHAR(255),
            vendor_contact VARCHAR(255),
            invoice_number VARCHAR(100),
            expense_date DATE NOT NULL,
            is_recurring BOOLEAN DEFAULT FALSE,
            recurring_frequency ENUM('weekly', 'monthly', 'quarterly', 'yearly') NULL,
            tax_deductible BOOLEAN DEFAULT FALSE,
            next_due_date DATE NULL,
            paid_by INT,
            approved_by INT NULL,
            status ENUM('pending', 'approved', 'paid', 'cancelled') DEFAULT 'paid',
            receipt_url VARCHAR(500),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (paid_by) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
        )
    ";
    
    $connection->exec($expensesTableSql);
    echo "<p>✅ Created 'expenses' table</p>";
    
    // Create financial_reports table for cached reports
    $reportsTableSql = "
        CREATE TABLE IF NOT EXISTS financial_reports (
            id INT PRIMARY KEY AUTO_INCREMENT,
            report_type ENUM('daily', 'weekly', 'monthly', 'yearly', 'custom') NOT NULL,
            report_date DATE NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            total_income DECIMAL(12,2) DEFAULT 0,
            total_expenses DECIMAL(12,2) DEFAULT 0,
            net_profit DECIMAL(12,2) DEFAULT 0,
            room_revenue DECIMAL(12,2) DEFAULT 0,
            extra_services_revenue DECIMAL(12,2) DEFAULT 0,
            occupancy_rate DECIMAL(5,2) DEFAULT 0,
            average_daily_rate DECIMAL(8,2) DEFAULT 0,
            report_data JSON,
            generated_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
            UNIQUE KEY unique_report (report_type, report_date)
        )
    ";
    
    $connection->exec($reportsTableSql);
    echo "<p>✅ Created 'financial_reports' table</p>";
    
    // Add accounting-related columns to bookings table
    $bookingColumns = [
        'payment_status' => "ENUM('pending', 'partial', 'paid', 'refunded') DEFAULT 'pending'",
        'payment_method' => "VARCHAR(50) NULL",
        'paid_amount' => "DECIMAL(10,2) DEFAULT 0.00",
        'discount_amount' => "DECIMAL(10,2) DEFAULT 0.00",
        'tax_amount' => "DECIMAL(10,2) DEFAULT 0.00",
        'service_charge' => "DECIMAL(10,2) DEFAULT 0.00"
    ];
    
    foreach ($bookingColumns as $column => $definition) {
        $stmt = $connection->prepare("SHOW COLUMNS FROM bookings LIKE ?");
        $stmt->execute([$column]);
        $exists = $stmt->fetch();
        
        if (!$exists) {
            $sql = "ALTER TABLE bookings ADD COLUMN {$column} {$definition}";
            $connection->exec($sql);
            echo "<p>✅ Added '{$column}' column to bookings table</p>";
        } else {
            echo "<p>ℹ️ Column '{$column}' already exists in bookings table</p>";
        }
    }
    
    // Create indexes for better performance
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_income_date ON income(transaction_date)",
        "CREATE INDEX IF NOT EXISTS idx_income_type ON income(income_type)",
        "CREATE INDEX IF NOT EXISTS idx_expenses_date ON expenses(expense_date)",
        "CREATE INDEX IF NOT EXISTS idx_expenses_category ON expenses(expense_category)",
        "CREATE INDEX IF NOT EXISTS idx_reports_date ON financial_reports(report_date, report_type)"
    ];
    
    foreach ($indexes as $indexSql) {
        try {
            $connection->exec($indexSql);
            echo "<p>✅ Added database index</p>";
        } catch (Exception $e) {
            echo "<p>ℹ️ Index may already exist</p>";
        }
    }
    
    // Insert sample data for testing
    echo "<h3>Adding Sample Data:</h3>";
    
    // Sample income entries
    $sampleIncome = [
        ['room_booking', 'Room 101 - Standard Double', 120.00, 'credit_card', '2024-12-20', 'John Smith', 'john.smith@email.com', '+1-555-0123'],
        ['extra_bed', 'Extra bed for Room 205', 25.00, 'cash', '2024-12-20', 'Maria Garcia', 'maria.garcia@email.com', '+1-555-0124'],
        ['food_beverage', 'Restaurant order - Room service', 45.50, 'credit_card', '2024-12-21', 'David Johnson', 'david.johnson@email.com', '+1-555-0125'],
        ['parking', 'Parking fee', 15.00, 'cash', '2024-12-21', 'Sarah Wilson', 'sarah.wilson@email.com', '+1-555-0126'],
        ['laundry', 'Laundry service', 30.00, 'debit_card', '2024-12-22', 'Michael Brown', 'michael.brown@email.com', '+1-555-0127']
    ];
    
    $stmt = $connection->prepare("
        INSERT INTO income (income_type, description, amount, payment_method, transaction_date, guest_name, guest_email, guest_phone) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($sampleIncome as $income) {
        $stmt->execute($income);
    }
    echo "<p>✅ Added sample income entries</p>";
    
    // Sample expense entries
    $sampleExpenses = [
        ['utilities', 'Electricity bill - December', 450.00, 'bank_transfer', 'Electric Company', 'support@electriccompany.com', '2024-12-15', 1],
        ['cleaning', 'Cleaning supplies', 85.75, 'cash', 'Clean Supply Co', 'info@cleansupply.com', '2024-12-18', 0],
        ['maintenance', 'Plumbing repair - Room 304', 180.00, 'check', 'Fix-It Services', 'service@fixitservices.com', '2024-12-19', 1],
        ['food_beverage', 'Restaurant inventory', 320.50, 'credit_card', 'Food Distributors Inc', 'orders@fooddist.com', '2024-12-20', 1],
        ['internet', 'WiFi service - Monthly', 95.00, 'bank_transfer', 'TechNet ISP', 'support@technetisp.com', '2024-12-21', 1]
    ];
    
    $stmt = $connection->prepare("
        INSERT INTO expenses (expense_category, description, amount, payment_method, vendor_name, vendor_contact, expense_date, tax_deductible) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($sampleExpenses as $expense) {
        $stmt->execute($expense);
    }
    echo "<p>✅ Added sample expense entries</p>";
    
    echo "<p><strong>✅ Accounting system database setup completed!</strong></p>";
    echo "<p><a href='accounting_dashboard.php'>→ Go to Accounting Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>