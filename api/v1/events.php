<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Helper to check admin
function requireAdmin($isAdmin) {
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Admin access required']);
        exit;
    }
}

function eventImageExists(string $path): bool {
    $path = trim($path);
    if ($path === '') {
        return false;
    }
    if (preg_match('#^https?://#i', $path)) {
        return true;
    }
    $root = realpath(__DIR__ . '/../../');
    if (!$root) {
        return false;
    }
    $fullPath = $root . '/' . ltrim($path, '/');
    return is_file($fullPath);
}

function getCategoryFallbackImage(string $category, int $eventId = 0): string {
    $cat = strtoupper(trim($category));
    $pools = [
        'FESTIVAL' => [
            'images/phewa_sunset.png', 'images/pokhara_lake.png', 'images/family_fun_nepal.png',
            'images/bhaktapur_temple.png', 'images/ktm_durbar.png', 'images/chitwan_rhino.png',
            'images/sarangkot_sunrise.png', 'images/food_drinks_nepal.png',
        ],
        'FESTIVALS' => [
            'images/phewa_sunset.png', 'images/pokhara_lake.png', 'images/family_fun_nepal.png',
            'images/bhaktapur_temple.png', 'images/ktm_durbar.png', 'images/chitwan_rhino.png',
            'images/sarangkot_sunrise.png', 'images/food_drinks_nepal.png',
        ],
        'CONCERT' => ['images/kathmandu_night_hero.png', 'images/city_excitement_nepal.png', 'images/phewa_sunset.png'],
        'MUSIC & CONCERT' => ['images/kathmandu_night_hero.png', 'images/city_excitement_nepal.png'],
        'WORKSHOP' => ['images/city_excitement_nepal.png', 'images/ktm_durbar.png', 'images/bhaktapur_temple.png'],
        'FOOD' => ['images/food_drinks_nepal.png', 'images/family_fun_nepal.png'],
        'FOOD & CUISINE' => ['images/food_drinks_nepal.png', 'images/family_fun_nepal.png'],
        'SPORTS' => ['images/annapurna_trek.png', 'images/everest_trek.png', 'images/sarangkot_sunrise.png'],
        'NATURE' => ['images/chitwan_rhino.png', 'images/ebc_summit.png', 'images/namche_bazaar.png'],
        'ARTS' => ['images/bhaktapur_temple.png', 'images/pashupatinath_aarti.png', 'images/ktm_durbar.png'],
        'ARTS & CULTURE' => ['images/bhaktapur_temple.png', 'images/pashupatinath_aarti.png', 'images/ktm_durbar.png'],
        'RELIGIOUS' => ['images/pashupatinath_aarti.png', 'images/lumbini_temple.png'],
        'NIGHTLIFE' => ['images/kathmandu_night_hero.png', 'images/city_excitement_nepal.png'],
        'PHOTOGRAPHY' => ['images/sarangkot_sunrise.png', 'images/annapurna_trek.png', 'images/phewa_sunset.png'],
    ];

    $pool = $pools[$cat] ?? [
        'images/pokhara_lake.png',
        'images/bhaktapur_temple.png',
        'images/chitwan_rhino.png',
        'images/annapurna_trek.png',
        'images/food_drinks_nepal.png',
    ];

    return $pool[abs($eventId) % count($pool)];
}

function resolveEventDisplayImagePath(array $event): string {
    $candidates = [];

    if (!empty($event['image_path'])) {
        $candidates[] = trim((string)$event['image_path']);
    }

    if (!empty($event['gallery_images'])) {
        foreach (explode(',', (string)$event['gallery_images']) as $galleryPath) {
            $galleryPath = trim($galleryPath);
            if ($galleryPath !== '') {
                $candidates[] = $galleryPath;
            }
        }
    }

    foreach ($candidates as $path) {
        if (strpos($path, 'placeholder_event') !== false) {
            continue;
        }
        if (eventImageExists($path)) {
            return $path;
        }
    }

    return getCategoryFallbackImage((string)($event['category'] ?? ''), (int)($event['id'] ?? 0));
}

