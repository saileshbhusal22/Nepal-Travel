<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// ── Auth guard (uncomment in production) ─────────────────────────
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header('Location: /Nepal-Travel/user/login.php'); exit;
// }

$message = ''; 
$message_type = '';

// ── CREATE ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title          = trim($conn->real_escape_string($_POST['title'] ?? ''));
    $emoji          = trim($conn->real_escape_string($_POST['emoji'] ?? ''));
    $category       = trim($conn->real_escape_string($_POST['category'] ?? ''));
    $location       = trim($conn->real_escape_string($_POST['location'] ?? ''));
    $days           = (int)($_POST['days'] ?? 0);
    $season         = trim($conn->real_escape_string($_POST['season'] ?? ''));
    $price          = (float)($_POST['price'] ?? 0);
    $original_price = (float)($_POST['original_price'] ?? 0);
    $rating         = (float)($_POST['rating'] ?? 0);
    $reviews_count  = (int)($_POST['reviews_count'] ?? 0);
    $features       = trim($conn->real_escape_string($_POST['features'] ?? ''));
    $description    = trim($conn->real_escape_string($_POST['description'] ?? ''));
    $image_url      = trim($conn->real_escape_string($_POST['image_url'] ?? ''));
    $image_url_2    = trim($conn->real_escape_string($_POST['image_url_2'] ?? ''));
    $image_url_3    = trim($conn->real_escape_string($_POST['image_url_3'] ?? ''));
    $image_url_4    = trim($conn->real_escape_string($_POST['image_url_4'] ?? ''));

    if (empty($title) || empty($price)) {
        $message = 'Title and Price are required.';
        $message_type = 'error';
    } else {
        $sql = "INSERT INTO deals (title, emoji, category, location, days, season, price, original_price, rating, reviews_count, features, description, image_url, image_url_2, image_url_3, image_url_4)
                VALUES ('$title','$emoji','$category','$location',$days,'$season',$price,$original_price,$rating,$reviews_count,'$features','$description','$image_url','$image_url_2','$image_url_3','$image_url_4')";
        if ($conn->query($sql)) {
            header('Location: deals_crud.php?msg=created'); exit;
        } else {
            $message = 'Database error: ' . $conn->error;
            $message_type = 'error';
        }
    }
}

// ── UPDATE ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id             = (int)$_POST['id'];
    $title          = trim($conn->real_escape_string($_POST['title'] ?? ''));
    $emoji          = trim($conn->real_escape_string($_POST['emoji'] ?? ''));
    $category       = trim($conn->real_escape_string($_POST['category'] ?? ''));
    $location       = trim($conn->real_escape_string($_POST['location'] ?? ''));
    $days           = (int)($_POST['days'] ?? 0);
    $season         = trim($conn->real_escape_string($_POST['season'] ?? ''));
    $price          = (float)($_POST['price'] ?? 0);
    $original_price = (float)($_POST['original_price'] ?? 0);
    $rating         = (float)($_POST['rating'] ?? 0);
    $reviews_count  = (int)($_POST['reviews_count'] ?? 0);
    $features       = trim($conn->real_escape_string($_POST['features'] ?? ''));
    $description    = trim($conn->real_escape_string($_POST['description'] ?? ''));
    $image_url      = trim($conn->real_escape_string($_POST['image_url'] ?? ''));
    $image_url_2    = trim($conn->real_escape_string($_POST['image_url_2'] ?? ''));
    $image_url_3    = trim($conn->real_escape_string($_POST['image_url_3'] ?? ''));
    $image_url_4    = trim($conn->real_escape_string($_POST['image_url_4'] ?? ''));

    $sql = "UPDATE deals SET
                title='$title', emoji='$emoji', category='$category', location='$location',
                days=$days, season='$season', price=$price, original_price=$original_price,
                rating=$rating, reviews_count=$reviews_count, features='$features',
                description='$description', image_url='$image_url', image_url_2='$image_url_2',
                image_url_3='$image_url_3', image_url_4='$image_url_4'
            WHERE id=$id";
    if ($conn->query($sql)) {
        header('Location: deals_crud.php?msg=updated'); exit;
    } else {
        $message = 'Database error: ' . $conn->error;
        $message_type = 'error';
    }
}

