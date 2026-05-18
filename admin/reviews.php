<?php
session_name('nepal_admin_session');
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config/db.php';

// ── Auth guard ───────────────────────────────────────────────────
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header('Location: /Nepal-Travel/user/login.php'); exit;
// }
$admin_id = (int)($_SESSION['user_id'] ?? 1);

// ════════════════════════════════════════════════════════════════
//  ALL POST ACTIONS — must be before any output
// ════════════════════════════════════════════════════════════════

// ── Delete Deal Review ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_deal_review'])) {
    $review_id = (int)$_POST['review_id'];
    $conn->query("DELETE FROM deal_reviews WHERE id=$review_id");
    header('Location: reviews.php?tab=deals&deleted=1'); exit;
}

// ── Delete User Deal Review ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_deal_review'])) {
    $review_id = (int)$_POST['review_id'];
    $conn->query("DELETE FROM user_deal_reviews WHERE id=$review_id");
    header('Location: reviews.php?tab=userdeals&deleted=1'); exit;
}

// ════════════════════════════════════════════════════════════════
//  DATA FETCHING
// ════════════════════════════════════════════════════════════════

$activeTab = $_GET['tab'] ?? 'deals';

// ── Deal Reviews ─────────────────────────────────────────────────
$deal_reviews_result = $conn->query("
    SELECT dr.*, u.full_name AS user_name, u.email AS user_email, 
           u.profile_image, d.title AS deal_name
    FROM deal_reviews dr
    LEFT JOIN users u ON u.id = dr.user_id
    LEFT JOIN deals d ON d.id = dr.deal_id
    ORDER BY dr.created_at DESC
");
$deal_reviews = $deal_reviews_result ? $deal_reviews_result->fetch_all(MYSQLI_ASSOC) : [];

// ── User Deal Reviews ────────────────────────────────────────────
$user_deal_reviews_result = $conn->query("
    SELECT udr.*, u.full_name AS user_name, u.email AS user_email,
           u.profile_image, ud.title AS deal_title
    FROM user_deal_reviews udr
    LEFT JOIN users u ON u.id = udr.user_id
    LEFT JOIN user_deals ud ON ud.id = udr.ud_id
    ORDER BY udr.created_at DESC
");
$user_deal_reviews = $user_deal_reviews_result ? $user_deal_reviews_result->fetch_all(MYSQLI_ASSOC) : [];

// ── Calculate average ratings ────────────────────────────────────
$avg_deal_rating = 0;
if (!empty($deal_reviews)) {
    $avg_deal_rating = array_sum(array_column($deal_reviews, 'rating')) / count($deal_reviews);
}

$avg_user_deal_rating = 0;
if (!empty($user_deal_reviews)) {
    $avg_user_deal_rating = array_sum(array_column($user_deal_reviews, 'rating')) / count($user_deal_reviews);
}

// ── Flash message ────────────────────────────────────────────────
$msg = ''; $msg_type = 'success';
if (isset($_GET['deleted'])) { $msg = '✓ Review deleted successfully.'; $msg_type = 'success'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reviews Manager — Nepal Travel Admin</title>
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

/* ── LAYOUT ── */
.admin-wrap{display:flex;min-height:100vh}

/* ── SIDEBAR ── */
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
.sb-footer{padding:16px 24px;border-top:1px solid var(--border)}

/* ── MAIN ── */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden}
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:0 36px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.tb-breadcrumb{font-family:var(--ff-m);font-size:11px;color:var(--muted2);letter-spacing:1px}
.tb-breadcrumb span{color:var(--gold)}
.tb-actions{display:flex;align-items:center;gap:14px}
.tb-tag{font-size:10px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;color:var(--green2);background:rgba(46,125,82,0.15);border:1px solid rgba(76,175,125,0.2);padding:5px 14px;border-radius:20px;font-family:var(--ff-m)}
.tb-time{font-size:11px;color:var(--muted2);font-family:var(--ff-m)}
.content{padding:36px;flex:1;overflow-y:auto}

/* ── ALERT ── */
.alert{display:flex;align-items:center;gap:12px;padding:13px 20px;border-radius:8px;font-size:13px;margin-bottom:24px;border-left:3px solid}
.alert-success{background:rgba(76,175,125,0.1);color:var(--green2);border-color:var(--green2)}
.alert-error{background:rgba(224,85,85,0.1);color:var(--red2);border-color:var(--red2)}

/* ── SECTION HEADER ── */
.sec-hd{display:flex;align-items:center;gap:20px;margin-bottom:28px}
.sec-hd-title{font-family:var(--ff-d);font-size:26px;font-weight:800;color:var(--text)}
.sec-hd-rule{flex:1;height:1px;background:var(--border2)}
.sec-hd-count{font-family:var(--ff-m);font-size:11px;color:var(--muted2);letter-spacing:1px}

/* ── STAT CARDS ── */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:36px}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:22px 20px;position:relative;overflow:hidden;transition:border-color 0.2s,transform 0.2s}
.stat-card:hover{border-color:var(--border2);transform:translateY(-2px)}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:var(--accent,var(--gold))}
.stat-card-n{font-family:var(--ff-d);font-size:38px;font-weight:800;color:var(--text);line-height:1;margin-bottom:6px}
.stat-card-l{font-size:11px;color:var(--muted);letter-spacing:1.5px;text-transform:uppercase;font-weight:600}
.stat-card-ico{position:absolute;top:18px;right:18px;font-size:22px;opacity:0.18}
.stat-card-sub{font-size:12px;color:var(--muted);margin-top:6px;font-family:var(--ff-m)}

