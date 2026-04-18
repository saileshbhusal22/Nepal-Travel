<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// ── Simple admin auth guard ──────────────────────────────────────
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header('Location: /Nepal-Travel/user/login.php'); exit;
// }

// ── Fetch stats ──────────────────────────────────────────────────
$total_users    = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_bookings = $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0];
$confirmed      = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetch_row()[0];
$cancelled      = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='booking cancel' OR status='cancelled'")->fetch_row()[0];
$pending        = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='active'")->fetch_row()[0];

// ── Fetch users ──────────────────────────────────────────────────
$users_result = $conn->query("SELECT id, full_name, username, email, phone, email_verified, phone_verified, created_at, profile_image FROM users ORDER BY id DESC");
$users = $users_result->fetch_all(MYSQLI_ASSOC);

// ── Fetch bookings with user name ────────────────────────────────
$bookings_result = $conn->query("
    SELECT b.id, b.user_id, b.name, b.destination, b.date, b.guests, b.status, b.created_at,
           u.full_name, u.email
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    ORDER BY b.id DESC
");
$bookings = $bookings_result->fetch_all(MYSQLI_ASSOC);

// ── Handle status update ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $bid    = (int)$_POST['booking_id'];
    $status = $conn->real_escape_string($_POST['status']);
    $conn->query("UPDATE bookings SET status='$status' WHERE id=$bid");
    header('Location: dashboard.php?tab=' . ($_GET['tab'] ?? 'bookings') . '&updated=1');
    exit;
}

// ── Handle delete user ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid = (int)$_POST['user_id'];
    $conn->query("DELETE FROM bookings WHERE user_id=$uid");
    $conn->query("DELETE FROM users WHERE id=$uid");
    header('Location: dashboard.php?tab=users&deleted=1');
    exit;
}

$activeTab = $_GET['tab'] ?? 'overview';
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
  --bg:#0C0E14;
  --surface:#13161F;
  --surface2:#1A1E2A;
  --border:rgba(255,255,255,0.07);
  --border2:rgba(255,255,255,0.12);
  --text:#F0EEE8;
  --muted:rgba(240,238,232,0.45);
  --muted2:rgba(240,238,232,0.25);
  --gold:#C9A227;
  --gold2:#E8C44A;
  --green:#2E7D52;
  --green2:#4CAF7D;
  --red:#8C2020;
  --red2:#E05555;
  --blue:#1E4A8C;
  --blue2:#4A90D9;
  --amber:#8C5A10;
  --amber2:#F0A030;
  --ff-display:'Syne',sans-serif;
  --ff-body:'DM Sans',sans-serif;
  --ff-mono:'DM Mono',monospace;
}
html,body{height:100%;background:var(--bg);color:var(--text);font-family:var(--ff-body);-webkit-font-smoothing:antialiased}
a{text-decoration:none;color:inherit}
button,input,select{font-family:var(--ff-body)}

/* ── LAYOUT ── */
.admin-wrap{display:flex;min-height:100vh}

/* ── SIDEBAR ── */
.sidebar{
  width:240px;flex-shrink:0;
  background:var(--surface);
  border-right:1px solid var(--border);
  display:flex;flex-direction:column;
  position:sticky;top:0;height:100vh;
  overflow-y:auto;
}
.sb-logo{padding:28px 24px 22px;border-bottom:1px solid var(--border)}
.sb-logo-title{
  font-family:var(--ff-display);font-size:17px;font-weight:800;
  color:var(--text);letter-spacing:-0.3px;
  display:flex;align-items:center;gap:10px;
}
.sb-logo-title em{font-style:italic;color:var(--gold)}
.sb-logo-sub{font-size:10px;color:var(--muted2);letter-spacing:2px;text-transform:uppercase;margin-top:4px;font-family:var(--ff-mono)}
.sb-nav{padding:16px 12px;flex:1}
.sb-section-label{
  font-size:9px;letter-spacing:2.5px;text-transform:uppercase;
  color:var(--muted2);font-weight:600;padding:0 12px;
  margin:16px 0 8px;font-family:var(--ff-mono);
}
.sb-link{
  display:flex;align-items:center;gap:11px;
  padding:10px 12px;border-radius:8px;
  font-size:13px;font-weight:500;color:var(--muted);
  transition:all 0.18s;cursor:pointer;
  border:1px solid transparent;
}
.sb-link svg{width:16px;height:16px;flex-shrink:0;opacity:0.6}
.sb-link:hover{background:var(--surface2);color:var(--text);border-color:var(--border)}
.sb-link.on{background:rgba(201,162,39,0.1);color:var(--gold);border-color:rgba(201,162,39,0.2)}
.sb-link.on svg{opacity:1}
.sb-badge{
  margin-left:auto;background:rgba(201,162,39,0.15);
  color:var(--gold);font-size:10px;font-weight:700;
  padding:2px 8px;border-radius:20px;font-family:var(--ff-mono);
}
.sb-footer{padding:16px 24px;border-top:1px solid var(--border)}
.sb-footer-user{display:flex;align-items:center;gap:10px}
.sb-avatar{
  width:34px;height:34px;border-radius:50%;
  background:rgba(201,162,39,0.2);border:1px solid rgba(201,162,39,0.3);
  display:flex;align-items:center;justify-content:center;
  font-family:var(--ff-display);font-size:13px;font-weight:700;color:var(--gold);
  flex-shrink:0;
}
.sb-footer-name{font-size:12px;font-weight:600;color:var(--text)}
.sb-footer-role{font-size:10px;color:var(--muted2);font-family:var(--ff-mono)}

