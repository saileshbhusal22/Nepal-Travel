<?php
/**

 * Author: Ramal Gurung
 * Group: L5CG6
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "nepal_travel");

if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$comment_text = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($post_id <= 0 || empty($comment_text)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid input'
    ]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO comments (user_id, post_id, comment_text) 
    VALUES (?, ?, ?)
");

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Prepare failed: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param("iis", $user_id, $post_id, $comment_text);

if ($stmt->execute()) {

    // Fetch username safely without get_result()
    $u_stmt = $conn->prepare("
        SELECT COALESCE(NULLIF(full_name, ''), NULLIF(username, ''), 'Anonymous') AS username
        FROM users
        WHERE id = ?
    ");

    if (!$u_stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'User query failed: ' . $conn->error
        ]);
        exit;
    }

    $u_stmt->bind_param("i", $user_id);
    $u_stmt->execute();

    $username = 'Anonymous'; // Default
    $u_stmt->bind_result($username);
    $u_stmt->fetch();
    $u_stmt->close();

    echo json_encode([
        'success' => true,
        'comment' => [
            'id' => $conn->insert_id,
            'user_id' => $user_id,
            'username' => $username,
            'comment_text' => htmlspecialchars($comment_text),
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);

} else {

    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
