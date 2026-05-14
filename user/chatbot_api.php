<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
require_once '../config/openai_config.php';

// Ensure clean output
ob_start();

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($data['messages'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request. Messages required.'
    ]);
    ob_end_flush();
    exit;
}

$messages = $data['messages'];
$language = $data['language'] ?? 'english';
$conversationId = $data['conversation_id'] ?? null;

// Validate messages array
if (!is_array($messages) || count($messages) === 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid messages format'
    ]);
    ob_end_flush();
    exit;
}

// Add language hint to system prompt
$systemPrompt = SYSTEM_PROMPT;
if ($language !== 'english') {
    $languageName = $language === 'nepali' ? 'Nepali' : 'Hindi';
    $systemPrompt .= "\n\n[IMPORTANT: User language is $languageName. Respond entirely in $languageName unless they ask otherwise.]";
}

// Ensure system prompt is first
if (empty($messages) || $messages[0]['role'] !== 'system') {
    array_unshift($messages, [
        'role' => 'system',
        'content' => $systemPrompt
    ]);
}

try {
    // Prepare API request
    $requestData = [
        'model' => OPENROUTER_MODEL,
        'messages' => $messages,
        'temperature' => OPENROUTER_TEMPERATURE,
        'max_tokens' => OPENROUTER_MAX_TOKENS,
        'top_p' => 0.9,
        'frequency_penalty' => 0.5,
        'stream' => true // Enable streaming
    ];

    // Clear output buffer for streaming
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // Set streaming headers
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');

    $ch = curl_init(OPENROUTER_API_URL);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // Important: stream directly to client
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENROUTER_API_KEY,
        'HTTP-Referer: ' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'http://localhost')
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));

    // Handle incoming stream chunks and push to client immediately
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) {
        echo $data;
        flush(); // Push the data to the browser
        return strlen($data);
    });

    curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Handle curl errors mid-stream
    if ($curlError) {
        echo "data: " . json_encode(['error' => 'API Connection Error: ' . $curlError]) . "\n\n";
    }

    exit;

} catch (Exception $e) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    } else {
        echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
    }
}