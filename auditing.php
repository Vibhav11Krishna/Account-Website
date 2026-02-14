
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Accounting Firm</title>
  <link rel="stylesheet" href="assets/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="assets/script.js" defer></script>

</head>
<body>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <header class="site-header">
    <div class="header-container">

      <a href="#home" class="logo-group">
        <img src="assets/logo.jpg" alt="Logo" class="main-logo">
        <div class="logo-text">
          <span class="firm-name">KARUNESH KUMAR</span>
          <span class="firm-sub">& ASSOCIATES</span>
        </div>
      </a>

      <nav class="nav-desktop">
        <ul class="nav-list">
          <li><a href="index.php">Home</a></li>
          <li><a href="index.php">About</a></li>
          <li><a href="index.php">Services</a></li>
          <li><a href="blogs.php">Insights</a></li>
          <li><a href="index.php">Testimonials</a></li>
          <li><a href="index.php">Team</a></li>
          <li><a href="index.php">Careers</a></li>
          <li><a href="index.php">Contact</a></li>
        </ul>
         <button class="btn-login" onclick="window.location.href='./Register.php'">
    Portal Login
</button>
      </nav>

      <button class="ham-trigger" id="openDrawer" aria-label="Toggle Menu">
        <span class="ham-bar"></span>
        <span class="ham-bar"></span>
        <span class="ham-bar"></span>
      </button>

    </div>
  </header>

  <div class="drawer-overlay" id="drawerOverlay"></div>
  <div class="right-drawer" id="rightDrawer">
    <div class="drawer-header">
      <span class="drawer-label">Menu</span>
      <button class="drawer-close" id="closeDrawer">&times;</button>
    </div>
    <div class="drawer-content">
      <button class="drawer-login-btn" onclick="window.location.href='./Register.php'">
    Portal Login
