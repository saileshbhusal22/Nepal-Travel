<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

set_exception_handler(function ($exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server exception',
        'error' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
    ]);
    exit;
});

function logCreateIdeaError($message) {
    $logPath = __DIR__ . '/create_idea_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logPath, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_COMPILE_WARNING])) {
        logCreateIdeaError(sprintf('Shutdown error: %s in %s on line %d', $error['message'], $error['file'], $error['line']));
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Fatal error',
            'error' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line'],
        ]);
        exit;
    }
});

session_start();
$dbPath = dirname(__DIR__, 3) . '/config/db.php';
if (!file_exists($dbPath)) {
    logCreateIdeaError('Missing DB config: ' . $dbPath);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server configuration error', 'error' => 'DB config not found']);
    exit;
}
require_once $dbPath;

if ($conn->connect_error) {
    logCreateIdeaError('DB connection failed: ' . $conn->connect_error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server configuration error', 'error' => 'Database connection failed']);
    exit;
}

foreach (['travel_ideas', 'travel_idea_details', 'itineraries', 'travel_idea_experiences', 'experience_types', 'provinces'] as $tableName) {
    $tblRes = $conn->query("SHOW TABLES LIKE '$tableName'");
    if (!$tblRes || $tblRes->num_rows === 0) {
        logCreateIdeaError('Missing required table: ' . $tableName);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server configuration error', 'error' => 'Missing required table: ' . $tableName]);
        exit;
    }
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$title = '';
if (isset($_POST['title'])) {
    $title = trim($_POST['title']);
} elseif (isset($_POST['travelIdeaTitle'])) {
    $title = trim($_POST['travelIdeaTitle']);
}
$slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
$subtitle = isset($_POST['subtitle']) ? trim($_POST['subtitle']) : null;
$province = isset($_POST['province']) ? trim($_POST['province']) : null;
$province_slug = isset($_POST['province_slug']) ? trim($_POST['province_slug']) : null;
$experience_types = isset($_POST['experience_types']) ? $_POST['experience_types'] : [];
$difficulty = isset($_POST['difficulty']) ? trim($_POST['difficulty']) : null;
$duration_days = isset($_POST['duration_days']) ? (int) $_POST['duration_days'] : 0;
$nights = isset($_POST['nights']) ? (int) $_POST['nights'] : 0;
$transport = isset($_POST['transport']) ? trim($_POST['transport']) : null;
$accommodation = isset($_POST['accommodation']) ? trim($_POST['accommodation']) : null;
$best_time = isset($_POST['best_time']) ? trim($_POST['best_time']) : null;
$pro_tip = isset($_POST['pro_tip']) ? trim($_POST['pro_tip']) : null;
$content = isset($_POST['content']) ? trim($_POST['content']) : null;
$highlights_input = isset($_POST['highlights']) ? trim($_POST['highlights']) : '';

if (empty($province_slug) && !empty($province)) {
    $province_slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $province));
    $province_slug = trim($province_slug, '-');
}

function normalizeSlug($text) {
    return trim(strtolower(preg_replace('/[^a-z0-9]+/', '-', $text)), '-');
}

