<script>
// Modern PMS Theme Scripts - Universal for all pages

// Sidebar toggle function
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('collapsed');
}

// Theme toggle function
function toggleTheme() {
    const body = document.body;
    const themeToggle = document.querySelector('.theme-toggle');
    
    if (body.classList.contains('light-theme')) {
        body.classList.remove('light-theme');
        body.classList.add('dark-theme');
        themeToggle.textContent = '🌙';
        localStorage.setItem('theme', 'dark');
    } else {
        body.classList.remove('dark-theme');
        body.classList.add('light-theme');
        themeToggle.textContent = '☀️';
        localStorage.setItem('theme', 'light');
    }
}

// Currency toggle function (for pages that use it)
let currentCurrency = 'PEN'; // Default to PEN (Soles)

function toggleCurrency() {
    const currencySymbol = document.getElementById('currency-symbol');
    const currencyAmounts = document.querySelectorAll('.currency-amount');
    
    if (!currencySymbol) return; // Exit if no currency toggle on page
    
    if (currentCurrency === 'PEN') {
        currentCurrency = 'USD';
        currencySymbol.textContent = '$';
        
        currencyAmounts.forEach(element => {
            const usdValue = element.getAttribute('data-usd');
            if (usdValue) {
                element.textContent = '$' + usdValue;
            }
        });
    } else {
        currentCurrency = 'PEN';
        currencySymbol.textContent = 'S/.';
        
        currencyAmounts.forEach(element => {
            const penValue = element.getAttribute('data-pen');
            if (penValue) {
                element.textContent = 'S/. ' + penValue;
            }
        });
    }
    
    // Save preference
    localStorage.setItem('pms_currency', currentCurrency);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load saved theme
    const savedTheme = localStorage.getItem('theme') || 'dark';
    const themeToggle = document.querySelector('.theme-toggle');
    
    if (themeToggle) {
        if (savedTheme === 'light') {
            document.body.classList.add('light-theme');
            document.body.classList.remove('dark-theme');
            themeToggle.textContent = '☀️';
        } else {
            document.body.classList.add('dark-theme');
            document.body.classList.remove('light-theme');
            themeToggle.textContent = '🌙';
        }
    }

    // Load saved currency preference (if currency toggle exists)
    const currencyToggle = document.querySelector('.currency-toggle');
    if (currencyToggle) {
        const savedCurrency = localStorage.getItem('pms_currency') || 'PEN';
        if (savedCurrency === 'USD') {
            toggleCurrency(); // Switch to USD if it was saved
        }
    }
});
</script>
