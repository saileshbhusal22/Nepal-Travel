<?php
session_name('nepal_admin_session');
session_start();
require_once __DIR__ . '/../config/db.php';

// ── Auth guard ──────────────────────────────────────────────────
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header('Location: /Nepal-Travel/user/login.php'); exit;
// }
$admin_id = (int)($_SESSION['user_id'] ?? 1);

// ── Auto-expire deals past visible_until ────────────────────────
$conn->query("UPDATE user_deals SET status='expired' WHERE status='approved' AND visible_until IS NOT NULL AND visible_until < NOW()");
$conn->query("UPDATE user_subscriptions SET status='expired' WHERE status='active' AND expires_at IS NOT NULL AND expires_at < NOW()");

$msg = ''; $msg_type = '';
$tab = $_GET['tab'] ?? 'subscriptions';

// ═══════════════════════════════════════════
//  ACTIONS
// ═══════════════════════════════════════════

// ── Approve Subscription ────────────────────────────────────────
if ($_POST['action'] ?? '' === 'approve_sub') {
    $sub_id  = (int)$_POST['sub_id'];
    $sub_row = $conn->query("SELECT us.*, sp.duration_days FROM user_subscriptions us JOIN subscription_plans sp ON sp.id=us.plan_id WHERE us.id=$sub_id");
    if ($sub_row && ($sub = $sub_row->fetch_assoc())) {
        $starts  = date('Y-m-d H:i:s');
        $expires = date('Y-m-d H:i:s', strtotime("+{$sub['duration_days']} days"));
        $conn->query("UPDATE user_subscriptions SET status='active', starts_at='$starts', expires_at='$expires', approved_by=$admin_id, approved_at=NOW() WHERE id=$sub_id");
        $msg = 'Subscription activated!'; $msg_type = 'success';
    }
}

// ── Reject Subscription ─────────────────────────────────────────
if ($_POST['action'] ?? '' === 'reject_sub') {
    $sub_id = (int)$_POST['sub_id'];
    $conn->query("UPDATE user_subscriptions SET status='cancelled' WHERE id=$sub_id");
    $msg = 'Subscription rejected.'; $msg_type = 'error';
}

