<?php
require_once __DIR__ . '/config/db.php';
$plans = $conn->query("SELECT * FROM event_subscription_plans")->fetch_all(MYSQLI_ASSOC);
echo "Plans:\n";
print_r($plans);

$user_subs = $conn->query("SELECT * FROM user_event_subscriptions WHERE user_id = 40")->fetch_all(MYSQLI_ASSOC);
echo "User 40 subs:\n";
print_r($user_subs);
?>
