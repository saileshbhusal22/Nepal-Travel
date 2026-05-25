<?php
/**

 * Author: Ramal Gurung
 * Group: L5CG6
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM travel_ideas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$ideaRes = $stmt->get_result();
if ($ideaRes->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Idea not found']);
    exit;
}
$idea = $ideaRes->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT content, highlights, logistics FROM travel_idea_details WHERE idea_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$detailsRes = $stmt->get_result();
$details = $detailsRes->fetch_assoc() ?: [];
$stmt->close();

$stmt = $conn->prepare("SELECT et.name FROM travel_idea_experiences tie JOIN experience_types et ON tie.experience_type_id = et.id WHERE tie.idea_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$expRes = $stmt->get_result();
$experiences = [];
while ($row = $expRes->fetch_assoc()) {
    $experiences[] = $row['name'];
}
$stmt->close();

$stmt = $conn->prepare("SELECT * FROM itineraries WHERE idea_id = ? ORDER BY day_order ASC");
$stmt->bind_param("i", $id);
$stmt->execute();
$itineraryRes = $stmt->get_result();
$itineraries = [];
while ($row = $itineraryRes->fetch_assoc()) {
    $itineraries[] = $row;
}
$stmt->close();

echo json_encode([
    'success' => true,
    'idea' => $idea,
    'details' => $details,
    'experiences' => $experiences,
    'itineraries' => $itineraries
]);
?>
