-- Room Management Database Extension (Corrected)
-- Add description, features, and image_path columns to rooms table
USE hotel_booking_system;

-- Add new columns to rooms table for enhanced room management
-- These will be added at the end of the table structure

-- Check if columns exist before adding them
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'rooms' 
     AND table_schema = 'hotel_booking_system' 
     AND column_name = 'description') > 0,
    'SELECT ''description column already exists'' as message;',
    'ALTER TABLE rooms ADD COLUMN description TEXT;'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'rooms' 
     AND table_schema = 'hotel_booking_system' 
     AND column_name = 'features') > 0,
    'SELECT ''features column already exists'' as message;',
    'ALTER TABLE rooms ADD COLUMN features TEXT;'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'rooms' 
     AND table_schema = 'hotel_booking_system' 
     AND column_name = 'image_path') > 0,
    'SELECT ''image_path column already exists'' as message;',
    'ALTER TABLE rooms ADD COLUMN image_path VARCHAR(255);'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;