/**
 * AiNi Travel Mobile — Hotel Loading & Display
 * Bootstrap 5 card renderer — NO Tailwind, NO icon fonts
 */

// Inline SVG icons — zero CDN dependency, works on all devices
const SVG = {
    pin:  '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A31.493 31.493 0 0 1 8 14.58a31.481 31.481 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94zM8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10z"/><path d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>',
    star: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#f59e0b" viewBox="0 0 16 16"><path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/></svg>',
    coin: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#f59e0b" viewBox="0 0 16 16"><path d="M5.5 9.511c.076.954.83 1.697 2.182 1.785V12h.6v-.709c1.4-.098 2.218-.846 2.218-1.932 0-.987-.626-1.496-1.745-1.76l-.473-.112V5.57c.6.068.982.396 1.074.85h1.052c-.076-.919-.864-1.638-2.126-1.716V4h-.6v.719c-1.195.117-2.01.836-2.01 1.853 0 .9.606 1.472 1.613 1.707l.397.098v2.034c-.615-.093-1.022-.43-1.114-.9H5.5zm2.177-2.166c-.59-.137-.91-.416-.91-.836 0-.47.345-.822.915-.925v1.76h-.005zm.692 1.193c.717.166 1.048.435 1.048.91 0 .542-.412.914-1.135.982V8.518l.087.02z"/><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/></svg>',
    door: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M8.5 10c-.276 0-.5-.448-.5-1s.224-1 .5-1 .5.448.5 1-.224 1-.5 1Z"/><path d="M10.828.122A.5.5 0 0 1 11 .5V1h.5A1.5 1.5 0 0 1 13 2.5V15h1.5a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1H3V1.5a.5.5 0 0 1 .43-.495l7-1a.5.5 0 0 1 .398.117zM11.5 2H11v13h1V2.5a.5.5 0 0 0-.5-.5zM4 1.934V15h6V1.077l-6 .857z"/></svg>',
    cal:  '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>',
    search: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>',
    map:  '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M15.817.113A.5.5 0 0 1 16 .5v14a.5.5 0 0 1-.402.49l-5 1a.502.502 0 0 1-.196 0L5.5 15.01l-4.902.98A.5.5 0 0 1 0 15.5v-14a.5.5 0 0 1 .402-.49l5-1a.5.5 0 0 1 .196 0L10.5.99l4.902-.98a.5.5 0 0 1 .415.103zM10 1.91l-4-.8v12.98l4 .8V1.91zm1 12.98 4-.8V1.11l-4 .8v12.98zm-6-.82V1.11l-4 .8v12.98l4-.8z"/></svg>',
    list: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/></svg>',
    wa:   '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg>',
};

let allHotels = [];

// ── Load from API ─────────────────────────────────────────────────────────────
function loadHotels() {
    fetch('/api/get_hotels.php')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.hotels && data.hotels.length) {
                allHotels = data.hotels;
                displayHotels(allHotels);
            } else {
                loadSampleHotels();
            }
        })
        .catch(() => loadSampleHotels());
}

// ── Render cards ─────────────────────────────────────────────────────────────
function displayHotels(hotels) {
    const el = document.getElementById('hotelsList');
    const countEl = document.getElementById('hotelCount');

    if (!hotels.length) {
        el.innerHTML = `
            <div class="aini-empty">
                ${SVG.search}
                <div class="mt-2 fw-semibold">Sin resultados</div>
                <div class="small mt-1">Intenta con otro destino</div>
                <button class="btn aini-btn-outline mt-3" onclick="clearSearch()">Ver todos</button>
            </div>`;
        if (countEl) countEl.textContent = 0;
        return;
    }

    // Render cards with staggered animation
    el.innerHTML = hotels.map((h, i) =>
        buildCard(h, i)
    ).join('');

    // Trigger animation frame so CSS transition fires
    requestAnimationFrame(() => {
        el.querySelectorAll('.aini-hotel-card').forEach((card, i) => {
            card.style.animationDelay = (i * 0.06) + 's';
            card.classList.add('aini-animate');
        });
    });

    // Pop the count badge
    if (countEl) {
        countEl.textContent = hotels.length;
        countEl.classList.remove('aini-pop');
        void countEl.offsetWidth; // reflow to restart animation
        countEl.classList.add('aini-pop');
    }
}

