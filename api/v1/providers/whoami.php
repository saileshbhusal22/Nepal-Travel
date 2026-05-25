<?php
/**
 
 * Author: Bijay Sapkota
 * Group: L5CG6
 */
session_start();
header('Content-Type: application/json');
echo json_encode([
    'session' => $_SESSION,
    'session_id' => session_id(),
    'cookie_params' => session_get_cookie_params(),
    'user_id' => $_SESSION['user_id'] ?? 'not set',
    'is_admin' => $_SESSION['is_admin'] ?? 'not set'
]);
?>