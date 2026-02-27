<!-- PARTNER LOGIN -->
<div class="aini-auth-card">

    <div class="text-center mb-3">
        <div style="font-size:2.5rem; color:var(--aini-secondary);">
            <?php echo icon('building', '40'); ?>
        </div>
        <div class="aini-auth-title mt-2">Partner Login</div>
        <div class="aini-auth-sub">Accede al panel de gestión de tu propiedad</div>
    </div>

    <?php if (!empty($partnerLoginError)): ?>
    <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:0.85rem;">
        <span class="fw-semibold">&#9888;</span> <?php echo htmlspecialchars($partnerLoginError); ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="?page=partner_login">
        <input type="hidden" name="redirect" value="partner_dashboard">

        <div class="mb-3">
            <label class="form-label small fw-semibold">Email</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><?php echo icon('envelope', '16'); ?></span>
                <input type="email" name="email" class="form-control" 
                       placeholder="owner@hotel.com" required autocomplete="email">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label small fw-semibold">Contraseña</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><?php echo icon('lock', '16'); ?></span>
                <input type="password" name="password" class="form-control" 
                       placeholder="········" required id="partnerPwd">
                <button class="btn btn-outline-secondary" type="button" onclick="togglePartnerPwd()">
                    <span id="partnerEye">👁</span>
                </button>
            </div>
        </div>

        <button type="submit" name="partner_login" 
                class="btn w-100 py-2 mb-3 fw-bold text-white"
                style="background:var(--aini-gradient); border-radius:10px; font-size:1rem;">
            <?php echo icon('login', '16'); ?> Acceder al Panel
        </button>
    </form>

    <div class="text-center small text-muted mb-3">
        ¿Sin cuenta de partner?
        <a href="/partner_register.php" class="fw-semibold" style="color:var(--aini-secondary);">
            Registra tu propiedad
        </a>
    </div>

    <hr class="my-3">

    <div class="text-center small text-muted">
        ¿Eres viajero?
        <a href="/mobile/?page=login" class="fw-semibold" style="color:var(--aini-primary);">
            Login de viajero
        </a>
    </div>
</div>

<script>
function togglePartnerPwd() {
    const f = document.getElementById('partnerPwd');
    const e = document.getElementById('partnerEye');
    f.type = f.type === 'password' ? 'text' : 'password';
    e.textContent = f.type === 'password' ? '\uD83D\uDC41' : '\uD83D\uDE48';
}
</script>
