<?php
require_once __DIR__ . '/config/db.php';
$conn->query("UPDATE user_event_subscriptions SET events_posted = 0 WHERE id = 14");
echo "Reset events_posted to 0.\n";
?>
