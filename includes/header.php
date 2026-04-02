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
    <link rel="stylesheet" href="assets/css/styles.css">
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
    ?>
    <header class="site-header" style="<?php echo $header_style; ?>">
        <!-- Top Utility Bar matching reference -->
        <div class="top-utility-bar" style="background: rgba(43, 76, 140, 0.95); color: #fff; padding: 10px 0; font-size: 12px; font-weight: 500; letter-spacing: 0.5px;">
            <div class="container" style="display: flex; justify-content: space-between; align-items: center; max-width: 1400px; padding: 0 40px;">
                <div class="utility-left">
                    <span style="color: #fff;">Foreign Visitors Requirement: <a href="#" style="color: #fff; text-decoration: underline;">Nepal Digital Arrival Card (NDAC)</a></span>
                </div>
                <div class="utility-right" style="display: flex; gap: 25px; align-items: center;">
                    <a href="saved.php" style="color: #fff; text-decoration: none; display: flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f5a623" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.7 0l-1.1 1-1.1-1a5.5 5.5 0 0 0-7.8 7.8l1 1 7.9 8 7.8-7.9 1-1a5.5 5.5 0 0 0 0-7.8z"></path></svg>
                        SAVED
                    </a>
                    <a href="#" style="color: #fff; text-decoration: none; display: flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f5a623" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        SEARCH
                    </a>
                    <a href="#" style="color: #fff; text-decoration: none; display: flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f5a623" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                        TM GALLERY
                    </a>
                    <a href="/Nepal-Travel/Nepal-Travel/user/Register.php" style="color: #fff; text-decoration: none; display: flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f5a623" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        Signup
                    </a>
                </div>
            </div>
        </div>

        <div class="main-nav-bar" style="background: <?php echo $nav_bg; ?>; padding: 25px 0;">
            <div class="container" style="display: flex; justify-content: space-between; align-items: center; max-width:85%; padding: 0 40px;">
                <div class="logo-wrapper">
                    <a href="index.php" style="display: flex; flex-direction: column; text-decoration: none; line-height: 1; margin-top: -5px;">
                        <div style="display: flex; align-items: baseline; justify-content: center; margin-left: 20px;">
                            <span style="font-family: 'Montserrat', sans-serif; font-size: 11px; color: #d32f2f; font-weight: 800; letter-spacing: 0.5px; text-shadow: <?php echo $text_shadow; ?>;">Visit</span>
                        </div>
                        <div style="display: flex; align-items: baseline;">
                            <span style="font-family: 'Playfair Display', serif; font-size: 38px; color: <?php echo $text_main; ?>; font-weight: 700; letter-spacing: -1px; margin-top: -4px; text-shadow: <?php echo $text_shadow; ?>;">Nepal</span>
                            <span style="font-family: 'Montserrat', sans-serif; font-size: 12px; color: #d32f2f; font-weight: 800; margin-left: 2px; text-shadow: <?php echo $text_shadow; ?>;">2026</span>
                        </div>
                        <span style="font-family: 'Great Vibes', cursive; color: <?php echo $script_color; ?>; font-size: 24px; margin-left: 25px; margin-top: -8px; text-shadow: <?php echo $text_shadow; ?>;">Truly Authentic</span>
                    </a>
                </div>

                <nav class="nav-links" style="display: flex; gap: 40px; align-items: center; margin-left: auto;">
                    <style>
                        .nav-links a { text-decoration: none; color: <?php echo $text_main; ?>; font-weight: 600; font-size: 13px; position: relative; padding-bottom: 5px; white-space: nowrap; letter-spacing: 1px; text-shadow: <?php echo $text_shadow; ?>; transition: all 0.3s; }
                        .nav-links a:hover { color: <?php echo $script_color; ?>; }
                        .nav-links a.active::after { content: ''; position: absolute; bottom: -5px; left: 0; width: 100%; height: 2px; background: <?php echo $text_main; ?>; }
                    </style>
                    <a href="about.php" class="<?php echo $current_page == 'about.php' ? 'active' : ''; ?>">ABOUT NEPAL</a>
                    <a href="experience.php" class="<?php echo $current_page == 'experience.php' ? 'active' : ''; ?>">EXPERIENCE</a>
                    <a href="travel-ideas.php" class="<?php echo $current_page == 'travel-ideas.php' ? 'active' : ''; ?>">TRAVEL IDEAS</a>
                    <a href="deals-and-packages.php" class="<?php echo $current_page == 'deals-and-packages.php' ? 'active' : ''; ?>">DEALS & PACKAGES</a>
                    <a href="events.php" class="<?php echo $current_page == 'events.php' ? 'active' : ''; ?>">EVENTS & HAPPENINGS</a>
                </nav>
            </div>
        </div>
    </header>