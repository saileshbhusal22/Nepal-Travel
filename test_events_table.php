<?php
require_once __DIR__ . '/config/db.php';
$res = $conn->query("DESCRIBE events");
$cols = [];
while ($row = $res->fetch_assoc()) $cols[] = $row['Field'] . ' ' . $row['Type'];
print_r($cols);

$events = $conn->query("SELECT id, user_id, title, subscription_id FROM events WHERE user_id = 40");
print_r($events->fetch_all(MYSQLI_ASSOC));
?>
