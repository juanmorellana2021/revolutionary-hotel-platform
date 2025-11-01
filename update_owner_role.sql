UPDATE users SET role='owner' WHERE user_type='owner';
SELECT id, email, role, user_type FROM users WHERE user_type='owner';
