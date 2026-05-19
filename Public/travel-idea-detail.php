<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/travel-idea-details-data.php';
require_once __DIR__ . '/../includes/travel-idea-db-seeder.php';

$id = isset($_GET['id']) ? trim($_GET['id']) : '';
$detail = null;

function formatDurationText($days, $nights) {
    $days = (int)$days;
    $nights = (int)$nights;
    if ($days <= 0) {
        return 'TBD';
    }
    $text = $days . 'D';
    if ($nights > 0) {
        $text .= $nights . 'N';
    }
    return $text;
}

function buildTravelIdeaDetail($row) {
    $subtitle = $row['subtitle'] ?? '';
    $experienceLabel = $row['experience_types'] ?? '';
    $content = !empty($row['content']) ? $row['content'] : (!empty($subtitle) ? $subtitle : 'A travel idea shared by our community.');

    $highlights = [];
    if (!empty($row['highlights'])) {
        $highlights = json_decode($row['highlights'], true);
        if (!is_array($highlights)) {
            $highlights = [];
        }
    }
    if (empty($highlights)) {
        $fallbacks = array_filter([
            !empty($subtitle) ? $subtitle : null,
            !empty($row['difficulty']) ? 'Difficulty: ' . $row['difficulty'] : null,
            (!empty($row['duration_days']) && (int)$row['duration_days'] > 0) ? $row['duration_days'] . ' day trip' : null,
            !empty($experienceLabel) ? $experienceLabel : null,
        ]);
        $highlights = !empty($fallbacks) ? array_values($fallbacks) : ['Community travel idea'];
    }

    return [
        'id' => $row['id'],
        'owner_id' => isset($row['owner_id']) ? (int)$row['owner_id'] : null,
        'slug' => $row['slug'],
        'title' => $row['title'],
        'province' => $row['province'] ?: 'Unknown Province',
        'duration' => formatDurationText($row['duration_days'], $row['nights']),
        'duration_days' => isset($row['duration_days']) ? (int)$row['duration_days'] : 0,
        'nights' => isset($row['nights']) ? (int)$row['nights'] : 0,
        'vibe' => !empty($subtitle) ? $subtitle : (!empty($experienceLabel) ? $experienceLabel : 'Shared Travel Idea'),
        'hero_image' => !empty($row['hero_image']) ? $row['hero_image'] : (!empty($row['image_path']) ? $row['image_path'] : '../images/default_idea.png'),
        'intro' => $content,
        'content' => $content,
        'is_dynamic' => true,
        'highlights' => $highlights,
        'logistics' => !empty($row['logistics']) ? json_decode($row['logistics'], true) : ['transport' => 'Details are provided in the shared description above.', 'accommodation' => 'Check the travel idea text for accommodation recommendations.', 'best_time' => 'This is a community idea; best timing is flexible.', 'pro_tip' => 'Read the shared plan carefully for local tips.'],
        'difficulty' => !empty($row['difficulty']) ? $row['difficulty'] : 'Unknown'
    ];
}

