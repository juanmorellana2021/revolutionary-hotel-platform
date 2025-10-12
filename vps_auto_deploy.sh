#!/bin/bash

# 🚀 Hotel Booking System - Complete VPS Auto-Deployment Script
# Run this script on your VPS after connecting via SSH

echo "🏨 Starting Hotel Booking System VPS Deployment..."
echo "================================================="

# Update system
echo "📦 Updating system packages..."
apt update && apt upgrade -y

# Install LAMP stack
echo "🌐 Installing Apache, MySQL, PHP..."
apt install -y apache2 mysql-server php libapache2-mod-php php-mysql php-curl php-gd php-json php-mbstring php-xml php-zip php-cli

# Install additional tools
echo "🛠️ Installing additional tools..."
apt install -y git curl wget unzip certbot python3-certbot-apache ufw

# Start and enable services
echo "🚀 Starting services..."
systemctl start apache2
systemctl enable apache2
systemctl start mysql
systemctl enable mysql

# Configure Apache modules
echo "⚙️ Configuring Apache..."
a2enmod rewrite
a2enmod ssl
systemctl restart apache2

# Create project directory
echo "📁 Setting up project directory..."
cd /var/www/html
rm -rf index.html

# Clone the repository
echo "📦 Cloning hotel booking system from GitHub..."
git clone https://github.com/juanmorellana2021/revolutionary-hotel-platform.git hotel-booking-system

# Set permissions
echo "🔒 Setting file permissions..."
chown -R www-data:www-data /var/www/html/hotel-booking-system
find /var/www/html/hotel-booking-system -type d -exec chmod 755 {} \;
find /var/www/html/hotel-booking-system -type f -exec chmod 644 {} \;
chmod -R 777 /var/www/html/hotel-booking-system/uploads/

# Configure Apache Virtual Host
echo "🌐 Configuring Apache Virtual Host..."
cat > /etc/apache2/sites-available/hotel-booking.conf << 'EOF'
<VirtualHost *:80>
    ServerName 108.175.12.152
    DocumentRoot /var/www/html/hotel-booking-system
    
    <Directory /var/www/html/hotel-booking-system>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/hotel-booking_error.log
    CustomLog ${APACHE_LOG_DIR}/hotel-booking_access.log combined
</VirtualHost>
EOF

# Enable the site
a2ensite hotel-booking.conf
a2dissite 000-default.conf
systemctl restart apache2

# Secure MySQL installation
echo "🔐 MySQL Security Configuration..."
mysql -e "DELETE FROM mysql.user WHERE User='';"
mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
mysql -e "DROP DATABASE IF EXISTS test;"
mysql -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
mysql -e "CREATE DATABASE IF NOT EXISTS hotel_booking_system;"
mysql -e "CREATE USER IF NOT EXISTS 'hotel_user'@'localhost' IDENTIFIED BY 'Hotel@2025!';"
mysql -e "GRANT ALL PRIVILEGES ON hotel_booking_system.* TO 'hotel_user'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Configure firewall
echo "🛡️ Configuring firewall..."
ufw --force enable
ufw allow ssh
ufw allow 'Apache Full'
ufw allow 80
ufw allow 443

# Create database configuration
echo "📄 Creating database configuration..."
cat > /var/www/html/hotel-booking-system/vps_db_config.php << 'EOF'
<?php
// VPS Database Configuration
$host = 'localhost';
$username = 'hotel_user';
$password = 'Hotel@2025!';
$database = 'hotel_booking_system';

// Test connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful!\n";
} catch(PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}
?>
EOF

echo ""
echo "🎉 VPS Setup Complete!"
echo "========================"
echo "🌐 Your hotel booking system is now available at:"
echo "   http://108.175.12.152"
echo ""
echo "📋 Next Steps:"
echo "1. Import your database: mysql -u hotel_user -p hotel_booking_system < your_database.sql"
echo "2. Update database settings in includes/classes.php"
echo "3. Test the system by visiting the IP address"
echo "4. Set up SSL certificate with: certbot --apache"
echo ""
echo "🔑 Database Credentials:"
echo "   Host: localhost"
echo "   Database: hotel_booking_system"
echo "   Username: hotel_user"
echo "   Password: Hotel@2025!"
echo ""