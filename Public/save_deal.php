<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['saved_deals'])) {
    $_SESSION['saved_deals'] = [];
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $_GET['action'] ?? 'add';

if ($id > 0) {

    if ($action === 'add') {
        // avoid duplicate
        if (!in_array($id, $_SESSION['saved_deals'])) {
            $_SESSION['saved_deals'][] = $id;
            $_SESSION['message'] = "Deal added to wishlist ❤️";
        } else {
            $_SESSION['message'] = "Already in wishlist!";
        }
    }

    if ($action === 'remove') {
        $_SESSION['saved_deals'] = array_diff($_SESSION['saved_deals'], [$id]);
        $_SESSION['message'] = "Removed from wishlist ❌";
    }
}

// go back
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;