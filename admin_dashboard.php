<?php
/**
 * admin_dashboard.php
 * 
 * Main Admin Dashboard - Overview and quick access to all admin functions
 * 
 * @author AI Assistant
 * @date November 11, 2025
 */

session_start();
require_once 'db_connection_pdo.php';

// Admin check with proper role fetching
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user role if not in session
if (!isset($_SESSION['role'])) {
    $stmt = $pdo->prepare("SELECT role FROM ainitravel_users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['role'] = $user['role'] ?? 'user';
}

// Check if admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: public_booking.php');
    exit;
}

// Fetch dashboard statistics
$stats = [];

// Total Users
$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM ainitravel_users")->fetchColumn();
$stats['new_users_today'] = $pdo->query("SELECT COUNT(*) FROM ainitravel_users WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// Experiences
$stats['total_experiences'] = $pdo->query("SELECT COUNT(*) FROM aini_experiences")->fetchColumn();
$stats['pending_experiences'] = $pdo->query("SELECT COUNT(*) FROM aini_experiences WHERE status = 'pending_review'")->fetchColumn();
$stats['active_experiences'] = $pdo->query("SELECT COUNT(*) FROM aini_experiences WHERE status = 'approved'")->fetchColumn();

// Partners
$stats['total_partners'] = $pdo->query("SELECT COUNT(*) FROM aini_experience_partners")->fetchColumn();
$stats['active_partners'] = $pdo->query("SELECT COUNT(*) FROM aini_experience_partners WHERE status = 'active'")->fetchColumn();

// Bookings (if table exists)
try {
    $stats['total_bookings'] = $pdo->query("SELECT COUNT(*) FROM aini_experience_bookings")->fetchColumn();
    $stats['bookings_today'] = $pdo->query("SELECT COUNT(*) FROM aini_experience_bookings WHERE DATE(created_at) = CURDATE()")->fetchColumn();
} catch (Exception $e) {
    $stats['total_bookings'] = 0;
    $stats['bookings_today'] = 0;
}

// Revenue Stats
try {
    $revenue_stats = $pdo->query("SELECT 
        SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END) as total_revenue,
        SUM(CASE WHEN payment_status = 'paid' AND DATE(created_at) = CURDATE() THEN total_price ELSE 0 END) as today_revenue,
        SUM(CASE WHEN payment_status = 'paid' AND YEARWEEK(created_at) = YEARWEEK(NOW()) THEN total_price ELSE 0 END) as week_revenue,
        SUM(CASE WHEN payment_status = 'paid' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) THEN total_price ELSE 0 END) as month_revenue
        FROM aini_experience_bookings")->fetch(PDO::FETCH_ASSOC);
    $stats['revenue'] = $revenue_stats;
} catch (Exception $e) {
    $stats['revenue'] = ['total_revenue' => 0, 'today_revenue' => 0, 'week_revenue' => 0, 'month_revenue' => 0];
}

