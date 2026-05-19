<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
ob_start();

function sendJson(array $data, int $code = 200): void {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($code);
    echo json_encode($data);
    exit;
}

session_start();
$dbPath = dirname(__DIR__, 3) . '/config/db.php';
if (!file_exists($dbPath)) {
    sendJson(['success' => false, 'message' => 'Server configuration error', 'error' => 'DB config not found'], 500);
}
require_once $dbPath;

if (!isset($_SESSION['user_id'])) {
    sendJson(['success' => false, 'message' => 'Not authenticated'], 401);
}

$user_id = (int) $_SESSION['user_id'];

$idea_id = isset($_POST['idea_id']) ? (int) $_POST['idea_id'] : 0;
if ($idea_id <= 0) {
    sendJson(['success' => false, 'message' => 'Invalid idea selected'], 400);
}

$fetchStmt = $conn->prepare("SELECT user_id, image_path, slug FROM travel_ideas WHERE id = ? LIMIT 1");
if (!$fetchStmt) {
    sendJson(['success' => false, 'message' => 'Database error'], 500);
}
$fetchStmt->bind_param('i', $idea_id);
$fetchStmt->execute();
$result = $fetchStmt->get_result();
if (!$result || $result->num_rows === 0) {
    sendJson(['success' => false, 'message' => 'Travel idea not found'], 404);
}
$existing = $result->fetch_assoc();
$fetchStmt->close();

if ((int)$existing['user_id'] !== $user_id) {
    sendJson(['success' => false, 'message' => 'You are not authorized to edit this travel idea'], 403);
}

$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$subtitle = isset($_POST['subtitle']) ? trim($_POST['subtitle']) : null;
$province = isset($_POST['province']) ? trim($_POST['province']) : null;
$province_slug = isset($_POST['province_slug']) ? trim($_POST['province_slug']) : null;
$difficulty = isset($_POST['difficulty']) ? trim($_POST['difficulty']) : null;
$duration_days = isset($_POST['duration_days']) ? (int) $_POST['duration_days'] : null;
$nights = isset($_POST['nights']) ? (int) $_POST['nights'] : null;
$transport = isset($_POST['transport']) ? trim($_POST['transport']) : null;
$accommodation = isset($_POST['accommodation']) ? trim($_POST['accommodation']) : null;
$best_time = isset($_POST['best_time']) ? trim($_POST['best_time']) : null;
$pro_tip = isset($_POST['pro_tip']) ? trim($_POST['pro_tip']) : null;
$content = isset($_POST['content']) ? trim($_POST['content']) : null;
$highlights_input = isset($_POST['highlights']) ? trim($_POST['highlights']) : '';
$experience_types = isset($_POST['experience_types']) ? (array) $_POST['experience_types'] : [];

if (empty($title)) {
    sendJson(['success' => false, 'message' => 'Title is required'], 400);
}

if (empty($province_slug) && !empty($province)) {
    $province_slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $province));
    $province_slug = trim($province_slug, '-');
}

function normalizeSlug($text) {
    return trim(strtolower(preg_replace('/[^a-z0-9]+/', '-', $text)), '-');
}

$province_id = null;
if (!empty($province_slug)) {
    $provinceStmt = $conn->prepare("SELECT id FROM provinces WHERE slug = ? LIMIT 1");
    if ($provinceStmt) {
        $provinceStmt->bind_param('s', $province_slug);
        $provinceStmt->execute();
        $provinceRes = $provinceStmt->get_result();
        if ($provinceRes && $provinceRow = $provinceRes->fetch_assoc()) {
            $province_id = (int)$provinceRow['id'];
        }
        $provinceStmt->close();
    }
    if (empty($province_id) && !empty($province)) {
        $insertProvinceStmt = $conn->prepare("INSERT INTO provinces (name, slug) VALUES (?, ?)");
        if ($insertProvinceStmt) {
            $insertProvinceStmt->bind_param('ss', $province, $province_slug);
            $insertProvinceStmt->execute();
            if ($insertProvinceStmt->affected_rows > 0) {
                $province_id = $insertProvinceStmt->insert_id;
            }
            $insertProvinceStmt->close();
        }
    }
}

$intro = !empty($content) ? $content : (!empty($subtitle) ? $subtitle : 'A travel idea shared by our community.');
$highlights = [];
if (!empty($highlights_input)) {
    $highlights = array_filter(array_map('trim', preg_split('/\R+/', $highlights_input)));
}
if (empty($highlights) && !empty($subtitle)) {
    $highlights[] = $subtitle;
}
if (empty($highlights)) {
    $highlights[] = 'A travel idea shared by our community.';
}
$highlights = json_encode(array_values($highlights), JSON_UNESCAPED_UNICODE);

