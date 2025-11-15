# 💰 Payment Backend Setup Guide

## 🎓 What You're Learning

**Serverless Functions:**
- No server management (Vercel handles it!)
- Pay per request (first 100K requests/month FREE)
- Auto-scaling (handles 1 user or 1 million)
- Global edge deployment

**Payment Integration:**
- Gumroad webhooks
- License key generation
- Subscription handling
- Refund processing

---

## 📦 Step 1: Deploy to Vercel (5 minutes)

### A. Install Vercel CLI

```bash
npm install -g vercel
```

### B. Login to Vercel

```bash
cd c:\xampp\htdocs\testapp\revolutionary-hotel-platform-github\security-ai-backend
vercel login
```

Enter your email → Click verification link

### C. Deploy

```bash
vercel --prod
```

**Output:**
```
✅ Production: https://security-ai-backend.vercel.app
```

**Your API is now live!** 🎉

---

## 🧪 Step 2: Test the API (2 minutes)

### Test License Validation

```bash
curl -X POST https://security-ai-backend.vercel.app/api/validate-license \
  -H "Content-Type: application/json" \
  -d '{"key": "DEMO-PRO-2024"}'
```

**Expected response:**
```json
{
  "valid": true,
  "tier": "pro",
  "email": "demo@example.com",
  "message": "License validated successfully"
}
```

### Test Invalid Key

```bash
curl -X POST https://security-ai-backend.vercel.app/api/validate-license \
  -H "Content-Type: application/json" \
  -d '{"key": "INVALID-KEY"}'
```

**Expected response:**
```json
{
  "valid": false,
  "tier": "free",
  "message": "Invalid license key"
}
```

---

## 💳 Step 3: Set Up Gumroad (10 minutes)

### A. Create Gumroad Account

1. Go to https://gumroad.com
2. Sign up (free!)
3. Verify email

### B. Create Product

1. Click **"Create a Product"**
2. Fill in:
   - **Name:** AI Security Guardian Pro
   - **Price:** $9/month
   - **Description:**
     ```
     Unlock unlimited security scans, one-click auto-fix, and priority support.
     
     What's included:
     ✅ Unlimited file scans
     ✅ One-click auto-fix
     ✅ Security test generation
     ✅ Custom security rules
     ✅ Priority email support
     
     License key will be emailed after purchase.
     ```
   - **Type:** Recurring (Monthly)
   - **Category:** Software/Tools

3. Click **"I'll handle delivery"** (we'll send license key via webhook)

### C. Set Up Webhook

1. Go to **Settings → Advanced → Webhooks**
2. Add webhook URL:
   ```
   https://security-ai-backend.vercel.app/api/gumroad-webhook
   ```
3. Enable events:
   - ✅ Sale completed
   - ✅ Subscription restarted
   - ✅ Refund processed
4. Save

### D. Get Your Product Link

Copy the product URL, looks like:
```
https://juanmorellana.gumroad.com/l/ai-security-guardian-pro
```

---

## 🔗 Step 4: Update Extension to Use Real API (5 minutes)

Update the extension to point to your live API:

```bash
cd c:\xampp\htdocs\testapp\revolutionary-hotel-platform-github\ai-security-guardian
```

Edit `lib/license-validator.js`:

```javascript
constructor(context) {
    this.context = context;
    // YOUR REAL API!
    this.apiEndpoint = 'https://security-ai-backend.vercel.app/api/validate-license';
    this.pricingUrl = 'https://juanmorellana.gumroad.com/l/ai-security-guardian-pro';
    this.cachedTier = null;
    this.cacheExpiry = null;
}
```

Then rebuild:

```bash
npm version patch
vsce package
code --install-extension ai-security-guardian-1.0.2.vsix
```

---

## 💰 Step 5: Test Real Purchase Flow (End-to-End)

1. **User installs extension** (from marketplace)
2. **User tries auto-fix** → Sees paywall
3. **User clicks "Upgrade"** → Goes to Gumroad
4. **User pays $9** → Gumroad processes payment
5. **Gumroad sends webhook** → Your API generates license key
6. **User receives email** with license key
7. **User enters key in extension** → Extension validates with API
8. **User gets Pro features** → Auto-fix unlocked!

---

## 📊 Revenue Tracking

### Gumroad Dashboard Shows:

- Total sales
- Monthly recurring revenue (MRR)
- Customer list with emails
- Refund rate
- Conversion rate

### Example After 1 Month:

```
Sales: 25 customers × $9 = $225/month
Gumroad fee (10%): -$22.50
Your net: $202.50/month

After 6 months: $202.50 × 6 × 20 customers = $24,300/year
```

---

## 🔒 Security Best Practices

### 1. Validate Gumroad Signature (Prevent Fake Webhooks)

```javascript
// In gumroad-webhook.js
const crypto = require('crypto');

function verifyGumroadSignature(body, signature, secret) {
  const hash = crypto
    .createHmac('sha256', secret)
    .update(JSON.stringify(body))
    .digest('hex');
  return hash === signature;
}

// Use it:
const signature = req.headers['x-gumroad-signature'];
if (!verifyGumroadSignature(req.body, signature, process.env.GUMROAD_SECRET)) {
  return res.status(401).json({ error: 'Invalid signature' });
}
```

### 2. Use Environment Variables

Create `.env` file:
```
GUMROAD_SECRET=your-webhook-secret
DATABASE_URL=postgresql://...
```

Add to `vercel.json`:
```json
{
  "env": {
    "GUMROAD_SECRET": "@gumroad-secret"
  }
}
```

### 3. Store Licenses in Database (Not In-Memory)

Upgrade to PostgreSQL:
```bash
npm install pg
```

```javascript
const { Pool } = require('pg');
const pool = new Pool({ connectionString: process.env.DATABASE_URL });

await pool.query(
  'INSERT INTO licenses (key, tier, email, active) VALUES ($1, $2, $3, $4)',
  [licenseKey, tier, email, true]
);
```

---

## 🎓 What You Learned

### Serverless Architecture:
- **No servers to manage** - Vercel handles everything
- **Auto-scaling** - Handles 1 or 1,000,000 requests
- **Pay per use** - Only pay for what you use (100K requests/month FREE)

### Payment Integration:
- **Webhook handling** - Receive real-time payment notifications
- **License generation** - Create unique keys for customers
- **Subscription management** - Handle monthly recurring revenue

### API Design:
- **RESTful endpoints** - POST /api/validate-license
- **CORS handling** - Allow browser requests
- **Error handling** - Graceful degradation
- **Security** - Signature verification

---

## 🚀 Next Steps

1. ✅ Deploy backend to Vercel
2. ✅ Set up Gumroad product
3. ✅ Test with demo license key
4. ✅ Update extension with real API URL
5. ⏭️ **Build landing page** (Step 9)
6. ⏭️ **Launch publicly** (Step 10)

---

## 💡 Advanced Features (Future)

### Email Automation:
Send license key automatically via SendGrid/Mailgun

### Analytics Dashboard:
Track active licenses, revenue, churn rate

### Team Plans:
Enterprise tier with multiple seats

### Trial Period:
14-day free trial before charging

---

**You now have a COMPLETE payment system!** 💰

From $0 → $9/month per customer, fully automated! 🎉
