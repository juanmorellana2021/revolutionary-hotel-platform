-- Add hotel_id to rooms table to link rooms with hotels
-- This allows multi-hotel support on the platform

USE hotel_booking_system;

-- Step 1: Add hotel_id column to rooms table
ALTER TABLE rooms 
ADD COLUMN hotel_id INT(11) DEFAULT NULL AFTER id,
ADD INDEX idx_hotel_id (hotel_id),
ADD FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE;

-- Step 2: Link existing rooms to Samay Wasi Casa de Paz (assuming it's hotel id=1)
-- First, let's verify which hotel is Samay Wasi
-- SELECT id, name FROM hotels WHERE name LIKE '%Samay%';

-- Update all existing rooms to belong to Samay Wasi (hotel_id = 1)
-- Adjust the hotel_id if Samay Wasi has a different ID
UPDATE rooms SET hotel_id = 1 WHERE hotel_id IS NULL;

-- Step 3: Make hotel_id required for future inserts
ALTER TABLE rooms MODIFY hotel_id INT(11) NOT NULL;

-- Verify the changes
SELECT r.id, r.room_number, r.room_type, r.price, h.name as hotel_name
FROM rooms r
LEFT JOIN hotels h ON r.hotel_id = h.id
LIMIT 10;
