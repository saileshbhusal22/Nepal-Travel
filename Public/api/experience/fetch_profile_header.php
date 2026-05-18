<?php
require_once __DIR__ . '/../../../config/db.php';
header('Content-Type: application/json');

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$sql = "
    SELECT 
        u.id, 
        COALESCE(NULLIF(u.full_name, ''), u.username) as username,
        NULLIF(u.profile_image, 'default.png') as profile_image,
        (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as post_count,
        (SELECT COUNT(*) FROM likes l JOIN posts p ON l.post_id = p.id WHERE p.user_id = u.id) as total_likes
    FROM users u
    WHERE u.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    echo json_encode(['success' => true, 'user' => $user]);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}

$stmt->close();
$conn->close();
?>
