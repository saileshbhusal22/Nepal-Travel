<?php 
$current_page = 'events.php';
include __DIR__ . '/../includes/header.php'; 
?>

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
        left: 0;
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

    .eh-nav-btn {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: white;
        border: 2px solid #285da1;
        color: #285da1;
        display: flex;
        justify-content: center;
        align-items: center;
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        z-index: 100; /* Needs to be above everything */
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .eh-nav-btn:hover { background: #285da1; color: white; }
    .eh-prev { left: 20px; }
    .eh-next { right: 20px; }
    
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

</style>

<section class="events-hero-wrapper">
    <!-- Overlay control arrows -->
    <button class="eh-nav-btn eh-prev">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 19l-7-7 7-7"/></svg>
    </button>
    <button class="eh-nav-btn eh-next">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5l7 7-7 7"/></svg>
    </button>

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

        <a href="#full-calendar" style="position: absolute; bottom: 30px; text-decoration: underline; color: #285da1; font-weight: 700; font-size: 13px; letter-spacing: 1px;">DOWNLOAD CALENDAR PDF</a>
    </div>
</section>

<!-- General Events Feed -->
<section id="full-calendar" style="padding: 80px 0; background: #fff; font-family: 'Montserrat', sans-serif;">
    <div class="container" style="max-width: 1300px; display: grid; grid-template-columns: 280px 1fr; gap: 50px; align-items: start;">
        
        <!-- Left Sidebar Filter -->
        <aside class="events-sidebar" style="position: sticky; top: 120px;">
            <div style="margin-bottom: 30px;">
                <h4 style="font-size: 14px; font-weight: 800; color: #111; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 15px;">Search Happenings</h4>
                <div style="display: flex; background: #f4f5f7; border-radius: 6px; overflow: hidden; border: 1px solid #e0e0e0;">
                    <input type="text" placeholder="By Keywords" style="width: 100%; padding: 12px 15px; border: none; background: transparent; font-family: inherit; font-size: 14px; outline: none;">
                    <button style="background: transparent; border: none; padding: 0 15px; cursor: pointer;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                </div>
            </div>

            <!-- Date Picker Filter -->
            <div style="margin-bottom: 30px;">
                <label for="event-date-picker" style="font-size: 14px; font-weight: 800; color: #111; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 10px; display: block;">Select Date</label>
                <input type="date" id="event-date-picker" style="width: 100%; padding: 12px 15px; border: 1px solid #e0e0e0; border-radius: 6px; background: #f4f5f7; font-family: inherit; font-size: 14px; color: #111; transition: all 0.2s ease; box-sizing: border-box; cursor: pointer;" />
                <div style="margin-top: 8px; font-size: 12px; color: #888;">Select a date to filter events</div>
            </div>

            <div>
                <h4 style="font-size: 14px; font-weight: 800; color: #111; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 15px;">Categories</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px;">
                    <li style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="cat-arts" style="width: 18px; height: 18px; cursor: pointer;"> 
                        <label for="cat-arts" style="font-size: 14px; color: #444; font-weight: 500; cursor: pointer;">Arts &amp; Culture</label>
                    </li>
                    <li style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="cat-festivals" checked style="width: 18px; height: 18px; cursor: pointer;"> 
                        <label for="cat-festivals" style="font-size: 14px; color: #111; font-weight: 700; cursor: pointer;">Festivals</label>
                    </li>
                    <li style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="cat-nature" style="width: 18px; height: 18px; cursor: pointer;"> 
                        <label for="cat-nature" style="font-size: 14px; color: #444; font-weight: 500; cursor: pointer;">Nature</label>
                    </li>
                    <li style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="cat-sports" style="width: 18px; height: 18px; cursor: pointer;"> 
                        <label for="cat-sports" style="font-size: 14px; color: #444; font-weight: 500; cursor: pointer;">Sports &amp; Adventure</label>
                    </li>
                </ul>
            </div>
            
            <button id="applyFiltersBtn" style="margin-top: 30px; width: 100%; padding: 14px; background: #111; color: white; border: none; border-radius: 6px; font-weight: 700; letter-spacing: 1px; cursor: pointer;">APPLY FILTERS</button>
        </aside>

        <!-- Right Main Feed -->
        <main class="events-main">
            
            <!-- Month Tabs Navigation -->
            <div style="display: flex; gap: 25px; border-bottom: 1px solid #e0e0e0; margin-bottom: 40px; overflow-x: auto; padding-bottom: 0;">
                <?php 
                $months = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
                foreach($months as $m) {
                    $isActive = ($m === 'MAY');
                    $borderStr = $isActive ? "border-bottom: 4px solid #f5a623;" : "border-bottom: 4px solid transparent;";
                    $colorStr = $isActive ? "color: #111; font-weight: 800;" : "color: #888; font-weight: 600;";
                    
                    echo "<div class='month-tab' data-month='$m' style='padding: 0 5px 15px; cursor: pointer; text-transform: uppercase; font-size: 14px; transition: all 0.2s; $colorStr $borderStr' onclick=\"filterByMonth(this)\">$m</div>";
                }
                ?>
            </div>

            <!-- Events Grid -->
            <div class="events-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
                <!-- Dummy Event Data -->
                <?php 
                $events = [
                    ['img' => '../images/chitwan_rhino.png', 'badge' => 'FESTIVAL', 'badge_color' => '#6bb33b', 'title' => 'Buddha Jayanti Celebration', 'desc' => 'Join the grand spiritual celebration at the birthplace of Lord Buddha.', 'date' => '12 MAY 2026', 'day' => 12],
                    ['img' => '../images/pokhara_lake.png', 'badge' => 'SPORTS', 'badge_color' => '#285da1', 'title' => 'Phewa Lake Regatta', 'desc' => 'An exciting week of watersports and competitive boating over the pristine lake.', 'date' => '14 - 18 MAY 2026', 'day' => '14-18'],
                    ['img' => '../images/bhaktapur_temple.png', 'badge' => 'ARTS & CULTURE', 'badge_color' => '#cc3333', 'title' => 'Bhaktapur Pottery Expo', 'desc' => 'Witness master artisans crafting the finest terracotta pottery live.', 'date' => '20 MAY 2026', 'day' => 20],
                    ['img' => '../images/annapurna_trek.png', 'badge' => 'NATURE', 'badge_color' => '#5a3ba1', 'title' => 'Annapurna Spring Marathon', 'desc' => 'High-altitude endurance race trails crossing massive suspension bridges.', 'date' => '25 MAY 2026', 'day' => 25],
                    ['img' => '../images/food_drinks_nepal.png', 'badge' => 'FESTIVAL', 'badge_color' => '#6bb33b', 'title' => 'Kathmandu Momo Fiesta', 'desc' => 'Taste over 50 varieties of traditional dumplings across Thamel.', 'date' => '28 - 30 MAY 2026', 'day' => '28-30'],
                    ['img' => '../images/family_fun_nepal.png', 'badge' => 'ARTS & CULTURE', 'badge_color' => '#cc3333', 'title' => 'Patan Puppet Carnival', 'desc' => 'A magical evening of traditional string puppetry for the whole family.', 'date' => '31 MAY 2026', 'day' => 31]
                ];
                
                foreach($events as $e):
                ?>
                <div class="event-card" data-day="<?php echo $e['day']; ?>" style="border-radius: 16px; overflow: hidden; background: white; border: 1px solid #f0f0f0; display: flex; flex-direction: column; transition: transform 0.3s ease, box-shadow 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.03);" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 15px 35px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.03)';">
                    
                    <!-- Cover Image -->
                    <div style="position: relative; height: 180px; width: 100%;">
                        <img src="<?php echo $e['img']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <!-- Category Badge floating on image -->
                        <div style="position: absolute; top: 15px; left: 15px; background: <?php echo $e['badge_color']; ?>; color: white; padding: 6px 14px; font-size: 10px; font-weight: 800; border-radius: 30px; letter-spacing: 1px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                            <?php echo $e['badge']; ?>
                        </div>
                    </div>

                    <!-- Body Content -->
                    <div style="padding: 24px; display: flex; flex-direction: column; flex-grow: 1;">
                        <h3 style="font-size: 18px; margin: 0 0 10px; color: #111; font-weight: 800; line-height: 1.3; font-family: 'Playfair Display', serif;"><?php echo $e['title']; ?></h3>
                        <p style="color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 25px;"><?php echo $e['desc']; ?></p>
                    </div>

                    <!-- Signature Datestrip at absolute bottom -->
                    <div style="background: #f4f5f7; border-top: 1px solid #ececec; padding: 14px; text-align: center; color: #111; font-size: 13px; font-weight: 800; letter-spacing: 1px; margin-top: auto;">
                        <?php echo $e['date']; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Load More -->
            <div style="text-align: center; margin-top: 50px;">
                <button style="border: 2px solid #ccc; background: white; color: #333; padding: 14px 40px; border-radius: 50px; font-weight: 700; font-size: 13px; letter-spacing: 1px; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.borderColor='#111'; this.style.color='#111';" onmouseout="this.style.borderColor='#ccc'; this.style.color='#333';">VIEW MORE EVENTS</button>
            </div>
            
        </main>
    </div>
</section>

<script>
// Event filtering by date range
function filterEventsByDate(selectedDate, startDate, endDate) {
    const eventCards = document.querySelectorAll('.event-card');
    let visibleCount = 0;

    eventCards.forEach(card => {
        const dayAttr = card.getAttribute('data-day');
        let isVisible = false;

        if (selectedDate) {
            // Single date selection
            isVisible = dayAttr === selectedDate.toString();
        } else if (startDate && endDate) {
            // Range selection
            if (dayAttr.includes('-')) {
                // Event spans multiple days (e.g., "14-18")
                const [eventStart, eventEnd] = dayAttr.split('-').map(Number);
                isVisible = eventStart >= startDate && eventEnd <= endDate;
            } else {
                const eventDay = parseInt(dayAttr);
                isVisible = eventDay >= startDate && eventDay <= endDate;
            }
        } else {
            // No filter - show all
            isVisible = true;
        }

        card.style.display = isVisible ? 'flex' : 'none';
        if (isVisible) visibleCount++;
    });

    if (visibleCount === 0) {
        const noEventsMsg = document.querySelector('.no-events-message') || createNoEventsMessage();
        noEventsMsg.style.display = 'block';
    } else {
        const noEventsMsg = document.querySelector('.no-events-message');
        if (noEventsMsg) noEventsMsg.style.display = 'none';
    }
}

function createNoEventsMessage() {
    const msg = document.createElement('div');
    msg.className = 'no-events-message';
    msg.style.cssText = 'grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #999; font-size: 16px;';
    msg.textContent = 'No events found for selected date.';
    document.querySelector('.events-grid').appendChild(msg);
    return msg;
}

function filterByMonth(element) {
    // Update active state
    document.querySelectorAll('.month-tab').forEach(tab => {
        tab.style.borderBottom = '4px solid transparent';
        tab.style.color = '#888';
        tab.style.fontWeight = '600';
    });
    
    element.style.borderBottom = '4px solid #f5a623';
    element.style.color = '#111';
    element.style.fontWeight = '800';

    // For now, show all events (month filtering would be implemented here)
    filterEventsByDate();
}

// Calendar day click handler
document.addEventListener('click', function(e) {
    if (e.target.hasAttribute('data-day-select')) {
        const day = parseInt(e.target.getAttribute('data-day-select'));
        filterEventsByDate(day);
    }
});

// Apply Filters button handler (for date and category filtering)
document.addEventListener('DOMContentLoaded', function() {
    const applyFiltersBtn = document.getElementById('applyFiltersBtn');
    const datePickerInput = document.getElementById('event-date-picker');
    
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', function() {
            const selectedDateStr = datePickerInput.value;
            
            if (selectedDateStr) {
                // Parse the date from YYYY-MM-DD format
                const dateParts = selectedDateStr.split('-');
                const year = parseInt(dateParts[0]);
                const month = parseInt(dateParts[1]);
                const day = parseInt(dateParts[2]);
                
                // Only filter if it's May 2026
                if (year === 2026 && month === 5) {
                    filterEventsByDate(day);
                } else {
                    // Show "no events" message for dates outside May 2026
                    filterEventsByDate(-1); // Use invalid day to show no events
                }
            } else {
                // No date selected - show all events
                filterEventsByDate();
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
