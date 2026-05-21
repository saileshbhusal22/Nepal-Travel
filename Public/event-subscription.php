<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// ── Auth guard ─────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php?redirect=event-subscription'); exit;
}
$user_id = (int)$_SESSION['user_id'];

// ══════════════════════════════════════════════════════════════
//  ALL POST HANDLING MUST BE BEFORE ANY include / echo / html
// ══════════════════════════════════════════════════════════════

$msg = ''; $msg_type = '';

// ── Handle: Purchase subscription (manual) ─────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'subscribe') {
    $plan_id    = (int)$_POST['plan_id'];
    $pay_method = $conn->real_escape_string(trim($_POST['payment_method'] ?? 'manual'));
    $pay_ref    = $conn->real_escape_string(trim($_POST['payment_ref']    ?? ''));
    $pay_proof  = $conn->real_escape_string(trim($_POST['payment_proof']  ?? ''));

    $plan_row = $conn->query("SELECT * FROM event_subscription_plans WHERE id=$plan_id AND is_active=1");
    if ($plan_row && $plan_row->num_rows > 0) {
        $plan   = $plan_row->fetch_assoc();
        $amount = (float)$plan['price'];
        $sql    = "INSERT INTO user_event_subscriptions
                       (user_id, plan_id, status, payment_method, payment_ref, payment_proof, amount_paid)
                   VALUES ($user_id, $plan_id, 'pending', '$pay_method', '$pay_ref', '$pay_proof', $amount)";
        if ($conn->query($sql)) {
            header('Location: event-subscription.php?msg=submitted'); exit;
        } else {
            $msg = 'Database error: ' . $conn->error; $msg_type = 'error';
        }
    } else {
        $msg = 'Invalid or inactive plan selected.'; $msg_type = 'error';
    }
}

// ── Flash messages from redirect ──────────────────────────────
if (isset($_GET['msg'])) {
    $flash = [
        'submitted'          => '✓ Subscription submitted! Admin will review and activate it.',
        'esewa_success'      => '✓ eSewa payment verified! Your subscription is now active.',
        'khalti_success'     => '✓ Khalti payment verified! Your subscription is now active.',
        'payment_cancelled'  => '✗ Payment was cancelled. Please try again.',
        'payment_failed'     => '✗ Payment verification failed. Please contact support.',
        'amount_mismatch'    => '✗ Payment amount mismatch detected. Please contact support.',
        'already_activated'  => '✓ Your subscription is already active.',
        'already_subscribed' => '✓ You already have an active or pending subscription for this plan.',
        'already_has_active' => '✗ You already have an active subscription.',
        'invalid_plan'       => '✗ Invalid or inactive plan selected.',
    ];
    $msg      = $flash[$_GET['msg']] ?? '';
    $msg_type = str_starts_with($msg, '✗') ? 'error' : 'success';
}

// ── Payment errors from redirect ──────────────────────────────────
if (isset($_GET['esewa_error']) && empty($msg)) {
    $msg      = '✗ ' . htmlspecialchars($_GET['esewa_error']);
    $msg_type = 'error';
}
if (isset($_GET['khalti_error']) && empty($msg)) {
    $msg      = '✗ ' . htmlspecialchars($_GET['khalti_error']);
    $msg_type = 'error';
}

// ══════════════════════════════════════════════════════════════
//  DATA FETCHING
// ══════════════════════════════════════════════════════════════

$plans_result = $conn->query("SELECT * FROM event_subscription_plans WHERE plan_type='event' AND is_active=1 ORDER BY price ASC");
$plans        = $plans_result ? $plans_result->fetch_all(MYSQLI_ASSOC) : [];

