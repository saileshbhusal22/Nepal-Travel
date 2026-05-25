<?php 
/**

 * Author: Ramal Gurung
 * Group: L5CG6
 */
$current_page = 'travel-ideas.php';
include __DIR__ . '/../includes/header.php'; 

// Centralized Travel Ideas Data
$travel_ideas = [];
// attempt to load from database, fallback to static include
try {
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../includes/travel-idea-db-seeder.php';
    $tblRes = $conn->query("SHOW TABLES LIKE 'travel_ideas'");
    if ($tblRes && $tblRes->num_rows > 0) {
            $stmt = $conn->prepare("SELECT t.id, t.user_id, t.slug, t.title, COALESCE(p.name, '') AS province, t.province_slug, t.image_path, t.subtitle AS description, t.duration_days, t.nights, t.difficulty, GROUP_CONCAT(DISTINCT et.name ORDER BY et.name SEPARATOR ', ') AS type FROM travel_ideas t LEFT JOIN provinces p ON p.id = t.province_id LEFT JOIN travel_idea_experiences tie ON tie.idea_id = t.id LEFT JOIN experience_types et ON et.id = tie.experience_type_id GROUP BY t.id ORDER BY t.created_at DESC LIMIT 200");
            if ($stmt) {
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $durationDays = isset($row['duration_days']) ? (int)$row['duration_days'] : 0;
                    $durationText = '';
                    if ($durationDays > 0) {
                        $durationText = $durationDays . 'D';
                        if (!empty($row['nights']) && $row['nights'] > 0) {
                            $durationText .= $row['nights'] . 'N';
                        }
                    }

                    $experienceType = trim((string)($row['type'] ?? ''));
                    $travel_ideas[] = [
                        'id' => $row['id'],
                        'slug' => $row['slug'],
                        'title' => $row['title'],
                        'province' => $row['province'] ?? '',
                        'province_slug' => $row['province_slug'] ?? '',
                        'image' => !empty($row['image_path']) ? $row['image_path'] : '../images/default_idea.png',
                        'description' => !empty($row['description']) ? $row['description'] : $experienceType,
                        'season' => '',
                        'duration' => $durationText,
                        'duration_days' => $durationDays,
                        'difficulty' => $row['difficulty'] ?? '',
                        'type' => $experienceType !== '' ? $experienceType : 'Other',
                        'user_id' => $row['user_id'] ?? null
                    ];
                }
                $stmt->close();
            }

            if (empty($travel_ideas)) {
                travelIdeaDbSeedStaticTravelIdeas($conn);

                $stmt = $conn->prepare("SELECT t.id, t.user_id, t.slug, t.title, COALESCE(p.name, '') AS province, t.province_slug, t.image_path, t.subtitle AS description, t.duration_days, t.nights, t.difficulty, GROUP_CONCAT(DISTINCT et.name ORDER BY et.name SEPARATOR ', ') AS type FROM travel_ideas t LEFT JOIN provinces p ON p.id = t.province_id LEFT JOIN travel_idea_experiences tie ON tie.idea_id = t.id LEFT JOIN experience_types et ON et.id = tie.experience_type_id GROUP BY t.id ORDER BY t.created_at DESC LIMIT 200");
                if ($stmt) {
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_assoc()) {
                        $durationDays = isset($row['duration_days']) ? (int)$row['duration_days'] : 0;
                        $durationText = '';
                        if ($durationDays > 0) {
                            $durationText = $durationDays . 'D';
                            if (!empty($row['nights']) && $row['nights'] > 0) {
                                $durationText .= $row['nights'] . 'N';
                            }
                        }

                        $experienceType = trim((string)($row['type'] ?? ''));
                        $travel_ideas[] = [
                            'id' => $row['id'],
                            'slug' => $row['slug'],
                            'title' => $row['title'],
                            'province' => $row['province'] ?? '',
                            'province_slug' => $row['province_slug'] ?? '',
                            'image' => !empty($row['image_path']) ? $row['image_path'] : '../images/default_idea.png',
                            'description' => !empty($row['description']) ? $row['description'] : $experienceType,
                            'season' => '',
                            'duration' => $durationText,
                            'duration_days' => $durationDays,
                            'difficulty' => $row['difficulty'] ?? '',
                            'type' => $experienceType !== '' ? $experienceType : 'Other',
                            'user_id' => $row['user_id'] ?? null
                        ];
                    }
                    $stmt->close();
                }
            }
        } else {
            include_once '../includes/travel-ideas-data.php';
        }
    } catch (Throwable $e) {
        // fallback to static data on any DB error
        include_once '../includes/travel-ideas-data.php';
    }

// Helper to determine duration range for filtering
function getDurationRange($durationValue) {
    if (is_numeric($durationValue)) {
        $days = (int)$durationValue;
    } elseif (preg_match('/\d+/', (string)$durationValue, $matches)) {
        $days = (int)$matches[0];
    } else {
        $days = 0;
    }

    if ($days <= 3) return 'short';
    if ($days <= 7) return 'medium';
    return 'long';
}

$initialSearch = '';
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $initialSearch = trim($_GET['search']);
} elseif (isset($_GET['destination']) && trim($_GET['destination']) !== '') {
    $initialSearch = trim($_GET['destination']);
}
?>

<style>
:root {
    --primary-blue: #1b3a5a;
    --primary-yellow: #f5a623;
    --text-muted: #666;
    --bg-light: #f8f9fa;
    --card-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.hero-about {
    background-size: cover;
    background-position: center;
    position: relative;
    border-bottom: 5px solid var(--primary-yellow);
}

.filter-btn {
    display: block;
    width: 100%;
    padding: 10px 15px;
    margin-bottom: 6px;
    border: 1px solid #eee;
    background: white;
    text-align: left;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    color: var(--text-muted);
    font-size: 13px;
    transition: all 0.3s ease;
}

.filter-btn:hover {
    background: #f0f4f8;
    color: var(--primary-blue);
}

.filter-btn.active {
    background: var(--primary-blue);
    color: white;
    border-color: var(--primary-blue);
}

.search-container {
    margin-bottom: 30px;
    position: relative;
}

.search-input {
    width: 100%;
    padding: 15px 25px 15px 50px;
    border: 1px solid #eee;
    border-radius: 30px;
    font-size: 16px;
    box-shadow: var(--card-shadow);
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: var(--primary-yellow);
    box-shadow: 0 10px 40px rgba(245, 166, 35, 0.1);
}

.search-icon {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
}

.idea-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--card-shadow);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #eee;
    display: flex;
    flex-direction: column;
    text-decoration: none;
    color: inherit;
    height: 100%;
}

