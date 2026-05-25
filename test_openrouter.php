<?php
require_once __DIR__ . '/config/openai_config.php';
$ch = curl_init(OPENROUTER_API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$requestData = [
    'model' => OPENROUTER_MODEL,
    'messages' => [['role' => 'user', 'content' => 'Hello']],
    'max_tokens' => OPENROUTER_MAX_TOKENS
];
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . OPENROUTER_API_KEY
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
$response = curl_exec($ch);
echo "Response: " . $response . "\n";
curl_close($ch);
?>
