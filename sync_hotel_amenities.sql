-- Sync hotel amenities active status from XAMPP to VPS  
UPDATE hotel_amenities SET is_active = 1 WHERE amenity_name = 'Free WiFi';
UPDATE hotel_amenities SET is_active = 0 WHERE amenity_name = 'Swimming Pool';
UPDATE hotel_amenities SET is_active = 0 WHERE amenity_name = 'Fitness Center';
UPDATE hotel_amenities SET is_active = 0 WHERE amenity_name = 'Spa & Wellness';
UPDATE hotel_amenities SET is_active = 0 WHERE amenity_name = 'Restaurant';
UPDATE hotel_amenities SET is_active = 0 WHERE amenity_name = 'Bar/Lounge';
UPDATE hotel_amenities SET is_active = 1 WHERE amenity_name = 'Parking';
UPDATE hotel_amenities SET is_active = 0 WHERE amenity_name = 'Pet Friendly';
UPDATE hotel_amenities SET is_active = 0 WHERE amenity_name = 'Airport Shuttle';
UPDATE hotel_amenities SET is_active = 1 WHERE amenity_name = 'Meeting Rooms';
UPDATE hotel_amenities SET is_active = 0 WHERE amenity_name = 'Laundry Service';
UPDATE hotel_amenities SET is_active = 0 WHERE amenity_name = 'Safe Deposit Box';

-- Verify final status
SELECT 'FINAL HOTEL SETUP STATUS:' as status;
SELECT 'Hotel Info:' as section;
SELECT hotel_name, city, state, country, phone, email, total_rooms, hotel_rating FROM hotel_info;

SELECT 'Active Services:' as section;  
SELECT service_name FROM hotel_services WHERE is_active = 1;

SELECT 'Active Amenities:' as section;
SELECT amenity_name FROM hotel_amenities WHERE is_active = 1;