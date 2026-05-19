<?php
require_once __DIR__ . '/../../user/db.php';
header('Content-Type: application/json');

$district = isset($_GET['district']) ? trim($_GET['district']) : '';

if (empty($district)) {
    echo json_encode(['success' => false, 'message' => 'No district specified']);
    exit;
}

// Fetch total count and the latest/top image for this district
$sql = "
    SELECT 
        (SELECT COUNT(*) FROM posts WHERE destination = ?) as total_posts,
        image_path
    FROM posts 
    WHERE destination = ? 
    ORDER BY created_at DESC 
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $district, $district);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if ($data) {
    echo json_encode([
        'success' => true,
        'count' => $data['total_posts'],
        'image' => $data['image_path']
    ]);
} else {
    echo json_encode([
        'success' => true,
        'count' => 0,
        'image' => null
    ]);
}

$stmt->close();
$conn->close();
?>
