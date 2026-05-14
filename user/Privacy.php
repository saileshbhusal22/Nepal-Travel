<?php include '../includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Privacy Policy | Nepal Tours &amp; Treks</title>
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
      font-size: clamp(2rem, 5vw, 3rem); font-weight: 700; color: #fff; margin-bottom: 1rem;
    }
    .policy-hero-meta { font-size: 13px; color: rgba(255,255,255,0.35); }
    .policy-layout {
      max-width: 860px; margin: 0 auto; padding: 0 1.5rem 6rem;
      display: grid; grid-template-columns: 220px 1fr; gap: 2.5rem; align-items: start;
    }
    @media (max-width: 700px) {
      .policy-layout { grid-template-columns: 1fr; }
      .policy-toc { display: none; }
    }
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
      text-decoration: none; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.05);
      transition: color 0.2s;
    }
    .toc-link:last-child { border-bottom: none; }
    .toc-link:hover { color: #fff; }
    .other-link {
      display: block; margin-top: 1rem; font-size: 12px; color: #60a5fa;
      text-decoration: none; text-align: center;
    }
    .other-link:hover { text-decoration: underline; }
    .policy-content {}
    .section-block {
      background: rgba(15,18,30,0.88); backdrop-filter: blur(12px);
      border: 1px solid rgba(255,255,255,0.07); border-radius: 16px;
      padding: 2rem; margin-bottom: 1.2rem;
    }
    .sec-num { font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: rgba(255,255,255,0.25); margin-bottom: 6px; }
    .sec-title { font-family: 'Playfair Display', serif; font-size: 1.15rem; font-weight: 600; color: #fff; margin-bottom: 0.9rem; }
    .sec-body { font-size: 14px; line-height: 1.82; color: rgba(255,255,255,0.60); }
    .sec-body ul { margin: 0.6rem 0 0.6rem 1.2rem; }
    .sec-body li { margin-bottom: 0.35rem; }
    .alert-box {
      background: rgba(37,99,235,0.12); border: 1px solid rgba(96,165,250,0.20);
      border-radius: 10px; padding: 1rem 1.2rem; margin-bottom: 1rem;
      font-size: 13px; color: rgba(255,255,255,0.55); line-height: 1.70;
    }
    .alert-box strong { color: #93c5fd; font-weight: 600; }
    .data-table { width: 100%; border-collapse: collapse; margin-top: 0.8rem; }
    .data-table th {
      text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase;
      letter-spacing: 0.08em; color: rgba(255,255,255,0.30); padding: 8px 10px;
      border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .data-table td { font-size: 13px; color: rgba(255,255,255,0.55); padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: top; }
    .data-table tr:last-child td { border-bottom: none; }
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
    <h1>Privacy Policy</h1>
    <p class="policy-hero-meta">Nepal Tours &amp; Treks Pvt. Ltd. &nbsp;·&nbsp; Last updated: May 2026 &nbsp;·&nbsp; Effective immediately</p>
  </div>

  <div class="policy-layout">

    <aside class="policy-toc">
      <div class="toc-card">
        <p class="toc-label">Contents</p>
        <a class="toc-link" href="#collect">1. Data We Collect</a>
        <a class="toc-link" href="#use">2. How We Use It</a>
        <a class="toc-link" href="#sharing">3. Sharing &amp; Disclosure</a>
        <a class="toc-link" href="#retention">4. Data Retention</a>
        <a class="toc-link" href="#cookies">5. Cookies</a>
        <a class="toc-link" href="#security">6. Security</a>
        <a class="toc-link" href="#rights">7. Your Rights</a>
        <a class="toc-link" href="#children">8. Children's Privacy</a>
        <a class="toc-link" href="#changes">9. Policy Changes</a>
        <a class="toc-link" href="#contact-privacy">10. Contact</a>
      </div>
      <a class="other-link" href="terms.php">→ View Terms &amp; Conditions</a>
    </aside>

    <main class="policy-content">

      <div class="alert-box">
        <strong>Your privacy matters to us.</strong> Nepal Tours &amp; Treks Pvt. Ltd. is committed to protecting your personal information. This policy explains what data we collect, how we use it, and your rights under applicable Nepalese law and international data protection principles.
      </div>

      <div class="section-block" id="collect">
        <p class="sec-num">Section 01</p>
        <h2 class="sec-title">Data We Collect</h2>
        <div class="sec-body">
          <table class="data-table">
            <thead>
              <tr>
                <th>Category</th>
                <th>Examples</th>
                <th>Required?</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Identity</td>
                <td>Full name, date of birth, nationality, passport number &amp; expiry</td>
                <td>Yes — for permits &amp; immigration</td>
              </tr>
              <tr>
                <td>Contact</td>
                <td>Email address, phone number, home address</td>
                <td>Yes — for booking communication</td>
              </tr>
              <tr>
                <td>Health</td>
                <td>Medical conditions, dietary requirements, fitness level</td>
                <td>Yes — for safety on treks</td>
              </tr>
              <tr>
                <td>Emergency Contact</td>
                <td>Name, phone, relationship of next of kin</td>
                <td>Yes — mandatory for all treks</td>
              </tr>
              <tr>
                <td>Payment</td>
                <td>Card details (processed via secure gateway, not stored by us)</td>
                <td>Yes — for booking</td>
              </tr>
              <tr>
                <td>Usage</td>
                <td>Pages visited, browser type, device info, referral source</td>
                <td>No — optional analytics</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="section-block" id="use">
        <p class="sec-num">Section 02</p>
        <h2 class="sec-title">How We Use Your Data</h2>
        <div class="sec-body">
          <p>We process your personal data only for the following legitimate purposes:</p>
          <ul>
            <li>Processing, confirming, and managing your booking</li>
            <li>Obtaining required trekking and national park permits (TIMS, ACAP, NATT, etc.)</li>
            <li>Coordinating with guides, porters, hotels, and transport providers</li>
            <li>Sending trip-related communications: confirmations, itineraries, safety briefings</li>
            <li>Complying with Nepal Tourism Board (NTB) and immigration requirements</li>
            <li>Emergency response: sharing with rescue services if required for your safety</li>
            <li>Improving our services via anonymised website analytics</li>
            <li>Sending promotional offers — only with your explicit consent, unsubscribe anytime</li>
          </ul>
        </div>
      </div>

      <div class="section-block" id="sharing">
        <p class="sec-num">Section 03</p>
        <h2 class="sec-title">Sharing &amp; Disclosure</h2>
        <div class="sec-body">
          <p>We share your personal data only where necessary:</p>
          <ul>
            <li><strong style="color:#fff">Licensed service providers:</strong> Our guides, porters, accommodation, and domestic transport partners receive only the data needed to deliver your trip.</li>
            <li><strong style="color:#fff">Government authorities:</strong> Nepal Tourism Board, Department of Immigration, national park permit offices — as required by Nepalese law.</li>
            <li><strong style="color:#fff">Emergency services:</strong> Rescue teams and hospitals may receive your medical and identity data in a life-threatening situation.</li>
            <li><strong style="color:#fff">Payment processors:</strong> Secure third-party gateways process card payments. We never store full card details.</li>
          </ul>
          <p style="margin-top:0.8rem">We do <strong style="color:#fff">not</strong> sell, rent, or trade your personal data to third parties for marketing purposes.</p>
        </div>
      </div>

      <div class="section-block" id="retention">
        <p class="sec-num">Section 04</p>
        <h2 class="sec-title">Data Retention</h2>
        <div class="sec-body">
          <p>We retain your booking and personal records for <strong style="color:#fff">5 years</strong> following your trip, as required by Nepalese tourism and tax regulations. After this period, records are securely deleted or anonymised.</p>
          <p style="margin-top:0.8rem">Website analytics data (cookies) is retained for a maximum of 26 months. You may request early deletion of non-mandatory records by contacting us.</p>
        </div>
      </div>

      <div class="section-block" id="cookies">
        <p class="sec-num">Section 05</p>
        <h2 class="sec-title">Cookies &amp; Tracking</h2>
        <div class="sec-body">
          <p>Our website uses the following types of cookies:</p>
          <ul>
            <li><strong style="color:#fff">Essential cookies:</strong> Required for login sessions, booking forms, and security. Cannot be disabled.</li>
            <li><strong style="color:#fff">Analytics cookies:</strong> Google Analytics — anonymised usage data to improve our website. You may opt out via your browser or Google's opt-out tool.</li>
            <li><strong style="color:#fff">Preference cookies:</strong> Remember your currency, language, and display preferences.</li>
          </ul>
          <p style="margin-top:0.8rem">You can manage cookies through your browser settings at any time. Disabling non-essential cookies will not affect your ability to make a booking.</p>
        </div>
      </div>

      <div class="section-block" id="security">
        <p class="sec-num">Section 06</p>
        <h2 class="sec-title">Security</h2>
        <div class="sec-body">
          <p>We take reasonable technical and organisational measures to protect your personal data, including:</p>
          <ul>
            <li>SSL/TLS encryption on all data transmitted to and from our website</li>
            <li>Secure, access-controlled databases for personal and booking records</li>
            <li>PCI-DSS compliant payment gateways — we never see or store full card numbers</li>
            <li>Regular security audits and staff data-handling training</li>
          </ul>
          <p style="margin-top:0.8rem">No system is 100% secure. In the unlikely event of a data breach, we will notify affected users within 72 hours and take immediate remedial action.</p>
        </div>
      </div>

      <div class="section-block" id="rights">
        <p class="sec-num">Section 07</p>
        <h2 class="sec-title">Your Rights</h2>
        <div class="sec-body">
          <p>You have the following rights regarding your personal data:</p>
          <ul>
            <li><strong style="color:#fff">Access:</strong> Request a copy of the personal data we hold about you.</li>
            <li><strong style="color:#fff">Correction:</strong> Request correction of inaccurate or incomplete data.</li>
            <li><strong style="color:#fff">Deletion:</strong> Request deletion of data we no longer have a legal obligation to retain.</li>
            <li><strong style="color:#fff">Portability:</strong> Receive your data in a common machine-readable format.</li>
            <li><strong style="color:#fff">Objection:</strong> Object to processing for direct marketing at any time.</li>
            <li><strong style="color:#fff">Withdraw consent:</strong> Unsubscribe from marketing emails at any time via the link in any email.</li>
          </ul>
          <p style="margin-top:0.8rem">To exercise any of these rights, email <a href="mailto:privacy@nepaltours.com" style="color:#60a5fa">privacy@nepaltours.com</a>. We respond to all requests within 14 business days.</p>
        </div>
      </div>

      <div class="section-block" id="children">
        <p class="sec-num">Section 08</p>
        <h2 class="sec-title">Children's Privacy</h2>
        <div class="sec-body">
          <p>Our services are not directed to children under 18. Minors travelling with us must be booked by a parent or legal guardian, whose information will be used for all communications and legal purposes. We do not knowingly collect personal data directly from children.</p>
        </div>
      </div>

      <div class="section-block" id="changes">
        <p class="sec-num">Section 09</p>
        <h2 class="sec-title">Policy Changes</h2>
        <div class="sec-body">
          <p>We may update this Privacy Policy from time to time. When we make material changes, we will post the updated policy on this page with a revised effective date and, where appropriate, notify registered users by email. Continued use of our website or services after changes constitutes acceptance of the revised policy.</p>
        </div>
      </div>

      <div class="section-block" id="contact-privacy">
        <p class="sec-num">Section 10</p>
        <h2 class="sec-title">Contact Us</h2>
        <div class="sec-body">
          <p>For any privacy-related questions, requests, or concerns, please reach out to us directly:</p>
          <div class="contact-card">
            <div class="contact-item"><strong>Company</strong>Nepal Tours &amp; Treks Pvt. Ltd.</div>
            <div class="contact-item"><strong>Address</strong>Thamel, Kathmandu, Nepal</div>
            <div class="contact-item"><strong>Privacy Email</strong>privacy@nepaltours.com</div>
            <div class="contact-item"><strong>General Email</strong>info@nepaltours.com</div>
            <div class="contact-item"><strong>Phone</strong>+977-1-XXXXXXX</div>
          </div>
        </div>
      </div>

    </main>
  </div>

</div>
<?php include '../includes/footer.php'; ?>
</body>
</html>