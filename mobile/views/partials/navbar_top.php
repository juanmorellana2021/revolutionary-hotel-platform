<?php
$lang    = $_SESSION['lang'] ?? 'es';
$flagMap = [
    'es' => 'es', 'en' => 'us', 'pt' => 'br',
    'fr' => 'fr', 'de' => 'de', 'it' => 'it',
];
$currentFlag = $flagMap[$lang] ?? 'es';
$langLabels  = ['es'=>'ES','en'=>'EN','pt'=>'PT','fr'=>'FR','de'=>'DE','it'=>'IT'];
?>
<!-- TOP NAVBAR -->
<nav class="navbar navbar-light bg-white shadow-sm fixed-top aini-topnav">
    <div class="container-fluid px-3">

        <!-- Logo -->
        <a class="navbar-brand aini-logo" href="/mobile/">
            ✈️ AiNi Travel
        </a>

        <!-- Right side: search (hotels page only) + lang + login/avatar -->
        <div class="d-flex align-items-center gap-2">

            <!-- Search toggle — only shown on hotels page -->
            <?php if (($page ?? 'hotels') === 'hotels'): ?>
            <button onclick="toggleSearchCard()" id="navSearchBtn" title="Buscar"
                    style="background:var(--aini-gradient); border:none; border-radius:50%; width:34px; height:34px; color:white; font-size:1rem; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(102,126,234,0.4); flex-shrink:0;">
                🔍
            </button>
            <?php endif; ?>

            <!-- Language picker -->
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle lang-btn" 
                        type="button" data-bs-toggle="dropdown">
                    <span class="fi fi-<?php echo $currentFlag; ?>"></span>
                    <?php echo $langLabels[$lang] ?? 'ES'; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php foreach ($flagMap as $code => $flag): ?>
                    <li>
                        <a class="dropdown-item <?php echo $lang === $code ? 'active' : ''; ?>"
                           href="/mobile/set_language.php?lang=<?php echo $code; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">
                            <span class="fi fi-<?php echo $flag; ?>"></span>
                            <?php echo $langLabels[$code]; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Auth state -->
            <?php if ($isLoggedIn): ?>
            <div class="dropdown">
                <button class="btn btn-sm aini-avatar-btn dropdown-toggle" 
                        type="button" data-bs-toggle="dropdown">
                    <?php echo icon('person', '24'); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text fw-bold"><?php echo htmlspecialchars($userName); ?></span></li>
                    <li><a class="dropdown-item" href="/mobile/?page=profile"><?php echo icon('person', '16'); ?> Perfil</a></li>
                    <li><a class="dropdown-item" href="/mobile/?page=wallet"><?php echo icon('coin', '16'); ?> AiNi Coins: <?php echo $ainiCoins; ?></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="/logout.php"><?php echo icon('logout', '16'); ?> Salir</a></li>
                </ul>
            </div>
            <?php elseif ($isPartner): ?>
            <div class="dropdown">
                <button class="btn btn-sm aini-avatar-btn dropdown-toggle" 
                        type="button" data-bs-toggle="dropdown">
                    <?php echo icon('badge', '24'); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text fw-bold"><?php echo htmlspecialchars($partnerName); ?></span></li>
                    <li><a class="dropdown-item" href="/mobile/?page=partner_dashboard"><?php echo icon('dashboard', '16'); ?> Dashboard</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="/partners/logout.php"><?php echo icon('logout', '16'); ?> Salir</a></li>
                </ul>
            </div>
            <?php else: ?>
            <a href="/mobile/?page=login" class="btn btn-sm aini-btn-primary">
                <?php echo icon('login', '16'); ?> Login
            </a>
            <?php endif; ?>

        </div>
    </div>
</nav>
<!-- spacer for fixed top navbar -->
<div style="height:56px;"></div>
