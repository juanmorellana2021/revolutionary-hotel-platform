# Modern PMS Theme System

## Overview
Reusable PHP components for consistent UI across all PMS pages. Based on OOP principles and DRY (Don't Repeat Yourself).

## Components

### 1. `includes/modern_sidebar.php`
- Reusable sidebar navigation
- Auto-highlights active page
- Supports collapsed state
- All menu items standardized

### 2. `includes/modern_topbar.php`
- Top bar with page title
- Currency toggle button (optional)
- Theme toggle button
- Consistent across all pages

### 3. `includes/modern_theme_styles.php`
- All CSS for dark/light themes
- Sidebar styles
- Top bar styles
- Button styles
- Responsive design

### 4. `includes/modern_theme_scripts.php`
- `toggleSidebar()` function
- `toggleTheme()` function
- `toggleCurrency()` function
- localStorage persistence
- Auto-loads saved preferences

## How to Use

### Basic Template

```php
<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';

$page_title = "💰 Your Page Title";
$show_currency_toggle = true; // Optional, default true
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo strip_tags($page_title); ?></title>
    <?php include 'includes/modern_theme_styles.php'; ?>
</head>
<body class="dark-theme">
    <?php include 'includes/modern_sidebar.php'; ?>
    
    <div class="container">
        <?php include 'includes/modern_topbar.php'; ?>
        
        <!-- Your content here -->
        
    </div>
    
    <?php include 'includes/modern_theme_scripts.php'; ?>
</body>
</html>
```

### Currency Toggle Usage

For amounts that should toggle between PEN and USD, use:

```html
<span class="currency-amount" 
      data-pen="350.00" 
      data-usd="100.00">
    S/. 350.00
</span>
```

## Benefits

✅ **Consistency**: All pages look and work the same  
✅ **DRY**: No code duplication  
✅ **Maintainability**: Update once, affects all pages  
✅ **Easy to use**: Just 5 includes  
✅ **Themeable**: Dark/light mode built-in  
✅ **Responsive**: Works on all devices  

## Pages to Migrate

- [ ] accounting_dashboard.php
- [ ] calendar_view.php
- [ ] dashboard.php
- [ ] room_management.php
- [ ] income_management.php
- [ ] expense_management.php
- [ ] employee_management.php

## Example

See `example_modern_page.php` for a working demo.
