<?php
session_start();
require_once __DIR__ . '/../../user/db.php';

header('Content-Type: application/json');

$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;

if ($post_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit;
}

$sql = "
    SELECT c.id, c.comment_text, c.created_at, c.user_id, COALESCE(NULLIF(u.full_name, ''), u.username) as username
    FROM comments c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.post_id = ?
    ORDER BY c.created_at ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}

echo json_encode(['success' => true, 'comments' => $comments]);

$stmt->close();
$conn->close();
?>
