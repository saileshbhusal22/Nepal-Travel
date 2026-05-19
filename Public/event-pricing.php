<?php 
$current_page = 'events.php';
include '../includes/header.php'; 
?>

<style>
    :root {
        --gold: #f5a623;
        --navy: #1b3a5a;
    }
    .pricing-hero {
        background: linear-gradient(rgba(27, 58, 90, 0.9), rgba(27, 58, 90, 0.9)), url('../images/hero_nepal.png');
        background-size: cover;
        padding: 100px 0;
        text-align: center;
        color: white;
    }
    .pricing-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        border: 1px solid #eee;
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .pricing-card.premium {
        border: 2px solid var(--gold);
        transform: scale(1.05);
        z-index: 2;
    }
    .pricing-card:hover {
        transform: translateY(-10px);
    }
    .price-tag {
        font-size: 48px;
        font-weight: 800;
        color: var(--navy);
        margin: 20px 0;
    }
    .feature-list {
        list-style: none;
        padding: 0;
        margin: 30px 0;
        text-align: left;
        flex-grow: 1;
    }
    .feature-list li {
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        color: #555;
    }
    .check-icon { color: #4caf50; }
    .x-icon { color: #f44336; }
</style>

<section class="pricing-hero">
    <div class="container">
        <h1 style="font-family: 'Playfair Display', serif; font-size: 56px; margin-bottom: 20px;">Host Your Event in Nepal</h1>
        <p style="font-size: 18px; opacity: 0.9; max-width: 700px; margin: 0 auto;">Choose the perfect plan to showcase your event to our global community of travelers.</p>
    </div>
</section>

<section style="padding: 100px 0; background: #f9f9f9;">
    <div class="container" style="max-width: 1000px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: stretch;">
            
            <!-- Free Plan -->
            <div class="pricing-card">
                <span style="font-size: 12px; font-weight: 800; color: #888; text-transform: uppercase; letter-spacing: 2px;">Community</span>
                <h2 style="font-family: 'Playfair Display', serif; font-size: 32px; color: var(--navy); margin: 10px 0;">Standard Plan</h2>
                <div class="price-tag">Free</div>
                <p style="color: #777; font-size: 14px;">Perfect for small community gatherings and free cultural festivals.</p>
                
                <ul class="feature-list">
                    <li><span class="check-icon">✓</span> Standard Event Card</li>
                    <li><span class="check-icon">✓</span> Date & Location Info</li>
                    <li><span class="check-icon">✓</span> Category Tagging</li>
                    <li><span class="x-icon">✕</span> Featured Placement</li>
                    <li><span class="x-icon">✕</span> Ticket Sales Integration</li>
                    <li><span class="x-icon">✕</span> Analytics Dashboard</li>
                </ul>
                
                <a href="events.php" class="premium-btn" style="background: var(--navy); width: 100%; border: none; text-decoration: none; display: inline-block;">START FOR FREE</a>
            </div>

            <!-- Premium Plan -->
            <div class="pricing-card premium">
                <div style="position: absolute; top: -15px; right: 30px; background: var(--gold); color: white; padding: 5px 15px; border-radius: 20px; font-size: 11px; font-weight: 800;">MOST POPULAR</div>
                <span style="font-size: 12px; font-weight: 800; color: var(--gold); text-transform: uppercase; letter-spacing: 2px;">Professional</span>
                <h2 style="font-family: 'Playfair Display', serif; font-size: 32px; color: var(--navy); margin: 10px 0;">Premium Plan</h2>
                <div class="price-tag">$49 <small style="font-size: 16px; color: #888; font-weight: 400;">/ event</small></div>
                <p style="color: #777; font-size: 14px;">Maximized visibility with advanced sales and promotion tools.</p>
                
                <ul class="feature-list">
                    <li><span class="check-icon">✓</span> <strong>Featured Spotlight placement</strong></li>
                    <li><span class="check-icon">✓</span> <strong>"BUY TICKETS" direct integration</strong></li>
                    <li><span class="check-icon">✓</span> Gold Border & Promoted Badge</li>
                    <li><span class="check-icon">✓</span> Promotional Banner in Hero</li>
                    <li><span class="check-icon">✓</span> Early Bird Tag Support</li>
                    <li><span class="check-icon">✓</span> Event Analytics Dashboard</li>
                </ul>
                
                <button class="premium-btn" style="background: var(--gold); color: var(--navy); width: 100%; border: none; font-weight: 800;">GO PREMIUM NOW</button>
            </div>

        </div>

        <div style="margin-top: 80px; text-align: center; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
            <h3 style="font-family: 'Playfair Display', serif; color: var(--navy); font-size: 28px; margin-bottom: 15px;">Need a custom solution?</h3>
            <p style="color: #666; margin-bottom: 25px;">For large scale festivals and government cultural events, contact our partnership team.</p>
            <a href="mailto:partners@nepaltravels.com" style="color: var(--gold); font-weight: 800; text-decoration: none; font-size: 14px; letter-spacing: 1px;">CONTACT PARTNERSHIPS →</a>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>