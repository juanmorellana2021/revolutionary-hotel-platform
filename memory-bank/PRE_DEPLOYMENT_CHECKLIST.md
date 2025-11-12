# Pre-Deployment Checklist

Run through this checklist **BEFORE** deploying any changes to production server.

## 🔍 Code Review

- [ ] **Syntax Check**: Run `node -c filename.js` for JavaScript files
- [ ] **No Console Logs**: Remove or comment out debug console.log statements (except errors)
- [ ] **Error Handling**: All async functions wrapped in try/catch
- [ ] **Validation**: Input validation for all user-provided data
- [ ] **SQL Injection**: All queries use parameterized statements ($1, $2, etc.)
- [ ] **Authentication**: All protected endpoints check user authentication
- [ ] **Authorization**: Users can only access/modify their own data

## 💾 Backup Strategy

- [ ] **Backup Current Files**: 
  ```bash
  ssh social-vps "cp /path/to/file.ext /path/to/file.ext.backup"
  ```

- [ ] **Database Backup** (if schema changes):
  ```bash
  ssh social-vps "sudo -u postgres pg_dump aini_platform > /tmp/aini_platform_backup_$(date +%Y%m%d_%H%M%S).sql"
  ```

- [ ] **Config Backup** (if changing nginx/systemd):
  ```bash
  ssh social-vps "cp /etc/nginx/sites-available/ainiflow /tmp/ainiflow.conf.backup"
  ```

- [ ] **Verify Backup Created**: Check file exists and has content
  ```bash
  ssh social-vps "ls -lh /path/to/backup && head -5 /path/to/backup"
  ```

## 🧪 Local Testing

- [ ] **Create Test File**: Use `/tmp/` for testing before moving to production
  ```bash
  ssh social-vps "cat > /tmp/test_file.ext << 'EOF'
  [paste content]
  EOF"
  ```

- [ ] **Syntax Validation**: 
  ```bash
  ssh social-vps "node -c /tmp/test_file.js"
  ```

- [ ] **Line Count Check**: Ensure file is complete
  ```bash
  ssh social-vps "wc -l /tmp/test_file.ext"
  ```

- [ ] **API Test with curl**: Test endpoints before frontend integration
  ```bash
  ssh social-vps "curl -s http://localhost:3000/api/endpoint | head -20"
  ```

- [ ] **Database Query Test**: Test queries in psql before adding to code
  ```bash
  ssh social-vps "sudo -u postgres psql -d aini_platform -c 'SELECT * FROM table LIMIT 1;'"
  ```

## 📝 Documentation

- [ ] **Code Comments**: Complex logic has explanatory comments
- [ ] **API Documentation**: New endpoints documented in /memory-bank/
- [ ] **Database Changes**: Schema changes documented
- [ ] **Environment Variables**: New env vars documented in README

## 🚀 Deployment Steps

### 1. Upload Files
```bash
scp local_file.ext social-vps:/tmp/file.ext
```

### 2. Verify Upload
```bash
ssh social-vps "ls -lh /tmp/file.ext && md5sum /tmp/file.ext"
md5sum local_file.ext  # Compare checksums
```

### 3. Move to Production (with backup)
```bash
ssh social-vps "sudo cp /path/to/production/file.ext /path/to/production/file.ext.backup && sudo cp /tmp/file.ext /path/to/production/file.ext"
```

### 4. Set Permissions
```bash
ssh social-vps "sudo chown www-data:www-data /path/to/file.ext && sudo chmod 644 /path/to/file.ext"
```

### 5. Restart Service (if needed)
```bash
ssh social-vps "sudo systemctl restart ainiflow"
```

### 6. Verify Service Running
```bash
ssh social-vps "sudo systemctl status ainiflow | head -10"
```

## ✅ Post-Deployment Verification

- [ ] **Service Status**: Check service is active/running
  ```bash
  ssh social-vps "sudo systemctl status ainiflow"
  ```

- [ ] **Check Logs**: Look for errors
  ```bash
  ssh social-vps "sudo journalctl -u ainiflow -n 50 --no-pager"
  ```

- [ ] **Test Endpoint**: Verify API responds
  ```bash
  ssh social-vps "curl -s http://localhost:3000/api/endpoint"
  ```

- [ ] **Frontend Test**: Load page in browser (https://ainiflow.com/page.html)

- [ ] **Database Verify**: Check data saved correctly
  ```bash
  ssh social-vps "sudo -u postgres psql -d aini_platform -c 'SELECT COUNT(*) FROM table;'"
  ```

- [ ] **Error Check**: Monitor for new errors in next 5 minutes
  ```bash
  ssh social-vps "sudo journalctl -u ainiflow -f"
  ```

## 🔄 Rollback Plan

If something goes wrong:

### 1. Restore Backup File
```bash
ssh social-vps "sudo cp /path/to/file.ext.backup /path/to/file.ext"
```

### 2. Restart Service
```bash
ssh social-vps "sudo systemctl restart ainiflow"
```

### 3. Verify Restored
```bash
ssh social-vps "sudo systemctl status ainiflow"
```

### 4. Database Rollback (if needed)
```bash
ssh social-vps "sudo -u postgres psql -d aini_platform < /tmp/backup.sql"
```

## 🎯 Success Criteria

Deployment is successful when:

- [ ] Service shows `active (running)` status
- [ ] No error messages in logs
- [ ] API endpoints return expected data
- [ ] Frontend loads without console errors
- [ ] Database operations complete successfully
- [ ] Existing features still work (no regression)

## 📊 Risk Assessment

**Low Risk** (can deploy immediately):
- CSS changes
- HTML content updates
- New API endpoints (not modifying existing)
- Documentation updates

**Medium Risk** (extra testing required):
- JavaScript logic changes
- Database queries modified
- New dependencies added
- Authentication/authorization changes

**High Risk** (backup + staged rollout):
- Database schema changes
- Server configuration changes
- Breaking API changes
- Payment/wallet logic modifications

---

**Remember**: 
- It's better to deploy slowly and safely than to break production
- Always have a rollback plan
- Test in /tmp before moving to production
- Monitor logs after deployment
- Document what you changed and why
