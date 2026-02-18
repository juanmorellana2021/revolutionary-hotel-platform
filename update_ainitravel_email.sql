UPDATE email_config 
SET smtp_username = 'support@ainitravel.com',
    smtp_password = 'FpF5vBZ5!t$6LFp',
    from_email = 'support@ainitravel.com',
    from_name = 'AiNi Travel',
    reply_to = 'support@ainitravel.com',
    updated_at = NOW()
WHERE id = 1;
