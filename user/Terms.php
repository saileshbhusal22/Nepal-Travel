<?php include '../includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Terms &amp; Conditions | Nepal Tours &amp; Treks</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'DM Sans', sans-serif;
      background-image: url('https://www.discovertreks.com/wp-content/uploads/2017/09/Nepal-discover-himalayan-treks.jpg');
      background-size: cover; background-position: center; background-attachment: fixed;
      color: #fff; min-height: 100vh;
    }
    .page-overlay { min-height: 100vh; background: rgba(8,10,20,0.55); }

    /* Hero */
    .policy-hero {
      text-align: center; padding: 5rem 2rem 4rem;
      background: linear-gradient(to bottom, rgba(0,0,0,0.50) 0%, transparent 100%);
    }
    .policy-hero-eye {
      font-size: 10px; font-weight: 700; letter-spacing: 0.22em;
      text-transform: uppercase; color: rgba(255,255,255,0.40); margin-bottom: 0.8rem;
    }
    .policy-hero h1 {
      font-family: 'Playfair Display', serif;
      font-size: clamp(2rem, 5vw, 3rem); font-weight: 700; color: #fff;
      margin-bottom: 1rem;
    }
    .policy-hero-meta { font-size: 13px; color: rgba(255,255,255,0.35); }

    /* Layout */
    .policy-layout {
      max-width: 860px; margin: 0 auto;
      padding: 0 1.5rem 6rem;
      display: grid; grid-template-columns: 220px 1fr; gap: 2.5rem; align-items: start;
    }
    @media (max-width: 700px) {
      .policy-layout { grid-template-columns: 1fr; }
      .policy-toc { display: none; }
    }

    /* TOC */
    .policy-toc { position: sticky; top: 2rem; }
    .toc-card {
      background: rgba(15,18,30,0.88); backdrop-filter: blur(12px);
      border: 1px solid rgba(255,255,255,0.07); border-radius: 14px; padding: 1.2rem;
    }
    .toc-label {
      font-size: 10px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase;
      color: rgba(255,255,255,0.28); margin-bottom: 0.8rem;
    }
    .toc-link {
      display: block; font-size: 13px; color: rgba(255,255,255,0.45);
      text-decoration: none; padding: 6px 0;
      border-bottom: 1px solid rgba(255,255,255,0.05);
      transition: color 0.2s;
    }
    .toc-link:last-child { border-bottom: none; }
    .toc-link:hover { color: #fff; }
    .toc-link.active { color: #60a5fa; }

    .other-link {
      display: block; margin-top: 1rem;
      font-size: 12px; color: #60a5fa; text-decoration: none; text-align: center;
    }
    .other-link:hover { text-decoration: underline; }

    /* Content */
    .policy-content {}
    .section-block {
      background: rgba(15,18,30,0.88); backdrop-filter: blur(12px);
      border: 1px solid rgba(255,255,255,0.07); border-radius: 16px;
      padding: 2rem; margin-bottom: 1.2rem;
    }
    .sec-num {
      font-size: 10px; font-weight: 700; letter-spacing: 0.14em;
      text-transform: uppercase; color: rgba(255,255,255,0.25); margin-bottom: 6px;
    }
    .sec-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.15rem; font-weight: 600; color: #fff; margin-bottom: 0.9rem;
    }
    .sec-body { font-size: 14px; line-height: 1.82; color: rgba(255,255,255,0.60); }
    .sec-body ul { margin: 0.6rem 0 0.6rem 1.2rem; }
    .sec-body li { margin-bottom: 0.35rem; }

    /* Highlight box */
    .alert-box {
      background: rgba(37,99,235,0.12); border: 1px solid rgba(96,165,250,0.20);
      border-radius: 10px; padding: 1rem 1.2rem; margin-bottom: 1rem;
      font-size: 13px; color: rgba(255,255,255,0.55); line-height: 1.70;
    }
    .alert-box strong { color: #93c5fd; font-weight: 600; }

    /* Contact card */
    .contact-card {
      background: rgba(37,99,235,0.10); border: 1px solid rgba(96,165,250,0.15);
      border-radius: 12px; padding: 1.2rem 1.5rem; margin-top: 1rem;
      display: flex; flex-wrap: wrap; gap: 1rem 2rem;
    }
    .contact-item { font-size: 13px; color: rgba(255,255,255,0.55); }
    .contact-item strong { color: rgba(255,255,255,0.80); font-weight: 500; display: block; margin-bottom: 2px; }
  </style>
</head>
<body>
<div class="page-overlay">

  <div class="policy-hero">
    <p class="policy-hero-eye">Legal Documents</p>
    <h1>Terms &amp; Conditions</h1>
    <p class="policy-hero-meta">Nepal Tours &amp; Treks Pvt. Ltd. &nbsp;·&nbsp; Last updated: May 2026 &nbsp;·&nbsp; Effective immediately</p>
  </div>

  <div class="policy-layout">

    <!-- TOC -->
    <aside class="policy-toc">
      <div class="toc-card">
        <p class="toc-label">Contents</p>
        <a class="toc-link" href="#booking">1. Booking &amp; Confirmation</a>
        <a class="toc-link" href="#payment">2. Payment</a>
        <a class="toc-link" href="#cancellation">3. Cancellation &amp; Refunds</a>
        <a class="toc-link" href="#insurance">4. Travel Insurance</a>
        <a class="toc-link" href="#health">5. Health &amp; Fitness</a>
        <a class="toc-link" href="#altitude">6. Altitude &amp; Risk</a>
        <a class="toc-link" href="#itinerary">7. Itinerary Changes</a>
        <a class="toc-link" href="#conduct">8. Conduct</a>
        <a class="toc-link" href="#liability">9. Liability</a>
        <a class="toc-link" href="#permits">10. Permits &amp; Visas</a>
        <a class="toc-link" href="#governing">11. Governing Law</a>
        <a class="toc-link" href="#contact-terms">12. Contact</a>
      </div>
      <a class="other-link" href="privacy.php">→ View Privacy Policy</a>
    </aside>

    <!-- Content -->
    <main class="policy-content">

      <div class="alert-box">
        <strong>Please read carefully.</strong> By making a booking with Nepal Tours &amp; Treks Pvt. Ltd., you confirm that you have read, understood, and agree to these Terms &amp; Conditions on behalf of yourself and all members of your party.
      </div>

      <div class="section-block" id="booking">
        <p class="sec-num">Section 01</p>
        <h2 class="sec-title">Booking &amp; Confirmation</h2>
        <div class="sec-body">
          <p>All bookings are confirmed only upon receipt of a minimum 30% non-refundable deposit and a completed booking form. A booking confirmation and receipt will be sent to the email address provided within 2 business days.</p>
          <p style="margin-top:0.8rem">Nepal Tours &amp; Treks Pvt. Ltd. is a licensed trekking and tour operator registered under the Nepal Tourism Board (NTB) and the Trekking Agencies Association of Nepal (TAAN).</p>
        </div>
      </div>

      <div class="section-block" id="payment">
        <p class="sec-num">Section 02</p>
        <h2 class="sec-title">Payment</h2>
        <div class="sec-body">
          <p>Full payment is required no later than 30 days before the departure date. For bookings made within 30 days of departure, full payment is due at the time of booking.</p>
          <ul>
            <li>Accepted payment methods: bank transfer, credit/debit card, eSewa, Khalti, and cash (NPR or USD).</li>
            <li>All prices are quoted in Nepalese Rupees (NPR) unless otherwise stated.</li>
            <li>International card payments may incur a 3% processing fee.</li>
            <li>Currency exchange rates for USD/EUR payments are applied at the rate on the day of payment.</li>
          </ul>
        </div>
      </div>

      <div class="section-block" id="cancellation">
        <p class="sec-num">Section 03</p>
        <h2 class="sec-title">Cancellation &amp; Refund Policy</h2>
        <div class="sec-body">
          <ul>
            <li><strong style="color:#fff">30+ days before departure:</strong> 70% refund of total trip cost (deposit not refunded).</li>
            <li><strong style="color:#fff">15–29 days before departure:</strong> 50% refund.</li>
            <li><strong style="color:#fff">7–14 days before departure:</strong> 25% refund.</li>
            <li><strong style="color:#fff">Less than 7 days / No-show:</strong> No refund.</li>
          </ul>
          <p style="margin-top:0.8rem">Deposits are non-refundable under all circumstances. Refunds are processed within 14 business days via the original payment method.</p>
          <p style="margin-top:0.8rem">If Nepal Tours &amp; Treks cancels a trip due to causes within our control, clients receive a full refund or the option to reschedule at no additional cost.</p>
        </div>
      </div>

      <div class="section-block" id="insurance">
        <p class="sec-num">Section 04</p>
        <h2 class="sec-title">Travel Insurance</h2>
        <div class="sec-body">
          <p>Comprehensive travel and medical insurance is <strong style="color:#fff">mandatory</strong> for all trekking, mountaineering, and adventure packages. Your policy must include:</p>
          <ul>
            <li>Emergency medical treatment and hospitalization</li>
            <li>High-altitude helicopter rescue and evacuation</li>
            <li>Trip cancellation and curtailment</li>
            <li>Personal liability</li>
          </ul>
          <p style="margin-top:0.8rem">Proof of valid insurance must be submitted before or upon arrival. We reserve the right to refuse participation if adequate insurance cannot be confirmed.</p>
        </div>
      </div>

      <div class="section-block" id="health">
        <p class="sec-num">Section 05</p>
        <h2 class="sec-title">Health &amp; Fitness</h2>
        <div class="sec-body">
          <p>Participants must be in good physical health and fitness appropriate for the chosen package. It is your responsibility to:</p>
          <ul>
            <li>Disclose any pre-existing medical conditions at the time of booking.</li>
            <li>Consult a qualified physician before undertaking high-altitude treks.</li>
            <li>Ensure vaccinations are up to date as recommended for Nepal travel.</li>
          </ul>
          <p style="margin-top:0.8rem">Nepal Tours &amp; Treks reserves the right to decline or remove from a trip any participant deemed medically or physically unfit to continue safely, without liability for refund.</p>
        </div>
      </div>

      <div class="section-block" id="altitude">
        <p class="sec-num">Section 06</p>
        <h2 class="sec-title">Altitude &amp; Risk</h2>
        <div class="sec-body">
          <p>Trekking at high altitude (above 3,000m) carries inherent risks including Acute Mountain Sickness (AMS), High-Altitude Pulmonary Edema (HAPE), and High-Altitude Cerebral Edema (HACE). Our certified guides follow UIAA-recommended acclimatization protocols.</p>
          <p style="margin-top:0.8rem">Clients must follow all guide instructions regarding pace, rest days, and descent. Nepal Tours &amp; Treks is not liable for altitude-related illness, injury, or death. Refusal to descend when advised may void your insurance coverage.</p>
        </div>
      </div>

      <div class="section-block" id="itinerary">
        <p class="sec-num">Section 07</p>
        <h2 class="sec-title">Itinerary Changes</h2>
        <div class="sec-body">
          <p>Nepal Tours &amp; Treks reserves the right to alter, modify, or cancel any itinerary without prior notice due to:</p>
          <ul>
            <li>Adverse weather conditions or natural disasters</li>
            <li>Trail closures or permit restrictions by authorities</li>
            <li>Health emergencies or evacuation requirements</li>
            <li>Political unrest or government directives</li>
          </ul>
          <p style="margin-top:0.8rem">We will always endeavour to provide equivalent alternatives. No refund is issued for changes caused by circumstances beyond our control.</p>
        </div>
      </div>

      <div class="section-block" id="conduct">
        <p class="sec-num">Section 08</p>
        <h2 class="sec-title">Client Conduct</h2>
        <div class="sec-body">
          <p>Clients are expected to respect local culture, religious sites, and customs at all times. Nepal is a deeply spiritual country — please dress modestly at monasteries and temples, ask before photographing people, and follow your guide's cultural guidance.</p>
          <p style="margin-top:0.8rem">Nepal Tours &amp; Treks reserves the right to remove any client from a trip who behaves in a disruptive, disrespectful, or dangerous manner, without refund.</p>
        </div>
      </div>

      <div class="section-block" id="liability">
        <p class="sec-num">Section 09</p>
        <h2 class="sec-title">Limitation of Liability</h2>
        <div class="sec-body">
          <p>Nepal Tours &amp; Treks acts as a booking agent for independent accommodation, transport, and activity providers. We are not liable for personal injury, illness, death, loss of property, delay, or additional expenses caused by:</p>
          <ul>
            <li>Acts of third-party suppliers (hotels, airlines, local transport)</li>
            <li>Acts of god, natural disasters, extreme weather</li>
            <li>Political events, strikes, or government actions</li>
            <li>Client negligence or failure to follow guide instructions</li>
          </ul>
        </div>
      </div>

      <div class="section-block" id="permits">
        <p class="sec-num">Section 10</p>
        <h2 class="sec-title">Permits, Visas &amp; Regulations</h2>
        <div class="sec-body">
          <p>Required trekking permits (TIMS card, ACAP, Langtang, Everest National Park, etc.) are arranged by Nepal Tours &amp; Treks and are included in package prices where stated. Clients must hold a valid Nepal tourist visa for the duration of their stay.</p>
          <p style="margin-top:0.8rem">Clients are responsible for ensuring their passport is valid for at least 6 months beyond the trip end date. We are not responsible for visa rejections or permit denials due to client documentation issues.</p>
        </div>
      </div>

      <div class="section-block" id="governing">
        <p class="sec-num">Section 11</p>
        <h2 class="sec-title">Governing Law &amp; Disputes</h2>
        <div class="sec-body">
          <p>These Terms &amp; Conditions are governed by and construed in accordance with the laws of Nepal. Any disputes arising from a booking with Nepal Tours &amp; Treks shall be subject to the exclusive jurisdiction of the courts of Kathmandu, Bagmati Province, Nepal.</p>
        </div>
      </div>

      <div class="section-block" id="contact-terms">
        <p class="sec-num">Section 12</p>
        <h2 class="sec-title">Contact Us</h2>
        <div class="sec-body">
          <p>For questions regarding these Terms &amp; Conditions, please contact our office:</p>
          <div class="contact-card">
            <div class="contact-item"><strong>Company</strong>Nepal Tours &amp; Treks Pvt. Ltd.</div>
            <div class="contact-item"><strong>Address</strong>Thamel, Kathmandu, Nepal</div>
            <div class="contact-item"><strong>Email</strong>info@nepaltours.com</div>
            <div class="contact-item"><strong>Phone</strong>+977-1-XXXXXXX</div>
            <div class="contact-item"><strong>NTB Reg.</strong>XXXXXXX</div>
          </div>
        </div>
      </div>

    </main>
  </div>

</div>
<?php include '../includes/footer.php'; ?>
</body>
</html>