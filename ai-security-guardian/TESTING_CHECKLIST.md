# 🧪 TESTING PLAN - AI Security Guardian

## ✅ Manual Testing Checklist

### Test 1: Scanner Detection (2 minutes)
1. Open `social_routes.js` in VS Code
2. Right-click in editor
3. Click **"AI Security: Scan Current File"**
4. Expected: Shows "⚠️ Found 6 security issues"
5. Click "View Issues"
6. Expected: See list with:
   - NO_RATE_LIMIT (2 issues)
   - UNBOUNDED_LIMIT (1 issue)
   - NO_INPUT_SANITIZATION (1 issue)
   - NO_CSRF_PROTECTION (2 issues)

**✅ PASS / ❌ FAIL:** _______

### Test 2: Red Squiggly Lines (1 minute)
1. After scanning, check editor
2. Expected: Red/yellow underlines on vulnerable lines
3. Hover over underlines
4. Expected: See error message with severity

**✅ PASS / ❌ FAIL:** _______

### Test 3: Auto-Fix (Free Tier Paywall) (2 minutes)
1. Click "Auto-Fix" button
2. Expected: Shows message "🔒 Auto-fix is a Pro feature. Upgrade for $9/month!"
3. Expected: Shows buttons "Upgrade to Pro" and "Enter License Key"
4. Click "Enter License Key"
5. Enter fake key: "test-key-123"
6. Expected: Saves but still shows as free tier

**✅ PASS / ❌ FAIL:** _______

### Test 4: Free Tier Limit (3 minutes)
1. Scan the same file 11 times (one more than limit)
2. Expected: First 10 scans work
3. Expected: 11th scan shows "Monthly scan limit reached"

**To reset usage:**
```javascript
// In VS Code Developer Tools (Ctrl+Shift+I)
// Go to Application → IndexedDB → Clear workspace state
```

**✅ PASS / ❌ FAIL:** _______

### Test 5: Command Palette (1 minute)
1. Press `Ctrl+Shift+P`
2. Type "AI Security"
3. Expected: See all commands:
   - AI Security: Scan Current File
   - AI Security: Scan Entire Workspace
   - AI Security: Auto-Fix All Issues
   - AI Security: Generate Security Tests
   - AI Security: Show Security Dashboard

**✅ PASS / ❌ FAIL:** _______

### Test 6: Context Menu (1 minute)
1. Right-click in any .js or .php file
2. Expected: See "AI Security: Scan Current File" in menu
3. Right-click in a .txt file
4. Expected: Should NOT see the menu option

**✅ PASS / ❌ FAIL:** _______

### Test 7: Dashboard (1 minute)
1. Command Palette → "AI Security: Show Security Dashboard"
2. Expected: Opens webview panel with stats (all 0s initially)

**✅ PASS / ❌ FAIL:** _______

### Test 8: PHP File Scanning (3 minutes)
1. Create test file `test-vuln.php`:
```php
<?php
session_start();
$name = $_POST['name'];
echo $name;
$query = "SELECT * FROM users WHERE id = " . $_GET['id'];
?>
```
2. Right-click → Scan
3. Expected: Detects XSS and SQL injection

**✅ PASS / ❌ FAIL:** _______

### Test 9: Error Handling (2 minutes)
1. Open a very large file (10,000+ lines)
2. Scan it
3. Expected: Completes without crashing
4. Open a file with special characters
5. Expected: Handles gracefully

**✅ PASS / ❌ FAIL:** _______

### Test 10: Extension Settings (2 minutes)
1. Go to Settings (Ctrl+,)
2. Search "AI Security Guardian"
3. Expected: See settings:
   - License Key (empty)
   - Auto Scan on Save (unchecked)
   - Severity (medium)
4. Toggle "Auto Scan on Save" to ON
5. Edit and save a .js file
6. Expected: Auto-scans on save

**✅ PASS / ❌ FAIL:** _______

---

## 🐛 Known Issues to Test

### Issue 1: Extension Not Activating
**Symptom:** Commands don't appear
**Fix:** Check VS Code output panel for errors
**Check:** extension.js line 1 - requires are correct

### Issue 2: Scanner Not Finding Issues
**Symptom:** "No issues found" on vulnerable code
**Fix:** Check regex patterns in security-scanner.js
**Check:** Patterns match actual code syntax

### Issue 3: Auto-Fix Crashes
**Symptom:** Extension crashes when clicking Auto-Fix
**Fix:** Check auto-fixer.js for undefined variables
**Check:** All templates are properly defined

---

## 🔧 Quick Fixes for Common Bugs

### If commands don't appear:
```bash
# Reload VS Code
Ctrl+Shift+P → "Developer: Reload Window"
```

### If scanner returns errors:
```bash
# Check Output panel
Ctrl+Shift+U → Select "AI Security Guardian"
```

### If extension won't install:
```bash
# Uninstall first
code --uninstall-extension juanmorellana.ai-security-guardian

# Reinstall
code --install-extension ai-security-guardian-1.0.0.vsix
```

---

## 📊 Expected Results

After all tests pass:
- ✅ Scanner detects 6+ vulnerabilities correctly
- ✅ UI shows proper messages and buttons
- ✅ Free tier limits work
- ✅ Commands appear in palette and context menu
- ✅ Works on both .js and .php files
- ✅ No crashes or errors
- ✅ Settings are accessible

---

## 🎬 Next Steps After Testing

1. **If all tests pass:**
   - Commit final version
   - Publish to marketplace
   - Continue to Step 8 (Payment backend)

2. **If tests fail:**
   - Note which tests failed
   - Debug and fix issues
   - Re-package: `vsce package`
   - Re-install and test again

---

## 🧪 Advanced Testing (Optional)

### Performance Test:
- Scan 100 files at once
- Should complete in < 30 seconds

### Memory Test:
- Scan large codebase (1000+ files)
- Check memory usage in Task Manager
- Should stay under 500MB

### Edge Cases:
- Empty file → Should show "No issues"
- File with only comments → Should show "No issues"
- Malformed code → Should not crash

---

## ✅ READY TO TEST?

**Steps:**
1. Open VS Code
2. Open `social_routes.js`
3. Right-click → "AI Security: Scan Current File"
4. Run through the checklist above
5. Report back which tests passed/failed!

**Let me know what happens!** 🚀
