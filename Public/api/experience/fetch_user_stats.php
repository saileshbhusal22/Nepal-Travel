<?php
session_start();
require_once __DIR__ . '/../../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get Post Count
$stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$post_count = $stmt->get_result()->fetch_row()[0];

// Get Total Likes Received (on user's posts)
$stmt = $conn->prepare("SELECT COUNT(*) FROM likes l JOIN posts p ON l.post_id = p.id WHERE p.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$likes_received = $stmt->get_result()->fetch_row()[0];

// Get Save Count (posts the user saved)
$stmt = $conn->prepare("SELECT COUNT(*) FROM saves WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$save_count = $stmt->get_result()->fetch_row()[0];

echo json_encode([
    'success' => true,
    'stats' => [
        'posts' => $post_count,
        'likes' => $likes_received,
        'saves' => $save_count
    ]
]);
?>
