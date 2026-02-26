<!-- TRAVELER LOGIN -->
<div class="aini-auth-card">

    <!-- Header -->
    <div class="text-center mb-3">
        <div style="font-size:2.5rem; background:var(--aini-gradient); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">
            <?php echo icon('plane', '40'); ?>
        </div>
        <div class="aini-auth-title mt-2">Bienvenido</div>
        <div class="aini-auth-sub">Inicia sesión para gestionar tus reservas y AiNi Coins</div>
    </div>

    <!-- Google OAuth button -->
    <a href="/google_oauth.php" class="aini-google-btn mb-2">
        <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="20" alt="Google">
        Continuar con Google
    </a>

    <div class="aini-divider">o con tu email</div>

    <!-- Error / success messages -->
    <?php if (!empty($loginError)): ?>
    <div class="alert alert-danger alert-sm py-2 px-3 mb-3" style="font-size:0.85rem;">
        <span class="fw-semibold">&#9888;</span> <?php echo htmlspecialchars($loginError); ?>
    </div>
    <?php endif; ?>

    <!-- Email / password form — POSTs to existing login.php handler -->
    <form method="POST" action="/login.php" id="loginForm">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
        <input type="hidden" name="mobile_redirect" value="/mobile/">

        <div class="mb-3">
            <label class="form-label small fw-semibold">Email</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><?php echo icon('envelope', '16'); ?></span>
                <input type="email" name="email" class="form-control" 
                       placeholder="tu@email.com" required autocomplete="email">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label small fw-semibold">Contraseña</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><?php echo icon('lock', '16'); ?></span>
                <input type="password" name="password" class="form-control" 
                       placeholder="········" required autocomplete="current-password" id="pwdField">
                <button class="btn btn-outline-secondary" type="button" onclick="togglePwd()">
                    <span id="eyeIcon">👁</span>
                </button>
            </div>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="remember_me" id="rememberMe">
            <label class="form-check-label small" for="rememberMe">Recordarme 30 días</label>
        </div>

        <button type="submit" name="login" class="btn aini-btn-primary w-100 py-2 mb-3" style="font-size:1rem;">
            <?php echo icon('login', '16'); ?> Iniciar Sesión
        </button>
    </form>

    <div class="text-center mt-1 mb-1 small text-muted">¿No tienes cuenta?</div>
    <a href="/mobile/?page=register" class="btn w-100 py-2 fw-bold mb-3"
       style="background:#f0eaff; color:var(--aini-primary); border-radius:10px; font-size:0.95rem; border:2px solid var(--aini-primary);">
        ✨ Crear cuenta gratis
    </a>

    <hr class="my-3">

    <div class="text-center small text-muted">
        ¿Eres propietario de hotel?
        <a href="/mobile/?page=partner_login" class="fw-semibold" style="color:var(--aini-secondary);">
            <?php echo icon('badge', '16'); ?> Partner Login
        </a>
    </div>
</div>

<script>
function togglePwd() {
    const f = document.getElementById('pwdField');
    const e = document.getElementById('eyeIcon');
    if (f.type === 'password') {
        f.type = 'text';
        e.textContent = '\uD83D\uDE48';
    } else {
        f.type = 'password';
        e.textContent = '\uD83D\uDC41';
    }
}
</script>
