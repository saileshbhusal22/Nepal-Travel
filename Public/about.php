<?php
$current_page = 'about.php';
include dirname(__FILE__) . '/../includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-about" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('../images/kathmandu_night_hero.png');">
    <div class="container hero-about-container">
        <!-- Left Side: Title -->
        <div class="hero-about-title">
            <h1 class="title-overlap">
                <span class="script-font">About</span>
                <span class="sans-bold">Nepal</span>
            </h1>
        </div>

        <!-- Right Side: Menu Cards (Now acting as Tabs) -->
        <div class="hero-about-menu">
            <button class="menu-card active" onclick="switchTab('tab-about', this)">
                <div class="menu-icon">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                </div>
                <div class="menu-text">About<br>Nepal</div>
            </button>
            <button class="menu-card" onclick="switchTab('tab-arriving', this)">
                <div class="menu-icon">
                    <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                </div>
                <div class="menu-text">Arriving and Entering<br>Nepal</div>
            </button>
            <button class="menu-card" onclick="switchTab('tab-getting-around', this)">
                <div class="menu-icon">
                    <svg viewBox="0 0 24 24"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"></polygon><line x1="9" y1="3" x2="9" y2="18"></line><line x1="15" y1="6" x2="15" y2="21"></line></svg>
                </div>
                <div class="menu-text">Getting Around<br>Nepal</div>
            </button>
            <button class="menu-card" onclick="switchTab('tab-travel-guide', this)">
                <div class="menu-icon">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon></svg>
                </div>
                <div class="menu-text">Travel<br>Guide</div>
            </button>
        </div>
    </div>
</section>