function normalizeEventImages(array $event): array {
    $event['display_image'] = resolveEventDisplayImagePath($event);
    return $event;
}

function ensureWritableUploadDir(): ?string {
    $root = realpath(__DIR__ . '/../../');
    if (!$root) {
        return null;
    }

    $uploadDir = $root . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
        return null;
    }

    if (!is_writable($uploadDir)) {
        @chmod($uploadDir, 0777);
    }

    return is_writable($uploadDir) ? $uploadDir : null;
}

function saveUploadedFile(array $file, string $uploadDir, string $fileName): array {
    $relativePath = 'images/uploads/' . $fileName;
    $targetPath = $uploadDir . $fileName;
    $tmpName = $file['tmp_name'] ?? '';

    if ($tmpName === '' || !is_file($tmpName)) {
        return ['ok' => false, 'path' => '', 'error' => 'Upload temp file is missing. Try a smaller image or re-select the file.'];
    }

    $saved = false;
    if (is_uploaded_file($tmpName)) {
        $saved = @move_uploaded_file($tmpName, $targetPath);
    }
    if (!$saved) {
        $saved = @copy($tmpName, $targetPath);
    }

    if ($saved && is_file($targetPath)) {
        @chmod($targetPath, 0644);
        return ['ok' => true, 'path' => $relativePath, 'error' => ''];
    }

    if (!is_writable($uploadDir)) {
        return [
            'ok' => false,
            'path' => '',
            'error' => 'The images/uploads folder is not writable. In XAMPP, run: chmod -R 777 images/uploads inside the Nepal-Travel project folder.',
        ];
    }

    return ['ok' => false, 'path' => '', 'error' => 'Failed to save image on the server. Check file size (max 5MB) and try again.'];
}


