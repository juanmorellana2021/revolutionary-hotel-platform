
// Handle payment status updates from modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment_status'])) {
    $bookingId = $_POST['booking_id'];
    $paymentStatus = $_POST['payment_status'];
    $paidAmount = $_POST['paid_amount'] ?? 0;

    try {
        $stmt = $connection->prepare("UPDATE bookings SET payment_status = ?, paid_amount = ?, payment_method = 'cash' WHERE id = ?");
        $success = $stmt->execute([$paymentStatus, $paidAmount, $bookingId]);

        if ($success) {
            $message = 'Payment status updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update payment status';
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = 'Error updating payment status: ' . $e->getMessage();
        $messageType = 'error';
    }

    // Refresh the page to show updated status
    header('Location: calendar_view.php?month=' . $currentMonth . '&year=' . $currentYear);
    exit;
}
