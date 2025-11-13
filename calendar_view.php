<?php
session_start();
require_once 'includes/classes.php';
require_once 'includes/hotel_classes.php';
require_once 'includes/currency_manager.php';

// Set timezone to avoid date conflicts
date_default_timezone_set('America/New_York'); // Adjust this to your preferred timezone

// Get database connection
$database = new Database();
$connection = $database->getConnection();

// Create booking_extensions table if it doesn't exist
try {
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS booking_extensions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        original_checkout DATE NOT NULL,
        new_checkout DATE NOT NULL,
        additional_nights INT NOT NULL,
        extension_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        payment_method VARCHAR(50),
        payment_status ENUM('paid', 'pending', 'overdue') DEFAULT 'pending',
        payment_due_date DATE,
        payment_notes TEXT,
        discount_type VARCHAR(20),
        discount_amount DECIMAL(10,2) DEFAULT 0.00,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_by INT,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
    )";
    $connection->query($createTableSQL);
    
    // Create booking_notes table if it doesn't exist
    $createNotesTableSQL = "
    CREATE TABLE IF NOT EXISTS booking_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        note_text TEXT NOT NULL,
        created_by INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )";
    $connection->query($createNotesTableSQL);
    
    // Create booking_guests table for multiple guests per booking
    $createGuestsTableSQL = "
    CREATE TABLE IF NOT EXISTS booking_guests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        guest_name VARCHAR(255) NOT NULL,
        guest_email VARCHAR(255),
        guest_phone VARCHAR(50),
        passport_number VARCHAR(50),
        id_number VARCHAR(50),
        is_primary BOOLEAN DEFAULT FALSE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
    )";
    $connection->query($createGuestsTableSQL);
    
    // Add multi-room booking columns if they don't exist
    try {
        $connection->query("ALTER TABLE bookings ADD COLUMN is_multi_room BOOLEAN DEFAULT FALSE");
    } catch (Exception $e) {
        // Column might already exist
    }
    try {
        $connection->query("ALTER TABLE bookings ADD COLUMN primary_booking_id INT NULL");
    } catch (Exception $e) {
        // Column might already exist
    }
    
} catch (Exception $e) {
    // Tables might already exist, continue silently
}

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_role'])) {
    header('Location: index.php');
    exit;
}

