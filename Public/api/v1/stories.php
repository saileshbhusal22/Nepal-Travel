<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../../config/db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Fetch latest 12 stories for slider/pagination
    $res = $conn->query("SELECT * FROM traveler_stories ORDER BY created_at DESC LIMIT 12");
    $stories = [];
    while ($row = $res->fetch_assoc()) {
        $stories[] = $row;
    }
    
    // Fallback to defaults if none found
    if (empty($stories)) {
        $stories = [
            [
                'name' => 'Sarah Jenkins',
                'country' => 'Australia',
                'quote' => "Nepal exceeded every expectation. The Sherpa people's hospitality is as high as the mountains they live in. EBC was life-changing!",
                'image_path' => 'images/annapurna_trek.png'
            ],
            [
                'name' => 'Marco Rossi',
                'country' => 'Italy',
                'quote' => "The food in Kathmandu and the peace in Pokhara — Nepal is a perfect blend of chaos and serenity. Can't wait to go back!",
                'image_path' => 'images/pokhara_lake.png'
            ],
            [
                'name' => 'Liam O\'Connell',
                'country' => 'Ireland',
                'quote' => "Seeing a tiger in Chitwan was the highlight of my year. Nepal is truly a wildlife lover's paradise.",
                'image_path' => 'images/chitwan_rhino.png'
            ]
        ];
    }
    
    echo json_encode(['success' => true, 'stories' => $stories]);
}

if ($method === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Please login to share your story.']);
        exit;
    }

    $name = $_POST['name'] ?? '';
    $country = $_POST['country'] ?? '';
    $quote = $_POST['quote'] ?? '';
    $user_id = $_SESSION['user_id'];
    $image_path = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=f5a623&color=fff&size=150'; // Default

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploadDir = __DIR__ . '/../../../images/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $image_path = 'images/uploads/' . $fileName;
        }
    }

    $stmt = $conn->prepare("INSERT INTO traveler_stories (user_id, name, country, quote, image_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $name, $country, $quote, $image_path);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Story shared successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
}
?>