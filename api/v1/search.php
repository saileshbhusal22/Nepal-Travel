<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';
$qLike = '%' . $query . '%';

$results = [
    'experiences' => [],
    'ideas' => [],
    'deals' => [],
    'events' => []
];

if (strlen(trim($query)) > 0) {
    // Search posts (Experiences/Stories)
    $stmt = $conn->prepare("SELECT id, caption as title, image_path as image FROM posts WHERE caption LIKE ? OR destination LIKE ? OR location LIKE ? LIMIT 10");
    $stmt->bind_param("sss", $qLike, $qLike, $qLike);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $row['type'] = 'EXPERIENCE';
        $row['link'] = '/Nepal-Travel/Public/experience.php?post_id=' . $row['id'];
        if (empty($row['image']) || strpos($row['image'], 'http') !== 0 && !file_exists(__DIR__ . '/../../' . ltrim($row['image'], '/'))) {
            $row['image'] = '/Nepal-Travel/images/default_idea.png';
        } else if (strpos($row['image'], 'http') !== 0) {
            $row['image'] = '/Nepal-Travel/' . ltrim($row['image'], '/');
        }
        $results['experiences'][] = $row;
    }

    // Search travel ideas
    $stmt = $conn->prepare("SELECT id, title, image_path as image, slug FROM travel_ideas WHERE title LIKE ? OR subtitle LIKE ? LIMIT 10");
    $stmt->bind_param("ss", $qLike, $qLike);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $row['type'] = 'IDEA';
        $row['link'] = '/Nepal-Travel/Public/travel-idea-detail.php?slug=' . urlencode($row['slug']);
        if (empty($row['image']) || strpos($row['image'], 'http') !== 0 && !file_exists(__DIR__ . '/../../' . ltrim($row['image'], '/'))) {
            $row['image'] = '/Nepal-Travel/images/default_idea.png';
        } else if (strpos($row['image'], 'http') !== 0) {
            $row['image'] = '/Nepal-Travel/' . ltrim($row['image'], '/');
        }
        $results['ideas'][] = $row;
    }

    // Search deals
    $stmt = $conn->prepare("SELECT id, title, image_url as image, category as type FROM deals WHERE title LIKE ? OR category LIKE ? OR location LIKE ? LIMIT 10");
    $stmt->bind_param("sss", $qLike, $qLike, $qLike);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $row['type'] = strtoupper($row['type']);
        if ($row['type'] == '') $row['type'] = 'DEAL';
        $row['link'] = '/Nepal-Travel/Public/deal-details.php?id=' . $row['id'];
        if (empty($row['image']) || strpos($row['image'], 'http') !== 0 && !file_exists(__DIR__ . '/../../' . ltrim($row['image'], '/'))) {
            $row['image'] = '/Nepal-Travel/images/placeholder.jpg';
        } else if (strpos($row['image'], 'http') !== 0) {
            $row['image'] = '/Nepal-Travel/' . ltrim($row['image'], '/');
        }
        $results['deals'][] = $row;
    }
    
    // Search user deals
    $stmt = $conn->prepare("SELECT id, title, image_url as image, category as type FROM user_deals WHERE status='approved' AND (title LIKE ? OR category LIKE ? OR location LIKE ?) LIMIT 10");
    $stmt->bind_param("sss", $qLike, $qLike, $qLike);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $row['type'] = strtoupper($row['type']);
        if ($row['type'] == '') $row['type'] = 'DEAL';
        $row['link'] = '/Nepal-Travel/Public/Ud_deal_details.php?id=' . $row['id'];
        if (empty($row['image']) || strpos($row['image'], 'http') !== 0 && !file_exists(__DIR__ . '/../../' . ltrim($row['image'], '/'))) {
            $row['image'] = '/Nepal-Travel/images/placeholder.jpg';
        } else if (strpos($row['image'], 'http') !== 0) {
            $row['image'] = '/Nepal-Travel/' . ltrim($row['image'], '/');
        }
        $results['deals'][] = $row;
    }

    // Search events
    $stmt = $conn->prepare("SELECT id, title, image_path as image, category as type FROM events WHERE title LIKE ? OR description LIKE ? OR location LIKE ? OR venue_name LIKE ? LIMIT 10");
    $stmt->bind_param("ssss", $qLike, $qLike, $qLike, $qLike);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $row['type'] = strtoupper($row['type']);
        if ($row['type'] == '') $row['type'] = 'EVENT';
        $row['link'] = '/Nepal-Travel/Public/event-detail.php?id=' . $row['id'];
        if (empty($row['image']) || strpos($row['image'], 'http') !== 0 && !file_exists(__DIR__ . '/../../' . ltrim($row['image'], '/'))) {
            $row['image'] = '/Nepal-Travel/images/default_idea.png';
        } else if (strpos($row['image'], 'http') !== 0) {
            $row['image'] = '/Nepal-Travel/' . ltrim($row['image'], '/');
        }
        $results['events'][] = $row;
    }
}

echo json_encode(['success' => true, 'results' => $results]);
