# AGENT PLAN: FileStructureValidator

## ✅ STATUS: IMPLEMENTED (2025-11-14)

**Implementation Complete:**
- ✅ FileStructureValidator agent created in swarm-agents.js
- ✅ Integrated into autoFixer() workflow
- ✅ Scans production server via SSH
- ✅ Validates all href and include/require references
- ✅ Detects broken links and halts deployment
- ✅ Shows line numbers and available files

**Location:** `ai-dev-engineer-extension/lib/swarm-agents.js` (lines ~765-925)

---

## PURPOSE
Prevent broken links by verifying file/path existence BEFORE creating components or modifying code that references other files.

---

## AGENT SPECIFICATION

### Name
**FileStructureValidator**

### Trigger Conditions
Runs AUTOMATICALLY before ANY operation that:
1. Creates navigation menus/sidebars
2. Creates components with `<a href="">` links
3. Adds `include()`, `require()`, or similar file references
4. Modifies routing or URL paths
5. Creates forms with `action=""` attributes pointing to other files

### Core Functions

#### 1. `scanProductionServer(directory)`
```javascript
async scanProductionServer(directory) {
    // SSH to production and list files
    const command = `ssh prod-vps "find ${directory} -maxdepth 1 -type f -name '*.php' | sort"`;
    const result = await executeCommand(command);
    return result.files; // Array of actual PHP files
}
```

#### 2. `scanLocalWorkspace(directory)`
```javascript
scanLocalWorkspace(directory) {
    const fs = require('fs');
    const path = require('path');
    return fs.readdirSync(directory)
        .filter(file => file.endsWith('.php'))
        .sort();
}
```

#### 3. `compareStructures(production, local)`
```javascript
compareStructures(production, local) {
    return {
        inProductionOnly: production.filter(f => !local.includes(f)),
        inLocalOnly: local.filter(f => !production.includes(f)),
        inBoth: production.filter(f => local.includes(f))
    };
}
```

#### 4. `validateLinks(fileContent, availableFiles)`
```javascript
validateLinks(fileContent, availableFiles) {
    // Extract all href="..." and include/require references
    const hrefRegex = /href=["']([^"']+\.php)["']/g;
    const includeRegex = /(?:include|require)(?:_once)?\s*\(?\s*["']([^"']+)["']/g;
    
    const links = [];
    let match;
    
    while ((match = hrefRegex.exec(fileContent)) !== null) {
        links.push({type: 'href', path: match[1], line: getLineNumber(fileContent, match.index)});
    }
    
    while ((match = includeRegex.exec(fileContent)) !== null) {
        links.push({type: 'include', path: match[1], line: getLineNumber(fileContent, match.index)});
    }
    
    // Check each link
    const broken = links.filter(link => {
        const filename = path.basename(link.path);
        return !availableFiles.includes(filename);
    });
    
    return {
        totalLinks: links.length,
        brokenLinks: broken,
        validLinks: links.length - broken.length
    };
}
```

#### 5. `haltIfBroken(validation)`
```javascript
haltIfBroken(validation) {
    if (validation.brokenLinks.length > 0) {
        stream.markdown(`## 🚨 CRITICAL: BROKEN FILE REFERENCES DETECTED\n\n`);
        stream.markdown(`**SWARM WORK HALTED**\n\n`);
        stream.markdown(`Found ${validation.brokenLinks.length} broken link(s):\n\n`);
        
        validation.brokenLinks.forEach(link => {
            stream.markdown(`- **Line ${link.line}**: \`${link.path}\` (${link.type}) ❌ NOT FOUND\n`);
        });
        
        stream.markdown(`\n**ACTION REQUIRED:**\n`);
        stream.markdown(`1. Verify actual files on production server\n`);
        stream.markdown(`2. Update links to match existing files\n`);
        stream.markdown(`3. Or create missing files if needed\n\n`);
        stream.markdown(`See: memory-bank/VERIFY_FILE_STRUCTURE_RULE.md\n\n`);
        
        return {
            error: 'BROKEN_LINKS',
            message: 'File structure validation failed',
            brokenLinks: validation.brokenLinks
        };
    }
    
    return {success: true};
}
```

---

## INTEGRATION POINTS

### 1. Update `swarm-agents.js`

Add FileStructureValidator to swarm agents array:

```javascript
const agents = [
    bugHunter,
    backupFinder,
    codeMerger,
    inputValidator,
    formHardener,
    uiEnhancer,
    backendValidator,
    databaseValidator,
    securityGuard,
    productionDeployer,
    autoFixer,
    architectureAnalyzer,
    fileStructureValidator  // NEW AGENT
];
```

### 2. Update `autoFixer()` Function

Add FileStructureValidator check BEFORE any component creation:

```javascript
async function autoFixer(stream, filePath, fileContent) {
    // Existing backup check...
    
    // NEW: File structure validation
    if (isComponentWithLinks(fileContent)) {
        const productionFiles = await fileStructureValidator.scanProductionServer('/var/www/html/manage/');
        const localFiles = fileStructureValidator.scanLocalWorkspace('.');
        const validation = fileStructureValidator.validateLinks(fileContent, productionFiles);
        
        const result = fileStructureValidator.haltIfBroken(validation);
        if (result.error) {
            return result;
        }
    }
    
    // Continue with existing agents...
}