.idea-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.1);
}

.card-img-wrapper {
    height: 220px;
    overflow: hidden;
    position: relative;
}

.card-img-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.idea-card:hover .card-img-wrapper img {
    transform: scale(1.1);
}

.province-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: var(--primary-yellow);
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
}

.season-badge {
    position: absolute;
    bottom: 15px;
    left: 15px;
    background: rgba(255,255,255,0.9);
    color: var(--primary-blue);
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
}

.card-content {
    padding: 20px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.card-title {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    color: var(--primary-blue);
    margin: 0 0 8px 0;
}

.card-desc {
    color: var(--text-muted);
    font-size: 13px;
    line-height: 1.5;
    margin: 0 0 15px 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-meta {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    padding-top: 15px;
    border-top: 1px solid #f5f5f5;
}

.meta-item {
    font-size: 11px;
    color: #888;
    display: flex;
    align-items: center;
    gap: 5px;
}

.meta-item strong {
    color: var(--primary-blue);
}

.view-btn {
    background: var(--primary-blue);
    color: white;
    text-align: center;
    padding: 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    transition: all 0.3s ease;
    margin-top: auto;
}

.idea-card:hover .view-btn {
    background: var(--primary-yellow);
}

.share-idea-btn {
    background: var(--primary-blue);
    color: white;
    text-align: center;
    padding: 12px 18px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    white-space: nowrap;
}

.share-idea-btn:hover {
    background: var(--primary-yellow);
    color: #111;
}

/* Package Card Styles */
.package-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--card-shadow);
    border: 1px solid #eee;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
}

.package-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.package-price {
    font-size: 18px;
    color: var(--primary-yellow);
    font-weight: 800;
    margin-top: 10px;
    display: block;
}

