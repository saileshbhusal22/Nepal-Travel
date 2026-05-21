<?php include '../includes/header.php'; ?>

<link rel="stylesheet" href="../assets/css/experience.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">

<!-- Hero Section -->
<section class="hero-refined" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('../images/annapurna_trek.png'); padding-top: 150px; padding-bottom: 80px; text-align: center;">
    <div class="hero-content">
        <h1 class="hero-title">
            <span class="script-font" style="font-family: 'Great Vibes', cursive; color: #f5a623; font-size: 3rem;">Share Your</span><br>
            <span class="sans-bold" style="font-family: 'Playfair Display', serif; color: white; font-size: 4rem; text-transform: uppercase;">Experiences</span>
        </h1>
        <p class="hero-description" style="color: white; font-size: 16px; margin: 0 auto; max-width: 600px;">Capture your moments. Share with the travel community.</p>
        <?php if (isset($_SESSION['user_id'])): ?>
            <button id="openPostModalBtn" class="premium-btn">Create a Post</button>
        <?php else: ?>
            <a href="<?= htmlspecialchars($auth_login_url) ?>" class="premium-btn" style="text-decoration: none;">Log In to Post</a>
        <?php endif; ?>
    </div>
</section>

<!-- Interactive Nepal Map Integration -->
<section class="interactive-map-section map-section container" style="margin-top: -40px; margin-bottom: 40px; background: white; padding: 40px 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); position: relative; z-index: 10;">
    <div style="text-align: center; margin-bottom: 20px;">
        <h2 style="font-family: 'Playfair Display', serif; color: #1b3a5a;">Explore by Destination</h2>
        <p style="color: #666;">Click on a district below to discover experiences from that region.</p>
    </div>
    
    <div class="map-layout" style="display: flex; gap: 40px; align-items: center; justify-content: space-between;">
        <div class="map-visual map-wrapper" style="flex: 1.5; max-width: 600px; cursor: pointer;">
            <?php include '../includes/map.php'; ?>
        </div>
        <div class="map-details" style="flex: 1;">
            <div class="province-card" style="background: white; border-radius: 12px; padding: 40px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08); border-left: 8px solid #f5a623; text-align: left; min-height: 200px; display: flex; flex-direction: column; justify-content: center;">
                <h3 id="cardDistrictTitle" style="font-family: 'Montserrat', sans-serif; font-size: 28px; font-weight: 800; color: #1b3a5a; margin-bottom: 20px; text-transform: uppercase;">Discover Nepal</h3>
                <p id="cardDistrictDesc" style="font-size: 15px; color: #666; line-height: 1.8; margin-bottom: 20px;">
                    Select a district from the map to instantly filter the feed and reveal regional experiences.
                </p>
                <div id="districtPreviewBox" class="district-preview-box">
                    <img id="districtPreviewImg" src="" class="preview-thumbnail">
                    <div class="preview-meta">
                        <span id="previewCountText">0 Experiences</span>
                        <span style="color: #f5a623; font-weight: 800;">TOP STORY</span>
                    </div>
                </div>
                <div style="margin-top: auto;">
                    <button id="clearMapFilterBtn" class="premium-btn" style="display: none; background: transparent; color: #d32f2f; border: 2px solid #d32f2f; padding: 12px 24px; font-size: 0.9em; border-radius: 30px; width: 100%; font-weight: 800; text-transform: uppercase;">Clear Filter</button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Experience Hero Section -->
<section class="experience-hero" style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1526718583451-e88ebf2791af?q=80&w=2000&auto=format&fit=crop'); height: 350px; background-position: center; display: flex; align-items: center; justify-content: center; text-align: center; color: white; margin-bottom: 50px;">
    <div class="container">
        <h4 style="text-transform: uppercase; letter-spacing: 4px; font-weight: 800; color: #f5a623; margin-bottom: 10px;">Community Stories</h4>
        <h1 style="font-family: 'Playfair Display', serif; font-size: 56px; margin: 0; line-height: 1.1;">Nepal Through Your Eyes</h1>
        <p style="font-size: 18px; max-width: 600px; margin: 20px auto 0; opacity: 0.9;">Join a community of explorers sharing raw, authentic moments from every corner of the Himalayas.</p>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="<?= htmlspecialchars($auth_login_url) ?>" class="premium-btn" style="margin-top: 30px; display: inline-block; text-decoration: none;">Start Sharing Your Story</a>
        <?php else: ?>
            <button id="openPostModalBtn" class="premium-btn" style="margin-top: 30px; border: none;">Share Your Latest Adventure</button>
        <?php endif; ?>
    </div>