$logistics = json_encode([
    'transport' => $transport ?: 'Check local transport options.',
    'accommodation' => $accommodation ?: 'Book accommodation based on your preferred budget and route.',
    'best_time' => $best_time ?: 'Best timing depends on the destination and season.',
    'pro_tip' => $pro_tip ?: 'Plan your days around local sunrise and crowd patterns.',
], JSON_UNESCAPED_UNICODE);

$hero_image_path = $existing['image_path'];
if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] === UPLOAD_ERR_OK) {
    $image = $_FILES['hero_image'];
    $ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];
    if (in_array($ext, $allowed, true)) {
        $upload_dir = __DIR__ . '/../../../images/travel_ideas/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $filename = uniqid('idea_') . '.' . $ext;
        $dest = $upload_dir . $filename;
        if (move_uploaded_file($image['tmp_name'], $dest)) {
            $hero_image_path = '../images/travel_ideas/' . $filename;
        }
    }
}

$itineraryItems = [];
$dayOrders = isset($_POST['itinerary_day_order']) ? (array) $_POST['itinerary_day_order'] : [];
$dayTitles = isset($_POST['itinerary_day_title']) ? (array) $_POST['itinerary_day_title'] : [];
$dayMornings = isset($_POST['itinerary_morning']) ? (array) $_POST['itinerary_morning'] : [];
$dayAfternoons = isset($_POST['itinerary_afternoon']) ? (array) $_POST['itinerary_afternoon'] : [];
$dayEvenings = isset($_POST['itinerary_evening']) ? (array) $_POST['itinerary_evening'] : [];

$maxItems = max(count($dayOrders), count($dayTitles), count($dayMornings), count($dayAfternoons), count($dayEvenings));
for ($i = 0; $i < $maxItems; $i++) {
    $orderValue = trim($dayOrders[$i] ?? '');
    $day_order = ctype_digit((string)$orderValue) && (int)$orderValue > 0 ? (int)$orderValue : null;
    $titleDay = trim($dayTitles[$i] ?? '');
    $morning = trim($dayMornings[$i] ?? '');
    $afternoon = trim($dayAfternoons[$i] ?? '');
    $evening = trim($dayEvenings[$i] ?? '');

    if (empty($titleDay) && empty($morning) && empty($afternoon) && empty($evening)) {
        continue;
    }

    $itineraryItems[] = [
        'day_order' => $day_order,
        'title' => $titleDay ?: 'Day ' . ($day_order ?: ($i + 1)),
        'morning' => $morning ?: null,
        'afternoon' => $afternoon ?: null,
        'evening' => $evening ?: null,
    ];
}

$usedOrders = [];
foreach ($itineraryItems as &$item) {
    if (empty($item['day_order']) || isset($usedOrders[$item['day_order']])) {
        $item['day_order'] = null;
    } else {
        $usedOrders[$item['day_order']] = true;
    }
}
unset($item);

$nextOrder = 1;
foreach ($itineraryItems as &$item) {
    if ($item['day_order'] === null) {
        while (isset($usedOrders[$nextOrder])) {
            $nextOrder++;
        }
        $item['day_order'] = $nextOrder;
        $usedOrders[$nextOrder] = true;
    }
}
unset($item);

usort($itineraryItems, function ($a, $b) {
    return $a['day_order'] <=> $b['day_order'];
});

if (empty($itineraryItems)) {
    $itineraryItems[] = [
        'day_order' => 1,
        'title' => 'Day 1',
        'morning' => $intro,
        'afternoon' => null,
        'evening' => null,
    ];
}

$updateStmt = $conn->prepare("UPDATE travel_ideas SET title = ?, subtitle = ?, province_id = ?, province_slug = ?, image_path = ?, duration_days = ?, nights = ?, transport = ?, accommodation = ?, best_time = ?, pro_tip = ?, difficulty = ? WHERE id = ?");
if (!$updateStmt) {
    sendJson(['success' => false, 'message' => 'Database error', 'error' => $conn->error], 500);
}
$subtitleValue = !empty($subtitle) ? $subtitle : null;
$provinceSlugValue = !empty($province_slug) ? $province_slug : null;
$imagePathValue = !empty($hero_image_path) ? $hero_image_path : null;
$durationDaysValue = $duration_days > 0 ? $duration_days : null;
$nightsValue = $nights >= 0 ? $nights : null;
$transportValue = !empty($transport) ? $transport : null;
$accommodationValue = !empty($accommodation) ? $accommodation : null;
$bestTimeValue = !empty($best_time) ? $best_time : null;
$proTipValue = !empty($pro_tip) ? $pro_tip : null;
$difficultyValue = !empty($difficulty) ? $difficulty : null;

