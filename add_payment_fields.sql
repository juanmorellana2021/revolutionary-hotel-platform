-- Add missing payment fields to VPS bookings table
ALTER TABLE bookings ADD COLUMN payment_status ENUM('pending','partial','paid','refunded') DEFAULT 'pending';
ALTER TABLE bookings ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN paid_amount DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE bookings ADD COLUMN tax_amount DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE bookings ADD COLUMN service_charge DECIMAL(10,2) DEFAULT 0.00;

-- Update all bookings to show as PAID (green in calendar)
UPDATE bookings SET 
    payment_status = 'paid',
    paid_amount = total_price,
    payment_method = 'cash'
WHERE status = 'confirmed';

-- Verify the changes
SELECT 'PAYMENT STATUS UPDATE COMPLETE' as result;
SELECT 
    status,
    payment_status,
    COUNT(*) as count
FROM bookings 
GROUP BY status, payment_status;

SELECT 'SAMPLE BOOKINGS:' as info;
SELECT 
    id,
    check_in_date,
    check_out_date,
    total_price,
    paid_amount,
    payment_status,
    payment_method
FROM bookings 
ORDER BY check_in_date 
LIMIT 5;