# 🤖 AI Assistant Commands & Conventions

## Purpose
This document defines special commands and conventions for working with GitHub Copilot on the AiNi Travel Platform project.

---

## 📋 Command Reference

### 🔹 "Victor" Command

**Usage:** When the developer calls the AI "Victor"

**Action:** The AI should immediately:
1. Read the `SESSION_LOG.md` file
2. Refresh context about recent development sessions
3. Understand current project state
4. Resume from where we left off

**Example:**
```
Developer: "Victor, what did we do last time?"
AI: *Reads SESSION_LOG.md first, then responds with session summary*
```

**Why:** This ensures the AI has full context from previous sessions without requiring the developer to repeat everything.

---

## 📝 Active Conventions

### Session Documentation
- **Always create/update SESSION_LOG.md** at the end of each work session
- Include: What was done, files modified, next priorities, technical notes
- Format: Markdown with clear sections and emojis for readability

### Naming Clarifications
- **Developer:** juanmorellana2021 (GitHub username)
- **Project:** AiNi Travel Platform
- **Real Business:** Samay Wasi Casa de Paz (hotel in Cusco, Perú)
- **AI Assistant:** GitHub Copilot (not "Victor")

### File Management
- **Production VPS:** 108.175.12.152
- **SSH Alias:** `prod-vps`
- **Deployment:** Use SCP commands documented in SESSION_LOG.md
- **Database:** `hotel_booking_system` on production VPS

---

## 🔧 Standard Workflows

### When Starting a Session
1. Check if developer says "Victor" → Read SESSION_LOG.md
2. Review "NEXT SESSION PRIORITIES" section
3. Check "NOTES FOR NEXT SESSION" for important reminders
4. Confirm current state before making changes

### When Ending a Session
1. Update SESSION_LOG.md with:
   - Date and session summary
   - All implementations completed
   - Files created/modified
   - Next priorities
   - Important technical notes
2. Confirm all changes are deployed to production
3. Document any issues or blockers

### When Making Database Changes
1. Always use `hotel_properties` table (NOT `hotels`)
2. Test SQL locally first if possible
3. Document schema changes in SESSION_LOG.md
4. Keep backup of SQL scripts

### When Deploying Code
1. Test changes locally if possible
2. Use SCP to deploy to production
3. Verify deployment with curl or browser test
4. Document deployment in SESSION_LOG.md

---

## 📚 Key Documents

| Document | Purpose |
|----------|---------|
| `SESSION_LOG.md` | Recent session memory and context |
| `COMPREHENSIVE_README.md` | Overall project documentation |
| `SERVER_INFRASTRUCTURE_GUIDE.md` | VPS setup and configuration |
| `AI_ASSISTANT_COMMANDS.md` | This file - AI commands |

---

## 🎯 Response Guidelines

### When Developer Asks for Implementation
1. ✅ Explain what you'll do first
2. ✅ Show code/commands being executed
3. ✅ Deploy to production
4. ✅ Verify it works
5. ✅ Summarize what was accomplished

### When Developer Reports Issues
1. ✅ Read relevant files to understand context
2. ✅ Debug systematically
3. ✅ Explain the root cause
4. ✅ Implement fix
5. ✅ Verify fix works

### When Session Ends
1. ✅ Summarize accomplishments
2. ✅ Update SESSION_LOG.md
3. ✅ List what's ready for next session
4. ✅ Document any blockers or TODOs

---

## 🚨 Important Reminders

### Database
- ⚠️ Table name: `hotel_properties` (not `hotels`)
- ⚠️ Always check `is_active = 1` in queries
- ⚠️ JSON fields: `features`, `images`
- ⚠️ Real hotel ID: 1 (Samay Wasi Casa de Paz)

### Architecture
- ⚠️ Frontend: Tailwind CSS + jQuery
- ⚠️ Backend: PHP 8.2 + MySQL
- ⚠️ AI: Ollama on 72.60.1.16:11434
- ⚠️ Maps: Leaflet.js

### Mobile Responsive
- ⚠️ Breakpoint: 768px (mobile vs desktop)
- ⚠️ Hamburger menu for < 768px
- ⚠️ Vertical stack layout on mobile

### Fixed Positioning
- ⚠️ Header: z-50, top-0
- ⚠️ Search: z-40, top-16
- ⚠️ Mobile menu: z-45, top-16
- ⚠️ Content: z-20, top-32
- ⚠️ Footer: z-30, bottom-0

---

## 📅 Version History

**v1.0** - October 26, 2025
- Initial creation
- Documented "Victor" command
- Established session documentation conventions
- Listed key reminders and workflows

---

## 🔄 Updates

This document should be updated when:
- New commands are established
- Conventions change
- Important project decisions are made
- New workflows are standardized

---

*Last Updated: October 26, 2025*
*Maintained by: GitHub Copilot for juanmorellana2021*
