-- Multi-tenant hotel ownership setup

-- 1. Remove redundant hotel_id from users (we only need current_hotel_id)
ALTER TABLE users DROP COLUMN hotel_id;

-- 2. Add user_type to distinguish between hotel owners, staff, and guests
ALTER TABLE users ADD COLUMN user_type ENUM('owner', 'staff', 'guest') DEFAULT 'guest' AFTER user_role;

-- 3. Create a table to track which hotels a user can access (for staff who work at multiple properties)
CREATE TABLE IF NOT EXISTS user_hotel_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    access_level ENUM('owner', 'manager', 'staff', 'viewer') DEFAULT 'viewer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_hotel (user_id, hotel_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hotel_info(id) ON DELETE CASCADE
);

-- 4. Set existing admin users as owners
UPDATE users SET user_type = 'owner' WHERE user_role = 'admin';
UPDATE users SET user_type = 'staff' WHERE user_role = 'manager';

-- 5. Set owner_id for existing hotel to first admin user
UPDATE hotel_info 
SET owner_id = (SELECT id FROM users WHERE user_role = 'admin' LIMIT 1) 
WHERE owner_id IS NULL;

-- 6. Give all admin users access to all existing hotels
INSERT INTO user_hotel_access (user_id, hotel_id, access_level)
SELECT u.id, h.id, 'owner'
FROM users u
CROSS JOIN hotel_info h
WHERE u.user_role = 'admin'
ON DUPLICATE KEY UPDATE access_level = 'owner';
