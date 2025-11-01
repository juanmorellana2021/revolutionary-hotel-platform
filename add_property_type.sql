-- Add property_type column to hotel_info table
ALTER TABLE hotel_info ADD COLUMN property_type VARCHAR(50) DEFAULT 'hotel' AFTER hotel_name;
