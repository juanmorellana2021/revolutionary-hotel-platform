# Setup pms.ainitravel.com Subdomain
## Step-by-Step Guide (Without Breaking Current Site)

**Goal:** Move PMS from current location to `https://pms.ainitravel.com`

**Current State:** PMS running at `/var/www/html` on 108.175.12.152

**Safe Approach:** Set up subdomain FIRST, test it, then switch DNS

---

## ✅ Step 1: Add DNS Record (Do This First!)

**Go to your domain registrar** (wherever you bought ainitravel.com - GoDaddy, Namecheap, etc.)

**Add this DNS record:**

```
Type: A
Host: pms
Value: 108.175.12.152
TTL: 3600 (or Auto)
```

**This creates:** `pms.ainitravel.com` → `108.175.12.152`

**Wait 5-30 minutes** for DNS to propagate.

**Test it works:**
```bash
ping pms.ainitravel.com
# Should return: 108.175.12.152
```

---

## ✅ Step 2: Create Directory for PMS

**SSH into your server:**

```bash
ssh hotel-vps
```

**Create new directory:**

```bash
# Create directory for PMS subdomain
sudo mkdir -p /var/www/pms.ainitravel.com

# Copy ALL current PMS files to new location
sudo cp -r /var/www/html/* /var/www/pms.ainitravel.com/

# Set correct permissions
sudo chown -R www-data:www-data /var/www/pms.ainitravel.com
sudo chmod -R 755 /var/www/pms.ainitravel.com
```

**Why copy instead of move?**
- Old site keeps working while we test
- If something breaks, no downtime
- Once confirmed working, we can clean up

---

## ✅ Step 3: Create Apache Virtual Host

**Create config file:**

```bash
sudo nano /etc/apache2/sites-available/pms.ainitravel.com.conf
```

**Paste this configuration:**

```apache
<VirtualHost *:80>
    ServerName pms.ainitravel.com
    ServerAdmin admin@ainitravel.com
    DocumentRoot /var/www/pms.ainitravel.com
    
    <Directory /var/www/pms.ainitravel.com>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/pms_error.log
    CustomLog ${APACHE_LOG_DIR}/pms_access.log combined
    
    # PHP settings
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
</VirtualHost>
```

**Save and exit:**
- Press `Ctrl + X`
- Press `Y` to confirm
- Press `Enter`

---

## ✅ Step 4: Enable the Site

```bash
# Enable the new virtual host
sudo a2ensite pms.ainitravel.com.conf

# Test Apache configuration (make sure no errors!)
sudo apache2ctl configtest

# Should say: "Syntax OK"

# Reload Apache
sudo systemctl reload apache2
```

---

## ✅ Step 5: Test (HTTP - No SSL Yet)

**Open browser and go to:**

```
http://pms.ainitravel.com
```

**You should see:** Your PMS dashboard!

**If it doesn't work:**

1. **Check DNS propagated:**
   ```bash
   ping pms.ainitravel.com
   # Should show: 108.175.12.152
   ```

2. **Check Apache logs:**
   ```bash
   sudo tail -f /var/log/apache2/pms_error.log
   ```

3. **Check virtual host enabled:**
   ```bash
   ls -la /etc/apache2/sites-enabled/ | grep pms
   ```

---

## ✅ Step 6: Get SSL Certificate (HTTPS)

**Once HTTP is working, add SSL:**

```bash
# Install certbot (if not already installed)
sudo apt update
sudo apt install certbot python3-certbot-apache -y

# Get SSL certificate for pms.ainitravel.com
sudo certbot --apache -d pms.ainitravel.com

# Follow prompts:
# 1. Enter email address
# 2. Agree to terms
# 3. Choose: Redirect HTTP to HTTPS (option 2)
```

**Certbot automatically:**
- Creates SSL certificate
- Updates Apache config
- Redirects HTTP → HTTPS
- Sets up auto-renewal (cert renews every 90 days)

---

## ✅ Step 7: Verify HTTPS Works

**Open browser:**

```
https://pms.ainitravel.com
```

**Should see:**
- 🔒 Padlock in browser (secure)
- Your PMS dashboard loads
- No certificate warnings

---

## ✅ Step 8: Update Your Code (If Needed)

**Check if any hardcoded URLs in your PHP files:**

```bash
# Search for old domain references
cd /var/www/pms.ainitravel.com
grep -r "108.175.12.152" .
grep -r "http://ainitravel.com" .
```

**If you find hardcoded URLs, update them to:**
```php
// OLD (bad):
$redirect_url = "http://108.175.12.152/dashboard.php";

// NEW (good):
$redirect_url = "https://pms.ainitravel.com/dashboard.php";

// BEST (dynamic):
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$redirect_url = "$protocol://$host/dashboard.php";
```

---

## ✅ Step 9: Update Database Connection (Important!)

**Check if db_connection.php has correct host:**

```bash
nano /var/www/pms.ainitravel.com/db_connection.php
```

**Make sure it points to localhost or correct DB:**

```php
<?php
$host = 'localhost';  // ✅ Good (DB on same server)
// NOT: $host = '108.175.12.152';  ❌ Bad

$database = 'hotel_booking_db';
$username = 'root';
$password = 'your_password';

$conn = new mysqli($host, $username, $password, $database);
?>
```

---

## ✅ Step 10: Test Everything

**Test these features:**

