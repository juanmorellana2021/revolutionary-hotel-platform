-- Sync room prices from XAMPP to VPS
-- Based on XAMPP data: Room 101 = $89.99, Room 102 = $129.99, others = $0.00

UPDATE rooms SET price_per_night = 89.99 WHERE room_number = '101';
UPDATE rooms SET price_per_night = 129.99 WHERE room_number = '102';

-- Set the rest to 0.00 as they are in XAMPP (not yet priced)
UPDATE rooms SET price_per_night = 0.00 WHERE room_number IN ('103', '104', '105', '106', '107', '108', '109', '110', '111');

-- Also sync the room types to match XAMPP exactly
UPDATE rooms SET room_type = 'Executive Suite' WHERE room_number = '101';
UPDATE rooms SET room_type = 'Standard Double' WHERE room_number = '102';
UPDATE rooms SET room_type = 'Deluxe Queen' WHERE room_number = '103';
UPDATE rooms SET room_type = 'Twin Beds' WHERE room_number = '104';
UPDATE rooms SET room_type = 'Deluxe Twin' WHERE room_number = '105';
UPDATE rooms SET room_type = 'Twin Beds' WHERE room_number = '106';
UPDATE rooms SET room_type = 'Twin Beds' WHERE room_number = '107';
UPDATE rooms SET room_type = 'Deluxe Twin' WHERE room_number = '108';
UPDATE rooms SET room_type = 'Standard Double' WHERE room_number = '109';
UPDATE rooms SET room_type = 'Standard Double' WHERE room_number = '110';
UPDATE rooms SET room_type = 'Deluxe Queen' WHERE room_number = '111';

-- Verify the sync
SELECT 'ROOM PRICES SYNCHRONIZED' as status;
SELECT room_number, room_type, price_per_night, capacity 
FROM rooms 
ORDER BY CAST(SUBSTRING(room_number, 1) AS UNSIGNED);