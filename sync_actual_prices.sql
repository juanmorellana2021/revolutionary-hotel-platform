-- Add price field and sync the ACTUAL room prices from XAMPP
-- The prices you set were in the 'price' field, not 'price_per_night'

ALTER TABLE rooms ADD COLUMN price DECIMAL(10,2) DEFAULT 0.00;

-- Sync the actual prices from XAMPP 'price' field
UPDATE rooms SET price = 35.00 WHERE room_number = '101';
UPDATE rooms SET price = 32.00 WHERE room_number = '102';
UPDATE rooms SET price = 40.00 WHERE room_number = '103';
UPDATE rooms SET price = 28.00 WHERE room_number = '104';
UPDATE rooms SET price = 28.00 WHERE room_number = '105';
UPDATE rooms SET price = 26.00 WHERE room_number = '106';
UPDATE rooms SET price = 28.00 WHERE room_number = '107';
UPDATE rooms SET price = 28.00 WHERE room_number = '108';
UPDATE rooms SET price = 28.00 WHERE room_number = '109';
UPDATE rooms SET price = 30.00 WHERE room_number = '110';
UPDATE rooms SET price = 35.00 WHERE room_number = '111';

-- Also update price_per_night to match the price field for consistency
UPDATE rooms SET price_per_night = price WHERE price > 0;

-- Verify the correct prices are now set
SELECT 'ACTUAL ROOM PRICES FROM XAMPP RESTORED' as status;
SELECT 
    room_number, 
    room_type, 
    price as actual_price,
    price_per_night,
    capacity,
    max_occupancy
FROM rooms 
ORDER BY CAST(SUBSTRING(room_number, 1) AS UNSIGNED);