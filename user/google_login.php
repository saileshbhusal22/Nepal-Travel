<?php


session_start();

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

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

$email    = $payload['email'] ?? '';
$fullName = $payload['name']  ?? 'Google User';
$googleId = $payload['sub']   ?? '';

if ($email === '') {
    http_response_code(400);
    exit("No email received");
}

/* Check if user already exists */
$stmt = $conn->prepare("SELECT id, full_name, email, has_password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    $_SESSION['user_id']      = $user['id'];
    $_SESSION['user_name']    = $user['full_name'];
    $_SESSION['user_email']   = $user['email'];
    $_SESSION['has_password'] = (bool) $user['has_password'];

    // Update google_id if not already saved
    $upd = $conn->prepare("UPDATE users SET google_id = ? WHERE id = ? AND google_id IS NULL");
    $upd->bind_param("si", $googleId, $user['id']);
    $upd->execute();

    echo $user['has_password'] ? "ok" : "set_password";
    exit;
}

/* Create new Google user — no real password */
$username = 'google_' . substr(md5($googleId), 0, 8);
$password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

$insert = $conn->prepare("
    INSERT INTO users (full_name, username, email, phone, password, email_verified, google_id, has_password)
    VALUES (?, ?, ?, '', ?, 1, ?, 0)
");
$insert->bind_param("sssss", $fullName, $username, $email, $password, $googleId);

if ($insert->execute()) {
    $_SESSION['user_id']      = $insert->insert_id;
    $_SESSION['user_name']    = $fullName;
    $_SESSION['user_email']   = $email;
    $_SESSION['has_password'] = false;

    echo "set_password"; // Tell frontend to show the modal
} else {
    http_response_code(500);
    echo "Database error";
}
?>