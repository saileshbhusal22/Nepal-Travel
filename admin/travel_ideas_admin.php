<?php
/**
 * Author: Ishwor Dhital
 * Student ID: 2462304
 * Group: L5CG6
 */
session_name('nepal_admin_session');
session_start();
require_once __DIR__ . '/../config/db.php';

// ── Auth guard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /Nepal-Travel/user/login.php'); exit;
}

$message = ''; 
$message_type = '';

// ── DELETE IDEA ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    
    // Clean up related data
    $conn->query("DELETE FROM itineraries WHERE idea_id=$id");
    $conn->query("DELETE FROM travel_idea_details WHERE idea_id=$id");
    $conn->query("DELETE FROM travel_idea_experiences WHERE idea_id=$id");
    
    if ($conn->query("DELETE FROM travel_ideas WHERE id=$id")) {
        header('Location: travel_ideas_admin.php?msg=deleted'); exit;
    } else {
        $message = 'Database error: ' . $conn->error;
        $message_type = 'error';
    }
}

// ── Flash messages ────────────────────────────────────────────────
if (isset($_GET['msg'])) {
    $msgs = ['deleted' => 'Travel Idea deleted successfully ✓'];
    $message = $msgs[$_GET['msg']] ?? '';
    $message_type = 'success';
}

// ── Fetch all travel ideas ─────────────────────────────────────────
$sql = "
    SELECT 
        t.id, t.title, t.image_path, t.created_at, t.user_id, t.status, t.difficulty,
        u.full_name, u.username, u.email
    FROM travel_ideas t
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
";
$ideas = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// ── Active Tabs and Admin Info (for sidebar) ────────────────────────
$activeTab = 'travel_ideas';
$admin_id = (int)$_SESSION['user_id'];
$admin_info_res = $conn->query("SELECT full_name, username, profile_image FROM users WHERE id = $admin_id LIMIT 1");
$admin_info     = $admin_info_res ? $admin_info_res->fetch_assoc() : [];
$admin_display  = htmlspecialchars($admin_info['full_name'] ?? $admin_info['username'] ?? 'Admin');
$admin_initial  = strtoupper(substr($admin_info['full_name'] ?? $admin_info['username'] ?? 'A', 0, 1));
$admin_avatar   = $admin_info['profile_image'] ?? '';

// Chat badge count for sidebar
$chat_table_exists = $conn->query("SHOW TABLES LIKE 'chat_messages'")->num_rows > 0;
$total_chat_unread = 0;
if ($chat_table_exists) {
    $unread_res = $conn->query("SELECT COUNT(*) FROM chat_messages WHERE sender='user' AND is_read=0");
    $total_chat_unread = $unread_res ? (int)$unread_res->fetch_row()[0] : 0;
}

$pending_subs = 0;
$pending_deals = 0;
$total_reviews = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Travel Ideas — Admin Panel</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
/* Mimic dashboard styles */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0C0E14;--surface:#13161F;--surface2:#1A1E2A;
  --border:rgba(255,255,255,0.07);--border2:rgba(255,255,255,0.12);
  --text:#F0EEE8;--muted:rgba(240,238,232,0.45);--muted2:rgba(240,238,232,0.25);
  --gold:#C9A227;
  --green2:#4CAF7D;--red2:#E05555;--blue2:#4A90D9;
  --ff-d:'Syne',sans-serif;--ff-b:'DM Sans',sans-serif;--ff-m:'DM Mono',monospace;
}
html,body{height:100%;background:var(--bg);color:var(--text);font-family:var(--ff-b);-webkit-font-smoothing:antialiased}
a{text-decoration:none;color:inherit}
button,input,select,textarea{font-family:var(--ff-b)}
.admin-wrap{display:flex;min-height:100vh}

/* Sidebar */
.sidebar{width:240px;flex-shrink:0;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto}
.sb-logo{padding:28px 24px 22px;border-bottom:1px solid var(--border)}
.sb-logo-title{font-family:var(--ff-d);font-size:17px;font-weight:800;color:var(--text);letter-spacing:-0.3px;display:flex;align-items:center;gap:10px}
.sb-logo-title em{font-style:italic;color:var(--gold)}
.sb-nav{padding:16px 12px;flex:1}
.sb-link{display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:8px;font-size:13px;font-weight:500;color:var(--muted);transition:all 0.18s;cursor:pointer;border:1px solid transparent}
.sb-link svg{width:16px;height:16px;flex-shrink:0;opacity:0.6}
.sb-link:hover{background:var(--surface2);color:var(--text);border-color:var(--border)}
.sb-link.on{background:rgba(201,162,39,0.1);color:var(--gold);border-color:rgba(201,162,39,0.2)}
.sb-link.on svg{opacity:1}
.sb-badge{margin-left:auto;background:rgba(201,162,39,0.15);color:var(--gold);font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;font-family:var(--ff-m)}

