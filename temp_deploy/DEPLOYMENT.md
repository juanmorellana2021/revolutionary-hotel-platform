# 🚀 Hotel Booking System - VPS Deployment Guide

## Prerequisites
- Ubuntu/Debian VPS server
- Root access or sudo privileges
- Domain name (optional but recommended)

## Quick Setup

### 1. Connect to Your VPS
```bash
ssh root@your-server-ip
# or
ssh your-username@your-server-ip
```

### 2. Upload and Run Setup Script
```bash
# Download the setup script
wget https://raw.githubusercontent.com/juanmorellana2021/revolutionary-hotel-platform/main/server-setup.sh

# Make it executable
chmod +x server-setup.sh

# Run the setup (as root)
sudo ./server-setup.sh
```

### 3. Secure MySQL Installation
```bash
sudo mysql_secure_installation
```

### 4. Set Up Database
```bash
# Download database setup script
wget https://raw.githubusercontent.com/juanmorellana2021/revolutionary-hotel-platform/main/setup-database.sql

# Edit the password in the SQL file
nano setup-database.sql
# Change 'your_secure_password_here' to a strong password

# Run the database setup
sudo mysql -u root -p < setup-database.sql
```

### 5. Clone Your Project
```bash
# Remove default directory if it exists
sudo rm -rf /var/www/html/hotel-booking-system

# Clone your repository
sudo git clone https://github.com/juanmorellana2021/revolutionary-hotel-platform.git /var/www/html/hotel-booking-system

# Set proper permissions
sudo chown -R www-data:www-data /var/www/html/hotel-booking-system
sudo chmod -R 755 /var/www/html/hotel-booking-system
sudo mkdir -p /var/www/html/hotel-booking-system/uploads
sudo chmod -R 766 /var/www/html/hotel-booking-system/uploads
```

### 6. Configure Database Connection
Edit your database configuration files:

```bash
sudo nano /var/www/html/hotel-booking-system/includes/classes.php
```

Update the database connection settings:
```php
private $host = "localhost";
private $db_name = "hotel_booking_system";
private $username = "hotel_user";
private $password = "your_secure_password_here"; // Use the password from step 4
```

### 7. Update Apache Virtual Host
```bash
sudo nano /etc/apache2/sites-available/hotel-booking.conf
```

Replace `your-domain.com` with your actual domain name or server IP.

### 8. Test the Installation
```bash
# Restart Apache
sudo systemctl restart apache2

# Check Apache status
sudo systemctl status apache2

# Check if site is accessible
curl -I http://your-server-ip
```

### 9. Set Up SSL (Optional but Recommended)
```bash
# If you have a domain name
sudo certbot --apache -d your-domain.com -d www.your-domain.com
```

## Post-Installation

### Access Your Site
- **HTTP**: http://your-server-ip or http://your-domain.com
- **HTTPS**: https://your-domain.com (after SSL setup)

### Default Login
The application will guide you through creating the first admin user.

### Deployment Updates
To update your site with new changes:
```bash
sudo /var/www/html/deploy.sh
```

## Troubleshooting

### Check Apache Logs
```bash
sudo tail -f /var/log/apache2/hotel-booking_error.log
```

### Check MySQL Connection
```bash
mysql -u hotel_user -p hotel_booking_system
```

### File Permissions Issues
```bash
sudo chown -R www-data:www-data /var/www/html/hotel-booking-system
sudo chmod -R 755 /var/www/html/hotel-booking-system
sudo chmod -R 766 /var/www/html/hotel-booking-system/uploads
```

### Apache Configuration Test
```bash
sudo apache2ctl configtest
```

## Security Considerations

1. **Change default passwords** in database and application
2. **Enable firewall** (UFW is configured automatically)
3. **Set up SSL certificates** for HTTPS
4. **Regular updates**: `sudo apt update && sudo apt upgrade`
5. **Backup your database** regularly

## Support

If you encounter issues:
1. Check the error logs
2. Verify file permissions
3. Ensure MySQL is running: `sudo systemctl status mysql`
4. Ensure Apache is running: `sudo systemctl status apache2`

---

🎉 **Your hotel booking system is now live!** 🏨