<!-- Content Container -->
<div class="tab-content-container" style="min-height: 800px;">

    <!-- TAB 1: ABOUT NEPAL -->
    <div id="tab-about" class="tab-panel active" style="display: block;">
        <section class="container" style="padding: 80px 0;">
            <div class="about-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;">
                <div class="about-text">
                    <h2 style="color: var(--primary-blue); font-size: 36px; margin-bottom: 20px; font-weight: 800;">The Heart of the Himalayas</h2>
                    <p style="color: var(--text-gray); font-size: 16px; margin-bottom: 20px; line-height: 1.8;">Nepal is a spectacular land of discovery and unique experiences. Nestled between India and China, it is home to 8 of the 14 highest peaks in the world, including Mount Everest. Beyond the mountains, it features ancient cultures, stunning architecture, and unparalleled biodiversity.</p>
                    <p style="color: var(--text-gray); font-size: 16px; line-height: 1.8;">Our cultural heritage is woven into our daily lives, where ancient traditions blend seamlessly with modern harmony. Discover a destination where once is never enough.</p>
                </div>
                <div class="about-images" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <img src="../images/bhaktapur_temple.png" alt="Heritage" style="width: 100%; border-radius: 12px; height: 350px; object-fit: cover; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                    <img src="../images/pokhara_lake.png" alt="Lake" style="width: 100%; border-radius: 12px; height: 350px; object-fit: cover; margin-top: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                </div>
            </div>
        </section>
        <section style="background: #f4f7f9; padding: 80px 0;">
            <div class="container" style="text-align: center; max-width: 1000px;">
                <h2 style="color: var(--primary-blue); font-size: 30px; margin-bottom: 40px;">A Tapestry of Diverse Landscapes</h2>
                
                <!-- Landscape Comparison Strip -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 50px;">
                    <div style="position: relative; border-radius: 12px; overflow: hidden; height: 250px;">
                        <img src="../images/chitwan_rhino.png" alt="Terai" style="width: 100%; height: 100%; object-fit: cover;">
                        <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.7)); padding: 15px; color: white; text-align: left;">
                            <h4 style="margin: 0; font-size: 18px; font-weight: 800;">The Terai</h4>
                            <p style="margin: 5px 0 0; font-size: 12px; opacity: 0.9;">Tropical jungles & fertile plains.</p>
                        </div>
                    </div>
                    <div style="position: relative; border-radius: 12px; overflow: hidden; height: 250px;">
                        <img src="../images/pokhara_lake.png" alt="Hills" style="width: 100%; height: 100%; object-fit: cover;">
                        <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.7)); padding: 15px; color: white; text-align: left;">
                            <h4 style="margin: 0; font-size: 18px; font-weight: 800;">The Hills</h4>
                            <p style="margin: 5px 0 0; font-size: 12px; opacity: 0.9;">Terraced fields & vibrant valleys.</p>
                        </div>
                    </div>
                    <div style="position: relative; border-radius: 12px; overflow: hidden; height: 250px;">
                        <img src="../images/everest_trek.png" alt="Himalayas" style="width: 100%; height: 100%; object-fit: cover;">
                        <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.7)); padding: 15px; color: white; text-align: left;">
                            <h4 style="margin: 0; font-size: 18px; font-weight: 800;">The Himalayas</h4>
                            <p style="margin: 5px 0 0; font-size: 12px; opacity: 0.9;">Glaciers & world's highest peaks.</p>
                        </div>
                    </div>
                </div>

                <p style="color: var(--text-gray); font-size: 16px; line-height: 1.8; margin-bottom: 60px;">From the dense, humid jungles of the Terai plains in the south to the desolate, freezing altitudes of the High Himalayas in the north, Nepal offers a gradient of geography that invites explorers of all kinds.</p>

                <!-- Orienting Quick Facts -->
                <div style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px;">
                    <div style="border-right: 1px solid #eee; padding: 0 10px;">
                        <div style="font-size: 10px; color: #999; text-transform: uppercase; letter-spacing: 1px; font-weight: 800; margin-bottom: 5px;">Population</div>
                        <div style="font-size: 18px; font-weight: 800; color: var(--primary-blue);">~30 Million</div>
                    </div>
                    <div style="border-right: 1px solid #eee; padding: 0 10px;">
                        <div style="font-size: 10px; color: #999; text-transform: uppercase; letter-spacing: 1px; font-weight: 800; margin-bottom: 5px;">Capital</div>
                        <div style="font-size: 18px; font-weight: 800; color: var(--primary-blue);">Kathmandu</div>
                    </div>
                    <div style="border-right: 1px solid #eee; padding: 0 10px;">
                        <div style="font-size: 10px; color: #999; text-transform: uppercase; letter-spacing: 1px; font-weight: 800; margin-bottom: 5px;">Currency</div>
                        <div style="font-size: 18px; font-weight: 800; color: var(--primary-blue);">NPR (Rs)</div>
                    </div>
                    <div style="border-right: 1px solid #eee; padding: 0 10px;">
                        <div style="font-size: 10px; color: #999; text-transform: uppercase; letter-spacing: 1px; font-weight: 800; margin-bottom: 5px;">Language</div>
                        <div style="font-size: 18px; font-weight: 800; color: var(--primary-blue);">Nepali</div>
                    </div>
                    <div style="padding: 0 10px;">
                        <div style="font-size: 10px; color: #999; text-transform: uppercase; letter-spacing: 1px; font-weight: 800; margin-bottom: 5px;">Time Zone</div>
                        <div style="font-size: 18px; font-weight: 800; color: var(--primary-blue);">UTC+5:45</div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- TAB 2: ARRIVING AND ENTERING (Malaysia Prototype Replica) -->
    <div id="tab-arriving" class="tab-panel" style="display: none; padding: 80px 0;">
        <div class="container" style="max-width: 1000px;">
            <h2 style="font-size: 32px; font-weight: 500; color: #333; margin-bottom: 20px;">Getting To Nepal</h2>
            <p style="color: #666; font-size: 16px; line-height: 1.8; margin-bottom: 50px; max-width: 600px;">
                Prepare for a stress-free journey by planning ahead! Before embarking on your adventure to the breathtaking country of Nepal, equip yourself with essential information to ensure a smooth and enjoyable visit.
            </p>

            <h3 style="font-size: 24px; font-weight: 500; color: #333; margin-bottom: 20px;">By Air</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-bottom: 40px; align-items: start;">
                <div>
                    <p style="color: #666; font-size: 15px; line-height: 1.8; margin-bottom: 20px;">
                        Situated in Kathmandu, Tribhuvan International Airport (TIA) serves as the central entrance to Nepal. This airport is conveniently located approximately 5 kilometers away from the bustling tourist hub of Thamel.
                    </p>
                    <p style="color: #666; font-size: 15px; line-height: 1.8; margin-bottom: 20px;">
                        In addition to TIA, Nepal boasts newly established international airports like Gautam Buddha International Airport in Bhairahawa and Pokhara International Airport that connect visitors directly to specific regional destinations.
                    </p>
                </div>
                <div>
                    <img src="../images/kathmandu_night_hero.png" alt="Kathmandu City" style="width: 100%; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); object-fit: cover; aspect-ratio: 16/9;">
                </div>
            </div>

            <!-- Airlines Table -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 60px;">
                <thead>
                    <tr style="border-top: 1px solid #ddd; border-bottom: 1px solid #ddd;">
                        <th style="padding: 15px 0; text-align: left; font-size: 14px; color: #111;">Nepal Airlines</th>
                        <th style="padding: 15px 0; text-align: left; font-size: 14px; color: #111;">Himalaya Airlines</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 25px 30px 25px 0; vertical-align: top; width: 50%;">
                            <p style="color: #555; font-size: 14px; line-height: 1.8; margin-bottom: 15px;">Nepal Airlines, proudly serving as the flag carrier of Nepal, deeply connects global travelers to the majestic Himalayas.</p>
                            <span style="font-size: 14px; color: #555;">Website : <a href="https://www.nepalairlines.com.np" target="_blank" rel="noopener noreferrer" style="color: #2b70c9;">https://www.nepalairlines.com.np</a></span>
                        </td>
                        <td style="padding: 25px 0 25px 20px; vertical-align: top; width: 50%;">
                            <p style="color: #555; font-size: 14px; line-height: 1.8; margin-bottom: 15px;">Himalaya Airlines is a premium international carrier bridging South Asian destinations directly with Kathmandu.</p>
                            <span style="font-size: 14px; color: #555;">Website : <a href="https://www.himalaya-airlines.com" target="_blank" rel="noopener noreferrer" style="color: #2b70c9;">https://www.himalaya-airlines.com</a></span>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h3 style="font-size: 24px; font-weight: 500; color: #333; margin-bottom: 20px;">By Land</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-bottom: 40px; align-items: center;">
                <div>
                    <p style="color: #666; font-size: 15px; line-height: 1.8; margin-bottom: 20px;">
                        Nepal welcomes visitors from India and China through different entry points for those arriving by road. Enjoy scenic overland routes crossing directly into the foothills and lush plains.
                    </p>
                    <p style="color: #666; font-size: 15px; line-height: 1.8;">
                        Popular entry points from India include Sunauli, Kakarbhitta, and Birgunj. From Tibet (China), the spectacular Rasuwagadhi border offers dramatic Himalayan views.
                    </p>
                </div>
                <div>
                    <img src="../images/lumbini_temple.png" alt="Lumbini Entry Point Area" style="width: 100%; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); object-fit: cover; aspect-ratio: 16/9;">
                </div>
            </div>

            <!-- Borders Table -->
            <table style="width: 60%; border-collapse: collapse; margin-bottom: 60px;">
                <thead>
                    <tr style="border-top: 1px solid #ddd; border-bottom: 1px solid #ddd;">
                        <th style="padding: 15px 30px 15px 0; text-align: left; font-size: 14px; color: #111;">Country</th>
                        <th style="padding: 15px 0; text-align: left; font-size: 14px; color: #111;">Border</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 20px 30px 20px 0; font-size: 14px; color: #555; border-bottom: 1px solid #eee;">India</td>
                        <td style="padding: 20px 0; font-size: 14px; color: #555; border-bottom: 1px solid #eee;">Sunauli (Bhairahawa)</td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 30px 20px 0; font-size: 14px; color: #555; border-bottom: 1px solid #eee;">India</td>
                        <td style="padding: 20px 0; font-size: 14px; color: #555; border-bottom: 1px solid #eee;">Kakarbhitta</td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 30px 20px 0; font-size: 14px; color: #555; border-bottom: 1px solid #ddd;">China</td>
                        <td style="padding: 20px 0; font-size: 14px; color: #555; border-bottom: 1px solid #ddd;">Rasuwagadhi (Kerung)</td>
                    </tr>
                </tbody>
            </table>

            <h2 style="font-size: 32px; font-weight: 500; color: #333; margin-bottom: 30px;">Passports & Visas</h2>
            
            <h3 style="font-size: 22px; font-weight: 500; color: #333; margin-bottom: 20px;">Entry Requirements</h3>
            <p style="color: #666; font-size: 15px; line-height: 1.8; margin-bottom: 15px;">
                Before entering Nepal, there are several requirements that visitors must meet. These include having a valid passport with at least 6 months validity from your arrival date.
            </p>
            <p style="color: #666; font-size: 15px; line-height: 1.8; margin-bottom: 40px;">
                Please visit the official portal of the <a href="https://www.immigration.gov.np/en" target="_blank" rel="noopener noreferrer" style="color: #2b70c9;">Department of Immigration</a> for more details about the <a href="https://www.immigration.gov.np/en/page/tourist-visa" target="_blank" rel="noopener noreferrer" style="color: #2b70c9;">entry requirements</a>.
            </p>

            <h3 style="font-size: 22px; font-weight: 500; color: #333; margin-bottom: 20px;">Visa</h3>
            <p style="color: #666; font-size: 15px; line-height: 1.8; margin-bottom: 25px;">
                Most international visitors can easily obtain a Tourist Visa on Arrival at Tribhuvan International Airport and other designated land borders.
            </p>

            <!-- Visa Fee Table -->
            <div style="margin-bottom: 40px; background: #fff; border: 1px solid #eee; border-radius: 8px; overflow: hidden;">
                <div style="background: #f8f9fa; padding: 15px 20px; font-weight: 800; color: var(--primary-blue); border-bottom: 1px solid #eee; display: flex; justify-content: space-between;">
                    <span>Tourist Visa Type</span>
                    <span>Fee (USD)</span>
                </div>
                <div style="padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between;">
                    <span>15 Days (Multiple Entry)</span>
                    <span style="font-weight: 700;">$30</span>
                </div>
                <div style="padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between;">
                    <span>30 Days (Multiple Entry)</span>
                    <span style="font-weight: 700;">$50</span>
                </div>
                <div style="padding: 15px 20px; display: flex; justify-content: space-between;">
                    <span>90 Days (Multiple Entry)</span>
                    <span style="font-weight: 700;">$125</span>
                </div>
            </div>

            <div style="background: #fff8e1; border-left: 4px solid #ffc107; padding: 20px; border-radius: 4px; margin-bottom: 30px;">
                <h4 style="margin: 0 0 10px; color: #856404; font-size: 16px; font-weight: 800;">Which visa do I need?</h4>
                <ul style="margin: 0; padding-left: 20px; color: #856404; font-size: 14px; line-height: 1.6;">
                    <li><strong>SAARC Citizens:</strong> First 30 days are visa-free for citizens of SAARC countries (excluding Afghanistan).</li>
                    <li><strong>Indian Citizens:</strong> No visa required. Valid ID card (Voter ID or Passport) is sufficient for entry by air.</li>
                    <li><strong>Other Nationalities:</strong> Standard Tourist Visa on Arrival required for most countries.</li>
                    <li><strong>Visa-Exempt:</strong> Pre-arranged visa required for citizens of Nigeria, Ghana, Zimbabwe, Swaziland, Cameroon, Somalia, Liberia, Ethiopia, Iraq, Palestine, and Afghanistan.</li>
                </ul>
            </div>

            <p style="color: #666; font-size: 15px; line-height: 1.8; margin-bottom: 20px;">
                To access the necessary information on Visa options or to apply online, simply visit the official website of the <a href="https://nepalimmigration.gov.np/" target="_blank" rel="noopener noreferrer" style="color: #2b70c9;">Nepal Immigration Portal</a>.
            </p>
        </div>
    </div>

    <!-- TAB 3: GETTING AROUND NEPAL -->
    <div id="tab-getting-around" class="tab-panel" style="display: none; padding: 80px 0;">
        <div class="container" style="max-width: 1000px;">
            <h2 style="font-size: 32px; font-weight: 500; color: #333; margin-bottom: 20px;">Getting Around Nepal</h2>
            <p style="color: #666; font-size: 16px; line-height: 1.8; margin-bottom: 40px;">
                Once you arrive, navigating the beautiful landscapes of Nepal is an adventure! Here are your prime transportation options.
            </p>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-bottom: 60px; align-items: center;">
                <div>
                    <img src="../images/everest_trek.png" alt="Mountain Flight" style="width: 100%; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); object-fit: cover; aspect-ratio: 16/9;">
                </div>
                <div>
                    <h3 style="font-size: 22px; font-weight: 500; color: #333; margin-bottom: 20px;">Domestic Flights</h3>
                    <p style="color: #666; font-size: 15px; line-height: 1.8; margin-bottom: 20px;">
                        For long distances, particularly mountainous terrain like heading to Lukla (Everest) or Jomsom, robust domestic airlines (Buddha Air, Yeti Airlines) provide swift scenic flights. It's often the fastest way to travel between vast regions.
                    </p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-bottom: 40px; align-items: center;">
                <div>
                    <h3 style="font-size: 22px; font-weight: 500; color: #333; margin-bottom: 20px;">Tourist Buses</h3>
                    <p style="color: #666; font-size: 15px; line-height: 1.8; margin-bottom: 20px;">
                        Luxury and VIP tourist buses connect major cities like Kathmandu, Pokhara, and Chitwan. They are highly affordable and surprisingly comfortable for long overland routes, featuring AC and frequent rest stops.
                    </p>
                    <div style="background: #f0f4f8; padding: 15px; border-radius: 8px;">
                        <span style="font-size: 12px; font-weight: 800; color: var(--primary-blue); text-transform: uppercase; letter-spacing: 1px;">Approximate Travel Times</span>
                        <ul style="margin: 10px 0 0; padding-left: 20px; font-size: 14px; color: #555;">
                            <li><strong>Kathmandu → Pokhara:</strong> ~6–7 hrs (Bus) / 25 min (Flight)</li>
                            <li><strong>Kathmandu → Chitwan:</strong> ~5–6 hrs (Bus) / 20 min (Flight)</li>
                            <li><strong>Pokhara → Chitwan:</strong> ~4–5 hrs (Bus)</li>
                        </ul>
                    </div>
                </div>
                <div>
                    <img src="../images/sarangkot_sunrise.png" alt="Travel Logistics" style="width: 100%; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); object-fit: cover; aspect-ratio: 16/9;">
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 4: TRAVEL GUIDE -->
    <div id="tab-travel-guide" class="tab-panel" style="display: none; padding: 80px 0;">
        <div class="container" style="max-width: 1000px;">
            <h2 style="font-size: 32px; font-weight: 500; color: #333; margin-bottom: 20px;">Essential Travel Guide</h2>
            <p style="color: #666; font-size: 16px; line-height: 1.8; margin-bottom: 40px;">
                Keep these local tips in mind to respect the culture and maximize your Himalayan experience.
            </p>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-bottom: 60px; align-items: center;">
                <div>
                    <h3 style="font-size: 22px; font-weight: 500; color: #333; margin-bottom: 20px;">Currency & Connectivity</h3>
                    <p style="color: #666; font-size: 15px; line-height: 1.8; margin-bottom: 20px;">
                        The primary currency is the Nepalese Rupee (NPR). While ATMs are abundant in Kathmandu and Pokhara, be sure to carry cash when trekking in remote villages. Purchasing a local Ncell or NTC sim card at the airport ensures seamless 4G connectivity across most of the country.
                    </p>
                </div>
                <div>
                    <img src="../images/food_drinks_nepal.png" alt="Local Culture" style="width: 100%; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); object-fit: cover; aspect-ratio: 16/9;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-bottom: 40px; align-items: center;">
                <div>
                    <img src="../images/bhaktapur_temple.png" alt="Temple Etiquette" style="width: 100%; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); object-fit: cover; aspect-ratio: 16/9;">
                </div>
                <div>
                    <h3 style="font-size: 22px; font-weight: 500; color: #333; margin-bottom: 20px;">Culture & Etiquette</h3>
                    <p style="color: #666; font-size: 15px; line-height: 1.8; margin-bottom: 20px;">
                        Always walk around stupas and monasteries in a clockwise direction. Remove your shoes before entering someone's home or a sacred temple. Finally, never underestimate the warmth of local hospitality—a simple "Namaste" goes a long way!
                    </p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-bottom: 60px; align-items: start;">
                <div>
                    <h3 style="font-size: 22px; font-weight: 500; color: #333; margin-bottom: 20px;">Health & Safety</h3>
                    <p style="color: #666; font-size: 15px; line-height: 1.8; margin-bottom: 15px;">
                        <strong>Altitude Sickness (AMS):</strong> If trekking above 3,000m, ascend slowly, stay hydrated, and never ignore headaches or dizziness. Descend immediately if symptoms worsen.
                    </p>
                    <p style="color: #666; font-size: 15px; line-height: 1.8; margin-bottom: 15px;">
                        <strong>Water:</strong> Never drink tap water. Always use bottled, boiled, or purified water (using tablets/filters).
                    </p>
                    <p style="color: #666; font-size: 15px; line-height: 1.8; margin-bottom: 15px;">
                        <strong>Insurance:</strong> Ensure your travel insurance covers high-altitude rescue and helicopter evacuation if you plan to trek.
                    </p>
                </div>
                <div style="background: #fdf2f2; padding: 30px; border-radius: 12px; border: 1px solid #fee2e2;">
                    <h4 style="margin: 0 0 20px; color: #991b1b; font-size: 18px; font-weight: 800;">Emergency Contacts</h4>
                    <div style="margin-bottom: 15px; display: flex; justify-content: space-between; font-size: 14px;">
                        <span style="color: #b91c1c; font-weight: 700;">Tourist Police:</span>
                        <span style="color: #111; font-weight: 800;">1144 / 01-4247041</span>
                    </div>
                    <div style="margin-bottom: 15px; display: flex; justify-content: space-between; font-size: 14px;">
                        <span style="color: #b91c1c; font-weight: 700;">Emergency Hotline:</span>
                        <span style="color: #111; font-weight: 800;">100</span>
                    </div>
                    <div style="margin-bottom: 15px; display: flex; justify-content: space-between; font-size: 14px;">
                        <span style="color: #b91c1c; font-weight: 700;">Ambulance (Red Cross):</span>
                        <span style="color: #111; font-weight: 800;">102</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 14px;">
                        <span style="color: #b91c1c; font-weight: 700;">Fire Brigade:</span>
                        <span style="color: #111; font-weight: 800;">101</span>
                    </div>
                </div>
            </div>

            <h3 style="font-size: 24px; font-weight: 500; color: #333; margin-bottom: 30px;">Packing Guide by Season</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px;">
                <div style="padding: 25px; background: #fff; border: 1px solid #eee; border-radius: 12px;">
                    <h5 style="margin: 0 0 15px; color: var(--primary-blue); font-size: 16px; font-weight: 800;">Spring & Autumn (Trekking)</h5>
                    <ul style="margin: 0; padding-left: 20px; color: #666; font-size: 14px; line-height: 1.8;">
                        <li>Layered clothing (Moisture-wicking base)</li>
                        <li>Down jacket & windbreaker</li>
                        <li>Sturdy trekking boots (broken in)</li>
                        <li>Sunscreen & polarized sunglasses</li>
                    </ul>
                </div>
                <div style="padding: 25px; background: #fff; border: 1px solid #eee; border-radius: 12px;">
                    <h5 style="margin: 0 0 15px; color: var(--primary-blue); font-size: 16px; font-weight: 800;">Summer (Monsoon)</h5>
                    <ul style="margin: 0; padding-left: 20px; color: #666; font-size: 14px; line-height: 1.8;">
                        <li>Waterproof raincoat & bag covers</li>
                        <li>Quick-dry clothing & extra socks</li>
                        <li>Insect repellent (for leeches/mosquitoes)</li>
                        <li>Sturdy umbrella</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- JavaScript to handle Tab Switching dynamically -->
