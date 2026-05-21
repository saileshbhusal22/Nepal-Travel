<?php
require_once __DIR__ . '/config/db.php';
$res = $conn->query("SELECT id, title, subscription_id, created_at FROM events WHERE user_id = 40");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
