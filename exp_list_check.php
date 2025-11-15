<?php
/**
 * experiences_list.php
 * 
 * Public listing of approved experiences/tours
 * Integrates with public_booking.php navigation
 * 
 * @author AI Assistant + juanmorellana2021
 * @date November 10, 2025
 */

session_start();
require_once 'db_connection_pdo.php';
require_once 'classes/ExperienceManager.php';

$experienceManager = new ExperienceManager($pdo);

// Get filters from query string
$filters = [
    'category' => $_GET['category'] ?? '',
    'country' => $_GET['country'] ?? '',
    'city' => $_GET['city'] ?? '',
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? '',
    'difficulty' => $_GET['difficulty'] ?? '',
    'search' => $_GET['search'] ?? '',
    'limit' => 20,
    'offset' => ($_GET['page'] ?? 1 - 1) * 20
];

// Get experiences
$experiences = $experienceManager->search($filters);

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : 'Guest';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Experiencias y Tours | AiNi Travel</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#667eea',
                        secondary: '#764ba2',
                    }
                }
            }
        }
    </script>
    
    <style>
        body {
            padding-top: 80px;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .experience-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .experience-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .category-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .filter-sidebar {
            position: sticky;
            top: 100px;
            max-height: calc(100vh - 120px);
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <?php include 'includes/navigation.php'; ?>
    
    <!-- Hero Section -->
    <section class="gradient-bg text-white py-16" style="margin-top: 64px;">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-5xl font-bold mb-4">Descubre Experiencias Únicas</h1>
            <p class="text-xl opacity-90 mb-8">Tours, aventuras y actividades inolvidables</p>
            
            <!-- Search Bar -->
            <form method="GET" class="max-w-3xl mx-auto">
                <div class="flex gap-2">
                    <input type="text" name="search" 
                           value="<?php echo htmlspecialchars($filters['search']); ?>"
                           placeholder="Busca experiencias, destinos, actividades..." 
                           class="flex-1 px-6 py-4 rounded-lg text-gray-900 text-lg">
                    <button type="submit" class="bg-yellow-400 text-gray-900 px-8 py-4 rounded-lg font-semibold hover:bg-yellow-300 transition">
                        <i class="fas fa-search mr-2"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </section>
    
    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- Filters Sidebar -->
            <aside class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 filter-sidebar">
                    <h3 class="text-xl font-bold mb-4 text-gray-800">
                        <i class="fas fa-filter mr-2 text-primary"></i> Filtros
                    </h3>
                    
                    <form method="GET" id="filterForm">
                        <!-- Category Filter -->
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Categoría</label>
                            <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg" onchange="this.form.submit()">
                                <option value="">Todas</option>
                                <option value="adventure" <?php echo $filters['category'] === 'adventure' ? 'selected' : ''; ?>>🏔️ Aventura</option>
                                <option value="cultural" <?php echo $filters['category'] === 'cultural' ? 'selected' : ''; ?>>🎭 Cultural</option>
                                <option value="food" <?php echo $filters['category'] === 'food' ? 'selected' : ''; ?>>🍽️ Gastronomía</option>
                                <option value="nature" <?php echo $filters['category'] === 'nature' ? 'selected' : ''; ?>>🌿 Naturaleza</option>
                                <option value="wellness" <?php echo $filters['category'] === 'wellness' ? 'selected' : ''; ?>>🧘 Bienestar</option>
                                <option value="water_sports" <?php echo $filters['category'] === 'water_sports' ? 'selected' : ''; ?>>🏄 Deportes Acuáticos</option>
                                <option value="city_tour" <?php echo $filters['category'] === 'city_tour' ? 'selected' : ''; ?>>🏛️ Tour Urbano</option>
                                <option value="multi_day" <?php echo $filters['category'] === 'multi_day' ? 'selected' : ''; ?>>🎒 Varios Días</option>
                            </select>
                        </div>
                        
                        <!-- Country Filter -->
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">País</label>
                            <input type="text" name="country" value="<?php echo htmlspecialchars($filters['country']); ?>" 
                                   placeholder="Ej: Peru" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <!-- City Filter -->
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ciudad</label>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($filters['city']); ?>" 
                                   placeholder="Ej: Cusco" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        
                        <!-- Price Range -->
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Precio (USD)</label>
                            <div class="flex gap-2">
                                <input type="number" name="min_price" value="<?php echo htmlspecialchars($filters['min_price']); ?>" 
                                       placeholder="Min" class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg">
                                <input type="number" name="max_price" value="<?php echo htmlspecialchars($filters['max_price']); ?>" 
                                       placeholder="Max" class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg">
                            </div>
                        </div>
                        
                        <!-- Difficulty Filter -->
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Dificultad</label>
                            <select name="difficulty" class="w-full px-3 py-2 border border-gray-300 rounded-lg" onchange="this.form.submit()">
                                <option value="">Todas</option>
                                <option value="easy" <?php echo $filters['difficulty'] === 'easy' ? 'selected' : ''; ?>>🟢 Fácil</option>
                                <option value="moderate" <?php echo $filters['difficulty'] === 'moderate' ? 'selected' : ''; ?>>🟡 Moderado</option>
                                <option value="hard" <?php echo $filters['difficulty'] === 'hard' ? 'selected' : ''; ?>>🟠 Difícil</option>
                                <option value="expert" <?php echo $filters['difficulty'] === 'expert' ? 'selected' : ''; ?>>🔴 Experto</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg font-semibold hover:bg-secondary transition">
                            <i class="fas fa-search mr-2"></i> Aplicar Filtros
                        </button>
                        
                        <a href="experiences_list.php" class="block text-center mt-3 text-sm text-gray-600 hover:text-primary">
                            Limpiar filtros
                        </a>
                    </form>
                </div>
            </aside>
            
            <!-- Experiences Grid -->
            <main class="lg:col-span-3">
                <div class="mb-6 flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <?php echo count($experiences); ?> Experiencias Disponibles
                    </h2>
                </div>
                
                <?php if (empty($experiences)): ?>
                    <div class="bg-white rounded-lg shadow-md p-12 text-center">
                        <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-2xl font-bold text-gray-700 mb-2">No se encontraron experiencias</h3>
                        <p class="text-gray-600 mb-6">Intenta ajustar los filtros o buscar algo diferente</p>
                        <a href="experiences_list.php" class="inline-block bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-secondary transition">
                            Ver todas las experiencias
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($experiences as $exp): ?>
                            <div class="bg-white rounded-lg shadow-md overflow-hidden experience-card" 
                                 onclick="window.location.href='experience_detail.php?id=<?php echo $exp['id']; ?>'">
                                
                                <!-- Image -->
                                <div class="relative h-48 bg-gray-200">
                                    <?php if ($exp['cover_photo']): ?>
                                        <img src="<?php echo htmlspecialchars($exp['cover_photo']); ?>" 
                                             alt="<?php echo htmlspecialchars($exp['title']); ?>"
                                             class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-primary to-secondary">
                                            <i class="fas fa-mountain text-white text-6xl opacity-50"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Category Badge -->
                                    <div class="absolute top-3 left-3">
                                        <span class="category-badge bg-white text-primary">
                                            <?php 
                                            $categoryIcons = [
                                                'adventure' => '🏔️ Aventura',
                                                'cultural' => '🎭 Cultural',
                                                'food' => '🍽️ Gastronomía',
                                                'nature' => '🌿 Naturaleza',
                                                'wellness' => '🧘 Bienestar',
                                                'water_sports' => '🏄 Acuático',
                                                'city_tour' => '🏛️ Urbano',
                                                'multi_day' => '🎒 Multi-día'
                                            ];
                                            echo $categoryIcons[$exp['category']] ?? $exp['category'];
                                            ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Rating -->
                                    <?php if ($exp['avg_rating'] > 0): ?>
                                        <div class="absolute top-3 right-3 bg-white px-2 py-1 rounded-full">
                                            <span class="text-yellow-500 font-bold">★ <?php echo number_format($exp['avg_rating'], 1); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Content -->
                                <div class="p-4">
                                    <h3 class="text-xl font-bold text-gray-800 mb-2">
                                        <?php echo htmlspecialchars($exp['title']); ?>
                                    </h3>
                                    
                                    <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                                        <?php echo htmlspecialchars($exp['short_description']); ?>
                                    </p>
                                    
                                    <!-- Info Row -->
                                    <div class="flex items-center text-sm text-gray-500 mb-3 gap-4">
                                        <span><i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($exp['city'] . ', ' . $exp['country']); ?></span>
                                        <?php if ($exp['duration_hours']): ?>
                                            <span><i class="fas fa-clock mr-1"></i> <?php echo $exp['duration_hours']; ?>h</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Price & Book -->
                                    <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                                        <div>
                                            <span class="text-2xl font-bold text-primary">$<?php echo number_format($exp['price_usd'], 0); ?></span>
                                            <span class="text-sm text-gray-500">/persona</span>
                                        </div>
                                        <button class="bg-primary text-white px-4 py-2 rounded-lg font-semibold hover:bg-secondary transition">
                                            Ver Detalles
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="gradient-bg text-white py-12 mt-16">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h4 class="text-xl font-bold mb-4">AiNi Travel</h4>
                    <p class="text-sm opacity-90">Descubre el mundo con experiencias únicas y auténticas</p>
                </div>
                <div>
                    <h5 class="font-semibold mb-3">Enlaces Rápidos</h5>
                    <ul class="space-y-2 text-sm">
                        <li><a href="public_booking.php" class="hover:text-yellow-300">Hoteles</a></li>
                        <li><a href="experiences_list.php" class="hover:text-yellow-300">Experiencias</a></li>
                        <li><a href="experience_register.php" class="hover:text-yellow-300">Ser Socio</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-semibold mb-3">Soporte</h5>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-yellow-300">Centro de Ayuda</a></li>
                        <li><a href="#" class="hover:text-yellow-300">Contacto</a></li>
                        <li><a href="#" class="hover:text-yellow-300">Términos y Condiciones</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-semibold mb-3">Síguenos</h5>
                    <div class="flex gap-4 text-2xl">
                        <a href="#" class="hover:text-yellow-300"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="hover:text-yellow-300"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="hover:text-yellow-300"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            <div class="text-center mt-8 pt-8 border-t border-white/20 text-sm opacity-75">
                © 2025 AiNi Travel. Todos los derechos reservados.
            </div>
        </div>
    </footer>
    
</body>
</html>