</section>

<section style="background: #f8f9fa; padding-bottom: 80px;">
    <div class="container" style="max-width: 1300px;">
        <div class="feed-layout" style="display: grid; grid-template-columns: 1fr 350px; gap: 50px; align-items: start;">
            <!-- Main Feed -->
            <main class="feed-column">
                <!-- Category Filter Bar -->
                <div class="category-filter-bar" style="margin-bottom: 25px; overflow-x: auto; white-space: nowrap; padding-bottom: 10px; display: flex; gap: 10px; scrollbar-width: none;">
                    <button class="cat-btn active" data-category="all">All Experiences</button>
                    <button class="cat-btn" data-category="Trekking">🥾 Trekking</button>
                    <button class="cat-btn" data-category="Wildlife">🐘 Wildlife</button>
                    <button class="cat-btn" data-category="Food">🍲 Food</button>
                    <button class="cat-btn" data-category="Festival">🎉 Festival</button>
                    <button class="cat-btn" data-category="Culture">🏺 Culture</button>
                    <button class="cat-btn" data-category="Adventure">🧗 Adventure</button>
                </div>

                <!-- Feed Filter Bar -->
                <div class="feed-filter-tabs" style="display: flex; gap: 30px; margin-bottom: 30px; border-bottom: 1px solid #ddd; padding-bottom: 15px;">
                    <button class="filter-tab active" data-sort="latest" style="background: none; border: none; font-family: 'Montserrat', sans-serif; font-weight: 800; font-size: 14px; color: #1b3a5a; cursor: pointer; position: relative; padding: 0;">LATEST MOMENTS</button>
                    <button class="filter-tab" data-sort="trending" style="background: none; border: none; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 14px; color: #999; cursor: pointer; padding: 0;">TRENDING</button>
                    <button class="filter-tab" data-sort="most_discussed" style="background: none; border: none; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 14px; color: #999; cursor: pointer; padding: 0;">MOST DISCUSSED</button>
                </div>
                <div id="feedContainer">
                    <!-- Skeletons injected here -->
                </div>
            </main>
        
        <!-- Sidebar Profile info -->
        <aside class="feed-sidebar">
            <div class="sidebar-card profile-card" style="padding: 0; overflow: hidden; border: 1px solid #1b3a5a22;">
                <!-- Profile Banner -->
                <div style="height: 100px; background: url('https://images.unsplash.com/photo-1544735716-392fe2489ffa?q=80&w=1000&auto=format&fit=crop') center/cover no-repeat; position: relative;">
                    <div style="position: absolute; inset: 0; background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.3));"></div>
                </div>
                
                <div style="padding: 0 20px 20px; margin-top: -40px; position: relative; z-index: 2;">
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="profile-info" style="text-align: center;">
                        <div class="avatar-placeholder" style="background: linear-gradient(135deg, #1b3a5a, #2c537a);">
                            <?php echo strtoupper(substr($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'U', 0, 1)); ?>
                        </div>
                        <h3 style="font-family: 'Playfair Display', serif; font-size: 20px;">Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Traveler'); ?>!</h3>
                        
                        <div id="postQuotaBanner" style="display:none;font-size:12px;line-height:1.5;color:#666;background:#fff8e8;border:1px solid #f5a62333;border-radius:10px;padding:10px 12px;margin:12px 0;text-align:left;"></div>

                        <div class="user-stats" style="display: flex; justify-content: center; gap: 20px; margin: 15px 0;">
                            <div class="stat-item" style="text-align: center;">
                                <span id="userPostCount" style="display: block; font-weight: 800; font-size: 18px; color: #1b3a5a;">-</span>
                                <span style="font-size: 10px; color: #999; text-transform: uppercase;">Posts</span>
                            </div>
                            <div class="stat-item" style="text-align: center;">
                                <span id="userLikeCount" style="display: block; font-weight: 800; font-size: 18px; color: #1b3a5a;">-</span>
                                <span style="font-size: 10px; color: #999; text-transform: uppercase;">Likes</span>
                            </div>
                            <div class="stat-item" style="text-align: center;">
                                <span id="userSaveCount" style="display: block; font-weight: 800; font-size: 18px; color: #1b3a5a;">-</span>
                                <span style="font-size: 10px; color: #999; text-transform: uppercase;">Saves</span>
                            </div>
                        </div>

                        <div class="sidebar-actions" style="margin-top: 20px; display: flex; flex-direction: column; gap: 10px;">
                            <button id="openPostModalBtnSide" class="premium-btn" style="width: 100%; border: none;">Create a Post</button>
                            <a href="experience-subscription.php" class="premium-btn outline" style="width: 100%; text-align: center; text-decoration: none; background: transparent; border: 2px solid #f5a623; color: #c47d00; box-sizing: border-box;">Subscription (Khalti / eSewa)</a>
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <button id="myPostsFilterBtn" class="premium-btn outline" style="width: 100%; background: transparent; border: 2px solid #1b3a5a; color: #1b3a5a;">My Shared Memories</button>
                            <?php endif; ?>
                        </div>
                </div>
                <?php else: ?>
                    <div class="profile-info" style="padding: 20px;">
                        <div class="avatar-placeholder" style="background: #f0f4f8; color: #1b3a5a;">
                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <h3 style="font-family: 'Playfair Display', serif; font-size: 20px;">Join the Community</h3>
                        <p style="margin-bottom: 20px;">Share your personal travel moments and connect with a world of explorers.</p>
                        <a href="<?= htmlspecialchars($auth_login_url) ?>" class="premium-btn small-btn" style="text-decoration: none; display: block; border: none;">Log In to Share</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Search Card -->
            <div class="sidebar-card search-card" style="padding: 20px; background: rgba(255, 255, 255, 0.82); backdrop-filter: blur(10px); border: 1px solid #1b3a5a11; margin-top: 20px;">
                <h3 style="font-family: 'Playfair Display', serif; font-size: 18px; margin-bottom: 15px;">Search Experiences</h3>
                <div class="experience-search-wrapper" style="position: relative;">
                    <input type="text" id="feedSearchInput" placeholder="Keywords, locations, users..." style="width: 100%; padding: 12px 12px 12px 40px; border: 1px solid #ddd; border-radius: 12px; font-family: 'Montserrat', sans-serif; transition: all 0.3s; box-sizing: border-box;">
                    <span style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); opacity: 0.5;">🔍</span>
                </div>
            </div>

            <!-- Top Contributors Widget -->
            <div class="sidebar-card contributors-card" style="padding: 25px; margin-top: 20px;">
                <h3 style="font-family: 'Playfair Display', serif; font-size: 20px; margin-bottom: 20px; color: #1b3a5a;">Top Contributors</h3>
                <div id="topContributorsList">
                    <!-- Loaded via JS -->
                    <div class="shimmer" style="height: 50px; border-radius: 10px; margin-bottom: 10px;"></div>
                    <div class="shimmer" style="height: 50px; border-radius: 10px; margin-bottom: 10px;"></div>
                    <div class="shimmer" style="height: 50px; border-radius: 10px;"></div>
                </div>
                <div style="margin-top: 20px; text-align: center; font-size: 12px; color: #999;">
                    Rankings based on posts and likes received.
                </div>
            </div>
            </div>
        </aside>
    </div>
