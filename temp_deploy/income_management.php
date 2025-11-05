<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'includes/accounting_classes.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'manager') {
    header('Location: index.php');
    exit;
}

$incomeManager = new IncomeManager();
$hotelInfo = new HotelInfo();
$hotel = $hotelInfo->getHotelInfo();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_income'])) {
        $result = $incomeManager->addIncome([
            'income_type' => $_POST['income_type'],
            'description' => $_POST['description'],
            'amount' => (float)$_POST['amount'],
            'currency' => $_POST['currency'] ?? 'PEN',
            'payment_method' => $_POST['payment_method'],
            'transaction_date' => $_POST['transaction_date'],
            'booking_id' => !empty($_POST['booking_id']) ? (int)$_POST['booking_id'] : null,
            'guest_name' => $_POST['guest_name'],
            'guest_email' => $_POST['guest_email'],
            'guest_phone' => $_POST['guest_phone'],
            'created_by' => $_SESSION['user']['id'],
            'notes' => $_POST['notes'] ?? ''
        ]);
        
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
    
    if (isset($_POST['update_income'])) {
        $result = $incomeManager->updateIncome((int)$_POST['income_id'], [
            'income_type' => $_POST['income_type'],
            'description' => $_POST['description'],
            'amount' => (float)$_POST['amount'],
            'currency' => $_POST['currency'] ?? 'PEN',
            'payment_method' => $_POST['payment_method'],
            'transaction_date' => $_POST['transaction_date'],
            'booking_id' => !empty($_POST['booking_id']) ? (int)$_POST['booking_id'] : null,
            'guest_name' => $_POST['guest_name'],
            'guest_email' => $_POST['guest_email'],
            'guest_phone' => $_POST['guest_phone'],
            'notes' => $_POST['notes'] ?? ''
        ]);
        
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
    
    if (isset($_POST['delete_income'])) {
        $result = $incomeManager->deleteIncome((int)$_POST['income_id']);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
}

// Get filters
$filters = [];
if (!empty($_GET['income_type'])) {
    $filters['income_type'] = $_GET['income_type'];
}
if (!empty($_GET['payment_method'])) {
    $filters['payment_method'] = $_GET['payment_method'];
}
if (!empty($_GET['start_date'])) {
    $filters['start_date'] = $_GET['start_date'];
}
if (!empty($_GET['end_date'])) {
    $filters['end_date'] = $_GET['end_date'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 20;
$filters['limit'] = $perPage;
$filters['offset'] = ($page - 1) * $perPage;

// Get income records
$incomeRecords = $incomeManager->getIncome($filters);
$totalFilters = $filters;
unset($totalFilters['limit'], $totalFilters['offset']);
$totalIncome = $incomeManager->getIncome($totalFilters);
$totalPages = ceil(count($totalIncome) / $perPage);

// Calculate totals - convert all to PEN
$totalAmount = 0;
foreach ($totalIncome as $income) {
    $currency = $income['currency'] ?? 'PEN';
    if ($currency === 'USD') {
        $totalAmount += $income['amount'] * 3.50; // Convert USD to PEN
    } else {
        $totalAmount += $income['amount']; // Already in PEN
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income Management - <?php echo htmlspecialchars($hotel['hotel_name'] ?? 'Hotel Management'); ?></title>
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
        }

        .navbar {
            background: rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .nav-links a:hover, .nav-links a.active {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .page-header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .total-display {
            background: #28a745;
            color: white;
            padding: 15px 30px;
            border-radius: 10px;
            display: inline-block;
            margin-top: 15px;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f8f9fa;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
            font-size: 1rem;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.85rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-group textarea {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .table-container {
            overflow-x: auto;
        }

        .income-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .income-table th,
        .income-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .income-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .income-table tbody tr:hover {
            background: #f8f9fa;
        }

        .amount {
            font-weight: bold;
            color: #28a745;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            text-decoration: none;
            color: #495057;
        }

        .pagination a:hover {
            background: #f8f9fa;
        }

        .pagination .current {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 85vh;
            overflow-y: auto;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .filters {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="nav-container">
            <div class="logo">💰 Income Management</div>
            <div class="nav-links">
                <a href="manager_dashboard.php">Dashboard</a>
                <a href="room_management.php">Rooms</a>
                <a href="calendar_view.php">Calendar</a>
                <a href="accounting_dashboard.php">Accounting</a>
                <a href="income_management.php" class="active">Income</a>
                <a href="expense_management.php">Expenses</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1>💰 Income Management</h1>
            <p>Track and manage all hotel income sources</p>
            <div class="total-display">
                Total Income: S/. <?php echo number_format($totalAmount, 2); ?> 
                (<?php echo count($totalIncome); ?> entries)
            </div>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Filters Section -->
        <div class="section">
            <h2>🔍 Filter Income Records</h2>
            <form method="GET">
                <div class="filters">
                    <div class="form-group">
                        <label for="income_type">Income Type</label>
                        <select id="income_type" name="income_type">
                            <option value="">All Types</option>
                            <option value="room_booking" <?php echo ($_GET['income_type'] ?? '') === 'room_booking' ? 'selected' : ''; ?>>Room Booking</option>
                            <option value="extra_bed" <?php echo ($_GET['income_type'] ?? '') === 'extra_bed' ? 'selected' : ''; ?>>Extra Bed</option>
                            <option value="food_beverage" <?php echo ($_GET['income_type'] ?? '') === 'food_beverage' ? 'selected' : ''; ?>>Food & Beverage</option>
                            <option value="laundry" <?php echo ($_GET['income_type'] ?? '') === 'laundry' ? 'selected' : ''; ?>>Laundry</option>
                            <option value="spa" <?php echo ($_GET['income_type'] ?? '') === 'spa' ? 'selected' : ''; ?>>Spa Services</option>
                            <option value="parking" <?php echo ($_GET['income_type'] ?? '') === 'parking' ? 'selected' : ''; ?>>Parking</option>
                            <option value="wifi" <?php echo ($_GET['income_type'] ?? '') === 'wifi' ? 'selected' : ''; ?>>WiFi</option>
                            <option value="minibar" <?php echo ($_GET['income_type'] ?? '') === 'minibar' ? 'selected' : ''; ?>>Minibar</option>
                            <option value="conference" <?php echo ($_GET['income_type'] ?? '') === 'conference' ? 'selected' : ''; ?>>Conference Room</option>
                            <option value="other" <?php echo ($_GET['income_type'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="payment_method">Payment Method</label>
                        <select id="payment_method" name="payment_method">
                            <option value="">All Methods</option>
                            <option value="cash" <?php echo ($_GET['payment_method'] ?? '') === 'cash' ? 'selected' : ''; ?>>Cash</option>
                            <option value="credit_card" <?php echo ($_GET['payment_method'] ?? '') === 'credit_card' ? 'selected' : ''; ?>>Credit Card</option>
                            <option value="debit_card" <?php echo ($_GET['payment_method'] ?? '') === 'debit_card' ? 'selected' : ''; ?>>Debit Card</option>
                            <option value="bank_transfer" <?php echo ($_GET['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                            <option value="online" <?php echo ($_GET['payment_method'] ?? '') === 'online' ? 'selected' : ''; ?>>Online Payment</option>
                            <option value="booking_com" <?php echo ($_GET['payment_method'] ?? '') === 'booking_com' ? 'selected' : ''; ?>>Booking.com</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $_GET['start_date'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $_GET['end_date'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" placeholder="Description, guest name..." value="<?php echo $_GET['search'] ?? ''; ?>">
                    </div>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">🔍 Apply Filters</button>
                    <a href="income_management.php" class="btn btn-warning">🔄 Clear Filters</a>
                    <button type="button" class="btn btn-success" onclick="openAddModal()">➕ Add Income</button>
                </div>
            </form>
        </div>

        <!-- Income Records Table -->
        <div class="section">
            <div class="table-container">
                <table class="income-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Guest</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($incomeRecords)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px; color: #6c757d;">
                                    No income records found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($incomeRecords as $income): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($income['transaction_date'])); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $income['income_type'])); ?></td>
                                    <td><?php echo htmlspecialchars($income['description']); ?></td>
                                    <td>
                                        <?php if (isset($income['guest_name']) && !empty($income['guest_name'])): ?>
                                            <strong><?php echo htmlspecialchars($income['guest_name']); ?></strong><br>
                                            <?php if (isset($income['guest_email']) && !empty($income['guest_email'])): ?>
                                                <small>📧 <?php echo htmlspecialchars($income['guest_email']); ?></small><br>
                                            <?php endif; ?>
                                            <?php if (isset($income['guest_phone']) && !empty($income['guest_phone'])): ?>
                                                <small>📱 <?php echo htmlspecialchars($income['guest_phone']); ?></small>
                                            <?php endif; ?>
                                        <?php elseif (isset($income['booking_id']) && $income['booking_id']): ?>
                                            <em>Booking #<?php echo $income['booking_id']; ?></em>
                                        <?php else: ?>
                                            <em>No guest info</em>
                                        <?php endif; ?>
                                    </td>
                                    <td class="amount">
                                        <?php 
                                        $currency = $income['currency'] ?? 'PEN';
                                        $symbol = $currency === 'PEN' ? 'S/.' : '$';
                                        echo $symbol . ' ' . number_format($income['amount'], 2);
                                        
                                        // Show conversion if different from primary currency
                                        if ($currency === 'USD') {
                                            $penAmount = $income['amount'] * 3.50;
                                            echo '<br><small class="text-muted">≈ S/. ' . number_format($penAmount, 2) . '</small>';
                                        } else if ($currency === 'PEN') {
                                            $usdAmount = $income['amount'] / 3.50;
                                            echo '<br><small class="text-muted">≈ $' . number_format($usdAmount, 2) . '</small>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $income['payment_method'])); ?></td>
                                    <td>
                                        <button onclick="editIncome(<?php echo htmlspecialchars(json_encode($income)); ?>)" class="btn btn-primary btn-sm">✏️ Edit</button>
                                        <button onclick="deleteIncome(<?php echo $income['id']; ?>)" class="btn btn-danger btn-sm">🗑️ Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">« Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next »</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Income Modal -->
    <div id="incomeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add Income</h2>
            <form id="incomeForm" method="POST">
                <input type="hidden" id="income_id" name="income_id">
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_income_type">Income Type *</label>
                        <select id="modal_income_type" name="income_type" required>
                            <option value="room_booking">Room Booking</option>
                            <option value="extra_bed">Extra Bed</option>
                            <option value="food_beverage">Food & Beverage</option>
                            <option value="laundry">Laundry</option>
                            <option value="spa">Spa Services</option>
                            <option value="parking">Parking</option>
                            <option value="wifi">WiFi</option>
                            <option value="minibar">Minibar</option>
                            <option value="conference">Conference Room</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="modal_payment_method">Payment Method *</label>
                        <select id="modal_payment_method" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="online">Online Payment</option>
                            <option value="booking_com">Booking.com</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="modal_description">Description *</label>
                    <input type="text" id="modal_description" name="description" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_amount">Amount *</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="number" id="modal_amount" name="amount" step="0.01" min="0" required style="flex: 1;">
                            <select id="modal_currency" name="currency" style="width: 80px;">
                                <option value="PEN">PEN</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                        <div id="currency_conversion" style="margin-top: 5px; font-size: 0.85rem; color: #666;"></div>
                    </div>
                    <div class="form-group">
                        <label for="modal_transaction_date">Transaction Date *</label>
                        <input type="date" id="modal_transaction_date" name="transaction_date" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="modal_booking_id">Booking ID (Optional)</label>
                    <input type="number" id="modal_booking_id" name="booking_id">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_guest_name">Guest Name</label>
                        <input type="text" id="modal_guest_name" name="guest_name">
                    </div>
                    <div class="form-group">
                        <label for="modal_guest_email">Guest Email</label>
                        <input type="email" id="modal_guest_email" name="guest_email">
                    </div>
                </div>
                <div class="form-group">
                    <label for="modal_guest_phone">Guest Phone</label>
                    <input type="tel" id="modal_guest_phone" name="guest_phone">
                </div>
                <div class="form-group">
                    <label for="modal_notes">Notes</label>
                    <textarea id="modal_notes" name="notes" rows="3"></textarea>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" id="submitBtn" name="add_income" class="btn btn-success">💰 Add Income</button>
                    <button type="button" onclick="closeModal()" class="btn btn-warning">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Income';
            document.getElementById('incomeForm').reset();
            document.getElementById('income_id').value = '';
            document.getElementById('modal_transaction_date').value = new Date().toISOString().split('T')[0];
            document.getElementById('submitBtn').textContent = '💰 Add Income';
            document.getElementById('submitBtn').name = 'add_income';
            document.getElementById('incomeModal').style.display = 'block';
        }

        function editIncome(income) {
            document.getElementById('modalTitle').textContent = 'Edit Income';
            document.getElementById('income_id').value = income.id;
            document.getElementById('modal_income_type').value = income.income_type;
            document.getElementById('modal_description').value = income.description;
            document.getElementById('modal_amount').value = income.amount;
            document.getElementById('modal_currency').value = income.currency || 'PEN';
            document.getElementById('modal_payment_method').value = income.payment_method;
            document.getElementById('modal_transaction_date').value = income.transaction_date;
            document.getElementById('modal_booking_id').value = income.booking_id || '';
            document.getElementById('modal_guest_name').value = income.guest_name || '';
            document.getElementById('modal_guest_email').value = income.guest_email || '';
            document.getElementById('modal_guest_phone').value = income.guest_phone || '';
            document.getElementById('modal_notes').value = income.notes || '';
            document.getElementById('submitBtn').textContent = '✏️ Update Income';
            document.getElementById('submitBtn').name = 'update_income';
            document.getElementById('incomeModal').style.display = 'block';
            
            // Update currency conversion display
            setTimeout(updateCurrencyConversion, 100);
        }

        function deleteIncome(incomeId) {
            if (confirm('Are you sure you want to delete this income record? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="income_id" value="${incomeId}">
                    <input type="hidden" name="delete_income" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function closeModal() {
            document.getElementById('incomeModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('incomeModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Handle currency conversion display
        function updateCurrencyConversion() {
            const amountInput = document.getElementById('modal_amount');
            const currencySelect = document.getElementById('modal_currency');
            const conversionDisplay = document.getElementById('currency_conversion');
            
            const amount = parseFloat(amountInput.value) || 0;
            const currency = currencySelect.value;
            const exchangeRate = 3.50; // PEN to USD rate
            
            if (amount > 0) {
                if (currency === 'PEN') {
                    const usdAmount = (amount / exchangeRate).toFixed(2);
                    conversionDisplay.innerHTML = `<small class="text-muted">≈ $${usdAmount} USD</small>`;
                } else {
                    const penAmount = (amount * exchangeRate).toFixed(2);
                    conversionDisplay.innerHTML = `<small class="text-muted">≈ S/. ${penAmount} PEN</small>`;
                }
                conversionDisplay.style.display = 'block';
            } else {
                conversionDisplay.style.display = 'none';
            }
        }

        // Add event listeners for currency conversion
        document.addEventListener('DOMContentLoaded', function() {
            const amountInput = document.getElementById('modal_amount');
            const currencySelect = document.getElementById('modal_currency');
            
            if (amountInput && currencySelect) {
                amountInput.addEventListener('input', updateCurrencyConversion);
                currencySelect.addEventListener('change', updateCurrencyConversion);
            }
        });
    </script>
</body>
</html>