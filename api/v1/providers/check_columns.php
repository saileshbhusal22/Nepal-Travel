<?php
require_once __DIR__ . '/../../config/db.php';
$res = $conn->query("DESCRIBE events");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>