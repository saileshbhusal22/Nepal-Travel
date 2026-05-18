<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// ── Auth guard ──────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'not_logged_in', 'redirect' => '/Nepal-Travel/user/login.php']);
    exit;
}

require_once '../config/db.php';
$userId = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// ── Route ───────────────────────────────────────────────────────────────────
switch ($action) {

    // List all conversations for this user (newest first)
    case 'list_conversations':
        $stmt = $conn->prepare("
            SELECT c.id, c.title, c.language, c.created_at, c.updated_at,
                   (SELECT content FROM ai_messages
                    WHERE conversation_id = c.id AND role != 'system'
                    ORDER BY created_at DESC LIMIT 1) AS last_message,
                   (SELECT COUNT(*) FROM ai_messages WHERE conversation_id = c.id AND role = 'user') AS msg_count
            FROM ai_conversations c
            WHERE c.user_id = ?
            ORDER BY c.updated_at DESC
            LIMIT 50
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success' => true, 'conversations' => $rows]);
        break;

    // Get all messages for a conversation
    case 'get_messages':
        $convId = (int)($_GET['conversation_id'] ?? 0);
        if (!$convId) { echo json_encode(['success' => false, 'error' => 'Missing conversation_id']); exit; }

        // Verify ownership
        $chk = $conn->prepare("SELECT id, title, language FROM ai_conversations WHERE id = ? AND user_id = ?");
        $chk->bind_param('ii', $convId, $userId);
        $chk->execute();
        $conv = $chk->get_result()->fetch_assoc();
        $chk->close();
        if (!$conv) { echo json_encode(['success' => false, 'error' => 'Not found']); exit; }

        $stmt = $conn->prepare("
            SELECT id, role, content, created_at
            FROM ai_messages
            WHERE conversation_id = ? AND role != 'system'
            ORDER BY created_at ASC
        ");
        $stmt->bind_param('i', $convId);
        $stmt->execute();
        $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success' => true, 'conversation' => $conv, 'messages' => $messages]);
        break;

    // Create a new conversation
    case 'create_conversation':
        $data     = json_decode(file_get_contents('php://input'), true);
        $title    = trim($data['title'] ?? 'New Chat');
        $language = trim($data['language'] ?? 'english');
        if (strlen($title) > 255) $title = substr($title, 0, 252) . '…';

        $stmt = $conn->prepare("INSERT INTO ai_conversations (user_id, title, language) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $userId, $title, $language);
        $stmt->execute();
        $convId = $stmt->insert_id;
        $stmt->close();
        echo json_encode(['success' => true, 'conversation_id' => $convId]);
        break;

    // Save a single message to DB
    case 'save_message':
        $data   = json_decode(file_get_contents('php://input'), true);
        $convId = (int)($data['conversation_id'] ?? 0);
        $role   = $data['role'] ?? '';
        $content= trim($data['content'] ?? '');

        if (!$convId || !in_array($role, ['user', 'assistant']) || $content === '') {
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']); exit;
        }

        // Verify ownership
        $chk = $conn->prepare("SELECT id FROM ai_conversations WHERE id = ? AND user_id = ?");
        $chk->bind_param('ii', $convId, $userId);
        $chk->execute();
        if (!$chk->get_result()->fetch_assoc()) {
            $chk->close();
            echo json_encode(['success' => false, 'error' => 'Not found']); exit;
        }
        $chk->close();

        // Insert message
        $stmt = $conn->prepare("INSERT INTO ai_messages (conversation_id, user_id, role, content) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iiss', $convId, $userId, $role, $content);
        $stmt->execute();
        $msgId = $stmt->insert_id;
        $stmt->close();

        // Auto-generate title from first user message
        if ($role === 'user') {
            $chkTitle = $conn->prepare("SELECT title FROM ai_conversations WHERE id = ?");
            $chkTitle->bind_param('i', $convId);
            $chkTitle->execute();
            $curTitle = $chkTitle->get_result()->fetch_assoc()['title'] ?? '';
            $chkTitle->close();

            if ($curTitle === 'New Chat' || $curTitle === '') {
                $autoTitle = mb_substr($content, 0, 60);
                if (mb_strlen($content) > 60) $autoTitle .= '…';
                $upd = $conn->prepare("UPDATE ai_conversations SET title = ? WHERE id = ?");
                $upd->bind_param('si', $autoTitle, $convId);
                $upd->execute();
                $upd->close();
            }

            // Touch updated_at
            $touch = $conn->prepare("UPDATE ai_conversations SET updated_at = NOW() WHERE id = ?");
            $touch->bind_param('i', $convId);
            $touch->execute();
            $touch->close();
        }

        echo json_encode(['success' => true, 'message_id' => $msgId]);
        break;

    // Update conversation title
    case 'rename_conversation':
        $data   = json_decode(file_get_contents('php://input'), true);
        $convId = (int)($data['conversation_id'] ?? 0);
        $title  = trim($data['title'] ?? '');
        if (!$convId || !$title) { echo json_encode(['success' => false, 'error' => 'Invalid']); exit; }
        if (strlen($title) > 255) $title = substr($title, 0, 252) . '…';
        $stmt = $conn->prepare("UPDATE ai_conversations SET title = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param('sii', $title, $convId, $userId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        break;

    // Delete a conversation and all its messages
    case 'delete_conversation':
        $data   = json_decode(file_get_contents('php://input'), true);
        $convId = (int)($data['conversation_id'] ?? 0);
        if (!$convId) { echo json_encode(['success' => false, 'error' => 'Invalid']); exit; }
        $stmt = $conn->prepare("DELETE FROM ai_conversations WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $convId, $userId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        break;

    // Get full message history for AI context (includes system role, all messages)
    case 'get_context':
        $convId = (int)($_GET['conversation_id'] ?? 0);
        if (!$convId) { echo json_encode(['success' => false, 'error' => 'Missing conversation_id']); exit; }
        $chk = $conn->prepare("SELECT id FROM ai_conversations WHERE id = ? AND user_id = ?");
        $chk->bind_param('ii', $convId, $userId);
        $chk->execute();
        if (!$chk->get_result()->fetch_assoc()) {
            $chk->close();
            echo json_encode(['success' => false, 'error' => 'Not found']); exit;
        }
        $chk->close();
        // Last 40 messages for context window
        $stmt = $conn->prepare("
            SELECT role, content FROM ai_messages
            WHERE conversation_id = ?
            ORDER BY created_at ASC
            LIMIT 40
        ");
        $stmt->bind_param('i', $convId);
        $stmt->execute();
        $msgs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success' => true, 'messages' => $msgs]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
?>
