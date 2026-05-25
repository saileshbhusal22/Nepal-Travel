<?php
/**
 * Author: Ishwor Dhital
 * Student ID: 2462304
 * Group: L5CG6
 */
$conn = new mysqli("localhost", "root", "", "nepal_travel");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>