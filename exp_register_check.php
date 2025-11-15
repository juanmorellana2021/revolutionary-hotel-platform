<?php
/**
 * experience_register.php
 * 
 * Public registration form for travel agents and tour guides
 * to submit their experiences/tours to AiNi Travel platform
 * 
 * FEATURES:
 * - Multi-step form (Partner Info → Experience Details → Pricing → Photos)
 * - Client-side validation
 * - Map integration for location selection
 * - Photo upload preview
 * - CSRF protection
 * 
 * @author AI Assistant + juanmorellana2021
 * @date November 10, 2025
 */

session_start();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registra tu Experiencia | AiNi Travel</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet Map CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        :root {
            --aini-primary: #2C3E50;
            --aini-secondary: #E74C3C;
            --aini-accent: #3498DB;
            --aini-gold: #F39C12;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .registration-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .registration-header {
            background: linear-gradient(135deg, var(--aini-primary), var(--aini-accent));
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .registration-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .registration-header p {
            margin: 10px 0 0;
            opacity: 0.9;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            padding: 30px;
            background: #f8f9fa;
            border-bottom: 3px solid var(--aini-accent);
        }
        
        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #dee2e6;
            color: #6c757d;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 8px;
            transition: all 0.3s;
        }
        
        .step.active .step-number {
            background: var(--aini-accent);
            color: white;
            transform: scale(1.1);
        }
        
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
        
        .step.completed .step-number::after {
            content: '✓';
        }
        
        .step-title {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .step.active .step-title {
            color: var(--aini-accent);
            font-weight: 700;
        }
        
        .form-content {
            padding: 40px;
        }
        
        .form-step {
            display: none;
        }
        
        .form-step.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-label {
            font-weight: 600;
            color: var(--aini-primary);
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--aini-accent);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .required-field::after {
            content: ' *';
            color: var(--aini-secondary);
        }
        
        #map {
            height: 300px;
            border-radius: 10px;
            margin-top: 10px;
        }
        
        .photo-upload-zone {
            border: 3px dashed #dee2e6;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .photo-upload-zone:hover {
            border-color: var(--aini-accent);
            background: #f8f9fa;
        }
        
        .photo-upload-zone i {
            font-size: 3rem;
            color: var(--aini-accent);
            margin-bottom: 15px;
        }
        
        .photo-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .photo-preview-item {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .photo-preview-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .photo-preview-item .remove-photo {
            position: absolute;
            top: 5px;
            right: 5px;
            background: var(--aini-secondary);
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .btn-navigation {
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-prev {
            background: #6c757d;
            color: white;
            border: none;
        }
        
        .btn-prev:hover {
            background: #5a6268;
            transform: translateX(-5px);
        }
        
        .btn-next {
            background: var(--aini-accent);
            color: white;
            border: none;
        }
        
        .btn-next:hover {
            background: #2980b9;
            transform: translateX(5px);
        }
        
        .btn-submit {
            background: var(--aini-gold);
            color: white;
            border: none;
        }
        
        .btn-submit:hover {
            background: #e67e22;
            transform: scale(1.05);
        }
        
        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e9ecef;
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid var(--aini-accent);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .info-box i {
            color: var(--aini-accent);
            margin-right: 10px;
        }
        
        .price-converter {
            background: #fff3cd;
            border: 2px solid var(--aini-gold);
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
        }
        
        .price-display {
            display: flex;
            justify-content: space-around;
            margin-top: 10px;
        }
        
        .price-item {
            text-align: center;
        }
        
        .price-item .label {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .price-item .value {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--aini-primary);
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <!-- Header -->
        <div class="registration-header">
            <h1><i class="fas fa-mountain"></i> Registra tu Experiencia</h1>
            <p>Comparte tus tours y aventuras con viajeros de todo el mundo</p>
        </div>
        
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-title">Información del Socio</div>
            </div>
            <div class="step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-title">Detalles de la Experiencia</div>
            </div>
            <div class="step" data-step="3">
                <div class="step-number">3</div>
                <div class="step-title">Precios y Logística</div>
            </div>
            <div class="step" data-step="4">
                <div class="step-number">4</div>
                <div class="step-title">Fotos y Ubicación</div>
            </div>
        </div>
        
        <!-- Form -->
        <form id="experienceForm" class="form-content" action="experience_submit.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <!-- STEP 1: Partner Information -->
            <div class="form-step active" data-step="1">
                <h3 class="mb-4"><i class="fas fa-user-tie"></i> Información del Socio</h3>
                
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <strong>¿Eres nuevo?</strong> Completa tus datos. Si ya eres socio, usa el mismo email.
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Nombre del Negocio</label>
                        <input type="text" class="form-control" name="business_name" required 
                               placeholder="Ej: Cusco Adventures Peru">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Nombre del Propietario</label>
                        <input type="text" class="form-control" name="owner_name" required 
                               placeholder="Ej: Ricardo Mendoza">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Email</label>
                        <input type="email" class="form-control" name="email" required 
                               placeholder="contacto@tunegocio.com">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Teléfono</label>
                        <input type="tel" class="form-control" name="phone" required 
                               placeholder="+51 984 123 456">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">País</label>
                        <select class="form-select" name="country" required>
                            <option value="">Seleccionar...</option>
                            <option value="Peru">Perú</option>
                            <option value="Bolivia">Bolivia</option>
                            <option value="Ecuador">Ecuador</option>
                            <option value="Colombia">Colombia</option>
                            <option value="Chile">Chile</option>
                            <option value="Argentina">Argentina</option>
                            <option value="Brazil">Brasil</option>
                            <option value="Mexico">México</option>
                            <option value="Costa Rica">Costa Rica</option>
                            <option value="Other">Otro</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Ciudad</label>
                        <input type="text" class="form-control" name="city" required 
                               placeholder="Ej: Cusco">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Dirección (Opcional)</label>
                    <input type="text" class="form-control" name="address" 
                           placeholder="Calle, número, distrito">
                </div>
            </div>
            
            <!-- STEP 2: Experience Details -->
            <div class="form-step" data-step="2">
                <h3 class="mb-4"><i class="fas fa-mountain-sun"></i> Detalles de la Experiencia</h3>
                
                <div class="mb-3">
                    <label class="form-label required-field">Título de la Experiencia</label>
                    <input type="text" class="form-control" name="title" required 
                           placeholder="Ej: Machu Picchu Sunrise Trek"
                           maxlength="255">
                    <small class="text-muted">Máximo 255 caracteres</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label required-field">Descripción Corta</label>
                    <textarea class="form-control" name="short_description" rows="2" required
                              placeholder="Breve resumen de la experiencia (aparecerá en búsquedas)"
                              maxlength="500"></textarea>
                    <small class="text-muted">Máximo 500 caracteres</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label required-field">Descripción Completa</label>
                    <textarea class="form-control" name="description" rows="6" required
                              placeholder="Describe la experiencia en detalle: qué incluye, qué van a hacer los viajeros, qué verán, etc."></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Categoría</label>
                        <select class="form-select" name="category" required>
                            <option value="">Seleccionar...</option>
                            <option value="adventure">🏔️ Aventura</option>
                            <option value="cultural">🎭 Cultural</option>
                            <option value="food">🍽️ Gastronomía</option>
                            <option value="nature">🌿 Naturaleza</option>
                            <option value="wellness">🧘 Bienestar</option>
                            <option value="water_sports">🏄 Deportes Acuáticos</option>
                            <option value="city_tour">🏛️ Tour Urbano</option>
                            <option value="multi_day">🎒 Varios Días</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nivel de Dificultad</label>
                        <select class="form-select" name="difficulty_level">
                            <option value="easy">🟢 Fácil</option>
                            <option value="moderate" selected>🟡 Moderado</option>
                            <option value="hard">🟠 Difícil</option>
                            <option value="expert">🔴 Experto</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Etiquetas (separadas por coma)</label>
                    <input type="text" class="form-control" name="tags" 
                           placeholder="Ej: hiking, mountains, photography, inca trail">
                    <small class="text-muted">Ayuda a los viajeros a encontrar tu experiencia</small>
                </div>
            </div>
            
            <!-- STEP 3: Pricing & Logistics -->
            <div class="form-step" data-step="3">
                <h3 class="mb-4"><i class="fas fa-dollar-sign"></i> Precios y Logística</h3>
                
                <div class="info-box">
                    <i class="fas fa-coins"></i>
                    <strong>Precio en USD:</strong> El sistema calculará automáticamente el equivalente en AiNi Coins.
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label required-field">Precio por Persona (USD)</label>
                        <input type="number" class="form-control" name="price_usd" id="priceUSD" 
                               min="1" step="0.01" required placeholder="150.00">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Descuento (%)</label>
                        <input type="number" class="form-control" name="discount_percentage" 
                               min="0" max="100" step="1" value="0">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Duración (Días)</label>
                        <input type="number" class="form-control" name="duration_days" 
                               min="1" value="1">
                    </div>
                </div>
                
                <div class="price-converter" id="priceConverter" style="display:none;">
                    <strong><i class="fas fa-calculator"></i> Conversión Automática:</strong>
                    <div class="price-display">
                        <div class="price-item">
                            <div class="label">AiNi Rewards</div>
                            <div class="value" id="priceRewards">-</div>
                        </div>
                        <div class="price-item">
                            <div class="label">AiNi Crypto</div>
                            <div class="value" id="priceCrypto">-</div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Duración (Horas)</label>
                        <input type="number" class="form-control" name="duration_hours" 
                               min="1" max="24" placeholder="8">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Mín. Participantes</label>
                        <input type="number" class="form-control" name="min_participants" 
                               min="1" value="1">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Máx. Participantes</label>
                        <input type="number" class="form-control" name="max_participants" 
                               min="1" value="10">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fecha de Inicio (Temporada)</label>
                        <input type="date" class="form-control" name="start_date">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fecha de Fin (Temporada)</label>
                        <input type="date" class="form-control" name="end_date">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Días Disponibles</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="available_days[]" value="monday" id="mon">
                            <label class="form-check-label" for="mon">Lun</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="available_days[]" value="tuesday" id="tue">
                            <label class="form-check-label" for="tue">Mar</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="available_days[]" value="wednesday" id="wed">
                            <label class="form-check-label" for="wed">Mié</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="available_days[]" value="thursday" id="thu">
                            <label class="form-check-label" for="thu">Jue</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="available_days[]" value="friday" id="fri">
                            <label class="form-check-label" for="fri">Vie</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="available_days[]" value="saturday" id="sat">
                            <label class="form-check-label" for="sat">Sáb</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="available_days[]" value="sunday" id="sun">
                            <label class="form-check-label" for="sun">Dom</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- STEP 4: Photos & Location -->
            <div class="form-step" data-step="4">
                <h3 class="mb-4"><i class="fas fa-camera"></i> Fotos y Ubicación</h3>
                
                <div class="mb-4">
                    <label class="form-label">Foto Principal (Cover)</label>
                    <div class="photo-upload-zone" onclick="document.getElementById('coverPhoto').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click para subir foto de portada</p>
                        <small class="text-muted">JPG, PNG, WEBP - Máx 5MB</small>
                    </div>
                    <input type="file" id="coverPhoto" name="cover_photo" accept="image/*" 
                           style="display:none;" onchange="previewCover(this)">
                    <div id="coverPreview" class="photo-preview mt-3"></div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Galería de Fotos (Máx. 10)</label>
                    <div class="photo-upload-zone" onclick="document.getElementById('galleryPhotos').click()">
                        <i class="fas fa-images"></i>
                        <p>Click para subir fotos adicionales</p>
                        <small class="text-muted">Múltiples archivos permitidos</small>
                    </div>
                    <input type="file" id="galleryPhotos" name="gallery_photos[]" accept="image/*" 
                           multiple style="display:none;" onchange="previewGallery(this)">
                    <div id="galleryPreview" class="photo-preview mt-3"></div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label required-field">Punto de Encuentro</label>
                    <input type="text" class="form-control" name="meeting_point" id="meetingPoint" required
                           placeholder="Ej: Plaza de Armas, Cusco - Fuente Principal">
                    <small class="text-muted">Donde los viajeros se encontrarán contigo</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Ubicación en el Mapa (Click para marcar)</label>
                    <div id="map"></div>
                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">
                </div>
            </div>
            
            <!-- Navigation Buttons -->
            <div class="navigation-buttons">
                <button type="button" class="btn btn-prev btn-navigation" onclick="previousStep()" style="display:none;">
                    <i class="fas fa-arrow-left"></i> Anterior
                </button>
                <button type="button" class="btn btn-next btn-navigation" onclick="nextStep()">
                    Siguiente <i class="fas fa-arrow-right"></i>
                </button>
                <button type="submit" class="btn btn-submit btn-navigation" style="display:none;">
                    <i class="fas fa-paper-plane"></i> Enviar Registro
                </button>
            </div>
        </form>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        let currentStep = 1;
        const totalSteps = 4;
        let map = null;
        let marker = null;
        
        // Initialize map
        function initMap() {
            map = L.map('map').setView([-13.5319, -71.9675], 13); // Cusco, Peru
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            
            map.on('click', function(e) {
                if (marker) {
                    map.removeLayer(marker);
                }
                marker = L.marker(e.latlng).addTo(map);
                document.getElementById('latitude').value = e.latlng.lat.toFixed(8);
                document.getElementById('longitude').value = e.latlng.lng.toFixed(8);
            });
        }
        
        // Navigation
        function nextStep() {
            if (!validateStep(currentStep)) {
                return;
            }
            
            if (currentStep < totalSteps) {
                currentStep++;
                updateStepDisplay();
            }
        }
        
        function previousStep() {
            if (currentStep > 1) {
                currentStep--;
                updateStepDisplay();
            }
        }
        
        function updateStepDisplay() {
            // Hide all steps
            document.querySelectorAll('.form-step').forEach(step => {
                step.classList.remove('active');
            });
            
            // Show current step
            document.querySelector(`.form-step[data-step="${currentStep}"]`).classList.add('active');
            
            // Update step indicator
            document.querySelectorAll('.step').forEach(step => {
                const stepNum = parseInt(step.dataset.step);
                step.classList.remove('active', 'completed');
                
                if (stepNum === currentStep) {
                    step.classList.add('active');
                } else if (stepNum < currentStep) {
                    step.classList.add('completed');
                }
            });
            
            // Update navigation buttons
            const btnPrev = document.querySelector('.btn-prev');
            const btnNext = document.querySelector('.btn-next');
            const btnSubmit = document.querySelector('.btn-submit');
            
            btnPrev.style.display = currentStep === 1 ? 'none' : 'block';
            btnNext.style.display = currentStep === totalSteps ? 'none' : 'block';
            btnSubmit.style.display = currentStep === totalSteps ? 'block' : 'none';
            
            // Initialize map when reaching step 4
            if (currentStep === 4 && !map) {
                setTimeout(initMap, 100);
            }
        }
        
        function validateStep(step) {
            const currentStepElement = document.querySelector(`.form-step[data-step="${step}"]`);
            const inputs = currentStepElement.querySelectorAll('[required]');
            
            for (let input of inputs) {
                if (!input.value.trim()) {
                    input.focus();
                    input.classList.add('is-invalid');
                    alert('Por favor completa todos los campos obligatorios (*)');
                    return false;
                }
                input.classList.remove('is-invalid');
            }
            
            return true;
        }
        
        // Price converter
        document.getElementById('priceUSD').addEventListener('input', function() {
            const priceUSD = parseFloat(this.value);
            
            if (priceUSD > 0) {
                // AiNi Rewards: 1 USD = 100 Rewards (stable)
                const rewards = Math.round(priceUSD * 100);
                
                // AiNi Crypto: ~1 USD per coin (volatile, fetch from API in production)
                const crypto = (priceUSD / 0.9728).toFixed(2);
                
                document.getElementById('priceRewards').textContent = rewards.toLocaleString() + ' 🪙';
                document.getElementById('priceCrypto').textContent = crypto + ' 💎';
                document.getElementById('priceConverter').style.display = 'block';
                
                // Set hidden inputs for form submission
                document.querySelector('input[name="price_aini_rewards"]')?.remove();
                document.querySelector('input[name="price_aini_crypto"]')?.remove();
                
                const form = document.getElementById('experienceForm');
                form.insertAdjacentHTML('beforeend', `<input type="hidden" name="price_aini_rewards" value="${rewards}">`);
                form.insertAdjacentHTML('beforeend', `<input type="hidden" name="price_aini_crypto" value="${crypto}">`);
            } else {
                document.getElementById('priceConverter').style.display = 'none';
            }
        });
        
        // Photo preview
        function previewCover(input) {
            const preview = document.getElementById('coverPreview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <div class="photo-preview-item">
                            <img src="${e.target.result}" alt="Cover">
                        </div>
                    `;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function previewGallery(input) {
            const preview = document.getElementById('galleryPreview');
            preview.innerHTML = '';
            
            if (input.files) {
                Array.from(input.files).slice(0, 10).forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML += `
                            <div class="photo-preview-item">
                                <img src="${e.target.result}" alt="Photo ${index + 1}">
                                <button type="button" class="remove-photo" onclick="removeGalleryPhoto(${index})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        `;
                    };
                    reader.readAsDataURL(file);
                });
            }
        }
        
        // Form submission
        document.getElementById('experienceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!validateStep(currentStep)) {
                return;
            }
            
            // Show loading
            const submitBtn = document.querySelector('.btn-submit');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            submitBtn.disabled = true;
            
            // Submit form
            const formData = new FormData(this);
            
            fetch('experience_submit.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    localStorage.removeItem('experienceFormData'); // Clear auto-save
                    alert('¡Experiencia registrada con éxito! Nuestro equipo la revisará pronto.');
                    window.location.href = 'experiences_list.php';
                } else {
                    alert('Error: ' + data.message);
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al enviar el formulario. Por favor intenta de nuevo.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
        
        // ========================================
        // AUTO-SAVE TO LOCALSTORAGE
        // ========================================
        function saveFormData() {
            const formData = {};
            const form = document.getElementById('experienceForm');
            const inputs = form.querySelectorAll('input, textarea, select');
            
            inputs.forEach(input => {
                if (input.type === 'file') return; // Skip file inputs
                if (input.name === 'csrf_token') return; // Skip CSRF token
                formData[input.name || input.id] = input.value;
            });
            
            localStorage.setItem('experienceFormData', JSON.stringify(formData));
            console.log('Form auto-saved');
        }
        
        function loadFormData() {
            const savedData = localStorage.getItem('experienceFormData');
            if (!savedData) return;
            
            const formData = JSON.parse(savedData);
            const form = document.getElementById('experienceForm');
            
            Object.keys(formData).forEach(key => {
                // Escape special characters in attribute selector
                const escapedKey = key.replace(/\[/g, '\\[').replace(/\]/g, '\\]');
                const input = form.querySelector(`[name="${escapedKey}"], #${escapedKey}`);
                if (input && input.type !== 'file') {
                    input.value = formData[key];
                }
            });
            
            console.log('Form data restored from auto-save');
        }
        
        // Auto-save every 5 seconds
        setInterval(saveFormData, 5000);
        
        // Save on every input change
        document.querySelectorAll('input, textarea, select').forEach(input => {
            input.addEventListener('change', saveFormData);
        });
        
        // Load saved data on page load
        loadFormData();
        
        // Initialize map on page load
        initMap();
        
        // Clear saved data on successful submit
        document.getElementById('experienceForm').addEventListener('submit', function(e) {
            // Will clear after successful submission in the fetch success block
        });
    </script>
</body>
</html>