// ── DELETE ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    if ($conn->query("DELETE FROM deals WHERE id=$id")) {
        header('Location: deals_crud.php?msg=deleted'); exit;
    } else {
        $message = 'Database error: ' . $conn->error;
        $message_type = 'error';
    }
}

// ── Flash messages ────────────────────────────────────────────────
if (isset($_GET['msg'])) {
    $msgs = ['created' => 'Deal created successfully ✓', 'updated' => 'Deal updated successfully ✓', 'deleted' => 'Deal deleted successfully ✓'];
    $message = $msgs[$_GET['msg']] ?? '';
    $message_type = 'success';
}

// ── Fetch all deals ───────────────────────────────────────────────
$deals = $conn->query("SELECT * FROM deals ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

// ── Fetch single deal for edit ────────────────────────────────────
$edit_deal = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $r   = $conn->query("SELECT * FROM deals WHERE id=$eid");
    $edit_deal = $r ? $r->fetch_assoc() : null;
}

$show_form = isset($_GET['new']) || $edit_deal;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Deals & Packages — Admin CRUD</title>
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
  --green2:#4CAF7D;
  --red2:#E05555;
  --amber2:#F0A030;
  --ff-display:'Syne',sans-serif;
  --ff-body:'DM Sans',sans-serif;
  --ff-mono:'DM Mono',monospace;
}
html,body{min-height:100%;background:var(--bg);color:var(--text);font-family:var(--ff-body);-webkit-font-smoothing:antialiased}
a{text-decoration:none;color:inherit}
button,input,select,textarea{font-family:var(--ff-body)}

/* LAYOUT */
.wrap{display:flex;min-height:100vh}

/* SIDEBAR */
.sidebar{width:240px;flex-shrink:0;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:sticky;top:0;height:100vh;}
.sb-logo{padding:28px 24px 22px;border-bottom:1px solid var(--border)}
.sb-logo-title{font-family:var(--ff-display);font-size:17px;font-weight:800;color:var(--text);display:flex;align-items:center;gap:10px;}
.sb-logo-title em{font-style:italic;color:var(--gold)}
.sb-logo-sub{font-size:10px;color:var(--muted2);letter-spacing:2px;text-transform:uppercase;margin-top:4px;font-family:var(--ff-mono)}
.sb-nav{padding:16px 12px;flex:1}
.sb-section-label{font-size:9px;letter-spacing:2.5px;text-transform:uppercase;color:var(--muted2);font-weight:600;padding:0 12px;margin:16px 0 8px;font-family:var(--ff-mono);}
.sb-link{display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:8px;font-size:13px;font-weight:500;color:var(--muted);transition:all 0.18s;border:1px solid transparent;}
.sb-link svg{width:16px;height:16px;flex-shrink:0;opacity:0.6}
.sb-link:hover{background:var(--surface2);color:var(--text);border-color:var(--border)}
.sb-link.on{background:rgba(201,162,39,0.1);color:var(--gold);border-color:rgba(201,162,39,0.2)}
.sb-link.on svg{opacity:1}
.sb-footer{padding:16px 24px;border-top:1px solid var(--border)}
.sb-avatar{width:34px;height:34px;border-radius:50%;background:rgba(201,162,39,0.2);border:1px solid rgba(201,162,39,0.3);display:flex;align-items:center;justify-content:center;font-family:var(--ff-display);font-size:13px;font-weight:700;color:var(--gold);}

/* MAIN */
.main{flex:1;display:flex;flex-direction:column}
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:0 36px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
.tb-breadcrumb{font-family:var(--ff-mono);font-size:11px;color:var(--muted2);letter-spacing:1px}
.tb-breadcrumb span{color:var(--gold)}
.tb-actions{display:flex;align-items:center;gap:12px}
.content{padding:36px;flex:1}

/* SECTION HEADER */
.sec-hd{display:flex;align-items:center;gap:20px;margin-bottom:28px}
.sec-hd-title{font-family:var(--ff-display);font-size:26px;font-weight:800;color:var(--text)}
.sec-hd-rule{flex:1;height:1px;background:var(--border2)}
.sec-hd-count{font-family:var(--ff-mono);font-size:11px;color:var(--muted2);letter-spacing:1px}

