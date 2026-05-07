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
        'frequency_penalty' => 0.5
    ];

    $ch = curl_init(OPENROUTER_API_URL);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENROUTER_API_KEY,
        'HTTP-Referer: ' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'http://localhost')
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Handle curl errors
    if ($curlError) {
        throw new Exception('API Connection Error: ' . $curlError);
    }

    // Handle empty response
    if (empty($response)) {
        throw new Exception('Empty API response');
    }

    $result = json_decode($response, true);

    // Handle API errors
    if ($httpCode !== 200) {
        $errorMsg = 'API Error (HTTP ' . $httpCode . ')';
        
        if (isset($result['error'])) {
            if (is_array($result['error'])) {
                $errorMsg = $result['error']['message'] ?? $errorMsg;
            } else {
                $errorMsg = $result['error'];
            }
        }
        
        throw new Exception($errorMsg);
    }

    // Validate response structure
    if (!isset($result['choices'][0]['message']['content'])) {
        throw new Exception('Invalid API response format');
    }

    $reply = trim($result['choices'][0]['message']['content']);

    // Return successful response
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => $reply,
        'conversation_id' => $conversationId,
        'language' => $language,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}