# 🚨 CRITICAL RULE: ALWAYS BACKUP BEFORE EDITING

## MANDATORY PRE-EDIT CHECKLIST

**BEFORE making ANY changes to a working file, you MUST:**

### 1. CREATE BACKUP
```powershell
Copy-Item filename.php filename_backup_YYYY-MM-DD.php
```

### 2. VERIFY BACKUP EXISTS
```powershell
Test-Path filename_backup_YYYY-MM-DD.php
# Must return: True
```

### 3. VERIFY BACKUP SIZE
```powershell
Get-Item filename.php, filename_backup_YYYY-MM-DD.php | Select-Object Name, Length
# Both files should have same size
```

### 4. THEN AND ONLY THEN - Make Changes

---

## WHY THIS RULE EXISTS

**Lesson learned:** 2025-11-13 - Accounting Dashboard Incident
- ❌ Skipped backup step
- ❌ Made changes that corrupted file encoding
- ❌ Created duplicate menu with broken emoji rendering (δŸ, δŸ·, etc.)
- ❌ Had to use git restore instead of simple file copy
- ❌ Wasted time troubleshooting instead of developing

**If backup existed:**
- ✅ Simple restore: `Copy-Item backup.php original.php`
- ✅ No git needed
- ✅ No encoding issues to debug
- ✅ 30 seconds vs 30 minutes

---

## SWARM COMMANDER PROTOCOL

**The Swarm Commander MUST enforce this rule:**

Before ANY autoFixer or agent starts work:
1. ✅ Check if backup exists
2. ❌ If NO backup → **HALT and CREATE BACKUP FIRST**
3. ✅ If backup exists → Proceed with work

**Add to autoFixer() function:**
```javascript
async autoFixer(stream, task) {
    stream.markdown(`# 🎯 AutoFixer - AI Swarm Activated\n\n`);
    
    // 🚨 CRITICAL: Verify backup exists
    const editor = vscode.window.activeTextEditor;
    if (editor) {
        const fileName = editor.document.fileName;
        const backupName = fileName.replace(/\.php$/, '_backup.php');
        
        if (!fs.existsSync(backupName)) {
            stream.markdown(`\n🚨 **BACKUP REQUIRED**\n`);
            stream.markdown(`No backup found. Creating backup first...\n\n`);
            stream.markdown(`\`\`\`\nCopy-Item ${fileName} ${backupName}\n\`\`\`\n`);
            stream.markdown(`\n⚠️ **Agent work HALTED until backup is verified**\n`);
            return;
        } else {
            stream.markdown(`✅ Backup verified: ${backupName}\n\n`);
        }
    }
    
    // Continue with normal swarm work...
}
```

---

## AGENT RESPONSIBILITY

**Every agent (BugHunter, UIEnhancer, CodeMerger, etc.) should:**
1. Assume backup EXISTS (Commander verified it)
2. If making destructive changes → Remind user backup exists
3. Report backup location in final summary

---

## USER INSTRUCTION COMPLIANCE

**User has stated this rule MULTIPLE times:**
- "make backup first"
- "save backup before editing"  
- "why didn't you make backup like I always tell you"

**This is NOT optional. This is MANDATORY.**

---

## EXCEPTION CASES

**ONLY skip backup if:**
- ✅ Creating a NEW file (doesn't exist yet)
- ✅ Making changes to a temporary test file
- ✅ Working in a dedicated backup/test directory

**ALL OTHER CASES: BACKUP FIRST!**

---

## PUNISHMENT FOR VIOLATION

If backup is skipped:
1. User loses trust in the system
2. Time wasted on recovery instead of progress
3. Risk of data loss
4. Broken functionality on production

**NEVER SKIP THE BACKUP. NEVER.**
