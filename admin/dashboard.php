<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// ── Auth guard ───────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php'); exit;
}

// ── Ensure role is loaded into session from DB ───────────────────────────────
if (!isset($_SESSION['role'])) {
    $uid_check = (int)$_SESSION['user_id'];
    $role_result = $conn->query("SELECT role FROM users WHERE id = $uid_check");
    if ($role_result && $role_row = $role_result->fetch_assoc()) {
        $_SESSION['role'] = $role_row['role'];
    }
}

if ($_SESSION['role'] !== 'admin') {
    header('Location: /Nepal-Travel/user/login.php'); exit;
}

$admin_id = (int)$_SESSION['user_id'];

// ── CRITICAL: Write and close session BEFORE any AJAX-heavy page loads ───────
// This prevents the session file from being locked during the page request,
// which would cause concurrent AJAX calls (chat polling) to block each other
// and potentially cause logout on reload.
// We only do this after all POST actions are handled below.

// ── Auto-expire ──────────────────────────────────────────────────────────────
$conn->query("UPDATE user_deals SET status='expired' WHERE status='approved' AND visible_until IS NOT NULL AND visible_until < NOW()");
$conn->query("UPDATE user_subscriptions SET status='expired' WHERE status='active' AND expires_at IS NOT NULL AND expires_at < NOW()");

