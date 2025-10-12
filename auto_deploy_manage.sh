#!/bin/bash

# 🏨 Hotel Booking System - Auto Deploy to /manage
# One-command deployment script

echo "🏨 Starting Hotel Booking System Deployment to /manage..."
echo "======================================================="

# Update system
echo "📦 Updating system packages..."
apt update -y

# Install LAMP stack if not already installed
echo "🌐 Installing required packages..."
apt install -y apache2 mysql-server php libapache2-mod-php php-mysql php-curl php-gd php-json php-mbstring php-xml php-zip php-cli git

# Start services
echo "🚀 Starting services..."
systemctl start apache2
systemctl enable apache2
systemctl start mysql
systemctl enable mysql

# Enable Apache modules
echo "⚙️ Configuring Apache..."
a2enmod rewrite
systemctl restart apache2

# Create /manage directory
echo "📁 Creating /manage directory..."
mkdir -p /var/www/html/manage
cd /var/www/html/manage

# Remove any existing content
rm -rf /var/www/html/manage/*
rm -rf /var/www/html/manage/.git

# Clone hotel booking system
echo "📦 Cloning hotel booking system..."
git clone https://github.com/juanmorellana2021/revolutionary-hotel-platform.git .

# Set permissions
echo "🔒 Setting file permissions..."
chown -R www-data:www-data /var/www/html/manage
find /var/www/html/manage -type d -exec chmod 755 {} \;
find /var/www/html/manage -type f -exec chmod 644 {} \;

# Create uploads directory
mkdir -p /var/www/html/manage/uploads
chmod -R 777 /var/www/html/manage/uploads/

# Set up database
echo "🗄️ Setting up database..."
mysql -e "CREATE DATABASE IF NOT EXISTS hotel_booking_system;" 2>/dev/null
mysql -e "CREATE USER IF NOT EXISTS 'hotel_user'@'localhost' IDENTIFIED BY 'Hotel@2025!';" 2>/dev/null
mysql -e "GRANT ALL PRIVILEGES ON hotel_booking_system.* TO 'hotel_user'@'localhost';" 2>/dev/null
mysql -e "FLUSH PRIVILEGES;" 2>/dev/null

# Import database schema
echo "📊 Importing database schema..."
if [ -f "database.sql" ]; then
    mysql -u hotel_user -pHotel@2025! hotel_booking_system < database.sql
    echo "✅ Database schema imported successfully"
else
    echo "⚠️ database.sql not found, skipping import"
fi

# Update database connection settings
echo "⚙️ Configuring database connection..."
if [ -f "includes/classes.php" ]; then
    # Update database credentials in the main classes file
    sed -i 's/private $username = "root";/private $username = "hotel_user";/' includes/classes.php
    sed -i 's/private $password = "";/private $password = "Hotel@2025!";/' includes/classes.php
    echo "✅ Database credentials updated"
fi

# Test database connection
echo "🧪 Testing database connection..."
php -r "
try {
    \$pdo = new PDO('mysql:host=localhost;dbname=hotel_booking_system', 'hotel_user', 'Hotel@2025!');
    echo '✅ Database connection successful!\n';
} catch(PDOException \$e) {
    echo '❌ Database connection failed: ' . \$e->getMessage() . '\n';
}
"

echo ""
echo "🎉 Hotel Booking System Deployment Complete!"
echo "============================================="
echo ""
echo "🌐 Your hotel management system is now available at:"
echo "   📱 Main URL: http://108.175.12.152/manage"
echo "   🔐 Login: http://108.175.12.152/manage/index.php"
echo "   📅 Calendar: http://108.175.12.152/manage/calendar_view.php"
echo "   📊 Dashboard: http://108.175.12.152/manage/dashboard.php"
echo ""
echo "🔑 Database Information:"
echo "   Host: localhost"
echo "   Database: hotel_booking_system"
echo "   Username: hotel_user"
echo "   Password: Hotel@2025!"
echo ""
echo "✨ Features Available:"
echo "   • Multi-room booking system"
echo "   • Multi-guest management"
echo "   • Calendar view with reservations"
echo "   • Receipt generation"
echo "   • Room management with photos"
echo "   • Financial reporting"
echo "   • Staff management"
echo ""
echo "🚀 Ready to manage your hotel bookings!"