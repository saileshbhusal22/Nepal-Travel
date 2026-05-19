<?php
$conn = new mysqli('localhost', 'root', '', 'nepal_travel');
if ($conn->connect_error) {
    die('DBERR: ' . $conn->connect_error . "\n");
}
$res = $conn->query('SELECT COUNT(*) AS total, SUM(user_id IS NOT NULL) AS owned, SUM(user_id>0) AS owned_gt0 FROM travel_ideas');
if (!$res) {
    die('QUERYERR: ' . $conn->error . "\n");
}
print_r($res->fetch_assoc());
