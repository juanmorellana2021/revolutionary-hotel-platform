#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Fix accounting_dashboard.php to use theme includes"""

with open('accounting_dashboard.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Find and replace the old sidebar section
old_sidebar_start = '    <!-- Sidebar -->\n    <div class="sidebar" id="sidebar">\n        <div class="hotel-header">'
new_sidebar = '''    <?php 
    // Include dashboard-style sidebar with FontAwesome icons
    include 'includes/modern_sidebar.php'; 
    
    // Configure topbar settings
    $show_currency_toggle = true;
    $search_placeholder = "Search transactions, reports...";
    ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <?php include 'includes/modern_topbar_dashboard_style.php'; ?>
        
        <!-- Dashboard Content -->
        <div class="dashboard-content">'''

# Find where the old structure ends
old_structure_end = '        <!-- Financial Stats Grid -->'

# Find the positions
start_pos = content.find(old_sidebar_start)
end_pos = content.find(old_structure_end)

if start_pos != -1 and end_pos != -1:
    # Replace the entire old structure
    new_content = content[:start_pos] + new_sidebar + '\n        ' + content[end_pos:]
    
    with open('accounting_dashboard.php', 'w', encoding='utf-8') as f:
        f.write(new_content)
    
    print("✅ Successfully updated accounting_dashboard.php")
    print(f"   Removed {end_pos - start_pos} characters of old HTML")
    print(f"   Added {len(new_sidebar)} characters of new includes")
else:
    print("❌ Could not find markers")
    print(f"   Start position: {start_pos}")
    print(f"   End position: {end_pos}")