- [ ] Login works
- [ ] Dashboard loads
- [ ] Bookings display
- [ ] Can create new booking
- [ ] Images/CSS load (no broken resources)
- [ ] Database queries work
- [ ] File uploads work
- [ ] Logout works

**Check browser console for errors:**
- Press `F12` → Console tab
- Look for "Mixed Content" warnings (HTTP resources on HTTPS page)

---

## ✅ Step 11: Update Main Domain (Optional)

**If you want ainitravel.com to be a marketing site:**

**Create marketing site virtual host:**

```bash
sudo nano /etc/apache2/sites-available/ainitravel.com.conf
```

**Paste:**

```apache
<VirtualHost *:80>
    ServerName ainitravel.com
    ServerAlias www.ainitravel.com
    DocumentRoot /var/www/marketing
    
    <Directory /var/www/marketing>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/marketing_error.log
    CustomLog ${APACHE_LOG_DIR}/marketing_access.log combined
</VirtualHost>
```

**Create simple landing page:**

```bash
sudo mkdir -p /var/www/marketing
sudo nano /var/www/marketing/index.html
```

**Add simple HTML:**

```html
<!DOCTYPE html>
<html>
<head>
    <title>AiniTravel - Hotel Management Platform</title>
    <style>
        body { font-family: Arial; text-align: center; padding: 50px; }
        h1 { color: #6366f1; }
        .cta { 
            background: #6366f1; 
            color: white; 
            padding: 15px 30px; 
            text-decoration: none; 
            border-radius: 5px;
            display: inline-block;
            margin: 20px;
        }
    </style>
</head>
<body>
    <h1>AiniTravel</h1>
    <p>Modern Hotel Management Platform for Peru & Latin America</p>
    <a href="https://pms.ainitravel.com" class="cta">Login to PMS</a>
    <a href="#contact" class="cta">Contact Sales</a>
</body>
</html>
```

**Enable and get SSL:**

```bash
sudo a2ensite ainitravel.com.conf
sudo systemctl reload apache2
sudo certbot --apache -d ainitravel.com -d www.ainitravel.com
```

---

## ✅ Step 12: Clean Up (After Everything Works)

**Once pms.ainitravel.com is confirmed working:**

**Option A: Keep /var/www/html as backup**
```bash
# Just leave it there (uses minimal space)
```

**Option B: Remove old files**
```bash
# ONLY do this after 100% sure subdomain works!
sudo rm -rf /var/www/html/*
```

---

## 🎯 Final Directory Structure

```
/var/www/
├── pms.ainitravel.com/     ← PMS Application (HTTPS)
│   ├── dashboard.php
│   ├── bookings.php
│   ├── hotel_setup.php
│   ├── db_connection.php
│   └── ...all PMS files
│
├── marketing/              ← Landing Page (Optional)
│   └── index.html
│
└── html/                   ← Old location (can remove later)
    └── ...old files (backup)
```

---

## 🔧 Troubleshooting

### **Issue: pms.ainitravel.com doesn't load**

**Check 1: DNS**
```bash
nslookup pms.ainitravel.com
# Should return: 108.175.12.152
```

**Check 2: Apache config**
```bash
sudo apache2ctl -S
# Should list: pms.ainitravel.com
```

**Check 3: Firewall**
```bash
sudo ufw status
# Make sure port 80 and 443 are allowed
```

---

### **Issue: SSL certificate fails**

**Check DNS first:**
```bash
ping pms.ainitravel.com
```

**Try manual certificate:**
```bash
sudo certbot certonly --apache -d pms.ainitravel.com
```

**Check Apache SSL module:**
```bash
sudo a2enmod ssl
sudo systemctl restart apache2
```

---

### **Issue: CSS/Images not loading (Mixed Content)**

**Fix asset URLs in your code:**

```php
<!-- OLD (breaks on HTTPS): -->
<link href="http://pms.ainitravel.com/style.css">

<!-- NEW (protocol-relative): -->
<link href="//pms.ainitravel.com/style.css">

<!-- BEST (dynamic): -->
<link href="<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/style.css">
```

---

## 📋 Quick Command Checklist

**Run these commands in order:**

```bash
# 1. Create directory
sudo mkdir -p /var/www/pms.ainitravel.com
sudo cp -r /var/www/html/* /var/www/pms.ainitravel.com/
sudo chown -R www-data:www-data /var/www/pms.ainitravel.com

# 2. Create virtual host (use nano to paste config above)
sudo nano /etc/apache2/sites-available/pms.ainitravel.com.conf

# 3. Enable site
sudo a2ensite pms.ainitravel.com.conf
sudo apache2ctl configtest
sudo systemctl reload apache2

# 4. Get SSL
sudo certbot --apache -d pms.ainitravel.com

# 5. Test
curl https://pms.ainitravel.com
```

---

## ✅ Success Checklist

- [ ] DNS A record added for `pms` → `108.175.12.152`
- [ ] Directory created at `/var/www/pms.ainitravel.com`
- [ ] Files copied from `/var/www/html`
- [ ] Apache virtual host created
- [ ] Site enabled and Apache reloaded
- [ ] `http://pms.ainitravel.com` loads (HTTP test)
- [ ] SSL certificate installed
- [ ] `https://pms.ainitravel.com` loads with padlock 🔒
- [ ] Login works
- [ ] Database connections work
- [ ] No console errors (F12)
- [ ] All features tested

---

## 🚀 You're Done!

Your PMS is now running at:

**`https://pms.ainitravel.com`** 🎉

Professional, secure, and ready to scale!

