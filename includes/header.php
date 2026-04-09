<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepal Truly Authentic - Home</title>
    <meta name="description"
        content="Discover the beauty of Nepal. From the majestic Himalayas to the vibrant culture and exotic wildlife, explore Nepal your way.">
    <!-- Google Fonts for Montserrat and Great Vibes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400..900;1,400..900&family=Great+Vibes&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>

    <!-- Two-Tier Header Navigation -->
    <?php 
        $current_page = basename($_SERVER['PHP_SELF']); 
        $is_home = ($current_page == 'index.php' || $current_page == '');
        $header_style = $is_home ? 'position: absolute; top: 0; left: 0; width: 100%; z-index: 1000; background: transparent;' : 'position: relative; z-index: 1000; background: #fff; border-bottom: 1px solid #eee;';
        $nav_bg = $is_home ? 'transparent' : '#fff';

        // Colors for dynamic contrast
        $text_main = $is_home ? '#ffffff' : '#1b3a5a';
        $text_shadow = $is_home ? '0 4px 6px rgba(0,0,0,0.6)' : 'none';
        $script_color = $is_home ? '#f5a623' : '#285da1';
        
        // ---- Profile image resolution ----
        $profileImageUrl = null;
        if (!empty($_SESSION['profile_image']) && $_SESSION['profile_image'] !== 'default.png') {
            $absPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/Nepal-Travel/' . ltrim($_SESSION['profile_image'], '/');
            if (file_exists($absPath)) {
                $profileImageUrl = '/Nepal-Travel/' . ltrim($_SESSION['profile_image'], '/') . '?t=' . time();
            }
        }
        $initials = strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1));
    ?>
    <header class="site-header <?php echo $is_home ? 'transparent-header' : ''; ?>" style="<?php echo $header_style; ?>">
        <!-- Top Utility Bar -->
        <div class="top-utility-bar">
            <div class="container">
                <div class="utility-left">
                    <span>Foreign Visitors Requirement: <a href="#">Nepal Digital Arrival Card (NDAC)</a></span>
                </div>
                <div class="utility-right">
                    <a href="saved.php" class="utility-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f5a623" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.7 0l-1.1 1-1.1-1a5.5 5.5 0 0 0-7.8 7.8l1 1 7.9 8 7.8-7.9 1-1a5.5 5.5 0 0 0 0-7.8z"></path></svg>
                        SAVED
                    </a>
                    <a href="#" class="utility-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f5a623" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        SEARCH
                    </a>
                    
                    <!-- USER SECTION START -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/Nepal-Travel/user/dashboard.php" class="user-profile-link">
                            <?php if ($profileImageUrl): ?>
                                <img src="<?php echo $profileImageUrl; ?>" class="user-avatar-img" alt="Profile">
                            <?php else: ?>
                                <span class="user-avatar-initial"><?php echo $initials; ?></span>
                            <?php endif; ?>
                            <span class="user-greeting">Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        </a>
                        <a href="/Nepal-Travel/user/logout.php" class="utility-link">Logout</a>
                    <?php else: ?>
                        <a href="/Nepal-Travel/user/login.php" class="utility-link">Login</a>
                        <a href="/Nepal-Travel/user/Register.php" class="utility-link">Signup</a>
                    <?php endif; ?>
                    <!-- USER SECTION END -->
                </div>
            </div>
        </div>

        <div class="main-nav-bar" style="background: <?php echo $nav_bg; ?>;">
            <div class="container">
                <div class="logo-wrapper">
                    <a href="index.php" class="logo-link">
                        <div class="visit-badge">Visit</div>
                        <div class="logo-main">
                            <span class="logo-nepal" style="color: <?php echo $text_main; ?>; text-shadow: <?php echo $text_shadow; ?>;">Nepal</span>
                            <span class="logo-year">2026</span>
                        </div>
                        <span class="logo-tagline" style="color: <?php echo $script_color; ?>; text-shadow: <?php echo $text_shadow; ?>;">Truly Authentic</span>
                    </a>
                </div>

                <nav class="nav-links">
                    <a href="about-nepal.php" class="<?php echo $current_page == 'about-nepal.php' ? 'active' : ''; ?>" style="color: <?php echo $text_main; ?>; text-shadow: <?php echo $text_shadow; ?>;">ABOUT NEPAL</a>
                    <a href="experience.php" class="<?php echo $current_page == 'experience.php' ? 'active' : ''; ?>" style="color: <?php echo $text_main; ?>; text-shadow: <?php echo $text_shadow; ?>;">EXPERIENCE</a>
                    <a href="travel-ideas.php" class="<?php echo $current_page == 'travel-ideas.php' ? 'active' : ''; ?>" style="color: <?php echo $text_main; ?>; text-shadow: <?php echo $text_shadow; ?>;">TRAVEL IDEAS</a>
                    <a href="deals-and-packages.php" class="<?php echo $current_page == 'deals-and-packages.php' ? 'active' : ''; ?>" style="color: <?php echo $text_main; ?>; text-shadow: <?php echo $text_shadow; ?>;">DEALS & PACKAGES</a>
                    <a href="events.php" class="<?php echo $current_page == 'events.php' ? 'active' : ''; ?>" style="color: <?php echo $text_main; ?>; text-shadow: <?php echo $text_shadow; ?>;">EVENTS & HAPPENINGS</a>
                </nav>
            </div>
        </div>
    </header>
<!-- Done by Sanskar -->