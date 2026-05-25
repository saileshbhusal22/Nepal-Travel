<?php
/**
 * Author: Ishwor Dhital
 * Student ID: 2462304
 * Group: L5CG6
 */
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/install_experience_subscriptions.php';
require_once __DIR__ . '/../includes/experience_subscription.php';
require_once __DIR__ . '/../includes/auth_redirect.php';

install_experience_subscriptions_if_needed($conn);

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . auth_build_login_url('/Nepal-Travel/Public/experience-subscription.php'));
    exit;
}
$user_id = (int)$_SESSION['user_id'];

$msg = '';
$msg_type = '';

if (isset($_GET['msg'])) {
    $flash = [
        'esewa_success' => '✓ eSewa payment verified! You can post unlimited experiences until your plan expires.',
        'khalti_success' => '✓ Khalti payment verified! You can post unlimited experiences until your plan expires.',
        'payment_failed' => '✗ Payment was not completed. Please try again.',
        'amount_mismatch' => '✗ Payment amount mismatch. Please contact support.',
        'already_activated' => '✓ Subscription already activated.',
        'already_subscribed' => '✓ A payment for this plan is already pending.',
        'already_has_active' => '✓ You already have an active experience subscription.',
        'invalid_plan' => '✗ Invalid plan selected.',
    ];
    $msg = $flash[$_GET['msg']] ?? '';
    $msg_type = str_starts_with($msg, '✗') ? 'error' : 'success';
}

if (isset($_GET['esewa_error']) && $msg === '') {
    $msg = '✗ ' . htmlspecialchars($_GET['esewa_error']);
    $msg_type = 'error';
}
if (isset($_GET['khalti_error']) && $msg === '') {
    $msg = '✗ ' . htmlspecialchars($_GET['khalti_error']);
    $msg_type = 'error';
}

$quota = experience_get_post_quota($conn, $user_id);

$plans_result = $conn->query('SELECT * FROM experience_subscription_plans WHERE is_active = 1 ORDER BY price ASC');
$plans = $plans_result ? $plans_result->fetch_all(MYSQLI_ASSOC) : [];

