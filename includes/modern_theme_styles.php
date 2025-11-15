<style>
    /* Modern PMS Theme Styles - Universal for all pages */
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        transition: all 0.3s ease;
        margin-left: 260px;
    }

    /* Dark Theme (Default) */
    body.dark-theme {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        color: #e0e0e0;
    }

    /* Light Theme */
    body.light-theme {
        background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
        color: #1a202c;
    }

    /* Sidebar Styles */
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        width: 260px;
        background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        padding: 20px 0;
        z-index: 1000;
        box-shadow: 4px 0 10px rgba(0, 0, 0, 0.3);
        transition: width 0.3s ease;
        overflow-y: auto;
    }

    .sidebar.collapsed {
        width: 80px;
    }

    .sidebar.collapsed .menu-item span:last-child,
    .sidebar.collapsed .hotel-name {
        display: none;
    }

    .sidebar.collapsed .logo-text {
        font-size: 24px;
    }

    .hotel-header {
        padding: 0 20px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 20px;
    }

    .logo-text {
        font-size: 32px;
        text-align: center;
    }

    .hotel-name {
        color: #60a5fa;
        font-size: 18px;
        font-weight: 600;
        margin-top: 10px;
        text-align: center;
    }

    .menu {
        list-style: none;
        padding: 0;
    }

    .menu-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px 20px;
        color: #cbd5e1;
        text-decoration: none;
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
    }

    .menu-item:hover {
        background: rgba(96, 165, 250, 0.1);
        border-left-color: #60a5fa;
        color: #60a5fa;
    }

    .menu-item.active {
        background: rgba(96, 165, 250, 0.15);
        border-left-color: #60a5fa;
        color: #60a5fa;
        font-weight: 600;
    }

    /* Light theme sidebar */
    body.light-theme .sidebar {
        background: linear-gradient(180deg, #ffffff 0%, #f7fafc 100%);
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
    }

    body.light-theme .hotel-header {
        border-bottom: 2px solid #e2e8f0;
    }

    body.light-theme .hotel-name {
        color: #2d3748;
    }

    body.light-theme .menu-item {
        color: #4a5568;
    }

    body.light-theme .menu-item:hover {
        background: rgba(96, 165, 250, 0.1);
        color: #2563eb;
    }

    body.light-theme .menu-item.active {
        background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
        color: white;
    }

    /* Container with sidebar offset */
    .container {
        margin-left: 260px;
        padding: 30px;
        transition: margin-left 0.3s ease;
    }

    .sidebar.collapsed ~ .container {
        margin-left: 80px;
    }

    /* Top Bar */
    .top-bar {
        background: rgba(30, 41, 59, 0.6);
        backdrop-filter: blur(10px);
        padding: 20px 30px;
        border-radius: 15px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
    }

    body.light-theme .top-bar {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #e2e8f0;
    }

    .page-title {
        font-size: 28px;
        font-weight: 600;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    body.light-theme .page-title {
        color: #2d3748;
    }

    .controls {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    /* Theme and Currency Toggle Buttons */
    .theme-toggle, .currency-toggle {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    }

    .theme-toggle:hover, .currency-toggle:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }

    body.light-theme .theme-toggle,
    body.light-theme .currency-toggle {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    }

    /* Responsive */
    @media (max-width: 768px) {
        body, .container {
            margin-left: 80px;
        }
        
        .sidebar {
            width: 80px;
        }
        
        .sidebar .menu-item span:last-child,
        .sidebar .hotel-name {
            display: none;
        }
    }
</style>
