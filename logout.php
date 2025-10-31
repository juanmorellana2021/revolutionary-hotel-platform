<?php
session_start();
session_destroy();
header('Location: owner_login.php');
exit;
?>