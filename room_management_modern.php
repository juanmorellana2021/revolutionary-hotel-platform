<?php
// MODERN DASHBOARD - PMS VERSION - Nov 2, 2025
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'includes/templates/head_tailwind.php';
require_once 'includes/templates/sidebar_tailwind.php';
require_once 'includes/templates/topbar_tailwind.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: owner_login.php');
    exit;
}

// Get current hotel info
$hotelInfo = new HotelInfo();
$hotel = $hotelInfo->getHotelInfo();

$database = new Database();
$conn = $database->getConnection();

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$bookingObj = new Booking();
$roomObj = new Room();

// Get hotel bookings (all bookings for this hotel, not just user's bookings)
$currentHotelId = $_SESSION['current_hotel_id'] ?? 1;
$stmt = $conn->prepare("
    SELECT b.*, r.room_number, r.room_type 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.hotel_id = ? 
    ORDER BY b.created_at DESC
");
$stmt->execute([$currentHotelId]);
$userBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rooms = $roomObj->getAllRooms();

// Get room photos efficiently (single query)
$roomPhotos = [];
$photoStmt = $conn->prepare("SELECT room_id, photo_path, is_primary FROM room_photos WHERE is_primary = 1 ORDER BY room_id");
$photoStmt->execute();
while ($photo = $photoStmt->fetch(PDO::FETCH_ASSOC)) {
    $roomPhotos[$photo['room_id']] = $photo['photo_path'];
}

// Calculate availability based on current bookings
$today = date('Y-m-d');
$occupiedRoomIds = [];
$availableCount = 0;
$occupiedCount = 0;

// Get currently occupied rooms (check_in_date <= today AND check_out_date > today)
$occupiedStmt = $conn->prepare("
    SELECT DISTINCT room_id
    FROM bookings
    WHERE hotel_id = ?
    AND check_in_date <= ?
    AND check_out_date > ?
    AND status IN ('confirmed', 'checked_in')
");
$occupiedStmt->execute([$currentHotelId, $today, $today]);
while ($row = $occupiedStmt->fetch(PDO::FETCH_ASSOC)) {
    $occupiedRoomIds[] = $row['room_id'];
}

// Update room availability status
foreach ($rooms as &$room) {
    $room['is_available'] = !in_array($room['id'], $occupiedRoomIds);
    if ($room['is_available']) {
        $availableCount++;
    } else {
        $occupiedCount++;
    }
}
unset($room);

// Get user role for sidebar
$stmt = $conn->prepare("SELECT user_role, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialize templates
$headTemplate = new HeadTemplate();
$headTemplate->setTitle(($hotel['hotel_name'] ?? 'Dashboard') . ' - Room Management');
$headTemplate->render();

$sidebarTemplate = new SidebarTemplate($hotel['hotel_name'] ?? 'AiNi Travel PMS', 'room_management_modern', $userInfo['role'] ?? 'manager');

$topbarTemplate = new TopbarTemplate();
$topbarTemplate->setUser($user);
?>

<!-- Main Container -->
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <?php $sidebarTemplate->render(); ?>

    <!-- Main Content Area -->
    <div class="content-offset ml-64 transition-all duration-300">
        <?php $topbarTemplate->render(); ?>

        <!-- Dashboard Content -->
        <main class="topbar-offset pt-16 p-6">
            <!-- Room Management Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">🏨 Room Management</h1>
                    <p class="text-gray-400">Manage your hotel rooms and availability</p>
                </div>
                <button onclick="openAddRoomModal()" class="bg-[#BCCEFB] text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#A8BFEA] hover:shadow-lg transition">
                    <i class="fas fa-plus mr-2"></i> Add New Room
                </button>
            </div>
            
            <!-- Compact Stats - Tailwind -->
            <div class="grid grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-800 bg-opacity-50 rounded-xl p-4 border border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Total Rooms</p>
                            <p class="text-2xl font-bold text-white mt-1"><?php echo count($rooms); ?></p>
                        </div>
                        <div class="bg-purple-500 bg-opacity-20 p-3 rounded-lg">
                            <i class="fas fa-bed text-purple-400 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 bg-opacity-50 rounded-xl p-4 border border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Available</p>
                            <p class="text-2xl font-bold text-green-400 mt-1">
                                <?php echo $availableCount; ?>
                            </p>
                        </div>
                        <div class="bg-green-500 bg-opacity-20 p-3 rounded-lg">
                            <i class="fas fa-check-circle text-green-400 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 bg-opacity-50 rounded-xl p-4 border border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Occupied</p>
                            <p class="text-2xl font-bold text-orange-400 mt-1">
                                <?php echo $occupiedCount; ?>
                            </p>
                        </div>
                        <div class="bg-orange-500 bg-opacity-20 p-3 rounded-lg">
                            <i class="fas fa-user text-orange-400 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 bg-opacity-50 rounded-xl p-4 border border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Avg Price</p>
                            <p class="text-2xl font-bold text-blue-400 mt-1">
                                $<?php
                                $totalPrice = array_reduce($rooms, function($sum, $r) { 
                                    return $sum + ($r['price'] ?? 0); 
                                }, 0);
                                echo number_format(count($rooms) > 0 ? $totalPrice / count($rooms) : 0, 0);
                                ?>
                            </p>
                        </div>
                        <div class="bg-blue-500 bg-opacity-20 p-3 rounded-lg">
                            <i class="fas fa-dollar-sign text-blue-400 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rooms Grid - App-like -->
            <div class="bg-gray-800 bg-opacity-30 rounded-xl p-6 border border-gray-700">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-white">All Rooms</h2>
                    <div class="text-sm text-gray-400">
                        Showing <?php echo count($rooms); ?> room<?php echo count($rooms) !== 1 ? 's' : ''; ?>
                    </div>
                </div>

                <!-- Compact Room Grid - 4 columns, smaller cards -->
                <div class="grid grid-cols-4 gap-3 max-h-[calc(100vh-400px)] overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-gray-700 scrollbar-track-gray-900">
                    <?php if (empty($rooms)): ?>
                        <div class="col-span-4 text-center py-12">
                            <i class="fas fa-bed text-gray-600 text-5xl mb-4"></i>
                            <p class="text-gray-400">No rooms added yet</p>
                            <button onclick="openAddRoomModal()" class="mt-4 bg-[#BCCEFB] text-white px-6 py-2 rounded-lg hover:bg-[#A8BFEA] transition">Add Your First Room</button>
                        </div>
                    <?php else: ?>
                        <?php foreach($rooms as $room): ?>
                            <div class="bg-gray-900 bg-opacity-50 rounded-lg overflow-hidden border border-gray-700 hover:border-indigo-500 transition cursor-pointer group">
                                <!-- Room Photo -->
                                <div class="h-24 bg-gray-800 relative overflow-hidden">
                                    <?php if (isset($roomPhotos[$room['id']])): ?>
                                        <img src="<?php echo htmlspecialchars($roomPhotos[$room['id']]); ?>" 
                                             alt="Room <?php echo htmlspecialchars($room['room_number']); ?>" 
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center">
                                            <i class="fas fa-bed text-gray-700 text-3xl"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="absolute top-2 right-2 <?php echo ($room['is_available'] ?? false) ? 'bg-green-500' : 'bg-red-500'; ?> bg-opacity-90 text-white px-2 py-0.5 rounded text-xs font-bold">
                                        <?php echo ($room['is_available'] ?? false) ? 'âœ“' : 'âœ—'; ?>
                                    </span>
                                </div>
                                <!-- Room Info -->
                                <div class="p-3">
                                    <div class="mb-2">
                                        <h3 class="text-white font-bold">Room <?php echo htmlspecialchars($room['room_number']); ?></h3>
                                        <p class="text-gray-400 text-xs"><?php echo htmlspecialchars($room['room_type']); ?></p>
                                    </div>
                                    <div class="space-y-1 text-xs mb-3">
                                        <div class="flex items-center text-gray-300">
                                            <i class="fas fa-users w-4 text-indigo-400 mr-1"></i>
                                            <span><?php echo $room['max_occupancy'] ?? 2; ?> guests</span>
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-dollar-sign w-4 text-green-400 mr-1"></i>
                                            <span class="font-bold text-green-400">$<?php echo number_format($room['price'] ?? 0, 0); ?></span>
                                        </div>
                                    </div>
                                    <div class="flex gap-1">
                                        <button onclick="editRoom(<?php echo $room['id']; ?>)" class="flex-1 bg-[#BCCEFB] text-gray-800 py-1.5 rounded text-xs hover:bg-[#A8BFEA] transition" title="Edit Room">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="managePhotos(<?php echo $room['id']; ?>)" class="flex-1 bg-indigo-600 text-white py-1.5 rounded text-xs hover:bg-indigo-700 transition" title="Manage Photos">
                                            <i class="fas fa-camera"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Room Modal -->
    <div id="addRoomModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
        <div class="bg-gray-800 rounded-xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-white">Add New Room</h2>
                <button onclick="closeAddRoomModal()" class="text-gray-400 hover:text-white text-2xl">&times;</button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="add_room" value="1">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-gray-300 text-sm block mb-1">Room Number</label>
                        <input type="text" name="room_number" required class="w-full bg-[#BCCEFB] text-white px-3 py-2 rounded border border-gray-600 focus:border-gray-500 outline-none">
                    </div>
                    <div>
                        <label class="text-gray-300 text-sm block mb-1">Room Type</label>
                        <select name="room_type" class="w-full bg-[#BCCEFB] text-white px-3 py-2 rounded border border-gray-600 focus:border-gray-500 outline-none">
                            <option>Single</option>
                            <option>Double</option>
                            <option>Suite</option>
                            <option>Deluxe</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-gray-300 text-sm block mb-1">Price per Night</label>
                        <input type="number" name="price" step="0.01" required class="w-full bg-[#BCCEFB] text-white px-3 py-2 rounded border border-gray-600 focus:border-gray-500 outline-none">
                    </div>
                    <div>
                        <label class="text-gray-300 text-sm block mb-1">Max Occupancy</label>
                        <input type="number" name="max_occupancy" required class="w-full bg-[#BCCEFB] text-white px-3 py-2 rounded border border-gray-600 focus:border-gray-500 outline-none">
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="flex-1 bg-[#BCCEFB] text-gray-800 py-3 rounded-lg font-semibold hover:bg-[#A8BFEA] transition">
                        <i class="fas fa-plus mr-2"></i> Add Room
                    </button>
                    <button type="button" onclick="closeAddRoomModal()" class="flex-1 bg-[#BCCEFB] text-gray-800 py-3 rounded-lg font-semibold hover:bg-[#BCCEFB] transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openAddRoomModal() {
            document.getElementById('addRoomModal').classList.remove('hidden');
        }
        
        function closeAddRoomModal() {
            document.getElementById('addRoomModal').classList.add('hidden');
        }
        
        // Theme Toggle Functionality
        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('theme-icon');
            
            body.classList.toggle('light-theme');
            
            // Update icon
            if (body.classList.contains('light-theme')) {
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
                localStorage.setItem('theme', 'light');
            } else {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
                localStorage.setItem('theme', 'dark');
            }
        }
        
        // Load saved theme preference on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            const themeIcon = document.getElementById('theme-icon');
            
            if (savedTheme === 'light') {
                document.body.classList.add('light-theme');
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            }
        });
        
        // AI-Powered Room Filtering with Real Ollama Integration
        async function askAI() {
            const input = document.getElementById('aiInput');
            const query = input.value.trim();
            
            if (!query) return;
            
            // Show loading state
            showAIThinking();
            
            try {
                // Call backend AI endpoint
                const formData = new FormData();
                formData.append('query', query);
                
                const response = await fetch('ai_room_query.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Apply AI-generated filters to room display
                    filterRoomsByAI(data.rooms, data.response, data.filtered_count);
                } else {
                    // Fallback to basic filtering
                    console.error('AI query failed:', data.error);
                    showAIError(data.error || 'AI service temporarily unavailable');
                }
                
            } catch (error) {
                console.error('AI request error:', error);
                showAIError('Error connecting to AI service');
            }
        }
        
        function showAIThinking() {
            const existing = document.getElementById('ai-response');
            if (existing) existing.remove();
            
            const response = document.createElement('div');
            response.id = 'ai-response';
            response.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(99, 102, 241, 0.4);
                z-index: 1000;
                max-width: 400px;
                animation: slideIn 0.3s ease-out;
            `;
            
            response.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-robot fa-pulse" style="font-size: 1.5rem;"></i>
                    <div>
                        <div style="font-weight: 600;">Consultando con AI...</div>
                        <div style="font-size: 0.875rem; opacity: 0.9;">Analizando inventario de habitaciones</div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(response);
        }
        
        function filterRoomsByAI(aiRooms, aiMessage, count) {
            const roomCards = document.querySelectorAll('.grid.grid-cols-4 > div:not(.col-span-4)');
            
            // Create map of room numbers from AI response
            const roomNumbersToShow = new Set(aiRooms.map(r => r.room_number));
            
            let visibleCount = 0;
            
            roomCards.forEach(card => {
                // Extract room number from card
                const roomNumberEl = card.querySelector('.text-gray-800.font-bold');
                if (!roomNumberEl) return;
                
                const roomNumber = roomNumberEl.textContent.replace('Room ', '').trim();
                
                // Show only rooms returned by AI
                if (roomNumbersToShow.size === 0 || roomNumbersToShow.has(roomNumber)) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Update display counter
            const countDisplay = document.querySelector('.text-sm.text-gray-400');
            if (countDisplay) {
                countDisplay.textContent = `AI encontrÃ³ ${count} habitaciÃ³n${count !== 1 ? 'es' : ''}`;
            }
            
            // Show AI response
            showAIResponse(aiMessage, count);
        }
        
        function showAIError(error) {
            const existing = document.getElementById('ai-response');
            if (existing) existing.remove();
            
            const response = document.createElement('div');
            response.id = 'ai-response';
            response.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(239, 68, 68, 0.4);
                z-index: 1000;
                max-width: 400px;
                animation: slideIn 0.3s ease-out;
            `;
            
            response.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 1.5rem;"></i>
                    <div>
                        <div style="font-weight: 600;">Error</div>
                        <div style="font-size: 0.875rem; opacity: 0.9;">${error}</div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; margin-left: auto;">Ã—</button>
                </div>
            `;
            
            document.body.appendChild(response);
            
            setTimeout(() => {
                if (response.parentElement) {
                    response.style.animation = 'slideOut 0.3s ease-out';
                    setTimeout(() => response.remove(), 300);
                }
            }, 5000);
        }
        
        function showAIResponse(aiMessage, count) {
            // Remove existing response
            const existing = document.getElementById('ai-response');
            if (existing) existing.remove();
            
            // Create response bubble
            const response = document.createElement('div');
            response.id = 'ai-response';
            response.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(99, 102, 241, 0.4);
                z-index: 1000;
                max-width: 400px;
                animation: slideIn 0.3s ease-out;
            `;
            
            response.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-robot" style="font-size: 1.5rem;"></i>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; margin-bottom: 4px;">ðŸ¤– AI Respuesta</div>
                        <div style="font-size: 0.875rem; opacity: 0.95; line-height: 1.4;">${aiMessage}</div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; flex-shrink: 0;">Ã—</button>
                </div>
            `;
            
            document.body.appendChild(response);
            
            // Auto-remove after 8 seconds
            setTimeout(() => {
                if (response.parentElement) {
                    response.style.animation = 'slideOut 0.3s ease-out';
                    setTimeout(() => response.remove(), 300);
                }
            }, 8000);
        }
        
        // Add animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Edit Room Function
        function editRoom(roomId) {
            window.location.href = 'room_edit.php?id=' + roomId;
        }
        
        // Manage Photos Function
        function managePhotos(roomId) {
            window.location.href = 'photo_upload.php?room_id=' + roomId;
        }
    </script>
</body>
</html>
