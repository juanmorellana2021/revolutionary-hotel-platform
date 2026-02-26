<?php
// Clear the force_desktop cookie and go back to mobile
setcookie('force_desktop', '', time() - 3600, '/');
header('Location: /mobile/');
exit();
?>
