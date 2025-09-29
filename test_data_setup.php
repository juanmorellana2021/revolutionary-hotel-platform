<?php
// test_data_setup.php - Create sample hotels and rooms for testing

require_once 'db_connection.php';

echo "🚀 Setting up test data for AiNi Travel booking platform...\n\n";

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Sample hotels data
    $hotels = [
        [
            'hotel_name' => 'Ocean View Resort Miami',
            'location' => '1234 Ocean Drive',
            'city' => 'Miami Beach',
            'state' => 'Florida',
            'country' => 'USA',
            'description' => 'Luxury beachfront resort with stunning ocean views, world-class spa, and award-winning restaurants. Perfect for romantic getaways and family vacations.',
            'amenities' => 'Ocean View,Private Beach,Pool,Spa,Restaurant,WiFi,Room Service,Fitness Center,Valet Parking',
            'category' => 'luxury',
            'star_rating' => 4.8,
            'whatsapp_number' => '+1305555OCEAN',
            'email' => 'reservations@oceanviewmiami.com'
        ],
        [
            'hotel_name' => 'Downtown Business Tower NYC',
            'location' => '456 Manhattan Avenue',
            'city' => 'New York',
            'state' => 'New York',
            'country' => 'USA',
            'description' => 'Modern business hotel in the heart of Manhattan. Perfect for business travelers with state-of-the-art conference facilities and executive services.',
            'amenities' => 'Business Center,Conference Rooms,WiFi,Fitness Center,Restaurant,Concierge,Airport Shuttle',
            'category' => 'business',
            'star_rating' => 4.6,
            'whatsapp_number' => '+1212555TOWER',
            'email' => 'business@nycbusinesstower.com'
        ],
        [
            'hotel_name' => 'Budget Traveler Inn Austin',
            'location' => '789 South Congress',
            'city' => 'Austin',
            'state' => 'Texas',
            'country' => 'USA',
            'description' => 'Clean, comfortable, and affordable accommodations in the heart of Austin. Great for budget-conscious travelers who want to explore the live music capital.',
            'amenities' => 'Free WiFi,Continental Breakfast,Parking,Pet Friendly,24hr Front Desk',
            'category' => 'budget',
            'star_rating' => 4.3,
            'whatsapp_number' => '+1512555AUSTIN',
            'email' => 'info@budgetaustin.com'
        ],
        [
            'hotel_name' => 'Family Paradise Resort Orlando',
            'location' => '321 Magic Kingdom Drive',
            'city' => 'Orlando',
            'state' => 'Florida',
            'country' => 'USA',
            'description' => 'Family-friendly resort minutes from Disney World. Features themed rooms, kids club, multiple pools, and character dining experiences.',
            'amenities' => 'Kids Club,Multiple Pools,Game Room,Restaurant,WiFi,Shuttle to Parks,Family Suites,Playground',
            'category' => 'family',
            'star_rating' => 4.7,
            'whatsapp_number' => '+1407555FAMILY',
            'email' => 'family@paradiseorlando.com'
        ],
        [
            'hotel_name' => 'Mountain Lodge Retreat Aspen',
            'location' => '567 Alpine Way',
            'city' => 'Aspen',
            'state' => 'Colorado',
            'country' => 'USA',
            'description' => 'Luxurious mountain retreat with breathtaking alpine views. Features world-class skiing, cozy fireplaces, and gourmet mountain cuisine.',
            'amenities' => 'Mountain Views,Ski Access,Fireplace,Spa,Fine Dining,WiFi,Valet,Concierge,Hot Tub',
            'category' => 'luxury',
            'star_rating' => 4.9,
            'whatsapp_number' => '+1970555ASPEN',
            'email' => 'luxury@mountainlodgeaspen.com'
        ],
        [
            'hotel_name' => 'Beach Bungalow Hotel San Diego',
            'location' => '890 Pacific Beach Boulevard',
            'city' => 'San Diego',
            'state' => 'California',
            'country' => 'USA',
            'description' => 'Charming beachside hotel with direct beach access. Perfect for surfers, beach lovers, and those seeking California coastal vibes.',
            'amenities' => 'Beach Access,Surfboard Rental,Pool,Restaurant,WiFi,Beach Volleyball,Fire Pits',
            'category' => 'beach',
            'star_rating' => 4.5,
            'whatsapp_number' => '+1619555BEACH',
            'email' => 'waves@beachbungalowsd.com'
        ],
        [
            'hotel_name' => 'Historic Boutique Hotel Charleston',
            'location' => '123 Rainbow Row',
            'city' => 'Charleston',
            'state' => 'South Carolina',
            'country' => 'USA',
            'description' => 'Elegant historic boutique hotel in the heart of Charleston\'s historic district. Features antique furnishings, southern hospitality, and award-winning cuisine.',
            'amenities' => 'Historic Architecture,Fine Dining,Courtyard,WiFi,Concierge,Valet,Spa Services',
            'category' => 'boutique',
            'star_rating' => 4.8,
            'whatsapp_number' => '+1843555CHARM',
            'email' => 'reservations@historiccharleston.com'
        ],
        [
            'hotel_name' => 'Desert Oasis Resort Phoenix',
            'location' => '456 Camelback Mountain Road',
            'city' => 'Phoenix',
            'state' => 'Arizona',
            'country' => 'USA',
            'description' => 'Stunning desert resort with championship golf course, luxury spa, and breathtaking mountain views. Perfect for golf enthusiasts and spa lovers.',
            'amenities' => 'Golf Course,Spa,Multiple Pools,Tennis,Restaurant,WiFi,Desert Tours,Fitness Center',
            'category' => 'resort',
            'star_rating' => 4.6,
            'whatsapp_number' => '+1602555DESERT',
            'email' => 'golf@desertoasisphx.com'
        ]
    ];
    
    echo "Creating hotels...\n";
    
    foreach ($hotels as $hotel) {
        $query = "
            INSERT INTO hotels (
                hotel_name, location, city, state, country, description, 
                amenities, category, star_rating, whatsapp_number, email, 
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            'ssssssssdss',
            $hotel['hotel_name'],
            $hotel['location'],
            $hotel['city'],
            $hotel['state'],
            $hotel['country'],
            $hotel['description'],
            $hotel['amenities'],
            $hotel['category'],
            $hotel['star_rating'],
            $hotel['whatsapp_number'],
            $hotel['email']
        );
        
        $stmt->execute();
        $hotel_id = $conn->insert_id;
        
        echo "✅ Created hotel: {$hotel['hotel_name']} (ID: $hotel_id)\n";
        
        // Create rooms for each hotel
        $room_types = [
            ['type' => 'Standard Room', 'price' => 120, 'occupancy' => 2, 'amenities' => 'Queen Bed,WiFi,TV,Air Conditioning,Private Bathroom'],
            ['type' => 'Deluxe Room', 'price' => 180, 'occupancy' => 2, 'amenities' => 'King Bed,WiFi,TV,Air Conditioning,Private Bathroom,Mini Bar,Balcony'],
            ['type' => 'Suite', 'price' => 280, 'occupancy' => 4, 'amenities' => 'King Bed,Living Area,WiFi,TV,Air Conditioning,Private Bathroom,Mini Bar,Balcony,Kitchenette']
        ];
        
        // Adjust prices based on hotel category
        $price_multiplier = 1.0;
        switch ($hotel['category']) {
            case 'luxury': $price_multiplier = 1.8; break;
            case 'resort': $price_multiplier = 1.6; break;
            case 'boutique': $price_multiplier = 1.4; break;
            case 'business': $price_multiplier = 1.3; break;
            case 'beach': $price_multiplier = 1.2; break;
            case 'family': $price_multiplier = 1.1; break;
            case 'budget': $price_multiplier = 0.6; break;
        }
        
        foreach ($room_types as $room) {
            $adjusted_price = round($room['price'] * $price_multiplier);
            
            // Create 3-5 rooms of each type
            $room_count = rand(3, 5);
            for ($i = 1; $i <= $room_count; $i++) {
                $room_number = $room['type'][0] . sprintf('%02d', $i);
                
                $room_query = "
                    INSERT INTO rooms (
                        hotel_id, room_type, room_number, description, 
                        base_price, max_occupancy, amenities, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'available', NOW())
                ";
                
                $room_description = "Comfortable {$room['type']} with modern amenities and excellent service.";
                
                $room_stmt = $conn->prepare($room_query);
                $room_stmt->bind_param(
                    'issssis',
                    $hotel_id,
                    $room['type'],
                    $room_number,
                    $room_description,
                    $adjusted_price,
                    $room['occupancy'],
                    $room['amenities']
                );
                
                $room_stmt->execute();
            }
        }
        
        echo "   ✅ Created rooms for {$hotel['hotel_name']}\n";
    }
    
    // Create sample guests
    echo "\nCreating sample guests...\n";
    
    $guests = [
        ['name' => 'John Smith', 'email' => 'john.smith@email.com', 'phone' => '+1555123JOHN'],
        ['name' => 'Sarah Johnson', 'email' => 'sarah.j@email.com', 'phone' => '+1555456SARAH'],
        ['name' => 'Mike Davis', 'email' => 'mike.davis@email.com', 'phone' => '+1555789MIKE'],
        ['name' => 'Emily Brown', 'email' => 'emily.brown@email.com', 'phone' => '+1555321EMILY'],
        ['name' => 'David Wilson', 'email' => 'david.w@email.com', 'phone' => '+1555654DAVID']
    ];
    
    foreach ($guests as $guest) {
        $query = "INSERT INTO guests (name, email, phone, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sss', $guest['name'], $guest['email'], $guest['phone']);
        $stmt->execute();
        $guest_id = $conn->insert_id;
        
        // Create wallet for guest
        $wallet_query = "INSERT INTO guest_wallets (guest_id, aini_coins, loyalty_points, created_at) VALUES (?, ?, ?, NOW())";
        $wallet_stmt = $conn->prepare($wallet_query);
        $initial_coins = rand(0, 500);
        $initial_points = rand(0, 1000);
        $wallet_stmt->bind_param('iii', $guest_id, $initial_coins, $initial_points);
        $wallet_stmt->execute();
        
        echo "✅ Created guest: {$guest['name']} (ID: $guest_id) with $initial_coins AiNi coins\n";
    }
    
    // Create sample reviews
    echo "\nCreating sample reviews...\n";
    
    $reviews = [
        ['rating' => 5, 'comment' => 'Absolutely amazing stay! The ocean view was breathtaking and the staff was incredibly friendly. Will definitely book again!'],
        ['rating' => 4, 'comment' => 'Great location and clean rooms. The WiFi was fast and the breakfast was delicious. Highly recommend!'],
        ['rating' => 5, 'comment' => 'Perfect for our family vacation. Kids loved the pool and the staff went above and beyond to make our stay special.'],
        ['rating' => 4, 'comment' => 'Excellent business hotel. The conference facilities were top-notch and the location was perfect for our meetings.'],
        ['rating' => 5, 'comment' => 'Luxury at its finest! The spa was incredible and the mountain views were stunning. Worth every penny!'],
        ['rating' => 4, 'comment' => 'Great value for money. Clean, comfortable, and the staff was very helpful. Perfect for budget travelers.']
    ];
    
    // Get hotel and guest IDs
    $hotel_result = $conn->query("SELECT hotel_id FROM hotels");
    $hotel_ids = [];
    while ($row = $hotel_result->fetch_assoc()) {
        $hotel_ids[] = $row['hotel_id'];
    }
    
    $guest_result = $conn->query("SELECT guest_id FROM guests");
    $guest_ids = [];
    while ($row = $guest_result->fetch_assoc()) {
        $guest_ids[] = $row['guest_id'];
    }
    
    // Create reviews for each hotel
    foreach ($hotel_ids as $hotel_id) {
        $review_count = rand(2, 4);
        for ($i = 0; $i < $review_count; $i++) {
            $review = $reviews[array_rand($reviews)];
            $guest_id = $guest_ids[array_rand($guest_ids)];
            
            $query = "INSERT INTO reviews (hotel_id, guest_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('iids', $hotel_id, $guest_id, $review['rating'], $review['comment']);
            $stmt->execute();
        }
    }
    
    echo "✅ Created sample reviews for all hotels\n";
    
    // Create some sample bookings
    echo "\nCreating sample bookings...\n";
    
    $booking_count = 0;
    foreach ($hotel_ids as $hotel_id) {
        // Get rooms for this hotel
        $room_result = $conn->query("SELECT room_id, base_price FROM rooms WHERE hotel_id = $hotel_id LIMIT 2");
        
        while ($room = $room_result->fetch_assoc()) {
            $guest_id = $guest_ids[array_rand($guest_ids)];
            $check_in = date('Y-m-d', strtotime('+' . rand(1, 30) . ' days'));
            $check_out = date('Y-m-d', strtotime($check_in . ' +' . rand(1, 7) . ' days'));
            $nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
            $total_cost = $room['base_price'] * $nights;
            $aini_coins = round($total_cost * 0.2);
            $booking_ref = 'AINI' . date('Y') . str_pad(++$booking_count, 6, '0', STR_PAD_LEFT);
            
            $query = "
                INSERT INTO bookings (
                    hotel_id, room_id, guest_id, booking_reference,
                    check_in_date, check_out_date, guests, total_cost,
                    status, booking_source, aini_coins_earned, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', 'website', ?, NOW())
            ";
            
            $stmt = $conn->prepare($query);
            $guest_count = rand(1, 3);
            $stmt->bind_param(
                'iiisssiidi',
                $hotel_id, $room['room_id'], $guest_id, $booking_ref,
                $check_in, $check_out, $guest_count, $total_cost, $aini_coins
            );
            $stmt->execute();
            
            if ($booking_count >= 10) break 2; // Limit to 10 sample bookings
        }
    }
    
    echo "✅ Created $booking_count sample bookings\n";
    
    // Commit transaction
    $conn->commit();
    
    // Display summary
    echo "\n🎉 TEST DATA SETUP COMPLETE!\n\n";
    echo "Summary:\n";
    echo "--------\n";
    
    $hotel_count = $conn->query("SELECT COUNT(*) as count FROM hotels")->fetch_assoc()['count'];
    $room_count = $conn->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'];
    $guest_count = $conn->query("SELECT COUNT(*) as count FROM guests")->fetch_assoc()['count'];
    $review_count = $conn->query("SELECT COUNT(*) as count FROM reviews")->fetch_assoc()['count'];
    $booking_count = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
    
    echo "✅ Hotels: $hotel_count\n";
    echo "✅ Rooms: $room_count\n";
    echo "✅ Guests: $guest_count\n";
    echo "✅ Reviews: $review_count\n";
    echo "✅ Bookings: $booking_count\n";
    echo "\n🌐 Ready to test at: http://localhost/hotel-booking-system/public_booking.php\n";
    echo "🏨 Manager dashboard: http://localhost/hotel-booking-system/manager_dashboard.php\n";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>