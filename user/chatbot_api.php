<?php
session_start();
header('Cache-Control: no-cache, must-revalidate');

// ── Auth guard ──────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    // For streaming endpoint return JSON error
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode([
        'success'  => false,
        'error'    => 'not_logged_in',
        'redirect' => '/Nepal-Travel/user/login.php'
    ]);
    exit;
}

require_once '../config/openai_config.php';
require_once '../config/db.php';

$userId = (int)$_SESSION['user_id'];

ob_start();

// ── Validate request ────────────────────────────────────────────────────────
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($data['messages'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    ob_end_flush();
    exit;
}

$messages       = $data['messages'];
$language       = $data['language']        ?? 'english';
$conversationId = (int)($data['conversation_id'] ?? 0);

// Validate messages array
if (!is_array($messages) || count($messages) === 0) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid messages format']);
    ob_end_flush();
    exit;
}

// ── Verify conversation ownership ───────────────────────────────────────────
if ($conversationId > 0) {
    $chk = $conn->prepare("SELECT id FROM ai_conversations WHERE id = ? AND user_id = ?");
    $chk->bind_param('ii', $conversationId, $userId);
    $chk->execute();
    if (!$chk->get_result()->fetch_assoc()) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden']);
        ob_end_flush();
        exit;
    }
    $chk->close();
}

// ── Build system prompt ─────────────────────────────────────────────────────
$systemPrompt = SYSTEM_PROMPT;
if ($language !== 'english') {
    $langName = $language === 'nepali' ? 'Nepali' : 'Hindi';
    $systemPrompt .= "\n\n[IMPORTANT: User language is $langName. Respond entirely in $langName unless they ask otherwise.]";
}

// Ensure system prompt is first
if (empty($messages) || $messages[0]['role'] !== 'system') {
    array_unshift($messages, ['role' => 'system', 'content' => $systemPrompt]);
}

// ── Stream to client ────────────────────────────────────────────────────────
try {
    $requestData = [
        'model'             => OPENROUTER_MODEL,
        'messages'          => $messages,
        'temperature'       => OPENROUTER_TEMPERATURE,
        'max_tokens'        => OPENROUTER_MAX_TOKENS,
        'top_p'             => 0.9,
        'frequency_penalty' => 0.5,
        'stream'            => true
    ];

    while (ob_get_level() > 0) ob_end_clean();

    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    // Pass conversation_id back in header so JS can pick it up
    if ($conversationId > 0) {
        header('X-Conversation-Id: ' . $conversationId);
    }

    $ch = curl_init(OPENROUTER_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENROUTER_API_KEY,
        'HTTP-Referer: ' . ($_SERVER['HTTP_REFERER'] ?? 'http://localhost')
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $chunk) {
        echo $chunk;
        flush();
        return strlen($chunk);
    });

    curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo "data: " . json_encode(['error' => 'API Connection Error: ' . $curlError]) . "\n\n";
    }

    exit;

} catch (Exception $e) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } else {
        echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
    }
}
?>