/* ALERT */
.alert{display:flex;align-items:center;gap:12px;padding:13px 20px;border-radius:8px;font-size:13px;margin-bottom:24px;border-left:3px solid;}
.alert-success{background:rgba(76,175,125,0.1);color:var(--green2);border-color:var(--green2)}
.alert-error{background:rgba(224,85,85,0.1);color:var(--red2);border-color:var(--red2)}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 20px;border-radius:7px;font-size:13px;font-weight:600;border:1px solid;cursor:pointer;transition:all 0.15s;text-decoration:none;}
.btn-gold{background:var(--gold);color:#000;border-color:var(--gold)}
.btn-gold:hover{background:var(--gold2);border-color:var(--gold2)}
.btn-ghost{background:transparent;color:var(--muted);border-color:var(--border2)}
.btn-ghost:hover{color:var(--text);border-color:var(--border2);background:var(--surface2)}
.btn-sm{padding:5px 12px;font-size:11px;border-radius:5px}
.btn-edit{color:var(--amber2);border-color:rgba(240,160,48,0.25);background:transparent}
.btn-edit:hover{background:rgba(240,160,48,0.12)}
.btn-del{color:var(--red2);border-color:rgba(224,85,85,0.25);background:transparent}
.btn-del:hover{background:var(--red2);color:#fff;border-color:var(--red2)}

/* FORM CARD */
.form-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:32px}
.form-card-hd{padding:20px 28px;border-bottom:1px solid var(--border);background:var(--surface2);display:flex;align-items:center;justify-content:space-between;}
.form-card-hd-title{font-family:var(--ff-display);font-size:16px;font-weight:700;color:var(--text)}
.form-card-body{padding:28px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px}
.form-grid-4{display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:18px}
.fg{display:flex;flex-direction:column;gap:7px}
.fg.full{grid-column:1/-1}
.flbl{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);font-weight:600;font-family:var(--ff-mono);}
.fin{background:var(--surface2);color:var(--text);border:1px solid var(--border2);border-radius:7px;padding:10px 14px;font-size:13px;outline:none;transition:border-color 0.2s;width:100%;}
.fin:focus{border-color:rgba(201,162,39,0.5)}
.fin::placeholder{color:var(--muted2)}
textarea.fin{resize:vertical;min-height:90px;line-height:1.6}
.fin-hint{font-size:11px;color:var(--muted2);margin-top:3px}
.form-actions{display:flex;gap:12px;margin-top:24px;padding-top:20px;border-top:1px solid var(--border)}