.package-cta {
    display: block;
    width: 100%;
    padding: 10px;
    margin-top: 15px;
    border: 2px solid var(--primary-blue);
    color: var(--primary-blue);
    text-align: center;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.package-card:hover .package-cta {
    background: var(--primary-blue);
    color: white;
}

.hidden {
    display: none;
}

    .travel-image-preview-container {
        width: 100%;
        max-width: 100%;
        max-height: 360px;
        overflow: hidden;
    }

    .travel-image-preview-container img,
    .travel-image-preview-container .cropper-container,
    .travel-image-preview-container .cropper-wrap-box,
    .travel-image-preview-container .cropper-view-box,
    .travel-image-preview-container .cropper-canvas,
    .travel-image-preview-container .cropper-canvas img {
        width: 100% !important;
        max-width: 100% !important;
        height: auto !important;
        max-height: 100% !important;
        object-fit: cover !important;
    }

    .modal-content {
        max-width: min(760px, calc(100vw - 48px));
        max-height: calc(100vh - 80px);
        overflow-y: auto;
    }
</style>

<!-- Hero Section -->
<section class="hero-about" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('../images/hero_nepal.png'); height: 350px; display: flex; align-items: center; justify-content: center;">
    <div class="container" style="text-align: center;">
        <h1 class="script-font" style="color: var(--primary-yellow); font-size: 45px; margin-bottom: -10px; font-family: 'Great Vibes', cursive;">Inspiring</h1>
        <h1 class="sans-bold" style="color: white; font-size: 60px; text-transform: uppercase; letter-spacing: 3px; font-family: 'Playfair Display', serif;">Travel Ideas</h1>
        <p style="color: rgba(255,255,255,0.9); font-size: 18px; max-width: 600px; margin: 15px auto 0;">Explore curated journeys and legendary landscapes across the Himalayas.</p>
    </div>
</section>

<!-- Content Section -->
<section style="background: var(--bg-light); padding: 60px 0;">
    <div class="container" style="max-width: 1300px; display: grid; grid-template-columns: 280px 1fr; gap: 40px;">
        
        <!-- Sidebar -->
        <aside>
            <div style="background: white; border-radius: 15px; padding: 25px; box-shadow: var(--card-shadow); position: sticky; top: 120px; max-height: calc(100vh - 140px); overflow-y: auto;">
                <?php if (isset($_SESSION['user_id'])): ?>
                <!-- My Ideas Filter -->
                <div style="margin-bottom: 25px;">
                    <h3 style="font-size: 14px; font-weight: 800; color: var(--primary-blue); letter-spacing: 1px; margin-bottom: 15px; display: flex; justify-content: space-between;">
                        MY IDEAS
                        <span id="resetMyIdeas" style="display:none; font-size: 9px; color: var(--primary-yellow); cursor: pointer;">RESET</span>
                    </h3>
                    <div class="filter-group" id="myIdeasFilters">
                        <button class="filter-btn active" data-ownership="all">All Ideas</button>
                        <button class="filter-btn" data-ownership="mine">My Shared Ideas</button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Province Filter -->
                <div style="margin-bottom: 25px;">
                    <h3 style="font-size: 14px; font-weight: 800; color: var(--primary-blue); letter-spacing: 1px; margin-bottom: 15px; display: flex; justify-content: space-between;">
                        PROVINCE
                        <span id="resetProvince" style="display:none; font-size: 9px; color: var(--primary-yellow); cursor: pointer;">RESET</span>
                    </h3>
                    <div class="filter-group" id="provinceFilters">
                        <button class="filter-btn active" data-province="all">All Regions</button>
                        <button class="filter-btn" data-province="koshi">Koshi</button>
                        <button class="filter-btn" data-province="madhesh">Madhesh</button>
                        <button class="filter-btn" data-province="bagmati">Bagmati</button>
                        <button class="filter-btn" data-province="gandaki">Gandaki</button>
                        <button class="filter-btn" data-province="lumbini">Lumbini</button>
                        <button class="filter-btn" data-province="karnali">Karnali</button>
                        <button class="filter-btn" data-province="sudurpashchim">Sudurpashchim</button>
                    </div>
                </div>

                <!-- Type Filter -->
                <div style="margin-bottom: 25px;">
                    <h3 style="font-size: 14px; font-weight: 800; color: var(--primary-blue); letter-spacing: 1px; margin-bottom: 15px; display: flex; justify-content: space-between;">
                        EXPERIENCE
                        <span id="resetType" style="display:none; font-size: 9px; color: var(--primary-yellow); cursor: pointer;">RESET</span>
                    </h3>
                    <div class="filter-group" id="typeFilters">
                        <button class="filter-btn active" data-type="all">All Types</button>
                        <button class="filter-btn" data-type="Trekking">Trekking</button>
                        <button class="filter-btn" data-type="Culture">Culture</button>
                        <button class="filter-btn" data-type="Wildlife">Wildlife</button>
                        <button class="filter-btn" data-type="Pilgrimage">Pilgrimage</button>
                        <button class="filter-btn" data-type="Adventure">Adventure</button>
                    </div>
                </div>

                <!-- Duration Filter -->
                <div style="margin-bottom: 0;">
                    <h3 style="font-size: 14px; font-weight: 800; color: var(--primary-blue); letter-spacing: 1px; margin-bottom: 15px; display: flex; justify-content: space-between;">
                        DURATION
                        <span id="resetDuration" style="display:none; font-size: 9px; color: var(--primary-yellow); cursor: pointer;">RESET</span>
                    </h3>
                    <div class="filter-group" id="durationFilters">
                        <button class="filter-btn active" data-duration="all">Any Length</button>
                        <button class="filter-btn" data-duration="short">1-3 Days</button>
                        <button class="filter-btn" data-duration="medium">4-7 Days</button>
                        <button class="filter-btn" data-duration="long">8+ Days</button>
                    </div>
                </div>


            </div>
        </aside>

        <!-- Grid -->
        <div>
            <!-- Search Bar with Share Button -->
            <div style="display: flex; gap: 12px; margin-bottom: 30px; align-items: center;">
                <div class="search-container" style="margin-bottom: 0; flex: 1;">
                    <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" id="searchInput" class="search-input" placeholder="Search destinations, activities, or regions...">
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button id="openTravelIdeaModalBtn" class="share-idea-btn">Share Idea</button>
                <?php else: ?>
                    <a href="../user/login.php" class="share-idea-btn" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">Share Idea</a>
                <?php endif; ?>
            </div>

            <div id="ideasGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;">
                <?php foreach($travel_ideas as $idea): ?>
                <?php $durRange = getDurationRange($idea['duration'] ?? '1D'); ?>
                <a href="travel-idea-detail.php?id=<?php echo $idea['id']; ?>" 
                   class="idea-card" 
                   data-province="<?php echo $idea['province_slug']; ?>"
                   data-type="<?php echo htmlspecialchars($idea['type'] ?? 'Other'); ?>"
                   data-duration="<?php echo htmlspecialchars($durRange); ?>"
                   data-title="<?php echo strtolower($idea['title']); ?>"
                   data-desc="<?php echo strtolower($idea['description']); ?>"
                   data-userid="<?php echo htmlspecialchars($idea['user_id'] ?? ''); ?>">
                    <div class="card-img-wrapper">
                        <img src="<?php echo htmlspecialchars($idea['image']); ?>" alt="<?php echo htmlspecialchars($idea['title']); ?>">
                        <span class="province-badge"><?php echo htmlspecialchars($idea['province']); ?></span>
                        <span class="season-badge">🍂 <?php echo htmlspecialchars($idea['season'] ?? 'All Seasons'); ?></span>
                        
                    </div>
                    <div class="card-content">
                        <h2 class="card-title"><?php echo htmlspecialchars($idea['title']); ?></h2>
                        <p class="card-desc"><?php echo htmlspecialchars($idea['description']); ?></p>
                        
                        <div class="card-meta">
                            <div class="meta-item">
                                <!-- Clock Icon -->
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                <strong><?php echo htmlspecialchars($idea['duration'] ?? 'TBD'); ?></strong>
                            </div>
                            <div class="meta-item">
                                <!-- Mountain Icon -->
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m8 3 4 8 5-5 5 15H2L8 3z"></path></svg>
                                <strong><?php echo htmlspecialchars($idea['difficulty'] ?? 'Unknown'); ?></strong>
                            </div>
                        </div>

                        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == ($idea['user_id'] ?? '')): ?>
                            <div style="display: flex; gap: 10px; margin-top: auto;">
                                <div class="view-btn" style="flex: 2; margin-top: 0; display: flex; align-items: center; justify-content: center;">View Detail</div>
                                <button class="edit-idea-btn" data-id="<?php echo $idea['id']; ?>" style="flex: 1; background: var(--primary-yellow); color: white; border: none; border-radius: 8px; font-size: 12px; font-weight: 700; text-transform: uppercase; cursor: pointer; transition: all 0.3s ease;">Edit</button>
                                <button class="delete-idea-btn" data-id="<?php echo $idea['id']; ?>" style="flex: 1; background: #d32f2f; color: white; border: none; border-radius: 8px; font-size: 12px; font-weight: 700; text-transform: uppercase; cursor: pointer; transition: all 0.3s ease;">Delete</button>
                            </div>
                        <?php else: ?>
                            <div class="view-btn">View Journey Detail</div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Empty State -->
            <div id="noResults" class="hidden" style="text-align: center; padding: 100px 0;">
                <h3 style="color: var(--primary-blue); font-family: 'Playfair Display', serif; font-size: 24px;">No matching journeys found</h3>
                <p style="color: var(--text-muted); margin-top: 10px;">Try adjusting your filters or search terms.</p>
            </div>
        </div>
    </div>
</section>

