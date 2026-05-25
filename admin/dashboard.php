<?php
/**
 * Author: Ishwor Dhital
 * Student ID: 2462304
 * Group: L5CG6
 */
session_name('nepal_admin_session');
session_start();
require_once __DIR__ . '/../config/db.php';

// ── Auth guard ───────────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /Nepal-Travel/user/login.php'); exit;
}
$admin_id = (int)$_SESSION['user_id'];

// ── Fetch logged-in admin info ───────────────────────────────────
$admin_info_res = $conn->query("SELECT full_name, username, profile_image FROM users WHERE id = $admin_id LIMIT 1");
$admin_info     = $admin_info_res ? $admin_info_res->fetch_assoc() : [];
$admin_display  = htmlspecialchars($admin_info['full_name'] ?? $admin_info['username'] ?? 'Admin');
$admin_initial  = strtoupper(substr($admin_info['full_name'] ?? $admin_info['username'] ?? 'A', 0, 1));
$admin_username = htmlspecialchars($admin_info['username'] ?? '');
$admin_avatar   = $admin_info['profile_image'] ?? '';

// ── Auto-expire ──────────────────────────────────────────────────
$conn->query("UPDATE user_deals SET status='expired' WHERE status='approved' AND visible_until IS NOT NULL AND visible_until < NOW()");
$conn->query("UPDATE user_subscriptions SET status='expired' WHERE status='active' AND expires_at IS NOT NULL AND expires_at < NOW()");
$exp_sub_table_exists = $conn->query("SHOW TABLES LIKE 'user_experience_subscriptions'")->num_rows > 0;
if ($exp_sub_table_exists) {
    $conn->query("UPDATE user_experience_subscriptions SET status='expired' WHERE status='active' AND expires_at IS NOT NULL AND expires_at < NOW()");
}

