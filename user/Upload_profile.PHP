<?php
// Catch ANY PHP error/warning and return it as JSON instead of breaking the response
set_error_handler(function($errno, $errstr) {
    echo json_encode(['success' => false, 'message' => "PHP Error: $errstr (code $errno)"]);
    exit;
});

if (session_status() === PHP_SESSION_NONE) { session_start(); }

header('Content-Type: application/json');

// ── 1. Auth check ──────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in. Please refresh and try again.']);
    exit;
}

// ── 2. DB connection ───────────────────────────────────────────
require_once '../config/db.php'; // $conn = mysqli

// ── 3. File check ──────────────────────────────────────────────
if (!isset($_FILES['profile_image'])) {
    echo json_encode(['success' => false, 'message' => 'No file received by server.']);
    exit;
}

$uploadErrors = [
    UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload_max_filesize limit.',
    UPLOAD_ERR_FORM_SIZE  => 'File exceeds form MAX_FILE_SIZE limit.',
    UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
    UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server.',
    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
    UPLOAD_ERR_EXTENSION  => 'A PHP extension blocked the upload.',
];

$errCode = $_FILES['profile_image']['error'];
if ($errCode !== UPLOAD_ERR_OK) {
    $msg = $uploadErrors[$errCode] ?? "Unknown upload error (code $errCode).";
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

$file = $_FILES['profile_image'];

// ── 4. Validate MIME type ──────────────────────────────────────
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if (function_exists('finfo_open')) {
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
} else {
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mimeMap  = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
    $mimeType = $mimeMap[$ext] ?? 'unknown';
}

if (!in_array($mimeType, $allowed)) {
    echo json_encode(['success' => false, 'message' => "Invalid file type ($mimeType). Only JPG, PNG, GIF, WEBP allowed."]);
    exit;
}

// ── 5. Validate size (5 MB) ────────────────────────────────────
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
    exit;
}

// ── 6. Create upload directory ─────────────────────────────────
$uploadDir = __DIR__ . '/../assets/uploads/profiles/';

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Could not create upload directory. Check folder permissions.']);
        exit;
    }
}

if (!is_writable($uploadDir)) {
    echo json_encode(['success' => false, 'message' => 'Upload directory is not writable: ' . $uploadDir]);
    exit;
}

// ── 7. Delete old profile image (skip default.png) ────────────
$stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result  = $stmt->get_result();
$oldUser = $result->fetch_assoc();
$stmt->close();

if ($oldUser && !empty($oldUser['profile_image']) && $oldUser['profile_image'] !== 'default.png') {
    $oldFilePath = __DIR__ . '/../' . $oldUser['profile_image'];
    if (file_exists($oldFilePath)) {
        unlink($oldFilePath);
    }
}

// ── 8. Move uploaded file ──────────────────────────────────────
$ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename   = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
$destPath   = $uploadDir . $filename;
$dbSavePath = 'assets/uploads/profiles/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file. Check server temp/permissions.']);
    exit;
}

// ── 9. Update database ─────────────────────────────────────────
$stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
$stmt->bind_param("si", $dbSavePath, $_SESSION['user_id']);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'File saved but DB update failed: ' . $stmt->error]);
    $stmt->close();
    exit;
}
$stmt->close();

// ── 10. Update session & respond ──────────────────────────────
$_SESSION['profile_image'] = $dbSavePath;

echo json_encode([
    'success'   => true,
    'message'   => 'Profile photo updated successfully!',
    'image_url' => '/Nepal-Travel/' . $dbSavePath . '?t=' . time()
]);