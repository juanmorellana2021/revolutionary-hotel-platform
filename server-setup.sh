#!/bin/bash

# 🚀 Hotel Booking System - VPS Server Setup Script
# This script sets up a complete LAMP stack for the hotel booking system

echo "🏨 Hotel Booking System VPS Setup Starting..."
echo "================================================"

# Update system packages
echo "📦 Updating system packages..."
apt update && apt upgrade -y

# Install Apache web server
echo "🌐 Installing Apache web server..."
apt install apache2 -y
systemctl start apache2
systemctl enable apache2

# Install MySQL database server
echo "🗄️ Installing MySQL database server..."
apt install mysql-server -y
systemctl start mysql
systemctl enable mysql

# Install PHP and required extensions
echo "🐘 Installing PHP and extensions..."
apt install php libapache2-mod-php php-mysql php-curl php-gd php-json php-mbstring php-xml php-zip -y

# Install Git for repository cloning
echo "📚 Installing Git..."
apt install git -y

# Install Certbot for SSL certificates
echo "🔒 Installing Certbot for SSL..."
apt install certbot python3-certbot-apache -y

# Configure Apache
echo "⚙️ Configuring Apache..."
a2enmod rewrite
a2enmod ssl

# Create project directory
echo "📁 Creating project directory..."
mkdir -p /var/www/html/hotel-booking-system
chown -R www-data:www-data /var/www/html/hotel-booking-system
chmod -R 755 /var/www/html/hotel-booking-system

# Configure MySQL (secure installation will be done manually)
echo "🔐 MySQL installed. You'll need to run mysql_secure_installation manually."

# Create Apache virtual host configuration
echo "🌍 Creating Apache virtual host..."
cat > /etc/apache2/sites-available/hotel-booking.conf << 'EOF'
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/html/hotel-booking-system
    
    <Directory /var/www/html/hotel-booking-system>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/hotel-booking_error.log
    CustomLog ${APACHE_LOG_DIR}/hotel-booking_access.log combined
</VirtualHost>
EOF

# Enable the site
a2ensite hotel-booking.conf
a2dissite 000-default.conf

# Create .htaccess file for security
cat > /var/www/html/hotel-booking-system/.htaccess << 'EOF'
# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
Header always set Referrer-Policy "strict-origin-when-cross-origin"

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Cache static files
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>

# URL Rewriting
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
EOF

# Restart Apache
systemctl reload apache2

# Install Composer (for PHP dependencies if needed)
echo "📦 Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Set up firewall
echo "🛡️ Configuring UFW firewall..."
ufw allow OpenSSH
ufw allow 'Apache Full'
ufw --force enable

# Create deployment script
cat > /var/www/html/deploy.sh << 'EOF'
#!/bin/bash
# 🚀 Hotel Booking System Deployment Script

echo "🔄 Deploying Hotel Booking System..."

# Navigate to project directory
cd /var/www/html/hotel-booking-system

# Pull latest changes from Git
echo "📥 Pulling latest changes..."
git pull origin main

# Set proper permissions
echo "🔒 Setting permissions..."
chown -R www-data:www-data /var/www/html/hotel-booking-system
chmod -R 755 /var/www/html/hotel-booking-system
chmod -R 766 /var/www/html/hotel-booking-system/uploads

# Clear any cache if needed
echo "🧹 Clearing cache..."
# Add cache clearing commands here if your app uses caching

echo "✅ Deployment complete!"
EOF

chmod +x /var/www/html/deploy.sh

echo ""
echo "🎉 Server setup complete!"
echo "================================================"
echo ""
echo "📋 Next steps:"
echo "1. Run: mysql_secure_installation"
echo "2. Set up MySQL database using the setup-database.sql file"
echo "3. Clone your repository: git clone https://github.com/juanmorellana2021/revolutionary-hotel-platform.git /var/www/html/hotel-booking-system"
echo "4. Configure database connection in your PHP files"
echo "5. Set up SSL: certbot --apache -d your-domain.com"
echo "6. Update virtual host with your actual domain name"
echo ""
echo "🔗 Your site will be available at: http://your-server-ip"
echo "📁 Project location: /var/www/html/hotel-booking-system"
echo "🚀 Deployment script: /var/www/html/deploy.sh"
echo ""
echo "Happy hosting! 🚀"