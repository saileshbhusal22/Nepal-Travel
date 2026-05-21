<?php

  session_start();


if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php'); exit;
}
require_once '../config/db.php';
require_once '../includes/saved_helpers.php';

// ── AJAX: Cancel booking (called via fetch, returns JSON) ─────────────────
if (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['booking_action']) && $_POST['booking_action'] === 'cancel'
) {
    header('Content-Type: application/json');
    $bid = (int)($_POST['booking_id'] ?? 0);

    $chk = $conn->prepare("SELECT id, status, payment_method FROM bookings WHERE id = ? AND user_id = ?");
    $chk->bind_param("ii", $bid, $_SESSION['user_id']);
    $chk->execute();
    $bk = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$bk) {
        echo json_encode(['success' => false, 'message' => 'Invalid booking.']);
        exit;
    }
    if ($bk['status'] === 'cancelled') {
        echo json_encode(['success' => false, 'message' => 'Already cancelled.']);
        exit;
    }
    if ($bk['payment_method'] === 'khalti') {
        echo json_encode(['success' => false, 'message' => 'Khalti-paid bookings cannot be self-cancelled. Please contact support.']);
        exit;
    }

    $c = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
    $c->bind_param("i", $bid);
    if ($c->execute()) {
        // Return new total booking count AND new confirmed (paid) count
        $stmtTotal = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
        $stmtTotal->bind_param("i", $_SESSION['user_id']);
        $stmtTotal->execute();
        $stmtTotal->bind_result($newTotal);
        $stmtTotal->fetch();
        $stmtTotal->close();

        $stmtPaid = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND payment_method = 'khalti' AND status = 'confirmed'");
        $stmtPaid->bind_param("i", $_SESSION['user_id']);
        $stmtPaid->execute();
        $stmtPaid->bind_result($newPaid);
        $stmtPaid->fetch();
        $stmtPaid->close();

        echo json_encode([
            'success'       => true,
            'message'       => 'Booking cancelled.',
            'newTotal'      => (int)$newTotal,
            'newPaidTrips'  => (int)$newPaid,
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    $c->close();
    exit;
}
// ─────────────────────────────────────────────────────────────────────────

$stmt = $conn->prepare("SELECT id, full_name, username, email, profile_image, bio, location, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) { session_destroy(); header('Location: /Nepal-Travel/user/login.php'); exit; }

$_SESSION['user_name']     = $user['full_name'];
$_SESSION['profile_image'] = $user['profile_image'];

$profileImageUrl = null;
if (!empty($user['profile_image']) && $user['profile_image'] !== 'default.png') {
    $absPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/Nepal-Travel/' . ltrim($user['profile_image'], '/');
    if (file_exists($absPath))
        $profileImageUrl = '/Nepal-Travel/' . ltrim($user['profile_image'], '/') . '?t=' . time();
}

$userName    = htmlspecialchars($user['full_name']);
$userEmail   = htmlspecialchars($user['email']);
$userUsername= htmlspecialchars($user['username']);
$initials    = strtoupper(substr($user['full_name'], 0, 2));
$memberSince = date('F Y', strtotime($user['created_at']));
$activeTab   = $_GET['tab'] ?? 'overview';


$stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($bookingCount);
$stmt->fetch();
$stmt->close();

$stmtPaid = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND payment_method = 'khalti' AND status = 'confirmed'");
$stmtPaid->bind_param("i", $_SESSION['user_id']);
$stmtPaid->execute();
$stmtPaid->bind_result($paidTripsCount);
$stmtPaid->fetch();
$stmtPaid->close();

$savedCount = getTotalSavedCount($conn);

// ── SUBSCRIPTIONS FETCH ────────────────────────────────────────────────
if ($activeTab === 'subscriptions') {
    $stmt = $conn->prepare("SELECT ues.*, esp.display_name AS name FROM user_event_subscriptions ues JOIN event_subscription_plans esp ON ues.plan_id = esp.id WHERE user_id = ? ORDER BY ues.created_at DESC");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $event_subscriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare("SELECT us.*, sp.display_name AS name FROM user_subscriptions us JOIN subscription_plans sp ON us.plan_id = sp.id WHERE user_id = ? ORDER BY us.created_at DESC");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $deal_subscriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
// ─────────────────────────────────────────────────────────────────────────


// ── SETTINGS: name / password ─────────────────────────────────────────────
$settings_message = ''; $settings_msg_type = '';

if (isset($_GET['delete_error'])) {
    $settings_message  = urldecode($_GET['delete_error']);
    $settings_msg_type = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $activeTab === 'settings') {
    if (isset($_POST['update_name'])) {
        $new_name = trim($_POST['full_name'] ?? '');
        if (empty($new_name)) { $settings_message = 'Full name cannot be empty.'; $settings_msg_type = 'error'; }
        else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE id = ?");
            $stmt->bind_param("si", $new_name, $_SESSION['user_id']);
            if ($stmt->execute()) { $_SESSION['user_name'] = $new_name; $userName = htmlspecialchars($new_name); $settings_message = 'Name updated!'; $settings_msg_type = 'success'; }
            else { $settings_message = 'Database error.'; $settings_msg_type = 'error'; }
            $stmt->close();
        }
    }
    if (isset($_POST['change_password'])) {
        $cp = $_POST['current_password'] ?? ''; $np = $_POST['new_password'] ?? ''; $cnp = $_POST['confirm_password'] ?? '';
        if (empty($cp)||empty($np)||empty($cnp)) { $settings_message='All fields required.'; $settings_msg_type='error'; }
        elseif ($np !== $cnp) { $settings_message='Passwords do not match.'; $settings_msg_type='error'; }
        elseif (strlen($np)<6) { $settings_message='Min 6 characters.'; $settings_msg_type='error'; }
        else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']); $stmt->execute(); $stmt->bind_result($hp); $stmt->fetch(); $stmt->close();
            if (!password_verify($cp, $hp)) { $settings_message='Current password incorrect.'; $settings_msg_type='error'; }
            else {
                $nh = password_hash($np, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $nh, $_SESSION['user_id']);
                if ($stmt->execute()) { $settings_message='Password changed!'; $settings_msg_type='success'; }
                else { $settings_message='Database error.'; $settings_msg_type='error'; }
                $stmt->close();
            }
        }
    }
}

// ── BOOKING ACTIONS (non-cancel, non-AJAX) ────────────────────────────────
$booking_action_message = ''; $booking_action_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_action']) && $_POST['booking_action'] === 'update_guests') {
    $bid = (int)($_POST['booking_id'] ?? 0);

    $chk = $conn->prepare("SELECT id, status, payment_method FROM bookings WHERE id = ? AND user_id = ?");
    $chk->bind_param("ii", $bid, $_SESSION['user_id']); $chk->execute();
    $bk = $chk->get_result()->fetch_assoc(); $chk->close();

    if (!$bk) {
        $booking_action_message = 'Invalid booking.'; $booking_action_type = 'error';
    } else {
        $ng = (int)($_POST['guests'] ?? 0);
        if ($ng < 1 || $ng > 50) {
            $booking_action_message = 'Guests must be between 1 and 50.'; $booking_action_type = 'error';
        } elseif ($bk['status'] === 'cancelled') {
            $booking_action_message = 'Booking is cancelled.'; $booking_action_type = 'error';
        } elseif ($bk['payment_method'] === 'khalti') {
            $booking_action_message = 'Khalti-paid bookings cannot be modified. Please contact support.'; $booking_action_type = 'error';
        } else {
            $u = $conn->prepare("UPDATE bookings SET guests = ? WHERE id = ?");
            $u->bind_param("ii", $ng, $bid);
            if ($u->execute()) { $booking_action_message = 'Guests updated!'; $booking_action_type = 'success'; }
            else { $booking_action_message = 'Database error.'; $booking_action_type = 'error'; }
            $u->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Nepal Journey — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Outfit:wght@300;400;500;600&family=Space+Mono&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --stone:#2B2620;
  --stone2:#3D3530;
  --bark:#5C4A3A;
  --soil:#7A5C44;
  --sand:#C4A882;
  --sand2:#D4BC98;
  --mist:#EDE8E0;
  --fog:#F7F4EF;
  --snow:#FDFCFA;
  --forest:#2D4A2D;
  --fern:#4A7040;
  --moss:#7A9B6A;
  --sage:#B4C9A8;
  --ember:#C4622A;
  --ember2:#E8956A;
  --flag-r:#C0392B;
  --khalti:#5C2D91;
  --khalti-light:#EDE0FF;
  --ff-serif:'Libre Baskerville',Georgia,serif;
  --ff-body:'Outfit',sans-serif;
  --ff-mono:'Space Mono',monospace;
}
html,body{min-height:100%;font-family:var(--ff-body);background:var(--fog);color:var(--stone);-webkit-font-smoothing:antialiased}
a{text-decoration:none;color:inherit}
input,button{font-family:var(--ff-body)}

/* TOPBAR */
.topbar{
  background:var(--stone);
  height:62px;display:flex;align-items:center;
  padding:0 52px;justify-content:space-between;
  position:sticky;top:0;z-index:200;
}
.tb-logo{font-family:var(--ff-serif);font-size:20px;font-weight:700;color:var(--snow);display:flex;align-items:center;gap:14px;}
.tb-logo em{font-style:italic;color:var(--sand)}
.nepal-flag{display:flex;flex-direction:column;gap:1px;flex-shrink:0;}
.flag-top{width:0;height:0;border-left:11px solid transparent;border-right:11px solid transparent;border-bottom:14px solid var(--flag-r);}
.flag-bot{width:0;height:0;border-left:11px solid transparent;border-right:11px solid transparent;border-bottom:10px solid #2563A8;}
.tb-right{display:flex;align-items:center;gap:20px}
.tb-back{font-size:12px;color:rgba(255,255,255,0.35);letter-spacing:0.5px;font-weight:400;transition:color 0.2s;}
.tb-back:hover{color:var(--sand)}
.tb-user-pill{display:flex;align-items:center;gap:10px;background:rgba(255,255,255,0.06);border:1px solid rgba(196,168,130,0.2);border-radius:100px;padding:5px 14px 5px 5px;cursor:pointer;transition:border-color 0.2s;}
.tb-user-pill:hover{border-color:rgba(196,168,130,0.5)}
.tb-avatar{width:32px;height:32px;border-radius:50%;background:var(--bark);overflow:hidden;display:flex;align-items:center;justify-content:center;flex-shrink:0;position:relative;}
.tb-avatar img{width:100%;height:100%;object-fit:cover}
.tb-avatar-init{font-size:12px;font-weight:700;color:var(--sand);font-family:var(--ff-serif)}
.tb-uname{font-size:12px;font-weight:500;color:rgba(255,255,255,0.7)}
.upload-spin{display:none;position:absolute;inset:0;border-radius:50%;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;}
.upload-spin.on{display:flex}
.upload-spin svg{width:20px;height:20px;animation:rot 0.8s linear infinite}
@keyframes rot{to{transform:rotate(360deg)}}
#pfInput{display:none}
.tb-logout{font-size:12px;color:rgba(255,255,255,0.3);display:flex;align-items:center;gap:6px;transition:color 0.2s;}
.tb-logout:hover{color:#ff7070}
.tb-logout svg{width:14px;height:14px}

/* HERO */
.hero{
  background:
    linear-gradient(to bottom,rgba(43,38,32,0.65) 0%,rgba(43,38,32,0.50) 50%,rgba(43,38,32,0.80) 100%),
    url('https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=1600&auto=format&fit=crop&q=80')
    center center / cover no-repeat;
  padding:52px 52px 0;
  position:relative;overflow:hidden;
}
.hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at center, transparent 40%, rgba(43,38,32,0.45) 100%);pointer-events:none;z-index:1;}
.hero-grid{display:grid;grid-template-columns:1fr auto;gap:40px;align-items:end;position:relative;z-index:2;margin-bottom:40px;}
.hero-eyebrow{font-family:var(--ff-mono);font-size:10px;letter-spacing:3px;color:var(--sand);text-transform:uppercase;margin-bottom:14px;opacity:0.9;}
.hero-h1{font-family:var(--ff-serif);font-size:46px;font-weight:700;color:var(--snow);line-height:1.1;text-shadow:0 2px 12px rgba(0,0,0,0.4);}
.hero-h1 em{font-style:italic;color:var(--ember2)}
.hero-sub{font-size:14px;color:rgba(255,255,255,0.55);margin-top:12px;line-height:1.7;font-weight:300;}
.hero-right{text-align:right;flex-shrink:0}
.hr-since-label{font-family:var(--ff-mono);font-size:10px;color:rgba(255,255,255,0.35);letter-spacing:2px;text-transform:uppercase;}
.hr-since-val{font-family:var(--ff-serif);font-size:18px;color:var(--sand);margin-top:4px;}
.hr-actions{margin-top:20px;display:flex;gap:10px;justify-content:flex-end}
.hero-stats-strip{display:flex;border-top:1px solid rgba(255,255,255,0.10);position:relative;z-index:2;background:rgba(0,0,0,0.25);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);}
.hss{flex:1;padding:20px 28px;border-right:1px solid rgba(255,255,255,0.08);transition:background 0.2s;}
.hss:last-child{border-right:none}
.hss:hover{background:rgba(255,255,255,0.06)}
.hss-n{
  font-family:var(--ff-serif);font-size:36px;font-weight:700;color:var(--snow);line-height:1;margin-bottom:5px;
  transition:transform 0.35s cubic-bezier(0.34,1.56,0.64,1), color 0.25s;
  display:inline-block;
}
/* Number pop animation triggered by JS */
.hss-n.pop{
  transform:scale(1.25);
  color:var(--ember2);
}
.hss-l{font-size:10px;letter-spacing:2.5px;text-transform:uppercase;color:rgba(255,255,255,0.4);font-weight:500;}

/* TAB BAR */
.tabbar{background:var(--mist);border-bottom:2px solid rgba(196,168,130,0.5);padding:0 52px;display:flex;align-items:flex-end;gap:2px;position:sticky;top:62px;z-index:100;box-shadow:0 2px 10px rgba(43,38,32,0.07);}
.tab{display:inline-flex;align-items:center;gap:8px;padding:14px 20px 12px;font-size:13px;font-weight:500;color:var(--bark);border-bottom:3px solid transparent;margin-bottom:-2px;transition:all 0.18s;white-space:nowrap;}
.tab svg{width:15px;height:15px;flex-shrink:0;opacity:0.55}
.tab:hover{color:var(--stone);background:rgba(92,74,58,0.05)}
.tab.on{color:var(--stone);border-bottom-color:var(--ember)}
.tab.on svg{opacity:1}
.tab-gap{flex:1}
.tab-explore{align-self:center;display:inline-flex;align-items:center;gap:7px;padding:8px 18px;font-size:12px;font-weight:600;letter-spacing:0.4px;color:var(--snow);background:var(--forest);border-radius:5px;transition:background 0.2s;margin-left:12px;}
.tab-explore:hover{background:var(--fern)}
.tab-explore svg{width:13px;height:13px;fill:var(--snow);opacity:1}

/* CONTENT */
.content{padding:48px 52px 72px;max-width:1160px}

/* section heading */
.sh{display:flex;align-items:center;gap:18px;margin-bottom:28px}
.sh-title{font-family:var(--ff-serif);font-size:24px;font-weight:400;color:var(--stone);white-space:nowrap}
.sh-rule{flex:1;height:1px;background:var(--sand);opacity:0.35}
.sh-link{font-size:11px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:var(--ember);white-space:nowrap;transition:opacity 0.2s}
.sh-link:hover{opacity:0.65}

/* PROFILE CARD */
.pcard{background:var(--snow);border:1px solid rgba(196,168,130,0.4);border-radius:12px;overflow:hidden;margin-bottom:48px;}
.pc-banner{background:linear-gradient(135deg,var(--stone2) 0%,var(--bark) 100%);padding:30px 36px;display:flex;align-items:center;gap:24px;position:relative;overflow:hidden;}
.pc-banner::after{content:'';position:absolute;right:-60px;top:-60px;width:240px;height:240px;border-radius:50%;background:rgba(196,168,130,0.05);border:1px solid rgba(196,168,130,0.06);}
.pc-banner::before{content:'';position:absolute;right:60px;top:10px;width:120px;height:120px;border-radius:50%;background:rgba(196,168,130,0.03);}
.pc-av{width:76px;height:76px;border-radius:50%;border:3px solid rgba(196,168,130,0.6);overflow:hidden;cursor:pointer;position:relative;display:flex;align-items:center;justify-content:center;background:var(--stone);flex-shrink:0;z-index:2;transition:border-color 0.2s;}
.pc-av:hover{border-color:var(--ember2)}
.pc-av img{width:100%;height:100%;object-fit:cover}
.pc-av-init{font-family:var(--ff-serif);font-size:28px;color:var(--sand)}
.pc-av-overlay{position:absolute;inset:0;border-radius:50%;background:rgba(0,0,0,0.45);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity 0.2s;}
.pc-av:hover .pc-av-overlay{opacity:1}
.pc-av-overlay svg{width:20px;height:20px;fill:#fff}
.pc-text{z-index:2;position:relative}
.pc-name{font-family:var(--ff-serif);font-size:24px;color:var(--snow);font-weight:700;margin-bottom:4px}
.pc-un{font-family:var(--ff-mono);font-size:12px;color:rgba(255,255,255,0.35)}
.pc-since-wrap{margin-left:auto;text-align:right;z-index:2;position:relative}
.pc-since-lbl{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.2);font-family:var(--ff-mono)}
.pc-since-v{font-family:var(--ff-serif);font-size:17px;color:var(--sand);margin-top:3px}
.pc-change-btn{display:inline-flex;align-items:center;gap:5px;margin-top:14px;font-size:10px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;color:rgba(196,168,130,0.6);background:transparent;border:1px solid rgba(196,168,130,0.2);border-radius:20px;padding:5px 14px;cursor:pointer;transition:all 0.2s;}
.pc-change-btn:hover{color:var(--sand);border-color:rgba(196,168,130,0.5);background:rgba(196,168,130,0.06)}
.pc-row{display:grid;grid-template-columns:160px 1fr;align-items:center;padding:16px 36px;border-bottom:1px solid var(--mist);transition:background 0.15s;}
.pc-row:last-child{border-bottom:none}
.pc-row:hover{background:var(--fog)}
.pc-k{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--soil);font-weight:600}
.pc-v{font-size:14px;color:var(--stone)}
.pc-v.mono{font-family:var(--ff-mono);font-size:12px;color:var(--bark)}
.badge-ok{display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--forest);background:rgba(45,74,45,0.1);padding:3px 10px;border-radius:20px;font-weight:500;}

