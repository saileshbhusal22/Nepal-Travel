<?php 
/**
 
 * Author: Bijay Sapkota
 * Group: L5CG6
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$current_page = 'events.php';
include '../includes/header.php'; 
?>
<script>
    var isAdmin = <?php echo (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) ? 'true' : 'false'; ?>;
    var currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
</script>

<!-- Events Hero Section (Split Panel Design) -->
<style>
    /* Reset header for this page to have absolute transparent positioning */
    .site-header { position: absolute !important; top: 0; left: 0; width: 100%; z-index: 1000; background: transparent !important; border-bottom: none !important; }
    .main-nav-bar { background: transparent !important; }
    
    .nav-links a, .logo-wrapper span { text-shadow: 0 4px 6px rgba(0,0,0,0.6) !important; color: #fff !important; }
    .logo-wrapper span[style*="d32f2f"] { color: #f5a623 !important; } /* Make red text yellow for dark background */

    .events-hero-wrapper {
        display: flex;
        height: 100vh;
        width: 100%;
        overflow: hidden;
        position: relative;
    }

    /* Left Panel (Vertical Split Slider) */
    .eh-left-panels {
        display: flex;
        width: 55%;
        height: 100%;
    }

    .eh-panel {
        flex: 1;
        position: relative;
        overflow: hidden;
        transition: flex 0.5s ease;
    }
    
    .eh-panel:hover {
        flex: 1.5;
    }

    .eh-panel img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        position: absolute;
        top: 0;
        left: 0;a
        z-index: 1;
    }
    
    .eh-panel-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 40px 20px;
        background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.4) 50%, transparent 100%);
        z-index: 2;
        color: white;
    }

    /* Right Panel (Text & Controls) */
    .eh-right-panel {
        width: 45%;
        height: 100%;
        background-color: #fcfcfc;
        background-image: url('data:image/svg+xml;utf8,<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><filter id="noise"><feTurbulence type="fractalNoise" baseFrequency="0.8" numOctaves="4" stitchTiles="stitch"/></filter><rect width="100" height="100" filter="url(%23noise)" opacity="0.08"/></svg>');
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .eh-title-box {
        text-align: center;
        max-width: 90%;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    /* Vibrant staggered title boxes matching reference exactly */
    .eh-title-row-1, .eh-title-row-2, .eh-title-row-3 {
        display: inline-block;
        padding: 8px 45px;
        border-radius: 60px;
        color: white;
        font-family: 'Playfair Display', serif;
        margin-bottom: -15px; /* Overlap them like the reference */
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        z-index: 5;
        position: relative;
    }

    /* Animation Keyframes */
    @keyframes shimmer {
        0% { background-position: -468px 0; }
        100% { background-position: 468px 0; }
    }

    .skeleton-shimmer {
        background: #f6f7f8;
        background-image: linear-gradient(to right, #f6f7f8 0%, #edeef1 20%, #f6f7f8 40%, #f6f7f8 100%);
        background-repeat: no-repeat;
        background-size: 800px 100%; 
        display: inline-block;
        position: relative; 
        animation: shimmer 1.5s infinite linear;
    }

    .skeleton-card {
        border-radius: 16px;
        overflow: hidden;
        background: white;
        border: 1px solid #f0f0f0;
        height: 420px;
        display: flex;
        flex-direction: column;
    }

    .skeleton-image { height: 180px; width: 100%; }
    .skeleton-title { height: 24px; width: 70%; margin: 24px 24px 10px; border-radius: 4px; }
    .skeleton-text { height: 14px; width: 90%; margin: 0 24px 8px; border-radius: 4px; }
    .skeleton-footer { height: 45px; width: 100%; margin-top: auto; }

    /* Cinematic 16:9 Image Ratios */
    .event-card img {
        aspect-ratio: 16 / 9;
        object-fit: cover;
        width: 100%;
        height: auto;
    }

    .premium-btn {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .premium-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    /* Mobile Responsive Polish */
    @media (max-width: 900px) {
        .events-layout-container {
            grid-template-columns: 1fr !important;
        }
        .events-sidebar {
            position: relative !important;
            top: 0 !important;
            margin-bottom: 40px;
        }
        .events-sidebar iframe {
            width: 100% !important;
        }
        #eventsGrid {
            grid-template-columns: 1fr 1fr !important;
        }
    }

    @media (max-width: 600px) {
        #eventsGrid {
            grid-template-columns: 1fr !important;
        }
        .eh-title-row-1 { font-size: 48px; }
        .eh-title-row-2 { font-size: 36px; }
        .eh-title-row-3 { font-size: 28px; }
    }

    .eh-title-row-1 { 
        background: #cc3333; 
        font-size: 72px; 
        font-weight: 700;
        transform: rotate(-2deg); 
        letter-spacing: -2px;
        z-index: 3;
    }
    .eh-title-row-2 { 
        background: #6bb33b; 
        font-size: 56px; 
        font-weight: 700;
        transform: rotate(1deg); 
        letter-spacing: -1px;
        z-index: 4;
    }
    .eh-title-row-3 { 
        background: #5a3ba1; 
        font-size: 42px; 
        font-weight: 700;
        transform: rotate(-1deg); 
        z-index: 5;
    }

    /* Small decorative floating circle images */
    .eh-mascot {
        position: absolute;
        right: 30px;
        width: 90px;
        height: 90px;
        border-radius: 50%;
        border: 4px solid white;
        box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        overflow: hidden;
        z-index: 10;
        background: white;
    }
    
    .eh-mascot img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .eh-mascot-1 { bottom: 200px; }
    .eh-mascot-2 { bottom: 90px; }

    /* Form Multi-Section Styling */
    .form-section { display: none; animation: fadeIn 0.4s ease; }
    .form-section.active { display: block; }
    
    .step-indicator {
        height: 6px;
        flex: 1;
        background: #eee;
        border-radius: 3px;
        transition: all 0.3s;
    }
    .step-indicator.active { background: #285da1; }
    .step-indicator.completed { background: #6bb33b; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    #addEventModal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    align-items: flex-start;
    justify-content: center;
    overflow-y: auto;
    background: rgba(13, 27, 42, 0.98);
    padding: 40px;
}

#addEventModal.active {
    display: flex !important;
}

#successModal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 10000;
    align-items: center;
    justify-content: center;
    background: rgba(13, 27, 42, 0.98);
    padding: 40px;
}

#successModal.active {
    display: flex !important;
}

