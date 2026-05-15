<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php'); exit;
}
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Nepal-Travel/user/dashboard.php?tab=settings'); exit;
}

$user_id = (int)$_SESSION['user_id'];

// Verify password before deletion
$password_input = $_POST['delete_password'] ?? '';
if (empty($password_input)) {
    header('Location: /Nepal-Travel/user/dashboard.php?tab=settings&delete_error=Password+is+required'); exit;
}

$stmt = $conn->prepare("SELECT password, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($hashed_pw, $profile_image);
$stmt->fetch();
$stmt->close();

if (!password_verify($password_input, $hashed_pw)) {
    header('Location: /Nepal-Travel/user/dashboard.php?tab=settings&delete_error=Incorrect+password.+Please+try+again'); exit;
}

// Delete profile image file if not default
if (!empty($profile_image) && $profile_image !== 'default.png') {
    $absPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/Nepal-Travel/' . ltrim($profile_image, '/');
    if (file_exists($absPath)) { @unlink($absPath); }
}

// Delete user bookings first (FK safety), then the user row
$del_bookings = $conn->prepare("DELETE FROM bookings WHERE user_id = ?");
$del_bookings->bind_param("i", $user_id);
$del_bookings->execute();
$del_bookings->close();

$del_user = $conn->prepare("DELETE FROM users WHERE id = ?");
$del_user->bind_param("i", $user_id);
$del_user->execute();
$del_user->close();

// Destroy session completely
session_unset();
session_destroy();

header('Location: /Nepal-Travel/Public/index.php?account=deleted'); exit;