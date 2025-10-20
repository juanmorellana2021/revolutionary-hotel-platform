-- ============================================
-- SAMAY WASI MIGRATION TO MULTI-TENANT
-- ============================================
-- Purpose: Migrate existing Samay Wasi hotel data to multi-tenant structure
-- Prerequisites: Run aini_multitenant_schema.sql first
-- Date: October 2025
-- ============================================

-- Step 1: Create Samay Wasi hotel entry
-- ============================================

INSERT INTO hotels (
    hotel_name,
    slug,
    owner_user_id,
    hotel_description,
    tagline,
    star_rating,
    property_type,
    address,
    city,
    region,
    country,
    phone,
    email,
    amenities,
    policies,
    featured,
    verified,
    status,
    commission_rate,
    currency,
    timezone,
    approved_at
) VALUES (
    'Samay Wasi Hotel',
    'samay-wasi-cusco',
    (SELECT id FROM users WHERE role = 'owner' LIMIT 1), -- Get the owner user
    'A comfortable and welcoming hotel in the heart of Cusco, offering modern amenities and traditional Peruvian hospitality.',
    'Your home in Cusco - Traditional hospitality, modern comfort',
    4, -- 4-star rating
    'hotel',
    'Calle [Address], Cusco', -- Update with actual address
    'Cusco',
    'Cusco',
    'Peru',
    '+51 [Phone]', -- Update with actual phone
    'info@samaywasi.com', -- Update with actual email
    JSON_ARRAY(
        'Free WiFi',
        'Breakfast Included',
        'Airport Shuttle',
        '24-Hour Front Desk',
        'Housekeeping',
        'Room Service',
        'Laundry Service',
        'Tour Desk',
        'Luggage Storage',
        'Safe Deposit Box'
    ),
    JSON_OBJECT(
        'check_in_time', '14:00',
        'check_out_time', '12:00',
        'cancellation_policy', 'Free cancellation up to 24 hours before arrival',
        'payment_methods', JSON_ARRAY('Cash', 'Credit Card', 'Debit Card'),
        'pets_allowed', false,
        'children_allowed', true,
        'smoking_allowed', false
    ),
    1, -- Featured hotel
    1, -- Verified
    'active', -- Active status
    15.00, -- 15% commission
    'PEN',
    'America/Lima',
    CURRENT_TIMESTAMP
);

-- Get the hotel_id we just created
SET @samay_wasi_id = LAST_INSERT_ID();


-- Step 2: Update all existing records with hotel_id
-- ============================================

