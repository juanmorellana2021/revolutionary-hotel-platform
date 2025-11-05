# October 2025 Data Migration Script
# This script will migrate users and bookings from XAMPP to VPS

# Step 1: Export users who have October bookings
echo "=== EXPORTING OCTOBER USERS AND BOOKINGS ==="

# Get users with October bookings
C:\xampp\mysql\bin\mysql -u root hotel_booking_system -e "
SELECT CONCAT(
    'INSERT INTO users (id, first_name, last_name, email, password, user_role, role, created_at, updated_at) VALUES (',
    id, ', ',
    QUOTE(first_name), ', ',
    QUOTE(last_name), ', ',
    QUOTE(email), ', ',
    QUOTE(password), ', ',
    QUOTE(COALESCE(user_role, 'guest')), ', ',
    QUOTE(COALESCE(role, 'guest')), ', ',
    QUOTE(created_at), ', ',
    QUOTE(updated_at), ');'
) as sql_statement
FROM users 
WHERE id IN (
    SELECT DISTINCT user_id 
    FROM bookings 
    WHERE check_in_date >= '2025-10-01' OR check_out_date >= '2025-10-01'
)
ORDER BY id;" > october_users_migration.sql

echo "Users exported to october_users_migration.sql"

# Get October bookings
C:\xampp\mysql\bin\mysql -u root hotel_booking_system -e "
SELECT CONCAT(
    'INSERT INTO bookings (id, user_id, room_id, check_in_date, check_out_date, total_price, status, created_at, is_multi_room, primary_booking_id) VALUES (',
    id, ', ',
    user_id, ', ',
    room_id, ', ',
    QUOTE(check_in_date), ', ',
    QUOTE(check_out_date), ', ',
    total_price, ', ',
    QUOTE(status), ', ',
    QUOTE(created_at), ', ',
    COALESCE(is_multi_room, 0), ', ',
    COALESCE(primary_booking_id, 'NULL'), ');'
) as sql_statement
FROM bookings 
WHERE check_in_date >= '2025-10-01' OR check_out_date >= '2025-10-01'
ORDER BY id;" > october_bookings_migration.sql

echo "Bookings exported to october_bookings_migration.sql"

echo "=== EXPORT COMPLETE ==="