if (!empty($id)) {
    $queryBase = "SELECT t.id, t.user_id AS owner_id, t.title, COALESCE(p.name, '') AS province, t.province_slug, t.image_path, t.slug, t.subtitle, t.duration_days, t.nights, t.difficulty, d.content, d.highlights, d.logistics, d.hero_image, GROUP_CONCAT(DISTINCT et.name ORDER BY et.name SEPARATOR ', ') AS experience_types FROM travel_ideas t LEFT JOIN provinces p ON p.id = t.province_id LEFT JOIN travel_idea_details d ON d.idea_id = t.id LEFT JOIN travel_idea_experiences tie ON tie.idea_id = t.id LEFT JOIN experience_types et ON et.id = tie.experience_type_id";
    if (is_numeric($id) && $id > 0) {
        $queryBase .= " WHERE t.id = ? GROUP BY t.id LIMIT 1";
        $stmt = $conn->prepare($queryBase);
        if ($stmt) {
            $stmt->bind_param('i', $id);
        }
    } else {
        $slug = trim($id);
        $queryBase .= " WHERE t.slug = ? GROUP BY t.id LIMIT 1";
        $stmt = $conn->prepare($queryBase);
        if ($stmt) {
            $stmt->bind_param('s', $slug);
        }
    }

    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $detail = buildTravelIdeaDetail($row);
        } elseif (!is_numeric($id) && isset($slug) && travelIdeaDbSeedTravelIdeaBySlug($conn, $slug)) {
            $stmt->close();
            $stmt = $conn->prepare($queryBase);
            if ($stmt) {
                $stmt->bind_param('s', $slug);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $row = $result->fetch_assoc()) {
                    $detail = buildTravelIdeaDetail($row);
                }
            }
        }

        if ($detail) {
            if ($stmt) { $stmt->close(); $stmt = null; }
            $itineraryStmt = $conn->prepare("SELECT id, day_order, day_title, morning, afternoon, evening, image_path FROM itineraries WHERE idea_id = ? ORDER BY day_order ASC, id ASC");
            if ($itineraryStmt) {
                $itineraryStmt->bind_param('i', $detail['id']);
                $itineraryStmt->execute();
                $itineraryRes = $itineraryStmt->get_result();
                $detail_itinerary = [];
                while ($itineraryRow = $itineraryRes->fetch_assoc()) {
                    $detail_itinerary[] = [
                        'id' => (int)$itineraryRow['id'],
                        'day_order' => (int)$itineraryRow['day_order'],
                        'title' => $itineraryRow['day_title'] ?: 'Day ' . $itineraryRow['day_order'],
                        'morning' => $itineraryRow['morning'],
                        'afternoon' => $itineraryRow['afternoon'],
                        'evening' => $itineraryRow['evening'],
                        'img' => $itineraryRow['image_path']
                    ];
                }
                $itineraryStmt->close();
                if (!empty($detail_itinerary)) {
                    $detail['itinerary'] = $detail_itinerary;
                } else {
                    $detail['itinerary'] = [['title' => 'Day 1', 'morning' => $detail['content'], 'afternoon' => '', 'evening' => '', 'img' => null]];
                }
            }
        } else {
            if ($stmt) { $stmt->close(); $stmt = null; }
        }
    }
}

$relatedRows = [];
if ($detail && isset($detail['id']) && is_numeric($detail['id'])) {
    $relatedStmt = $conn->prepare(
        "SELECT t.id, t.title, t.subtitle, t.image_path, d.hero_image, t.duration_days, t.nights
         FROM travel_ideas t
         LEFT JOIN travel_idea_details d ON d.idea_id = t.id
         WHERE t.id != ?
         ORDER BY t.id DESC
         LIMIT 3"
    );
    if ($relatedStmt) {
        $relatedStmt->bind_param('i', $detail['id']);
        $relatedStmt->execute();
        $relatedRows = $relatedStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $relatedStmt->close();
    }
}

if (!$detail && isset($travel_idea_details[$id])) {
    $detail = $travel_idea_details[$id];
    $detail['is_dynamic'] = true;
    if (!isset($detail['intro'])) {
        $detail['intro'] = $detail['content'] ?? '';
    }
}

if (!$detail) {
    header('Location: travel-ideas.php');
    exit;
}

$current_page = 'travel-ideas.php';
include '../includes/header.php'; 
?>

<link rel="stylesheet" href="../assets/css/travel-idea-detail.css">