// ── Approve Deal ────────────────────────────────────────────────
if ($_POST['action'] ?? '' === 'approve_deal') {
    $deal_id = (int)$_POST['deal_id'];
    // Get subscription duration for this deal
    $drow = $conn->query("
        SELECT ud.id, sp.duration_days
        FROM user_deals ud
        JOIN user_subscriptions us ON us.id = ud.subscription_id
        JOIN subscription_plans sp ON sp.id = us.plan_id
        WHERE ud.id = $deal_id
    ");
    if ($drow && ($d = $drow->fetch_assoc())) {
        $from  = date('Y-m-d H:i:s');
        $until = date('Y-m-d H:i:s', strtotime("+{$d['duration_days']} days"));
        $conn->query("
            UPDATE user_deals
            SET status='approved', visible_from='$from', visible_until='$until',
                approved_by=$admin_id, approved_at=NOW()
            WHERE id=$deal_id
        ");
        $msg = 'Deal approved and published!'; $msg_type = 'success';
    }
}

// ── Reject Deal ─────────────────────────────────────────────────
if ($_POST['action'] ?? '' === 'reject_deal') {
    $deal_id = (int)$_POST['deal_id'];
    $reason  = $conn->real_escape_string(trim($_POST['reason'] ?? 'Does not meet our guidelines.'));
    $conn->query("UPDATE user_deals SET status='rejected', rejection_reason='$reason' WHERE id=$deal_id");
    $msg = 'Deal rejected.'; $msg_type = 'error';
}

// ── Delete Deal ─────────────────────────────────────────────────
if ($_POST['action'] ?? '' === 'delete_deal') {
    $deal_id = (int)$_POST['deal_id'];
    $conn->query("DELETE FROM user_deals WHERE id=$deal_id");
    $msg = 'Deal deleted.'; $msg_type = 'success';
}

// ─── Redirect flash ─────────────────────────────────────────────
if ($msg) { header("Location: subscription_admin.php?tab=$tab&msg=" . urlencode($msg) . "&mt=$msg_type"); exit; }
if (isset($_GET['msg'])) { $msg = $_GET['msg']; $msg_type = $_GET['mt'] ?? 'success'; }

// ═══════════════════════════════════════════
//  FETCH DATA
// ═══════════════════════════════════════════
// Subscriptions
$subs = $conn->query("
    SELECT us.*, sp.name AS plan_name, sp.display_name, sp.duration_days, sp.deal_limit,
           u.full_name AS user_name, u.email AS user_email
    FROM user_subscriptions us
    JOIN subscription_plans sp ON sp.id = us.plan_id
    LEFT JOIN users u ON u.id = us.user_id
    ORDER BY us.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// User deals
$deals = $conn->query("
    SELECT ud.*, u.full_name AS user_name, u.email AS user_email,
           sp.display_name AS plan_display, sp.duration_days
    FROM user_deals ud
    LEFT JOIN users u ON u.id = ud.user_id
    LEFT JOIN user_subscriptions us ON us.id = ud.subscription_id
    LEFT JOIN subscription_plans sp ON sp.id = us.plan_id
    ORDER BY ud.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Counts
$pending_subs  = count(array_filter($subs,  fn($s) => $s['status'] === 'pending'));
$pending_deals = count(array_filter($deals, fn($d) => $d['status'] === 'pending'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Subscription Manager — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0C0E14;--surface:#13161F;--surface2:#1A1E2A;--surface3:#20253a;
  --border:rgba(255,255,255,0.07);--border2:rgba(255,255,255,0.12);
  --text:#F0EEE8;--muted:rgba(240,238,232,0.45);--muted2:rgba(240,238,232,0.25);
  --gold:#C9A227;--gold2:#E8C44A;
  --green:#4CAF7D;--red:#E05555;--blue:#2563eb;--amber:#F0A030;
  --ff-d:'Syne',sans-serif;--ff-b:'DM Sans',sans-serif;--ff-m:'DM Mono',monospace;
}
html,body{min-height:100%;background:var(--bg);color:var(--text);font-family:var(--ff-b);-webkit-font-smoothing:antialiased}

/* LAYOUT */
.wrap{display:flex;min-height:100vh}

/* SIDEBAR */
.sidebar{width:240px;flex-shrink:0;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:sticky;top:0;height:100vh}
.sb-logo{padding:28px 24px 22px;border-bottom:1px solid var(--border)}
.sb-logo-title{font-family:var(--ff-d);font-size:17px;font-weight:800;color:var(--text);display:flex;align-items:center;gap:10px}
.sb-logo-title em{font-style:italic;color:var(--gold)}
.sb-logo-sub{font-size:10px;color:var(--muted2);letter-spacing:2px;text-transform:uppercase;margin-top:4px;font-family:var(--ff-m)}
.sb-nav{padding:16px 12px;flex:1}
.sb-section-label{font-size:9px;letter-spacing:2.5px;text-transform:uppercase;color:var(--muted2);font-weight:600;padding:0 12px;margin:16px 0 8px;font-family:var(--ff-m)}
.sb-link{display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:8px;font-size:13px;font-weight:500;color:var(--muted);transition:all 0.18s;border:1px solid transparent}
.sb-link:hover{background:var(--surface2);color:var(--text);border-color:var(--border)}
.sb-link.on{background:rgba(201,162,39,0.1);color:var(--gold);border-color:rgba(201,162,39,0.2)}
.sb-footer{padding:16px 24px;border-top:1px solid var(--border)}
.sb-avatar{width:34px;height:34px;border-radius:50%;background:rgba(201,162,39,0.2);border:1px solid rgba(201,162,39,0.3);display:flex;align-items:center;justify-content:center;font-family:var(--ff-d);font-size:13px;font-weight:700;color:var(--gold)}

/* MAIN */
.main{flex:1;display:flex;flex-direction:column}
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:0 36px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.tb-breadcrumb{font-family:var(--ff-m);font-size:11px;color:var(--muted2);letter-spacing:1px}
.tb-breadcrumb span{color:var(--gold)}
.content{padding:36px;flex:1}

/* ALERT */
.alert{display:flex;align-items:center;gap:12px;padding:13px 20px;border-radius:8px;font-size:13px;margin-bottom:24px;border-left:3px solid}
.alert-success{background:rgba(76,175,125,0.1);color:var(--green);border-color:var(--green)}
.alert-error{background:rgba(224,85,85,0.1);color:var(--red);border-color:var(--red)}

/* STATS ROW */
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.2rem 1.4rem}
.stat-val{font-family:var(--ff-m);font-size:1.8rem;font-weight:700;color:var(--text);line-height:1}
.stat-label{font-size:11px;color:var(--muted2);margin-top:6px;letter-spacing:1px;text-transform:uppercase;font-family:var(--ff-m)}
.stat-badge{display:inline-block;background:rgba(224,85,85,0.15);color:var(--red);font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;margin-top:5px}

/* TABS */
.tabs{display:flex;gap:4px;margin-bottom:2rem;background:var(--surface2);padding:4px;border-radius:10px;width:fit-content;border:1px solid var(--border)}
.tab-btn{padding:9px 22px;border-radius:7px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all 0.15s;font-family:var(--ff-b);color:var(--muted);background:transparent;display:flex;align-items:center;gap:8px}
.tab-btn.active{background:var(--gold);color:#000}
.tab-badge{background:rgba(224,85,85,0.9);color:#fff;font-size:9px;font-weight:800;padding:2px 6px;border-radius:10px;font-family:var(--ff-m)}

/* TABLE CARD */
.tcard{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:2rem}
.tcard-hd{padding:16px 22px;border-bottom:1px solid var(--border);background:var(--surface2);display:flex;align-items:center;justify-content:space-between}
.tcard-hd-title{font-family:var(--ff-d);font-size:15px;font-weight:700}
.tcard-search{padding:12px 22px;border-bottom:1px solid var(--border)}
.search-inp{width:100%;max-width:380px;padding:8px 14px;background:var(--surface2);border:1px solid var(--border2);border-radius:7px;font-size:13px;color:var(--text);outline:none;transition:border-color 0.2s;font-family:var(--ff-b)}
.search-inp:focus{border-color:rgba(201,162,39,0.4)}
.search-inp::placeholder{color:var(--muted2)}
.tscroll{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead th{font-size:9px;letter-spacing:2.5px;text-transform:uppercase;color:var(--muted2);font-weight:700;padding:11px 16px;text-align:left;background:var(--surface2);border-bottom:1px solid var(--border);white-space:nowrap;font-family:var(--ff-m)}
tbody td{padding:12px 16px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover td{background:rgba(255,255,255,0.02)}
.mono{font-family:var(--ff-m);font-size:11px;color:var(--muted)}

/* STATUS PILLS */
.pill{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;font-family:var(--ff-m);white-space:nowrap}
.pill-pending{background:rgba(201,162,39,0.12);color:var(--gold);border:1px solid rgba(201,162,39,0.2)}
.pill-active{background:rgba(76,175,125,0.12);color:var(--green);border:1px solid rgba(76,175,125,0.2)}
.pill-expired{background:rgba(255,255,255,0.04);color:var(--muted2);border:1px solid var(--border)}
.pill-cancelled, .pill-rejected{background:rgba(224,85,85,0.1);color:var(--red);border:1px solid rgba(224,85,85,0.2)}
.pill-approved{background:rgba(76,175,125,0.12);color:var(--green);border:1px solid rgba(76,175,125,0.2)}

/* ACTION BUTTONS */
.act-row{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:6px;font-size:11px;font-weight:700;border:1px solid;cursor:pointer;transition:all 0.15s;font-family:var(--ff-b);white-space:nowrap}
.btn-approve{color:var(--green);border-color:rgba(76,175,125,0.3);background:transparent}
.btn-approve:hover{background:var(--green);color:#000;border-color:var(--green)}
.btn-reject{color:var(--red);border-color:rgba(224,85,85,0.25);background:transparent}
.btn-reject:hover{background:var(--red);color:#fff;border-color:var(--red)}
.btn-delete{color:var(--muted2);border-color:var(--border);background:transparent}
.btn-delete:hover{background:var(--red);color:#fff;border-color:var(--red)}
.btn-view{color:var(--blue);border-color:rgba(37,99,235,0.25);background:transparent}
.btn-view:hover{background:var(--blue);color:#fff;border-color:var(--blue)}

/* DEAL THUMBNAIL */
.deal-thumb{width:48px;height:36px;object-fit:cover;border-radius:5px;border:1px solid var(--border2)}
.deal-thumb-placeholder{width:48px;height:36px;border-radius:5px;background:var(--surface3);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:16px}

/* MODAL */
.modal-bd{display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.8);backdrop-filter:blur(5px);align-items:center;justify-content:center;padding:1.5rem}
.modal-bd.open{display:flex}
.modal-box{background:#13161f;border:1px solid rgba(255,255,255,0.1);border-radius:18px;width:100%;max-width:460px;box-shadow:0 30px 80px rgba(0,0,0,0.7);animation:mIn 0.2s ease}
@keyframes mIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}
.modal-hd{padding:1.2rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.07);display:flex;align-items:center;justify-content:space-between}
.modal-hd-title{font-family:var(--ff-d);font-size:15px;font-weight:700}
.modal-close{background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);color:var(--muted);width:30px;height:30px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:15px;transition:all 0.15s}
.modal-close:hover{background:rgba(224,85,85,0.2);color:#ff6b6b}
.modal-body{padding:1.4rem 1.5rem 1.6rem}
.flbl{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);font-weight:600;font-family:var(--ff-m);display:block;margin-bottom:7px}
.fin{background:var(--surface2);color:var(--text);border:1px solid var(--border2);border-radius:7px;padding:10px 14px;font-size:13px;font-family:var(--ff-b);outline:none;width:100%;transition:border-color 0.2s}
.fin:focus{border-color:rgba(224,85,85,0.4)}
.btn-confirm{width:100%;padding:12px;border:none;border-radius:8px;font-family:var(--ff-b);font-size:13px;font-weight:700;cursor:pointer;margin-top:14px;transition:all 0.15s}
.btn-confirm-red{background:var(--red);color:#fff}
.btn-confirm-red:hover{background:#c93333}

/* empty */
.empty{padding:50px;text-align:center;color:var(--muted2)}
.empty-ico{font-size:40px;opacity:0.2;margin-bottom:12px}

/* deal detail panel */
.deal-detail{background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:14px 16px;margin-bottom:14px;font-size:13px;line-height:1.8;color:var(--muted)}
.deal-detail strong{color:var(--text)}

@media(max-width:768px){.sidebar{display:none}.content{padding:20px}}
</style>
</head>
<body>
<div class="wrap">

  <!-- SIDEBAR -->
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
      <a href="dashboard.php" class="sb-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
        Dashboard
      </a>
      <a href="deals_crud.php" class="sb-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42z"/></svg>
        Admin Deals
      </a>
      <a href="subscription_admin.php" class="sb-link on">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
        Subscriptions
      </a>
      <a href="/Nepal-Travel/Public/index.php" class="sb-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        View Site
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

  <!-- MAIN -->
  <div class="main">
    <header class="topbar">
      <div class="tb-breadcrumb">NEPAL TRAVEL / <span>SUBSCRIPTION MANAGER</span></div>
    </header>

    <div class="content">

      <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <!-- STATS -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-val"><?= count($subs) ?></div>
          <div class="stat-label">Total Subscriptions</div>
          <?php if ($pending_subs > 0): ?><div class="stat-badge"><?= $pending_subs ?> Pending</div><?php endif; ?>
        </div>
        <div class="stat-card">
          <div class="stat-val"><?= count(array_filter($subs, fn($s) => $s['status'] === 'active')) ?></div>
          <div class="stat-label">Active Subscribers</div>
        </div>
        <div class="stat-card">
          <div class="stat-val"><?= count($deals) ?></div>
          <div class="stat-label">Total User Deals</div>
          <?php if ($pending_deals > 0): ?><div class="stat-badge"><?= $pending_deals ?> Pending</div><?php endif; ?>
        </div>
        <div class="stat-card">
          <div class="stat-val">NPR <?= number_format(array_sum(array_column(array_filter($subs, fn($s) => in_array($s['status'],['active','expired'])), 'amount_paid'))) ?></div>
          <div class="stat-label">Total Revenue</div>
        </div>
      </div>

      <!-- TABS -->
      <div class="tabs">
        <button class="tab-btn <?= $tab === 'subscriptions' ? 'active' : '' ?>" onclick="switchTab('subscriptions')">
          💳 Subscriptions
          <?php if ($pending_subs > 0): ?><span class="tab-badge"><?= $pending_subs ?></span><?php endif; ?>
        </button>
        <button class="tab-btn <?= $tab === 'deals' ? 'active' : '' ?>" onclick="switchTab('deals')">
          🏔️ User Deals
          <?php if ($pending_deals > 0): ?><span class="tab-badge"><?= $pending_deals ?></span><?php endif; ?>
        </button>
      </div>

      <!-- ════════════ SUBSCRIPTIONS TAB ════════════ -->
      <div id="tab-subscriptions" style="display:<?= $tab === 'subscriptions' ? 'block' : 'none' ?>">
        <div class="tcard">
          <div class="tcard-hd">
            <div class="tcard-hd-title">All Subscriptions</div>
          </div>
          <div class="tcard-search">
            <input type="text" class="search-inp" placeholder="Search by user, plan, status…" oninput="filterTable('subTable',this.value)">
          </div>
          <div class="tscroll">
            <table id="subTable">
              <thead>
                <tr>
                  <th>ID</th><th>User</th><th>Plan</th><th>Amount</th>
                  <th>Method</th><th>Ref</th><th>Status</th>
                  <th>Starts</th><th>Expires</th><th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($subs)): ?>
                  <tr><td colspan="10"><div class="empty"><div class="empty-ico">💳</div><p>No subscriptions yet.</p></div></td></tr>
                <?php endif; ?>
                <?php foreach ($subs as $s): ?>
                <tr>
                  <td class="mono">#<?= $s['id'] ?></td>
                  <td>
                    <div style="font-weight:600"><?= htmlspecialchars($s['user_name'] ?? 'User #'.$s['user_id']) ?></div>
                    <div class="mono"><?= htmlspecialchars($s['user_email'] ?? '') ?></div>
                  </td>
                  <td><span class="pill pill-active" style="font-size:9px"><?= htmlspecialchars($s['display_name']) ?></span></td>
                  <td class="mono">NPR <?= number_format($s['amount_paid']) ?></td>
                  <td class="mono"><?= htmlspecialchars($s['payment_method']) ?></td>
                  <td class="mono" style="max-width:120px;overflow:hidden;text-overflow:ellipsis" title="<?= htmlspecialchars($s['payment_ref']) ?>">
                    <?= htmlspecialchars($s['payment_ref'] ?: '—') ?>
                  </td>
                  <td><span class="pill pill-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span></td>
                  <td class="mono"><?= $s['starts_at'] ? date('M d, Y', strtotime($s['starts_at'])) : '—' ?></td>
                  <td class="mono"><?= $s['expires_at'] ? date('M d, Y', strtotime($s['expires_at'])) : '—' ?></td>
                  <td>
                    <div class="act-row">
                      <?php if ($s['payment_proof']): ?>
                        <a href="<?= htmlspecialchars($s['payment_proof']) ?>" target="_blank" class="btn btn-view">🖼 Proof</a>
                      <?php endif; ?>
                      <?php if ($s['status'] === 'pending'): ?>
                        <form method="POST" style="display:inline">
                          <input type="hidden" name="action" value="approve_sub">
                          <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
                          <button type="submit" class="btn btn-approve">✓ Approve</button>
                        </form>
                        <button class="btn btn-reject" onclick="openRejectSub(<?= $s['id'] ?>)">✕ Reject</button>
                      <?php elseif ($s['status'] === 'active'): ?>
                        <span style="font-size:11px;color:var(--green)">● Active</span>
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

      <!-- ════════════ DEALS TAB ════════════ -->
      <div id="tab-deals" style="display:<?= $tab === 'deals' ? 'block' : 'none' ?>">
        <div class="tcard">
          <div class="tcard-hd">
            <div class="tcard-hd-title">User-Submitted Deals</div>
          </div>
          <div class="tcard-search">
            <input type="text" class="search-inp" placeholder="Search deals by title, user, status…" oninput="filterTable('dealTable',this.value)">
          </div>
          <div class="tscroll">
            <table id="dealTable">
              <thead>
                <tr>
                  <th>ID</th><th>Image</th><th>Title</th><th>User</th>
                  <th>Plan</th><th>Price</th><th>Status</th>
                  <th>Visible Until</th><th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($deals)): ?>
                  <tr><td colspan="9"><div class="empty"><div class="empty-ico">🏔️</div><p>No user deals yet.</p></div></td></tr>
                <?php endif; ?>
                <?php foreach ($deals as $d):
                  $hasImg = !empty($d['image_url']) && strtoupper(trim($d['image_url'])) !== 'NULL';
                  $until  = $d['visible_until'] ? date('M d, Y', strtotime($d['visible_until'])) : '—';
                ?>
                <tr>
                  <td class="mono">#<?= $d['id'] ?></td>
                  <td>
                    <?php if ($hasImg): ?>
                      <img src="<?= htmlspecialchars($d['image_url']) ?>" class="deal-thumb" onerror="this.style.display='none'">
                    <?php else: ?>
                      <div class="deal-thumb-placeholder"><?= $d['emoji'] ?? '🏔️' ?></div>
                    <?php endif; ?>
                  </td>
                  <td style="font-weight:600;max-width:180px">
                    <?= htmlspecialchars($d['title']) ?>
                    <?php if (!empty($d['description'])): ?>
                      <div class="mono" style="margin-top:3px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($d['description']) ?></div>
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
                    <?php if ($d['status'] === 'rejected' && $d['rejection_reason']): ?>
                      <div class="mono" style="margin-top:4px;color:var(--red);font-size:10px"><?= htmlspecialchars($d['rejection_reason']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="mono"><?= $until ?></td>
                  <td>
                    <div class="act-row">
                      <button class="btn btn-view" onclick="viewDealDetail(<?= htmlspecialchars(json_encode($d), ENT_QUOTES) ?>)">👁 View</button>
                      <?php if ($d['status'] === 'pending'): ?>
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

    </div>
  </div>
</div>

<!-- ══ REJECT SUBSCRIPTION MODAL ══ -->
<div class="modal-bd" id="rejectSubModal" onclick="closeBd(event,'rejectSubModal')">
  <div class="modal-box">
    <div class="modal-hd">
      <div class="modal-hd-title">Reject Subscription</div>
      <button class="modal-close" onclick="closeM('rejectSubModal')">✕</button>
    </div>
    <div class="modal-body">
      <p style="font-size:13px;color:var(--muted);margin-bottom:16px">Are you sure you want to reject this subscription? The user will not be activated.</p>
      <form method="POST">
        <input type="hidden" name="action" value="reject_sub">
        <input type="hidden" name="sub_id" id="reject_sub_id">
        <button type="submit" class="btn-confirm btn-confirm-red">Confirm Reject</button>
      </form>
    </div>
  </div>
</div>

<!-- ══ REJECT DEAL MODAL ══ -->
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
        <textarea name="reason" class="fin" rows="3" placeholder="e.g. Missing images, incorrect pricing, duplicate listing…"></textarea>
        <button type="submit" class="btn-confirm btn-confirm-red">Reject Deal</button>
      </form>
    </div>
  </div>
</div>

<!-- ══ DEAL DETAIL MODAL ══ -->
<div class="modal-bd" id="dealDetailModal" onclick="closeBd(event,'dealDetailModal')">
  <div class="modal-box" style="max-width:520px;max-height:85vh;overflow-y:auto">
    <div class="modal-hd">
      <div class="modal-hd-title" id="dd_title">Deal Detail</div>
      <button class="modal-close" onclick="closeM('dealDetailModal')">✕</button>
    </div>
    <div class="modal-body" id="dd_body"></div>
  </div>
</div>

<script>
function switchTab(tab) {
  ['subscriptions','deals'].forEach(t => {
    document.getElementById('tab-'+t).style.display = t === tab ? 'block' : 'none';
  });
  document.querySelectorAll('.tab-btn').forEach((b,i) => {
    b.classList.toggle('active', ['subscriptions','deals'][i] === tab);
  });
  history.replaceState(null,'','?tab='+tab);
}

function filterTable(tableId, q) {
  q = q.toLowerCase();
  document.querySelectorAll('#'+tableId+' tbody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

function openM(id)  { document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
function closeM(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
function closeBd(e,id){ if(e.target===document.getElementById(id)) closeM(id); }
document.addEventListener('keydown', e => { if(e.key==='Escape') document.querySelectorAll('.modal-bd.open').forEach(m => { m.classList.remove('open'); document.body.style.overflow=''; }); });

function openRejectSub(id) { document.getElementById('reject_sub_id').value=id; openM('rejectSubModal'); }
function openRejectDeal(id){ document.getElementById('reject_deal_id').value=id; openM('rejectDealModal'); }

function viewDealDetail(d) {
  document.getElementById('dd_title').textContent = d.title;
  const img = d.image_url && d.image_url.toUpperCase() !== 'NULL'
    ? `<img src="${d.image_url}" style="width:100%;height:160px;object-fit:cover;border-radius:10px;margin-bottom:14px;border:1px solid rgba(255,255,255,0.08)">`
    : '';
  const row = (label, val) => val ? `<div style="display:flex;gap:10px;padding:5px 0;border-bottom:1px solid rgba(255,255,255,0.05)"><span style="color:rgba(255,255,255,0.4);width:110px;flex-shrink:0;font-size:12px">${label}</span><span style="font-size:13px">${val}</span></div>` : '';
  document.getElementById('dd_body').innerHTML = img +
    row('Category',  d.category) +
    row('Location',  d.location) +
    row('Duration',  d.days ? d.days+' days' : '') +
    row('Season',    d.season) +
    row('Price',     d.price ? 'NPR '+Number(d.price).toLocaleString() : '') +
    row('Orig. Price', d.original_price > 0 ? 'NPR '+Number(d.original_price).toLocaleString() : '') +
    row('Features',  d.features) +
    `<div style="margin-top:12px;font-size:13px;color:rgba(255,255,255,0.55);line-height:1.7">${d.description || ''}</div>`;
  openM('dealDetailModal');
}
</script>
</body>
</html>