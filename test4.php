<?php
require_once __DIR__ . '/config/db.php';
$active_sub_id = 14;
$conn->query("UPDATE user_event_subscriptions SET events_posted = events_posted + 1 WHERE id = $active_sub_id");
echo "Affected rows: " . $conn->affected_rows . "\n";
?>