</button>
      <nav class="drawer-nav-list">
        <a href="index.php" class="d-link"><i class="fa-solid fa-house"></i> Home</a>
        <a href="index.php" class="d-link"><i class="fa-solid fa-building"></i> About</a>
        <a href="index.php" class="d-link"><i class="fa-solid fa-gears"></i> Services</a>
        <a href="blogs.php" class="d-link"><i class="fa-solid fa-chart-line"></i> Insights</a>
        <a href="index.php" class="d-link"><i class="fa-solid fa-user-circle"></i>Testimonials</a>
        <a href="index.php" class="d-link"><i class="fa-solid fa-users"></i> Team</a>
        <a href="index.php" class="d-link"><i class="fa-solid fa-user-tie"></i> Careers</a>
        <a href="index.php" class="d-link"><i class="fa-solid fa-envelope"></i> Contact</a>
      </nav>
    </div>
  </div>

  <style>
    :root {
      --navy: #0b3c74;
      --orange: #ff8c00;
      --white: #ffffff;
    }

    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
      padding-top: 80px;
    }

    /* HEADER ALIGNMENT CORE */
    .site-header {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100px;
      background: var(--white);
      z-index: 1000;
      display: flex;
      align-items: center;
      /* Vertical alignment */
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }

    .header-container {
      width: 100%;
      max-width: 1300px;
      margin: 0 auto;
      padding: 0 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      /* This aligns Logo and Hamburger */
    }

    /* LOGO BOX */
    .logo-group {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      line-height: 0;
    }

    .main-logo {
      height: 46px;
      width: auto;
    }

    .logo-text {
      display: flex;
      flex-direction: column;
      line-height: 1.2;
    }

    .firm-name {
      color: var(--navy);
      font-weight: 700;
      font-size: 18px;
    }

    .firm-sub {
      color: var(--orange);
      font-size: 10px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* HAMBURGER ALIGNMENT FIX */
    .ham-trigger {
      display: none;
      background: #f1f5f9;
      border: none;
      width: 44px;
      height: 44px;
      border-radius: 8px;
      cursor: pointer;
      flex-direction: column;
      justify-content: center;
      /* Centers bars vertically */
      align-items: center;
      /* Centers bars horizontally */
      gap: 5px;
      /* Space between bars */
      padding: 0;
    }

    .ham-bar {
      width: 22px;
      height: 2px;
      background-color: var(--navy);
      border-radius: 2px;
    }

    /* DESKTOP NAV */
    .nav-desktop {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .nav-list {
      display: flex;
      list-style: none;
      gap: 20px;
      margin: 0;
      padding: 0;
    }

    .nav-list a {
      text-decoration: none;
      color: #444;
      font-weight: 600;
      font-size: 14px;
    }

    .btn-login {
      background: var(--navy);
      color: #fff;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      font-weight: 700;
      cursor: pointer;
      margin-left: 10px;
    }

    /* DRAWER */
    .right-drawer {
      position: fixed;
      top: 0;
      right: -320px;
      width: 300px;
      height: 100%;
      background: #fff;
      z-index: 2000;
      transition: 0.4s cubic-bezier(0.77, 0, 0.175, 1);
      box-shadow: -10px 0 30px rgba(0, 0, 0, 0.1);
    }

    .right-drawer.active {
      right: 0;
    }

    .drawer-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.4);
      z-index: 1999;
      display: none;
    }

    .drawer-overlay.active {
      display: block;
    }

    .drawer-header {
      padding: 20px;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .drawer-close {
      background: none;
      border: none;
      font-size: 35px;
      cursor: pointer;
      color: #999;
    }

    .drawer-content {
      padding: 30px 20px;
    }

    .drawer-login-btn {
      width: 100%;
      padding: 15px;
      background: var(--navy);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-weight: 700;
      margin-bottom: 25px;
      cursor: pointer;
    }

    .d-link {
      display: flex;
      align-items: center;
      gap: 15px;
      padding: 15px 0;
      text-decoration: none;
      color: var(--navy);
      font-weight: 700;
      border-bottom: 1px solid #f9f9f9;
    }

    @media (max-width: 1100px) {
      .nav-desktop {
        display: none;
      }

      .ham-trigger {
        display: flex;
      }
    }
  </style>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const openBtn = document.getElementById('openDrawer');
      const closeBtn = document.getElementById('closeDrawer');
      const drawer = document.getElementById('rightDrawer');
      const overlay = document.getElementById('drawerOverlay');

      const toggle = () => {
        drawer.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.style.overflow = drawer.classList.contains('active') ? 'hidden' : '';
      };

      if (openBtn) openBtn.addEventListener('click', toggle);
      if (closeBtn) closeBtn.addEventListener('click', toggle);
      if (overlay) overlay.addEventListener('click', toggle);
      document.querySelectorAll('.d-link').forEach(l => l.addEventListener('click', toggle));
    });

    function openLogin() {
      alert("Opening Client Portal...");
    }
  </script>

  <section class="incorp-final-section" id="incorporation-service">
    <div class="container-premium">

      <div class="section-intro">
        <div class="service-box-tag">
          <span class="s-num">Service 03</span>
          <span class="s-name">Audit & Assurance</span>
        </div>
        <h2>Audit & <br><span class="text-navy">Assurance</span> <span class="text-orange">Services</span></h2>
        <div class="dual-accent">
          <span class="bar-navy"></span>
          <span class="bar-orange"></span>
        </div>
        <p class="desc-text">
         We provide independent and risk-focused audit services to strengthen governance, internal controls, and stakeholder confidence. Our audits are conducted in compliance with applicable laws and professional standards.
        </p>
      </div>

      <div class="premium-grid">
        <div class="premium-card">
          <div class="card-image-box">
            <img src="assets/Audit1.jpg" alt="Entity Registration">
            <div class="card-overlay"></div>
          </div>
          <div class="card-icon-float"><i class="fa-solid fa-gavel"></i></div>
          <div class="card-details">
            <h4>Statutory Audit</h4>
            <p>Audits conducted as per applicable laws and standards</p>
          </div>
          <div class="hover-border"></div>
        </div>

        <div class="premium-card">
          <div class="card-image-box">
            <img src="assets/Audit2.jpg" alt="Startup India">
            <div class="card-overlay"></div>
          </div>
          <div class="card-icon-float"><i class="fa-solid fa-magnifying-glass-chart"></i></div>
          <div class="card-details">
            <h4>Internal Audit</h4>
            <p>Evaluation of internal processes and control systems</p>
          </div>
          <div class="hover-border"></div>
        </div>

        <div class="premium-card">
          <div class="card-image-box">
            <img src="assets/Audit3.jpg" alt="Equity Design">
            <div class="card-overlay"></div>
          </div>
          <div class="card-icon-float"><i class="fa-solid fa-calculator"></i></div>
          <div class="card-details">
            <h4>Cost Audit</h4>
            <p>Review of cost records and compliance requirements.</p>
          </div>
          <div class="hover-border"></div>
        </div>

         <div class="premium-card">
          <div class="card-image-box">
            <img src="assets/Audit4.jpg" alt="Startup India">
            <div class="card-overlay"></div>
          </div>
          <div class="card-icon-float"><i class="fa-solid fa-building-columns"></i></div>
          <div class="card-details">
            <h4>Bank Concurrent Audit</h4>
            <p>Ongoing audit support for banking operations</p>
          </div>
          <div class="hover-border"></div>
        </div>

         <div class="premium-card">
          <div class="card-image-box">
            <img src="assets/Audit5.jpg" alt="Startup India">
            <div class="card-overlay"></div>
          </div>
          <div class="card-icon-float"><i class="fa-solid fa-boxes-stacked"></i></div>
          <div class="card-details">
            <h4>Stock Audit</h4>
            <p>Verification and valuation of inventory.</p>
          </div>
          <div class="hover-border"></div>
        </div>

         <div class="premium-card">
          <div class="card-image-box">
            <img src="assets/Audit6.jpg" alt="Startup India">
            <div class="card-overlay"></div>
          </div>
          <div class="card-icon-float"><i class="fa-solid fa-chart-line"></i></div>
          <div class="card-details">
            <h4>Management Audit</h4>
            <p>Assessment of operational efficiency and governance.</p>
          </div>
          <div class="hover-border"></div>
        </div>
      </div>

