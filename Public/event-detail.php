<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

$user_email = '';
$user_phone = '';
if (isset($_SESSION['user_id'])) {
    $u_stmt = $conn->prepare("SELECT email, phone FROM users WHERE id = ?");
    $u_stmt->bind_param("i", $_SESSION['user_id']);
    $u_stmt->execute();
    $u_stmt->bind_result($user_email, $user_phone);
    $u_stmt->fetch();
    $u_stmt->close();
}

$current_page = 'events.php';
include '../includes/header.php'; 
?>

<!-- Premium Event Detail Styles -->
<style>
    /* =========================================
   OWNER EDIT DELETE CONTROLS
========================================= */

.owner-action-bar{
    position: fixed;
    bottom: 25px;
    right: 25px;
    z-index: 99999;
    display: none;
    gap: 12px;
}

.owner-btn{
    border: none;
    padding: 14px 20px;
    border-radius: 50px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.3s ease;
    color: white;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.owner-btn.edit{
    background: #F5A623;
}

.owner-btn.delete{
    background: #dc3545;
}

.owner-btn:hover{
    transform: translateY(-3px);
}

/* =========================================
   EDIT MODAL
========================================= */

.custom-modal{
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.7);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 100000;
    padding: 20px;
}

.custom-modal.active{
    display: flex;
}

.custom-modal-box{
    background: white;
    width: 100%;
    max-width: 700px;
    border-radius: 24px;
    padding: 35px;
    max-height: 90vh;
    overflow-y: auto;
}

.custom-modal-title{
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 25px;
}

.custom-form-group{
    margin-bottom: 20px;
}

.custom-form-group label{
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 700;
}

.custom-form-group input,
.custom-form-group textarea{
    width: 100%;
    padding: 14px;
    border-radius: 12px;
    border: 1px solid #ddd;
    font-size: 14px;
}

.custom-form-group textarea{
    min-height: 140px;
    resize: vertical;
}

.custom-modal-actions{
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 20px;
}

.custom-modal-btn{
    border: none;
    padding: 14px 22px;
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
}

.custom-modal-btn.cancel{
    background: #eee;
}

.custom-modal-btn.save{
    background: #F5A623;
    color: white;
}

