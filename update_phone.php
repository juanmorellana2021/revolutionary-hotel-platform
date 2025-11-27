<?php
$pdo = new PDO('mysql:host=localhost;dbname=hotel_booking_system', 'hoteluser', 'hotelpass123');
$stmt = $pdo->prepare("UPDATE ainitravel_users SET phone = ? WHERE id = 2");
$stmt->execute(['+51938118436']);
echo "Updated user 2 with phone +51938118436\n";
$stmt = $pdo->prepare("SELECT id, phone, aini_coins FROM ainitravel_users WHERE id = 2");
$stmt->execute();
print_r($stmt->fetch(PDO::FETCH_ASSOC));
?>