<section class="process-section">
    <div class="container-premium">
        <div class="section-intro">
            <h2>Our <span class="text-orange">Engagement Process</span></h2>
        </div>

        <div class="process-wrapper">
            <div class="process-step">
                <div class="step-number">01</div>
                <div class="step-icon"><i class="fas fa-comments"></i></div>
                <h4>Plan the Audit</h4>
                <p>Define scope, timelines, and audit objectives</p>
                <div class="step-arrow"><i class="fas fa-chevron-right"></i></div>
            </div>

            <div class="process-step">
                <div class="step-number">02</div>
                <div class="step-icon"><i class="fas fa-microscope"></i></div>
                <h4>Review Records </h4>
                <p>Examine books, documents, and internal controls</p>
                <div class="step-arrow"><i class="fas fa-chevron-right"></i></div>
            </div>

            <div class="process-step">
                <div class="step-number">03</div>
                <div class="step-icon"><i class="fas fa-cogs"></i></div>
                <h4>Test & Evaluate </h4>
                <p>Perform audit procedures and risk assessments</p>
                <div class="step-arrow"><i class="fas fa-chevron-right"></i></div>
            </div>

            <div class="process-step">
                <div class="step-number">04</div>
                <div class="step-icon"><i class="fas fa-chart-line"></i></div>
                <h4>Report Findings </h4>
                <p>Issue audit reports with observations and recommendations</p>
            </div>
        </div>
    </div>
</section>

      <div class="checklist-section">
        <div class="checklist-inner">
          <div class="checklist-header">
            <h3>Pre-Auditing <span class="text-orange">Checklist</span></h3>
            <p>Essential documents required to initiate the Auditing process.</p>
          </div>
          <div class="checklist-grid">
            <div class="check-item"><i class="fas fa-check-circle"></i> <span>Books of accounts and ledgers</span></div>
            <div class="check-item"><i class="fas fa-check-circle"></i> <span>Financial statements</span></div>
            <div class="check-item"><i class="fas fa-check-circle"></i> <span>Bank statements and confirmations</span></div>
            <div class="check-item"><i class="fas fa-check-circle"></i> <span>Invoices, vouchers, and supporting documents</span></div>
            <div class="check-item"><i class="fas fa-check-circle"></i> <span>Statutory registers and compliance records</span></div>
            <div class="check-item"><i class="fas fa-check-circle"></i> <span>Internal control and policy documents</span></div>
          </div>
        </div>
      </div>

      <div class="faq-wrap">
        <h3 class="faq-head">General <span class="text-orange">Inquiries</span></h3>
        <div class="faq-box">
          <div class="faq-top">
            <span>Are your audits compliant with legal and professional standards?</span>
            <i class="fas fa-plus"></i>
          </div>
          <div class="faq-btm">
            <p>Yes, all audits are conducted as per applicable laws and auditing standards</p>
          </div>
        </div>
        <div class="faq-box">
          <div class="faq-top">
            <span>What types of audits do you provide?</span>
            <i class="fas fa-plus"></i>
          </div>
          <div class="faq-btm">
            <p>We provide statutory, internal, cost, bank, stock, and management audits.</p>
          </div>
        </div>

        <div class="faq-box">
          <div class="faq-top">
            <span>Will you share audit findings before final reporting?</span>
            <i class="fas fa-plus"></i>
          </div>
          <div class="faq-btm">
            <p>Yes, observations are discussed with management before finalization</p>
          </div>
        </div>

        <div class="faq-box">
          <div class="faq-top">
            <span>Do you provide recommendations after audit?</span>
            <i class="fas fa-plus"></i>
          </div>
          <div class="faq-btm">
            <p>Yes, we provide practical recommendations to strengthen controls and processes.</p>
          </div>
        </div>

        <div class="faq-box">
          <div class="faq-top">
            <span>Can you support us after the audit is completed?</span>
            <i class="fas fa-plus"></i>
          </div>
          <div class="faq-btm">
            <p>Yes, we offer post-audit advisory and compliance support</p>
          </div>
        </div>
      </div>

      <div class="navy-orange-banner">
        <div class="banner-content">
          <div class="banner-left">
            <h3>Ready to Audit Your Business?</h3>
            <p>Consult with CMA Karunesh Kumar for expert Auditing Services.</p>
          </div>
          <a href="tel:+919097047484" class="orange-btn">
            <i class="fas fa-phone-alt"></i> Book Discovery Call
          </a>
        </div>
      </div>

    </div>
  </section>

  <style>

     .process-section {
    padding: 80px 0;
    background: #f7f9fc;
}

