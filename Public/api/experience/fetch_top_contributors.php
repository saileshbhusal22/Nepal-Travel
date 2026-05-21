<?php
session_start();
require_once __DIR__ . '/../../../config/db.php';

header('Content-Type: application/json');

// Calculate Top Contributors
// Scoring: Post = 10pts, Like Received = 2pts, Comment Received = 1pt
$sql = "
    SELECT * FROM (
        SELECT 
            u.id, 
            COALESCE(NULLIF(u.full_name, ''), u.username) as username,
            NULLIF(u.profile_image, 'default.png') as profile_image,
            COUNT(DISTINCT p.id) as post_count,
            (SELECT COUNT(*) FROM likes l JOIN posts p2 ON l.post_id = p2.id WHERE p2.user_id = u.id) as total_likes,
            (SELECT COUNT(*) FROM comments c JOIN posts p3 ON c.post_id = p3.id WHERE p3.user_id = u.id) as total_comments
        FROM users u
        JOIN posts p ON u.id = p.user_id
        GROUP BY u.id
    ) as stats
    ORDER BY (post_count * 10 + total_likes * 2 + total_comments) DESC
    LIMIT 5
";

$result = $conn->query($sql);
$contributors = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['score'] = ($row['post_count'] * 10) + ($row['total_likes'] * 2) + $row['total_comments'];
        $contributors[] = $row;
    }
}

echo json_encode(['success' => true, 'contributors' => $contributors]);

$conn->close();
?>

