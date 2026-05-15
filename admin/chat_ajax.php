<?php
session_start();

// ── CRITICAL: Release session lock immediately after reading session data ────
// Without this, concurrent AJAX polling requests lock the PHP session file,
// which causes the main page reload to hang and then redirect to login.
$session_user_id = $_SESSION['user_id'] ?? null;
$session_role    = $_SESSION['role']    ?? null;
session_write_close(); // Release the session file lock NOW

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
// Prevent any caching of AJAX responses
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

function json_response($ok, $data = [], $error = '') {
    echo json_encode([
        'ok'        => (bool)$ok,
        'data'      => $data,
        'error'     => $error,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// ── Auth: must be logged in ──────────────────────────────────────────────────
if (!$session_user_id) {
    json_response(false, [], 'Not authenticated');
}

$uid_tmp = (int)$session_user_id;

// ── Load role from DB (don't touch session — it's already closed) ────────────
$role_res = $conn->query("SELECT role FROM users WHERE id = $uid_tmp LIMIT 1");
if (!$role_res || !($role_row = $role_res->fetch_assoc())) {
    json_response(false, [], 'User not found');
}

if ($role_row['role'] !== 'admin') {
    json_response(false, [], 'Admin access required');
}

$action   = trim($_POST['action'] ?? $_GET['action'] ?? '');
$admin_id = $uid_tmp;

// ── ADMIN_FETCH_CONVERSATIONS ────────────────────────────────────────────────
if ($action === 'admin_fetch_conversations') {
    $query = "
        SELECT
            cm.user_id,
            u.full_name,
            u.username,
            u.email,
            u.profile_image,
            MAX(cm.created_at) AS last_message_time,
            SUM(CASE WHEN cm.sender = 'user' AND cm.is_read = 0 THEN 1 ELSE 0 END) AS unread_count,
            (SELECT message FROM chat_messages
             WHERE user_id = cm.user_id
             ORDER BY created_at DESC LIMIT 1) AS last_message,
            (SELECT sender FROM chat_messages
             WHERE user_id = cm.user_id
             ORDER BY created_at DESC LIMIT 1) AS last_sender
        FROM chat_messages cm
        LEFT JOIN users u ON u.id = cm.user_id
        GROUP BY cm.user_id, u.full_name, u.username, u.email, u.profile_image
        ORDER BY MAX(cm.created_at) DESC
    ";

    $result = $conn->query($query);
    if (!$result) {
        json_response(false, [], 'Database error: ' . $conn->error);
    }

    $conversations = [];
    while ($row = $result->fetch_assoc()) {
        $conversations[] = [
            'user_id'           => (int)$row['user_id'],
            'full_name'         => $row['full_name'],
            'username'          => $row['username'],
            'email'             => $row['email'],
            'profile_image'     => $row['profile_image'],
            'last_message_time' => $row['last_message_time'],
            'unread_count'      => (int)$row['unread_count'],
            'last_message'      => $row['last_message'],
            'last_sender'       => $row['last_sender']
        ];
    }

    json_response(true, ['conversations' => $conversations]);
}

// ── ADMIN_FETCH_MESSAGES ─────────────────────────────────────────────────────
if ($action === 'admin_fetch_messages') {
    $target_user_id = (int)($_POST['user_id'] ?? 0);

    if ($target_user_id <= 0) {
        json_response(false, [], 'Invalid user ID');
    }

    $stmt = $conn->prepare("
        SELECT id, message, sender, is_read, created_at
        FROM chat_messages
        WHERE user_id = ?
        ORDER BY created_at ASC
        LIMIT 300
    ");
    if (!$stmt) {
        json_response(false, [], 'Database error: ' . $conn->error);
    }

    $stmt->bind_param('i', $target_user_id);
    $stmt->execute();
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

    // Mark user messages as read
    $upd = $conn->prepare("
        UPDATE chat_messages
        SET is_read = 1
        WHERE user_id = ? AND sender = 'user' AND is_read = 0
    ");
    if ($upd) {
        $upd->bind_param('i', $target_user_id);
        $upd->execute();
        $upd->close();
    }

    json_response(true, ['messages' => $messages]);
}

// ── ADMIN_REPLY ──────────────────────────────────────────────────────────────
if ($action === 'admin_reply') {
    $target_user_id = (int)($_POST['user_id'] ?? 0);
    $message        = trim($_POST['message'] ?? '');

    if ($target_user_id <= 0) {
        json_response(false, [], 'Invalid user ID');
    }
    if ($message === '') {
        json_response(false, [], 'Message cannot be empty');
    }
    if (strlen($message) > 5000) {
        json_response(false, [], 'Message too long (max 5000 chars)');
    }

    // Verify user exists
    $chk = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
    if ($chk) {
        $chk->bind_param('i', $target_user_id);
        $chk->execute();
        $exists = $chk->get_result()->num_rows > 0;
        $chk->close();
        if (!$exists) {
            json_response(false, [], 'User does not exist');
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO chat_messages (user_id, sender, message, is_read, created_at)
        VALUES (?, 'admin', ?, 0, NOW())
    ");
    if (!$stmt) {
        json_response(false, [], 'Database error: ' . $conn->error);
    }

    $stmt->bind_param('is', $target_user_id, $message);

    if ($stmt->execute()) {
        json_response(true, [
            'id'         => $conn->insert_id,
            'message'    => $message,
            'sender'     => 'admin',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } else {
        json_response(false, [], 'Failed to save reply: ' . $stmt->error);
    }
    $stmt->close();
}

// ── ADMIN_DELETE_CONVERSATION ────────────────────────────────────────────────
if ($action === 'admin_delete_conversation') {
    $target_user_id = (int)($_POST['user_id'] ?? 0);

    if ($target_user_id <= 0) {
        json_response(false, [], 'Invalid user ID');
    }

    $stmt = $conn->prepare("DELETE FROM chat_messages WHERE user_id = ?");
    if (!$stmt) {
        json_response(false, [], 'Database error: ' . $conn->error);
    }

    $stmt->bind_param('i', $target_user_id);

    if ($stmt->execute()) {
        json_response(true, ['deleted_count' => $stmt->affected_rows]);
    } else {
        json_response(false, [], 'Failed to delete conversation');
    }
    $stmt->close();
}

// ── ADMIN_UNREAD_TOTAL ───────────────────────────────────────────────────────
if ($action === 'admin_unread_total') {
    $result = $conn->query("SELECT COUNT(*) AS count FROM chat_messages WHERE sender = 'user' AND is_read = 0");
    if (!$result) {
        json_response(false, [], 'Database error');
    }
    $row = $result->fetch_assoc();
    json_response(true, ['count' => (int)$row['count']]);
}

// ── DEFAULT ──────────────────────────────────────────────────────────────────
json_response(false, [], 'Unknown action: ' . $action);