.process-wrapper {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;
    margin-top: 50px;
    position: relative;
}

.process-step {
    text-align: center;
    position: relative;
    padding: 20px;
    transition: transform 0.3s ease;
}

.process-step:hover {
    transform: translateY(-10px);
}

.step-number {
    font-size: 60px;
    font-weight: 900;
    color: rgba(11, 60, 116, 0.05); /* Large faint number behind icon */
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1;
}

.step-icon {
    width: 80px;
    height: 80px;
    background: #fff;
    border: 1px solid #e2e8f0;
    color: #0b3c74;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    margin: 0 auto 20px;
    position: relative;
    z-index: 2;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.process-step:hover .step-icon {
    background: #0b3c74;
    color: #fff;
    border-color: #0b3c74;
}

.process-step h4 {
    color: #0b3c74;
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 12px;
}

.process-step p {
    font-size: 14px;
    color: #64748b;
    line-height: 1.6;
}

.step-arrow {
    position: absolute;
    top: 40px;
    right: -20px;
    color: #ff8c00;
    font-size: 18px;
}

/* Mobile Responsive */
@media (max-width: 991px) {
    .process-wrapper { grid-template-columns: repeat(2, 1fr); }
    .step-arrow { display: none; }
}

@media (max-width: 576px) {
    .process-wrapper { grid-template-columns: 1fr; }
}
 
    /* --- FINAL REFINED CSS --- */
    .incorp-final-section {
      padding: 120px 0 80px;
      background: #f7f9fc;
      font-family: 'Inter', sans-serif;
    }

    .container-premium {
      max-width: 1300px;
      margin: 0 auto;
      padding: 0 20px;
    }

    /* Boxed Service Tag */
    .service-box-tag {
      display: inline-flex;
      align-items: center;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      overflow: hidden;
      margin-bottom: 25px;
      background: #fff;
    }

    .s-num {
      background: #0b3c74;
      color: #fff;
      padding: 6px 14px;
      font-weight: 700;
      font-size: 12px;
    }

    .s-name {
      color: #ff8c00;
      padding: 6px 14px;
      font-weight: 700;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    /* Header Alignment */
    .section-intro {
      text-align: center;
      margin-bottom: 90px;
    }

    .section-intro h2 {
      font-size: clamp(32px, 5vw, 44px);
      color: #0b3c74;
      margin: 15px 0;
      font-weight: 700;
      line-height: 1.1;
    }

    .text-navy {
      color: #3b82f6;
    }

    .text-orange {
      color: #ff8c00;
    }

    .dual-accent {
      display: flex;
      justify-content: center;
      gap: 6px;
      margin: 25px 0;
    }

    .bar-navy {
      width: 45px;
      height: 6px;
      background: #0b3c74;
      border-radius: 10px;
    }

    .bar-orange {
      width: 22px;
      height: 6px;
      background: #ff8c00;
      border-radius: 10px;
    }

    .desc-text {
      font-size: 18px;
      color: #64748b;
      max-width: 750px;
      margin: 0 auto;
      line-height: 1.7;
    }

    /* Grid & Full Visible Icons */
    .premium-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 40px;
      margin-bottom: 100px;
    }

    .premium-card {
      background: #fff;
      border-radius: 20px;
      border: 1px solid #e2e8f0;
      position: relative;
      transition: 0.4s;
      overflow: visible;
    }

    .premium-card:hover {
      transform: translateY(-12px);
      box-shadow: 0 30px 60px rgba(11, 60, 116, 0.12);
      border-color: #3b82f6;
    }

    .card-image-box {
      position: relative;
      height: 200px;
      border-radius: 20px 20px 0 0;
      overflow: hidden;
    }

    .card-image-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .card-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(to top, rgba(11, 60, 116, 0.4), transparent);
    }

    /* ICON FIX: Ensures full visibility */
    .card-icon-float {
      position: absolute;
      top: 170px;
      right: 25px;
      width: 65px;
      height: 65px;
      background: #ff8c00;
      color: #fff;
      border-radius: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 26px;
      box-shadow: 0 12px 20px rgba(255, 140, 0, 0.3);
      z-index: 20;
    }

    .card-details {
      padding: 55px 30px 35px;
    }

    .card-details h4 {
      color: #0b3c74;
      font-size: 22px;
      margin-bottom: 12px;
      font-weight: 700;
    }

    .card-details p {
      color: #64748b;
      font-size: 15px;
      line-height: 1.6;
      margin: 0;
    }

    .hover-border {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 0;
      height: 5px;
      background: #ff8c00;
      transition: 0.4s;
    }

    .premium-card:hover .hover-border {
      width: 100%;
    }

    /* Checklist & FAQ */
    .checklist-section {
      background: #f8fafc;
      padding: 60px;
      border-radius: 35px;
      margin-bottom: 80px;
      border-left: 10px solid #ff8c00;
    }

    .checklist-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      margin-top: 30px;
    }

    .check-item {
      display: flex;
      align-items: center;
      gap: 15px;
      color: #1e293b;
      font-size: 16px;
      font-weight: 600;
    }

    .check-item i {
      color: #ff8c00;
      font-size: 20px;
      flex-shrink: 0;
    }

    .faq-wrap {
      max-width: 850px;
      margin: 0 auto 100px;
    }

    .faq-head {
      text-align: center;
      margin-bottom: 50px;
      color: #0b3c74;
      font-size: 32px;
      font-weight: 700;
    }

    .faq-box {
      border-bottom: 1px solid #e2e8f0;
      padding: 25px 5px;
      cursor: pointer;
    }

    .faq-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-weight: 700;
      color: #1e293b;
      font-size: 18px;
    }

    .faq-top i {
      color: #ff8c00;
      transition: 0.3s;
    }

    .faq-btm {
      max-height: 0;
      overflow: hidden;
      transition: 0.4s ease;
      color: #64748b;
    }

    .faq-box.active .faq-btm {
      max-height: 150px;
      padding-top: 15px;
    }

    .faq-box.active .faq-top i {
      transform: rotate(45deg);
    }

    /* Banner */
    .navy-orange-banner {
      background: #0b3c74;
      padding: 60px 50px;
      border-radius: 30px;
      border-left: 12px solid #ff8c00;
      box-shadow: 0 20px 40px rgba(11, 60, 116, 0.2);
    }

    .banner-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 30px;
    }

    .banner-left h3 {
      color: #fff;
      font-size: 30px;
      margin-bottom: 10px;
      font-weight: 800;
    }

    .banner-left p {
      color: #e2e8f0;
      font-size: 17px;
    }

    .orange-btn {
      background: #ff8c00;
      color: #fff;
      padding: 18px 40px;
      border-radius: 60px;
      text-decoration: none;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 12px;
      transition: 0.3s;
      white-space: nowrap;
      font-size: 16px;
    }

    /* RESPONSIVE */
    @media (max-width: 992px) {
      .premium-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
      }

      .checklist-grid {
        grid-template-columns: 1fr;
      }

      .banner-content {
        flex-direction: column;
        text-align: center;
      }
    }

    @media (max-width: 768px) {
      .premium-grid {
        grid-template-columns: 1fr;
      }

      .section-intro h2 {
        font-size: 32px;
      }

      .checklist-section {
        padding: 40px 20px;
      }
    }
  </style>

  <script>
    // FAQ TOGGLE SCRIPT
    document.querySelectorAll('.faq-box').forEach(item => {
      item.addEventListener('click', () => {
        item.classList.toggle('active');
      });
    });
  </script>
 <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700&display=swap" rel="stylesheet">

  <div id="kk-chatbot">
    <div id="kk-window" class="kk-hidden">
      <div class="kk-header">
        <div class="kk-brand">
          <div class="ai-neural-status">
            <div class="inner-dot"></div>
            <div class="outer-pulse"></div>
          </div>
          <div>
            <strong>KK & Associates</strong>
            <small>Neural AI Active</small>
          </div>
        </div>
        <button onclick="toggleChat()" class="close-btn">✕</button>
      </div>

      <div id="kk-chat-logs">
        <div class="msg bot">Welcome. I am the intelligence system for Karunesh Kumar & Associates. How may I assist with your financial queries?</div>
      </div>

      <div id="typing-indicator" class="kk-hidden">
        <span></span><span></span><span></span>
      </div>

      <div class="kk-input-area">
        <input type="text" id="kk-user-input" placeholder="Ask about GST, Tax, or Filing..." onkeypress="if(event.key==='Enter') sendMessage()">
        <button onclick="sendMessage()" class="send-trigger">
          <svg viewBox="0 0 24 24">
            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
          </svg>
        </button>
      </div>
    </div>

    <button id="kk-fab" onclick="toggleChat()">
      <div class="fab-rings"></div>
      <div class="fab-rings delay"></div>
      <div class="fab-core">
        <svg viewBox="0 0 24 24" class="neural-svg">
          <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" stroke="currentColor" stroke-width="1.5" fill="none" />
          <circle cx="12" cy="12" r="3" fill="currentColor" />
          <path d="M12 2v4m0 12v4M2 12h4m12 0h4m-15.4-6.4l2.8 2.8m9.2 9.2l2.8 2.8m-12-12l2.8-2.8m9.2 9.2l2.8-2.8" stroke="currentColor" stroke-width="1.5" />
        </svg>
      </div>
    </button>
  </div>

  <style>
    :root {
      --deep-navy: #051937;
      --royal-blue: #0b3c74;
      --electric-orange: #ff8c00;
      --soft-white: #ffffff;
      --bot-bg: #f4f7fa;
    }

    #kk-chatbot {
      position: fixed;
      bottom: 40px;
      right: 30px;
      z-index: 9999;
      font-family: 'Plus Jakarta Sans', sans-serif;
    }

    /* --- FAB AI ICON --- */
    #kk-fab {
      width: 65px;
      height: 65px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--deep-navy), var(--royal-blue));
      border: none;
      cursor: pointer;
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 10px 30px rgba(5, 25, 55, 0.4);
    }

    .fab-core {
      z-index: 5;
      color: white;
      transition: 0.3s;
    }

    .neural-svg {
      width: 32px;
      height: 32px;
      filter: drop-shadow(0 0 5px rgba(255, 140, 0, 0.5));
    }

    .fab-rings {
      position: absolute;
      width: 100%;
      height: 100%;
      border-radius: 50%;
      background: var(--electric-orange);
      opacity: 0.4;
      animation: neural-pulse 2.5s infinite ease-out;
      z-index: 1;
    }

    .fab-rings.delay {
      animation-delay: 1.25s;
    }

    /* --- CHAT WINDOW --- */
    #kk-window {
      width: 380px;
      height: 500px;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.5);
      border-radius: 24px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
      transform-origin: bottom right;
      position: absolute;
      bottom: 85px;
      right: 0;
    }

    #kk-window.kk-hidden {
      transform: scale(0.8) translateY(40px);
      opacity: 0;
      pointer-events: none;
    }

    /* Header */
    .kk-header {
      background: linear-gradient(to right, var(--deep-navy), var(--royal-blue));
      color: white;
      padding: 22px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .kk-brand {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    /* 1. The Parent Container (Crucial for alignment) */
.ai-neural-status {
  position: relative;
  width: 12px;
  height: 12px;
  display: flex;       /* Use flexbox */
  align-items: center; /* Center vertically */
  justify-content: center; /* Center horizontally */
}

/* 2. The Static Inner Dot */
.inner-dot {
  width: 8px;   /* Slightly smaller for better pulse gap */
  height: 8px;
  background: #00ff88;
  border-radius: 50%;
  box-shadow: 0 0 8px #00ff88;
  z-index: 2;   /* Keep it on top */
}

/* 3. The Pulsing Outer Ring */
.outer-pulse {
  position: absolute; /* Stay relative to parent center */
  width: 100%;
  height: 100%;
  border: 1.5px solid #00ff88;
  border-radius: 50%;
  animation: status-pulse 2s infinite ease-out;
  pointer-events: none; /* Ignore clicks */
}

/* 4. The Corrected Animation (Adding scale) */
@keyframes status-pulse {
  0% {
    transform: scale(1);
    opacity: 1;
  }
  100% {
    transform: scale(3.5); /* Grows from the center */
    opacity: 0;
  }
}

    .close-btn {
      background: rgba(255, 255, 255, 0.1);
      border: none;
      color: white;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      cursor: pointer;
    }

    /* Messages */
    #kk-chat-logs {
      flex: 1;
      padding: 25px;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 15px;
      background: #fff;
    }

    .msg {
      padding: 14px 18px;
      border-radius: 18px;
      font-size: 14px;
      max-width: 82%;
      line-height: 1.6;
      animation: slideIn 0.4s ease-out;
    }

    .user {
      align-self: flex-end;
      background: var(--royal-blue);
      color: white;
      border-bottom-right-radius: 4px;
      box-shadow: 0 4px 12px rgba(11, 60, 116, 0.2);
    }

    .bot {
      align-self: flex-start;
      background: var(--bot-bg);
      color: #2c3e50;
      border-bottom-left-radius: 4px;
      border: 1px solid #eef2f7;
    }

    /* Input */
    .kk-input-area {
      padding: 18px;
      background: #fff;
      border-top: 1px solid #f0f0f0;
      display: flex;
      gap: 12px;
      align-items: center;
    }

    #kk-user-input {
      flex: 1;
      border: none;
      padding: 10px;
      outline: none;
      font-size: 15px;
      color: var(--deep-navy);
    }

    .send-trigger {
      background: var(--electric-orange);
      color: white;
      border: none;
      width: 45px;
      height: 45px;
      border-radius: 50%;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: 0.3s;
      box-shadow: 0 4px 15px rgba(255, 140, 0, 0.3);
    }

    .send-trigger:hover {
      transform: scale(1.1) rotate(-10deg);
      background: #e67e00;
    }

    .send-trigger svg {
      width: 20px;
      height: 20px;
      fill: currentColor;
    }

    /* Typing */
    #typing-indicator {
      padding: 10px 25px;
      display: flex;
      gap: 5px;
    }

    #typing-indicator span {
      width: 7px;
      height: 7px;
      background: var(--electric-orange);
      border-radius: 50%;
      animation: bounce 1.4s infinite;
      opacity: 0.6;
    }

    /* Animations */
    @keyframes neural-pulse {
      0% {
        transform: scale(1);
        opacity: 0.4;
      }

      100% {
        transform: scale(2.2);
        opacity: 0;
      }
    }

    @keyframes status-pulse {
      0% {
        transform: scale(1);
        opacity: 1;
      }

      100% {
        transform: scale(3);
        opacity: 0;
      }
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(15px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes bounce {

      0%,
      80%,
      100% {
        transform: translateY(0);
      }

      40% {
        transform: translateY(-8px);
      }
    }

    .kk-hidden {
      display: none !important;
    }
  </style>

  <script>
    function toggleChat() {
      document.getElementById('kk-window').classList.toggle('kk-hidden');
    }

    async function sendMessage() {
      const input = document.getElementById('kk-user-input');
      const logs = document.getElementById('kk-chat-logs');
      const typing = document.getElementById('typing-indicator');
      const text = input.value.trim();
      if (!text) return;

      logs.innerHTML += `<div class="msg user">${text}</div>`;
      input.value = '';
      typing.classList.remove('kk-hidden');
      logs.scrollTop = logs.scrollHeight;

      try {
        const response = await fetch('../chat_proxy.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            message: text
          })
        });
        const data = await response.json();
        typing.classList.add('kk-hidden');

        const reply = data.choices ? data.choices[0].message.content : "System recalibrating. Please retry.";
        logs.innerHTML += `<div class="msg bot">${reply.replace(/\n/g, '<br>')}</div>`;
      } catch (e) {
        typing.classList.add('kk-hidden');
        logs.innerHTML += `<div class="msg bot">Connection unstable. Check your network.</div>`;
      }
      logs.scrollTop = logs.scrollHeight;
    }
  </script>
 <footer class="footer-premium">
    <div class="footer-top-accent"></div>
    <div class="footer-container">

        <div class="footer-column">
            <div class="footer-brand">
               <img src="assets/ICMAI.png" class="footer-logo-v8" alt="ICMAI Logo">
                <p class="brand-desc">Karunesh Kumar & Associates brings professional excellence to cost auditing, taxation, and business advisory, empowering firms across India to scale with integrity.</p>
            </div>
            <div class="footer-social-links">
                <a href="https://www.facebook.com/profile.php?id=61582640901104" class="s-icon" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" class="s-icon" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
          <a href="https://x.com/Karunesh_CMA" class="s-icon" aria-label="Twitter"><i class="fa-brands fa-x-twitter"></i></a>
          <a href="https://wa.me/919097047484" class="s-icon" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>

        <div class="footer-column">
            <h4 class="footer-heading">Quick Links</h4>
            <ul class="footer-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="index.php">Our Firm</a></li>
                <li><a href="index.php">Services</a></li>
                <li><a href="blogs.php">Latest Insights</a></li>
                <li><a href="index.php">Testimonials</a></li>
                <li><a href="index.php">Join the Team</a></li>
                <li><a href="index.php">Contact Us</a></li>
            </ul>
        </div>

        <div class="footer-column">
            <h4 class="footer-heading">Get In Touch</h4>
            <div class="contact-list">
                <div class="contact-item">
                    <i class="fas fa-phone-alt c-icon"></i>
                    <div class="c-text">
                        <span>+91 90970 47484</span>
                        <span>0612-3555957</span>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope-open-text c-icon"></i>
                    <div class="c-text">
                        <span>karunesh.cma@gmail.com</span>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fas fa-clock c-icon"></i>
                    <div class="c-text">
                        <span>Mon - Sat: 10AM - 7PM</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-column">
            <h4 class="footer-heading">Our Locations</h4>
            <div class="location-group">
                <div class="loc-item">
                    <h6><i class="fas fa-map-marker-alt"></i> Patna (HQ)</h6>
                    <p>2nd Floor, Shyam Market, Pillar No: 75, Bailey Road, Patna - 800014</p>
                </div>
                <div class="loc-item">
                    <h6><i class="fas fa-building"></i> West Bengal</h6>
                    <p>Regional Branch: Kolkata</p>
                </div>
            </div>
        </div>

    </div>

    <div class="footer-bottom">
        <div class="footer-container bottom-flex">
            <p>© 2026 Karunesh Kumar & Associates. All Rights Reserved.</p>
            <div class="footer-legal">
                <a href="#">Privacy Policy</a>
                <span class="sep">|</span>
                <a href="#">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>

