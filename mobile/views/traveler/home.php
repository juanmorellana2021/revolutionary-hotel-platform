<!-- HOME — Search + Hotel List -->

<!-- Search Card (collapsed by default, toggled by navbar button) -->
<div id="searchCardWrapper" style="overflow:hidden; max-height:0; transition:max-height 0.35s ease;">
<div class="aini-search-card">
    <div class="row g-2">
        <div class="col-12">
            <div class="input-group">
                <span class="input-group-text bg-white" style="border-right:none;">
                    🌍
                </span>
                <input type="text" id="searchDestination" class="form-control" 
                       placeholder="¿A dónde vas?" autocomplete="off"
                       style="border-left:none;">
            </div>
        </div>
        <div class="col-6">
            <input type="text" id="searchCheckin" class="form-control" 
                   placeholder="📅 Check-in"
                   onfocus="this.type='date'; this.placeholder=''"
                   onblur="if(!this.value){this.type='text'; this.placeholder='📅 Check-in';}">
        </div>
        <div class="col-6">
            <input type="text" id="searchCheckout" class="form-control" 
                   placeholder="📅 Check-out"
                   onfocus="this.type='date'; this.placeholder=''"
                   onblur="if(!this.value){this.type='text'; this.placeholder='📅 Check-out';}">
        </div>
        <div class="col-8">
            <select id="searchGuests" class="form-select" onchange="toggleGroupInput(this)">
                <option value="1">1 huésped</option>
                <option value="2" selected>2 huéspedes</option>
                <option value="3">3 huéspedes</option>
                <option value="4">4 huéspedes</option>
                <option value="5">5 huéspedes</option>
                <option value="6">6 huéspedes</option>
                <option value="7">7 huéspedes</option>
                <option value="8">8 huéspedes</option>
                <option value="group">👥 Grupo (más de 8)</option>
            </select>
        </div>
        <!-- Group size input — shown only when "Grupo" is selected -->
        <div class="col-8" id="groupSizeRow" style="display:none;">
            <input type="number" id="groupSize" class="form-control" 
                   min="9" max="200" placeholder="¿Cuántas personas?" 
                   oninput="document.getElementById('searchGuests').value=this.value||'group'">
        </div>
        <div class="col-4">
            <button class="aini-search-btn" onclick="searchHotels(); toggleSearchCard(false)">
                🔎 Buscar
            </button>
        </div>
    </div>
</div>
</div><!-- /searchCardWrapper -->

<!-- Lazy Leaflet map (hidden by default) -->
<div id="mobileMapContainer" style="display:none; height:55vh; margin:0 12px 14px; border-radius:12px; overflow:hidden;">
    <div id="mobileMap" style="height:100%;"></div>
</div>

<!-- Filter Pills -->
<div class="aini-filters">
    <button class="aini-filter-pill active" onclick="filterCategory('all', this)" data-i18n>
        Todos
    </button>
    <button class="aini-filter-pill" onclick="filterCategory('luxury', this)" data-i18n>
        Lujo
    </button>
    <button class="aini-filter-pill" onclick="filterCategory('budget', this)" data-i18n>
        Economico
    </button>
    <button class="aini-filter-pill" onclick="filterCategory('beach', this)" data-i18n>
        Playa
    </button>
    <button class="aini-filter-pill" onclick="filterCategory('business', this)" data-i18n>
        Negocios
    </button>
    <button class="aini-filter-pill" onclick="filterCategory('family', this)" data-i18n>
        Familiar
    </button>
</div>

<!-- Section title -->
<div class="aini-section-title">
    <span data-i18n>Propiedades Disponibles</span>
    <span class="aini-count-badge" id="hotelCount">...</span>
</div>

<!-- Hotel list container -->
<div id="hotelsList">
    <div class="aini-loading">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-2 small">Cargando hoteles increíbles...</div>
    </div>
</div>

<!-- Hotel Detail Offcanvas (slides up from bottom) -->
<div class="offcanvas offcanvas-bottom aini-detail-canvas" tabindex="-1" id="hotelDetail">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold" id="hotelDetailName">Hotel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body" id="hotelDetailBody" style="overflow-y:auto;">
        <!-- Populated by JS -->
    </div>
</div>

<!-- Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Confirmar Reserva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="bookingModalBody">
                <!-- Populated by JS -->
            </div>
        </div>
    </div>
</div>

<!-- Sofia AI floating button -->
<button class="aini-sofia-fab" onclick="openSofia()" title="Sofia IA">
    💬
</button>

<!-- Sofia Chat Offcanvas -->
<div class="offcanvas offcanvas-bottom" tabindex="-1" id="sofiaChat" style="height:75vh; border-radius:20px 20px 0 0;">
    <div class="offcanvas-header" style="background:var(--aini-gradient); color:white; border-radius:20px 20px 0 0;">
        <h5 class="offcanvas-title">💬 Sofia IA</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-0">
        <div id="sofiaMessages" style="flex:1; overflow-y:auto; padding:16px;">
            <div class="aini-sofia-msg bot">
                <div class="aini-sofia-bubble">
                    ¡Hola! Soy Sofia, tu asistente de viajes. ¿En qué te ayudo hoy? 🏨
                </div>
            </div>
        </div>
        <div class="p-3 border-top">
            <div class="input-group">
                <input type="text" id="sofiaInput" class="form-control" 
                       placeholder="Pregúntame sobre hoteles..." 
                       onkeypress="if(event.key==='Enter') sendSofia()">
                <button class="btn aini-btn-primary px-3" onclick="sendSofia()">
                    <?php echo icon('send', '16'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JS: hotels + search + sofia -->
<script src="/mobile/js/hotels.js?v=2.2"></script>
<script src="/mobile/js/sofia.js?v=2.0"></script>
<script>
function toggleGroupInput(sel) {
    const row = document.getElementById('groupSizeRow');
    if (sel.value === 'group') {
        row.style.display = 'block';
        document.getElementById('groupSize').focus();
    } else {
        row.style.display = 'none';
    }
}
function toggleSearchCard(forceOpen) {
    const wrapper = document.getElementById('searchCardWrapper');
    const btn = document.getElementById('navSearchBtn');
    const isOpen = wrapper.style.maxHeight !== '0px' && wrapper.style.maxHeight !== '';
    const open = forceOpen !== undefined ? forceOpen : !isOpen;
    wrapper.style.maxHeight = open ? '600px' : '0px';
    if (btn) btn.innerHTML = open ? '✕' : '🔍';
}
</script>

<style>
/* Sofia chat bubbles */
.aini-sofia-msg { margin-bottom: 10px; }
.aini-sofia-msg.bot .aini-sofia-bubble {
    background: #f0eaff;
    color: #333;
    border-radius: 0 12px 12px 12px;
    padding: 10px 14px;
    max-width: 85%;
    display: inline-block;
    font-size: 0.88rem;
}
.aini-sofia-msg.user { text-align: right; }
.aini-sofia-msg.user .aini-sofia-bubble {
    background: var(--aini-gradient);
    color: white;
    border-radius: 12px 12px 0 12px;
    padding: 10px 14px;
    max-width: 85%;
    display: inline-block;
    font-size: 0.88rem;
}
</style>
