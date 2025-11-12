# CI/CD Deployment Setup Guide

## 🚀 Quick Start

### 1. GitHub Secrets Setup
Add these secrets to your GitHub repository (Settings → Secrets → Actions):

```
VPS_HOST=your-vps-ip-address
VPS_USERNAME=your-ssh-username
VPS_SSH_KEY=your-private-ssh-key
VPS_PORT=22
TELEGRAM_BOT_TOKEN=your-telegram-bot-token (optional)
TELEGRAM_CHAT_ID=your-telegram-chat-id (optional)
```

### 2. VPS Preparation

```bash
# SSH into your VPS
ssh your-vps

# Navigate to app directory
cd /var/www/aini-platform

# Make scripts executable
chmod +x run-migrations.sh rollback.sh pre-deploy-check.sh

# Create logs directory
mkdir -p logs

# Create backups directory
sudo mkdir -p /var/backups/aini-platform
sudo chown $USER:$USER /var/backups/aini-platform

# Test PM2 setup
pm2 list
pm2 save
pm2 startup  # Follow the command it outputs
```

### 3. Test Deployment Manually

```bash
# On your local machine
cd revolutionary-hotel-platform-github

# Run pre-deployment checks
bash pre-deploy-check.sh

# If all checks pass, push to GitHub
git add .
git commit -m "Test deployment"
git push origin main

# Watch GitHub Actions: github.com/your-username/revolutionary-hotel-platform/actions
```

---

## 📋 How It Works

### Deployment Flow:

```
1. Push to main branch
   ↓
2. GitHub Actions triggered
   ↓
3. Pre-Deployment Checks
   - Run tests
   - Validate syntax
   - Check dependencies
   ↓
4. Deploy to VPS
   - Store current commit (for rollback)
   - Pull latest code
   - Install dependencies
   - Run migrations
   - Restart PM2
   ↓
5. Health Check
   - Ping /health endpoint
   - If fails → Auto rollback
   - If success → Continue
   ↓
6. Post-Deployment
   - Warm up cache
   - Create deployment tag
   - Send notifications
   ↓
7. Done! ✅
```

---

## 🔄 Rollback Procedure

### Automatic Rollback
If health check fails, GitHub Actions automatically rolls back.

### Manual Rollback
```bash
# SSH into VPS
ssh your-vps

# Run rollback script
cd /var/www/aini-platform
bash rollback.sh

# Or manual rollback:
git log --oneline -5  # Find previous commit
git reset --hard <commit-hash>
pm2 restart aini-platform
```

---

## 🗄️ Database Migrations

### Creating a Migration

```bash
# Create new migration file (numbered sequentially)
cat > migrations/002_add_user_column.sql << 'EOF'
-- Add email column to users
ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(255);

-- Index for email lookups
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
EOF
```

### Running Migrations

Migrations run automatically during deployment. To run manually:

```bash
ssh your-vps
cd /var/www/aini-platform
bash run-migrations.sh
```

### Migration Naming Convention
- `001_create_migrations_table.sql` (setup)
- `002_add_user_email.sql`
- `003_create_notifications_table.sql`
- etc.

---

## 📊 Monitoring Deployments

### GitHub Actions Dashboard
View deployment status: `https://github.com/your-username/revolutionary-hotel-platform/actions`

### VPS Logs
```bash
# PM2 logs
pm2 logs aini-platform

# Migration logs
cat /var/www/aini-platform/logs/migrations.log

# Deployment history
git log --oneline -10
```

### Health Checks
```bash
# Check application health
curl https://ainiflow.com/health

# Check metrics
curl https://ainiflow.com/metrics
```

---

## 🔔 Notifications Setup

### Telegram (Recommended)

1. **Create Bot:**
   - Message `@BotFather` on Telegram
   - Send `/newbot`
   - Follow prompts, get bot token

2. **Get Chat ID:**
   - Message your bot to activate it
   - Message `@userinfobot`
   - Copy your chat ID

3. **Add to GitHub Secrets:**
   - `TELEGRAM_BOT_TOKEN`: Your bot token
   - `TELEGRAM_CHAT_ID`: Your chat ID

4. **Uncomment in deploy.yml:**
   - Find the `curl` commands in notification steps
   - Remove the `#` to enable

