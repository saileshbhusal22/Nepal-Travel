<?php
session_start();
require_once __DIR__ . '/../../user/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Simulate subscription by setting is_subscribed to 1
$sql = "UPDATE users SET is_subscribed = 1 WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Subscription successful!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Subscription failed: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
