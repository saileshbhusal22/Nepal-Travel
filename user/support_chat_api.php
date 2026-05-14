<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ─────────────────────────────────────────────
// Helper: JSON response
// ─────────────────────────────────────────────
function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// ─────────────────────────────────────────────
// ACTION: start_session
// Creates or retrieves a support session for the user
// ─────────────────────────────────────────────
if ($action === 'start_session') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $_SESSION['user_id'] ?? null;
    $guestName = htmlspecialchars($data['guest_name'] ?? 'Guest');
    $guestEmail = htmlspecialchars($data['guest_email'] ?? '');

    if ($userId) {
        // Check for an existing open session for logged-in user
        $stmt = $conn->prepare("SELECT id FROM support_sessions WHERE user_id = ? AND status = 'open' ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($res) {
            respond(['success' => true, 'session_id' => $res['id']]);
        }

        // Create new session
        $name = htmlspecialchars($_SESSION['user_name'] ?? 'User');
        $email = htmlspecialchars($_SESSION['user_email'] ?? '');
        $stmt = $conn->prepare("INSERT INTO support_sessions (user_id, guest_name, guest_email) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $userId, $name, $email);
    } else {
        // Guest — check session storage
        if (!empty($_SESSION['support_session_id'])) {
            $sid = (int)$_SESSION['support_session_id'];
            $check = $conn->prepare("SELECT id FROM support_sessions WHERE id = ? AND status = 'open'");
            $check->bind_param('i', $sid);
            $check->execute();
            if ($row = $check->get_result()->fetch_assoc()) {
                $check->close();
                respond(['success' => true, 'session_id' => $row['id']]);
            }
            $check->close();
        }
        // New guest session
        $stmt = $conn->prepare("INSERT INTO support_sessions (guest_name, guest_email) VALUES (?, ?)");
        $stmt->bind_param('ss', $guestName, $guestEmail);
    }

    if ($stmt->execute()) {
        $sessionId = $conn->insert_id;
        $_SESSION['support_session_id'] = $sessionId;
        $stmt->close();
        respond(['success' => true, 'session_id' => $sessionId]);
    }
    respond(['success' => false, 'error' => 'Could not start session'], 500);
}

// ─────────────────────────────────────────────
// ACTION: send_message
// ─────────────────────────────────────────────
if ($action === 'send_message') {
    $data = json_decode(file_get_contents('php://input'), true);
    $sessionId = (int)($data['session_id'] ?? 0);
    $message = trim($data['message'] ?? '');

    if (!$sessionId || !$message) {
        respond(['success' => false, 'error' => 'Missing session or message'], 400);
    }

    // Verify session exists & is open
    $check = $conn->prepare("SELECT id FROM support_sessions WHERE id = ? AND status = 'open'");
    $check->bind_param('i', $sessionId);
    $check->execute();
    if (!$check->get_result()->fetch_assoc()) {
        respond(['success' => false, 'error' => 'Session not found or closed'], 404);
    }
    $check->close();

    $sender = 'user';
    $stmt = $conn->prepare("INSERT INTO support_messages (session_id, sender, message) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $sessionId, $sender, $message);

    if ($stmt->execute()) {
        $msgId = $conn->insert_id;
        // Update session last message & unread count
        $upd = $conn->prepare("UPDATE support_sessions SET last_message = ?, last_message_at = NOW(), unread_admin = unread_admin + 1 WHERE id = ?");
        $upd->bind_param('si', $message, $sessionId);
        $upd->execute();
        $upd->close();
        $stmt->close();
        respond(['success' => true, 'message_id' => $msgId]);
    }
    respond(['success' => false, 'error' => 'Failed to send'], 500);
}

// ─────────────────────────────────────────────
// ACTION: get_messages
// Poll for new messages since last_id
// ─────────────────────────────────────────────
if ($action === 'get_messages') {
    $sessionId = (int)($_GET['session_id'] ?? 0);
    $lastId    = (int)($_GET['last_id'] ?? 0);

    if (!$sessionId) respond(['success' => false, 'error' => 'Missing session_id'], 400);

    $stmt = $conn->prepare("SELECT id, sender, message, sent_at FROM support_messages WHERE session_id = ? AND id > ? ORDER BY id ASC");
    $stmt->bind_param('ii', $sessionId, $lastId);
    $stmt->execute();
    $res = $stmt->get_result();
    $messages = [];
    while ($row = $res->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();

    // Mark admin messages as read
    $markRead = $conn->prepare("UPDATE support_messages SET is_read = 1 WHERE session_id = ? AND sender = 'admin' AND is_read = 0");
    $markRead->bind_param('i', $sessionId);
    $markRead->execute();
    $markRead->close();

    // Reset unread_user count
    $conn->query("UPDATE support_sessions SET unread_user = 0 WHERE id = $sessionId");

    // Check session status
    $statusQ = $conn->prepare("SELECT status FROM support_sessions WHERE id = ?");
    $statusQ->bind_param('i', $sessionId);
    $statusQ->execute();
    $statusRow = $statusQ->get_result()->fetch_assoc();
    $statusQ->close();

    respond(['success' => true, 'messages' => $messages, 'session_status' => $statusRow['status'] ?? 'open']);
}

// ─────────────────────────────────────────────
// ACTION: get_unread_count
// ─────────────────────────────────────────────
if ($action === 'get_unread_count') {
    $sessionId = (int)($_GET['session_id'] ?? 0);
    if (!$sessionId) respond(['count' => 0]);
    $stmt = $conn->prepare("SELECT unread_user FROM support_sessions WHERE id = ?");
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    respond(['count' => (int)($row['unread_user'] ?? 0)]);
}

respond(['success' => false, 'error' => 'Unknown action'], 400);
?>
