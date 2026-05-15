<?php
// ============================================================
// PUBLIC/CHAT_AJAX.PHP - User chat handler
// ============================================================

session_start();
header('Content-Type: application/json; charset=utf-8');

// Database connection
require_once __DIR__ . '/../config/db.php';

function json_response($ok, $data = [], $error = '') {
    echo json_encode([
        'ok'        => (bool)$ok,
        'data'      => $data,
        'error'     => $error,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// ── Auth: logged-in users only ───────────────────────────────
if (!isset($_SESSION['user_id'])) {
    json_response(false, [], 'Not authenticated');
}

// Block admins — they should use admin/chat_ajax.php
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    json_response(false, [], 'Use admin chat endpoint');
}

$action  = trim($_POST['action'] ?? $_GET['action'] ?? '');
$user_id = (int)$_SESSION['user_id'];

// ── Ensure chat_messages table exists ────────────────────────
$createTableSQL = "
    CREATE TABLE IF NOT EXISTS chat_messages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        sender ENUM('user', 'admin') NOT NULL DEFAULT 'user',
        message TEXT NOT NULL,
        is_read TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_user_id (user_id),
        KEY idx_created_at (created_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (!$conn->query($createTableSQL)) {
    error_log('Failed to create chat_messages table: ' . $conn->error);
}

// ── FETCH ────────────────────────────────────────────────────
if ($action === 'fetch') {
    $stmt = $conn->prepare("
        SELECT id, message, sender, is_read, created_at
        FROM chat_messages
        WHERE user_id = ?
        ORDER BY created_at ASC
        LIMIT 100
    ");
    
    if (!$stmt) {
        json_response(false, [], 'Database error: ' . $conn->error);
    }

    $stmt->bind_param('i', $user_id);
    
    if (!$stmt->execute()) {
        json_response(false, [], 'Query failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'id'         => (int)$row['id'],
            'message'    => $row['message'],
            'sender'     => $row['sender'],
            'is_read'    => (int)$row['is_read'],
            'created_at' => $row['created_at']
        ];
    }
    $stmt->close();

    // Mark admin messages as read
    $upd = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE user_id = ? AND sender = 'admin' AND is_read = 0");
    if ($upd) {
        $upd->bind_param('i', $user_id);
        $upd->execute();
        $upd->close();
    }

    json_response(true, ['messages' => $messages]);
}

// ── SEND ─────────────────────────────────────────────────────
if ($action === 'send') {
    $message = trim($_POST['message'] ?? '');

    if (empty($message)) {
        json_response(false, [], 'Message cannot be empty');
    }
    
    if (strlen($message) > 5000) {
        json_response(false, [], 'Message too long (max 5000 characters)');
    }

    // Sanitize message
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    $stmt = $conn->prepare("
        INSERT INTO chat_messages (user_id, sender, message, is_read, created_at)
        VALUES (?, 'user', ?, 0, NOW())
    ");
    
    if (!$stmt) {
        json_response(false, [], 'Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('is', $user_id, $message);

    if ($stmt->execute()) {
        json_response(true, [
            'id'         => $conn->insert_id,
            'message'    => $message,
            'sender'     => 'user',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } else {
        json_response(false, [], 'Failed to save message: ' . $stmt->error);
    }
    
    $stmt->close();
}

// ── UNREAD_COUNT ─────────────────────────────────────────────
if ($action === 'unread_count') {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM chat_messages
        WHERE user_id = ? AND sender = 'admin' AND is_read = 0
    ");
    
    if (!$stmt) {
        json_response(false, [], 'Database error: ' . $conn->error);
    }

    $stmt->bind_param('i', $user_id);
    
    if (!$stmt->execute()) {
        json_response(false, [], 'Query failed');
    }
    
    $result = $stmt->get_result();
    $row    = $result->fetch_assoc();
    $stmt->close();

    json_response(true, ['count' => (int)($row['count'] ?? 0)]);
}

// ── DEFAULT ──────────────────────────────────────────────────
json_response(false, [], 'Unknown action: ' . $action);
?>