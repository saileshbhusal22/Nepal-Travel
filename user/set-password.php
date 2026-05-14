<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Not logged in");
}

$input = json_decode(file_get_contents('php://input'), true);
$password = $input['password'] ?? '';
$confirm  = $input['confirm']  ?? '';

if (strlen($password) < 8) {
    http_response_code(400);
    exit("Password must be at least 8 characters");
}

if ($password !== $confirm) {
    http_response_code(400);
    exit("Passwords do not match");
}

$hashed = password_hash($password, PASSWORD_DEFAULT);
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE users SET password = ?, has_password = 1 WHERE id = ?");
$stmt->bind_param("si", $hashed, $userId);

if ($stmt->execute()) {
    $_SESSION['has_password'] = true;
    echo "ok";
} else {
    http_response_code(500);
    echo "Database error";
}
?>