-- Add role column to ainitravel_users table
ALTER TABLE ainitravel_users 
ADD COLUMN role ENUM('user', 'admin', 'partner') DEFAULT 'user' AFTER email;

-- Make Juan an admin
UPDATE ainitravel_users SET role = 'admin' WHERE id = 2;
