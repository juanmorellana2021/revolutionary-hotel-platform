# Investor Portal Deployment Checklist

## Files Created (Ready for Upload)

### Core Pages
- ✅ `investor-login.php` - Login page for returning investors
- ✅ `investor-portal.php` - Main investor dashboard with downloads
- ✅ `investor-access.html` - NDA signing page (UPDATED - now redirects to portal)
- ✅ `investors.html` - Presentation deck (UPDATED - added PDF download button)
- ✅ `log_investor_activity.php` - Activity tracking API

### Documents
- ✅ `INVESTMENT_RISK_ANALYSIS.md` - Comprehensive risk study

### Existing Files (Already Deployed)
- `log_nda.php` - NDA submission handler
- `investor-admin.php` - Admin dashboard
- `admin-login.php` - Admin authentication
- `db_connection.php` - Database connection

## Upload to Production

Upload these files to `/var/www/html/ainitravel.com/`:

```bash
# New files
investor-login.php
investor-portal.php
log_investor_activity.php
INVESTMENT_RISK_ANALYSIS.md

# Updated files
investor-access.html
investors.html
```

## User Flows

### New Investor Flow
1. Visit `investor-access.html` (or click link from email)
2. Fill out NDA form and sign digitally
3. Automatically redirected to `investor-portal.php`
4. Can view presentation, download risk analysis, contact CEO

### Returning Investor Flow
1. Visit `investor-login.php`
2. Enter email address
3. System verifies NDA signature exists
4. Redirected to `investor-portal.php`
5. Access all materials + download options

### Portal Features
- 📊 View interactive presentation
- ⚠️ Download risk analysis document
- 📥 PDF export of presentation (via browser print)
- 📧 Direct contact to CEO
- 📝 Activity tracking (what they viewed, when)
- 🔐 Session-based authentication

## Admin Features (Already Deployed)

Admins can:
- View all NDA submissions at `investor-admin.php`
- See investor activity logs
- Update investor status
- View signatures
- Track engagement

## Testing Locally (Before Deploy)

1. Open `investor-access.html` in browser
2. Sign NDA (use test email)
3. Should redirect to `investor-portal.php`
4. Test all download links
5. Logout and test `investor-login.php`

## Production URLs (After Deploy)

- Public NDA: https://ainitravel.com/investor-access.html
- Investor Login: https://ainitravel.com/investor-login.php
- Investor Portal: https://ainitravel.com/investor-portal.php
- Admin Dashboard: https://ainitravel.com/investor-admin.php

## Email Templates

### New Investor Invitation
```
Subject: AiniTravel Series A - Investor Materials

Hi [Name],

Thank you for your interest in AiniTravel's Series A fundraising round.

To access our investor presentation and materials, please sign our NDA:
https://ainitravel.com/investor-access.html

Once signed, you'll have immediate access to:
- Interactive pitch deck
- Financial projections & data visualizations
- Comprehensive risk analysis
- Direct contact to our CEO

Questions? Reply to this email or contact juan.ceo@ainitravel.com

Best,
Juan Morellana
Founder & CEO, AiniTravel
```

### Returning Investor Login
```
Subject: Access Your AiniTravel Investor Materials

Hi [Name],

Access your investor portal anytime:
https://ainitravel.com/investor-login.php

Use the email address where you received this message.

Best,
Juan Morellana
```

## Security Notes

- All NDA submissions are logged with IP address and timestamp
- Session-based authentication (30-minute timeout)
- Activity tracking for compliance
- Signatures stored securely in `/signatures/` directory
- Admin notifications for new NDAs

## Next Steps

1. ✅ Local testing complete
2. ⏳ Upload files to production server
3. ⏳ Test production URLs
4. ⏳ Send test NDA invitation to yourself
5. ⏳ Verify full flow works on production
6. ✅ Ready to send to real investors!

---

**Deployment Commands** (when SSH is available):

```bash
# Upload new/updated files
scp investor-login.php root@68.183.137.166:/var/www/html/ainitravel.com/
scp investor-portal.php root@68.183.137.166:/var/www/html/ainitravel.com/
scp log_investor_activity.php root@68.183.137.166:/var/www/html/ainitravel.com/
scp investor-access.html root@68.183.137.166:/var/www/html/ainitravel.com/
scp investors.html root@68.183.137.166:/var/www/html/ainitravel.com/
scp INVESTMENT_RISK_ANALYSIS.md root@68.183.137.166:/var/www/html/ainitravel.com/

# Verify permissions
ssh root@68.183.137.166 "chmod 644 /var/www/html/ainitravel.com/investor-*.php"
ssh root@68.183.137.166 "chown www-data:www-data /var/www/html/ainitravel.com/investor-*.php"
```
