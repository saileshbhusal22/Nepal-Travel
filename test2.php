<?php
require_once __DIR__ . '/config/db.php';
$res = $conn->query("SELECT * FROM users WHERE id = 40");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
