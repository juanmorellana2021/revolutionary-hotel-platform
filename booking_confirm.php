<?php
session_start();
require_once 'db_connection_pdo.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login with return URL
    $return_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: login.php?return=" . $return_url);
    exit();
}

// Get parameters
$hotel_id = $_GET['hotel_id'] ?? null;
$room_type = $_GET['room_type'] ?? null;

if (!$hotel_id) {
    header('Location: public_booking.php');
    exit();
}

// Get hotel details
$stmt = $pdo->prepare("SELECT * FROM hotel_properties WHERE id = ?");
$stmt->execute([$hotel_id]);
$hotel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hotel) {
    header('Location: public_booking.php');
    exit();
}

// Get user details
$stmt = $pdo->prepare("SELECT * FROM ainitravel_users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get room details if room_type specified
$room_info = null;
$available_rooms = [];
$rooms_by_type = [];
if ($room_type) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE hotel_id = ? AND room_type = ? AND is_available = 1 LIMIT 1");
    $stmt->execute([$hotel_id, $room_type]);
    $room_info = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // If no room_type specified, fetch all available rooms for selection
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE hotel_id = ? AND is_available = 1 ORDER BY price ASC");
    $stmt->execute([$hotel_id]);
    $available_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group rooms by type
    foreach ($available_rooms as $room) {
        $type = $room['room_type'];
        if (!isset($rooms_by_type[$type])) {
            $rooms_by_type[$type] = [
                'room_type' => $type,
                'count' => 0,
                'price' => $room['price'],
                'currency' => $room['currency'] ?? 'USD',
                'description' => $room['description'] ?? '',
                'amenities' => $room['amenities'] ?? '',
                'max_occupancy' => $room['max_occupancy'] ?? 2,
                'extra_bed_available' => $room['extra_bed_available'] ?? 0,
                'extra_bed_price' => $room['extra_bed_price'] ?? 0,
                'first_room_id' => $room['id'], // Store first available room ID
                'rooms' => []
            ];
        }
        $rooms_by_type[$type]['count']++;
        $rooms_by_type[$type]['rooms'][] = $room;
    }
}

// Get hotel images
$images = json_decode($hotel['images'] ?? '[]', true);
$primary_image = !empty($images) ? (is_string($images[0]) ? $images[0] : $images[0]['path']) : '';