<!-- Hero Section -->
<section class="detail-hero" style="background-image: url('<?php echo htmlspecialchars($detail['hero_image']); ?>');">
    <?php if (isset($_SESSION['user_id']) && $detail['owner_id'] !== null && $detail['owner_id'] === (int)$_SESSION['user_id']): ?>
        <div class="owner-action-buttons">
            <button id="editIdeaBtn" class="edit-idea-button">Edit Travel Idea</button>
            <button id="deleteIdeaBtn" class="delete-idea-button">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                    <path d="M10 11v6"></path>
                    <path d="M14 11v6"></path>
                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                </svg>
                Delete Travel Idea
            </button>
        </div>
    <?php endif; ?>
    <div class="container hero-info">
        <span class="vibe-tag"><?php echo htmlspecialchars($detail['vibe']); ?></span>
        <h1 class="detail-title"><?php echo htmlspecialchars($detail['title']); ?></h1>
        <div class="detail-meta">
            <span>
                <!-- Location Icon -->
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"/><circle cx="12" cy="10" r="3"/></svg>
                <?php echo htmlspecialchars($detail['province']); ?>
            </span>
            <span>
                <!-- Clock Icon -->
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                <?php echo htmlspecialchars($detail['duration']); ?>
            </span>
            <?php if(isset($detail['difficulty'])): ?>
            <span>
                <!-- Mountain Icon -->
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m8 3 4 8 5-5 5 15H2L8 3z"></path></svg>
                <?php echo htmlspecialchars($detail['difficulty']); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Main Content Area -->
