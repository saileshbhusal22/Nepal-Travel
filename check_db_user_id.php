<?php
require_once __DIR__ . '/config/db.php';

$res = $conn->query("SHOW COLUMNS FROM travel_ideas LIKE 'user_id'");
if ($res->num_rows == 0) {
    echo "user_id column is missing. Attempting to add...\n";
    $alt = $conn->query("ALTER TABLE travel_ideas ADD COLUMN user_id INT UNSIGNED DEFAULT NULL AFTER id");
    if ($alt) echo "Column added successfully.\n";
    else echo "Failed to add column: " . $conn->error . "\n";
} else {
    echo "user_id column exists.\n";
}
