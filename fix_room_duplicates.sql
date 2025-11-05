-- Fix duplicate room numbers
UPDATE rooms SET room_number = '108' WHERE id = 8;
UPDATE rooms SET room_number = '109' WHERE id = 9;  
UPDATE rooms SET room_number = '110' WHERE id = 10;
UPDATE rooms SET room_number = '111' WHERE id = 11;

-- Verify the changes
SELECT id, room_number, room_type FROM rooms ORDER BY id;