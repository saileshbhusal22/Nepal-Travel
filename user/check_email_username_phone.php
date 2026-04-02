<?php
include 'db.php';

if (isset($_POST['email'])) {
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    echo ($stmt->num_rows > 0) ? "taken" : "available";
    exit;
}

if (isset($_POST['username'])) {
    $username = trim($_POST['username']);

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    echo ($stmt->num_rows > 0) ? "taken" : "available";
    exit;
}

if (isset($_POST['phone'])) {
    $phone = trim($_POST['phone']);

    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->store_result();

    echo ($stmt->num_rows > 0) ? "taken" : "available";
    exit;
}
?>