/* QUICK ACTIONS */
.qa-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:48px;}
.qa{background:var(--snow);border:1px solid var(--mist);border-radius:12px;padding:26px 22px 22px;position:relative;overflow:hidden;transition:transform 0.2s,border-color 0.2s,box-shadow 0.2s;}
.qa-accent{position:absolute;top:0;left:0;right:0;height:3px;background:var(--sand);transform:scaleX(0);transform-origin:left;transition:transform 0.25s ease;}
.qa:hover{transform:translateY(-5px);border-color:rgba(196,168,130,0.6);box-shadow:0 10px 30px rgba(43,38,32,0.1)}
.qa:hover .qa-accent{transform:scaleX(1)}
.qa-ico{width:46px;height:46px;border-radius:10px;background:var(--fog);border:1px solid var(--mist);display:flex;align-items:center;justify-content:center;margin-bottom:16px;transition:background 0.2s,border-color 0.2s;}
.qa:hover .qa-ico{background:rgba(122,155,106,0.15);border-color:var(--sage)}
.qa-ico svg{width:22px;height:22px;fill:var(--bark)}
.qa:hover .qa-ico svg{fill:var(--forest)}
.qa-t{font-size:14px;font-weight:600;color:var(--stone);margin-bottom:5px}
.qa-s{font-size:12px;color:var(--soil)}
.qa-arr{position:absolute;top:20px;right:20px;font-size:16px;color:var(--mist);transition:color 0.2s,transform 0.2s;}
.qa:hover .qa-arr{color:var(--ember);transform:translate(3px,-3px)}

