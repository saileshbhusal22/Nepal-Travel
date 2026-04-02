<?php
include 'db.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE email_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $update = $conn->prepare("UPDATE users SET email_verified = 1, email_token = NULL WHERE id = ?");
        $update->bind_param("i", $user['id']);
        $update->execute();

        echo "Email verified successfully!";
    } else {
        echo "Invalid or expired token.";
    }
}
?>