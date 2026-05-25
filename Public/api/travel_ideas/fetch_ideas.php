<?php
/**

 * Author: Ramal Gurung
 * Group: L5CG6
 */
require_once __DIR__ . '/../../../config/db.php';
header('Content-Type: application/json');

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

$sql = "SELECT t.id, t.title, t.slug, COALESCE(p.name, '') AS province, t.province_slug, t.image_path, t.subtitle AS description, t.duration_days, t.nights, t.difficulty, GROUP_CONCAT(DISTINCT et.name ORDER BY et.name SEPARATOR ', ') AS experience_types, t.created_at, u.username AS author
        FROM travel_ideas t
        LEFT JOIN provinces p ON p.id = t.province_id
        LEFT JOIN travel_idea_experiences tie ON tie.idea_id = t.id
        LEFT JOIN experience_types et ON et.id = tie.experience_type_id
        LEFT JOIN users u ON u.id = t.user_id
        GROUP BY t.id
        ORDER BY t.created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
}
$stmt->close();

echo json_encode(['success'=>true,'ideas'=>$rows]);
$conn->close();
?>