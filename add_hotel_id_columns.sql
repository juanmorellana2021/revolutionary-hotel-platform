-- Add current_hotel_id to users table (which hotel they're currently managing/viewing)
ALTER TABLE users ADD COLUMN current_hotel_id INT DEFAULT 1 AFTER id;
ALTER TABLE users ADD INDEX idx_current_hotel_id (current_hotel_id);

-- Add owner_id to hotel_info table (which user owns this hotel)
ALTER TABLE hotel_info ADD COLUMN owner_id INT DEFAULT NULL AFTER id;
ALTER TABLE hotel_info ADD INDEX idx_owner_id (owner_id);

-- Add hotel_id to employees table
ALTER TABLE employees ADD COLUMN hotel_id INT DEFAULT 1 AFTER id;
ALTER TABLE employees ADD INDEX idx_hotel_id (hotel_id);

-- Add hotel_id to time_clock table
ALTER TABLE time_clock ADD COLUMN hotel_id INT DEFAULT 1 AFTER id;
ALTER TABLE time_clock ADD INDEX idx_hotel_id (hotel_id);

-- Add hotel_id to bookings table if it doesn't exist
ALTER TABLE bookings ADD COLUMN hotel_id INT DEFAULT 1 AFTER id;
ALTER TABLE bookings ADD INDEX idx_bookings_hotel_id (hotel_id);

-- Add hotel_id to rooms table if it doesn't exist
ALTER TABLE rooms ADD COLUMN hotel_id INT DEFAULT 1 AFTER id;
ALTER TABLE rooms ADD INDEX idx_hotel_id (hotel_id);

-- Ensure we have at least one hotel in hotel_info
INSERT IGNORE INTO hotel_info (id, hotel_name, timezone, country) 
VALUES (1, 'Main Hotel', 'America/Lima', 'Peru');

-- Add foreign key constraints (optional, for data integrity)
-- ALTER TABLE users ADD FOREIGN KEY (hotel_id) REFERENCES hotel_info(id);
-- ALTER TABLE employees ADD FOREIGN KEY (hotel_id) REFERENCES hotel_info(id);
