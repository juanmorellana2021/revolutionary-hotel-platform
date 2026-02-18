<?php
session_start();
require_once 'db_connection_pdo.php';
require_once 'AiniTravelEmailSender.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

try {
    // Get form data
    $user_id = $_SESSION['user_id'];
    $hotel_id = intval($_POST['hotel_id'] ?? 0);
    $guest_name = trim($_POST['guest_name'] ?? '');
    $guest_email = trim($_POST['guest_email'] ?? '');
    $guest_phone = trim($_POST['guest_phone'] ?? '');
    $check_in = trim($_POST['check_in'] ?? '');
    $check_out = trim($_POST['check_out'] ?? '');
    $special_requests = trim($_POST['special_requests'] ?? '');
    $selected_rooms_json = trim($_POST['selected_rooms_json'] ?? '');

    // Validation
    if (!$hotel_id || !$check_in || !$check_out || !$guest_name || !$guest_email || !$guest_phone) {
        throw new Exception('Faltan campos requeridos');
    }

    // Parse selected rooms
    $selected_rooms = json_decode($selected_rooms_json, true);
    if (empty($selected_rooms)) {
        throw new Exception('Debes seleccionar al menos una habitación');
    }

    // Validate dates
    $checkin_date = new DateTime($check_in);
    $checkout_date = new DateTime($check_out);
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    if ($checkin_date < $today) {
        throw new Exception('La fecha de check-in no puede ser en el pasado');
    }

    if ($checkout_date <= $checkin_date) {
        throw new Exception('La fecha de check-out debe ser después del check-in');
    }

    $nights = $checkin_date->diff($checkout_date)->days;

    // Get hotel details
    $stmt = $pdo->prepare("SELECT * FROM hotel_properties WHERE id = ?");
    $stmt->execute([$hotel_id]);
    $hotel = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$hotel) {
        throw new Exception('Hotel no encontrado');
    }

    // Calculate total price across all rooms
    $grand_total = 0;
    $total_rooms = 0;
    $currency = $selected_rooms[0]['currency'] ?? 'USD';
    
    foreach ($selected_rooms as $room) {
        $room_subtotal = floatval($room['price']) * $nights * intval($room['quantity']);
        $grand_total += $room_subtotal;
        $total_rooms += intval($room['quantity']);
    }

    $aini_coins = round($grand_total * 0.2); // 20% of price in coins
    $currency_symbol = $currency === 'PEN' ? 'S/' : '$';

    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Insert booking for each room type
        $booking_ids = [];
        $booking_reference = 'AINI-' . strtoupper(substr(uniqid(), -8));
        
        foreach ($selected_rooms as $room) {
            $room_type = $room['room_type'];
            $quantity = intval($room['quantity']);
            $price_per_night = floatval($room['price']);
            $total_price = $price_per_night * $nights * $quantity;
            
            $stmt = $pdo->prepare("
                INSERT INTO guest_bookings (
                    booking_reference, user_id, hotel_id, check_in, check_out, nights,
                    guests, room_type, room_count,
                    price_per_night, total_price, currency,
                    aini_coins_earned, status, payment_status, booking_source,
                    guest_name, guest_email, guest_phone, special_requests,
                    created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, 'pending', 'pending', 'website',
                    ?, ?, ?, ?,
                    NOW()
                )
            ");

            $stmt->execute([
                $booking_reference,
                $user_id,
                $hotel_id,
                $check_in,
                $check_out,
                $nights,
                $quantity * 2, // Default 2 guests per room
                $room_type,
                $quantity,
                $price_per_night,
                $total_price,
                $currency,
                round($total_price * 0.2), // Coins for this room type
                $guest_name,
                $guest_email,
                $guest_phone,
                $special_requests ?: null
            ]);

            $booking_ids[] = $pdo->lastInsertId();
        }

        // Award AiNi Coins to user
        $stmt = $pdo->prepare("UPDATE ainitravel_users SET aini_coins = aini_coins + ?, total_bookings = total_bookings + 1 WHERE id = ?");
        $stmt->execute([$aini_coins, $user_id]);

        // Commit transaction
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

    // Build room details for email
    $room_details_html = '';
    foreach ($selected_rooms as $room) {
        $room_total = floatval($room['price']) * $nights * intval($room['quantity']);
        $room_details_html .= "
            <tr>
                <td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$room['room_type']}</td>
                <td style='padding: 8px; border-bottom: 1px solid #ddd; text-align: center;'>{$room['quantity']}</td>
                <td style='padding: 8px; border-bottom: 1px solid #ddd; text-align: right;'>{$currency_symbol}" . number_format($room['price'], 2) . "</td>
                <td style='padding: 8px; border-bottom: 1px solid #ddd; text-align: right;'>{$currency_symbol}" . number_format($room_total, 2) . "</td>
            </tr>";
    }

    // Get room type for emails (first room type)
    $room_type_display = $selected_rooms[0]['room_type'];

    // Send email notification to hotel
    $to_hotel = $hotel['email'];
    $subject = "🔔 Please Confirm New Reservation - " . $booking_reference;
    
    $currency_symbol = $currency === 'PEN' ? 'S/' : '$';
    
    $message_hotel = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #667eea; }
        .label { font-weight: bold; color: #667eea; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🏨 New Reservation Received - Please Confirm</h1>
            <p style='font-size: 24px; font-weight: bold; margin: 10px 0;'>" . $booking_reference . "</p>
        </div>
        <div class='content'>
            <p>Hello " . htmlspecialchars($hotel['contact_person']) . ",</p>
            <p><strong>You have received a new reservation through AiNi Travel. Please confirm or decline this reservation as soon as possible.</strong></p>
            
            <div class='info-box'>
                <p><span class='label'>📅 Check-in:</span> " . date('d/m/Y', strtotime($check_in)) . "</p>
                <p><span class='label'>📅 Check-out:</span> " . date('d/m/Y', strtotime($check_out)) . "</p>
                <p><span class='label'>🌙 Noches:</span> " . $nights . "</p>
                <p><span class='label'>🛏️ Habitaciones:</span> " . $total_rooms . " habitación" . ($total_rooms > 1 ? 'es' : '') . "</p>
            </div>
            
            <div class='info-box'>
                <h3 style='color: #667eea; margin-top: 0;'>🛏️ Desglose de Habitaciones</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <thead>
                        <tr style='background: #f0f0f0;'>
                            <th style='padding: 8px; text-align: left;'>Tipo</th>
                            <th style='padding: 8px; text-align: center;'>Cantidad</th>
                            <th style='padding: 8px; text-align: right;'>Precio/Noche</th>
                            <th style='padding: 8px; text-align: right;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        " . $room_details_html . "
                    </tbody>
                </table>
            </div>
            
            <div class='info-box'>
                <h3 style='color: #667eea; margin-top: 0;'>👤 Información del Huésped</h3>
                <p><span class='label'>Nombre:</span> " . htmlspecialchars($guest_name) . "</p>
                <p><span class='label'>Email:</span> " . htmlspecialchars($guest_email) . "</p>
                <p><span class='label'>Teléfono:</span> " . htmlspecialchars($guest_phone) . "</p>
                " . ($id_number ? "<p><span class='label'>ID:</span> " . htmlspecialchars($id_number) . "</p>" : "") . "
            </div>
            
            " . ($special_requests ? "
            <div class='info-box'>
                <h3 style='color: #667eea; margin-top: 0;'>💬 Solicitudes Especiales</h3>
                <p>" . nl2br(htmlspecialchars($special_requests)) . "</p>
            </div>
            " : "") . "
            
            <div class='info-box'>
                <h3 style='color: #667eea; margin-top: 0;'>💰 Detalles de Pago</h3>
                <p><span class='label'>Total:</span> " . $currency_symbol . number_format($grand_total, 2) . " " . $currency . "</p>
                <p><span class='label'>Tu ganancia (88%):</span> " . $currency_symbol . number_format($grand_total * 0.88, 2) . "</p>
                <p><span class='label'>Comisión AiNi (12%):</span> " . $currency_symbol . number_format($grand_total * 0.12, 2) . "</p>
                <p style='color: #666; font-size: 14px;'>El pago se realizará en el hotel al momento del check-in</p>
            </div>
            
            <p style='background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;'>
                <strong>⚠️ ACTION REQUIRED:</strong> Please review and confirm this reservation as soon as possible.
            </p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='https://ainitravel.com/partners/view_bookings.php?id=" . $hotel_id . "' 
                   style='display: inline-block; background: #28a745; color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 10px;'>
                    ✅ Confirm Reservation Now
                </a>
                <br>
                <p style='margin: 10px 0; color: #666;'>Or <a href='https://ainitravel.com/partners/login.php' style='color: #667eea; text-decoration: none; font-weight: bold;'>log in to your partner dashboard</a> to view all reservations</p>
            </div>
        </div>
        <div class='footer'>
            <p>AiNi Travel - Your Hotel Management Platform</p>
            <p>📧 reservations@ainitravel.com | 📱 +51 938 118 436</p>
            <p><a href='https://ainitravel.com/partners/login.php' style='color: #667eea;'>Partner Dashboard Login</a></p>
        </div>
    </div>
</body>
</html>
    ";

    // Send emails using PHPMailer
    $emailSender = new AiniTravelEmailSender($pdo);
    
    // Send email to hotel
    $emailSender->sendEmail($to_hotel, $subject, $message_hotel);

    // Send confirmation email to guest
    $to_guest = $guest_email;
    $subject_guest = "📝 Reserva Recibida - " . $booking_reference;
    
    $message_guest = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #667eea; }
        .label { font-weight: bold; color: #667eea; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>📝 Reserva Recibida</h1>
            <p style='font-size: 24px; font-weight: bold; margin: 10px 0;'>" . $booking_reference . "</p>
            <p style='font-size: 14px; margin: 0;'>Pendiente de Confirmación</p>
        </div>
        <div class='content'>
            <p>Hola " . htmlspecialchars($guest_name) . ",</p>
            <p>¡Gracias por reservar con AiNi Travel! Tu solicitud de reserva ha sido enviada a:</p>
            
            <div class='info-box'>
                <h2 style='margin-top: 0; color: #667eea;'>" . htmlspecialchars($hotel['name']) . "</h2>
                <p>📍 " . htmlspecialchars($hotel['location']) . "</p>
                <p>📞 " . htmlspecialchars($hotel['phone']) . "</p>
                <p>✉️ " . htmlspecialchars($hotel['email']) . "</p>
            </div>
            
            <div class='info-box'>
                <h3 style='color: #667eea; margin-top: 0;'>📋 Detalles de tu Reserva</h3>
                <p><span class='label'>Referencia:</span> " . $booking_reference . "</p>
                <p><span class='label'>Check-in:</span> " . date('d/m/Y', strtotime($check_in)) . "</p>
                <p><span class='label'>Check-out:</span> " . date('d/m/Y', strtotime($check_out)) . "</p>
                <p><span class='label'>Noches:</span> " . $nights . "</p>
                <p><span class='label'>Habitaciones:</span> " . $total_rooms . " habitación" . ($total_rooms > 1 ? 'es' : '') . "</p>
                <table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>
                    <thead>
                        <tr style='background: #f0f0f0;'>
                            <th style='padding: 8px; text-align: left;'>Tipo</th>
                            <th style='padding: 8px; text-align: center;'>Cantidad</th>
                            <th style='padding: 8px; text-align: right;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        " . $room_details_html . "
                    </tbody>
                    <tfoot>
                        <tr style='background: #f0f0f0; font-weight: bold;'>
                            <td colspan='2' style='padding: 8px; text-align: right;'>Total:</td>
                            <td style='padding: 8px; text-align: right;'>" . $currency_symbol . number_format($grand_total, 2) . "</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class='info-box' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;'>
                <h3 style='margin-top: 0;'>🪙 ¡Ganaste AiNi Coins!</h3>
                <p style='font-size: 32px; font-weight: bold; margin: 10px 0;'>" . $aini_coins . " coins</p>
                <p>Úsalos en tu próxima reserva</p>
            </div>
            
            <p style='background: #d1ecf1; padding: 15px; border-radius: 8px; border-left: 4px solid #0c5460; color: #0c5460;'>
                <strong>⏳ Esperando Confirmación:</strong><br>
                Tu reserva está pendiente de aprobación por parte del hotel. 
                Recibirás un email de confirmación una vez que el hotel apruebe tu reserva.
                El hotel suele responder en menos de 24 horas.
            </p> 
                El pago se realiza directamente en el hotel.
            </p>
            
            <p style='background: #d4edda; padding: 15px; border-radius: 8px; border-left: 4px solid #155724; color: #155724;'>
                <strong>✓ Cancelación Gratuita:</strong><br>
                Puedes cancelar hasta 24 horas antes del check-in sin cargo.
            </p>
        </div>
        <div class='footer'>
            <p>¿Necesitas ayuda? Contáctanos</p>
            <p>📧 support@ainitravel.com | 📱 +51 938 118 436</p>
            <p style='margin-top: 20px;'>AiNi Travel - Viaja más, gana más</p>
        </div>
    </div>
</body>
</html>
    ";

    // Send email to guest
    $emailSender->sendEmail($to_guest, $subject_guest, $message_guest);

    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Reserva creada exitosamente',
        'booking_ids' => $booking_ids,
        'booking_reference' => $booking_reference,
        'aini_coins_earned' => $aini_coins,
        'total_price' => $grand_total,
        'currency' => $currency
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
