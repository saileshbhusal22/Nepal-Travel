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
$comment_text = isset($data['comment']) ? trim($data['comment']) : '';

if ($post_id <= 0 || empty($comment_text)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO comments (user_id, post_id, comment_text) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $user_id, $post_id, $comment_text);

if ($stmt->execute()) {
    // Fetch user info for return
    $u_stmt = $conn->prepare("SELECT COALESCE(NULLIF(full_name, ''), username) as username FROM users WHERE id = ?");
    $u_stmt->bind_param("i", $user_id);
    $u_stmt->execute();
    $user_res = $u_stmt->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true, 
        'comment' => [
            'id' => $conn->insert_id,
            'user_id' => $user_id,
            'username' => $user_res['username'],
            'comment_text' => htmlspecialchars($comment_text),
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$stmt->close();
$conn->close();
?>
