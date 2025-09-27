-- Room Management Database Extension
-- Add description and features columns to rooms table
USE hotel_booking_system;

-- Add new columns to rooms table for enhanced room management
ALTER TABLE rooms 
ADD COLUMN description TEXT AFTER amenities,
ADD COLUMN features TEXT AFTER description,
ADD COLUMN image_path VARCHAR(255) AFTER features;