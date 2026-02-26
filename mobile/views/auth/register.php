<!-- TRAVELER REGISTER -->
<div class="aini-auth-card">

    <div class="text-center mb-3">
        <div style="font-size:2.5rem; background:var(--aini-gradient); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">
            <?php echo icon('register', '40'); ?>
        </div>
        <div class="aini-auth-title mt-2">Crear Cuenta</div>
        <div class="aini-auth-sub">Únete y gana <strong>50 AiNi Coins</strong> de bienvenida</div>
    </div>

    <!-- Google OAuth -->
    <a href="/google_oauth.php" class="aini-google-btn mb-2">
        <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="20" alt="Google">
        Registrarse con Google
    </a>

    <div class="aini-divider">o con tu email</div>

    <?php if (!empty($registerError)): ?>
    <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:0.85rem;">
        <span class="fw-semibold">&#9888;</span> <?php echo htmlspecialchars($registerError); ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="/register.php" id="registerForm">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
        <input type="hidden" name="mobile_redirect" value="/mobile/">

        <div class="mb-3">
            <label class="form-label small fw-semibold">Nombre completo</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><?php echo icon('person', '16'); ?></span>
                <input type="text" name="name" class="form-control" 
                       placeholder="Tu nombre" required autocomplete="name">
            </div>
        </div>

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
                       placeholder="Mínimo 6 caracteres" required minlength="6" id="regPwd">
                <button class="btn btn-outline-secondary" type="button" onclick="toggleRegPwd()">
                    <span id="regEye">👁</span>
                </button>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label small fw-semibold">Confirmar contraseña</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><?php echo icon('lock', '16'); ?></span>
                <input type="password" name="confirm_password" class="form-control" 
                       placeholder="Repite tu contraseña" required id="regPwd2">
            </div>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="terms" id="termsCheck" required>
            <label class="form-check-label small" for="termsCheck">
                Acepto los <a href="/mobile/?page=terms" style="color:var(--aini-primary);">Términos</a> y 
                <a href="/mobile/?page=privacy" style="color:var(--aini-primary);">Privacidad</a>
            </label>
        </div>

        <button type="submit" name="register" class="btn aini-btn-primary w-100 py-2 mb-3" style="font-size:1rem;">
            <?php echo icon('login', '16'); ?> Crear cuenta gratis
        </button>
    </form>

    <div class="text-center small text-muted">
        ¿Ya tienes cuenta? 
        <a href="/mobile/?page=login" class="fw-semibold" style="color:var(--aini-primary);">Iniciar sesión</a>
    </div>
</div>

<script>
function toggleRegPwd() {
    const f = document.getElementById('regPwd');
    const e = document.getElementById('regEye');
    f.type = f.type === 'password' ? 'text' : 'password';
    e.textContent = f.type === 'password' ? '\uD83D\uDC41' : '\uD83D\uDE48';
}
// Confirm password match
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const p1 = document.getElementById('regPwd').value;
    const p2 = document.getElementById('regPwd2').value;
    if (p1 !== p2) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
    }
});
</script>
