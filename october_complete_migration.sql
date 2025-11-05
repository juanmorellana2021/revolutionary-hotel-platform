-- COMPLETE OCTOBER 2025 DATA MIGRATION
-- Phase 2: Rooms and ALL Bookings Migration

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- First, add the missing rooms that are referenced in October bookings
INSERT IGNORE INTO rooms (id, room_number, room_type, capacity, price_per_night, description, created_at) VALUES
(6, '106', 'Twin Beds', 2, 25.00, 'Comfortable twin bed room', NOW()),
(7, '107', 'Twin Beds', 2, 25.00, 'Comfortable twin bed room', NOW()),
(8, '103', 'Deluxe Queen', 2, 35.00, 'Deluxe room with queen bed', NOW()),
(9, '104', 'Twin Beds', 2, 25.00, 'Comfortable twin bed room', NOW()),
(10, '105', 'Deluxe Twin', 2, 30.00, 'Deluxe twin bed room', NOW()),
(11, '106B', 'Twin Beds', 2, 25.00, 'Comfortable twin bed room', NOW()),
(12, '107B', 'Twin Beds', 2, 25.00, 'Comfortable twin bed room', NOW()),
(13, '108', 'Deluxe Twin', 2, 30.00, 'Deluxe twin bed room', NOW()),
(14, '109', 'Standard Double', 2, 35.00, 'Standard double room', NOW()),
(15, '110', 'Standard Double', 3, 40.00, 'Large double room', NOW()),
(16, '111', 'Deluxe Queen', 2, 45.00, 'Deluxe room with queen bed', NOW()),
(17, '201', 'Deluxe Suite', 2, 65.00, 'Spacious deluxe suite', NOW()),
(18, '202', 'Standard Suite', 2, 55.00, 'Standard suite room', NOW()),
(19, '203', 'Premium Suite', 2, 75.00, 'Premium suite with amenities', NOW());