// ════════════════════════════════════════════════════════════════
//  ALL POST ACTIONS — must be before any output
// ════════════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $bid    = (int)$_POST['booking_id'];
    $status = $conn->real_escape_string($_POST['status']);
    $conn->query("UPDATE bookings SET status='$status' WHERE id=$bid");
    header('Location: dashboard.php?tab=bookings&updated=1'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_booking'])) {
    $bid = (int)$_POST['booking_id'];
    $conn->query("DELETE FROM bookings WHERE id=$bid");
    header('Location: dashboard.php?tab=bookings&deleted_booking=1'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all_bookings'])) {
    $conn->query("DELETE FROM bookings");
    header('Location: dashboard.php?tab=bookings&deleted_all=1'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid = (int)$_POST['user_id'];
    $conn->query("DELETE FROM bookings WHERE user_id=$uid");
    $conn->query("DELETE FROM users WHERE id=$uid");
    header('Location: dashboard.php?tab=users&deleted=1'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'approve_sub') {
    $sub_id  = (int)$_POST['sub_id'];
    $sub_row = $conn->query("SELECT us.*, sp.duration_days FROM user_subscriptions us JOIN subscription_plans sp ON sp.id = us.plan_id WHERE us.id = $sub_id");
    if ($sub_row && ($sub = $sub_row->fetch_assoc())) {
        $starts  = date('Y-m-d H:i:s');
        $expires = date('Y-m-d H:i:s', strtotime("+{$sub['duration_days']} days"));
        $conn->query("UPDATE user_subscriptions SET status='active', starts_at='$starts', expires_at='$expires', approved_by=$admin_id, approved_at=NOW() WHERE id=$sub_id");
    }
    header('Location: dashboard.php?tab=subscriptions&msg=' . urlencode('✓ Subscription activated!') . '&mt=success'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reject_sub') {
    $sub_id = (int)$_POST['sub_id'];
    $conn->query("UPDATE user_subscriptions SET status='cancelled' WHERE id=$sub_id");
    header('Location: dashboard.php?tab=subscriptions&msg=' . urlencode('Subscription rejected.') . '&mt=error'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'approve_deal') {
    $deal_id = (int)$_POST['deal_id'];
    $drow = $conn->query("SELECT ud.id, sp.duration_days FROM user_deals ud JOIN user_subscriptions us ON us.id = ud.subscription_id JOIN subscription_plans sp ON sp.id = us.plan_id WHERE ud.id = $deal_id");
    if ($drow && ($d = $drow->fetch_assoc())) {
        $from  = date('Y-m-d H:i:s');
        $until = date('Y-m-d H:i:s', strtotime("+{$d['duration_days']} days"));
        $conn->query("UPDATE user_deals SET status='approved', visible_from='$from', visible_until='$until', approved_by=$admin_id, approved_at=NOW() WHERE id=$deal_id");
    }
    header('Location: dashboard.php?tab=subscriptions&msg=' . urlencode('✓ Deal approved and published!') . '&mt=success'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reject_deal') {
    $deal_id = (int)$_POST['deal_id'];
    $reason  = $conn->real_escape_string(trim($_POST['reason'] ?? 'Does not meet our guidelines.'));
    $conn->query("UPDATE user_deals SET status='rejected', rejection_reason='$reason' WHERE id=$deal_id");
    header('Location: dashboard.php?tab=subscriptions&msg=' . urlencode('Deal rejected.') . '&mt=error'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_deal') {
    $deal_id = (int)$_POST['deal_id'];
    $conn->query("DELETE FROM user_deals WHERE id=$deal_id");
    header('Location: dashboard.php?tab=subscriptions&msg=' . urlencode('Deal deleted.') . '&mt=success'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'approve_experience_sub' && $exp_sub_table_exists) {
    $sub_id  = (int)$_POST['sub_id'];
    $sub_row = $conn->query("SELECT ues.*, esp.duration_days FROM user_experience_subscriptions ues JOIN experience_subscription_plans esp ON esp.id=ues.plan_id WHERE ues.id=$sub_id");
    if ($sub_row && ($sub = $sub_row->fetch_assoc())) {
        $starts  = date('Y-m-d H:i:s');
        $expires = date('Y-m-d H:i:s', strtotime("+{$sub['duration_days']} days"));
        $conn->query("UPDATE user_experience_subscriptions SET status='active', starts_at='$starts', expires_at='$expires', approved_at=NOW() WHERE id=$sub_id");
    }
    header('Location: dashboard.php?tab=subscriptions&subtab=experience&msg=' . urlencode('✓ Experience subscription activated!') . '&mt=success'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reject_experience_sub' && $exp_sub_table_exists) {
    $sub_id = (int)$_POST['sub_id'];
    $conn->query("UPDATE user_experience_subscriptions SET status='cancelled' WHERE id=$sub_id");
    header('Location: dashboard.php?tab=subscriptions&subtab=experience&msg=' . urlencode('Experience subscription rejected.') . '&mt=error'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_experience_sub' && $exp_sub_table_exists) {
    $sub_id = (int)$_POST['sub_id'];
    $conn->query("DELETE FROM user_experience_subscriptions WHERE id=$sub_id");
    header('Location: dashboard.php?tab=subscriptions&subtab=experience&msg=' . urlencode('Experience subscription deleted.') . '&mt=success'); exit;
}

// ════════════════════════════════════════════════════════════════
//  DATA FETCHING
// ════════════════════════════════════════════════════════════════

$activeTab = $_GET['tab'] ?? 'overview';

$total_users    = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_bookings = $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0];
$confirmed      = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetch_row()[0];
$cancelled      = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='booking cancel' OR status='cancelled'")->fetch_row()[0];
$pending        = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='active'")->fetch_row()[0];

$users_result = $conn->query("SELECT id, full_name, username, email, phone, email_verified, phone_verified, created_at, profile_image FROM users ORDER BY id DESC");
$users = $users_result ? $users_result->fetch_all(MYSQLI_ASSOC) : [];

$bookings_result = $conn->query("SELECT b.id, b.user_id, b.name, b.destination, b.date, b.guests, b.status, b.created_at, u.full_name, u.email FROM bookings b LEFT JOIN users u ON b.user_id = u.id ORDER BY b.id DESC");
$bookings = $bookings_result ? $bookings_result->fetch_all(MYSQLI_ASSOC) : [];

$subs_result = $conn->query("SELECT us.*, sp.name AS plan_name, sp.display_name, sp.duration_days, sp.deal_limit, u.full_name AS user_name, u.email AS user_email FROM user_subscriptions us JOIN subscription_plans sp ON sp.id = us.plan_id LEFT JOIN users u ON u.id = us.user_id ORDER BY us.created_at DESC");
$subs = $subs_result ? $subs_result->fetch_all(MYSQLI_ASSOC) : [];

$deals_result = $conn->query("SELECT ud.*, u.full_name AS user_name, u.email AS user_email, sp.display_name AS plan_display, sp.duration_days FROM user_deals ud LEFT JOIN users u ON u.id = ud.user_id LEFT JOIN user_subscriptions us ON us.id = ud.subscription_id LEFT JOIN subscription_plans sp ON sp.id = us.plan_id ORDER BY ud.created_at DESC");
$deals = $deals_result ? $deals_result->fetch_all(MYSQLI_ASSOC) : [];

$experience_subs = [];
if ($exp_sub_table_exists) {
    $exp_subs_result = $conn->query("
        SELECT ues.*, esp.display_name, esp.duration_days,
               u.full_name AS user_name, u.email AS user_email,
               (SELECT COUNT(*) FROM posts p WHERE p.user_id = ues.user_id) AS posts_count
        FROM user_experience_subscriptions ues
        JOIN experience_subscription_plans esp ON esp.id = ues.plan_id
        LEFT JOIN users u ON u.id = ues.user_id
        ORDER BY ues.created_at DESC
    ");
    $experience_subs = $exp_subs_result ? $exp_subs_result->fetch_all(MYSQLI_ASSOC) : [];
}

$total_deal_reviews      = (int)($conn->query("SELECT COUNT(*) FROM deal_reviews")->fetch_row()[0] ?? 0);
$total_user_deal_reviews = (int)($conn->query("SELECT COUNT(*) FROM user_deal_reviews")->fetch_row()[0] ?? 0);
$total_reviews           = $total_deal_reviews + $total_user_deal_reviews;

$pending_subs  = count(array_filter($subs,  fn($s) => $s['status'] === 'pending'));
$pending_experience_subs = count(array_filter($experience_subs, fn($s) => $s['status'] === 'pending'));
$pending_deals = count(array_filter($deals, fn($d) => $d['status'] === 'pending'));
$sub_revenue   = array_sum(array_column(array_filter($subs, fn($s) => in_array($s['status'], ['active','expired'])), 'amount_paid'));
$experience_revenue = array_sum(array_column(array_filter($experience_subs, fn($s) => in_array($s['status'], ['active','expired'])), 'amount_paid'));

$chat_table_exists = $conn->query("SHOW TABLES LIKE 'chat_messages'")->num_rows > 0;
$total_chat_unread = 0;
if ($chat_table_exists) {
    $unread_res = $conn->query("SELECT COUNT(*) FROM chat_messages WHERE sender='user' AND is_read=0");
    $total_chat_unread = $unread_res ? (int)$unread_res->fetch_row()[0] : 0;
}

$msg = ''; $msg_type = 'success';
if (isset($_GET['msg']))             { $msg = $_GET['msg']; $msg_type = $_GET['mt'] ?? 'success'; }
if (isset($_GET['updated']))         { $msg = '✓ Booking status updated successfully.'; $msg_type = 'success'; }
if (isset($_GET['deleted']))         { $msg = '✓ User deleted successfully.'; $msg_type = 'success'; }
if (isset($_GET['deleted_booking'])) { $msg = '✓ Booking deleted successfully.'; $msg_type = 'success'; }
if (isset($_GET['deleted_all']))     { $msg = '✓ All bookings deleted successfully.'; $msg_type = 'success'; }

$total_pending_alerts = $pending_subs + $pending_deals + $pending_experience_subs;
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
  --topbar-h:60px;
  --ff-d:'Syne',sans-serif;--ff-b:'DM Sans',sans-serif;--ff-m:'DM Mono',monospace;
}
html,body{height:100%;background:var(--bg);color:var(--text);font-family:var(--ff-b);-webkit-font-smoothing:antialiased}
a{text-decoration:none;color:inherit}
button,input,select,textarea{font-family:var(--ff-b)}

/* ── LAYOUT — no sidebar, full width ── */
.admin-wrap{display:flex;flex-direction:column;min-height:100vh}

/* ══════════════════════════════════════
   TOP BAR
══════════════════════════════════════ */
.topbar{
  background:var(--surface);
  border-bottom:1px solid var(--border);
  height:var(--topbar-h);
  display:flex;align-items:center;
  padding:0 20px;gap:14px;
  position:sticky;top:0;z-index:500;
}

/* Hamburger button */
.ham-btn{
  width:40px;height:40px;border-radius:8px;
  background:var(--surface2);border:1px solid var(--border2);
  display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;
  cursor:pointer;flex-shrink:0;transition:background 0.2s,border-color 0.2s;
  padding:0;
}
.ham-btn:hover{background:var(--surface3);border-color:rgba(201,162,39,0.3)}
.ham-btn span{display:block;width:18px;height:2px;background:var(--text);border-radius:2px;transition:all 0.3s cubic-bezier(0.4,0,0.2,1)}
.ham-btn.open span:nth-child(1){transform:translateY(7px) rotate(45deg)}
.ham-btn.open span:nth-child(2){opacity:0;transform:scaleX(0)}
.ham-btn.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}

/* Logo in topbar */
.tb-logo{font-family:var(--ff-d);font-size:16px;font-weight:800;color:var(--text);letter-spacing:-0.3px;display:flex;align-items:center;gap:8px;margin-right:4px}
.tb-logo em{font-style:italic;color:var(--gold)}
.tb-logo svg{width:20px;height:20px;flex-shrink:0}

/* Breadcrumb */
.tb-breadcrumb{font-family:var(--ff-m);font-size:11px;color:var(--muted2);letter-spacing:1px;margin-left:4px}
.tb-breadcrumb span{color:var(--gold)}

/* Right side */
.tb-right{display:flex;align-items:center;gap:10px;margin-left:auto}
.tb-tag{font-size:10px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;color:var(--green2);background:rgba(46,125,82,0.15);border:1px solid rgba(76,175,125,0.2);padding:4px 12px;border-radius:20px;font-family:var(--ff-m)}
.tb-time{font-size:11px;color:var(--muted2);font-family:var(--ff-m);display:none}
.tb-admin-chip{display:flex;align-items:center;gap:8px;background:var(--surface2);border:1px solid var(--border2);padding:5px 12px 5px 6px;border-radius:24px;cursor:pointer}
.tb-admin-av{width:26px;height:26px;border-radius:50%;background:rgba(201,162,39,0.2);border:1px solid rgba(201,162,39,0.3);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--gold);font-family:var(--ff-d);overflow:hidden;flex-shrink:0}
.tb-admin-av img{width:100%;height:100%;object-fit:cover}
.tb-admin-name{font-size:11px;color:var(--muted);font-family:var(--ff-m)}

/* Alert indicator */
.tb-alert-dot{position:relative;cursor:pointer}
.tb-alert-btn{width:36px;height:36px;border-radius:8px;background:var(--surface2);border:1px solid var(--border2);display:flex;align-items:center;justify-content:center;font-size:15px}
.tb-alert-badge{position:absolute;top:-4px;right:-4px;background:var(--red2);color:#fff;font-size:9px;font-weight:800;min-width:16px;height:16px;border-radius:8px;display:flex;align-items:center;justify-content:center;padding:0 3px;font-family:var(--ff-m);border:2px solid var(--surface)}

@media(min-width:640px){
  .tb-time{display:block}
  .topbar{padding:0 28px}
}

/* ══════════════════════════════════════
   DRAWER / FLYOUT MENU
══════════════════════════════════════ */
.drawer-overlay{
  position:fixed;inset:0;z-index:800;
  background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);
  opacity:0;pointer-events:none;
  transition:opacity 0.28s ease;
}
.drawer-overlay.open{opacity:1;pointer-events:all}

.drawer{
  position:fixed;top:0;left:0;bottom:0;
  width:280px;z-index:900;
  background:var(--surface);
  border-right:1px solid rgba(255,255,255,0.08);
  display:flex;flex-direction:column;
  transform:translateX(-100%);
  transition:transform 0.3s cubic-bezier(0.4,0,0.2,1);
  box-shadow:8px 0 40px rgba(0,0,0,0.5);
}
.drawer.open{transform:translateX(0)}

/* Drawer header */
.drawer-hd{
  padding:22px 22px 18px;
  border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;
  flex-shrink:0;
}
.drawer-logo{font-family:var(--ff-d);font-size:17px;font-weight:800;color:var(--text);display:flex;align-items:center;gap:10px}
.drawer-logo em{font-style:italic;color:var(--gold)}
.drawer-logo-sub{font-size:9px;color:var(--muted2);letter-spacing:2px;text-transform:uppercase;margin-top:3px;font-family:var(--ff-m)}
.drawer-close{width:32px;height:32px;border-radius:50%;background:var(--surface2);border:1px solid var(--border2);color:var(--muted);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;transition:all 0.15s;flex-shrink:0}
.drawer-close:hover{background:rgba(224,85,85,0.15);color:var(--red2);border-color:rgba(224,85,85,0.3)}

/* Drawer nav */
.drawer-nav{flex:1;overflow-y:auto;padding:14px 12px;scrollbar-width:thin;scrollbar-color:var(--border2) transparent}
.drawer-nav::-webkit-scrollbar{width:3px}
.drawer-nav::-webkit-scrollbar-thumb{background:var(--border2);border-radius:2px}

.drawer-section-label{font-size:9px;letter-spacing:2.5px;text-transform:uppercase;color:var(--muted2);font-weight:600;padding:0 12px;margin:16px 0 6px;font-family:var(--ff-m)}
.drawer-section-label:first-child{margin-top:4px}

.nav-link{
  display:flex;align-items:center;gap:11px;
  padding:10px 12px;border-radius:9px;
  font-size:13px;font-weight:500;color:var(--muted);
  transition:all 0.18s;cursor:pointer;
  border:1px solid transparent;
  text-decoration:none;
  position:relative;
}
.nav-link svg{width:16px;height:16px;flex-shrink:0;opacity:0.6;transition:opacity 0.18s}
.nav-link:hover{background:var(--surface2);color:var(--text);border-color:var(--border)}
.nav-link:hover svg{opacity:1}
.nav-link.on{background:rgba(201,162,39,0.1);color:var(--gold);border-color:rgba(201,162,39,0.2)}
.nav-link.on svg{opacity:1}

.nav-badge{margin-left:auto;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;font-family:var(--ff-m)}
.nav-badge-gold{background:rgba(201,162,39,0.15);color:var(--gold)}
.nav-badge-alert{background:rgba(224,85,85,0.2);color:var(--red2)}
.nav-badge-chat{background:rgba(74,144,217,0.2);color:var(--blue2)}

/* Drawer divider */
.drawer-divider{height:1px;background:var(--border);margin:10px 12px}

/* Drawer footer — admin info */
.drawer-footer{
  padding:14px 18px;border-top:1px solid var(--border);flex-shrink:0;
  display:flex;align-items:center;gap:10px;
}
.drawer-footer-av{width:36px;height:36px;border-radius:50%;background:rgba(201,162,39,0.2);border:1px solid rgba(201,162,39,0.3);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:var(--gold);font-family:var(--ff-d);flex-shrink:0;overflow:hidden}
.drawer-footer-av img{width:100%;height:100%;object-fit:cover}
.drawer-footer-name{font-size:12px;font-weight:600;color:var(--text)}
.drawer-footer-sub{font-size:10px;color:var(--muted2);font-family:var(--ff-m)}

/* ── MAIN CONTENT ── */
.main{flex:1;overflow-y:auto}
.content{padding:24px 20px;max-width:1600px;margin:0 auto}
@media(min-width:768px){.content{padding:32px 32px}}
@media(min-width:1200px){.content{padding:36px 40px}}

/* ── ALERT ── */
.alert{display:flex;align-items:center;gap:12px;padding:13px 20px;border-radius:8px;font-size:13px;margin-bottom:24px;border-left:3px solid}
.alert-success{background:rgba(76,175,125,0.1);color:var(--green2);border-color:var(--green2)}
.alert-error{background:rgba(224,85,85,0.1);color:var(--red2);border-color:var(--red2)}

/* ── SECTION HEADER ── */
.sec-hd{display:flex;align-items:center;gap:20px;margin-bottom:28px;flex-wrap:wrap}
.sec-hd-title{font-family:var(--ff-d);font-size:24px;font-weight:800;color:var(--text)}
@media(min-width:640px){.sec-hd-title{font-size:28px}}
.sec-hd-rule{flex:1;height:1px;background:var(--border2);min-width:20px}
.sec-hd-count{font-family:var(--ff-m);font-size:11px;color:var(--muted2);letter-spacing:1px}

/* ── STAT CARDS ── */
.stats-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;margin-bottom:28px}
@media(min-width:640px){.stats-grid{grid-template-columns:repeat(3,1fr)}}
@media(min-width:900px){.stats-grid{grid-template-columns:repeat(5,1fr)}}
.stats-grid-sub{grid-template-columns:repeat(2,1fr)}
@media(min-width:640px){.stats-grid-sub{grid-template-columns:repeat(3,1fr)}}
@media(min-width:900px){.stats-grid-sub{grid-template-columns:repeat(4,1fr)}}

.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:20px 18px;position:relative;overflow:hidden;transition:border-color 0.2s,transform 0.2s}
.stat-card:hover{border-color:var(--border2);transform:translateY(-2px)}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:var(--accent,var(--gold))}
.stat-card-n{font-family:var(--ff-d);font-size:34px;font-weight:800;color:var(--text);line-height:1;margin-bottom:6px}
.stat-card-l{font-size:10px;color:var(--muted);letter-spacing:1.5px;text-transform:uppercase;font-weight:600}
.stat-card-ico{position:absolute;top:16px;right:16px;font-size:20px;opacity:0.18}
.stat-pending-badge{display:inline-block;background:rgba(224,85,85,0.15);color:var(--red2);font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;margin-top:6px;font-family:var(--ff-m)}

/* ── SUB TABS ── */
.sub-tabs{display:flex;gap:4px;margin-bottom:1.5rem;background:var(--surface2);padding:4px;border-radius:10px;width:fit-content;border:1px solid var(--border);flex-wrap:wrap}
.sub-tab-btn{padding:8px 16px;border-radius:7px;font-size:12px;font-weight:600;border:none;cursor:pointer;transition:all 0.15s;font-family:var(--ff-b);color:var(--muted);background:transparent;display:flex;align-items:center;gap:8px}
.sub-tab-btn.active{background:var(--gold);color:#000}
.sub-tab-badge{background:rgba(224,85,85,0.9);color:#fff;font-size:9px;font-weight:800;padding:2px 6px;border-radius:10px;font-family:var(--ff-m)}

/* ── TABLE CARD ── */
.tcard{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:28px}
.tcard-hd{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface2);flex-wrap:wrap;gap:10px}
.tcard-hd-title{font-family:var(--ff-d);font-size:15px;font-weight:700;color:var(--text)}
.tcard-hd-sub{font-size:11px;color:var(--muted2);font-family:var(--ff-m);margin-top:2px}
.tcard-search{padding:12px 20px;border-bottom:1px solid var(--border);background:var(--surface);display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.search-inp{flex:1;min-width:180px;padding:8px 14px;background:var(--surface2);border:1px solid var(--border2);border-radius:7px;font-size:13px;color:var(--text);outline:none;transition:border-color 0.2s}
.search-inp::placeholder{color:var(--muted2)}
.search-inp:focus{border-color:rgba(201,162,39,0.4)}
.filter-btns{display:flex;gap:6px;flex-wrap:wrap}
.filter-btn{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:600;letter-spacing:0.5px;border:1px solid var(--border2);background:var(--surface2);color:var(--muted);cursor:pointer;transition:all 0.18s;font-family:var(--ff-m);white-space:nowrap}
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

/* ── TABLE ── */
.tscroll{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead th{font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--muted2);font-weight:700;padding:11px 16px;text-align:left;background:var(--surface2);border-bottom:1px solid var(--border);white-space:nowrap;font-family:var(--ff-m)}
tbody td{padding:12px 16px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover td{background:rgba(255,255,255,0.02)}
.mono{font-family:var(--ff-m);font-size:11px;color:var(--muted)}

/* ── PILLS ── */
.pill{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;font-family:var(--ff-m);white-space:nowrap}
.pill-confirmed,.pill-active,.pill-approved{background:rgba(46,125,82,0.2);color:var(--green2);border:1px solid rgba(76,175,125,0.2)}
.pill-cancelled,.pill-rejected{background:rgba(140,32,32,0.2);color:var(--red2);border:1px solid rgba(224,85,85,0.2)}
.pill-pending{background:rgba(140,90,16,0.2);color:var(--amber2);border:1px solid rgba(240,160,48,0.2)}
.pill-expired{background:rgba(255,255,255,0.05);color:var(--muted2);border:1px solid var(--border)}
.pill-verified{background:rgba(46,125,82,0.15);color:var(--green2);border:1px solid rgba(76,175,125,0.15)}
.pill-unverified{background:rgba(140,32,32,0.15);color:var(--red2);border:1px solid rgba(224,85,85,0.15)}
.dot{width:5px;height:5px;border-radius:50%;background:currentColor;display:inline-block}

/* ── AVATAR ── */
.av{width:32px;height:32px;border-radius:50%;background:rgba(201,162,39,0.15);border:1px solid rgba(201,162,39,0.2);display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--gold);font-family:var(--ff-d);flex-shrink:0;overflow:hidden}
.av img{width:100%;height:100%;object-fit:cover}
.user-cell{display:flex;align-items:center;gap:10px}
.user-cell-name{font-size:13px;font-weight:600;color:var(--text)}
.user-cell-email{font-size:11px;color:var(--muted);font-family:var(--ff-m)}

/* ── ACTION BUTTONS ── */
.act-row{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;border-radius:6px;font-size:11px;font-weight:700;border:1px solid;cursor:pointer;transition:all 0.15s;white-space:nowrap;background:transparent;font-family:var(--ff-b)}
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

/* ── STATUS SELECT ── */
.status-sel{background:var(--surface2);color:var(--text);border:1px solid var(--border2);border-radius:6px;padding:5px 10px;font-size:12px;cursor:pointer;outline:none;font-family:var(--ff-m)}
.status-sel:focus{border-color:rgba(201,162,39,0.4)}
.save-btn{background:var(--gold);color:#000;border:none;border-radius:6px;padding:5px 12px;font-size:11px;font-weight:700;cursor:pointer;transition:background 0.15s}
.save-btn:hover{background:var(--gold2)}

.bk-actions-cell{min-width:200px}
.bk-status-form{display:flex;gap:6px;align-items:center;margin-bottom:6px}
.bk-delete-form{display:flex}
.bk-delete-form button{width:100%}

/* ── DEAL THUMBNAIL ── */
.deal-thumb{width:48px;height:36px;object-fit:cover;border-radius:5px;border:1px solid var(--border2)}
.deal-thumb-ph{width:48px;height:36px;border-radius:5px;background:var(--surface3);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:16px}

/* ── OVERVIEW GRID ── */
.ov-grid{display:grid;grid-template-columns:1fr;gap:20px;margin-top:8px}
@media(min-width:900px){.ov-grid{grid-template-columns:1fr 1fr}}
.mini-table tbody td{padding:10px 14px;font-size:12px}
.mini-table thead th{padding:10px 14px}

/* ── EMPTY ── */
.empty{padding:50px;text-align:center;color:var(--muted2)}
.empty-ico{font-size:36px;opacity:0.2;margin-bottom:12px}
.empty p{font-size:13px}
.no-results-row{display:none}
.no-results-row td{text-align:center;padding:40px;color:var(--muted2);font-size:13px}

/* ── MODAL ── */
.modal-bd{display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.8);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:1.5rem}
.modal-bd.open{display:flex}
.modal-box{background:#13161f;border:1px solid rgba(255,255,255,0.1);border-radius:18px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;box-shadow:0 40px 80px rgba(0,0,0,0.7);animation:mIn 0.2s ease}
@keyframes mIn{from{opacity:0;transform:translateY(16px) scale(0.97)}to{opacity:1;transform:none}}
.modal-hd{padding:1.2rem 1.4rem 1rem;border-bottom:1px solid rgba(255,255,255,0.07);display:flex;align-items:center;justify-content:space-between}
.modal-hd-title{font-family:var(--ff-d);font-size:15px;font-weight:700;color:var(--text)}
.modal-close{background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);color:var(--muted);width:30px;height:30px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:15px;transition:all 0.15s}
.modal-close:hover{background:rgba(224,85,85,0.2);color:#ff6b6b}
.modal-body{padding:1.3rem 1.4rem 1.5rem}
.flbl{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);font-weight:600;font-family:var(--ff-m);display:block;margin-bottom:7px}
.fin{background:var(--surface2);color:var(--text);border:1px solid var(--border2);border-radius:7px;padding:10px 14px;font-size:13px;font-family:var(--ff-b);outline:none;width:100%;transition:border-color 0.2s}
.fin:focus{border-color:rgba(224,85,85,0.4)}
.btn-confirm{width:100%;padding:12px;border:none;border-radius:8px;font-family:var(--ff-b);font-size:13px;font-weight:700;cursor:pointer;margin-top:14px;transition:all 0.15s}
.btn-confirm-red{background:var(--red2);color:#fff}
.btn-confirm-red:hover{background:#c93333}

/* ── TOAST ── */
.toast{position:fixed;bottom:24px;right:20px;background:var(--surface);border:1px solid var(--border2);color:var(--text);padding:12px 18px;border-radius:8px;font-size:13px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,0.4);transform:translateY(12px);opacity:0;transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1);z-index:9999;pointer-events:none;border-left:3px solid var(--green2);max-width:300px}
.toast.show{transform:translateY(0);opacity:1}

/* ══════════════════════════════════════
   LIVE CHAT PANEL
══════════════════════════════════════ */
.chat-layout{display:grid;grid-template-columns:1fr;height:auto;border:1px solid var(--border);border-radius:14px;overflow:hidden;background:var(--surface)}
@media(min-width:768px){.chat-layout{grid-template-columns:280px 1fr;height:calc(100vh - var(--topbar-h) - 120px);min-height:520px}}

.chat-sessions{background:var(--surface2);border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden;max-height:300px}
@media(min-width:768px){.chat-sessions{max-height:none}}
.chat-sessions-hd{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.chat-sessions-hd-title{font-family:var(--ff-d);font-size:14px;font-weight:700;color:var(--text)}
.chat-sessions-list{flex:1;overflow-y:auto}
.chat-sessions-list::-webkit-scrollbar{width:3px}
.chat-sessions-list::-webkit-scrollbar-thumb{background:var(--border2);border-radius:2px}
.chat-session-item{padding:12px 16px;border-bottom:1px solid var(--border);cursor:pointer;transition:background 0.15s;display:flex;align-items:flex-start;gap:10px;position:relative}
.chat-session-item:hover{background:rgba(255,255,255,0.03)}
.chat-session-item.active{background:rgba(201,162,39,0.08);border-left:2px solid var(--gold)}
.chat-session-av{width:34px;height:34px;border-radius:50%;background:rgba(74,144,217,0.2);border:1px solid rgba(74,144,217,0.3);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:var(--blue2);font-family:var(--ff-d);flex-shrink:0}
.chat-session-info{flex:1;min-width:0}
.chat-session-name{font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.chat-session-preview{font-size:11px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px;font-family:var(--ff-m)}
.chat-session-time{font-size:10px;color:var(--muted2);font-family:var(--ff-m);flex-shrink:0;margin-top:2px}
.chat-unread-badge{background:var(--blue2);color:#fff;font-size:9px;font-weight:800;padding:1px 6px;border-radius:10px;font-family:var(--ff-m);position:absolute;top:12px;right:10px}
.chat-empty-sessions{padding:36px 16px;text-align:center;color:var(--muted2);font-size:13px}
.chat-sessions-search{padding:8px 12px;border-bottom:1px solid var(--border);flex-shrink:0}
.chat-sessions-search input{width:100%;background:var(--surface3);border:1px solid var(--border2);border-radius:7px;padding:7px 12px;font-size:12px;color:var(--text);outline:none}
.chat-sessions-search input::placeholder{color:var(--muted2)}

.chat-pane{display:flex;flex-direction:column;overflow:hidden;min-height:400px}
@media(min-width:768px){.chat-pane{min-height:0}}
.chat-pane-hd{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;flex-shrink:0;background:var(--surface2)}
.chat-pane-hd-av{width:34px;height:34px;border-radius:50%;background:rgba(74,144,217,0.2);border:1px solid rgba(74,144,217,0.3);display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:var(--blue2);font-family:var(--ff-d);flex-shrink:0}
.chat-pane-hd-info{flex:1}
.chat-pane-hd-name{font-size:14px;font-weight:700;color:var(--text)}
.chat-pane-hd-sub{font-size:11px;color:var(--muted2);font-family:var(--ff-m);margin-top:1px}
.chat-online-dot{width:7px;height:7px;border-radius:50%;background:var(--green2);display:inline-block;margin-right:4px;animation:pulse-g 2s infinite}
@keyframes pulse-g{0%,100%{opacity:1}50%{opacity:0.5}}
.chat-messages-area{flex:1;overflow-y:auto;padding:18px;display:flex;flex-direction:column;gap:11px;background:#0f1119;min-height:200px}
@media(min-width:768px){.chat-messages-area{min-height:0}}
.chat-messages-area::-webkit-scrollbar{width:3px}
.chat-messages-area::-webkit-scrollbar-thumb{background:var(--border2);border-radius:2px}
.chat-bubble-wrap{display:flex;flex-direction:column}
.chat-bubble-wrap.user{align-items:flex-start}
.chat-bubble-wrap.admin{align-items:flex-end}
.chat-bubble{max-width:74%;padding:10px 14px;border-radius:14px;font-size:13px;line-height:1.55;word-break:break-word;animation:bIn 0.18s ease}
@keyframes bIn{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:none}}
.chat-bubble.user{background:var(--surface3);color:var(--text);border-radius:4px 14px 14px 14px;border:1px solid var(--border2)}
.chat-bubble.admin{background:linear-gradient(135deg,rgba(201,162,39,0.25),rgba(201,162,39,0.1));color:var(--text);border-radius:14px 4px 14px 14px;border:1px solid rgba(201,162,39,0.25)}
.chat-bubble-time{font-size:10px;color:var(--muted2);margin-top:3px;font-family:var(--ff-m)}
.chat-no-session{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--muted2);padding:40px}
.chat-no-session-ico{font-size:48px;opacity:0.15;margin-bottom:14px}
.chat-no-session p{font-size:13px}
.chat-input-area{padding:12px 16px;border-top:1px solid var(--border);display:flex;gap:8px;align-items:flex-end;flex-shrink:0;background:var(--surface)}
.chat-input{flex:1;background:var(--surface2);border:1px solid var(--border2);border-radius:9px;padding:10px 14px;font-size:13px;color:var(--text);outline:none;resize:none;max-height:100px;line-height:1.5;transition:border-color 0.2s;font-family:var(--ff-b)}
.chat-input::placeholder{color:var(--muted2)}
.chat-input:focus{border-color:rgba(201,162,39,0.4)}
.chat-send-btn{background:var(--gold);color:#000;border:none;border-radius:8px;padding:10px 16px;font-size:12px;font-weight:700;cursor:pointer;transition:background 0.15s;display:flex;align-items:center;gap:5px;flex-shrink:0;height:42px}
.chat-send-btn:hover{background:var(--gold2)}
.chat-send-btn:disabled{opacity:0.4;cursor:not-allowed}
.chat-date-sep{text-align:center;font-size:10px;color:var(--muted2);font-family:var(--ff-m);letter-spacing:1px;padding:4px 0;position:relative}
.chat-date-sep::before,.chat-date-sep::after{content:'';position:absolute;top:50%;width:26%;height:1px;background:var(--border)}
.chat-date-sep::before{left:5%}
.chat-date-sep::after{right:5%}
</style>
</head>
<body>

<div class="admin-wrap">

  <!-- ══ TOPBAR ══ -->
  <header class="topbar">
    <!-- Hamburger button -->
    <button class="ham-btn" id="hamBtn" onclick="toggleDrawer()" aria-label="Open menu">
      <span></span><span></span><span></span>
    </button>

    <!-- Logo -->
    <div class="tb-logo">
      <svg viewBox="0 0 24 24" fill="none">
        <path d="M12 2L2 19h20L12 2z" fill="rgba(201,162,39,0.3)" stroke="#C9A227" stroke-width="1.5"/>
      </svg>
      Nepal <em>Admin</em>
    </div>

    <!-- Breadcrumb -->
    <div class="tb-breadcrumb" style="display:none" id="tb-crumb-wrap">
      / <span><?= strtoupper($activeTab) ?></span>
    </div>
    <style>@media(min-width:480px){#tb-crumb-wrap{display:block!important}}</style>

    <!-- Right actions -->
    <div class="tb-right">
      <span class="tb-time" id="clock"></span>
      <span class="tb-tag">● LIVE</span>

      <?php if ($total_pending_alerts > 0): ?>
      <div class="tb-alert-dot">
        <a href="subscription_admin.php" class="tb-alert-btn">🔔</a>
        <span class="tb-alert-badge"><?= $total_pending_alerts ?></span>
      </div>
      <?php endif; ?>

      <div class="tb-admin-chip">
        <div class="tb-admin-av">
          <?php if (!empty($admin_avatar) && $admin_avatar !== 'default.png'): ?>
            <img src="/Nepal-Travel/<?= ltrim(htmlspecialchars($admin_avatar), '/') ?>" alt="">
          <?php else: ?>
            <?= $admin_initial ?>
          <?php endif; ?>
        </div>
        <span class="tb-admin-name"><?= $admin_display ?></span>
      </div>
    </div>
  </header>

  <!-- ══ DRAWER OVERLAY ══ -->
  <div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>

  <!-- ══ DRAWER ══ -->
  <nav class="drawer" id="drawer">

    <div class="drawer-hd">
      <div>
        <div class="drawer-logo">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <path d="M12 2L2 19h20L12 2z" fill="rgba(201,162,39,0.3)" stroke="#C9A227" stroke-width="1.5"/>
          </svg>
          Nepal <em>Admin</em>
        </div>
        <div class="drawer-logo-sub">Control Panel</div>
      </div>
      <button class="drawer-close" onclick="closeDrawer()">✕</button>
    </div>

    <div class="drawer-nav">

      <div class="drawer-section-label">Navigation</div>

      <a href="?tab=overview" class="nav-link <?= $activeTab==='overview'?'on':'' ?>" onclick="closeDrawer()">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
        Overview
      </a>

      <a href="?tab=users" class="nav-link <?= $activeTab==='users'?'on':'' ?>" onclick="closeDrawer()">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        Users
        <span class="nav-badge nav-badge-gold"><?= $total_users ?></span>
      </a>

      <a href="?tab=bookings" class="nav-link <?= $activeTab==='bookings'?'on':'' ?>" onclick="closeDrawer()">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
        Bookings
        <span class="nav-badge nav-badge-gold"><?= $total_bookings ?></span>
      </a>

      <a href="subscription_admin.php" class="nav-link <?= $activeTab==='subscriptions'?'on':'' ?>">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
        Subscriptions
        <?php if ($pending_subs + $pending_deals + $pending_experience_subs > 0): ?>
          <span class="nav-badge nav-badge-alert"><?= $pending_subs + $pending_deals + $pending_experience_subs ?></span>
        <?php else: ?>
          <span class="nav-badge nav-badge-gold"><?= count($subs) + count($experience_subs) ?></span>
        <?php endif; ?>
      </a>

      <a href="?tab=chat" class="nav-link <?= $activeTab==='chat'?'on':'' ?>" onclick="closeDrawer()">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>
        Live Chat
        <?php if ($total_chat_unread > 0): ?>
          <span class="nav-badge nav-badge-chat" id="sb-chat-badge"><?= $total_chat_unread ?></span>
        <?php else: ?>
          <span class="nav-badge nav-badge-chat" id="sb-chat-badge" style="display:none">0</span>
        <?php endif; ?>
      </a>

      <a href="reviews.php" class="nav-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24z"/></svg>
        Reviews
        <span class="nav-badge nav-badge-gold"><?= $total_reviews ?></span>
      </a>

      <a href="posts.php" class="nav-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 3H3C2 3 1 4 1 5v14c0 1.1.9 2 2 2h18c1 0 2-.9 2-2V5c0-1-1-2-2-2zm0 16H3V5h18v14zm-10-7h8v2h-8v-2zm0-4h8v2h-8V8zm-6 8h4v-8H5v8z"/></svg>
        Posts
      </a>

      <div class="drawer-divider"></div>
      <div class="drawer-section-label">Links</div>

      <a href="/Nepal-Travel/Public/index.php" class="nav-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        View Site
      </a>

      <a href="deals_crud.php" class="nav-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42z"/></svg>
        Deals &amp; Packages
      </a>

      <a href="travel_ideas_admin.php" class="nav-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 21c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7z"/></svg>
        Travel Ideas
      </a>

      <div class="drawer-divider"></div>

      <a href="logout.php" class="nav-link" style="color:var(--red2);border-color:transparent">
        <svg viewBox="0 0 24 24" fill="currentColor" style="opacity:0.7"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
        Sign Out
      </a>

    </div>

    <!-- Footer -->
    <div class="drawer-footer">
      <div class="drawer-footer-av">
        <?php if (!empty($admin_avatar) && $admin_avatar !== 'default.png'): ?>
          <img src="/Nepal-Travel/<?= ltrim(htmlspecialchars($admin_avatar), '/') ?>" alt="">
        <?php else: ?>
          <?= $admin_initial ?>
        <?php endif; ?>
      </div>
      <div>
        <div class="drawer-footer-name"><?= $admin_display ?></div>
        <div class="drawer-footer-sub"><?= $admin_username ? '@'.$admin_username : '// Super Admin' ?></div>
      </div>
    </div>
  </nav>

  <!-- ══ MAIN CONTENT ══ -->
  <div class="main">
    <div class="content">

      <?php if ($msg): ?>
        <div class="alert alert-<?= htmlspecialchars($msg_type) ?>"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>


      <!-- ════════════════════════════════
           OVERVIEW TAB
      ════════════════════════════════ -->
      <?php if ($activeTab === 'overview'): ?>

        <div class="sec-hd">
          <h1 class="sec-hd-title">Overview</h1>
          <div class="sec-hd-rule"></div>
          <span class="sec-hd-count"><?= date('D, d M Y') ?></span>
        </div>

        <div class="stats-grid">
          <div class="stat-card" style="--accent:#C9A227">
            <div class="stat-card-ico">👥</div>
            <div class="stat-card-n"><?= $total_users ?></div>
            <div class="stat-card-l">Total Users</div>
          </div>
          <div class="stat-card" style="--accent:#4A90D9">
            <div class="stat-card-ico">📋</div>
            <div class="stat-card-n"><?= $total_bookings ?></div>
            <div class="stat-card-l">Total Bookings</div>
          </div>
          <div class="stat-card" style="--accent:#4CAF7D">
            <div class="stat-card-ico">✅</div>
            <div class="stat-card-n"><?= $confirmed ?></div>
            <div class="stat-card-l">Confirmed</div>
          </div>
          <div class="stat-card" style="--accent:#F0A030">
            <div class="stat-card-ico">⏳</div>
            <div class="stat-card-n"><?= $pending ?></div>
            <div class="stat-card-l">Pending</div>
          </div>
          <div class="stat-card" style="--accent:#E05555">
            <div class="stat-card-ico">❌</div>
            <div class="stat-card-n"><?= $cancelled ?></div>
            <div class="stat-card-l">Cancelled</div>
          </div>
        </div>

        <div class="stats-grid stats-grid-sub" style="margin-bottom:24px">
          <div class="stat-card" style="--accent:#C9A227">
            <div class="stat-card-ico">💳</div>
            <div class="stat-card-n"><?= count($subs) ?></div>
            <div class="stat-card-l">Subscriptions</div>
            <?php if ($pending_subs > 0): ?><div class="stat-pending-badge"><?= $pending_subs ?> Pending</div><?php endif; ?>
          </div>
          <div class="stat-card" style="--accent:#4CAF7D">
            <div class="stat-card-ico">✅</div>
            <div class="stat-card-n"><?= count(array_filter($subs, fn($s) => $s['status']==='active')) ?></div>
            <div class="stat-card-l">Active Subscribers</div>
          </div>
          <div class="stat-card" style="--accent:#4A90D9">
            <div class="stat-card-ico">🏔️</div>
            <div class="stat-card-n"><?= count($deals) ?></div>
            <div class="stat-card-l">User Deals</div>
            <?php if ($pending_deals > 0): ?><div class="stat-pending-badge"><?= $pending_deals ?> Pending</div><?php endif; ?>
          </div>
          <div class="stat-card" style="--accent:#E8C44A">
            <div class="stat-card-ico">💰</div>
            <div class="stat-card-n" style="font-size:20px;padding-top:4px">NPR <?= number_format($sub_revenue) ?></div>
            <div class="stat-card-l">Sub Revenue</div>
          </div>
        </div>

        <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px">
          <div class="stat-card" style="--accent:#E8C44A">
            <div class="stat-card-ico">⭐</div>
            <div class="stat-card-n"><?= $total_reviews ?></div>
            <div class="stat-card-l">Total Reviews</div>
          </div>
          <div class="stat-card" style="--accent:#C9A227">
            <div class="stat-card-ico">🏔️</div>
            <div class="stat-card-n"><?= $total_deal_reviews ?></div>
            <div class="stat-card-l">Deal Reviews</div>
          </div>
          <div class="stat-card" style="--accent:#4A90D9">
            <div class="stat-card-ico">💬</div>
            <div class="stat-card-n"><?= $total_chat_unread ?></div>
            <div class="stat-card-l">Unread Chats</div>
            <?php if ($total_chat_unread > 0): ?>
              <div style="margin-top:8px"><a href="?tab=chat" style="font-size:11px;color:var(--blue2);font-family:var(--ff-m)">Reply now →</a></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="ov-grid">
          <div class="tcard">
            <div class="tcard-hd">
              <div>
                <div class="tcard-hd-title">Recent Bookings</div>
                <div class="tcard-hd-sub">Latest 5 entries</div>
              </div>
              <a href="?tab=bookings" style="font-size:11px;color:var(--gold)">View all →</a>
            </div>
            <div class="tscroll">
              <table class="mini-table">
                <thead><tr><th>ID</th><th>Name</th><th>Destination</th><th>Status</th></tr></thead>
                <tbody>
                  <?php foreach(array_slice($bookings,0,5) as $b):
                    $s = strtolower($b['status']);
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
              <div>
                <div class="tcard-hd-title">Recent Users</div>
                <div class="tcard-hd-sub">Latest 5 registered</div>
              </div>
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

        <?php if ($pending_subs > 0 || $pending_deals > 0 || $pending_experience_subs > 0): ?>
        <div class="tcard" style="border-color:rgba(201,162,39,0.25)">
          <div class="tcard-hd" style="background:rgba(201,162,39,0.05)">
            <div>
              <div class="tcard-hd-title">⚠️ Pending Review</div>
              <div class="tcard-hd-sub"><?= $pending_subs ?> deal subscription(s) · <?= $pending_experience_subs ?> experience subscription(s) · <?= $pending_deals ?> deal(s) awaiting action</div>
            </div>
            <a href="subscription_admin.php" style="font-size:11px;color:var(--gold);font-weight:700">Review Now →</a>
          </div>
        </div>
        <?php endif; ?>


      <!-- ════════════════════════════════
           USERS TAB
      ════════════════════════════════ -->
      <?php elseif ($activeTab === 'users'): ?>

        <div class="sec-hd">
          <h1 class="sec-hd-title">All Users</h1>
          <div class="sec-hd-rule"></div>
          <span class="sec-hd-count"><?= count($users) ?> TOTAL</span>
        </div>

        <div class="tcard">
          <div class="tcard-hd">
            <div>
              <div class="tcard-hd-title">Registered Users</div>
              <div class="tcard-hd-sub">All accounts in the system</div>
            </div>
          </div>
          <div class="tcard-search">
            <input type="text" class="search-inp" id="userSearch" placeholder="Search by name, username or email…" oninput="filterTable('userSearch','usersTable')">
          </div>
          <div class="tscroll">
            <table id="usersTable">
              <thead>
                <tr><th>ID</th><th>User</th><th>Username</th><th>Phone</th><th>Email Verified</th><th>Phone Verified</th><th>Joined</th><th>Actions</th></tr>
              </thead>
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
                  <td>
                    <span class="pill <?= $u['email_verified'] ? 'pill-verified' : 'pill-unverified' ?>">
                      <span class="dot"></span><?= $u['email_verified'] ? 'Verified' : 'Unverified' ?>
                    </span>
                  </td>
                  <td>
                    <span class="pill <?= $u['phone_verified'] ? 'pill-verified' : 'pill-unverified' ?>">
                      <span class="dot"></span><?= $u['phone_verified'] ? 'Verified' : 'Unverified' ?>
                    </span>
                  </td>
                  <td class="mono"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                  <td>
                    <form method="POST" action="?tab=users" style="display:inline"
                          onsubmit="return confirm('Delete this user and all their bookings?')">
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


      <!-- ════════════════════════════════
           BOOKINGS TAB
      ════════════════════════════════ -->
      <?php elseif ($activeTab === 'bookings'): ?>

        <div class="sec-hd">
          <h1 class="sec-hd-title">All Bookings</h1>
          <div class="sec-hd-rule"></div>
          <span class="sec-hd-count"><?= count($bookings) ?> TOTAL</span>
        </div>

        <div class="tcard">
          <div class="tcard-hd">
            <div>
              <div class="tcard-hd-title">Booking Records</div>
              <div class="tcard-hd-sub">All bookings across all users</div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
              <span class="pill pill-confirmed"><span class="dot"></span><?= $confirmed ?> Confirmed</span>
              <span class="pill pill-pending"><span class="dot"></span><?= $pending ?> Pending</span>
              <span class="pill pill-cancelled"><span class="dot"></span><?= $cancelled ?> Cancelled</span>
              <?php if(!empty($bookings)): ?>
              <form method="POST" action="?tab=bookings"
                    onsubmit="return confirm('⚠️ DELETE ALL <?= $total_bookings ?> BOOKINGS?\n\nThis is permanent and cannot be undone. Are you absolutely sure?')">
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
              <button class="filter-btn f-all active" data-status="all" onclick="setFilter(this)">
                <span class="fb-dot"></span>All<span class="fb-count"><?= $total_bookings ?></span>
              </button>
              <button class="filter-btn f-confirmed" data-status="confirmed" onclick="setFilter(this)">
                <span class="fb-dot"></span>Confirmed<span class="fb-count"><?= $confirmed ?></span>
              </button>
              <button class="filter-btn f-pending" data-status="pending" onclick="setFilter(this)">
                <span class="fb-dot"></span>Pending<span class="fb-count"><?= $pending ?></span>
              </button>
              <button class="filter-btn f-cancelled" data-status="cancelled" onclick="setFilter(this)">
                <span class="fb-dot"></span>Cancelled<span class="fb-count"><?= $cancelled ?></span>
              </button>
            </div>
          </div>
          <div class="tscroll">
            <table id="bkTable">
              <thead>
                <tr>
                  <th>ID</th><th>Booking Name</th><th>User Account</th><th>Destination</th>
                  <th>Travel Date</th><th>Guests</th><th>Status</th><th>Booked On</th><th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if(empty($bookings)): ?>
                  <tr><td colspan="9"><div class="empty"><div class="empty-ico">📋</div><p>No bookings found.</p></div></td></tr>
                <?php else: ?>
                  <?php foreach($bookings as $b):
                    $s   = strtolower($b['status']);
                    $cls = str_contains($s,'confirm') ? 'confirmed' : (str_contains($s,'cancel') ? 'cancelled' : 'pending');
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
                          <option value="pending"        <?= $b['status']==='pending'       ?'selected':'' ?>>Pending</option>
                          <option value="confirmed"      <?= $b['status']==='confirmed'     ?'selected':'' ?>>Confirmed</option>
                          <option value="booking cancel" <?= $b['status']==='booking cancel'?'selected':'' ?>>Cancelled</option>
                        </select>
                        <button type="submit" name="update_status" class="save-btn">Save</button>
                      </form>
                      <form method="POST" action="?tab=bookings" class="bk-delete-form"
                            onsubmit="return confirm('Permanently delete booking #<?= $bid_padded ?>? This cannot be undone.')">
                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                        <button type="submit" name="delete_booking" class="btn act-btn-del" style="width:100%;justify-content:center">
                          <svg viewBox="0 0 24 24" fill="currentColor" style="width:12px;height:12px"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                          Delete Booking
                        </button>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <tr class="no-results-row" id="bkNoResults">
                    <td colspan="9" style="text-align:center;padding:48px;color:var(--muted2);font-size:13px">No bookings match your current filter.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>


      <!-- ════════════════════════════════
           SUBSCRIPTIONS TAB
      ════════════════════════════════ -->
      <?php elseif ($activeTab === 'subscriptions'): ?>

        <div class="sec-hd">
          <h1 class="sec-hd-title">Subscription Manager</h1>
          <div class="sec-hd-rule"></div>
          <span class="sec-hd-count"><?= count($subs) ?> TOTAL</span>
        </div>

        <div class="stats-grid stats-grid-sub">
          <div class="stat-card" style="--accent:#C9A227">
            <div class="stat-card-ico">💳</div>
            <div class="stat-card-n"><?= count($subs) ?></div>
            <div class="stat-card-l">Total Subscriptions</div>
            <?php if ($pending_subs > 0): ?><div class="stat-pending-badge"><?= $pending_subs ?> Pending</div><?php endif; ?>
          </div>
          <div class="stat-card" style="--accent:#4CAF7D">
            <div class="stat-card-ico">✅</div>
            <div class="stat-card-n"><?= count(array_filter($subs, fn($s) => $s['status']==='active')) ?></div>
            <div class="stat-card-l">Active Subscribers</div>
          </div>
          <div class="stat-card" style="--accent:#4A90D9">
            <div class="stat-card-ico">🏔️</div>
            <div class="stat-card-n"><?= count($deals) ?></div>
            <div class="stat-card-l">User Deals</div>
            <?php if ($pending_deals > 0): ?><div class="stat-pending-badge"><?= $pending_deals ?> Pending</div><?php endif; ?>
          </div>
          <div class="stat-card" style="--accent:#E8C44A">
            <div class="stat-card-ico">💰</div>
            <div class="stat-card-n" style="font-size:20px;padding-top:6px">NPR <?= number_format($sub_revenue) ?></div>
            <div class="stat-card-l">Deal Sub Revenue</div>
          </div>
          <div class="stat-card" style="--accent:#9B59B6">
            <div class="stat-card-ico">📸</div>
            <div class="stat-card-n"><?= count($experience_subs) ?></div>
            <div class="stat-card-l">Experience Subs</div>
            <?php if ($pending_experience_subs > 0): ?><div class="stat-pending-badge"><?= $pending_experience_subs ?> Pending</div><?php endif; ?>
          </div>
          <div class="stat-card" style="--accent:#1ABC9C">
            <div class="stat-card-ico">💵</div>
            <div class="stat-card-n" style="font-size:20px;padding-top:6px">NPR <?= number_format($experience_revenue) ?></div>
            <div class="stat-card-l">Experience Revenue</div>
          </div>
        </div>

        <div class="sub-tabs">
          <button class="sub-tab-btn active" id="stab-subs" onclick="switchSubTab('subs')">
            💳 Deal Subscriptions
            <?php if ($pending_subs > 0): ?><span class="sub-tab-badge"><?= $pending_subs ?></span><?php endif; ?>
          </button>
          <button class="sub-tab-btn" id="stab-experience" onclick="switchSubTab('experience')">
            📸 Experience Subscriptions
            <?php if ($pending_experience_subs > 0): ?><span class="sub-tab-badge"><?= $pending_experience_subs ?></span><?php endif; ?>
          </button>
          <button class="sub-tab-btn" id="stab-deals" onclick="switchSubTab('deals')">
            🏔️ User Deals
            <?php if ($pending_deals > 0): ?><span class="sub-tab-badge"><?= $pending_deals ?></span><?php endif; ?>
          </button>
          <a href="subscription_admin.php?tab=experience_subscriptions" class="sub-tab-btn" style="text-decoration:none;font-size:12px">Full manager →</a>
        </div>

        <div id="spanel-subs">
          <div class="tcard">
            <div class="tcard-hd">
              <div>
                <div class="tcard-hd-title">All Subscriptions</div>
                <div class="tcard-hd-sub">Review payment proofs and activate plans</div>
              </div>
            </div>
            <div class="tcard-search">
              <input type="text" class="search-inp" placeholder="Search by user, plan, status…" oninput="filterTable2('subTable', this.value)">
            </div>
            <div class="tscroll">
              <table id="subTable">
                <thead>
                  <tr><th>ID</th><th>User</th><th>Plan</th><th>Amount</th><th>Method</th><th>Ref</th><th>Status</th><th>Starts</th><th>Expires</th><th>Actions</th></tr>
                </thead>
                <tbody>
                  <?php if(empty($subs)): ?>
                    <tr><td colspan="10"><div class="empty"><div class="empty-ico">💳</div><p>No subscriptions yet.</p></div></td></tr>
                  <?php endif; ?>
                  <?php foreach($subs as $s): ?>
                  <tr>
                    <td class="mono">#<?= $s['id'] ?></td>
                    <td>
                      <div style="font-weight:600"><?= htmlspecialchars($s['user_name'] ?? 'User #'.$s['user_id']) ?></div>
                      <div class="mono"><?= htmlspecialchars($s['user_email'] ?? '') ?></div>
                    </td>
                    <td><span class="pill pill-active" style="font-size:9px"><?= htmlspecialchars($s['display_name']) ?></span></td>
                    <td class="mono">NPR <?= number_format($s['amount_paid']) ?></td>
                    <td class="mono"><?= htmlspecialchars($s['payment_method']) ?></td>
                    <td class="mono" style="max-width:110px;overflow:hidden;text-overflow:ellipsis" title="<?= htmlspecialchars($s['payment_ref']) ?>">
                      <?= htmlspecialchars($s['payment_ref'] ?: '—') ?>
                    </td>
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

        <div id="spanel-experience" style="display:none">
          <div class="tcard">
            <div class="tcard-hd">
              <div>
                <div class="tcard-hd-title">Experience Subscriptions</div>
                <div class="tcard-hd-sub">Users who paid via Khalti/eSewa to post after 5 free experiences</div>
              </div>
            </div>
            <?php if (!$exp_sub_table_exists): ?>
              <div class="empty"><div class="empty-ico">📸</div><p>Run <code>sql/create_experience_subscriptions.sql</code> to enable this section.</p></div>
            <?php else: ?>
            <div class="tcard-search">
              <input type="text" class="search-inp" placeholder="Search by user, plan, status…" oninput="filterTable2('experienceSubTable', this.value)">
            </div>
            <div class="tscroll">
              <table id="experienceSubTable">
                <thead>
                  <tr><th>ID</th><th>User</th><th>Posts</th><th>Plan</th><th>Amount</th><th>Method</th><th>Ref</th><th>Status</th><th>Starts</th><th>Expires</th><th>Actions</th></tr>
                </thead>
                <tbody>
                  <?php if(empty($experience_subs)): ?>
                    <tr><td colspan="11"><div class="empty"><div class="empty-ico">📸</div><p>No experience subscriptions yet.</p></div></td></tr>
                  <?php endif; ?>
                  <?php foreach($experience_subs as $s): ?>
                  <tr>
                    <td class="mono">#<?= $s['id'] ?></td>
                    <td>
                      <div style="font-weight:600"><?= htmlspecialchars($s['user_name'] ?? 'User #'.$s['user_id']) ?></div>
                      <div class="mono"><?= htmlspecialchars($s['user_email'] ?? '') ?></div>
                    </td>
                    <td class="mono"><?= (int)$s['posts_count'] ?></td>
                    <td><span class="pill pill-active" style="font-size:9px"><?= htmlspecialchars($s['display_name']) ?></span></td>
                    <td class="mono">NPR <?= number_format((float)$s['amount_paid']) ?></td>
                    <td class="mono"><?= htmlspecialchars($s['payment_method'] ?? '—') ?></td>
                    <td class="mono" style="max-width:110px;overflow:hidden;text-overflow:ellipsis" title="<?= htmlspecialchars($s['payment_ref'] ?? '') ?>">
                      <?= htmlspecialchars($s['payment_ref'] ?: '—') ?>
                    </td>
                    <td><span class="pill pill-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span></td>
                    <td class="mono"><?= $s['starts_at'] ? date('M d, Y', strtotime($s['starts_at'])) : '—' ?></td>
                    <td class="mono"><?= $s['expires_at'] ? date('M d, Y', strtotime($s['expires_at'])) : '—' ?></td>
                    <td>
                      <div class="act-row">
                        <?php if($s['status'] === 'pending'): ?>
                          <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="approve_experience_sub">
                            <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-approve">✓ Approve</button>
                          </form>
                          <button class="btn btn-reject" onclick="openRejectExperienceSub(<?= $s['id'] ?>)">✕ Reject</button>
                        <?php elseif($s['status'] === 'active'): ?>
                          <span style="font-size:11px;color:var(--green2)">● Active</span>
                        <?php endif; ?>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this experience subscription?')">
                          <input type="hidden" name="action" value="delete_experience_sub">
                          <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
                          <button type="submit" class="btn btn-delete">🗑</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <div id="spanel-deals" style="display:none">
          <div class="tcard">
            <div class="tcard-hd">
              <div>
                <div class="tcard-hd-title">User-Submitted Deals</div>
                <div class="tcard-hd-sub">Review and approve partner listings</div>
              </div>
            </div>
            <div class="tcard-search">
              <input type="text" class="search-inp" placeholder="Search deals by title, user, status…" oninput="filterTable2('dealTable', this.value)">
            </div>
            <div class="tscroll">
              <table id="dealTable">
                <thead>
                  <tr><th>ID</th><th>Image</th><th>Title</th><th>User</th><th>Plan</th><th>Price</th><th>Status</th><th>Visible Until</th><th>Actions</th></tr>
                </thead>
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
                    <td style="font-weight:600;max-width:160px">
                      <?= htmlspecialchars($d['title']) ?>
                      <?php if(!empty($d['description'])): ?>
                        <div class="mono" style="margin-top:3px;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($d['description']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div style="font-weight:600"><?= htmlspecialchars($d['user_name'] ?? 'User #'.$d['user_id']) ?></div>
                      <div class="mono"><?= htmlspecialchars($d['user_email'] ?? '') ?></div>
                    </td>
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
                        <button class="btn btn-view" onclick="viewDealDetail(<?= htmlspecialchars(json_encode($d), ENT_QUOTES) ?>)">👁 View</button>
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


      <!-- ════════════════════════════════
           LIVE CHAT TAB
      ════════════════════════════════ -->
      <?php elseif ($activeTab === 'chat'): ?>

        <div class="sec-hd" style="flex-wrap:wrap;gap:12px">
          <h1 class="sec-hd-title">Live Chat</h1>
          <div class="sec-hd-rule"></div>
          <span class="sec-hd-count" id="chat-session-count">Loading…</span>
          <button type="button" class="btn act-btn-del" id="delete-all-chats-btn" onclick="deleteAllChats()" style="margin-left:auto;padding:8px 16px;font-size:12px">
            🗑 Delete All Chats
          </button>
        </div>

        <div class="chat-layout">
          <div class="chat-sessions">
            <div class="chat-sessions-hd">
              <div class="chat-sessions-hd-title">💬 Conversations</div>
              <span id="chat-total-unread-badge" style="background:rgba(74,144,217,0.2);color:var(--blue2);font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;font-family:var(--ff-m);display:none"></span>
            </div>
            <div class="chat-sessions-search">
              <input type="text" id="sessSearch" placeholder="Search users…" oninput="filterSessions(this.value)">
            </div>
            <div class="chat-sessions-list" id="chat-sessions-list">
              <div class="chat-empty-sessions">
                <div style="font-size:28px;opacity:0.2;margin-bottom:10px">💬</div>
                <p>Loading conversations…</p>
              </div>
            </div>
          </div>

          <div class="chat-pane" id="chat-pane">
            <div class="chat-no-session" id="chat-no-session">
              <div class="chat-no-session-ico">💬</div>
              <p>Select a conversation to start replying</p>
            </div>

            <div id="chat-active-pane" style="display:none;flex-direction:column;height:100%">
              <div class="chat-pane-hd" id="chat-pane-hd">
                <div class="chat-pane-hd-av" id="chat-pane-av">?</div>
                <div class="chat-pane-hd-info">
                  <div class="chat-pane-hd-name" id="chat-pane-name">—</div>
                  <div class="chat-pane-hd-sub" id="chat-pane-sub"><span class="chat-online-dot"></span>Active session</div>
                </div>
                <div style="margin-left:auto;font-family:var(--ff-m);font-size:10px;color:var(--muted2)" id="chat-pane-sess"></div>
              </div>

              <div class="chat-messages-area" id="chat-messages-area"></div>

              <div class="chat-input-area">
                <textarea id="chat-admin-input" class="chat-input" placeholder="Type your reply…" rows="1"></textarea>
                <button class="chat-send-btn" id="chat-admin-send">
                  <svg viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                  Send
                </button>
              </div>
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

<div class="modal-bd" id="rejectExperienceSubModal" onclick="closeBd(event,'rejectExperienceSubModal')">
  <div class="modal-box">
    <div class="modal-hd">
      <div class="modal-hd-title">Reject Experience Subscription</div>
      <button class="modal-close" onclick="closeM('rejectExperienceSubModal')">✕</button>
    </div>
    <div class="modal-body">
      <p style="font-size:13px;color:var(--muted);margin-bottom:16px">Reject this experience subscription?</p>
      <form method="POST">
        <input type="hidden" name="action" value="reject_experience_sub">
        <input type="hidden" name="sub_id" id="reject_experience_sub_id">
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
const CHAT_HANDLER = '/Nepal-Travel/Public/chat_handler.php';

// ── Drawer ─────────────────────────────────────────────────────
function toggleDrawer(){
  const d = document.getElementById('drawer');
  const o = document.getElementById('drawerOverlay');
  const h = document.getElementById('hamBtn');
  const open = d.classList.toggle('open');
  o.classList.toggle('open', open);
  h.classList.toggle('open', open);
  document.body.style.overflow = open ? 'hidden' : '';
}
function closeDrawer(){
  document.getElementById('drawer').classList.remove('open');
  document.getElementById('drawerOverlay').classList.remove('open');
  document.getElementById('hamBtn').classList.remove('open');
  document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if(e.key === 'Escape') closeDrawer(); });

// ── Clock ──────────────────────────────────────────────────────
function updateClock(){
  const el = document.getElementById('clock');
  if(el) el.textContent = new Date().toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
updateClock(); setInterval(updateClock, 1000);

// ── Toast ──────────────────────────────────────────────────────
function showToast(msg, isError){
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.style.borderLeftColor = isError ? 'var(--red2)' : 'var(--green2)';
  t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'), 3200);
}
<?php if($msg): ?>
document.addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($msg) ?>, <?= $msg_type==='error'?'true':'false' ?>));
<?php endif; ?>

// ── Table search helpers ───────────────────────────────────────
function filterTable(inputId, tableId){
  const q = document.getElementById(inputId).value.toLowerCase();
  document.querySelectorAll('#'+tableId+' tbody tr').forEach(r=>{
    r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
function filterTable2(tableId, q){
  q = q.toLowerCase();
  document.querySelectorAll('#'+tableId+' tbody tr').forEach(r=>{
    r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// ── Bookings filter ────────────────────────────────────────────
let activeStatus = 'all';
function setFilter(btn){
  document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  activeStatus = btn.dataset.status;
  filterBookings();
}
function filterBookings(){
  const q = (document.getElementById('bkSearch')?.value||'').toLowerCase();
  let visible = 0;
  document.querySelectorAll('#bkTable tbody tr:not(#bkNoResults)').forEach(row=>{
    const rs = (row.dataset.status||'').toLowerCase();
    const matchText   = !q || row.textContent.toLowerCase().includes(q);
    const matchStatus = activeStatus==='all' || rs===activeStatus;
    const show = matchText && matchStatus;
    row.style.display = show ? '' : 'none';
    if(show) visible++;
  });
  const nr = document.getElementById('bkNoResults');
  if(nr) nr.style.display = visible===0 ? '' : 'none';
}

// ── Subscription sub-tabs ──────────────────────────────────────
function switchSubTab(tab){
  ['subs','experience','deals'].forEach(t=>{
    const panel = document.getElementById('spanel-'+t);
    const btn   = document.getElementById('stab-'+t);
    if(panel) panel.style.display = t===tab ? 'block' : 'none';
    if(btn) btn.classList.toggle('active', t===tab);
  });
}
<?php if(isset($_GET['subtab']) && $_GET['subtab']==='experience'): ?>
document.addEventListener('DOMContentLoaded',()=>switchSubTab('experience'));
<?php elseif(isset($_GET['subtab']) && $_GET['subtab']==='deals'): ?>
document.addEventListener('DOMContentLoaded',()=>switchSubTab('deals'));
<?php endif; ?>

// ── Modal helpers ──────────────────────────────────────────────
function openM(id) { document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
function closeM(id){ document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
function closeBd(e,id){ if(e.target===document.getElementById(id)) closeM(id); }
document.addEventListener('keydown',e=>{
  if(e.key==='Escape') document.querySelectorAll('.modal-bd.open').forEach(m=>{
    m.classList.remove('open'); document.body.style.overflow='';
  });
});
function openRejectSub(id){ document.getElementById('reject_sub_id').value=id; openM('rejectSubModal'); }
function openRejectExperienceSub(id){ document.getElementById('reject_experience_sub_id').value=id; openM('rejectExperienceSubModal'); }
function openRejectDeal(id){ document.getElementById('reject_deal_id').value=id; openM('rejectDealModal'); }

function viewDealDetail(d){
  document.getElementById('dd_title').textContent = d.title;
  const img = d.image_url && d.image_url.toUpperCase()!=='NULL'
    ? `<img src="${d.image_url}" style="width:100%;height:160px;object-fit:cover;border-radius:10px;margin-bottom:14px;border:1px solid rgba(255,255,255,0.08)">`
    : '';
  const row = (label,val) => val
    ? `<div style="display:flex;gap:10px;padding:6px 0;border-bottom:1px solid rgba(255,255,255,0.05)">
         <span style="color:rgba(255,255,255,0.4);width:110px;flex-shrink:0;font-size:12px">${label}</span>
         <span style="font-size:13px">${val}</span>
       </div>` : '';
  document.getElementById('dd_body').innerHTML = img
    + row('Category',d.category)
    + row('Location',d.location)
    + row('Duration',d.days ? d.days+' days' : '')
    + row('Season',d.season)
    + row('Price',d.price ? 'NPR '+Number(d.price).toLocaleString() : '')
    + row('Original Price',d.original_price>0 ? 'NPR '+Number(d.original_price).toLocaleString() : '')
    + row('Features',d.features)
    + `<div style="margin-top:12px;font-size:13px;color:rgba(255,255,255,0.55);line-height:1.7">${d.description||''}</div>`;
  openM('dealDetailModal');
}

// ══════════════════════════════════════════════════════════════
//  LIVE CHAT ENGINE
// ══════════════════════════════════════════════════════════════
<?php if ($activeTab === 'chat'): ?>
(function(){
  let sessions=[], activeSession=null, lastMsgId=0, globalLastId=0;
  const sessListEl  = document.getElementById('chat-sessions-list');
  const msgsEl      = document.getElementById('chat-messages-area');
  const inputEl     = document.getElementById('chat-admin-input');
  const sendBtn     = document.getElementById('chat-admin-send');
  const noSessEl    = document.getElementById('chat-no-session');
  const activePaneEl= document.getElementById('chat-active-pane');
  const paneNameEl  = document.getElementById('chat-pane-name');
  const paneSubEl   = document.getElementById('chat-pane-sub');
  const paneAvEl    = document.getElementById('chat-pane-av');
  const paneSessEl  = document.getElementById('chat-pane-sess');
  const countEl     = document.getElementById('chat-session-count');
  const totalBadgeEl= document.getElementById('chat-total-unread-badge');
  const sbBadgeEl   = document.getElementById('sb-chat-badge');

  function fmtTime(str){ if(!str) return ''; const d=new Date(str.replace(' ','T')); return d.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'}); }
  function fmtDate(str){ if(!str) return ''; const d=new Date(str.replace(' ','T')); return d.toLocaleDateString([],{weekday:'short',month:'short',day:'numeric'}); }
  function isToday(str){ if(!str) return false; return new Date(str.replace(' ','T')).toDateString()===new Date().toDateString(); }
  function fmtSessTime(str){ return isToday(str) ? fmtTime(str) : fmtDate(str); }
  function initials(name){ if(!name||name.startsWith('Guest')) return '?'; return name.split(' ').map(w=>w[0]||'').join('').substring(0,2).toUpperCase(); }

  function escHtml(str){ const d=document.createElement('div'); d.appendChild(document.createTextNode(str||'')); return d.innerHTML; }

  function renderSessions(list){
    const q=(document.getElementById('sessSearch')?.value||'').toLowerCase();
    const filtered=q?list.filter(s=>(s.display_name||s.session_id).toLowerCase().includes(q)):list;
    if(!filtered.length){
      sessListEl.innerHTML=`<div class="chat-empty-sessions"><div style="font-size:28px;opacity:0.2;margin-bottom:10px">💬</div><p>${q?'No matches.':'No conversations yet.'}</p></div>`;
      return;
    }
    const totalUnread=list.reduce((acc,s)=>acc+(+s.unread||0),0);
    countEl.textContent=list.length+' conversation'+(list.length!==1?'s':'');
    if(totalUnread>0){totalBadgeEl.textContent=totalUnread+' unread';totalBadgeEl.style.display='inline';if(sbBadgeEl){sbBadgeEl.textContent=totalUnread;sbBadgeEl.style.display='inline';}}
    else{totalBadgeEl.style.display='none';if(sbBadgeEl)sbBadgeEl.style.display='none';}
    sessListEl.innerHTML=filtered.map(s=>{
      const name=s.full_name||s.username||('Guest · '+s.session_id.substring(0,8));
      const isActive=activeSession&&activeSession.session_id===s.session_id;
      const unread=+s.unread||0;
      return `<div class="chat-session-item${isActive?' active':''}" data-sess="${escHtml(s.session_id)}" onclick="openSession(${JSON.stringify(s).replace(/"/g,'&quot;')})">
        <div class="chat-session-av">${initials(name)}</div>
        <div class="chat-session-info">
          <div class="chat-session-name">${escHtml(name)}</div>
          <div class="chat-session-preview">${escHtml(s.email||s.session_id.substring(0,16)+'…')}</div>
        </div>
        <div style="flex-shrink:0;text-align:right">
          <div class="chat-session-time">${fmtSessTime(s.last_at)}</div>
          ${unread>0?`<span class="chat-unread-badge" style="display:inline-block;margin-top:4px">${unread}</span>`:''}
        </div>
      </div>`;
    }).join('');
  }

  window.filterSessions=function(){ renderSessions(sessions); };

  window.openSession=function(s){
    activeSession=s; lastMsgId=0;
    const name=s.full_name||s.username||('Guest · '+s.session_id.substring(0,8));
    paneNameEl.textContent=name;
    paneAvEl.textContent=initials(name);
    paneSubEl.innerHTML=`<span class="chat-online-dot"></span> ${escHtml(s.email||s.session_id.substring(0,16)+'…')}`;
    paneSessEl.textContent='Session: '+s.session_id.substring(0,12)+'…';
    noSessEl.style.display='none';
    activePaneEl.style.display='flex';
    document.querySelectorAll('.chat-session-item').forEach(el=>{el.classList.toggle('active',el.dataset.sess===s.session_id);});
    msgsEl.innerHTML='';
    loadHistory(s.session_id);
    inputEl.focus();
  };

  function loadHistory(sessId){
    fetch(`${CHAT_HANDLER}?action=admin_poll&since=0&session_id=${encodeURIComponent(sessId)}`)
    .then(r=>r.json()).then(d=>{
      if(!d.ok) return;
      sessions=d.sessions||sessions; renderSessions(sessions);
      msgsEl.innerHTML=''; let lastDate='';
      (d.history||[]).forEach(m=>{
        const md=fmtDate(m.created_at);
        if(md!==lastDate){const sep=document.createElement('div');sep.className='chat-date-sep';sep.textContent=md;msgsEl.appendChild(sep);lastDate=md;}
        appendMessage(m.sender,m.message,m.created_at,false);
        lastMsgId=Math.max(lastMsgId,+m.id); globalLastId=Math.max(globalLastId,+m.id);
      });
      scrollToBottom();
    }).catch(console.error);
  }

  function appendMessage(sender,text,time,doScroll){
    const wrap=document.createElement('div'); wrap.className='chat-bubble-wrap '+sender;
    const bubble=document.createElement('div'); bubble.className='chat-bubble '+sender; bubble.textContent=text;
    const t=document.createElement('div'); t.className='chat-bubble-time';
    t.textContent=(sender==='admin'?'🏔️ You · ':'👤 User · ')+fmtTime(time);
    wrap.appendChild(bubble); wrap.appendChild(t); msgsEl.appendChild(wrap);
    if(doScroll!==false) scrollToBottom();
  }
  function scrollToBottom(){ msgsEl.scrollTop=msgsEl.scrollHeight; }

  function sendReply(){
    if(!activeSession) return;
    const txt=inputEl.value.trim(); if(!txt) return;
    appendMessage('admin',txt,new Date().toISOString().replace('T',' ').substring(0,19),true);
    inputEl.value=''; inputEl.style.height='auto'; sendBtn.disabled=true;
    fetch(CHAT_HANDLER,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'action=admin_reply&session_id='+encodeURIComponent(activeSession.session_id)+'&message='+encodeURIComponent(txt)})
    .then(r=>r.json()).then(d=>{if(d.ok) globalLastId=Math.max(globalLastId,+d.id);})
    .catch(console.error).finally(()=>{sendBtn.disabled=false;});
  }
  sendBtn.addEventListener('click',sendReply);
  inputEl.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendReply();}});
  inputEl.addEventListener('input',()=>{inputEl.style.height='auto';inputEl.style.height=Math.min(inputEl.scrollHeight,100)+'px';});

  function poll(){
    const sp=activeSession?'&session_id='+encodeURIComponent(activeSession.session_id):'';
    fetch(`${CHAT_HANDLER}?action=admin_poll&since=${globalLastId}${sp}`)
    .then(r=>r.json()).then(d=>{
      if(!d.ok) return;
      sessions=d.sessions||sessions; renderSessions(sessions);
      if(activeSession&&d.new_msgs){
        d.new_msgs.forEach(m=>{
          if(+m.id<=lastMsgId) return;
          if(m.session_id!==activeSession.session_id) return;
          if(m.sender==='user') appendMessage('user',m.message,m.created_at,true);
          lastMsgId=Math.max(lastMsgId,+m.id); globalLastId=Math.max(globalLastId,+m.id);
        });
      }
    }).catch(console.error);
  }

  function initialLoad(){
    fetch(`${CHAT_HANDLER}?action=admin_poll&since=0`)
    .then(r=>r.json()).then(d=>{
      if(!d.ok) return;
      sessions=d.sessions||[]; renderSessions(sessions);
      countEl.textContent=sessions.length+' conversation'+(sessions.length!==1?'s':'');
    }).catch(()=>{countEl.textContent='Could not load sessions';});
  }

  window.deleteAllChats=function(){
    if(!confirm('Delete ALL live chat messages permanently? This cannot be undone.')) return;
    const btn=document.getElementById('delete-all-chats-btn'); if(btn) btn.disabled=true;
    fetch(CHAT_HANDLER,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=admin_delete_all'})
    .then(r=>r.json()).then(d=>{
      if(!d.ok){alert(d.error||'Failed to delete chats.');return;}
      sessions=[]; activeSession=null; lastMsgId=0; globalLastId=0;
      renderSessions([]); msgsEl.innerHTML='';
      noSessEl.style.display='flex'; activePaneEl.style.display='none';
      countEl.textContent='0 conversations';
      if(totalBadgeEl) totalBadgeEl.style.display='none';
      if(sbBadgeEl) sbBadgeEl.style.display='none';
      alert('All chats deleted ('+(d.deleted??0)+' messages).');
    }).catch(()=>alert('Could not delete chats.'))
    .finally(()=>{if(btn) btn.disabled=false;});
  };

  initialLoad();
  setInterval(poll, 3000);
})();
<?php endif; ?>

// ── Sidebar chat badge polling (non-chat tabs) ─────────────────
<?php if ($activeTab !== 'chat'): ?>
(function(){
  const sbBadge=document.getElementById('sb-chat-badge');
  if(!sbBadge) return;
  setInterval(()=>{
    fetch(`${CHAT_HANDLER}?action=unread_count`)
    .then(r=>r.json()).then(d=>{
      if(!d.ok) return;
      if(d.count>0){sbBadge.textContent=d.count;sbBadge.style.display='inline';}
      else sbBadge.style.display='none';
    }).catch(()=>{});
  }, 10000);
})();
<?php endif; ?>
</script>
</body>
</html>