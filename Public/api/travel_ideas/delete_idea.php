<?php
/**

 * Author: Ramal Gurung
 * Group: L5CG6
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$chk = $conn->prepare("SELECT id FROM travel_ideas WHERE id = ? AND user_id = ?");
$chk->bind_param("ii", $id, $user_id);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Not authorized to delete this idea']);
    exit;
}
$chk->close();

$stmt = $conn->prepare("DELETE FROM travel_ideas WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete']);
}
$stmt->close();
?>
