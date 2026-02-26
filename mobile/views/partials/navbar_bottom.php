<?php
$currentPage = $_GET['page'] ?? 'hotels';

// Unread message count (quick query)
$unreadMessages = 0;
if ($isLoggedIn || $isPartner) {
    try {
        require_once __DIR__ . '/../../../db_connection_pdo.php';
        if ($isLoggedIn) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking_messages WHERE recipient_id = ? AND is_read = 0");
            $stmt->execute([$_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking_messages WHERE partner_id = ? AND is_read = 0 AND sender_type='user'");
            $stmt->execute([$partnerId]);
        }
        $unreadMessages = (int)$stmt->fetchColumn();
    } catch(Exception $e) { /* silently fail */ }
}
?>
<!-- BOTTOM TAB BAR -->
<nav class="navbar fixed-bottom aini-bottom-nav">
    <div class="container-fluid p-0">
        <div class="row g-0 w-100 text-center">

        <?php if ($isPartner): ?>
            <!-- PARTNER bottom nav -->
            <div class="col">
                <a href="/mobile/?page=partner_dashboard" class="aini-tab <?php echo $currentPage==='partner_dashboard'?'active':''; ?>">
                    <?php echo icon('dashboard', '20'); ?><span>Home</span>
                </a>
            </div>
            <div class="col">
                <a href="/mobile/?page=partner_bookings" class="aini-tab <?php echo $currentPage==='partner_bookings'?'active':''; ?>">
                    <?php echo icon('calendar', '20'); ?><span>Reservas</span>
                </a>
            </div>
            <div class="col position-relative">
                <a href="/mobile/?page=partner_messages" class="aini-tab <?php echo $currentPage==='partner_messages'?'active':''; ?>">
                    <?php echo icon('chat', '20'); ?>
                    <?php if($unreadMessages > 0): ?><span class="aini-badge"><?php echo $unreadMessages; ?></span><?php endif; ?>
                    <span>Mensajes</span>
                </a>
            </div>
            <div class="col">
                <a href="/mobile/?page=partner_rooms" class="aini-tab <?php echo $currentPage==='partner_rooms'?'active':''; ?>">
                    <?php echo icon('door', '20'); ?><span>Cuartos</span>
                </a>
            </div>
            <div class="col">
                <a href="/mobile/?page=partner_profile" class="aini-tab <?php echo $currentPage==='partner_profile'?'active':''; ?>">
                    <?php echo icon('badge', '20'); ?><span>Perfil</span>
                </a>
            </div>

        <?php elseif ($isLoggedIn): ?>
            <!-- TRAVELER logged-in bottom nav -->
            <div class="col">
                <a href="/mobile/?page=hotels" class="aini-tab <?php echo $currentPage==='hotels'?'active':''; ?>">
                    <?php echo icon('building', '20'); ?><span>Hoteles</span>
                </a>
            </div>
            <div class="col">
                <a href="/mobile/?page=hotels" class="aini-tab"
                   onclick="if(typeof toggleMapView==='function'){event.preventDefault();toggleMapView();}">
                    <?php echo icon('map', '20'); ?><span>Mapa</span>
                </a>
            </div>
            <div class="col position-relative">
                <a href="/mobile/?page=messages" class="aini-tab <?php echo $currentPage==='messages'?'active':''; ?>">
                    <?php echo icon('chat', '20'); ?>
                    <?php if($unreadMessages > 0): ?><span class="aini-badge"><?php echo $unreadMessages; ?></span><?php endif; ?>
                    <span>Mensajes</span>
                </a>
            </div>
            <div class="col">
                <a href="/mobile/?page=bookings" class="aini-tab <?php echo $currentPage==='bookings'?'active':''; ?>">
                    <?php echo icon('calendar', '20'); ?><span>Reservas</span>
                </a>
            </div>

        <?php else: ?>
            <!-- GUEST bottom nav -->
            <div class="col">
                <a href="/mobile/?page=hotels" class="aini-tab <?php echo $currentPage==='hotels'?'active':''; ?>">
                    <?php echo icon('building', '20'); ?><span>Hoteles</span>
                </a>
            </div>
            <div class="col">
                <a href="/mobile/?page=hotels" class="aini-tab"
                   onclick="if(typeof toggleMapView==='function'){event.preventDefault();toggleMapView();}">
                    <?php echo icon('map', '20'); ?><span>Mapa</span>
                </a>
            </div>
            <div class="col">
                <a href="/mobile/?page=login" class="aini-tab <?php echo in_array($currentPage,['login','register'])?'active':''; ?>">
                    <?php echo icon('login', '20'); ?><span>Login</span>
                </a>
            </div>
            <div class="col">
                <a href="/mobile/?page=partner_login" class="aini-tab <?php echo $currentPage==='partner_login'?'active':''; ?>">
                    <?php echo icon('badge', '20'); ?><span>Partner</span>
                </a>
            </div>
        <?php endif; ?>

        </div><!-- /row -->
    </div>
</nav>
<!-- spacer for fixed bottom nav -->
<div style="height:64px;"></div>
