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

    $nav_bg       = $is_home ? 'transparent' : '#fff';
    $text_main    = $is_home ? '#ffffff'     : '#1b3a5a';
    $text_shadow  = $is_home ? '0 4px 6px rgba(0,0,0,0.6)' : 'none';
    $script_color = $is_home ? '#f5a623'     : '#285da1';

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
?>

<header class="site-header" style="<?php echo $header_style; ?>">

    <!-- Top Utility Bar -->
    <div class="top-utility-bar" style="background: rgba(43, 76, 140, 0.95); color: #fff; padding: 10px 0; font-size: 12px; font-weight: 500; letter-spacing: 0.5px;">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; max-width: 1400px; padding: 0 40px;">
            <div class="utility-left">
                <span style="color: #fff;">Foreign Visitors Requirement: 
                    <a href="#" style="color: #fff; text-decoration: underline;">Nepal Digital Arrival Card (NDAC)</a>
                </span>
            </div>
            
            <div class="utility-right" style="display: flex; gap: 25px; align-items: center;">
                <a href="saved.php" style="color: #fff; text-decoration: none; display: flex; align-items: center; gap: 6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f5a623" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.8 4.6a5.5 5.5 0 0 0-7.7 0l-1.1 1-1.1-1a5.5 5.5 0 0 0-7.8 7.8l1 1 7.9 8 7.8-7.9 1-1a5.5 5.5 0 0 0 0-7.8z"></path>
                    </svg>
                    SAVED
                </a>
                
                <button id="openSearchBtn" style="background: none; border: none; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 0;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f5a623" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    SEARCH
                </button>

                <!-- User Section -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/Nepal-Travel/user/dashboard.php" style="color: #fff; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                        <?php if ($profileImageUrl): ?>
                            <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" 
                                 alt="Profile" 
                                 style="width: 28px; height: 28px; border-radius: 50%; border: 2px solid #f5a623; object-fit: cover;">
                        <?php else: ?>
                            <span style="width: 28px; height: 28px; border-radius: 50%; border: 2px solid #f5a623; 
                                       background: linear-gradient(135deg, #1b3a5a, #285da1); 
                                       display: inline-flex; align-items: center; justify-content: center; 
                                       font-size: 12px; font-weight: 700; color: #fff;">
                                <?php echo htmlspecialchars($initials); ?>
                            </span>
                        <?php endif; ?>
                        <span>Hi, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                    </a>
                    <a href="/Nepal-Travel/user/logout.php" style="color: #fff; text-decoration: none;">Logout</a>
                <?php else: ?>
                    <a href="/Nepal-Travel/user/login.php" style="color: #fff; text-decoration: none;">Login</a>
                    <a href="/Nepal-Travel/user/Register.php" style="color: #fff; text-decoration: none;">Signup</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <div class="main-nav-bar" style="background: <?php echo $nav_bg; ?>; padding: 25px 0;">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; max-width: 85%; padding: 0 40px;">
            
            <!-- Logo -->
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

            <!-- Navigation Links -->
            <nav class="nav-links" style="display: flex; gap: 40px; align-items: center; margin-left: auto;">
                <a href="about.php" class="<?php echo $current_page == 'about.php' ? 'active' : ''; ?>">ABOUT NEPAL</a>
                <a href="experience.php" class="<?php echo $current_page == 'experience.php' ? 'active' : ''; ?>">EXPERIENCE</a>
                <a href="travel-ideas.php" class="<?php echo $current_page == 'travel-ideas.php' ? 'active' : ''; ?>">TRAVEL IDEAS</a>
                <a href="deals-and-packages.php" class="<?php echo $current_page == 'deals-and-packages.php' ? 'active' : ''; ?>">DEALS & PACKAGES</a>
                <a href="events.php" class="<?php echo $current_page == 'events.php' ? 'active' : ''; ?>">EVENTS & HAPPENINGS</a>
            </nav>
        </div>
    </div>
</header>

<!-- Search Portal -->
<div id="searchPortal" class="search-portal">
    <div class="search-portal-content">
        <div class="search-header">
            <div class="search-input-wrapper">
                <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="globalSearchInput" class="search-input" placeholder="Search experiences, ideas, deals...">
                <span id="searchPulse" class="search-pulse"></span>
                <button id="clearSearchInput" class="clear-search-btn" title="Clear search">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <button id="closeSearch" class="close-search-btn" title="Close search">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Search Tabs -->
        <div class="search-tabs">
            <button class="search-tab-btn active" data-tab="all">
                All (<span id="count-all">0</span>)
            </button>
            <button class="search-tab-btn" data-tab="experiences">
                Stories (<span id="count-experiences">0</span>)
            </button>
            <button class="search-tab-btn" data-tab="ideas">
                Ideas (<span id="count-ideas">0</span>)
            </button>
            <button class="search-tab-btn" data-tab="deals">
                Deals (<span id="count-deals">0</span>)
            </button>
        </div>

        <!-- Suggestions Panel -->
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
            <div id="recentSearchesBlock" class="suggestions-section" style="display: none;">
                <h3>Recent searches</h3>
                <div id="recentSearchesChips" class="suggestion-chips"></div>
            </div>
        </div>

        <!-- Search Results Grid -->
        <div id="searchResultsGrid" class="search-results-grid"></div>
    </div>
</div>

<!-- Done by Sanskar -->

<script src="../assets/js/global-search.js"></script>
