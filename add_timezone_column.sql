-- Add timezone column to hotel_info table
ALTER TABLE hotel_info ADD COLUMN timezone VARCHAR(50) DEFAULT 'America/Lima' AFTER country;

-- Update existing records to use Peru timezone
UPDATE hotel_info SET timezone = 'America/Lima' WHERE timezone IS NULL OR timezone = '';