/* TABLE CARD */
.tcard{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:32px}
.tcard-hd{padding:18px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface2);}
.tcard-hd-title{font-family:var(--ff-display);font-size:15px;font-weight:700;color:var(--text)}
.tcard-search{padding:14px 24px;border-bottom:1px solid var(--border);background:var(--surface);}
.search-inp{width:100%;padding:9px 14px;background:var(--surface2);border:1px solid var(--border2);border-radius:7px;font-size:13px;color:var(--text);outline:none;transition:border-color 0.2s;}
.search-inp::placeholder{color:var(--muted2)}
.search-inp:focus{border-color:rgba(201,162,39,0.4)}
.tscroll{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead th{font-size:9px;letter-spacing:2.5px;text-transform:uppercase;color:var(--muted2);font-weight:700;padding:12px 18px;text-align:left;background:var(--surface2);border-bottom:1px solid var(--border);white-space:nowrap;font-family:var(--ff-mono);}
tbody td{padding:13px 18px;border-bottom:1px solid var(--border);font-size:13px;color:var(--text);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover td{background:rgba(255,255,255,0.02)}
.mono{font-family:var(--ff-mono);font-size:11px;color:var(--muted)}
.deal-img{width:52px;height:38px;object-fit:cover;border-radius:5px;border:1px solid var(--border2);}
.deal-img-placeholder{width:52px;height:38px;border-radius:5px;background:var(--surface2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:18px;}
.pill{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;font-family:var(--ff-mono);white-space:nowrap;}
.pill-cat{background:rgba(201,162,39,0.12);color:var(--gold);border:1px solid rgba(201,162,39,0.2)}
.act-row{display:flex;gap:6px;align-items:center}

/* empty */
.empty{padding:60px;text-align:center;color:var(--muted2)}
.empty-ico{font-size:44px;opacity:0.2;margin-bottom:12px}

/* toast */
.toast{position:fixed;bottom:28px;right:28px;background:var(--surface);border:1px solid var(--border2);color:var(--text);padding:13px 20px;border-radius:8px;font-size:13px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,0.4);transform:translateY(12px);opacity:0;transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1);z-index:9999;pointer-events:none;border-left:3px solid var(--green2);}
.toast.show{transform:translateY(0);opacity:1}

@media(max-width:768px){
  .sidebar{display:none}
  .form-grid,.form-grid-3,.form-grid-4{grid-template-columns:1fr}
  .content{padding:20px}
}
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
      <a href="dashboard.php?tab=overview" class="sb-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
        Overview
      </a>
      <a href="dashboard.php?tab=users" class="sb-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        Users
      </a>
      <a href="dashboard.php?tab=bookings" class="sb-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
        Bookings
      </a>
      <a href="deals_crud.php" class="sb-link on">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42z"/></svg>
        Deals & Packages
      </a>
      <div class="sb-section-label" style="margin-top:24px">Links</div>
      <a href="/Nepal-Travel/Public/index.php" class="sb-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        View Site
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
          <div style="font-size:12px;font-weight:600;color:var(--text)">Admin</div>
          <div style="font-size:10px;color:var(--muted2);font-family:var(--ff-mono)">// Super Admin</div>
        </div>
      </div>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main">
    <header class="topbar">
      <div class="tb-breadcrumb">NEPAL TRAVEL / <span>DEALS & PACKAGES</span></div>
      <div class="tb-actions">
        <?php if (!$show_form): ?>
          <a href="?new=1" class="btn btn-gold">
            <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            Add New Deal
          </a>
        <?php else: ?>
          <a href="deals_crud.php" class="btn btn-ghost">← Back to List</a>
        <?php endif; ?>
      </div>
    </header>

    <div class="content">

      <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?>">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>


      <!-- ══════════════════════════════════
           CREATE / EDIT FORM
      ══════════════════════════════════ -->
      <?php if ($show_form): ?>

        <div class="sec-hd">
          <h1 class="sec-hd-title"><?= $edit_deal ? 'Edit Deal' : 'Add New Deal' ?></h1>
          <div class="sec-hd-rule"></div>
        </div>

        <form method="POST" action="deals_crud.php">
          <input type="hidden" name="action" value="<?= $edit_deal ? 'update' : 'create' ?>">
          <?php if ($edit_deal): ?>
            <input type="hidden" name="id" value="<?= $edit_deal['id'] ?>">
          <?php endif; ?>

          <!-- Basic Info -->
          <div class="form-card">
            <div class="form-card-hd">
              <div class="form-card-hd-title">Basic Information</div>
            </div>
            <div class="form-card-body">
              <div class="form-grid">
                <div class="fg full">
                  <label class="flbl">Title *</label>
                  <input type="text" name="title" class="fin" placeholder="e.g. Everest Base Camp Trek" required value="<?= htmlspecialchars($edit_deal['title'] ?? '') ?>">
                </div>
              </div>
              <div class="form-grid-4" style="margin-top:18px">
                <div class="fg">
                  <label class="flbl">Emoji</label>
                  <input type="text" name="emoji" class="fin" placeholder="🏔️" value="<?= htmlspecialchars($edit_deal['emoji'] ?? '') ?>">
                </div>
                <div class="fg">
                  <label class="flbl">Category</label>
                  <input type="text" name="category" class="fin" placeholder="e.g. Trekking" value="<?= htmlspecialchars($edit_deal['category'] ?? '') ?>">
                </div>
                <div class="fg">
                  <label class="flbl">Location</label>
                  <input type="text" name="location" class="fin" placeholder="e.g. Solukhumbu" value="<?= htmlspecialchars($edit_deal['location'] ?? '') ?>">
                </div>
                <div class="fg">
                  <label class="flbl">Season</label>
                  <input type="text" name="season" class="fin" placeholder="e.g. Spring / Autumn" value="<?= htmlspecialchars($edit_deal['season'] ?? '') ?>">
                </div>
              </div>
              <div class="form-grid" style="margin-top:18px">
                <div class="fg full">
                  <label class="flbl">Description</label>
                  <textarea name="description" class="fin" placeholder="Describe the deal…"><?= htmlspecialchars($edit_deal['description'] ?? '') ?></textarea>
                </div>
                <div class="fg full">
                  <label class="flbl">Features / Highlights</label>
                  <input type="text" name="features" class="fin" placeholder="Comma-separated: Guided Tour, Meals Included, Permits" value="<?= htmlspecialchars($edit_deal['features'] ?? '') ?>">
                  <span class="fin-hint">Separate each highlight with a comma</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Pricing & Stats -->
          <div class="form-card">
            <div class="form-card-hd">
              <div class="form-card-hd-title">Pricing & Stats</div>
            </div>
            <div class="form-card-body">
              <div class="form-grid-4">
                <div class="fg">
                  <label class="flbl">Price (NPR) *</label>
                  <input type="number" name="price" class="fin" placeholder="0" required step="0.01" value="<?= htmlspecialchars($edit_deal['price'] ?? '') ?>">
                </div>
                <div class="fg">
                  <label class="flbl">Original Price (NPR)</label>
                  <input type="number" name="original_price" class="fin" placeholder="0" step="0.01" value="<?= htmlspecialchars($edit_deal['original_price'] ?? '') ?>">
                  <span class="fin-hint">Leave 0 if no discount</span>
                </div>
                <div class="fg">
                  <label class="flbl">Duration (Days)</label>
                  <input type="number" name="days" class="fin" placeholder="0" min="0" value="<?= htmlspecialchars($edit_deal['days'] ?? '') ?>">
                </div>
                <div class="fg">
                  <label class="flbl">Rating (0–5)</label>
                  <input type="number" name="rating" class="fin" placeholder="0.0" step="0.1" min="0" max="5" value="<?= htmlspecialchars($edit_deal['rating'] ?? '') ?>">
                </div>
                <div class="fg">
                  <label class="flbl">Reviews Count</label>
                  <input type="number" name="reviews_count" class="fin" placeholder="0" min="0" value="<?= htmlspecialchars($edit_deal['reviews_count'] ?? '') ?>">
                </div>
              </div>
            </div>
          </div>

          <!-- Images -->
          <div class="form-card">
            <div class="form-card-hd">
              <div class="form-card-hd-title">Images</div>
            </div>
            <div class="form-card-body">
              <div class="form-grid">
                <div class="fg">
                  <label class="flbl">Main Image URL</label>
                  <input type="text" name="image_url" class="fin" placeholder="https://…" value="<?= htmlspecialchars($edit_deal['image_url'] ?? '') ?>">
                </div>
                <div class="fg">
                  <label class="flbl">Image 2 URL</label>
                  <input type="text" name="image_url_2" class="fin" placeholder="https://…" value="<?= htmlspecialchars($edit_deal['image_url_2'] ?? '') ?>">
                </div>
                <div class="fg">
                  <label class="flbl">Image 3 URL</label>
                  <input type="text" name="image_url_3" class="fin" placeholder="https://…" value="<?= htmlspecialchars($edit_deal['image_url_3'] ?? '') ?>">
                </div>
                <div class="fg">
                  <label class="flbl">Image 4 URL</label>
                  <input type="text" name="image_url_4" class="fin" placeholder="https://…" value="<?= htmlspecialchars($edit_deal['image_url_4'] ?? '') ?>">
                </div>
              </div>

              <!-- Live image preview -->
              <?php $preview = $edit_deal['image_url'] ?? ''; ?>
              <?php if ($preview): ?>
              <div style="margin-top:18px">
                <p class="flbl" style="margin-bottom:10px">Current Main Image</p>
                <img src="<?= htmlspecialchars($preview) ?>" style="height:120px;border-radius:8px;border:1px solid var(--border2);object-fit:cover;" onerror="this.style.display='none'">
              </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-gold">
              <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
              <?= $edit_deal ? 'Save Changes' : 'Create Deal' ?>
            </button>
            <a href="deals_crud.php" class="btn btn-ghost">Cancel</a>
          </div>
        </form>


      <!-- ══════════════════════════════════
           DEALS LIST TABLE
      ══════════════════════════════════ -->
      <?php else: ?>

        <div class="sec-hd">
          <h1 class="sec-hd-title">Deals & Packages</h1>
          <div class="sec-hd-rule"></div>
          <span class="sec-hd-count"><?= count($deals) ?> TOTAL</span>
        </div>

        <div class="tcard">
          <div class="tcard-hd">
            <div>
              <div class="tcard-hd-title">All Deals</div>
            </div>
            <a href="?new=1" class="btn btn-gold btn-sm">
              <svg viewBox="0 0 24 24" fill="currentColor" style="width:12px;height:12px"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
              Add Deal
            </a>
          </div>
          <div class="tcard-search">
            <input type="text" class="search-inp" id="dealSearch" placeholder="Search by title, category, location…" oninput="filterDeals()">
          </div>
          <div class="tscroll">
            <table id="dealsTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Image</th>
                  <th>Title</th>
                  <th>Category</th>
                  <th>Location</th>
                  <th>Days</th>
                  <th>Price (NPR)</th>
                  <th>Orig. Price</th>
                  <th>Rating</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($deals)): ?>
                  <tr><td colspan="10"><div class="empty"><div class="empty-ico">🏷️</div><p>No deals yet. Add your first one!</p></div></td></tr>
                <?php endif; ?>
                <?php foreach ($deals as $d): ?>
                <tr>
                  <td class="mono">#<?= $d['id'] ?></td>
                  <td>
                    <?php if (!empty($d['image_url']) && strtoupper($d['image_url']) !== 'NULL'): ?>
                      <img src="<?= htmlspecialchars($d['image_url']) ?>" class="deal-img" onerror="this.style.display='none'">
                    <?php else: ?>
                      <div class="deal-img-placeholder"><?= !empty($d['emoji']) ? $d['emoji'] : '🏔️' ?></div>
                    <?php endif; ?>
                  </td>
                  <td style="font-weight:600;max-width:200px"><?= htmlspecialchars($d['title']) ?></td>
                  <td>
                    <?php if (!empty($d['category'])): ?>
                      <span class="pill pill-cat"><?= htmlspecialchars($d['category']) ?></span>
                    <?php else: ?>—<?php endif; ?>
                  </td>
                  <td style="color:var(--muted)"><?= htmlspecialchars($d['location'] ?? '—') ?></td>
                  <td style="text-align:center"><?= (int)($d['days'] ?? 0) ?></td>
                  <td class="mono"><?= number_format((float)$d['price']) ?></td>
                  <td class="mono" style="color:var(--muted2)">
                    <?= !empty($d['original_price']) && (float)$d['original_price'] > 0 ? number_format((float)$d['original_price']) : '—' ?>
                  </td>
                  <td>
                    <?php if (!empty($d['rating'])): ?>
                      <span style="color:var(--gold)">★</span> <?= number_format((float)$d['rating'],1) ?>
                    <?php else: ?>—<?php endif; ?>
                  </td>
                  <td>
                    <div class="act-row">
                      <a href="?edit=<?= $d['id'] ?>" class="btn btn-sm btn-edit">
                        <svg viewBox="0 0 24 24" fill="currentColor" style="width:11px;height:11px"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                        Edit
                      </a>
                      <form method="POST" action="deals_crud.php" style="display:inline" onsubmit="return confirm('Delete this deal permanently?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-del">
                          <svg viewBox="0 0 24 24" fill="currentColor" style="width:11px;height:11px"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                          Delete
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      <?php endif; ?>

    </div><!-- /content -->
  </div><!-- /main -->
</div><!-- /wrap -->

<div class="toast" id="toast"></div>

<script>
function filterDeals() {
  const q = document.getElementById('dealSearch').value.toLowerCase();
  document.querySelectorAll('#dealsTable tbody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3200);
}

<?php if ($message_type === 'success'): ?>
document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($message) ?>'));
<?php endif; ?>
</script>
</body>
</html>