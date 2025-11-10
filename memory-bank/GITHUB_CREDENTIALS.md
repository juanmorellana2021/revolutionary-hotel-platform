# GitHub Configuration & Credentials

**Last Updated**: November 10, 2025

---

## 🔐 Personal Access Token (PAT)

**Token Name**: hotelapp  
**Created**: November 10, 2025  
**Expiration**: Check GitHub settings (typically 90 days or custom)  
**Scopes**: repo (full control of private repositories)

**Current Token**:
```
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
```

---

## 📦 Repository Information

**Repository Name**: revolutionary-hotel-platform  
**Owner**: juanmorellana2021  
**URL**: https://github.com/juanmorellana2021/revolutionary-hotel-platform  
**Current Branch**: main  
**Local Path**: c:\xampp\htdocs\testapp\revolutionary-hotel-platform-github\

---

## 🔧 Git Configuration Commands

### Save Token in Windows Credential Manager (One-time setup)
```powershell
# Enable credential helper
git config --global credential.helper wincred

# Next git push will prompt for credentials - use token as password
# Username: juanmorellana2021
# Password: [paste token above]
```

### Manual Authentication (if needed)
```powershell
# Set remote URL with token embedded (less secure, but works)
cd c:\xampp\htdocs\testapp\revolutionary-hotel-platform-github
git remote set-url origin https://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@github.com/juanmorellana2021/revolutionary-hotel-platform.git
```

---

## 📤 Git Push Workflow

### Standard Push Process
```powershell
# Navigate to repository
cd c:\xampp\htdocs\testapp\revolutionary-hotel-platform-github

# Check status
git status

# Stage all changes
git add .

# Commit with message
git commit -m "Your commit message here"

# Push to GitHub (will use saved credentials)
git push origin main
```

### First-Time Push with New Token
```powershell
cd c:\xampp\htdocs\testapp\revolutionary-hotel-platform-github
git add .
git commit -m "Add experience registration system"
git push origin main

# When prompted:
# Username: juanmorellana2021
# Password: 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
```

---

## 🚨 Token Security Best Practices

1. **Never commit token to repository** - Keep in this private memory file only
2. **Regenerate if exposed** - If token appears in public code, regenerate immediately
3. **Use credential manager** - Let Windows store it encrypted, don't hardcode in scripts
4. **Set expiration** - Tokens should expire (30-90 days) for security
5. **Minimal scopes** - Only grant necessary permissions (repo access)

---

## 🔄 Token Regeneration (when expires)

1. Go to: https://github.com/settings/tokens
2. Find "hotelapp" token
3. Click "Regenerate token"
4. Copy new token
5. Update this file
6. Update Windows Credential Manager (will prompt on next push)

---

## 📋 Common Git Commands

```powershell
# Check current branch
git branch

# Check remote URL
git remote -v

# Pull latest changes
git pull origin main

# View commit history
git log --oneline -10

# Discard local changes
git checkout -- .

# Create new branch
git checkout -b feature-name

# Switch back to main
git checkout main

# Delete local branch
git branch -d feature-name
```

---

## ⚠️ Troubleshooting

### "Authentication failed" Error
```powershell
# Clear stored credentials
git config --global --unset credential.helper
git config --global credential.helper wincred

# Next push will prompt for new credentials
```

### "Permission denied" Error
- Token may have expired
- Token may lack required scopes
- Regenerate token with 'repo' scope

### "Remote origin already exists" Error
```powershell
# View current remote
git remote -v

# Update remote URL
git remote set-url origin https://github.com/juanmorellana2021/revolutionary-hotel-platform.git
```

---

## 📁 Files to Commit (Pending)

Latest work (November 10, 2025):
- `create_experience_tables.sql` - Database migration
- `classes/ExperienceManager.php` - Business logic class
- `experience_register.php` - Registration form
- `experience_submit.php` - Form controller
- `memory-bank/EXPERIENCE_SYSTEM_ARCHITECTURE.md` - Documentation
- Updated `memory-bank/systemPatterns.md` (15 new patterns)
- Updated `memory-bank/progress.md` (experience system section)
- Updated `memory-bank/decisionLog.md` (15 new decisions)

---

**REMEMBER**: This token is like a password - keep it private, never share publicly, never commit to Git repository!