if ($_SESSION['user_role'] !== 'manager' && $_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Handle AJAX room status updates by date
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status_by_date') {
    header('Content-Type: application/json');
    
    try {
        $roomId = (int)$_POST['room_id'];
        $status = $_POST['status'];
        $notes = $_POST['notes'] ?? '';
        $dateMode = $_POST['date_mode'];
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        
        // Validate status
        $validStatuses = ['clean', 'dirty', 'maintenance', 'out_of_order'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception('Invalid status');
        }
        
        if ($dateMode === 'permanent') {
            // Update room's permanent status
            $stmt = $connection->prepare("UPDATE rooms SET status = ? WHERE id = ?");
            $stmt->execute([$status, $roomId]);
            
            // Also clear any future date-specific statuses
            $stmt = $connection->prepare("DELETE FROM room_status_by_date WHERE room_id = ? AND status_date >= CURDATE()");
            $stmt->execute([$roomId]);
            
        } else {
            // Insert/update status for specific dates
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            $interval = new DateInterval('P1D');
            $period = new DatePeriod($start, $interval, $end->modify('+1 day'));
            
            $stmt = $connection->prepare("
                INSERT INTO room_status_by_date (room_id, status_date, status, notes, created_by) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status), notes = VALUES(notes), updated_at = CURRENT_TIMESTAMP
            ");
            
            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                $stmt->execute([$roomId, $dateStr, $status, $notes, $_SESSION['user']['id'] ?? null]);
            }
        }
        
        // Log the change
        try {
            $logNotes = $notes . " | Date Mode: $dateMode | Dates: $startDate to $endDate";
            $stmt = $connection->prepare("INSERT INTO room_status_log (room_id, status, notes, changed_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$roomId, $status, $logNotes, $_SESSION['user']['id'] ?? null]);
        } catch (Exception $logError) {
            error_log("Failed to log room status change: " . $logError->getMessage());
        }
        
        echo json_encode(['success' => true, 'message' => 'Room status updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle AJAX room status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    
    try {
        $roomId = (int)$_POST['room_id'];
        $status = $_POST['status'];
        $notes = $_POST['notes'] ?? '';
        
        // Validate status
        $validStatuses = ['clean', 'dirty', 'maintenance', 'out_of_order'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception('Invalid status');
        }
        
        // Update room status
        $stmt = $connection->prepare("UPDATE rooms SET status = ? WHERE id = ?");
        $success = $stmt->execute([$status, $roomId]);
        
        if ($success) {
            // Log the status change (optional - table may not exist)
            try {
                $stmt = $connection->prepare("INSERT INTO room_status_log (room_id, status, notes, changed_by) VALUES (?, ?, ?, ?)");
                $stmt->execute([$roomId, $status, $notes, $_SESSION['user']['id'] ?? null]);
            } catch (Exception $logError) {
                // Continue even if logging fails
                error_log("Failed to log room status change: " . $logError->getMessage());
            }
            
            echo json_encode(['success' => true, 'message' => 'Room status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update room status']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle AJAX paid amount updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_paid_amount') {
    header('Content-Type: application/json');
    
    try {
        $bookingId = (int)$_POST['booking_id'];
        $paidAmount = (float)$_POST['paid_amount'];
        $notes = $_POST['notes'] ?? '';
        
        // Debug: Log session info
        error_log("UPDATE_PAID_AMOUNT: Session user ID = " . ($_SESSION['user']['id'] ?? 'NULL'));
        
        // Validate paid amount
        if ($paidAmount < 0) {
            throw new Exception('El monto pagado no puede ser negativo');
        }
        
        // Get booking info for validation
        $stmt = $connection->prepare("SELECT total_price, guest_name FROM bookings WHERE id = ?");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            throw new Exception('Reserva no encontrada');
        }
        
        // Update paid amount
        $stmt = $connection->prepare("UPDATE bookings SET paid_amount = ? WHERE id = ?");
        $success = $stmt->execute([$paidAmount, $bookingId]);
        
        if ($success) {
            // Log the payment change
            $logMessage = "Monto pagado actualizado: ${paidAmount} USD";
            if (!empty($notes)) {
                $logMessage .= " - Notas: " . $notes;
            }
            
            // Get user ID safely, fallback to 1 if session user is not available
            $userId = $_SESSION['user']['id'] ?? 1;
            
            $stmt = $connection->prepare("
                INSERT INTO booking_notes (booking_id, note_text, created_by, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$bookingId, $logMessage, $userId]);
            
            // Update payment status based on amount vs total
            $paymentStatus = 'pending';
            if ($paidAmount >= $booking['total_price']) {
                $paymentStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partial';
            }
            
            $stmt = $connection->prepare("UPDATE bookings SET payment_status = ? WHERE id = ?");
            $stmt->execute([$paymentStatus, $bookingId]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Monto pagado actualizado exitosamente',
                'new_amount' => $paidAmount,
                'payment_status' => $paymentStatus
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el monto pagado']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle Quick Booking creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_booking'])) {
    // Check terms acceptance first
    $termsAccepted = isset($_POST['terms_accepted']) && $_POST['terms_accepted'] === 'on';
    
    if (!$termsAccepted) {
        $message = 'You must accept the Terms & Conditions and Guest Responsibility Agreement to create a booking.';
        $messageType = 'error';
    } else {
        // Handle multiple rooms - for now, use first room as primary
        $roomIds = $_POST['room_ids'] ?? [$_POST['room_id']];
        $roomGuests = $_POST['room_guests'] ?? [$_POST['guest_count']];
        $roomId = !empty($roomIds[0]) ? $roomIds[0] : $_POST['room_id'];
        
        $checkIn = $_POST['check_in_date'];
        $checkOut = $_POST['check_out_date'];
        $guestName = $_POST['guest_names'][0] ?? $_POST['guest_name'];
        $guestEmail = $_POST['guest_emails'][0] ?? $_POST['guest_email'];
        $guestPhone = $_POST['guest_phones'][0] ?? $_POST['guest_phone'] ?? '';
        $passportNumber = $_POST['guest_passports'][0] ?? $_POST['passport_number'] ?? '';
        $idNumber = $_POST['guest_ids'][0] ?? $_POST['id_number'] ?? '';
        
        $roomManager = new Room();
        
        // Build multiple rooms information for special requests
        $multipleRoomsInfo = '';
        if (is_array($roomIds) && count($roomIds) > 1) {
            $multipleRoomsInfo = "Multiple Rooms Booking:\n";
            for ($i = 0; $i < count($roomIds); $i++) {
                if (!empty($roomIds[$i])) {
                    $roomData = $roomManager->getRoomById($roomIds[$i]);
                    $guests = $roomGuests[$i] ?? 2;
                    $multipleRoomsInfo .= "Room " . ($i + 1) . ": " . $roomData['room_number'] . " (" . $roomData['room_type'] . ") - {$guests} guests\n";
                }
            }
            $multipleRoomsInfo .= "\n";
        }
    
        // Create a temporary guest user or use existing
        $userManager = new User();
        
        // Check if guest exists
        $stmt = $connection->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$guestEmail]);
        $existingUser = $stmt->fetch();
        
        if ($existingUser) {
            $guestId = $existingUser['id'];
        } else {
            // Create new guest user with terms acceptance
            $names = explode(' ', $guestName, 2);
            $firstName = $names[0];
            $lastName = isset($names[1]) ? $names[1] : '';
            
            $result = $userManager->register($firstName, $lastName, $guestEmail, 'temp123', true, '1.0');
            if ($result['success']) {
                $guestId = $result['user_id'];
                
                // Update phone number
                if (!empty($guestPhone)) {
                    $stmt = $connection->prepare("UPDATE users SET phone = ? WHERE id = ?");
                    $stmt->execute([$guestPhone, $guestId]);
                }
            } else {
                $message = 'Failed to create guest user';
                $messageType = 'error';
            }
        }
        
        if (isset($guestId)) {
            // Calculate total price with custom pricing and discounts
            $room = $roomManager->getRoomById($roomId);
            $days = (strtotime($checkOut) - strtotime($checkIn)) / (60 * 60 * 24);
            
            // Handle currency selection and custom pricing
            $selectedCurrency = $_POST['currency_type'] ?? 'USD';
            $customPriceUSD = !empty($_POST['override_price_usd']) ? (float)$_POST['override_price_usd'] : 0;
            $customPricePEN = !empty($_POST['override_price_pen']) ? (float)$_POST['override_price_pen'] : 0;
            
            // Determine if custom pricing is being used
            $useCustomPrice = ($customPriceUSD > 0 || $customPricePEN > 0);
            
            // Determine price per night in USD (for consistent database storage)
            if ($useCustomPrice) {
                if ($selectedCurrency === 'USD' && $customPriceUSD > 0) {
                    $pricePerNight = $customPriceUSD / $days;
                } elseif ($selectedCurrency === 'PEN' && $customPricePEN > 0) {
                    $pricePerNight = ($customPricePEN / 3.75) / $days; // Convert PEN to USD
                } else {
                    // Fallback
                    $pricePerNight = $customPriceUSD > 0 ? ($customPriceUSD / $days) : (($customPricePEN > 0 ? ($customPricePEN / 3.75) / $days : $room['price']));
                }
            } else {
                $pricePerNight = $room['price']; // Default room price in USD
            }
            
            $subtotal = $pricePerNight * $days;
            
            // Apply discount if provided
            $discountAmount = 0;
            $discountType = $_POST['discount_type'] ?? '';
            $discountValue = (float)($_POST['discount_value'] ?? 0);
            $discountReason = $_POST['discount_reason'] ?? '';
            
            if ($discountType && $discountValue > 0) {
                if ($discountType === 'percentage') {
                    $discountAmount = $subtotal * ($discountValue / 100);
                } elseif ($discountType === 'fixed_usd') {
                    $discountAmount = min($discountValue, $subtotal);
                } elseif ($discountType === 'fixed_pen') {
                    $discountAmount = min($discountValue / 3.75, $subtotal); // Convert PEN to USD
                }
            }
            
            // Apply customer discount
            $customerDiscountType = $_POST['customer_discount_type'] ?? '';
            $customerDiscountValue = (float)($_POST['customer_discount_value'] ?? 0);
            $customerDiscountReason = $_POST['customer_discount_reason'] ?? '';
            
            if ($customerDiscountType && $customerDiscountValue > 0) {
                if ($customerDiscountType === 'percentage') {
                    $discountAmount += $subtotal * ($customerDiscountValue / 100);
                } elseif ($customerDiscountType === 'fixed') {
                    $discountAmount += min($customerDiscountValue, $subtotal);
                }
            }
            
            $totalPrice = $subtotal - $discountAmount;
            
            // Prepare special requests with pricing info and multiple rooms info
            $specialRequests = $multipleRoomsInfo;
            if ($useCustomPrice) {
                $displayPrice = $selectedCurrency === 'USD' ? 
                    "$" . number_format($pricePerNight, 2) : 
                    "S/ " . number_format($pricePerNight * 3.75, 2);
                $specialRequests .= "Custom pricing ({$selectedCurrency}): {$displayPrice}/night. ";
            }
            if ($discountAmount > 0) {
                $specialRequests .= "Discount applied: ";
                if ($discountType === 'percentage') {
                    $specialRequests .= $discountValue . "% ";
                } elseif ($discountType === 'fixed_usd') {
                    $specialRequests .= "$" . number_format($discountAmount, 2) . " ";
                } elseif ($discountType === 'fixed_pen') {
                    $specialRequests .= "S/ " . number_format($discountAmount * 3.75, 2) . " ";
                }
                if ($discountReason) {
                    $specialRequests .= "(" . $discountReason . ") ";
                }
                if ($customerDiscountReason) {
                    $specialRequests .= "Customer: " . $customerDiscountReason . " ";
                }
                $specialRequests .= "Subtotal: $" . number_format($subtotal, 2) . " / S/ " . number_format($subtotal * 3.75, 2) . 
                                  ", Total: $" . number_format($totalPrice, 2) . " / S/ " . number_format($totalPrice * 3.75, 2) . ". ";
            }
            
            // Handle payment information
            $paymentStatus = $_POST['payment_status'] ?? 'pending';
            $paymentMethod = $_POST['payment_method'] ?? null;
            $paidAmount = !empty($_POST['paid_amount']) ? (float)$_POST['paid_amount'] : 0.00;
            
            // Generate booking reference
            $bookingReference = 'HTL-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Create bookings for each room (multiple rooms support)
            $allBookingIds = [];
            $primaryBookingId = null;
            $totalSuccess = true;
            
            // Calculate price per room (divide total among rooms)
            $pricePerRoom = count($roomIds) > 1 ? $totalPrice / count($roomIds) : $totalPrice;
            
            $stmt = $connection->prepare("INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, total_price, selected_currency, special_requests, discount_amount, payment_status, payment_method, paid_amount, booking_reference, guest_name, guest_email, guest_phone, passport_number, id_number, status, is_multi_room, primary_booking_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?, ?)");
            
            foreach ($roomIds as $index => $currentRoomId) {
                if (empty($currentRoomId)) continue;
                
                $isPrimaryRoom = ($index === 0);
                $isMultiRoom = count($roomIds) > 1;
                
                $success = $stmt->execute([
                    $guestId, 
                    $currentRoomId, 
                    $checkIn, 
                    $checkOut, 
                    $pricePerRoom, 
                    $selectedCurrency,
                    $specialRequests, 
                    $discountAmount / count($roomIds), // Divide discount among rooms
                    $paymentStatus,
                    $paymentMethod,
                    $paidAmount / count($roomIds), // Divide paid amount among rooms
                    $bookingReference . ($isMultiRoom ? '-R' . ($index + 1) : ''),
                    $guestName,
                    $guestEmail,
                    $guestPhone,
                    $passportNumber,
                    $idNumber,
                    $isMultiRoom ? 1 : 0,
                    $primaryBookingId
                ]);
                
                if ($success) {
                    $currentBookingId = $connection->lastInsertId();
                    $allBookingIds[] = $currentBookingId;
                    
                    if ($isPrimaryRoom) {
                        $primaryBookingId = $currentBookingId;
                    }
                } else {
                    $totalSuccess = false;
                    break;
                }
            }
            
            // Update non-primary bookings with primary_booking_id
            if ($totalSuccess && $primaryBookingId && count($allBookingIds) > 1) {
                $updateStmt = $connection->prepare("UPDATE bookings SET primary_booking_id = ? WHERE id IN (" . implode(',', array_slice($allBookingIds, 1)) . ")");
                $updateStmt->execute([$primaryBookingId]);
            }
            
            if ($totalSuccess) {
                $bookingId = $primaryBookingId;
                
                // Handle multiple guests
                $guestNames = $_POST['guest_names'] ?? [$guestName];
                $guestEmails = $_POST['guest_emails'] ?? [$guestEmail];
                $guestPhones = $_POST['guest_phones'] ?? [$guestPhone];
                $guestPassports = $_POST['guest_passports'] ?? [$passportNumber];
                $guestIds = $_POST['guest_ids'] ?? [$idNumber];
                
                // Insert all guests into booking_guests table
                $guestStmt = $connection->prepare("INSERT INTO booking_guests (booking_id, guest_name, guest_email, guest_phone, passport_number, id_number, is_primary) VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                for ($i = 0; $i < count($guestNames); $i++) {
                    if (!empty($guestNames[$i])) {
                        $isPrimary = ($i === 0);
                        $guestStmt->execute([
                            $bookingId,
                            $guestNames[$i],
                            $guestEmails[$i] ?? '',
                            $guestPhones[$i] ?? '',
                            $guestPassports[$i] ?? '',
                            $guestIds[$i] ?? '',
                            $isPrimary
                        ]);
                    }
                }
                
                // Create income record
                if (file_exists('includes/accounting_classes.php')) {
                    require_once 'includes/accounting_classes.php';
                    $incomeManager = new IncomeManager();
                    
                    $roomStmt = $connection->prepare("SELECT room_number, room_type FROM rooms WHERE id = ?");
                    $roomStmt->execute([$roomId]);
                    $roomInfo = $roomStmt->fetch();
                    
                    $incomeAmount = $totalPrice;
                    $incomeCurrency = 'USD';
                    
                    if ($selectedCurrency === 'PEN') {
                        $incomeAmount = $totalPrice * 3.50;
                        $incomeCurrency = 'PEN';
                    }
                    
                    $incomeResult = $incomeManager->addIncome([
                        'booking_id' => $bookingId,
                        'income_type' => 'room_booking',
                        'description' => 'Room ' . ($roomInfo['room_number'] ?? $roomId) . ' - ' . ($roomInfo['room_type'] ?? 'Booking'),
                        'amount' => $incomeAmount,
                        'currency' => $incomeCurrency,
                        'payment_method' => $paymentMethod,
                        'payment_status' => $paymentStatus,
                        'transaction_date' => $checkIn,
                        'guest_name' => $guestName,
                        'guest_email' => $guestEmail,
                        'guest_phone' => $guestPhone,
                        'created_by' => $_SESSION['user']['id'],
                        'notes' => 'Auto-generated from booking ' . $bookingReference
                    ]);
                }
                
                $message = 'Booking created successfully! Reference: ' . $bookingReference;
                $messageType = 'success';
                $_SESSION['last_booking_id'] = $bookingId;
                
                // Redirect to avoid form resubmission
                header('Location: calendar_view.php?success=1&ref=' . urlencode($bookingReference));
                exit;
            } else {
                $message = 'Failed to create booking';
                $messageType = 'error';
            }
        }
    }
}

$roomManager = new Room();
$bookingManager = new BookingManager();
$hotelInfo = new HotelInfo();
$hotel = $hotelInfo->getHotelInfo();

// Get current month and year, or from URL parameters
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Ensure valid month/year
if ($currentMonth < 1) { $currentMonth = 12; $currentYear--; }
if ($currentMonth > 12) { $currentMonth = 1; $currentYear++; }

// Get all rooms with all fields including discount settings
$database = new Database();
$connection = $database->getConnection();
$stmt = $connection->prepare("SELECT * FROM rooms ORDER BY room_number");
$stmt->execute();
$rooms = $stmt->fetchAll();

// Get existing room photos that are already uploaded
$database = new Database();
$connection = $database->getConnection();
$roomPhotos = [];
try {
    // Get all room photos (prioritize primary, but get any photo if no primary)
    $stmt = $connection->prepare("
        SELECT room_id, photo_path, is_primary 
        FROM room_photos 
        WHERE photo_path IS NOT NULL AND photo_path != ''
        ORDER BY is_primary DESC, id ASC
    ");
    $stmt->execute();
    $allPhotos = $stmt->fetchAll();
    
    $usedRooms = [];
    foreach ($allPhotos as $photo) {
        // Only use the first photo for each room (primary gets priority due to ORDER BY)
        if (!isset($usedRooms[$photo['room_id']])) {
            $roomPhotos[$photo['room_id']] = $photo['photo_path'];
            $usedRooms[$photo['room_id']] = true;
        }
    }
} catch (Exception $e) {
    // Fallback to empty array if there are issues
    $roomPhotos = [];
}

// Get bookings for current month (and a bit before/after for overlap)
$startDate = date('Y-m-01', mktime(0, 0, 0, $currentMonth - 1, 1, $currentYear));
$endDate = date('Y-m-t', mktime(0, 0, 0, $currentMonth + 1, 1, $currentYear));

$db = new Database();
$connection = $db->getConnection();

$stmt = $connection->prepare("
    SELECT b.*, r.room_number, r.room_type, 'available' as status, 
           u.first_name, u.last_name, u.email,
           GROUP_CONCAT(DISTINCT CONCAT(bg.guest_name, '|', COALESCE(bg.guest_email, ''), '|', COALESCE(bg.guest_phone, ''), '|', bg.is_primary) SEPARATOR ';;;') as all_guests,
           (CASE WHEN b.is_multi_room = 1 THEN 
               (SELECT GROUP_CONCAT(CONCAT(r2.room_number, ' (', r2.room_type, ')') SEPARATOR ', ') 
                FROM bookings b2 
                JOIN rooms r2 ON b2.room_id = r2.id 
                WHERE (b2.primary_booking_id = b.id OR (b.primary_booking_id IS NOT NULL AND (b2.primary_booking_id = b.primary_booking_id OR b2.id = b.primary_booking_id)))
                AND b2.status != 'cancelled'
               )
           ELSE r.room_number 
           END) as all_rooms
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN booking_guests bg ON b.id = bg.booking_id
    WHERE b.check_out_date >= ? AND b.check_in_date <= ?
    AND b.status != 'cancelled'
    GROUP BY b.id, r.room_number, r.room_type, 
             u.first_name, u.last_name, u.email
    ORDER BY b.check_in_date
");
$stmt->execute([$startDate, $endDate]);
$bookings = $stmt->fetchAll();

// Load room status by date
$stmt = $connection->prepare("
    SELECT room_id, status_date, status 
    FROM room_status_by_date 
    WHERE status_date BETWEEN ? AND ?
");
$stmt->execute([$startDate, $endDate]);
$roomStatusByDate = [];
while ($row = $stmt->fetch()) {
    $key = $row['room_id'] . '_' . $row['status_date'];
    $roomStatusByDate[$key] = $row['status'];
}

// Process multiple guests data for each booking
foreach ($bookings as &$booking) {
    $booking['guests_list'] = [];
    $booking['primary_guest'] = null;
    
    if (!empty($booking['all_guests'])) {
        $guestsData = explode(';;;', $booking['all_guests']);
        foreach ($guestsData as $guestData) {
            if (!empty($guestData)) {
                $parts = explode('|', $guestData);
                if (count($parts) >= 4) {
                    $guest = [
                        'name' => $parts[0],
                        'email' => $parts[1],
                        'phone' => $parts[2],
                        'is_primary' => (bool)$parts[3]
                    ];
                    $booking['guests_list'][] = $guest;
                    
                    if ($guest['is_primary']) {
                        $booking['primary_guest'] = $guest;
                    }
                }
            }
        }
    }
    
    // Fallback to main booking guest_name if no guests found in booking_guests table
    if (empty($booking['guests_list']) && !empty($booking['guest_name'])) {
        $booking['primary_guest'] = [
            'name' => $booking['guest_name'],
            'email' => $booking['guest_email'] ?? '',
            'phone' => $booking['guest_phone'] ?? '',
            'is_primary' => true
        ];
        $booking['guests_list'][] = $booking['primary_guest'];
    }
    
    // Set display name for backward compatibility
    $booking['display_guest_name'] = $booking['primary_guest']['name'] ?? $booking['guest_name'] ?? 'Guest';
    $booking['all_guest_names'] = implode(', ', array_column($booking['guests_list'], 'name'));
}
unset($booking); // Break reference

// Calculate stats
$totalRooms = count($rooms);
$totalDays = date('t', mktime(0, 0, 0, $currentMonth, 1, $currentYear));
$occupiedDays = 0;
foreach ($bookings as $booking) {
    $checkIn = new DateTime($booking['check_in_date']);
    $checkOut = new DateTime($booking['check_out_date']);
    $monthStart = new DateTime("$currentYear-$currentMonth-01");
    $monthEnd = new DateTime(date('Y-m-t', mktime(0, 0, 0, $currentMonth, 1, $currentYear)));
    
    $start = max($checkIn, $monthStart);
    $end = min($checkOut, $monthEnd);
    $occupiedDays += $start->diff($end)->days;
}
$occupancyRate = ($totalRooms * $totalDays > 0) ? ($occupiedDays / ($totalRooms * $totalDays)) * 100 : 0;

// Get currency manager
$currencyManager = new CurrencyManager();
$exchangeRate = $currencyManager->getExchangeRate();

// Calendar helper functions
function getDaysInMonth($month, $year) {
    return date('t', mktime(0, 0, 0, $month, 1, $year));
}

function getFirstDayOfWeek($month, $year) {
    return date('w', mktime(0, 0, 0, $month, 1, $year));
}

function isDateBooked($roomId, $date, $bookings) {
    foreach ($bookings as $booking) {
        if ($booking['room_id'] == $roomId && $date >= $booking['check_in_date'] && $date < $booking['check_out_date']) {
            return $booking;
        }
    }
    return false;
}

function getMonthName($month) {
    return date('F', mktime(0, 0, 0, $month, 1));
}

// Previous/Next month navigation
$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

$nextMonth = $currentMonth + 1;
$nextYear = $currentYear;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar View - <?php echo htmlspecialchars($hotel['name'] ?? 'Hotel PMS'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #e0e0e0;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        /* Import sidebar from includes */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 260px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            padding: 20px 0;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease, width 0.3s ease;
            overflow-y: auto;
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar.collapsed .menu-item span,
        .sidebar.collapsed .hotel-name {
            display: none;
        }

        .sidebar.collapsed .logo-text {
            font-size: 24px;
        }

        .hotel-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .hotel-name {
            color: #60a5fa;
            font-size: 18px;
            font-weight: 600;
            margin-top: 10px;
        }

        .logo-text {
            font-size: 32px;
            text-align: center;
        }

        .menu {
            list-style: none;
        }

        .menu-item {
            padding: 12px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #94a3b8;
            text-decoration: none;
            border-left: 3px solid transparent;
        }

        .menu-item:hover {
            background: rgba(96, 165, 250, 0.1);
            border-left-color: #60a5fa;
            color: #60a5fa;
        }

        .menu-item.active {
            background: rgba(96, 165, 250, 0.15);
            border-left-color: #60a5fa;
            color: #60a5fa;
            font-weight: 600;
        }

        .menu-item.has-dropdown {
            flex-direction: column;
            align-items: stretch;
        }

        .menu-item-header {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
        }

        .dropdown-arrow {
            margin-left: auto;
            transition: transform 0.3s ease;
            font-size: 12px;
        }

        .dropdown-arrow.open {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            padding-left: 20px;
        }

        .dropdown-menu.open {
            max-height: 200px;
        }

        .dropdown-item {
            padding: 10px 0;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.3s ease;
            font-size: 14px;
        }

        .dropdown-item:hover {
            color: #60a5fa;
        }

        .toggle-btn {
            position: fixed;
            left: 270px;
            top: 20px;
            background: #1e293b;
            border: none;
            color: #60a5fa;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 1001;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            transition: left 0.3s ease;
        }

        .toggle-btn:hover {
            background: #334155;
        }

        .sidebar.collapsed ~ .toggle-btn {
            left: 90px;
        }

        .main-content {
            margin-left: 260px;
            padding: 30px;
            transition: margin-left 0.3s ease;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: 80px;
        }

        .top-bar {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .controls {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .currency-toggle, .theme-toggle {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        .currency-toggle:hover, .theme-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #60a5fa;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #94a3b8;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .calendar-controls {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .month-nav {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .month-nav a {
            background: #3b82f6;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .month-nav a:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .current-month {
            font-size: 24px;
            font-weight: 600;
            color: #fff;
            min-width: 200px;
            text-align: center;
        }

        .calendar-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-info {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .calendar-wrapper {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            overflow-x: auto;
            margin-bottom: 30px;
        }

        .calendar-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 1200px;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }

        .calendar-table th {
            background: #1e293b;
            color: #60a5fa;
            padding: 15px 8px;
            text-align: center;
            font-weight: 600;
            border: 1px solid rgba(96, 165, 250, 0.2);
            font-size: 13px;
        }

        .calendar-table th:first-child {
            border-top-left-radius: 10px;
        }

        .calendar-table th:last-child {
            border-top-right-radius: 10px;
        }

        .room-header {
            min-width: 150px;
            position: sticky;
            left: 0;
            background: #1e293b !important;
            z-index: 10;
        }

        .room-info {
            background: #1e293b;
            padding: 15px;
            min-width: 150px;
            position: sticky;
            left: 0;
            z-index: 5;
            border: 1px solid rgba(96, 165, 250, 0.2);
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }

        .room-details {
            text-align: center;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }

        .room-number {
            font-weight: 600;
            color: #60a5fa;
            margin-bottom: 5px;
            font-size: 16px;
        }

        .room-type {
            color: #94a3b8;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .room-price {
            color: #10b981;
            font-size: 13px;
            font-weight: 600;
        }

        .room-status-badge {
            display: inline-block;
            font-size: 16px;
            margin-left: 5px;
        }

        .room-status-btn {
            background: #334155;
            color: #94a3b8;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 11px;
            cursor: pointer;
            margin-top: 5px;
            transition: all 0.3s ease;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }

        .room-status-btn:hover {
            background: #475569;
            color: #60a5fa;
        }

        .room-image {
            width: 100%;
            max-width: 120px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 10px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .room-image:hover {
            transform: scale(1.05);
        }

        .room-image-placeholder {
            width: 100%;
            height: 80px;
            background: rgba(59, 130, 246, 0.1);
            border: 2px dashed rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .day-cell {
            padding: 8px;
            text-align: center;
            cursor: pointer;
            border: 1px solid rgba(96, 165, 250, 0.1);
            background: rgba(30, 41, 59, 0.5);
            transition: all 0.3s ease;
            min-width: 60px;
            height: 60px;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }

        .day-cell:hover {
            background: rgba(59, 130, 246, 0.2);
            transform: scale(1.05);
        }

        .day-cell.available {
            background: rgba(6, 182, 212, 0.2);
        }

        .day-cell.booked {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
        }

        .day-cell.booked.paid {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .day-cell.booked.partial {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .day-cell.checkout {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
            font-weight: 600;
        }

        .day-cell.weekend {
            background: rgba(51, 65, 85, 0.5);
        }

        .day-cell.room-dirty {
            border: 3px solid #dc2626;
            background: rgba(220, 38, 38, 0.1);
        }

        .day-cell.room-maintenance {
            border: 3px solid #f59e0b;
            background: rgba(245, 158, 11, 0.1);
        }

        .day-cell.room-out-of-order {
            border: 3px solid #ef4444;
            background: rgba(239, 68, 68, 0.15);
        }

        .booking-info {
            font-size: 12px;
            font-weight: 600;
        }

        .day-number {
            font-size: 11px;
            color: #94a3b8;
        }

        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 20px;
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(10px);
            border-radius: 15px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .legend-color {
            width: 30px;
            height: 20px;
            border-radius: 5px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            margin: 0;
            padding: 30px;
            border-radius: 20px;
            max-width: 900px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(96, 165, 250, 0.3);
        }

        .modal-header h2 {
            color: #60a5fa;
            font-size: 24px;
        }

        .close {
            color: #94a3b8;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #60a5fa;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #94a3b8;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(96, 165, 250, 0.3);
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group input[type="date"] {
            color-scheme: dark;
            cursor: pointer;
        }
        
        .form-group input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(96, 165, 250, 0.2);
        }

        .btn-cancel {
            background: #475569;
            color: white;
        }

        .btn-cancel:hover {
            background: #334155;
        }

        @media print {
            .sidebar, .toggle-btn, .top-bar, .calendar-controls, .legend, .modal {
                display: none !important;
            }

            body {
                background: white;
                color: black;
            }

            .main-content {
                margin-left: 0;
            }

            .calendar-wrapper {
                background: white;
            }

            .calendar-table th {
                background: #f0f0f0 !important;
                color: black !important;
            }

            .day-cell {
                border: 1px solid #ccc !important;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="hotel-header">
            <div class="logo-text">🏨</div>
            <div class="hotel-name"><?php echo htmlspecialchars($hotel['name'] ?? 'Hotel PMS'); ?></div>
        </div>
        
        <ul class="menu">
            <li>
                <a href="dashboard.php" class="menu-item">
                    <span style="font-size: 20px;">🏠</span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item has-dropdown <?php echo (basename($_SERVER['PHP_SELF']) == 'room_management.php' || basename($_SERVER['PHP_SELF']) == 'photo_upload.php') ? 'open' : ''; ?>">
                <div class="menu-item-header" onclick="toggleDropdown(this)">
                    <span style="font-size: 20px;">🛏️</span>
                    <span>Rooms</span>
                    <span class="dropdown-arrow">▼</span>
                </div>
                <div class="dropdown-menu <?php echo (basename($_SERVER['PHP_SELF']) == 'room_management.php' || basename($_SERVER['PHP_SELF']) == 'photo_upload.php') ? 'open' : ''; ?>">
                    <a href="room_management.php" class="dropdown-item">
                        <span style="font-size: 16px;">📋</span>
                        <span>Manage Rooms</span>
                    </a>
                    <a href="photo_upload.php" class="dropdown-item">
                        <span style="font-size: 16px;">📸</span>
                        <span>Room Photos</span>
                    </a>
                </div>
            </li>
            <li>
                <a href="calendar_view.php" class="menu-item active">
                    <span style="font-size: 20px;">📅</span>
                    <span>Calendar</span>
                </a>
            </li>
            <li>
                <a href="accounting_dashboard.php" class="menu-item">
                    <span style="font-size: 20px;">💰</span>
                    <span>Accounting</span>
                </a>
            </li>
            <li>
                <a href="analytics.php" class="menu-item">
                    <span style="font-size: 20px;">📊</span>
                    <span>Analytics</span>
                </a>
            </li>
            <li>
                <a href="employee_management.php" class="menu-item">
                    <span style="font-size: 20px;">👥</span>
                    <span>Employees</span>
                </a>
            </li>
            <li>
                <a href="owner_account.php" class="menu-item">
                    <span style="font-size: 20px;">🏢</span>
                    <span>My Properties</span>
                </a>
            </li>
            <li>
                <a href="hotel_setup.php" class="menu-item">
                    <span style="font-size: 20px;">⚙️</span>
                    <span>Settings</span>
                </a>
            </li>
            <li>
                <a href="logout.php" class="menu-item">
                    <span style="font-size: 20px;">🚪</span>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Sidebar Toggle Button -->
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                🗓️ Calendar View
            </div>
            <div class="controls">
                <button class="btn btn-success" onclick="openBookingModal()" style="margin-right: 15px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 10px 20px; border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer;">
                    ➕ New Booking
                </button>
                <button class="currency-toggle" onclick="toggleCurrency()">
                    <span id="currency-symbol">USD</span> 💱
                </button>
                <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
            </div>
        </div>

        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalRooms; ?></div>
                <div class="stat-label">Total Rooms</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($bookings); ?></div>
                <div class="stat-label">Active Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($occupancyRate, 1); ?>%</div>
                <div class="stat-label">Occupancy Rate</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalDays; ?></div>
                <div class="stat-label">Days in Month</div>
            </div>
        </div>

        <!-- Calendar Controls -->
        <div class="calendar-controls">
            <div class="month-nav">
                <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>">◀ Prev</a>
                <div class="current-month">
                    <?php echo getMonthName($currentMonth) . ' ' . $currentYear; ?>
                </div>
                <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>">Next ▶</a>
            </div>
            <div class="calendar-actions">
                <button onclick="openBookingModal()" class="btn btn-success">+ Quick Booking</button>
                <a href="room_management.php" class="btn btn-info">Manage Rooms</a>
                <button onclick="window.print()" class="btn btn-primary">🖨️ Print</button>
            </div>
        </div>

        <!-- Calendar Grid -->
        <div class="calendar-wrapper">
            <table class="calendar-table">
                <thead>
                    <tr>
                        <th class="room-header">Room</th>
                        <?php
                        $daysInMonth = getDaysInMonth($currentMonth, $currentYear);
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $dayOfWeek = date('D', mktime(0, 0, 0, $currentMonth, $day, $currentYear));
                            echo "<th>{$day}<br><small>{$dayOfWeek}</small></th>";
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td class="room-info <?php 
                                $roomStatus = $room['status'] ?? 'clean';
                                if ($roomStatus === 'dirty') echo 'room-dirty';
                                elseif ($roomStatus === 'maintenance') echo 'room-maintenance';
                                elseif ($roomStatus === 'out_of_order') echo 'room-out-of-order';
                            ?>">
                                <div class="room-details">
                                    <div class="room-number">
                                        Room <?php echo htmlspecialchars($room['room_number']); ?>
                                        <?php if ($roomStatus === 'clean'): ?>
                                            <span class="room-status-badge clean" title="Room is Clean">✨</span>
                                        <?php elseif ($roomStatus === 'dirty'): ?>
                                            <span class="room-status-badge dirty" title="Needs Cleaning">🧹</span>
                                        <?php elseif ($roomStatus === 'maintenance'): ?>
                                            <span class="room-status-badge maintenance" title="Under Maintenance">🔧</span>
                                        <?php elseif ($roomStatus === 'out_of_order'): ?>
                                            <span class="room-status-badge out-of-order" title="Out of Order">⚠️</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="margin-top: 5px;">
                                        <button class="room-status-btn" onclick="openRoomStatusModal(<?php echo $room['id']; ?>, '<?php echo $room['room_number']; ?>', '<?php echo $roomStatus; ?>')" title="Change Room Status">⚙️ Manage Status</button>
                                    </div>
                                    <div class="room-type"><?php echo htmlspecialchars($room['room_type']); ?></div>
                                    <div class="room-price" data-usd="<?php echo number_format($room['price'] ?? 0, 0); ?>">
                                        $<?php echo number_format($room['price'] ?? 0, 0); ?>/night
                                    </div>
                                </div>
                                <?php if (isset($roomPhotos[$room['id']]) && !empty($roomPhotos[$room['id']])): ?>
                                    <img src="<?php echo htmlspecialchars($roomPhotos[$room['id']]); ?>" 
                                         alt="Room <?php echo htmlspecialchars($room['room_number']); ?>" 
                                         class="room-image"
                                         onclick="openImageModal('<?php echo htmlspecialchars($roomPhotos[$room['id']]); ?>', '<?php echo htmlspecialchars($room['room_number']); ?>')"
                                         title="Click to view larger image"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="room-image-placeholder" style="display: none;">
                                        🏨
                                    </div>
                                <?php else: ?>
                                    <div class="room-image-placeholder">
                                        🏨
                                    </div>
                                <?php endif; ?>
                            </td>
                            <?php
                            for ($day = 1; $day <= $daysInMonth; $day++) {
                                $currentDate = date('Y-m-d', mktime(0, 0, 0, $currentMonth, $day, $currentYear));
                                $booking = isDateBooked($room['id'], $currentDate, $bookings);
                                $isWeekend = date('w', mktime(0, 0, 0, $currentMonth, $day, $currentYear)) == 0 || 
                                           date('w', mktime(0, 0, 0, $currentMonth, $day, $currentYear)) == 6;
                                $isToday = $currentDate == date('Y-m-d');
                                
                                $cellClass = 'day-cell';
                                if ($isWeekend) $cellClass .= ' weekend';
                                
                                if ($booking) {
                                    if ($currentDate == $booking['check_out_date']) {
                                        $cellClass .= ' checkout';
                                        $cellContent = '<div class="booking-info">OUT</div>';
                                    } else {
                                        $cellClass .= ' booked';
                                        
                                        // Add payment status to cell class
                                        $paymentStatus = $booking['payment_status'] ?? 'pending';
                                        if ($paymentStatus === 'paid') {
                                            $cellClass .= ' paid';
                                        } elseif ($paymentStatus === 'partial') {
                                            $cellClass .= ' partial';
                                        }
                                        
                                        // Show multiple guest names or fallback to initials
                                        if (!empty($booking['all_guest_names'])) {
                                            $guestNames = strlen($booking['all_guest_names']) > 15 
                                                ? substr($booking['all_guest_names'], 0, 15) . '...' 
                                                : $booking['all_guest_names'];
                                            $cellContent = '<div class="booking-info">' . htmlspecialchars($guestNames) . '</div>';
                                        } else {
                                            $cellContent = '<div class="booking-info">' . 
                                                         substr($booking['first_name'], 0, 1) . 
                                                         substr($booking['last_name'], 0, 1) . '</div>';
                                        }
                                    }
                                    
                                    // Prepare booking data for JavaScript
                                    $bookingData = json_encode([
                                        'id' => $booking['id'],
                                        'room_id' => $booking['room_id'],
                                        'guest_name' => $booking['display_guest_name'],
                                        'guest_email' => !empty($booking['guest_email']) ? $booking['guest_email'] : $booking['email'],
                                        'guest_phone' => !empty($booking['guest_phone']) ? $booking['guest_phone'] : ($booking['phone'] ?? ''),
                                        'guests_list' => $booking['guests_list'],
                                        'all_guest_names' => $booking['all_guest_names'],
                                        'is_multi_room' => $booking['is_multi_room'] ?? false,
                                        'all_rooms' => $booking['all_rooms'] ?? $booking['room_number'],
                                        'passport_number' => $booking['passport_number'] ?? '',
                                        'id_number' => $booking['id_number'] ?? '',
                                        'room_number' => $booking['room_number'],
                                        'room_type' => $booking['room_type'],
                                        'check_in_date' => $booking['check_in_date'],
                                        'check_out_date' => $booking['check_out_date'],
                                        'total_amount' => $booking['total_price'],
                                        'total_price' => $booking['total_price'],
                                        'discount_amount' => $booking['discount_amount'] ?? 0,
                                        'status' => $booking['status'],
                                        'special_requests' => $booking['special_requests'] ?? '',
                                        'booking_date' => $booking['created_at'],
                                        'payment_status' => $booking['payment_status'] ?? 'pending',
                                        'payment_method' => $booking['payment_method'] ?? '',
                                        'paid_amount' => $booking['paid_amount'] ?? 0,
                                        'booking_reference' => $booking['booking_reference'] ?? '',
                                        'debug_raw_total_price' => $booking['total_price'],
                                        'debug_raw_discount' => $booking['discount_amount']
                                    ]);
                                } else {
                                    // Check room status for available rooms
                                    $isPastDate = $currentDate < date('Y-m-d');
                                    
                                    // First check if there's a date-specific status
                                    $statusKey = $room['id'] . '_' . $currentDate;
                                    $roomStatus = isset($roomStatusByDate[$statusKey]) 
                                        ? $roomStatusByDate[$statusKey] 
                                        : ($room['status'] ?? 'clean');
                                    
                                    if (!$isPastDate && $roomStatus !== 'clean') {
                                        // Apply room status styling for current and future dates only
                                        if ($roomStatus === 'dirty') {
                                            $cellClass .= ' room-dirty';
                                        } elseif ($roomStatus === 'maintenance') {
                                            $cellClass .= ' room-maintenance';
                                        } elseif ($roomStatus === 'out_of_order') {
                                            $cellClass .= ' room-out-of-order';
                                        }
                                        $cellContent = '<div class="day-number">' . $day . '</div>';
                                    } else {
                                        // Clean rooms or past dates always show as available
                                        $cellClass .= ' available';
                                        $cellContent = '<div class="day-number">' . $day . '</div>';
                                    }
                                    $bookingData = 'null';
                                }
                                
                                echo "<td class=\"{$cellClass}\" onclick=\"cellClick('{$room['id']}', '{$currentDate}', '" . 
                                     ($booking ? 'booked' : 'available') . "', " . htmlspecialchars($bookingData) . ")\" title=\"" . 
                                     ($booking ? 'Booked by ' . $booking['first_name'] . ' ' . $booking['last_name'] : 'Available') . 
                                     "\">{$cellContent}</td>";
                            }
                            ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Legend -->
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);"></div>
                <span>💧 Available</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"></div>
                <span>✅ Paid Booking</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);"></div>
                <span>❌ Pending Payment</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"></div>
                <span>⚠️ Partial Payment</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);"></div>
                <span>🚪 Check-out</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: rgba(51, 65, 85, 0.5); border: 1px solid #475569;"></div>
                <span>📆 Weekend</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: white; border: 3px solid #dc2626;"></div>
                <span>🧹 Dirty Room</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: white; border: 3px solid #f59e0b;"></div>
                <span>🔧 Maintenance</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: white; border: 3px solid #ef4444;"></div>
                <span>⚠️ Out of Order</span>
            </div>
        </div>
    </div>

    <script>
        // Currency conversion
        const EXCHANGE_RATE = <?php echo $exchangeRate; ?>;
        let currentCurrency = localStorage.getItem('currency') || 'USD';

        function toggleCurrency() {
            currentCurrency = currentCurrency === 'USD' ? 'PEN' : 'USD';
            localStorage.setItem('currency', currentCurrency);
            
            document.getElementById('currency-symbol').textContent = currentCurrency;
            
            // Update room prices
            document.querySelectorAll('.room-price').forEach(el => {
                const usd = parseFloat(el.getAttribute('data-usd'));
                if (currentCurrency === 'PEN') {
                    const pen = usd * EXCHANGE_RATE;
                    el.innerHTML = 'S/. ' + pen.toFixed(0) + '/night';
                } else {
                    el.innerHTML = '$' + usd + '/night';
                }
            });
        }

        // Initialize currency on page load
        if (currentCurrency === 'PEN') {
            toggleCurrency();
        }

        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
        }

        // Restore sidebar state
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            document.querySelector('.sidebar').classList.add('collapsed');
        }

        // Theme toggle
        function toggleTheme() {
            // Placeholder for theme functionality
            alert('Theme toggle coming soon!');
        }

        // Dropdown toggle for Rooms menu
        function toggleDropdown(element) {
            const menuItem = element.parentElement;
            const dropdownMenu = menuItem.querySelector('.dropdown-menu');
            const arrow = element.querySelector('.dropdown-arrow');
            
            menuItem.classList.toggle('open');
            dropdownMenu.classList.toggle('open');
            
            if (menuItem.classList.contains('open')) {
                arrow.style.transform = 'rotate(180deg)';
            } else {
                arrow.style.transform = 'rotate(0deg)';
            }
        }

        // Cell click handler
        function cellClick(roomId, date, status, bookingData) {
            console.log('cellClick called:', {roomId, date, status, bookingData});
            if (status === 'booked' && bookingData) {
                // Show booking details
                currentBookingData = bookingData;
                showGuestInfo(bookingData);
            } else {
                // Show quick booking modal pre-filled with this room and date
                console.log('Opening booking modal for room:', roomId, 'date:', date);
                openBookingModal(roomId, date);
            }
        }

        function showBookingDetails(bookingData) {
            alert('Booking details modal - Coming soon!\n\nBooking ID: ' + bookingData.id + '\nGuest: ' + bookingData.guest_name);
        }

        function openImageModal(imagePath, roomNumber) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('imageModalImg');
            const modalTitle = document.getElementById('imageModalTitle');
            
            modalImg.src = imagePath;
            modalTitle.textContent = `Room ${roomNumber} - Image`;
            modal.style.display = 'flex';
            
            // Prevent body scrolling when modal is open
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.style.display = 'none';
            
            // Restore body scrolling
            document.body.style.overflow = 'auto';
        }

        // Room Status Modal Functions
        let currentRoomId = null;

        function openRoomStatusModal(roomId, roomNumber, currentStatus) {
            currentRoomId = roomId;
            
            const modal = document.getElementById('roomStatusModal');
            if (!modal) {
                alert('Error: Modal no encontrado');
                return;
            }
            
            document.getElementById('roomStatusModalTitle').textContent = `Room ${roomNumber}`;
            document.getElementById('currentRoomStatus').textContent = getStatusLabel(currentStatus);
            document.getElementById('newRoomStatus').value = currentStatus;
            document.getElementById('statusChangeNotes').value = '';
            
            // Reset date mode
            document.getElementById('statusDateMode').value = 'single';
            toggleStatusDateFields();
            
            modal.style.display = 'flex';
        }

        function toggleStatusDateFields() {
            const mode = document.getElementById('statusDateMode').value;
            const singleField = document.getElementById('statusSingleDateField');
            const rangeFields = document.getElementById('statusDateRangeFields');
            
            if (mode === 'single') {
                singleField.style.display = 'block';
                rangeFields.style.display = 'none';
            } else if (mode === 'range') {
                singleField.style.display = 'none';
                rangeFields.style.display = 'block';
            } else { // permanent
                singleField.style.display = 'none';
                rangeFields.style.display = 'none';
            }
        }

        function closeRoomStatusModal() {
            document.getElementById('roomStatusModal').style.display = 'none';
            currentRoomId = null;
        }

        function getStatusLabel(status) {
            switch(status) {
                case 'clean': return '✨ Clean & Ready';
                case 'dirty': return '🧹 Needs Cleaning';
                case 'maintenance': return '🔧 Under Maintenance';
                case 'out_of_order': return '⚠️ Out of Order';
                default: return status;
            }
        }

        function updateRoomStatus() {
            if (!currentRoomId) return;
            
            const newStatus = document.getElementById('newRoomStatus').value;
            const notes = document.getElementById('statusChangeNotes').value;
            const dateMode = document.getElementById('statusDateMode').value;
            
            // Get dates based on mode
            let startDate, endDate;
            if (dateMode === 'single') {
                startDate = endDate = document.getElementById('statusSingleDate').value;
            } else if (dateMode === 'range') {
                startDate = document.getElementById('statusStartDate').value;
                endDate = document.getElementById('statusEndDate').value;
            } else { // permanent
                startDate = endDate = 'permanent';
            }
            
            // Create form data
            const formData = new FormData();
            formData.append('action', 'update_status_by_date');
            formData.append('room_id', currentRoomId);
            formData.append('status', newStatus);
            formData.append('notes', notes);
            formData.append('date_mode', dateMode);
            formData.append('start_date', startDate);
            formData.append('end_date', endDate);
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            
            // Send AJAX request
            fetch('calendar_view.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeRoomStatusModal();
                    location.reload(); // Refresh to show updated status
                } else {
                    alert('Error updating room status: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating room status');
            });
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const roomStatusModal = document.getElementById('roomStatusModal');
            const imageModal = document.getElementById('imageModal');
            const paymentModal = document.getElementById('paymentStatusModal');
            const guestModal = document.getElementById('guestInfoModal');
            const editModal = document.getElementById('editBookingModal');
            const extendModal = document.getElementById('extendStayModal');
            const roomMgmtModal = document.getElementById('roomManagementModal');
            const bookingModal = document.getElementById('bookingModal');
            
            if (event.target == roomStatusModal) {
                closeRoomStatusModal();
            }
            if (event.target == imageModal) {
                closeImageModal();
            }
            if (event.target == paymentModal) {
                closePaymentStatusModal();
            }
            if (event.target == guestModal) {
                closeGuestInfoModal();
            }
            if (event.target == editModal) {
                closeEditBookingModal();
            }
            if (event.target == extendModal) {
                closeExtendStayModal();
            }
            if (event.target == roomMgmtModal) {
                closeRoomManagementModal();
            }
            if (event.target == bookingModal) {
                closeBookingModal();
            }
        }

        // Close modals with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeRoomStatusModal();
                closeImageModal();
                closePaymentStatusModal();
                closeGuestInfoModal();
                closeEditBookingModal();
                closeExtendStayModal();
                closeRoomManagementModal();
                closeBookingModal();
            }
        });

        // Payment Status Modal Functions
        let selectedPaymentStatus = null;

        function openPaymentStatusModal() {
            console.log('openPaymentStatusModal called');
            console.log('currentBookingData:', currentBookingData);
            
            if (!currentBookingData) {
                alert('No hay datos de reserva disponibles.');
                return;
            }
            
            try {
                const modal = document.getElementById('paymentStatusModal');
                console.log('Payment modal element:', modal);
                
                if (!modal) {
                    console.error('Payment modal not found');
                    alert('Error: Modal de pago no encontrado');
                    return;
                }
                
                // Populate modal info
                const bookingInfo = document.getElementById('paymentModalBookingInfo');
                const amountInfo = document.getElementById('paymentModalAmount');
                
                console.log('bookingInfo element:', bookingInfo);
                console.log('amountInfo element:', amountInfo);
                
                if (bookingInfo) {
                    bookingInfo.textContent = `#${currentBookingData.id} - ${currentBookingData.guest_name}`;
                }
                
                if (amountInfo) {
                    let amount = currentBookingData.total_amount || currentBookingData.total_price_usd || currentBookingData.total_price || 0;
                    console.log('Amount:', amount);
                    
                    if (amount && !isNaN(amount)) {
                        const usdAmount = parseFloat(amount).toFixed(2);
                        const penAmount = (parseFloat(amount) * USD_TO_PEN_RATE).toFixed(2);
                        
                        if (currentCurrency === 'USD') {
                            amountInfo.innerHTML = `$${usdAmount} USD <span style="color: #94a3b8; font-size: 0.9em;">(S/ ${penAmount} PEN)</span>`;
                        } else {
                            amountInfo.innerHTML = `S/ ${penAmount} PEN <span style="color: #94a3b8; font-size: 0.9em;">($${usdAmount} USD)</span>`;
                        }
                    } else {
                        amountInfo.textContent = `Monto no disponible`;
                    }
                }
                
                // Reset form
                selectedPaymentStatus = null;
                const paymentAmount = document.getElementById('paymentAmount');
                const paymentNotes = document.getElementById('paymentNotes');
                const paymentAmountSection = document.getElementById('paymentAmountSection');
                const confirmButton = document.getElementById('confirmPaymentStatus');
                
                if (paymentAmount) paymentAmount.value = '';
                if (paymentNotes) paymentNotes.value = '';
                if (paymentAmountSection) paymentAmountSection.style.display = 'none';
                if (confirmButton) confirmButton.disabled = true;
                
                // Remove selected class from all buttons
                document.querySelectorAll('.payment-status-btn').forEach(btn => {
                    btn.style.opacity = '1';
                    btn.style.transform = 'scale(1)';
                    btn.style.boxShadow = 'none';
                });
                
                // Close guest info modal
                closeGuestInfoModal();
                
                console.log('About to show modal...');
                modal.style.display = 'flex';
                console.log('Modal display set to flex');
                console.log('Payment modal opened successfully');
                
            } catch (error) {
                console.error('Error opening payment modal:', error);
                alert('Error al abrir el modal de pago: ' + error.message);
            }
        }
        
        function closePaymentStatusModal() {
            const modal = document.getElementById('paymentStatusModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // Initialize payment status system when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Handle payment status button clicks
            document.querySelectorAll('.payment-status-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    selectedPaymentStatus = this.dataset.status;
                    
                    // Remove selected state from all buttons
                    document.querySelectorAll('.payment-status-btn').forEach(b => {
                        b.style.opacity = '0.6';
                        b.style.transform = 'scale(1)';
                        b.style.boxShadow = 'none';
                    });
                    
                    // Add selected state to clicked button
                    this.style.opacity = '1';
                    this.style.transform = 'scale(1.05)';
                    this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.3)';
                    
                    // Show amount input for partial payments
                    const amountSection = document.getElementById('paymentAmountSection');
                    if (selectedPaymentStatus === 'partial') {
                        amountSection.style.display = 'block';
                        document.getElementById('paymentAmount').required = true;
                    } else {
                        amountSection.style.display = 'none';
                        document.getElementById('paymentAmount').required = false;
                        // Set default amounts
                        if (selectedPaymentStatus === 'paid') {
                            let amount = currentBookingData.total_amount || currentBookingData.total_price || 0;
                            document.getElementById('paymentAmount').value = amount;
                        } else {
                            document.getElementById('paymentAmount').value = 0;
                        }
                    }
                    
                    // Enable confirm button
                    document.getElementById('confirmPaymentStatus').disabled = false;
                });
            });
            
            // Handle confirm button click
            const confirmBtn = document.getElementById('confirmPaymentStatus');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    if (!selectedPaymentStatus) {
                        alert('Por favor seleccione un estado de pago.');
                        return;
                    }
                    
                    let paidAmount = 0;
                    let totalAmount = currentBookingData.total_amount || currentBookingData.total_price || 0;
                    
                    if (selectedPaymentStatus === 'paid') {
                        paidAmount = totalAmount;
                    } else if (selectedPaymentStatus === 'partial') {
                        const amountInput = document.getElementById('paymentAmount').value;
                        if (!amountInput || amountInput <= 0) {
                            alert('Por favor ingrese un monto válido para el pago parcial.');
                            return;
                        }
                        paidAmount = parseFloat(amountInput);
                    } else if (selectedPaymentStatus === 'refunded') {
                        paidAmount = totalAmount;
                    }
                    
                    const notes = document.getElementById('paymentNotes').value;
                    
                    // Confirmation
                    const statusText = {
                        'paid': 'PAGADO COMPLETO',
                        'pending': 'NO PAGADO', 
                        'partial': 'PAGO PARCIAL',
                        'refunded': 'REEMBOLSADO'
                    };
                    
                    const confirmation = confirm(
                        `¿Cambiar estado a ${statusText[selectedPaymentStatus]}?\n\n` +
                        `Reserva: #${currentBookingData.id}\n` +
                        `Monto: $${parseFloat(paidAmount).toFixed(2)} USD (S/ ${(parseFloat(paidAmount) * USD_TO_PEN_RATE).toFixed(2)} PEN)\n` +
                        (notes ? `Notas: ${notes}` : '')
                    );
                    
                    if (confirmation) {
                        // Create and submit form
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.innerHTML = `
                            <input type="hidden" name="update_payment_status" value="1">
                            <input type="hidden" name="booking_id" value="${currentBookingData.id}">
                            <input type="hidden" name="payment_status" value="${selectedPaymentStatus}">
                            <input type="hidden" name="paid_amount" value="${paidAmount}">
                            <input type="hidden" name="payment_notes" value="${notes}">
                        `;
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
        });
        // Helper functions for Guest Info Modal
        const USD_TO_PEN_RATE = <?php echo $exchangeRate; ?>;
        
        // Rooms data for JavaScript
        const roomsData = <?php echo json_encode($rooms); ?>;

        function formatDualCurrency(usdAmount) {
            const penAmount = usdAmount * USD_TO_PEN_RATE;
            return `$${parseFloat(usdAmount).toFixed(2)} USD <span style="color: #94a3b8; font-size: 0.9em;">(S/ ${penAmount.toFixed(2)} PEN)</span>`;
        }

        function formatDateSpanish(dateString) {
            if (!dateString) return '';
            const parts = dateString.split('-');
            const year = parseInt(parts[0]);
            const month = parseInt(parts[1]) - 1;
            const day = parseInt(parts[2]);
            const date = new Date(year, month, day);
            
            const days = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
            const months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 
                           'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
            
            const dayName = days[date.getDay()];
            const dayNum = date.getDate();
            const monthName = months[date.getMonth()];
            const yearNum = date.getFullYear();
            
            return `${dayName}, ${dayNum} ${monthName} ${yearNum}`;
        }

        function calculateNights(checkInDate, checkOutDate) {
            if (!checkInDate || !checkOutDate) return 0;
            const checkInParts = checkInDate.split('-');
            const checkOutParts = checkOutDate.split('-');
            const checkIn = new Date(parseInt(checkInParts[0]), parseInt(checkInParts[1]) - 1, parseInt(checkInParts[2]));
            const checkOut = new Date(parseInt(checkOutParts[0]), parseInt(checkOutParts[1]) - 1, parseInt(checkOutParts[2]));
            const diffTime = Math.abs(checkOut - checkIn);
            return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        }

        function getPaymentStatusColor(status) {
            const colors = {
                'pending': '#dc2626',
                'paid': '#10b981',
                'partial': '#f59e0b',
                'refunded': '#6c757d'
            };
            return colors[status] || '#6c757d';
        }
        
        function getPaymentStatusText(status) {
            const texts = {
                'pending': '⏳ Pendiente',
                'paid': '✅ Pagado',
                'partial': '⚡ Parcial',
                'refunded': '↩️ Reembolsado'
            };
            return texts[status] || status;
        }

        // Guest Information Modal Functions
        function showGuestInfo(booking) {
            // Store booking data for other modals
            currentBookingData = {
                id: booking.id,
                room_id: booking.room_id,
                room_number: booking.room_number,
                room_type: booking.room_type,
                guest_name: booking.guest_name || booking.display_guest_name,
                guest_email: booking.guest_email || '',
                guest_phone: booking.guest_phone || '',
                passport_number: booking.passport_number || '',
                id_number: booking.id_number || '',
                check_in_date: booking.check_in_date,
                check_out_date: booking.check_out_date,
                total_amount: booking.total_amount || booking.total_price,
                total_price: booking.total_amount || booking.total_price,
                discount_amount: booking.discount_amount || 0,
                special_requests: booking.special_requests || '',
                guests_list: booking.guests_list || [],
                all_guest_names: booking.all_guest_names || booking.guest_name || 'Guest',
                payment_status: booking.payment_status || 'pending',
                paid_amount: booking.paid_amount || 0,
                booking_date: booking.booking_date,
                is_multi_room: booking.is_multi_room || false,
                all_rooms: booking.all_rooms || booking.room_number
            };
            
            // Generate guests display
            let guestsDisplay = '';
            if (booking.guests_list && booking.guests_list.length > 0) {
                guestsDisplay = '<div style="margin: 15px 0;">';
                guestsDisplay += `<h4 style="color: #60a5fa; margin-bottom: 12px; font-size: 16px;">👥 Guests (${booking.guests_list.length})</h4>`;
                
                booking.guests_list.forEach((guest, index) => {
                    const isPrimary = guest.is_primary;
                    guestsDisplay += `
                        <div style="background: ${isPrimary ? 'rgba(16, 185, 129, 0.1)' : 'rgba(30, 41, 59, 0.4)'}; padding: 12px; border-radius: 8px; margin-bottom: 8px; border-left: 3px solid ${isPrimary ? '#10b981' : '#475569'};">
                            <div>
                                <strong style="color: ${isPrimary ? '#10b981' : '#e0e0e0'};">${guest.name}</strong> 
                                ${isPrimary ? '<span style="background: #10b981; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; margin-left: 8px;">Primary</span>' : ''}
                                ${guest.email ? `<br><small style="color: #94a3b8;">📧 ${guest.email}</small>` : ''}
                                ${guest.phone ? `<br><small style="color: #94a3b8;">📱 ${guest.phone}</small>` : ''}
                            </div>
                        </div>
                    `;
                });
                guestsDisplay += '</div>';
            } else {
                guestsDisplay = `
                    <p style="color: #94a3b8;"><strong>Nombre:</strong> <span style="color: #e0e0e0;">${booking.guest_name || booking.display_guest_name || 'Guest'}</span></p>
                    <p style="color: #94a3b8;"><strong>Email:</strong> <a href="mailto:${booking.guest_email || ''}" style="color: #60a5fa; text-decoration: none;">📧 ${booking.guest_email || 'No email'}</a></p>
                    ${booking.guest_phone ? `<p style="color: #94a3b8;"><strong>WhatsApp:</strong> <a href="https://wa.me/${booking.guest_phone.replace(/[\s\-\(\)]/g, '')}" target="_blank" style="color: #10b981; text-decoration: none;">📱 ${booking.guest_phone}</a></p>` : ''}
                `;
            }

            const content = `
                <div style="background: rgba(30, 41, 59, 0.6); padding: 25px; border-radius: 15px; margin-bottom: 20px; border: 1px solid rgba(96, 165, 250, 0.2);">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                        <div>
                            <h3 style="color: #60a5fa; margin-bottom: 15px; font-size: 18px;">👤 Información del Cliente</h3>
                            ${guestsDisplay}
                            <p style="color: #94a3b8; margin-top: 10px;"><strong>ID Reserva:</strong> <span style="color: #60a5fa;">#${booking.id}</span></p>
                            <p style="color: #94a3b8;"><strong>Estado:</strong> <span style="background: ${booking.status === 'confirmed' ? 'rgba(16, 185, 129, 0.2)' : 'rgba(245, 158, 11, 0.2)'}; padding: 4px 12px; border-radius: 6px; color: ${booking.status === 'confirmed' ? '#10b981' : '#f59e0b'};">${booking.status === 'confirmed' ? 'Confirmada' : booking.status}</span></p>
                        </div>
                        <div>
                            <h3 style="color: #60a5fa; margin-bottom: 15px; font-size: 18px;">🏨 Detalles de la Habitación</h3>
                            ${booking.is_multi_room ? 
                                `<p style="color: #94a3b8;"><strong>Habitaciones:</strong> <span style="color: #e0e0e0;">${booking.all_rooms}</span> <span style="background: #06b6d4; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8em;">Multi-Room</span></p>` :
                                `<p style="color: #94a3b8;"><strong>Habitación:</strong> <span style="color: #e0e0e0;">${booking.room_number}</span></p>`
                            }
                            <p style="color: #94a3b8;"><strong>Tipo:</strong> <span style="color: #e0e0e0;">${booking.room_type}</span></p>
                            <p style="color: #94a3b8;"><strong>Precio Total:</strong> <span style="color: #10b981;">${formatDualCurrency(booking.total_amount)}</span></p>
                            <p style="color: #94a3b8;"><strong>Estado de Pago:</strong> <span style="background: ${getPaymentStatusColor(booking.payment_status)}; padding: 4px 12px; border-radius: 6px; color: white; font-weight: 600;">${getPaymentStatusText(booking.payment_status)}</span></p>
                            <p style="color: #94a3b8;"><strong>Monto Pagado:</strong> <span style="color: #10b981;">${formatDualCurrency(booking.paid_amount || 0)}</span></p>
                        </div>
                    </div>
                </div>
                
                <div style="background: rgba(59, 130, 246, 0.1); padding: 25px; border-radius: 15px; margin-bottom: 20px; border: 1px solid rgba(96, 165, 250, 0.3);">
                    <h3 style="color: #60a5fa; margin-bottom: 20px; font-size: 18px;">📅 Fechas de Estancia</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; text-align: center;">
                        <div>
                            <p style="color: #94a3b8; margin-bottom: 8px;"><strong>Check-in</strong></p>
                            <p style="font-size: 1.1em; color: #60a5fa; font-weight: 600;">${formatDateSpanish(booking.check_in_date)}</p>
                        </div>
                        <div>
                            <p style="color: #94a3b8; margin-bottom: 8px;"><strong>Check-out</strong></p>
                            <p style="font-size: 1.1em; color: #60a5fa; font-weight: 600;">${formatDateSpanish(booking.check_out_date)}</p>
                        </div>
                        <div>
                            <p style="color: #94a3b8; margin-bottom: 8px;"><strong>Noches</strong></p>
                            <p style="font-size: 1.1em; color: #60a5fa; font-weight: 600;">${calculateNights(booking.check_in_date, booking.check_out_date)}</p>
                        </div>
                    </div>
                </div>
                
                ${booking.special_requests ? `
                <div style="background: rgba(245, 158, 11, 0.1); padding: 20px; border-radius: 15px; margin-bottom: 20px; border: 1px solid rgba(245, 158, 11, 0.3);">
                    <h3 style="color: #f59e0b; margin-bottom: 12px; font-size: 16px;">📝 Solicitudes Especiales</h3>
                    <p style="color: #e0e0e0;">${booking.special_requests}</p>
                </div>
                ` : ''}
                
                <div style="background: rgba(168, 85, 247, 0.1); padding: 20px; border-radius: 15px; border: 1px solid rgba(168, 85, 247, 0.3);">
                    <h3 style="color: #a855f7; margin-bottom: 12px; font-size: 16px;">📊 Información de Reserva</h3>
                    <p style="color: #94a3b8;"><strong>Fecha de Reserva:</strong> <span style="color: #e0e0e0;">${new Date(booking.booking_date).toLocaleDateString('es-ES', {year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'})}</span></p>
                </div>
            `;
            
            document.getElementById('guestInfoContent').innerHTML = content;
            document.getElementById('guestInfoModal').style.display = 'flex';
        }

        function closeGuestInfoModal() {
            document.getElementById('guestInfoModal').style.display = 'none';
        }

        function generateReceipt() {
            if (currentBookingData) {
                window.open(`receipt_handler.php?booking_id=${currentBookingData.id}`, '_blank');
            }
        }

        function printReceipt() {
            if (currentBookingData) {
                window.open(`receipt_handler.php?booking_id=${currentBookingData.id}&action=print&print=1`, '_blank');
            }
        }

        function downloadPDF() {
            if (currentBookingData) {
                window.open(`receipt_handler.php?booking_id=${currentBookingData.id}&action=pdf`, '_blank');
            }
        }

        function emailReceipt() {
            if (currentBookingData) {
                alert('Email receipt feature - Coming soon!');
            }
        }

        function editBooking() {
            if (!currentBookingData) {
                alert('No hay datos de reserva disponibles para editar.');
                return;
            }
            closeGuestInfoModal();
            openEditBookingModal();
        }

        function openEditBookingModal() {
            if (!currentBookingData) return;
            
            // Populate form fields
            document.getElementById('edit_booking_id').value = currentBookingData.id;
            document.getElementById('edit_room_id').value = currentBookingData.room_id;
            document.getElementById('edit_guest_name').value = currentBookingData.guest_name;
            document.getElementById('edit_guest_email').value = currentBookingData.guest_email || '';
            document.getElementById('edit_guest_phone').value = currentBookingData.guest_phone || '';
            document.getElementById('edit_passport_number').value = currentBookingData.passport_number || '';
            document.getElementById('edit_id_number').value = currentBookingData.id_number || '';
            document.getElementById('edit_check_in').value = currentBookingData.check_in_date;
            document.getElementById('edit_check_out').value = currentBookingData.check_out_date;
            document.getElementById('edit_total_price').value = currentBookingData.total_price;
            document.getElementById('edit_special_requests').value = currentBookingData.special_requests || '';
            
            document.getElementById('editBookingModal').style.display = 'flex';
        }

        function closeEditBookingModal() {
            document.getElementById('editBookingModal').style.display = 'none';
        }

        function deleteBooking() {
            if (!currentBookingData) return;
            
            const confirmation = confirm(
                `⚠️ ¿Está seguro de que desea eliminar esta reserva?\n\n` +
                `Reserva #${currentBookingData.id}\n` +
                `Guest: ${currentBookingData.guest_name}\n` +
                `Room: ${currentBookingData.room_number}\n\n` +
                `Esta acción NO se puede deshacer.`
            );
            
            if (confirmation) {
                window.location.href = `calendar_view.php?delete_booking=${currentBookingData.id}&month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>`;
            }
        }

        // Extend Stay Modal Functions
        function extendStay() {
            if (!currentBookingData) {
                alert('No hay reserva seleccionada');
                return;
            }
            
            try {
                console.log('Opening extend stay modal with data:', currentBookingData);
                
                // Populate current booking information
                document.getElementById('extend_booking_id').value = currentBookingData.id;
                document.getElementById('current_guest_name').textContent = currentBookingData.all_guest_names || currentBookingData.guest_name || 'N/A';
                document.getElementById('current_room_info').textContent = `Room ${currentBookingData.room_number || 'N/A'} - ${currentBookingData.room_type || 'N/A'}`;
                document.getElementById('current_checkin').textContent = formatDateSpanish(currentBookingData.check_in_date);
                document.getElementById('current_checkout').textContent = formatDateSpanish(currentBookingData.check_out_date);
                
                // Set minimum date for new checkout (must be after current checkout)
                const currentCheckout = new Date(currentBookingData.check_out_date);
                currentCheckout.setDate(currentCheckout.getDate() + 1); // At least 1 day extension
                document.getElementById('new_checkout_date').min = currentCheckout.toISOString().split('T')[0];
                
                // Calculate room price per night from booking data
                const totalPrice = parseFloat(currentBookingData.total_amount) || parseFloat(currentBookingData.total_price) || 0;
                const nights = calculateNights(currentBookingData.check_in_date, currentBookingData.check_out_date);
                const pricePerNightUSD = nights > 0 ? totalPrice / nights : totalPrice;
                const pricePerNightPEN = pricePerNightUSD * USD_TO_PEN_RATE;
                
                // Store price per night for calculations
                currentBookingData.room_price = pricePerNightUSD;
                
                document.getElementById('room_price_per_night').textContent = `S/ ${pricePerNightPEN.toFixed(2)} ($${pricePerNightUSD.toFixed(2)} USD)`;
                
                // Reset form
                document.getElementById('extendStayForm').reset();
                document.getElementById('extend_booking_id').value = currentBookingData.id;
                
                // Reset calculations
                document.getElementById('additional_nights').textContent = '0';
                document.getElementById('extension_subtotal').textContent = 'S/ 0.00';
                document.getElementById('extension_discount').textContent = 'S/ 0.00';
                document.getElementById('extension_total').textContent = 'S/ 0.00';
                
                // Hide payment sections initially
                const paymentDetails = document.getElementById('payment_details');
                const paymentLaterDetails = document.getElementById('payment_later_details');
                
                if (paymentDetails) paymentDetails.style.display = 'none';
                if (paymentLaterDetails) paymentLaterDetails.style.display = 'none';
                
                // Close guest info modal
                closeGuestInfoModal();
                
                // Show modal
                const modal = document.getElementById('extendStayModal');
                if (modal) {
                    modal.style.display = 'flex';
                    console.log('Extend stay modal opened successfully');
                } else {
                    throw new Error('Extend stay modal not found');
                }
                
            } catch (error) {
                console.error('Error opening extend stay modal:', error);
                alert('Error al abrir el modal de extensión: ' + error.message);
            }
        }
        
        function closeExtendStayModal() {
            document.getElementById('extendStayModal').style.display = 'none';
        }
        
        function togglePaymentOptions() {
            const paymentMethod = document.getElementById('extend_payment_method').value;
            const paymentDetails = document.getElementById('payment_details');
            const paymentLaterDetails = document.getElementById('payment_later_details');
            
            if (paymentMethod === 'pay_now') {
                paymentDetails.style.display = 'block';
                paymentLaterDetails.style.display = 'none';
                
                // Set default due date to checkout date if paying now
                const checkoutDate = document.getElementById('new_checkout_date').value;
                if (checkoutDate) {
                    document.getElementById('payment_due_date').value = checkoutDate;
                }
            } else if (paymentMethod === 'pay_later') {
                paymentDetails.style.display = 'none';
                paymentLaterDetails.style.display = 'block';
                
                // Set default due date to checkout date + 3 days
                const checkoutDate = document.getElementById('new_checkout_date').value;
                if (checkoutDate) {
                    const dueDate = new Date(checkoutDate);
                    dueDate.setDate(dueDate.getDate() + 3);
                    document.getElementById('payment_due_date').value = dueDate.toISOString().split('T')[0];
                }
            } else {
                paymentDetails.style.display = 'none';
                paymentLaterDetails.style.display = 'none';
            }
            
            calculateExtensionCost();
        }
        
        function calculateExtensionCost() {
            if (!currentBookingData) return;
            
            const currentCheckout = new Date(currentBookingData.check_out_date);
            const newCheckoutInput = document.getElementById('new_checkout_date').value;
            
            if (!newCheckoutInput) {
                // Reset calculations if no date selected
                document.getElementById('additional_nights').textContent = '0';
                document.getElementById('extension_subtotal').textContent = 'S/ 0.00';
                document.getElementById('extension_discount').textContent = 'S/ 0.00';
                document.getElementById('extension_total').textContent = 'S/ 0.00';
                return;
            }
            
            const newCheckout = new Date(newCheckoutInput);
            
            if (newCheckout <= currentCheckout) {
                // Reset calculations if invalid date
                document.getElementById('additional_nights').textContent = '0';
                document.getElementById('extension_subtotal').textContent = 'S/ 0.00';
                document.getElementById('extension_discount').textContent = 'S/ 0.00';
                document.getElementById('extension_total').textContent = 'S/ 0.00';
                return;
            }
            
            // Calculate additional nights
            const additionalNights = Math.ceil((newCheckout - currentCheckout) / (1000 * 60 * 60 * 24));
            document.getElementById('additional_nights').textContent = additionalNights;
            
            // Get room price from booking data
            const priceUSD = parseFloat(currentBookingData.room_price) || parseFloat(currentBookingData.total_price_usd) || 0;
            const pricePEN = priceUSD * USD_TO_PEN_RATE;
            
            // Calculate subtotal
            const subtotalPEN = additionalNights * pricePEN;
            document.getElementById('extension_subtotal').textContent = `S/ ${subtotalPEN.toFixed(2)}`;
            
            // Calculate discount (per night amount in PEN)
            const discountPerNight = parseFloat(document.getElementById('extend_discount_amount').value) || 0;
            const totalDiscountPEN = discountPerNight * additionalNights;
            // Don't allow discount greater than subtotal
            const discountPEN = Math.min(totalDiscountPEN, subtotalPEN);
            
            document.getElementById('extension_discount').textContent = `S/ ${discountPEN.toFixed(2)}`;
            
            // Calculate total
            const totalPEN = subtotalPEN - discountPEN;
            document.getElementById('extension_total').textContent = `S/ ${totalPEN.toFixed(2)}`;
        }

        // Room Management Modal Functions
        function openRoomManagementModal(roomData) {
            console.log('Opening room management modal for:', roomData);
            
            // Populate modal with room data
            document.getElementById('manage_room_id').value = roomData.id;
            document.getElementById('room_management_title').textContent = `🧹 Room ${roomData.room_number} Management`;
            
            // Populate room info
            const roomInfo = document.getElementById('room_management_info');
            roomInfo.innerHTML = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px;">
                    <div>
                        <strong style="color: #94a3b8;">🏨 Room:</strong> <span style="color: #e0e0e0;">${roomData.room_number}</span><br>
                        <strong style="color: #94a3b8;">🛏️ Type:</strong> <span style="color: #e0e0e0;">${roomData.room_type}</span><br>
                        <strong style="color: #94a3b8;">💰 Price:</strong> <span style="color: #10b981;">$${parseFloat(roomData.price || 0).toFixed(0)}/night</span>
                    </div>
                    <div>
                        <strong style="color: #94a3b8;">📊 Current Status:</strong> <span style="color: #60a5fa;">${getRoomStatusText(roomData.status || 'clean')}</span><br>
                        <strong style="color: #94a3b8;">🧹 Last Cleaned:</strong> <span style="color: #e0e0e0;">${roomData.last_cleaned || 'Not recorded'}</span><br>
                        <strong style="color: #94a3b8;">👤 Cleaned By:</strong> <span style="color: #e0e0e0;">${roomData.cleaned_by || 'N/A'}</span>
                    </div>
                </div>
            `;
            
            // Set current status in dropdown
            document.getElementById('status').value = roomData.status || 'clean';
            
            // Clear notes
            document.getElementById('cleaning_notes').value = '';
            
            // Show modal
            document.getElementById('roomManagementModal').style.display = 'flex';
        }
        
        function closeRoomManagementModal() {
            document.getElementById('roomManagementModal').style.display = 'none';
        }
        
        function getRoomStatusText(status) {
            switch(status) {
                case 'clean': return '✅ Clean & Ready';
                case 'dirty': return '🧹 Needs Cleaning';
                case 'maintenance': return '🔧 Maintenance Required';
                case 'out_of_order': return '⚠️ Out of Order';
                default: return '✅ Clean & Ready';
            }
        }

        // Quick Booking Modal Functions
        function openBookingModal(roomId = null, date = null) {
            const modal = document.getElementById('bookingModal');
            
            // Reset form first
            roomCounter = 1;
            guestCounter = 1;
            
            // Pre-fill first room if provided
            if (roomId) {
                const firstRoomSelect = document.querySelector('.room-select[data-room-index="1"]');
                if (firstRoomSelect) {
                    firstRoomSelect.value = roomId;
                }
            }
            
            // Pre-fill check-in date if provided
            if (date) {
                document.getElementById('check_in_date').value = date;
                // Auto-set checkout to next day
                const checkIn = new Date(date);
                const checkOut = new Date(checkIn);
                checkOut.setDate(checkOut.getDate() + 1);
                document.getElementById('check_out_date').value = checkOut.toISOString().split('T')[0];
                
                // Update checkout min date
                document.getElementById('check_out_date').min = checkOut.toISOString().split('T')[0];
            }
            
            // Reset terms checkbox
            const termsCheckbox = document.getElementById('terms_accepted');
            if (termsCheckbox) {
                termsCheckbox.checked = false;
            }
            
            // Disable create booking button until terms are accepted
            const createButton = document.getElementById('create_booking_btn');
            if (createButton) {
                createButton.disabled = true;
            }
            
            // Calculate initial total
            calculateTotal();
            
            modal.style.display = 'flex';
        }
        
        function closeBookingModal() {
            const modal = document.getElementById('bookingModal');
            modal.style.display = 'none';
            
            // Reset multi-room container to just one room
            const roomsContainer = document.getElementById('roomsContainer');
            const allRoomItems = document.querySelectorAll('.room-selection-item');
            allRoomItems.forEach((item, index) => {
                if (index > 0) item.remove();
            });
            
            // Reset multi-guest container to just primary guest
            const guestsContainer = document.getElementById('guestsContainer');
            const allGuestItems = document.querySelectorAll('.guest-info-item');
            allGuestItems.forEach((item, index) => {
                if (index > 0) item.remove();
            });
            
            // Reset form inputs
            const firstRoomSelect = document.querySelector('.room-select');
            if (firstRoomSelect) firstRoomSelect.value = '';
            
            document.getElementById('check_in_date').value = '';
            document.getElementById('check_out_date').value = '';
            
            // Reset guest inputs
            document.querySelectorAll('.guest-name, .guest-email, .guest-phone, .guest-passport, .guest-id').forEach(input => {
                input.value = '';
            });
            
            // Reset payment fields
            document.getElementById('payment_status').value = 'pending';
            document.getElementById('payment_method_group').style.display = 'none';
            document.getElementById('paid_amount_group').style.display = 'none';
            
            // Reset discount fields
            document.getElementById('discount_type').value = '';
            document.getElementById('discount_value').disabled = true;
            document.getElementById('discount_value').value = '';
            document.getElementById('discount_reason').disabled = true;
            document.getElementById('discount_reason').value = '';
            
            // Reset customer discount
            document.getElementById('customer_discount_type').value = '';
            document.getElementById('customer_discount_value').disabled = true;
            document.getElementById('customer_discount_value').value = '';
            document.getElementById('customer_discount_reason').disabled = true;
            document.getElementById('customer_discount_reason').value = '';
            
            // Reset custom pricing
            document.getElementById('custom_price_type').value = '';
            document.getElementById('override_price_section').style.display = 'none';
            document.getElementById('adjustment_price_section').style.display = 'none';
            
            // Reset terms
            document.getElementById('terms_accepted').checked = false;
            document.getElementById('create_booking_btn').disabled = true;
            
            // Reset counters
            roomCounter = 1;
            guestCounter = 1;
        }
        

        // Update checkout min date when check-in changes
        document.addEventListener('DOMContentLoaded', function() {
            const checkInInput = document.getElementById('check_in_date');
            if (checkInInput) {
                checkInInput.addEventListener('change', function() {
                    const checkIn = new Date(this.value);
                    const checkOut = new Date(checkIn);
                    checkOut.setDate(checkOut.getDate() + 1);
                    
                    const checkOutInput = document.getElementById('check_out_date');
                    checkOutInput.min = checkOut.toISOString().split('T')[0];
                    
                    // Auto-set checkout if not set or if it's before new min
                    if (!checkOutInput.value || new Date(checkOutInput.value) <= checkIn) {
                        checkOutInput.value = checkOut.toISOString().split('T')[0];
                    }
                    
                    calculateTotal();
                });
            }
        });

        // Multiple rooms functionality
        let roomCounter = 1;
        
        window.addRoomSelection = function addRoomSelection() {
            roomCounter++;
            const roomsContainer = document.getElementById('roomsContainer');
            
            const roomHtml = `
                <div class="room-selection-item" id="room-item-${roomCounter}" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid #007bff;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h5 style="margin: 0; color: #495057;">Room ${roomCounter}</h5>
                        <button type="button" class="remove-room-btn btn btn-sm" onclick="removeRoomSelection(${roomCounter})" style="background: #dc3545; color: white; padding: 2px 8px; font-size: 0.8em;">
                            🗑️ Remove
                        </button>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr auto; gap: 15px; align-items: start;">
                        <div class="form-group">
                            <select class="room-select" name="room_ids[]" required onchange="updateGuestOptions(); calculateTotal();" data-room-index="${roomCounter}">
                                <option value="">Select Room</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo $room['id']; ?>">
                                        Room <?php echo $room['room_number']; ?> - <?php echo $room['room_type']; ?> 
                                        ($<?php echo number_format($room['price'] ?? 0, 2); ?> USD / S/ <?php echo number_format(($room['price'] ?? 0) * 3.75, 2); ?> PEN per night)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="width: 120px;">
                            <label style="font-size: 0.9em;">👥 Guests</label>
                            <select class="guests-select" name="room_guests[]" onchange="calculateTotal(); updateTotalGuests();" style="font-size: 0.9em;">
                                <option value="1">1 Guest</option>
                                <option value="2" selected>2 Guests</option>
                                <option value="3">3 Guests</option>
                                <option value="4">4 Guests</option>
                                <option value="5">5 Guests</option>
                                <option value="6">6 Guests</option>
                            </select>
                        </div>
                    </div>
                </div>
            `;
            
            roomsContainer.insertAdjacentHTML('beforeend', roomHtml);
            updateTotalGuests();
        }
        
        window.removeRoomSelection = function removeRoomSelection(roomIndex) {
            const roomItem = document.getElementById(`room-item-${roomIndex}`);
            const allRoomItems = document.querySelectorAll('.room-selection-item');
            
            if (roomItem && allRoomItems.length > 1 && roomIndex !== 1) {
                roomItem.style.transition = 'all 0.3s ease';
                roomItem.style.opacity = '0';
                roomItem.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    roomItem.remove();
                    updateTotalGuests();
                    calculateTotal();
                }, 300);
            } else {
                if (allRoomItems.length === 1) {
                    alert('At least one room must be selected.');
                }
            }
        }
        
        window.updateTotalGuests = function updateTotalGuests() {
            const guestSelects = document.querySelectorAll('.guests-select');
            let totalGuests = 0;
            
            guestSelects.forEach(select => {
                totalGuests += parseInt(select.value) || 0;
            });
            
            if (document.getElementById('totalGuestsDisplay')) {
                document.getElementById('totalGuestsDisplay').textContent = totalGuests;
            }
            
            if (document.getElementById('guest_count')) {
                document.getElementById('guest_count').value = totalGuests;
            }
        }
        
        // Multiple guests functionality
        let guestCounter = 1;
        
        window.addGuestInfo = function addGuestInfo() {
            guestCounter++;
            const guestsContainer = document.getElementById('guestsContainer');
            
            const guestHtml = `
                <div class="guest-info-item" id="guest-item-${guestCounter}" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid #17a2b8;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h5 style="margin: 0; color: #495057;">Guest ${guestCounter}</h5>
                        <button type="button" class="remove-guest-btn btn btn-sm" onclick="removeGuestInfo(${guestCounter})" style="background: #dc3545; color: white; padding: 2px 8px; font-size: 0.8em;">
                            🗑️ Remove
                        </button>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px;">
                        <div class="form-group">
                            <label style="font-size: 0.9em;">👤 Full Name</label>
                            <input type="text" class="guest-name" name="guest_names[]" 
                                   placeholder="Full name" style="font-size: 0.9em;" data-guest-index="${guestCounter}">
                        </div>
                        <div class="form-group">
                            <label style="font-size: 0.9em;">📧 Email</label>
                            <input type="email" class="guest-email" name="guest_emails[]" 
                                   placeholder="guest@email.com" style="font-size: 0.9em;" data-guest-index="${guestCounter}">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label style="font-size: 0.9em;">📱 Phone</label>
                            <input type="tel" class="guest-phone" name="guest_phones[]" 
                                   placeholder="+51 999 999 999" style="font-size: 0.9em;"
                                   pattern="[\+]?[0-9\s\-\(\)]+" data-guest-index="${guestCounter}">
                        </div>
                        <div class="form-group">
                            <label style="font-size: 0.9em;">🛂 Passport</label>
                            <input type="text" class="guest-passport" name="guest_passports[]" 
                                   placeholder="A12345678" style="font-size: 0.9em;"
                                   pattern="[A-Z0-9]+" data-guest-index="${guestCounter}">
                        </div>
                        <div class="form-group">
                            <label style="font-size: 0.9em;">🆔 ID Number</label>
                            <input type="text" class="guest-id" name="guest_ids[]" 
                                   placeholder="12345678" style="font-size: 0.9em;"
                                   pattern="[A-Z0-9\-]+" data-guest-index="${guestCounter}">
                        </div>
                    </div>
                </div>
            `;
            
            guestsContainer.insertAdjacentHTML('beforeend', guestHtml);
            updateLegacyGuestFields();
        }
        
        window.removeGuestInfo = function removeGuestInfo(guestIndex) {
            const guestItem = document.getElementById(`guest-item-${guestIndex}`);
            const allGuestItems = document.querySelectorAll('.guest-info-item');
            
            if (guestItem && allGuestItems.length > 1 && guestIndex !== 1) {
                guestItem.style.transition = 'all 0.3s ease';
                guestItem.style.opacity = '0';
                guestItem.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    guestItem.remove();
                    updateLegacyGuestFields();
                }, 300);
            } else {
                if (allGuestItems.length === 1) {
                    alert('At least one guest must be present.');
                } else if (guestIndex === 1) {
                    alert('Cannot remove the primary guest.');
                }
            }
        }
        
        window.updateLegacyGuestFields = function updateLegacyGuestFields() {
            const primaryGuestName = document.querySelector('.guest-name[data-guest-index="1"]');
            const primaryGuestEmail = document.querySelector('.guest-email[data-guest-index="1"]');
            const primaryGuestPhone = document.querySelector('.guest-phone[data-guest-index="1"]');
            const primaryGuestPassport = document.querySelector('.guest-passport[data-guest-index="1"]');
            const primaryGuestId = document.querySelector('.guest-id[data-guest-index="1"]');
            
            if (primaryGuestName && document.getElementById('guest_name_legacy')) document.getElementById('guest_name_legacy').value = primaryGuestName.value;
            if (primaryGuestEmail && document.getElementById('guest_email')) document.getElementById('guest_email').value = primaryGuestEmail.value;
            if (primaryGuestPhone && document.getElementById('guest_phone')) document.getElementById('guest_phone').value = primaryGuestPhone.value;
            if (primaryGuestPassport && document.getElementById('passport_number')) document.getElementById('passport_number').value = primaryGuestPassport.value;
            if (primaryGuestId && document.getElementById('id_number')) document.getElementById('id_number').value = primaryGuestId.value;
        }
        
        window.validateGuestInfo = function validateGuestInfo() {
            const primaryGuestName = document.querySelector('.guest-name[data-guest-index="1"]');
            const primaryGuestEmail = document.querySelector('.guest-email[data-guest-index="1"]');
            
            if (!primaryGuestName || !primaryGuestName.value.trim()) {
                alert('Primary guest name is required.');
                primaryGuestName?.focus();
                return false;
            }
            
            if (!primaryGuestEmail || !primaryGuestEmail.value.trim()) {
                alert('Primary guest email is required.');
                primaryGuestEmail?.focus();
                return false;
            }
            
            updateLegacyGuestFields();
            return true;
        }

        function changeCurrency() {
            currentCurrency = document.getElementById('booking_currency').value;
            
            if (document.getElementById('currency_type_hidden')) {
                document.getElementById('currency_type_hidden').value = currentCurrency;
            }
            
            calculateTotal();
        }

        function toggleCustomPricing() {
            const customType = document.getElementById('custom_price_type').value;
            const overrideSection = document.getElementById('override_price_section');
            const adjustmentSection = document.getElementById('adjustment_price_section');
            
            overrideSection.style.display = 'none';
            adjustmentSection.style.display = 'none';
            
            document.getElementById('override_price_usd').value = '';
            document.getElementById('override_price_pen').value = '';
            document.getElementById('adjustment_amount').value = '';
            document.getElementById('adjustment_reason').value = '';
            
            if (customType === 'override') {
                overrideSection.style.display = 'block';
                const selectedCurrency = document.getElementById('booking_currency').value;
                
                if (selectedCurrency === 'USD') {
                    document.getElementById('override_price_usd').disabled = false;
                    document.getElementById('override_price_pen').disabled = true;
                    document.getElementById('override_price_usd').focus();
                } else {
                    document.getElementById('override_price_usd').disabled = true;
                    document.getElementById('override_price_pen').disabled = false;
                    document.getElementById('override_price_pen').focus();
                }
            } else if (customType === 'adjustment') {
                adjustmentSection.style.display = 'block';
                document.getElementById('adjustment_amount').focus();
            }
            
            calculateTotal();
        }

        function toggleCustomerDiscount() {
            const discountType = document.getElementById('customer_discount_type').value;
            const discountValue = document.getElementById('customer_discount_value');
            const discountReason = document.getElementById('customer_discount_reason');
            
            if (discountType) {
                discountValue.disabled = false;
                discountReason.disabled = false;
                
                if (discountType === 'percentage') {
                    discountValue.placeholder = 'e.g., 10 for 10%';
                    discountValue.max = '100';
                } else {
                    discountValue.placeholder = 'e.g., 15 for $15 off';
                    discountValue.max = '9999';
                }
                discountValue.focus();
            } else {
                discountValue.disabled = true;
                discountReason.disabled = true;
                discountValue.value = '';
                discountReason.value = '';
            }
            calculateTotal();
        }

        function togglePaymentFields() {
            const paymentStatus = document.getElementById('payment_status').value;
            const paymentMethodGroup = document.getElementById('payment_method_group');
            const paidAmountGroup = document.getElementById('paid_amount_group');
            const paidAmountInput = document.getElementById('paid_amount');
            
            if (paymentStatus === 'paid' || paymentStatus === 'partial') {
                paymentMethodGroup.style.display = 'block';
                paidAmountGroup.style.display = 'block';
                
                if (paymentStatus === 'paid') {
                    const totalPriceText = document.getElementById('totalPrice').textContent;
                    const totalAmount = parseFloat(totalPriceText.replace(/[^0-9.]/g, '')) || 0;
                    paidAmountInput.value = totalAmount.toFixed(2);
                } else {
                    paidAmountInput.value = '';
                }
            } else {
                paymentMethodGroup.style.display = 'none';
                paidAmountGroup.style.display = 'none';
                paidAmountInput.value = '';
            }
        }

        function toggleDiscountInput() {
            const discountType = document.getElementById('discount_type').value;
            const discountValueInput = document.getElementById('discount_value');
            const discountReasonInput = document.getElementById('discount_reason');
            
            if (discountType) {
                discountValueInput.disabled = false;
                discountReasonInput.disabled = false;
                discountValueInput.focus();
            } else {
                discountValueInput.disabled = true;
                discountReasonInput.disabled = true;
                discountValueInput.value = '';
                discountReasonInput.value = '';
            }
            calculateTotal();
        }

        function toggleCreateBookingButton() {
            const termsCheckbox = document.getElementById('terms_accepted');
            const createButton = document.getElementById('create_booking_btn');
            
            if (termsCheckbox && createButton) {
                createButton.disabled = !termsCheckbox.checked;
            }
        }

        function updateGuestOptions() {
            const roomId = document.getElementById('room_id').value;
            const guestSelect = document.getElementById('guest_count');
            
            if (!roomId || !guestSelect) {
                return;
            }
            
            const selectedRoom = roomsData.find(room => room.id == roomId);
            if (!selectedRoom) {
                return;
            }
            
            const maxOccupancy = parseInt(selectedRoom.max_occupancy) || 2;
            const extraBedAvailable = selectedRoom.extra_bed_available == 1;
            const maxGuests = extraBedAvailable ? maxOccupancy + 1 : maxOccupancy;
            
            guestSelect.innerHTML = '';
            
            for (let i = 1; i <= Math.max(maxGuests, 6); i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = `${i} Guest${i > 1 ? 's' : ''}`;
                
                if (i > maxGuests) {
                    option.textContent += ' ⚠️ (Exceeds capacity)';
                    option.style.color = '#dc3545';
                } else if (i > maxOccupancy) {
                    option.textContent += ' (Extra bed)';
                    option.style.color = '#ffc107';
                }
                
                if (i === 2) {
                    option.selected = true;
                }
                
                guestSelect.appendChild(option);
            }
        }

        function calculateTotal() {
            const roomSelects = document.querySelectorAll('.room-select');
            const guestSelects = document.querySelectorAll('.guests-select');
            const checkIn = document.getElementById('check_in_date')?.value;
            const checkOut = document.getElementById('check_out_date')?.value;
            
            let totalGuestCount = 0;
            guestSelects.forEach(select => {
                totalGuestCount += parseInt(select.value) || 0;
            });
            
            const customPriceType = document.getElementById('custom_price_type')?.value;
            const selectedCurrency = document.getElementById('booking_currency')?.value || 'USD';
            const discountType = document.getElementById('discount_type')?.value;
            const discountValue = parseFloat(document.getElementById('discount_value')?.value) || 0;

            let hasValidRooms = false;
            roomSelects.forEach(select => {
                if (select.value) hasValidRooms = true;
            });

            if (!hasValidRooms || !checkIn || !checkOut) {
                resetPriceDisplay();
                return;
            }

            const firstRoomId = roomSelects[0]?.value || '';
            if (document.getElementById('room_id')) document.getElementById('room_id').value = firstRoomId;
            if (document.getElementById('guest_count')) document.getElementById('guest_count').value = totalGuestCount;

            const checkInDate = new Date(checkIn);
            const checkOutDate = new Date(checkOut);
            const nights = Math.ceil((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));

            if (nights <= 0) {
                resetPriceDisplay();
                return;
            }

            let totalPricePerNightUSD = 0;
            
            roomSelects.forEach((roomSelect, index) => {
                const roomId = roomSelect.value;
                if (!roomId) return;

                const guestCount = parseInt(guestSelects[index]?.value) || 2;
                const selectedRoom = roomsData.find(room => room.id == roomId);
                
                if (!selectedRoom) return;

                const basePrice = parseFloat(selectedRoom.price) || 0;
                totalPricePerNightUSD += basePrice;
            });

            let finalPricePerNightUSD = totalPricePerNightUSD;
            
            if (customPriceType === 'override') {
                const overridePriceUSD = parseFloat(document.getElementById('override_price_usd')?.value) || 0;
                const overridePricePEN = parseFloat(document.getElementById('override_price_pen')?.value) || 0;
                
                if (selectedCurrency === 'USD' && overridePriceUSD > 0) {
                    finalPricePerNightUSD = overridePriceUSD / nights;
                } else if (selectedCurrency === 'PEN' && overridePricePEN > 0) {
                    finalPricePerNightUSD = (overridePricePEN / 3.75) / nights;
                }
            } else if (customPriceType === 'adjustment') {
                const adjustmentType = document.getElementById('adjustment_type')?.value;
                const adjustmentAmount = parseFloat(document.getElementById('adjustment_amount')?.value) || 0;
                
                if (adjustmentAmount > 0) {
                    if (adjustmentType === 'add') {
                        finalPricePerNightUSD = totalPricePerNightUSD + adjustmentAmount;
                    } else {
                        finalPricePerNightUSD = Math.max(0, totalPricePerNightUSD - adjustmentAmount);
                    }
                }
            }
            
            const pricePerNightUSD = finalPricePerNightUSD;
            let subtotalUSD = pricePerNightUSD * nights;
            let discountAmountUSD = 0;
            let customerDiscountUSD = 0;

            if (discountType && discountValue > 0) {
                if (discountType === 'percentage') {
                    discountAmountUSD = subtotalUSD * (discountValue / 100);
                } else if (discountType === 'fixed_usd') {
                    discountAmountUSD = Math.min(discountValue, subtotalUSD);
                } else if (discountType === 'fixed_pen') {
                    discountAmountUSD = Math.min(discountValue / 3.75, subtotalUSD);
                }
            }

            const customerDiscountType = document.getElementById('customer_discount_type')?.value;
            const customerDiscountValue = parseFloat(document.getElementById('customer_discount_value')?.value) || 0;
            
            if (customerDiscountType && customerDiscountValue > 0) {
                if (customerDiscountType === 'percentage') {
                    customerDiscountUSD = subtotalUSD * (customerDiscountValue / 100);
                } else if (customerDiscountType === 'fixed') {
                    customerDiscountUSD = Math.min(customerDiscountValue, subtotalUSD);
                }
            }

            const totalDiscountUSD = discountAmountUSD + customerDiscountUSD;
            const totalUSD = Math.max(0, subtotalUSD - totalDiscountUSD);

            updatePriceDisplay(pricePerNightUSD, nights, subtotalUSD, discountAmountUSD, customerDiscountUSD, totalUSD);
        }

        function updatePriceDisplay(pricePerNight, nights, subtotal, discount, customerDiscount, total) {
            if (document.getElementById('basePrice')) {
                document.getElementById('basePrice').textContent = `$${pricePerNight.toFixed(2)}`;
            }
            if (document.getElementById('nightCount')) {
                document.getElementById('nightCount').textContent = nights;
            }
            if (document.getElementById('subtotal')) {
                document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
            }
            
            const discountDisplay = document.getElementById('discountDisplay');
            if (discountDisplay) {
                if (discount > 0) {
                    document.getElementById('discountAmount').textContent = `-$${discount.toFixed(2)}`;
                    discountDisplay.style.display = 'block';
                } else {
                    discountDisplay.style.display = 'none';
                }
            }
            
            const customerDiscountDisplay = document.getElementById('customerDiscountDisplay');
            if (customerDiscountDisplay) {
                if (customerDiscount > 0) {
                    document.getElementById('customerDiscountAmount').textContent = `-$${customerDiscount.toFixed(2)}`;
                    customerDiscountDisplay.style.display = 'block';
                } else {
                    customerDiscountDisplay.style.display = 'none';
                }
            }
            
            if (document.getElementById('totalPrice')) {
                document.getElementById('totalPrice').textContent = `$${total.toFixed(2)}`;
            }
        }

        function resetPriceDisplay() {
            if (document.getElementById('basePrice')) document.getElementById('basePrice').textContent = '$0.00';
            if (document.getElementById('nightCount')) document.getElementById('nightCount').textContent = '0';
            if (document.getElementById('subtotal')) document.getElementById('subtotal').textContent = '$0.00';
            if (document.getElementById('totalPrice')) document.getElementById('totalPrice').textContent = '$0.00';
            if (document.getElementById('discountDisplay')) document.getElementById('discountDisplay').style.display = 'none';
            if (document.getElementById('customerDiscountDisplay')) document.getElementById('customerDiscountDisplay').style.display = 'none';
        }

        function formatCurrencyInput(amount) {
            return `$${amount.toFixed(2)} USD`;
        }
    </script>

    <!-- Edit Booking Modal -->
    <div id="editBookingModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h2>✏️ Editar Reserva</h2>
                <span class="close" onclick="closeEditBookingModal()">&times;</span>
            </div>
            <form method="POST" id="editBookingForm">
                <input type="hidden" id="edit_booking_id" name="edit_booking_id">
                <input type="hidden" name="edit_booking" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_room_id">🏨 Room</label>
                        <select id="edit_room_id" name="edit_room_id" required>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo $room['id']; ?>">
                                    Room <?php echo $room['room_number']; ?> - <?php echo $room['room_type']; ?> 
                                    ($<?php echo number_format($room['price'] ?? 0, 2); ?> USD)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_guest_name">👤 Guest Name</label>
                        <input type="text" id="edit_guest_name" name="edit_guest_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_check_in">📅 Check-in</label>
                        <input type="date" id="edit_check_in" name="edit_check_in" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_check_out">📅 Check-out</label>
                        <input type="date" id="edit_check_out" name="edit_check_out" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_total_price">💰 Total Price (USD)</label>
                        <input type="number" id="edit_total_price" name="edit_total_price" step="0.01" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_guest_email">📧 Email</label>
                        <input type="email" id="edit_guest_email" name="edit_guest_email" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_guest_phone">📱 Phone</label>
                        <input type="tel" id="edit_guest_phone" name="edit_guest_phone">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_passport_number">🛂 Passport</label>
                        <input type="text" id="edit_passport_number" name="edit_passport_number">
                    </div>
                    <div class="form-group">
                        <label for="edit_id_number">🆔 ID Number</label>
                        <input type="text" id="edit_id_number" name="edit_id_number">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_special_requests">📝 Special Requests</label>
                    <textarea id="edit_special_requests" name="edit_special_requests" rows="3"></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="deleteBooking()" class="btn" style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); margin-right: auto;">
                        🗑️ Delete Booking
                    </button>
                    <button type="button" onclick="closeEditBookingModal()" class="btn btn-cancel">❌ Cancel</button>
                    <button type="submit" class="btn btn-success">💾 Update Booking</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Extend Stay Modal -->
    <div id="extendStayModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2>📅 Extender Estadía</h2>
                <span class="close" onclick="closeExtendStayModal()">&times;</span>
            </div>
            <form method="POST" id="extendStayForm">
                <input type="hidden" id="extend_booking_id" name="extend_booking_id">
                <input type="hidden" name="extend_stay" value="1">
                
                <!-- Current Booking Information -->
                <div style="background: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 10px; margin-bottom: 25px;">
                    <h3 style="color: #60a5fa; margin-bottom: 15px; font-size: 18px;">📋 Reserva Actual</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <strong style="color: #94a3b8;">👤 Huésped:</strong>
                            <div id="current_guest_name" style="color: #e0e0e0; margin-top: 5px;">-</div>
                        </div>
                        <div>
                            <strong style="color: #94a3b8;">🏨 Habitación:</strong>
                            <div id="current_room_info" style="color: #e0e0e0; margin-top: 5px;">-</div>
                        </div>
                        <div>
                            <strong style="color: #94a3b8;">📅 Check-in:</strong>
                            <div id="current_checkin" style="color: #e0e0e0; margin-top: 5px;">-</div>
                        </div>
                        <div>
                            <strong style="color: #94a3b8;">📅 Check-out Actual:</strong>
                            <div id="current_checkout" style="color: #10b981; margin-top: 5px; font-weight: 600;">-</div>
                        </div>
                    </div>
                </div>
                
                <!-- Extension Details -->
                <div class="form-group">
                    <label for="new_checkout_date">📅 Nueva Fecha de Check-out</label>
                    <input type="date" id="new_checkout_date" name="new_checkout_date" onchange="calculateExtensionCost()" required>
                </div>
                
                <div class="form-group">
                    <label for="extend_payment_method">💳 Método de Pago</label>
                    <select id="extend_payment_method" name="extend_payment_method" onchange="togglePaymentOptions()" required>
                        <option value="">Seleccionar...</option>
                        <option value="pay_now">💰 Pagar Ahora</option>
                        <option value="pay_later">📅 Pagar Después</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="extend_discount_amount">🎁 Descuento por Noche (Soles)</label>
                    <input type="number" id="extend_discount_amount" name="extend_discount_amount" step="0.01" min="0" value="0" onchange="calculateExtensionCost()" placeholder="0.00">
                    <small style="color: #94a3b8; display: block; margin-top: 5px;">Descuento en S/ por cada noche adicional</small>
                </div>
                
                <!-- Payment Details (Pay Now) -->
                <div id="payment_details" style="display: none; background: rgba(16, 185, 129, 0.1); padding: 20px; border-radius: 10px; margin-bottom: 20px; border: 1px solid rgba(16, 185, 129, 0.3);">
                    <h3 style="color: #10b981; margin-bottom: 15px; font-size: 16px;">💰 Detalles de Pago</h3>
                    <div class="form-group">
                        <label for="extend_payment_type">Tipo de Pago</label>
                        <select id="extend_payment_type" name="extend_payment_type">
                            <option value="cash">💵 Efectivo</option>
                            <option value="credit_card">💳 Tarjeta de Crédito</option>
                            <option value="debit_card">💳 Tarjeta de Débito</option>
                            <option value="bank_transfer">🏦 Transferencia</option>
                            <option value="yape">📱 Yape</option>
                            <option value="plin">📱 Plin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="extend_payment_notes">📝 Notas de Pago</label>
                        <textarea id="extend_payment_notes" name="extend_payment_notes" rows="2" placeholder="Referencia de pago, número de transacción, etc."></textarea>
                    </div>
                </div>
                
                <!-- Payment Later Details -->
                <div id="payment_later_details" style="display: none; background: rgba(245, 158, 11, 0.1); padding: 20px; border-radius: 10px; margin-bottom: 20px; border: 1px solid rgba(245, 158, 11, 0.3);">
                    <h3 style="color: #f59e0b; margin-bottom: 15px; font-size: 16px;">📅 Pago Pendiente</h3>
                    <div class="form-group">
                        <label for="payment_due_date">Fecha Límite de Pago</label>
                        <input type="date" id="payment_due_date" name="payment_due_date">
                    </div>
                    <div class="form-group">
                        <label for="extend_payment_notes_later">📝 Notas</label>
                        <textarea id="extend_payment_notes_later" name="extend_payment_notes_later" rows="2" placeholder="Acuerdo de pago, método esperado, etc."></textarea>
                    </div>
                </div>
                
                <!-- Cost Summary -->
                <div style="background: rgba(96, 165, 250, 0.1); padding: 20px; border-radius: 10px; margin-bottom: 25px; border: 1px solid rgba(96, 165, 250, 0.3);">
                    <h3 style="color: #60a5fa; margin-bottom: 15px; font-size: 16px;">💰 Resumen de Costos</h3>
                    <div style="display: grid; gap: 12px;">
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                            <span style="color: #94a3b8;">🌙 Noches Adicionales:</span>
                            <strong style="color: #e0e0e0;" id="additional_nights">0</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                            <span style="color: #94a3b8;">💵 Precio por Noche:</span>
                            <strong style="color: #e0e0e0;" id="room_price_per_night">S/ 0.00</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                            <span style="color: #94a3b8;">📊 Subtotal:</span>
                            <strong style="color: #e0e0e0;" id="extension_subtotal">S/ 0.00</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                            <span style="color: #94a3b8;">🎁 Descuento:</span>
                            <strong style="color: #f59e0b;" id="extension_discount">S/ 0.00</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 15px 0; background: rgba(16, 185, 129, 0.1); margin: 10px -10px -10px; padding: 15px 10px; border-radius: 8px;">
                            <span style="color: #10b981; font-size: 18px; font-weight: 600;">💎 Total a Pagar:</span>
                            <strong style="color: #10b981; font-size: 20px;" id="extension_total">S/ 0.00</strong>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="closeExtendStayModal()" class="btn btn-cancel">❌ Cancelar</button>
                    <button type="submit" class="btn btn-success">✅ Confirmar Extensión</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Room Management Modal -->
    <div id="roomManagementModal" class="modal">
        <div class="modal-content" style="max-width: 550px;">
            <div class="modal-header">
                <h2 id="room_management_title">🧹 Room Management</h2>
                <span class="close" onclick="closeRoomManagementModal()">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" id="manage_room_id" name="manage_room_id">
                <input type="hidden" name="update_status" value="1">
                
                <!-- Room Info Display -->
                <div style="background: rgba(96, 165, 250, 0.1); padding: 20px; border-radius: 10px; margin-bottom: 25px; border: 1px solid rgba(96, 165, 250, 0.3);">
                    <h3 style="color: #60a5fa; margin-bottom: 15px; font-size: 16px;">🏨 Room Details</h3>
                    <div id="room_management_info" style="color: #e0e0e0;">
                        <!-- Room info will be populated by JavaScript -->
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="status">🏠 Room Status</label>
                    <select id="status" name="status" required>
                        <option value="clean">✅ Clean & Ready</option>
                        <option value="dirty">🧹 Needs Cleaning</option>
                        <option value="maintenance">🔧 Maintenance Required</option>
                        <option value="out_of_order">⚠️ Out of Order</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="cleaning_notes">📝 Notes</label>
                    <textarea id="cleaning_notes" name="cleaning_notes" rows="4" 
                              placeholder="Add notes about room condition, cleaning requirements, or maintenance issues..."></textarea>
                </div>
                
                <!-- Automatic Notifications Info -->
                <div style="background: rgba(16, 185, 129, 0.1); padding: 20px; border-radius: 10px; margin: 20px 0; border: 1px solid rgba(16, 185, 129, 0.3);">
                    <h4 style="color: #10b981; margin-bottom: 12px; font-size: 15px;">📱 Automatic Notifications</h4>
                    <div style="font-size: 13px; color: #94a3b8; line-height: 1.8;">
                        <p style="margin: 5px 0;"><strong style="color: #e0e0e0;">🧹 Dirty Room:</strong> Cleaning staff will be notified via WhatsApp</p>
                        <p style="margin: 5px 0;"><strong style="color: #e0e0e0;">🔧 Maintenance:</strong> Maintenance team will be alerted</p>
                        <p style="margin: 5px 0;"><strong style="color: #e0e0e0;">⚠️ Out of Order:</strong> Management will be notified immediately</p>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="closeRoomManagementModal()" class="btn btn-cancel">❌ Cancel</button>
                    <button type="submit" class="btn btn-success">💾 Update Room Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payment Status Modal -->
    <div id="paymentStatusModal" class="modal">
        <div class="modal-content" style="max-width: 550px;">
            <div class="modal-header">
                <h2>🏦 Cambiar Estado de Pago</h2>
                <span class="close" onclick="closePaymentStatusModal()">&times;</span>
            </div>
            
            <div style="padding: 5px 0 20px 0;">
                <div style="background: rgba(59, 130, 246, 0.1); padding: 20px; border-radius: 12px; border-left: 4px solid #60a5fa; margin-bottom: 20px;">
                    <p style="margin: 0 0 10px 0;"><strong style="color: #94a3b8;">Reserva:</strong> <span id="paymentModalBookingInfo" style="color: #60a5fa; font-weight: 600;"></span></p>
                    <p style="margin: 0;"><strong style="color: #94a3b8;">Monto Total:</strong> <span id="paymentModalAmount" style="color: #10b981; font-weight: 600;"></span></p>
                </div>
                
                <div style="margin: 25px 0;">
                    <label style="display: block; margin-bottom: 15px; font-weight: 600; color: #94a3b8;">Seleccionar Estado de Pago:</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        
                        <button type="button" class="payment-status-btn" data-status="paid" 
                                style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 18px; border: none; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; font-weight: 600; transition: all 0.3s ease;">
                            <span style="font-size: 1.3em;">✅</span>
                            <span>Pago Completo</span>
                        </button>
                        
                        <button type="button" class="payment-status-btn" data-status="pending"
                                style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: white; padding: 18px; border: none; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; font-weight: 600; transition: all 0.3s ease;">
                            <span style="font-size: 1.3em;">❌</span>
                            <span>No Pagado</span>
                        </button>
                        
                        <button type="button" class="payment-status-btn" data-status="partial"
                                style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 18px; border: none; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; font-weight: 600; transition: all 0.3s ease;">
                            <span style="font-size: 1.3em;">⚡</span>
                            <span>Pago Parcial</span>
                        </button>
                        
                        <button type="button" class="payment-status-btn" data-status="refunded"
                                style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: white; padding: 18px; border: none; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; font-weight: 600; transition: all 0.3s ease;">
                            <span style="font-size: 1.3em;">↩️</span>
                            <span>Reembolsado</span>
                        </button>
                        
                    </div>
                </div>
                
                <div id="paymentAmountSection" class="form-group" style="display: none;">
                    <label for="paymentAmount">Monto Pagado:</label>
                    <input type="number" id="paymentAmount" step="0.01" min="0" 
                           placeholder="Ingrese el monto pagado">
                </div>
                
                <div class="form-group">
                    <label for="paymentNotes">Notas (opcional):</label>
                    <textarea id="paymentNotes" rows="3" 
                              placeholder="Agregar notas sobre el pago..."></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="closePaymentStatusModal()" class="btn btn-cancel">
                        Cancelar
                    </button>
                    <button type="button" id="confirmPaymentStatus" class="btn btn-primary" disabled>
                        Confirmar Cambio
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Guest Information Modal -->
    <div id="guestInfoModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h2>📋 Información de Reserva</h2>
                <span class="close" onclick="closeGuestInfoModal()">&times;</span>
            </div>
            <div id="guestInfoContent" style="color: #e0e0e0;">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div style="margin-top: 25px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: center;">
                <button type="button" onclick="editBooking()" class="btn btn-primary">✏️ Editar Reserva</button>
                <button type="button" onclick="extendStay()" class="btn" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">📅 Extender Estadía</button>
                <button type="button" onclick="openPaymentStatusModal()" class="btn btn-info">💳 Estado de Pago</button>
                <button type="button" onclick="generateReceipt()" class="btn" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">🧾 Ver Recibo</button>
                <button type="button" onclick="printReceipt()" class="btn btn-success">🖨️ Imprimir</button>
                <button type="button" onclick="downloadPDF()" class="btn" style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);">📄 PDF</button>
                <button type="button" onclick="emailReceipt()" class="btn" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">📧 Email</button>
                <button type="button" onclick="closeGuestInfoModal()" class="btn btn-cancel">🚪 Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Room Status Management Modal -->
    <div id="roomStatusModal" class="modal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h2>🏨 Room Status Management</h2>
                <span class="close" onclick="closeRoomStatusModal()">&times;</span>
            </div>
            
            <div style="margin-bottom: 25px; padding: 20px; background: rgba(59, 130, 246, 0.1); border-radius: 12px; border-left: 4px solid #60a5fa;">
                <strong id="roomStatusModalTitle" style="color: #60a5fa; font-size: 18px;">Room 101</strong>
                <div style="font-size: 14px; color: #94a3b8; margin-top: 8px;">
                    Current Status: <span id="currentRoomStatus" style="font-weight: bold; color: #60a5fa;"></span>
                </div>
            </div>

            <div class="form-group">
                <label style="display: block; margin-bottom: 10px; font-weight: 600;">Change Status To:</label>
                <select id="newRoomStatus" style="width: 100%; padding: 12px; background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(96, 165, 250, 0.3); border-radius: 10px; color: #fff; font-size: 14px;">
                    <option value="clean">✨ Clean & Ready</option>
                    <option value="dirty">🧹 Needs Cleaning</option>
                    <option value="maintenance">🔧 Under Maintenance</option>
                    <option value="out_of_order">⚠️ Out of Order</option>
                </select>
            </div>

            <div class="form-group">
                <label style="display: block; margin-bottom: 10px; font-weight: 600;">📅 Apply To:</label>
                <select id="statusDateMode" onchange="toggleStatusDateFields()" style="width: 100%; padding: 12px; background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(96, 165, 250, 0.3); border-radius: 10px; color: #fff; font-size: 14px;">
                    <option value="single">Single Day</option>
                    <option value="range">Date Range</option>
                    <option value="permanent">Permanent (All Future Dates)</option>
                </select>
            </div>

            <div id="statusSingleDateField" class="form-group">
                <label style="display: block; margin-bottom: 10px; font-weight: 600;">📆 Date:</label>
                <input type="date" id="statusSingleDate" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 12px; background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(96, 165, 250, 0.3); border-radius: 10px; color: #fff; font-size: 14px;">
            </div>

            <div id="statusDateRangeFields" class="form-group" style="display: none;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 10px; font-weight: 600;">From:</label>
                        <input type="date" id="statusStartDate" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 12px; background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(96, 165, 250, 0.3); border-radius: 10px; color: #fff; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 10px; font-weight: 600;">To:</label>
                        <input type="date" id="statusEndDate" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" style="width: 100%; padding: 12px; background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(96, 165, 250, 0.3); border-radius: 10px; color: #fff; font-size: 14px;">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label style="display: block; margin-bottom: 10px; font-weight: 600;">Notes (Optional):</label>
                <textarea id="statusChangeNotes" placeholder="Add any notes about this status change..." 
                          style="width: 100%; height: 80px; padding: 12px; background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(96, 165, 250, 0.3); border-radius: 10px; color: #fff; font-size: 14px; resize: vertical;"></textarea>
            </div>

            <div class="modal-footer">
                <button onclick="closeRoomStatusModal()" class="btn btn-cancel">Cancel</button>
                <button onclick="updateRoomStatus()" class="btn btn-primary">Update Status</button>
            </div>
        </div>
    </div>

    <!-- Quick Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>📝 Quick Booking</h3>
                <span class="close" onclick="closeBookingModal()">&times;</span>
            </div>
            <form method="POST">
                <!-- Main booking information - Multiple Rooms Section -->
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <label style="font-size: 1.1em; font-weight: bold; color: #495057;">🏨 Room Selection</label>
                        <button type="button" onclick="addRoomSelection()" class="btn btn-sm" style="background: #28a745; color: white; padding: 5px 15px; font-size: 0.9em;">
                            ➕ Add Room
                        </button>
                    </div>
                    
                    <div id="roomsContainer">
                        <!-- First room selection (always present) -->
                        <div class="room-selection-item" id="room-item-1" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid #007bff;">
                            <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 10px;">
                                <h5 style="margin: 0; color: #495057;">Room 1</h5>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 15px; align-items: start;">
                                <div class="form-group">
                                    <select class="room-select" name="room_ids[]" required onchange="updateGuestOptions(); calculateTotal();" data-room-index="1">
                                        <option value="">Select Room</option>
                                        <?php foreach ($rooms as $room): ?>
                                            <option value="<?php echo $room['id']; ?>">
                                                Room <?php echo $room['room_number']; ?> - <?php echo $room['room_type']; ?> 
                                                ($<?php echo number_format($room['price'] ?? 0, 2); ?> USD / S/ <?php echo number_format(($room['price'] ?? 0) * 3.75, 2); ?> PEN per night)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Guest count for this room -->
                                <div class="form-group" style="width: 120px;">
                                    <label style="font-size: 0.9em;">👥 Guests</label>
                                    <select class="guests-select" name="room_guests[]" onchange="calculateTotal()" style="font-size: 0.9em;">
                                        <option value="1">1 Guest</option>
                                        <option value="2" selected>2 Guests</option>
                                        <option value="3">3 Guests</option>
                                        <option value="4">4 Guests</option>
                                        <option value="5">5 Guests</option>
                                        <option value="6">6 Guests</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Room photo previews -->
                            <?php foreach ($rooms as $room): ?>
                                <?php if (isset($roomPhotos[$room['id']])): ?>
                                    <div class="room-preview" id="room-preview-<?php echo $room['id']; ?>-1" style="margin-top: 10px; display: none;">
                                        <img src="<?php echo $roomPhotos[$room['id']]; ?>" alt="Habitación <?php echo $room['room_number']; ?>" style="max-width: 200px; border-radius: 5px;">
                                        <p style="font-size: 0.9em; color: #666; margin-top: 5px;">📸 Room preview</p>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Legacy single room field for backward compatibility (hidden) -->
                <input type="hidden" id="room_id" name="room_id" value="">
                <input type="hidden" id="guest_count" name="guest_count" value="2">
                
                <!-- All main fields in one compact 5-column row -->
                <div style="display: grid; grid-template-columns: 1fr 1fr 0.8fr 1fr 0.8fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label for="check_in_date" style="font-size: 0.9em;">📅 Check-in</label>
                        <input type="date" id="check_in_date" name="check_in_date" required 
                               min="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" style="font-size: 0.9em;" onchange="calculateTotal()">
                    </div>
                    <div class="form-group">
                        <label for="check_out_date" style="font-size: 0.9em;">📅 Check-out</label>
                        <input type="date" id="check_out_date" name="check_out_date" required
                               min="<?php echo date('Y-m-d'); ?>" style="font-size: 0.9em;" onchange="calculateTotal()">
                    </div>
                    <div class="form-group">
                        <label for="guest_name" style="font-size: 0.9em;">👤 Guest Name</label>
                        <input type="text" id="guest_name_quick" name="guest_name" required 
                               placeholder="Full name" style="font-size: 0.9em;">
                    </div>
                    <div class="form-group">
                        <label for="booking_currency" style="font-size: 0.9em;">💱 Currency</label>
                        <select id="booking_currency" name="booking_currency" onchange="changeCurrency()" style="font-size: 0.9em;">
                            <option value="USD">🇺🇸 USD</option>
                            <option value="PEN">🇵🇪 PEN</option>
                        </select>
                    </div>
                </div>
                
                <!-- Total guests display -->
                <div style="text-align: center; margin-bottom: 15px; padding: 10px; background: #e3f2fd; border-radius: 5px; color: #1976d2;">
                    <strong>👥 Total Guests: <span id="totalGuestsDisplay">2</span></strong>
                </div>
                
                <!-- Guest Management Section -->
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <label style="font-size: 1.1em; font-weight: bold; color: #495057;">👥 Guest Information</label>
                        <button type="button" onclick="addGuestInfo()" class="btn btn-sm" style="background: #17a2b8; color: white; padding: 5px 15px; font-size: 0.9em;">
                            ➕ Add Guest
                        </button>
                    </div>
                    
                    <div id="guestsContainer">
                        <!-- Primary guest (always present) -->
                        <div class="guest-info-item" id="guest-item-1" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid #17a2b8;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h5 style="margin: 0; color: #495057;">Primary Guest</h5>
                                <span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8em;">Main Contact</span>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px;">
                                <div class="form-group">
                                    <label style="font-size: 0.9em;">👤 Full Name</label>
                                    <input type="text" class="guest-name" name="guest_names[]" required 
                                           placeholder="Full name" style="font-size: 0.9em;" data-guest-index="1">
                                </div>
                                <div class="form-group">
                                    <label style="font-size: 0.9em;">📧 Email</label>
                                    <input type="email" class="guest-email" name="guest_emails[]" required 
                                           placeholder="guest@email.com" style="font-size: 0.9em;" data-guest-index="1">
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label style="font-size: 0.9em;">📱 Phone</label>
                                    <input type="tel" class="guest-phone" name="guest_phones[]" 
                                           placeholder="+51 999 999 999" style="font-size: 0.9em;"
                                           pattern="[\+]?[0-9\s\-\(\)]+" data-guest-index="1">
                                </div>
                                <div class="form-group">
                                    <label style="font-size: 0.9em;">🛂 Passport</label>
                                    <input type="text" class="guest-passport" name="guest_passports[]" 
                                           placeholder="A12345678" style="font-size: 0.9em;"
                                           pattern="[A-Z0-9]+" data-guest-index="1">
                                </div>
                                <div class="form-group">
                                    <label style="font-size: 0.9em;">🆔 ID Number</label>
                                    <input type="text" class="guest-id" name="guest_ids[]" 
                                           placeholder="12345678" style="font-size: 0.9em;"
                                           pattern="[A-Z0-9\-]+" data-guest-index="1">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Exchange rate info -->
                <div style="text-align: center; margin-bottom: 15px; color: #6c757d; font-size: 0.85em;">
                    💱 Exchange Rate: 1 USD = 3.75 PEN
                </div>
                
                <!-- Legacy single guest fields for backward compatibility (hidden) -->
                <input type="hidden" id="guest_name_legacy" name="guest_name" value="">
                <input type="hidden" id="guest_email" name="guest_email" value="">
                <input type="hidden" id="guest_phone" name="guest_phone" value="">
                <input type="hidden" id="passport_number" name="passport_number" value="">
                <input type="hidden" id="id_number" name="id_number" value="">
                
                <!-- Compact Pricing Section -->
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
                    <h4 style="color: #495057; margin-bottom: 12px; font-size: 1.1em;">💰 Pricing & Discounts</h4>
                    
                    <!-- Everything in one horizontal layout -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr; gap: 12px; align-items: start;">
                        <!-- Price Summary (Compact) -->
                        <div id="priceDisplay" style="background: white; padding: 10px; border-radius: 5px; border: 1px solid #dee2e6; font-size: 0.85em;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                                <span>Base/night:</span>
                                <span id="basePrice">$0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                                <span>Nights:</span>
                                <span id="nightCount">0</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                                <span>Subtotal:</span>
                                <span id="subtotal">$0.00</span>
                            </div>
                            <div id="discountDisplay" style="display: none; color: #28a745; margin-bottom: 3px;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Discount:</span>
                                    <span id="discountAmount">-$0.00</span>
                                </div>
                            </div>
                            <div id="customerDiscountDisplay" style="display: none; color: #ff6b6b; margin-bottom: 3px;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Customer Discount:</span>
                                    <span id="customerDiscountAmount">-$0.00</span>
                                </div>
                            </div>
                            <hr style="margin: 5px 0;">
                            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 0.95em;">
                                <span>Total:</span>
                                <span id="totalPrice">$0.00</span>
                            </div>
                        </div>
                        
                        <!-- Customer Discount -->
                        <div class="form-group">
                            <label style="font-size: 0.9em; margin-bottom: 5px; display: block;">
                                🏷️ Customer Discount
                            </label>
                            <select id="customer_discount_type" onchange="toggleCustomerDiscount()" style="font-size: 0.8em; padding: 4px; margin-bottom: 3px; width: 100%;">
                                <option value="">No Discount</option>
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount ($)</option>
                            </select>
                            <input type="number" id="customer_discount_value" 
                                   step="0.01" min="0" max="100" placeholder="e.g., 10 for 10% or $10" disabled
                                   onchange="calculateTotal()" style="font-size: 0.8em; padding: 4px; margin-bottom: 3px; width: 100%;">
                            <input type="text" id="customer_discount_reason" 
                                   placeholder="Reason (e.g., Loyal customer)" disabled
                                   style="font-size: 0.8em; padding: 4px; width: 100%;">
                        </div>
                        
                        <!-- Custom Pricing -->
                        <div class="form-group">
                            <label style="font-size: 0.9em; margin-bottom: 5px; display: block;">
                                💲 Custom Pricing
                            </label>
                            <select id="custom_price_type" onchange="toggleCustomPricing()" style="font-size: 0.8em; padding: 4px; margin-bottom: 3px; width: 100%;">
                                <option value="">Use Calculated Price</option>
                                <option value="override">Override Total Price</option>
                                <option value="adjustment">Price Adjustment (+/-)</option>
                            </select>
                            
                            <!-- Override Price Section -->
                            <div id="override_price_section" style="display: none;">
                                <input type="number" id="override_price_usd" name="override_price_usd" 
                                       step="0.01" min="0" placeholder="Total USD price" disabled 
                                       onchange="calculateTotal()" style="font-size: 0.8em; padding: 4px; margin-bottom: 3px; width: 100%;">
                                <input type="number" id="override_price_pen" name="override_price_pen" 
                                       step="0.01" min="0" placeholder="Total PEN price" disabled 
                                       onchange="calculateTotal()" style="font-size: 0.8em; padding: 4px; width: 100%;">
                            </div>
                            
                            <!-- Price Adjustment Section -->
                            <div id="adjustment_price_section" style="display: none;">
                                <select id="adjustment_type" style="font-size: 0.8em; padding: 4px; margin-bottom: 3px; width: 100%;" onchange="calculateTotal()">
                                    <option value="add">Add to Price (+)</option>
                                    <option value="subtract">Subtract from Price (-)</option>
                                </select>
                                <input type="number" id="adjustment_amount" 
                                       step="0.01" min="0" placeholder="Adjustment amount" 
                                       onchange="calculateTotal()" style="font-size: 0.8em; padding: 4px; margin-bottom: 3px; width: 100%;">
                                <input type="text" id="adjustment_reason" 
                                       placeholder="Reason (e.g., Weekend surcharge, Special rate)" 
                                       style="font-size: 0.8em; padding: 4px; width: 100%;">
                            </div>
                        </div>
                        
                        <!-- Discount Type -->
                        <div class="form-group">
                            <label for="discount_type" style="font-size: 0.9em;">🏷️ Discount Type</label>
                            <select id="discount_type" name="discount_type" onchange="toggleDiscountInput()" style="font-size: 0.85em;">
                                <option value="">No discount</option>
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed_usd">Fixed (USD)</option>
                                <option value="fixed_pen">Fixed (PEN)</option>
                            </select>
                        </div>
                        
                        <!-- Discount Value -->
                        <div class="form-group">
                            <label for="discount_value" style="font-size: 0.9em;">💰 Value</label>
                            <input type="number" id="discount_value" name="discount_value" 
                                   step="0.01" min="0" placeholder="0" disabled onchange="calculateTotal()"
                                   style="font-size: 0.85em;">
                        </div>
                        
                        <!-- Discount Reason -->
                        <div class="form-group">
                            <label for="discount_reason" style="font-size: 0.9em;">📝 Reason</label>
                            <input type="text" id="discount_reason" name="discount_reason" 
                                   placeholder="e.g., Frequent guest" disabled
                                   style="font-size: 0.85em;">
                        </div>
                    </div>
                </div>
                
                <!-- Payment Information Section -->
                <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #28a745;">
                    <h4 style="color: #155724; margin-bottom: 12px; font-size: 1.1em;">💳 Payment Information</h4>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; align-items: end;">
                        <div class="form-group">
                            <label for="payment_status" style="font-size: 0.9em;">Payment Status</label>
                            <select id="payment_status" name="payment_status" onchange="togglePaymentFields()" style="font-size: 0.9em;">
                                <option value="pending">💳 Pending Payment</option>
                                <option value="paid">✅ Mark as Paid</option>
                                <option value="partial">⚡ Partial Payment</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="payment_method_group" style="display: none;">
                            <label for="payment_method" style="font-size: 0.9em;">Payment Method</label>
                            <select id="payment_method" name="payment_method" style="font-size: 0.9em;">
                                <option value="">Select Method</option>
                                <option value="cash">💵 Cash</option>
                                <option value="card">💳 Credit/Debit Card</option>
                                <option value="transfer">🏦 Bank Transfer</option>
                                <option value="paypal">📱 PayPal</option>
                                <option value="crypto">₿ Cryptocurrency</option>
                                <option value="other">🔄 Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="paid_amount_group" style="display: none;">
                            <label for="paid_amount" style="font-size: 0.9em;">Amount Paid</label>
                            <input type="number" id="paid_amount" name="paid_amount" 
                                   step="0.01" min="0" placeholder="0.00" 
                                   style="font-size: 0.9em;">
                        </div>
                    </div>
                </div>

                <!-- Hidden fields for form submission -->
                <input type="hidden" id="currency_type_hidden" name="currency_type" value="USD">
                <input type="hidden" id="custom_pricing_type_hidden" name="custom_pricing_type" value="">
                <input type="hidden" id="custom_pricing_value_hidden" name="custom_pricing_value" value="">
                <input type="hidden" id="custom_pricing_reason_hidden" name="custom_pricing_reason" value="">
                
                <!-- Terms and Conditions Agreement -->
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0; border: 2px solid #e9ecef;">
                    <div style="display: flex; align-items: start; gap: 10px;">
                        <input type="checkbox" id="terms_accepted" name="terms_accepted" required 
                               style="margin-top: 4px; transform: scale(1.2);" onchange="toggleCreateBookingButton()">
                        <label for="terms_accepted" style="font-size: 0.9em; line-height: 1.4; color: #495057;">
                            <strong>📋 Agreement Required:</strong> I acknowledge that I have read, understood, and agree to be legally bound by the 
                            <a href="terms_and_conditions.php" target="_blank" style="color: #007bff; text-decoration: underline;">
                                Terms & Conditions, Guest Responsibility Agreement, Property Damage & Reputation Protection Policy
                            </a>. 
                            I accept full financial responsibility for any damages to hotel property during my stay, agree to maintain appropriate behavior standards, and acknowledge legal liability for false or defamatory reviews.
                        </label>
                    </div>
                    <div style="margin-top: 10px; font-size: 0.8em; color: #6c757d; padding-left: 30px;">
                        ⚠️ <strong>Important:</strong> Checking this box confirms your legal agreement to all hotel policies including damage liability, guest conduct standards, and reputation protection from false reviews.
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="quick_booking" class="btn btn-success" id="create_booking_btn" disabled onclick="return validateGuestInfo()">💾 Create Booking</button>
                    <button type="button" onclick="closeBookingModal()" class="btn" style="background: #6c757d; color: white;">❌ Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal" style="display: none; align-items: center; justify-content: center;">
        <div class="modal-content" style="max-width: 90%; max-height: 90vh; padding: 0; background: transparent; box-shadow: none;">
            <div style="position: relative; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 20px; overflow: hidden;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 2px solid rgba(96, 165, 250, 0.3);">
                    <h3 id="imageModalTitle" style="color: #60a5fa; margin: 0;">Room Image</h3>
                    <button onclick="closeImageModal()" class="close" style="background: none; border: none; font-size: 32px; cursor: pointer; color: #94a3b8; line-height: 1;">&times;</button>
                </div>
                <div style="padding: 20px; display: flex; justify-content: center; align-items: center;">
                    <img id="imageModalImg" src="" alt="Room Image" style="max-width: 100%; max-height: 70vh; border-radius: 10px; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>
</body>
</html>
