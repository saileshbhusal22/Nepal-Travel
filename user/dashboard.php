<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php'); exit;
}
require_once '../config/db.php';

$stmt = $conn->prepare("SELECT id, full_name, username, email, profile_image, created_at FROM users WHERE id = ?");
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

// ── COUNTS FOR HERO STATS ──────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($bookingCount);
$stmt->fetch();
$stmt->close();

$savedCount = count($_SESSION['saved_deals'] ?? []);
// ──────────────────────────────────────────────────────────────────────────

$settings_message = ''; $settings_msg_type = '';
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

$booking_action_message = ''; $booking_action_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_action'])) {
    $bid = (int)($_POST['booking_id'] ?? 0); $action = $_POST['booking_action'];
    $chk = $conn->prepare("SELECT id, status FROM bookings WHERE id = ? AND user_id = ?");
    $chk->bind_param("ii", $bid, $_SESSION['user_id']); $chk->execute();
    $bk = $chk->get_result()->fetch_assoc(); $chk->close();
    if (!$bk) { $booking_action_message='Invalid booking.'; $booking_action_type='error'; }
    elseif ($action === 'update_guests') {
        $ng = (int)($_POST['guests'] ?? 0);
        if ($ng<1||$ng>50) { $booking_action_message='Guests 1-50.'; $booking_action_type='error'; }
        elseif ($bk['status']==='cancelled') { $booking_action_message='Booking is cancelled.'; $booking_action_type='error'; }
        else {
            $u = $conn->prepare("UPDATE bookings SET guests=? WHERE id=?");
            $u->bind_param("ii",$ng,$bid);
            if ($u->execute()) { $booking_action_message='Guests updated!'; $booking_action_type='success'; }
            else { $booking_action_message='DB error.'; $booking_action_type='error'; }
            $u->close();
        }
    } elseif ($action === 'cancel') {
        if ($bk['status']==='cancelled') { $booking_action_message='Already cancelled.'; $booking_action_type='error'; }
        else {
            $c = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE id=?");
            $c->bind_param("i",$bid);
            if ($c->execute()) { $booking_action_message='Booking cancelled.'; $booking_action_type='success'; }
            else { $booking_action_message='DB error.'; $booking_action_type='error'; }
            $c->close();
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
.tb-logo{
  font-family:var(--ff-serif);font-size:20px;font-weight:700;
  color:var(--snow);display:flex;align-items:center;gap:14px;
}
.tb-logo em{font-style:italic;color:var(--sand)}
.nepal-flag{display:flex;flex-direction:column;gap:1px;flex-shrink:0;}
.flag-top{width:0;height:0;border-left:11px solid transparent;border-right:11px solid transparent;border-bottom:14px solid var(--flag-r);}
.flag-bot{width:0;height:0;border-left:11px solid transparent;border-right:11px solid transparent;border-bottom:10px solid #2563A8;}
.tb-right{display:flex;align-items:center;gap:20px}
.tb-back{font-size:12px;color:rgba(255,255,255,0.35);letter-spacing:0.5px;font-weight:400;transition:color 0.2s;}
.tb-back:hover{color:var(--sand)}
.tb-user-pill{
  display:flex;align-items:center;gap:10px;
  background:rgba(255,255,255,0.06);
  border:1px solid rgba(196,168,130,0.2);
  border-radius:100px;padding:5px 14px 5px 5px;
  cursor:pointer;transition:border-color 0.2s;
}
.tb-user-pill:hover{border-color:rgba(196,168,130,0.5)}
.tb-avatar{
  width:32px;height:32px;border-radius:50%;
  background:var(--bark);overflow:hidden;
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;position:relative;
}
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
.hero::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse at center, transparent 40%, rgba(43,38,32,0.45) 100%);
  pointer-events:none;z-index:1;
}
.hero-grid{display:grid;grid-template-columns:1fr auto;gap:40px;align-items:end;position:relative;z-index:2;margin-bottom:40px;}
.hero-eyebrow{font-family:var(--ff-mono);font-size:10px;letter-spacing:3px;color:var(--sand);text-transform:uppercase;margin-bottom:14px;opacity:0.9;}
.hero-h1{font-family:var(--ff-serif);font-size:46px;font-weight:700;color:var(--snow);line-height:1.1;text-shadow:0 2px 12px rgba(0,0,0,0.4);}
.hero-h1 em{font-style:italic;color:var(--ember2)}
.hero-sub{font-size:14px;color:rgba(255,255,255,0.55);margin-top:12px;line-height:1.7;font-weight:300;}
.hero-right{text-align:right;flex-shrink:0}
.hr-since-label{font-family:var(--ff-mono);font-size:10px;color:rgba(255,255,255,0.35);letter-spacing:2px;text-transform:uppercase;}
.hr-since-val{font-family:var(--ff-serif);font-size:18px;color:var(--sand);margin-top:4px;}
.hr-actions{margin-top:20px;display:flex;gap:10px;justify-content:flex-end}
.hero-stats-strip{
  display:flex;border-top:1px solid rgba(255,255,255,0.10);
  position:relative;z-index:2;
  background:rgba(0,0,0,0.25);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);
}
.hss{flex:1;padding:20px 28px;border-right:1px solid rgba(255,255,255,0.08);transition:background 0.2s;}
.hss:last-child{border-right:none}
.hss:hover{background:rgba(255,255,255,0.06)}
.hss-n{font-family:var(--ff-serif);font-size:36px;font-weight:700;color:var(--snow);line-height:1;margin-bottom:5px;}
.hss-l{font-size:10px;letter-spacing:2.5px;text-transform:uppercase;color:rgba(255,255,255,0.4);font-weight:500;}

