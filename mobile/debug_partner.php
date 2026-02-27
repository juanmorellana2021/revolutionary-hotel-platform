<?php
$pdo = new PDO('mysql:host=localhost;dbname=hotel_booking_system;charset=utf8mb4','hoteluser','hotelpass123');
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

echo "=== PARTNER 5 ===\n";
$r = $pdo->query('SELECT id,email,business_name FROM aini_partner_businesses WHERE id=5');
print_r($r->fetch());

echo "\n=== HOTELS partner_business_id=5 ===\n";
$r2 = $pdo->query('SELECT id,name,email,partner_business_id,status,is_active FROM hotel_properties WHERE partner_business_id=5');
print_r($r2->fetchAll());

echo "\n=== HOTELS email=manager@hotel.com ===\n";
$st = $pdo->prepare('SELECT id,name,email,partner_business_id,status FROM hotel_properties WHERE email=?');
$st->execute(['manager@hotel.com']);
print_r($st->fetchAll());