</section>

<!-- Create Post Modal -->
<div id="postModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <span class="close-modal" id="closePostModal">&times;</span>
        <h2>Create New Post</h2>
        <form id="createPostForm" enctype="multipart/form-data">
            <div class="form-group">
                <label>Upload Image</label>
                <input type="file" name="image" id="postImage" accept="image/*">
            </div>
            <div class="image-preview-container" style="display:none; text-align: center; margin: 15px 0;">
                <div style="max-height: 400px; overflow: hidden; border-radius: 8px; background: #f4f4f4;">
                    <img id="imagePreview" src="" alt="Preview" style="display: block; max-width: 100%;">
                </div>
                <div style="margin-top: 10px; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <p style="margin: 0; font-size: 12px; color: #666;">Drag to adjust the crop, or skip to use the full photo.</p>
                    <button type="button" id="skipCropBtn" class="small-btn" style="background: #f0f0f0; color: #555; border: 1px solid #ddd; padding: 6px 15px; font-size: 12px; border-radius: 20px; font-weight: 700; cursor: pointer;">SKIP CROP / USE FULL PHOTO</button>
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Specific Location / Spot</label>
                <input type="text" name="location" id="postLocation" placeholder="e.g. Swayambhunath Stupa, Thamel" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: 'Montserrat', sans-serif; box-sizing: border-box;">
            </div>
            <div class="form-group" style="margin-bottom: 15px; position: relative;">
                <label>Related District (For Map Tagging)</label>
                <input type="text" id="postDestinationInput" placeholder="Type to search your district..." autocomplete="off" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: 'Montserrat', sans-serif; box-sizing: border-box; text-transform: uppercase;">
                <input type="hidden" name="destination" id="postDestinationValue">
                <div id="districtSuggestions" style="position: absolute; top: calc(100% - 2px); left: 0; right: 0; background: white; border: 1px solid #ddd; border-radius: 0 0 8px 8px; max-height: 200px; overflow-y: auto; z-index: 100; display: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1);"></div>
                <small id="districtFeedback" style="color: #666; font-size: 13px; font-weight: 600; display: block; margin-top: 8px; font-family: 'Montserrat', sans-serif;"></small>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Experience Category</label>
                <select name="tags" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: 'Montserrat', sans-serif;">
                    <option value="">Select Category</option>
                    <option value="Trekking">Trekking</option>
                    <option value="Wildlife">Wildlife</option>
                    <option value="Food">Food</option>
                    <option value="Festival">Festival</option>
                    <option value="Culture">Culture</option>
                    <option value="Adventure">Adventure</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Write a Caption</label>
                <textarea name="caption" rows="4" placeholder="What was the highlight of this experience?"></textarea>
            </div>
            <button type="submit" class="premium-btn" style="width: 100%;">Share Experience</button>
            <div id="postError" style="color: red; margin-top: 10px; display: none;"></div>
        </form>
    </div>
