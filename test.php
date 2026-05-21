<?php
require_once __DIR__ . '/config/db.php';
$res = $conn->query("SELECT * FROM event_subscription_plans");
echo "PLANS:\n";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
$res = $conn->query("SELECT * FROM user_event_subscriptions");
echo "\nUSER_SUBS:\n";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
