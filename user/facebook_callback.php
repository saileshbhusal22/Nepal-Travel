<?php
session_start();
include __DIR__ . '/../db.php';

if (!isset($_POST['access_token'])) {
    http_response_code(400);
    exit("Missing token");
}

$accessToken = $_POST['access_token'];

$url = "https://graph.facebook.com/me?fields=id,name,email&access_token=" . urlencode($accessToken);
$response = file_get_contents($url);

if ($response === false) {
    http_response_code(401);
    exit("Facebook request failed");
}

$data = json_decode($response, true);

if (!isset($data['id'])) {
    http_response_code(401);
    exit("Invalid Facebook login");
}

$facebookId = $data['id'];
$fullName = $data['name'] ?? 'Facebook User';
$email = $data['email'] ?? '';

if ($email === '') {
    $email = 'fb_' . $facebookId . '@noemail.com';
}

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

$username = 'fb_' . substr(md5($facebookId), 0, 8);
$phone = '';
$password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

$insert = $conn->prepare("INSERT INTO users (full_name, username, email, phone, password, email_verified) VALUES (?, ?, ?, ?, ?, 1)");
$insert->bind_param("sssss", $fullName, $username, $email, $phone, $password);

if ($insert->execute()) {
    $_SESSION['user_id'] = $insert->insert_id;
    $_SESSION['user_name'] = $fullName;
    $_SESSION['user_email'] = $email;
    echo "ok";
} else {
    http_response_code(500);
    echo "Database error";
}
?>