<section style="background: var(--bg-light); padding-bottom: 80px;">
    <div class="container" style="max-width: 1300px; display: grid; grid-template-columns: 1fr 350px; gap: 80px;">
        
        <!-- Left Article Side -->
        <main class="detail-article">
            <p class="intro-text"><?php echo htmlspecialchars($detail['intro']); ?></p>
            
            <?php if (!empty($detail['is_dynamic']) && !empty($detail['content']) && $detail['content'] !== $detail['intro']): ?>
            <div style="margin-bottom: 60px;">
                <h3 style="font-family: 'Playfair Display', serif; font-size: 32px; color: var(--primary-blue); margin-bottom: 20px;">Travel Idea Details</h3>
                <div style="background: white; border-radius: 18px; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.05); line-height: 1.8; color: #334155;">
                    <?php echo nl2br(htmlspecialchars($detail['content'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <div style="margin-bottom: 60px;">
                <h3 style="font-family: 'Playfair Display', serif; font-size: 32px; color: var(--primary-blue); margin-bottom: 30px;">Trip Highlights</h3>
                <div class="highlights-grid">
                    <?php foreach ($detail['highlights'] as $highlight): ?>
                    <div class="highlight-item">
                        <p><?php echo htmlspecialchars($highlight); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Itinerary Timeline -->
            <div class="itinerary-section">
                <h3 style="font-family: 'Playfair Display', serif; font-size: 32px; color: var(--primary-blue); margin-bottom: 40px;">The Itinerary</h3>
                
                <?php foreach (is_array($detail['itinerary']) ? $detail['itinerary'] : [] as $day => $info): ?>
                <?php $displayOrder = isset($info['day_order']) && (int)$info['day_order'] > 0 ? (int)$info['day_order'] : ($day + 1); ?>
                <?php $itineraryTitle = isset($info['title']) ? $info['title'] : (isset($info['details']) ? $info['details'] : ('Day ' . $displayOrder)); ?>
                <?php
                    $dayLabel = 'Day ' . $displayOrder;
                    $itineraryImage = null;
                    if (!empty($info['img'])) {
                        $itineraryImage = $info['img'];
                    } elseif (!empty($info['image_path'])) {
                        $itineraryImage = $info['image_path'];
                    } elseif (!empty($detail['hero_image'])) {
                        $itineraryImage = $detail['hero_image'];
                    } else {
                        $itineraryImage = '../images/default_idea.png';
                    }
                    if (strpos($itineraryImage, '../') === 0) {
                        $itineraryImage = '/Nepal-Travel/' . substr($itineraryImage, 3);
                    }
                ?>
                <div class="day-card">
                    <div class="day-number"><?php echo htmlspecialchars($dayLabel); ?></div>
                    <div class="day-content">
                        <h4><?php echo htmlspecialchars($itineraryTitle); ?></h4>
                        
                        <?php if (isset($info['morning'])): ?>
                        <div class="activity">
                            <strong>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="m12 1v2"/><path d="m12 21v2"/><path d="m4.22 4.22 1.42 1.42"/><path d="m18.36 18.36 1.42 1.42"/><path d="m1 12h2"/><path d="m21 12h2"/><path d="m4.22 19.78 1.42-1.42"/><path d="m18.36 5.64 1.42-1.42"/></svg>
                                Morning
                            </strong>
                            <p><?php echo htmlspecialchars($info['morning']); ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($info['afternoon'])): ?>
                        <div class="activity">
                            <strong>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="M20 12h2"/><path d="m19.07 4.93-1.41 1.41"/><path d="M15.947 12.65a4 4 0 0 0-5.925-4.128c.08.398.128.81.128 1.228 0 2.21-1.79 4-4 4-.42 0-.83-.05-1.228-.127a4 4 0 1 0 11.025-1z"/></svg>
                                Afternoon
                            </strong>
                            <p><?php echo htmlspecialchars($info['afternoon']); ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($info['evening'])): ?>
                        <div class="activity">
                            <strong>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                                Evening
                            </strong>
                            <p><?php echo htmlspecialchars($info['evening']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="day-image">
                        <img src="<?php echo htmlspecialchars($itineraryImage); ?>" alt="<?php echo htmlspecialchars($itineraryTitle); ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>

        <!-- Right Logistics Container -->
        <aside>
            <div class="info-sidebar-box">
                <h3>Good to Know</h3>
                <ul class="info-list">
                    <li>
                        <strong>Transport</strong>
                        <p><?php echo htmlspecialchars($detail['logistics']['transport']); ?></p>
                    </li>
                    <li>
                        <strong>Accommodation</strong>
                        <p><?php echo htmlspecialchars($detail['logistics']['accommodation']); ?></p>
                    </li>
                    <li>
                        <strong>Best Time to Go</strong>
                        <p><?php echo htmlspecialchars($detail['logistics']['best_time']); ?></p>
                    </li>
                    <li style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 30px;">
                        <strong>Pro Travel Tip</strong>
                        <p style="color: #d35400; font-style: italic;"><?php echo htmlspecialchars($detail['logistics']['pro_tip']); ?></p>
                    </li>
                </ul>
            </div>

            <!-- Related Suggestions -->
            <div class="related-ideas-sidebar" style="margin-top: 50px;">
                <h4 style="font-family: 'Playfair Display', serif; font-size: 22px; margin-bottom: 25px; color: var(--primary-blue);">More to Explore</h4>
                <?php foreach ($relatedRows as $rdata): ?>
                <?php
                    $relatedImage = !empty($rdata['hero_image']) ? $rdata['hero_image'] : (!empty($rdata['image_path']) ? $rdata['image_path'] : '../images/default_idea.png');
                    $relatedSubtitle = !empty($rdata['subtitle']) ? $rdata['subtitle'] : 'Shared Travel Idea';
                    $relatedDuration = formatDurationText($rdata['duration_days'], $rdata['nights']);
                ?>
                <a href="travel-idea-detail.php?id=<?php echo $rdata['id']; ?>" class="related-card-link" style="text-decoration: none; display: block; margin-bottom: 20px;">
                    <div class="related-card" style="display: flex; gap: 15px; align-items: center;">
                        <img src="<?php echo htmlspecialchars($relatedImage); ?>" style="width: 80px; height: 80px; border-radius: 10px; object-fit: cover;">
                        <div>
                            <span style="font-size: 10px; text-transform: uppercase; color: var(--primary-yellow); font-weight: 800; letter-spacing: 1px;"><?php echo htmlspecialchars($relatedSubtitle); ?></span>
                            <h5 style="margin: 3px 0; color: var(--primary-blue); font-size: 14px; line-height: 1.3;"><?php echo htmlspecialchars($rdata['title']); ?></h5>
                            <span style="font-size: 11px; color: #999;"><?php echo htmlspecialchars($relatedDuration); ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 40px; text-align: center;">
                <a href="travel-ideas.php" class="premium-btn" style="width: 100%; box-sizing: border-box; display: inline-block; text-decoration: none;">Explore All Ideas</a>
            </div>
        </aside>

    </div>
</section>

<?php if (isset($_SESSION['user_id']) && $detail['owner_id'] !== null && $detail['owner_id'] === (int)$_SESSION['user_id']): ?>
<div id="editIdeaModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <span class="close-modal" id="closeEditModal">&times;</span>
        <h2>Edit Travel Idea</h2>
        <form id="editIdeaForm" enctype="multipart/form-data">
            <input type="hidden" name="idea_id" value="<?php echo htmlspecialchars($detail['id']); ?>">
            <div class="field-grid">
                <label>
                    <span>Title</span>
                    <input type="text" name="title" id="editIdeaTitle" required>
                </label>
                <label>
                    <span>Short Summary</span>
                    <input type="text" name="subtitle" id="editIdeaSubtitle">
                </label>
                <label>
                    <span>Province</span>
                    <input type="text" name="province" id="editIdeaProvince" placeholder="e.g. Bagmati">
                </label>
                <label>
                    <span>Difficulty</span>
                    <select name="difficulty" id="editIdeaDifficulty">
                        <option value="">Select difficulty</option>
                        <option value="Easy">Easy</option>
                        <option value="Moderate">Moderate</option>
                        <option value="Challenging">Challenging</option>
                    </select>
                </label>
                <label>
                    <span>Duration (days)</span>
                    <input type="number" name="duration_days" id="editIdeaDays" min="1" placeholder="e.g. 5">
                </label>
                <label>
                    <span>Nights</span>
                    <input type="number" name="nights" id="editIdeaNights" min="0" placeholder="e.g. 4">
                </label>
            </div>

            <label style="display:block; margin-top:20px;">
                <span>Main Content</span>
                <textarea name="content" id="editIdeaContent" rows="5" required></textarea>
            </label>

            <label style="display:block; margin-top:20px;">
                <span>Trip Highlights (one per line)</span>
                <textarea name="highlights" id="editIdeaHighlights" rows="3" placeholder="Highlight 1\nHighlight 2"></textarea>
            </label>

            <div class="field-grid" style="margin-top:20px;">
                <label>
                    <span>Transport</span>
                    <input type="text" name="transport" id="editIdeaTransport">
                </label>
                <label>
                    <span>Accommodation</span>
                    <input type="text" name="accommodation" id="editIdeaAccommodation">
                </label>
                <label>
                    <span>Best Time</span>
                    <input type="text" name="best_time" id="editIdeaBestTime">
                </label>
                <label>
                    <span>Pro Tip</span>
                    <input type="text" name="pro_tip" id="editIdeaProTip">
                </label>
            </div>

            <div class="itinerary-editor">
                <div class="editor-header">
                    <h3>Edit Itinerary</h3>
                    <button type="button" id="addDayBtn" class="secondary-btn">Add Day</button>
                </div>
                <div id="itineraryDaysContainer"></div>
            </div>

            <div id="editIdeaError" class="form-error" style="display:none;"></div>
            <div style="display:flex; gap: 16px; flex-wrap: wrap; margin-top: 24px; align-items:center;">
                <button type="submit" class="primary-btn">Save Changes</button>
                <button type="button" class="secondary-btn" id="cancelEditBtn">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
(function() {
    const detail = <?php echo json_encode($detail, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const editIdeaBtn = document.getElementById('editIdeaBtn');
    const deleteIdeaBtn = document.getElementById('deleteIdeaBtn');
    const editModal = document.getElementById('editIdeaModal');
    const closeEditModal = document.getElementById('closeEditModal');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const itineraryContainer = document.getElementById('itineraryDaysContainer');
    const editForm = document.getElementById('editIdeaForm');

    if (editIdeaBtn && editModal) {

    function createItineraryDay(day = {}) {
        const idx = itineraryContainer.children.length + 1;
        const wrapper = document.createElement('div');
        wrapper.className = 'day-block';

        const header = document.createElement('div');
        header.className = 'day-block-header';

        const title = document.createElement('strong');
        title.textContent = `Day ${idx}`;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-day-btn';
        removeBtn.textContent = 'Remove';
        removeBtn.addEventListener('click', () => {
            wrapper.remove();
            refreshDayLabels();
        });

        header.appendChild(title);
        header.appendChild(removeBtn);
        wrapper.appendChild(header);

        const grid = document.createElement('div');
        grid.className = 'field-grid day-fields';

        const orderLabel = document.createElement('label');
        const orderSpan = document.createElement('span');
        orderSpan.textContent = 'Day Order';
        const orderInput = document.createElement('input');
        orderInput.type = 'number';
        orderInput.name = 'itinerary_day_order[]';
        orderInput.min = '1';
        orderInput.value = day.day_order ?? idx;
        orderLabel.appendChild(orderSpan);
        orderLabel.appendChild(orderInput);

        const titleLabel = document.createElement('label');
        const titleSpan = document.createElement('span');
        titleSpan.textContent = 'Title';
        const titleInput = document.createElement('input');
        titleInput.type = 'text';
        titleInput.name = 'itinerary_day_title[]';
        titleInput.placeholder = `Day ${idx} title`;
        titleInput.value = day.title || '';
        titleLabel.appendChild(titleSpan);
        titleLabel.appendChild(titleInput);

        const morningLabel = document.createElement('label');
        const morningSpan = document.createElement('span');
        morningSpan.textContent = 'Morning';
        const morningTextarea = document.createElement('textarea');
        morningTextarea.name = 'itinerary_morning[]';
        morningTextarea.rows = 2;
        morningTextarea.value = day.morning || '';
        morningLabel.appendChild(morningSpan);
        morningLabel.appendChild(morningTextarea);

        const afternoonLabel = document.createElement('label');
        const afternoonSpan = document.createElement('span');
        afternoonSpan.textContent = 'Afternoon';
        const afternoonTextarea = document.createElement('textarea');
        afternoonTextarea.name = 'itinerary_afternoon[]';
        afternoonTextarea.rows = 2;
        afternoonTextarea.value = day.afternoon || '';
        afternoonLabel.appendChild(afternoonSpan);
        afternoonLabel.appendChild(afternoonTextarea);

        const eveningLabel = document.createElement('label');
        const eveningSpan = document.createElement('span');
        eveningSpan.textContent = 'Evening';
        const eveningTextarea = document.createElement('textarea');
        eveningTextarea.name = 'itinerary_evening[]';
        eveningTextarea.rows = 2;
        eveningTextarea.value = day.evening || '';
        eveningLabel.appendChild(eveningSpan);
        eveningLabel.appendChild(eveningTextarea);

        grid.appendChild(orderLabel);
        grid.appendChild(titleLabel);
        grid.appendChild(morningLabel);
        grid.appendChild(afternoonLabel);
        grid.appendChild(eveningLabel);

        wrapper.appendChild(grid);
        itineraryContainer.appendChild(wrapper);
    }

    function refreshDayLabels() {
        itineraryContainer.querySelectorAll('.day-block').forEach((block, index) => {
            const label = block.querySelector('.day-block-header strong');
            if (label) {
                label.textContent = `Day ${index + 1}`;
            }
        });
    }

    function populateForm() {
        document.getElementById('editIdeaTitle').value = detail.title || '';
        document.getElementById('editIdeaSubtitle').value = detail.vibe || '';
        document.getElementById('editIdeaProvince').value = detail.province || '';
        document.getElementById('editIdeaDifficulty').value = detail.difficulty || '';
        document.getElementById('editIdeaDays').value = detail.duration_days || '';
        document.getElementById('editIdeaNights').value = detail.nights || '';
        document.getElementById('editIdeaContent').value = detail.content || '';
        document.getElementById('editIdeaHighlights').value = Array.isArray(detail.highlights) ? detail.highlights.join('\n') : detail.highlights || '';
        document.getElementById('editIdeaTransport').value = detail.logistics?.transport || '';
        document.getElementById('editIdeaAccommodation').value = detail.logistics?.accommodation || '';
        document.getElementById('editIdeaBestTime').value = detail.logistics?.best_time || '';
        document.getElementById('editIdeaProTip').value = detail.logistics?.pro_tip || '';

        itineraryContainer.innerHTML = '';
        const itinerary = Array.isArray(detail.itinerary) ? detail.itinerary : [];
        if (itinerary.length === 0) {
            createItineraryDay({day_order: 1, title: 'Day 1', morning: '', afternoon: '', evening: ''});
        } else {
            itinerary.forEach(day => createItineraryDay(day));
        }
    }

    function showModal() {
        populateForm();
        editModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        editModal.style.display = 'none';
        document.body.style.overflow = '';
        const url = new URL(window.location.href);
        if (url.searchParams.has('open_edit')) {
            url.searchParams.delete('open_edit');
            window.history.replaceState({}, '', url.toString());
        }
    }

    editIdeaBtn.addEventListener('click', showModal);
    if (deleteIdeaBtn) {
        deleteIdeaBtn.addEventListener('click', async () => {
            if (!confirm('Delete this travel idea? This cannot be undone.')) {
                return;
            }
            deleteIdeaBtn.disabled = true;
            const originalText = deleteIdeaBtn.textContent;
            deleteIdeaBtn.textContent = 'Deleting...';
            try {
                const response = await fetch('./api/travel_ideas/delete_idea.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({ idea_id: detail.id })
                });
                const result = await response.json();
                if (result.success) {
                    window.location.href = 'travel-ideas.php';
                } else {
                    alert(result.message || 'Unable to delete travel idea.');
                }
            } catch (err) {
                alert('Unable to delete travel idea. Please try again.');
            } finally {
                deleteIdeaBtn.disabled = false;
                deleteIdeaBtn.textContent = originalText;
            }
        });
    }
    closeEditModal.addEventListener('click', closeModal);
    cancelEditBtn.addEventListener('click', closeModal);

    editModal.addEventListener('click', (event) => {
        if (event.target === editModal) {
            closeModal();
        }
    });

    const openEditParam = new URLSearchParams(window.location.search).get('open_edit');
    if (openEditParam === '1') {
        showModal();
    }

    document.getElementById('addDayBtn').addEventListener('click', () => {
        createItineraryDay({day_order: itineraryContainer.children.length + 1, title: `Day ${itineraryContainer.children.length + 1}`, morning: '', afternoon: '', evening: ''});
    });

    editForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const errorEl = document.getElementById('editIdeaError');
        errorEl.style.display = 'none';
        errorEl.textContent = '';
        const submitButton = editForm.querySelector('button[type="submit"]');
        const originalLabel = submitButton.textContent;
        submitButton.textContent = 'Saving...';
        submitButton.disabled = true;

        const formData = new FormData(editForm);
        try {
            const response = await fetch('api/travel_ideas/update_idea.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                closeModal();
                const url = new URL(window.location.href);
                url.searchParams.delete('open_edit');
                setTimeout(() => window.location.href = url.toString(), 200);
            } else {
                errorEl.textContent = data.message || 'Could not save changes.';
                errorEl.style.display = 'block';
            }
        } catch (err) {
            errorEl.textContent = 'Unable to save changes. Please try again.';
            errorEl.style.display = 'block';
        } finally {
            submitButton.textContent = originalLabel;
            submitButton.disabled = false;
        }
    });
    }
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
