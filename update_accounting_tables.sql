-- Update accounting tables to match the class expectations
-- Update the income table (only add missing columns)
ALTER TABLE income 
ADD COLUMN income_type VARCHAR(50) DEFAULT 'room_booking',
ADD COLUMN created_by INT NULL,
ADD COLUMN notes TEXT NULL,
ADD COLUMN receipt_number VARCHAR(50) NULL,
ADD COLUMN income_status VARCHAR(20) DEFAULT 'received';

-- Update the expenses table (only add missing columns)
ALTER TABLE expenses 
ADD COLUMN payment_method VARCHAR(50) DEFAULT 'cash',
ADD COLUMN vendor_name VARCHAR(100) NULL,
ADD COLUMN vendor_contact VARCHAR(100) NULL,
ADD COLUMN invoice_number VARCHAR(50) NULL,
ADD COLUMN is_recurring BOOLEAN DEFAULT FALSE,
ADD COLUMN recurring_frequency VARCHAR(20) NULL,
ADD COLUMN next_due_date DATE NULL,
ADD COLUMN paid_by INT NULL,
ADD COLUMN status VARCHAR(20) DEFAULT 'paid',
ADD COLUMN approved_by INT NULL,
ADD COLUMN approval_date DATE NULL,
ADD COLUMN tax_deductible DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN notes TEXT NULL;

-- Rename category to expense_category for consistency
ALTER TABLE expenses CHANGE COLUMN category expense_category VARCHAR(100);

-- Rename transaction_date to expense_date for consistency
ALTER TABLE expenses CHANGE COLUMN transaction_date expense_date DATE NOT NULL;

-- Add foreign key constraints
ALTER TABLE income 
ADD FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
ADD FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE expenses 
ADD FOREIGN KEY (paid_by) REFERENCES users(id) ON DELETE SET NULL,
ADD FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL;