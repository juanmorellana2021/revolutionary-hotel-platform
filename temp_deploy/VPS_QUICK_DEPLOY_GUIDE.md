# 🚀 VPS Deployment Instructions

## 📋 Quick Setup Guide

### **Step 1: Connect to Your VPS**
```bash
ssh root@108.175.12.152
# Enter your password when prompted
```

### **Step 2: Run Auto-Deployment Script**
```bash
# Download and run the deployment script
wget https://raw.githubusercontent.com/juanmorellana2021/revolutionary-hotel-platform/main/vps_auto_deploy.sh
chmod +x vps_auto_deploy.sh
./vps_auto_deploy.sh
```

### **Step 3: Setup Database**
```bash
# Navigate to project directory
cd /var/www/html/hotel-booking-system

# Run database setup
chmod +x vps_database_setup.sh
./vps_database_setup.sh
```

### **Step 4: Access Your Hotel System**
Open your browser and go to: **http://108.175.12.152**

---

## 🔧 Manual Setup (Alternative)

If the auto-script doesn't work, follow these manual steps:

### **1. Update System & Install LAMP**
```bash
apt update && apt upgrade -y
apt install -y apache2 mysql-server php libapache2-mod-php php-mysql php-curl php-gd php-json php-mbstring php-xml php-zip git
```

### **2. Clone Repository**
```bash
cd /var/www/html
git clone https://github.com/juanmorellana2021/revolutionary-hotel-platform.git hotel-booking-system
chown -R www-data:www-data hotel-booking-system
chmod -R 755 hotel-booking-system
```

### **3. Configure Database**
```bash
mysql -e "CREATE DATABASE hotel_booking_system;"
mysql -e "CREATE USER 'hotel_user'@'localhost' IDENTIFIED BY 'Hotel@2025!';"
mysql -e "GRANT ALL PRIVILEGES ON hotel_booking_system.* TO 'hotel_user'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"
```

### **4. Import Database**
```bash
cd /var/www/html/hotel-booking-system
mysql -u hotel_user -pHotel@2025! hotel_booking_system < database.sql
```

### **5. Configure Apache**
```bash
a2enmod rewrite
systemctl restart apache2
```

---

## 🔒 Security Setup

### **Firewall Configuration**
```bash
ufw enable
ufw allow ssh
ufw allow 'Apache Full'
```

### **SSL Certificate (Optional)**
```bash
apt install certbot python3-certbot-apache
# If you have a domain name:
# certbot --apache -d yourdomain.com
```

---

## 🧪 Testing

### **Test Database Connection**
```bash
cd /var/www/html/hotel-booking-system
php vps_db_config.php
```

### **Check System Status**
```bash
systemctl status apache2
systemctl status mysql
```

---

## 📱 Access Information

- **URL**: http://108.175.12.152
- **Database Host**: localhost
- **Database Name**: hotel_booking_system
- **Database User**: hotel_user
- **Database Password**: Hotel@2025!

---

## 🆘 Troubleshooting

### **If website shows Apache default page:**
```bash
a2dissite 000-default
a2ensite hotel-booking
systemctl restart apache2
```

### **If database connection fails:**
```bash
mysql -u root -p
# Then run the database creation commands manually
```

### **Check Apache error logs:**
```bash
tail -f /var/log/apache2/error.log
```