$my_subs = $conn->query("
    SELECT us.*, sp.name AS plan_name, sp.display_name, sp.event_limit
    FROM user_event_subscriptions us
    JOIN event_subscription_plans sp ON sp.id = us.plan_id
    WHERE us.user_id = $user_id
      AND us.status IN ('active','pending')
    ORDER BY us.created_at DESC
");
$active_subs = $my_subs ? $my_subs->fetch_all(MYSQLI_ASSOC) : [];

$my_deals = $conn->query("
    SELECT e.*, us.status AS sub_status, sp.display_name AS plan_display
    FROM events e
    JOIN user_event_subscriptions us ON us.id = e.subscription_id
    JOIN event_subscription_plans sp ON sp.id = us.plan_id
    WHERE e.user_id = $user_id
    ORDER BY e.created_at DESC
");
$my_deals_arr = $my_deals ? $my_deals->fetch_all(MYSQLI_ASSOC) : [];

$plan_icons  = ['pay_per_event' => '⚡', 'monthly_events' => '📅', 'yearly_events' => '🏆'];
$plan_colors = ['pay_per_event' => '#e84393', 'monthly_events' => '#2563eb', 'yearly_events' => '#c9a227'];

include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Host Your Event | Nepal Tours</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#080a12;--surface:#0f1320;--surface2:#161b28;--surface3:#1c2235;
  --border:rgba(255,255,255,0.07);--border2:rgba(255,255,255,0.12);
  --text:#f0ede6;--muted:rgba(240,237,230,0.45);--muted2:rgba(240,237,230,0.22);
  --gold:#c9a227;--gold2:#e8c44a;--blue:#2563eb;--pink:#e84393;
  --green:#4caf7d;--red:#e05555;--esewa:#60bb46;--khalti:#5C2D91;
}
html{scroll-behavior:smooth}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;-webkit-font-smoothing:antialiased}

/* ── HERO ── */
.sub-hero{
  position:relative;min-height:340px;display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg,#080a12 0%,#0d1428 40%,#0a0e1c 100%);
  overflow:hidden;padding:4rem 2rem 3rem;text-align:center;
}
.sub-hero::before{
  content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse 80% 60% at 50% 0%,rgba(201,162,39,0.12),transparent 70%),
             radial-gradient(ellipse 40% 40% at 10% 80%,rgba(37,99,235,0.08),transparent 60%),
             radial-gradient(ellipse 40% 40% at 90% 80%,rgba(96,187,70,0.08),transparent 60%);
  pointer-events:none;
}
.sub-hero-tag{font-family:'DM Mono',monospace;font-size:10px;letter-spacing:3px;text-transform:uppercase;color:var(--gold);margin-bottom:1.2rem;opacity:.8}
.sub-hero h1{font-family:'Playfair Display',serif;font-size:clamp(2rem,5vw,3.8rem);font-weight:900;color:var(--text);line-height:1.08;margin-bottom:1rem}
.sub-hero h1 em{font-style:italic;color:var(--gold)}
.sub-hero p{color:var(--muted);font-size:1rem;max-width:560px;margin:0 auto;line-height:1.7}
.hero-grid-bg{position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.02) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.02) 1px,transparent 1px);background-size:40px 40px;pointer-events:none}

/* ── LAYOUT ── */
.page-wrap{max-width:1160px;margin:0 auto;padding:3.5rem 2rem 6rem}

/* ── SECTION HEADING ── */
.section-title{font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:700;color:var(--text);margin-bottom:1.8rem;display:flex;align-items:center;gap:12px}
.section-title::after{content:'';flex:1;height:1px;background:var(--border2)}

/* ── ALERT ── */
.alert{display:flex;align-items:center;gap:12px;padding:14px 20px;border-radius:10px;font-size:13.5px;margin-bottom:2rem;border-left:3px solid}
.alert-success{background:rgba(76,175,125,0.1);color:var(--green);border-color:var(--green)}
.alert-error{background:rgba(224,85,85,0.1);color:var(--red);border-color:var(--red)}

