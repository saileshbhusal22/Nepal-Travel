<?php
/**
 * Author: Ishwor Dhital
 * Student ID: 2462304
 * Group: L5CG6
 */
session_name('nepal_admin_session');
session_start();
require_once __DIR__ . '/../config/db.php';

// ── Auth guard (uncomment in production) ─────────────────────────
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header('Location: /Nepal-Travel/user/login.php'); exit;
// }

$message = ''; 
$message_type = '';

// ── DELETE POST ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    
    // Clean up related data just in case cascading is not enabled
    $conn->query("DELETE FROM likes WHERE post_id=$id");
    $conn->query("DELETE FROM comments WHERE post_id=$id");
    $conn->query("DELETE FROM saves WHERE post_id=$id");
    
    if ($conn->query("DELETE FROM posts WHERE id=$id")) {
        header('Location: posts.php?msg=deleted'); exit;
    } else {
        $message = 'Database error: ' . $conn->error;
        $message_type = 'error';
    }
}

// ── Flash messages ────────────────────────────────────────────────
if (isset($_GET['msg'])) {
    $msgs = ['deleted' => 'Post deleted successfully ✓'];
    $message = $msgs[$_GET['msg']] ?? '';
    $message_type = 'success';
}

// ── Fetch all posts ───────────────────────────────────────────────
$sql = "
    SELECT 
        p.id, p.caption, p.image_path, p.created_at, p.user_id, p.destination, p.location, p.tags,
        u.full_name, u.username, u.profile_image,
        (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) as like_count,
        (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) as comment_count
    FROM posts p
    LEFT JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
";
$posts = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// ── View Comments (Optional GET handler) ──────────────────────────
$view_comments = null;
$view_post_id = null;
if (isset($_GET['view_comments'])) {
    $view_post_id = (int)$_GET['view_comments'];
    $view_comments = $conn->query("
        SELECT c.*, u.username, u.full_name 
        FROM comments c 
        LEFT JOIN users u ON c.user_id = u.id 
        WHERE c.post_id=$view_post_id 
        ORDER BY c.created_at DESC
    ")->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Posts — Admin Panel</title>
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
  --blue2:#4A90D9;
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
.btn-view{color:var(--blue2);border-color:rgba(74,144,217,0.25);background:transparent}
.btn-view:hover{background:rgba(74,144,217,0.12)}
.btn-del{color:var(--red2);border-color:rgba(224,85,85,0.25);background:transparent}
.btn-del:hover{background:var(--red2);color:#fff;border-color:var(--red2)}

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
.post-img{width:64px;height:64px;object-fit:cover;border-radius:5px;border:1px solid var(--border2);}
.post-img-placeholder{width:64px;height:64px;border-radius:5px;background:var(--surface2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--muted2)}
.act-row{display:flex;gap:6px;align-items:center}
.user-info { display: flex; align-items: center; gap: 8px; }
.user-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; }
.caption-text { max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block;}

/* MODAL / COMMENTS */
.comments-panel {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 24px;
    margin-bottom: 24px;
}
.comment-item {
    border-bottom: 1px solid var(--border);
    padding: 12px 0;
}
.comment-item:last-child {
    border-bottom: none;
}
.comment-user {
    font-weight: 600;
    color: var(--gold);
    font-size: 13px;
}
.comment-text {
    color: var(--text);
    margin-top: 4px;
    font-size: 14px;
}
.comment-date {
    color: var(--muted);
    font-size: 11px;
    font-family: var(--ff-mono);
    margin-top: 4px;
}

/* empty */
.empty{padding:60px;text-align:center;color:var(--muted2)}
.empty-ico{font-size:44px;opacity:0.2;margin-bottom:12px}

/* toast */
.toast{position:fixed;bottom:28px;right:28px;background:var(--surface);border:1px solid var(--border2);color:var(--text);padding:13px 20px;border-radius:8px;font-size:13px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,0.4);transform:translateY(12px);opacity:0;transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1);z-index:9999;pointer-events:none;border-left:3px solid var(--green2);}
.toast.show{transform:translateY(0);opacity:1}