$sub_result = $conn->query("
    SELECT ues.*, esp.display_name, esp.name AS plan_name
    FROM user_experience_subscriptions ues
    JOIN experience_subscription_plans esp ON esp.id = ues.plan_id
    WHERE ues.user_id = $user_id AND ues.status IN ('active','pending')
    ORDER BY ues.created_at DESC
");
$active_subs = $sub_result ? $sub_result->fetch_all(MYSQLI_ASSOC) : [];

$plan_icons = ['monthly' => '📸', 'yearly' => '🏔️'];
$plan_colors = ['monthly' => '#2563eb', 'yearly' => '#c9a227'];

include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Experience Subscription | Nepal Tours</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#080a12;--surface:#0f1320;--surface2:#161b28;
  --border:rgba(255,255,255,0.07);--border2:rgba(255,255,255,0.12);
  --text:#f0ede6;--muted:rgba(240,237,230,0.45);
  --gold:#c9a227;--blue:#2563eb;--green:#4caf7d;--red:#e05555;
  --esewa:#60bb46;--khalti:#5C2D91;
}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
.sub-hero{position:relative;min-height:300px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#080a12,#0d1428);padding:4rem 2rem 3rem;text-align:center}
.sub-hero h1{font-family:'Playfair Display',serif;font-size:clamp(2rem,5vw,3.2rem);font-weight:900}
.sub-hero h1 em{font-style:italic;color:var(--gold)}
.sub-hero p{color:var(--muted);max-width:560px;margin:12px auto 0;line-height:1.7}
.page-wrap{max-width:1100px;margin:0 auto;padding:3rem 2rem 5rem}
.quota-banner{background:var(--surface);border:1px solid var(--border2);border-radius:16px;padding:1.5rem 2rem;margin-bottom:2.5rem;display:flex;flex-wrap:wrap;gap:1.5rem;align-items:center;justify-content:space-between}
.quota-banner strong{color:var(--gold)}
.alert{padding:14px 20px;border-radius:10px;margin-bottom:2rem;font-size:14px;border-left:3px solid}
.alert-success{background:rgba(76,175,125,0.1);color:var(--green);border-color:var(--green)}
.alert-error{background:rgba(224,85,85,0.1);color:var(--red);border-color:var(--red)}
.section-title{font-family:'Playfair Display',serif;font-size:1.5rem;margin-bottom:1.5rem}
.plans-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem}
.plan-card{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:2rem;transition:transform .2s}
.plan-card:hover{transform:translateY(-4px)}
.plan-card.popular{border-color:var(--gold)}
.plan-name{font-family:'Playfair Display',serif;font-size:1.25rem;font-weight:700;margin:.5rem 0}
.plan-desc{font-size:13px;color:var(--muted);line-height:1.6;margin-bottom:1rem}
.plan-price{font-family:'DM Mono',monospace;font-size:1.8rem;font-weight:700}
.plan-feat{font-size:13px;color:var(--muted);margin:6px 0}
.plan-feat::before{content:'✓ ';color:var(--green)}
.plan-cta{width:100%;padding:13px;border:none;border-radius:10px;font-weight:700;cursor:pointer;margin-top:1rem;color:#fff}
.sub-card{background:var(--surface2);border:1px solid var(--border);border-radius:12px;padding:1rem 1.25rem;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.status-pill{font-size:11px;padding:4px 10px;border-radius:20px;text-transform:uppercase;font-weight:700}
.status-active{background:rgba(76,175,125,0.15);color:var(--green)}
.status-pending{background:rgba(201,162,39,0.15);color:var(--gold)}
.back-link{display:inline-block;margin-top:2rem;color:var(--gold);text-decoration:none;font-weight:600}
.modal-bd{display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.8);align-items:center;justify-content:center;padding:1.5rem}
.modal-bd.open{display:flex}
.modal-box{background:#13161f;border:1px solid rgba(255,255,255,.1);border-radius:22px;width:100%;max-width:480px;padding:0;overflow:hidden}
.modal-hd{padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
.modal-body{padding:1.5rem}
.btn-submit{width:100%;padding:14px;border:none;border-radius:10px;font-weight:800;cursor:pointer;margin-bottom:10px;color:#fff}
.btn-esewa{background:var(--esewa)}
.btn-khalti{background:var(--khalti)}
.modal-close{background:transparent;border:1px solid var(--border2);color:var(--muted);width:32px;height:32px;border-radius:50%;cursor:pointer}
</style>
</head>
<body>

<div class="sub-hero">
  <div>
    <h1>Share More<br><em>Experiences</em></h1>
    <p>Every member gets <strong><?= EXPERIENCE_FREE_POST_LIMIT ?> free posts</strong>. Subscribe to keep sharing your travel stories with the community.</p>
  </div>
</div>

<div class="page-wrap">

  <?php if ($msg): ?>
    <div class="alert alert-<?= htmlspecialchars($msg_type) ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="quota-banner">
    <div>
      <div style="font-size:12px;text-transform:uppercase;letter-spacing:2px;color:var(--muted);margin-bottom:6px">Your posting quota</div>
      <div style="font-size:1.1rem">
        <strong><?= (int)$quota['posts_used'] ?></strong> posts used ·
        <strong><?= (int)$quota['free_remaining'] ?></strong> of <?= EXPERIENCE_FREE_POST_LIMIT ?> free remaining
      </div>
      <?php if ($quota['has_active_subscription']): ?>
        <div style="font-size:13px;color:var(--green);margin-top:8px">
          ✓ Active subscription until <?= date('M d, Y', strtotime($quota['subscription_expires_at'])) ?>
        </div>
      <?php elseif ($quota['requires_subscription']): ?>
        <div style="font-size:13px;color:var(--red);margin-top:8px">Free posts used — subscribe below to continue posting.</div>
      <?php endif; ?>
    </div>
    <a href="experience.php" class="back-link" style="margin:0">← Back to Experiences</a>
  </div>

  <?php if (!empty($active_subs)): ?>
  <div class="section-title">My Subscriptions</div>
  <?php foreach ($active_subs as $sub): ?>
    <div class="sub-card">
      <div>
        <strong><?= htmlspecialchars($sub['display_name']) ?></strong>
        <?php if ($sub['expires_at']): ?>
          <div style="font-size:12px;color:var(--muted);margin-top:4px">Expires: <?= date('M d, Y', strtotime($sub['expires_at'])) ?></div>
        <?php endif; ?>
      </div>
      <span class="status-pill status-<?= htmlspecialchars($sub['status']) ?>"><?= ucfirst($sub['status']) ?></span>
    </div>
  <?php endforeach; ?>
  <br>
  <?php endif; ?>

  <?php if (empty($plans)): ?>
    <div class="alert alert-error">Subscription plans are not set up yet. Please run <code>sql/create_experience_subscriptions.sql</code> in your database.</div>
  <?php elseif (!$quota['has_active_subscription']): ?>
  <div class="section-title">Choose a Plan</div>
  <div class="plans-grid">
    <?php foreach ($plans as $plan):
      $icon = $plan_icons[$plan['name']] ?? '📦';
      $color = $plan_colors[$plan['name']] ?? '#fff';
      $popular = $plan['name'] === 'monthly';
    ?>
    <div class="plan-card <?= $popular ? 'popular' : '' ?>">
      <div style="font-size:2rem"><?= $icon ?></div>
      <div class="plan-name"><?= htmlspecialchars($plan['display_name']) ?></div>
      <div class="plan-desc"><?= htmlspecialchars($plan['description']) ?></div>
      <div class="plan-price" style="color:<?= $color ?>">NPR <?= number_format((float)$plan['price']) ?></div>
      <div class="plan-feat">Unlimited experience posts</div>
      <div class="plan-feat"><?= (int)$plan['duration_days'] ?> days access</div>
      <div class="plan-feat">Pay with Khalti or eSewa</div>
      <button type="button" class="plan-cta" style="background:<?= $color ?>"
        onclick="openPayModal(<?= (int)$plan['id'] ?>, '<?= htmlspecialchars($plan['display_name'], ENT_QUOTES) ?>', <?= (float)$plan['price'] ?>)">
        Subscribe with Khalti / eSewa
      </button>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <div class="alert alert-success">You're all set! <a href="experience.php" style="color:var(--gold)">Go share an experience</a>.</div>
  <?php endif; ?>

</div>

<div class="modal-bd" id="payModal" onclick="if(event.target===this)closeModal()">
  <div class="modal-box">
    <div class="modal-hd">
      <strong id="mps_name">Plan</strong>
      <button type="button" class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">
      <p id="mps_price" style="font-family:'DM Mono',monospace;color:var(--gold);margin-bottom:1.25rem">NPR 0</p>

      <form method="POST" action="esewa_experience_subscription_initiate.php">
        <input type="hidden" name="action" value="esewa_pay">
        <input type="hidden" name="plan_id" id="esewa_plan_id">
        <button type="submit" class="btn-submit btn-esewa">Pay with eSewa</button>
      </form>

      <form method="POST" action="khalti_experience_subscription_initiate.php">
        <input type="hidden" name="action" value="khalti_pay">
        <input type="hidden" name="plan_id" id="khalti_plan_id">
        <button type="submit" class="btn-submit btn-khalti">Pay with Khalti</button>
      </form>
    </div>
  </div>
</div>

<script>
function openPayModal(planId, planName, planPrice) {
  document.getElementById('esewa_plan_id').value = planId;
  document.getElementById('khalti_plan_id').value = planId;
  document.getElementById('mps_name').textContent = planName;
  document.getElementById('mps_price').textContent = 'NPR ' + Number(planPrice).toLocaleString();
  document.getElementById('payModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal() {
  document.getElementById('payModal').classList.remove('open');
  document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
