<?php
session_start();
require_once __DIR__ . '/../../user/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$post_id = isset($data['post_id']) ? (int)$data['post_id'] : 0;

if ($post_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit;
}

// 1. Verify ownership
$stmt = $conn->prepare("SELECT image_path FROM posts WHERE id = ? AND user_id = ?");
$stmt->bind_param("is", $post_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $image_path = __DIR__ . '/../../' . $row['image_path'];
    
    // 2. Delete comments first (due to foreign key or just clean up)
    $stmt_comm = $conn->prepare("DELETE FROM comments WHERE post_id = ?");
    $stmt_comm->bind_param("i", $post_id);
    $stmt_comm->execute();
    $stmt_comm->close();
    
    // 3. Delete likes
    $stmt_likes = $conn->prepare("DELETE FROM likes WHERE post_id = ?");
    $stmt_likes->bind_param("i", $post_id);
    $stmt_likes->execute();
    $stmt_likes->close();

    // 4. Delete the post
    $stmt_del = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt_del->bind_param("i", $post_id);
    
    if ($stmt_del->execute()) {
        // 5. Delete physical file
        if (file_exists($image_path)) {
            unlink($image_path);
        }
        echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete from database']);
    }
    $stmt_del->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Post not found or unauthorized']);
}

$stmt->close();
$conn->close();
?>
