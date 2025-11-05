ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'guest';

-- Update existing users with appropriate roles
UPDATE users SET role = 'manager' WHERE email = 'manager@hotel.com';
UPDATE users SET role = 'admin' WHERE email = 'admin@hotel.com';
UPDATE users SET role = 'guest' WHERE email = 'guest@hotel.com';