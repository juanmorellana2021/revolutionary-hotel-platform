<?php
session_start();
require_once '../db_connection_pdo.php';

// Check if logged in
if (!isset($_SESSION['partner_id'])) {
    header('Location: login.php');
    exit();
}

// Get hotel ID
$hotel_id = $_GET['id'] ?? null;
if (!$hotel_id) {
    header('Location: dashboard.php');
    exit();
}

// Get partner info
$stmt = $pdo->prepare("SELECT * FROM aini_partner_businesses WHERE id = ?");
$stmt->execute([$_SESSION['partner_id']]);
$partner = $stmt->fetch(PDO::FETCH_ASSOC);

// Get hotel info - make sure it belongs to this partner
$stmt = $pdo->prepare("
    SELECT hp.* 
    FROM hotel_properties hp
    WHERE hp.id = ? AND (hp.partner_business_id = ? OR hp.email = ?)
");
$stmt->execute([$hotel_id, $_SESSION['partner_id'], $partner['email']]);
$hotel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hotel) {
    header('Location: dashboard.php');
    exit();
}

// Filters
$status_filter = $_GET['status'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM guest_bookings WHERE hotel_id = ?";
$params = [$hotel_id];

if ($status_filter !== 'all') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

if ($date_from) {
    $query .= " AND check_in >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $query .= " AND check_out <= ?";
    $params[] = $date_to;
}

if ($search) {
    $query .= " AND (guest_name LIKE ? OR guest_email LIKE ? OR booking_reference LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$today = date('Y-m-d');
$this_month_start = date('Y-m-01');
$this_month_end = date('Y-m-t');

$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
        SUM(CASE WHEN check_in = ? THEN 1 ELSE 0 END) as checkins_today,
        SUM(CASE WHEN check_in >= ? AND check_in <= ? AND status IN ('confirmed', 'pending') THEN total_price ELSE 0 END) as revenue_this_month
    FROM guest_bookings 
    WHERE hotel_id = ?
");
$stmt->execute([$today, $this_month_start, $this_month_end, $hotel_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$hotel_revenue = $stats['revenue_this_month'] * 0.88; // Hotel keeps 88%
$platform_commission = $stats['revenue_this_month'] * 0.12; // Platform takes 12%

// Status colors and icons
$status_config = [
    'pending' => ['color' => 'yellow', 'icon' => 'fa-clock', 'label' => 'Pending'],
    'confirmed' => ['color' => 'green', 'icon' => 'fa-check-circle', 'label' => 'Confirmed'],
    'cancelled' => ['color' => 'red', 'icon' => 'fa-times-circle', 'label' => 'Cancelled']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - <?= htmlspecialchars($hotel['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Top Navigation -->
    <nav class="gradient-bg shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="hotel_profile.php?id=<?= $hotel_id ?>" class="text-white hover:text-purple-100 transition">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <h1 class="text-2xl font-bold text-white">
                        <i class="fas fa-calendar-check mr-2"></i>Bookings - <?= htmlspecialchars($hotel['name']) ?>
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-white hover:text-purple-100">
                        <i class="fas fa-th-large mr-1"></i>Dashboard
                    </a>
                    <a href="logout.php" class="bg-white text-purple-600 px-4 py-2 rounded-lg font-semibold hover:bg-purple-50 transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <i class="fas fa-calendar-alt text-3xl text-purple-600 mr-4"></i>
                    <div>
                        <p class="text-sm text-gray-500">Total Bookings</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $stats['total_bookings'] ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-3xl text-green-600 mr-4"></i>
                    <div>
                        <p class="text-sm text-gray-500">Confirmed</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $stats['confirmed_bookings'] ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <i class="fas fa-clock text-3xl text-yellow-600 mr-4"></i>
                    <div>
                        <p class="text-sm text-gray-500">Pending</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $stats['pending_bookings'] ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <i class="fas fa-door-open text-3xl text-blue-600 mr-4"></i>
                    <div>
                        <p class="text-sm text-gray-500">Check-ins Today</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $stats['checkins_today'] ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-600 to-purple-800 rounded-lg shadow p-6 text-white">
                <div>
                    <p class="text-sm text-purple-100 mb-1">Revenue This Month</p>
                    <p class="text-2xl font-bold">$<?= number_format($hotel_revenue, 2) ?></p>
                    <p class="text-xs text-purple-200 mt-1">You keep 88%</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <input type="hidden" name="id" value="<?= $hotel_id ?>">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Status</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="confirmed" <?= $status_filter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Check-in From</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Check-in To</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                           placeholder="Name, email, or booking #"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>

                <div class="flex items-end space-x-2">
                    <button type="submit" class="flex-1 bg-purple-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-purple-700 transition">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                    <a href="?id=<?= $hotel_id ?>" class="bg-gray-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-gray-600 transition">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Bookings List -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">
                    <i class="fas fa-list mr-2 text-purple-600"></i>Bookings (<?= count($bookings) ?>)
                </h2>
                <button onclick="window.print()" class="bg-gray-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-gray-700 transition">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
            </div>

            <?php if (empty($bookings)): ?>
                <div class="p-12 text-center">
                    <i class="fas fa-calendar-times text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No Bookings Found</h3>
                    <p class="text-gray-500">
                        <?php if ($status_filter !== 'all' || $date_from || $date_to || $search): ?>
                            Try adjusting your filters to see more results.
                        <?php else: ?>
                            Bookings will appear here once guests make reservations.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Ref</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guest</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check-in</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check-out</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($bookings as $booking): 
                                $config = $status_config[$booking['status']] ?? $status_config['pending'];
                                $nights = (strtotime($booking['check_out']) - strtotime($booking['check_in'])) / 86400;
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= $booking['booking_reference'] ?? 'ANI-' . str_pad($booking['id'], 6, '0', STR_PAD_LEFT) ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?= date('M d, Y', strtotime($booking['created_at'])) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($booking['guest_name'] ?? 'N/A') ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($booking['guest_email'] ?? 'N/A') ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($booking['guest_phone'] ?? 'N/A') ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= date('M d, Y', strtotime($booking['check_in'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= date('M d, Y', strtotime($booking['check_out'])) ?>
                                        <span class="text-xs text-gray-500 block"><?= $nights ?> night<?= $nights != 1 ? 's' : '' ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">
                                            $<?= number_format($booking['total_price'], 2) ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            You keep: $<?= number_format($booking['total_price'] * 0.88, 2) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?= $config['color'] ?>-100 text-<?= $config['color'] ?>-800">
                                            <i class="fas <?= $config['icon'] ?> mr-1"></i>
                                            <?= $config['label'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="booking_details.php?id=<?= $booking['id'] ?>&hotel_id=<?= $hotel_id ?>" 
                                           class="text-purple-600 hover:text-purple-900 mr-3">
                                            <i class="fas fa-eye mr-1"></i>View
                                        </a>
                                        <button onclick="openBookingChat(<?= $booking['id'] ?>, '<?= htmlspecialchars($booking['guest_name'] ?? 'Guest', ENT_QUOTES) ?>', '<?= htmlspecialchars($booking['booking_reference'] ?? 'ANI-' . str_pad($booking['id'], 6, '0', STR_PAD_LEFT), ENT_QUOTES) ?>')" 
                                                class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-comment-dots mr-1"></i>Chat
                                        </button>
                                        <?php if ($booking['status'] === 'pending'): ?>
                                            <a href="approve_booking.php?id=<?= $booking['id'] ?>&hotel_id=<?= $hotel_id ?>" 
                                               class="text-green-600 hover:text-green-900"
                                               onclick="return confirm('Approve this booking?');">
                                                <i class="fas fa-check-circle mr-1"></i>Approve
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Chat Bubble Modal -->
    <div id="chatModal" class="hidden fixed bottom-4 right-4 w-96 h-[600px] bg-white rounded-2xl shadow-2xl z-50 flex flex-col">
        <!-- Chat Header -->
        <div class="gradient-bg text-white px-6 py-4 rounded-t-2xl flex justify-between items-center">
            <div>
                <h3 class="font-bold text-lg" id="chatGuestName">Guest Chat</h3>
                <p class="text-sm opacity-90">Reserva #<span id="chatBookingRef">-</span></p>
            </div>
            <button onclick="closeBookingChat()" class="hover:bg-white/20 rounded-full p-2 transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Chat Messages -->
        <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
            <div class="text-center text-gray-500 text-sm py-4">
                <i class="fas fa-info-circle"></i> Chat with your guest
            </div>
        </div>

        <!-- Chat Input -->
        <div class="p-4 border-t border-gray-200 bg-white rounded-b-2xl">
            <div class="flex gap-2">
                <input type="text" id="chatInput" placeholder="Type your message..." class="flex-1 px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:border-purple-500" onkeypress="if(event.key==='Enter') sendBookingMessage()">
                <button onclick="sendBookingMessage()" class="px-6 py-2 bg-purple-600 text-white rounded-full hover:bg-purple-700 transition font-semibold">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentBookingId = null;
        let currentConversationId = null;
        let messageCheckInterval = null;

        function openBookingChat(bookingId, guestName, bookingRef) {
            currentBookingId = bookingId;
            document.getElementById('chatGuestName').textContent = guestName;
            document.getElementById('chatBookingRef').textContent = bookingRef;
            document.getElementById('chatModal').classList.remove('hidden');
            document.getElementById('chatMessages').innerHTML = '<div class="text-center text-gray-500 text-sm py-4"><i class="fas fa-spinner fa-spin"></i> Loading messages...</div>';
            
            // Load message history and start polling
            loadMessages(bookingId);
            
            // Poll for new messages every 3 seconds
            if (messageCheckInterval) clearInterval(messageCheckInterval);
            messageCheckInterval = setInterval(() => loadMessages(bookingId), 3000);
        }

        function closeBookingChat() {
            document.getElementById('chatModal').classList.add('hidden');
            currentBookingId = null;
            if (messageCheckInterval) {
                clearInterval(messageCheckInterval);
                messageCheckInterval = null;
            }
        }

        async function loadMessages(bookingId) {
            try {
                const response = await fetch(`../booking_chat_api.php?action=get_messages&booking_id=${bookingId}`, {
                    credentials: 'same-origin'
                });
                
                // Show detailed error if response is not OK
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('API Error Response:', errorText);
                    document.getElementById('chatMessages').innerHTML = `
                        <div class="text-red-600 text-xs p-4 bg-red-50 rounded">
                            <strong>HTTP ${response.status} Error:</strong><br>
                            <pre class="mt-2 whitespace-pre-wrap">${errorText.substring(0, 500)}</pre>
                        </div>`;
                    return;
                }
                
                const data = await response.json();
                
                if (data.success) {
                    currentConversationId = data.data.conversation_id;
                    const messages = data.data.messages;
                    
                    const chatBox = document.getElementById('chatMessages');
                    chatBox.innerHTML = '';
                    
                    if (messages.length === 0) {
                        chatBox.innerHTML = '<div class="text-center text-gray-500 text-sm py-4"><i class="fas fa-comments"></i> No messages yet. Start the conversation!</div>';
                    } else {
                        messages.forEach(msg => {
                            appendMessage(msg.sender_type, msg.sender_name, msg.message, msg.created_at);
                        });
                        chatBox.scrollTop = chatBox.scrollHeight;
                        
                        // Mark messages as read
                        markBookingRead();
                    }
                } else {
                    // Show API error message
                    document.getElementById('chatMessages').innerHTML = `
                        <div class="text-red-600 text-xs p-4 bg-red-50 rounded">
                            <strong>API Error:</strong> ${data.error || 'Unknown error'}
                        </div>`;
                }
            } catch (error) {
                console.error('Error loading messages:', error);
                document.getElementById('chatMessages').innerHTML = `
                    <div class="text-red-600 text-xs p-4 bg-red-50 rounded">
                        <strong>JavaScript Error:</strong> ${error.message}<br>
                        <small class="text-gray-600 mt-1 block">Check browser console for details</small>
                    </div>`;
            }
        }

        async function sendBookingMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            
            if (!message || !currentBookingId) return;
            
            input.value = '';
            input.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('action', 'send_message');
                formData.append('booking_id', currentBookingId);
                formData.append('message', message);
                formData.append('sender_type', 'owner');
                
                const response = await fetch('../booking_chat_api.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Reload messages to show the new one
                    loadMessages(currentBookingId);
                } else {
                    alert('Error: ' + (data.error || 'Could not send message'));
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Error sending message');
            } finally {
                input.disabled = false;
                input.focus();
            }
        }

        async function markBookingRead() {
            // Messages marked as read automatically when loading
        }

        function appendMessage(senderType, senderName, message, timestamp) {
            const chatBox = document.getElementById('chatMessages');
            const isOwner = senderType === 'owner';
            
            // Remove "no messages" placeholder if exists
            if (chatBox.querySelector('.text-gray-500')) {
                chatBox.innerHTML = '';
            }
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `flex ${isOwner ? 'justify-end' : 'justify-start'}`;
            
            messageDiv.innerHTML = `
                <div class="max-w-[75%] ${isOwner ? 'bg-purple-600 text-white' : 'bg-white'} rounded-2xl px-4 py-2 shadow-sm">
                    <p class="text-xs ${isOwner ? 'text-purple-200' : 'text-gray-600'} mb-1">${senderName}</p>
                    <p class="text-sm">${escapeHtml(message)}</p>
                    <p class="text-xs ${isOwner ? 'text-purple-200' : 'text-gray-400'} mt-1 opacity-75">${formatTime(timestamp)}</p>
                </div>
            `;
            
            chatBox.appendChild(messageDiv);
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = (now - date) / 1000; // seconds
            
            if (diff < 60) return 'Now';
            if (diff < 3600) return Math.floor(diff / 60) + 'm';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h';
            return date.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
        }

        // Chat now uses simple AJAX polling instead of Socket.IO
    </script>

</body>
</html>