/* TAB BAR */
.tabbar{
  background:var(--mist);border-bottom:2px solid rgba(196,168,130,0.5);
  padding:0 52px;display:flex;align-items:flex-end;gap:2px;
  position:sticky;top:62px;z-index:100;box-shadow:0 2px 10px rgba(43,38,32,0.07);
}
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
.qa-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:48px;}
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
.bk-id{font-family:var(--ff-mono);font-size:11px;color:var(--soil)}
.bk-dest{font-weight:600}
.pill{display:inline-block;padding:4px 12px;border-radius:20px;font-size:10px;font-weight:600;letter-spacing:1px;text-transform:uppercase;}
.pill-pending{background:#FEF3E2;color:#9A6200}
.pill-confirmed{background:#E5F2E5;color:#2A5C2A}
.pill-cancelled{background:#FCEAEA;color:#8C2020}
.bk-btns{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.btn-tkt{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:500;color:var(--stone);border:1px solid var(--mist);border-radius:4px;padding:5px 12px;transition:all 0.2s;}
.btn-tkt:hover{background:var(--stone);color:var(--snow);border-color:var(--stone)}
.btn-cancbk{font-size:11px;font-weight:500;color:var(--flag-r);border:1px solid rgba(192,57,43,0.25);border-radius:4px;padding:5px 12px;background:transparent;cursor:pointer;transition:all 0.2s;}
.btn-cancbk:hover{background:var(--flag-r);color:#fff;border-color:var(--flag-r)}
.gf{display:inline-flex;align-items:center;gap:8px}
.gf input{width:60px;padding:5px 8px;border-radius:4px;border:1px solid var(--mist);font-size:13px;text-align:center;background:var(--fog);color:var(--stone);}
.gf input:focus{outline:none;border-color:var(--moss)}
.btn-upd{font-size:11px;font-weight:500;color:var(--forest);border:1px solid rgba(45,74,45,0.3);border-radius:4px;padding:5px 12px;background:transparent;cursor:pointer;transition:all 0.2s;}
.btn-upd:hover{background:var(--forest);color:#fff}
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
    <div class="hss"><div class="hss-n">0</div><div class="hss-l">Trips Taken</div></div>
    <div class="hss"><div class="hss-n"><?= $bookingCount ?></div><div class="hss-l">Bookings</div></div>
    <div class="hss"><div class="hss-n"><?= $savedCount ?></div><div class="hss-l">Saved Places</div></div>
    <div class="hss"><div class="hss-n">0</div><div class="hss-l">Peaks Explored</div></div>
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
    <a href="/Nepal-Travel/Public/events.php" class="qa">
      <div class="qa-accent"></div>
      <div class="qa-ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg></div>
      <div class="qa-t">Events &amp; Festivals</div><div class="qa-s">Culture &amp; tradition</div>
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
  $stmt = $conn->prepare("SELECT id, destination, date, guests, status, created_at FROM bookings WHERE user_id = ? ORDER BY created_at DESC");
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
          <thead><tr><th>ID</th><th>Destination</th><th>Travel Date</th><th>Guests</th><th>Status</th><th>Booked</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($bookings as $b): ?>
            <tr>
              <td class="bk-id">#<?php echo str_pad($b['id'],6,'0',STR_PAD_LEFT); ?></td>
              <td class="bk-dest"><?php echo htmlspecialchars($b['destination']); ?></td>
              <td><?php echo date('M j, Y', strtotime($b['date'])); ?></td>
              <td><?php if ($b['status']!=='cancelled'): ?>
                <form method="POST" action="?tab=bookings" class="gf">
                  <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                  <input type="hidden" name="booking_action" value="update_guests">
                  <input type="number" name="guests" value="<?php echo $b['guests']; ?>" min="1" max="50">
                  <button type="submit" class="btn-upd">Save</button>
                </form>
              <?php else: ?><span style="color:var(--soil)"><?php echo $b['guests']; ?></span><?php endif; ?></td>
              <td><span class="pill pill-<?php echo $b['status']; ?>"><?php echo ucfirst($b['status']); ?></span></td>
              <td style="color:var(--soil)"><?php echo date('M j, Y', strtotime($b['created_at'])); ?></td>
              <td>
                <div class="bk-btns">
                  <a href="/Nepal-Travel/Public/ticket.php?id=<?php echo $b['id']; ?>" class="btn-tkt">
                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:12px;height:12px"><path d="M22 10V6c0-1.11-.9-2-2-2H4c-1.1 0-1.99.89-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-1.99 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2z"/></svg>
                    Ticket
                  </a>
                  <?php if ($b['status']!=='cancelled'): ?>
                    <form method="POST" action="?tab=bookings" style="display:inline">
                      <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                      <input type="hidden" name="booking_action" value="cancel">
                      <button type="submit" class="btn-cancbk" onclick="return confirm('Cancel this booking?')">Cancel</button>
                    </form>
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
    $saved_ids = $_SESSION['saved_deals'] ?? [];
    $saved_deals = [];
    if (!empty($saved_ids)) {
        $ids = implode(',', array_map('intval', $saved_ids));
        $result = $conn->query("SELECT * FROM deals WHERE id IN ($ids)");
        $saved_deals = $result->fetch_all(MYSQLI_ASSOC);
    }
?>

  <div class="sh">
    <span class="sh-title">Saved Places</span>
    <div class="sh-rule"></div>
    <a href="/Nepal-Travel/Public/deals-and-packages.php" class="sh-link">Browse all →</a>
  </div>

  <?php if (empty($saved_deals)): ?>
    <div class="saved-cards">
      <div class="saved-ph"><div class="saved-ph-glyph">⛺</div><p>Save trekking camps &amp; base camps</p></div>
      <div class="saved-ph"><div class="saved-ph-glyph">🏔</div><p>Bookmark peak routes &amp; trails</p></div>
      <div class="saved-ph"><div class="saved-ph-glyph">🕌</div><p>Favourite temples &amp; heritage sites</p></div>
    </div>
    <div class="cta-row">
      <a href="/Nepal-Travel/Public/deals-and-packages.php" class="btn-plan">Explore &amp; Save Places →</a>
    </div>

  <?php else: ?>
    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:22px; margin-bottom:40px;">
      <?php foreach ($saved_deals as $deal): ?>
      <div style="background:var(--snow); border:1px solid var(--mist); border-radius:12px; overflow:hidden; display:flex; flex-direction:column; transition:transform 0.2s,box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 32px rgba(43,38,32,0.12)'" onmouseout="this.style.transform='';this.style.boxShadow=''">

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

          <a href="/Nepal-Travel/pages/deal-details.php?id=<?= (int)$deal['id'] ?>" style="font-family:var(--ff-serif); font-size:17px; font-weight:700; color:var(--stone); text-decoration:none; line-height:1.3;">
            <?= htmlspecialchars($deal['title']) ?>
          </a>

          <div style="display:flex; gap:14px; font-size:12px; color:var(--soil); margin-top:2px;">
            <?php if (!empty($deal['days'])): ?>
            <span>📅 <?= (int)$deal['days'] ?> days</span>
            <?php endif; ?>
            <?php if (!empty($deal['rating'])): ?>
            <span>⭐ <?= number_format((float)$deal['rating'], 1) ?></span>
            <?php endif; ?>
          </div>

          <div style="margin-top:auto; padding-top:14px; border-top:1px solid var(--mist); display:flex; justify-content:space-between; align-items:center;">
            <span style="font-family:var(--ff-serif); font-size:16px; font-weight:700; color:var(--stone);">
              NPR <?= number_format((float)$deal['price']) ?>
            </span>
            <a href="/Nepal-Travel/Public/save_deal.php?id=<?= (int)$deal['id'] ?>&action=remove"
               style="font-size:11px; font-weight:600; color:var(--flag-r); background:rgba(192,57,43,0.08); border:1px solid rgba(192,57,43,0.2); border-radius:4px; padding:6px 14px; text-decoration:none;"
               onmouseover="this.style.background='var(--flag-r)';this.style.color='#fff'"
               onmouseout="this.style.background='rgba(192,57,43,0.08)';this.style.color='var(--flag-r)'">
               ✕ Remove
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php elseif ($activeTab === 'settings'): ?>

  <?php if (!empty($settings_message)): ?>
    <div class="alert alert-<?php echo $settings_msg_type; ?>">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="<?php echo $settings_msg_type==='success'?'M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z':'M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z';?>"/></svg>
      <?php echo htmlspecialchars($settings_message); ?>
    </div>
  <?php endif; ?>

  <div class="sh" style="margin-top:0"><span class="sh-title">Account Settings</span><div class="sh-rule"></div></div>

  <div class="stg-card">
    <div class="stg-hd"><h3>Update Name</h3><p>Change how your name appears across the site</p></div>
    <div class="stg-body">
      <form method="POST" action="?tab=settings">
        <div class="fg"><label class="flbl">Full Name</label><input type="text" name="full_name" class="fin" value="<?php echo $userName; ?>" required></div>
        <button type="submit" name="update_name" class="btn-sv">Save Changes</button>
      </form>
    </div>
  </div>

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

  <div class="stg-card" style="opacity:0.6">
    <div class="stg-hd"><h3>Email Address</h3><p>Contact support if you need to change your email</p></div>
    <div class="stg-body">
      <div class="fg"><label class="flbl">Email</label><input type="email" class="fin" value="<?php echo $userEmail; ?>" disabled></div>
    </div>
  </div>

<?php endif; ?>

</div><!-- /content -->

<div class="toast" id="toast"></div>

<script>
(function(){
  const pfInput  = document.getElementById('pfInput');
  const tbAvatar = document.getElementById('tbAvatar');
  const tbImg    = document.getElementById('tbAvatarImg');
  const spin     = document.getElementById('uploadSpin');
  const toast    = document.getElementById('toast');
  const pcAvs    = document.querySelectorAll('.pc-av');

  pfInput.addEventListener('change', function(){
    if (!this.files || !this.files[0]) return;
    const file = this.files[0];
    if (file.size > 5*1024*1024){ showToast('File too large — max 5 MB.','error'); return; }

    const reader = new FileReader();
    reader.onload = e => {
      if (tbImg) tbImg.src = e.target.result;
      else {
        tbAvatar.innerHTML = '<img src="'+e.target.result+'" style="width:100%;height:100%;object-fit:cover">';
      }
      pcAvs.forEach(a => {
        let img = a.querySelector('img');
        if (!img){
          const ov = a.querySelector('.pc-av-overlay');
          a.innerHTML = '';
          img = document.createElement('img');
          img.style.cssText = 'width:100%;height:100%;object-fit:cover';
          a.appendChild(img);
          if(ov) a.appendChild(ov);
        }
        img.src = e.target.result;
      });
    };
    reader.readAsDataURL(file);

    spin.classList.add('on');
    const fd = new FormData(); fd.append('profile_image', file);
    fetch('/Nepal-Travel/user/upload_profile.php',{method:'POST',body:fd})
      .then(r=>r.json())
      .then(d=>{
        spin.classList.remove('on');
        showToast(d.success ? 'Profile photo updated!' : (d.message||'Upload failed.'), d.success?'success':'error');
      })
      .catch(e=>{ spin.classList.remove('on'); showToast(e.message||'Upload failed.','error'); });
    this.value='';
  });

  function showToast(msg,type){
    toast.textContent = msg;
    toast.className = 'toast '+type+' show';
    setTimeout(()=>toast.classList.remove('show'),3500);
  }
})();
</script>
</body>
</html>