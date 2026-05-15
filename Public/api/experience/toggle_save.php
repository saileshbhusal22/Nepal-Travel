<?php
session_start();
require_once __DIR__ . '/../../../config/db.php';

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

// Check if already saved
$check_stmt = $conn->prepare("SELECT id FROM saves WHERE user_id = ? AND post_id = ?");
$check_stmt->bind_param("ii", $user_id, $post_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Already saved, so unsave
    $del_stmt = $conn->prepare("DELETE FROM saves WHERE user_id = ? AND post_id = ?");
    $del_stmt->bind_param("ii", $user_id, $post_id);
    $del_stmt->execute();
    $action = "unsaved";
} else {
    // Not saved, so save
    $ins_stmt = $conn->prepare("INSERT INTO saves (user_id, post_id) VALUES (?, ?)");
    $ins_stmt->bind_param("ii", $user_id, $post_id);
    $ins_stmt->execute();
    $action = "saved";
}

echo json_encode(['success' => true, 'action' => $action]);

$conn->close();
?>