// ════════════════════════════════════════════════════════════════════════════
//  ALL POST ACTIONS — must be before any output
// ════════════════════════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $bid    = (int)$_POST['booking_id'];
    $stmt   = $conn->prepare("UPDATE bookings SET status=? WHERE id=?");
    $status = $_POST['status'];
    $stmt->bind_param("si", $status, $bid);
    $stmt->execute();
    $stmt->close();
    header('Location: dashboard.php?tab=bookings&updated=1'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_booking'])) {
    $bid  = (int)$_POST['booking_id'];
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id=?");
    $stmt->bind_param("i", $bid);
    $stmt->execute();
    $stmt->close();
    header('Location: dashboard.php?tab=bookings&deleted_booking=1'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all_bookings'])) {
    $conn->query("DELETE FROM bookings");
    header('Location: dashboard.php?tab=bookings&deleted_all=1'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid  = (int)$_POST['user_id'];
    $stmt = $conn->prepare("DELETE FROM bookings WHERE user_id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->close();
    header('Location: dashboard.php?tab=users&deleted=1'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'approve_sub') {
    $sub_id  = (int)$_POST['sub_id'];
    $sub_row = $conn->query("SELECT us.*, sp.duration_days FROM user_subscriptions us JOIN subscription_plans sp ON sp.id = us.plan_id WHERE us.id = $sub_id");
    if ($sub_row && ($sub = $sub_row->fetch_assoc())) {
        $starts  = date('Y-m-d H:i:s');
        $expires = date('Y-m-d H:i:s', strtotime("+{$sub['duration_days']} days"));
        $stmt    = $conn->prepare("UPDATE user_subscriptions SET status='active', starts_at=?, expires_at=?, approved_by=?, approved_at=NOW() WHERE id=?");
        $stmt->bind_param("ssii", $starts, $expires, $admin_id, $sub_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: dashboard.php?tab=subscriptions&msg=' . urlencode('✓ Subscription activated!') . '&mt=success'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reject_sub') {
    $sub_id = (int)$_POST['sub_id'];
    $stmt   = $conn->prepare("UPDATE user_subscriptions SET status='cancelled' WHERE id=?");
    $stmt->bind_param("i", $sub_id);
    $stmt->execute();
    $stmt->close();
    header('Location: dashboard.php?tab=subscriptions&msg=' . urlencode('Subscription rejected.') . '&mt=error'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'approve_deal') {
    $deal_id = (int)$_POST['deal_id'];
    $drow    = $conn->query("SELECT ud.id, sp.duration_days FROM user_deals ud JOIN user_subscriptions us ON us.id = ud.subscription_id JOIN subscription_plans sp ON sp.id = us.plan_id WHERE ud.id = $deal_id");
    if ($drow && ($d = $drow->fetch_assoc())) {
        $from  = date('Y-m-d H:i:s');
        $until = date('Y-m-d H:i:s', strtotime("+{$d['duration_days']} days"));
        $stmt  = $conn->prepare("UPDATE user_deals SET status='approved', visible_from=?, visible_until=?, approved_by=?, approved_at=NOW() WHERE id=?");
        $stmt->bind_param("ssii", $from, $until, $admin_id, $deal_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: dashboard.php?tab=subscriptions&msg=' . urlencode('✓ Deal approved and published!') . '&mt=success'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reject_deal') {
    $deal_id = (int)$_POST['deal_id'];
    $reason  = trim($_POST['reason'] ?? 'Does not meet our guidelines.');
    $stmt    = $conn->prepare("UPDATE user_deals SET status='rejected', rejection_reason=? WHERE id=?");
    $stmt->bind_param("si", $reason, $deal_id);
    $stmt->execute();
    $stmt->close();
    header('Location: dashboard.php?tab=subscriptions&msg=' . urlencode('Deal rejected.') . '&mt=error'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_deal') {
    $deal_id = (int)$_POST['deal_id'];
    $stmt    = $conn->prepare("DELETE FROM user_deals WHERE id=?");
    $stmt->bind_param("i", $deal_id);
    $stmt->execute();
    $stmt->close();
    header('Location: dashboard.php?tab=subscriptions&msg=' . urlencode('Deal deleted.') . '&mt=success'); exit;
}

// ════════════════════════════════════════════════════════════════════════════
//  DATA FETCHING
// ════════════════════════════════════════════════════════════════════════════

$activeTab = $_GET['tab'] ?? 'overview';

$total_users    = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_bookings = $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0];
$confirmed      = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetch_row()[0];
$cancelled      = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='booking cancel' OR status='cancelled'")->fetch_row()[0];
$pending        = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='active'")->fetch_row()[0];

$users_result = $conn->query("SELECT id, full_name, username, email, phone, email_verified, phone_verified, created_at, profile_image FROM users ORDER BY id DESC");
$users        = $users_result ? $users_result->fetch_all(MYSQLI_ASSOC) : [];

$bookings_result = $conn->query("SELECT b.id, b.user_id, b.name, b.destination, b.date, b.guests, b.status, b.created_at, u.full_name, u.email FROM bookings b LEFT JOIN users u ON b.user_id = u.id ORDER BY b.id DESC");
$bookings        = $bookings_result ? $bookings_result->fetch_all(MYSQLI_ASSOC) : [];

$subs_result = $conn->query("SELECT us.*, sp.name AS plan_name, sp.display_name, sp.duration_days, sp.deal_limit, u.full_name AS user_name, u.email AS user_email FROM user_subscriptions us JOIN subscription_plans sp ON sp.id = us.plan_id LEFT JOIN users u ON u.id = us.user_id ORDER BY us.created_at DESC");
$subs        = $subs_result ? $subs_result->fetch_all(MYSQLI_ASSOC) : [];

$deals_result = $conn->query("SELECT ud.*, u.full_name AS user_name, u.email AS user_email, sp.display_name AS plan_display, sp.duration_days FROM user_deals ud LEFT JOIN users u ON u.id = ud.user_id LEFT JOIN user_subscriptions us ON us.id = ud.subscription_id LEFT JOIN subscription_plans sp ON sp.id = us.plan_id ORDER BY ud.created_at DESC");
$deals        = $deals_result ? $deals_result->fetch_all(MYSQLI_ASSOC) : [];

$total_deal_reviews      = (int)($conn->query("SELECT COUNT(*) FROM deal_reviews")->fetch_row()[0] ?? 0);
$total_user_deal_reviews = (int)($conn->query("SELECT COUNT(*) FROM user_deal_reviews")->fetch_row()[0] ?? 0);
$total_reviews           = $total_deal_reviews + $total_user_deal_reviews;

$pending_subs  = count(array_filter($subs,  fn($s) => $s['status'] === 'pending'));
$pending_deals = count(array_filter($deals, fn($d) => $d['status'] === 'pending'));
$sub_revenue   = array_sum(array_column(array_filter($subs, fn($s) => in_array($s['status'], ['active','expired'])), 'amount_paid'));

// ── Chat unread count ────────────────────────────────────────────────────────
$chat_unread_result = $conn->query("SELECT COUNT(*) FROM chat_messages WHERE sender='user' AND is_read=0");
$chat_unread_count  = $chat_unread_result ? (int)$chat_unread_result->fetch_row()[0] : 0;

// ── Flash message ────────────────────────────────────────────────────────────
$msg = ''; $msg_type = 'success';
if (isset($_GET['msg']))             { $msg = $_GET['msg']; $msg_type = $_GET['mt'] ?? 'success'; }
if (isset($_GET['updated']))         { $msg = '✓ Booking status updated successfully.'; $msg_type = 'success'; }
if (isset($_GET['deleted']))         { $msg = '✓ User deleted successfully.'; $msg_type = 'success'; }
if (isset($_GET['deleted_booking'])) { $msg = '✓ Booking deleted successfully.'; $msg_type = 'success'; }
if (isset($_GET['deleted_all']))     { $msg = '✓ All bookings deleted successfully.'; $msg_type = 'success'; }

// ── Is chat tab active ───────────────────────────────────────────────────────
$isChatTab = ($activeTab === 'chat') ? 'true' : 'false';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nepal Travel — Admin Panel</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0C0E14;--surface:#13161F;--surface2:#1A1E2A;--surface3:#20253a;
  --border:rgba(255,255,255,0.07);--border2:rgba(255,255,255,0.12);
  --text:#F0EEE8;--muted:rgba(240,238,232,0.45);--muted2:rgba(240,238,232,0.25);
  --gold:#C9A227;--gold2:#E8C44A;
  --green:#2E7D52;--green2:#4CAF7D;
  --red:#8C2020;--red2:#E05555;
  --blue:#1E4A8C;--blue2:#4A90D9;
  --amber:#8C5A10;--amber2:#F0A030;
  --ff-d:'Syne',sans-serif;--ff-b:'DM Sans',sans-serif;--ff-m:'DM Mono',monospace;
}
html,body{height:100%;background:var(--bg);color:var(--text);font-family:var(--ff-b);-webkit-font-smoothing:antialiased}
a{text-decoration:none;color:inherit}
button,input,select,textarea{font-family:var(--ff-b)}
.admin-wrap{display:flex;min-height:100vh}
.sidebar{width:240px;flex-shrink:0;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto}
.sb-logo{padding:28px 24px 22px;border-bottom:1px solid var(--border)}
.sb-logo-title{font-family:var(--ff-d);font-size:17px;font-weight:800;color:var(--text);letter-spacing:-0.3px;display:flex;align-items:center;gap:10px}
.sb-logo-title em{font-style:italic;color:var(--gold)}
.sb-logo-sub{font-size:10px;color:var(--muted2);letter-spacing:2px;text-transform:uppercase;margin-top:4px;font-family:var(--ff-m)}
.sb-nav{padding:16px 12px;flex:1}
.sb-section-label{font-size:9px;letter-spacing:2.5px;text-transform:uppercase;color:var(--muted2);font-weight:600;padding:0 12px;margin:16px 0 8px;font-family:var(--ff-m)}
.sb-link{display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:8px;font-size:13px;font-weight:500;color:var(--muted);transition:all 0.18s;cursor:pointer;border:1px solid transparent}
.sb-link svg{width:16px;height:16px;flex-shrink:0;opacity:0.6}
.sb-link:hover{background:var(--surface2);color:var(--text);border-color:var(--border)}
.sb-link.on{background:rgba(201,162,39,0.1);color:var(--gold);border-color:rgba(201,162,39,0.2)}
.sb-link.on svg{opacity:1}
.sb-badge{margin-left:auto;background:rgba(201,162,39,0.15);color:var(--gold);font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;font-family:var(--ff-m)}
.sb-badge-alert{background:rgba(224,85,85,0.2);color:var(--red2)}
.sb-badge-chat{background:rgba(76,175,125,0.2);color:var(--green2)}
.sb-footer{padding:16px 24px;border-top:1px solid var(--border)}
.sb-avatar{width:34px;height:34px;border-radius:50%;background:rgba(201,162,39,0.2);border:1px solid rgba(201,162,39,0.3);display:flex;align-items:center;justify-content:center;font-family:var(--ff-d);font-size:13px;font-weight:700;color:var(--gold);flex-shrink:0}
.main{flex:1;display:flex;flex-direction:column;overflow:hidden}
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:0 36px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.tb-breadcrumb{font-family:var(--ff-m);font-size:11px;color:var(--muted2);letter-spacing:1px}
.tb-breadcrumb span{color:var(--gold)}
.tb-actions{display:flex;align-items:center;gap:14px}
.tb-tag{font-size:10px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;color:var(--green2);background:rgba(46,125,82,0.15);border:1px solid rgba(76,175,125,0.2);padding:5px 14px;border-radius:20px;font-family:var(--ff-m)}
.tb-time{font-size:11px;color:var(--muted2);font-family:var(--ff-m)}
.content{padding:36px;flex:1;overflow-y:auto}
.alert{display:flex;align-items:center;gap:12px;padding:13px 20px;border-radius:8px;font-size:13px;margin-bottom:24px;border-left:3px solid}
.alert-success{background:rgba(76,175,125,0.1);color:var(--green2);border-color:var(--green2)}
.alert-error{background:rgba(224,85,85,0.1);color:var(--red2);border-color:var(--red2)}
.sec-hd{display:flex;align-items:center;gap:20px;margin-bottom:28px}
.sec-hd-title{font-family:var(--ff-d);font-size:26px;font-weight:800;color:var(--text)}
.sec-hd-rule{flex:1;height:1px;background:var(--border2)}
.sec-hd-count{font-family:var(--ff-m);font-size:11px;color:var(--muted2);letter-spacing:1px}
.stats-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:36px}
.stats-grid-sub{grid-template-columns:repeat(4,1fr)}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:22px 20px;position:relative;overflow:hidden;transition:border-color 0.2s,transform 0.2s}
.stat-card:hover{border-color:var(--border2);transform:translateY(-2px)}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:var(--accent,var(--gold))}
.stat-card-n{font-family:var(--ff-d);font-size:38px;font-weight:800;color:var(--text);line-height:1;margin-bottom:6px}
.stat-card-l{font-size:11px;color:var(--muted);letter-spacing:1.5px;text-transform:uppercase;font-weight:600}
.stat-card-ico{position:absolute;top:18px;right:18px;font-size:22px;opacity:0.18}
.stat-pending-badge{display:inline-block;background:rgba(224,85,85,0.15);color:var(--red2);font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;margin-top:6px;font-family:var(--ff-m)}
.sub-tabs{display:flex;gap:4px;margin-bottom:1.5rem;background:var(--surface2);padding:4px;border-radius:10px;width:fit-content;border:1px solid var(--border)}
.sub-tab-btn{padding:9px 22px;border-radius:7px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all 0.15s;font-family:var(--ff-b);color:var(--muted);background:transparent;display:flex;align-items:center;gap:8px}
.sub-tab-btn.active{background:var(--gold);color:#000}
.sub-tab-badge{background:rgba(224,85,85,0.9);color:#fff;font-size:9px;font-weight:800;padding:2px 6px;border-radius:10px;font-family:var(--ff-m)}
.tcard{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:32px}
.tcard-hd{padding:18px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface2);flex-wrap:wrap;gap:10px}
.tcard-hd-title{font-family:var(--ff-d);font-size:15px;font-weight:700;color:var(--text)}
.tcard-hd-sub{font-size:11px;color:var(--muted2);font-family:var(--ff-m);margin-top:2px}
.tcard-search{padding:14px 24px;border-bottom:1px solid var(--border);background:var(--surface);display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.search-inp{flex:1;min-width:200px;padding:9px 14px;background:var(--surface2);border:1px solid var(--border2);border-radius:7px;font-size:13px;color:var(--text);outline:none;transition:border-color 0.2s}
.search-inp::placeholder{color:var(--muted2)}
.search-inp:focus{border-color:rgba(201,162,39,0.4)}
.filter-btns{display:flex;gap:8px;flex-wrap:wrap}
.filter-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;font-size:11px;font-weight:600;letter-spacing:0.5px;border:1px solid var(--border2);background:var(--surface2);color:var(--muted);cursor:pointer;transition:all 0.18s;font-family:var(--ff-m);white-space:nowrap}
.filter-btn:hover{color:var(--text)}
.filter-btn.active{background:rgba(201,162,39,0.12);color:var(--gold);border-color:rgba(201,162,39,0.3)}
.filter-btn .fb-dot{width:5px;height:5px;border-radius:50%;display:inline-block}
.filter-btn.active .fb-dot{background:var(--gold)}
.filter-btn:not(.active) .fb-dot{background:var(--muted2)}
.filter-btn.f-confirmed.active{background:rgba(46,125,82,0.15);color:var(--green2);border-color:rgba(76,175,125,0.3)}
.filter-btn.f-confirmed.active .fb-dot{background:var(--green2)}
.filter-btn.f-pending.active{background:rgba(140,90,16,0.15);color:var(--amber2);border-color:rgba(240,160,48,0.3)}
.filter-btn.f-pending.active .fb-dot{background:var(--amber2)}
.filter-btn.f-cancelled.active{background:rgba(140,32,32,0.15);color:var(--red2);border-color:rgba(224,85,85,0.3)}
.filter-btn.f-cancelled.active .fb-dot{background:var(--red2)}
.fb-count{background:rgba(255,255,255,0.07);color:inherit;border-radius:10px;padding:1px 7px;font-size:10px}
.tscroll{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead th{font-size:9px;letter-spacing:2.5px;text-transform:uppercase;color:var(--muted2);font-weight:700;padding:12px 20px;text-align:left;background:var(--surface2);border-bottom:1px solid var(--border);white-space:nowrap;font-family:var(--ff-m)}
tbody td{padding:13px 20px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover td{background:rgba(255,255,255,0.02)}
.mono{font-family:var(--ff-m);font-size:11px;color:var(--muted)}
.pill{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;font-family:var(--ff-m);white-space:nowrap}
.pill-confirmed,.pill-active,.pill-approved{background:rgba(46,125,82,0.2);color:var(--green2);border:1px solid rgba(76,175,125,0.2)}
.pill-cancelled,.pill-rejected{background:rgba(140,32,32,0.2);color:var(--red2);border:1px solid rgba(224,85,85,0.2)}
.pill-pending{background:rgba(140,90,16,0.2);color:var(--amber2);border:1px solid rgba(240,160,48,0.2)}
.pill-expired{background:rgba(255,255,255,0.05);color:var(--muted2);border:1px solid var(--border)}
.pill-verified{background:rgba(46,125,82,0.15);color:var(--green2);border:1px solid rgba(76,175,125,0.15)}
.pill-unverified{background:rgba(140,32,32,0.15);color:var(--red2);border:1px solid rgba(224,85,85,0.15)}
.dot{width:5px;height:5px;border-radius:50%;background:currentColor;display:inline-block}
.av{width:32px;height:32px;border-radius:50%;background:rgba(201,162,39,0.15);border:1px solid rgba(201,162,39,0.2);display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--gold);font-family:var(--ff-d);flex-shrink:0;overflow:hidden}
.av img{width:100%;height:100%;object-fit:cover}
.user-cell{display:flex;align-items:center;gap:10px}
.user-cell-name{font-size:13px;font-weight:600;color:var(--text)}
.user-cell-email{font-size:11px;color:var(--muted);font-family:var(--ff-m)}
.act-row{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:6px;font-size:11px;font-weight:700;border:1px solid;cursor:pointer;transition:all 0.15s;white-space:nowrap;background:transparent;font-family:var(--ff-b)}
.btn-approve{color:var(--green2);border-color:rgba(76,175,125,0.3)}
.btn-approve:hover{background:var(--green2);color:#000;border-color:var(--green2)}
.btn-reject{color:var(--red2);border-color:rgba(224,85,85,0.25)}
.btn-reject:hover{background:var(--red2);color:#fff;border-color:var(--red2)}
.btn-delete{color:var(--muted2);border-color:var(--border)}
.btn-delete:hover{background:var(--red2);color:#fff;border-color:var(--red2)}
.btn-view{color:var(--blue2);border-color:rgba(74,144,217,0.25)}
.btn-view:hover{background:var(--blue2);color:#fff;border-color:var(--blue2)}
.act-btn-del{color:var(--red2);border-color:rgba(224,85,85,0.25)}
.act-btn-del:hover{background:var(--red2);color:#fff;border-color:var(--red2)}
.status-sel{background:var(--surface2);color:var(--text);border:1px solid var(--border2);border-radius:6px;padding:5px 10px;font-size:12px;cursor:pointer;outline:none;font-family:var(--ff-m)}
.status-sel:focus{border-color:rgba(201,162,39,0.4)}
.save-btn{background:var(--gold);color:#000;border:none;border-radius:6px;padding:5px 14px;font-size:11px;font-weight:700;cursor:pointer;transition:background 0.15s}
.save-btn:hover{background:var(--gold2)}
.bk-actions-cell{min-width:200px}
.bk-status-form{display:flex;gap:6px;align-items:center;margin-bottom:6px}
.bk-delete-form{display:flex}
.bk-delete-form button{width:100%}
.deal-thumb{width:48px;height:36px;object-fit:cover;border-radius:5px;border:1px solid var(--border2)}
.deal-thumb-ph{width:48px;height:36px;border-radius:5px;background:var(--surface3);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:16px}
.ov-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:8px}
.mini-table tbody td{padding:10px 16px;font-size:12px}
.mini-table thead th{padding:10px 16px}
.empty{padding:50px;text-align:center;color:var(--muted2)}
.empty-ico{font-size:40px;opacity:0.2;margin-bottom:12px}
.empty p{font-size:13px}
.modal-bd{display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.8);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:1.5rem}
.modal-bd.open{display:flex}
.modal-box{background:#13161f;border:1px solid rgba(255,255,255,0.1);border-radius:18px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;box-shadow:0 40px 80px rgba(0,0,0,0.7);animation:mIn 0.2s ease}
@keyframes mIn{from{opacity:0;transform:translateY(16px) scale(0.97)}to{opacity:1;transform:none}}
.modal-hd{padding:1.3rem 1.5rem 1rem;border-bottom:1px solid rgba(255,255,255,0.07);display:flex;align-items:center;justify-content:space-between}
.modal-hd-title{font-family:var(--ff-d);font-size:15px;font-weight:700;color:var(--text)}
.modal-close{background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);color:var(--muted);width:30px;height:30px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:15px;transition:all 0.15s}
.modal-close:hover{background:rgba(224,85,85,0.2);color:#ff6b6b}
.modal-body{padding:1.4rem 1.5rem 1.6rem}
.flbl{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);font-weight:600;font-family:var(--ff-m);display:block;margin-bottom:7px}
.fin{background:var(--surface2);color:var(--text);border:1px solid var(--border2);border-radius:7px;padding:10px 14px;font-size:13px;font-family:var(--ff-b);outline:none;width:100%;transition:border-color 0.2s}
.fin:focus{border-color:rgba(224,85,85,0.4)}
.btn-confirm{width:100%;padding:12px;border:none;border-radius:8px;font-family:var(--ff-b);font-size:13px;font-weight:700;cursor:pointer;margin-top:14px;transition:all 0.15s}
.btn-confirm-red{background:var(--red2);color:#fff}
.btn-confirm-red:hover{background:#c93333}
.toast{position:fixed;bottom:28px;right:28px;background:var(--surface);border:1px solid var(--border2);color:var(--text);padding:13px 20px;border-radius:8px;font-size:13px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,0.4);transform:translateY(12px);opacity:0;transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1);z-index:9999;pointer-events:none;border-left:3px solid var(--green2)}
.toast.show{transform:translateY(0);opacity:1}
/* ── CHAT ── */
.chat-layout{display:grid;grid-template-columns:320px 1fr;gap:0;height:calc(100vh - 200px);min-height:500px;background:var(--surface);border:1px solid var(--border);border-radius:16px;overflow:hidden}
.chat-sidebar{border-right:1px solid var(--border);display:flex;flex-direction:column;background:var(--surface2)}
.chat-sidebar-hd{padding:18px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.chat-sidebar-title{font-family:var(--ff-d);font-size:14px;font-weight:700;color:var(--text)}
.chat-total-badge{background:rgba(201,162,39,0.15);color:var(--gold);font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px;font-family:var(--ff-m)}
.chat-search-wrap{padding:12px 16px;border-bottom:1px solid var(--border);flex-shrink:0}
.chat-search-inp{width:100%;background:var(--surface3);border:1px solid var(--border2);border-radius:8px;padding:8px 12px;font-size:12px;color:var(--text);outline:none;font-family:var(--ff-b);transition:border-color 0.2s}
.chat-search-inp::placeholder{color:var(--muted2)}
.chat-search-inp:focus{border-color:rgba(201,162,39,0.4)}
.conv-list{flex:1;overflow-y:auto;padding:8px}
.conv-list::-webkit-scrollbar{width:3px}
.conv-list::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.1);border-radius:3px}
.conv-item{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:10px;cursor:pointer;transition:all 0.15s;border:1px solid transparent;margin-bottom:2px}
.conv-item:hover{background:var(--surface3);border-color:var(--border)}
.conv-item.active{background:rgba(201,162,39,0.08);border-color:rgba(201,162,39,0.25)}
.conv-av{width:40px;height:40px;border-radius:50%;background:rgba(201,162,39,0.15);border:1px solid rgba(201,162,39,0.25);display:flex;align-items:center;justify-content:center;font-family:var(--ff-d);font-size:15px;font-weight:700;color:var(--gold);flex-shrink:0;overflow:hidden;position:relative}
.conv-av img{width:100%;height:100%;object-fit:cover}
.conv-online-dot{position:absolute;bottom:1px;right:1px;width:9px;height:9px;background:var(--green2);border-radius:50%;border:2px solid var(--surface2)}
.conv-info{flex:1;min-width:0}
.conv-name{font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.conv-preview{font-size:11px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px}
.conv-meta{display:flex;flex-direction:column;align-items:flex-end;gap:5px;flex-shrink:0}
.conv-time{font-size:10px;color:var(--muted2);font-family:var(--ff-m)}
.conv-unread-badge{background:var(--red2);color:#fff;font-size:9px;font-weight:800;min-width:18px;height:18px;border-radius:9px;display:flex;align-items:center;justify-content:center;padding:0 5px;font-family:var(--ff-m)}
.conv-empty{text-align:center;padding:50px 20px;color:var(--muted2);font-size:13px}
.conv-empty-ico{font-size:32px;opacity:0.3;margin-bottom:10px}
.chat-panel{display:flex;flex-direction:column;background:var(--bg)}
.chat-panel-hd{padding:16px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:14px;background:var(--surface);flex-shrink:0}
.chat-panel-user-info .name{font-size:14px;font-weight:700;color:var(--text)}
.chat-panel-user-info .email{font-size:11px;color:var(--muted);font-family:var(--ff-m);margin-top:2px}
.chat-panel-actions{margin-left:auto;display:flex;gap:8px}
.chat-panel-hd-empty{padding:16px 24px;border-bottom:1px solid var(--border);background:var(--surface);display:flex;align-items:center;justify-content:center;flex-shrink:0;min-height:60px}
.chat-msgs-area{flex:1;overflow-y:auto;padding:24px 20px;display:flex;flex-direction:column;gap:12px;scroll-behavior:smooth}
.chat-msgs-area::-webkit-scrollbar{width:4px}
.chat-msgs-area::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.1);border-radius:4px}
.chat-placeholder{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:var(--muted2);text-align:center;padding:40px}
.chat-placeholder-ico{font-size:48px;opacity:0.2;margin-bottom:16px}
.chat-placeholder h3{font-family:var(--ff-d);font-size:20px;font-weight:700;margin-bottom:8px;color:var(--muted)}
.chat-placeholder p{font-size:13px;line-height:1.7}
.msg-wrap{display:flex;flex-direction:column}
.msg-wrap.admin{align-items:flex-end}
.msg-wrap.user{align-items:flex-start}
.msg-bubble{max-width:70%;padding:10px 15px;border-radius:16px;font-size:13px;line-height:1.55;word-break:break-word;animation:bubbleIn 0.2s ease}
@keyframes bubbleIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
.msg-bubble.admin{background:linear-gradient(135deg,rgba(201,162,39,0.2),rgba(201,162,39,0.1));color:var(--text);border:1px solid rgba(201,162,39,0.2);border-bottom-right-radius:4px}
.msg-bubble.user{background:var(--surface2);color:var(--text);border:1px solid var(--border2);border-bottom-left-radius:4px}
.msg-time{font-size:10px;color:var(--muted2);margin-top:4px;padding:0 4px;font-family:var(--ff-m)}
.msg-wrap.admin .msg-time{text-align:right}
.msg-date-divider{text-align:center;margin:16px 0;position:relative}
.msg-date-divider::before{content:'';position:absolute;top:50%;left:0;right:0;height:1px;background:var(--border)}
.msg-date-divider span{position:relative;background:var(--bg);padding:0 12px;font-size:10px;color:var(--muted2);font-family:var(--ff-m);letter-spacing:1px;text-transform:uppercase}
.chat-footer{padding:14px 20px;border-top:1px solid var(--border);background:var(--surface);display:flex;gap:10px;align-items:flex-end;flex-shrink:0}
.chat-footer-disabled{padding:18px 20px;border-top:1px solid var(--border);background:var(--surface);text-align:center;color:var(--muted2);font-size:13px;flex-shrink:0}
.chat-inp{flex:1;background:var(--surface2);border:1.5px solid var(--border2);border-radius:10px;padding:10px 14px;font-size:13px;color:var(--text);outline:none;resize:none;max-height:120px;overflow-y:auto;font-family:var(--ff-b);line-height:1.5;transition:border-color 0.2s}
.chat-inp::placeholder{color:var(--muted2)}
.chat-inp:focus{border-color:rgba(201,162,39,0.4)}
.chat-send-btn{background:linear-gradient(135deg,var(--gold),#a07d1a);border:none;width:42px;height:42px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:transform 0.15s,box-shadow 0.15s;box-shadow:0 3px 12px rgba(201,162,39,0.3)}
.chat-send-btn:hover{transform:scale(1.08);box-shadow:0 5px 18px rgba(201,162,39,0.45)}
.chat-send-btn:active{transform:scale(0.95)}
.chat-send-btn svg{width:18px;height:18px;fill:#000}
.chat-loading{display:flex;align-items:center;justify-content:center;gap:5px;padding:40px;color:var(--muted2);font-size:12px}
.chat-loading span{width:6px;height:6px;border-radius:50%;background:var(--gold);animation:ldot 1.2s infinite}
.chat-loading span:nth-child(2){animation-delay:0.2s}
.chat-loading span:nth-child(3){animation-delay:0.4s}
@keyframes ldot{0%,60%,100%{transform:translateY(0);opacity:0.4}30%{transform:translateY(-6px);opacity:1}}
.btn-del-conv{background:transparent;border:1px solid rgba(224,85,85,0.25);color:var(--red2);padding:5px 12px;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;font-family:var(--ff-b);transition:all 0.15s}
.btn-del-conv:hover{background:var(--red2);color:#fff}
/* ── new-message indicator on chat tab ── */
.chat-new-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--red2);margin-left:6px;vertical-align:middle;animation:pulse 1.5s infinite}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.5;transform:scale(0.7)}}
@media(max-width:1200px){.stats-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:1024px){.sidebar{width:200px}.ov-grid{grid-template-columns:1fr}.chat-layout{grid-template-columns:260px 1fr}}
@media(max-width:900px){.chat-layout{grid-template-columns:1fr;height:auto}}
@media(max-width:768px){.sidebar{display:none}.stats-grid{grid-template-columns:repeat(2,1fr)}.content{padding:20px}.topbar{padding:0 20px}.tcard-search{flex-direction:column;align-items:stretch}.ov-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="admin-wrap">

  <!-- ══ SIDEBAR ══ -->
  <aside class="sidebar">
    <div class="sb-logo">
      <div class="sb-logo-title">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
          <path d="M12 2L2 19h20L12 2z" fill="rgba(201,162,39,0.3)" stroke="#C9A227" stroke-width="1.5"/>
        </svg>
        Nepal <em>Admin</em>
      </div>
      <div class="sb-logo-sub">Control Panel</div>
    </div>
    <nav class="sb-nav">
      <div class="sb-section-label">Navigation</div>
      <a href="?tab=overview" class="sb-link <?= $activeTab==='overview'?'on':'' ?>">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
        Overview
      </a>
      <a href="?tab=users" class="sb-link <?= $activeTab==='users'?'on':'' ?>">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        Users
        <span class="sb-badge"><?= $total_users ?></span>
      </a>
      <a href="?tab=bookings" class="sb-link <?= $activeTab==='bookings'?'on':'' ?>">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
        Bookings
        <span class="sb-badge"><?= $total_bookings ?></span>
      </a>
      <a href="?tab=subscriptions" class="sb-link <?= $activeTab==='subscriptions'?'on':'' ?>">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
        Subscriptions
        <?php if ($pending_subs + $pending_deals > 0): ?>
          <span class="sb-badge sb-badge-alert"><?= $pending_subs + $pending_deals ?></span>
        <?php else: ?>
          <span class="sb-badge"><?= count($subs) ?></span>
        <?php endif; ?>
      </a>
      <a href="?tab=chat" class="sb-link <?= $activeTab==='chat'?'on':'' ?>" id="chatSidebarLink">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
        Live Chat
        <?php if ($chat_unread_count > 0): ?>
          <span class="sb-badge sb-badge-alert" id="sidebarChatBadge"><?= $chat_unread_count ?></span>
        <?php else: ?>
          <span class="sb-badge sb-badge-chat" id="sidebarChatBadge" style="<?= $chat_unread_count > 0 ? '' : 'display:none' ?>"><?= $chat_unread_count ?></span>
        <?php endif; ?>
      </a>
      <a href="reviews.php" class="sb-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24zM12 15.4l-3.76 2.27 1-4.28-3.32-2.88 4.38-.38L12 6.1l1.71 4.04 4.38.38-3.32 2.88 1 4.28L12 15.4z"/></svg>
        Reviews
        <span class="sb-badge"><?= $total_reviews ?></span>
      </a>
      <div class="sb-section-label" style="margin-top:24px">Links</div>
      <a href="/Nepal-Travel/Public/index.php" class="sb-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        View Site
      </a>
      <a href="deals_crud.php" class="sb-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42z"/></svg>
        Deals &amp; Packages
      </a>
      <a href="/Nepal-Travel/user/logout.php" class="sb-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
        Sign Out
      </a>
    </nav>
    <div class="sb-footer">
      <div style="display:flex;align-items:center;gap:10px">
        <div class="sb-avatar">A</div>
        <div>
          <div style="font-size:12px;font-weight:600">Admin</div>
          <div style="font-size:10px;color:var(--muted2);font-family:var(--ff-m)">// Super Admin</div>
        </div>
      </div>
    </div>
  </aside>

  <!-- ══ MAIN ══ -->
  <div class="main">
    <header class="topbar">
      <div class="tb-breadcrumb">NEPAL TRAVEL / <span><?= strtoupper($activeTab) ?></span></div>
      <div class="tb-actions">
        <span class="tb-tag">● LIVE</span>
        <span class="tb-time" id="clock"></span>
      </div>
    </header>

    <div class="content">
      <?php if ($msg): ?>
        <div class="alert alert-<?= htmlspecialchars($msg_type) ?>"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <!-- ════ OVERVIEW ════ -->
      <?php if ($activeTab === 'overview'): ?>
        <div class="sec-hd">
          <h1 class="sec-hd-title">Overview</h1>
          <div class="sec-hd-rule"></div>
          <span class="sec-hd-count"><?= date('D, d M Y') ?></span>
        </div>
        <div class="stats-grid">
          <div class="stat-card" style="--accent:#C9A227"><div class="stat-card-ico">👥</div><div class="stat-card-n"><?= $total_users ?></div><div class="stat-card-l">Total Users</div></div>
          <div class="stat-card" style="--accent:#4A90D9"><div class="stat-card-ico">📋</div><div class="stat-card-n"><?= $total_bookings ?></div><div class="stat-card-l">Total Bookings</div></div>
          <div class="stat-card" style="--accent:#4CAF7D"><div class="stat-card-ico">✅</div><div class="stat-card-n"><?= $confirmed ?></div><div class="stat-card-l">Confirmed</div></div>
          <div class="stat-card" style="--accent:#F0A030"><div class="stat-card-ico">⏳</div><div class="stat-card-n"><?= $pending ?></div><div class="stat-card-l">Pending</div></div>
          <div class="stat-card" style="--accent:#E05555"><div class="stat-card-ico">❌</div><div class="stat-card-n"><?= $cancelled ?></div><div class="stat-card-l">Cancelled</div></div>
        </div>
        <div class="stats-grid stats-grid-sub" style="margin-bottom:24px">
          <div class="stat-card" style="--accent:#C9A227">
            <div class="stat-card-ico">💳</div><div class="stat-card-n"><?= count($subs) ?></div><div class="stat-card-l">Subscriptions</div>
            <?php if ($pending_subs > 0): ?><div class="stat-pending-badge"><?= $pending_subs ?> Pending</div><?php endif; ?>
          </div>
          <div class="stat-card" style="--accent:#4CAF7D">
            <div class="stat-card-ico">✅</div><div class="stat-card-n"><?= count(array_filter($subs, fn($s) => $s['status']==='active')) ?></div><div class="stat-card-l">Active Subscribers</div>
          </div>
          <div class="stat-card" style="--accent:#4A90D9">
            <div class="stat-card-ico">🏔️</div><div class="stat-card-n"><?= count($deals) ?></div><div class="stat-card-l">User Deals</div>
            <?php if ($pending_deals > 0): ?><div class="stat-pending-badge"><?= $pending_deals ?> Pending</div><?php endif; ?>
          </div>
          <div class="stat-card" style="--accent:#E8C44A">
            <div class="stat-card-ico">💰</div><div class="stat-card-n" style="font-size:22px;padding-top:4px">NPR <?= number_format($sub_revenue) ?></div><div class="stat-card-l">Sub Revenue</div>
          </div>
        </div>
        <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px">
          <div class="stat-card" style="--accent:#4CAF7D">
            <div class="stat-card-ico">💬</div><div class="stat-card-n"><?= $chat_unread_count ?></div><div class="stat-card-l">Unread Messages</div>
            <?php if ($chat_unread_count > 0): ?>
              <a href="?tab=chat" style="display:inline-block;margin-top:6px;font-size:10px;color:var(--green2);font-family:var(--ff-m)">Reply now →</a>
            <?php endif; ?>
          </div>
          <div class="stat-card" style="--accent:#E8C44A"><div class="stat-card-ico">⭐</div><div class="stat-card-n"><?= $total_reviews ?></div><div class="stat-card-l">Total Reviews</div></div>
          <div class="stat-card" style="--accent:#C9A227"><div class="stat-card-ico">🏔️</div><div class="stat-card-n"><?= $total_deal_reviews ?></div><div class="stat-card-l">Deal Reviews</div></div>
        </div>
        <div class="ov-grid">
          <div class="tcard">
            <div class="tcard-hd">
              <div><div class="tcard-hd-title">Recent Bookings</div><div class="tcard-hd-sub">Latest 5 entries</div></div>
              <a href="?tab=bookings" style="font-size:11px;color:var(--gold)">View all →</a>
            </div>
            <div class="tscroll">
              <table class="mini-table">
                <thead><tr><th>ID</th><th>Name</th><th>Destination</th><th>Status</th></tr></thead>
                <tbody>
                  <?php foreach(array_slice($bookings,0,5) as $b):
                    $s   = strtolower($b['status']);
                    $cls = str_contains($s,'confirm') ? 'confirmed' : (str_contains($s,'cancel') ? 'cancelled' : 'pending');
                  ?>
                  <tr>
                    <td class="mono">#<?= str_pad($b['id'],4,'0',STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($b['name'] ?? $b['full_name'] ?? '—') ?></td>
                    <td style="color:var(--muted)"><?= htmlspecialchars($b['destination']) ?></td>
                    <td><span class="pill pill-<?= $cls ?>"><span class="dot"></span><?= htmlspecialchars(ucfirst($b['status'])) ?></span></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <div class="tcard">
            <div class="tcard-hd">
              <div><div class="tcard-hd-title">Recent Users</div><div class="tcard-hd-sub">Latest 5 registered</div></div>
              <a href="?tab=users" style="font-size:11px;color:var(--gold)">View all →</a>
            </div>
            <div class="tscroll">
              <table class="mini-table">
                <thead><tr><th>User</th><th>Username</th><th>Verified</th></tr></thead>
                <tbody>
                  <?php foreach(array_slice($users,0,5) as $u): ?>
                  <tr>
                    <td>
                      <div class="user-cell">
                        <div class="av">
                          <?php if(!empty($u['profile_image']) && $u['profile_image']!=='default.png'): ?>
                            <img src="/Nepal-Travel/<?= ltrim($u['profile_image'],'/') ?>" alt="">
                          <?php else: ?>
                            <?= strtoupper(substr($u['full_name'],0,1)) ?>
                          <?php endif; ?>
                        </div>
                        <div class="user-cell-name"><?= htmlspecialchars($u['full_name']) ?></div>
                      </div>
                    </td>
                    <td class="mono">@<?= htmlspecialchars($u['username']) ?></td>
                    <td>
                      <?php if($u['email_verified']): ?>
                        <span class="pill pill-verified"><span class="dot"></span>Yes</span>
                      <?php else: ?>
                        <span class="pill pill-unverified"><span class="dot"></span>No</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <?php if ($pending_subs > 0 || $pending_deals > 0): ?>
        <div class="tcard" style="border-color:rgba(201,162,39,0.25);margin-top:20px">
          <div class="tcard-hd" style="background:rgba(201,162,39,0.05)">
            <div>
              <div class="tcard-hd-title">⚠️ Pending Review</div>
              <div class="tcard-hd-sub"><?= $pending_subs ?> subscription(s) · <?= $pending_deals ?> deal(s) awaiting action</div>
            </div>
            <a href="?tab=subscriptions" style="font-size:11px;color:var(--gold);font-weight:700">Review Now →</a>
          </div>
        </div>
        <?php endif; ?>

      <!-- ════ USERS ════ -->
      <?php elseif ($activeTab === 'users'): ?>
        <div class="sec-hd">
          <h1 class="sec-hd-title">All Users</h1>
          <div class="sec-hd-rule"></div>
          <span class="sec-hd-count"><?= count($users) ?> TOTAL</span>
        </div>
        <div class="tcard">
          <div class="tcard-hd">
            <div><div class="tcard-hd-title">Registered Users</div><div class="tcard-hd-sub">All accounts in the system</div></div>
          </div>
          <div class="tcard-search">
            <input type="text" class="search-inp" id="userSearch" placeholder="Search by name, username or email…" oninput="filterTable('userSearch','usersTable')">
          </div>
          <div class="tscroll">
            <table id="usersTable">
              <thead><tr><th>ID</th><th>User</th><th>Username</th><th>Phone</th><th>Email Verified</th><th>Phone Verified</th><th>Joined</th><th>Actions</th></tr></thead>
              <tbody>
                <?php if(empty($users)): ?>
                  <tr><td colspan="8"><div class="empty"><div class="empty-ico">👤</div><p>No users found.</p></div></td></tr>
                <?php endif; ?>
                <?php foreach($users as $u): ?>
                <tr>
                  <td class="mono">#<?= $u['id'] ?></td>
                  <td>
                    <div class="user-cell">
                      <div class="av">
                        <?php if(!empty($u['profile_image']) && $u['profile_image']!=='default.png'): ?>
                          <img src="/Nepal-Travel/<?= ltrim($u['profile_image'],'/') ?>" alt="">
                        <?php else: ?>
                          <?= strtoupper(substr($u['full_name'],0,1)) ?>
                        <?php endif; ?>
                      </div>
                      <div>
                        <div class="user-cell-name"><?= htmlspecialchars($u['full_name']) ?></div>
                        <div class="user-cell-email"><?= htmlspecialchars($u['email']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td class="mono">@<?= htmlspecialchars($u['username']) ?></td>
                  <td class="mono"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                  <td><span class="pill <?= $u['email_verified'] ? 'pill-verified' : 'pill-unverified' ?>"><span class="dot"></span><?= $u['email_verified'] ? 'Verified' : 'Unverified' ?></span></td>
                  <td><span class="pill <?= $u['phone_verified'] ? 'pill-verified' : 'pill-unverified' ?>"><span class="dot"></span><?= $u['phone_verified'] ? 'Verified' : 'Unverified' ?></span></td>
                  <td class="mono"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                  <td>
                    <form method="POST" action="?tab=users" style="display:inline" onsubmit="return confirm('Delete this user and all their bookings?')">
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <button type="submit" name="delete_user" class="btn act-btn-del">
                        <svg viewBox="0 0 24 24" fill="currentColor" style="width:12px;height:12px"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                        Delete
                      </button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      <!-- ════ BOOKINGS ════ -->
      <?php elseif ($activeTab === 'bookings'): ?>
        <div class="sec-hd">
          <h1 class="sec-hd-title">All Bookings</h1>
          <div class="sec-hd-rule"></div>
          <span class="sec-hd-count"><?= count($bookings) ?> TOTAL</span>
        </div>
        <div class="tcard">
          <div class="tcard-hd">
            <div><div class="tcard-hd-title">Booking Records</div><div class="tcard-hd-sub">All bookings across all users</div></div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
              <span class="pill pill-confirmed"><span class="dot"></span><?= $confirmed ?> Confirmed</span>
              <span class="pill pill-pending"><span class="dot"></span><?= $pending ?> Pending</span>
              <span class="pill pill-cancelled"><span class="dot"></span><?= $cancelled ?> Cancelled</span>
              <?php if(!empty($bookings)): ?>
              <form method="POST" action="?tab=bookings" style="margin-left:8px" onsubmit="return confirm('DELETE ALL <?= $total_bookings ?> BOOKINGS? This cannot be undone.')">
                <button type="submit" name="delete_all_bookings" class="btn act-btn-del" style="padding:5px 14px">
                  <svg viewBox="0 0 24 24" fill="currentColor" style="width:12px;height:12px"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                  Delete All
                </button>
              </form>
              <?php endif; ?>
            </div>
          </div>
          <div class="tcard-search">
            <input type="text" class="search-inp" id="bkSearch" placeholder="Search by name, destination, user…" oninput="filterBookings()">
            <div class="filter-btns">
              <button class="filter-btn f-all active" data-status="all" onclick="setFilter(this)"><span class="fb-dot"></span>All<span class="fb-count"><?= $total_bookings ?></span></button>
              <button class="filter-btn f-confirmed" data-status="confirmed" onclick="setFilter(this)"><span class="fb-dot"></span>Confirmed<span class="fb-count"><?= $confirmed ?></span></button>
              <button class="filter-btn f-pending" data-status="pending" onclick="setFilter(this)"><span class="fb-dot"></span>Pending<span class="fb-count"><?= $pending ?></span></button>
              <button class="filter-btn f-cancelled" data-status="cancelled" onclick="setFilter(this)"><span class="fb-dot"></span>Cancelled<span class="fb-count"><?= $cancelled ?></span></button>
            </div>
          </div>
          <div class="tscroll">
            <table id="bkTable">
              <thead><tr><th>ID</th><th>Booking Name</th><th>User Account</th><th>Destination</th><th>Travel Date</th><th>Guests</th><th>Status</th><th>Booked On</th><th>Actions</th></tr></thead>
              <tbody>
                <?php if(empty($bookings)): ?>
                  <tr><td colspan="9"><div class="empty"><div class="empty-ico">📋</div><p>No bookings found.</p></div></td></tr>
                <?php else: ?>
                  <?php foreach($bookings as $b):
                    $s          = strtolower($b['status']);
                    $cls        = str_contains($s,'confirm') ? 'confirmed' : (str_contains($s,'cancel') ? 'cancelled' : 'pending');
                    $bid_padded = str_pad($b['id'],6,'0',STR_PAD_LEFT);
                  ?>
                  <tr data-status="<?= $cls ?>">
                    <td class="mono">#<?= $bid_padded ?></td>
                    <td style="font-weight:600"><?= htmlspecialchars($b['name'] ?? '—') ?></td>
                    <td>
                      <?php if(!empty($b['full_name'])): ?>
                        <div style="font-size:12px;font-weight:600"><?= htmlspecialchars($b['full_name']) ?></div>
                        <div class="mono" style="font-size:10px"><?= htmlspecialchars($b['email'] ?? '') ?></div>
                      <?php else: ?>
                        <span class="mono">User #<?= $b['user_id'] ?></span>
                      <?php endif; ?>
                    </td>
                    <td style="color:var(--gold)"><?= htmlspecialchars($b['destination']) ?></td>
                    <td class="mono"><?= date('d M Y', strtotime($b['date'])) ?></td>
                    <td style="text-align:center"><?= (int)$b['guests'] ?></td>
                    <td><span class="pill pill-<?= $cls ?>"><span class="dot"></span><?= htmlspecialchars(ucfirst($b['status'])) ?></span></td>
                    <td class="mono"><?= date('d M Y', strtotime($b['created_at'])) ?></td>
                    <td class="bk-actions-cell">
                      <form method="POST" action="?tab=bookings" class="bk-status-form">
                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                        <select name="status" class="status-sel">
                          <option value="pending"        <?= $b['status']==='pending'        ? 'selected':'' ?>>Pending</option>
                          <option value="confirmed"      <?= $b['status']==='confirmed'      ? 'selected':'' ?>>Confirmed</option>
                          <option value="booking cancel" <?= $b['status']==='booking cancel' ? 'selected':'' ?>>Cancelled</option>
                        </select>
                        <button type="submit" name="update_status" class="save-btn">Save</button>
                      </form>
                      <form method="POST" action="?tab=bookings" class="bk-delete-form" onsubmit="return confirm('Permanently delete booking #<?= $bid_padded ?>?')">
                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                        <button type="submit" name="delete_booking" class="btn act-btn-del" style="width:100%;justify-content:center">
                          <svg viewBox="0 0 24 24" fill="currentColor" style="width:12px;height:12px"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                          Delete Booking
                        </button>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <tr id="bkNoResults" style="display:none"><td colspan="9" style="text-align:center;padding:48px;color:var(--muted2);font-size:13px">No bookings match your filter.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      <!-- ════ SUBSCRIPTIONS ════ -->
      <?php elseif ($activeTab === 'subscriptions'): ?>
        <div class="sec-hd">
          <h1 class="sec-hd-title">Subscription Manager</h1>
          <div class="sec-hd-rule"></div>
          <span class="sec-hd-count"><?= count($subs) ?> TOTAL</span>
        </div>
        <div class="stats-grid stats-grid-sub">
          <div class="stat-card" style="--accent:#C9A227">
            <div class="stat-card-ico">💳</div><div class="stat-card-n"><?= count($subs) ?></div><div class="stat-card-l">Total Subscriptions</div>
            <?php if ($pending_subs > 0): ?><div class="stat-pending-badge"><?= $pending_subs ?> Pending</div><?php endif; ?>
          </div>
          <div class="stat-card" style="--accent:#4CAF7D">
            <div class="stat-card-ico">✅</div><div class="stat-card-n"><?= count(array_filter($subs, fn($s) => $s['status']==='active')) ?></div><div class="stat-card-l">Active Subscribers</div>
          </div>
          <div class="stat-card" style="--accent:#4A90D9">
            <div class="stat-card-ico">🏔️</div><div class="stat-card-n"><?= count($deals) ?></div><div class="stat-card-l">User Deals</div>
            <?php if ($pending_deals > 0): ?><div class="stat-pending-badge"><?= $pending_deals ?> Pending</div><?php endif; ?>
          </div>
          <div class="stat-card" style="--accent:#E8C44A">
            <div class="stat-card-ico">💰</div><div class="stat-card-n" style="font-size:22px;padding-top:6px">NPR <?= number_format($sub_revenue) ?></div><div class="stat-card-l">Total Revenue</div>
          </div>
        </div>
        <div class="sub-tabs">
          <button class="sub-tab-btn active" id="stab-subs" onclick="switchSubTab('subs')">
            💳 Subscriptions <?php if ($pending_subs > 0): ?><span class="sub-tab-badge"><?= $pending_subs ?></span><?php endif; ?>
          </button>
          <button class="sub-tab-btn" id="stab-deals" onclick="switchSubTab('deals')">
            🏔️ User Deals <?php if ($pending_deals > 0): ?><span class="sub-tab-badge"><?= $pending_deals ?></span><?php endif; ?>
          </button>
        </div>
        <div id="spanel-subs">
          <div class="tcard">
            <div class="tcard-hd"><div><div class="tcard-hd-title">All Subscriptions</div><div class="tcard-hd-sub">Review payment proofs and activate plans</div></div></div>
            <div class="tcard-search"><input type="text" class="search-inp" placeholder="Search by user, plan, status…" oninput="filterTable2('subTable', this.value)"></div>
            <div class="tscroll">
              <table id="subTable">
                <thead><tr><th>ID</th><th>User</th><th>Plan</th><th>Amount</th><th>Method</th><th>Ref</th><th>Status</th><th>Starts</th><th>Expires</th><th>Actions</th></tr></thead>
                <tbody>
                  <?php if(empty($subs)): ?>
                    <tr><td colspan="10"><div class="empty"><div class="empty-ico">💳</div><p>No subscriptions yet.</p></div></td></tr>
                  <?php endif; ?>
                  <?php foreach($subs as $s): ?>
                  <tr>
                    <td class="mono">#<?= $s['id'] ?></td>
                    <td><div style="font-weight:600"><?= htmlspecialchars($s['user_name'] ?? 'User #'.$s['user_id']) ?></div><div class="mono"><?= htmlspecialchars($s['user_email'] ?? '') ?></div></td>
                    <td><span class="pill pill-active" style="font-size:9px"><?= htmlspecialchars($s['display_name']) ?></span></td>
                    <td class="mono">NPR <?= number_format($s['amount_paid']) ?></td>
                    <td class="mono"><?= htmlspecialchars($s['payment_method']) ?></td>
                    <td class="mono" style="max-width:120px;overflow:hidden;text-overflow:ellipsis" title="<?= htmlspecialchars($s['payment_ref']) ?>"><?= htmlspecialchars($s['payment_ref'] ?: '—') ?></td>
                    <td><span class="pill pill-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span></td>
                    <td class="mono"><?= $s['starts_at'] ? date('M d, Y', strtotime($s['starts_at'])) : '—' ?></td>
                    <td class="mono"><?= $s['expires_at'] ? date('M d, Y', strtotime($s['expires_at'])) : '—' ?></td>
                    <td>
                      <div class="act-row">
                        <?php if(!empty($s['payment_proof'])): ?>
                          <a href="<?= htmlspecialchars($s['payment_proof']) ?>" target="_blank" class="btn btn-view">🖼 Proof</a>
                        <?php endif; ?>
                        <?php if($s['status'] === 'pending'): ?>
                          <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="approve_sub">
                            <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-approve">✓ Approve</button>
                          </form>
                          <button class="btn btn-reject" onclick="openRejectSub(<?= $s['id'] ?>)">✕ Reject</button>
                        <?php elseif($s['status'] === 'active'): ?>
                          <span style="font-size:11px;color:var(--green2)">● Active</span>
                        <?php elseif($s['status'] === 'expired'): ?>
                          <span style="font-size:11px;color:var(--muted2)">Expired</span>
                        <?php elseif($s['status'] === 'cancelled'): ?>
                          <span style="font-size:11px;color:var(--red2)">Rejected</span>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div id="spanel-deals" style="display:none">
          <div class="tcard">
            <div class="tcard-hd"><div><div class="tcard-hd-title">User-Submitted Deals</div><div class="tcard-hd-sub">Review and approve partner listings</div></div></div>
            <div class="tcard-search"><input type="text" class="search-inp" placeholder="Search deals by title, user, status…" oninput="filterTable2('dealTable', this.value)"></div>
            <div class="tscroll">
              <table id="dealTable">
                <thead><tr><th>ID</th><th>Image</th><th>Title</th><th>User</th><th>Plan</th><th>Price</th><th>Status</th><th>Visible Until</th><th>Actions</th></tr></thead>
                <tbody>
                  <?php if(empty($deals)): ?>
                    <tr><td colspan="9"><div class="empty"><div class="empty-ico">🏔️</div><p>No user deals yet.</p></div></td></tr>
                  <?php endif; ?>
                  <?php foreach($deals as $d):
                    $hasImg = !empty($d['image_url']) && strtoupper(trim($d['image_url'])) !== 'NULL';
                    $until  = $d['visible_until'] ? date('M d, Y', strtotime($d['visible_until'])) : '—';
                  ?>
                  <tr>
                    <td class="mono">#<?= $d['id'] ?></td>
                    <td>
                      <?php if($hasImg): ?>
                        <img src="<?= htmlspecialchars($d['image_url']) ?>" class="deal-thumb" onerror="this.style.display='none'">
                      <?php else: ?>
                        <div class="deal-thumb-ph"><?= $d['emoji'] ?? '🏔️' ?></div>
                      <?php endif; ?>
                    </td>
                    <td style="font-weight:600;max-width:180px">
                      <?= htmlspecialchars($d['title']) ?>
                      <?php if(!empty($d['description'])): ?>
                        <div class="mono" style="margin-top:3px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($d['description']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td><div style="font-weight:600"><?= htmlspecialchars($d['user_name'] ?? 'User #'.$d['user_id']) ?></div><div class="mono"><?= htmlspecialchars($d['user_email'] ?? '') ?></div></td>
                    <td class="mono" style="font-size:10px"><?= htmlspecialchars($d['plan_display'] ?? '—') ?></td>
                    <td class="mono">NPR <?= number_format((float)$d['price']) ?></td>
                    <td>
                      <span class="pill pill-<?= $d['status'] ?>"><?= ucfirst($d['status']) ?></span>
                      <?php if($d['status']==='rejected' && !empty($d['rejection_reason'])): ?>
                        <div class="mono" style="margin-top:4px;color:var(--red2);font-size:10px"><?= htmlspecialchars($d['rejection_reason']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="mono"><?= htmlspecialchars($until) ?></td>
                    <td>
                      <div class="act-row">
                        <button class="btn btn-view" data-deal='<?= htmlspecialchars(json_encode($d), ENT_QUOTES) ?>' onclick="viewDealDetail(JSON.parse(this.dataset.deal))">👁 View</button>
                        <?php if($d['status'] === 'pending'): ?>
                          <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="approve_deal">
                            <input type="hidden" name="deal_id" value="<?= $d['id'] ?>">
                            <button type="submit" class="btn btn-approve">✓ Approve</button>
                          </form>
                          <button class="btn btn-reject" onclick="openRejectDeal(<?= $d['id'] ?>)">✕ Reject</button>
                        <?php endif; ?>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this deal permanently?')">
                          <input type="hidden" name="action" value="delete_deal">
                          <input type="hidden" name="deal_id" value="<?= $d['id'] ?>">
                          <button type="submit" class="btn btn-delete">🗑</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      <!-- ════ CHAT ════ -->
      <?php elseif ($activeTab === 'chat'): ?>
        <div class="sec-hd">
          <h1 class="sec-hd-title">Live Chat</h1>
          <div class="sec-hd-rule"></div>
          <span class="sec-hd-count" id="chatTotalBadge">Loading…</span>
        </div>
        <div class="chat-layout">
          <div class="chat-sidebar">
            <div class="chat-sidebar-hd">
              <div class="chat-sidebar-title">Conversations</div>
              <span class="chat-total-badge" id="convCountBadge">—</span>
            </div>
            <div class="chat-search-wrap">
              <input type="text" class="chat-search-inp" id="convSearch" placeholder="Search users…" oninput="filterConvs()">
            </div>
            <div class="conv-list" id="convList">
              <div class="chat-loading"><span></span><span></span><span></span></div>
            </div>
          </div>
          <div class="chat-panel" id="chatPanel">
            <div class="chat-panel-hd-empty" id="chatPanelHdEmpty">
              <span style="font-size:12px;color:var(--muted2);font-family:var(--ff-m)">← Select a conversation to start replying</span>
            </div>
            <div class="chat-panel-hd" id="chatPanelHd" style="display:none">
              <div class="conv-av" id="chatActiveAv" style="width:38px;height:38px;font-size:15px">?</div>
              <div class="chat-panel-user-info">
                <div class="name" id="chatActiveName">—</div>
                <div class="email" id="chatActiveEmail">—</div>
              </div>
              <div class="chat-panel-actions">
                <button class="btn-del-conv" onclick="deleteConversation()">🗑 Clear Chat</button>
              </div>
            </div>
            <div class="chat-msgs-area" id="chatMsgsArea">
              <div class="chat-placeholder">
                <div class="chat-placeholder-ico">💬</div>
                <h3>No conversation open</h3>
                <p>Choose a user from the list<br>to view and reply to their messages.</p>
              </div>
            </div>
            <div class="chat-footer-disabled" id="chatFooterDisabled">Select a conversation to reply</div>
            <div class="chat-footer" id="chatFooter" style="display:none">
              <textarea
                class="chat-inp"
                id="adminChatInp"
                rows="1"
                placeholder="Type your reply…"
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();adminSend();}"
                oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,120)+'px';"
              ></textarea>
              <button class="chat-send-btn" onclick="adminSend()" title="Send reply">
                <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
              </button>
            </div>
          </div>
        </div>
      <?php endif; ?>

    </div><!-- /content -->
  </div><!-- /main -->
</div><!-- /admin-wrap -->

<!-- ══ MODALS ══ -->
<div class="modal-bd" id="rejectSubModal" onclick="closeBd(event,'rejectSubModal')">
  <div class="modal-box">
    <div class="modal-hd">
      <div class="modal-hd-title">Reject Subscription</div>
      <button class="modal-close" onclick="closeM('rejectSubModal')">✕</button>
    </div>
    <div class="modal-body">
      <p style="font-size:13px;color:var(--muted);margin-bottom:16px">Are you sure you want to reject this subscription?</p>
      <form method="POST">
        <input type="hidden" name="action" value="reject_sub">
        <input type="hidden" name="sub_id" id="reject_sub_id">
        <button type="submit" class="btn-confirm btn-confirm-red">Confirm Reject</button>
      </form>
    </div>
  </div>
</div>
<div class="modal-bd" id="rejectDealModal" onclick="closeBd(event,'rejectDealModal')">
  <div class="modal-box">
    <div class="modal-hd">
      <div class="modal-hd-title">Reject Deal</div>
      <button class="modal-close" onclick="closeM('rejectDealModal')">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST">
        <input type="hidden" name="action" value="reject_deal">
        <input type="hidden" name="deal_id" id="reject_deal_id">
        <label class="flbl">Reason for Rejection</label>
        <textarea name="reason" class="fin" rows="3" placeholder="e.g. Missing images, incorrect pricing…"></textarea>
        <button type="submit" class="btn-confirm btn-confirm-red">Reject Deal</button>
      </form>
    </div>
  </div>
</div>
<div class="modal-bd" id="dealDetailModal" onclick="closeBd(event,'dealDetailModal')">
  <div class="modal-box" style="max-width:520px">
    <div class="modal-hd">
      <div class="modal-hd-title" id="dd_title">Deal Detail</div>
      <button class="modal-close" onclick="closeM('dealDetailModal')">✕</button>
    </div>
    <div class="modal-body" id="dd_body"></div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
// ── Clock ────────────────────────────────────────────────────────────────────
(function tick(){
  var el = document.getElementById('clock');
  if(el) el.textContent = new Date().toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  setTimeout(tick, 1000);
})();

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(msg, isError){
  var t = document.getElementById('toast');
  t.textContent = msg;
  t.style.borderLeftColor = isError ? 'var(--red2)' : 'var(--green2)';
  t.classList.add('show');
  setTimeout(function(){ t.classList.remove('show'); }, 3200);
}
<?php if($msg): ?>
document.addEventListener('DOMContentLoaded', function(){
  showToast(<?= json_encode($msg) ?>, <?= $msg_type==='error' ? 'true' : 'false' ?>);
});
<?php endif; ?>

// ── Table search ──────────────────────────────────────────────────────────────
function filterTable(inputId, tableId){
  var q = document.getElementById(inputId).value.toLowerCase();
  document.querySelectorAll('#'+tableId+' tbody tr').forEach(function(r){
    r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
function filterTable2(tableId, q){
  q = q.toLowerCase();
  document.querySelectorAll('#'+tableId+' tbody tr').forEach(function(r){
    r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// ── Bookings filter ───────────────────────────────────────────────────────────
var activeStatus = 'all';
function setFilter(btn){
  document.querySelectorAll('.filter-btn').forEach(function(b){ b.classList.remove('active'); });
  btn.classList.add('active');
  activeStatus = btn.dataset.status;
  filterBookings();
}
function filterBookings(){
  var q = (document.getElementById('bkSearch') ? document.getElementById('bkSearch').value : '').toLowerCase();
  var visible = 0;
  document.querySelectorAll('#bkTable tbody tr:not(#bkNoResults)').forEach(function(row){
    var rs          = (row.dataset.status || '').toLowerCase();
    var matchText   = !q || row.textContent.toLowerCase().includes(q);
    var matchStatus = activeStatus === 'all' || rs === activeStatus;
    var show        = matchText && matchStatus;
    row.style.display = show ? '' : 'none';
    if(show) visible++;
  });
  var nr = document.getElementById('bkNoResults');
  if(nr) nr.style.display = visible === 0 ? '' : 'none';
}

// ── Sub tabs ──────────────────────────────────────────────────────────────────
function switchSubTab(tab){
  ['subs','deals'].forEach(function(t){
    document.getElementById('spanel-'+t).style.display = t === tab ? 'block' : 'none';
    document.getElementById('stab-'+t).classList.toggle('active', t === tab);
  });
}

// ── Modals ────────────────────────────────────────────────────────────────────
function openM(id)  { document.getElementById(id).classList.add('open');    document.body.style.overflow='hidden'; }
function closeM(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
function closeBd(e,id){ if(e.target===document.getElementById(id)) closeM(id); }
document.addEventListener('keydown', function(e){
  if(e.key==='Escape') document.querySelectorAll('.modal-bd.open').forEach(function(m){ m.classList.remove('open'); document.body.style.overflow=''; });
});
function openRejectSub(id)  { document.getElementById('reject_sub_id').value=id;  openM('rejectSubModal'); }
function openRejectDeal(id) { document.getElementById('reject_deal_id').value=id; openM('rejectDealModal'); }
function viewDealDetail(d){
  document.getElementById('dd_title').textContent = d.title;
  var img = d.image_url && d.image_url.toUpperCase() !== 'NULL'
    ? '<img src="'+d.image_url+'" style="width:100%;height:160px;object-fit:cover;border-radius:10px;margin-bottom:14px;border:1px solid rgba(255,255,255,0.08)">'
    : '';
  function row(label,val){ return val ? '<div style="display:flex;gap:10px;padding:6px 0;border-bottom:1px solid rgba(255,255,255,0.05)"><span style="color:rgba(255,255,255,0.4);width:110px;flex-shrink:0;font-size:12px">'+label+'</span><span style="font-size:13px">'+val+'</span></div>' : ''; }
  document.getElementById('dd_body').innerHTML = img
    + row('Category',d.category)
    + row('Location',d.location)
    + row('Duration',d.days ? d.days+' days' : '')
    + row('Season',d.season)
    + row('Price',d.price ? 'NPR '+Number(d.price).toLocaleString() : '')
    + row('Original Price',d.original_price>0 ? 'NPR '+Number(d.original_price).toLocaleString() : '')
    + row('Features',d.features)
    + '<div style="margin-top:12px;font-size:13px;color:rgba(255,255,255,0.55);line-height:1.7">'+(d.description||'')+'</div>';
  openM('dealDetailModal');
}

// ════════════════════════════════════════════════════════════════════════════
//  ADMIN CHAT — only boots on the chat tab
//  KEY FIXES:
//  1. session_write_close() in chat_ajax.php releases the session lock so
//     page reloads don't get blocked and redirected to login.
//  2. Polling pauses when the tab/window is hidden (Page Visibility API)
//     to reduce unnecessary server load.
//  3. Conversation list uses delegated click events + a JS Map instead of
//     inline JSON in onclick attributes (avoids HTML encoding issues).
//  4. AbortController cancels in-flight requests when switching conversations.
// ════════════════════════════════════════════════════════════════════════════
var IS_CHAT_TAB = <?= $isChatTab ?>;

(function(){
  if(!IS_CHAT_TAB) return;

  var AJAX_URL    = 'chat_ajax.php';
  var POLL_CONVS  = 8000;   // refresh conversation list every 8s
  var POLL_MSGS   = 3000;   // refresh active messages every 3s

  var conversations   = [];
  var convMap         = {};  // user_id → conversation object
  var activeUserId    = null;
  var activeUserName  = '';
  var activeUserEmail = '';
  var lastMsgIds      = new Set(); // track rendered message IDs to avoid flicker
  var isLoadingMsgs   = false;
  var isSending       = false;
  var pollConvsTimer  = null;
  var pollMsgsTimer   = null;
  var msgAbortCtrl    = null; // AbortController for message fetches

  // ── Helpers ─────────────────────────────────────────────────────────────────
  function formatTime(str){
    if(!str) return '';
    var d    = new Date(str.replace(' ','T'));
    var diff = (Date.now() - d) / 1000;
    if(diff < 60)    return 'just now';
    if(diff < 3600)  return Math.floor(diff/60)+'m ago';
    if(diff < 86400) return Math.floor(diff/3600)+'h ago';
    return d.toLocaleDateString([],{month:'short',day:'numeric'});
  }

  function initials(name){
    if(!name) return '?';
    return name.split(' ').map(function(w){ return w[0]||''; }).join('').slice(0,2).toUpperCase();
  }

  function escHtml(str){
    var d = document.createElement('div');
    d.textContent = String(str||'');
    return d.innerHTML;
  }

  // ── Pause polling when page is hidden (prevents session contention) ──────────
  document.addEventListener('visibilitychange', function(){
    if(document.hidden){
      clearInterval(pollConvsTimer);
      clearInterval(pollMsgsTimer);
    } else {
      // Resume when user comes back
      loadConversations();
      pollConvsTimer = setInterval(loadConversations, POLL_CONVS);
      if(activeUserId){
        loadMessages();
        pollMsgsTimer = setInterval(loadMessages, POLL_MSGS);
      }
    }
  });

  // ── Load conversations ───────────────────────────────────────────────────────
  async function loadConversations(){
    try {
      var fd = new FormData();
      fd.append('action','admin_fetch_conversations');
      var r = await fetch(AJAX_URL, {method:'POST', body:fd});
      if(!r.ok) return;
      var d = await r.json();
      if(!d.ok){ console.error('Chat conversations error:', d.error); return; }

      conversations = d.data.conversations || [];

      // Rebuild map
      convMap = {};
      conversations.forEach(function(c){ convMap[c.user_id] = c; });

      renderConversations(conversations);

      // Update unread badge in sidebar
      var totalUnread = conversations.reduce(function(s,c){ return s+(c.unread_count||0); }, 0);
      var sideBadge   = document.getElementById('sidebarChatBadge');
      if(sideBadge){
        sideBadge.textContent   = totalUnread;
        sideBadge.style.display = totalUnread > 0 ? '' : 'none';
        sideBadge.className     = 'sb-badge '+(totalUnread>0 ? 'sb-badge-alert' : 'sb-badge-chat');
      }

      var badge = document.getElementById('chatTotalBadge');
      if(badge) badge.textContent = conversations.length+' USER'+(conversations.length!==1?'S':'');

      var convCount = document.getElementById('convCountBadge');
      if(convCount) convCount.textContent = conversations.length;

    } catch(e){
      if(e.name !== 'AbortError') console.error('loadConversations error:', e);
    }
  }

  // ── Render conversation list ─────────────────────────────────────────────────
  function renderConversations(list){
    var el = document.getElementById('convList');
    var q  = (document.getElementById('convSearch') ? document.getElementById('convSearch').value : '').toLowerCase();
    var filtered = q
      ? list.filter(function(c){ return (c.full_name||'').toLowerCase().includes(q)||(c.email||'').toLowerCase().includes(q); })
      : list;

    if(filtered.length === 0){
      el.innerHTML = '<div class="conv-empty"><div class="conv-empty-ico">💬</div><p>'+(q?'No users match your search.':'No conversations yet.')+'</p></div>';
      return;
    }

    el.innerHTML = filtered.map(function(c){
      var unreadBadge   = c.unread_count > 0 ? '<div class="conv-unread-badge">'+c.unread_count+'</div>' : '';
      var lastMsgPrefix = c.last_sender === 'admin' ? '↑ You: ' : '';
      var preview       = c.last_message
        ? (lastMsgPrefix + c.last_message).slice(0, 42) + (c.last_message.length > 42 ? '…' : '')
        : 'No messages yet';
      var avContent = c.profile_image && c.profile_image !== 'default.png'
        ? '<img src="/Nepal-Travel/'+escHtml(c.profile_image)+'" alt="">'
        : initials(c.full_name);
      // ✅ data-uid only — no JSON in onclick attributes
      return '<div class="conv-item '+(c.user_id == activeUserId ? 'active' : '')+'" data-uid="'+escHtml(c.user_id)+'">'
        +'<div class="conv-av" style="position:relative">'+avContent+(c.unread_count>0 ? '<div class="conv-online-dot"></div>' : '')+'</div>'
        +'<div class="conv-info">'
          +'<div class="conv-name">'+escHtml(c.full_name||'Unknown User')+'</div>'
          +'<div class="conv-preview">'+escHtml(preview)+'</div>'
        +'</div>'
        +'<div class="conv-meta">'
          +'<div class="conv-time">'+formatTime(c.last_message_time)+'</div>'
          +unreadBadge
        +'</div>'
        +'</div>';
    }).join('');
  }

  // ── Delegated click on conversation list (no inline JSON risk) ───────────────
  document.getElementById('convList').addEventListener('click', function(e){
    var item = e.target.closest('.conv-item');
    if(!item) return;
    var uid = item.dataset.uid;
    var c   = convMap[uid];
    if(!c) return;
    selectConversation(c.user_id, c.full_name||'User', c.email||'', c.profile_image||'');
  });

  // Filter conversations
  window.filterConvs = function(){ renderConversations(conversations); };

  // ── Select a conversation ────────────────────────────────────────────────────
  function selectConversation(userId, name, email, profileImage){
    // Cancel any in-flight message fetch from previous conversation
    if(msgAbortCtrl) msgAbortCtrl.abort();
    clearInterval(pollMsgsTimer);

    activeUserId    = userId;
    activeUserName  = name;
    activeUserEmail = email;
    lastMsgIds      = new Set();

    // Show panel
    document.getElementById('chatPanelHdEmpty').style.display   = 'none';
    document.getElementById('chatPanelHd').style.display        = 'flex';
    document.getElementById('chatFooterDisabled').style.display = 'none';
    document.getElementById('chatFooter').style.display         = 'flex';

    // Set avatar
    var avEl = document.getElementById('chatActiveAv');
    if(profileImage && profileImage !== 'default.png'){
      avEl.innerHTML = '<img src="/Nepal-Travel/'+escHtml(profileImage)+'" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%">';
    } else {
      avEl.innerHTML   = '';
      avEl.textContent = initials(name);
    }
    document.getElementById('chatActiveName').textContent  = name;
    document.getElementById('chatActiveEmail').textContent = email||'';

    // Show loading spinner
    document.getElementById('chatMsgsArea').innerHTML = '<div class="chat-loading"><span></span><span></span><span></span></div>';

    // Highlight active conv
    document.querySelectorAll('.conv-item').forEach(function(el){
      el.classList.toggle('active', el.dataset.uid == userId);
    });

    // Start polling messages for this conversation
    loadMessages();
    pollMsgsTimer = setInterval(loadMessages, POLL_MSGS);

    // Focus input
    setTimeout(function(){ var inp = document.getElementById('adminChatInp'); if(inp) inp.focus(); }, 100);
  }

  // ── Load messages for active conversation ────────────────────────────────────
  async function loadMessages(){
    if(!activeUserId || isLoadingMsgs) return;
    isLoadingMsgs = true;

    // Create fresh abort controller
    msgAbortCtrl = new AbortController();

    try {
      var fd = new FormData();
      fd.append('action','admin_fetch_messages');
      fd.append('user_id', activeUserId);
      var r = await fetch(AJAX_URL, {method:'POST', body:fd, signal: msgAbortCtrl.signal});
      if(!r.ok){ isLoadingMsgs=false; return; }
      var d = await r.json();
      if(d.ok && d.data && d.data.messages){
        renderMessages(d.data.messages);
        // Refresh conv list to update unread counts
        loadConversations();
      } else {
        console.error('Fetch messages error:', d.error);
      }
    } catch(e){
      if(e.name !== 'AbortError') console.error('loadMessages error:', e);
    } finally {
      isLoadingMsgs = false;
    }
  }

  // ── Render messages ──────────────────────────────────────────────────────────
  function renderMessages(msgs){
    var area = document.getElementById('chatMsgsArea');
    if(!msgs || msgs.length === 0){
      area.innerHTML = '<div class="chat-placeholder"><div class="chat-placeholder-ico">💬</div><h3>'+escHtml(activeUserName)+'</h3><p>No messages yet.<br>Waiting for the user to start the conversation.</p></div>';
      lastMsgIds = new Set();
      return;
    }

    // Check if anything new arrived — compare IDs
    var newIds = new Set(msgs.map(function(m){ return m.id; }));
    var hasNew = false;
    newIds.forEach(function(id){ if(!lastMsgIds.has(id)) hasNew = true; });
    if(!hasNew && lastMsgIds.size === newIds.size) return; // nothing changed
    lastMsgIds = newIds;

    var wasAtBottom = (area.scrollHeight - area.scrollTop - area.clientHeight) < 60;

    var html = '', lastDate = '';
    msgs.forEach(function(m){
      var d       = new Date((m.created_at||'').replace(' ','T'));
      var dateStr = d.toLocaleDateString([],{weekday:'long',month:'long',day:'numeric'});
      if(dateStr !== lastDate){
        html += '<div class="msg-date-divider"><span>'+dateStr+'</span></div>';
        lastDate = dateStr;
      }
      var side  = m.sender === 'admin' ? 'admin' : 'user';
      var label = m.sender === 'admin' ? 'You (Admin)' : escHtml(activeUserName);
      html += '<div class="msg-wrap '+side+'" data-msgid="'+m.id+'">'
        +'<div class="msg-bubble '+side+'">'+escHtml(m.message)+'</div>'
        +'<div class="msg-time">'+label+' · '+formatTime(m.created_at)+'</div>'
        +'</div>';
    });
    area.innerHTML = html;

    // Auto-scroll to bottom only if admin was already near the bottom
    if(wasAtBottom){
      setTimeout(function(){ area.scrollTop = area.scrollHeight; }, 0);
    }
  }

  // ── Send admin reply ─────────────────────────────────────────────────────────
  window.adminSend = async function(){
    if(isSending) return;
    var inp = document.getElementById('adminChatInp');
    var msg = inp.value.trim();
    if(!msg || !activeUserId) return;

    isSending        = true;
    inp.value        = '';
    inp.style.height = 'auto';

    // Optimistic bubble — add immediately for perceived speed
    var area = document.getElementById('chatMsgsArea');
    var wrap = document.createElement('div');
    wrap.className = 'msg-wrap admin';
    wrap.innerHTML = '<div class="msg-bubble admin">'+escHtml(msg)+'</div><div class="msg-time">You (Admin) · just now</div>';
    // Remove placeholder if present
    var placeholder = area.querySelector('.chat-placeholder');
    if(placeholder) placeholder.remove();
    area.appendChild(wrap);
    area.scrollTop = area.scrollHeight;

    try {
      var fd = new FormData();
      fd.append('action','admin_reply');
      fd.append('user_id', activeUserId);
      fd.append('message', msg);
      var r = await fetch(AJAX_URL, {method:'POST', body:fd});
      if(r.ok){
        var d = await r.json();
        if(!d.ok){
          showToast('Send failed: '+d.error, true);
          // Put the message back in the input
          inp.value = msg;
        }
      }
    } catch(e){
      showToast('Send error. Check connection.', true);
      inp.value = msg;
    } finally {
      isSending = false;
    }

    // Refresh messages after a short delay to sync IDs
    setTimeout(loadMessages, 500);
  };

  // ── Delete conversation ──────────────────────────────────────────────────────
  window.deleteConversation = async function(){
    if(!activeUserId) return;
    if(!confirm('Delete ALL messages with '+activeUserName+'? This cannot be undone.')) return;
    try {
      var fd = new FormData();
      fd.append('action','admin_delete_conversation');
      fd.append('user_id', activeUserId);
      var r = await fetch(AJAX_URL, {method:'POST', body:fd});
      var d = await r.json();
      if(d.ok){
        showToast('Conversation cleared.', false);
        clearInterval(pollMsgsTimer);
        activeUserId = null;
        lastMsgIds   = new Set();

        document.getElementById('chatPanelHdEmpty').style.display   = 'flex';
        document.getElementById('chatPanelHd').style.display        = 'none';
        document.getElementById('chatFooter').style.display         = 'none';
        document.getElementById('chatFooterDisabled').style.display = '';
        document.getElementById('chatMsgsArea').innerHTML =
          '<div class="chat-placeholder">'
          +'<div class="chat-placeholder-ico">💬</div>'
          +'<h3>No conversation open</h3>'
          +'<p>Choose a user from the list<br>to view and reply to their messages.</p>'
          +'</div>';
        loadConversations();
      } else {
        showToast('Failed to delete: '+d.error, true);
      }
    } catch(e){ showToast('Delete error.', true); }
  };

  // ── Boot ─────────────────────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', function(){
    loadConversations();
    pollConvsTimer = setInterval(loadConversations, POLL_CONVS);
  });

})();
</script>

</body>
</html>