.custom-modal-btn.delete{
    background: #dc3545;
    color: white;
}
    :root {
        --accent: #F5A623;
        --accent-dark: #D48C00;
        --text-main: #111;
        --text-muted: #666;
        --bg-light: #F9F9F9;
        --glass: rgba(255, 255, 255, 0.85);
    }

    /* Header Transparency Reset */
    .site-header { position: fixed !important; top: 0; left: 0; width: 100%; z-index: 1000; background: transparent !important; transition: all 0.4s ease; border-bottom: none !important; }
    .site-header.scrolled { background: var(--glass) !important; backdrop-filter: blur(15px); border-bottom: 1px solid rgba(0,0,0,0.05) !important; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .site-header.scrolled .nav-links a, .site-header.scrolled .logo-wrapper span { color: #111 !important; text-shadow: none !important; }

    body { background: #fff; font-family: 'Inter', sans-serif; }

    /* Hero Section */
    .detail-hero {
        height: 85vh;
        width: 100%;
        position: relative;
        background: #000;
        overflow: hidden;
        display: flex;
        align-items: center;
    }
    
    .detail-hero img#dImg {
        width: 100%;
        height: 100%;
        object-fit: cover;
        position: absolute;
        top: 0;
        left: 0;
        opacity: 0.7;
        z-index: 1;
        transform: scale(1.05);
        transition: transform 10s ease;
    }
    .detail-hero:hover img#dImg { transform: scale(1.15); }

    .detail-hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(0deg, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.4) 50%, rgba(0,0,0,0.7) 100%);
        z-index: 2;
    }

    .hero-content-inner {
        position: relative;
        z-index: 3;
        width: 100%;
        color: white;
    }

    .breadcrumb {
        display: flex;
        gap: 10px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-bottom: 30px;
        opacity: 0.9;
    }
    .breadcrumb a { color: white; text-decoration: none; transition: 0.3s; }
    .breadcrumb a:hover { color: var(--accent); }
    .breadcrumb span { color: var(--accent); }

    .event-title-main {
        font-family: 'Playfair Display', serif;
        font-size: clamp(48px, 8vw, 90px);
        font-weight: 900;
        line-height: 1.05;
        margin: 0 0 25px 0;
        max-width: 1000px;
        text-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }

    .hero-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        align-items: center;
    }

    .badge-premium {
        background: var(--accent);
        color: white;
        padding: 10px 24px;
        font-size: 11px;
        font-weight: 900;
        border-radius: 50px;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        box-shadow: 0 5px 15px rgba(245, 166, 35, 0.4);
    }

    .hero-info-item {
        color: rgba(255,255,255,0.9);
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Main Layout */
    .event-main-wrap { padding: 80px 0; background: #fff; }
    .event-grid { display: grid; grid-template-columns: 1fr 380px; gap: 80px; }

    /* Left Column Content */
    .section-title {
        font-family: 'Playfair Display', serif;
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 30px;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .section-title i { color: var(--accent); font-size: 24px; }

    .content-card {
        margin-bottom: 60px;
        padding-bottom: 60px;
        border-bottom: 1px solid #eee;
    }
    .content-card:last-child { border-bottom: none; }

    .description-text { font-size: 18px; line-height: 1.9; color: #444; }

    /* Timeline Section */
    .timeline { position: relative; padding-left: 30px; }
    .timeline::before {
        content: '';
        position: absolute;
        left: 0;
        top: 5px;
        bottom: 5px;
        width: 2px;
        background: #eee;
    }
    .timeline-item { position: relative; margin-bottom: 40px; }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -34px;
        top: 5px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--accent);
        border: 4px solid white;
        box-shadow: 0 0 0 2px var(--accent);
    }
    .timeline-time { font-size: 12px; font-weight: 800; color: var(--accent); margin-bottom: 8px; text-transform: uppercase; }
    .timeline-title { font-size: 18px; font-weight: 700; margin-bottom: 5px; color: #111; }
    .timeline-desc { font-size: 15px; color: #666; line-height: 1.6; }

    /* Raffle Box */
    .raffle-box {
        background: linear-gradient(135deg, #FFF9F0 0%, #FFF2DD 100%);
        border: 2px dashed var(--accent);
        border-radius: 20px;
        padding: 40px;
        text-align: center;
        margin-bottom: 60px;
    }
    .raffle-icon { font-size: 48px; margin-bottom: 20px; }
    .raffle-prize { font-size: 36px; font-weight: 800; color: var(--text-main); margin-bottom: 10px; }

    /* Gallery Grid */
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
    }
    .gallery-item {
        border-radius: 12px;
        overflow: hidden;
        aspect-ratio: 1;
        cursor: pointer;
    }
    .gallery-item img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
    .gallery-item:hover img { transform: scale(1.1); }
    .gallery-item.wide { grid-column: span 2; aspect-ratio: 2/1; }

    /* Chips/Tags */
    .tag-container { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
    .tag-chip {
        background: #f5f5f5;
        color: #666;
        padding: 8px 18px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: 0.3s;
    }
    .tag-chip:hover { background: var(--accent); color: white; }

    /* Sidebar Sticky */
    .sidebar-sticky { position: sticky; top: 110px; }

    .logistics-card {
        background: white;
        border-radius: 24px;
        padding: 35px;
        box-shadow: 0 30px 60px rgba(0,0,0,0.06);
        border: 1px solid rgba(0,0,0,0.04);
        margin-bottom: 30px;
    }

    .logistics-item { display: flex; gap: 20px; margin-bottom: 25px; }
    .logistics-item:last-child { margin-bottom: 0; }
    
    .l-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: #FFF9F0;
        color: var(--accent);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .l-info h4 { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #999; margin: 0 0 5px 0; letter-spacing: 1px; }
    .l-info p { font-size: 15px; font-weight: 700; color: #111; margin: 0; line-height: 1.4; }

    /* Seats Progress */
    .seats-box { margin-top: 25px; padding-top: 25px; border-top: 1px solid #eee; }
    .progress-wrap { height: 8px; background: #eee; border-radius: 10px; margin: 12px 0; overflow: hidden; }
    .progress-bar { height: 100%; background: var(--accent); border-radius: 10px; width: 0%; transition: width 1.5s ease; }
    .seats-label { display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; }

    .cta-btn {
        width: 100%;
        padding: 20px;
        background: var(--accent);
        color: white;
        text-align: center;
        text-decoration: none;
        font-weight: 800;
        font-size: 14px;
        border-radius: 12px;
        display: block;
        margin-top: 30px;
        transition: 0.3s;
        box-shadow: 0 10px 20px rgba(245, 166, 35, 0.2);
    }
    .cta-btn:hover { background: var(--accent-dark); transform: translateY(-2px); }

    /* Organizer Card */
    .organizer-card {
        background: #111;
        border-radius: 20px;
        padding: 30px;
        color: white;
        margin-top: 30px;
    }
    .org-header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
    .org-avatar { width: 50px; height: 50px; border-radius: 50%; background: #333; overflow: hidden; }
    .org-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .org-info h5 { font-size: 16px; margin: 0; font-weight: 700; }
    .org-info span { font-size: 12px; color: #888; }
    
    .org-contact { font-size: 13px; color: #ccc; display: flex; align-items: center; gap: 8px; margin-bottom: 10px; text-decoration: none; }
    .org-contact:hover { color: var(--accent); }

    /* Badges */
    .floating-badges { display: flex; gap: 10px; margin-top: 20px; }
    .badge-item {
        background: #f8f8f8;
        padding: 8px 18px;
        border-radius: 50px;
        font-size: 11px;
        font-weight: 800;
        color: #666;
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1px solid rgba(0,0,0,0.05);
    }
    .badge-item.badge-featured {
        background: #f5a623;
        color: white;
        border: none;
        box-shadow: 0 4px 12px rgba(245, 166, 35, 0.3);
    }
    .badge-item.badge-parking {
        background: #28a745;
        color: white;
        border: none;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
    }
    .badge-item i { font-size: 14px; }

    .raffle-box {
        margin-top: 40px;
        background: #fffdf5;
        border: 2px dashed #f5a623;
        border-radius: 20px;
        padding: 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
        margin-bottom: 40px;
    }

    /* Map Section Bottom */
    .map-full { width: 100%; height: 500px; margin-top: 80px; filter: grayscale(1) invert(0.1) contrast(0.8); transition: 0.5s; }
    .map-full:hover { filter: none; }

    @media (max-width: 1024px) {
        .event-grid { grid-template-columns: 1fr; gap: 40px; }
        .detail-hero { height: 60vh; }
        .event-title-main { font-size: 40px; }
        .sidebar-sticky { position: static; }
    }

    /* Modern Glassmorphic Ticket Booking Modal */
    .modal-overlay {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(10, 12, 22, 0.75);
        backdrop-filter: blur(12px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }
    .modal-overlay.active {
        opacity: 1;
        pointer-events: auto;
    }
    .modal-card {
        background: rgba(24, 28, 44, 0.95);
        border: 1px solid rgba(255,255,255,0.09);
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        border-radius: 24px;
        padding: 35px;
        width: 100%;
        max-width: 500px;
        position: relative;
        transform: scale(0.9);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        color: white;
    }
    .modal-overlay.active .modal-card {
        transform: scale(1);
    }
    .modal-close-btn {
        position: absolute;
        top: 20px; right: 20px;
        background: none; border: none;
        color: rgba(255,255,255,0.4);
        font-size: 28px; cursor: pointer;
        transition: color 0.2s;
    }
    .modal-close-btn:hover { color: white; }
    
    .modal-header { margin-bottom: 25px; text-align: center; }
    .modal-header h3 { font-family: 'Playfair Display', serif; font-size: 26px; margin-bottom: 8px; color: white; }
    .modal-header p { font-size: 14px; color: rgba(255,255,255,0.5); }
    
    .modal-form-group { margin-bottom: 18px; }
    .modal-form-group label { display: block; font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
    .modal-form-group input {
        width: 100%; padding: 12px 16px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 12px; color: white; font-size: 14px;
        outline: none; transition: all 0.25s;
    }
    .modal-form-group input:focus { border-color: var(--accent); background: rgba(245,166,35,0.05); }
    
    .modal-form-row { display: grid; grid-template-columns: 1fr 120px; gap: 15px; }
    
    .modal-price-breakdown {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 16px; padding: 18px; margin-bottom: 25px;
    }
    .modal-btn {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 14px 28px; border-radius: 12px; font-weight: 700; font-size: 14px;
        cursor: pointer; transition: all 0.2s; border: none;
    }
    .modal-btn.primary { background: #2b78e4; color: white; }
    .modal-btn.primary:hover { background: #1a63cc; transform: translateY(-1px); }
    .modal-btn.secondary { background: rgba(255,255,255,0.08); color: white; border: 1px solid rgba(255,255,255,0.12); }
    .modal-btn.secondary:hover { background: rgba(255,255,255,0.12); }
    
    .esewa-btn-logo {
        background: #60bb46; color: white; font-weight: 900;
        font-size: 14px; border-radius: 4px; padding: 2px 8px; margin-right: 4px;
        display: inline-block; text-transform: lowercase; font-family: sans-serif;
    }
</style>

<div id="loadingState" style="height: 100vh; display: flex; align-items: center; justify-content: center; background: white;">
    <div class="loader-ripple"></div>
</div>

<div id="eventDetailContent" style="display: none;">
    <!-- Hero Banner -->
    <section class="detail-hero">
        <img id="dImg" src="" alt="Event Hero" onerror="this.src='../images/placeholder_event.jpg'">
        <div class="detail-hero-overlay"></div>
        <div class="container hero-content-inner">
            <div class="breadcrumb">
                <a href="index.php">HOME</a> / <a href="events.php">EVENTS</a> / <span id="dCatBread"></span>
            </div>
            <h1 class="event-title-main" id="dTitle"></h1>
            <div class="hero-meta">
                <span id="dCatBadge" class="badge-premium"></span>
                <div class="hero-info-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <span id="dLocHero"></span>
                </div>
                <div class="hero-info-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span id="dDateHero"></span>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content Area -->
    <section class="event-main-wrap">
        <div class="container event-grid">
            
            <!-- Left Side: Content -->
            <div class="content-left">
                
                <!-- Overview -->
                <div class="content-card">
                    <h2 class="section-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                        Experience Overview
                    </h2>
                    <div id="dDesc" class="description-text"></div>
                    
                    <div style="display: flex; gap: 30px; margin-top: 40px; padding: 25px; background: #f9f9f9; border-radius: 16px;">
                        <div class="l-info">
                            <h4>LANGUAGE</h4>
                            <p id="dLang">English & Nepali</p>
                        </div>
                        <div class="l-info">
                            <h4>AGE GROUP</h4>
                            <p id="dAge">All Ages</p>
                        </div>
                        <div class="l-info">
                            <h4>EVENT TYPE</h4>
                            <p id="dType">Public Event</p>
                        </div>
                    </div>
                </div>

                <!-- Timeline / Schedule -->
                <div class="content-card">
                    <h2 class="section-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        Event Timeline
                    </h2>
                    <div class="timeline" id="dTimeline">
                        <!-- Dynamic Content -->
                        <div class="timeline-item">
                            <div class="timeline-time">Day 1 • 09:00 AM</div>
                            <div class="timeline-title">Opening Ceremony & Rituals</div>
                            <div class="timeline-desc">Traditional welcoming ceremony followed by cultural performances and religious rituals.</div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-time">Day 1 • 02:00 PM</div>
                            <div class="timeline-title">Heritage Walk & Exhibition</div>
                            <div class="timeline-desc">Guided tour through the historic sites and viewing of local art exhibitions.</div>
                        </div>
                    </div>
                </div>

                <!-- Raffle Section (Optional) -->
                <div id="dRaffleBox" class="raffle-box" style="display: none;">
                    <div class="raffle-icon">🏆</div>
                    <h3 style="font-family: 'Playfair Display', serif; font-size: 24px; margin-bottom: 10px;">Event Lucky Draw</h3>
                    <p style="color: #666; margin-bottom: 25px;">Enter the raffle for a chance to win exclusive prizes!</p>
                    <div class="raffle-prize" id="dPrize1"></div>
                    <p id="dDrawTime" style="font-size: 14px; font-weight: 700; color: var(--accent);"></p>
                </div>

                <!-- Gallery -->
                <div class="content-card">
                    <h2 class="section-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                        Snapshots & Memories
                    </h2>
                    <div class="gallery-grid" id="dGallery">
                        <!-- Dynamic Content -->
                    </div>
                </div>

                <!-- Video Embed -->
                <div id="dVideoBox" class="content-card" style="display: none;">
                    <h2 class="section-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                        Official Trailer
                    </h2>
                    <div class="video-wrapper" style="position: relative; padding-bottom: 56.25%; height: 0; border-radius: 20px; overflow: hidden; background: #000;">
                        <iframe id="dVideoFrame" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" frameborder="0" allowfullscreen></iframe>
                    </div>
                </div>

                <!-- Tags & Share -->
                <div class="content-card">
                    <h4 style="font-size: 12px; font-weight: 800; color: #999; margin-bottom: 15px; letter-spacing: 1.5px; text-transform: uppercase;">TAGS & CATEGORIES</h4>
                    <div class="tag-container" id="dTags"></div>
                    <div style="margin-top: 40px; display: flex; align-items: center; gap: 20px;">
    <span style="font-size: 14px; font-weight: 700;">Share Event:</span>
    <div style="display: flex; gap: 12px;">

        <a href="#" 
           onclick="shareEvent('facebook')" 
           style="display: flex; 
                  align-items: center; 
                  justify-content: center;
                  width: 48px; 
                  height: 48px; 
                  background: #1877F2; 
                  border-radius: 50%;
                  box-shadow: 0 4px 12px rgba(24,119,242,0.3); 
                  text-decoration: none;
                  transition: transform 0.2s, box-shadow 0.2s;"
           onmouseover="this.style.transform='translateY(-3px)'"
           onmouseout="this.style.transform='translateY(0)'">
            <svg width="24" height="24" fill="white" viewBox="0 0 24 24">
                <path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1V12h3l-.5 3H13v6.8c4.56-.93 8-4.96 8-9.8z"/>
            </svg>
        </a>

        <a href="#" 
           onclick="shareEvent('whatsapp')" 
           style="display: flex; 
                  align-items: center; 
                  justify-content: center;
                  width: 48px; 
                  height: 48px; 
                  background: #25D366; 
                  border-radius: 50%;
                  box-shadow: 0 4px 12px rgba(37,211,102,0.3); 
                  text-decoration: none;
                  transition: transform 0.2s, box-shadow 0.2s;"
           onmouseover="this.style.transform='translateY(-3px)'"
           onmouseout="this.style.transform='translateY(0)'">
            <svg width="24" height="24" fill="white" viewBox="0 0 24 24">
                <path d="M12.031 2c-5.516 0-9.986 4.471-9.986 9.987 0 1.763.456 3.42 1.253 4.868l-1.334 4.87 4.985-1.309c1.393.759 2.977 1.192 4.662 1.192h.001c5.517 0 9.988-4.471 9.988-9.987 0-5.517-4.471-9.987-9.988-9.987zm0 18.28h-.001c-1.503 0-2.977-.404-4.26-1.169l-.306-.181-3.167.83.846-3.092-.198-.315c-.838-1.332-1.282-2.884-1.282-4.48 0-4.563 3.714-8.276 8.277-8.276 2.212 0 4.291.861 5.854 2.425 1.564 1.565 2.426 3.644 2.426 5.854-.001 4.565-3.715 8.279-8.278 8.279zm4.536-6.19c-.249-.125-1.473-.726-1.701-.809-.228-.083-.393-.125-.558.125-.165.25-.64.809-.784.975-.144.166-.289.187-.538.062-.25-.125-1.053-.388-2.007-1.24-.741-.661-1.242-1.477-1.387-1.726-.145-.25-.016-.385.11-.51.114-.112.25-.291.375-.437.125-.145.166-.25.25-.417.083-.166.042-.312-.021-.437-.062-.125-.558-1.346-.765-1.848-.2-.49-.404-.423-.558-.431-.144-.008-.31-.01-.476-.01s-.437.062-.663.312c-.225.25-.863.844-.863 2.059 0 1.215.885 2.39 1.01 2.556.125.166 1.742 2.66 4.22 3.732.59.255 1.05.408 1.408.522.593.188 1.133.161 1.56.097.476-.071 1.473-.603 1.68-.187.208-.584.208-1.083.146-1.167-.063-.083-.23-.125-.478-.25z"/>
            </svg>
        </a>

    </div>
</div>
                    
                    
                </div>

            </div>

            <!-- Right Side: Sidebar -->
            <div class="sidebar-right">
                <div class="sidebar-sticky">
                    
                    <!-- Main Box -->
                    <div class="logistics-card">
                        <div class="logistics-item">
                            <div class="l-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></div>
                            <div class="l-info">
                                <h4>DATE & TIME</h4>
                                <p id="dDateTimeFormatted"></p>
                                <a href="#" id="dAddToCal" class="org-contact" style="margin-top: 5px; color: var(--accent); font-weight: 800; font-size: 11px;">+ ADD TO CALENDAR</a>
                            </div>
                        </div>

                        <div class="logistics-item">
                            <div class="l-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg></div>
                            <div class="l-info">
                                <h4>VENUE</h4>
                                <p id="dVenueFull"></p>
                                <a id="dVenueMap" href="#" target="_blank" class="org-contact" style="margin-top: 5px; color: var(--accent); font-weight: 800; font-size: 11px;">VIEW ON MAP</a>
                            </div>
                        </div>

                        <div class="logistics-item">
                            <div class="l-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></div>
                            <div class="l-info">
                                <h4>ADMISSION</h4>
                                <p id="dAdmissionPrice"></p>
                            </div>
                        </div>

                        <!-- Seats Progress -->
                        <div id="dSeatsBox" class="seats-box" style="display: none;">
                            <div class="seats-label">
                                <span>Seats Availability</span>
                                <span id="dSeatsPercent">0%</span>
                            </div>
                            <div class="progress-wrap"><div id="dSeatsProgress" class="progress-bar"></div></div>
                            <p id="dSeatsText" style="font-size: 11px; color: #888; margin: 0; font-weight: 600;"></p>
                        </div>

                        <div id="premiumCTA" style="display: none;">
                            <a id="dTicketLink" href="#" target="_blank" class="cta-btn">SECURE YOUR TICKETS</a>
                        </div>
                        
                        <div class="floating-badges">
                            <div id="badgeParking" class="badge-item" style="display: none;"><i class="fas fa-parking"></i> FREE PARKING</div>
                            <div id="badgeFeatured" class="badge-item" style="display: none;"><i class="fas fa-star"></i> FEATURED</div>
                        </div>
                    </div>

                    <!-- Organizer Card -->
                    <div id="dOrgBox" class="organizer-card" style="display: none;">
                        <div class="org-header">
                            <div class="org-avatar"><img src="https://ui-avatars.com/api/?name=Organizer&background=333&color=fff" id="dOrgImg"></div>
                            <div class="org-info">
                                <h5 id="dOrgName"></h5>
                                <span>Verified Organizer</span>
                            </div>
                        </div>
                        <a href="#" id="dOrgEmail" class="org-contact"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg> <span id="dOrgEmailText"></span></a>
                        <a href="#" id="dOrgPhone" class="org-contact"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg> <span id="dOrgPhoneText"></span></a>
                    </div>

                    <!-- Sidebar Footer Info -->
                    <p style="font-size: 11px; color: #999; text-align: center; margin-top: 20px; padding: 0 20px;">
                        By securing your tickets, you agree to the event terms and conditions. Ticket sales are final and non-refundable.
                    </p>

                </div>
            </div>

        </div>
    </section>

    <!-- Map Section Full Width -->
    <div style="background: #fff; padding-bottom: 80px;">
        <div class="container">
            <h2 class="section-title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line></svg>
                Location & Venue
            </h2>
        </div>
        <div class="map-full">
            <iframe id="dMapFrameFull" width="100%" height="100%" frameborder="0" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </div>

    <!-- Related Events Section -->
    <section style="background: #fdfdfd; padding: 80px 0;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px;">
                <h2 class="section-title" style="margin-bottom: 0;">You Might Also Like</h2>
                <a href="events.php" style="color: var(--accent); font-weight: 800; font-size: 13px; text-decoration: none;">VIEW ALL EVENTS →</a>
            </div>
            <div id="relatedEventsGrid" class="events-grid-minimal" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
                <!-- Related events will load here -->
            </div>
        </div>
    </section>
</div>

<!-- Owner controls (must be in DOM before script binds listeners) -->
<div class="owner-action-bar" id="ownerActionBar">
    <button type="button" class="owner-btn edit" id="editEventBtn">✏️ Edit</button>
    <button type="button" class="owner-btn delete" id="deleteEventBtn">🗑 Delete</button>
</div>

<div class="custom-modal" id="deleteEventModal">
    <div class="custom-modal-box">
        <div class="custom-modal-title">Delete Event</div>
        <p style="margin-bottom:25px;">Are you sure you want to permanently delete this event?</p>
        <div class="custom-modal-actions">
            <button type="button" class="custom-modal-btn cancel" id="cancelDeleteBtn">Cancel</button>
            <button type="button" class="custom-modal-btn delete" id="confirmDeleteBtn">Delete Event</button>
        </div>
    </div>
</div>

<script>
    
// Session status variables
const isLoggedIn     = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
const sessionUserId  = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
const sessionIsAdmin = <?= (!empty($_SESSION['is_admin'])) ? 'true' : 'false' ?>;

function openTicketModal(e) {
    const modal = document.getElementById('ticketModal');
    if (!modal) return;
    
    document.getElementById('modalEventTitle').textContent = e.title || 'Untitled Event';
    document.getElementById('modalEventTitleInput').value = e.title || 'Untitled Event';
    document.getElementById('modalEventId').value = e.id;
    document.getElementById('modalEventDateInput').value = e.start_date || e.event_date || '';
    
    const isPaid = parseInt(e.is_paid || 0) === 1;
    const ticketPrice = isPaid ? parseFloat(e.price_npr || 0) : 0;
    
    document.getElementById('modalTicketPriceInput').value = ticketPrice;
    document.getElementById('modalAmountInput').value = ticketPrice;
    
    // Clear dynamic breakdown variables
    document.getElementById('modalTicketQty').value = 1;
    document.getElementById('modalQtyText').textContent = '1 ticket';
    
    if (isPaid) {
        document.getElementById('modalSinglePriceText').textContent = 'NPR ' + ticketPrice.toLocaleString('en-IN', { maximumFractionDigits: 0 });
        document.getElementById('modalTotalPriceText').textContent = 'NPR ' + ticketPrice.toLocaleString('en-IN', { maximumFractionDigits: 0 });
        document.getElementById('modalSubmitBtn').innerHTML = '<span class="esewa-btn-logo">e</span> Pay with eSewa';
        document.getElementById('modalSubmitBtn').className = 'modal-btn primary';
        document.getElementById('modalSubmitBtn').style.background = '#2b78e4';
        document.getElementById('modalSubmitBtn').style.color = 'white';
    } else {
        document.getElementById('modalSinglePriceText').textContent = 'Free Admission';
        document.getElementById('modalTotalPriceText').textContent = 'Free';
        document.getElementById('modalSubmitBtn').innerHTML = '🎟️ Get Free Ticket';
        document.getElementById('modalSubmitBtn').className = 'modal-btn secondary';
        document.getElementById('modalSubmitBtn').style.background = '#28a745';
        document.getElementById('modalSubmitBtn').style.color = 'white';
    }
    
    if (isLoggedIn) {
        document.getElementById('modalLoggedOutState').style.display = 'none';
        document.getElementById('ticketBookingForm').style.display = 'block';
    } else {
        document.getElementById('modalLoggedOutState').style.display = 'block';
        document.getElementById('modalLoginLink').href = `../user/login.php?redirect=Public/event-detail.php?id=${e.id}`;
        document.getElementById('ticketBookingForm').style.display = 'none';
    }
    
    modal.classList.add('active');
}

function closeTicketModal() {
    const modal = document.getElementById('ticketModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    // Header Scroll Logic
    const header = document.querySelector('.site-header');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');

    if (!id) {
        window.location.href = 'events.php';
        return;
    }

    try {
        const res = await fetch(`../api/v1/events.php?id=${id}`);
        const data = await res.json();

        if (data.success) {
            const e = data.event;
            if (!e) throw new Error("Event data is empty");
            
            // Hero
            let imgPath = e.image_path || '../images/placeholder_event.jpg';
            if (imgPath.startsWith('images/')) {
                imgPath = '../' + imgPath;
            }
            document.getElementById('dImg').src = imgPath;
            document.getElementById('dTitle').textContent = e.title || 'Untitled Event';
            document.getElementById('dCatBadge').textContent = e.category || 'General';
            document.getElementById('dCatBread').textContent = e.category || 'General';
            document.getElementById('dLocHero').textContent = e.region || e.location || 'Nepal';
            
            // Date Formatting
            const startDate = e.start_date ? new Date(e.start_date) : null;
            const endDate = e.end_date ? new Date(e.end_date) : null;
            const options = { day: '2-digit', month: 'short', year: 'numeric' };
            
            let dateStr = "";
            if (startDate && !isNaN(startDate)) {
                const sStr = startDate.toLocaleDateString('en-GB', options).toUpperCase();
                if (endDate && !isNaN(endDate) && e.start_date !== e.end_date) {
                    const eStr = endDate.toLocaleDateString('en-GB', options).toUpperCase();
                    dateStr = `${sStr} - ${eStr}`;
                } else {
                    dateStr = sStr;
                }
            } else {
                let d = e.event_date || '';
                let m = e.month === 'ALL' ? '' : (e.month || '');
                // Fix: Avoid "15 SEP SEP" duplication
                if (d.toUpperCase().includes(m.toUpperCase())) {
                    dateStr = `${d} 2026`.trim();
                } else {
                    dateStr = `${d} ${m} 2026`.trim();
                }
            }
            document.getElementById('dDateHero').textContent = dateStr;
                // Badges
            const featBadge = document.getElementById('badgeFeatured');
            const parkBadge = document.getElementById('badgeParking');
            if (featBadge) {
                if (e.is_featured == 1) {
                    featBadge.style.display = 'flex';
                    featBadge.classList.add('badge-featured');
                    featBadge.innerHTML = `<i class="fas fa-star"></i> ${e.featured_badge_text || 'FEATURED'}`;
                } else {
                    featBadge.style.display = 'none';
                }
            }
            if (parkBadge) {
                if (e.free_parking == 1) {
                    parkBadge.style.display = 'flex';
                    parkBadge.classList.add('badge-parking');
                } else {
                    parkBadge.style.display = 'none';
                }
            }

            // Sidebar Logistics
            const timeStr = (e.start_time && e.start_time !== '00:00:00' && e.end_time && e.end_time !== '00:00:00') 
                ? ` • ${formatTime(e.start_time)} – ${formatTime(e.end_time)}` 
                : "";
            document.getElementById('dDateTimeFormatted').textContent = (dateStr + timeStr).trim();
            document.getElementById('dVenueFull').textContent = e.venue_name || e.location || 'Nepal';
            document.getElementById('dAdmissionPrice').textContent = e.is_paid == 1 ? `NPR ${parseFloat(e.price_npr || 0).toLocaleString()}` : 'Free Admission';
            
            // Seats & Progress
            const seatsBox = document.getElementById('dSeatsBox');
            if (seatsBox && e.seats > 0 && !e.unlimited_seats) {
                seatsBox.style.display = 'block';
                const totalSeats = parseInt(e.seats || 100);
                const occupancy = (parseInt(e.id) % 30) + 40; 
                const soldCount = Math.round((occupancy / 100) * totalSeats);
                
                const percentEl = document.getElementById('dSeatsPercent');
                const progressEl = document.getElementById('dSeatsProgress');
                const textEl = document.getElementById('dSeatsText');
                
                if (percentEl) percentEl.innerText = `${occupancy}% Full`;
                if (progressEl) {
                    progressEl.style.width = `${occupancy}%`;
                    progressEl.style.background = occupancy > 85 ? '#cc3333' : '#f5a623';
                }
                if (textEl) textEl.innerText = `${totalSeats - soldCount} Seats Remaining out of ${totalSeats}`;
            }
    
            // Add to Calendar Link
            if (startDate && !isNaN(startDate)) {
                const calStart = (e.start_date || "").replace(/-/g, '') + (e.start_time ? 'T' + e.start_time.replace(/:/g, '') + '00Z' : '');
                const calEnd = (e.end_date || e.start_date || "").replace(/-/g, '') + (e.end_time ? 'T' + e.end_time.replace(/:/g, '') + '00Z' : '');
                document.getElementById('dAddToCal').href = `https://www.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(e.title || "")}&dates=${calStart}/${calEnd}&details=${encodeURIComponent(e.description || "")}&location=${encodeURIComponent(e.location || "")}`;
            }

            // Seats Progress
            if (e.seats > 0 && !e.unlimited_seats) {
                document.getElementById('dSeatsBox').style.display = 'block';
                const occupancy = Math.floor(Math.random() * 40) + 60; 
                const left = Math.floor(e.seats * (1 - occupancy/100));
                document.getElementById('dSeatsProgress').style.width = occupancy + '%';
                document.getElementById('dSeatsPercent').textContent = occupancy + '%';
                document.getElementById('dSeatsText').textContent = `${left.toLocaleString()} / ${parseInt(e.seats).toLocaleString()} seats remaining`;
            }

            // CTA & Badges
            if (e.is_premium == 1) {
                document.getElementById('premiumCTA').style.display = 'block';
                document.getElementById('dTicketLink').href = e.ticket_link || e.registration_url || '#';
                document.getElementById('badgeFeatured').style.display = 'flex';
            }
            if (e.free_parking == 1) document.getElementById('badgeParking').style.display = 'flex';

            // Content Left
            document.getElementById('dDesc').innerHTML = (e.description || 'No description available.').replace(/\n/g, '<br>');
            document.getElementById('dLang').textContent = e.language || 'English & Nepali';
            document.getElementById('dAge').textContent = e.age_group || 'All Ages';
            document.getElementById('dType').textContent = e.is_international == 1 ? 'International Event' : 'Local Community Event';

            // Raffle
            if (e.raffle_enabled == 1) {
                document.getElementById('dRaffleBox').style.display = 'block';
                document.getElementById('dPrize1').textContent = e.raffle_prize_1 || 'Grand Surprise Prize';
                if (e.raffle_draw_time) {
                    const dTime = new Date(e.raffle_draw_time);
                    if (!isNaN(dTime)) {
                        document.getElementById('dDrawTime').textContent = `DRAW TIME: ${dTime.toLocaleString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }).toUpperCase()}`;
                    }
                }
            }

            // Gallery
            let galleryList = [];
            if (e.gallery_images && e.gallery_images.trim() !== "") {
                // Use ONLY uploaded gallery images if they exist
                galleryList = e.gallery_images.split(',').map(img => {
                    let trimmed = img.trim();
                    return trimmed.startsWith('images/') ? '../' + trimmed : trimmed;
                });
            } else {
                // Fallback to placeholders only if no gallery images were uploaded
                galleryList = [imgPath];
                const catImg = {
                    'ARTS & CULTURE': 'https://images.unsplash.com/photo-1544644181-1484b3fdfc62',
                    'FOOD & CUISINE': 'https://images.unsplash.com/photo-1504674900247-0877df9cc836',
                    'MUSIC & CONCERT': 'https://images.unsplash.com/photo-1459749411177-042180ce673c',
                    'FESTIVALS': 'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3',
                    'SPORTS': 'https://images.unsplash.com/photo-1574629810360-7efbbe195018',
                    'NATURE': 'https://images.unsplash.com/photo-1544735716-392fe2489ffa'
                };
                const catKey = (e.category || "").toUpperCase();
                const placeholder = catImg[catKey] || 'https://images.unsplash.com/photo-1544735716-392fe2489ffa';
                for(let i=0; i<5; i++) galleryList.push(`${placeholder}?sig=${i}`);
            }
            
            const gHtml = galleryList.map((src, i) => `
                <div class="gallery-item ${i === 0 ? 'wide' : ''}">
                    <img src="${src}" alt="Gallery ${i}">
                </div>
            `).join('');
            document.getElementById('dGallery').innerHTML = gHtml;

            // Organizer
            if (e.organizer_name) {
                document.getElementById('dOrgBox').style.display = 'block';
                document.getElementById('dOrgName').textContent = e.organizer_name;
                document.getElementById('dOrgEmailText').textContent = e.organizer_email || 'contact@organizer.com';
                document.getElementById('dOrgEmail').href = `mailto:${e.organizer_email || ''}`;
                document.getElementById('dOrgPhoneText').textContent = e.organizer_contact || '+977-1-4XXXXXX';
                document.getElementById('dOrgPhone').href = `tel:${e.organizer_contact || ''}`;
                document.getElementById('dOrgImg').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(e.organizer_name)}&background=333&color=fff`;
            }

            // Tags
            const tagData = e.tags || e.category || "Event";
            const tags = tagData.split(',').map(t => t.trim());
            document.getElementById('dTags').innerHTML = tags.map(t => `<a href="events.php?q=${encodeURIComponent(t)}" class="tag-chip">#${t.toLowerCase().replace(/\s+/g, '') }</a>`).join('');

            // Maps
            const locQuery = encodeURIComponent(e.venue_name ? `${e.venue_name}, ${e.location || ''}` : (e.location || 'Nepal'));
            const mapSrc = `https://maps.google.com/maps?q=${locQuery}&t=&z=14&ie=UTF8&iwloc=&output=embed`;
            document.getElementById('dMapFrameFull').src = mapSrc;
            document.getElementById('dVenueMap').href = `https://www.google.com/maps/search/?api=1&query=${locQuery}`;

            // Load Related Events
            loadRelatedEvents(e.category || "", e.id);

            // Ticket CTA
            const ticketUrl = e.registration_url || e.ticket_link;
            const isPaid = parseInt(e.is_paid || 0) === 1;
            
            if (isPaid || (ticketUrl && ticketUrl.trim() !== '')) {
                document.getElementById('premiumCTA').style.display = 'block';
                const ticketBtn = document.getElementById('dTicketLink');
                if (ticketBtn) {
                    // Clone to strip any prior standard link click events
                    const newTicketBtn = ticketBtn.cloneNode(true);
                    ticketBtn.parentNode.replaceChild(newTicketBtn, ticketBtn);
                    
                    newTicketBtn.addEventListener('click', (event) => {
                        event.preventDefault();
                        openTicketModal(e);
                    });
                }
            } else {
                document.getElementById('premiumCTA').style.display = 'none';
            }

            // Bind Ticket Quantity Input Dynamic Calculation
            const qtyInput = document.getElementById('modalTicketQty');
            if (qtyInput) {
                qtyInput.addEventListener('input', (event) => {
                    const qty = Math.max(1, parseInt(event.target.value) || 1);
                    const price = parseFloat(document.getElementById('modalTicketPriceInput').value || 0);
                    const total = price * qty;
                    
                    document.getElementById('modalQtyText').textContent = `${qty} ticket${qty > 1 ? 's' : ''}`;
                    if (price > 0) {
                        document.getElementById('modalTotalPriceText').textContent = 'NPR ' + total.toLocaleString('en-IN', { maximumFractionDigits: 0 });
                        document.getElementById('modalAmountInput').value = total.toFixed(2);
                    }
                });
            }
            showOwnerControls(e);

            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('eventDetailContent').style.display = 'block';
            window.scrollTo(0,0);
        } else {
            alert('Event not found: ' + (data.message || 'Unknown error'));
            window.location.href = 'events.php';
        }
    } catch (err) {
        console.error("DEBUG ERROR:", err);
        alert('Failed to load event details: ' + err.message);
    }
});

function formatTime(timeStr) {
    if (!timeStr) return "";
    const [h, m] = timeStr.split(':');
    const hour = parseInt(h);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const h12 = hour % 12 || 12;
    return `${h12}:${m} ${ampm}`;
}

async function loadRelatedEvents(category, excludeId) {
    try {
        const res = await fetch(`../api/v1/events.php?category=${encodeURIComponent(category)}`);
        const data = await res.json();
        if (data.success) {
            const filtered = data.events.filter(ev => ev.id !== excludeId).slice(0, 3);
            const grid = document.getElementById('relatedEventsGrid');
            if (filtered.length === 0) {
                grid.innerHTML = '<p style="color:#999; font-size:14px;">No similar events found at the moment.</p>';
                return;
            }
            grid.innerHTML = filtered.map(ev => {
                let relatedImg = ev.image_path || '../images/placeholder_event.jpg';
                if (relatedImg.startsWith('images/')) {
                    relatedImg = '../' + relatedImg;
                }
                const rawDate = (ev.event_date && ev.event_date !== 'null') ? ev.event_date : '';
                const displayDate = rawDate || (ev.month !== 'ALL' ? ev.month : 'MAY');
                const suffixMonth = (rawDate && !rawDate.toUpperCase().includes(ev.month.toUpperCase())) ? ` ${ev.month}` : '';
                const dateStr = `${displayDate}${suffixMonth} 2026`.trim();

                return `
                    <a href="event-detail.php?id=${ev.id}" style="text-decoration:none; color:inherit;">
                        <div style="background:white; border-radius:16px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.05);">
                            <img src="${relatedImg}" onerror="this.src='../images/placeholder_event.jpg'" style="width:100%; height:180px; object-fit:cover;">
                            <div style="padding:20px;">
                                <span style="font-size:10px; font-weight:800; color:var(--accent); text-transform:uppercase;">${ev.category}</span>
                                <h4 style="font-size:16px; margin:8px 0; font-family:'Playfair Display',serif;">${ev.title}</h4>
                                <p style="font-size:12px; color:#888; margin:0;">${dateStr}</p>
                            </div>
                        </div>
                    </a>
                `;
            }).join('');
        }
    } catch (err) { console.error(err); }
}

function shareEvent(platform) {
    const url = window.location.href;
    const title = document.getElementById('dTitle').textContent;
    let shareUrl = "";
    if (platform === 'facebook') shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
    if (platform === 'whatsapp') shareUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(title + " " + url)}`;
    if (shareUrl) window.open(shareUrl, '_blank');
}
/* =========================================
   OWNER EVENT CONTROLS
========================================= */

let currentEventData = null;

/* Show Edit/Delete Buttons */

function showOwnerControls(eventData){

    currentEventData = eventData;

    if(!isLoggedIn) return;

    const ownerBar = document.getElementById('ownerActionBar');

    const isOwner =
        Number(eventData.user_id) === Number(sessionUserId);

    if (ownerBar && (isOwner || sessionIsAdmin)) {
        ownerBar.style.display = 'flex';
    }
}

function initOwnerControls() {
    const editBtn = document.getElementById('editEventBtn');
    const deleteBtn = document.getElementById('deleteEventBtn');
    const deleteModal = document.getElementById('deleteEventModal');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    if (editBtn) {
        editBtn.addEventListener('click', () => {
            if (!currentEventData) return;
            window.location.href = `events.php?edit=${currentEventData.id}`;
        });
    }

    if (deleteBtn && deleteModal) {
        deleteBtn.addEventListener('click', () => deleteModal.classList.add('active'));
    }

    if (cancelDeleteBtn && deleteModal) {
        cancelDeleteBtn.addEventListener('click', () => deleteModal.classList.remove('active'));
    }

    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', async () => {
            if (!currentEventData) return;

            try {
                const response = await fetch(
                    `../api/v1/events.php?id=${currentEventData.id}`,
                    { method: 'DELETE' }
                );
                const data = await response.json();

                if (data.success) {
                    alert('Event deleted successfully');
                    window.location.href = 'events.php';
                } else {
                    alert(data.message || 'Delete failed');
                }
            } catch (err) {
                console.error(err);
                alert('Something went wrong');
            }
        });
    }
}

initOwnerControls();
</script>

    

<!-- eSewa Ticket Booking Modal -->
<div id="ticketModal" class="modal-overlay">
    <div class="modal-card">
        <button class="modal-close-btn" onclick="closeTicketModal()">&times;</button>
        <div class="modal-header">
            <h3>Secure Your Tickets</h3>
            <p id="modalEventTitle" style="color: var(--accent); font-weight: 700;"></p>
        </div>
        
        <!-- Logged Out State -->
        <div id="modalLoggedOutState" style="display: none; text-align: center; padding: 20px 0;">
            <p style="color: #ccc; margin-bottom: 20px; font-size: 14px;">🔐 You must be logged in to secure your tickets.</p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <a href="../user/login.php" id="modalLoginLink" class="modal-btn primary" style="text-decoration: none;">Login</a>
                <a href="../user/register.php" class="modal-btn secondary" style="text-decoration: none;">Register</a>
            </div>
        </div>
        
        <!-- Logged In State -->
        <form id="ticketBookingForm" method="POST" style="display: none;" action="event_esewa_booking_initiate.php">
            <input type="hidden" name="event_id" id="modalEventId">
            <input type="hidden" name="event_title" id="modalEventTitleInput">
            <input type="hidden" name="ticket_price" id="modalTicketPriceInput">
            <input type="hidden" name="amount" id="modalAmountInput">
            <input type="hidden" name="event_date" id="modalEventDateInput">
            
            <div class="modal-form-group">
                <label>Full Name</label>
                <input type="text" name="name" id="modalUserName" required placeholder="Enter your full name" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>">
            </div>
            
            <div class="modal-form-group">
                <label>Email Address</label>
                <input type="email" id="modalUserEmail" readonly style="opacity: 0.7; cursor: not-allowed; background: rgba(255,255,255,0.05);" value="<?= htmlspecialchars($user_email) ?>">
            </div>
            
            <div class="modal-form-row">
                <div class="modal-form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" id="modalUserPhone" required placeholder="e.g. 98XXXXXXXX" value="<?= htmlspecialchars($user_phone) ?>">
                </div>
                <div class="modal-form-group">
                    <label>Number of Tickets</label>
                    <input type="number" name="guests" id="modalTicketQty" min="1" max="10" value="1" required>
                </div>
            </div>
            
            <div class="modal-price-breakdown">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: #aaa;">Price per ticket</span>
                    <span id="modalSinglePriceText">NPR 0.00</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 13px; color: #888;">
                    <span>Quantity</span>
                    <span id="modalQtyText">1 ticket</span>
                </div>
                <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 12px; display: flex; justify-content: space-between; font-weight: 700; font-size: 16px;">
                    <span>Total Amount</span>
                    <span id="modalTotalPriceText" style="color: var(--accent);">NPR 0.00</span>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="submit" id="modalSubmitBtn" class="modal-btn primary" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <span class="esewa-btn-logo">e</span> Pay with eSewa
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
