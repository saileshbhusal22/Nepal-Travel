<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../user/db.php';
include_once __DIR__ . '/../../includes/travel-ideas-data.php';
include_once __DIR__ . '/../../includes/deals-data.php';

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [
    'experiences' => [],
    'ideas' => [],
    'deals' => []
];

if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'results' => $results]);
    exit;
}

$search_term = "%$query%";

// 1. Search Experiences (Community Posts)
$stmt = $conn->prepare("
    SELECT p.id, p.caption, p.image_path, p.location, p.destination, u.username 
    FROM posts p 
    LEFT JOIN users u ON p.user_id = u.id 
    WHERE p.caption LIKE ? OR p.location LIKE ? OR p.destination LIKE ? OR u.username LIKE ? 
    LIMIT 5
");
$stmt->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $results['experiences'][] = $row;
}
$stmt->close();

// 2. Search Travel Ideas (Static Data)
foreach ($travel_ideas as $idea) {
    if (stripos($idea['title'], $query) !== false || 
        stripos($idea['description'], $query) !== false || 
        stripos($idea['province'], $query) !== false) {
        $results['ideas'][] = $idea;
    }
}
$results['ideas'] = array_slice($results['ideas'], 0, 5);

// 3. Search Deals (Static Data)
foreach ($deals as $deal) {
    if (stripos($deal['title'], $query) !== false || 
        stripos($deal['desc'], $query) !== false || 
        stripos($deal['region'], $query) !== false ||
        stripos($deal['category_badge'], $query) !== false) {
        $results['deals'][] = $deal;
    }
}
$results['deals'] = array_slice($results['deals'], 0, 5);

echo json_encode([
    'success' => true,
    'query' => $query,
    'results' => $results,
    'counts' => [
        'experiences' => count($results['experiences']),
        'ideas' => count($results['ideas']),
        'deals' => count($results['deals']),
        'total' => count($results['experiences']) + count($results['ideas']) + count($results['deals'])
    ]
]);
?>
