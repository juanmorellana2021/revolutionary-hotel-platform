-- Check October bookings payment status
SELECT 
    DATE(check_in_date) as date, 
    COUNT(*) as bookings, 
    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count 
FROM bookings 
WHERE check_in_date >= '2025-10-01' AND check_in_date <= '2025-10-31' 
GROUP BY DATE(check_in_date) 
ORDER BY date;

-- Overall payment status summary
SELECT 'OVERALL PAYMENT STATUS:' as info;
SELECT payment_status, COUNT(*) as count FROM bookings GROUP BY payment_status;