<script>
function switchTab(tabId, clickedButton) {
    // 1. Hide all tab panels
    document.querySelectorAll('.tab-panel').forEach(panel => {
        panel.style.display = 'none';
    });
    
    // 2. Remove 'active' class from all buttons
    document.querySelectorAll('.menu-card').forEach(btn => {
        btn.classList.remove('active');
        // Reset styles dynamically since external CSS might be targeting .active
        btn.style.background = '#eef2f5'; // Default inactive
        btn.style.color = 'var(--primary-blue)';
    });

    // 3. Show selected tab panel
    document.getElementById(tabId).style.display = 'block';
    
    // 4. Highlight clicked button
    clickedButton.classList.add('active');
    // Force active styles
    clickedButton.style.background = 'var(--primary-blue)';
    clickedButton.style.color = '#fff';
    
    // Also color the SVG icons properly inside the active button
    document.querySelectorAll('.menu-card .menu-icon svg').forEach(svg => {
        svg.style.stroke = 'var(--primary-blue)';
    });
    clickedButton.querySelector('.menu-icon svg').style.stroke = 'var(--primary-yellow)';

    // 5. Smooth scroll down to the content area so the user doesn't have to scroll manually
    document.querySelector('.tab-content-container').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
    });
}

// Initialize the first tab styling on page load (since button 1 is .active via HTML)
document.addEventListener('DOMContentLoaded', () => {
    let firstBtn = document.querySelector('.menu-card.active');
    if (firstBtn) {
        firstBtn.style.background = 'var(--primary-blue)';
        firstBtn.style.color = '#fff';
        let svg = firstBtn.querySelector('.menu-icon svg');
        if (svg) svg.style.stroke = 'var(--primary-yellow)';
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>