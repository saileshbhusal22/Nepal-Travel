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
    sendJson(['success' => false, 'message' => 'Invalid travel idea selected'], 400);
}

$fetchStmt = $conn->prepare(
    "SELECT t.user_id, t.image_path, d.hero_image
     FROM travel_ideas t
     LEFT JOIN travel_idea_details d ON d.idea_id = t.id
     WHERE t.id = ?
     LIMIT 1"
);
if (!$fetchStmt) {
    sendJson(['success' => false, 'message' => 'Database error', 'error' => $conn->error], 500);
}
$fetchStmt->bind_param('i', $idea_id);
$fetchStmt->execute();
$result = $fetchStmt->get_result();
if (!$result || $result->num_rows === 0) {
    sendJson(['success' => false, 'message' => 'Travel idea not found'], 404);
}
$row = $result->fetch_assoc();
$fetchStmt->close();

if (empty($row['user_id']) || (int)$row['user_id'] !== $user_id) {
    sendJson(['success' => false, 'message' => 'You are not authorized to delete this travel idea'], 403);
}

$imagePaths = [];
if (!empty($row['hero_image'])) {
    $imagePaths[] = $row['hero_image'];
}
if (!empty($row['image_path'])) {
    $imagePaths[] = $row['image_path'];
}

$itineraryStmt = $conn->prepare("SELECT image_path FROM itineraries WHERE idea_id = ?");
if ($itineraryStmt) {
    $itineraryStmt->bind_param('i', $idea_id);
    $itineraryStmt->execute();
    $itineraryResult = $itineraryStmt->get_result();
    while ($itineraryRow = $itineraryResult->fetch_assoc()) {
        if (!empty($itineraryRow['image_path'])) {
            $imagePaths[] = $itineraryRow['image_path'];
        }
    }
    $itineraryStmt->close();
}

try {
    $conn->begin_transaction();

    $tables = [
        'travel_idea_experiences' => 'idea_id',
        'itineraries' => 'idea_id',
        'travel_idea_details' => 'idea_id',
        'travel_ideas' => 'id',
    ];

    foreach ($tables as $table => $column) {
        $stmt = $conn->prepare("DELETE FROM {$table} WHERE {$column} = ?");
        if (!$stmt) {
            throw new RuntimeException($conn->error ?: 'Failed to prepare delete statement');
        }
        $stmt->bind_param('i', $idea_id);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();

    foreach ($imagePaths as $imagePath) {
        $imagePath = str_replace('\\', '/', $imagePath);
        $parsed = parse_url($imagePath, PHP_URL_PATH);
        $normalized = $parsed !== null ? $parsed : $imagePath;
        $normalized = preg_replace('#^(?:\.{1,2}/)+|^/+#', '', $normalized);
        $localPath = dirname(__DIR__, 3) . '/' . $normalized;
        if (file_exists($localPath) && is_file($localPath)) {
            @unlink($localPath);
        }
    }

    sendJson(['success' => true, 'message' => 'Travel idea deleted successfully']);
} catch (Throwable $e) {
    if ($conn->errno) {
        $conn->rollback();
    }
    sendJson(['success' => false, 'message' => 'Unable to delete travel idea', 'error' => $e->getMessage()], 500);
}
