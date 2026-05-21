<?php
session_start();
$_SESSION['user_id'] = 40;
$_POST = [
    'title' => 'Test Event from script',
    'start_date' => '2026-06-01',
    'is_paid' => 0,
    'description' => 'Test',
    'what_to_expect' => 'Test',
];

require_once __DIR__ . '/api/v1/events.php';
?>
