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
            $partner_id = filter_input(INPUT_POST, 'partner_id', FILTER_VALIDATE_INT);
            $new_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            if (!in_array($new_status, ['pending', 'active', 'suspended', 'rejected'])) {
                throw new Exception('Invalid status');
            }
            
            $stmt = $pdo->prepare("UPDATE aini_experience_partners SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_status, $partner_id]);
            
            echo json_encode(['success' => true, 'message' => 'Partner status updated']);
            exit;
        }
        
        if ($_POST['action'] === 'delete_partner') {
            $partner_id = filter_input(INPUT_POST, 'partner_id', FILTER_VALIDATE_INT);
            
            // Check if partner has experiences
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM aini_experiences WHERE partner_id = ?");
            $stmt->execute([$partner_id]);
            $experience_count = $stmt->fetchColumn();
            
            if ($experience_count > 0) {
                throw new Exception("Cannot delete partner with $experience_count existing experiences");
            }
            
            $stmt = $pdo->prepare("DELETE FROM aini_experience_partners WHERE id = ?");
            $stmt->execute([$partner_id]);
            
            echo json_encode(['success' => true, 'message' => 'Partner deleted']);
            exit;
        }
        
        if ($_POST['action'] === 'add_note') {
            $partner_id = filter_input(INPUT_POST, 'partner_id', FILTER_VALIDATE_INT);
            $note = filter_input(INPUT_POST, 'note', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            $stmt = $pdo->prepare("UPDATE aini_experience_partners SET admin_notes = ? WHERE id = ?");
            $stmt->execute([$note, $partner_id]);
            
            echo json_encode(['success' => true, 'message' => 'Note saved']);
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
$country_filter = filter_input(INPUT_GET, 'country', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(business_name LIKE ? OR owner_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where[] = "status = ?";
    $params[] = $status_filter;
}

if ($country_filter) {
    $where[] = "country = ?";
    $params[] = $country_filter;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_sql = "SELECT COUNT(*) FROM aini_experience_partners $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_partners = $count_stmt->fetchColumn();
$total_pages = ceil($total_partners / $per_page);

// Get partners with experience counts
$sql = "SELECT p.*, 
        COUNT(DISTINCT e.id) as total_experiences,
        COUNT(DISTINCT CASE WHEN e.status = 'approved' THEN e.id END) as active_experiences
        FROM aini_experience_partners p
        LEFT JOIN aini_experiences e ON p.id = e.partner_id
        $where_clause
        GROUP BY p.id
        ORDER BY p.created_at DESC 
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get countries for filter
try {
    $countries_stmt = $pdo->query("SELECT DISTINCT country FROM aini_experience_partners WHERE country IS NOT NULL ORDER BY country");
    $countries = $countries_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $countries = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Management - AiniTravel Admin</title>
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
            max-width: 1600px;
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        .stat-card.active {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-card.suspended {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
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
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
            white-space: nowrap;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge.active {
            background: #d4edda;
            color: #155724;
        }

        .badge.suspended {
            background: #f8d7da;
            color: #721c24;
        }

        .badge.rejected {
            background: #d6d8d9;
            color: #383d41;
        }

        .partner-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .partner-info strong {
            font-size: 16px;
            color: #333;
        }

        .partner-info small {
            color: #666;
        }

        .contact-info {
            font-size: 13px;
            color: #666;
        }

        .contact-info div {
            margin-bottom: 3px;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
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

        .btn-approve {
            background: #4caf50;
            color: white;
        }

        .btn-suspend {
            background: #ff9800;
            color: white;
        }

        .btn-reject {
            background: #f44336;
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
            max-width: 700px;
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

        .btn-cancel {
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
                font-size: 14px;
            }

            th, td {
                padding: 10px;
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
            <h1>🤝 Partner Management</h1>
            <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <div id="alert" class="alert"></div>

        <div class="stats">
            <div class="stat-card">
                <h3><?php echo $total_partners; ?></h3>
                <p>Total Partners</p>
            </div>
            <div class="stat-card pending">
                <h3><?php 
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM aini_experience_partners WHERE status = 'pending'");
                        echo $stmt->fetchColumn();
                    } catch (Exception $e) {
                        echo 0;
                    }
                ?></h3>
                <p>Pending Approval</p>
            </div>
            <div class="stat-card active">
                <h3><?php 
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM aini_experience_partners WHERE status = 'active'");
                        echo $stmt->fetchColumn();
                    } catch (Exception $e) {
                        echo 0;
                    }
                ?></h3>
                <p>Active Partners</p>
            </div>
            <div class="stat-card suspended">
                <h3><?php 
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM aini_experience_partners WHERE status = 'suspended'");
                        echo $stmt->fetchColumn();
                    } catch (Exception $e) {
                        echo 0;
                    }
                ?></h3>
                <p>Suspended</p>
            </div>
        </div>

        <form method="GET" class="filters">
            <input type="text" name="search" placeholder="Search business, owner, or email..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            
            <select name="status">
                <option value="">All Status</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
            </select>
            
            <select name="country">
                <option value="">All Countries</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?php echo htmlspecialchars($country); ?>" 
                            <?php echo $country_filter === $country ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($country); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit">🔍 Filter</button>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Business Info</th>
                        <th>Contact</th>
                        <th>Location</th>
                        <th>Experiences</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($partners)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                                No partners found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($partners as $partner): ?>
                            <tr>
                                <td><?php echo $partner['id']; ?></td>
                                <td>
                                    <div class="partner-info">
                                        <strong><?php echo htmlspecialchars($partner['business_name']); ?></strong>
                                        <small>Owner: <?php echo htmlspecialchars($partner['owner_name']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="contact-info">
                                        <div>📧 <?php echo htmlspecialchars($partner['email']); ?></div>
                                        <div>📱 <?php echo htmlspecialchars($partner['phone']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="contact-info">
                                        <div><?php echo htmlspecialchars($partner['city']); ?></div>
                                        <div><?php echo htmlspecialchars($partner['country']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo $partner['active_experiences']; ?></strong> active<br>
                                    <small style="color: #999;"><?php echo $partner['total_experiences']; ?> total</small>
                                </td>
                                <td>
                                    <span class="badge <?php echo $partner['status']; ?>">
                                        <?php echo ucfirst($partner['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($partner['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <button class="btn btn-view" onclick="viewPartner(<?php echo htmlspecialchars(json_encode($partner)); ?>)">
                                            👁️ View
                                        </button>
                                        
                                        <?php if ($partner['status'] === 'pending'): ?>
                                            <button class="btn btn-approve" onclick="updateStatus(<?php echo $partner['id']; ?>, 'active')">
                                                ✅ Approve
                                            </button>
                                            <button class="btn btn-reject" onclick="updateStatus(<?php echo $partner['id']; ?>, 'rejected')">
                                                ❌ Reject
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($partner['status'] === 'active'): ?>
                                            <button class="btn btn-suspend" onclick="updateStatus(<?php echo $partner['id']; ?>, 'suspended')">
                                                🔒 Suspend
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($partner['status'] === 'suspended'): ?>
                                            <button class="btn btn-approve" onclick="updateStatus(<?php echo $partner['id']; ?>, 'active')">
                                                ✅ Activate
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($partner['total_experiences'] == 0): ?>
                                            <button class="btn btn-delete" onclick="deletePartner(<?php echo $partner['id']; ?>, '<?php echo htmlspecialchars($partner['business_name']); ?>')">
                                                🗑️ Delete
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
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&country=<?php echo urlencode($country_filter); ?>">
                        ← Previous
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= min(10, $total_pages); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&country=<?php echo urlencode($country_filter); ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&country=<?php echo urlencode($country_filter); ?>">
                        Next →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- View Partner Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <h2>Partner Details</h2>
            <div id="partnerDetails"></div>
            
            <div class="notes-section">
                <label style="display: block; margin-bottom: 10px; font-weight: 600;">Admin Notes</label>
                <textarea id="adminNotes" placeholder="Add internal notes about this partner..."></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-cancel" onclick="closeModal()">Close</button>
                <button type="button" class="btn btn-save" onclick="saveNotes()">Save Notes</button>
            </div>
        </div>
    </div>

    <script>
        let currentPartnerId = null;

        function showAlert(message, type = 'success') {
            const alert = document.getElementById('alert');
            alert.textContent = message;
            alert.className = `alert ${type}`;
            alert.style.display = 'block';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        function viewPartner(partner) {
            currentPartnerId = partner.id;
            
            const statusColors = {
                pending: '#856404',
                active: '#155724',
                suspended: '#721c24',
                rejected: '#383d41'
            };
            
            const html = `
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Business Name</label>
                        <div class="value">${partner.business_name}</div>
                    </div>
                    <div class="detail-item">
                        <label>Owner Name</label>
                        <div class="value">${partner.owner_name}</div>
                    </div>
                    <div class="detail-item">
                        <label>Email</label>
                        <div class="value">${partner.email}</div>
                    </div>
                    <div class="detail-item">
                        <label>Phone</label>
                        <div class="value">${partner.phone}</div>
                    </div>
                    <div class="detail-item">
                        <label>City</label>
                        <div class="value">${partner.city}</div>
                    </div>
                    <div class="detail-item">
                        <label>Country</label>
                        <div class="value">${partner.country}</div>
                    </div>
                    <div class="detail-item">
                        <label>Status</label>
                        <div class="value" style="color: ${statusColors[partner.status]}; font-weight: 600;">
                            ${partner.status.toUpperCase()}
                        </div>
                    </div>
                    <div class="detail-item">
                        <label>Registered</label>
                        <div class="value">${new Date(partner.created_at).toLocaleDateString()}</div>
                    </div>
                    <div class="detail-item">
                        <label>Total Experiences</label>
                        <div class="value">${partner.total_experiences}</div>
                    </div>
                    <div class="detail-item">
                        <label>Active Experiences</label>
                        <div class="value">${partner.active_experiences}</div>
                    </div>
                </div>
            `;
            
            document.getElementById('partnerDetails').innerHTML = html;
            document.getElementById('adminNotes').value = partner.admin_notes || '';
            document.getElementById('viewModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('viewModal').classList.remove('active');
        }

        async function updateStatus(partnerId, newStatus) {
            const actions = {
                active: 'approve',
                suspended: 'suspend',
                rejected: 'reject'
            };
            
            if (!confirm(`Are you sure you want to ${actions[newStatus]} this partner?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('partner_id', partnerId);
            formData.append('status', newStatus);
            
            try {
                const response = await fetch('admin_partners.php', {
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

        async function deletePartner(partnerId, businessName) {
            if (!confirm(`Are you sure you want to DELETE "${businessName}"? This action cannot be undone!`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_partner');
            formData.append('partner_id', partnerId);
            
            try {
                const response = await fetch('admin_partners.php', {
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

        async function saveNotes() {
            const formData = new FormData();
            formData.append('action', 'add_note');
            formData.append('partner_id', currentPartnerId);
            formData.append('note', document.getElementById('adminNotes').value);
            
            try {
                const response = await fetch('admin_partners.php', {
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