<style>
    :root {
        --footer-navy: #081d35;
        --footer-deep: #051324;
        --accent-orange: #ff8c00;
        --text-gray: #cbd5e1;
    }

    .footer-premium {
        background: var(--footer-navy);
        color: var(--text-gray);
        padding-top: 80px;
        position: relative;
        font-family: 'Inter', sans-serif;
        overflow: hidden;
    }

    /* Top Gradient Bar */
    .footer-top-accent {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 4px;
        background: linear-gradient(90deg, #0b3c74 0%, var(--accent-orange) 50%, #0b3c74 100%);
    }

    /* Grid Alignment Fix */
    .footer-container {
        max-width: 1250px;
        margin: 0 auto;
        padding: 0 25px;
        display: grid;
        grid-template-columns: 1.2fr 0.8fr 1fr 1fr;
        gap: 50px;
        padding-bottom: 60px;
    }

    /* Column 1 - Brand */
    .footer-logo-v8 {
        width: 280px;
        height: 100px;
        margin-bottom: 20px;
       
    }

    .brand-desc {
        font-size: 14px;
        line-height: 1.8;
        color: #94a3b8;
    }

    /* Headings */
    .footer-heading {
        color: #fff;
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 35px;
        position: relative;
    }

    .footer-heading::after {
        content: "";
        position: absolute;
        bottom: -10px; left: 0;
        width: 35px; height: 3px;
        background: var(--accent-orange);
    }

    /* Column 2 - Links */
    .footer-links { list-style: none; padding: 0; margin: 0; }
    .footer-links li { margin-bottom: 15px; }
    .footer-links a {
        color: var(--text-gray);
        text-decoration: none;
        font-size: 14px;
        display: flex;
        align-items: center;
        transition: 0.3s ease;
    }

    .footer-links a::before {
        content: "\f054"; /* FontAwesome Chevron */
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        font-size: 10px;
        margin-right: 12px;
        color: var(--accent-orange);
    }

    .footer-links a:hover {
        color: #fff;
        transform: translateX(5px);
    }

    /* Column 3 - Contact */
    .contact-list { display: flex; flex-direction: column; gap: 20px; }
    .contact-item { display: flex; gap: 15px; align-items: flex-start; }
    .c-icon { color: var(--accent-orange); font-size: 16px; margin-top: 4px; }
    .c-text span { display: block; font-size: 14px; margin-bottom: 4px; color: #fff; font-weight: 500; }

    /* Column 4 - Locations */
    .loc-item { margin-bottom: 25px; }
    .loc-item h6 { color: #fff; margin: 0 0 8px 0; font-size: 15px; display: flex; align-items: center; gap: 10px; }
    .loc-item h6 i { color: var(--accent-orange); }
    .loc-item p { font-size: 13px; line-height: 1.6; margin: 0; color: #94a3b8; }

    /* Social Icons Alignment */
    .footer-social-links { display: flex; gap: 12px; margin-top: 25px; }
    .s-icon {
        width: 38px; height: 38px;
        background: rgba(255,255,255,0.06);
        color: #fff; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        text-decoration: none; transition: 0.3s;
    }
    .s-icon:hover { background: var(--accent-orange); transform: translateY(-3px); box-shadow: 0 5px 15px rgba(255, 140, 0, 0.3); }

    /* Bottom Bar Fix */
    .footer-bottom {
        background: var(--footer-deep);
        padding: 25px 0;
        border-top: 1px solid rgba(255,255,255,0.05);
    }

    .bottom-flex {
        display: flex; /* Switched to flex for bar alignment */
        grid-template-columns: none; 
        justify-content: space-between;
        align-items: center;
    }

    .bottom-flex p { font-size: 13px; margin: 0; color: #64748b; }
    .footer-legal { display: flex; align-items: center; gap: 15px; }
    .footer-legal a { color: #64748b; text-decoration: none; font-size: 13px; transition: 0.3s; }
    .footer-legal a:hover { color: var(--accent-orange); }
    .sep { color: #1e293b; }

    /* RESPONSIVE BREAKPOINTS */
    @media (max-width: 1024px) {
        .footer-container { grid-template-columns: repeat(2, 1fr); gap: 40px; }
    }

    @media (max-width: 600px) {
        .footer-container { grid-template-columns: 1fr; padding-bottom: 40px; }
        .bottom-flex { flex-direction: column; gap: 15px; text-align: center; }
        .footer-logo-v8 { width: 200px; }
    }
</style>
  <!-- WHATSAPP STICKY -->
  <a href="https://wa.me/919097047484" target="_blank" class="side-whatsapp">
    <i class="fab fa-whatsapp"></i>
  </a>
</body>

</html>