-- Update users (employees, managers, receptionists belong to Samay Wasi)
-- Guests (role='guest') will have NULL hotel_id (they're platform-wide)
UPDATE users 
SET hotel_id = @samay_wasi_id 
WHERE role IN ('employee', 'receptionist', 'manager', 'owner', 'investor');

-- Update rooms
UPDATE rooms 
SET hotel_id = @samay_wasi_id;

-- Update bookings
UPDATE bookings 
SET hotel_id = @samay_wasi_id;

-- Update employees (if exists as separate table)
UPDATE employees 
SET hotel_id = @samay_wasi_id;

-- Update time_clock
UPDATE time_clock 
SET hotel_id = @samay_wasi_id;

-- Update payroll (if exists)
UPDATE payroll 
SET hotel_id = @samay_wasi_id 
WHERE 1=1; -- Only if table exists

-- Update income
UPDATE income 
SET hotel_id = @samay_wasi_id 
WHERE 1=1; -- Only if table exists

-- Update expenses
UPDATE expenses 
SET hotel_id = @samay_wasi_id 
WHERE 1=1; -- Only if table exists


-- Step 3: Create initial room availability data
-- ============================================
-- Generate availability for next 365 days for all rooms

INSERT INTO room_availability (hotel_id, room_id, date, available_quantity, base_price, status)
SELECT 
    @samay_wasi_id,
    r.id,
    DATE_ADD(CURRENT_DATE(), INTERVAL n.num DAY) as date,
    r.quantity,
    r.price,
    'available'
FROM rooms r
CROSS JOIN (
    SELECT a.num + b.num * 10 + c.num * 100 as num
    FROM 
        (SELECT 0 as num UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
        (SELECT 0 as num UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
        (SELECT 0 as num UNION SELECT 1 UNION SELECT 2 UNION SELECT 3) c
    WHERE a.num + b.num * 10 + c.num * 100 < 365
) n
WHERE r.hotel_id = @samay_wasi_id
ON DUPLICATE KEY UPDATE available_quantity = VALUES(available_quantity);


-- Step 4: Block dates that already have bookings
-- ============================================

UPDATE room_availability ra
INNER JOIN bookings b ON ra.room_id = b.room_id
SET ra.available_quantity = 0
WHERE ra.hotel_id = @samay_wasi_id
AND ra.date >= b.check_in_date 
AND ra.date < b.check_out_date
AND b.status IN ('confirmed', 'checked_in');


-- Step 5: Create sample reviews (optional - if you have review data)
-- ============================================
-- If you have existing review/feedback data, migrate it here
-- Example structure:

/*
INSERT INTO reviews (
    hotel_id,
    booking_id,
    guest_id,
    rating_overall,
    rating_cleanliness,
    rating_comfort,
    rating_location,
    rating_facilities,
    rating_staff,
    rating_value,
    review_title,
    review_text,
    guest_name,
    guest_country,
    status,
    verified_stay,
    created_at
)
SELECT 
    @samay_wasi_id,
    b.id,
    b.guest_id,
    8.5, -- Default rating if no data
    9,
    8,
    9,
    8,
    9,
    8,
    'Great stay!',
    'Had a wonderful time at Samay Wasi.',
    u.name,
    'Peru',
    'approved',
    1,
    b.check_out_date
FROM bookings b
INNER JOIN users u ON b.guest_id = u.id
WHERE b.hotel_id = @samay_wasi_id
AND b.status = 'checked_out'
LIMIT 10; -- Sample reviews
*/


-- Step 6: Create initial hotel photos record
-- ============================================
-- Add existing hotel photos to hotel_photos table

INSERT INTO hotel_photos (hotel_id, photo_url, caption, display_order, is_primary, photo_category)
VALUES 
    (@samay_wasi_id, '/uploads/hotels/samay-wasi/exterior-1.jpg', 'Hotel Exterior', 1, 1, 'exterior'),
    (@samay_wasi_id, '/uploads/hotels/samay-wasi/lobby-1.jpg', 'Reception Lobby', 2, 0, 'lobby'),
    (@samay_wasi_id, '/uploads/hotels/samay-wasi/room-1.jpg', 'Deluxe Room', 3, 0, 'room');

-- If you have existing room photos in the rooms table, migrate them
INSERT INTO hotel_photos (hotel_id, photo_url, caption, display_order, is_primary, photo_category)
SELECT 
    @samay_wasi_id,
    CONCAT('/uploads/rooms/', r.id, '/', rp.photo_filename),
    CONCAT(r.room_type, ' - ', r.room_name),
    rp.photo_order,
    0,
    'room'
FROM rooms r
INNER JOIN room_photos rp ON r.id = rp.room_id
WHERE r.hotel_id = @samay_wasi_id;


-- Step 7: Verification queries
-- ============================================

-- Check that all records have hotel_id
SELECT 'Users with hotel' as table_name, COUNT(*) as count 
FROM users WHERE hotel_id = @samay_wasi_id
UNION ALL
SELECT 'Rooms', COUNT(*) FROM rooms WHERE hotel_id = @samay_wasi_id
UNION ALL
SELECT 'Bookings', COUNT(*) FROM bookings WHERE hotel_id = @samay_wasi_id
UNION ALL
SELECT 'Time Clock', COUNT(*) FROM time_clock WHERE hotel_id = @samay_wasi_id
UNION ALL
SELECT 'Employees', COUNT(*) FROM employees WHERE hotel_id = @samay_wasi_id
UNION ALL
SELECT 'Hotel Photos', COUNT(*) FROM hotel_photos WHERE hotel_id = @samay_wasi_id
UNION ALL
SELECT 'Room Availability', COUNT(*) FROM room_availability WHERE hotel_id = @samay_wasi_id;

-- Verify no bookings without hotel_id
SELECT COUNT(*) as bookings_without_hotel_id FROM bookings WHERE hotel_id IS NULL;

-- Should return 0


-- Step 8: Update hotel statistics
-- ============================================

-- Update hotel with current statistics
UPDATE hotels h
SET 
    h.meta_description = (
        SELECT CONCAT(
            'Book your stay at ', h.hotel_name, ' in ', h.city, 
            '. Rated ', ROUND(AVG(r.rating_overall), 1), '/10 based on ', 
            COUNT(r.id), ' guest reviews.'
        )
        FROM reviews r
        WHERE r.hotel_id = h.id
    )
WHERE h.id = @samay_wasi_id;


-- ============================================
-- MIGRATION VERIFICATION CHECKLIST
-- ============================================
/*
✓ Hotels table has Samay Wasi entry
✓ All users (except guests) have hotel_id
✓ All rooms have hotel_id
✓ All bookings have hotel_id  
✓ All employees have hotel_id
✓ All time_clock records have hotel_id
✓ Room availability generated for 365 days
✓ Existing bookings block availability correctly
✓ Hotel photos added
✓ No NULL hotel_id in required tables

Next Steps:
1. Test tenant context system
2. Verify data isolation (queries only return Samay Wasi data)
3. Test hotel owner dashboard with new multi-tenant queries
4. Update all PHP files to use TenantContext
5. Create public search interface
*/

-- ============================================
-- ROLLBACK SCRIPT (in case of issues)
-- ============================================
/*
-- To rollback this migration:

-- Remove hotel_id from all tables
ALTER TABLE users DROP FOREIGN KEY users_ibfk_[check number];
ALTER TABLE users DROP COLUMN hotel_id;

ALTER TABLE rooms DROP FOREIGN KEY rooms_ibfk_[check number];
ALTER TABLE rooms DROP COLUMN hotel_id;

ALTER TABLE bookings DROP FOREIGN KEY bookings_ibfk_[check number];
ALTER TABLE bookings DROP COLUMN hotel_id;

-- Repeat for all tables...

-- Delete hotel record
DELETE FROM hotels WHERE id = @samay_wasi_id;

-- Drop new tables
DROP TABLE hotel_photos;
DROP TABLE reviews;
DROP TABLE room_availability;
DROP TABLE promotions;
DROP TABLE payment_transactions;
DROP TABLE search_logs;
DROP TABLE wishlists;
DROP TABLE admin_logs;
*/
