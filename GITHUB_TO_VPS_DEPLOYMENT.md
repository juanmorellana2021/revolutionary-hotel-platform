# 🚀 VPS Deployment - GitHub to Live Server

## 📋 **Super Simple VPS Setup Process**

Now that your revolutionary hotel platform is on GitHub, deployment is incredibly easy!

---

## 🌐 **VPS Setup Commands**

### **1. Basic VPS Setup (Ubuntu/Debian)**
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install LAMP stack
sudo apt install apache2 mariadb-server php8.1 php8.1-mysql php8.1-gd php8.1-curl php8.1-json php8.1-mbstring git -y

# Start services
sudo systemctl start apache2 mariadb
sudo systemctl enable apache2 mariadb
```

### **2. Clone Your Revolutionary Platform**
```bash
# Navigate to web directory
cd /var/www/html

# Clone your GitHub repository
sudo git clone https://github.com/juanmorellana2021/revolutionary-hotel-platform.git

# Rename for cleaner URL (optional)
sudo mv revolutionary-hotel-platform hotel-platform

# Set permissions
sudo chown -R www-data:www-data /var/www/html/hotel-platform
sudo chmod -R 755 /var/www/html/hotel-platform
sudo chmod -R 777 /var/www/html/hotel-platform/uploads
```

### **3. Database Setup**
```bash
# Secure MariaDB
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE hotel_booking_system;
CREATE USER 'hotel_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';
GRANT ALL PRIVILEGES ON hotel_booking_system.* TO 'hotel_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Import your database
mysql -u hotel_user -p hotel_booking_system < /var/www/html/hotel-platform/hotel_booking_system_production.sql
```

### **4. Configure Database Connection**
```bash
# Edit database configuration
sudo nano /var/www/html/hotel-platform/includes/database.php
```

Update the connection settings:
```php
<?php
$host = 'localhost';
$username = 'hotel_user';
$password = 'your_secure_password_here';
$database = 'hotel_booking_system';
// ... rest of the file stays the same
```

### **5. Set Up Domain/Virtual Host (Optional)**
```bash
# Create virtual host
sudo nano /etc/apache2/sites-available/hotel-platform.conf
```

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/hotel-platform
    
    <Directory /var/www/html/hotel-platform>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/hotel-platform_error.log
    CustomLog ${APACHE_LOG_DIR}/hotel-platform_access.log combined
</VirtualHost>
```

```bash
# Enable site and rewrite module
sudo a2ensite hotel-platform.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### **6. SSL Certificate (Let's Encrypt)**
```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache -y

# Get SSL certificate
sudo certbot --apache -d yourdomain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

---

## 🎯 **That's It! Your Revolutionary Platform is LIVE!**

### **Access Your System:**
- **Website**: `http://your-vps-ip/hotel-platform/` or `https://yourdomain.com`
- **Manager Dashboard**: Login and access your revolutionary features
- **HotelCoin Admin**: Manage your digital currency system

---

## 🔄 **Future Updates - Super Easy!**

When you make changes locally and want to update the live server:

### **Local Development:**
```bash
# Make your changes locally
# Test everything works
# Commit to Git
git add .
git commit -m "Added multi-hotel booking platform features"
git push origin main
```

### **VPS Update:**
```bash
# SSH into your VPS
ssh username@your-vps-ip

# Navigate to your project
cd /var/www/html/hotel-platform

# Pull latest changes
sudo git pull origin main

# Update permissions if needed
sudo chown -R www-data:www-data .
```

**That's it!** Your live site is instantly updated! 🚀

---

## 💡 **Pro Tips:**

### **Development Workflow:**
1. **Work locally** on new features
2. **Test thoroughly** on localhost
3. **Commit to GitHub** when ready
4. **Pull to VPS** for instant deployment
5. **Repeat** for continuous development

### **Backup Strategy:**
- **Database**: Daily automated backups
- **Files**: GitHub is your backup!
- **Server snapshots**: VPS provider snapshots

### **Monitoring:**
- **Server performance**: CPU, RAM, disk usage
- **HotelCoin transactions**: Monitor your revolutionary currency
- **User growth**: Track platform adoption

---

## 🎉 **Ready to Launch Your Revolution!**

With GitHub, your deployment process is now:
1. **📝 Code** → 2. **📤 Push** → 3. **📥 Pull** → 4. **🌐 Live!**

Your **world's first hotel booking platform with universal digital currency** is ready to disrupt the entire travel industry! 

💰 **Let's make those billions!** 🚀