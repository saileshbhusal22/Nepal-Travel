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
if (in_array($action, ['admin_poll', 'admin_reply', 'unread_count']) && isset($_COOKIE['nepal_admin_session'])) {
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


// ── Resolve chat session ID ───────────────────────────────────────
// KEY FIX: If the user is logged in, derive a stable session ID from
// their user_id so it survives logout → login cycles.
// Guests get a persistent cookie-based ID so their history also survives
// a page reload until they log in (at which point it merges).
function resolveChatSession($conn, $userId) {
    if ($userId) {
        // Logged-in users always use a deterministic session key
        return 'user_' . $userId;
    }

    // Guest: use a long-lived cookie (not the PHP session, which dies on logout)
    $cookieName = 'nt_chat_sess';
    if (!empty($_COOKIE[$cookieName])) {
        $val = preg_replace('/[^a-f0-9]/', '', $_COOKIE[$cookieName]);
        if (strlen($val) === 32) {
            return 'guest_' . $val;
        }
    }
    // Create a new guest cookie valid for 90 days
    $token = bin2hex(random_bytes(16));
    setcookie($cookieName, $token, [
        'expires'  => time() + 90 * 86400,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    return 'guest_' . $token;
}

$chatSession = resolveChatSession($conn, $userId);

// ── When a user just logged in, merge any prior guest messages ────
// If the user had a guest cookie before logging in, move those messages
// over to their user_ session so history is not lost.
if ($userId && !empty($_COOKIE['nt_chat_sess'])) {
    $guestToken = preg_replace('/[^a-f0-9]/', '', $_COOKIE['nt_chat_sess']);
    if (strlen($guestToken) === 32) {
        $guestSess = $conn->real_escape_string('guest_' . $guestToken);
        $userSess  = $conn->real_escape_string($chatSession);
        // Only merge if the guest session actually has messages
        $chk = $conn->query("SELECT COUNT(*) FROM chat_messages WHERE session_id='$guestSess'");
        if ($chk && (int)$chk->fetch_row()[0] > 0) {
            // Re-assign guest messages to user session and attach user_id
            $conn->query("
                UPDATE chat_messages
                SET session_id = '$userSess',
                    user_id    = IF(sender = 'user', $userId, user_id)
                WHERE session_id = '$guestSess'
            ");
        }
    }
}

// ══════════════════════════════════════════════════════════════════
//  ACTION: user sends a message
// ══════════════════════════════════════════════════════════════════
if ($action === 'send') {
    $raw = trim($_POST['message'] ?? '');
    if ($raw === '') fail('Empty message.');

    $msg     = $conn->real_escape_string(htmlspecialchars($raw, ENT_QUOTES, 'UTF-8'));
    $uid_sql = $userId ? $userId : 'NULL';
    $sess    = $conn->real_escape_string($chatSession);

    $conn->query("
        INSERT INTO chat_messages (session_id, user_id, sender, message)
        VALUES ('$sess', $uid_sql, 'user', '$msg')
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
    $since = (int)($_GET['since'] ?? 0);
    $sess  = $conn->real_escape_string($chatSession);

    $res = $conn->query("
        SELECT id, sender, message, created_at
        FROM chat_messages
        WHERE session_id = '$sess'
          AND id > $since
        ORDER BY id ASC
        LIMIT 50
    ");

    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

    // Mark admin messages as read when user polls
    $conn->query("
        UPDATE chat_messages
        SET is_read = 1
        WHERE session_id = '$sess' AND sender = 'admin' AND is_read = 0
    ");

    jsonOut(['ok' => true, 'messages' => $rows]);
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

fail('Unknown action.');