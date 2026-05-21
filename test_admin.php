<?php
require_once __DIR__ . '/config/db.php';
$res = $conn->query("SELECT * FROM users WHERE id = 40");
print_r($res->fetch_assoc());
?>
