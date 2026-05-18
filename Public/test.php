<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Step 1: Starting...<br>";
include '../includes/header.php';
echo "Step 2: Header OK<br>";

require_once __DIR__ . '/../config/db.php';
echo "Step 3: DB OK<br>";

include 'includes/deals-data.php';
echo "Step 4: Deals data OK<br>";

include 'includes/map.php';
echo "Step 5: Map OK<br>";
?>