<!-- Travel Idea Post Modal -->
<div id="travelIdeaModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; z-index: 2000; background: rgba(15, 23, 42, 0.65); align-items: flex-start; justify-content: center; padding: 40px 24px 24px; overflow-y: auto;">
    <div class="modal-content" style="background: #fff; border-radius: 24px; max-width: 760px; width: 100%; padding: 30px; position: relative; box-shadow: 0 30px 80px rgba(15, 23, 42, 0.18);">
        <button id="closeTravelIdeaModal" style="position: absolute; top: 18px; right: 18px; border: none; background: transparent; font-size: 28px; line-height: 1; color: #334155; cursor: pointer;">&times;</button>
        <h2 id="travelIdeaModalTitle" style="margin: 0 0 12px; color: #1b3a5a; font-size: 28px;">Share a Travel Idea</h2>
        <p style="margin: 0 0 24px; color: #64748b;">Upload a photo, tag a district, and tell the community about your next recommended route.</p>
        <form id="travelIdeaForm" enctype="multipart/form-data" style="display:grid; gap:18px;">
            <input type="hidden" name="id" id="travelIdeaId">
            <div style="display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:16px;">
                <label style="display:block; font-weight:700; color:#334155;">
                    Journey Title
                    <input type="text" name="title" id="travelIdeaTitle" placeholder="e.g. Kathmandu Heritage Walk" required style="width:100%; margin-top:8px; padding:14px 16px; border:1px solid #d1d5db; border-radius:14px; font-size:14px;">
                </label>
                <label style="display:block; font-weight:700; color:#334155;">
                    Short Subtitle / Tagline
                    <input type="text" name="subtitle" placeholder="e.g. A living museum of culture and temples" style="width:100%; margin-top:8px; padding:14px 16px; border:1px solid #d1d5db; border-radius:14px; font-size:14px;">
                </label>
            </div>

            <label style="display:block; font-weight:700; color:#334155;">
                Cover Image
                <input type="file" name="image" id="travelPostImage" accept="image/jpeg,image/png,image/webp" required style="width:100%; margin-top:8px; padding:12px 14px; border:1px solid #d1d5db; border-radius:14px; font-size:14px;">
            </label>

            <div class="travel-image-preview-container" style="display:none; text-align:center; width:100%; box-sizing:border-box;">
                <div style="max-height: 420px; overflow:hidden; border-radius: 20px; background: #f8fafc; padding: 10px; width:100%; box-sizing:border-box;">
                    <img id="travelImagePreview" src="" alt="Preview" style="width:100%; max-width:100%; height:auto; max-height:400px; display:block; border-radius:16px; object-fit:cover;">
                </div>
                <button type="button" id="travelSkipCropBtn" style="margin-top: 12px; background: #f0f0f0; color: #334155; border: 1px solid #d1d5db; padding: 10px 18px; border-radius: 999px; cursor: pointer; font-size: 13px;">SKIP CROP / USE FULL PHOTO</button>
            </div>

            <div style="display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:16px;">
                <label style="display:block; font-weight:700; color:#334155;">
                    Province
                    <select name="province" id="travelProvinceSelect" required style="width:100%; margin-top:8px; padding:14px 16px; border:1px solid #d1d5db; border-radius:14px; font-size:14px;">
                        <option value="">Select Province</option>
                        <option value="Koshi">Koshi</option>
                        <option value="Madhesh">Madhesh</option>
                        <option value="Bagmati">Bagmati</option>
                        <option value="Gandaki">Gandaki</option>
                        <option value="Lumbini">Lumbini</option>
                        <option value="Karnali">Karnali</option>
                        <option value="Sudurpashchim">Sudurpashchim</option>
                    </select>
                </label>
                <label style="display:block; font-weight:700; color:#334155;">
                    Difficulty Level
                    <select name="difficulty" style="width:100%; margin-top:8px; padding:14px 16px; border:1px solid #d1d5db; border-radius:14px; font-size:14px;">
                        <option value="">Select Difficulty</option>
                        <option value="Easy">Easy</option>
                        <option value="Moderate">Moderate</option>
                        <option value="Challenging">Challenging</option>
                    </select>
                </label>
            </div>

            <div style="display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:16px;">
                <label style="display:block; font-weight:700; color:#334155;">
                    Number of Days
                    <input type="number" name="duration_days" min="1" placeholder="2" style="width:100%; margin-top:8px; padding:14px 16px; border:1px solid #d1d5db; border-radius:14px; font-size:14px;">
                </label>
                <label style="display:block; font-weight:700; color:#334155;">
                    Number of Nights
                    <input type="number" name="nights" min="0" placeholder="1" style="width:100%; margin-top:8px; padding:14px 16px; border:1px solid #d1d5db; border-radius:14px; font-size:14px;">
                </label>
            </div>

            <div style="display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:16px;">
                <label style="display:block; font-weight:700; color:#334155;">
                    Transport Mode
                    <input type="text" name="transport" placeholder="Taxi, Local Bus, Flight" style="width:100%; margin-top:8px; padding:14px 16px; border:1px solid #d1d5db; border-radius:14px; font-size:14px;">
                </label>
                <label style="display:block; font-weight:700; color:#334155;">
                    Accommodation Type
                    <input type="text" name="accommodation" placeholder="Hotels, Teahouses, Homestays" style="width:100%; margin-top:8px; padding:14px 16px; border:1px solid #d1d5db; border-radius:14px; font-size:14px;">
                </label>
            </div>

            <div style="display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:16px;">
                <label style="display:block; font-weight:700; color:#334155;">
                    Best Time to Visit
                    <input type="text" name="best_time" placeholder="e.g. Sept–April" style="width:100%; margin-top:8px; padding:14px 16px; border:1px solid #d1d5db; border-radius:14px; font-size:14px;">
                </label>
                <div></div>
            </div>

            <div style="display:grid; gap:12px;">
                <strong style="font-size:14px; color:#334155; letter-spacing:0.7px;">Experience Type</strong>
                <div style="display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:10px;">
                    <label style="font-weight:600; color:#334155;"><input type="checkbox" name="experience_types[]" value="Trekking"> Trekking</label>
                    <label style="font-weight:600; color:#334155;"><input type="checkbox" name="experience_types[]" value="Culture"> Culture</label>
                    <label style="font-weight:600; color:#334155;"><input type="checkbox" name="experience_types[]" value="Wildlife"> Wildlife</label>
                    <label style="font-weight:600; color:#334155;"><input type="checkbox" name="experience_types[]" value="Pilgrimage"> Pilgrimage</label>
                    <label style="font-weight:600; color:#334155;"><input type="checkbox" name="experience_types[]" value="Adventure"> Adventure</label>
                    <label style="font-weight:600; color:#334155;"><input type="checkbox" name="experience_types[]" value="Nature"> Nature</label>
                    <label style="font-weight:600; color:#334155;"><input type="checkbox" name="experience_types[]" value="History"> History</label>
                </div>
            </div>

            <label style="display:block; font-weight:700; color:#334155;">
                Highlights
                <textarea name="highlights" rows="3" placeholder="Add 3-5 highlights, one per line." style="width:100%; margin-top:8px; padding:14px 16px; border:1px solid #d1d5db; border-radius:14px; font-size:14px; resize:vertical;"></textarea>
            </label>

            <label style="display:block; font-weight:700; color:#334155;">
                Travel Idea Details
                <textarea name="content" id="travelIdeaDetails" rows="5" placeholder="Write detailed route, stops, itinerary notes, or helpful tips for this idea." required style="width:100%; margin-top:8px; padding:14px 16px; border:1px solid #d1d5db; border-radius:14px; font-size:14px; resize:vertical;"></textarea>
            </label>

            <label style="display:block; font-weight:700; color:#334155;">
                Pro Travel Tip
                <textarea name="pro_tip" rows="3" placeholder="Morning visits recommended to avoid crowds" style="width:100%; margin-top:8px; padding:14px 16px; border:1px solid #d1d5db; border-radius:14px; font-size:14px; resize:vertical;"></textarea>
            </label>

            <div style="display:flex; justify-content:space-between; align-items:center; gap:16px;">
                <h3 style="margin:0; color:#1b3a5a; font-size:18px;">Itinerary Builder</h3>
                <button type="button" id="addItineraryDayBtn" class="premium-btn" style="border-radius:999px; padding:10px 18px; font-size:13px;">Add Another Day</button>
            </div>

            <div id="itineraryContainer" style="display:grid; gap:20px; margin-top:10px;"></div>
            <input type="hidden" name="province_slug" id="travelProvinceSlug" value="">

            <button type="submit" class="premium-btn" style="width:100%; border-radius:999px; padding:14px 20px;">Submit Travel Idea</button>
            <div id="travelIdeaError" style="display:none; color:#dc2626; font-size:13px; margin-top:-8px;"></div>
        </form>

        <template id="itineraryDayTemplate">
            <div class="itinerary-day-block" style="padding:18px; border:1px solid #e2e8f0; border-radius:20px; background:#f8fafc; position:relative;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px;">
                    <h4 class="itinerary-day-heading" style="margin:0; color:#1b3a5a; font-size:16px;">Day 1</h4>
                    <button type="button" class="remove-itinerary-day-btn" style="display:none; background:#f8fafc; color:#dc2626; border:1px solid #f1f5f9; border-radius:999px; padding:8px 14px; cursor:pointer; font-size:13px;">Remove</button>
                </div>
                <div style="display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:16px; margin-bottom:16px;">
                    <label style="display:block; font-weight:700; color:#334155;">
                        Day Number
                        <input type="number" name="itinerary_day_order[]" min="1" placeholder="1" style="width:100%; margin-top:8px; padding:14px 16px; border:1px solid #d1d5db; border-radius:14px; font-size:14px;">
                    </label>
                    <label style="display:block; font-weight:700; color:#334155;">
                        Day Title
                        <input type="text" name="itinerary_day_title[]" placeholder="e.g. Heritage Tour" style="width:100%; margin-top:8px; padding:14px 16px; border:1px solid #d1d5db; border-radius:14px; font-size:14px;">
                    </label>
                    <label style="display:block; font-weight:700; color:#334155;">
                        Day Image (optional)
                        <input type="file" name="itinerary_image[]" accept="image/jpeg,image/png,image/webp" style="width:100%; margin-top:8px; padding:12px 14px; border:1px solid #d1d5db; border-radius:14px; font-size:14px;">
                    </label>
                </div>
                <div style="display:grid; gap:16px;">
                    <label style="display:block; font-weight:700; color:#334155;">
                        Morning Activities
                        <textarea name="itinerary_morning[]" rows="3" placeholder="e.g. Durbar Square" style="width:100%; margin-top:8px; padding:14px 16px; border:1px solid #d1d5db; border-radius:14px; font-size:14px; resize:vertical;"></textarea>
                    </label>
                    <label style="display:block; font-weight:700; color:#334155;">
                        Afternoon Activities
                        <textarea name="itinerary_afternoon[]" rows="3" placeholder="e.g. Swayambhunath" style="width:100%; margin-top:8px; padding:14px 16px; border:1px solid #d1d5db; border-radius:14px; font-size:14px; resize:vertical;"></textarea>
                    </label>
                    <label style="display:block; font-weight:700; color:#334155;">
                        Evening Activities
                        <textarea name="itinerary_evening[]" rows="3" placeholder="e.g. Dinner at a traditional restaurant" style="width:100%; margin-top:8px; padding:14px 16px; border:1px solid #d1d5db; border-radius:14px; font-size:14px; resize:vertical;"></textarea>
                    </label>
                </div>
            </div>
        </template>
    </div>
