USE hotel_booking_system;
ALTER TABLE investor_activity_log 
ADD COLUMN action VARCHAR(100) AFTER investor_id,
ADD COLUMN details TEXT AFTER action,
ADD COLUMN ip_address VARCHAR(45) AFTER details;
