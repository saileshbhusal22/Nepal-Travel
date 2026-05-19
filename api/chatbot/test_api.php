<?php
$API_KEY = "AIzaSyDOPxXdsJE33-VOLfjxgrOwrNrUi_Ct-g0";
$MODEL = "gemini-1.5-flash";
$systemInstruction = "You are a test assistant.";
$userMessage = "Hello";

$payload = [
    "contents" => [
        [
            "role" => "user",
            "parts" => [
                ["text" => $systemInstruction . "\n\nUser Question: " . $userMessage]
            ]
        ]
    ]
];

// Let's test with gemini-pro which is the most standard legacy endpoint that works everywhere.
$ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$API_KEY}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