$experience_types = is_array($experience_types) ? array_filter(array_map('trim', $experience_types)) : [];
$experience_type_ids = [];
foreach ($experience_types as $experience_type_name) {
    $experience_type_slug = normalizeSlug($experience_type_name);
    if ($experience_type_slug === '') {
        continue;
    }

    $experience_type_id = null;
    $typeStmt = $conn->prepare("SELECT id FROM experience_types WHERE slug = ? LIMIT 1");
    if ($typeStmt) {
        $typeStmt->bind_param('s', $experience_type_slug);
        $typeStmt->execute();
        $typeRes = $typeStmt->get_result();
        if ($typeRes && $typeRow = $typeRes->fetch_assoc()) {
            $experience_type_id = (int)$typeRow['id'];
        }
        $typeStmt->close();
    }

    if (!$experience_type_id) {
        $insertTypeStmt = $conn->prepare("INSERT INTO experience_types (name, slug) VALUES (?, ?)");
        if ($insertTypeStmt) {
            $insertTypeStmt->bind_param('ss', $experience_type_name, $experience_type_slug);
            $insertTypeStmt->execute();
            if ($insertTypeStmt->affected_rows > 0) {
                $experience_type_id = $insertTypeStmt->insert_id;
            }
            $insertTypeStmt->close();
        }
    }

    if ($experience_type_id) {
        $experience_type_ids[] = $experience_type_id;
    }
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
$highlights_arr = [];
if (!empty($highlights_input)) {
    $highlights_arr = array_filter(array_map('trim', preg_split('/\R+/', $highlights_input)));
}
if (empty($highlights_arr) && !empty($subtitle)) {
    $highlights_arr[] = $subtitle;
}
if (empty($highlights_arr)) {
    $highlights_arr = array_filter([
        !empty($subtitle) ? $subtitle : null,
        !empty($transport) ? 'Transport: ' . $transport : null,
        !empty($accommodation) ? 'Stay: ' . $accommodation : null,
        !empty($best_time) ? 'Best time: ' . $best_time : null,
        !empty($difficulty) ? 'Difficulty: ' . $difficulty : null,
    ]);
}
if (empty($highlights_arr)) {
    $highlights_arr[] = 'Community travel idea from ' . ($province ?: 'Nepal');
}
$highlights = json_encode(array_values($highlights_arr), JSON_UNESCAPED_UNICODE);

$logistics = json_encode([
    'transport' => $transport ?: 'Check local transport options.',
    'accommodation' => $accommodation ?: 'Book accommodation based on your preferred budget and route.',
    'best_time' => $best_time ?: 'Best timing depends on the destination and season.',
    'pro_tip' => $pro_tip ?: 'Plan your days around local sunrise and crowd patterns.',
], JSON_UNESCAPED_UNICODE);

$itineraryItems = [];
$dayOrders = isset($_POST['itinerary_day_order']) ? (array) $_POST['itinerary_day_order'] : [];
$dayTitles = isset($_POST['itinerary_day_title']) ? (array) $_POST['itinerary_day_title'] : [];
$dayMornings = isset($_POST['itinerary_morning']) ? (array) $_POST['itinerary_morning'] : [];
$dayAfternoons = isset($_POST['itinerary_afternoon']) ? (array) $_POST['itinerary_afternoon'] : [];
$dayEvenings = isset($_POST['itinerary_evening']) ? (array) $_POST['itinerary_evening'] : [];

$itineraryImages = [];
if (isset($_FILES['itinerary_image']) && is_array($_FILES['itinerary_image']['name'])) {
    $files = $_FILES['itinerary_image'];
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK && !empty($files['tmp_name'][$i])) {
            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($ext, $allowed)) {
                $upload_dir = __DIR__ . '/../../../images/travel_ideas/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $filename = uniqid('itinerary_') . '.' . $ext;
                $dest = $upload_dir . $filename;
                if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
                    $itineraryImages[$i] = '../images/travel_ideas/' . $filename;
                }
            }
        }
    }
}

$maxItems = max(count($dayOrders), count($dayTitles), count($dayMornings), count($dayAfternoons), count($dayEvenings));
for ($i = 0; $i < $maxItems; $i++) {
    $orderValue = trim($dayOrders[$i] ?? '');
    $day_order = ctype_digit($orderValue) && (int)$orderValue > 0 ? (int)$orderValue : null;
    $day_title = trim($dayTitles[$i] ?? '');
    $morning = trim($dayMornings[$i] ?? '');
    $afternoon = trim($dayAfternoons[$i] ?? '');
    $evening = trim($dayEvenings[$i] ?? '');
    $img = isset($itineraryImages[$i]) ? $itineraryImages[$i] : null;

    if (empty($day_title) && empty($morning) && empty($afternoon) && empty($evening) && empty($img)) {
        continue;
    }

    $itineraryItems[] = [
        'day_order' => $day_order,
        'title' => $day_title ?: 'Day ' . ($day_order ?: ($i + 1)),
        'morning' => $morning,
        'afternoon' => $afternoon,
        'evening' => $evening,
        'img' => $img,
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
        'afternoon' => '',
        'evening' => '',
        'img' => null,
    ];
}
$itinerary = json_encode($itineraryItems, JSON_UNESCAPED_UNICODE);