// Chart data - Daily bookings last 7 days
try {
    $daily_bookings = $pdo->query("SELECT 
        DATE(created_at) as date,
        COUNT(*) as count
        FROM aini_experience_bookings
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $daily_bookings = [];
}

// Chart data - Revenue by category
try {
    $category_revenue = $pdo->query("SELECT 
        e.category,
        COUNT(b.id) as bookings,
        SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_price ELSE 0 END) as revenue
        FROM aini_experiences e
        LEFT JOIN aini_experience_bookings b ON e.id = b.experience_id
        GROUP BY e.category
        ORDER BY revenue DESC
        LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $category_revenue = [];
}

// Status breakdown
try {
    $experience_status = $pdo->query("SELECT 
        status,
        COUNT(*) as count
        FROM aini_experiences
        GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $experience_status = [];
}

// Recent Activity
try {
    $recent_users = $pdo->query("SELECT name, email, created_at FROM ainitravel_users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recent_users = [];
}

try {
    $recent_experiences = $pdo->query("SELECT name as title, status, created_at FROM aini_experiences ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recent_experiences = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AiniTravel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .dashboard-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            color: #667eea;
            font-size: 32px;
        }
        .header .breadcrumb {
            color: #666;
            font-size: 14px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        .stat-card.purple .icon { background: #ede9fe; color: #7c3aed; }
        .stat-card.blue .icon { background: #dbeafe; color: #3b82f6; }
        .stat-card.green .icon { background: #d1fae5; color: #10b981; }
        .stat-card.orange .icon { background: #fed7aa; color: #f59e0b; }
        .stat-card.red .icon { background: #fee2e2; color: #ef4444; }
        .stat-card.teal .icon { background: #ccfbf1; color: #14b8a6; }
        
        .stat-card .value {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .stat-card .label {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .stat-card .trend {
            font-size: 12px;
            color: #10b981;
        }
        .stat-card .trend.down {
            color: #ef4444;
        }
        
        /* Quick Actions Grid */
        .quick-actions {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .quick-actions h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .action-btn {
            display: flex;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(102, 126, 234, 0.3);
        }
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .action-btn.green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .action-btn.blue {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        .action-btn.orange {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        .action-btn.red {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        .action-btn.teal {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
        }
        .action-btn .icon {
            font-size: 24px;
            margin-right: 15px;
        }
        .action-btn .content {
            flex: 1;
        }
        .action-btn .title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .action-btn .desc {
            font-size: 12px;
            opacity: 0.9;
        }
        
        /* Recent Activity */
        .activity-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .activity-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .activity-card h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .activity-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-item .info {
            flex: 1;
        }
        .activity-item .title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .activity-item .meta {
            font-size: 12px;
            color: #666;
        }
        .activity-item .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge.pending { background: #fef3c7; color: #92400e; }
        .badge.active { background: #d1fae5; color: #065f46; }
        .badge.approved { background: #dbeafe; color: #1e40af; }
        
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .back-btn:hover {
            background: #5568d3;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .actions-grid {
                grid-template-columns: 1fr;
            }
            .activity-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>🎯 Admin Dashboard</h1>
                <div class="breadcrumb">
                    <i class="fas fa-home"></i> Dashboard / Overview
                </div>
            </div>
            <a href="public_booking.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Site
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="icon"><i class="fas fa-users"></i></div>
                <div class="value"><?php echo number_format($stats['total_users']); ?></div>
                <div class="label">Total Users</div>
                <div class="trend">
                    <i class="fas fa-arrow-up"></i> +<?php echo $stats['new_users_today']; ?> today
                </div>
            </div>
            
            <div class="stat-card blue">
                <div class="icon"><i class="fas fa-map-marked-alt"></i></div>
                <div class="value"><?php echo number_format($stats['total_experiences']); ?></div>
                <div class="label">Total Experiences</div>
                <div class="trend">
                    <?php echo $stats['active_experiences']; ?> active
                </div>
            </div>
            
            <div class="stat-card orange">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <div class="value"><?php echo number_format($stats['pending_experiences']); ?></div>
                <div class="label">Pending Review</div>
                <div class="trend">
                    Needs attention
                </div>
            </div>
            
            <div class="stat-card green">
                <div class="icon"><i class="fas fa-briefcase"></i></div>
                <div class="value"><?php echo number_format($stats['total_partners']); ?></div>
                <div class="label">Partners</div>
                <div class="trend">
                    <?php echo $stats['active_partners']; ?> active
                </div>
            </div>
            
            <div class="stat-card teal">
                <div class="icon"><i class="fas fa-calendar-check"></i></div>
                <div class="value"><?php echo number_format($stats['total_bookings']); ?></div>
                <div class="label">Total Bookings</div>
                <div class="trend">
                    <i class="fas fa-arrow-up"></i> +<?php echo $stats['bookings_today']; ?> today
                </div>
            </div>
            
            <div class="stat-card red">
                <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="value">$<?php echo number_format($stats['revenue']['month_revenue'] ?? 0, 0); ?></div>
                <div class="label">Revenue This Month</div>
                <div class="trend">
                    $<?php echo number_format($stats['revenue']['today_revenue'] ?? 0, 0); ?> today
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="activity-section">
            <div class="activity-card">
                <h3><i class="fas fa-chart-line"></i> Bookings Last 7 Days</h3>
                <canvas id="bookingsChart" style="max-height: 250px;"></canvas>
            </div>
            
            <div class="activity-card">
                <h3><i class="fas fa-chart-pie"></i> Experience Status</h3>
                <canvas id="statusChart" style="max-height: 250px;"></canvas>
            </div>
        </div>

        <div class="activity-section">
            <div class="activity-card">
                <h3><i class="fas fa-chart-bar"></i> Revenue by Category</h3>
                <canvas id="categoryChart" style="max-height: 300px;"></canvas>
            </div>
            
            <div class="activity-card">
                <h3><i class="fas fa-money-bill-wave"></i> Revenue Overview</h3>
                <div style="padding: 20px 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 15px;">
                        <div>
                            <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Total Revenue</div>
                            <div style="font-size: 28px; font-weight: bold; color: #667eea;">$<?php echo number_format($stats['revenue']['total_revenue'] ?? 0, 2); ?></div>
                        </div>
                        <div style="font-size: 36px; color: #667eea;"><i class="fas fa-chart-line"></i></div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                        <div style="padding: 15px; background: #f0fdf4; border-radius: 8px; text-align: center;">
                            <div style="font-size: 11px; color: #16a34a; font-weight: 600; margin-bottom: 5px;">TODAY</div>
                            <div style="font-size: 20px; font-weight: bold; color: #16a34a;">$<?php echo number_format($stats['revenue']['today_revenue'] ?? 0, 0); ?></div>
                        </div>
                        <div style="padding: 15px; background: #eff6ff; border-radius: 8px; text-align: center;">
                            <div style="font-size: 11px; color: #2563eb; font-weight: 600; margin-bottom: 5px;">THIS WEEK</div>
                            <div style="font-size: 20px; font-weight: bold; color: #2563eb;">$<?php echo number_format($stats['revenue']['week_revenue'] ?? 0, 0); ?></div>
                        </div>
                        <div style="padding: 15px; background: #fef3c7; border-radius: 8px; text-align: center;">
                            <div style="font-size: 11px; color: #d97706; font-weight: 600; margin-bottom: 5px;">THIS MONTH</div>
                            <div style="font-size: 20px; font-weight: bold; color: #d97706;">$<?php echo number_format($stats['revenue']['month_revenue'] ?? 0, 0); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="actions-grid">
                <a href="admin_experience_moderate.php" class="action-btn">
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                    <div class="content">
                        <div class="title">Moderate Experiences</div>
                        <div class="desc"><?php echo $stats['pending_experiences']; ?> pending approval</div>
                    </div>
                </a>
                
                <a href="admin_users.php" class="action-btn blue">
                    <div class="icon"><i class="fas fa-users-cog"></i></div>
                    <div class="content">
                        <div class="title">Manage Users</div>
                        <div class="desc">View, edit, suspend users</div>
                    </div>
                </a>
                
                <a href="admin_partners.php" class="action-btn green">
                    <div class="icon"><i class="fas fa-handshake"></i></div>
                    <div class="content">
                        <div class="title">Manage Partners</div>
                        <div class="desc">Approve tour operators</div>
                    </div>
                </a>
                
                <a href="admin_bookings.php" class="action-btn teal">
                    <div class="icon"><i class="fas fa-ticket-alt"></i></div>
                    <div class="content">
                        <div class="title">View Bookings</div>
                        <div class="desc">All reservations</div>
                    </div>
                </a>
                
                <a href="admin_experiences.php" class="action-btn orange">
                    <div class="icon"><i class="fas fa-edit"></i></div>
                    <div class="content">
                        <div class="title">Manage Experiences</div>
                        <div class="desc">Edit, delete experiences</div>
                    </div>
                </a>
                
                <a href="admin_reports.php" class="action-btn red">
                    <div class="icon"><i class="fas fa-chart-line"></i></div>
                    <div class="content">
                        <div class="title">Reports & Analytics</div>
                        <div class="desc">Revenue, conversions</div>
                    </div>
                </a>
                
                <a href="admin_reviews.php" class="action-btn">
                    <div class="icon"><i class="fas fa-star"></i></div>
                    <div class="content">
                        <div class="title">Review Moderation</div>
                        <div class="desc">Manage user reviews</div>
                    </div>
                </a>
                
                <a href="admin_settings.php" class="action-btn blue">
                    <div class="icon"><i class="fas fa-cog"></i></div>
                    <div class="content">
                        <div class="title">System Settings</div>
                        <div class="desc">Site configuration</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="activity-section">
            <div class="activity-card">
                <h3><i class="fas fa-user-plus"></i> Recent Users</h3>
                <?php foreach ($recent_users as $user): ?>
                    <div class="activity-item">
                        <div class="info">
                            <div class="title"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="meta">
                                <?php echo htmlspecialchars($user['email']); ?> • 
                                <?php echo date('M d, Y g:i A', strtotime($user['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="activity-card">
                <h3><i class="fas fa-map-marked-alt"></i> Recent Experiences</h3>
                <?php foreach ($recent_experiences as $exp): ?>
                    <div class="activity-item">
                        <div class="info">
                            <div class="title"><?php echo htmlspecialchars($exp['title']); ?></div>
                            <div class="meta">
                                <?php echo date('M d, Y g:i A', strtotime($exp['created_at'])); ?>
                            </div>
                        </div>
                        <span class="badge <?php echo $exp['status']; ?>">
                            <?php echo strtoupper(str_replace('_', ' ', $exp['status'])); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        // Bookings Chart
        const bookingsCtx = document.getElementById('bookingsChart').getContext('2d');
        const bookingsData = <?php echo json_encode($daily_bookings); ?>;
        
        new Chart(bookingsCtx, {
            type: 'bar',
            data: {
                labels: bookingsData.map(d => {
                    const date = new Date(d.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Bookings',
                    data: bookingsData.map(d => d.count),
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // Status Pie Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusData = <?php echo json_encode($experience_status); ?>;
        
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusData.map(s => s.status.replace('_', ' ').toUpperCase()),
                datasets: [{
                    data: statusData.map(s => s.count),
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(156, 163, 175, 0.8)'
                    ],
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Category Revenue Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryData = <?php echo json_encode($category_revenue); ?>;
        
        new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: categoryData.map(c => c.category || 'Unknown'),
                datasets: [{
                    label: 'Revenue ($)',
                    data: categoryData.map(c => parseFloat(c.revenue || 0)),
                    backgroundColor: 'rgba(20, 184, 166, 0.8)',
                    borderColor: 'rgba(20, 184, 166, 1)',
                    borderWidth: 2,
                    borderRadius: 8
                }, {
                    label: 'Bookings',
                    data: categoryData.map(c => c.bookings),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    </script>
</body>
</html>
