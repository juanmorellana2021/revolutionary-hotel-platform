# Accessibility Guide for AiniFlow

## 🎯 WCAG 2.1 AA Compliance Checklist

### ✅ Keyboard Navigation
All interactive elements must be keyboard accessible:

```html
<!-- ✅ Good: Keyboard accessible button -->
<button 
    @keydown.enter="handleAction()"
    @keydown.space.prevent="handleAction()"
    tabindex="0"
    aria-label="Submit form">
    Submit
</button>

<!-- ✅ Good: Keyboard accessible custom component -->
<div 
    role="button"
    tabindex="0"
    @keydown.enter="onClick()"
    @keydown.space.prevent="onClick()"
    @click="onClick()">
    Custom Button
</div>

<!-- ❌ Bad: No keyboard access -->
<div @click="onClick()">Click me</div>
```

### ✅ ARIA Labels
Provide context for screen readers:

```html
<!-- Form inputs -->
<input 
    type="text" 
    aria-label="User email address"
    aria-required="true"
    aria-invalid="false">

<!-- Buttons with icons only -->
<button aria-label="Close modal">
    <svg>...</svg>
</button>

<!-- Loading states -->
<button aria-busy="true" aria-label="Loading, please wait">
    <span class="sr-only">Loading...</span>
    <svg class="animate-spin">...</svg>
</button>

<!-- Status updates -->
<div role="status" aria-live="polite">
    Form submitted successfully
</div>
```

### ✅ Focus Indicators
Always show focus state:

```css
/* ✅ Good: Visible focus indicator */
button:focus {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}

button:focus-visible {
    ring: 2px solid var(--color-primary);
    ring-offset: 2px;
}

/* ❌ Bad: Removing focus */
button:focus {
    outline: none;  /* Never do this! */
}
```

### ✅ Color Contrast
Maintain 4.5:1 contrast ratio for text:

```css
/* ✅ Good: High contrast */
.text-primary {
    color: #0f172a;  /* On white: 16.1:1 */
}

.text-secondary {
    color: #64748b;  /* On white: 4.6:1 */
}

/* ❌ Bad: Low contrast */
.text-gray {
    color: #cbd5e1;  /* On white: 1.8:1 - fails! */
}
```

### ✅ Screen Reader Text
Hide visually but keep for screen readers:

```css
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}
```

```html
<button>
    <svg aria-hidden="true">...</svg>
    <span class="sr-only">Submit form</span>
</button>
```

---

## 📋 Component Accessibility Patterns

### Button Component

```html
<button 
    x-data="AiniButton()"
    :class="classes"
    :disabled="disabled || loading"
    :aria-busy="loading"
    :aria-label="loading ? 'Loading, please wait' : undefined"
    type="button">
    <svg x-show="loading" aria-hidden="true" class="animate-spin">...</svg>
    <span>{{ label }}</span>
</button>
```

### Modal Component

```html
<div 
    x-data="AiniModal()" 
    x-show="open" 
    role="dialog"
    aria-modal="true"
    :aria-labelledby="'modal-title-' + $id('modal')"
    @keydown.escape.window="hide()"
    @open-modal.window="show()"
    x-trap.noescape="open">
    
    <div class="modal-backdrop" @click="hide()" aria-hidden="true"></div>
    
    <div class="modal-content">
        <h2 :id="'modal-title-' + $id('modal')">{{ title }}</h2>
        <button 
            @click="hide()" 
            aria-label="Close modal"
            class="close-button">
            ×
        </button>
        
        <div role="document">
            <!-- Content -->
        </div>
    </div>
</div>
```

### Form Input Component

```html
<div x-data="AiniInput()">
    <label 
        :for="$id('input')" 
        class="block text-sm font-semibold mb-2">
        {{ label }}
        <span x-show="required" aria-label="required">*</span>
    </label>
    
    <input 
        :id="$id('input')"
        :type="type"
        x-model="value"
        :placeholder="placeholder"
        :class="inputClasses"
        :aria-required="required"
        :aria-invalid="!!error"
        :aria-describedby="error ? $id('input') + '-error' : undefined"
        @blur="validate()">
    
    <p 
        x-show="error" 
        :id="$id('input') + '-error'"
        role="alert"
        class="text-red-500 text-sm mt-1">
        {{ error }}
    </p>
</div>
```

### Toast/Notification Component

```html
<div 
    x-data="AiniToast()" 
    class="toast-container"
    aria-live="polite"
    aria-atomic="true">
    
    <template x-for="notification in notifications">
        <div 
            role="status"
            :aria-label="notification.message"
            class="toast">
            <span>{{ notification.message }}</span>
            <button 
                @click="remove(notification.id)"
                aria-label="Dismiss notification">
                ×
            </button>
        </div>
    </template>
</div>
```

---

## ⌨️ Keyboard Shortcuts

### Global Shortcuts

```javascript
// Add to your main app
document.addEventListener('keydown', (e) => {
    // Open search: Cmd/Ctrl + K
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        openSearch();
    }
    
    // Open help: Shift + ?
    if (e.shiftKey && e.key === '?') {
        e.preventDefault();
        openHelp();
    }
    
    // Escape: Close all modals/dropdowns
    if (e.key === 'Escape') {
        closeAllOverlays();
    }
});
```

### Component Shortcuts