</div>

<!-- "You Might Also Like" Section -->
<section style="padding: 80px 0; border-top: 1px solid #eee; background: white;">
    <div class="container">
        <h2 style="font-family: 'Playfair Display', serif; font-size: 32px; color: var(--primary-blue); text-align: center; margin-bottom: 40px;">Recommended Packages</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
            <a href="deal.php?id=himalayan-peak-retreat" class="package-card">
                <img src="https://images.unsplash.com/photo-1544735716-392fe2489ffa?q=80&w=600" style="width: 100%; height: 200px; object-fit: cover;">
                <div style="padding: 25px;">
                    <span style="color: var(--primary-yellow); font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Luxury Discovery</span>
                    <h4 style="margin: 10px 0; color: var(--primary-blue); font-size: 20px;">Himalayan Peak Retreat</h4>
                    <div style="display: flex; gap: 15px; color: #888; font-size: 12px; margin-bottom: 5px;">
                        <span>⏳ 12 Days</span>
                        <span>🔥 All Inclusive</span>
                    </div>
                    <span class="package-price">$1,450 <small style="font-size: 12px; color: #999; font-weight: 400;">/ person</small></span>
                    <span class="package-cta">Check Availability</span>
                </div>
            </a>
            <a href="deal.php?id=wild-west-expedition" class="package-card">
                <img src="https://images.unsplash.com/photo-1623492701902-47dc207df5dc?q=80&w=600" style="width: 100%; height: 200px; object-fit: cover;">
                <div style="padding: 25px;">
                    <span style="color: var(--primary-yellow); font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Adventure Solo</span>
                    <h4 style="margin: 10px 0; color: var(--primary-blue); font-size: 20px;">Wild West Expedition</h4>
                    <div style="display: flex; gap: 15px; color: #888; font-size: 12px; margin-bottom: 5px;">
                        <span>⏳ 18 Days</span>
                        <span>🔥 Remote Trek</span>
                    </div>
                    <span class="package-price">$2,100 <small style="font-size: 12px; color: #999; font-weight: 400;">/ person</small></span>
                    <span class="package-cta">Check Availability</span>
                </div>
            </a>
            <a href="deal.php?id=ancient-valley-wonders" class="package-card">
                <img src="../images/kathmandu_night_hero.png" style="width: 100%; height: 200px; object-fit: cover;">
                <div style="padding: 25px;">
                    <span style="color: var(--primary-yellow); font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Cultural Heritage</span>
                    <h4 style="margin: 10px 0; color: var(--primary-blue); font-size: 20px;">Ancient Valley Wonders</h4>
                    <div style="display: flex; gap: 15px; color: #888; font-size: 12px; margin-bottom: 5px;">
                        <span>⏳ 5 Days</span>
                        <span>🔥 Guided Tour</span>
                    </div>
                    <span class="package-price">$550 <small style="font-size: 12px; color: #999; font-weight: 400;">/ person</small></span>
                    <span class="package-cta">Check Availability</span>
                </div>
            </a>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const provinceBtns = document.querySelectorAll('#provinceFilters .filter-btn');
    const typeBtns = document.querySelectorAll('#typeFilters .filter-btn');
    const durationBtns = document.querySelectorAll('#durationFilters .filter-btn');
    const searchInput = document.getElementById('searchInput');
    const cards = document.querySelectorAll('.idea-card');
    const noResults = document.getElementById('noResults');
    const myIdeasBtns = document.querySelectorAll('#myIdeasFilters .filter-btn');
    const currentUserId = '<?php echo $_SESSION["user_id"] ?? ""; ?>';

    const urlParams = new URLSearchParams(window.location.search);
    const searchInputValue = (urlParams.get('destination') || urlParams.get('search') || '').trim();
    const initialSearchQuery = searchInputValue.toLowerCase();
    
    let activeProvince = 'all';
    let activeType = 'all';
    let activeDuration = 'all';
    let searchQuery = initialSearchQuery || '';
    let showOnlyMyIdeas = false;

    if (myIdeasBtns.length > 0) {
        myIdeasBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const activeOwnership = this.getAttribute('data-ownership');
                myIdeasBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                showOnlyMyIdeas = (activeOwnership === 'mine');
                applyFilters();
            });
        });
    }



    function applyFilters() {
        let visibleCount = 0;
        
        cards.forEach(card => {
            const cardProvince = card.getAttribute('data-province');
            const cardType = card.getAttribute('data-type');
            const cardDuration = card.getAttribute('data-duration');
            const cardTitle = card.getAttribute('data-title');
            const cardDesc = card.getAttribute('data-desc');
            const cardUserId = card.getAttribute('data-userid');
            
            const provinceMatch = (activeProvince === 'all' || cardProvince === activeProvince);
            const typeMatch = (activeType === 'all' || cardType === activeType);
            const durationMatch = (activeDuration === 'all' || cardDuration === activeDuration);
            const searchMatch = (searchQuery === '' || cardTitle.includes(searchQuery) || cardDesc.includes(searchQuery));
            const ownershipMatch = (!showOnlyMyIdeas || (currentUserId !== '' && cardUserId === currentUserId));

            if (provinceMatch && typeMatch && durationMatch && searchMatch && ownershipMatch) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        noResults.classList.toggle('hidden', visibleCount > 0);
        
        if (document.getElementById('resetMyIdeas')) {
            document.getElementById('resetMyIdeas').style.display = (showOnlyMyIdeas ? 'inline' : 'none');
        }
        document.getElementById('resetProvince').style.display = (activeProvince === 'all' ? 'none' : 'inline');
        document.getElementById('resetType').style.display = (activeType === 'all' ? 'none' : 'inline');
        document.getElementById('resetDuration').style.display = (activeDuration === 'all' ? 'none' : 'inline');
    }

    provinceBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            activeProvince = this.getAttribute('data-province');
            provinceBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            applyFilters();
        });
    });

    typeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            activeType = this.getAttribute('data-type');
            typeBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            applyFilters();
        });
    });

    durationBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            activeDuration = this.getAttribute('data-duration');
            durationBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            applyFilters();
        });
    });

    searchInput.addEventListener('input', function() {
        searchQuery = this.value.toLowerCase().trim();
        applyFilters();
    });

    if (searchInputValue) {
        searchInput.value = searchInputValue;
        applyFilters();
        document.getElementById('ideasGrid').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    if (document.getElementById('resetMyIdeas')) {
        document.getElementById('resetMyIdeas').addEventListener('click', () => {
            document.querySelector('[data-ownership="all"]').click();
        });
    }

    document.getElementById('resetProvince').addEventListener('click', () => {
        document.querySelector('[data-province="all"]').click();
    });

    document.getElementById('resetType').addEventListener('click', () => {
        document.querySelector('[data-type="all"]').click();
    });

    document.getElementById('resetDuration').addEventListener('click', () => {
        document.querySelector('[data-duration="all"]').click();
    });
});
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script src="../assets/js/travel-ideas-api.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const openBtn = document.getElementById('openTravelIdeaModalBtn');
    const modal = document.getElementById('travelIdeaModal');
    const closeBtn = document.getElementById('closeTravelIdeaModal');
    const form = document.getElementById('travelIdeaForm');
    const postImage = document.getElementById('travelPostImage');
    const imagePreview = document.getElementById('travelImagePreview');
    const skipBtn = document.getElementById('travelSkipCropBtn');
    const provinceSelect = document.getElementById('travelProvinceSelect');
    const provinceSlugInput = document.getElementById('travelProvinceSlug');
    const itineraryContainer = document.getElementById('itineraryContainer');
    const itineraryTemplate = document.getElementById('itineraryDayTemplate');
    const addItineraryDayBtn = document.getElementById('addItineraryDayBtn');
    let cropper = null;

    const updateProvinceSlug = () => {
        if (!provinceSelect || !provinceSlugInput) return;
        const slug = provinceSelect.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
        provinceSlugInput.value = slug;
    };

    const updateItineraryHeadings = () => {
        const blocks = itineraryContainer.querySelectorAll('.itinerary-day-block');
        blocks.forEach((block, index) => {
            const heading = block.querySelector('.itinerary-day-heading');
            const orderInput = block.querySelector('input[name="itinerary_day_order[]"]');
            let dayNumber = index + 1;
            if (orderInput && orderInput.value.trim() !== '' && Number(orderInput.value) > 0) {
                dayNumber = Number(orderInput.value);
            }
            if (heading) heading.textContent = 'Day ' + dayNumber;
            const removeBtn = block.querySelector('.remove-itinerary-day-btn');
            if (removeBtn) removeBtn.style.display = blocks.length > 1 ? 'inline-flex' : 'none';
        });
    };

    const addItineraryDay = (data = {}) => {
        if (!itineraryTemplate || !itineraryContainer) return;
        const clone = itineraryTemplate.content.cloneNode(true);
        const heading = clone.querySelector('.itinerary-day-heading');
        const dayOrderInput = clone.querySelector('input[name="itinerary_day_order[]"]');
        const titleInput = clone.querySelector('input[name="itinerary_day_title[]"]');
        const morningInput = clone.querySelector('textarea[name="itinerary_morning[]"]');
        const afternoonInput = clone.querySelector('textarea[name="itinerary_afternoon[]"]');
        const eveningInput = clone.querySelector('textarea[name="itinerary_evening[]"]');
        const removeBtn = clone.querySelector('.remove-itinerary-day-btn');

        if (dayOrderInput) {
            dayOrderInput.value = data.day_order ? data.day_order : itineraryContainer.children.length + 1;
            dayOrderInput.addEventListener('input', updateItineraryHeadings);
        }
        if (titleInput && data.title) titleInput.value = data.title;
        if (morningInput && data.morning) morningInput.value = data.morning;
        if (afternoonInput && data.afternoon) afternoonInput.value = data.afternoon;
        if (eveningInput && data.evening) eveningInput.value = data.evening;

        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                const block = this.closest('.itinerary-day-block');
                if (block) { block.remove(); updateItineraryHeadings(); }
            });
        }

        itineraryContainer.appendChild(clone);
        updateItineraryHeadings();
    };

    const initializeItinerary = () => {
        if (!itineraryContainer) return;
        if (itineraryContainer.children.length === 0) {
            addItineraryDay();
        } else {
            updateItineraryHeadings();
        }
    };

    if (openBtn) openBtn.addEventListener('click', () => { 
        if(modal) { 
            if(form) form.reset();
            const idInput = document.getElementById('travelIdeaId');
            if(idInput) idInput.value = '';
            const title = document.getElementById('travelIdeaModalTitle');
            if(title) title.innerText = 'Share a Travel Idea';
            const imgInput = document.getElementById('travelPostImage');
            if(imgInput) imgInput.required = true;
            modal.style.display = 'flex'; 
            document.body.style.overflow='hidden'; 
        }
    });
    if (closeBtn) closeBtn.addEventListener('click', () => { if(modal) { modal.style.display = 'none'; document.body.style.overflow=''; }});
    if (modal) modal.addEventListener('click', (e) => { if (e.target === modal) { modal.style.display = 'none'; document.body.style.overflow=''; } });

    if (provinceSelect) {
        updateProvinceSlug();
        provinceSelect.addEventListener('change', updateProvinceSlug);
    }
    if (addItineraryDayBtn) {
        addItineraryDayBtn.addEventListener('click', function() {
            addItineraryDay();
        });
    }
    initializeItinerary();

    document.querySelectorAll('.delete-idea-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (!confirm('Are you sure you want to delete this travel idea?')) return;
            const ideaId = this.getAttribute('data-id');
            const originalText = this.innerText;
            this.innerText = '...';
            this.disabled = true;
            try {
                const fd = new FormData();
                fd.append('id', ideaId);
                const res = await fetch('api/travel_ideas/delete_idea.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    this.closest('a.idea-card').remove();
                } else {
                    alert(data.message || 'Failed to delete');
                    this.innerText = originalText;
                    this.disabled = false;
                }
            } catch (err) {
                console.error(err);
                alert('Error deleting idea');
                this.innerText = originalText;
                this.disabled = false;
            }
        });
    });

    document.querySelectorAll('.edit-idea-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            const ideaId = this.getAttribute('data-id');
            const originalText = this.innerText;
            this.innerText = '...';
            this.disabled = true;
            try {
                const res = await fetch('api/travel_ideas/get_idea.php?id=' + ideaId);
                const data = await res.json();
                console.log("Edit Idea Data:", data);
                if (data.success && data.idea) {
                    const form = document.getElementById('travelIdeaForm');
                    if (form) form.reset();
                    
                    const idInput = document.getElementById('travelIdeaId');
                    if (idInput) idInput.value = ideaId;
                    
                    const title = document.getElementById('travelIdeaModalTitle');
                    if (title) title.innerText = 'Edit Travel Idea';
                    
                    const imgInput = document.getElementById('travelPostImage');
                    if (imgInput) imgInput.required = false;
                    
                    const setVal = (name, val) => {
                        const el = document.querySelector(`#travelIdeaForm [name="${name}"]`);
                        if (el) el.value = val || '';
                    };
                    
                    setVal('title', data.idea.title);
                    setVal('subtitle', data.idea.subtitle);
                    
                    const provinceValue = data.idea.province_slug || data.idea.province || '';
                    const provinceEl = document.querySelector('#travelIdeaForm [name="province"]');
                    if (provinceEl) {
                        const expectedVal = data.idea.province || (provinceValue.charAt(0).toUpperCase() + provinceValue.slice(1));
                        // try to match option exactly, else assign
                        let found = false;
                        for (let opt of provinceEl.options) {
                            if (opt.value.toLowerCase() === expectedVal.toLowerCase() || opt.value.toLowerCase() === provinceValue.toLowerCase()) {
                                provinceEl.value = opt.value;
                                found = true;
                                break;
                            }
                        }
                        if (!found) provinceEl.value = expectedVal;
                    }
                    
                    setVal('difficulty', data.idea.difficulty);
                    setVal('duration_days', data.idea.duration_days);
                    setVal('nights', data.idea.nights);
                    setVal('transport', data.idea.transport);
                    setVal('accommodation', data.idea.accommodation);
                    setVal('best_time', data.idea.best_time);
                    setVal('pro_tip', data.idea.pro_tip);
                    
                    if (data.details) {
                        setVal('content', data.details.content);
                        try {
                            const logistics = JSON.parse(data.details.logistics || '{}');
                            if (logistics.transport && !data.idea.transport) setVal('transport', logistics.transport);
                            if (logistics.accommodation && !data.idea.accommodation) setVal('accommodation', logistics.accommodation);
                            if (logistics.best_time && !data.idea.best_time) setVal('best_time', logistics.best_time);
                            if (logistics.pro_tip && !data.idea.pro_tip) setVal('pro_tip', logistics.pro_tip);
                        } catch(e) { console.error("Error parsing logistics", e); }
                    }
                    
                    // Populate Itineraries
                    const itineraryContainer = document.getElementById('itineraryContainer');
                    if (itineraryContainer && data.itineraries && data.itineraries.length > 0) {
                        itineraryContainer.innerHTML = '';
                        data.itineraries.forEach(item => {
                            if (typeof addItineraryDay === 'function') {
                                addItineraryDay({
                                    day_order: item.day_order,
                                    title: item.day_title,
                                    morning: item.morning,
                                    afternoon: item.afternoon,
                                    evening: item.evening
                                });
                            }
                        });
                    }
                    
                    const modal = document.getElementById('travelIdeaModal');
                    if (modal) { modal.style.display = 'flex'; document.body.style.overflow='hidden'; }
                } else {
                    alert('Could not load idea data');
                }
            } catch (err) {
                console.error(err);
                alert('Error loading idea data');
            } finally {
                this.innerText = originalText;
                this.disabled = false;
            }
        });
    });

    if (postImage) {
        postImage.addEventListener('change', function() {
            const file = this.files[0]; if (!file) return;
            const reader = new FileReader(); reader.onload = function(e) {
                imagePreview.src = e.target.result; const ctn = imagePreview.closest('.travel-image-preview-container'); if(ctn) ctn.style.display='block';
                if (cropper) cropper.destroy();
                cropper = new Cropper(imagePreview, { aspectRatio: 4/5, viewMode:1, autoCropArea:0.8 });
            }; reader.readAsDataURL(file);
        });
    }
    if (skipBtn) skipBtn.addEventListener('click', () => { if (cropper) { cropper.destroy(); cropper=null; skipBtn.innerText='✓ USING FULL PHOTO'; } });

    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const err = document.getElementById('travelIdeaError'); if (err) { err.style.display='none'; }
            const submitBtn = form.querySelector('button[type="submit"]'); const orig = submitBtn ? submitBtn.innerText : null; if (submitBtn) { submitBtn.innerText='Submitting...'; submitBtn.disabled=true; }

            const send = async (formData) => {
                try {
                    const ideaId = document.getElementById('travelIdeaId') ? document.getElementById('travelIdeaId').value : '';
                    const apiEndpoint = ideaId ? 'api/travel_ideas/update_idea.php' : 'api/travel_ideas/create_idea.php';
                    const res = await postTravelIdea(form, { apiBase: apiEndpoint, formData });
                    if (res.success) {
                        form.reset(); const ctn = imagePreview.closest('.travel-image-preview-container'); if (ctn) ctn.style.display='none'; if (cropper) { cropper.destroy(); cropper=null; }
                        if (modal) { modal.style.display='none'; document.body.style.overflow=''; }
                        alert(ideaId ? 'Travel idea updated!' : 'Travel idea shared!');
                        window.location.reload();
                    } else {
                        if (err) { err.textContent = res.message || 'Failed'; err.style.display='block'; }
                        alert(res.message || 'Failed to share');
                    }
                } catch (ex) { console.error(ex); if (err) { err.textContent='Server error'; err.style.display='block'; } }
                finally { if (submitBtn) { submitBtn.disabled=false; submitBtn.innerText = orig; } }
            };

            if (cropper) {
                cropper.getCroppedCanvas({ width:1080, height:1350 }).toBlob(async (blob) => {
                    const fd = new FormData(form); fd.set('image', blob, `travel_idea_${Date.now()}.jpg`);
                    await send(fd);
                }, 'image/jpeg', 0.85);
            } else {
                const fd = new FormData(form);
                await send(fd);
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
