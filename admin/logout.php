<?php
/**
 * Author: Ishwor Dhital
 * Student ID: 2462304
 * Group: L5CG6
 */
session_name('nepal_admin_session');
session_start();
session_destroy();

// If we also want to clear the cookie:
if (isset($_COOKIE['nepal_admin_session'])) {
    setcookie('nepal_admin_session', '', time() - 3600, '/');
}

header("Location: ../user/login.php");
exit;
?>
