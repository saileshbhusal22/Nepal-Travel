<?php
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');

$month = $_GET['month'] ?? '';
$category = $_GET['category'] ?? '';
$query = $_GET['q'] ?? '';

$sql = "SELECT * FROM events WHERE 1=1";
$params = [];
$types = "";

if (!empty($month) && $month !== 'ALL') {
    $sql .= " AND month = ?";
    $params[] = $month;
    $types .= "s";
}

if (!empty($category)) {
    $categories = explode(',', $category);
    $placeholders = str_repeat('?,', count($categories) - 1) . '?';
    $sql .= " AND category IN ($placeholders)";
    foreach ($categories as $cat) {
        $params[] = strtoupper($cat);
        $types .= "s";
    }
}

if (!empty($query)) {
    $sql .= " AND (title LIKE ? OR description LIKE ?)";
    $searchTerm = "%$query%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$events = [];

while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

echo json_encode([
    'success' => true,
    'events' => $events,
    'count' => count($events)
]);
?>