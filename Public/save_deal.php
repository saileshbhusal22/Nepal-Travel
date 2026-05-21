<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/saved_helpers.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $_GET['action'] ?? 'add';
$redirect = $_SERVER['HTTP_REFERER'] ?? '/Nepal-Travel/Public/saved.php';

if ($id > 0) {
    if ($action === 'add') {
        if (saveDealId($conn, $id)) {
            $_SESSION['message'] = isDealSaved($id)
                ? 'Deal saved to your collection.'
                : 'Deal added to saved.';
        } else {
            $_SESSION['message'] = 'Could not save this deal.';
        }
    } elseif ($action === 'remove') {
        removeDealId($conn, $id);
        $_SESSION['message'] = 'Removed from saved.';
    }
}

header('Location: ' . $redirect);
exit;
