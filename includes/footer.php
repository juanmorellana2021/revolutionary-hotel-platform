    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Hotel System Common Scripts -->
    <script>
        // Global hotel system functions
        
        // Currency formatting
        function formatCurrency(amount, currency = 'PEN') {
            const symbols = { 'PEN': 'S/.', 'USD': '$' };
            const symbol = symbols[currency] || currency;
            return `${symbol} ${parseFloat(amount).toFixed(2)}`;
        }
        
        // Currency conversion (rates defined in system)
        function convertCurrency(amount, fromCurrency, toCurrency) {
            const rates = {
                'USD_TO_PEN': 3.50, // Employee rates
                'USD_TO_PEN_ROOM': 3.75 // Room booking rates  
            };
            
            if (fromCurrency === toCurrency) return amount;
            
            if (fromCurrency === 'USD' && toCurrency === 'PEN') {
                return amount * rates.USD_TO_PEN;
            } else if (fromCurrency === 'PEN' && toCurrency === 'USD') {
                return amount / rates.USD_TO_PEN;
            }
            
            return amount;
        }
        
        // Show Bootstrap toast notifications
        function showToast(message, type = 'success') {
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            // Create toast container if it doesn't exist
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                toastContainer.style.zIndex = '9999';
                document.body.appendChild(toastContainer);
            }
            
            // Add toast and show it
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            const toastElement = toastContainer.lastElementChild;
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            
            // Remove from DOM after it's hidden
            toastElement.addEventListener('hidden.bs.toast', () => {
                toastElement.remove();
            });
        }
        
        // Confirm dialog with Bootstrap styling
        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }
        
        // Auto-refresh functionality for real-time data
        function enableAutoRefresh(intervalMinutes = 5) {
            setTimeout(() => {
                if (document.visibilityState === 'visible') {
                    window.location.reload();
                }
            }, intervalMinutes * 60 * 1000);
        }
        
        // Initialize common functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Initialize all popovers
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        });
    </script>
    
    <!-- Custom page scripts -->
    <?php if (isset($customScripts)): ?>
        <script><?php echo $customScripts; ?></script>
    <?php endif; ?>
</body>
</html>