<?php
<<<<<<< HEAD
require_once __DIR__ . '/../config/db.php';
=======
session_start();
>>>>>>> aac85d9bbbc6bf1c950d58f48bc1736fc6c35d1e

require_once __DIR__ . '/../config/db.php';
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE email_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $update = $conn->prepare("UPDATE users SET email_verified = 1, email_token = NULL WHERE id = ?");
        $update->bind_param("i", $user['id']);
        $update->execute();

        // ✅ Create session for the user
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['message'] = "Email verified successfully! You are now logged in.";
        $_SESSION['message_type'] = "success";

        // ✅ Redirect to index.php after successful verification
        header("Location: ../Public/index.php");
        exit;
    } else {
        $_SESSION['message'] = "Invalid or expired token.";
        $_SESSION['message_type'] = "error";
        header("Location: ../Public/index.php");
        exit;
    }
}
?>