// 1. GET: Fetch Events OR Export iCal

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'fetch';

    if ($action === 'export') {
        exportICS($conn);
        exit;
    }

    $month = $_GET['month'] ?? '';
    $categoryFilter = $_GET['category'] ?? '';
    $query = $_GET['q'] ?? '';
    $viewMode = $_GET['view'] ?? 'public'; // 'public' or 'private'
    $id = $_GET['id'] ?? '';

    // Fetch Local Events
    $sql = "SELECT *, 'local' as source FROM events WHERE 1=1";
    $dbParams = [];
    $types = "";

    // Initialize external events
    $externalEvents = [];

    if (!empty($id)) {
        // ID lookup logic...
        if (is_numeric($id)) {
            $sql .= " AND id = ?";
            $dbParams[] = $id;
            $types .= "i";
        } else {
            require_once __DIR__ . '/providers/hamro_patro.php';
            $hpProvider = new HamroPatroProvider();
            $externalEvents = $hpProvider->fetchUpcomingFestivals();
            $sql .= " AND 1=0";
        }
    } else {
        if ($viewMode === 'private') {
            $sql .= " AND is_private = 1";
            
            // Official Festivals (Hamro Patro) go into the Government section
            require_once __DIR__ . '/providers/hamro_patro.php';
            $hpProvider = new HamroPatroProvider();
            $externalEvents = $hpProvider->fetchUpcomingFestivals();
        } else if ($viewMode === 'my') {
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Login required for My Events']);
                exit;
            }
            $sql .= " AND user_id = " . (int)$_SESSION['user_id'];
        } else {
            $sql .= " AND is_private = 0";
            // No external festivals in public section as per user request
        }

        if (!empty($month) && $month !== 'ALL') {
            $sql .= " AND month = ?";
            $dbParams[] = $month;
            $types .= "s";
        }

        // --- NEW FILTERS ---
        
        // Date Presets
        $dateFilter = $_GET['date'] ?? '';
        if ($dateFilter === 'today') {
            $sql .= " AND start_date = CURDATE()";
        } else if ($dateFilter === 'weekend') {
            $sql .= " AND (DAYOFWEEK(start_date) IN (1, 7) OR (CURDATE() <= start_date AND start_date <= DATE_ADD(CURDATE(), INTERVAL (7 - DAYOFWEEK(CURDATE())) DAY)))";
        } else if ($dateFilter === 'upcoming') {
            $sql .= " AND start_date >= CURDATE() AND start_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH)";
        }

        // Price
        $priceFilter = $_GET['price'] ?? '';
        if ($priceFilter === 'free') {
            $sql .= " AND (is_paid = 0 OR price_npr = 0)";
        } else if ($priceFilter === '0-500') {
            $sql .= " AND is_paid = 1 AND price_npr <= 500";
        } else if ($priceFilter === '500-2000') {
            $sql .= " AND is_paid = 1 AND price_npr > 500 AND price_npr <= 2000";
        } else if ($priceFilter === '2000+') {
            $sql .= " AND is_paid = 1 AND price_npr > 2000";
        }

        // Location
        $locFilter = $_GET['location'] ?? '';
        if (!empty($locFilter) && $locFilter !== 'all') {
            $sql .= " AND (location LIKE ? OR venue_name LIKE ?)";
            $dbParams[] = "%$locFilter%";
            $dbParams[] = "%$locFilter%";
            $types .= "ss";
        }

        // Ticket Status
        $statusFilter = $_GET['status'] ?? '';
        if (!empty($statusFilter)) {
            $statuses = explode(',', $statusFilter);
            $placeholders = implode(',', array_fill(0, count($statuses), '?'));
            $sql .= " AND ticket_status IN ($placeholders)";
            foreach($statuses as $s) {
                $dbParams[] = $s;
                $types .= "s";
            }
        }

        // Featured Only
        if (isset($_GET['featured']) && $_GET['featured'] === '1') {
            $sql .= " AND is_premium = 1";
        }

        // International
        if (isset($_GET['intl']) && $_GET['intl'] === '1') {
            $sql .= " AND is_international = 1";
        }

        // Categories (Multi-select)
        if (!empty($categoryFilter)) {
            $cats = explode(',', $categoryFilter);
            $catSql = [];
            foreach($cats as $c) {
                $catSql[] = "category LIKE ?";
                $dbParams[] = "%$c%";
                $types .= "s";
            }
            if ($catSql) {
                $sql .= " AND (" . implode(" OR ", $catSql) . ")";
            }
        }
    }

    $stmt = $conn->prepare($sql);
    if ($dbParams) {
        $stmt->bind_param($types, ...$dbParams);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $localEvents = [];
    while ($row = $result->fetch_assoc()) {
        $localEvents[] = normalizeEventImages($row);
    }

    // Merge and Filter
    $allEvents = array_merge($localEvents, $externalEvents);

    $filteredEvents = array_filter($allEvents, function($e) use ($month, $categoryFilter, $query) {
        $match = true;

        // Month Filter
        if (!empty($month) && $month !== 'ALL' && $e['month'] !== $month) {
            $match = false;
        }

        // Category Filter
        if ($match && !empty($categoryFilter)) {
            $cats = explode(',', strtoupper($categoryFilter));
            if (!in_array(strtoupper($e['category']), $cats)) {
                $match = false;
            }
        }

        // Search Query
        if ($match && !empty($query)) {
            $q = strtolower($query);
            if (strpos(strtolower($e['title']), $q) === false && 
                (isset($e['description']) && strpos(strtolower($e['description']), $q) === false)) {
                $match = false;
            }
        }

        return $match;
    });

    $finalEvents = array_values($filteredEvents);

    $id = $_GET['id'] ?? '';
    if (!empty($id)) {
        $singleEvent = null;
        foreach ($finalEvents as $event) {
            if ((string)$event['id'] === (string)$id) {
                $singleEvent = $event;
                break;
            }
        }
        if ($singleEvent) {
            echo json_encode(['success' => true, 'event' => $singleEvent]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Event not found']);
        }
        exit;
    }

    $has_active_event_sub = false;
    if (isset($_SESSION['user_id'])) {
        $u_id = (int)$_SESSION['user_id'];
        $sub_check_get = $conn->query("
            SELECT ues.id
            FROM user_event_subscriptions ues
            JOIN event_subscription_plans esp ON esp.id = ues.plan_id
            WHERE ues.user_id = $u_id
              AND ues.status = 'active'
              AND (ues.expires_at IS NULL OR ues.expires_at > NOW())
              AND ues.events_posted < esp.event_limit
              AND esp.plan_type = 'event'
            LIMIT 1
        ");
        if ($sub_check_get && $sub_check_get->num_rows > 0) {
            $has_active_event_sub = true;
        }
    }

    echo json_encode([
        'success' => true, 
        'events' => $finalEvents, 
        'count' => count($finalEvents),
        'current_user_id' => $_SESSION['user_id'] ?? 0,
        'has_active_event_sub' => $has_active_event_sub
    ]);
}

// ---------------------------------------------------------
// 2. POST: Create Event
// ---------------------------------------------------------
else if ($method === 'POST') {
    try {
        file_put_contents(__DIR__ . '/api_debug.log', "POST Data: " . print_r($_POST, true) . "\n", FILE_APPEND);
    if (!isset($_SESSION['user_id'])) {
        // Dev Fallback: Try to find a default user if session is missing on localhost
        $devUser = $conn->query("SELECT id FROM users WHERE username = 'sailesh' OR id = 1 LIMIT 1")->fetch_assoc();
        $_SESSION['user_id'] = $devUser['id'] ?? 1;
    }

    $user_id = $_SESSION['user_id'];
    
    $isEdit = isset($_POST['id']) && !empty($_POST['id']);
    $active_sub_id = null;
    if (!$isAdmin && !$isEdit) {
        $sub_check = $conn->query("
            SELECT ues.id, ues.events_posted, esp.event_limit
            FROM user_event_subscriptions ues
            JOIN event_subscription_plans esp ON esp.id = ues.plan_id
            WHERE ues.user_id = $user_id
              AND ues.status = 'active'
              AND (ues.expires_at IS NULL OR ues.expires_at > NOW())
              AND ues.events_posted < esp.event_limit
              AND esp.plan_type = 'event'
            ORDER BY ues.created_at ASC
            LIMIT 1
        ");
        if (!$sub_check || $sub_check->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Active Event Hosting Subscription required to host events.']);
            exit;
        }
        $sub = $sub_check->fetch_assoc();
        $active_sub_id = (int)$sub['id'];
    }
    
    $title = $_POST['title'] ?? '';
    $tags = $_POST['tags'] ?? '';
    $language = $_POST['language'] ?? 'Both';
    $age_group = $_POST['age_group'] ?? 'All Ages';
    $description = $_POST['description'] ?? '';
    $what_to_expect = $_POST['what_to_expect'] ?? '';
    $category = $_POST['category'] ?? 'FESTIVAL';
    
    $start_date = $_POST['start_date'] ?: null;
    $end_date = $_POST['end_date'] ?: null;
    $start_time = $_POST['start_time'] ?: null;
    $end_time = $_POST['end_time'] ?: null;
    $is_recurring = !empty($_POST['is_recurring']) ? 1 : 0;
    $recurring_frequency = $_POST['recurring_frequency'] ?? null;
    
    // Date & Month derivation
    $event_date = $start_date; 
    $month = 'JAN';
    if ($start_date) {
        $month = strtoupper(date('M', strtotime($start_date)));
    }
    
    $venue_name = $_POST['venue_name'] ?? '';
    $full_address = $_POST['full_address'] ?? '';
    $region = $_POST['region'] ?? 'Kathmandu Valley';
    $location = $region; // Sync location with region for legacy compatibility
    $google_maps_link = $_POST['google_maps_link'] ?? '';
    
    $is_paid = !empty($_POST['is_paid']) ? 1 : 0;
    $price_npr = (float)($_POST['price_npr'] ?? 0);
    $seats = (int)($_POST['seats'] ?? 0);
    $unlimited_seats = isset($_POST['unlimited_seats']) ? 1 : 0;
    $registration_url = $_POST['registration_url'] ?? '';
    $selling_fast_threshold = (int)($_POST['selling_fast_threshold'] ?? 80);
    
    $organizer_name = $_POST['organizer_name'] ?? '';
    $organizer_contact = $_POST['organizer_contact'] ?? '';
    $organizer_email = $_POST['organizer_email'] ?? '';
    $organizer_website = $_POST['organizer_website'] ?? '';
    $organizer_facebook = $_POST['organizer_facebook'] ?? '';
    $organizer_instagram = $_POST['organizer_instagram'] ?? '';
    
    $is_premium = !empty($_POST['is_premium']) ? 1 : 0;
    $is_featured = ($is_premium == 1) ? 1 : (!empty($_POST['is_featured']) ? 1 : 0);
    $homepage_spotlight = !empty($_POST['homepage_spotlight']) ? 1 : 0;
    $raffle_enabled = !empty($_POST['raffle_enabled']) ? 1 : 0;
    $raffle_draw_time = $_POST['raffle_draw_time'] ?: null;
    if ($raffle_draw_time) {
        $raffle_draw_time = str_replace('T', ' ', $raffle_draw_time);
    }
    $raffle_prize_1 = $_POST['raffle_prize_1'] ?? '';
    $raffle_prize_2 = $_POST['raffle_prize_2'] ?? '';
    $raffle_entry_fee = (float)($_POST['raffle_entry_fee'] ?? 0);
    $free_parking = !empty($_POST['free_parking']) ? 1 : 0;
    $is_international = ($_POST['event_type'] ?? '') === 'International' ? 1 : 0;
    
    $ticket_status = $_POST['ticket_status'] ?? 'Available';
    $early_bird_text = $_POST['early_bird_text'] ?? '';
    $featured_badge_text = $_POST['featured_badge_text'] ?? 'FEATURED';
    $is_private = ($isAdmin) ? 1 : 0;
    
    $ticket_price = $is_paid ? "Rs. " . number_format($price_npr, 2) : "Free";
    $ticket_link = $registration_url;

    $uploadDir = ensureWritableUploadDir();
    if ($uploadDir === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Upload folder is missing or not writable. Create images/uploads and set permissions (chmod 777).',
        ]);
        exit;
    }

    $sanitizeUploadFilename = static function ($name) {
        $name = basename((string)$name);
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
        return $name !== '' ? $name : 'image.jpg';
    };

    $placeholderImage = 'images/placeholder_event.jpg';

    $uploadErrorMessage = static function (int $code): string {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'Cover image exceeds server upload limit.',
            UPLOAD_ERR_FORM_SIZE => 'Cover image exceeds form size limit.',
            UPLOAD_ERR_PARTIAL => 'Cover image upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE => 'No cover image file received.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temp folder missing. Contact support.',
            UPLOAD_ERR_CANT_WRITE => 'Server could not write the cover image.',
            UPLOAD_ERR_EXTENSION => 'Cover image blocked by a server extension.',
        ];
        return $messages[$code] ?? 'Cover image upload failed.';
    };

    // Handle cover image upload
    $image_path = $placeholderImage;
    $coverUploadError = null;
    if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $fileName = time() . '_' . $sanitizeUploadFilename($_FILES['image']['name']);
            $savedCover = saveUploadedFile($_FILES['image'], $uploadDir, $fileName);
            if ($savedCover['ok']) {
                $image_path = $savedCover['path'];
            } else {
                $coverUploadError = $savedCover['error'];
            }
        } else {
            $coverUploadError = $uploadErrorMessage((int)$_FILES['image']['error']);
        }
    } elseif (!empty($_POST['image_path']) && strpos((string)$_POST['image_path'], 'placeholder_event') === false) {
        $image_path = $_POST['image_path'];
    }

    // Handle gallery uploads per slot (merge with existing images on edit)
    $existingBySlot = $_POST['gallery_existing'] ?? [];
    if (!is_array($existingBySlot)) {
        $existingBySlot = [];
    }

    $galleryPaths = [];
    if (isset($_FILES['gallery_images']) && is_array($_FILES['gallery_images']['name'])) {
        $fileArray = $_FILES['gallery_images'];
        $slotCount = count($fileArray['name']);

        for ($i = 0; $i < $slotCount; $i++) {
            if (($fileArray['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $fileName = time() . '_gallery' . $i . '_' . $sanitizeUploadFilename($fileArray['name'][$i]);
                $galleryFile = [
                    'tmp_name' => $fileArray['tmp_name'][$i],
                    'name' => $fileArray['name'][$i],
                    'error' => $fileArray['error'][$i],
                ];
                $savedGallery = saveUploadedFile($galleryFile, $uploadDir, $fileName);
                if ($savedGallery['ok']) {
                    $galleryPaths[] = $savedGallery['path'];
                } elseif (!empty($existingBySlot[$i])) {
                    $galleryPaths[] = trim((string)$existingBySlot[$i]);
                }
            } elseif (!empty($existingBySlot[$i])) {
                $galleryPaths[] = trim((string)$existingBySlot[$i]);
            }
        }
    } else {
        foreach ($existingBySlot as $existingPath) {
            $existingPath = trim((string)$existingPath);
            if ($existingPath !== '') {
                $galleryPaths[] = $existingPath;
            }
        }
    }

    if (!empty($galleryPaths)) {
        $gallery_images = implode(',', $galleryPaths);
    } elseif (!empty($_POST['gallery_images'])) {
        $gallery_images = $_POST['gallery_images'];
    } else {
        $gallery_images = '';
    }

    // Use first gallery image as cover when no dedicated cover was uploaded
    if (($image_path === $placeholderImage || strpos((string)$image_path, 'placeholder_event') !== false) && $gallery_images !== '') {
        $firstGallery = trim(explode(',', $gallery_images)[0]);
        if ($firstGallery !== '') {
            $image_path = $firstGallery;
        }
    }

    $isNewEvent = empty($_POST['id']);
    if ($isNewEvent && ($image_path === $placeholderImage || strpos((string)$image_path, 'placeholder_event') !== false)) {
        $msg = $coverUploadError
            ? $coverUploadError
            : 'Please upload a cover image (Section 2 — top box) or at least one gallery photo.';
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }

    if ($coverUploadError && strpos((string)$image_path, 'placeholder_event') !== false && $gallery_images === '') {
        echo json_encode(['success' => false, 'message' => $coverUploadError]);
        exit;
    }

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $eventId = (int)$_POST['id'];

        $ownerStmt = $conn->prepare("SELECT user_id FROM events WHERE id = ?");
        $ownerStmt->bind_param("i", $eventId);
        $ownerStmt->execute();
        $ownerRow = $ownerStmt->get_result()->fetch_assoc();
        if (!$ownerRow) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Event not found']);
            exit;
        }
        if (!$isAdmin && (int)$ownerRow['user_id'] !== (int)$user_id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized to edit this event']);
            exit;
        }

        $sql = "UPDATE events SET 
                title=?, tags=?, language=?, age_group=?, description=?, what_to_expect=?, image_path=?, gallery_images=?, 
                category=?, event_date=?, month=?, location=?, full_address=?, region=?, 
                start_date=?, end_date=?, start_time=?, end_time=?, is_recurring=?, recurring_frequency=?, 
                venue_name=?, google_maps_link=?, is_paid=?, price_npr=?, seats=?, unlimited_seats=?, 
                registration_url=?, selling_fast_threshold=?, organizer_name=?, organizer_contact=?, 
                organizer_email=?, organizer_website=?, organizer_facebook=?, organizer_instagram=?, 
                is_premium=?, is_featured=?, is_private=?, early_bird_text=?, featured_badge_text=?, 
                ticket_price=?, ticket_link=?, ticket_status=?, homepage_spotlight=?, raffle_enabled=?, 
                raffle_draw_time=?, raffle_prize_1=?, raffle_prize_2=?, raffle_entry_fee=?, free_parking=?, 
                is_international=?
                WHERE id=?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Update Prepare Error: ' . $conn->error]);
            exit;
        }
        $types = str_repeat('s', 18) . 'isss' . 'idiisi' . str_repeat('s', 6) . 'iii' . str_repeat('s', 5) . 'ii' . str_repeat('s', 3) . 'd' . 'iii';
        $stmt->bind_param($types, 
            $title, $tags, $language, $age_group, $description, $what_to_expect, $image_path, $gallery_images,
            $category, $event_date, $month, $location, $full_address, $region,
            $start_date, $end_date, $start_time, $end_time, $is_recurring, $recurring_frequency,
            $venue_name, $google_maps_link, $is_paid, $price_npr, $seats, $unlimited_seats,
            $registration_url, $selling_fast_threshold, $organizer_name, $organizer_contact,
            $organizer_email, $organizer_website, $organizer_facebook, $organizer_instagram,
            $is_premium, $is_featured, $is_private, $early_bird_text, $featured_badge_text,
            $ticket_price, $ticket_link, $ticket_status, $homepage_spotlight, $raffle_enabled,
            $raffle_draw_time, $raffle_prize_1, $raffle_prize_2, $raffle_entry_fee, $free_parking,
            $is_international, $eventId
        );
    } else {
        $sql = "INSERT INTO events (
                title, tags, language, age_group, description, what_to_expect, image_path, gallery_images, 
                category, event_date, month, location, full_address, region, 
                start_date, end_date, start_time, end_time, is_recurring, recurring_frequency, 
                venue_name, google_maps_link, is_paid, price_npr, seats, unlimited_seats, 
                registration_url, selling_fast_threshold, organizer_name, organizer_contact, 
                organizer_email, organizer_website, organizer_facebook, organizer_instagram, 
                is_premium, is_featured, is_private, user_id, early_bird_text, featured_badge_text, 
                ticket_price, ticket_link, ticket_status, homepage_spotlight, raffle_enabled, 
                raffle_draw_time, raffle_prize_1, raffle_prize_2, raffle_entry_fee, free_parking, 
                is_international, subscription_id
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Insert Prepare Error: ' . $conn->error]);
            exit;
        }
        $types = str_repeat('s', 18) . 'isss' . 'idiisi' . str_repeat('s', 6) . 'iiii' . str_repeat('s', 5) . 'ii' . str_repeat('s', 3) . 'd' . 'iii';
        $stmt->bind_param($types, 
            $title, $tags, $language, $age_group, $description, $what_to_expect, $image_path, $gallery_images,
            $category, $event_date, $month, $location, $full_address, $region,
            $start_date, $end_date, $start_time, $end_time, $is_recurring, $recurring_frequency,
            $venue_name, $google_maps_link, $is_paid, $price_npr, $seats, $unlimited_seats,
            $registration_url, $selling_fast_threshold, $organizer_name, $organizer_contact,
            $organizer_email, $organizer_website, $organizer_facebook, $organizer_instagram,
            $is_premium, $is_featured, $is_private, $user_id, $early_bird_text, $featured_badge_text,
            $ticket_price, $ticket_link, $ticket_status, $homepage_spotlight, $raffle_enabled,
            $raffle_draw_time, $raffle_prize_1, $raffle_prize_2, $raffle_entry_fee, $free_parking,
            $is_international, $active_sub_id
        );
    }
    
        if ($stmt->execute()) {
            $new_event_id = $conn->insert_id ?: ($_POST['id'] ?? 0);
            if (!$isEdit && $active_sub_id) {
                $conn->query("UPDATE user_event_subscriptions SET events_posted = events_posted + 1 WHERE id = $active_sub_id");
            }
            echo json_encode(['success' => true, 'message' => 'Event processed successfully', 'id' => $new_event_id]);
        } else {
            file_put_contents(__DIR__ . '/api_debug.log', "DB Error: " . $conn->error . "\n", FILE_APPEND);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
    }
}

