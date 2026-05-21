<?php
require_once __DIR__ . '/config/db.php';
$res = $conn->query("SELECT id, events_posted FROM user_event_subscriptions WHERE id = 14");
print_r($res->fetch_assoc());
?>