if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Title is required']);
    exit;
}

if (empty($slug)) {
    // generate a simple slug
    $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $title));
    $slug = trim($slug, '-');
}

// handle image upload (optional)
$image_path = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $image = $_FILES['image'];
    $ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success'=>false,'message'=>'Invalid image format']);
        exit;
    }
    $upload_dir = __DIR__ . '/../../../images/travel_ideas/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $filename = uniqid('idea_') . '.' . $ext;
    $dest = $upload_dir . $filename;
    if (move_uploaded_file($image['tmp_name'], $dest)) {
        $image_path = '../images/travel_ideas/' . $filename;
    }
}

// insert into travel_ideas
$stmt = $conn->prepare("INSERT INTO travel_ideas (user_id, title, subtitle, slug, province_id, province_slug, image_path, duration_days, nights, transport, accommodation, best_time, pro_tip, difficulty) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success'=>false,'message'=>'DB prepare failed: ' . $conn->error]);
    exit;
}
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$subtitle_value = !empty($subtitle) ? $subtitle : null;
$province_slug_value = !empty($province_slug) ? $province_slug : null;
$image_path_value = !empty($image_path) ? $image_path : null;
$transport_value = !empty($transport) ? $transport : null;
$accommodation_value = !empty($accommodation) ? $accommodation : null;
$best_time_value = !empty($best_time) ? $best_time : null;
$pro_tip_value = !empty($pro_tip) ? $pro_tip : null;
$difficulty_value = !empty($difficulty) ? $difficulty : null;
$duration_days_value = $duration_days > 0 ? $duration_days : null;
$nights_value = $nights >= 0 ? $nights : null;
$stmt->bind_param('isssissiisssss', $user_id, $title, $subtitle_value, $slug, $province_id, $province_slug_value, $image_path_value, $duration_days_value, $nights_value, $transport_value, $accommodation_value, $best_time_value, $pro_tip_value, $difficulty_value);
if ($stmt->execute()) {
    $idea_id = $stmt->insert_id;
    $stmt->close();

    $dstmt = $conn->prepare("INSERT INTO travel_idea_details (idea_id, content, highlights, logistics, hero_image) VALUES (?, ?, ?, ?, ?)");
    if ($dstmt) {
        $dstmt->bind_param('issss', $idea_id, $intro, $highlights, $logistics, $image_path_value);
        $dstmt->execute();
        $dstmt->close();
    }

    if (!empty($experience_type_ids)) {
        $experienceStmt = $conn->prepare("INSERT IGNORE INTO travel_idea_experiences (idea_id, experience_type_id) VALUES (?, ?)");
        if ($experienceStmt) {
            foreach ($experience_type_ids as $experience_type_id) {
                $experienceStmt->bind_param('ii', $idea_id, $experience_type_id);
                $experienceStmt->execute();
            }
            $experienceStmt->close();
        }
    }

    $itineraryStmt = $conn->prepare("INSERT INTO itineraries (idea_id, day_order, day_title, morning, afternoon, evening, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($itineraryStmt) {
        foreach ($itineraryItems as $index => $item) {
            $day_order = isset($item['day_order']) && is_int($item['day_order']) && $item['day_order'] > 0 ? $item['day_order'] : ($index + 1);
            $day_title = !empty($item['title']) ? $item['title'] : 'Day ' . $day_order;
            $morning = $item['morning'] ?? null;
            $afternoon = $item['afternoon'] ?? null;
            $evening = $item['evening'] ?? null;
            $item_image = $item['img'] ?? null;
            $itineraryStmt->bind_param('iisssss', $idea_id, $day_order, $day_title, $morning, $afternoon, $evening, $item_image);
            $itineraryStmt->execute();
        }
        $itineraryStmt->close();
    }

    echo json_encode(['success'=>true,'message'=>'Travel idea created','slug'=>$slug]);
} else {
    echo json_encode(['success'=>false,'message'=>'DB insert failed: ' . $stmt->error]);
}

$conn->close();
?>