</div>

<script>
    const currentUserId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>;
    window.NT_AUTH = {
        loginUrl: <?= json_encode($auth_login_url, JSON_UNESCAPED_SLASHES) ?>,
        registerUrl: <?= json_encode($auth_register_url, JSON_UNESCAPED_SLASHES) ?>,
        subscriptionUrl: '/Nepal-Travel/Public/experience-subscription.php'
    };
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script src="../assets/js/experience.js"></script>

<!-- Edit Post Modal -->
<div id="editPostModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <span class="close-modal" id="closeEditModal">&times;</span>
        <h2>Edit Experience</h2>
        <form id="editPostForm">
            <input type="hidden" name="post_id" id="editPostId">
            <div style="margin-bottom: 20px; text-align: center;">
                <img id="editImagePreview" src="" alt="Thumbnail" style="max-width: 100%; border-radius: 8px; max-height: 200px; object-fit: cover;">
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Specific Location / Spot</label>
                <input type="text" name="location" id="editPostLocation" placeholder="e.g. Swayambhunath Stupa" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: 'Montserrat', sans-serif; box-sizing: border-box;">
            </div>
            <div class="form-group" style="margin-bottom: 15px; position: relative;">
                <label>Related District (For Map Tagging)</label>
                <input type="text" id="editPostDestinationInput" placeholder="Type to search your district..." autocomplete="off" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: 'Montserrat', sans-serif; box-sizing: border-box; text-transform: uppercase;">
                <input type="hidden" name="destination" id="editPostDestinationValue">
                <div id="editDistrictSuggestions" style="position: absolute; top: calc(100% - 2px); left: 0; right: 0; background: white; border: 1px solid #ddd; border-radius: 0 0 8px 8px; max-height: 150px; overflow-y: auto; z-index: 100; display: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1);"></div>
                <div id="editDistrictFeedback" style="font-size: 11px; margin-top: 5px; height: 15px;"></div>
            </div>
            <div class="form-group">
                <label>Edit Caption</label>
                <textarea name="caption" id="editPostCaption" rows="4" placeholder="Update your memory..." required></textarea>
            </div>
            <button type="submit" class="premium-btn" style="width: 100%;">Save Changes</button>
            <div id="editPostError" style="color: red; margin-top: 10px; display: none;"></div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
