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

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $name = trim($_POST['name']);
        $property_type = $_POST['property_type'];
        $star_category = intval($_POST['star_category']);
        $contact_person = trim($_POST['contact_person']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $location = trim($_POST['location']);
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
        $description = trim($_POST['description']);
        $rooms_available = intval($_POST['rooms_available']);
        $price = floatval($_POST['price']);
        $website_url = trim($_POST['website_url']);
        $business_registration_number = trim($_POST['business_registration_number']);
        $tax_id = trim($_POST['tax_id']);
        
        // Get amenities
        $amenities = $_POST['amenities'] ?? [];
        $amenities_json = json_encode($amenities);
        
        // Update hotel
        $stmt = $pdo->prepare("
            UPDATE hotel_properties SET
                name = ?,
                property_type = ?,
                star_category = ?,
                contact_person = ?,
                email = ?,
                phone = ?,
                location = ?,
                latitude = ?,
                longitude = ?,
                description = ?,
                rooms_available = ?,
                price = ?,
                website_url = ?,
                business_registration_number = ?,
                tax_id = ?,
                amenities = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $name,
            $property_type,
            $star_category,
            $contact_person,
            $email,
            $phone,
            $location,
            $latitude,
            $longitude,
            $description,
            $rooms_available,
            $price,
            $website_url,
            $business_registration_number,
            $tax_id,
            $amenities_json,
            $hotel_id
        ]);
        
        $success = "Hotel profile updated successfully!";
        
        // Refresh hotel data
        $stmt = $pdo->prepare("SELECT * FROM hotel_properties WHERE id = ?");
        $stmt->execute([$hotel_id]);
        $hotel = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}

// Parse current amenities
$current_amenities = json_decode($hotel['amenities'] ?? '[]', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - <?= htmlspecialchars($hotel['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        #map { height: 400px; border-radius: 0.5rem; }
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
                        <i class="fas fa-edit mr-2"></i>Edit Hotel Profile
                    </h1>
                </div>
                <a href="logout.php" class="bg-white text-purple-600 px-4 py-2 rounded-lg font-semibold hover:bg-purple-50 transition">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                <div class="flex">
                    <i class="fas fa-check-circle text-green-400 text-xl mr-3"></i>
                    <p class="text-sm text-green-700"><?= $success ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                <div class="flex">
                    <i class="fas fa-exclamation-circle text-red-400 text-xl mr-3"></i>
                    <p class="text-sm text-red-700"><?= $error ?></p>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">

            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-info-circle text-purple-600 mr-2"></i>Basic Information
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-semibold mb-2">Hotel Name *</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($hotel['name']) ?>" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Property Type *</label>
                        <select name="property_type" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <?php
                            $types = ['hotel', 'hostel', 'resort', 'apartment', 'cabin', 'boutique'];
                            foreach ($types as $type) {
                                $selected = $hotel['property_type'] === $type ? 'selected' : '';
                                echo "<option value='$type' $selected>".ucfirst($type)."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Star Category *</label>
                        <select name="star_category" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>" <?= $hotel['star_category'] == $i ? 'selected' : '' ?>>
                                    <?= $i ?> Star<?= $i > 1 ? 's' : '' ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Total Rooms *</label>
                        <input type="number" name="rooms_available" value="<?= $hotel['rooms_available'] ?>" required min="1"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Price per Night (USD) *</label>
                        <input type="number" name="price" value="<?= $hotel['price'] ?>" required min="1" step="0.01"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-address-book text-purple-600 mr-2"></i>Contact Information
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Contact Person *</label>
                        <input type="text" name="contact_person" value="<?= htmlspecialchars($hotel['contact_person']) ?>" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Email *</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($hotel['email']) ?>" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                               placeholder="hotel@example.com">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Phone Number *</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($hotel['phone']) ?>" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                               placeholder="+51 938 118 436">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-semibold mb-2">Website URL</label>
                        <input type="url" name="website_url" value="<?= htmlspecialchars($hotel['website_url']) ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                               placeholder="https://yourhotel.com">
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-map-marker-alt text-purple-600 mr-2"></i>Location
                </h2>
                
                <div class="grid grid-cols-1 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Location / Address *</label>
                        <input type="text" name="location" id="location" value="<?= htmlspecialchars($hotel['location']) ?>" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                               placeholder="City, Country">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Latitude *</label>
                            <input type="number" name="latitude" id="latitude" value="<?= $hotel['latitude'] ?>" required step="0.000001"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Longitude *</label>
                            <input type="number" name="longitude" id="longitude" value="<?= $hotel['longitude'] ?>" required step="0.000001"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        </div>
                    </div>
                </div>

                <div id="map"></div>
                <p class="text-sm text-gray-500 mt-2">Click on the map to update location</p>
            </div>

            <!-- Description -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-align-left text-purple-600 mr-2"></i>Description
                </h2>
                <textarea name="description" rows="6" required
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                          placeholder="Describe your hotel, rooms, services, and what makes it special..."><?= htmlspecialchars($hotel['description']) ?></textarea>
            </div>

            <!-- Amenities -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-concierge-bell text-purple-600 mr-2"></i>Amenities & Services
                </h2>
                
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <?php
                    $amenities = [
                        'wifi' => 'Free WiFi',
                        'parking' => 'Free Parking',
                        'pool' => 'Swimming Pool',
                        'gym' => 'Fitness Center',
                        'restaurant' => 'Restaurant',
                        'spa' => 'Spa',
                        'bar' => 'Bar',
                        'breakfast' => 'Breakfast Included',
                        'airport_shuttle' => 'Airport Shuttle',
                        'pet_friendly' => 'Pet Friendly',
                        'air_conditioning' => 'Air Conditioning',
                        'room_service' => '24/7 Room Service'
                    ];
                    
                    foreach ($amenities as $key => $label):
                        $checked = in_array($key, $current_amenities) ? 'checked' : '';
                    ?>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="amenities[]" value="<?= $key ?>" <?= $checked ?>
                                   class="w-4 h-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                            <span class="text-gray-700"><?= $label ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Business Information -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-briefcase text-purple-600 mr-2"></i>Business Information
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Business Registration Number</label>
                        <input type="text" name="business_registration_number" value="<?= htmlspecialchars($hotel['business_registration_number']) ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Tax ID</label>
                        <input type="text" name="tax_id" value="<?= htmlspecialchars($hotel['tax_id']) ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex space-x-4">
                <button type="submit" class="flex-1 bg-purple-600 text-white py-4 rounded-lg font-bold text-lg hover:bg-purple-700 transition">
                    <i class="fas fa-save mr-2"></i>Save Changes
                </button>
                <a href="hotel_profile.php?id=<?= $hotel_id ?>" class="flex-1 bg-gray-500 text-white py-4 rounded-lg font-bold text-lg hover:bg-gray-600 transition text-center">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>

        </form>

    </div>

    <script>
        // Initialize map
        let currentLat = <?= $hotel['latitude'] ?>;
        let currentLng = <?= $hotel['longitude'] ?>;
        
        const map = L.map('map').setView([currentLat, currentLng], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        let marker = L.marker([currentLat, currentLng]).addTo(map);
        
        // Click on map to set location
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            
            // Update marker
            marker.setLatLng([lat, lng]);
            
            // Update form fields
            document.getElementById('latitude').value = lat.toFixed(6);
            document.getElementById('longitude').value = lng.toFixed(6);
        });
    </script>

</body>
</html>