/* ── MAIN ── */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden}

/* ── TOPBAR ── */
.topbar{
  background:var(--surface);
  border-bottom:1px solid var(--border);
  padding:0 36px;height:60px;
  display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;z-index:100;
}
.tb-breadcrumb{font-family:var(--ff-mono);font-size:11px;color:var(--muted2);letter-spacing:1px}
.tb-breadcrumb span{color:var(--gold)}
.tb-actions{display:flex;align-items:center;gap:14px}
.tb-tag{
  font-size:10px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;
  color:var(--green2);background:rgba(46,125,82,0.15);
  border:1px solid rgba(76,175,125,0.2);
  padding:5px 14px;border-radius:20px;font-family:var(--ff-mono);
}
.tb-time{font-size:11px;color:var(--muted2);font-family:var(--ff-mono)}

/* ── CONTENT ── */
.content{padding:36px;flex:1;overflow-y:auto}

/* ── SECTION HEADER ── */
.sec-hd{display:flex;align-items:center;gap:20px;margin-bottom:28px}
.sec-hd-title{font-family:var(--ff-display);font-size:26px;font-weight:800;color:var(--text)}
.sec-hd-rule{flex:1;height:1px;background:var(--border2)}
.sec-hd-count{font-family:var(--ff-mono);font-size:11px;color:var(--muted2);letter-spacing:1px}

/* ── STAT CARDS ── */
.stats-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:36px}
.stat-card{
  background:var(--surface);border:1px solid var(--border);
  border-radius:14px;padding:22px 20px;
  position:relative;overflow:hidden;
  transition:border-color 0.2s,transform 0.2s;
}
.stat-card:hover{border-color:var(--border2);transform:translateY(-2px)}
.stat-card::before{
  content:'';position:absolute;top:0;left:0;right:0;height:2px;
  background:var(--accent,var(--gold));
}
.stat-card-n{font-family:var(--ff-display);font-size:38px;font-weight:800;color:var(--text);line-height:1;margin-bottom:6px}
.stat-card-l{font-size:11px;color:var(--muted);letter-spacing:1.5px;text-transform:uppercase;font-weight:600}
.stat-card-ico{position:absolute;top:18px;right:18px;font-size:22px;opacity:0.18}

/* ── TABLE CARD ── */
.tcard{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:32px}
.tcard-hd{
  padding:18px 24px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;
  background:var(--surface2);
}
.tcard-hd-title{font-family:var(--ff-display);font-size:15px;font-weight:700;color:var(--text)}
.tcard-hd-sub{font-size:11px;color:var(--muted2);font-family:var(--ff-mono);margin-top:2px}

/* search + filter bar */
.tcard-search{
  padding:14px 24px;border-bottom:1px solid var(--border);
  background:var(--surface);
  display:flex;align-items:center;gap:16px;flex-wrap:wrap;
}
.search-inp{
  flex:1;min-width:200px;
  padding:9px 14px;background:var(--surface2);
  border:1px solid var(--border2);border-radius:7px;
  font-size:13px;color:var(--text);outline:none;
  transition:border-color 0.2s;
}
.search-inp::placeholder{color:var(--muted2)}
.search-inp:focus{border-color:rgba(201,162,39,0.4)}

/* filter buttons */
.filter-btns{display:flex;gap:8px;flex-wrap:wrap}
.filter-btn{
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 14px;border-radius:20px;
  font-size:11px;font-weight:600;letter-spacing:0.5px;
  border:1px solid var(--border2);
  background:var(--surface2);color:var(--muted);
  cursor:pointer;transition:all 0.18s;font-family:var(--ff-mono);
  white-space:nowrap;
}
.filter-btn:hover{border-color:var(--border2);color:var(--text)}
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
.fb-count{
  background:rgba(255,255,255,0.07);
  color:inherit;border-radius:10px;
  padding:1px 7px;font-size:10px;
}