$updateStmt->bind_param('ssissiisssssi', $title, $subtitleValue, $province_id, $provinceSlugValue, $imagePathValue, $durationDaysValue, $nightsValue, $transportValue, $accommodationValue, $bestTimeValue, $proTipValue, $difficultyValue, $idea_id);
if (!$updateStmt->execute()) {
    sendJson(['success' => false, 'message' => 'Unable to update travel idea', 'error' => $updateStmt->error], 500);
}
$updateStmt->close();

$experience_types = array_filter(array_map('trim', $experience_types));
$deleteExperienceStmt = $conn->prepare("DELETE FROM travel_idea_experiences WHERE idea_id = ?");
if ($deleteExperienceStmt) {
    $deleteExperienceStmt->bind_param('i', $idea_id);
    $deleteExperienceStmt->execute();
    $deleteExperienceStmt->close();
}

foreach ($experience_types as $experience_type_name) {
    $expSlug = normalizeSlug($experience_type_name);
    if ($expSlug === '') {
        continue;
    }

    $eStmt = $conn->prepare("SELECT id FROM experience_types WHERE slug = ? LIMIT 1");
    if (!$eStmt) {
        continue;
    }
    $eStmt->bind_param('s', $expSlug);
    $eStmt->execute();
    $eRes = $eStmt->get_result();
    $expId = null;
    if ($eRes && $eRow = $eRes->fetch_assoc()) {
        $expId = (int)$eRow['id'];
    }
    $eStmt->close();

    if (!$expId) {
        $insertExpStmt = $conn->prepare("INSERT INTO experience_types (name, slug) VALUES (?, ?)");
        if ($insertExpStmt) {
            $insertExpStmt->bind_param('ss', $experience_type_name, $expSlug);
            $insertExpStmt->execute();
            if ($insertExpStmt->affected_rows > 0) {
                $expId = $insertExpStmt->insert_id;
            }
            $insertExpStmt->close();
        }
    }

    if ($expId) {
        $pivotStmt = $conn->prepare("INSERT IGNORE INTO travel_idea_experiences (idea_id, experience_type_id) VALUES (?, ?)");
        if ($pivotStmt) {
            $pivotStmt->bind_param('ii', $idea_id, $expId);
            $pivotStmt->execute();
            $pivotStmt->close();
        }
    }
}

$detailStmt = $conn->prepare("UPDATE travel_idea_details SET content = ?, highlights = ?, logistics = ?, hero_image = ? WHERE idea_id = ?");
if ($detailStmt) {
    $detailStmt->bind_param('ssssi', $intro, $highlights, $logistics, $imagePathValue, $idea_id);
    $detailStmt->execute();
    if ($detailStmt->affected_rows === 0) {
        $insDetail = $conn->prepare("INSERT INTO travel_idea_details (idea_id, content, highlights, logistics, hero_image) VALUES (?, ?, ?, ?, ?)");
        if ($insDetail) {
            $insDetail->bind_param('issss', $idea_id, $intro, $highlights, $logistics, $imagePathValue);
            $insDetail->execute();
            $insDetail->close();
        }
    }
    $detailStmt->close();
}

$deleteItinerary = $conn->prepare("DELETE FROM itineraries WHERE idea_id = ?");
if ($deleteItinerary) {
    $deleteItinerary->bind_param('i', $idea_id);
    $deleteItinerary->execute();
    $deleteItinerary->close();
}

$itineraryStmt = $conn->prepare("INSERT INTO itineraries (idea_id, day_order, day_title, morning, afternoon, evening, image_path) VALUES (?, ?, ?, ?, ?, ?, NULL)");
if ($itineraryStmt) {
    foreach ($itineraryItems as $item) {
        $dayOrder = $item['day_order'];
        $dayTitle = !empty($item['title']) ? $item['title'] : 'Day ' . $dayOrder;
        $morning = $item['morning'] ?: null;
        $afternoon = $item['afternoon'] ?: null;
        $evening = $item['evening'] ?: null;
        $itineraryStmt->bind_param('iissss', $idea_id, $dayOrder, $dayTitle, $morning, $afternoon, $evening);
        $itineraryStmt->execute();
    }
    $itineraryStmt->close();
}

sendJson(['success' => true, 'message' => 'Travel idea updated successfully']);
$conn->close();
