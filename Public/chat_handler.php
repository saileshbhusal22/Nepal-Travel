<?php
/**
 * chat_handler.php
 * Place this at: /Nepal-Travel/Public/chat_handler.php
 *
 * Required DB table (run once):
 * ─────────────────────────────────────────────────────────────
 * CREATE TABLE IF NOT EXISTS chat_messages (
 *   id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *   session_id   VARCHAR(64)  NOT NULL,
 *   user_id      INT UNSIGNED DEFAULT NULL,
 *   sender       ENUM('user','admin') NOT NULL,
 *   message      TEXT NOT NULL,
 *   is_read      TINYINT(1) DEFAULT 0,
 *   created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
 *   INDEX idx_session (session_id),
 *   INDEX idx_created (created_at)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 * ─────────────────────────────────────────────────────────────
 */

$action = $_REQUEST['action'] ?? '';
if (in_array($action, ['admin_poll', 'admin_reply', 'unread_count', 'admin_delete_all']) && isset($_COOKIE['nepal_admin_session'])) {
    session_name('nepal_admin_session');
}
session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/db.php';

// ── Auto-create table if missing ──────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS chat_messages (
        id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        session_id   VARCHAR(64)  NOT NULL,
        user_id      INT UNSIGNED DEFAULT NULL,
        sender       ENUM('user','admin') NOT NULL,
        message      TEXT NOT NULL,
        is_read      TINYINT(1) DEFAULT 0,
        created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_session (session_id),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Action parsed above

// ── Helpers ───────────────────────────────────────────────────────
function jsonOut($data) { echo json_encode($data); exit; }
function fail($msg)     { jsonOut(['ok' => false, 'error' => $msg]); }

// ── Identify current actor ────────────────────────────────────────
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$userId  = (!$isAdmin && isset($_SESSION['user_id'])) ? (int)$_SESSION['user_id'] : null;

function requireLoggedInUser(): void {
    global $userId;
    if (!$userId) {
        jsonOut(['ok' => false, 'error' => 'Please log in to use chat.', 'login_required' => true]);
    }
}

function userChatSessionId(int $uid): string {
    return 'user_' . $uid;
}