function buildCard(h, i = 0) {
    // Use smaller Unsplash images on mobile
    const imgSrc = (h.images && h.images[0])
        ? h.images[0].replace(/\?.*$/, '') + '?w=400&q=70'
        : 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&q=70';

    const features = (h.features || []).slice(0, 3)
        .map(f => `<span class="aini-feature-pill">${f}</span>`)
        .join('');

    return `
    <div class="aini-hotel-card" onclick="viewHotel(${h.id})">
        <img src="${imgSrc}" 
             alt="${escHtml(h.name)}" 
             class="aini-hotel-img"
             loading="lazy"
             onerror="this.src='https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&q=70'">
        <div class="aini-hotel-body">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1 me-2">
                    <div class="aini-hotel-name">${escHtml(h.name)}</div>
                    <div class="aini-hotel-location">
                        ${SVG.pin} ${escHtml(h.location)}
                    </div>
                </div>
                <div class="aini-hotel-rating text-nowrap">
                    ${SVG.star} ${h.rating}
                </div>
            </div>

            <div class="aini-hotel-features">${features}</div>

            <div class="d-flex justify-content-between align-items-center mt-1">
                <div>
                    <span class="aini-hotel-price">$${h.price}</span>
                    <small>/noche</small>
                </div>
                <div class="aini-coins-badge">
                    ${SVG.coin} ${h.aini_coins} AiNi
                </div>
            </div>

            <div class="aini-hotel-btns">
                <button class="btn btn-outline-primary" onclick="event.stopPropagation(); viewHotel(${h.id})">
                    ${SVG.door} Ver Cuartos
                </button>
                <button class="btn aini-btn-primary" onclick="event.stopPropagation(); viewHotel(${h.id})">
                    ${SVG.cal} Reservar
                </button>
            </div>
        </div>
    </div>`;
}

// ── Search & filter ───────────────────────────────────────────────────────────
function searchHotels() {
    const dest   = document.getElementById('searchDestination').value.toLowerCase().trim();
    const guests = document.getElementById('searchGuests').value;

    if (!dest) {
        displayHotels(allHotels);
        return;
    }

    const terms = dest.split(/[\s,]+/).filter(t => t.length > 0);
    const filtered = allHotels.filter(h => {
        const text = `${h.name} ${h.location}`.toLowerCase();
        return terms.some(t => text.includes(t));
    });

    displayHotels(filtered);
}

function clearSearch() {
    document.getElementById('searchDestination').value = '';
    displayHotels(allHotels);
}

function filterCategory(cat, btn) {
    document.querySelectorAll('.aini-filter-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    if (cat === 'all') {
        displayHotels(allHotels);
        return;
    }
    displayHotels(allHotels.filter(h => (h.category || '').toLowerCase().includes(cat)));
}

// ── Hotel detail — navigate to full page ─────────────────────────────────────
function viewHotel(id) {
    window.location.href = '?page=hotel&id=' + id;
}

// Keep old offcanvas logic accessible if needed
function viewHotelOffcanvas(id) {
    const h = allHotels.find(x => x.id == id);
    if (!h) return;

    document.getElementById('hotelDetailName').textContent = h.name;

    // Build image gallery thumbnails
    const thumbs = (h.images || []).slice(0, 4).map((img, i) => `
        <img src="${img.replace(/\?.*$/, '')}?w=400&q=70" 
             class="rounded me-2 mb-2 ${i===0?'border border-primary':''}"
             style="width:72px;height:54px;object-fit:cover;cursor:pointer;"
             onclick="document.getElementById('detailMainImg').src='${img.replace(/\?.*$/, '')}?w=600&q=80'"
             loading="lazy">`
    ).join('');

    const features = (h.features || [])
        .map(f => `<span class="badge bg-light text-dark me-1 mb-1 border">${f}</span>`)
        .join('');

    document.getElementById('hotelDetailBody').innerHTML = `
        <img id="detailMainImg"
             src="${(h.images && h.images[0] ? h.images[0].replace(/\?.*$/, '')+'?w=600&q=80' : '')}"
             class="aini-detail-img mb-2"
             loading="lazy">
        <div class="d-flex gap-1 flex-wrap mb-3">${thumbs}</div>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <div class="fw-bold fs-5">${escHtml(h.name)}</div>
                <div class="text-muted small">${SVG.pin} ${escHtml(h.location)}</div>
            </div>
            <div class="text-warning fw-bold">${SVG.star} ${h.rating}</div>
        </div>

        <div class="mb-3">${features}</div>

        <div class="d-flex justify-content-between align-items-center p-3 rounded mb-3"
             style="background:#f0eaff;">
            <div>
                <div class="fw-bold fs-4" style="color:var(--aini-primary);">$${h.price}<small class="fs-6 fw-normal text-muted">/noche</small></div>
                <div class="small" style="color:var(--aini-coin);">${SVG.coin} Ganas ${h.aini_coins} AiNi Coins/noche</div>
            </div>
            <a href="https://maps.google.com/?q=${encodeURIComponent(h.location)}" 
               target="_blank" class="btn btn-sm btn-outline-secondary">
                ${SVG.map} Ver mapa
            </a>
        </div>

        <button class="btn aini-btn-primary w-100 py-2 mb-2" 
                onclick="bookHotel(${h.id}, '${escJs(h.name)}')"
                style="font-size:1rem; border-radius:10px;">
            ${SVG.cal} Reservar ahora
        </button>
        <a href="https://wa.me/1234567890?text=${encodeURIComponent('Hola AiNi, me interesa '+h.name+' en '+h.location)}"
           target="_blank"
           class="btn w-100 py-2 fw-bold"
           style="background:#25D366;color:white;border-radius:10px;font-size:1rem;">
            ${SVG.wa} Reservar por WhatsApp
        </a>`;

    new bootstrap.Offcanvas(document.getElementById('hotelDetail')).show();
}