/* BOOKINGS */
.bk-card{background:var(--snow);border:1px solid var(--mist);border-radius:12px;overflow:hidden;margin-bottom:48px;}
.bk-hd{display:flex;align-items:center;justify-content:space-between;padding:20px 30px;border-bottom:1px solid var(--mist);background:var(--fog);}
.bk-hd-t{font-family:var(--ff-serif);font-size:18px;color:var(--stone);font-weight:700}
.btn-new-bk{font-size:12px;font-weight:600;letter-spacing:0.5px;color:var(--snow);background:var(--forest);border-radius:5px;padding:8px 18px;transition:background 0.2s;}
.btn-new-bk:hover{background:var(--fern)}
table.bkt{width:100%;border-collapse:collapse}
table.bkt th{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--soil);font-weight:600;padding:14px 22px;text-align:left;background:var(--fog);border-bottom:1px solid var(--mist);}
table.bkt td{padding:15px 22px;border-bottom:1px solid var(--mist);font-size:13px;color:var(--stone);vertical-align:middle;}
table.bkt tr:last-child td{border-bottom:none}
table.bkt tr:hover td{background:rgba(247,244,239,0.8)}
/* Row fade-out on cancel */
table.bkt tr.cancelling td{transition:opacity 0.4s, background 0.4s;opacity:0.4;background:rgba(192,57,43,0.04);}
.bk-id{font-family:var(--ff-mono);font-size:11px;color:var(--soil)}
.bk-dest{font-weight:600}