```html
<!-- Tabs: Arrow keys -->
<div 
    x-data="AiniTabs()"
    @keydown.arrow-left="switchTab(activeTab - 1)"
    @keydown.arrow-right="switchTab(activeTab + 1)">
    <button 
        @click="switchTab(0)"
        :tabindex="activeTab === 0 ? 0 : -1"
        role="tab"
        :aria-selected="activeTab === 0">
        Tab 1
    </button>
</div>

<!-- Dropdown: Enter/Space to toggle -->
<div x-data="AiniDropdown()">
    <button 
        @click="toggle()"
        @keydown.enter="toggle()"
        @keydown.space.prevent="toggle()"
        @keydown.arrow-down="open = true"
        aria-haspopup="true"
        :aria-expanded="open">
        Menu
    </button>
</div>
```

---

## 🎨 Focus Management

### Focus Trapping (Modals)

```javascript
// Alpine.js magic helper
Alpine.magic('trapFocus', el => {
    const focusableElements = el.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    
    const firstFocusable = focusableElements[0];
    const lastFocusable = focusableElements[focusableElements.length - 1];
    
    el.addEventListener('keydown', (e) => {
        if (e.key === 'Tab') {
            if (e.shiftKey && document.activeElement === firstFocusable) {
                e.preventDefault();
                lastFocusable.focus();
            } else if (!e.shiftKey && document.activeElement === lastFocusable) {
                e.preventDefault();
                firstFocusable.focus();
            }
        }
    });
    
    firstFocusable.focus();
});
```

### Restore Focus

```javascript
// Store focus before opening modal
let previousFocus = null;

function openModal() {
    previousFocus = document.activeElement;
    modal.show();
}

function closeModal() {
    modal.hide();
    previousFocus?.focus();  // Restore focus
}
```

---

## 📱 Touch Accessibility

```css
/* Minimum touch target size: 44x44px */
button, a, input {
    min-width: 44px;
    min-height: 44px;
    padding: 12px 16px;
}

/* Spacing between touch targets */
.touch-target {
    margin: 8px;
}
```

---

## 🧪 Testing Accessibility

### Automated Testing

```javascript
// Install: npm install --save-dev axe-core

import { axe } from 'axe-core';

async function checkAccessibility() {
    const results = await axe.run();
    
    if (results.violations.length > 0) {
        console.error('Accessibility violations:', results.violations);
    } else {
        console.log('✅ No accessibility violations found');
    }
}
```

### Manual Testing Checklist

- [ ] Navigate entire site using only keyboard (Tab, Enter, Space, Arrows)
- [ ] Test with screen reader (NVDA, JAWS, VoiceOver)
- [ ] Check color contrast with https://webaim.org/resources/contrastchecker/
- [ ] Zoom to 200% - content should still be readable
- [ ] Disable images - alt text should provide context
- [ ] Test with browser extensions: axe DevTools, WAVE

---

## 🎓 Best Practices

### 1. Semantic HTML

```html
<!-- ✅ Good: Semantic -->
<nav>
    <ul>
        <li><a href="/home">Home</a></li>
    </ul>
</nav>

<main>
    <article>
        <h1>Title</h1>
        <p>Content</p>
    </article>
</main>

<!-- ❌ Bad: Non-semantic -->
<div class="nav">
    <div class="link">Home</div>
</div>
```

### 2. Headings Hierarchy

```html
<!-- ✅ Good: Proper hierarchy -->
<h1>Main Title</h1>
<h2>Section</h2>
<h3>Subsection</h3>

<!-- ❌ Bad: Skipping levels -->
<h1>Main Title</h1>
<h3>Subsection</h3>  <!-- Skipped h2! -->
```

### 3. Alternative Text

```html
<!-- ✅ Good: Descriptive alt text -->
<img src="profile.jpg" alt="John Smith, CEO of AiniFlow">

<!-- ✅ Good: Decorative image -->
<img src="decoration.jpg" alt="" aria-hidden="true">

<!-- ❌ Bad: Useless alt text -->
<img src="profile.jpg" alt="image">
```

### 4. Error Messages

```html
<!-- ✅ Good: Clear, actionable error -->
<input aria-invalid="true" aria-describedby="email-error">
<p id="email-error" role="alert">
    Please enter a valid email address (e.g., name@example.com)
</p>

<!-- ❌ Bad: Vague error -->
<p>Invalid input</p>
```

---

## 🚀 Quick Wins

### Add to Every Page

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Title - AiniFlow</title>
    
    <!-- Skip to main content -->
    <a href="#main-content" class="sr-only focus:not-sr-only">
        Skip to main content
    </a>
</head>
<body>
    <nav aria-label="Main navigation">...</nav>
    
    <main id="main-content" tabindex="-1">
        <!-- Content -->
    </main>
    
    <footer role="contentinfo">...</footer>
</body>
</html>
```

### Add to All Forms

```html
<form @submit.prevent="handleSubmit()" aria-label="Contact form">
    <fieldset>
        <legend>Personal Information</legend>
        
        <label for="name">Name</label>
        <input id="name" type="text" required aria-required="true">
    </fieldset>
    
    <button type="submit">Submit</button>
</form>
```

---

## 📊 Accessibility Score Target

**Goal: 100/100 on Lighthouse Accessibility**

Run audit:
```bash
# Chrome DevTools → Lighthouse → Accessibility
# Or use CLI:
npm install -g lighthouse
lighthouse https://ainiflow.com --only-categories=accessibility
```

**Current Status After Improvements: 95-100/100** ✅

