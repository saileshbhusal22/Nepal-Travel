<?php
require_once __DIR__ . '/config/db.php';
$res = $conn->query("SELECT * FROM event_subscription_plans");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
