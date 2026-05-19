<?php
$conn = new mysqli('localhost', 'root', '', 'nepal_travel');
if ($conn->connect_error) {
    die('DBERR: ' . $conn->connect_error . "\n");
}
$res = $conn->query('SELECT id, user_id, title FROM travel_ideas WHERE user_id > 0 ORDER BY id DESC LIMIT 5');
if (!$res) {
    die('QUERYERR: ' . $conn->error . "\n");
}
print_r($res->fetch_all(MYSQLI_ASSOC));
