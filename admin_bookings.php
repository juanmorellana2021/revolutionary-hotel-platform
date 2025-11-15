<?php
session_start();
require_once 'db_connection_pdo.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['name'] ?? 'Admin';

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_POST['action'] === 'update_status') {
            $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
            $new_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            if (!in_array($new_status, ['pending', 'confirmed', 'cancelled', 'completed', 'refunded'])) {
                throw new Exception('Invalid status');
            }
            
            $stmt = $pdo->prepare("UPDATE aini_experience_bookings SET booking_status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_status, $booking_id]);
            
            echo json_encode(['success' => true, 'message' => 'Booking status updated']);
            exit;
        }
        
        if ($_POST['action'] === 'update_payment') {
            $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
            $payment_status = filter_input(INPUT_POST, 'payment_status', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            if (!in_array($payment_status, ['pending', 'paid', 'refunded', 'failed'])) {
                throw new Exception('Invalid payment status');
            }
            
            $stmt = $pdo->prepare("UPDATE aini_experience_bookings SET payment_status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$payment_status, $booking_id]);
            
            echo json_encode(['success' => true, 'message' => 'Payment status updated']);
            exit;
        }
        
        if ($_POST['action'] === 'add_note') {
            $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
            $note = filter_input(INPUT_POST, 'note', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            $stmt = $pdo->prepare("UPDATE aini_experience_bookings SET admin_notes = ? WHERE id = ?");
            $stmt->execute([$note, $booking_id]);
            
            echo json_encode(['success' => true, 'message' => 'Note saved']);
            exit;
        }
        
        if ($_POST['action'] === 'delete_booking') {
            $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
            
            $stmt = $pdo->prepare("DELETE FROM aini_experience_bookings WHERE id = ?");
            $stmt->execute([$booking_id]);
            
            echo json_encode(['success' => true, 'message' => 'Booking deleted']);
            exit;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Pagination
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search and filter
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$status_filter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$payment_filter = filter_input(INPUT_GET, 'payment', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$date_from = filter_input(INPUT_GET, 'date_from', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$date_to = filter_input(INPUT_GET, 'date_to', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(e.name LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR b.booking_reference LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where[] = "b.booking_status = ?";
    $params[] = $status_filter;
}

if ($payment_filter) {
    $where[] = "b.payment_status = ?";
    $params[] = $payment_filter;
}

if ($date_from) {
    $where[] = "b.booking_date >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where[] = "b.booking_date <= ?";
    $params[] = $date_to;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
try {
    $count_sql = "SELECT COUNT(*) FROM aini_experience_bookings b
                  JOIN aini_experiences e ON b.experience_id = e.id
                  JOIN ainitravel_users u ON b.user_id = u.id
                  $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_bookings = $count_stmt->fetchColumn();
    $total_pages = ceil($total_bookings / $per_page);
} catch (Exception $e) {
    $total_bookings = 0;
    $total_pages = 0;
}

// Get bookings
try {
    $sql = "SELECT b.*, 
            e.name as experience_name,
            e.category,
            u.name as customer_name,
            u.email as customer_email,
            u.phone as customer_phone,
            p.business_name as partner_name
            FROM aini_experience_bookings b
            JOIN aini_experiences e ON b.experience_id = e.id
            JOIN ainitravel_users u ON b.user_id = u.id
            LEFT JOIN aini_experience_partners p ON e.partner_id = p.id
            $where_clause
            ORDER BY b.created_at DESC 
            LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $bookings = [];
}

// Calculate total revenue
try {
    $revenue_sql = "SELECT SUM(total_price) FROM aini_experience_bookings WHERE payment_status = 'paid'";
    $total_revenue = $pdo->query($revenue_sql)->fetchColumn() ?? 0;
} catch (Exception $e) {
    $total_revenue = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management - AiniTravel Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
        }

        .header .back-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s;
            display: inline-block;
        }

        .header .back-btn:hover {
            transform: translateY(-2px);
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .filters input,
        .filters select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            width: 100%;
        }

        .filters button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: opacity 0.2s;
        }

        .filters button:hover {
            opacity: 0.9;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-card.pending {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-card.confirmed {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-card.revenue {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .stat-card h3 {
            font-size: 32px;
            margin-bottom: 5px;
        }

        .stat-card p {
            opacity: 0.9;
            font-size: 14px;
        }

        .table-container {
            overflow-x: auto;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th {
            background: #f8f9fa;
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
            white-space: nowrap;
            font-size: 13px;
        }

        td {
            padding: 15px 10px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
            font-size: 13px;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }

        .badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge.confirmed {
            background: #d4edda;
            color: #155724;
        }

        .badge.cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .badge.completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge.refunded {
            background: #d6d8d9;
            color: #383d41;
        }

        .badge.paid {
            background: #d4edda;
            color: #155724;
        }

        .badge.failed {
            background: #f8d7da;
            color: #721c24;
        }

        .booking-info {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .booking-info strong {
            font-size: 14px;
            color: #333;
        }

        .booking-info small {
            color: #666;
            font-size: 12px;
        }

        .actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
            transition: opacity 0.2s;
            text-decoration: none;
            display: inline-block;
            white-space: nowrap;
        }

        .btn:hover {
            opacity: 0.8;
        }

        .btn-view {
            background: #2196f3;
            color: white;
        }

        .btn-confirm {
            background: #4caf50;
            color: white;
        }

        .btn-cancel {
            background: #f44336;
            color: white;
        }

        .btn-complete {
            background: #00bcd4;
            color: white;
        }

        .btn-delete {
            background: #9e9e9e;
            color: white;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            transition: all 0.2s;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .pagination .active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-content h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .detail-item.full {
            grid-column: 1 / -1;
        }

        .detail-item label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
            font-size: 12px;
            text-transform: uppercase;
        }

        .detail-item .value {
            font-size: 15px;
            color: #333;
        }

        .status-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        .status-actions select {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .status-actions button {
            padding: 12px 24px;
            background: #4caf50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .notes-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        .notes-section textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            min-height: 100px;
            resize: vertical;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 25px;
        }

        .btn-save {
            background: #4caf50;
            color: white;
            padding: 12px 24px;
        }

        .btn-close {
            background: #757575;
            color: white;
            padding: 12px 24px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }

        .alert.error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .table-container {
                font-size: 12px;
            }

            th, td {
                padding: 8px;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 Booking Management</h1>
            <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <div id="alert" class="alert"></div>

        <div class="stats">
            <div class="stat-card">
                <h3><?php echo $total_bookings; ?></h3>
                <p>Total Bookings</p>
            </div>
            <div class="stat-card pending">
                <h3><?php 
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM aini_experience_bookings WHERE booking_status = 'pending'");
                        echo $stmt->fetchColumn();
                    } catch (Exception $e) {
                        echo 0;
                    }
                ?></h3>
                <p>Pending</p>
            </div>
            <div class="stat-card confirmed">
                <h3><?php 
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM aini_experience_bookings WHERE booking_status = 'confirmed'");
                        echo $stmt->fetchColumn();
                    } catch (Exception $e) {
                        echo 0;
                    }
                ?></h3>
                <p>Confirmed</p>
            </div>
            <div class="stat-card revenue">
                <h3>$<?php echo number_format($total_revenue, 2); ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>

        <form method="GET" class="filters">
            <input type="text" name="search" placeholder="Search booking, customer, experience..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            
            <select name="status">
                <option value="">All Booking Status</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="refunded" <?php echo $status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
            </select>
            
            <select name="payment">
                <option value="">All Payment Status</option>
                <option value="pending" <?php echo $payment_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="paid" <?php echo $payment_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                <option value="refunded" <?php echo $payment_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                <option value="failed" <?php echo $payment_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
            </select>
            
            <input type="date" name="date_from" placeholder="From Date" value="<?php echo htmlspecialchars($date_from); ?>">
            <input type="date" name="date_to" placeholder="To Date" value="<?php echo htmlspecialchars($date_to); ?>">
            
            <button type="submit">🔍 Filter</button>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Customer</th>
                        <th>Experience</th>
                        <th>Date</th>
                        <th>Guests</th>
                        <th>Total</th>
                        <th>Booking Status</th>
                        <th>Payment</th>
                        <th>Booked</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px; color: #999;">
                                No bookings found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></td>
                                <td>
                                    <div class="booking-info">
                                        <strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong>
                                        <small><?php echo htmlspecialchars($booking['customer_email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="booking-info">
                                        <strong><?php echo htmlspecialchars($booking['experience_name']); ?></strong>
                                        <small><?php echo htmlspecialchars($booking['category']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                <td><?php echo $booking['number_of_guests']; ?> guests</td>
                                <td><strong>$<?php echo number_format($booking['total_price'], 2); ?></strong></td>
                                <td>
                                    <span class="badge <?php echo $booking['booking_status']; ?>">
                                        <?php echo ucfirst($booking['booking_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $booking['payment_status']; ?>">
                                        <?php echo ucfirst($booking['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div class="actions">
                                        <button class="btn btn-view" onclick="viewBooking(<?php echo htmlspecialchars(json_encode($booking)); ?>)">
                                            👁️ View
                                        </button>
                                        
                                        <?php if ($booking['booking_status'] === 'pending'): ?>
                                            <button class="btn btn-confirm" onclick="quickUpdateStatus(<?php echo $booking['id']; ?>, 'confirmed')">
                                                ✅ Confirm
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking['booking_status'] === 'confirmed'): ?>
                                            <button class="btn btn-complete" onclick="quickUpdateStatus(<?php echo $booking['id']; ?>, 'completed')">
                                                ✓ Complete
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($booking['booking_status'], ['pending', 'confirmed'])): ?>
                                            <button class="btn btn-cancel" onclick="quickUpdateStatus(<?php echo $booking['id']; ?>, 'cancelled')">
                                                ❌ Cancel
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&payment=<?php echo urlencode($payment_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                        ← Previous
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= min(10, $total_pages); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&payment=<?php echo urlencode($payment_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&payment=<?php echo urlencode($payment_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                        Next →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- View Booking Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <h2>Booking Details</h2>
            <div id="bookingDetails"></div>
            
            <div class="status-actions">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 12px;">Booking Status</label>
                    <select id="bookingStatus">
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="completed">Completed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 12px;">Payment Status</label>
                    <select id="paymentStatus">
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="refunded">Refunded</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                <button onclick="updateStatuses()" style="margin-top: 20px;">Update Status</button>
            </div>
            
            <div class="notes-section">
                <label style="display: block; margin-bottom: 10px; font-weight: 600;">Admin Notes</label>
                <textarea id="adminNotes" placeholder="Add internal notes about this booking..."></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-close" onclick="closeModal()">Close</button>
                <button type="button" class="btn btn-save" onclick="saveNotes()">Save Notes</button>
            </div>
        </div>
    </div>

    <script>
        let currentBookingId = null;

        function showAlert(message, type = 'success') {
            const alert = document.getElementById('alert');
            alert.textContent = message;
            alert.className = `alert ${type}`;
            alert.style.display = 'block';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        function viewBooking(booking) {
            currentBookingId = booking.id;
            
            const html = `
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Booking Reference</label>
                        <div class="value">${booking.booking_reference}</div>
                    </div>
                    <div class="detail-item">
                        <label>Booking Date</label>
                        <div class="value">${new Date(booking.booking_date).toLocaleDateString()}</div>
                    </div>
                    <div class="detail-item">
                        <label>Customer Name</label>
                        <div class="value">${booking.customer_name}</div>
                    </div>
                    <div class="detail-item">
                        <label>Email</label>
                        <div class="value">${booking.customer_email}</div>
                    </div>
                    <div class="detail-item">
                        <label>Phone</label>
                        <div class="value">${booking.customer_phone || 'N/A'}</div>
                    </div>
                    <div class="detail-item">
                        <label>Number of Guests</label>
                        <div class="value">${booking.number_of_guests}</div>
                    </div>
                    <div class="detail-item full">
                        <label>Experience</label>
                        <div class="value">${booking.experience_name} (${booking.category})</div>
                    </div>
                    <div class="detail-item full">
                        <label>Partner</label>
                        <div class="value">${booking.partner_name || 'N/A'}</div>
                    </div>
                    <div class="detail-item">
                        <label>Total Price</label>
                        <div class="value" style="font-size: 20px; color: #4caf50; font-weight: bold;">$${parseFloat(booking.total_price).toFixed(2)}</div>
                    </div>
                    <div class="detail-item">
                        <label>Created</label>
                        <div class="value">${new Date(booking.created_at).toLocaleString()}</div>
                    </div>
                    ${booking.special_requests ? `
                    <div class="detail-item full">
                        <label>Special Requests</label>
                        <div class="value">${booking.special_requests}</div>
                    </div>
                    ` : ''}
                </div>
            `;
            
            document.getElementById('bookingDetails').innerHTML = html;
            document.getElementById('bookingStatus').value = booking.booking_status;
            document.getElementById('paymentStatus').value = booking.payment_status;
            document.getElementById('adminNotes').value = booking.admin_notes || '';
            document.getElementById('viewModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('viewModal').classList.remove('active');
        }

        async function quickUpdateStatus(bookingId, newStatus) {
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('booking_id', bookingId);
            formData.append('status', newStatus);
            
            try {
                const response = await fetch('admin_bookings.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.', 'error');
            }
        }

        async function updateStatuses() {
            const bookingStatus = document.getElementById('bookingStatus').value;
            const paymentStatus = document.getElementById('paymentStatus').value;
            
            // Update booking status
            const formData1 = new FormData();
            formData1.append('action', 'update_status');
            formData1.append('booking_id', currentBookingId);
            formData1.append('status', bookingStatus);
            
            // Update payment status
            const formData2 = new FormData();
            formData2.append('action', 'update_payment');
            formData2.append('booking_id', currentBookingId);
            formData2.append('payment_status', paymentStatus);
            
            try {
                await fetch('admin_bookings.php', { method: 'POST', body: formData1 });
                await fetch('admin_bookings.php', { method: 'POST', body: formData2 });
                
                showAlert('Status updated successfully', 'success');
                setTimeout(() => location.reload(), 1500);
            } catch (error) {
                showAlert('An error occurred. Please try again.', 'error');
            }
        }

        async function saveNotes() {
            const formData = new FormData();
            formData.append('action', 'add_note');
            formData.append('booking_id', currentBookingId);
            formData.append('note', document.getElementById('adminNotes').value);
            
            try {
                const response = await fetch('admin_bookings.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    closeModal();
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.', 'error');
            }
        }

        // Close modal when clicking outside
        document.getElementById('viewModal').addEventListener('click', (e) => {
            if (e.target.id === 'viewModal') {
                closeModal();
            }
        });
    </script>
</body>
</html>
