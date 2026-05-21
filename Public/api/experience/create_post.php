<?php
session_start();
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/install_experience_subscriptions.php';
require_once __DIR__ . '/../../../includes/experience_subscription.php';

install_experience_subscriptions_if_needed($conn);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if (!experience_can_create_post($conn, $user_id)) {
    $quota = experience_get_post_quota($conn, $user_id);
    echo json_encode([
        'success' => false,
        'message' => 'You have used all ' . EXPERIENCE_FREE_POST_LIMIT . ' free posts. Subscribe to continue sharing experiences.',
        'subscription_required' => true,
        'quota' => $quota,
        'subscription_url' => '/Nepal-Travel/Public/experience-subscription.php',
    ]);
    exit;
}
$caption = isset($_POST['caption']) ? trim($_POST['caption']) : '';
$post_location = isset($_POST['location']) && !empty(trim($_POST['location'])) ? trim($_POST['location']) : null;
$post_destination = isset($_POST['destination']) && !empty(trim($_POST['destination'])) ? trim($_POST['destination']) : null;
$tags = isset($_POST['tags']) && !empty(trim($_POST['tags'])) ? trim($_POST['tags']) : null;

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Image upload failed']);
    exit;
}

$image = $_FILES['image'];
$ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
$allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

if (!in_array($ext, $allowed_exts)) {
    echo json_encode(['success' => false, 'message' => 'Invalid image format']);
    exit;
}

$upload_dir = __DIR__ . '/../../../images/experience/';  
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$new_filename = uniqid('post_', true) . '.' . $ext;
$destination = $upload_dir . $new_filename;

if (move_uploaded_file($image['tmp_name'], $destination)) {
    // Save to database
    $image_path = 'images/experience/' . $new_filename;  
    
    $stmt = $conn->prepare("INSERT INTO posts (user_id, image_path, caption, destination, location, tags) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $image_path, $caption, $post_destination, $post_location, $tags);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Post created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
}

$conn->close();
?>