### Discord

1. Server Settings → Integrations → Webhooks
2. Create webhook, copy URL
3. Add `DISCORD_WEBHOOK_URL` to GitHub secrets
4. Use `send_discord_notification` function

### Slack

1. Create app at api.slack.com/apps
2. Enable Incoming Webhooks
3. Add `SLACK_WEBHOOK_URL` to GitHub secrets
4. Use `send_slack_notification` function

---

## 🛡️ Security Best Practices

### SSH Key Setup
```bash
# Generate SSH key (if you don't have one)
ssh-keygen -t ed25519 -C "github-actions"

# Copy public key to VPS
ssh-copy-id -i ~/.ssh/id_ed25519.pub user@your-vps

# Copy PRIVATE key to GitHub secrets
cat ~/.ssh/id_ed25519  # Copy this to VPS_SSH_KEY secret
```

### Environment Variables
```bash
# On VPS, create .env file
cat > /var/www/aini-platform/.env << 'EOF'
NODE_ENV=production
DATABASE_URL=postgresql://...
REDIS_URL=redis://localhost:6379
SESSION_SECRET=your-secret-here
EOF

# Protect it
chmod 600 .env
```

---

## 🧪 Testing Before Production

### Staging Environment (Optional)
```yaml
# Add to deploy.yml for staging branch
on:
  push:
    branches: [ staging ]
```

### Manual Deployment Trigger
Already included! Go to Actions → Deploy to Production → Run workflow

---

## ⚡ Performance Tips

### Faster Deployments
```bash
# Use npm ci instead of npm install (already configured)
# Skip devDependencies in production
npm ci --production

# Enable PM2 cluster mode for zero-downtime
pm2 start index.js -i max --name aini-platform
```

### Cache Dependencies
Already configured in workflow:
```yaml
- uses: actions/setup-node@v3
  with:
    cache: 'npm'  # Caches node_modules
```

---

## 🐛 Troubleshooting

### Deployment Failed: "Permission Denied"
```bash
# Check SSH key permissions
chmod 600 ~/.ssh/id_ed25519

# Verify SSH access
ssh -i ~/.ssh/id_ed25519 user@your-vps
```

### Health Check Failing
```bash
# Check if app is running
ssh your-vps "pm2 list"

# Check logs
ssh your-vps "pm2 logs aini-platform --lines 50"

# Manual health check
ssh your-vps "curl http://localhost:3000/health"
```

### Rollback Not Working
```bash
# Check last deployment info
cat /tmp/last_deploy.txt

# Find previous commits
git log --oneline -10

# Manual reset
git reset --hard <commit-hash>
pm2 restart aini-platform
```

### Migration Failed
```bash
# Check migration logs
cat logs/migrations.log

# Check what's applied
ssh your-vps "sudo -u postgres psql -d aini_platform -c 'SELECT * FROM migrations_log ORDER BY applied_at DESC LIMIT 10;'"

# Manually run specific migration
ssh your-vps "sudo -u postgres psql -d aini_platform -f migrations/002_example.sql"
```

---

## 📈 Metrics to Track

After deployment, monitor:

1. **Response Times:** Should stay under 500ms
2. **Error Rate:** Should be <1%
3. **Memory Usage:** Watch for leaks
4. **Cache Hit Rate:** Should be >80%
5. **Database Connections:** Should not grow indefinitely

Check via: `https://ainiflow.com/monitoring`

---

## ✅ Deployment Checklist

Before every deployment:

- [ ] Tests passing locally (`npm test`)
- [ ] No uncommitted changes
- [ ] Database migrations tested
- [ ] Environment variables set
- [ ] Backup created (automatic)
- [ ] Notifications configured
- [ ] Rollback plan ready

---

## 🎯 Next Steps

1. **Set up secrets** in GitHub
2. **Test deployment** with a small change
3. **Configure notifications** (Telegram recommended)
4. **Monitor first deployment** closely
5. **Document any custom migrations** needed

**First Deployment:**
```bash
# Make a small change
echo "# Test deployment" >> README.md
git add README.md
git commit -m "Test: First automated deployment"
git push origin main

# Watch it deploy!
```

🚀 **You now have enterprise-grade CI/CD!**
