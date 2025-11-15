<style>
/* Dashboard-Style Modern Theme CSS */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: #0f172a;
    color: #e2e8f0;
    transition: background 0.5s ease, color 0.5s ease;
}

/* Sidebar */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    width: 260px;
    background: #1e293b;
    padding: 2rem 0;
    z-index: 100;
    border-right: 1px solid rgba(255,255,255,0.1);
}

.logo {
    padding: 0 1.5rem;
    margin-bottom: 3rem;
}

.logo h2 {
    color: #fff;
    font-size: 1.5rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.logo h2 i {
    color: #6366f1;
}

.nav-menu {
    list-style: none;
}

.nav-item {
    margin-bottom: 0.5rem;
    padding: 0 1rem;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem 1rem;
    color: #94a3b8;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s;
    font-weight: 500;
}

.nav-link:hover, .nav-link.active {
    background: rgba(99, 102, 241, 0.1);
    color: #6366f1;
}

.nav-link i {
    font-size: 1.1rem;
    width: 20px;
}

/* Main Content */
.main-content {
    margin-left: 260px;
    min-height: 100vh;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
}

.top-bar {
    background: rgba(30, 41, 59, 0.8);
    backdrop-filter: blur(10px);
    padding: 1.5rem 2rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 50;
}

.search-box {
    position: relative;
    width: 400px;
}

.search-box input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 3rem;
    background: rgba(15, 23, 42, 0.5);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    color: #e2e8f0;
    outline: none;
    transition: all 0.3s;
}

.search-box input:focus {
    border-color: #6366f1;
    background: rgba(15, 23, 42, 0.8);
}

.search-box i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-left: 1rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
}

/* Theme Toggle Button */
.theme-toggle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(99, 102, 241, 0.1);
    border: 1px solid rgba(99, 102, 241, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    margin-right: 1rem;
}

.theme-toggle:hover {
    background: rgba(99, 102, 241, 0.2);
    border-color: rgba(99, 102, 241, 0.5);
    transform: scale(1.05);
}

.theme-toggle i {
    color: #6366f1;
    font-size: 1.1rem;
    transition: all 0.3s;
}

/* Currency Toggle */
.currency-toggle {
    padding: 0.5rem 1rem;
    background: rgba(99, 102, 241, 0.1);
    border: 1px solid rgba(99, 102, 241, 0.3);
    border-radius: 8px;
    color: #6366f1;
    cursor: pointer;
    transition: all 0.3s;
    font-weight: 600;
}

.currency-toggle:hover {
    background: rgba(99, 102, 241, 0.2);
    border-color: rgba(99, 102, 241, 0.5);
}

/* Dashboard Content */
.dashboard-content {
    padding: 2rem;
}

/* Light Theme */
body.light-theme {
    background: #f1f5f9;
    color: #0f172a;
}

body.light-theme .sidebar {
    background: #ffffff;
    border-right-color: rgba(0,0,0,0.1);
}

body.light-theme .logo h2 {
    color: #0f172a;
}

body.light-theme .nav-link {
    color: #64748b;
}

body.light-theme .nav-link:hover,
body.light-theme .nav-link.active {
    background: rgba(99, 102, 241, 0.1);
    color: #6366f1;
}

body.light-theme .main-content {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

body.light-theme .top-bar {
    background: rgba(255, 255, 255, 0.9);
    border-bottom-color: rgba(0,0,0,0.1);
}

body.light-theme .search-box input {
    background: rgba(241, 245, 249, 0.8);
    border-color: rgba(0,0,0,0.1);
    color: #0f172a;
}

body.light-theme .search-box input:focus {
    background: rgba(241, 245, 249, 1);
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0;
    }
    
    .search-box {
        width: 200px;
    }
}
</style>
