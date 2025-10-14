-- Room Photos Migration from XAMPP to VPS
-- Maps photos to correct room IDs based on room numbers

-- Room 101 (id=1 on VPS) - Executive Suite
INSERT INTO room_photos (room_id, photo_path, photo_name, is_primary, upload_date) VALUES 
(1, 'uploads/rooms/68d84a10b9fd6_executive room.jpg', '68d84a10b9fd6_executive room.jpg', 1, '2025-09-27 15:33:20');

-- Room 102 (id=2 on VPS) - Standard Double  
INSERT INTO room_photos (room_id, photo_path, photo_name, is_primary, upload_date) VALUES 
(2, 'uploads/rooms/68d9b6cdbb5cc_574522699.jpg', '68d9b6cdbb5cc_574522699.jpg', 1, '2025-09-28 17:29:33'),
(2, 'uploads/rooms/68df187d66212_2025-02-24.jpg', '68df187d66212_2025-02-24.jpg', 0, '2025-10-02 19:27:41'),
(2, 'uploads/rooms/68df188522ea4_2025-02-24.jpg', '68df188522ea4_2025-02-24.jpg', 0, '2025-10-02 19:27:49');

-- Room 103 (id=3 on VPS) - Deluxe Suite
INSERT INTO room_photos (room_id, photo_path, photo_name, is_primary, upload_date) VALUES 
(3, 'uploads/rooms/68d9b8889203a_0224812000ejlx6yi846A_Z_1280_720_R5 1.jpg', '68d9b8889203a_0224812000ejlx6yi846A_Z_1280_720_R5 1.jpg', 1, '2025-09-28 17:36:56');

-- Room 104 (id=4 on VPS) - Family Room
INSERT INTO room_photos (room_id, photo_path, photo_name, is_primary, upload_date) VALUES 
(4, 'uploads/rooms/68d9b98681eb8_2025-02-24.jpg', '68d9b98681eb8_2025-02-24.jpg', 1, '2025-09-28 17:41:10');

-- Room 105 (id=5 on VPS) - Presidential Suite 
INSERT INTO room_photos (room_id, photo_path, photo_name, is_primary, upload_date) VALUES 
(5, 'uploads/rooms/68dabfc79cf24_executive room (1).jpg', '68dabfc79cf24_executive room (1).jpg', 0, '2025-09-29 12:20:07');

-- Room 106 (id=6 on VPS) - Twin Beds
INSERT INTO room_photos (room_id, photo_path, photo_name, is_primary, upload_date) VALUES 
(6, 'uploads/rooms/68dabffd710d1_executive room (1).jpg', '68dabffd710d1_executive room (1).jpg', 0, '2025-09-29 12:21:01');

-- Room 107 (id=7 on VPS) - Twin Beds
INSERT INTO room_photos (room_id, photo_path, photo_name, is_primary, upload_date) VALUES 
(7, 'uploads/rooms/68dac06c484b5_Standar Double Bed .jpg', '68dac06c484b5_Standar Double Bed .jpg', 0, '2025-09-29 12:22:52');

-- Room 108 (id=8 on VPS) - Deluxe Queen (mapped from old room 108)
INSERT INTO room_photos (room_id, photo_path, photo_name, is_primary, upload_date) VALUES 
(8, 'uploads/rooms/68de02796c0fc_Standar Double Bed .jpg', '68de02796c0fc_Standar Double Bed .jpg', 0, '2025-10-01 23:41:29'),
(8, 'uploads/rooms/68de032f00a05_Standar Double Bed .jpg', '68de032f00a05_Standar Double Bed .jpg', 0, '2025-10-01 23:44:31');

-- Set proper permissions for uploaded files
-- MANUAL STEP: Set apache ownership on VPS with: chown -R www-data:www-data /var/www/html/uploads/rooms

-- Verify the migration
SELECT 'ROOM PHOTOS MIGRATION COMPLETE' as status;
SELECT r.room_number, r.room_type, rp.photo_name, rp.is_primary 
FROM rooms r 
LEFT JOIN room_photos rp ON r.id = rp.room_id 
ORDER BY r.id, rp.is_primary DESC;