/* ── SUB TABS ── */
.sub-tabs{display:flex;gap:4px;margin-bottom:1.5rem;background:var(--surface2);padding:4px;border-radius:10px;width:fit-content;border:1px solid var(--border)}
.sub-tab-btn{padding:9px 22px;border-radius:7px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all 0.15s;font-family:var(--ff-b);color:var(--muted);background:transparent;display:flex;align-items:center;gap:8px}
.sub-tab-btn.active{background:var(--gold);color:#000}

/* ── TABLE CARD ── */
.tcard{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:32px}
.tcard-hd{padding:18px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface2);flex-wrap:wrap;gap:10px}
.tcard-hd-title{font-family:var(--ff-d);font-size:15px;font-weight:700;color:var(--text)}
.tcard-hd-sub{font-size:11px;color:var(--muted2);font-family:var(--ff-m);margin-top:2px}
.tcard-search{padding:14px 24px;border-bottom:1px solid var(--border);background:var(--surface);display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.search-inp{flex:1;min-width:200px;padding:9px 14px;background:var(--surface2);border:1px solid var(--border2);border-radius:7px;font-size:13px;color:var(--text);outline:none;transition:border-color 0.2s}
.search-inp::placeholder{color:var(--muted2)}
.search-inp:focus{border-color:rgba(201,162,39,0.4)}

/* ── TABLE ── */
.tscroll{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead th{font-size:9px;letter-spacing:2.5px;text-transform:uppercase;color:var(--muted2);font-weight:700;padding:12px 20px;text-align:left;background:var(--surface2);border-bottom:1px solid var(--border);white-space:nowrap;font-family:var(--ff-m)}
tbody td{padding:13px 20px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover td{background:rgba(255,255,255,0.02)}
.mono{font-family:var(--ff-m);font-size:11px;color:var(--muted)}

/* ── AVATAR ── */
.av{width:32px;height:32px;border-radius:50%;background:rgba(201,162,39,0.15);border:1px solid rgba(201,162,39,0.2);display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--gold);font-family:var(--ff-d);flex-shrink:0;overflow:hidden}
.av img{width:100%;height:100%;object-fit:cover}
.user-cell{display:flex;align-items:center;gap:10px}
.user-cell-name{font-size:13px;font-weight:600;color:var(--text)}
.user-cell-email{font-size:11px;color:var(--muted);font-family:var(--ff-m)}

/* ── RATING STARS ── */
.stars{color:var(--gold2);font-size:14px;letter-spacing:1px}
.stars-muted{color:var(--muted2)}

/* ── ACTION BUTTONS ── */
.act-row{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:6px;font-size:11px;font-weight:700;border:1px solid;cursor:pointer;transition:all 0.15s;white-space:nowrap;background:transparent;font-family:var(--ff-b)}
.btn-delete{color:var(--red2);border-color:rgba(224,85,85,0.25)}
.btn-delete:hover{background:var(--red2);color:#fff;border-color:var(--red2)}
.btn-view{color:var(--blue2);border-color:rgba(74,144,217,0.25)}
.btn-view:hover{background:var(--blue2);color:#fff;border-color:var(--blue2)}

/* ── REVIEW TEXT ── */
.review-text{max-width:300px;line-height:1.5;color:var(--muted)}
.review-text-short{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

/* ── EMPTY ── */
.empty{padding:50px;text-align:center;color:var(--muted2)}
.empty-ico{font-size:40px;opacity:0.2;margin-bottom:12px}
.empty p{font-size:13px}

/* ── MODAL ── */
.modal-bd{display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.8);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:1.5rem}
.modal-bd.open{display:flex}
.modal-box{background:#13161f;border:1px solid rgba(255,255,255,0.1);border-radius:18px;width:100%;max-width:600px;max-height:90vh;overflow-y:auto;box-shadow:0 40px 80px rgba(0,0,0,0.7);animation:mIn 0.2s ease}
@keyframes mIn{from{opacity:0;transform:translateY(16px) scale(0.97)}to{opacity:1;transform:none}}
.modal-hd{padding:1.3rem 1.5rem 1rem;border-bottom:1px solid rgba(255,255,255,0.07);display:flex;align-items:center;justify-content:space-between}
.modal-hd-title{font-family:var(--ff-d);font-size:15px;font-weight:700;color:var(--text)}
.modal-close{background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);color:var(--muted);width:30px;height:30px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:15px;transition:all 0.15s}
.modal-close:hover{background:rgba(224,85,85,0.2);color:#ff6b6b}
.modal-body{padding:1.4rem 1.5rem 1.6rem}
.btn-confirm{width:100%;padding:12px;border:none;border-radius:8px;font-family:var(--ff-b);font-size:13px;font-weight:700;cursor:pointer;margin-top:14px;transition:all 0.15s}
.btn-confirm-red{background:var(--red2);color:#fff}
.btn-confirm-red:hover{background:#c93333}

/* ── TOAST ── */
.toast{position:fixed;bottom:28px;right:28px;background:var(--surface);border:1px solid var(--border2);color:var(--text);padding:13px 20px;border-radius:8px;font-size:13px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,0.4);transform:translateY(12px);opacity:0;transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1);z-index:9999;pointer-events:none;border-left:3px solid var(--green2)}
.toast.show{transform:translateY(0);opacity:1}

@media(max-width:1200px){.stats-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){.sidebar{display:none}.stats-grid{grid-template-columns:1fr}.content{padding:20px}.topbar{padding:0 20px}}
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
      <div class="sb-logo-sub">Reviews Manager</div>
    </div>

    <nav class="sb-nav">
      <div class="sb-section-label">Navigation</div>

      <a href="dashboard.php?tab=overview" class="sb-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
        Dashboard
      </a>
      <a href="dashboard.php?tab=users" class="sb-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        Users
      </a>
      <a href="dashboard.php?tab=bookings" class="sb-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
        Bookings
      </a>
      <a href="dashboard.php?tab=subscriptions" class="sb-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
        Subscriptions
      </a>
      <a href="reviews.php" class="sb-link on">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24zM12 15.4l-3.76 2.27 1-4.28-3.32-2.88 4.38-.38L12 6.1l1.71 4.04 4.38.38-3.32 2.88 1 4.28L12 15.4z"/></svg>
        Reviews
        <span class="sb-badge"><?= count($deal_reviews) + count($user_deal_reviews) ?></span>
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
      <a href="logout.php" class="sb-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
        Sign Out
      </a>
    </nav>

    <div class="sb-footer">
      <div style="display:flex;align-items:center;gap:10px">
        <div class="av" style="width:34px;height:34px;font-size:13px">A</div>
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
      <div class="tb-breadcrumb">
        NEPAL TRAVEL / <span>REVIEWS MANAGER</span>
      </div>
      <div class="tb-actions">
        <span class="tb-tag">● LIVE</span>
        <span class="tb-time" id="clock"></span>
      </div>
    </header>

    <div class="content">

      <?php if ($msg): ?>
        <div class="alert alert-<?= htmlspecialchars($msg_type) ?>"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <div class="sec-hd">
        <h1 class="sec-hd-title">Reviews Manager</h1>
        <div class="sec-hd-rule"></div>
        <span class="sec-hd-count"><?= count($deal_reviews) + count($user_deal_reviews) ?> TOTAL</span>
      </div>

      <!-- Stats row -->
      <div class="stats-grid">
        <div class="stat-card" style="--accent:#C9A227">
          <div class="stat-card-ico">⭐</div>
          <div class="stat-card-n"><?= count($deal_reviews) + count($user_deal_reviews) ?></div>
          <div class="stat-card-l">Total Reviews</div>
        </div>
        <div class="stat-card" style="--accent:#4CAF7D">
          <div class="stat-card-ico">🏔️</div>
          <div class="stat-card-n"><?= count($deal_reviews) ?></div>
          <div class="stat-card-l">Deal Reviews</div>
          <div class="stat-card-sub">Avg: <?= number_format($avg_deal_rating, 1) ?> ★</div>
        </div>
        <div class="stat-card" style="--accent:#4A90D9">
          <div class="stat-card-ico">👥</div>
          <div class="stat-card-n"><?= count($user_deal_reviews) ?></div>
          <div class="stat-card-l">User Deal Reviews</div>
          <div class="stat-card-sub">Avg: <?= number_format($avg_user_deal_rating, 1) ?> ★</div>
        </div>
        <div class="stat-card" style="--accent:#E8C44A">
          <div class="stat-card-ico">📊</div>
          <div class="stat-card-n"><?= number_format(($avg_deal_rating + $avg_user_deal_rating) / 2, 1) ?></div>
          <div class="stat-card-l">Overall Avg Rating</div>
          <div class="stat-card-sub">Combined average</div>
        </div>
      </div>

      <!-- Sub-tabs: Deal Reviews / User Deal Reviews -->
      <div class="sub-tabs">
        <button class="sub-tab-btn <?= $activeTab === 'deals' ? 'active' : '' ?>" id="rtab-deals" onclick="switchTab('deals')">
          🏔️ Deal Reviews (<?= count($deal_reviews) ?>)
        </button>
        <button class="sub-tab-btn <?= $activeTab === 'userdeals' ? 'active' : '' ?>" id="rtab-userdeals" onclick="switchTab('userdeals')">
          👥 User Deal Reviews (<?= count($user_deal_reviews) ?>)
        </button>
      </div>

      <!-- ── DEAL REVIEWS PANEL ── -->
      <div id="rpanel-deals" style="display:<?= $activeTab === 'deals' ? 'block' : 'none' ?>">
        <div class="tcard">
          <div class="tcard-hd">
            <div>
              <div class="tcard-hd-title">Deal Reviews</div>
              <div class="tcard-hd-sub">Reviews from main deals catalog</div>
            </div>
          </div>
          <div class="tcard-search">
            <input type="text" class="search-inp" placeholder="Search by user, deal, review text…" oninput="filterTable('dealReviewsTable', this.value)">
          </div>
          <div class="tscroll">
            <table id="dealReviewsTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>User</th>
                  <th>Deal</th>
                  <th>Booking ID</th>
                  <th>Rating</th>
                  <th>Review Text</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if(empty($deal_reviews)): ?>
                  <tr><td colspan="8"><div class="empty"><div class="empty-ico">⭐</div><p>No deal reviews yet.</p></div></td></tr>
                <?php endif; ?>
                <?php foreach($deal_reviews as $r): ?>
                <tr>
                  <td class="mono">#<?= $r['id'] ?></td>
                  <td>
                    <div class="user-cell">
                      <div class="av">
                        <?php if(!empty($r['profile_image']) && $r['profile_image']!=='default.png'): ?>
                          <img src="/Nepal-Travel/<?= ltrim($r['profile_image'],'/') ?>" alt="">
                        <?php else: ?>
                          <?= strtoupper(substr($r['user_name'] ?? 'U', 0, 1)) ?>
                        <?php endif; ?>
                      </div>
                      <div>
                        <div class="user-cell-name"><?= htmlspecialchars($r['user_name'] ?? 'User #'.$r['user_id']) ?></div>
                        <div class="user-cell-email"><?= htmlspecialchars($r['user_email'] ?? '') ?></div>
                      </div>
                    </div>
                  </td>
                  <td style="font-weight:500;color:var(--gold)"><?= htmlspecialchars($r['deal_name'] ?? 'Deal #'.$r['deal_id']) ?></td>
                  <td class="mono"><?= $r['booking_id'] ? '#'.$r['booking_id'] : '—' ?></td>
                  <td>
                    <span class="stars">
                      <?= str_repeat('★', (int)$r['rating']) ?><span class="stars-muted"><?= str_repeat('☆', 5-(int)$r['rating']) ?></span>
                    </span>
                    <div class="mono" style="font-size:10px">(<?= $r['rating'] ?>/5)</div>
                  </td>
                  <td>
                    <div class="review-text review-text-short" title="<?= htmlspecialchars($r['review_text']) ?>">
                      <?= htmlspecialchars($r['review_text']) ?>
                    </div>
                  </td>
                  <td class="mono"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                  <td>
                    <div class="act-row">
                      <button class="btn btn-view" onclick="viewReview(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>, 'deal')">
                        👁 View
                      </button>
                      <button class="btn btn-delete" onclick="confirmDelete(<?= $r['id'] ?>, 'deal')">
                        🗑 Delete
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ── USER DEAL REVIEWS PANEL ── -->
      <div id="rpanel-userdeals" style="display:<?= $activeTab === 'userdeals' ? 'block' : 'none' ?>">
        <div class="tcard">
          <div class="tcard-hd">
            <div>
              <div class="tcard-hd-title">User Deal Reviews</div>
              <div class="tcard-hd-sub">Reviews from user-submitted deals</div>
            </div>
          </div>
          <div class="tcard-search">
            <input type="text" class="search-inp" placeholder="Search by user, deal, review text…" oninput="filterTable('userDealReviewsTable', this.value)">
          </div>
          <div class="tscroll">
            <table id="userDealReviewsTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>User</th>
                  <th>Deal</th>
                  <th>Booking ID</th>
                  <th>Rating</th>
                  <th>Review Text</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if(empty($user_deal_reviews)): ?>
                  <tr><td colspan="8"><div class="empty"><div class="empty-ico">👥</div><p>No user deal reviews yet.</p></div></td></tr>
                <?php endif; ?>
                <?php foreach($user_deal_reviews as $r): ?>
                <tr>
                  <td class="mono">#<?= $r['id'] ?></td>
                  <td>
                    <div class="user-cell">
                      <div class="av">
                        <?php if(!empty($r['profile_image']) && $r['profile_image']!=='default.png'): ?>
                          <img src="/Nepal-Travel/<?= ltrim($r['profile_image'],'/') ?>" alt="">
                        <?php else: ?>
                          <?= strtoupper(substr($r['user_name'] ?? 'U', 0, 1)) ?>
                        <?php endif; ?>
                      </div>
                      <div>
                        <div class="user-cell-name"><?= htmlspecialchars($r['user_name'] ?? 'User #'.$r['user_id']) ?></div>
                        <div class="user-cell-email"><?= htmlspecialchars($r['user_email'] ?? '') ?></div>
                      </div>
                    </div>
                  </td>
                  <td style="font-weight:500;color:var(--blue2)"><?= htmlspecialchars($r['deal_title'] ?? 'Deal #'.$r['ud_id']) ?></td>
                  <td class="mono"><?= $r['booking_id'] ? '#'.$r['booking_id'] : '—' ?></td>
                  <td>
                    <span class="stars">
                      <?= str_repeat('★', (int)$r['rating']) ?><span class="stars-muted"><?= str_repeat('☆', 5-(int)$r['rating']) ?></span>
                    </span>
                    <div class="mono" style="font-size:10px">(<?= $r['rating'] ?>/5)</div>
                  </td>
                  <td>
                    <div class="review-text review-text-short" title="<?= htmlspecialchars($r['review_text']) ?>">
                      <?= htmlspecialchars($r['review_text']) ?>
                    </div>
                  </td>
                  <td class="mono"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                  <td>
                    <div class="act-row">
                      <button class="btn btn-view" onclick="viewReview(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>, 'userdeal')">
                        👁 View
                      </button>
                      <button class="btn btn-delete" onclick="confirmDelete(<?= $r['id'] ?>, 'userdeal')">
                        🗑 Delete
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div><!-- /content -->
  </div><!-- /main -->
</div><!-- /admin-wrap -->

<!-- ══ VIEW REVIEW MODAL ══ -->
<div class="modal-bd" id="viewModal" onclick="closeBd(event,'viewModal')">
  <div class="modal-box">
    <div class="modal-hd">
      <div class="modal-hd-title" id="modal_title">Review Details</div>
      <button class="modal-close" onclick="closeM('viewModal')">✕</button>
    </div>
    <div class="modal-body" id="modal_body"></div>
  </div>
</div>

<!-- ══ DELETE CONFIRMATION MODAL ══ -->
<div class="modal-bd" id="deleteModal" onclick="closeBd(event,'deleteModal')">
  <div class="modal-box" style="max-width:480px">
    <div class="modal-hd">
      <div class="modal-hd-title">Delete Review</div>
      <button class="modal-close" onclick="closeM('deleteModal')">✕</button>
    </div>
    <div class="modal-body">
      <p style="font-size:13px;color:var(--muted);margin-bottom:16px">Are you sure you want to delete this review? This action cannot be undone.</p>
      <form method="POST" id="deleteForm">
        <input type="hidden" name="review_id" id="delete_review_id">
        <input type="hidden" name="delete_deal_review" id="delete_type_deal" style="display:none">
        <input type="hidden" name="delete_user_deal_review" id="delete_type_userdeal" style="display:none">
        <button type="submit" class="btn-confirm btn-confirm-red">Confirm Delete</button>
      </form>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
// ── Clock ─────────────────────────────────────────────────────────
function updateClock(){
  document.getElementById('clock').textContent =
    new Date().toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
updateClock(); setInterval(updateClock,1000);

// ── Toast ─────────────────────────────────────────────────────────
function showToast(msg, isError){
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.style.borderLeftColor = isError ? 'var(--red2)' : 'var(--green2)';
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3200);
}
<?php if($msg): ?>
document.addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($msg) ?>, <?= $msg_type==='error'?'true':'false' ?>));
<?php endif; ?>

// ── Tab switching ─────────────────────────────────────────────────
function switchTab(tab){
  // Update URL without reload
  const url = new URL(window.location);
  url.searchParams.set('tab', tab);
  window.history.pushState({}, '', url);
  
  // Update panels
  document.getElementById('rpanel-deals').style.display = tab === 'deals' ? 'block' : 'none';
  document.getElementById('rpanel-userdeals').style.display = tab === 'userdeals' ? 'block' : 'none';
  
  // Update tab buttons
  document.getElementById('rtab-deals').classList.toggle('active', tab === 'deals');
  document.getElementById('rtab-userdeals').classList.toggle('active', tab === 'userdeals');
}

// ── Table filter ──────────────────────────────────────────────────
function filterTable(tableId, query){
  query = query.toLowerCase();
  document.querySelectorAll('#'+tableId+' tbody tr').forEach(row=>{
    if(row.querySelector('.empty')) return; // Skip empty state row
    row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
  });
}

// ── Modal helpers ─────────────────────────────────────────────────
function openM(id) { document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
function closeM(id){ document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
function closeBd(e,id){ if(e.target===document.getElementById(id)) closeM(id); }
document.addEventListener('keydown',e=>{ 
  if(e.key==='Escape') {
    document.querySelectorAll('.modal-bd.open').forEach(m=>{
      m.classList.remove('open');
      document.body.style.overflow='';
    });
  }
});

// ── View review detail ────────────────────────────────────────────
function viewReview(review, type){
  const stars = '★'.repeat(review.rating) + '☆'.repeat(5-review.rating);
  const dealLabel = type === 'deal' ? 'Deal' : 'User Deal';
  const dealName = type === 'deal' ? (review.deal_name || 'Deal #'+review.deal_id) : (review.deal_title || 'Deal #'+review.ud_id);
  
  document.getElementById('modal_title').textContent = 'Review #' + review.id;
  document.getElementById('modal_body').innerHTML = `
    <div style="margin-bottom:20px">
      <div style="font-size:24px;color:var(--gold2);margin-bottom:8px">${stars}</div>
      <div style="font-size:11px;color:var(--muted2);font-family:var(--ff-m)">Rating: ${review.rating}/5</div>
    </div>
    
    <div style="display:grid;gap:12px;margin-bottom:20px">
      <div style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.05)">
        <span style="color:rgba(255,255,255,0.4);width:100px;flex-shrink:0;font-size:12px">Reviewer:</span>
        <span style="font-size:13px;font-weight:600">${review.user_name || 'User #'+review.user_id}</span>
      </div>
      <div style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.05)">
        <span style="color:rgba(255,255,255,0.4);width:100px;flex-shrink:0;font-size:12px">Email:</span>
        <span style="font-size:13px;font-family:var(--ff-m)">${review.user_email || '—'}</span>
      </div>
      <div style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.05)">
        <span style="color:rgba(255,255,255,0.4);width:100px;flex-shrink:0;font-size:12px">${dealLabel}:</span>
        <span style="font-size:13px;font-weight:600;color:${type==='deal'?'var(--gold)':'var(--blue2)'}">${dealName}</span>
      </div>
      <div style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.05)">
        <span style="color:rgba(255,255,255,0.4);width:100px;flex-shrink:0;font-size:12px">Booking ID:</span>
        <span style="font-size:13px;font-family:var(--ff-m)">${review.booking_id ? '#'+review.booking_id : '—'}</span>
      </div>
      <div style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.05)">
        <span style="color:rgba(255,255,255,0.4);width:100px;flex-shrink:0;font-size:12px">Date:</span>
        <span style="font-size:13px;font-family:var(--ff-m)">${new Date(review.created_at).toLocaleDateString('en-US', {year:'numeric',month:'short',day:'numeric'})}</span>
      </div>
    </div>
    
    <div style="margin-top:16px">
      <div style="font-size:11px;color:var(--muted2);font-family:var(--ff-m);letter-spacing:2px;text-transform:uppercase;margin-bottom:8px">Review Text</div>
      <div style="background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:14px;font-size:13px;line-height:1.6;color:var(--text)">
        ${review.review_text || '<em style="color:var(--muted2)">No review text provided</em>'}
      </div>
    </div>
  `;
  openM('viewModal');
}

// ── Confirm delete ────────────────────────────────────────────────
function confirmDelete(reviewId, type){
  document.getElementById('delete_review_id').value = reviewId;
  
  // Show/hide the appropriate hidden input
  if(type === 'deal'){
    document.getElementById('delete_type_deal').style.display = 'block';
    document.getElementById('delete_type_deal').disabled = false;
    document.getElementById('delete_type_userdeal').style.display = 'none';
    document.getElementById('delete_type_userdeal').disabled = true;
  } else {
    document.getElementById('delete_type_userdeal').style.display = 'block';
    document.getElementById('delete_type_userdeal').disabled = false;
    document.getElementById('delete_type_deal').style.display = 'none';
    document.getElementById('delete_type_deal').disabled = true;
  }
  
  openM('deleteModal');
}
</script>
</body>
</html>