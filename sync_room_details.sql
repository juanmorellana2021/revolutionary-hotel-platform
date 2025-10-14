-- Add missing room fields to VPS and sync room details from XAMPP
ALTER TABLE rooms ADD COLUMN room_status ENUM('clean','dirty','maintenance','out_of_order') DEFAULT 'clean';
ALTER TABLE rooms ADD COLUMN amenities TEXT DEFAULT NULL;
ALTER TABLE rooms ADD COLUMN extra_bed_available TINYINT(1) DEFAULT 0;
ALTER TABLE rooms ADD COLUMN extra_bed_price DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE rooms ADD COLUMN is_available TINYINT(1) DEFAULT 1;
ALTER TABLE rooms ADD COLUMN max_occupancy INT DEFAULT 1;

-- Update Room 101 with XAMPP details
UPDATE rooms SET 
    room_status = 'clean',
    amenities = 'Free fast Wifi, Desk, optional extra bed, view the Mountain View to the garden, private bathroom and shower fast',
    extra_bed_available = 1,
    extra_bed_price = 15.00,
    is_available = 1,
    max_occupancy = 2,
    capacity = 2
WHERE room_number = '101';

-- Update Room 102 with XAMPP details  
UPDATE rooms SET 
    room_status = 'dirty',
    amenities = 'Free WiFi, Air Conditioning, TV, Private Bathroom',
    extra_bed_available = 0,
    extra_bed_price = 0.00,
    is_available = 1,
    max_occupancy = 1,
    capacity = 1
WHERE room_number = '102';

-- Set reasonable defaults for other rooms
UPDATE rooms SET 
    room_status = 'clean',
    is_available = 1,
    max_occupancy = 2
WHERE room_number NOT IN ('101', '102');

-- Verify the room details
SELECT 'ROOM DETAILS SYNCHRONIZED' as status;
SELECT 
    room_number, 
    room_type, 
    price_per_night, 
    capacity,
    max_occupancy,
    room_status,
    extra_bed_available,
    extra_bed_price,
    is_available
FROM rooms 
ORDER BY CAST(SUBSTRING(room_number, 1) AS UNSIGNED);