// Default dates (check-in tomorrow, check-out day after)
$default_checkin = date('Y-m-d', strtotime('+1 day'));
$default_checkout = date('Y-m-d', strtotime('+2 days'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Reserva - <?= htmlspecialchars($hotel['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold">🪙 AiNi Travel - Confirmar Reserva</h1>
                <a href="hotel_details.php?id=<?= $hotel_id ?>" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-full transition">
                    ← Volver
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-6xl mx-auto px-4 py-8">
        
        <div class="grid md:grid-cols-3 gap-8">
            
            <!-- Booking Form -->
            <div class="md:col-span-2">
                <form id="bookingForm" class="bg-white rounded-xl shadow-lg p-8 space-y-6">
                    <h2 class="text-3xl font-bold text-gray-900 mb-6">Confirmar tu Reserva</h2>
                    
                    <!-- Hotel Info -->
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-6 border-2 border-purple-200">
                        <div class="flex gap-4">
                            <?php if ($primary_image): ?>
                                <img src="<?= htmlspecialchars($primary_image) ?>" alt="<?= htmlspecialchars($hotel['name']) ?>" 
                                     class="w-32 h-32 object-cover rounded-lg">
                            <?php else: ?>
                                <div class="w-32 h-32 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center text-white text-4xl">
                                    🏨
                                </div>
                            <?php endif; ?>
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($hotel['name']) ?></h3>
                                <p class="text-gray-700 mb-2">
                                    <span class="text-red-500">📍</span> <?= htmlspecialchars($hotel['location']) ?>
                                </p>
                                <?php if ($room_info): ?>
                                    <div class="inline-block px-3 py-1 bg-purple-500 text-white rounded-full text-sm font-semibold">
                                        🛏️ <?= htmlspecialchars($room_info['room_type']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Guest Information -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4">👤 Información del Huésped</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre Completo *</label>
                                <input type="text" name="guest_name" required
                                       value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                <input type="email" name="guest_email" required
                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono *</label>
                                <input type="tel" name="guest_phone" required
                                       placeholder="+51 999 999 999"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Documento de Identidad</label>
                                <input type="text" name="id_number"
                                       placeholder="DNI / Pasaporte"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>

                    <!-- Stay Dates -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4">📅 Fechas de Estadía</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Check-in *</label>
                                <input type="date" name="check_in" required
                                       value="<?= $default_checkin ?>"
                                       min="<?= date('Y-m-d') ?>"
                                       onchange="updateCheckout(); calculateTotal();"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Check-out *</label>
                                <input type="date" name="check_out" required
                                       value="<?= $default_checkout ?>"
                                       min="<?= $default_checkin ?>"
                                       onchange="calculateTotal();"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 mt-2">
                            <span id="nightsCount">1</span> noche(s)
                        </p>
                    </div>

                    <!-- Room Selection (if not pre-selected) -->
                    <?php if (!$room_info): ?>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 mb-4">🛏️ Seleccionar Habitaciones</h3>
                            
                            <!-- Selected Rooms Summary -->
                            <div id="selectedRoomsContainer" class="mb-4 space-y-2" style="display: none;">
                                <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-bold text-green-900">✓ Habitaciones Seleccionadas</h4>
                                        <button type="button" onclick="clearAllRooms()" class="text-xs text-red-600 hover:text-red-800">Limpiar todo</button>
                                    </div>
                                    <div id="selectedRoomsList" class="space-y-2"></div>
                                </div>
                            </div>
                            
                            <!-- Available Rooms -->
                            <div id="roomSelection" class="space-y-3">
                                <?php if (empty($rooms_by_type)): ?>
                                    <div class="bg-yellow-50 border-2 border-yellow-300 rounded-lg p-6 text-center">
                                        <p class="text-yellow-800 font-semibold">⚠️ No hay habitaciones disponibles en este momento</p>
                                        <p class="text-sm text-yellow-600 mt-2">Por favor contacta al hotel directamente</p>
                                    </div>
                                <?php else: ?>
                                    <p class="text-sm text-gray-600 mb-3">Agrega los tipos de habitaciones que necesitas:</p>
                                    <?php foreach ($rooms_by_type as $roomType): ?>
                                        <div class="room-option border-2 border-gray-200 rounded-lg p-5 hover:border-purple-300 transition-all"
                                             data-room-id="<?= $roomType['first_room_id'] ?>"
                                             data-room-type="<?= htmlspecialchars($roomType['room_type']) ?>">
                                            <div class="flex justify-between items-start gap-4">
                                                <div class="flex-1">
                                                    <div class="flex items-start justify-between mb-2">
                                                        <h4 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($roomType['room_type']) ?></h4>
                                                        <span class="ml-3 inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                                            ✓ <?= $roomType['count'] ?> disponible<?= $roomType['count'] > 1 ? 's' : '' ?>
                                                        </span>
                                                    </div>
                                                    <?php if (!empty($roomType['description'])): ?>
                                                        <p class="text-sm text-gray-600 mt-1 mb-2"><?= htmlspecialchars($roomType['description']) ?></p>
                                                    <?php endif; ?>
                                                    <div class="flex flex-wrap gap-3 mt-2 text-sm text-gray-700">
                                                        <?php if (!empty($roomType['max_occupancy'])): ?>
                                                            <span class="flex items-center gap-1">
                                                                <span>👥</span>
                                                                <span>Máx: <?= $roomType['max_occupancy'] ?> persona<?= $roomType['max_occupancy'] > 1 ? 's' : '' ?></span>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($roomType['extra_bed_available'])): ?>
                                                            <span class="flex items-center gap-1">
                                                                <span>🛏️</span>
                                                                <span>Cama extra (+<?= $roomType['currency'] === 'PEN' ? 'S/' : '$' ?><?= number_format($roomType['extra_bed_price'], 2) ?>)</span>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!empty($roomType['amenities'])): ?>
                                                        <p class="text-xs text-gray-500 mt-2">📋 <?= htmlspecialchars($roomType['amenities']) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-center">
                                                    <p class="text-3xl font-bold text-purple-600 mb-1"><?= $roomType['currency'] === 'PEN' ? 'S/' : '$' ?><?= number_format($roomType['price'], 2) ?></p>
                                                    <p class="text-xs text-gray-500 mb-3">por noche</p>
                                                    <div class="flex items-center gap-2">
                                                        <select class="px-2 py-1 border border-gray-300 rounded text-sm" id="qty_<?= $roomType['first_room_id'] ?>">
                                                            <option value="1">1</option>
                                                            <option value="2">2</option>
                                                            <option value="3">3</option>
                                                            <option value="4">4</option>
                                                            <option value="5">5</option>
                                                        </select>
                                                        <button type="button" 
                                                                onclick="addRoomToSelection(<?= htmlspecialchars(json_encode($roomType)) ?>)"
                                                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-semibold transition">
                                                            + Agregar
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <!-- Hidden field to store selected rooms as JSON -->
                            <input type="hidden" name="selected_rooms_json" id="selected_rooms_json" value="">
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="room_id" value="<?= $room_info['id'] ?>">
                        <input type="hidden" name="room_type" value="<?= htmlspecialchars($room_info['room_type']) ?>">
                        <input type="hidden" name="num_rooms" value="1">
                    <?php endif; ?>

                    <!-- Special Requests -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4">💬 Solicitudes Especiales</h3>
                        <textarea name="special_requests" rows="4"
                                  placeholder="Ej: Cama extra, habitación en piso alto, llegada tarde, etc."
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Opcional - El hotel hará lo posible por cumplir tus solicitudes</p>
                    </div>

                    <!-- Hidden Fields -->
                    <input type="hidden" name="hotel_id" value="<?= $hotel_id ?>">
                    <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">

                    <!-- Submit Button -->
                    <button type="submit" 
                            class="w-full gradient-bg text-white py-4 rounded-lg font-bold text-lg hover:opacity-90 transition flex items-center justify-center gap-2">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                        </svg>
                        Confirmar Reserva
                    </button>

                    <p class="text-center text-sm text-gray-600">
                        🔒 Tu información está segura • 💳 Pagas en el hotel
                    </p>
                </form>

                <!-- Success Message -->
                <div id="successMessage" class="hidden bg-white rounded-xl shadow-lg p-8 text-center">
                    <div class="text-6xl mb-4">✅</div>
                    <h2 class="text-3xl font-bold text-green-600 mb-4">¡Reserva Confirmada!</h2>
                    <p class="text-lg text-gray-700 mb-6">
                        Tu reserva ha sido enviada a <strong><?= htmlspecialchars($hotel['name']) ?></strong>
                    </p>
                    <div class="bg-green-50 border-2 border-green-200 rounded-lg p-6 mb-6">
                        <p class="text-sm text-gray-700 mb-2">Referencia de Reserva:</p>
                        <p class="text-2xl font-bold text-green-700" id="bookingReference">-</p>
                    </div>
                    <p class="text-gray-600 mb-6">
                        Recibirás un email de confirmación en breve. El hotel te contactará para confirmar tu reserva.
                    </p>
                    <div class="flex gap-3 justify-center">
                        <a href="public_booking.php" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-semibold transition">
                            Ver Más Hoteles
                        </a>
                        <a href="my_bookings.php" class="px-6 py-3 gradient-bg text-white rounded-lg font-semibold hover:opacity-90 transition">
                            Ver Mis Reservas
                        </a>
                    </div>
                </div>
            </div>

            <!-- Summary Sidebar -->
            <div>
                <div class="bg-white rounded-xl shadow-lg p-6 sticky top-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">📋 Resumen de Reserva</h3>
                    
                    <div class="space-y-4">
                        <div id="summaryRoomInfo" class="pb-4 border-b border-gray-200" <?= !$room_info ? 'style="display:none;"' : '' ?>>
                            <p class="text-sm text-gray-600 mb-1">Habitación</p>
                            <p id="summaryRoomType" class="text-lg font-semibold text-gray-900"><?= $room_info ? htmlspecialchars($room_info['room_type']) : '' ?></p>
                        </div>
                        <div id="summaryPriceInfo" class="pb-4 border-b border-gray-200" <?= !$room_info ? 'style="display:none;"' : '' ?>>
                            <p class="text-sm text-gray-600 mb-1">Precio por noche</p>
                            <p id="summaryPricePerNight" class="text-2xl font-bold text-purple-600">
                                <?= $room_info ? (($room_info['currency'] ?? 'USD') === 'PEN' ? 'S/' : '$') . number_format($room_info['price'], 2) : '' ?>
                            </p>
                        </div>
                        
                        <div class="pb-4 border-b border-gray-200">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-700">Noches</span>
                                <span class="font-semibold" id="summaryNights">1</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-700">Subtotal</span>
                                <span class="font-semibold" id="summarySubtotal">$0.00</span>
                            </div>
                        </div>

                        <div id="summaryRoomCount"></div>

                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-lg font-bold text-gray-900">Total</span>
                                <span class="text-2xl font-bold text-purple-600" id="summaryTotal">$0.00</span>
                            </div>
                            <p class="text-xs text-gray-600">Pago en el hotel</p>
                        </div>

                        <div class="gradient-bg text-white rounded-lg p-4 text-center">
                            <div class="text-sm mb-1">🪙 Ganarás</div>
                            <div class="text-3xl font-bold" id="summaryCoins">0 coins</div>
                            <div class="text-xs opacity-90">por esta reserva</div>
                        </div>

                        <div class="text-center">
                            <div class="flex items-center justify-center gap-2 text-green-600 mb-3">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                                </svg>
                                <span class="font-semibold">Cancelación Gratuita</span>
                            </div>
                            <p class="text-xs text-gray-600">Cancela hasta 24h antes del check-in</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        let roomInfo = <?= $room_info ? json_encode($room_info) : 'null' ?>;
        const hotelId = <?= $hotel_id ?>;
        let selectedRooms = []; // Array to store multiple room type selections

        // Function to add a room type to selection
        function addRoomToSelection(roomType) {
            console.log('addRoomToSelection called with:', roomType);
            const qtyElement = document.getElementById('qty_' + roomType.first_room_id);
            const quantity = parseInt(qtyElement.value) || 1;
            console.log('Quantity:', quantity);
            
            // Check if this room type is already selected
            const existingIndex = selectedRooms.findIndex(r => r.room_type === roomType.room_type);
            
            if (existingIndex >= 0) {
                // Update quantity for existing room type
                selectedRooms[existingIndex].quantity += quantity;
            } else {
                // Add new room type
                selectedRooms.push({
                    id: roomType.first_room_id,
                    room_type: roomType.room_type,
                    price: roomType.price,
                    currency: roomType.currency,
                    quantity: quantity
                });
            }
            
            // Update roomInfo for summary (use first selected room)
            if (selectedRooms.length > 0) {
                roomInfo = selectedRooms[0];
            }
            
            console.log('selectedRooms after addition:', selectedRooms);
            updateSelectedRoomsDisplay();
            calculateTotal();
        }

        // Function to remove a room type from selection
        function removeRoomFromSelection(roomType) {
            selectedRooms = selectedRooms.filter(r => r.room_type !== roomType);
            
            // Update roomInfo
            if (selectedRooms.length > 0) {
                roomInfo = selectedRooms[0];
            } else {
                roomInfo = null;
            }
            
            updateSelectedRoomsDisplay();
            calculateTotal();
        }

        // Function to clear all selected rooms
        function clearAllRooms() {
            selectedRooms = [];
            roomInfo = null;
            updateSelectedRoomsDisplay();
            calculateTotal();
        }

        // Function to update the selected rooms display
        function updateSelectedRoomsDisplay() {
            const container = document.getElementById('selectedRoomsContainer');
            const list = document.getElementById('selectedRoomsList');
            
            if (selectedRooms.length === 0) {
                container.style.display = 'none';
                document.getElementById('summaryRoomInfo').style.display = 'none';
                document.getElementById('summaryPriceInfo').style.display = 'none';
                return;
            }
            
            container.style.display = 'block';
            
            // Build list HTML
            const totalRooms = selectedRooms.reduce((sum, r) => sum + r.quantity, 0);
            let html = '';
            
            selectedRooms.forEach(room => {
                const currencySymbol = room.currency === 'PEN' ? 'S/' : '$';
                html += `
                    <div class="flex justify-between items-center bg-white p-3 rounded border border-green-200">
                        <div class="flex-1">
                            <span class="font-semibold text-gray-900">${room.room_type}</span>
                            <span class="text-sm text-gray-600"> × ${room.quantity}</span>
                            <span class="text-sm text-gray-500 ml-2">(${currencySymbol}${parseFloat(room.price).toFixed(2)} c/u)</span>
                        </div>
                        <button type="button" onclick="removeRoomFromSelection('${room.room_type}')" 
                                class="text-red-600 hover:text-red-800 text-sm">🗑️</button>
                    </div>
                `;
            });
            
            list.innerHTML = html;
            
            // Update summary sidebar
            if (selectedRooms.length > 0) {
                const firstRoom = selectedRooms[0];
                const currencySymbol = firstRoom.currency === 'PEN' ? 'S/' : '$';
                document.getElementById('summaryRoomInfo').style.display = 'block';
                document.getElementById('summaryPriceInfo').style.display = 'none'; // Hide single price, show breakdown
                document.getElementById('summaryRoomType').textContent = totalRooms + ' habitación' + (totalRooms > 1 ? 'es' : '');
            }
            
            // Update hidden field with JSON
            document.getElementById('selected_rooms_json').value = JSON.stringify(selectedRooms);
        }

        function updateCheckout() {
            const checkin = document.querySelector('input[name="check_in"]').value;
            const checkoutInput = document.querySelector('input[name="check_out"]');
            
            if (checkin) {
                const nextDay = new Date(checkin);
                nextDay.setDate(nextDay.getDate() + 1);
                checkoutInput.min = nextDay.toISOString().split('T')[0];
                
                if (checkoutInput.value <= checkin) {
                    checkoutInput.value = nextDay.toISOString().split('T')[0];
                }
            }
        }

        // Initialize
        updateCheckout();
        calculateTotal();

        // Calculate total function
        function calculateTotal() {
            const checkin = new Date(document.querySelector('input[name="check_in"]').value);
            const checkout = new Date(document.querySelector('input[name="check_out"]').value);
            
            console.log('calculateTotal called - checkin:', checkin, 'checkout:', checkout);
            
            if (!checkin || !checkout || isNaN(checkin.getTime()) || isNaN(checkout.getTime()) || checkout <= checkin) {
                console.log('Invalid dates, skipping calculation');
                return;
            }
            
            const nights = Math.ceil((checkout - checkin) / (1000 * 60 * 60 * 24));
            document.getElementById('nightsCount').textContent = nights;
            document.getElementById('summaryNights').textContent = nights;
            
            // Calculate for multiple room types
            if (selectedRooms.length > 0) {
                let grandTotal = 0;
                let currency = selectedRooms[0].currency === 'PEN' ? 'S/' : '$';
                let breakdownHTML = '';
                
                selectedRooms.forEach(room => {
                    const roomSubtotal = parseFloat(room.price) * nights * room.quantity;
                    grandTotal += roomSubtotal;
                    
                    breakdownHTML += `
                        <div class="flex justify-between text-sm text-gray-700 mb-1">
                            <span>${room.room_type} × ${room.quantity}</span>
                            <span>${currency}${roomSubtotal.toFixed(2)}</span>
                        </div>
                    `;
                });
                
                const coins = Math.round(grandTotal * 0.2);
                
                console.log('Updating summary - Total:', currency + grandTotal.toFixed(2), 'Coins:', coins);
                
                // Update summary
                document.getElementById('summarySubtotal').textContent = currency + grandTotal.toFixed(2);
                document.getElementById('summaryTotal').textContent = currency + grandTotal.toFixed(2);
                document.getElementById('summaryCoins').textContent = coins + ' coins';
                
                // Show room breakdown
                const roomCountElement = document.getElementById('summaryRoomCount');
                if (roomCountElement) {
                    roomCountElement.innerHTML = `<div class="pb-4 border-b border-gray-200">${breakdownHTML}</div>`;
                }
            } else if (roomInfo) {
                // Fallback for pre-selected single room
                const pricePerNight = parseFloat(roomInfo.price);
                const subtotal = nights * pricePerNight;
                const coins = Math.round(subtotal * 0.2);
                const currency = roomInfo.currency === 'PEN' ? 'S/' : '$';
                
                document.getElementById('summarySubtotal').textContent = currency + subtotal.toFixed(2);
                document.getElementById('summaryTotal').textContent = currency + subtotal.toFixed(2);
                document.getElementById('summaryCoins').textContent = coins + ' coins';
                
                const roomCountElement = document.getElementById('summaryRoomCount');
                if (roomCountElement) {
                    roomCountElement.innerHTML = '';
                }
            }
        }

        // Initialize
        updateCheckout();
        if (roomInfo) {
            calculateTotal();
        }

        // Handle form submission
        document.getElementById('bookingForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validate room selection if no room was pre-selected
            if (!roomInfo && selectedRooms.length === 0) {
                alert('⚠️ Por favor selecciona al menos una habitación antes de continuar');
                return;
            }
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            
            // Disable submit button
            submitButton.disabled = true;
            submitButton.innerHTML = '<svg class="animate-spin h-6 w-6 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

            try {
                const response = await fetch('booking_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success message
                    document.getElementById('bookingForm').classList.add('hidden');
                    document.getElementById('successMessage').classList.remove('hidden');
                    document.getElementById('bookingReference').textContent = result.booking_reference;
                    
                    // Scroll to top
                    window.scrollTo({top: 0, behavior: 'smooth'});
                } else {
                    alert('❌ Error: ' + (result.message || 'No se pudo completar la reserva'));
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg> Confirmar Reserva';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Error al procesar la reserva. Por favor intenta nuevamente.');
                submitButton.disabled = false;
                submitButton.innerHTML = '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg> Confirmar Reserva';
            }
        });
    </script>

</body>
</html>
