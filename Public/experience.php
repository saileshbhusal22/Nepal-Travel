<?php include __DIR__ . '/../includes/header.php';  ?>

<!-- Hero Section -->
<section class="hero-refined" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('../images/annapurna_trek.png');">
    <div class="hero-content">
        <h1 class="hero-title">
            <span class="script-font" style="font-family: 'Great Vibes', cursive; color: #f5a623; font-size: 3rem;">Share Your</span><br>
            <span class="sans-bold" style="font-family: 'Playfair Display', serif; color: white; font-size: 4rem; text-transform: uppercase;">Experiences</span>
        </h1>
        <p class="hero-description" style="color: white; font-size: 16px; margin: 0 auto; max-width: 600px;">Capture your moments. Share with the travel community.</p>
        <?php if (isset($_SESSION['user_id'])): ?>
            <button id="openPostModalBtn" class="premium-btn">Create a Post</button>
        <?php else: ?>
            <a href="../user/login.php" class="premium-btn" style="text-decoration: none;">Log In to Post</a>
        <?php endif; ?>
    </div>
</section>

<!-- Main Layout: Sidebar + Grid -->
<section class="travel-ideas-section container">
    <div class="layout-grid">
        <!-- Sidebar Filter -->
        <aside class="sidebar-filter">
            <h3 class="filter-title">Filter Experiences</h3>
            
            <div class="filter-group">
                <h4>State / Region</h4>
                <select class="custom-select">
                    <option>All Regions</option>
                    <option>Everest Region</option>
                    <option>Annapurna Region</option>
                    <option>Kathmandu Valley</option>
                    <option>Terai Plains</option>
                </select>
            </div>
            
            <div class="filter-group">
                <h4>Category</h4>
                <label class="checkbox-label"><input type="checkbox"> <span>Heritage & Culture</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>Nature & Wildlife</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>Trekking & Adventure</span></label>
                <label class="checkbox-label"><input type="checkbox" checked> <span>Spiritual & Wellness</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>City Excitement</span></label>
            </div>
            
            <div class="filter-group">
                <h4>Duration</h4>
                <label class="checkbox-label"><input type="checkbox"> <span>Half Day</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>1 - 3 Days</span></label>
                <label class="checkbox-label"><input type="checkbox" checked> <span>4 - 7 Days</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>8 - 14 Days</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>15+ Days</span></label>
            </div>
            
            <div class="filter-group">
                <h4>Best Time to Visit</h4>
                <label class="checkbox-label"><input type="checkbox"> <span>January</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>February</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>March</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>April</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>May</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>June</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>July</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>August</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>September</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>October</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>November</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>December</span></label>
            </div>
            
            <button class="btn btn-primary" style="width: 100%; border-radius: 8px; padding: 12px; margin-top: 10px;">Apply Filters</button>
        </aside>

        <!-- Main Content -->
        <main class="content-grid">
            <div class="results-info">
                <p>Showing <strong>3</strong> matching experiences</p>
            </div>

            <div class="grid-container">
                <!-- Card 1 -->
                <div class="idea-card">
                    <div class="card-badge">7 DAYS 6 NIGHTS</div>
                    <img src="../images/pokhara_lake.png" alt="Pokhara" class="card-img">
                    <div class="card-overlay">
                        <span class="card-region">Gandaki Zone</span>
                        <h3 class="card-title">Ultimate Wellness & Yoga Retreat</h3>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="idea-card">
                    <div class="card-badge">5 DAYS 4 NIGHTS</div>
                    <img src="../images/bhaktapur_temple.png" alt="Heritage" class="card-img">
                    <div class="card-overlay">
                        <span class="card-region">Lumbini Province</span>
                        <h3 class="card-title">Buddhist Monastary Cultural Immersion</h3>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="idea-card">
                    <div class="card-badge">4 DAYS 3 NIGHTS</div>
                    <img src="../images/annapurna_trek.png" alt="Trek" class="card-img">
                    <div class="card-overlay">
                        <span class="card-region">Bagmati Zone</span>
                        <h3 class="card-title">Shivapuri National Park Forest Bathing</h3>
                    </div>
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
                <input type="file" name="image" id="postImage" accept="image/*" required>
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
                <textarea name="caption" rows="4" placeholder="What was the highlight of this experience?" required></textarea>
            </div>
            <button type="submit" class="premium-btn" style="width: 100%;">Share Experience</button>
            <div id="postError" style="color: red; margin-top: 10px; display: none;"></div>
        </form>
    </div>
</div>

<script>
    const currentUserId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>;
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