// ══════════════════════════════════════════════════════════════════
//  ACTION: user sends a message
// ══════════════════════════════════════════════════════════════════
if ($action === 'send') {
    requireLoggedInUser();

    $raw = trim($_POST['message'] ?? '');
    if ($raw === '') fail('Empty message.');

    $msg  = $conn->real_escape_string(htmlspecialchars($raw, ENT_QUOTES, 'UTF-8'));
    $sess = $conn->real_escape_string(userChatSessionId($userId));

    $conn->query("
        INSERT INTO chat_messages (session_id, user_id, sender, message)
        VALUES ('$sess', $userId, 'user', '$msg')
    ");

    jsonOut(['ok' => true, 'id' => $conn->insert_id]);
}

// ══════════════════════════════════════════════════════════════════
//  ACTION: admin sends reply to a session
// ══════════════════════════════════════════════════════════════════
if ($action === 'admin_reply') {
    if (!$isAdmin) fail('Unauthorized.');

    $raw  = trim($_POST['message'] ?? '');
    $sess = $conn->real_escape_string($_POST['session_id'] ?? '');
    if ($raw === '' || $sess === '') fail('Missing fields.');

    $msg = $conn->real_escape_string(htmlspecialchars($raw, ENT_QUOTES, 'UTF-8'));
    $uid = (int)($_SESSION['user_id'] ?? 0);

    $conn->query("
        INSERT INTO chat_messages (session_id, user_id, sender, message)
        VALUES ('$sess', $uid, 'admin', '$msg')
    ");

    // Mark all user messages in this session as read
    $conn->query("
        UPDATE chat_messages
        SET is_read = 1
        WHERE session_id = '$sess' AND sender = 'user'
    ");

    jsonOut(['ok' => true, 'id' => $conn->insert_id]);
}

// ══════════════════════════════════════════════════════════════════
//  ACTION: poll for new messages (user side)
// ══════════════════════════════════════════════════════════════════
if ($action === 'poll') {
    requireLoggedInUser();

    $since = (int)($_GET['since'] ?? 0);
    $sess  = $conn->real_escape_string(userChatSessionId($userId));

    $res = $conn->query("
        SELECT id, sender, message, created_at
        FROM chat_messages
        WHERE session_id = '$sess'
          AND id > $since
        ORDER BY id ASC
        LIMIT 50
    ");

    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

    $conn->query("
        UPDATE chat_messages
        SET is_read = 1
        WHERE session_id = '$sess' AND sender = 'admin' AND is_read = 0
    ");

    jsonOut(['ok' => true, 'messages' => $rows]);
}

// ══════════════════════════════════════════════════════════════════
//  ACTION: logged-in user deletes their own chat history
// ══════════════════════════════════════════════════════════════════
if ($action === 'delete_my_chat') {
    requireLoggedInUser();

    $sess = $conn->real_escape_string(userChatSessionId($userId));
    $conn->query("DELETE FROM chat_messages WHERE session_id = '$sess'");

    jsonOut(['ok' => true, 'deleted' => (int)$conn->affected_rows]);
}

// ══════════════════════════════════════════════════════════════════
//  ACTION: admin polls all active sessions + messages
// ══════════════════════════════════════════════════════════════════
if ($action === 'admin_poll') {
    if (!$isAdmin) fail('Unauthorized.');

    $since      = (int)($_GET['since'] ?? 0);
    $filterSess = $conn->real_escape_string($_GET['session_id'] ?? '');

    // All distinct sessions with last message info + unread count
    $sessRes = $conn->query("
        SELECT
            cm.session_id,
            u.full_name,
            u.username,
            u.email,
            MAX(cm.id)         AS last_msg_id,
            MAX(cm.created_at) AS last_at,
            SUM(cm.sender = 'user' AND cm.is_read = 0) AS unread
        FROM chat_messages cm
        LEFT JOIN users u ON u.id = (
            SELECT user_id FROM chat_messages
            WHERE session_id = cm.session_id
              AND sender = 'user'
              AND user_id IS NOT NULL
            ORDER BY id DESC
            LIMIT 1
        )
        GROUP BY cm.session_id
        ORDER BY last_at DESC
        LIMIT 100
    ");

    $sessions = $sessRes ? $sessRes->fetch_all(MYSQLI_ASSOC) : [];

    // New messages across all sessions since last poll
    $newMsgs = [];
    if ($since > 0) {
        $newRes = $conn->query("
            SELECT id, session_id, sender, message, created_at, is_read
            FROM chat_messages
            WHERE id > $since
            ORDER BY id ASC
            LIMIT 200
        ");
        $newMsgs = $newRes ? $newRes->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Full message history for the selected session
    $history = [];
    if ($filterSess !== '') {
        $hRes = $conn->query("
            SELECT id, sender, message, created_at
            FROM chat_messages
            WHERE session_id = '$filterSess'
            ORDER BY id ASC
        ");
        $history = $hRes ? $hRes->fetch_all(MYSQLI_ASSOC) : [];

        // Mark user messages as read when admin opens the thread
        $conn->query("
            UPDATE chat_messages
            SET is_read = 1
            WHERE session_id = '$filterSess' AND sender = 'user'
        ");
    }

    jsonOut([
        'ok'       => true,
        'sessions' => $sessions,
        'new_msgs' => $newMsgs,
        'history'  => $history,
    ]);
}

// ══════════════════════════════════════════════════════════════════
//  ACTION: get total unread count (for admin sidebar badge)
// ══════════════════════════════════════════════════════════════════
if ($action === 'unread_count') {
    if (!$isAdmin) fail('Unauthorized.');
    $r = $conn->query("SELECT COUNT(*) FROM chat_messages WHERE sender='user' AND is_read=0");
    jsonOut(['ok' => true, 'count' => (int)$r->fetch_row()[0]]);
}

// ══════════════════════════════════════════════════════════════════
//  ACTION: admin deletes all live chat messages
// ══════════════════════════════════════════════════════════════════
if ($action === 'admin_delete_all') {
    if (!$isAdmin) fail('Unauthorized.');
    $conn->query('DELETE FROM chat_messages');
    jsonOut(['ok' => true, 'deleted' => (int)$conn->affected_rows]);
}

fail('Unknown action.');