<?php
session_start();

require __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../db.php';

$client = new Google_Client([
    'client_id' => '1045079519630-reec2mcusabp0hg13bufjrmnpvm2a0jb.apps.googleusercontent.com'
]);

if (!isset($_POST['id_token'])) {
    http_response_code(400);
    exit("Missing token");
}

$idToken = $_POST['id_token'];
$payload = $client->verifyIdToken($idToken);

if (!$payload) {
    http_response_code(401);
    exit("Invalid token");
}

$email = $payload['email'] ?? '';
$fullName = $payload['name'] ?? 'Google User';
$googleId = $payload['sub'] ?? '';

if ($email === '') {
    http_response_code(400);
    exit("No email received");
}

/* Check if user already exists */
$stmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    echo "ok";
    exit;
}

/* Create new user */
$username = 'google_' . substr(md5($googleId), 0, 8);
$phone = '';
$password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

$insert = $conn->prepare("INSERT INTO users (full_name, username, email, phone, password, email_verified) VALUES (?, ?, ?, ?, ?, 1)");
$insert->bind_param("sssss", $fullName, $username, $email, $phone, $password);

if ($insert->execute()) {
    $_SESSION['user_id'] = $insert->insert_id;
    $_SESSION['user_name'] = $fullName;
    $_SESSION['user_email'] = $email;
    echo "ok";
}
else {
    http_response_code(500);
    echo "Database error";
}
?>