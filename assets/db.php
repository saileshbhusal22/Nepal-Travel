<?php
$conn = new mysqli("127.0.0.1", "root", "", "nepal_travel");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>