function isComponentWithLinks(content) {
    return content.includes('href=') || 
           content.includes('include') || 
           content.includes('require');
}
```

---

## WORKFLOW

```
User requests component creation
    ↓
Swarm Commander starts autoFixer()
    ↓
BackupFinder checks for backup ✓
    ↓
FileStructureValidator scans production server ← NEW
    ↓
FileStructureValidator scans local workspace ← NEW
    ↓
FileStructureValidator compares structures ← NEW
    ↓
FileStructureValidator validates all links in code ← NEW
    ↓
    ├─ Broken links found? → HALT, show error, require fix
    └─ All links valid? → Continue with other agents
```

---

## SUCCESS CRITERIA

✅ Agent detects when files referenced don't exist
✅ Agent halts work before deploying broken components
✅ Agent shows clear error messages with line numbers
✅ Agent suggests actual files that exist
✅ Prevents "modern_sidebar.php" type incidents

---

## IMPLEMENTATION STEPS

### Phase 1: Core Agent Creation (Priority 1)
1. Create `FileStructureValidator` agent class
2. Implement `scanProductionServer()` method
3. Implement `scanLocalWorkspace()` method
4. Implement `validateLinks()` method
5. Implement `haltIfBroken()` method
6. Add unit tests

### Phase 2: Integration (Priority 1)
1. Add agent to swarm-agents.js array
2. Update autoFixer() to call FileStructureValidator
3. Add `isComponentWithLinks()` helper function
4. Test with sample broken sidebar

### Phase 3: Enhancement (Priority 2)
1. Add smart suggestions (fuzzy match similar filenames)
2. Cache file structure to avoid repeated SSH calls
3. Add support for relative paths
4. Add support for subdirectories

### Phase 4: Documentation (Priority 2)
1. Update swarm-agents.js comments
2. Add examples to VERIFY_FILE_STRUCTURE_RULE.md
3. Create test cases in memory-bank

---

## ESTIMATED EFFORT

- **Phase 1**: 2-3 hours (core implementation)
- **Phase 2**: 1 hour (integration)
- **Phase 3**: 2 hours (enhancements)
- **Phase 4**: 30 minutes (documentation)

**Total**: ~5-6 hours development time

---

## DEPENDENCIES

- Node.js `fs` module (already available)
- SSH access to production server (already configured)
- Existing swarm-agents.js structure

---

## TESTING PLAN

### Test Case 1: Detect Broken Sidebar
```javascript
// Create sidebar with wrong links
const brokenSidebar = `
<a href="manager_dashboard.php">Dashboard</a>
<a href="hotel_setup.php">Settings</a>
`;

// Should HALT and report:
// - manager_dashboard.php NOT FOUND
// - hotel_setup.php NOT FOUND
```

### Test Case 2: Pass Valid Sidebar
```javascript
// Create sidebar with correct links
const validSidebar = `
<a href="accounting_dashboard.php">Dashboard</a>
<a href="calendar_view.php">Calendar</a>
`;

// Should PASS validation
```

### Test Case 3: Detect Broken Include
```javascript
// Create file with wrong include
const brokenInclude = `
<?php include 'includes/nonexistent_file.php'; ?>
`;

// Should HALT and report broken include
```

---

## MONITORING & METRICS

Track:
- Number of times agent prevents broken links
- Number of broken links detected per session
- Time saved by preventing deployment of broken code
- User satisfaction (fewer broken navigation incidents)

---

## FUTURE ENHANCEMENTS

1. **Auto-fix suggestions**: Offer to fix broken links automatically
2. **Database validation**: Check if linked files query correct tables
3. **API endpoint validation**: Verify AJAX calls point to valid endpoints
4. **Cross-reference validation**: Ensure form actions match POST handlers
5. **Documentation generation**: Auto-create sitemap from validated links