#subscriptionModal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    align-items: center;
    justify-content: center;
    background: rgba(13, 27, 42, 0.95);
    padding: 24px;
}

#subscriptionModal.active {
    display: flex !important;
}

.subscription-modal-card {
    position: relative;
    max-width: 440px;
    width: 100%;
    background: #fff;
    border-radius: 28px;
    padding: 48px 40px 36px;
    text-align: center;
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.25);
}

.subscription-modal-close {
    position: absolute;
    top: 18px;
    right: 18px;
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 50%;
    background: #f4f5f7;
    color: #555;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s, color 0.2s;
}

.subscription-modal-close:hover {
    background: #e8eaed;
    color: #111;
}

.subscription-modal-icon {
    font-size: 48px;
    margin-bottom: 16px;
    line-height: 1;
}

.subscription-modal-card h2 {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    margin: 0 0 12px;
    color: #111;
}

.subscription-modal-card p.subscription-modal-desc {
    color: #666;
    margin: 0 0 28px;
    font-size: 15px;
    line-height: 1.65;
    max-width: 340px;
    margin-left: auto;
    margin-right: auto;
}

.subscription-modal-cta {
    width: 100%;
    border: none;
    background: #285da1;
    color: #fff;
    font-weight: 800;
    letter-spacing: 0.08em;
    font-size: 13px;
    padding: 16px 24px;
    border-radius: 12px;
    cursor: pointer;
    transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
}

.subscription-modal-cta:hover {
    background: #1e4a85;
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(40, 93, 161, 0.35);
}

.subscription-modal-footnote {
    font-size: 11px;
    color: #999;
    margin: 20px 0 0;
    line-height: 1.5;
}
</style>

<section class="events-hero-wrapper">

    <!-- Left Split Image Panels -->
    <div class="eh-left-panels">
        <!-- Panel 1 -->
        <div class="eh-panel" style="border-right: 3px solid white;">
            <img src="../images/pokhara_lake.png" alt="Pokhara">
            <div class="eh-panel-overlay">
                <span style="font-size: 11px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase;">JAN - FEB 2026</span>
                <h3 style="font-family: 'Playfair Display', serif; font-size: 24px; margin: 10px 0;">Pokhara Boating Festival</h3>
            </div>
        </div>
        <!-- Panel 2 -->
        <div class="eh-panel" style="border-right: 3px solid white;">
            <img src="../images/bhaktapur_temple.png" alt="Event">
            <div class="eh-panel-overlay">
                <span style="font-size: 11px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase;">APRIL 2026</span>
                <h3 style="font-family: 'Playfair Display', serif; font-size: 24px; margin: 10px 0;">Bisket Jatra Festival</h3>
            </div>
        </div>
        <!-- Panel 3 -->
        <div class="eh-panel">
            <img src="../images/chitwan_rhino.png" alt="Chitwan Safari">
            <div class="eh-panel-overlay">
                <span style="font-size: 11px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase;">MAY 2026</span>
                <h3 style="font-family: 'Playfair Display', serif; font-size: 24px; margin: 10px 0;">Terai Cultural Parade</h3>
            </div>
        </div>
    </div>

    <!-- Right Content Panel -->
    <div class="eh-right-panel">
        
        <!-- Center Staggered Text -->
        <div class="eh-title-box">
            <div class="eh-title-row-1">Nepal</div>
            <div class="eh-title-row-2">Calendar of Events</div>
            <div class="eh-title-row-3">&amp; Festivals 2026</div>
        </div>

        <!-- Decorative Elements -->
        <div class="eh-mascot eh-mascot-1">
            <img src="../images/annapurna_trek.png" alt="Decorative">
        </div>
        <div class="eh-mascot eh-mascot-2">
            <img src="../images/food_drinks_nepal.png" alt="Decorative">
        </div>

        <a href="https://www.hamropatro.com/calendar" target="_blank" class="premium-btn" style="position: absolute; bottom: 40px; padding: 12px 30px; background: #285da1; color: white; border-radius: 30px; text-decoration: none; font-weight: 800; font-size: 11px; letter-spacing: 1px; display: flex; align-items: center; gap: 10px;">
            <img src="https://www.hamropatro.com/favicon.ico" style="width: 16px; height: 16px;">
            VIEW OFFICIAL HAMRO PATRO CALENDAR
        </a>
    </div>
</section>