/* table */
.tscroll{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead th{
  font-size:9px;letter-spacing:2.5px;text-transform:uppercase;
  color:var(--muted2);font-weight:700;
  padding:12px 20px;text-align:left;
  background:var(--surface2);border-bottom:1px solid var(--border);
  white-space:nowrap;font-family:var(--ff-mono);
}
tbody td{padding:13px 20px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover td{background:rgba(255,255,255,0.02)}
.mono{font-family:var(--ff-mono);font-size:11px;color:var(--muted)}

/* pills */
.pill{
  display:inline-flex;align-items:center;gap:5px;
  padding:3px 10px;border-radius:20px;
  font-size:10px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;
  font-family:var(--ff-mono);white-space:nowrap;
}
.pill-confirmed{background:rgba(46,125,82,0.2);color:var(--green2);border:1px solid rgba(76,175,125,0.2)}
.pill-cancelled{background:rgba(140,32,32,0.2);color:var(--red2);border:1px solid rgba(224,85,85,0.2)}
.pill-pending{background:rgba(140,90,16,0.2);color:var(--amber2);border:1px solid rgba(240,160,48,0.2)}
.pill-verified{background:rgba(46,125,82,0.15);color:var(--green2);border:1px solid rgba(76,175,125,0.15)}
.pill-unverified{background:rgba(140,32,32,0.15);color:var(--red2);border:1px solid rgba(224,85,85,0.15)}
.dot{width:5px;height:5px;border-radius:50%;background:currentColor;display:inline-block}

/* avatar */
.av{
  width:32px;height:32px;border-radius:50%;
  background:rgba(201,162,39,0.15);border:1px solid rgba(201,162,39,0.2);
  display:inline-flex;align-items:center;justify-content:center;
  font-size:12px;font-weight:700;color:var(--gold);
  font-family:var(--ff-display);flex-shrink:0;overflow:hidden;
}
.av img{width:100%;height:100%;object-fit:cover}
.user-cell{display:flex;align-items:center;gap:10px}
.user-cell-name{font-size:13px;font-weight:600;color:var(--text)}
.user-cell-email{font-size:11px;color:var(--muted);font-family:var(--ff-mono)}

/* action buttons */
.act-btn{
  display:inline-flex;align-items:center;gap:5px;
  font-size:11px;font-weight:600;padding:5px 12px;
  border-radius:6px;border:1px solid;cursor:pointer;
  transition:all 0.15s;white-space:nowrap;background:transparent;
  font-family:var(--ff-body);
}
.act-btn-del{color:var(--red2);border-color:rgba(224,85,85,0.25)}
.act-btn-del:hover{background:var(--red2);color:#fff;border-color:var(--red2)}

/* status select */
.status-sel{
  background:var(--surface2);color:var(--text);
  border:1px solid var(--border2);border-radius:6px;
  padding:5px 10px;font-size:12px;cursor:pointer;
  outline:none;font-family:var(--ff-mono);
}
.status-sel:focus{border-color:rgba(201,162,39,0.4)}

/* save btn */
.save-btn{
  background:var(--gold);color:#000;
  border:none;border-radius:6px;
  padding:5px 14px;font-size:11px;font-weight:700;
  cursor:pointer;transition:background 0.15s;font-family:var(--ff-body);
}
.save-btn:hover{background:var(--gold2)}

/* empty */
.empty{padding:60px;text-align:center;color:var(--muted2)}
.empty-ico{font-size:44px;opacity:0.2;margin-bottom:12px}
.empty p{font-size:13px}

/* no-results row */
.no-results-row{display:none}
.no-results-row td{text-align:center;padding:40px;color:var(--muted2);font-size:13px}

/* toast */
.toast{
  position:fixed;bottom:28px;right:28px;
  background:var(--surface);border:1px solid var(--border2);
  color:var(--text);padding:13px 20px;border-radius:8px;
  font-size:13px;font-weight:500;
  box-shadow:0 8px 32px rgba(0,0,0,0.4);
  transform:translateY(12px);opacity:0;
  transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1);
  z-index:9999;pointer-events:none;
  border-left:3px solid var(--green2);
}
.toast.show{transform:translateY(0);opacity:1}

/* overview grid */
.ov-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:8px}
.mini-table tbody td{padding:10px 16px;font-size:12px}
.mini-table thead th{padding:10px 16px}

/* responsive */
@media(max-width:1200px){.stats-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:1024px){.sidebar{width:200px}.ov-grid{grid-template-columns:1fr}}
@media(max-width:768px){
  .sidebar{display:none}
  .stats-grid{grid-template-columns:repeat(2,1fr)}
  .content{padding:20px}
  .topbar{padding:0 20px}
  .tcard-search{flex-direction:column;align-items:stretch}
  .filter-btns{justify-content:flex-start}
}
</style>
</head>
<body>

<div class="admin-wrap">

  <!-- ── SIDEBAR ── -->
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

      <div class="sb-section-label" style="margin-top:24px">Links</div>
      <a href="/Nepal-Travel/Public/index.php" class="sb-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        View Site
      </a>
      <a href="deals_crud.php" class="sb-link">
  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42z"/></svg>
  Deals & Packages
</a>
      <a href="/Nepal-Travel/user/logout.php" class="sb-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
        Sign Out
      </a>
    </nav>

    <div class="sb-footer">
      <div class="sb-footer-user">
        <div class="sb-avatar">A</div>
        <div>
          <div class="sb-footer-name">Admin</div>
          <div class="sb-footer-role">// Super Admin</div>
        </div>
      </div>
    </div>
  </aside>

  <!-- ── MAIN ── -->
  <div class="main">

    <!-- topbar -->
    <header class="topbar">
      <div class="tb-breadcrumb">
        NEPAL TRAVEL / <span><?= strtoupper($activeTab) ?></span>
      </div>
      <div class="tb-actions">
        <span class="tb-tag">● LIVE</span>
        <span class="tb-time" id="clock"></span>
      </div>
    </header>

    <!-- content -->
    <div class="content">

      <?php if (isset($_GET['updated'])): ?>
        <script>document.addEventListener('DOMContentLoaded',()=>showToast('✓ Booking status updated successfully'));</script>
      <?php endif; ?>
      <?php if (isset($_GET['deleted'])): ?>
        <script>document.addEventListener('DOMContentLoaded',()=>showToast('✓ User deleted successfully'));</script>
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

        <div class="ov-grid">
          <!-- Recent Bookings -->
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
                <thead>
                  <tr><th>ID</th><th>Name</th><th>Destination</th><th>Status</th></tr>
                </thead>
                <tbody>
                  <?php foreach(array_slice($bookings,0,5) as $b): ?>
                  <?php
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

          <!-- Recent Users -->
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
                <thead>
                  <tr><th>User</th><th>Username</th><th>Verified</th></tr>
                </thead>
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
                <tr>
                  <th>ID</th><th>User</th><th>Username</th><th>Phone</th>
                  <th>Email Verified</th><th>Phone Verified</th><th>Joined</th><th>Actions</th>
                </tr>
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
                    <?php if($u['email_verified']): ?>
                      <span class="pill pill-verified"><span class="dot"></span>Verified</span>
                    <?php else: ?>
                      <span class="pill pill-unverified"><span class="dot"></span>Unverified</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if($u['phone_verified']): ?>
                      <span class="pill pill-verified"><span class="dot"></span>Verified</span>
                    <?php else: ?>
                      <span class="pill pill-unverified"><span class="dot"></span>Unverified</span>
                    <?php endif; ?>
                  </td>
                  <td class="mono"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                  <td>
                    <form method="POST" action="?tab=users" style="display:inline"
                          onsubmit="return confirm('Delete this user and all their bookings?')">
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <button type="submit" name="delete_user" class="act-btn act-btn-del">
                        <svg viewBox="0 0 24 24" fill="currentColor" style="width:12px;height:12px">
                          <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                        </svg>
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
            <!-- Summary pills in header -->
            <div style="display:flex;gap:8px;align-items:center">
              <span class="pill pill-confirmed"><span class="dot"></span><?= $confirmed ?> Confirmed</span>
              <span class="pill pill-pending"><span class="dot"></span><?= $pending ?> Pending</span>
              <span class="pill pill-cancelled"><span class="dot"></span><?= $cancelled ?> Cancelled</span>
            </div>
          </div>

          <!-- Search + Filter bar -->
          <div class="tcard-search">
            <input type="text" class="search-inp" id="bkSearch"
                   placeholder="Search by name, destination, user…"
                   oninput="filterBookings()">
            <div class="filter-btns">
              <button class="filter-btn f-all active" data-status="all" onclick="setFilter(this)">
                <span class="fb-dot"></span>
                All
                <span class="fb-count"><?= $total_bookings ?></span>
              </button>
              <button class="filter-btn f-confirmed" data-status="confirmed" onclick="setFilter(this)">
                <span class="fb-dot"></span>
                Confirmed
                <span class="fb-count"><?= $confirmed ?></span>
              </button>
              <button class="filter-btn f-pending" data-status="pending" onclick="setFilter(this)">
                <span class="fb-dot"></span>
                Pending
                <span class="fb-count"><?= $pending ?></span>
              </button>
              <button class="filter-btn f-cancelled" data-status="cancelled" onclick="setFilter(this)">
                <span class="fb-dot"></span>
                Cancelled
                <span class="fb-count"><?= $cancelled ?></span>
              </button>
            </div>
          </div>

          <div class="tscroll">
            <table id="bkTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Booking Name</th>
                  <th>User Account</th>
                  <th>Destination</th>
                  <th>Travel Date</th>
                  <th>Guests</th>
                  <th>Status</th>
                  <th>Booked On</th>
                  <th>Update Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if(empty($bookings)): ?>
                  <tr><td colspan="9"><div class="empty"><div class="empty-ico">📋</div><p>No bookings found.</p></div></td></tr>
                <?php else: ?>
                  <?php foreach($bookings as $b): ?>
                  <?php
                    $s   = strtolower($b['status']);
                    $cls = str_contains($s,'confirm') ? 'confirmed' : (str_contains($s,'cancel') ? 'cancelled' : 'pending');
                    // data-status attribute used by JS filter
                    $ds  = $cls; // confirmed | pending | cancelled
                  ?>
                  <tr data-status="<?= $ds ?>">
                    <td class="mono">#<?= str_pad($b['id'],6,'0',STR_PAD_LEFT) ?></td>
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
                    <td>
                      <span class="pill pill-<?= $cls ?>">
                        <span class="dot"></span><?= htmlspecialchars(ucfirst($b['status'])) ?>
                      </span>
                    </td>
                    <td class="mono"><?= date('d M Y', strtotime($b['created_at'])) ?></td>
                    <td>
                      <form method="POST" action="?tab=bookings" style="display:flex;gap:6px;align-items:center">
                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                        <select name="status" class="status-sel">
                          <option value="pending"        <?= $b['status']==='pending'       ?'selected':'' ?>>Pending</option>
                          <option value="confirmed"      <?= $b['status']==='confirmed'     ?'selected':'' ?>>Confirmed</option>
                          <option value="booking cancel" <?= $b['status']==='booking cancel'?'selected':'' ?>>Cancelled</option>
                        </select>
                        <button type="submit" name="update_status" class="save-btn">Save</button>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <!-- shown when filter yields 0 results -->
                  <tr class="no-results-row" id="bkNoResults">
                    <td colspan="9" style="text-align:center;padding:48px;color:var(--muted2);font-size:13px">
                      No bookings match your current filter.
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      <?php endif; ?>

    </div><!-- /content -->
  </div><!-- /main -->
</div><!-- /admin-wrap -->

<div class="toast" id="toast"></div>

<script>
// ── Clock ────────────────────────────────────────────────────────
function updateClock(){
  document.getElementById('clock').textContent =
    new Date().toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
updateClock(); setInterval(updateClock, 1000);

// ── Toast ────────────────────────────────────────────────────────
function showToast(msg){
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3200);
}

// ── Users table search ───────────────────────────────────────────
function filterTable(inputId, tableId){
  const q = document.getElementById(inputId).value.toLowerCase();
  document.querySelectorAll('#' + tableId + ' tbody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// ── Bookings: combined status filter + search ────────────────────
let activeStatus = 'all';

function setFilter(btn) {
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  activeStatus = btn.dataset.status;
  filterBookings();
}

function filterBookings() {
  const q = (document.getElementById('bkSearch')?.value || '').toLowerCase();
  let visible = 0;

  document.querySelectorAll('#bkTable tbody tr:not(#bkNoResults)').forEach(row => {
    const rowStatus  = (row.dataset.status || '').toLowerCase();   // confirmed | pending | cancelled
    const matchText  = !q || row.textContent.toLowerCase().includes(q);
    const matchStatus =
      activeStatus === 'all'       ? true :
      activeStatus === 'confirmed' ? rowStatus === 'confirmed' :
      activeStatus === 'pending'   ? rowStatus === 'pending'   :
      activeStatus === 'cancelled' ? rowStatus === 'cancelled' : true;

    const show = matchText && matchStatus;
    row.style.display = show ? '' : 'none';
    if (show) visible++;
  });

  // show/hide the "no results" row
  const noRes = document.getElementById('bkNoResults');
  if (noRes) noRes.style.display = visible === 0 ? '' : 'none';
}
</script>
</body>
</html>