@media(max-width:768px){
  .sidebar{display:none}
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
      <a href="deals_crud.php" class="sb-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42z"/></svg>
        Deals & Packages
      </a>
      
      <!-- ── Posts Link ── -->
      <a href="posts.php" class="sb-link on">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 3H3C2 3 1 4 1 5v14c0 1.1.9 2 2 2h18c1 0 2-.9 2-2V5c0-1-1-2-2-2zm0 16H3V5h18v14zm-10-7h8v2h-8v-2zm0-4h8v2h-8V8zm-6 8h4v-8H5v8z"/></svg>
        Posts
      </a>

      <div class="sb-section-label" style="margin-top:24px">Links</div>
      <a href="/Nepal-Travel/Public/index.php" class="sb-link">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        View Site
      </a>
      <a href="logout.php" class="sb-link">
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
      <div class="tb-breadcrumb">NEPAL TRAVEL / <span>USER POSTS</span></div>
      <div class="tb-actions">
        <?php if ($view_post_id): ?>
          <a href="posts.php" class="btn btn-ghost">← Back to Posts</a>
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
           VIEW COMMENTS PANEL
      ══════════════════════════════════ -->
      <?php if ($view_post_id !== null && $view_comments !== null): ?>
        <div class="sec-hd">
          <h1 class="sec-hd-title">Comments for Post #<?= $view_post_id ?></h1>
          <div class="sec-hd-rule"></div>
          <span class="sec-hd-count"><?= count($view_comments) ?> COMMENTS</span>
        </div>

        <div class="comments-panel">
            <?php if (empty($view_comments)): ?>
                <div class="empty">
                    <div class="empty-ico">💬</div>
                    <p>No comments on this post yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($view_comments as $c): ?>
                    <div class="comment-item">
                        <div class="comment-user">
                            <?= htmlspecialchars($c['full_name'] ?? $c['username'] ?? 'User') ?>
                        </div>
                        <div class="comment-text">
                            <?= htmlspecialchars($c['comment_text'] ?? '') ?>
                        </div>
                        <div class="comment-date">
                            <?= date('d M Y, h:i A', strtotime($c['created_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- ══════════════════════════════════
           POSTS LIST TABLE
      ══════════════════════════════════ -->
      <div class="sec-hd">
        <h1 class="sec-hd-title">Community Posts</h1>
        <div class="sec-hd-rule"></div>
        <span class="sec-hd-count"><?= count($posts) ?> TOTAL</span>
      </div>

      <div class="tcard">
        <div class="tcard-hd">
          <div>
            <div class="tcard-hd-title">All Posts</div>
          </div>
        </div>
        <div class="tcard-search">
          <input type="text" class="search-inp" id="postSearch" placeholder="Search by caption, destination, location, or user…" oninput="filterPosts()">
        </div>
        <div class="tscroll">
          <table id="postsTable">
            <thead>
              <tr>
                <th>ID</th>
                <th>Image</th>
                <th>User</th>
                <th>Caption</th>
                <th>Destination / Location</th>
                <th>Likes</th>
                <th>Comments</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($posts)): ?>
                <tr><td colspan="9"><div class="empty"><div class="empty-ico">📸</div><p>No posts yet.</p></div></td></tr>
              <?php endif; ?>
              <?php foreach ($posts as $p): ?>
              <tr>
                <td class="mono">#<?= $p['id'] ?></td>
                <td>
                  <?php if (!empty($p['image_path'])): ?>
                    <img src="/Nepal-Travel/<?= ltrim(htmlspecialchars($p['image_path']), '/') ?>" class="post-img" onerror="this.style.display='none'">
                  <?php else: ?>
                    <div class="post-img-placeholder">📸</div>
                  <?php endif; ?>
                </td>
                <td>
                    <div class="user-info">
                        <?php 
                        $profile_img = !empty($p['profile_image']) && $p['profile_image'] !== 'default.png' 
                            ? '/Nepal-Travel/' . ltrim($p['profile_image'], '/') 
                            : 'https://ui-avatars.com/api/?name=' . urlencode($p['username'] ?? 'User') . '&background=random';
                        ?>
                        <img src="<?= htmlspecialchars($profile_img) ?>" class="user-avatar" alt="Avatar">
                        <div>
                            <div style="font-weight:600"><?= htmlspecialchars($p['full_name'] ?? $p['username'] ?? 'Unknown User') ?></div>
                            <div class="mono">@<?= htmlspecialchars($p['username'] ?? 'unknown') ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="caption-text" title="<?= htmlspecialchars($p['caption'] ?? '') ?>">
                        <?= htmlspecialchars($p['caption'] ?? '—') ?>
                    </span>
                </td>
                <td>
                  <div style="font-weight: 500"><?= htmlspecialchars($p['destination'] ?? '—') ?></div>
                  <div class="mono" style="color:var(--muted)"><?= htmlspecialchars($p['location'] ?? '—') ?></div>
                </td>
                <td class="mono">
                    <span style="color: var(--red2);">❤️</span> <?= $p['like_count'] ?>
                </td>
                <td class="mono">
                    <span style="color: var(--blue2);">💬</span> <?= $p['comment_count'] ?>
                </td>
                <td class="mono"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                <td>
                  <div class="act-row">
                    <a href="?view_comments=<?= $p['id'] ?>" class="btn btn-sm btn-view">
                      <svg viewBox="0 0 24 24" fill="currentColor" style="width:11px;height:11px"><path d="M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18zM18 14H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>
                      Comments
                    </a>
                    <form method="POST" action="posts.php" style="display:inline" onsubmit="return confirm('Delete this post permanently? This action cannot be undone.')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $p['id'] ?>">
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

    </div><!-- /content -->
  </div><!-- /main -->
</div><!-- /wrap -->

<div class="toast" id="toast"></div>

<script>
function filterPosts() {
  const q = document.getElementById('postSearch').value.toLowerCase();
  document.querySelectorAll('#postsTable tbody tr').forEach(row => {
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