<!-- General Events Feed -->
<section id="full-calendar" style="padding: 80px 0; background: #fff; font-family: 'Montserrat', sans-serif;">
    <div class="container events-layout-container" style="max-width: 1300px; display: grid; grid-template-columns: 280px 1fr; gap: 50px; align-items: start;">
        
        <!-- Left Sidebar Filter -->
        <aside class="events-sidebar" style="position: sticky; top: 120px; max-height: calc(100vh - 150px); overflow-y: auto; padding-right: 10px; scrollbar-width: thin;">
            
            <!-- Search -->
            <div style="margin-bottom: 30px;">
                <h4 style="font-size: 13px; font-weight: 800; color: #111; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 12px;">Search Happenings</h4>
                <div style="display: flex; background: #f4f5f7; border-radius: 6px; overflow: hidden; border: 1px solid #e0e0e0;">
                    <input type="text" id="eventSearchInput" placeholder="By Keywords" style="width: 100%; padding: 12px 15px; border: none; background: transparent; font-family: inherit; font-size: 14px; outline: none;">
                </div>
            </div>

            <!-- Categories -->
            <div style="margin-bottom: 30px;">
                <h4 style="font-size: 13px; font-weight: 800; color: #111; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 15px;">📁 Categories</h4>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php 
                    $fullCats = [
                        'ARTS & CULTURE' => 'Arts & Culture',
                        'FESTIVAL' => 'Festivals',
                        'NATURE' => 'Nature & Wildlife',
                        'SPORTS' => 'Sports & Adventure',
                        'FOOD' => 'Food & Cuisine',
                        'MUSIC' => 'Music & Concert',
                        'WORKSHOP' => 'Workshop & Education',
                        'RELIGIOUS' => 'Religious & Spiritual',
                        'PHOTOGRAPHY' => 'Photography & Art',
                        'NIGHTLIFE' => 'Nightlife & Entertainment'
                    ];
                    foreach($fullCats as $val => $label): ?>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; color: #444;">
                        <input type="checkbox" class="category-check" value="<?php echo $val; ?>" style="width: 16px; height: 16px; cursor: pointer;"> 
                        <?php echo $label; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Date / Time -->
            <div style="margin-bottom: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <h4 style="font-size: 13px; font-weight: 800; color: #111; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 15px;">📅 Date / Time</h4>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php 
                    $dateFilters = [
                        'all' => 'Anytime',
                        'today' => 'Today',
                        'weekend' => 'This Weekend',
                        'month' => 'This Month',
                        'upcoming' => 'Upcoming (3 Months)'
                    ];
                    foreach($dateFilters as $val => $label): ?>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; color: #444;">
                        <input type="radio" name="dateFilter" value="<?php echo $val; ?>" <?php echo $val === 'all' ? 'checked' : ''; ?> style="width: 16px; height: 16px; cursor: pointer;"> 
                        <?php echo $label; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Price -->
            <div style="margin-bottom: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <h4 style="font-size: 13px; font-weight: 800; color: #111; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 15px;">💰 Price</h4>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; color: #444;">
                        <input type="radio" name="priceFilter" value="all" checked style="width: 16px; height: 16px; cursor: pointer;"> Any Price
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; color: #444;">
                        <input type="radio" name="priceFilter" value="free" style="width: 16px; height: 16px; cursor: pointer;"> Free
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; color: #444;">
                        <input type="radio" name="priceFilter" value="0-500" style="width: 16px; height: 16px; cursor: pointer;"> Under NPR 500
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; color: #444;">
                        <input type="radio" name="priceFilter" value="500-2000" style="width: 16px; height: 16px; cursor: pointer;"> NPR 500 – 2000
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; color: #444;">
                        <input type="radio" name="priceFilter" value="2000+" style="width: 16px; height: 16px; cursor: pointer;"> Above NPR 2000
                    </label>
                </div>
            </div>

            <!-- Location -->
            <div style="margin-bottom: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <h4 style="font-size: 13px; font-weight: 800; color: #111; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 15px;">📍 Location</h4>
                <select id="locationFilter" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #e0e0e0; font-size: 13px; outline: none; background: #fff;">
                    <option value="all">All Regions</option>
                    <option value="Kathmandu">Kathmandu Valley</option>
                    <option value="Pokhara">Pokhara</option>
                    <option value="Chitwan">Chitwan</option>
                    <option value="Lumbini">Lumbini</option>
                    <option value="Mustang">Mustang</option>
                </select>
            </div>

            <!-- Ticket Availability -->
            <div style="margin-bottom: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <h4 style="font-size: 13px; font-weight: 800; color: #111; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 15px;">🎟️ Tickets</h4>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; color: #444;">
                        <input type="checkbox" class="ticket-status-check" value="Available" style="width: 16px; height: 16px; cursor: pointer;"> Available
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; color: #444;">
                        <input type="checkbox" class="ticket-status-check" value="Selling Fast" style="width: 16px; height: 16px; cursor: pointer;"> Selling Fast
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; color: #444; opacity: 0.5;">
                        <input type="checkbox" class="ticket-status-check" value="Sold Out" style="width: 16px; height: 16px; cursor: pointer;"> Sold Out
                    </label>
                </div>
            </div>

            <!-- Event Type -->
            <div style="margin-bottom: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <h4 style="font-size: 13px; font-weight: 800; color: #111; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 15px;">⭐ Event Type</h4>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; color: #444;">
                        <input type="checkbox" id="featuredOnlyCheck" style="width: 16px; height: 16px; cursor: pointer;"> Featured Only
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; color: #444;">
                        <input type="checkbox" id="intlOnlyCheck" style="width: 16px; height: 16px; cursor: pointer;"> International Events
                    </label>
                </div>
            </div>
            
            <button id="applyFiltersBtn" style="margin-top: 10px; width: 100%; padding: 14px; background: #111; color: white; border: none; border-radius: 6px; font-weight: 700; letter-spacing: 1px; cursor: pointer; transition: background 0.3s;" onmouseover="this.style.background='#333'" onmouseout="this.style.background='#111'">APPLY FILTERS</button>



            <!-- Featured Spotlight -->
            <div id="featuredSidebarSection" style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #eee; display: none;">
                <h4 style="font-size: 14px; font-weight: 800; color: #111; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 20px;">Featured Spotlight ✨</h4>
                <div id="featuredSidebarGrid"></div>
            </div>

            <!-- Community Integrations -->
            <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #eee;">
                <h4 style="font-size: 14px; font-weight: 800; color: #111; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 15px;">Integrations</h4>
                <a href="https://www.hamropatro.com/calendar" target="_blank" class="premium-btn" style="text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 11px; background: #285da1;">
                    <img src="https://www.hamropatro.com/favicon.ico" style="width: 14px; height: 14px;">
                    VIEW TRADITIONAL CALENDAR
                </a>
            </div>

            <!-- Premium Call to Action -->
            <div style="margin-top: 40px; background: #fdf8ef; padding: 30px; border-radius: 20px; border: 2px dashed #f5a623; text-align: center; box-shadow: 0 10px 30px rgba(245, 166, 35, 0.1); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <h4 style="font-family: 'Playfair Display', serif; font-size: 18px; font-weight: 800; color: #b45309; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 1px;">
                    <?php echo isset($_SESSION['user_id']) ? 'CREATOR HUB' : 'GROW YOUR AUDIENCE'; ?>
                </h4>
                <p style="font-size: 13px; color: #666; margin-bottom: 25px; line-height: 1.6;">
                    <?php echo isset($_SESSION['user_id']) ? 'Manage your festivals and reach thousands of travelers.' : 'Promote your festival or gathering to thousands of travelers.'; ?>
                </p>
                <button id="hostEventBtn" class="premium-btn" style="background: #f5a623; color: white; width: 100%; border: none; padding: 15px; border-radius: 30px; font-weight: 800; font-size: 12px; letter-spacing: 1px; cursor: pointer; box-shadow: 0 4px 15px rgba(245, 166, 35, 0.3);">HOST YOUR EVENT</button>
            </div>


            <!-- Admin Tools (Hidden unless admin) -->
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
            <div style="margin-top: 40px; background: #fff5e6; padding: 25px; border-radius: 12px; border: 1px dashed #f5a623;">
                <h4 style="font-size: 12px; font-weight: 800; color: #b45309; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 15px;">Admin Dashboard</h4>
                <button id="openAddEventModal" class="premium-btn" style="background: #f5a623; width: 100%; border: none; font-size: 12px;">+ ADD NEW EVENT</button>
                <p style="font-size: 11px; color: #b45309; margin-top: 10px; opacity: 0.8; text-align: center;">You have administrative access to modify the calendar.</p>
            </div>
            <?php endif; ?>
        </aside>

        <!-- Right Main Feed -->
        <main class="events-main">
            
            <!-- View Mode Toggles (Government vs Events) -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div style="display: flex; background: #f0f0f0; padding: 5px; border-radius: 40px; width: fit-content;">
                    <div class="view-mode-tab active" data-view="public" style="padding: 10px 25px; border-radius: 35px; cursor: pointer; font-size: 13px; font-weight: 800; transition: all 0.3s; background: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.05); color: #111;">EVENTS</div>
                    <div class="view-mode-tab" data-view="private" style="padding: 10px 25px; border-radius: 35px; cursor: pointer; font-size: 13px; font-weight: 800; transition: all 0.3s; color: #888;">GOVERNMENT EVENTS</div>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="view-mode-tab" data-view="my" style="padding: 10px 25px; border-radius: 35px; cursor: pointer; font-size: 13px; font-weight: 800; transition: all 0.3s; color: #888;">MY EVENTS</div>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 10px;">
                    <!-- Redundant Post Button Removed -->
                </div>
            </div>

            <!-- Month Tabs Navigation -->
            <div id="monthTabs" style="display: flex; gap: 25px; border-bottom: 1px solid #e0e0e0; margin-bottom: 40px; overflow-x: auto; padding-bottom: 0;">
                <?php 
                $months = ['ALL','JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
                foreach($months as $m) {
                    $isActive = ($m === 'ALL');
                    $borderStr = $isActive ? "border-bottom: 4px solid #f5a623;" : "border-bottom: 4px solid transparent;";
                    $colorStr = $isActive ? "color: #111; font-weight: 800;" : "color: #888; font-weight: 600;";
                    
                    echo "<div class='month-tab' data-month='$m' style='padding: 0 5px 15px; cursor: pointer; text-transform: uppercase; font-size: 14px; transition: all 0.2s; white-space: nowrap; $colorStr $borderStr'>$m</div>";
                }
                ?>
            </div>

            <!-- Events Grid -->
            <!-- Events Grid -->
            <div id="eventsGrid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; min-height: 400px;">
                <!-- Dynamically populated by events.js -->
            </div>

                <div style="text-align: center; margin-top: 60px; display: none;">
                <button id="loadMoreEvents" style="padding: 18px 45px; background: white; color: #111; border: 1px solid #eee; border-radius: 50px; font-size: 13px; font-weight: 800; letter-spacing: 2px; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-transform: uppercase;">VIEW MORE EVENTS</button>
            </div>

        </main>
 <!-- Add Event Modal (8-Section Premium Workflow) -->
<div id="addEventModal" class="search-overlay" style="background: rgba(13, 27, 42, 0.98); padding: 40px; overflow-y: auto;">
     <div class="container" style="max-width: 1200px; background: white; border-radius: 30px; padding: 0; position: relative; display: flex; overflow: hidden; min-height: 800px; box-shadow: 0 25px 50px rgba(0,0,0,0.3);">
        
        <!-- Left: Form Sections -->
        <div id="formScrollContainer" style="flex: 1.2; padding: 50px; border-right: 1px solid #eee; background: #fff; overflow-y: auto; max-height: 90vh;">
            <button id="closeEventModal" style="position: absolute; top: 25px; left: 25px; background: #f4f5f7; border: none; cursor: pointer; font-size: 20px; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; z-index: 10;">×</button>
            
            <div style="margin-bottom: 40px; padding-top: 20px;">
                <h2 style="font-family: 'Playfair Display', serif; color: #111; font-size: 32px; margin-bottom: 10px;">Host Your Event</h2>
                <div style="width: 100%; background: #f0f0f0; height: 6px; border-radius: 10px; margin-top: 25px; position: relative;">
                    <div id="formProgressFill" style="position: absolute; top: 0; left: 0; width: 12.5%; height: 100%; background: #F5A623; border-radius: 10px; transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);"></div>
                </div>
                <div id="sectionTitle" style="margin-top: 15px; font-size: 11px; font-weight: 800; color: #F5A623; letter-spacing: 2px; text-transform: uppercase;">Section 1: Basic Information</div>
            </div>

            <form id="addEventForm" enctype="multipart/form-data" novalidate>
                <!-- Section 1: Basic Info -->
                <div class="form-section active" data-section="1">
                    <h3 style="font-size: 18px; font-weight: 800; color: #111; margin-bottom: 25px; border-left: 4px solid #F5A623; padding-left: 15px;">1. Basic Information</h3>
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px; letter-spacing: 1px;">EVENT TITLE *</label>
                        <input type="text" name="title" id="form_title" placeholder="e.g. Kathmandu Jazz Nights" required style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; font-size: 15px; outline: none; transition: all 0.3s;" onfocus="this.style.borderColor='#F5A623'">
                    </div>
                    <div style="margin-bottom: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">CATEGORY</label>
                            <select name="category" id="form_category" style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; font-size: 14px; outline: none;">
                                <option value="FESTIVAL">Festival</option>
                                <option value="CONCERT">Concert</option>
                                <option value="WORKSHOP">Workshop & Education</option>
                                <option value="FOOD">Food & Cuisine</option>
                                <option value="SPORTS">Sports & Adventure</option>
                                <option value="NATURE">Nature & Wildlife</option>
                                <option value="ARTS">Arts & Culture</option>
                                <option value="RELIGIOUS">Religious & Spiritual</option>
                                <option value="NIGHTLIFE">Nightlife</option>
                                <option value="PHOTOGRAPHY">Photography</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">EVENT TYPE</label>
                            <div style="display: flex; gap: 15px; padding-top: 10px;">
                                <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; cursor: pointer;">
                                    <input type="radio" name="event_type" value="Community" checked style="accent-color: #F5A623;"> Community
                                </label>
                                <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; cursor: pointer;">
                                    <input type="radio" name="event_type" value="Government" style="accent-color: #F5A623;"> Government
                                </label>
                                <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; cursor: pointer;">
                                    <input type="radio" name="event_type" value="International" style="accent-color: #F5A623;"> International
                                </label>
                            </div>
                        </div>
                    </div>
                    <div style="margin-bottom: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">LANGUAGE</label>
                            <select name="language" style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; font-size: 14px; outline: none;">
                                <option value="Both">Both (Nepali & English)</option>
                                <option value="Nepali">Nepali Only</option>
                                <option value="English">English Only</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">AGE GROUP</label>
                            <div style="display: flex; gap: 15px; padding-top: 10px;">
                                <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; cursor: pointer;">
                                    <input type="radio" name="age_group" value="All Ages" checked style="accent-color: #F5A623;"> All Ages
                                </label>
                                <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; cursor: pointer;">
                                    <input type="radio" name="age_group" value="Family" style="accent-color: #F5A623;"> Family
                                </label>
                                <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; cursor: pointer;">
                                    <input type="radio" name="age_group" value="Adults" style="accent-color: #F5A623;"> Adults Only
                                </label>
                            </div>
                        </div>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">TAGS / KEYWORDS</label>
                        <input type="text" name="tags" id="tagsInput" placeholder="e.g. music, festival, kathmandu" style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; font-size: 14px; outline: none;">
                        <p style="font-size: 10px; color: #999; margin-top: 5px;">Separate with commas for automatic tagging.</p>
                    </div>
                </div>

                <!-- Section 2: Media -->
                <div class="form-section" data-section="2">
                    <h3 style="font-size: 18px; font-weight: 800; color: #111; margin-bottom: 25px; border-left: 4px solid #F5A623; padding-left: 15px;">2. Media Assets</h3>
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">COVER IMAGE OR GIF * <span style="color:#e51a2a;">(required)</span></label>
                        <input type="file" name="image" id="form_image_input" accept="image/*" style="display: none;">
                        <div id="dropZone" style="border: 2px dashed #eee; border-radius: 20px; padding: 40px; text-align: center; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#F5A623'; this.style.background='#fffdf5'" onmouseout="this.style.borderColor='#eee'; this.style.background='transparent'">
                            <div style="font-size: 40px; margin-bottom: 15px;">🖼️</div>
                            <p style="font-size: 14px; font-weight: 700; color: #111;">Drag & Drop or <span style="color: #F5A623;">Browse</span></p>
                            <p style="font-size: 11px; color: #999; margin-top: 5px;">Max 5MB. Supports JPG, PNG, GIF.</p>
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 12px;">GALLERY IMAGES (UP TO 5) - OPTIONAL</label>
                        <div id="galleryUploadContainer" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px;">
                            <?php for($i=0; $i<5; $i++): ?>
                            <div class="gallery-slot" data-index="<?php echo $i; ?>" style="aspect-ratio: 1; background: #f4f5f7; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #ccc; cursor: pointer; border: 1.5px dashed #eee; overflow: hidden; position: relative;">
                                <span class="plus-icon">+</span>
                                <input type="hidden" name="gallery_existing[<?php echo $i; ?>]" class="gallery-existing-input" value="">
                                <input type="file" name="gallery_images[]" class="gallery-input" accept="image/*" style="display: none;">
                                <img class="gallery-preview" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                                <button type="button" class="remove-gallery-img" style="display: none; position: absolute; top: 5px; right: 5px; background: rgba(229, 26, 42, 0.8); color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer; z-index: 5;">×</button>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <p style="font-size: 10px; color: #999; margin-top: 10px;">Select up to 5 additional images for your event gallery. If you skip the cover box above, the first gallery photo will be used as the cover.</p>
                    </div>
                </div>

                <!-- Section 3: Description -->
                <div class="form-section" data-section="3">
                    <h3 style="font-size: 18px; font-weight: 800; color: #111; margin-bottom: 25px; border-left: 4px solid #F5A623; padding-left: 15px;">3. Event Description</h3>
                    <div style="margin-bottom: 25px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <label style="font-size: 11px; font-weight: 800; color: #999; letter-spacing: 1px;">SHORT DESCRIPTION *</label>
                            <span id="shortDescCount" style="font-size: 10px; font-weight: 800; color: #F5A623;">0 / 150</span>
                        </div>
                        <textarea name="description" id="form_desc" required maxlength="150" placeholder="A catchy one-liner for the card preview..." style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; height: 100px; resize: none; font-size: 14px; outline: none; transition: all 0.3s;"></textarea>
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <label style="font-size: 11px; font-weight: 800; color: #999; letter-spacing: 1px;">WHAT TO EXPECT (DETAILED) *</label>
                            <span id="detailedDescCount" style="font-size: 10px; font-weight: 800; color: #F5A623;">0 / 500</span>
                        </div>
                        <textarea name="what_to_expect" id="form_what_to_expect" required maxlength="500" placeholder="Give more details about the vibe, schedule, and rules..." style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; height: 180px; resize: none; font-size: 14px; outline: none; transition: all 0.3s;"></textarea>
                    </div>
                </div>

                <!-- Section 4: Date & Time -->
                <div class="form-section" data-section="4">
                    <h3 style="font-size: 18px; font-weight: 800; color: #111; margin-bottom: 25px; border-left: 4px solid #F5A623; padding-left: 15px;">4. Date & Time</h3>
                    <div style="margin-bottom: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">START DATE *</label>
                            <input type="date" name="start_date" id="form_date" required style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; outline: none;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">END DATE</label>
                            <input type="date" name="end_date" style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; outline: none;">
                        </div>
                    </div>
                    <div style="margin-bottom: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">START TIME</label>
                            <input type="time" name="start_time" style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; outline: none;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">END TIME</label>
                            <input type="time" name="end_time" style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; outline: none;">
                        </div>
                    </div>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 16px;">
                        <label style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                            <span style="font-weight: 800; font-size: 13px; color: #111;">RECURRING EVENT</span>
                            <div style="position: relative;">
                                <input type="checkbox" name="is_recurring" id="recurringToggle" style="display: none;">
                                <div id="toggleTrack" style="width: 50px; height: 26px; background: #eee; border-radius: 20px; position: relative; transition: 0.3s;">
                                    <div id="toggleKnob" style="width: 20px; height: 20px; background: white; border-radius: 50%; position: absolute; top: 3px; left: 3px; transition: 0.3s; box-shadow: 0 2px 5px rgba(0,0,0,0.1);"></div>
                                </div>
                            </div>
                        </label>
                        <div id="recurringOptions" style="display: none; margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                            <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">FREQUENCY</label>
                            <select name="recurring_frequency" style="width: 100%; padding: 12px; border: 1.5px solid #eee; border-radius: 12px;">
                                <option value="Daily">Daily</option>
                                <option value="Weekly">Weekly</option>
                                <option value="Monthly">Monthly</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Location -->
                <div class="form-section" data-section="5">
                    <h3 style="font-size: 18px; font-weight: 800; color: #111; margin-bottom: 25px; border-left: 4px solid #F5A623; padding-left: 15px;">5. Location Details</h3>
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">VENUE / LOCATION NAME *</label>
                        <input type="text" name="venue_name" id="form_venue" placeholder="e.g. Garden of Dreams" required style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; outline: none;">
                    </div>
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">FULL ADDRESS</label>
                        <input type="text" name="full_address" placeholder="e.g. Tridevi Marg, Thamel, Kathmandu" style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; outline: none;">
                    </div>
                    <div style="margin-bottom: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">REGION / DISTRICT</label>
                            <select name="region" style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; outline: none;">
                                <option value="Kathmandu Valley">Kathmandu Valley</option>
                                <option value="Pokhara">Pokhara</option>
                                <option value="Chitwan">Chitwan</option>
                                <option value="Lumbini">Lumbini</option>
                                <option value="Mustang">Mustang</option>
                                <option value="Other">Other Regions</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">GOOGLE MAPS LINK</label>
                            <input type="text" name="google_maps_link" placeholder="https://goo.gl/maps/..." style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; outline: none;">
                        </div>
                    </div>
                </div>

                <!-- Section 6: Tickets -->
                <div class="form-section" data-section="6">
                    <h3 style="font-size: 18px; font-weight: 800; color: #111; margin-bottom: 25px; border-left: 4px solid #F5A623; padding-left: 15px;">6. Ticketing & Capacity</h3>
                    <div style="margin-bottom: 25px; background: #f8f9fa; padding: 20px; border-radius: 16px; display: flex; gap: 30px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 700; font-size: 14px;">
                            <input type="radio" name="is_paid" value="0" checked onchange="togglePricing(false)" style="accent-color: #F5A623;"> FREE EVENT
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 700; font-size: 14px;">
                            <input type="radio" name="is_paid" value="1" onchange="togglePricing(true)" style="accent-color: #F5A623;"> PAID EVENT
                        </label>
                    </div>
                    <div id="pricingInput" style="display: none; margin-bottom: 25px; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">PRICE (NPR) *</label>
                            <input type="number" name="price_npr" id="form_price" placeholder="500" style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; outline: none;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">BOOKING LINK</label>
                            <input type="text" name="registration_url" placeholder="https://..." style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; outline: none;">
                        </div>
                    </div>
                    <div style="margin-bottom: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: end;">
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">TOTAL SEATS</label>
                            <input type="number" name="seats" placeholder="100" style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px;">
                        </div>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding-bottom: 15px; font-size: 13px; font-weight: 700;">
                            <input type="checkbox" name="unlimited_seats" checked style="accent-color: #F5A623;"> UNLIMITED SEATS
                        </label>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">'SELLING FAST' ALERT THRESHOLD (%)</label>
                        <input type="number" name="selling_fast_threshold" value="80" style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px;">
                        <p style="font-size: 10px; color: #999; margin-top: 5px;">We'll show a "Selling Fast" badge when tickets hit this % capacity.</p>
                    </div>
                </div>

                <!-- Section 7: Organizer -->
                <div class="form-section" data-section="7">
                    <h3 style="font-size: 18px; font-weight: 800; color: #111; margin-bottom: 25px; border-left: 4px solid #F5A623; padding-left: 15px;">7. Organizer Info</h3>
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">ORGANIZER NAME *</label>
                        <input type="text" name="organizer_name" required placeholder="e.g. Visit Nepal Events Team" style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px; outline: none;">
                    </div>
                    <div style="margin-bottom: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">CONTACT NUMBER</label>
                            <input type="text" name="organizer_contact" placeholder="+977..." style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">EMAIL ADDRESS</label>
                            <input type="email" name="organizer_email" placeholder="hello@organizer.com" style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px;">
                        </div>
                    </div>
                    <div style="margin-bottom: 10px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">WEBSITE</label>
                            <input type="text" name="organizer_website" placeholder="https://..." style="width: 100%; padding: 15px; border: 1.5px solid #eee; border-radius: 12px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 11px; font-weight: 800; color: #999; margin-bottom: 8px;">SOCIAL LINKS</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" name="organizer_facebook" placeholder="Facebook Link" style="width: 50%; padding: 10px; border: 1.5px solid #eee; border-radius: 8px; font-size: 12px;">
                                <input type="text" name="organizer_instagram" placeholder="Instagram Link" style="width: 50%; padding: 10px; border: 1.5px solid #eee; border-radius: 8px; font-size: 12px;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 8: Premium -->
                <div class="form-section" data-section="8">
                    <h3 style="font-size: 18px; font-weight: 800; color: #b45309; margin-bottom: 25px; border-left: 4px solid #F5A623; padding-left: 15px; display: flex; align-items: center; gap: 10px;">
                        <span>8. 🌟 Premium Promotion</span>
                        <span style="font-size: 10px; background: #fff5e6; color: #F5A623; padding: 4px 10px; border-radius: 20px;">OPTIONAL</span>
                    </h3>
                    
                    <div style="border: 2px dashed #f5a623; background: #fffdf5; padding: 30px; border-radius: 20px; margin-bottom: 25px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                            <label style="display: flex; align-items: center; justify-content: space-between; background: white; padding: 15px; border-radius: 12px; border: 1px solid #fed7aa; cursor: pointer;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 20px;">🏅</span>
                                    <span style="font-weight: 800; font-size: 13px; color: #b45309;">FEATURED BADGE</span>
                                </div>
                                <input type="checkbox" name="is_featured" id="form_featured_check" style="width: 18px; height: 18px; accent-color: #F5A623;">
                            </label>
                            <label style="display: flex; align-items: center; justify-content: space-between; background: white; padding: 15px; border-radius: 12px; border: 1px solid #fed7aa; cursor: pointer;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 20px;">🚀</span>
                                    <span style="font-weight: 800; font-size: 13px; color: #b45309;">HOME SPOTLIGHT</span>
                                </div>
                                <input type="checkbox" name="homepage_spotlight" style="width: 18px; height: 18px; accent-color: #F5A623;">
                            </label>
                        </div>

                        <!-- Raffle Setup -->
                        <div style="background: #fff; border: 1px solid #fed7aa; padding: 25px; border-radius: 16px; margin-bottom: 20px;">
                            <label style="display: flex; align-items: center; gap: 10px; font-weight: 800; color: #111; margin-bottom: 20px; cursor: pointer;">
                                <input type="checkbox" name="raffle_enabled" id="raffleToggle" style="accent-color: #F5A623;"> 
                                ENABLE RAFFLE / PRIZE DRAW 🎁
                            </label>
                            <div id="raffleFields" style="display: none; border-top: 1.5px solid #fdf8ef; padding-top: 20px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                    <div>
                                        <label style="display: block; font-size: 10px; font-weight: 800; color: #999; margin-bottom: 8px;">DRAW TIME</label>
                                        <input type="datetime-local" name="raffle_draw_time" style="width: 100%; padding: 12px; border: 1.5px solid #eee; border-radius: 10px;">
                                    </div>
                                    <div>
                                        <label style="display: block; font-size: 10px; font-weight: 800; color: #999; margin-bottom: 8px;">RAFFLE ENTRY FEE (NPR)</label>
                                        <input type="number" name="raffle_entry_fee" placeholder="0" style="width: 100%; padding: 12px; border: 1.5px solid #eee; border-radius: 10px;">
                                    </div>
                                </div>
                                <div style="margin-bottom: 20px;">
                                    <label style="display: block; font-size: 10px; font-weight: 800; color: #999; margin-bottom: 8px;">1ST GRAND PRIZE</label>
                                    <input type="text" name="raffle_prize_1" placeholder="e.g. NPR 1,00,000 Cash" style="width: 100%; padding: 12px; border: 1.5px solid #eee; border-radius: 10px;">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 10px; font-weight: 800; color: #999; margin-bottom: 8px;">2ND GRAND PRIZE</label>
                                    <input type="text" name="raffle_prize_2" placeholder="e.g. Round Trip to Pokhara" style="width: 100%; padding: 12px; border: 1.5px solid #eee; border-radius: 10px;">
                                </div>
                            </div>
                        </div>

                        <label style="display: flex; align-items: center; gap: 12px; font-weight: 800; color: #111; cursor: pointer; padding: 10px;">
                            <input type="checkbox" name="free_parking" value="1" style="width: 20px; height: 20px; accent-color: #F5A623;">
                            <span>INCLUDES FREE PARKING 🅿️</span>
                        </label>
                    </div>
                </div>

                <!-- Hidden Controls -->
                <input type="hidden" name="month" id="form_month" value="JAN">
                <input type="hidden" name="id" value="">
                <input type="hidden" name="image_path" value="">
                <input type="hidden" name="gallery_images" value="">
                <input type="hidden" name="is_premium" id="isPremiumHidden" value="0">

                <!-- Navigation Buttons -->
                <div style="margin-top: 50px; display: flex; justify-content: space-between; align-items: center; padding-top: 30px; border-top: 1px solid #eee;">
                    <button type="button" id="prevStepBtn" style="visibility: hidden; padding: 16px 35px; background: #f4f5f7; color: #666; border: none; border-radius: 35px; font-weight: 700; cursor: pointer; transition: 0.3s;">BACK</button>
                    <button type="button" id="nextStepBtn" style="padding: 16px 45px; background: #111; color: white; border: none; border-radius: 35px; font-weight: 800; letter-spacing: 1px; cursor: pointer; transition: 0.3s;" onmouseover="this.style.background='#333'" onmouseout="this.style.background='#111'">NEXT STEP</button>
                    <button type="submit" id="submitEventBtn" style="display: none; padding: 16px 50px; background: #F5A623; color: white; border: none; border-radius: 35px; font-weight: 800; letter-spacing: 1px; cursor: pointer; transition: 0.3s; box-shadow: 0 10px 25px rgba(245, 166, 35, 0.4);">PUBLISH EVENT</button>
                </div>
            </form>
        </div>

        <!-- Right: Live Preview -->
        <div style="flex: 0.8; padding: 60px; background: #f4f5f7; display: flex; flex-direction: column; align-items: center; justify-content: center; position: sticky; top: 0;">
            <div style="width: 100%; text-align: center; margin-bottom: 40px;">
                <span style="font-size: 11px; font-weight: 800; color: #999; letter-spacing: 3px; text-transform: uppercase;">Real-Time Preview</span>
            </div>
            
            <div id="livePreviewCard" class="event-card" style="width: 380px; border: 2px solid #f0f0f0; border-radius: 24px; overflow: hidden; background: white; box-shadow: 0 30px 60px rgba(0,0,0,0.1); transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1); transform: scale(1);">
                <div style="height: 220px; background: #eee; position: relative; overflow: hidden;">
                    <img id="preview_img" src="../images/placeholder_event.jpg" style="width: 100%; height: 100%; object-fit: cover;">
                    <div id="preview_cat_badge" style="position: absolute; bottom: 20px; left: 20px; background: #285da1; color: white; padding: 8px 18px; font-size: 10px; font-weight: 1000; border-radius: 40px; text-transform: uppercase; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
                        Festival
                    </div>
                    <div id="preview_featured_badge" style="display: none; position: absolute; top: 20px; right: 20px; background: #f5a623; color: white; padding: 6px 15px; font-size: 10px; font-weight: 800; border-radius: 6px; box-shadow: 0 4px 10px rgba(245, 166, 35, 0.3);">✨ FEATURED</div>
                </div>
                <div style="padding: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <h3 id="preview_title_text" style="font-size: 24px; margin: 0; color: #111; font-family: 'Playfair Display', serif; line-height: 1.2;">Your Event Title</h3>
                    </div>
                    <p id="preview_desc_text" style="color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 25px; height: 45px; overflow: hidden;">Add a catchy one-liner in Section 3 to see it appear here in the real-time preview card...</p>
                    
                    <div id="preview_ticket_box" style="background: #fdf8ef; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 11px; font-weight: 800; color: #b45309; text-align: center; border: 1px solid #fed7aa; text-transform: uppercase; letter-spacing: 1px;">
                        TICKETS: FREE | UNLIMITED SEATS
                    </div>
                    
                    <div style="width: 100%; background: #f4f5f7; color: #111; padding: 15px; text-align: center; font-size: 12px; border-radius: 12px; font-weight: 800; letter-spacing: 1px; border: 1px solid #eee;">VIEW DETAILS</div>
                </div>
                <div id="preview_date_bar" style="background: #f4f5f7; padding: 20px; text-align: center; font-size: 14px; font-weight: 800; border-top: 1px solid #eee; text-transform: uppercase; letter-spacing: 1px;">
                    SELECT A DATE
                </div>
            </div>

            <div style="margin-top: 50px; padding: 25px; background: #fff; border-radius: 20px; width: 100%; max-width: 380px; border: 1px solid #eee; text-align: center;">
                <p style="font-size: 12px; color: #888; line-height: 1.6; margin: 0;">This preview reflects your final listing appearance. Premium sections like <b>Featured Badges</b> or <b>Raffles</b> will add special elements to this card.</p>
            </div>
        </div>
     </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="search-overlay" style="background: rgba(13, 27, 42, 0.98); padding: 40px; align-items: center; justify-content: center;">
    <div class="container" style="max-width: 500px; background: white; border-radius: 30px; padding: 50px; text-align: center; box-shadow: 0 30px 60px rgba(0,0,0,0.4);">
        <div style="font-size: 80px; margin-bottom: 30px;">✅</div>
        <h2 style="font-family: 'Playfair Display', serif; font-size: 32px; margin-bottom: 15px;">Success!</h2>
        <p style="color: #666; margin-bottom: 40px; font-size: 16px; line-height: 1.6;">Your event has been submitted for review! Our team will verify the details and it will be live on the Visit Nepal calendar shortly.</p>
        <button type="button" id="returnToCalendarBtn" style="width: 100%; padding: 18px; border: none; background: #111; color: white; border-radius: 40px; font-weight: 800; letter-spacing: 1px; cursor: pointer;">RETURN TO CALENDAR</button>
    </div>
</div>
   </div>
     </div>
</div>

<!-- Subscription Modal -->
<div id="subscriptionModal" class="search-overlay">
    <div class="subscription-modal-card">
        <button type="button" id="closeSubscriptionModal" class="subscription-modal-close" aria-label="Close">&times;</button>
        <div class="subscription-modal-icon" aria-hidden="true">🗓️</div>
        <h2>List Your Event</h2>
        <p class="subscription-modal-desc">Join Nepal's premier travel calendar. Reach thousands of travelers and locals by upgrading to a contributor account.</p>
        <button type="button" id="simulateSubscribeBtn" class="subscription-modal-cta">SUBSCRIBE NOW (5/mo)</button>
        <p class="subscription-modal-footnote">* Subscription allows you to post unlimited public events.</p>
    </div>
</div>

<!-- Include Events Frontend Logic -->
<script src="../assets/js/events.js?v=<?= time() ?>"></script>

<?php include '../includes/footer.php'; ?>