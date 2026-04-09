<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php');
    exit;
}

require_once '../config/db.php'; // $conn = mysqli connection

// Fetch fresh user data
$stmt = $conn->prepare("SELECT id, full_name, username, email, profile_image, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header('Location: /Nepal-Travel/user/login.php');
    exit;
}

// Sync session
$_SESSION['user_name']     = $user['full_name'];
$_SESSION['profile_image'] = $user['profile_image'];

// Resolve profile image URL
$profileImageUrl = null;
if (!empty($user['profile_image']) && $user['profile_image'] !== 'default.png') {
    $absPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/Nepal-Travel/' . ltrim($user['profile_image'], '/');
    if (file_exists($absPath)) {
        $profileImageUrl = '/Nepal-Travel/' . ltrim($user['profile_image'], '/') . '?t=' . time();
    }
}

$userName     = htmlspecialchars($user['full_name']);
$userEmail    = htmlspecialchars($user['email']);
$userUsername = htmlspecialchars($user['username']);
$initials     = strtoupper(substr($user['full_name'], 0, 1));
$memberSince  = date('F Y', strtotime($user['created_at']));

// Active tab
$activeTab = $_GET['tab'] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Nepal Truly Authentic</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:wght@700;900&family=Great+Vibes&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:      #1b3a5a;
            --blue:      #285da1;
            --gold:      #f5a623;
            --teal:      #1b8c6e;
            --light:     #f4f7fc;
            --white:     #ffffff;
            --muted:     #7a90a8;
            --border:    #e2e8f0;
            --sidebar-w: 280px;
            --font-body: 'Montserrat', sans-serif;
            --font-disp: 'Playfair Display', serif;
        }

        html, body {
            height: 100%;
            font-family: var(--font-body);
            background: var(--light);
            color: var(--navy);
        }

        a { text-decoration: none; color: inherit; }

        /* ===================== LAYOUT ===================== */
        .dash-wrap {
            display: flex;
            min-height: 100vh;
        }

        /* ===================== SIDEBAR ===================== */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--navy);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            z-index: 100;
            overflow-y: auto;
        }

        /* Sidebar brand */
        .sidebar-brand {
            padding: 28px 30px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-brand a {
            font-family: var(--font-disp);
            font-size: 22px;
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-brand span.dot {
            width: 8px; height: 8px;
            background: var(--gold);
            border-radius: 50%;
            display: inline-block;
        }

        /* ---- Profile Card inside sidebar ---- */
        .sidebar-profile {
            padding: 28px 24px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        /* Avatar ring */
        .avatar-ring {
            position: relative;
            width: 100px;
            height: 100px;
            margin-bottom: 14px;
            cursor: pointer;
        }

        .avatar-ring::before {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            background: conic-gradient(var(--gold), var(--blue), var(--teal), var(--gold));
            animation: spin-ring 5s linear infinite;
            z-index: 0;
        }

        @keyframes spin-ring {
            to { transform: rotate(360deg); }
        }

        .avatar-inner {
            position: absolute;
            inset: 3px;
            border-radius: 50%;
            background: var(--navy);
            overflow: hidden;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-inner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .avatar-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #243f60, var(--blue));
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 36px;
            font-weight: 800;
            color: var(--white);
        }

        /* Camera hover overlay */
        .avatar-overlay {
            position: absolute;
            inset: 3px;
            border-radius: 50%;
            background: rgba(0,0,0,0.5);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            opacity: 0;
            transition: opacity 0.2s ease;
            z-index: 2;
        }

        .avatar-ring:hover .avatar-overlay { opacity: 1; }

        .avatar-overlay svg { width: 22px; height: 22px; fill: #fff; }
        .avatar-overlay span { font-size: 9px; color: #fff; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; }

        /* Upload spinner */
        .upload-spinner {
            display: none;
            position: absolute;
            inset: 3px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            align-items: center;
            justify-content: center;
            z-index: 3;
        }

        .upload-spinner.active { display: flex; }

        .upload-spinner svg {
            width: 34px; height: 34px;
            animation: spin-ring 0.7s linear infinite;
        }

        #profileFileInput { display: none; }

        .profile-name-sidebar {
            font-size: 16px;
            font-weight: 700;
            color: var(--white);
            text-align: center;
            margin-bottom: 4px;
        }

        .profile-email-sidebar {
            font-size: 11px;
            color: rgba(255,255,255,0.5);
            text-align: center;
            margin-bottom: 12px;
        }

        .btn-change-photo {
            background: rgba(245,166,35,0.15);
            border: 1px solid rgba(245,166,35,0.4);
            color: var(--gold);
            border-radius: 20px;
            padding: 6px 16px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            font-family: var(--font-body);
            letter-spacing: 0.5px;
            transition: background 0.2s, transform 0.2s;
        }

        .btn-change-photo:hover {
            background: rgba(245,166,35,0.28);
            transform: translateY(-1px);
        }

        /* Sidebar Nav */
        .sidebar-nav {
            padding: 20px 0;
            flex: 1;
        }

        .nav-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: rgba(255,255,255,0.3);
            padding: 12px 30px 6px;
            text-transform: uppercase;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 30px;
            color: rgba(255,255,255,0.65);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            border-left: 3px solid transparent;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.06);
            color: var(--white);
        }

        .nav-item.active {
            background: rgba(245,166,35,0.1);
            color: var(--gold);
            border-left-color: var(--gold);
            font-weight: 700;
        }

        .nav-item svg { width: 18px; height: 18px; flex-shrink: 0; }

        /* Sidebar footer */
        .sidebar-footer {
            padding: 20px 30px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,0.5);
            font-size: 13px;
            transition: color 0.2s;
        }

        .sidebar-footer a:hover { color: #e53935; }
        .sidebar-footer svg { width: 16px; height: 16px; }

        /* ===================== MAIN CONTENT ===================== */
        .main-content {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Top bar */
        .topbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-title {
            font-family: var(--font-disp);
            font-size: 22px;
            color: var(--navy);
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-actions a {
            font-size: 13px;
            font-weight: 600;
            color: var(--blue);
            border: 1.5px solid var(--blue);
            border-radius: 20px;
            padding: 7px 18px;
            transition: background 0.2s, color 0.2s;
        }

        .topbar-actions a:hover {
            background: var(--blue);
            color: var(--white);
        }

        /* Page body */
        .page-body {
            padding: 36px 40px;
            flex: 1;
        }

        /* Welcome banner */
        .welcome-banner {
            background: linear-gradient(120deg, var(--navy) 0%, #2a5298 60%, #1b8c6e 100%);
            border-radius: 16px;
            padding: 32px 36px;
            color: var(--white);
            position: relative;
            overflow: hidden;
            margin-bottom: 32px;
        }

        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 220px; height: 220px;
            border-radius: 50%;
            background: rgba(245,166,35,0.12);
        }

        .welcome-banner::after {
            content: '';
            position: absolute;
            bottom: -60px; right: 80px;
            width: 160px; height: 160px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }

        .welcome-banner h2 {
            font-family: var(--font-disp);
            font-size: 28px;
            margin-bottom: 6px;
        }

        .welcome-banner p {
            font-size: 14px;
            opacity: 0.75;
        }

        .welcome-banner .member-badge {
            display: inline-block;
            background: rgba(245,166,35,0.2);
            border: 1px solid rgba(245,166,35,0.5);
            color: var(--gold);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            border-radius: 20px;
            padding: 4px 14px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--white);
            border-radius: 14px;
            padding: 24px;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 18px;
            transition: transform 0.2s, box-shadow 0.2s;
            animation: fadeUp 0.5s ease both;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(27,58,90,0.1);
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .stat-card:nth-child(1) { animation-delay: 0.05s; }
        .stat-card:nth-child(2) { animation-delay: 0.10s; }
        .stat-card:nth-child(3) { animation-delay: 0.15s; }
        .stat-card:nth-child(4) { animation-delay: 0.20s; }

        .stat-icon {
            width: 50px; height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon svg { width: 24px; height: 24px; }

        .stat-icon.blue  { background: rgba(40,93,161,0.1);  }
        .stat-icon.blue svg  { fill: var(--blue); }
        .stat-icon.gold  { background: rgba(245,166,35,0.1); }
        .stat-icon.gold svg  { fill: var(--gold); }
        .stat-icon.teal  { background: rgba(27,140,110,0.1); }
        .stat-icon.teal svg  { fill: var(--teal); }
        .stat-icon.navy  { background: rgba(27,58,90,0.1);   }
        .stat-icon.navy svg  { fill: var(--navy); }

        .stat-info h4 {
            font-size: 26px;
            font-weight: 800;
            color: var(--navy);
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-info p {
            font-size: 12px;
            color: var(--muted);
            font-weight: 500;
        }

        /* Section title */
        .section-title {
            font-family: var(--font-disp);
            font-size: 20px;
            color: var(--navy);
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* Account info card */
        .info-card {
            background: var(--white);
            border-radius: 14px;
            border: 1px solid var(--border);
            overflow: hidden;
            margin-bottom: 32px;
            animation: fadeUp 0.4s ease 0.25s both;
        }

        .info-card-header {
            padding: 18px 26px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-card-header h3 {
            font-size: 15px;
            font-weight: 700;
            color: var(--navy);
        }

        .info-card-header a {
            font-size: 12px;
            font-weight: 600;
            color: var(--blue);
        }

        .info-row {
            display: flex;
            align-items: center;
            padding: 16px 26px;
            border-bottom: 1px solid var(--border);
            gap: 16px;
        }

        .info-row:last-child { border-bottom: none; }

        .info-row label {
            width: 140px;
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            flex-shrink: 0;
        }

        .info-row span {
            font-size: 14px;
            font-weight: 500;
            color: var(--navy);
        }

        /* Quick links */
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
            animation: fadeUp 0.4s ease 0.3s both;
        }

        .quick-link-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 22px 20px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
        }

        .quick-link-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(27,58,90,0.1);
            border-color: var(--blue);
        }

        .quick-link-card .ql-icon {
            width: 42px; height: 42px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }

        .quick-link-card .ql-icon svg { width: 20px; height: 20px; }
        .quick-link-card .ql-title { font-size: 13px; font-weight: 700; color: var(--navy); }
        .quick-link-card .ql-sub   { font-size: 11px; color: var(--muted); }

        /* ===================== TOAST ===================== */
        .profile-toast {
            position: fixed;
            bottom: 28px; right: 28px;
            background: var(--navy);
            color: var(--white);
            padding: 14px 22px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 8px 28px rgba(0,0,0,0.2);
            transform: translateY(80px);
            opacity: 0;
            transition: all 0.35s cubic-bezier(0.34,1.56,0.64,1);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 10px;
            pointer-events: none;
        }

        .profile-toast.show   { transform: translateY(0); opacity: 1; }
        .profile-toast.success { border-left: 4px solid var(--teal); }
        .profile-toast.error   { border-left: 4px solid #e53935; }

        /* ===================== RESPONSIVE ===================== */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .page-body { padding: 24px 20px; }
            .topbar { padding: 14px 20px; }
        }
    </style>
</head>
<body>

<div class="dash-wrap">

    <!-- ==================== SIDEBAR ==================== -->
    <aside class="sidebar" id="sidebar">

        <!-- Brand -->
        <div class="sidebar-brand">
            <a href="/Nepal-Travel/Public/index.php">
                <span class="dot"></span> Nepal
            </a>
        </div>

        <!-- Profile Card -->
        <div class="sidebar-profile">

            <div class="avatar-ring" id="avatarRing" onclick="document.getElementById('profileFileInput').click()" title="Change profile photo">

                <div class="avatar-inner" id="avatarInner">
                    <?php if ($profileImageUrl): ?>
                        <img src="<?php echo $profileImageUrl; ?>" id="avatarImg" alt="Profile">
                    <?php else: ?>
                        <div class="avatar-placeholder" id="avatarPlaceholder">
                            <?php echo $initials; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="avatar-overlay">
                    <svg viewBox="0 0 24 24"><path d="M9 3L7.17 5H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-3.17L15 3H9zm3 14a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/></svg>
                    <span>Change</span>
                </div>

                <div class="upload-spinner" id="uploadSpinner">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#f5a623" stroke-width="2.5">
                        <circle cx="12" cy="12" r="9" stroke-dasharray="28 56" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>

            <input type="file" id="profileFileInput" accept="image/jpeg,image/png,image/gif,image/webp">

            <p class="profile-name-sidebar"><?php echo $userName; ?></p>
            <p class="profile-email-sidebar"><?php echo $userEmail; ?></p>

            <button class="btn-change-photo" onclick="document.getElementById('profileFileInput').click()">
                📷 Change Photo
            </button>

        </div>

        <!-- Nav -->
        <nav class="sidebar-nav">
            <p class="nav-label">Menu</p>

            <a href="?tab=overview" class="nav-item <?php echo $activeTab === 'overview' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                Overview
            </a>

            <a href="?tab=bookings" class="nav-item <?php echo $activeTab === 'bookings' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
                My Bookings
            </a>

            <a href="?tab=saved" class="nav-item <?php echo $activeTab === 'saved' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H7c-1.1 0-1.99.9-1.99 2L5 21l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                Saved Places
            </a>

            <a href="?tab=settings" class="nav-item <?php echo $activeTab === 'settings' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
                Settings
            </a>

            <p class="nav-label" style="margin-top:10px;">Explore</p>

            <a href="/Nepal-Travel/pages/experience.php" class="nav-item">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                Experiences
            </a>

            <a href="/Nepal-Travel/pages/deals-and-packages.php" class="nav-item">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>
                Deals & Packages
            </a>
        </nav>

        <!-- Logout -->
        <div class="sidebar-footer">
            <a href="/Nepal-Travel/user/logout.php">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
                Sign Out
            </a>
        </div>

    </aside>

    <!-- ==================== MAIN ==================== -->
    <div class="main-content">

        <!-- Topbar -->
        <div class="topbar">
            <h1 class="topbar-title">
                <?php
                    $titles = [
                        'overview' => 'Dashboard',
                        'bookings' => 'My Bookings',
                        'saved'    => 'Saved Places',
                        'settings' => 'Settings',
                    ];
                    echo $titles[$activeTab] ?? 'Dashboard';
                ?>
            </h1>
            <div class="topbar-actions">
                <a href="/Nepal-Travel/Public/index.php">← Back to Site</a>
            </div>
        </div>

        <!-- Page Body -->
        <div class="page-body">

            <?php if ($activeTab === 'overview'): ?>

                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <span class="member-badge">Member since <?php echo $memberSince; ?></span>
                    <h2>Welcome back, <?php echo $userName; ?>! 🏔️</h2>
                    <p>Ready to explore the Himalayas? Your next adventure awaits.</p>
                </div>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5z"/><path d="M4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
                        </div>
                        <div class="stat-info">
                            <h4>0</h4>
                            <p>Trips Taken</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon gold">
                            <svg viewBox="0 0 24 24"><path d="M17 3H7c-1.1 0-1.99.9-1.99 2L5 21l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                        </div>
                        <div class="stat-info">
                            <h4>0</h4>
                            <p>Saved Places</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon teal">
                            <svg viewBox="0 0 24 24"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
                        </div>
                        <div class="stat-info">
                            <h4>0</h4>
                            <p>Upcoming Bookings</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon navy">
                            <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        </div>
                        <div class="stat-info">
                            <h4>0</h4>
                            <p>Places Explored</p>
                        </div>
                    </div>
                </div>

                <!-- Account Info -->
                <h2 class="section-title">Account Information</h2>
                <div class="info-card">
                    <div class="info-card-header">
                        <h3>Personal Details</h3>
                        <a href="?tab=settings">Edit →</a>
                    </div>
                    <div class="info-row">
                        <label>Full Name</label>
                        <span><?php echo $userName; ?></span>
                    </div>
                    <div class="info-row">
                        <label>Username</label>
                        <span>@<?php echo $userUsername; ?></span>
                    </div>
                    <div class="info-row">
                        <label>Email</label>
                        <span><?php echo $userEmail; ?></span>
                    </div>
                    <div class="info-row">
                        <label>Member Since</label>
                        <span><?php echo $memberSince; ?></span>
                    </div>
                    <div class="info-row">
                        <label>Profile Photo</label>
                        <span><?php echo $profileImageUrl ? '✅ Uploaded' : '⚠️ Not uploaded — click your avatar to add one'; ?></span>
                    </div>
                </div>

                <!-- Quick Links -->
                <h2 class="section-title">Quick Actions</h2>
                <div class="quick-links">
                    <a href="/Nepal-Travel/pages/experience.php" class="quick-link-card">
                        <div class="ql-icon" style="background:rgba(40,93,161,0.1);">
                            <svg viewBox="0 0 24 24" fill="#285da1"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        </div>
                        <span class="ql-title">Explore Nepal</span>
                        <span class="ql-sub">Discover destinations</span>
                    </a>
                    <a href="/Nepal-Travel/pages/deals-and-packages.php" class="quick-link-card">
                        <div class="ql-icon" style="background:rgba(245,166,35,0.1);">
                            <svg viewBox="0 0 24 24" fill="#f5a623"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>
                        </div>
                        <span class="ql-title">View Deals</span>
                        <span class="ql-sub">Packages & offers</span>
                    </a>
                    <a href="/Nepal-Travel/pages/events.php" class="quick-link-card">
                        <div class="ql-icon" style="background:rgba(27,140,110,0.1);">
                            <svg viewBox="0 0 24 24" fill="#1b8c6e"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
                        </div>
                        <span class="ql-title">Events</span>
                        <span class="ql-sub">Festivals & culture</span>
                    </a>
                    <a href="/Nepal-Travel/pages/saved.php" class="quick-link-card">
                        <div class="ql-icon" style="background:rgba(27,58,90,0.1);">
                            <svg viewBox="0 0 24 24" fill="#1b3a5a"><path d="M17 3H7c-1.1 0-1.99.9-1.99 2L5 21l7-3 7 3V5c0-1.1-.9-2-2-2z"/></svg>
                        </div>
                        <span class="ql-title">Saved Places</span>
                        <span class="ql-sub">Your wishlist</span>
                    </a>
                </div>

            <?php elseif ($activeTab === 'bookings'): ?>
                <div class="info-card" style="padding:40px; text-align:center; color:var(--muted);">
                    <p style="font-size:40px; margin-bottom:12px;">🗓️</p>
                    <p style="font-size:16px; font-weight:600;">No bookings yet</p>
                    <p style="font-size:13px; margin-top:6px;">Start exploring Nepal and make your first booking!</p>
                </div>

            <?php elseif ($activeTab === 'saved'): ?>
                <div class="info-card" style="padding:40px; text-align:center; color:var(--muted);">
                    <p style="font-size:40px; margin-bottom:12px;">🔖</p>
                    <p style="font-size:16px; font-weight:600;">No saved places yet</p>
                    <p style="font-size:13px; margin-top:6px;">Browse experiences and save your favourites.</p>
                </div>

            <?php elseif ($activeTab === 'settings'): ?>
                <div class="info-card">
                    <div class="info-card-header"><h3>Account Settings</h3></div>
                    <div style="padding:28px 26px; color:var(--muted); font-size:14px;">
                        Settings panel coming soon. For now, update your profile photo by clicking your avatar in the sidebar.
                    </div>
                </div>
            <?php endif; ?>

        </div><!-- /page-body -->
    </div><!-- /main-content -->

</div><!-- /dash-wrap -->

<!-- Toast -->
<div class="profile-toast" id="profileToast"></div>

<!-- ==================== PROFILE UPLOAD JS ==================== -->
<script>
(function () {
    const fileInput     = document.getElementById('profileFileInput');
    const avatarInner   = document.getElementById('avatarInner');
    const uploadSpinner = document.getElementById('uploadSpinner');
    const toast         = document.getElementById('profileToast');

    fileInput.addEventListener('change', function () {
        if (!this.files || !this.files[0]) return;

        const file = this.files[0];

        if (file.size > 5 * 1024 * 1024) {
            showToast('File too large — max 5 MB.', 'error');
            return;
        }

        // Instant preview
        const reader = new FileReader();
        reader.onload = function (e) {
            let img = avatarInner.querySelector('img');
            if (!img) {
                avatarInner.innerHTML = '';
                img = document.createElement('img');
                img.id  = 'avatarImg';
                img.alt = 'Profile';
                avatarInner.appendChild(img);
            }
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);

        // Upload
        uploadSpinner.classList.add('active');

        const fd = new FormData();
        fd.append('profile_image', file);

        fetch('/Nepal-Travel/user/upload_profile.php', {
            method: 'POST',
            body: fd
        })
        .then(r => {
            // Get raw text first so we can debug non-JSON responses
            return r.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch(e) {
                    // Server returned non-JSON (PHP error/warning)
                    throw new Error('Server response: ' + text.substring(0, 200));
                }
            });
        })
        .then(data => {
            uploadSpinner.classList.remove('active');
            if (data.success) {
                showToast('✓ Profile photo updated!', 'success');

                // ── Update sidebar avatar live ──────────────────
                let img = avatarInner.querySelector('img');
                if (!img) {
                    // Remove placeholder letter div and create real img
                    avatarInner.innerHTML = '';
                    img = document.createElement('img');
                    img.id  = 'avatarImg';
                    img.alt = 'Profile';
                    img.style.width        = '100%';
                    img.style.height       = '100%';
                    img.style.objectFit    = 'cover';
                    img.style.borderRadius = '50%';
                    avatarInner.appendChild(img);
                }
                img.src = data.image_url;

                // ── Update header avatar live ───────────────────
                const ha = document.getElementById('headerProfileAvatar');
                if (ha) {
                    if (ha.tagName === 'IMG') {
                        ha.src = data.image_url;
                    } else {
                        // Header shows a letter span — swap it for an img
                        const newImg = document.createElement('img');
                        newImg.id        = 'headerProfileAvatar';
                        newImg.src       = data.image_url;
                        newImg.className = ha.className.replace('header-avatar-initial', 'header-avatar-img');
                        newImg.alt       = 'Profile';
                        ha.replaceWith(newImg);
                    }
                }

            } else {
                showToast('✗ ' + (data.message || 'Upload failed.'), 'error');
                console.error('Upload error:', data.message);
            }
        })
        .catch(err => {
            uploadSpinner.classList.remove('active');
            showToast('✗ ' + (err.message || 'Upload failed.'), 'error');
            console.error('Upload exception:', err);
        });

        this.value = '';
    });

    function showToast(msg, type) {
        toast.textContent = msg;
        toast.className   = 'profile-toast ' + type + ' show';
        setTimeout(() => toast.classList.remove('show'), 3500);
    }
})();
</script>

</body>
</html>