/* ── PLAN CARDS ── */
.plans-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.6rem;margin-bottom:3.5rem}
.plan-card{
  background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:2rem;
  position:relative;overflow:hidden;transition:border-color .2s,transform .2s,box-shadow .2s;
}
.plan-card:hover{transform:translateY(-4px);box-shadow:0 20px 50px rgba(0,0,0,.4)}
.plan-card.popular{border-color:var(--gold);box-shadow:0 0 30px rgba(201,162,39,.12)}
.plan-badge{position:absolute;top:16px;right:16px;background:var(--gold);color:#000;font-size:9px;font-weight:800;letter-spacing:2px;text-transform:uppercase;padding:4px 10px;border-radius:20px;font-family:'DM Mono',monospace}
.plan-icon{font-size:2.2rem;margin-bottom:1rem}
.plan-name{font-family:'Playfair Display',serif;font-size:1.3rem;font-weight:700;color:var(--text);margin-bottom:.4rem}
.plan-desc{font-size:12.5px;color:var(--muted);line-height:1.6;margin-bottom:1.4rem}
.plan-price-row{display:flex;align-items:baseline;gap:6px;margin-bottom:1.4rem}
.plan-price{font-family:'DM Mono',monospace;font-size:2rem;font-weight:700;color:var(--text)}
.plan-price-sub{font-size:12px;color:var(--muted2)}
.plan-features{display:flex;flex-direction:column;gap:7px;margin-bottom:1.6rem}
.plan-feat{display:flex;align-items:center;gap:9px;font-size:13px;color:var(--muted)}
.plan-feat::before{content:'✓';color:var(--green);font-weight:700;flex-shrink:0}
.plan-cta{
  width:100%;padding:13px;border:none;border-radius:10px;font-family:'DM Sans',sans-serif;
  font-size:14px;font-weight:700;cursor:pointer;transition:all .18s;letter-spacing:.03em;
}
.plan-cta:hover{opacity:.88;transform:scale(1.01)}

/* ── PAYMENT MODAL ── */
.modal-bd{display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.8);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:1.5rem}
.modal-bd.open{display:flex}
.modal-box{background:#13161f;border:1px solid rgba(255,255,255,.1);border-radius:22px;width:100%;max-width:500px;max-height:90vh;overflow-y:auto;box-shadow:0 40px 80px rgba(0,0,0,.7);animation:mIn .22s ease}
@keyframes mIn{from{opacity:0;transform:translateY(20px) scale(.97)}to{opacity:1;transform:none}}
.modal-hd{padding:1.5rem 1.8rem 1.2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.modal-hd-title{font-family:'Playfair Display',serif;font-size:1.2rem;font-weight:700;color:var(--text)}
.modal-close{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:var(--muted);width:32px;height:32px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;transition:all .15s}
.modal-close:hover{background:rgba(224,85,85,.2);color:#ff6b6b;border-color:rgba(224,85,85,.3)}
.modal-body{padding:1.5rem 1.8rem 2rem}
.form-group{display:flex;flex-direction:column;gap:7px;margin-bottom:16px}
.flbl{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);font-weight:600;font-family:'DM Mono',monospace}
.fin{background:var(--surface2);color:var(--text);border:1px solid var(--border2);border-radius:8px;padding:11px 14px;font-size:13.5px;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .2s;width:100%}
.fin:focus{border-color:rgba(96,187,70,.5)}
.fin::placeholder{color:var(--muted2)}
textarea.fin{resize:vertical;min-height:80px;line-height:1.6}
.fin-hint{font-size:11px;color:var(--muted2)}
.modal-plan-summary{background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:14px 16px;margin-bottom:20px;display:flex;align-items:center;gap:14px}
.mps-icon{font-size:2rem}
.mps-name{font-family:'Playfair Display',serif;font-size:15px;font-weight:700;color:var(--text)}
.mps-price{font-family:'DM Mono',monospace;font-size:13px;color:var(--gold)}
.btn-submit{width:100%;padding:14px;border:none;border-radius:10px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:800;cursor:pointer;transition:all .18s;letter-spacing:.03em;display:flex;align-items:center;justify-content:center;gap:8px}
.btn-submit:hover{filter:brightness(1.1);transform:scale(1.01)}
.btn-esewa{background:var(--esewa);color:#fff;margin-bottom:12px}
.btn-khalti{background:var(--khalti);color:#fff;margin-bottom:12px}
.btn-manual{background:var(--gold);color:#000}
.divider{display:flex;align-items:center;gap:10px;margin:16px 0}
.divider-line{flex:1;height:1px;background:var(--border)}
.divider-text{font-size:10px;color:var(--muted2);font-family:'DM Mono',monospace;letter-spacing:1px;white-space:nowrap}
.bank-info{background:rgba(37,99,235,.08);border:1px solid rgba(37,99,235,.2);border-radius:10px;padding:14px 16px;margin-bottom:20px;font-size:12.5px;color:rgba(140,180,255,.9);line-height:1.8}
.bank-info strong{color:#7cb3ff;display:block;margin-bottom:6px;font-family:'DM Mono',monospace;letter-spacing:1px;font-size:11px}

/* ── MY SUBSCRIPTIONS ── */
.sub-cards{display:flex;flex-direction:column;gap:1rem;margin-bottom:3.5rem}
.sub-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:1.3rem 1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1.5rem;flex-wrap:wrap}
.sub-card-left{display:flex;align-items:center;gap:14px}
.sub-card-icon{font-size:1.8rem;flex-shrink:0}
.sub-card-name{font-family:'Playfair Display',serif;font-size:15px;font-weight:700;color:var(--text)}
.sub-card-meta{font-size:12px;color:var(--muted);margin-top:3px;font-family:'DM Mono',monospace}
.status-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;font-family:'DM Mono',monospace}
.status-active{background:rgba(76,175,125,.15);color:var(--green);border:1px solid rgba(76,175,125,.25)}
.status-pending{background:rgba(201,162,39,.12);color:var(--gold);border:1px solid rgba(201,162,39,.2)}
.status-expired{background:rgba(255,255,255,.05);color:var(--muted2);border:1px solid var(--border)}
.progress-wrap{flex:1;min-width:160px;max-width:240px}
.progress-label{display:flex;justify-content:space-between;font-size:10px;color:var(--muted2);margin-bottom:5px;font-family:'DM Mono',monospace}
.progress-bar{height:4px;background:rgba(255,255,255,.07);border-radius:10px;overflow:hidden}
.progress-fill{height:100%;background:linear-gradient(90deg,var(--gold),var(--gold2));border-radius:10px}
.btn-submit-deal{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:var(--blue);color:#fff;border:none;border-radius:8px;font-size:12.5px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .15s;text-decoration:none}
.btn-submit-deal:hover{background:#1d4ed8}

/* ── MY EVENTS TABLE ── */
.deals-table-wrap{background:var(--surface);border:1px solid var(--border);border-radius:16px;overflow:hidden;margin-bottom:3rem}
.dt-header{padding:16px 20px;border-bottom:1px solid var(--border);background:var(--surface2);display:flex;align-items:center;justify-content:space-between}
.dt-header-title{font-family:'Playfair Display',serif;font-size:15px;font-weight:700}
.table-scroll{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead th{font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--muted2);font-weight:700;padding:11px 16px;background:var(--surface2);border-bottom:1px solid var(--border);text-align:left;white-space:nowrap;font-family:'DM Mono',monospace}
tbody td{padding:12px 16px;border-bottom:1px solid var(--border);font-size:13px;vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover td{background:rgba(255,255,255,.02)}
.deal-thumb{width:48px;height:36px;object-fit:cover;border-radius:5px;border:1px solid var(--border2)}
.deal-thumb-placeholder{width:48px;height:36px;border-radius:5px;background:var(--surface3);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:16px}
.mono{font-family:'DM Mono',monospace;font-size:11px;color:var(--muted)}
.empty-state{padding:4rem;text-align:center;color:var(--muted2)}
.empty-state-icon{font-size:3rem;opacity:.2;margin-bottom:1rem}

@media(max-width:600px){
  .plans-grid{grid-template-columns:1fr}
  .sub-card{flex-direction:column;align-items:flex-start}
}
</style>
</head>
<body>

<!-- ── HERO ── -->
<div class="sub-hero">
  <div class="hero-grid-bg"></div>
  <div style="position:relative;z-index:1">
    <div class="sub-hero-tag">// List Your Event</div>
    <h1>Host Your<br><em>Events in Nepal</em></h1>
    <p>Choose a plan, post your event, and reach thousands of travelers exploring Nepal every day.</p>
  </div>
</div>

<div class="page-wrap">

  <?php if ($msg): ?>
    <div class="alert alert-<?= htmlspecialchars($msg_type) ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <!-- ── PLANS ── -->
  <div class="section-title">Choose Your Plan</div>
  <div class="plans-grid">
    <?php foreach ($plans as $plan):
      $icon    = $plan_icons[$plan['name']]  ?? '📦';
      $color   = $plan_colors[$plan['name']] ?? '#fff';
      $popular = str_contains($plan['name'], 'monthly');
    ?>
    <div class="plan-card <?= $popular ? 'popular' : '' ?>">
      <?php if ($popular): ?><div class="plan-badge">Most Popular</div><?php endif; ?>
      <div class="plan-icon"><?= $icon ?></div>
      <div class="plan-name"><?= htmlspecialchars($plan['display_name']) ?></div>
      <div class="plan-desc"><?= htmlspecialchars($plan['description']) ?></div>
      <div class="plan-price-row">
        <div class="plan-price" style="color:<?= $color ?>">NPR <?= number_format($plan['price']) ?></div>
        <div class="plan-price-sub">
          <?php if (str_contains($plan['name'], 'monthly')): ?>/ month
          <?php elseif (str_contains($plan['name'], 'yearly')): ?>/ year
          <?php else: ?>one-time<?php endif; ?>
        </div>
      </div>
      <div class="plan-features">
        <div class="plan-feat">Visible for <?= (int)$plan['duration_days'] ?> days</div>
        <div class="plan-feat">Up to <?= (int)$plan['event_limit'] ?> event<?= (int)$plan['event_limit'] > 1 ? 's' : '' ?></div>
        <div class="plan-feat">Admin-reviewed &amp; published</div>
        <?php if (!str_contains($plan['name'], 'pay_per')): ?>
          <div class="plan-feat">Auto-removed after expiry</div>
        <?php endif; ?>
        <?php if (str_contains($plan['name'], 'yearly')): ?>
          <div class="plan-feat">Priority placement</div>
        <?php endif; ?>
      </div>
      <button class="plan-cta"
        onclick="openPayModal(<?= (int)$plan['id'] ?>, '<?= htmlspecialchars($plan['display_name'], ENT_QUOTES) ?>', <?= (float)$plan['price'] ?>)"
        style="background:<?= $color ?>;color:<?= str_contains($plan['name'], 'yearly') ? '#000' : '#fff' ?>">
        Get <?= htmlspecialchars($plan['display_name']) ?>
      </button>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── MY SUBSCRIPTIONS ── -->
  <?php if (!empty($active_subs)): ?>
  <div class="section-title">My Active Subscriptions</div>
  <div class="sub-cards">
    <?php foreach ($active_subs as $sub):
      $icon    = $plan_icons[$sub['plan_name']]  ?? '📦';
      $used    = (int)$sub['events_posted'];
      $limit   = (int)$sub['event_limit'];
      $pct     = $limit > 0 ? min(100, round(($used / $limit) * 100)) : 0;
      $expires = $sub['expires_at'] ? date('M d, Y', strtotime($sub['expires_at'])) : 'Pending activation';
    ?>
    <div class="sub-card">
      <div class="sub-card-left">
        <div class="sub-card-icon"><?= $icon ?></div>
        <div>
          <div class="sub-card-name"><?= htmlspecialchars($sub['display_name']) ?></div>
          <div class="sub-card-meta">
            Expires: <?= htmlspecialchars($expires) ?> &nbsp;·&nbsp;
            NPR <?= number_format((float)$sub['amount_paid']) ?>
          </div>
        </div>
      </div>
      <div class="progress-wrap">
        <div class="progress-label">
          <span>Events posted</span>
          <span><?= $used ?> / <?= $limit ?></span>
        </div>
        <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
      </div>
      <span class="status-pill status-<?= htmlspecialchars($sub['status']) ?>"><?= ucfirst($sub['status']) ?></span>
      <?php if ($sub['status'] === 'active' && $used < $limit): ?>
        <a href="events.php" class="btn-submit-deal">
          + Post Event
        </a>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ── MY EVENTS ── -->
  <div class="section-title">My Posted Events</div>
  <?php if (!empty($my_deals_arr)): ?>
  <div class="deals-table-wrap">
    <div class="dt-header">
      <div class="dt-header-title">All My Events</div>
    </div>
    <div class="table-scroll">
      <table>
        <thead>
          <tr>
            <th>Image</th><th>Event Title</th><th>Plan</th><th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($my_deals_arr as $d):
            $hasImg      = !empty($d['image_path']);
            $statusClass = 'status-' . $d['sub_status'];
          ?>
          <tr>
            <td>
              <?php if ($hasImg): ?>
                <img src="<?= htmlspecialchars($d['image_path']) ?>" class="deal-thumb"
                     onerror="this.style.display='none'" alt="">
              <?php else: ?>
                <div class="deal-thumb-placeholder">🎟️</div>
              <?php endif; ?>
            </td>
            <td style="font-weight:600;max-width:180px"><?= htmlspecialchars($d['title']) ?></td>
            <td class="mono"><?= htmlspecialchars($d['plan_display']) ?></td>
            <td><span class="status-pill <?= $statusClass ?>"><?= ucfirst($d['sub_status']) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php else: ?>
  <div class="deals-table-wrap">
    <div class="empty-state">
      <div class="empty-state-icon">🎟️</div>
      <p>No events submitted yet. Purchase a plan above to get started!</p>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /page-wrap -->

<!-- ══ PAYMENT MODAL ══ -->
<div class="modal-bd" id="payModal" onclick="closeIfBd(event,'payModal')">
  <div class="modal-box">
    <div class="modal-hd">
      <div class="modal-hd-title">Complete Purchase</div>
      <button class="modal-close" onclick="closeModal('payModal')">✕</button>
    </div>
    <div class="modal-body">

      <!-- Plan summary -->
      <div class="modal-plan-summary">
        <div class="mps-icon" id="mps_icon">📦</div>
        <div>
          <div class="mps-name"  id="mps_name">Plan Name</div>
          <div class="mps-price" id="mps_price">NPR 0</div>
        </div>
      </div>

      <!-- ── ESEWA BUTTON ── -->
      <form method="POST" action="esewa_event_subscription_initiate.php">
        <input type="hidden" name="action"  value="esewa_pay">
        <input type="hidden" name="plan_id" id="esewa_plan_id">
        <button type="submit" class="btn-submit btn-esewa">
          <svg width="20" height="20" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="20" cy="20" r="20" fill="#ffffff22"/>
            <text x="20" y="26" text-anchor="middle" font-size="14" font-weight="bold" fill="white">e</text>
          </svg>
          Pay with eSewa
        </button>
      </form>

      <!-- ── KHALTI BUTTON ── -->
      <form method="POST" action="khalti_event_subscription_initiate.php">
        <input type="hidden" name="action"  value="khalti_pay">
        <input type="hidden" name="plan_id" id="khalti_plan_id">
        <button type="submit" class="btn-submit btn-khalti">
          <svg width="20" height="20" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="20" cy="20" r="20" fill="#ffffff22"/>
            <text x="20" y="26" text-anchor="middle" font-size="14" font-weight="bold" fill="white">K</text>
          </svg>
          Pay with Khalti
        </button>
      </form>

      <!-- Divider -->
      <div class="divider">
        <div class="divider-line"></div>
        <div class="divider-text">OR PAY MANUALLY</div>
        <div class="divider-line"></div>
      </div>

      <!-- ── MANUAL PAYMENT ── -->
      <form method="POST" action="event-subscription.php">
        <input type="hidden" name="action"  value="subscribe">
        <input type="hidden" name="plan_id" id="pay_plan_id">

        <div class="bank-info">
          <strong>// PAYMENT INSTRUCTIONS</strong>
          Transfer to:<br>
          <strong>Bank:</strong> NIC Asia Bank &nbsp;|&nbsp; <strong>A/C:</strong> 123456789012<br>
          <strong>eSewa:</strong> 9841000000<br>
          After payment, enter the transaction reference below.
        </div>

        <div class="form-group">
          <label class="flbl">Payment Method</label>
          <select name="payment_method" class="fin" required>
            <option value="esewa">eSewa</option>
            <option value="khalti">Khalti</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="cash">Cash (Visit Office)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="flbl">Transaction Reference / ID</label>
          <input type="text" name="payment_ref" class="fin" placeholder="e.g. TXN20250001" required>
        </div>
        <div class="form-group">
          <label class="flbl">Payment Screenshot URL <span style="color:var(--muted2)">(optional)</span></label>
          <input type="text" name="payment_proof" class="fin" placeholder="Paste image link from Imgur, Drive, etc.">
          <div class="fin-hint">Upload screenshot to Imgur or Google Drive and paste the link</div>
        </div>
        <button type="submit" class="btn-submit btn-manual">Submit Manual Payment →</button>
      </form>

    </div>
  </div>
</div>

<script>
const planIcons = {
  <?php foreach ($plans as $p): ?>
  '<?= (int)$p['id'] ?>': '<?= $plan_icons[$p['name']] ?? '📦' ?>',
  <?php endforeach; ?>
};

function openPayModal(planId, planName, planPrice) {
  document.getElementById('pay_plan_id').value    = planId;
  document.getElementById('esewa_plan_id').value  = planId;
  document.getElementById('khalti_plan_id').value = planId;
  document.getElementById('mps_name').textContent = planName;
  document.getElementById('mps_price').textContent= 'NPR ' + Number(planPrice).toLocaleString();
  document.getElementById('mps_icon').textContent = planIcons[String(planId)] || '📦';
  openModal('payModal');
}

function openModal(id) {
  document.getElementById(id).classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
  document.body.style.overflow = '';
}

function closeIfBd(e, id) {
  if (e.target === document.getElementById(id)) closeModal(id);
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    closeModal('payModal');
  }
});
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
