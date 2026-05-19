<?php
session_start();
require_once __DIR__ . '/../../user/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$caption = isset($_POST['caption']) ? trim($_POST['caption']) : '';
$post_location = isset($_POST['location']) && !empty(trim($_POST['location'])) ? trim($_POST['location']) : null;
$post_destination = isset($_POST['destination']) && !empty(trim($_POST['destination'])) ? trim($_POST['destination']) : null;

if ($post_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit;
}

// 1. Verify ownership
$stmt = $conn->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ?");
$stmt->bind_param("is", $post_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt->close();
    
    // 2. Perform Update
    $stmt_upd = $conn->prepare("UPDATE posts SET caption = ?, location = ?, destination = ? WHERE id = ?");
    $stmt_upd->bind_param("sssi", $caption, $post_location, $post_destination, $post_id);
    
    if ($stmt_upd->execute()) {
        echo json_encode(['success' => true, 'message' => 'Post updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update post in database']);
    }
    $stmt_upd->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Post not found or unauthorized']);
}

$conn->close();
?>