/* STATUS PILLS */
.pill{display:inline-block;padding:4px 12px;border-radius:20px;font-size:10px;font-weight:600;letter-spacing:1px;text-transform:uppercase;}
.pill-pending{background:#FEF3E2;color:#9A6200}
.pill-active{background:#E5F2E5;color:#2A5C2A}
.pill-confirmed{background:#E5F2E5;color:#2A5C2A}
.pill-cancelled{background:#FCEAEA;color:#8C2020}
.pill-khalti{background:var(--khalti-light);color:var(--khalti)}

.bk-btns{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.btn-tkt{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:500;color:var(--stone);border:1px solid var(--mist);border-radius:4px;padding:5px 12px;transition:all 0.2s;}
.btn-tkt:hover{background:var(--stone);color:var(--snow);border-color:var(--stone)}
.btn-cancbk{font-size:11px;font-weight:500;color:var(--flag-r);border:1px solid rgba(192,57,43,0.25);border-radius:4px;padding:5px 12px;background:transparent;cursor:pointer;transition:all 0.2s;}
.btn-cancbk:hover{background:var(--flag-r);color:#fff;border-color:var(--flag-r)}
.btn-cancbk:disabled{opacity:0.5;cursor:not-allowed}
.gf{display:inline-flex;align-items:center;gap:8px}
.gf input{width:60px;padding:5px 8px;border-radius:4px;border:1px solid var(--mist);font-size:13px;text-align:center;background:var(--fog);color:var(--stone);}
.gf input:focus{outline:none;border-color:var(--moss)}
.btn-upd{font-size:11px;font-weight:500;color:var(--forest);border:1px solid rgba(45,74,45,0.3);border-radius:4px;padding:5px 12px;background:transparent;cursor:pointer;transition:all 0.2s;}
.btn-upd:hover{background:var(--forest);color:#fff}

.khalti-locked{display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--khalti);background:var(--khalti-light);border:1px solid rgba(92,45,145,0.2);border-radius:4px;padding:5px 12px;white-space:nowrap;}

.empty-st{padding:80px 40px;text-align:center}
.empty-glyph{font-size:80px;line-height:1;margin-bottom:18px;opacity:0.15;font-family:var(--ff-serif);font-weight:700;letter-spacing:-4px;color:var(--bark);}
.empty-h{font-family:var(--ff-serif);font-size:22px;color:var(--bark);margin-bottom:8px}
.empty-p{font-size:13px;color:var(--soil);margin-bottom:28px}
.btn-plan{display:inline-block;background:var(--ember);color:#fff;padding:12px 30px;border-radius:5px;font-size:13px;font-weight:600;letter-spacing:0.5px;transition:background 0.2s,transform 0.15s;}
.btn-plan:hover{background:#A8481A;transform:translateY(-1px)}

/* SAVED */
.saved-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:40px;}
.saved-ph{background:var(--snow);border:1.5px dashed rgba(196,168,130,0.5);border-radius:12px;padding:44px 20px;text-align:center;transition:border-color 0.2s,background 0.2s;}
.saved-ph:hover{border-color:var(--sand);background:var(--fog)}
.saved-ph-glyph{font-family:var(--ff-serif);font-size:44px;font-weight:700;color:var(--mist);line-height:1;margin-bottom:12px;}
.saved-ph p{font-size:12px;color:var(--soil)}
.cta-row{text-align:center;margin-top:16px}

/* SETTINGS */
.stg-card{background:var(--snow);border:1px solid var(--mist);border-radius:12px;overflow:hidden;margin-bottom:22px;}
.stg-hd{padding:18px 30px;border-bottom:1px solid var(--mist);background:var(--fog);}
.stg-hd h3{font-family:var(--ff-serif);font-size:19px;font-weight:700;color:var(--stone)}
.stg-hd p{font-size:12px;color:var(--soil);margin-top:3px}
.stg-body{padding:28px 30px}
.fg{margin-bottom:20px}
.fg:last-child{margin-bottom:0}
.flbl{display:block;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--soil);font-weight:600;margin-bottom:8px}
.fin{width:100%;max-width:400px;padding:11px 16px;border:1px solid var(--mist);border-radius:5px;font-size:14px;background:var(--fog);color:var(--stone);transition:border-color 0.2s;}
.fin:focus{outline:none;border-color:var(--moss)}
.fin:disabled{background:var(--mist);color:var(--soil);cursor:not-allowed}
.btn-sv{background:var(--stone);color:var(--snow);border:none;border-radius:5px;padding:11px 28px;font-size:13px;font-weight:600;cursor:pointer;letter-spacing:0.5px;transition:background 0.2s,transform 0.15s;}
.btn-sv:hover{background:var(--ember);transform:translateY(-1px)}

/* ALERT */
.alert{display:flex;align-items:center;gap:12px;padding:14px 20px;border-radius:8px;font-size:13px;margin-bottom:28px;border-left:3px solid;}
.alert svg{width:16px;height:16px;flex-shrink:0}
.alert-success{background:#EBF5EB;color:#265226;border-color:var(--moss)}
.alert-error{background:#FCEAEA;color:#7A1E1E;border-color:var(--flag-r)}

/* TOAST */
.toast{position:fixed;bottom:32px;right:32px;background:var(--stone);color:var(--snow);padding:14px 22px;border-radius:6px;font-size:13px;box-shadow:0 8px 32px rgba(43,38,32,0.25);transform:translateY(16px);opacity:0;transition:all 0.32s cubic-bezier(0.34,1.56,0.64,1);z-index:9999;pointer-events:none;border-left:3px solid var(--sand);}
.toast.show{transform:translateY(0);opacity:1}
.toast.success{border-left-color:var(--moss)}
.toast.error{border-left-color:var(--flag-r)}

/* DELETE MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(43,38,32,0.70);z-index:9000;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(3px);-webkit-backdrop-filter:blur(3px);}
.modal-overlay.open{display:flex}
.modal-box{background:var(--snow);border-radius:16px;max-width:440px;width:100%;overflow:hidden;box-shadow:0 28px 72px rgba(43,38,32,0.35);animation:modalIn 0.25s cubic-bezier(0.34,1.4,0.64,1);}
@keyframes modalIn{from{transform:scale(0.90) translateY(16px);opacity:0}to{transform:scale(1) translateY(0);opacity:1}}
.modal-head{padding:26px 28px 20px;border-bottom:1px solid var(--mist);background:linear-gradient(135deg,#fff8f7,var(--snow));}
.modal-head-icon{width:48px;height:48px;border-radius:50%;background:rgba(192,57,43,0.10);border:2px solid rgba(192,57,43,0.20);display:flex;align-items:center;justify-content:center;margin-bottom:14px;}
.modal-head-icon svg{width:22px;height:22px;fill:var(--flag-r)}
.modal-head h2{font-family:var(--ff-serif);font-size:20px;color:var(--flag-r);margin-bottom:6px;}
.modal-head p{font-size:13px;color:var(--soil);line-height:1.65;}
.modal-warns{margin:0 28px;padding:14px 16px;background:#fff8f7;border:1px solid rgba(192,57,43,0.18);border-radius:8px;list-style:none;margin-top:20px;}
.modal-warns li{font-size:12px;color:#7A1E1E;padding:4px 0;display:flex;align-items:center;gap:8px;}
.modal-warns li::before{content:'✕';font-weight:700;color:var(--flag-r);flex-shrink:0;}
.modal-body{padding:22px 28px;}
.modal-footer{padding:0 28px 26px;display:flex;gap:12px;}
.btn-cancel-modal{flex:1;background:var(--fog);color:var(--stone);border:1px solid var(--mist);border-radius:6px;padding:12px;font-size:13px;font-weight:600;cursor:pointer;transition:background 0.2s;}
.btn-cancel-modal:hover{background:var(--mist)}
.btn-delete-confirm{flex:1;background:var(--flag-r);color:#fff;border:none;border-radius:6px;padding:12px;font-size:13px;font-weight:600;cursor:pointer;transition:background 0.2s,opacity 0.2s;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-delete-confirm:hover{background:#9B2D20}
.btn-delete-confirm:disabled{opacity:0.6;cursor:not-allowed}
.del-pw-wrap{position:relative;}
.del-pw-wrap input{padding-right:44px;}
.del-pw-toggle{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:0;color:var(--soil);}
.del-pw-toggle svg{width:18px;height:18px;fill:currentColor}
.del-error{display:none;color:var(--flag-r);font-size:12px;margin-top:8px;font-weight:500;}
.del-error.show{display:block}

/* DANGER ZONE card */
.danger-card{background:var(--snow);border:1.5px solid rgba(192,57,43,0.30);border-radius:12px;overflow:hidden;margin-bottom:22px;}
.danger-hd{padding:18px 30px;border-bottom:1px solid rgba(192,57,43,0.15);background:linear-gradient(135deg,#fff8f7,var(--snow));}
.danger-hd h3{font-family:var(--ff-serif);font-size:19px;font-weight:700;color:var(--flag-r)}
.danger-hd p{font-size:12px;color:#7A3030;margin-top:3px;line-height:1.6;}
.danger-body{padding:24px 30px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;}
.danger-body p{font-size:13px;color:var(--soil);max-width:480px;line-height:1.6;}
.btn-open-delete{background:transparent;color:var(--flag-r);border:1.5px solid var(--flag-r);border-radius:6px;padding:11px 26px;font-size:13px;font-weight:600;cursor:pointer;letter-spacing:0.3px;transition:all 0.2s;white-space:nowrap;display:inline-flex;align-items:center;gap:8px;}
.btn-open-delete:hover{background:var(--flag-r);color:#fff}
.btn-open-delete svg{width:15px;height:15px;fill:currentColor}

/* Confirm cancel mini-modal */
.confirm-overlay{display:none;position:fixed;inset:0;background:rgba(43,38,32,0.55);z-index:8000;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px);}
.confirm-overlay.open{display:flex}
.confirm-box{background:var(--snow);border-radius:12px;max-width:360px;width:100%;padding:28px;box-shadow:0 20px 60px rgba(43,38,32,0.28);animation:modalIn 0.2s cubic-bezier(0.34,1.4,0.64,1);text-align:center;}
.confirm-box h3{font-family:var(--ff-serif);font-size:18px;color:var(--stone);margin-bottom:8px;}
.confirm-box p{font-size:13px;color:var(--soil);line-height:1.6;margin-bottom:22px;}
.confirm-btns{display:flex;gap:10px;}
.confirm-btns button{flex:1;border-radius:6px;padding:11px;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.2s;border:none;}
.cb-keep{background:var(--fog);color:var(--stone);border:1px solid var(--mist);}
.cb-keep:hover{background:var(--mist)}
.cb-yes{background:var(--flag-r);color:#fff;}
.cb-yes:hover{background:#9B2D20}
.cb-yes:disabled{opacity:0.6;cursor:not-allowed}

/* RESPONSIVE */
@media(max-width:900px){
  .topbar,.hero,.content{padding-left:20px;padding-right:20px}
  .tabbar{padding:0 20px;overflow-x:auto}
  .hero-h1{font-size:30px}
  .hero-grid{grid-template-columns:1fr;gap:16px}
  .hero-right{text-align:left}
  .hero-stats-strip{flex-wrap:wrap}
  .hss{min-width:50%;border-right:none;border-bottom:1px solid rgba(255,255,255,0.07)}
  .qa-grid{grid-template-columns:repeat(2,1fr)}
  .saved-cards{grid-template-columns:1fr}
  table.bkt{font-size:12px}
  .tab{padding:12px 14px;font-size:12px}
  .tab-explore{display:none}
  .danger-body{flex-direction:column;align-items:flex-start}
}
</style>
</head>
<body>


<!-- TOPBAR -->
<header class="topbar">
  <a href="/Nepal-Travel/Public/index.php" class="tb-logo">
    <div class="nepal-flag"><div class="flag-top"></div><div class="flag-bot"></div></div>
    Nepal <em>Truly</em> Authentic
  </a>
  <div class="tb-right">
    <a href="/Nepal-Travel/Public/index.php" class="tb-back">← Back to site</a>
    <div class="tb-user-pill" onclick="document.getElementById('pfInput').click()" title="Click to change photo">
      <div class="tb-avatar" id="tbAvatar">
        <?php if ($profileImageUrl): ?>
          <img src="<?php echo $profileImageUrl; ?>" id="tbAvatarImg" alt="">
        <?php else: ?>
          <div class="tb-avatar-init"><?php echo $initials; ?></div>
        <?php endif; ?>
        <div class="upload-spin" id="uploadSpin">
          <svg viewBox="0 0 24 24" fill="none" stroke="#C4A882" stroke-width="2.5"><circle cx="12" cy="12" r="9" stroke-dasharray="28 56" stroke-linecap="round"/></svg>
        </div>
      </div>
      <span class="tb-uname"><?php echo $userName; ?></span>
    </div>
    <input type="file" id="pfInput" accept="image/jpeg,image/png,image/gif,image/webp">
    <a href="/Nepal-Travel/user/logout.php" class="tb-logout">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
      Sign Out
    </a>
  </div>
</header>

<!-- HERO -->
<div class="hero">
  <div class="hero-grid">
    <div>
      <div class="hero-eyebrow">// My Journey — Nepal Dashboard</div>
      <h1 class="hero-h1">Namaste,<br><em><?php echo $userName; ?></em></h1>
      <p class="hero-sub">The Himalayas are always calling. Manage your adventures, bookings and saved places — all in one place.</p>
    </div>
    <div class="hero-right">
      <div class="hr-since-label">Trekker since</div>
      <div class="hr-since-val"><?php echo $memberSince; ?></div>
      <div class="hr-actions">
        <a href="?tab=bookings" style="font-size:12px;font-weight:600;color:var(--sand);background:rgba(196,168,130,0.15);border:1px solid rgba(196,168,130,0.3);border-radius:4px;padding:8px 16px;transition:all 0.2s" onmouseover="this.style.background='rgba(196,168,130,0.28)'" onmouseout="this.style.background='rgba(196,168,130,0.15)'">My Bookings</a>
        <a href="/Nepal-Travel/pages/experience.php" style="font-size:12px;font-weight:600;color:var(--snow);background:var(--ember);border-radius:4px;padding:8px 16px;transition:background 0.2s" onmouseover="this.style.background='#A8481A'" onmouseout="this.style.background='var(--ember)'">Explore →</a>
      </div>
    </div>
  </div>
  <div class="hero-stats-strip">
    <!-- Trips Taken = confirmed Khalti-paid bookings -->
    <div class="hss">
      <div class="hss-n" id="stat-trips"><?= $paidTripsCount ?></div>
      <div class="hss-l">Trips Taken</div>
    </div>
    <div class="hss">
      <div class="hss-n" id="stat-bookings"><?= $bookingCount ?></div>
      <div class="hss-l">Bookings</div>
    </div>
    <div class="hss">
      <div class="hss-n" id="stat-saved"><?= $savedCount ?></div>
      <div class="hss-l">Saved Places</div>
    </div>
    <div class="hss">
      <div class="hss-n">0</div>
      <div class="hss-l">Peaks Explored</div>
    </div>
  </div>
</div>

<!-- TABS -->
<nav class="tabbar">
  <a href="?tab=overview" class="tab <?php echo $activeTab==='overview'?'on':''; ?>">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
    Overview
  </a>
  <a href="?tab=bookings" class="tab <?php echo $activeTab==='bookings'?'on':''; ?>">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
    My Bookings
  </a>
  <a href="?tab=saved" class="tab <?php echo $activeTab==='saved'?'on':''; ?>">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H7c-1.1 0-1.99.9-1.99 2L5 21l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
    Saved Places
  </a>
  <a href="?tab=settings" class="tab <?php echo $activeTab==='settings'?'on':''; ?>">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
    Settings
  </a>
  <a href="?tab=subscriptions" class="tab <?php echo $activeTab==='subscriptions'?'on':''; ?>">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
    My Subscriptions
  </a>

  <div class="tab-gap"></div>
  <a href="/Nepal-Travel/Public/experience.php" class="tab-explore">
    <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
    Explore Nepal
  </a>
</nav>

<!-- CONTENT -->
<div class="content">

<?php if ($activeTab === 'overview'): ?>

  <div class="sh">
    <span class="sh-title">Your Profile</span>
    <div class="sh-rule"></div>
    <a href="?tab=settings" class="sh-link">Edit →</a>
  </div>

  <div class="pcard">
    <div class="pc-banner">
      <div class="pc-av" onclick="document.getElementById('pfInput').click()">
        <?php if ($profileImageUrl): ?>
          <img src="<?php echo $profileImageUrl; ?>" alt="Profile">
        <?php else: ?>
          <div class="pc-av-init"><?php echo $initials; ?></div>
        <?php endif; ?>
        <div class="pc-av-overlay">
          <svg viewBox="0 0 24 24"><path d="M9 3L7.17 5H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-3.17L15 3H9zm3 14a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/></svg>
        </div>
      </div>
      <div class="pc-text">
        <div class="pc-name"><?php echo $userName; ?></div>
        <div class="pc-un">@<?php echo $userUsername; ?></div>
        <button class="pc-change-btn" onclick="document.getElementById('pfInput').click()">
          <svg viewBox="0 0 24 24" fill="currentColor" style="width:12px;height:12px"><path d="M9 3L7.17 5H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-3.17L15 3H9z"/></svg>
          Change Photo
        </button>
      </div>
      <div class="pc-since-wrap">
        <div class="pc-since-lbl">Member since</div>
        <div class="pc-since-v"><?php echo $memberSince; ?></div>
      </div>
    </div>
    <div class="pc-rows">
      <div class="pc-row"><span class="pc-k">Full Name</span><span class="pc-v"><?php echo $userName; ?></span></div>
      <div class="pc-row"><span class="pc-k">Username</span><span class="pc-v mono">@<?php echo $userUsername; ?></span></div>
      <div class="pc-row"><span class="pc-k">Email</span><span class="pc-v mono"><?php echo $userEmail; ?></span></div>
      <div class="pc-row"><span class="pc-k">Photo</span><span class="pc-v">
        <?php if ($profileImageUrl): ?>
          <span class="badge-ok"><svg viewBox="0 0 24 24" fill="currentColor" style="width:12px;height:12px"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg> Uploaded</span>
        <?php else: ?>
          <span style="color:var(--soil);font-size:13px">Not set — click your avatar above to upload</span>
        <?php endif; ?>
      </span></div>
    </div>
  </div>

  <div class="sh"><span class="sh-title">Quick Actions</span><div class="sh-rule"></div></div>

  <div class="qa-grid">
    <a href="/Nepal-Travel/Public/experience.php" class="qa">
      <div class="qa-accent"></div>
      <div class="qa-ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></div>
      <div class="qa-t">Explore Nepal</div><div class="qa-s">Destinations &amp; trails</div>
      <span class="qa-arr">↗</span>
    </a>
    <a href="/Nepal-Travel/Public/deals-and-packages.php" class="qa">
      <div class="qa-accent"></div>
      <div class="qa-ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42z"/></svg></div>
      <div class="qa-t">Deals &amp; Packages</div><div class="qa-s">Special offers</div>
      <span class="qa-arr">↗</span>
    </a>

    <a href="?tab=bookings" class="qa">
      <div class="qa-accent"></div>
      <div class="qa-ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H7c-1.1 0-1.99.9-1.99 2L5 21l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg></div>
      <div class="qa-t">My Bookings</div><div class="qa-s">View all trips</div>
      <span class="qa-arr">↗</span>
    </a>
  </div>

<?php elseif ($activeTab === 'bookings'):
  $stmt = $conn->prepare("SELECT id, destination, date, guests, status, payment_method, created_at FROM bookings WHERE user_id = ? ORDER BY created_at DESC");
  $stmt->bind_param("i", $_SESSION['user_id']);
  $stmt->execute();
  $bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
?>
  <?php if (!empty($booking_action_message)): ?>
    <div class="alert alert-<?php echo $booking_action_type; ?>">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="<?php echo $booking_action_type==='success'?'M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z':'M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z';?>"/></svg>
      <?php echo htmlspecialchars($booking_action_message); ?>
    </div>
  <?php endif; ?>

  <div class="bk-card">
    <div class="bk-hd">
      <span class="bk-hd-t">Booking History</span>
      <a href="/Nepal-Travel/Public/booking.php" class="btn-new-bk">+ New Booking</a>
    </div>

    <?php if (count($bookings) === 0): ?>
      <div class="empty-st">
        <div class="empty-glyph">⛰</div>
        <div class="empty-h">No adventures yet</div>
        <p class="empty-p">The Himalayas are waiting — book your first Nepal experience today.</p>
        <a href="/Nepal-Travel/Public/booking.php" class="btn-plan">Plan a Trip →</a>
      </div>
    <?php else: ?>
      <div style="overflow-x:auto">
        <table class="bkt">
          <thead>
            <tr>
              <th>ID</th>
              <th>Destination</th>
              <th>Travel Date</th>
              <th>Guests</th>
              <th>Payment</th>
              <th>Status</th>
              <th>Booked</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $b):
              $isKhalti = ($b['payment_method'] === 'khalti');
            ?>
            <tr id="bk-row-<?php echo $b['id']; ?>">
              <td class="bk-id">#<?php echo str_pad($b['id'], 6, '0', STR_PAD_LEFT); ?></td>

              <td class="bk-dest"><?php echo htmlspecialchars($b['destination']); ?></td>

              <td><?php echo date('M j, Y', strtotime($b['date'])); ?></td>

              <!-- GUESTS: locked if Khalti-paid or confirmed -->
              <td>
                <?php if (!$isKhalti && $b['status'] !== 'cancelled' && $b['status'] !== 'confirmed'): ?>
                  <form method="POST" action="?tab=bookings" class="gf">
                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                    <input type="hidden" name="booking_action" value="update_guests">
                    <input type="number" name="guests" value="<?php echo $b['guests']; ?>" min="1" max="50">
                    <button type="submit" class="btn-upd">Save</button>
                  </form>
                <?php else: ?>
                  <span style="color:var(--soil)"><?php echo $b['guests']; ?></span>
                <?php endif; ?>
              </td>

              <!-- PAYMENT METHOD -->
              <td>
                <?php if ($isKhalti): ?>
                  <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;color:var(--khalti);background:var(--khalti-light);padding:3px 10px;border-radius:20px;">
                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:11px;height:11px"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    Khalti
                  </span>
                <?php else: ?>
                  <span style="font-size:12px;color:var(--soil);">
                    <?php echo ucfirst($b['payment_method'] ?? 'Cash'); ?>
                  </span>
                <?php endif; ?>
              </td>

              <!-- STATUS -->
              <td>
                <?php if ($isKhalti): ?>
                  <span class="pill pill-khalti" id="pill-<?php echo $b['id']; ?>">✓ Confirmed</span>
                <?php else: ?>
                  <span class="pill pill-<?php echo htmlspecialchars($b['status']); ?>" id="pill-<?php echo $b['id']; ?>">
                    <?php echo ucfirst($b['status']); ?>
                  </span>
                <?php endif; ?>
              </td>

              <td style="color:var(--soil)"><?php echo date('M j, Y', strtotime($b['created_at'])); ?></td>

              <!-- ACTIONS -->
              <td>
                <div class="bk-btns" id="actions-<?php echo $b['id']; ?>">
                  <a href="/Nepal-Travel/Public/ticket.php?id=<?php echo $b['id']; ?>" class="btn-tkt">
                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:12px;height:12px"><path d="M22 10V6c0-1.11-.9-2-2-2H4c-1.1 0-1.99.89-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-1.99 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2z"/></svg>
                    Ticket
                  </a>

                  <?php if ($isKhalti || $b['status'] === 'confirmed'): ?>
                    <span class="khalti-locked">
                      <svg viewBox="0 0 24 24" fill="currentColor" style="width:11px;height:11px"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                      Contact support to modify
                    </span>
                  <?php elseif ($b['status'] !== 'cancelled'): ?>
                    <!-- AJAX cancel button -->
                    <button
                      type="button"
                      class="btn-cancbk js-cancel-btn"
                      data-id="<?php echo $b['id']; ?>"
                    >Cancel</button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

<?php elseif ($activeTab === 'saved'):
    $saved_deals = getSavedDeals($conn);
    $saved_experiences = getSavedExperiencePosts($conn, (int)$_SESSION['user_id']);
?>

  <div class="sh">
    <span class="sh-title">Saved Items</span>
    <div class="sh-rule"></div>
    <a href="/Nepal-Travel/Public/saved.php" class="sh-link">View full page →</a>
  </div>

  <h3 style="font-family:var(--ff-serif); font-size:20px; color:var(--stone); margin-bottom:16px;">Experience Posts</h3>
  <?php if (empty($saved_experiences)): ?>
    <div class="saved-ph" style="margin-bottom:32px;"><div class="saved-ph-glyph">📸</div><p>Bookmark posts on the Experience page to see them here.</p></div>
    <div class="cta-row" style="margin-bottom:40px;">
      <a href="/Nepal-Travel/Public/experience.php" class="btn-plan">Browse Experiences →</a>
    </div>
  <?php else: ?>
    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:20px; margin-bottom:44px;">
      <?php foreach ($saved_experiences as $post): ?>
      <div style="background:var(--snow); border:1px solid var(--mist); border-radius:12px; overflow:hidden;">
        <a href="/Nepal-Travel/Public/experience.php#post-card-<?= (int)$post['id'] ?>">
          <img src="<?= htmlspecialchars(formatSavedExperienceImage($post['image_path'])) ?>" alt="" style="width:100%; height:180px; object-fit:cover; display:block;">
        </a>
        <div style="padding:16px 18px;">
          <div style="font-size:10px; font-weight:700; color:var(--ember); text-transform:uppercase; margin-bottom:6px;">
            <?= htmlspecialchars($post['username'] ?? 'Traveler') ?>
          </div>
          <p style="font-size:13px; color:var(--soil); line-height:1.5; margin:0 0 12px;">
            <?= htmlspecialchars(strlen($post['caption'] ?? '') > 90 ? substr($post['caption'], 0, 90) . '…' : ($post['caption'] ?? '')) ?>
          </p>
          <a href="/Nepal-Travel/Public/saved.php" style="font-size:11px; font-weight:700; color:var(--stone); text-decoration:none;">Manage saved →</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <h3 style="font-family:var(--ff-serif); font-size:20px; color:var(--stone); margin-bottom:16px;">Deals &amp; Packages</h3>
  <?php if (empty($saved_deals)): ?>
    <div class="saved-cards">
      <div class="saved-ph"><div class="saved-ph-glyph">⛺</div><p>Save trekking camps &amp; base camps</p></div>
      <div class="saved-ph"><div class="saved-ph-glyph">🏔</div><p>Bookmark peak routes &amp; trails</p></div>
      <div class="saved-ph"><div class="saved-ph-glyph">🕌</div><p>Favourite temples &amp; heritage sites</p></div>
    </div>
    <div class="cta-row">
      <a href="/Nepal-Travel/Public/deals-and-packages.php" class="btn-plan">Explore &amp; Save Deals →</a>
    </div>
  <?php else: ?>
    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:22px; margin-bottom:40px;">
      <?php foreach ($saved_deals as $deal): ?>
      <div style="background:var(--snow); border:1px solid var(--mist); border-radius:12px; overflow:hidden; display:flex; flex-direction:column;">
        <a href="/Nepal-Travel/Public/deal-details.php?id=<?= (int)$deal['id'] ?>" style="display:block; position:relative;">
          <img src="<?= htmlspecialchars($deal['image_url']) ?>" alt="<?= htmlspecialchars($deal['title']) ?>" style="width:100%; height:200px; object-fit:cover; display:block;">
          <?php if (!empty($deal['category'])): ?>
          <span style="position:absolute; top:12px; right:12px; background:var(--stone); color:var(--sand); font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; padding:5px 12px; border-radius:4px;">
            <?= htmlspecialchars($deal['category']) ?>
          </span>
          <?php endif; ?>
        </a>
        <div style="padding:18px 20px; flex:1; display:flex; flex-direction:column; gap:8px;">
          <?php if (!empty($deal['location'])): ?>
          <span style="font-size:11px; font-weight:700; color:var(--ember); letter-spacing:0.5px; text-transform:uppercase;">
            📍 <?= htmlspecialchars($deal['location']) ?>
          </span>
          <?php endif; ?>
          <a href="/Nepal-Travel/Public/deal-details.php?id=<?= (int)$deal['id'] ?>" style="font-family:var(--ff-serif); font-size:17px; font-weight:700; color:var(--stone); text-decoration:none; line-height:1.3;">
            <?= htmlspecialchars($deal['title']) ?>
          </a>
          <div style="margin-top:auto; padding-top:14px; border-top:1px solid var(--mist); display:flex; justify-content:space-between; align-items:center;">
            <span style="font-family:var(--ff-serif); font-size:16px; font-weight:700; color:var(--stone);">
              NPR <?= number_format((float)$deal['price']) ?>
            </span>
            <a href="/Nepal-Travel/Public/save_deal.php?id=<?= (int)$deal['id'] ?>&action=remove"
               style="font-size:11px; font-weight:600; color:var(--flag-r); background:rgba(192,57,43,0.08); border:1px solid rgba(192,57,43,0.2); border-radius:4px; padding:6px 14px; text-decoration:none;">
               ✕ Remove
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>


<?php elseif ($activeTab === 'subscriptions'): ?>

  <div class="sh"><span class="sh-title">My Subscriptions</span><div class="sh-rule"></div></div>

  <!-- EVENT SUBSCRIPTIONS -->
  <div class="bk-card" style="margin-bottom:30px;">
    <div class="bk-hd">
      <span class="bk-hd-t">Event Subscriptions</span>
    </div>
    <?php if (empty($event_subscriptions)): ?>
      <div class="empty-st" style="padding:40px 20px;">
        <div class="empty-glyph">🎫</div>
        <div class="empty-h" style="font-size:18px;">No event subscriptions</div>
        <p class="empty-p" style="margin-bottom:0;">You haven't purchased any event hosting plans.</p>
      </div>
    <?php else: ?>
      <div style="overflow-x:auto">
        <table class="bkt">
          <thead>
            <tr>
              <th>ID</th>
              <th>Plan</th>
              <th>Price</th>
              <th>Method</th>
              <th>Status</th>
              <th>Expires</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($event_subscriptions as $sub): ?>
            <tr>
              <td class="bk-id">#<?php echo str_pad($sub['id'], 6, '0', STR_PAD_LEFT); ?></td>
              <td class="bk-dest"><?php echo htmlspecialchars($sub['name']); ?></td>
              <td>NPR <?php echo number_format($sub['amount_paid']); ?></td>
              <td><?php echo ucfirst($sub['payment_method'] ?? 'manual'); ?></td>
              <td><span class="pill pill-<?php echo $sub['status']; ?>"><?php echo ucfirst($sub['status']); ?></span></td>
              <td><?php echo $sub['expires_at'] ? date('M j, Y', strtotime($sub['expires_at'])) : '—'; ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- DEAL SUBSCRIPTIONS -->
  <div class="bk-card">
    <div class="bk-hd">
      <span class="bk-hd-t">Deals & Packages Subscriptions</span>
    </div>
    <?php if (empty($deal_subscriptions)): ?>
      <div class="empty-st" style="padding:40px 20px;">
        <div class="empty-glyph">🏔</div>
        <div class="empty-h" style="font-size:18px;">No deal subscriptions</div>
        <p class="empty-p" style="margin-bottom:0;">You haven't purchased any plans to post deals.</p>
      </div>
    <?php else: ?>
      <div style="overflow-x:auto">
        <table class="bkt">
          <thead>
            <tr>
              <th>ID</th>
              <th>Plan</th>
              <th>Price</th>
              <th>Method</th>
              <th>Status</th>
              <th>Expires</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($deal_subscriptions as $sub): ?>
            <tr>
              <td class="bk-id">#<?php echo str_pad($sub['id'], 6, '0', STR_PAD_LEFT); ?></td>
              <td class="bk-dest"><?php echo htmlspecialchars($sub['name']); ?></td>
              <td>NPR <?php echo number_format($sub['amount_paid']); ?></td>
              <td><?php echo ucfirst($sub['payment_method'] ?? 'manual'); ?></td>
              <td><span class="pill pill-<?php echo $sub['status']; ?>"><?php echo ucfirst($sub['status']); ?></span></td>
              <td><?php echo $sub['expires_at'] ? date('M j, Y', strtotime($sub['expires_at'])) : '—'; ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

<?php elseif ($activeTab === 'settings'): ?>

  <?php if (!empty($settings_message)): ?>
    <div class="alert alert-<?php echo $settings_msg_type; ?>">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="<?php echo $settings_msg_type==='success'?'M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z':'M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z';?>"/></svg>
      <?php echo htmlspecialchars($settings_message); ?>
    </div>
  <?php endif; ?>

  <div class="sh" style="margin-top:0"><span class="sh-title">Account Settings</span><div class="sh-rule"></div></div>

  <!-- UPDATE NAME -->
  <div class="stg-card">
    <div class="stg-hd"><h3>Update Name</h3><p>Change how your name appears across the site</p></div>
    <div class="stg-body">
      <form method="POST" action="?tab=settings">
        <div class="fg"><label class="flbl">Full Name</label><input type="text" name="full_name" class="fin" value="<?php echo $userName; ?>" required></div>
        <button type="submit" name="update_name" class="btn-sv">Save Changes</button>
      </form>
    </div>
  </div>

  <!-- CHANGE PASSWORD -->
  <div class="stg-card">
    <div class="stg-hd"><h3>Change Password</h3><p>Minimum 6 characters required</p></div>
    <div class="stg-body">
      <form method="POST" action="?tab=settings">
        <div class="fg"><label class="flbl">Current Password</label><input type="password" name="current_password" class="fin" required></div>
        <div class="fg"><label class="flbl">New Password</label><input type="password" name="new_password" class="fin" required></div>
        <div class="fg"><label class="flbl">Confirm Password</label><input type="password" name="confirm_password" class="fin" required></div>
        <button type="submit" name="change_password" class="btn-sv">Update Password</button>
      </form>
    </div>
  </div>

  <!-- EMAIL (disabled) -->
  <div class="stg-card" style="opacity:0.6">
    <div class="stg-hd"><h3>Email Address</h3><p>Contact support if you need to change your email</p></div>
    <div class="stg-body">
      <div class="fg"><label class="flbl">Email</label><input type="email" class="fin" value="<?php echo $userEmail; ?>" disabled></div>
    </div>
  </div>

  <!-- DANGER ZONE -->
  <div class="danger-card">
    <div class="danger-hd">
      <h3>⚠ Danger Zone</h3>
      <p>Irreversible and destructive actions. Please read carefully before proceeding.</p>
    </div>
    <div class="danger-body">
      <p>Deleting your account will permanently remove your profile, all bookings, and your uploaded photo from our servers. <strong>This cannot be undone.</strong></p>
      <button type="button" class="btn-open-delete" id="openDeleteModal">
        <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
        Delete My Account
      </button>
    </div>
  </div>

<?php endif; ?>

</div><!-- /content -->

<!-- ── CONFIRM CANCEL MINI-MODAL ───────────────────────────────────────────── -->
<div class="confirm-overlay" id="confirmOverlay">
  <div class="confirm-box">
    <h3>Cancel this booking?</h3>
    <p>This will mark the booking as cancelled. You will need to re-book if you change your mind.</p>
    <div class="confirm-btns">
      <button type="button" class="cb-keep" id="confirmKeep">Keep it</button>
      <button type="button" class="cb-yes"  id="confirmYes">Yes, cancel</button>
    </div>
  </div>
</div>

<!-- DELETE ACCOUNT MODAL -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="delModalTitle">
    <div class="modal-head">
      <div class="modal-head-icon">
        <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
      </div>
      <h2 id="delModalTitle">Delete Your Account</h2>
      <p>You are about to permanently delete your account. This action is <strong>irreversible</strong> — once done, there is no recovery.</p>
    </div>
    <ul class="modal-warns">
      <li>Your profile and personal information will be erased</li>
      <li>All your bookings will be permanently deleted</li>
      <li>Your uploaded profile photo will be removed</li>
      <li>Your saved places will be lost</li>
    </ul>
    <form method="POST" action="/Nepal-Travel/user/delete_account.php" id="deleteAccountForm">
      <div class="modal-body">
        <label class="flbl" for="deletePasswordInput">Enter your password to confirm deletion</label>
        <div class="del-pw-wrap">
          <input type="password" name="delete_password" id="deletePasswordInput" class="fin" placeholder="Your current password" autocomplete="current-password" style="border-color:rgba(192,57,43,0.35);">
          <button type="button" class="del-pw-toggle" id="delPwToggle" title="Show/hide password">
            <svg id="delEyeIcon" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
          </button>
        </div>
        <p class="del-error" id="delError">Please enter your password before proceeding.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel-modal" id="closeDeleteModal">Keep My Account</button>
        <button type="submit" class="btn-delete-confirm" id="delSubmitBtn">
          <svg viewBox="0 0 24 24" fill="currentColor" style="width:15px;height:15px"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
          <span id="delBtnLabel">Yes, Delete Forever</span>
        </button>
      </div>
    </form>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
(function(){

  /* ── TOAST ──────────────────────────────────────────────────────────── */
  const toast = document.getElementById('toast');
  function showToast(msg, type) {
    toast.textContent = msg;
    toast.className = 'toast ' + (type || '') + ' show';
    clearTimeout(toast._t);
    toast._t = setTimeout(() => toast.classList.remove('show'), 3800);
  }

  /* ── ANIMATE HERO STAT NUMBER ───────────────────────────────────────── */
  function animateStat(el, newVal) {
    if (!el) return;
    const startVal = parseInt(el.textContent, 10) || 0;
    if (startVal === newVal) return;

    // Brief pop animation
    el.classList.add('pop');
    setTimeout(() => el.classList.remove('pop'), 400);

    // Count-down / count-up
    const duration = 600;
    const startTime = performance.now();
    function tick(now) {
      const elapsed = now - startTime;
      const progress = Math.min(elapsed / duration, 1);
      // ease-out
      const eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(startVal + (newVal - startVal) * eased);
      if (progress < 1) requestAnimationFrame(tick);
      else el.textContent = newVal;
    }
    requestAnimationFrame(tick);
  }

  /* ── AJAX BOOKING CANCEL ────────────────────────────────────────────── */
  const confirmOverlay = document.getElementById('confirmOverlay');
  const confirmYes     = document.getElementById('confirmYes');
  const confirmKeep    = document.getElementById('confirmKeep');
  let   pendingCancelId = null;

  // Open mini-confirm when any Cancel button clicked
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.js-cancel-btn');
    if (!btn) return;
    pendingCancelId = btn.dataset.id;
    confirmOverlay.classList.add('open');
    confirmYes.disabled = false;
    confirmYes.textContent = 'Yes, cancel';
  });

  // Close on overlay backdrop click or Keep button
  confirmKeep.addEventListener('click', closeConfirm);
  confirmOverlay.addEventListener('click', function(e) {
    if (e.target === confirmOverlay) closeConfirm();
  });
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && confirmOverlay.classList.contains('open')) closeConfirm();
  });

  function closeConfirm() {
    confirmOverlay.classList.remove('open');
    pendingCancelId = null;
  }

  // Confirmed — fire AJAX cancel
  confirmYes.addEventListener('click', function() {
    if (!pendingCancelId) return;
    const bookingId = pendingCancelId;

    confirmYes.disabled = true;
    confirmYes.textContent = 'Cancelling…';

    const fd = new FormData();
    fd.append('booking_action', 'cancel');
    fd.append('booking_id', bookingId);

    fetch(window.location.href, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: fd
    })
    .then(r => r.json())
    .then(data => {
      closeConfirm();

      if (data.success) {
        // 1. Update status pill in table row
        const pill = document.getElementById('pill-' + bookingId);
        if (pill) {
          pill.className = 'pill pill-cancelled';
          pill.textContent = 'Cancelled';
        }

        // 2. Remove the Cancel button, keep Ticket button intact
        const btn = document.querySelector('.js-cancel-btn[data-id="' + bookingId + '"]');
        if (btn) btn.remove();

        // 3. Update guests cell to remove edit form
        const row = document.getElementById('bk-row-' + bookingId);
        if (row) {
          const guestForm = row.querySelector('form.gf');
          if (guestForm) {
            const guestInput = guestForm.querySelector('input[name="guests"]');
            const guestVal = guestInput ? guestInput.value : '';
            const td = guestForm.parentElement;
            td.innerHTML = '<span style="color:var(--soil)">' + guestVal + '</span>';
          }
          
          row.classList.add('cancelling');
          setTimeout(() => row.classList.remove('cancelling'), 700);
        }

        // 4. Animate hero stats — update Bookings count + Trips Taken
        const statBookings = document.getElementById('stat-bookings');
        const statTrips    = document.getElementById('stat-trips');
        if (statBookings) animateStat(statBookings, data.newTotal);
        if (statTrips)    animateStat(statTrips, data.newPaidTrips);

        showToast('Booking cancelled successfully.', 'success');
      } else {
        showToast(data.message || 'Could not cancel booking.', 'error');
      }
    })
    .catch(() => {
      closeConfirm();
      showToast('Network error — please try again.', 'error');
    });
  });

  /* ── PROFILE PHOTO UPLOAD ───────────────────────────────────────────── */
  const pfInput  = document.getElementById('pfInput');
  const tbAvatar = document.getElementById('tbAvatar');
  const tbImg    = document.getElementById('tbAvatarImg');
  const spin     = document.getElementById('uploadSpin');
  const pcAvs    = document.querySelectorAll('.pc-av');

  pfInput.addEventListener('change', function(){
    if (!this.files || !this.files[0]) return;
    const file = this.files[0];
    if (file.size > 5*1024*1024) { showToast('File too large — max 5 MB.', 'error'); return; }

    const reader = new FileReader();
    reader.onload = e => {
      if (tbImg) tbImg.src = e.target.result;
      else {
        tbAvatar.innerHTML = '<img src="'+e.target.result+'" style="width:100%;height:100%;object-fit:cover">';
      }
      pcAvs.forEach(a => {
        let img = a.querySelector('img');
        if (!img) {
          const ov = a.querySelector('.pc-av-overlay');
          a.innerHTML = '';
          img = document.createElement('img');
          img.style.cssText = 'width:100%;height:100%;object-fit:cover';
          a.appendChild(img);
          if (ov) a.appendChild(ov);
        }
        img.src = e.target.result;
      });
    };
    reader.readAsDataURL(file);

    spin.classList.add('on');
    const fd2 = new FormData(); fd2.append('profile_image', file);
    fetch('/Nepal-Travel/user/upload_profile.php', { method:'POST', body:fd2 })
      .then(r => r.json())
      .then(d => {
        spin.classList.remove('on');
        showToast(d.success ? 'Profile photo updated!' : (d.message || 'Upload failed.'), d.success ? 'success' : 'error');
      })
      .catch(err => { spin.classList.remove('on'); showToast(err.message || 'Upload failed.', 'error'); });
    this.value = '';
  });

  /* ── DELETE MODAL ───────────────────────────────────────────────────── */
  const modal        = document.getElementById('deleteModal');
  const openBtn      = document.getElementById('openDeleteModal');
  const closeBtn     = document.getElementById('closeDeleteModal');
  const delForm      = document.getElementById('deleteAccountForm');
  const delPwInput   = document.getElementById('deletePasswordInput');
  const delSubmitBtn = document.getElementById('delSubmitBtn');
  const delBtnLabel  = document.getElementById('delBtnLabel');
  const delError     = document.getElementById('delError');
  const delPwToggle  = document.getElementById('delPwToggle');
  const delEyeIcon   = document.getElementById('delEyeIcon');

  const eyeOpen  = 'M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z';
  const eyeClosed = 'M19.07 14.93A9.93 9.93 0 0 0 21 12C19.27 7.61 15 4.5 10 4.5c-1.31 0-2.56.24-3.72.67l1.53 1.53A7.5 7.5 0 0 1 10 6c3.86 0 7.13 2.33 8.66 5.7-.46 1.01-1.1 1.92-1.87 2.69l1.28 1.54zM14.54 9.47l-1.54-1.54A3 3 0 0 0 9.07 11.93l1.54 1.54A3 3 0 0 0 14.54 9.47zM3 4.27l1.45 1.45A9.96 9.96 0 0 0 1 12c1.73 4.39 6 7.5 11 7.5 1.68 0 3.28-.37 4.72-1.02L18.73 20.5 20 19.27 4.27 3 3 4.27zm7 7l1.26 1.26A1.5 1.5 0 0 1 10 13.5a1.5 1.5 0 0 1-1.5-1.5 1.5 1.5 0 0 1 .5-1.23zm4.14 4.14-.71-.7A3 3 0 0 1 10 15a3 3 0 0 1-3-3 3 3 0 0 1 .29-1.26l-.7-.71A4.5 4.5 0 0 0 10 16.5a4.5 4.5 0 0 0 3.14-1.09z';

  function openDeleteModal() {
    modal.classList.add('open');
    delPwInput.value = '';
    delError.classList.remove('show');
    delSubmitBtn.disabled = false;
    delBtnLabel.textContent = 'Yes, Delete Forever';
    setTimeout(() => delPwInput.focus(), 120);
  }
  function closeDeleteModal() { modal.classList.remove('open'); }

  if (openBtn)  openBtn.addEventListener('click', openDeleteModal);
  if (closeBtn) closeBtn.addEventListener('click', closeDeleteModal);
  modal.addEventListener('click', function(e) { if (e.target === modal) closeDeleteModal(); });
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && modal.classList.contains('open')) closeDeleteModal();
  });

  delPwToggle.addEventListener('click', function() {
    const isPass = delPwInput.type === 'password';
    delPwInput.type = isPass ? 'text' : 'password';
    delEyeIcon.querySelector('path').setAttribute('d', isPass ? eyeClosed : eyeOpen);
    delPwInput.focus();
  });

  if (delForm) {
    delForm.addEventListener('submit', function(e) {
      if (!delPwInput.value.trim()) {
        e.preventDefault();
        delError.classList.add('show');
        delPwInput.focus();
        return;
      }
      delError.classList.remove('show');
      delSubmitBtn.disabled = true;
      delBtnLabel.textContent = 'Deleting…';
    });
  }

  if (delPwInput) {
    delPwInput.addEventListener('input', function() {
      if (this.value.trim()) delError.classList.remove('show');
    });
  }


})();
</script>


</body>
</html>