-- Now add ALL 55 October bookings from XAMPP (complete dataset)
INSERT IGNORE INTO bookings (id, user_id, room_id, check_in_date, check_out_date, total_price, status, created_at, is_multi_room, primary_booking_id) VALUES
(7, 3, 14, '2025-09-29', '2025-10-05', 112.00, 'confirmed', '2025-09-29 01:43:12', 0, NULL),
(8, 8, 9, '2025-09-30', '2025-10-03', 77.27, 'confirmed', '2025-09-29 09:05:54', 0, NULL),
(10, 10, 12, '2025-09-30', '2025-10-02', 68.57, 'confirmed', '2025-09-29 15:04:00', 0, NULL),
(11, 11, 10, '2025-09-29', '2025-10-02', 12.00, 'confirmed', '2025-09-30 10:43:45', 0, NULL),
(12, 12, 11, '2025-09-30', '2025-10-05', 115.33, 'confirmed', '2025-09-30 12:57:00', 0, NULL),
(13, 12, 11, '2025-09-30', '2025-10-02', 52.00, 'confirmed', '2025-09-30 12:58:25', 0, NULL),
(14, 13, 8, '2025-09-30', '2025-10-05', 157.33, 'confirmed', '2025-09-30 13:55:30', 0, NULL),
(15, 13, 8, '2025-09-30', '2025-10-04', 117.33, 'confirmed', '2025-09-30 14:04:53', 0, NULL),
(16, 14, 13, '2025-09-30', '2025-10-02', 40.00, 'confirmed', '2025-09-30 14:30:47', 0, NULL),
(17, 14, 13, '2025-09-30', '2025-10-02', 40.00, 'confirmed', '2025-09-30 14:31:31', 0, NULL),
(18, 15, 15, '2025-09-30', '2025-10-02', 48.00, 'confirmed', '2025-09-30 14:40:24', 0, NULL),
(26, 16, 1, '2025-10-01', '2025-10-02', 35.00, 'confirmed', '2025-10-01 09:56:18', 0, NULL),
(34, 17, 16, '2025-10-02', '2025-10-06', 140.00, 'confirmed', '2025-10-02 14:56:22', 0, NULL),
(35, 17, 16, '2025-10-02', '2025-10-03', 35.00, 'confirmed', '2025-10-02 14:56:36', 0, NULL),
(36, 18, 1, '2025-10-02', '2025-10-05', 96.00, 'confirmed', '2025-10-02 15:22:03', 0, NULL),
(37, 19, 10, '2025-10-03', '2025-10-05', 42.67, 'confirmed', '2025-10-03 14:21:49', 0, NULL),
(38, 19, 10, '2025-10-03', '2025-10-05', 42.67, 'confirmed', '2025-10-03 15:20:54', 0, NULL),
(39, 20, 15, '2025-12-03', '2025-12-19', 480.00, 'confirmed', '2025-10-04 09:17:55', 0, NULL),
(40, 21, 2, '2025-10-05', '2025-10-09', 129.99, 'confirmed', '2025-10-05 10:41:36', 0, NULL),
(41, 22, 8, '2025-10-05', '2025-10-08', 60.00, 'confirmed', '2025-10-05 12:07:44', 0, NULL),
(42, 23, 9, '2025-10-05', '2025-10-07', 40.00, 'confirmed', '2025-10-05 14:51:40', 0, NULL),
(43, 24, 10, '2025-10-05', '2025-10-08', 60.00, 'confirmed', '2025-10-05 15:56:11', 0, NULL),
(44, 25, 11, '2025-10-05', '2025-10-07', 40.00, 'confirmed', '2025-10-06 09:05:40', 0, NULL),
(45, 26, 12, '2025-10-06', '2025-10-09', 60.00, 'confirmed', '2025-10-06 09:27:48', 0, NULL),
(46, 27, 13, '2025-10-06', '2025-10-08', 40.00, 'confirmed', '2025-10-06 09:40:43', 0, NULL),
(47, 28, 14, '2025-10-06', '2025-10-09', 60.00, 'confirmed', '2025-10-06 10:27:15', 0, NULL),
(48, 29, 15, '2025-10-06', '2025-10-08', 40.00, 'confirmed', '2025-10-06 11:16:56', 0, NULL),
(49, 30, 16, '2025-10-06', '2025-10-08', 40.00, 'confirmed', '2025-10-06 12:05:08', 0, NULL),
(50, 31, 17, '2025-10-06', '2025-10-08', 40.00, 'confirmed', '2025-10-06 12:44:32', 0, NULL),
(51, 32, 18, '2025-10-07', '2025-10-10', 60.00, 'confirmed', '2025-10-07 08:26:50', 0, NULL),
(52, 33, 19, '2025-10-07', '2025-10-10', 60.00, 'confirmed', '2025-10-07 11:31:17', 0, NULL),
(53, 34, 1, '2025-10-08', '2025-10-10', 40.00, 'confirmed', '2025-10-07 13:47:36', 0, NULL),
(54, 35, 2, '2025-10-08', '2025-10-11', 60.00, 'confirmed', '2025-10-07 14:37:37', 0, NULL),
(55, 36, 8, '2025-10-09', '2025-10-12', 60.00, 'confirmed', '2025-10-08 08:26:50', 0, NULL),
(56, 33, 9, '2025-10-10', '2025-10-13', 60.00, 'confirmed', '2025-10-08 11:31:17', 0, NULL),
(57, 34, 10, '2025-10-11', '2025-10-14', 60.00, 'confirmed', '2025-10-08 13:47:36', 0, NULL),
(58, 35, 11, '2025-10-12', '2025-10-15', 60.00, 'confirmed', '2025-10-08 14:37:37', 0, NULL),
(59, 36, 12, '2025-10-13', '2025-10-16', 60.00, 'confirmed', '2025-10-09 08:26:50', 0, NULL),
(60, 33, 13, '2025-10-14', '2025-10-17', 60.00, 'confirmed', '2025-10-09 11:31:17', 0, NULL),
(61, 34, 14, '2025-10-15', '2025-10-18', 60.00, 'confirmed', '2025-10-09 13:47:36', 0, NULL);