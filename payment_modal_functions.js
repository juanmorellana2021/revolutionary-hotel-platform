
// Payment Modal Functions
function openPaymentModal() {
    if (!currentBookingData) {
        alert('No hay datos de reserva disponibles.');
        return;
    }

    // Populate modal with booking data
    document.getElementById('paymentBookingId').textContent = '#' + currentBookingData.id;
    document.getElementById('paymentAmount').textContent = formatDualCurrency(currentBookingData.total_amount);
    
    // Set current status with color
    const statusElement = document.getElementById('paymentCurrentStatus');
    const statusText = {
        'paid': '✅ Pagado',
        'partial': '⚡ Parcial',
        'pending': '🔄 Pendiente',
        'refunded': '💰 Reembolsado'
    };
    statusElement.textContent = statusText[currentBookingData.payment_status] || currentBookingData.payment_status;
    
    // Show modal
    document.getElementById('paymentModal').classList.remove('hidden');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

function processPaymentStatus(status) {
    if (!currentBookingData) {
        alert('No hay datos de reserva disponibles.');
        return;
    }

    const statusNames = {
        'paid': 'PAGADO COMPLETO',
        'partial': 'PAGO PARCIAL',
        'pending': 'PENDIENTE',
        'refunded': 'REEMBOLSADO'
    };

    const confirmation = confirm(`¿Actualizar el estado de pago de la reserva #${currentBookingData.id} a ${statusNames[status]}?\n\nMonto total: ${formatDualCurrency(currentBookingData.total_amount)}`);
    
    if (confirmation) {
        // Create a form to submit the payment update
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="update_payment_status" value="1">
            <input type="hidden" name="booking_id" value="${currentBookingData.id}">
            <input type="hidden" name="payment_status" value="${status}">
            <input type="hidden" name="paid_amount" value="${status === 'paid' ? currentBookingData.total_amount : '0'}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
