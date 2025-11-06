<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

require_once 'db_connection.php';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $investorId = $_POST['investor_id'];
    $newStatus = $_POST['status'];
    $notes = $_POST['notes'] ?? '';
    
    try {
        $stmt = $conn->prepare("UPDATE investor_ndas SET status = ?, notes = ?, last_activity = NOW() WHERE id = ?");
        $stmt->bind_param('ssi', $newStatus, $notes, $investorId);
        $stmt->execute();
        
        // Log activity
        $logStmt = $conn->prepare("INSERT INTO investor_activity_log (investor_id, activity_type, description) VALUES (?, 'status_changed', ?)");
        $desc = "Status changed to: $newStatus";
        $logStmt->bind_param('is', $investorId, $desc);
        $logStmt->execute();
        
        $success = "Investor status updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin-login.php');
    exit;
}

// Get filter
$statusFilter = $_GET['status'] ?? 'all';

// Fetch investors
try {
    if ($statusFilter === 'all') {
        $result = $conn->query("SELECT * FROM investor_ndas ORDER BY created_at DESC");
    } else {
        $stmt = $conn->prepare("SELECT * FROM investor_ndas WHERE status = ? ORDER BY created_at DESC");
        $stmt->bind_param('s', $statusFilter);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    $investors = $result->fetch_all(MYSQLI_ASSOC);
    
    // Get statistics
    $stats = [
        'total' => $conn->query("SELECT COUNT(*) as count FROM investor_ndas")->fetch_assoc()['count'],
        'nda_signed' => $conn->query("SELECT COUNT(*) as count FROM investor_ndas WHERE status = 'nda_signed'")->fetch_assoc()['count'],
        'presentation_viewed' => $conn->query("SELECT COUNT(*) as count FROM investor_ndas WHERE status = 'presentation_viewed'")->fetch_assoc()['count'],
        'meeting_scheduled' => $conn->query("SELECT COUNT(*) as count FROM investor_ndas WHERE status = 'meeting_scheduled'")->fetch_assoc()['count'],
        'due_diligence' => $conn->query("SELECT COUNT(*) as count FROM investor_ndas WHERE status = 'due_diligence'")->fetch_assoc()['count'],
        'term_sheet' => $conn->query("SELECT COUNT(*) as count FROM investor_ndas WHERE status = 'term_sheet'")->fetch_assoc()['count'],
        'closed' => $conn->query("SELECT COUNT(*) as count FROM investor_ndas WHERE status = 'closed'")->fetch_assoc()['count'],
    ];
} catch (Exception $e) {
    $error = "Error loading investors: " . $e->getMessage();
    $investors = [];
    $stats = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investor Dashboard - AiniTravel Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            font-size: 2rem;
            font-weight: 900;
        }

        .header-title {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-info {
            text-align: right;
        }

        .admin-name {
            font-weight: 600;
            font-size: 1rem;
        }

        .admin-role {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 900;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-label {
            font-weight: 600;
            color: #333;
        }

        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            color: #666;
            transition: all 0.3s ease;
        }

        .filter-btn:hover, .filter-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        .investors-table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 20px;
            text-align: left;
            font-weight: 700;
            color: #333;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        td {
            padding: 20px;
            border-top: 1px solid #f0f0f0;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-nda_signed { background: #e3f2fd; color: #1976d2; }
        .status-presentation_viewed { background: #f3e5f5; color: #7b1fa2; }
        .status-meeting_scheduled { background: #fff3e0; color: #f57c00; }
        .status-due_diligence { background: #fce4ec; color: #c2185b; }
        .status-term_sheet { background: #e8f5e9; color: #388e3c; }
        .status-closed { background: #c8e6c9; color: #2e7d32; }
        .status-passed { background: #ffebee; color: #c62828; }

        .investor-type {
            font-size: 0.85rem;
            color: #666;
            text-transform: capitalize;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #666;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .signature-preview {
            max-width: 200px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 5px;
            cursor: pointer;
        }

        .signature-preview:hover {
            border-color: #667eea;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .header {
                flex-direction: column;
                gap: 20px;
            }

            table {
                font-size: 0.85rem;
            }

            th, td {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="logo">🚀</div>
            <div class="header-title">Investor Dashboard</div>
        </div>
        <div class="header-right">
            <div class="admin-info">
                <div class="admin-name"><?= htmlspecialchars($_SESSION['admin_name']) ?></div>
                <div class="admin-role"><?= ucfirst(str_replace('_', ' ', $_SESSION['admin_role'])) ?></div>
            </div>
            <a href="?logout=1" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success)): ?>
            <div style="background: #d4edda; border: 2px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                ✓ <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div style="background: #f8d7da; border: 2px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                ⚠ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total'] ?? 0 ?></div>
                <div class="stat-label">Total Investors</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['nda_signed'] ?? 0 ?></div>
                <div class="stat-label">NDA Signed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['meeting_scheduled'] ?? 0 ?></div>
                <div class="stat-label">Meetings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['due_diligence'] ?? 0 ?></div>
                <div class="stat-label">Due Diligence</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['term_sheet'] ?? 0 ?></div>
                <div class="stat-label">Term Sheets</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['closed'] ?? 0 ?></div>
                <div class="stat-label">Closed Deals</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <span class="filter-label">Filter by Status:</span>
            <a href="?status=all" class="filter-btn <?= $statusFilter === 'all' ? 'active' : '' ?>">All</a>
            <a href="?status=nda_signed" class="filter-btn <?= $statusFilter === 'nda_signed' ? 'active' : '' ?>">NDA Signed</a>
            <a href="?status=presentation_viewed" class="filter-btn <?= $statusFilter === 'presentation_viewed' ? 'active' : '' ?>">Viewed</a>
            <a href="?status=meeting_scheduled" class="filter-btn <?= $statusFilter === 'meeting_scheduled' ? 'active' : '' ?>">Meeting</a>
            <a href="?status=due_diligence" class="filter-btn <?= $statusFilter === 'due_diligence' ? 'active' : '' ?>">Due Diligence</a>
            <a href="?status=term_sheet" class="filter-btn <?= $statusFilter === 'term_sheet' ? 'active' : '' ?>">Term Sheet</a>
            <a href="?status=closed" class="filter-btn <?= $statusFilter === 'closed' ? 'active' : '' ?>">Closed</a>
        </div>

        <!-- Investors Table -->
        <div class="investors-table">
            <?php if (empty($investors)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No investors yet</h3>
                    <p>When investors sign the NDA, they will appear here.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Type</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Signed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($investors as $investor): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($investor['full_name']) ?></strong><br>
                                    <small style="color: #999;"><?= htmlspecialchars($investor['title']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($investor['company']) ?></td>
                                <td>
                                    <span class="investor-type">
                                        <?= str_replace('-', ' ', $investor['investor_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="mailto:<?= htmlspecialchars($investor['email']) ?>" style="color: #667eea; text-decoration: none;">
                                        <?= htmlspecialchars($investor['email']) ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $investor['status'] ?>">
                                        <?= str_replace('_', ' ', $investor['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <small style="color: #999;">
                                        <?= date('M j, Y', strtotime($investor['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="actions">
                                        <button class="btn btn-primary" onclick="viewInvestor(<?= $investor['id'] ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="btn btn-secondary" onclick="updateStatus(<?= $investor['id'] ?>)">
                                            <i class="fas fa-edit"></i> Update
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal" id="updateModal">
        <div class="modal-content">
            <div class="modal-header">Update Investor Status</div>
            <form method="POST">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="investor_id" id="modal-investor-id">
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="modal-status" required>
                        <option value="nda_signed">NDA Signed</option>
                        <option value="presentation_viewed">Presentation Viewed</option>
                        <option value="meeting_scheduled">Meeting Scheduled</option>
                        <option value="due_diligence">Due Diligence</option>
                        <option value="term_sheet">Term Sheet</option>
                        <option value="closed">Closed</option>
                        <option value="passed">Passed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea name="notes" id="modal-notes" placeholder="Add notes about this investor..."></textarea>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Save Changes</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Investor Modal -->
    <div class="modal" id="viewModal">
        <div class="modal-content">
            <div class="modal-header">Investor Details</div>
            <div id="investor-details"></div>
            <button type="button" class="btn btn-secondary" onclick="closeViewModal()" style="width: 100%; margin-top: 20px;">Close</button>
        </div>
    </div>

    <script>
        const investorsData = <?= json_encode($investors) ?>;

        function updateStatus(investorId) {
            const investor = investorsData.find(i => i.id == investorId);
            if (investor) {
                document.getElementById('modal-investor-id').value = investorId;
                document.getElementById('modal-status').value = investor.status;
                document.getElementById('modal-notes').value = investor.notes || '';
                document.getElementById('updateModal').classList.add('active');
            }
        }

        function viewInvestor(investorId) {
            const investor = investorsData.find(i => i.id == investorId);
            if (investor) {
                const html = `
                    <div style="line-height: 1.8;">
                        <p><strong>Full Name:</strong> ${investor.full_name}</p>
                        <p><strong>Email:</strong> <a href="mailto:${investor.email}">${investor.email}</a></p>
                        <p><strong>Company:</strong> ${investor.company}</p>
                        <p><strong>Title:</strong> ${investor.title}</p>
                        <p><strong>Investor Type:</strong> ${investor.investor_type.replace('-', ' ')}</p>
                        <p><strong>Status:</strong> <span class="status-badge status-${investor.status}">${investor.status.replace('_', ' ')}</span></p>
                        <p><strong>Signed At:</strong> ${new Date(investor.agreed_at).toLocaleString()}</p>
                        <p><strong>IP Address:</strong> ${investor.ip_address || 'N/A'}</p>
                        ${investor.notes ? `<p><strong>Notes:</strong><br>${investor.notes}</p>` : ''}
                        <p><strong>Signature:</strong></p>
                        <img src="${investor.signature_path}" class="signature-preview" style="max-width: 100%;">
                    </div>
                `;
                document.getElementById('investor-details').innerHTML = html;
                document.getElementById('viewModal').classList.add('active');
            }
        }

        function closeModal() {
            document.getElementById('updateModal').classList.remove('active');
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.remove('active');
        }

        // Close modals on background click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
