-- Fix missing database fields causing calendar booking creation errors

-- Add missing fields to users table
ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL;
ALTER TABLE users ADD COLUMN terms_accepted TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN terms_accepted_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE users ADD COLUMN terms_version VARCHAR(10) DEFAULT '1.0';

-- Create booking_guests table if it doesn't exist
CREATE TABLE IF NOT EXISTS booking_guests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    guest_name VARCHAR(255) NOT NULL,
    guest_email VARCHAR(255) DEFAULT NULL,
    guest_phone VARCHAR(50) DEFAULT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Verify the changes
SELECT 'DATABASE FIELDS ADDED FOR BOOKING SYSTEM' as status;

-- Check users table now has phone field
SELECT 'Users table now has:' as info;
SHOW COLUMNS FROM users LIKE 'phone';

-- Check rooms table has price field  
SELECT 'Rooms table price field:' as info;
SHOW COLUMNS FROM rooms LIKE 'price';

-- Check booking_guests table exists
SELECT 'Booking guests table:' as info;
SELECT COUNT(*) as guest_records FROM booking_guests;