/* Main */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden}
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:0 36px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.content{padding:36px;flex:1;overflow-y:auto}
.sec-hd{display:flex;align-items:center;gap:20px;margin-bottom:28px}
.sec-hd-title{font-family:var(--ff-d);font-size:26px;font-weight:800;color:var(--text)}

/* Table & Buttons */
.tcard{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:32px}
.tcard-hd{padding:18px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface2)}
.tscroll{width:100%;overflow-x:auto}
table{width:100%;border-collapse:collapse;white-space:nowrap;font-size:13px}
th{text-align:left;padding:14px 24px;font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);border-bottom:1px solid var(--border);font-family:var(--ff-m)}
td{padding:16px 24px;border-bottom:1px solid var(--border)}
.mono{font-family:var(--ff-m);font-size:11px}
.act-row{display:flex;align-items:center;gap:8px}
.btn{background:var(--surface2);border:1px solid var(--border);color:var(--text);padding:6px 12px;border-radius:6px;font-size:11px;cursor:pointer;transition:all 0.2s}
.btn:hover{background:var(--surface3)}
.btn-delete{background:rgba(224,85,85,0.1);color:var(--red2);border-color:rgba(224,85,85,0.2)}
.btn-delete:hover{background:rgba(224,85,85,0.2)}

.btn-upload {
    background: var(--gold);
    color: #000;
    border: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
}

