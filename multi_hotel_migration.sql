-- Safe migration: Add columns only if they don't exist

-- Add current_hotel_id to users table (which hotel they're currently managing/viewing)
SET @query = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'hotel_booking_system' 
     AND TABLE_NAME = 'users' 
     AND COLUMN_NAME = 'current_hotel_id') = 0,
    'ALTER TABLE users ADD COLUMN current_hotel_id INT DEFAULT 1 AFTER id, ADD INDEX idx_current_hotel_id (current_hotel_id)',
    'SELECT "current_hotel_id already exists in users"'
));
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add owner_id to hotel_info table (which user owns this hotel)
SET @query = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'hotel_booking_system' 
     AND TABLE_NAME = 'hotel_info' 
     AND COLUMN_NAME = 'owner_id') = 0,
    'ALTER TABLE hotel_info ADD COLUMN owner_id INT DEFAULT NULL AFTER id, ADD INDEX idx_owner_id (owner_id)',
    'SELECT "owner_id already exists in hotel_info"'
));
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add hotel_id to employees table (which hotel they work at - permanent)
SET @query = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'hotel_booking_system' 
     AND TABLE_NAME = 'employees' 
     AND COLUMN_NAME = 'hotel_id') = 0,
    'ALTER TABLE employees ADD COLUMN hotel_id INT DEFAULT 1 AFTER id, ADD INDEX idx_emp_hotel_id (hotel_id)',
    'SELECT "hotel_id already exists in employees"'
));
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add hotel_id to time_clock table
SET @query = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'hotel_booking_system' 
     AND TABLE_NAME = 'time_clock' 
     AND COLUMN_NAME = 'hotel_id') = 0,
    'ALTER TABLE time_clock ADD COLUMN hotel_id INT DEFAULT 1 AFTER id, ADD INDEX idx_tc_hotel_id (hotel_id)',
    'SELECT "hotel_id already exists in time_clock"'
));
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add hotel_id to bookings table
SET @query = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'hotel_booking_system' 
     AND TABLE_NAME = 'bookings' 
     AND COLUMN_NAME = 'hotel_id') = 0,
    'ALTER TABLE bookings ADD COLUMN hotel_id INT DEFAULT 1 AFTER id, ADD INDEX idx_bookings_hotel_id (hotel_id)',
    'SELECT "hotel_id already exists in bookings"'
));
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add hotel_id to rooms table
SET @query = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'hotel_booking_system' 
     AND TABLE_NAME = 'rooms' 
     AND COLUMN_NAME = 'hotel_id') = 0,
    'ALTER TABLE rooms ADD COLUMN hotel_id INT DEFAULT 1 AFTER id, ADD INDEX idx_rooms_hotel_id (hotel_id)',
    'SELECT "hotel_id already exists in rooms"'
));
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure we have at least one hotel in hotel_info
INSERT IGNORE INTO hotel_info (id, hotel_name, timezone, country) 
VALUES (1, 'Main Hotel', 'America/Lima', 'Peru');

-- Set all existing admin users as owners of hotel 1
UPDATE users SET current_hotel_id = 1 WHERE user_role IN ('admin', 'manager');
UPDATE hotel_info SET owner_id = (SELECT id FROM users WHERE user_role = 'admin' LIMIT 1) WHERE id = 1 AND owner_id IS NULL;
