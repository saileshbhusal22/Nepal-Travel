<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepal Truly Authentic - Home</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400..900;1,400..900&family=Great+Vibes&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">

    <style>
        /* ---- Header Profile Avatar ---- */
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

        .header-avatar-img {
            object-fit: cover;
        }

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
            box-shadow: 0 0 0 3px rgba(245, 166, 35, 0.45);
        }

        .header-user-greeting {
            font-weight: 600;
            color: #f5a623;
            font-size: 12px;
            font-family: 'Montserrat', sans-serif;
        }
    </style>
</head>

<body>

<?php
    $current_page = basename($_SERVER['PHP_SELF']);
    $is_home      = ($current_page == 'index.php' || $current_page == '');

    $header_style = $is_home
        ? 'position: absolute; top: 0; left: 0; width: 100%; z-index: 1000; background: transparent;'
        : 'position: relative; z-index: 1000; background: #fff; border-bottom: 1px solid #eee;';

    $nav_bg      = $is_home ? 'transparent' : '#fff';
    $text_main   = $is_home ? '#ffffff'     : '#1b3a5a';
    $text_shadow = $is_home ? '0 4px 6px rgba(0,0,0,0.6)' : 'none';
    $script_color = $is_home ? '#f5a623'   : '#285da1';

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

<header class="site-header" style="<?php echo $header_style; ?>">

    <!-- Top Utility Bar -->
    <div class="top-utility-bar" style="background: rgba(43, 76, 140, 0.95); color: #fff; padding: 10px 0; font-size: 12px;">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; max-width: 1400px; padding: 0 40px;">

            <div class="utility-left">
                <span>Foreign Visitors Requirement:
                    <a href="#" style="color: #fff; text-decoration: underline;">
                        Nepal Digital Arrival Card (NDAC)
                    </a>
                </span>
            </div>

            <div class="utility-right" style="display: flex; gap: 20px; align-items: center;">

                <a href="saved.php" style="color: #fff; text-decoration: none;">SAVED</a>
                <a href="#"         style="color: #fff; text-decoration: none;">SEARCH</a>

                <!-- ✅ USER SECTION START -->
                <?php if (isset($_SESSION['user_id'])): ?>

                    <!-- Avatar + greeting → dashboard -->
                    <a href="/Nepal-Travel/user/dashboard.php" class="header-avatar-wrap">

                        <?php if ($profileImageUrl): ?>
                            <img src="<?php echo $profileImageUrl; ?>"
                                 id="headerProfileAvatar"
                                 class="header-avatar-img"
                                 alt="<?php echo htmlspecialchars($_SESSION['user_name']); ?>">
                        <?php else: ?>
                            <span class="header-avatar-initial" id="headerProfileAvatar">
                                <?php echo $initials; ?>
                            </span>
                        <?php endif; ?>

                        <span class="header-user-greeting">
                            Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </span>

                    </a>

                   

                    <a href="/Nepal-Travel/user/logout.php"
                       style="color: #fff; text-decoration: none;">Logout</a>

                <?php else: ?>

                    <a href="/Nepal-Travel/user/login.php"
                       style="color: #fff; text-decoration: none;">Login</a>

                    <a href="/Nepal-Travel/user/Register.php"
                       style="color: #fff; text-decoration: none;">Signup</a>

                <?php endif; ?>
                <!-- ✅ USER SECTION END -->

            </div>
        </div>
    </div>

    <!-- Main Nav Bar -->
    <div class="main-nav-bar" style="background: <?php echo $nav_bg; ?>; padding: 25px 0;">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; max-width:85%; padding: 0 40px;">

            <!-- Logo -->
            <div>
                <a href="index.php" style="text-decoration:none;">
                    <span style="font-size:32px; color:<?php echo $text_main; ?>;">Nepal</span>
                </a>
            </div>

            <!-- Nav Links -->
            <nav style="display:flex; gap:30px;">
                <a href="about.php"           style="color:<?php echo $text_main; ?>;">ABOUT</a>
                <a href="experience.php"      style="color:<?php echo $text_main; ?>;">EXPERIENCE</a>
                <a href="travel-ideas.php"    style="color:<?php echo $text_main; ?>;">TRAVEL IDEAS</a>
                <a href="deals-and-packages.php" style="color:<?php echo $text_main; ?>;">DEALS</a>
                <a href="events.php"          style="color:<?php echo $text_main; ?>;">EVENTS</a>
            </nav>

        </div>
    </div>

</header>