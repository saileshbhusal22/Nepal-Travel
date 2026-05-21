<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth_redirect.php';
$auth_login_url    = auth_build_login_url();
$auth_register_url = auth_build_register_url();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepal Truly Authentic - Home</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400..900;1,400..900&family=Great+Vibes&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/chatbot.css">
    

    <style>
        /* ============================================================
           RESET & BASE
        ============================================================ */
        *, *::before, *::after { box-sizing: border-box; }

        /* ============================================================
           HEADER AVATAR
        ============================================================ */
        .header-avatar-wrap {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .header-avatar-img,
        .header-avatar-initial {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 2px solid #f5a623;
            vertical-align: middle;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            flex-shrink: 0;
        }
        .header-avatar-img { object-fit: cover; }
        .header-avatar-initial {
            background: linear-gradient(135deg, #1b3a5a, #285da1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            font-family: 'Montserrat', sans-serif;
        }
        .header-avatar-wrap:hover .header-avatar-img,
        .header-avatar-wrap:hover .header-avatar-initial {
            transform: scale(1.1);
            box-shadow: 0 0 0 3px rgba(245,166,35,0.45);
        }
        .header-user-greeting {
            font-weight: 600;
            color: #f5a623;
            font-size: 12px;
            font-family: 'Montserrat', sans-serif;
        }

        /* ============================================================
           UTILITY BAR
        ============================================================ */
        .top-utility-bar {
            background: rgba(43,76,140,0.95);
            color: #fff;
            padding: 10px 0;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .utility-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
        }
        .utility-right {
            display: flex;
            gap: 25px;
            align-items: center;
        }
        .utility-right a,
        .utility-right button {
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .utility-right button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        /* ============================================================
           MAIN NAV BAR
        ============================================================ */
        .main-nav-bar {
            padding: 25px 0;
        }
        .nav-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 85%;
            margin: 0 auto;
            padding: 0 40px;
            position: relative;
        }

        /* Logo */
        .logo-wrapper a {
            display: flex;
            flex-direction: column;
            text-decoration: none;
            line-height: 1;
            transition: transform 0.3s ease;
        }
        .logo-visit {
            margin-left: 45px;
            margin-bottom: -8px;
            z-index: 1;
        }
        .logo-visit span {
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            color: #e31c25;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .logo-main {
            display: flex;
            align-items: baseline;
            position: relative;
        }
        .logo-nepal {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            font-weight: 900;
            letter-spacing: -1.5px;
        }
        .logo-year {
            font-family: 'Montserrat', sans-serif;
            font-size: 18px;
            color: #e31c25;
            font-weight: 900;
            margin-left: 2px;
        }
        .logo-tagline {
            margin-top: -18px;
            margin-left: 18px;
            z-index: 2;
        }
        .logo-tagline span {
            font-family: 'Great Vibes', cursive;
            color: #f5a623;
            font-size: 32px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        /* Desktop Nav Links */
        .nav-links {
            display: flex;
            gap: 40px;
            align-items: center;
            margin-left: auto;
        }
        .nav-links a {
            font-family: 'Montserrat', sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-decoration: none;
            position: relative;
            transition: color 0.2s;
        }
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: #f5a623;
            transition: width 0.25s ease;
        }
        .nav-links a:hover::after,
        .nav-links a.active::after { width: 100%; }

        /* ============================================================
           HAMBURGER BUTTON
        ============================================================ */
        .hamburger-btn {
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            border-radius: 8px;
            transition: background 0.2s;
            flex-shrink: 0;
            z-index: 1100;
        }
        .hamburger-btn:hover { background: rgba(255,255,255,0.12); }
        .hamburger-btn .bar {
            display: block;
            width: 22px;
            height: 2px;
            background: currentColor;
            border-radius: 2px;
            transition: transform 0.3s ease, opacity 0.3s ease, width 0.3s ease;
            transform-origin: center;
        }
        .hamburger-btn .bar + .bar { margin-top: 5px; }

        /* X state */
        .hamburger-btn.is-open .bar:nth-child(1) {
            transform: translateY(7px) rotate(45deg);
        }
        .hamburger-btn.is-open .bar:nth-child(2) {
            opacity: 0;
            width: 0;
        }
        .hamburger-btn.is-open .bar:nth-child(3) {
            transform: translateY(-7px) rotate(-45deg);
        }

        /* ============================================================
           MOBILE DRAWER - ENHANCED
        ============================================================ */
        .mobile-drawer {
            display: none;
            position: fixed;
            top: 0;
            right: -100%;
            width: min(320px, 88vw);
            height: 100vh;
            background: linear-gradient(180deg, #0d1f35 0%, #1a2f4a 100%);
            z-index: 2000;
            flex-direction: column;
            overflow-y: auto;
            transition: right 0.35s cubic-bezier(0.4,0,0.2,1);
            box-shadow: -8px 0 40px rgba(0,0,0,0.45);
        }
        .mobile-drawer.is-open { 
            right: 0;
            overflow-y: auto;
        }

        .drawer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 22px 18px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            flex-shrink: 0;
        }
        .drawer-logo {
            display: flex;
            flex-direction: column;
            line-height: 1;
            text-decoration: none;
        }
        .drawer-logo .d-visit {
            font-family: 'Montserrat', sans-serif;
            font-size: 10px;
            font-weight: 900;
            color: #e31c25;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-left: 28px;
            margin-bottom: -4px;
        }
        .drawer-logo .d-nepal {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 900;
            color: #fff;
            letter-spacing: -1px;
        }
        .drawer-logo .d-year {
            font-family: 'Montserrat', sans-serif;
            font-size: 12px;
            font-weight: 900;
            color: #e31c25;
            margin-left: 2px;
        }
        .drawer-logo .d-tagline {
            font-family: 'Great Vibes', cursive;
            color: #f5a623;
            font-size: 20px;
            margin-top: -10px;
            margin-left: 10px;
        }

        .drawer-close-btn {
            background: rgba(255,255,255,0.07);
            border: none;
            color: #fff;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
            flex-shrink: 0;
        }
        .drawer-close-btn:hover { background: rgba(255,255,255,0.16); }

        /* User card inside drawer */
        .drawer-user-card {
            margin: 14px 18px;
            background: rgba(245,166,35,0.08);
            border: 1px solid rgba(245,166,35,0.2);
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .drawer-user-card:hover {
            background: rgba(245,166,35,0.12);
            border-color: rgba(245,166,35,0.35);
        }
        .drawer-user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 2px solid #f5a623;
            object-fit: cover;
            flex-shrink: 0;
        }
        .drawer-user-avatar-initial {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 2px solid #f5a623;
            background: linear-gradient(135deg, #1b3a5a, #285da1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            flex-shrink: 0;
        }
        .drawer-user-info { 
            flex: 1; 
            min-width: 0; 
        }
        .drawer-user-name {
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .drawer-user-sub {
            font-family: 'Montserrat', sans-serif;
            font-size: 11px;
            color: rgba(255,255,255,0.45);
            margin-top: 2px;
        }

        /* Main Drawer Navigation */
        .drawer-nav {
            padding: 10px 0;
            flex: 1;
            overflow-y: auto;
        }
        .drawer-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 22px;
            font-family: 'Montserrat', sans-serif;
            font-size: 13px;
            font-weight: 700;
            color: rgba(255,255,255,0.80);
            text-decoration: none;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
            position: relative;
        }
        .drawer-nav a:hover,
        .drawer-nav a.active {
            color: #f5a623;
            background: rgba(245,166,35,0.12);
            border-left-color: #f5a623;
            padding-left: 24px;
        }
        .drawer-nav-icon {
            font-size: 18px;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .drawer-divider {
            margin: 8px 22px;
            border: none;
            border-top: 1px solid rgba(255,255,255,0.07);
        }

        /* Drawer utility links */
        .drawer-utility {
            padding: 10px 0 6px;
        }
        .drawer-utility a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 22px;
            font-family: 'Montserrat', sans-serif;
            font-size: 12px;
            font-weight: 600;
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .drawer-utility a:hover { 
            color: #f5a623;
            background: rgba(245,166,35,0.08);
            padding-left: 26px;
        }
        .drawer-utility-icon {
            font-size: 16px;
            width: 18px;
            flex-shrink: 0;
        }

        /* Drawer footer */
        .drawer-footer {
            padding: 14px 22px 24px;
            border-top: 1px solid rgba(255,255,255,0.07);
            flex-shrink: 0;
        }
        .drawer-footer-auth {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .drawer-btn-login {
            padding: 11px;
            background: transparent;
            border: 1.5px solid rgba(245,166,35,0.4);
            border-radius: 8px;
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            display: block;
        }
        .drawer-btn-login:hover { 
            border-color: #f5a623;
            color: #f5a623;
            background: rgba(245,166,35,0.08);
        }
        .drawer-btn-signup {
            padding: 11px;
            background: #f5a623;
            border: none;
            border-radius: 8px;
            color: #0f172a;
            font-family: 'Montserrat', sans-serif;
            font-size: 13px;
            font-weight: 800;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            display: block;
        }
        .drawer-btn-signup:hover { 
            background: #fbbf24;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245,166,35,0.3);
        }
        .drawer-btn-logout {
            display: block;
            width: 100%;
            padding: 11px;
            background: rgba(239,68,68,0.12);
            border: 1px solid rgba(239,68,68,0.25);
            border-radius: 8px;
            color: #fca5a5;
            font-family: 'Montserrat', sans-serif;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .drawer-btn-logout:hover { 
            background: rgba(239,68,68,0.22);
            border-color: rgba(239,68,68,0.4);
        }

        /* Overlay backdrop */
        .drawer-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 1999;
            backdrop-filter: blur(2px);
            opacity: 0;
            transition: opacity 0.35s ease;
        }
        .drawer-overlay.is-open {
            display: block;
            opacity: 1;
        }

        /* ============================================================
           RESPONSIVE BREAKPOINTS
        ============================================================ */

        /* Tablet: collapse nav links, show hamburger */
        @media (max-width: 1024px) {
            .nav-inner {
                max-width: 100%;
                padding: 0 24px;
            }
            .nav-links { gap: 24px; }
            .nav-links a { font-size: 12px; }
        }

        @media (max-width: 860px) {
            /* Hide desktop nav & utility bar text */
            .nav-links { display: none; }
            .utility-left { display: none; }

            /* Show hamburger */
            .hamburger-btn { display: flex; }

            /* Show mobile drawer */
            .mobile-drawer { display: flex; }
            .drawer-overlay { display: none; }

            /* Utility bar compact */
            .utility-inner {
                padding: 0 16px;
                justify-content: flex-end;
            }
            .utility-right { gap: 16px; }

            /* Compact nav bar */
            .main-nav-bar { padding: 16px 0; }
            .nav-inner { padding: 0 16px; }

            /* Slightly shrink logo on tablet */
            .logo-nepal { font-size: 36px; }
            .logo-year  { font-size: 14px; }
            .logo-tagline span { font-size: 24px; }
            .logo-visit span   { font-size: 11px; }
            .logo-visit { margin-left: 32px; }
            .logo-tagline { margin-top: -14px; margin-left: 12px; }
        }

        @media (max-width: 480px) {
            /* Utility bar: show only search & user on mobile */
            .utility-right { gap: 12px; }
            .utility-right .util-saved-link { display: none; }

            /* Logo even smaller */
            .logo-nepal { font-size: 28px; }
            .logo-year  { font-size: 12px; }
            .logo-tagline span { font-size: 20px; }
            .logo-visit span   { font-size: 10px; }
            .logo-visit { margin-left: 24px; }
            .logo-tagline { margin-top: -11px; margin-left: 8px; }

            /* Mobile drawer adjustments */
            .mobile-drawer {
                width: min(280px, 100vw);
            }
            .drawer-nav a,
            .drawer-utility a {
                font-size: 12px;
            }
        }

        @media (max-width: 360px) {
            .logo-nepal { font-size: 24px; }
            .logo-tagline span { font-size: 17px; }

            .mobile-drawer {
                width: 100vw;
            }
        }
    </style>
</head>

<body>

<!-- Sherpa AI user session meta (read by chatbot.js) -->
<span id="sherpa-user-meta" style="display:none"
  data-logged-in="<?php echo isset($_SESSION['user_id']) ? '1' : '0'; ?>"
  data-user-id="<?php echo isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : ''; ?>"
  data-user-name="<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : ''; ?>"
  data-login-url="<?php echo htmlspecialchars($auth_login_url); ?>"
  data-register-url="<?php echo htmlspecialchars($auth_register_url); ?>"
></span>

<?php
    $current_page = basename($_SERVER['PHP_SELF']);
    $is_home      = ($current_page == 'index.php' || $current_page == '');

    $header_style = $is_home
        ? 'position: absolute; top: 0; left: 0; width: 100%; z-index: 1000; background: transparent;'
        : 'position: relative; z-index: 1000; background: #fff; border-bottom: 1px solid #eee;';

    $nav_bg       = $is_home ? 'transparent' : '#fff';
    $text_main    = $is_home ? '#ffffff'     : '#1b3a5a';
    $text_shadow  = $is_home ? '0 4px 6px rgba(0,0,0,0.6)' : 'none';
    $script_color = $is_home ? '#f5a623'     : '#285da1';
    $nav_link_color = $is_home ? '#ffffff'   : '#1b3a5a';
    $hamburger_color = $is_home ? '#ffffff'  : '#1b3a5a';

    // Profile Image Logic
    $profileImageUrl = null;
    $initials = 'U';

    if (isset($_SESSION['user_id'])) {
        if (!empty($_SESSION['profile_image']) && $_SESSION['profile_image'] !== 'default.png') {
            $absPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/Nepal-Travel/' . ltrim($_SESSION['profile_image'], '/');
            if (file_exists($absPath)) {
                $profileImageUrl = '/Nepal-Travel/' . ltrim($_SESSION['profile_image'], '/') . '?t=' . time();
            }
        }
        $initials = strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1));
    }

    $headerSavedCount = 0;
    if (isset($_SESSION['user_id']) || !empty($_SESSION['saved_deals'])) {
        require_once __DIR__ . '/../config/db.php';
        require_once __DIR__ . '/saved_helpers.php';
        $headerSavedCount = getTotalSavedCount($conn);
    }

    $navLinks = [
        'about.php'            => 'About Nepal',
        'experience.php'       => 'Experience',
        'travel-ideas.php'     => 'Travel Ideas',
        'deals-and-packages.php' => 'Deals & Packages',
        'events.php'           => 'Events & Happenings',
    ];
    $navIcons = [
        'about.php'            => '🏔️',
        'experience.php'       => '🎭',
        'travel-ideas.php'     => '💡',
        'deals-and-packages.php' => '🎫',
        'events.php'           => '📅',
    ];
?>

<!-- ── Drawer Overlay ─────────────────────────────────────────── -->
<div class="drawer-overlay" id="drawerOverlay"></div>

<!-- ── Mobile Drawer - ENHANCED ─────────────────────────────────── -->
<div class="mobile-drawer" id="mobileDrawer" role="dialog" aria-modal="true" aria-label="Navigation menu">

    <div class="drawer-header">
        <a href="/Nepal-Travel/Public/index.php" class="drawer-logo" onclick="closeDrawer();">
            <span class="d-visit">Visit</span>
            <div style="display:flex;align-items:baseline;line-height:1;">
                <span class="d-nepal">Nepal</span>
                <span class="d-year">2026</span>
            </div>
            <span class="d-tagline">Truly Authentic</span>
        </a>
        <button class="drawer-close-btn" onclick="closeDrawer()" aria-label="Close menu">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    <!-- User Card (if logged in) -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="/Nepal-Travel/user/dashboard.php" class="drawer-user-card" onclick="closeDrawer();">
            <?php if ($profileImageUrl): ?>
                <img src="<?= htmlspecialchars($profileImageUrl) ?>" alt="Profile" class="drawer-user-avatar">
            <?php else: ?>
                <div class="drawer-user-avatar-initial"><?= htmlspecialchars($initials) ?></div>
            <?php endif; ?>
            <div class="drawer-user-info">
                <div class="drawer-user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></div>
                <div class="drawer-user-sub">View Dashboard →</div>
            </div>
        </a>
    <?php endif; ?>

    <!-- Main Navigation Links -->
    <nav class="drawer-nav">
        <?php foreach ($navLinks as $page => $label): ?>
            <a href="/Nepal-Travel/Public/<?= $page ?>"
               class="<?= $current_page === $page ? 'active' : '' ?>"
               onclick="closeDrawer();">
                <span class="drawer-nav-icon"><?= htmlspecialchars($navIcons[$page]) ?></span>
                <span><?= htmlspecialchars($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <hr class="drawer-divider">

    <!-- Utility Links -->
    <div class="drawer-utility">
        <a href="/Nepal-Travel/Public/saved.php" onclick="closeDrawer();">
            <span class="drawer-utility-icon">❤️</span>
            <span>Saved<?php if ($headerSavedCount > 0): ?> (<?= (int)$headerSavedCount ?>)<?php endif; ?></span>
        </a>
        <a href="#" id="mobileSearchBtn" onclick="openSearch(); closeDrawer();">
            <span class="drawer-utility-icon">🔍</span>
            <span>Search</span>
        </a>
    </div>

    <hr class="drawer-divider">

    <!-- Footer Authentication -->
    <div class="drawer-footer">
        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Logged In User - Show Logout Button -->
            <a href="/Nepal-Travel/user/logout.php" class="drawer-btn-logout">
                ← Sign Out
            </a>
        <?php else: ?>
            <!-- Not Logged In - Show Login & Signup -->
            <div class="drawer-footer-auth">
                <a href="<?= htmlspecialchars($auth_login_url) ?>" class="drawer-btn-login">
                    Login
                </a>
                <a href="<?= htmlspecialchars($auth_register_url) ?>" class="drawer-btn-signup">
                    Sign Up
                </a>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- ── Site Header ────────────────────────────────────────────── -->
<header class="site-header" style="<?= $header_style ?>">

    <!-- Top Utility Bar -->
    <div class="top-utility-bar">
        <div class="utility-inner">
            <div class="utility-left">
                <span>Foreign Visitors: 
                    <a href="https://immigration.gov.np/en/page/arrival-departure-information-1" style="color:#fff;text-decoration:underline;">Nepal Digital Arrival Card (NDAC)</a>
                </span>
            </div>
            <div class="utility-right">
                <a href="/Nepal-Travel/Public/saved.php" class="util-saved-link" style="display:inline-flex; align-items:center; gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.8 4.6a5.5 5.5 0 0 0-7.7 0l-1.1 1-1.1-1a5.5 5.5 0 0 0-7.8 7.8l1 1 7.9 8 7.8-7.9 1-1a5.5 5.5 0 0 0 0-7.8z"/>
                    </svg>
                    SAVED<?php if ($headerSavedCount > 0): ?><span style="background:#f5a623;color:#111;font-size:10px;font-weight:800;padding:2px 7px;border-radius:10px;min-width:18px;text-align:center;"><?= (int)$headerSavedCount ?></span><?php endif; ?>
                </a>
                <button id="openSearchBtn" style="cursor: pointer;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    SEARCH
                </button>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/Nepal-Travel/user/dashboard.php" style="color:#fff;text-decoration:none;display:flex;align-items:center;gap:8px;">
                        <?php if ($profileImageUrl): ?>
                            <img src="<?= htmlspecialchars($profileImageUrl) ?>"
                                 alt="Profile"
                                 style="width:28px;height:28px;border-radius:50%;border:2px solid #f5a623;object-fit:cover;">
                        <?php else: ?>
                            <span style="width:28px;height:28px;border-radius:50%;border:2px solid #f5a623;background:linear-gradient(135deg,#1b3a5a,#285da1);display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;">
                                <?= htmlspecialchars($initials) ?>
                            </span>
                        <?php endif; ?>
                        <span>Hi, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></span>
                    </a>
                    <a href="/Nepal-Travel/user/logout.php" style="color:#fff;text-decoration:none;">Logout</a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($auth_login_url) ?>" style="color:#fff;text-decoration:none;font-weight:600;">Login</a>
                    <a href="<?= htmlspecialchars($auth_register_url) ?>" style="background:#fbbf24;color:#0f172a;padding:6px 16px;border-radius:20px;text-decoration:none;font-weight:800;font-family:'Montserrat',sans-serif;box-shadow:0 4px 10px rgba(251,191,36,0.3);transition:all 0.3s ease;">Signup</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <div class="main-nav-bar" style="background:<?= $nav_bg ?>;">
        <div class="nav-inner">

            <!-- Logo -->
            <div class="logo-wrapper">
                <a href="/Nepal-Travel/Public/index.php">
                    <div class="logo-visit">
                        <span style="text-shadow:<?= $text_shadow ?>;">Visit</span>
                    </div>
                    <div class="logo-main">
                        <span class="logo-nepal" style="color:<?= $text_main ?>;text-shadow:<?= $text_shadow ?>;">Nepal</span>
                        <span class="logo-year"  style="text-shadow:<?= $text_shadow ?>;">2026</span>
                    </div>
                    <div class="logo-tagline">
                        <span>Truly Authentic</span>
                    </div>
                </a>
            </div>

            <!-- Desktop Nav Links -->
            <nav class="nav-links" style="color:<?= $nav_link_color ?>;">
                <?php foreach ($navLinks as $page => $label): ?>
                    <a href="/Nepal-Travel/Public/<?= $page ?>"
                       class="<?= $current_page === $page ? 'active' : '' ?>"
                       style="color:<?= $nav_link_color ?>;">
                        <?= htmlspecialchars($label) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Hamburger Button (mobile only) -->
            <button class="hamburger-btn"
                    id="hamburgerBtn"
                    aria-label="Open navigation menu"
                    aria-expanded="false"
                    style="color:<?= $hamburger_color ?>;"
                    onclick="openDrawer()">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>

        </div>
    </div>
</header>

<!-- Search Portal (if exists in your styles.css) -->
<div id="searchPortal" class="search-portal">
    <div class="search-portal-content">
        <div class="search-header">
            <div class="search-input-wrapper">
                <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="globalSearchInput" class="search-input" placeholder="Search experiences, ideas, deals...">
                <span id="searchPulse" class="search-pulse"></span>
                <button id="clearSearchInput" class="clear-search-btn" title="Clear search" style="background: none; border: none; cursor: pointer;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <button id="closeSearch" class="close-search-btn" title="Close search" style="background: none; border: none; cursor: pointer;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="search-tabs">
            <button class="search-tab-btn active" data-tab="all">All (<span id="count-all">0</span>)</button>
            <button class="search-tab-btn" data-tab="experiences">Stories (<span id="count-experiences">0</span>)</button>
            <button class="search-tab-btn" data-tab="ideas">Ideas (<span id="count-ideas">0</span>)</button>
            <button class="search-tab-btn" data-tab="deals">Deals (<span id="count-deals">0</span>)</button>
        </div>
        <div id="suggestionsPanel" class="suggestions-panel">
            <div class="suggestions-section">
                <h3>Suggested for you</h3>
                <div class="suggestion-chips">
                    <span class="suggestion-chip">Everest Trek</span>
                    <span class="suggestion-chip">Pokhara</span>
                    <span class="suggestion-chip">Kathmandu</span>
                    <span class="suggestion-chip">Budget Travel</span>
                </div>
            </div>
            <div id="recentSearchesBlock" class="suggestions-section" style="display:none;">
                <h3>Recent searches</h3>
                <div id="recentSearchesChips" class="suggestion-chips"></div>
            </div>
        </div>
        <div id="searchResultsGrid" class="search-results-grid"></div>
    </div>
</div>

<script src="../assets/js/global-search.js"></script>

<script>
/* ═══════════════════════════════════════════════════════════════
   DRAWER & NAVIGATION CONTROLS
═══════════════════════════════════════════════════════════════ */

const drawer       = document.getElementById('mobileDrawer');
const overlay      = document.getElementById('drawerOverlay');
const hamburgerBtn = document.getElementById('hamburgerBtn');

/**
 * Open the mobile drawer menu
 */
function openDrawer() {
    drawer.classList.add('is-open');
    overlay.classList.add('is-open');
    hamburgerBtn.classList.add('is-open');
    hamburgerBtn.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
}

/**
 * Close the mobile drawer menu
 */
function closeDrawer() {
    drawer.classList.remove('is-open');
    overlay.classList.remove('is-open');
    hamburgerBtn.classList.remove('is-open');
    hamburgerBtn.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
}

/**
 * Open the search portal
 */
function openSearch() {
    const portal = document.getElementById('searchPortal');
    if (portal) {
        portal.classList.add('active');
        const input = document.getElementById('globalSearchInput');
        if (input) {
            setTimeout(() => input.focus(), 100);
        }
    }
}

/* Event Listeners */
if (hamburgerBtn) {
    hamburgerBtn.addEventListener('click', openDrawer);
}

if (overlay) {
    overlay.addEventListener('click', closeDrawer);
}

/* Close drawer on Escape key */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDrawer();
    }
});

/* Open search button (desktop utility bar) */
const openSearchBtn = document.getElementById('openSearchBtn');
if (openSearchBtn) {
    openSearchBtn.addEventListener('click', openSearch);
}

/* Close search portal button */
const closeSearchBtn = document.getElementById('closeSearch');
if (closeSearchBtn) {
    closeSearchBtn.addEventListener('click', function() {
        const portal = document.getElementById('searchPortal');
        if (portal) {
            portal.classList.remove('active');
        }
    });
}

/* Clear search input button */
const clearSearchInput = document.getElementById('clearSearchInput');
if (clearSearchInput) {
    clearSearchInput.addEventListener('click', function() {
        const searchInput = document.getElementById('globalSearchInput');
        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
        }
    });
}

console.log('Header script loaded successfully');
</script>

