<?php
session_start();

echo "<pre>";
echo "=== Chatbot API Debug ===\n\n";

// Check auth
if (!isset($_SESSION['user_id'])) {
    echo "❌ NOT LOGGED IN\n";
    exit;
}

echo "✅ Logged in as: " . $_SESSION['user_id'] . " (" . $_SESSION['user_name'] . ")\n\n";

// Load config
require_once '../config/openai_config.php';
require_once '../config/db.php';

echo "=== Config Check ===\n";
echo "API URL: " . OPENROUTER_API_URL . "\n";
echo "API Key (first 20 chars): " . substr(OPENROUTER_API_KEY, 0, 20) . "...\n";
echo "Model: " . OPENROUTER_MODEL . "\n\n";

// Test OpenRouter API directly
echo "=== Testing OpenRouter API ===\n";

$testPayload = [
    'model'       => OPENROUTER_MODEL,
    'messages'    => [
        ['role' => 'user', 'content' => 'Hello, respond with one word']
    ],
    'temperature' => 0.7,
    'max_tokens'  => 100,
    'stream'      => false // Non-streaming for easier debugging
];

$ch = curl_init(OPENROUTER_API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . OPENROUTER_API_KEY,
    'HTTP-Referer: http://localhost'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
if ($curlError) {
    echo "❌ Curl Error: " . $curlError . "\n";
} else {
    echo "✅ No curl errors\n";
}

echo "\nResponse (first 500 chars):\n";
echo substr($response, 0, 500) . "\n";

// Try to decode as JSON
$decoded = json_decode($response, true);
if (json_last_error()) {
    echo "\n❌ JSON Decode Error: " . json_last_error_msg() . "\n";
} else {
    echo "\n✅ Valid JSON Response\n";
    if (isset($decoded['error'])) {
        echo "API Error: " . json_encode($decoded['error'], JSON_PRETTY_PRINT) . "\n";
    }
    if (isset($decoded['choices'])) {
        echo "API Success - Got response\n";
    }
}

echo "\n</pre>";
?>
