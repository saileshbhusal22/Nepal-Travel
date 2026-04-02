<?php
$conn = new mysqli("localhost", "root", "", "nepal_travel");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>