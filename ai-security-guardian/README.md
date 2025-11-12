# 🛡️ AI Security Guardian

**AI-powered security scanner with auto-fix for OWASP vulnerabilities**

Stop security issues before they reach production! # 🤖 AI Dev Engineer

> **Your AI pair programmer for production-grade code**

AI Dev Engineer helps you write secure, high-quality code by scanning for vulnerabilities, enforcing best practices, and teaching you professional development patterns.

## ✨ Features

### 🔍 **Smart Vulnerability Detection**
- **XSS (Cross-Site Scripting)** - Unsanitized user inputs
- **SQL Injection** - String interpolation in queries  
- **CSRF (Cross-Site Request Forgery)** - Missing token validation
- **Rate Limiting** - Missing DoS protection
- **Weak Cryptography** - MD5/SHA1 password hashing
- **Session Security** - Insecure cookie configurations

### 🤖 **One-Click Auto-Fix** (Pro)
Generate secure code automatically:
- Add rate limiting middleware
- Sanitize user inputs
- Add CSRF protection
- Convert to prepared statements
- Update to bcrypt password hashing

### 📊 **Real-Time Feedback**
- Red squiggly lines under vulnerabilities
- Severity indicators (Critical/High/Medium/Low)
- Line-by-line explanations
- Fix suggestions

### 🎯 **Security Score**
Track your code's security rating (0-100)

## 🚀 Quick Start

1. **Install** from VS Code Marketplace
2. **Open any .js or .php file**
3. **Right-click** → "AI Security: Scan Current File"
4. **View issues** and click "Auto-Fix" to secure your code!

## 💡 Usage

### Scan a File
```
Right-click in editor → AI Security: Scan Current File
```

### Auto-Fix Issues (Pro)
```
Right-click → AI Security: Auto-Fix All Issues
```

### Scan Entire Workspace (Pro)
```
Command Palette (Ctrl+Shift+P) → AI Security: Scan Entire Workspace
```

## 📸 Screenshots

### Before
```javascript
// ❌ Vulnerable Code
app.post('/api/swipe', async (req, res) => {
    const { message } = req.body;
    await db.query(`INSERT INTO messages VALUES ('${message}')`);
});
```

### After (Auto-Fixed)
```javascript
// ✅ Secure Code
const swipeLimiter = rateLimit({ windowMs: 60000, max: 100 });
const sanitizeInput = (input) => validator.escape(input);

app.post('/api/swipe', swipeLimiter, csrfProtection, async (req, res) => {
    const message = sanitizeInput(req.body.message);
    await db.query('INSERT INTO messages VALUES ($1)', [message]);
});
```

**Result:** Security score improved from 36% → 93%! 🎉

## 💰 Pricing

### Free Tier
✅ Scan 10 files/month  
✅ View vulnerabilities  
✅ Basic security tips  

### Pro - $9/month
✅ Unlimited scans  
✅ One-click auto-fix  
✅ Security test generation  
✅ Custom rules  
✅ Priority support  

[**Upgrade to Pro →**](https://securityai.dev/pricing)

## 🔐 Supported Languages

- JavaScript / TypeScript
- PHP
- More coming soon!

## 🎓 Learn More

- [OWASP Top 10](https://owasp.org/Top10/)
- [Security Best Practices](https://securityai.dev/learn)
- [Video Tutorials](https://securityai.dev/tutorials)

## 🐛 Found a Bug?

[Report it on GitHub →](https://github.com/juanmorellana2021/revolutionary-hotel-platform/issues)

## 📝 License

MIT

---

**Built with ❤️ by developers, for developers**

Protect your code. Protect your users. 🛡️
