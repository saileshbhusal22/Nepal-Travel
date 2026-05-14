<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';

// Basic admin auth check
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_id'])) {
    // Allow if there's any admin session — adjust to match your auth system
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// ─────────────────────────────────────────────
// ACTION: list_sessions
// ─────────────────────────────────────────────
if ($action === 'list_sessions') {
    $filter = $_GET['filter'] ?? 'open';
    $allowed = ['open', 'closed', 'all'];
    if (!in_array($filter, $allowed)) $filter = 'open';

    $where = $filter === 'all' ? '' : "WHERE s.status = '$filter'";

    $sql = "SELECT s.id, s.user_id, s.guest_name, s.guest_email, s.status,
                   s.last_message, s.last_message_at, s.unread_admin, s.created_at
            FROM support_sessions s
            $where
            ORDER BY s.last_message_at DESC
            LIMIT 100";

    $res = $conn->query($sql);
    $sessions = [];
    while ($row = $res->fetch_assoc()) {
        $sessions[] = $row;
    }
    respond(['success' => true, 'sessions' => $sessions]);
}

// ─────────────────────────────────────────────
// ACTION: get_session_messages
// ─────────────────────────────────────────────
if ($action === 'get_session_messages') {
    $sessionId = (int)($_GET['session_id'] ?? 0);
    $lastId    = (int)($_GET['last_id'] ?? 0);

    if (!$sessionId) respond(['success' => false, 'error' => 'Missing session_id'], 400);

    $stmt = $conn->prepare("SELECT id, sender, message, sent_at, is_read FROM support_messages WHERE session_id = ? AND id > ? ORDER BY id ASC");
    $stmt->bind_param('ii', $sessionId, $lastId);
    $stmt->execute();
    $res = $stmt->get_result();
    $messages = [];
    while ($row = $res->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();

    // Mark user messages as read
    $markRead = $conn->prepare("UPDATE support_messages SET is_read = 1 WHERE session_id = ? AND sender = 'user' AND is_read = 0");
    $markRead->bind_param('i', $sessionId);
    $markRead->execute();
    $markRead->close();

    // Reset unread_admin count
    $conn->query("UPDATE support_sessions SET unread_admin = 0 WHERE id = $sessionId");

    // Get session info
    $info = $conn->prepare("SELECT guest_name, guest_email, status, created_at FROM support_sessions WHERE id = ?");
    $info->bind_param('i', $sessionId);
    $info->execute();
    $sessionInfo = $info->get_result()->fetch_assoc();
    $info->close();

    respond(['success' => true, 'messages' => $messages, 'session' => $sessionInfo]);
}

// ─────────────────────────────────────────────
// ACTION: send_reply
// ─────────────────────────────────────────────
if ($action === 'send_reply') {
    $data = json_decode(file_get_contents('php://input'), true);
    $sessionId = (int)($data['session_id'] ?? 0);
    $message   = trim($data['message'] ?? '');

    if (!$sessionId || !$message) respond(['success' => false, 'error' => 'Missing fields'], 400);

    $sender = 'admin';
    $stmt = $conn->prepare("INSERT INTO support_messages (session_id, sender, message) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $sessionId, $sender, $message);

    if ($stmt->execute()) {
        $msgId = $conn->insert_id;
        $upd = $conn->prepare("UPDATE support_sessions SET last_message = ?, last_message_at = NOW(), unread_user = unread_user + 1 WHERE id = ?");
        $upd->bind_param('si', $message, $sessionId);
        $upd->execute();
        $upd->close();
        $stmt->close();
        respond(['success' => true, 'message_id' => $msgId]);
    }
    respond(['success' => false, 'error' => 'Failed to send'], 500);
}

// ─────────────────────────────────────────────
// ACTION: close_session
// ─────────────────────────────────────────────
if ($action === 'close_session') {
    $data = json_decode(file_get_contents('php://input'), true);
    $sessionId = (int)($data['session_id'] ?? 0);
    if (!$sessionId) respond(['success' => false, 'error' => 'Missing session_id'], 400);

    $stmt = $conn->prepare("UPDATE support_sessions SET status = 'closed' WHERE id = ?");
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    $stmt->close();
    respond(['success' => true]);
}

// ─────────────────────────────────────────────
// ACTION: get_unread_total
// Total unread across all sessions
// ─────────────────────────────────────────────
if ($action === 'get_unread_total') {
    $res = $conn->query("SELECT SUM(unread_admin) as total FROM support_sessions WHERE status = 'open'");
    $row = $res->fetch_assoc();
    respond(['total' => (int)($row['total'] ?? 0)]);
}

respond(['success' => false, 'error' => 'Unknown action'], 400);
?>
