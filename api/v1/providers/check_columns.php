<?php
/**
 
 * Author: Bijay Sapkota
 * Group: L5CG6
 */
require_once __DIR__ . '/../../../config/db.php';
$res = $conn->query("DESCRIBE events");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>