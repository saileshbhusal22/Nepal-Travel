<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $doc_name = trim($_POST['document_name'] ?? '');
    
    if (empty($doc_name) || !isset($_FILES['document_file'])) {
        echo json_encode(['success' => false, 'message' => 'Missing data']);
        exit;
    }

    $file = $_FILES['document_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF, JPG, PNG allowed.']);
        exit;
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large (max 10MB)']);
        exit;
    }

    $upload_dir = '../uploads/documents/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_name = 'doc_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target_path = $upload_dir . $file_name;
    $db_path = '/Nepal-Travel/uploads/documents/' . $file_name;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        $stmt = $conn->prepare("INSERT INTO user_documents (user_id, document_name, document_type, file_path) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $doc_name, $ext, $db_path);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
    }
}
?>