// ── Booking modal ─────────────────────────────────────────────────────────────
function bookHotel(id, name) {
    const checkin  = document.getElementById('searchCheckin').value;
    const checkout = document.getElementById('searchCheckout').value;
    const guests   = document.getElementById('searchGuests').value;
    const h        = allHotels.find(x => x.id == id);
    const nights   = calcNights(checkin, checkout);
    const total    = h ? h.price * nights : 0;

    document.getElementById('bookingModalBody').innerHTML = `
        <div class="text-center mb-3">
            <img src="${h && h.images && h.images[0] ? h.images[0].replace(/\?.*$/,'')+'?w=300&q=70' : ''}" 
                 class="rounded" style="width:100%;height:140px;object-fit:cover;">
        </div>
        <h6 class="fw-bold">${escHtml(name)}</h6>
        <div class="row g-2 mb-3">
            <div class="col-6">
                <label class="form-label small fw-semibold">Check-in</label>
                <input type="date" class="form-control" id="bCheckin" value="${checkin}">
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold">Check-out</label>
                <input type="date" class="form-control" id="bCheckout" value="${checkout}">
            </div>
        </div>
        <div class="p-3 rounded mb-3" style="background:#f8f9fa;">
            <div class="d-flex justify-content-between small mb-1">
                <span>$${h ? h.price : 0} × ${nights} noches</span>
                <span>$${total}</span>
            </div>
            <div class="d-flex justify-content-between small mb-1 text-success">
                <span>${SVG.coin} AiNi Coins que ganarás</span>
                <span>${h ? h.aini_coins * nights : 0}</span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between fw-bold">
                <span>Total</span><span>$${total}</span>
            </div>
        </div>
        <a href="https://wa.me/1234567890?text=${encodeURIComponent('Quiero reservar '+name+' del '+checkin+' al '+checkout+' para '+guests+' huéspedes')}"
           target="_blank"
           class="btn w-100 py-2 fw-bold mb-2"
           style="background:#25D366;color:white;border-radius:10px;font-size:1rem;">
            ${SVG.wa} Confirmar por WhatsApp
        </a>`;

    // Close offcanvas first, then show modal
    const oc = bootstrap.Offcanvas.getInstance(document.getElementById('hotelDetail'));
    if (oc) oc.hide();
    setTimeout(() => new bootstrap.Modal(document.getElementById('bookingModal')).show(), 300);
}

// ── Fallback sample data ──────────────────────────────────────────────────────
function loadSampleHotels() {
    allHotels = [
        { id:1, name:"Ocean View Resort", location:"Miami Beach, FL", rating:4.8, price:180, aini_coins:36,
          category:"luxury beach", features:["Vista al Mar","Piscina","WiFi","Spa"],
          images:["https://images.unsplash.com/photo-1520250497591-112f2f40a3f4"] },
        { id:2, name:"Downtown Business Hotel", location:"New York, NY", rating:4.6, price:220, aini_coins:44,
          category:"business luxury", features:["Centro Negocios","Gimnasio","WiFi"],
          images:["https://images.unsplash.com/photo-1542314831-068cd1dbfeeb"] },
        { id:3, name:"Budget Traveler Inn", location:"Austin, TX", rating:4.3, price:85, aini_coins:17,
          category:"budget", features:["WiFi","Estacionamiento","Desayuno"],
          images:["https://images.unsplash.com/photo-1568495248636-6432b97bd949"] },
        { id:4, name:"Family Paradise Resort", location:"Orlando, FL", rating:4.7, price:195, aini_coins:39,
          category:"family luxury", features:["Kids Club","Piscina","Restaurante"],
          images:["https://images.unsplash.com/photo-1563911302283-d2bc129e7570"] },
        { id:5, name:"Beachfront Paradise", location:"Cancún, México", rating:4.9, price:250, aini_coins:50,
          category:"luxury beach", features:["Todo Incluido","Playa","Spa"],
          images:["https://images.unsplash.com/photo-1499793983690-e29da59ef1c2"] },
    ];
    displayHotels(allHotels);
}

