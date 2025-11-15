# VERIFY FILE STRUCTURE BEFORE CREATING COMPONENTS RULE

## THE PROBLEM (2025-11-14)

**WHAT HAPPENED:**
Created reusable sidebar component (`modern_sidebar.php`) with hardcoded links to files that **DON'T EXIST** on production server:
- `manager_dashboard.php` ❌ (doesn't exist)
- `hotel_setup.php` ❌ (doesn't exist)
- `income_management.php` ❌ (unknown if exists)
- `expense_management.php` ❌ (unknown if exists)
- `employee_management.php` ❌ (unknown if exists)

**RESULT:**
User couldn't navigate to calendar or any other pages. All menu links broken.

**ROOT CAUSE:**
Made ASSUMPTIONS about file names instead of VERIFYING what files actually exist on the server.

---

## MANDATORY RULE: VERIFY BEFORE YOU CODE

### Before Creating ANY Component with Links/Paths:

1. **CHECK PRODUCTION SERVER FIRST**
   ```powershell
   ssh prod-vps "ls -la /var/www/html/manage/"
   ```
   OR
   ```powershell
   ssh prod-vps "find /var/www/html/manage/ -maxdepth 1 -name '*.php' | sort"
   ```

2. **CHECK LOCAL WORKSPACE**
   ```powershell
   Get-ChildItem *.php | Select-Object Name
   ```

3. **DOCUMENT ACTUAL FILES** in a comment at top of component:
   ```php
   /**
    * VERIFIED FILES (2025-11-14):
    * - dashboard.php (or accounting_dashboard.php?)
    * - calendar_view.php ✓
    * - room_management.php ✓
    * etc...
    */
   ```

4. **USE VARIABLES for uncertain paths:**
   ```php
   // If unsure, make it configurable
   $dashboardFile = $dashboardFile ?? 'accounting_dashboard.php';
   ```

5. **ASK USER TO CONFIRM** if file structure is unclear

---

## WHY THIS RULE EXISTS

**IMPACT OF VIOLATION:**
- ❌ Broken navigation = user can't access their own system
- ❌ Loss of trust and productivity
- ❌ Emergency rollback required
- ❌ Wasted time creating "reusable" components that don't work

**LESSON:**
Reusable components are ONLY useful if they use CORRECT paths. 
**VERIFY, don't ASSUME.**

---

## AGENT RESPONSIBILITY

**FileStructureValidator Agent** (to be created):
- MUST run before creating any component with file paths
- Scans production server directory structure
- Scans local workspace structure
- Creates map of actual files
- HALTS work if paths can't be verified
- Reports discrepancies between local and production

---

## EXCEPTION CASES

You CAN skip verification only if:
1. Creating NEW files (not linking to existing)
2. User explicitly provides exact file names
3. Working in isolated test environment

---

## PUNISHMENT FOR VIOLATION

- Broken production site
- User frustration and lost work time
- Damaged trust and credibility
- Emergency rollback operations

---

## USER'S INSTRUCTION

User has now flagged this as a **CRITICAL SYSTEMIC PROBLEM** that requires:
1. Memory bank documentation ✓ (this file)
2. New agent to prevent recurrence
3. Plan to implement prevention system

**USER'S EXACT WORDS:**
"i dont how or why this a big problem of the you i wnat yout note in memory bank and i want you to figuire what kind agents we need to prevent this again and give a plan to create this agent"

---

## RELATED RULES

- See: `BACKUP_FIRST_RULE.md` - Always backup before editing
- See: `ARCHITECTURE_ANALYZER` - Detects code issues
- New: **FileStructureValidator** - Verifies file existence before coding