// ---------------------------------------------------------
// 3. DELETE: Remove Event (Admin Only)
// ---------------------------------------------------------
else if ($method === 'DELETE') {
    $id = $_GET['id'] ?? 0;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Missing Event ID']);
        exit;
    }

    // Security check: must be owner or admin
    $checkStmt = $conn->prepare("SELECT user_id FROM events WHERE id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result()->fetch_assoc();
    
    $user_id = $_SESSION['user_id'] ?? 0;
    if (!$isAdmin && $checkRes['user_id'] != $user_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized to delete this event']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Event deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Delete failed']);
    }
}

// ---------------------------------------------------------
// 4. iCal Export Logic
// ---------------------------------------------------------
function exportICS($conn) {
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="nepal_events_2026.ics"');

    // 1. Fetch Local Events
    $res = $conn->query("SELECT * FROM events ORDER BY created_at");
    $allEvents = [];
    while ($row = $res->fetch_assoc()) {
        $allEvents[] = $row;
    }

    // 2. Fetch Hamro Patro Festivals
    require_once __DIR__ . '/providers/hamro_patro.php';
    $hpProvider = new HamroPatroProvider();
    $externalEvents = $hpProvider->fetchUpcomingFestivals();
    
    // 3. Merge
    $eventsToExport = array_merge($allEvents, $externalEvents);
    
    echo "BEGIN:VCALENDAR\r\n";
    echo "VERSION:2.0\r\n";
    echo "PRODID:-//Nepal Truly Authentic//Events Calendar//EN\r\n";
    echo "CALSCALE:GREGORIAN\r\n";
    echo "METHOD:PUBLISH\r\n";
    echo "X-WR-CALNAME:Nepal Events & Festivals 2026\r\n";
    echo "X-WR-TIMEZONE:Asia/Kathmandu\r\n";

    foreach ($eventsToExport as $e) {
        $uid = (isset($e['id']) ? $e['id'] : uniqid()) . "@nepaltravel.com";
        $stamp = date('Ymd\THis\Z');
        
        // Date parsing
        $monthNum = getMonthNum($e['month']);
        $start = "2026" . $monthNum . "01T090000Z"; 
        
        $title = $e['title'];
        if (!empty($e['event_date'])) {
            $title .= " (" . $e['event_date'] . ")";
        }

        $description = $e['description'];
        if (!empty($e['event_date'])) {
            $description = "Traditional Nepali Date: " . $e['event_date'] . "\n\n" . $description;
        }
        
        echo "BEGIN:VEVENT\r\n";
        echo "UID:$uid\r\n";
        echo "DTSTAMP:$stamp\r\n";
        echo "DTSTART:$start\r\n";
        echo "SUMMARY:" . escapeICS($title) . "\r\n";
        echo "DESCRIPTION:" . escapeICS($description) . "\r\n";
        echo "CATEGORIES:" . escapeICS($e['category']) . "\r\n";
        echo "END:VEVENT\r\n";
    }

    echo "END:VCALENDAR\r\n";
}

function getMonthNum($m) {
    $months = ['JAN'=>'01','FEB'=>'02','MAR'=>'03','APR'=>'04','MAY'=>'05','JUN'=>'06','JUL'=>'07','AUG'=>'08','SEP'=>'09','OCT'=>'10','NOV'=>'11','DEC'=>'12'];
    return $months[$m] ?? '01';
}

function escapeICS($str) {
    return str_replace([",", ";", "\n"], ["\\,", "\\;", "\\n"], $str);
}
?>