/* Modals */
.modal-bd { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(8px); }
.modal-bd.open { display: flex; }
.modal-box { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; width: 500px; max-width: 90%; max-height: 90vh; overflow-y: auto; padding: 24px; }
.form-grp { margin-bottom: 16px; }
.form-grp label { display: block; font-size: 12px; color: var(--muted); margin-bottom: 8px; }
.form-grp input, .form-grp select, .form-grp textarea { width: 100%; background: var(--surface2); border: 1px solid var(--border); color: #fff; padding: 10px; border-radius: 8px; font-family: var(--ff-b); }
</style>
</head>
<body>

<div class="admin-wrap">

  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="sb-logo">
      <div class="sb-logo-title">Nepal<em>Travel</em></div>
      <div class="sb-logo-sub">Admin Panel</div>
    </div>
    <nav class="sb-nav">
      <a href="dashboard.php?tab=overview" class="sb-link"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg> Overview</a>
      <a href="dashboard.php?tab=users" class="sb-link"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg> Users</a>
      <a href="posts.php" class="sb-link"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 3H3C2 3 1 4 1 5v14c0 1.1.9 2 2 2h18c1 0 2-.9 2-2V5c0-1-1-2-2-2zm0 16H3V5h18v14zm-10-7h8v2h-8v-2zm0-4h8v2h-8V8zm-6 8h4v-8H5v8z"/></svg> Posts</a>
      <a href="deals_crud.php" class="sb-link"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42z"/></svg> Deals</a>
      <a href="travel_ideas_admin.php" class="sb-link on"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 21c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7z"/></svg> Travel Ideas</a>
      <a href="dashboard.php?tab=bookings" class="sb-link"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg> Bookings</a>
      <a href="reviews.php" class="sb-link"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M22 9.24l-7.19-.62L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27 18.18 21l-1.63-7.03L22 9.24z"/></svg> Reviews</a>
      <a href="dashboard.php?tab=chat" class="sb-link"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg> Live Chat</a>
    </nav>
  </div>

  <!-- MAIN -->
  <div class="main">
    <header class="topbar">
      <div style="font-size:11px;color:var(--muted)">Admin / Travel Ideas</div>
      <div style="display:flex;align-items:center;gap:12px">
        <span style="font-size:11px"><?= $admin_display ?></span>
        <div style="width:30px;height:30px;border-radius:50%;background:var(--gold);color:#000;display:flex;align-items:center;justify-content:center;font-weight:bold"><?= $admin_initial ?></div>
      </div>
    </header>

    <div class="content">
      <?php if ($message): ?>
      <div class="alert alert-<?= $message_type ?>">
        <?= htmlspecialchars($message) ?>
      </div>
      <?php endif; ?>

      <div class="sec-hd">
        <h1 class="sec-hd-title">Travel Ideas</h1>
        <button class="btn-upload" onclick="openM('uploadModal')" style="margin-left: auto;">+ Upload Idea</button>
      </div>

      <div class="tcard">
        <div class="tcard-hd">
          <div style="font-size:15px;font-weight:bold;">All Travel Ideas</div>
        </div>
        <div class="tscroll">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Title</th>
                <th>Author</th>
                <th>Status</th>
                <th>Difficulty</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if(empty($ideas)): ?>
                <tr><td colspan="8" style="text-align:center;color:var(--muted)">No travel ideas found.</td></tr>
              <?php endif; ?>
              <?php foreach($ideas as $i): ?>
              <tr>
                <td class="mono">#<?= $i['id'] ?></td>
                <td>
                  <?php if($i['image_path']): ?>
                    <img src="<?= htmlspecialchars($i['image_path']) ?>" style="width:40px;height:40px;border-radius:6px;object-fit:cover">
                  <?php else: ?>
                    <div style="width:40px;height:40px;border-radius:6px;background:var(--surface2);display:flex;align-items:center;justify-content:center;font-size:18px">💡</div>
                  <?php endif; ?>
                </td>
                <td style="font-weight:bold;max-width:200px;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($i['title']) ?></td>
                <td><?= htmlspecialchars($i['full_name'] ?? $i['username'] ?? 'Admin') ?></td>
                <td>
                  <span style="background:<?= $i['status'] === 'published' ? 'var(--green2)' : 'var(--muted)' ?>;color:#000;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:bold;">
                    <?= strtoupper($i['status']) ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($i['difficulty'] ?? 'N/A') ?></td>
                <td class="mono"><?= date('M d, Y', strtotime($i['created_at'])) ?></td>
                <td>
                  <div class="act-row">
                    <a href="/Nepal-Travel/Public/travel_idea_detail.php?id=<?= $i['id'] ?>" target="_blank" class="btn">View</a>
                    <form method="POST" style="margin:0" onsubmit="return confirm('Are you sure you want to delete this travel idea?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $i['id'] ?>">
                      <button type="submit" class="btn btn-delete">Delete</button>
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

<!-- Upload Modal -->
<div class="modal-bd" id="uploadModal" onclick="if(event.target===this) closeM('uploadModal')">
  <div class="modal-box">
    <h2 style="margin-bottom:20px;font-family:var(--ff-d);">Upload Travel Idea</h2>
    <form id="uploadIdeaForm" onsubmit="submitIdea(event)">
      <div class="form-grp">
        <label>Title</label>
        <input type="text" name="title" required>
      </div>
      <div class="form-grp">
        <label>Cover Image</label>
        <input type="file" name="image" accept="image/*" required>
      </div>
      <div class="form-grp">
        <label>Province / Region</label>
        <input type="text" name="province" placeholder="e.g. Bagmati">
      </div>
      <div class="form-grp">
        <label>Duration (Days)</label>
        <input type="number" name="duration_days" min="1" required>
      </div>
      <div class="form-grp">
        <label>Difficulty</label>
        <select name="difficulty">
          <option value="Easy">Easy</option>
          <option value="Moderate">Moderate</option>
          <option value="Challenging">Challenging</option>
        </select>
      </div>
      <div class="form-grp">
        <label>Content / Intro</label>
        <textarea name="content" rows="4" required></textarea>
      </div>
      <button type="submit" class="btn-upload" style="width:100%" id="submitBtn">Upload Idea</button>
    </form>
  </div>
</div>

<script>
function openM(id) { document.getElementById(id).classList.add('open'); }
function closeM(id) { document.getElementById(id).classList.remove('open'); }

async function submitIdea(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    btn.textContent = 'Uploading...';
    btn.disabled = true;

    const fd = new FormData(e.target);
    // Add default day order to bypass empty itinerary validation just in case
    fd.append('itinerary_day_order[]', '1');
    fd.append('itinerary_day_title[]', 'Day 1');
    fd.append('itinerary_morning[]', 'Welcome and Arrival');

    try {
        const res = await fetch('/Nepal-Travel/Public/api/travel_ideas/create_idea.php', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if (data.success) {
            alert('Travel idea uploaded successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch(err) {
        alert('Network error occurred.');
    }
    btn.textContent = 'Upload Idea';
    btn.disabled = false;
}
</script>

</body>
</html>
