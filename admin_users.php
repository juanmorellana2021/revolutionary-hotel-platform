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
        if ($_POST['action'] === 'toggle_status') {
            $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            $new_status = filter_input(INPUT_POST, 'is_active', FILTER_VALIDATE_INT);
            
            $stmt = $pdo->prepare("UPDATE ainitravel_users SET is_active = ? WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'User status updated']);
            exit;
        }
        
        if ($_POST['action'] === 'update_role') {
            $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            $new_role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            if (!in_array($new_role, ['user', 'admin', 'partner'])) {
                throw new Exception('Invalid role');
            }
            
            $stmt = $pdo->prepare("UPDATE ainitravel_users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'User role updated']);
            exit;
        }
        
        if ($_POST['action'] === 'delete_user') {
            $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            
            // Don't allow deleting yourself
            if ($user_id == $admin_id) {
                throw new Exception('Cannot delete your own account');
            }
            
            $stmt = $pdo->prepare("DELETE FROM ainitravel_users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            echo json_encode(['success' => true, 'message' => 'User deleted']);
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
$role_filter = filter_input(INPUT_GET, 'role', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$status_filter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter) {
    $where[] = "role = ?";
    $params[] = $role_filter;
}

if ($status_filter !== '') {
    $where[] = "is_active = ?";
    $params[] = $status_filter;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_sql = "SELECT COUNT(*) FROM ainitravel_users $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $per_page);

// Get users
$sql = "SELECT id, name, email, role, is_active, created_at, last_login 
        FROM ainitravel_users 
        $where_clause 
        ORDER BY created_at DESC 
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - AiniTravel Admin</title>
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
            max-width: 1400px;
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

        .badge.user {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge.admin {
            background: #fce4ec;
            color: #c2185b;
        }

        .badge.partner {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .badge.active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge.inactive {
            background: #ffebee;
            color: #c62828;
        }

        .actions {
            display: flex;
            gap: 8px;
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
        }

        .btn:hover {
            opacity: 0.8;
        }

        .btn-edit {
            background: #2196f3;
            color: white;
        }

        .btn-toggle {
            background: #ff9800;
            color: white;
        }

        .btn-delete {
            background: #f44336;
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

        /* Modal */
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
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-content h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-group input:disabled {
            background: #f5f5f5;
            cursor: not-allowed;
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

            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👥 User Management</h1>
            <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <div id="alert" class="alert"></div>

        <div class="stats">
            <div class="stat-card">
                <h3><?php echo $total_users; ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat-card">
                <h3><?php 
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM ainitravel_users WHERE role = 'admin'");
                        echo $stmt->fetchColumn();
                    } catch (Exception $e) {
                        echo 0;
                    }
                ?></h3>
                <p>Admins</p>
            </div>
            <div class="stat-card">
                <h3><?php 
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM ainitravel_users WHERE role = 'partner'");
                        echo $stmt->fetchColumn();
                    } catch (Exception $e) {
                        echo 0;
                    }
                ?></h3>
                <p>Partners</p>
            </div>
            <div class="stat-card">
                <h3><?php 
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM ainitravel_users WHERE is_active = 1");
                        echo $stmt->fetchColumn();
                    } catch (Exception $e) {
                        echo 0;
                    }
                ?></h3>
                <p>Active Users</p>
            </div>
        </div>

        <form method="GET" class="filters">
            <input type="text" name="search" placeholder="Search by name or email..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            
            <select name="role">
                <option value="">All Roles</option>
                <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>User</option>
                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="partner" <?php echo $role_filter === 'partner' ? 'selected' : ''; ?>>Partner</option>
            </select>
            
            <select name="status">
                <option value="">All Status</option>
                <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Active</option>
                <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
            </select>
            
            <button type="submit">🔍 Filter</button>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                                No users found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td><?php echo $user['last_login'] ? date('M d, Y', strtotime($user['last_login'])) : 'Never'; ?></td>
                                <td>
                                    <div class="actions">
                                        <button class="btn btn-edit" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                            ✏️ Edit
                                        </button>
                                        <button class="btn btn-toggle" 
                                                onclick="toggleStatus(<?php echo $user['id']; ?>, <?php echo $user['is_active'] ? 0 : 1; ?>)">
                                            <?php echo $user['is_active'] ? '🔒 Suspend' : '✅ Activate'; ?>
                                        </button>
                                        <?php if ($user['id'] != $admin_id): ?>
                                            <button class="btn btn-delete" 
                                                    onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
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
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                        ← Previous
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                        Next →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Edit User</h2>
            <form id="editForm">
                <input type="hidden" id="edit_user_id">
                
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" id="edit_name" disabled>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="edit_email" disabled>
                </div>
                
                <div class="form-group">
                    <label>Role</label>
                    <select id="edit_role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="partner">Partner</option>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAlert(message, type = 'success') {
            const alert = document.getElementById('alert');
            alert.textContent = message;
            alert.className = `alert ${type}`;
            alert.style.display = 'block';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_name').value = user.name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('editModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        document.getElementById('editForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'update_role');
            formData.append('user_id', document.getElementById('edit_user_id').value);
            formData.append('role', document.getElementById('edit_role').value);
            
            try {
                const response = await fetch('admin_users.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.', 'error');
            }
        });

        async function toggleStatus(userId, newStatus) {
            if (!confirm(`Are you sure you want to ${newStatus ? 'activate' : 'suspend'} this user?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('user_id', userId);
            formData.append('is_active', newStatus);
            
            try {
                const response = await fetch('admin_users.php', {
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

        async function deleteUser(userId, userName) {
            if (!confirm(`Are you sure you want to DELETE user "${userName}"? This action cannot be undone!`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_user');
            formData.append('user_id', userId);
            
            try {
                const response = await fetch('admin_users.php', {
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

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', (e) => {
            if (e.target.id === 'editModal') {
                closeModal();
            }
        });
    </script>
</body>
</html>
