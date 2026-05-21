<?php
require_once __DIR__ . '/config/db.php';
$res = $conn->query("SELECT id, title, user_id, subscription_id FROM events ORDER BY id DESC LIMIT 10");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
