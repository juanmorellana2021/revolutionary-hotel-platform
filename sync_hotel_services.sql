-- Sync hotel services active status from XAMPP to VPS
UPDATE hotel_services SET is_active = 1 WHERE service_name = '24/7 Front Desk';
UPDATE hotel_services SET is_active = 0 WHERE service_name = 'Room Service';
UPDATE hotel_services SET is_active = 1 WHERE service_name = 'Housekeeping';
UPDATE hotel_services SET is_active = 0 WHERE service_name = 'Concierge Service';
UPDATE hotel_services SET is_active = 0 WHERE service_name = 'Wake-up Calls';
UPDATE hotel_services SET is_active = 1 WHERE service_name = 'Luggage Storage';
UPDATE hotel_services SET is_active = 1 WHERE service_name = 'Express Check-in/out';
UPDATE hotel_services SET is_active = 0 WHERE service_name = 'Business Center';

-- Check hotel amenities on VPS
SELECT 'SERVICES UPDATED - CHECKING AMENITIES:' as status;
SELECT * FROM hotel_amenities ORDER BY id;