// ── Utils ─────────────────────────────────────────────────────────────────────
function calcNights(c1, c2) {
    if (!c1 || !c2) return 1;
    const d = (new Date(c2) - new Date(c1)) / 86400000;
    return d > 0 ? Math.ceil(d) : 1;
}
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escJs(s) {
    return String(s).replace(/'/g,"\\'").replace(/\n/g,' ');
}

// ── Map (lazy Leaflet) ────────────────────────────────────────────────────────
let mapLoaded = false;
let mobileMap = null;

function toggleMapView() {
    const container = document.getElementById('mobileMapContainer');
    const listEl    = document.getElementById('hotelsList');
    const isShowing = container.style.display !== 'none';

    if (isShowing) {
        container.style.display = 'none';
        listEl.style.display = '';
        return;
    }

    // Switch to map
    listEl.style.display = 'none';
    container.style.display = 'block';

    if (!mapLoaded) {
        // Lazy-load Leaflet CSS + JS
        const css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(css);

        const js = document.createElement('script');
        js.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        js.onload = () => {
            mapLoaded = true;
            initMobileMap();
        };
        document.body.appendChild(js);
    } else if (mobileMap) {
        mobileMap.invalidateSize();
    }
}

function initMobileMap() {
    // Start at world view, we'll move once we have location
    mobileMap = L.map('mobileMap').setView([20, -80], 3);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 18
    }).addTo(mobileMap);

    // Add hotel markers
    const pts = [];
    allHotels.forEach(h => {
        if (!h.latitude || !h.longitude) return;
        pts.push([h.latitude, h.longitude]);
        L.marker([h.latitude, h.longitude])
            .addTo(mobileMap)
            .bindPopup(`
                <b>${escHtml(h.name)}</b><br>
                <span style="color:#6b7280;font-size:.8rem">${escHtml(h.location)}</span><br>
                <b style="color:#667eea">$${h.price}/noche</b><br>
                <div style="display:flex;gap:6px;margin-top:8px;">
                    <button onclick="toggleMapView(); viewHotel(${h.id})"
                            style="flex:1;padding:5px 8px;background:#667eea;color:white;border:none;border-radius:6px;cursor:pointer;font-size:.8rem;">
                        🏨 Ver hotel
                    </button>
                    <a href="https://www.google.com/maps/dir/?api=1&destination=${h.latitude},${h.longitude}&travelmode=driving"
                       target="_blank"
                       style="flex:1;padding:5px 8px;background:#25D366;color:white;border:none;border-radius:6px;cursor:pointer;font-size:.8rem;text-decoration:none;text-align:center;">
                        🧭 Cómo llegar
                    </a>
                </div>
            `);
    });

    // Try to zoom to user's current location
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;

                // "You are here" marker with a pulsing blue dot
                const youIcon = L.divIcon({
                    html: '<div style="width:16px;height:16px;background:#2563eb;border:3px solid white;border-radius:50%;box-shadow:0 0 0 4px rgba(37,99,235,0.3);"></div>',
                    iconSize: [16, 16],
                    iconAnchor: [8, 8],
                    className: ''
                });
                L.marker([lat, lng], { icon: youIcon })
                    .addTo(mobileMap)
                    .bindPopup('<b>📍 Estás aquí</b>')
                    .openPopup();

                // Zoom to user location, then show nearby hotels
                mobileMap.setView([lat, lng], 12);
            },
            () => {
                // Permission denied or unavailable — fall back to hotel bounds
                if (pts.length > 1) mobileMap.fitBounds(pts, { padding: [30, 30], maxZoom: 8 });
                else if (pts.length === 1) mobileMap.setView(pts[0], 12);
            },
            { timeout: 8000, maximumAge: 60000 }
        );
    } else {
        // No geolocation API — fit to hotel markers
        if (pts.length > 1) mobileMap.fitBounds(pts, { padding: [30, 30], maxZoom: 8 });
    }
}

// ── Boot ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', loadHotels);
