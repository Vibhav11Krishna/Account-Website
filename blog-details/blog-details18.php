!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Blogs</title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">


</head>

<body>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <header class="site-header">
    <div class="header-container">

      <a href="#home" class="logo-group">
        <img src="../assets/logo.jpg" alt="Logo" class="main-logo">
        <div class="logo-text">
          <span class="firm-name">KARUNESH KUMAR</span>
          <span class="firm-sub">& ASSOCIATES</span>
        </div>
      </a>

      <nav class="nav-desktop">
        <ul class="nav-list">
          <li><a href="../index.php">Home</a></li>
          <li><a href="../index.php">About</a></li>
          <li><a href="../index.php">Services</a></li>
          <li><a href="../blogs.php">Insights</a></li>
          <li><a href="../index.php">Testimonials</a></li>
          <li><a href="../index.php">Team</a></li>
          <li><a href="../index.php">Careers</a></li>
          <li><a href="../index.php">Contact</a></li>
        </ul>
        <button class="btn-login" onclick="window.location.href='../Register.php'">
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
      <button class="drawer-login-btn" onclick="window.location.href='../Register.php'">
        Portal Login
      </button>
      <nav class="drawer-nav-list">
        <a href="../index.php" class="d-link"><i class="fa-solid fa-house"></i> Home</a>
        <a href="../index.php" class="d-link"><i class="fa-solid fa-building"></i> About</a>
        <a href="../index.php" class="d-link"><i class="fa-solid fa-gears"></i> Services</a>
        <a href="../blogs.php" class="d-link"><i class="fa-solid fa-chart-line"></i> Insights</a>
        <a href="../index.php" class="d-link"><i class="fa-solid fa-user-circle"></i>Testimonials</a>
        <a href="../index.php" class="d-link"><i class="fa-solid fa-users"></i> Team</a>
        <a href="../index.php" class="d-link"><i class="fa-solid fa-user-tie"></i> Careers</a>
        <a href="../index.php" class="d-link"><i class="fa-solid fa-envelope"></i> Contact</a>
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


  <style>
    :root {
      --navy: #051937;
      --orange: #ff8c00;
      --text: #2d3436;
      --bg: #f4f7fa;
      --white: #ffffff;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.6;
    }

    .main-layout {
      display: grid;
      grid-template-columns: 80px 1fr 340px;
      max-width: 1300px;
      margin: 40px auto;
      gap: 30px;
      padding: 0 20px;
    }

    /* Floating Socials */
    .social-sidebar {
      position: sticky;
      top: 100px;
      height: fit-content;
      display: flex;
      flex-direction: column;
      gap: 15px;
      align-items: center;
    }

    .share-icon {
      width: 45px;
      height: 45px;
      background: var(--white);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      color: var(--navy);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
      transition: 0.3s;
      font-weight: 700;
    }

    .share-icon:hover {
      background: var(--orange);
      color: white;
      transform: scale(1.1);
    }

    /* Blog Content */
    .blog-article {
      background: var(--white);
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }

    .blog-hero {
      padding: 80px 60px;
      background-size: cover;
      background-position: center;
      color: white;
      text-align: left;
    }

    .blog-hero h1 {
      font-size: 2.8rem;
      margin: 15px 0;
      line-height: 1.2;
    }

    .category-label {
      background: #df1d1d;
      padding: 5px 15px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 800;
      text-transform: uppercase;
    }

    .content-padding {
      padding: 40px 60px;
    }

    .toc-box {
      background: #f9fbff;
      padding: 25px;
      border-radius: 12px;
      margin-bottom: 30px;
      border: 1px solid #eef2f8;
    }

    .toc-box ul {
      list-style: none;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-top: 10px;
    }

    .toc-box a {
      text-decoration: none;
      color: var(--navy);
      font-weight: 600;
      font-size: 0.9rem;
    }

    .blog-body h2 {
      color: var(--navy);
      margin: 35px 0 15px;
      font-size: 1.8rem;
    }

    .blog-body p {
      margin-bottom: 20px;
      font-size: 1.1rem;
      color: #444;
      text-align: justify;
    }

    /* Table Styling */
    .data-table {
      width: 100%;
      border-collapse: collapse;
      margin: 25px 0;
      font-size: 0.95rem;
    }

    .data-table th {
      background: #f8f9fa;
      text-align: left;
      padding: 12px;
      border-bottom: 2px solid var(--orange);
    }

    .data-table td {
      padding: 12px;
      border-bottom: 1px solid #eee;
    }

    /* Contact Form Section */
    .contact-section {
      margin-top: 50px;
      padding: 40px;
      background: #051937;
      color: white;
      border-radius: 12px;
    }

    .contact-section h3 {
      font-size: 1.5rem;
      margin-bottom: 10px;
      color: var(--orange);
    }

    .contact-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-top: 20px;
    }

    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 12px;
      border-radius: 6px;
      border: none;
      margin-top: 5px;
    }

    .btn-submit {
      grid-column: span 2;
      padding: 15px;
      background: var(--orange);
      border: none;
      color: white;
      font-weight: 700;
      border-radius: 6px;
      cursor: pointer;
      font-size: 1rem;
    }

    /* Sidebar */
    .sidebar {
      display: flex;
      flex-direction: column;
      gap: 25px;
    }

    .sidebar-widget {
      background: var(--white);
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
    }

    .related-card {
      display: flex;
      gap: 12px;
      margin-bottom: 15px;
      text-decoration: none;
      align-items: center;
    }

    .related-card img {
      width: 60px;
      height: 60px;
      border-radius: 6px;
      object-fit: cover;
    }

    .related-card span {
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--navy);
    }

    @media (max-width: 1000px) {
      .main-layout {
        grid-template-columns: 1fr;
      }

      .social-sidebar {
        display: none;
      }

      .contact-grid {
        grid-template-columns: 1fr;
      }

      .btn-submit {
        grid-column: span 1;
      }

      .content-padding {
        padding: 30px 20px;
      }
    }

    /* Centering the Footer Navigation */
    .blog-footer-nav {
      margin: 40px 0;
      padding-top: 30px;
      border-top: 1px solid #eee;
      display: flex;
      /* Use flexbox for easy centering */
      justify-content: center;
      align-items: center;
    }

    /* Breadcrumb Styling */
    .breadcrumb {
      margin-bottom: 25px;
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--text-muted);
    }

    .breadcrumb a {
      text-decoration: none;
      color: var(--navy);
      transition: color 0.3s ease;
    }

    .breadcrumb a:hover {
      color: var(--orange);
    }

    .breadcrumb .sep {
      margin: 0 10px;
      color: #cbd5e0;
    }

    .breadcrumb .current {
      color: var(--orange);
    }
  </style>
  </head>

  <body>
    <main class="blog-article">
      <div class="content-padding" style="padding-bottom: 0;">
        <nav class="breadcrumb">
          <a href="../index.php">Home</a>
          <span class="sep">›</span>
          <a href="../blogs.php">Insights</a>
          <span class="sep">›</span>
          <span class="current">Regulatory Updates</span>
        </nav>
      </div>


    </main>
    <div class="main-layout">
        <aside class="social-sidebar">
        <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" class="share-icon">in</a>
        <a href="https://x.com/Karunesh_CMA" class="share-icon">X</a>
        <a href="#" class="share-icon">@</a>
    </aside>

      <main class="blog-article">
        <div class="blog-hero" style="background-image: linear-gradient(rgba(5, 25, 55, 0.8), rgba(5, 25, 55, 0.8)), url('../assets/blog18.jpg');">
          <span class="category-label">Legal Updates</span>
          <h1>Regulatory Updates Every Business Should Watch This Year</h1>
          <p>Key legal and policy shifts that could affect operations, compliance, and growth</p>
        </div>

        <div class="content-padding">
          <nav class="toc-box">
            <h4>Table Of Contents</h4>
            <ul>
              <li><a href="#major">Major Tax Law and Budget-Driven Reforms</a></li>
              <li><a href="#gst">GST Modernisation and GST 2.0</a></li>
              <li><a href="#corporate">Corporate Law and Compliance Revisions</a></li>
              <li><a href="#conclusion">Conclusion</a></li>
            </ul>
          </nav>

          <div class="blog-body">
            <section id="summary">
              <p>In an evolving business environment, regulatory changes can significantly impact company operations, tax planning, reporting requirements, and compliance obligations. This year is shaping up to bring a range of updates from tax law reforms to corporate, GST, and labour compliance adjustments. Proactively understanding these developments helps businesses stay compliant, optimize processes, and make strategic decisions with confidence.</p>
              <p>Below, we cover the most important updates that business owners and leaders should watch closely.</p>
            </section>

            <section id="major">
              <h2>Major Tax Law and Budget-Driven Reforms</h2>
              <p>This year is expected to see sweeping changes in tax compliance and law. The <strong>Income-tax Act</strong>, 2025, which replaces the older tax statute and aims to modernise tax provisions, is scheduled to come into force from <strong>1 April 2026,</strong>. It focuses on simplifying compliance, reducing litigation, and aligning India’s tax system with global best practices, including digital assessments and anti-avoidance tools. </p>
              <p>Additionally, the Union Budget 2026-27 emphasised ease of doing business, digitisation, and tax certainty, with proposals to rationalise corporate tax provisions, revise TCS/STT rates, and introduce safe harbour and transfer pricing reforms. </p>
              <p>These tax reforms will be crucial for businesses to plan their compliance calendars and cash flow strategies this year.</p>
            </section>


            <section id="gst">
              <h2>GST Modernisation and GST 2.0</h2>
              <p>Indirect tax compliance is also evolving. The upgraded GST 2.0 framework — launched in September 2025 — continues to roll out enhancements aimed at simplifying the structure, lowering compliance burdens, and expanding digital integrations like automated reconciliation and API-based reporting systems. </p>
              <p>At the same time, regular GST notifications and council decisions affecting e-invoicing, input tax credit eligibility, and portal authentication requirements are expected throughout 2026. </p>
              <p>Staying updated on these GST developments will help businesses avoid credit denials, interest, and penalties.</p>
            </section>

             <section id="corporate">
              <h2>Corporate Law and Compliance Revisions</h2>
              <p>Corporate governance and compliance continue to undergo reform. Amendments to merger frameworks allow faster and broader use of fast-track merger provisions for eligible companies, reducing dependence on tribunal approvals. </p>
              <p>Regulators are also focusing on ease-of-doing-business policies  including revisions in compliance timelines, rationalisation of minor offences through bills like the Jan Vishwas Bill 2.0, and enhanced digitisation of filings. </p>
              <p>For businesses, these changes affect how board decisions, filings, and corporate restructurings are planned and executed.</p>
            </section>

            <section id="conclusion">
              <h2>Conclusion</h2>
              <p>Regulatory updates this year span tax law modernisation, GST enhancements, corporate law reforms, and digital compliance trends. Keeping pace with these changes is essential not only for legal compliance but also for strategic planning, investor confidence, and operational efficiency.</p>
              <p><strong>Karunesh Kumar & Associates</strong> helps businesses navigate regulatory changes with expert advisory services, compliance reviews, and customised implementation support. Our proactive guidance ensures that organisations stay updated, compliant, and positioned for growth in a dynamic regulatory environment.</p>
          </div>
          <div class="contact-section">
            <h3>Direct Consultation with Karunesh Kumar & Associates</h3>
            <p>Have specific questions about Regulatory Updates ? Message us below.</p>

            <form class="contact-grid" action="submit_form.php" method="POST">
              <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" placeholder="Enter your name" required>
              </div>
              <div class="form-group">
                <label>Business Email</label>
                <input type="email" name="email" placeholder="email@company.com" required>
              </div>
              <div class="form-group" style="grid-column: span 2;">
                <label>Your Query</label>
                <textarea name="message" rows="4" placeholder="Briefly describe your tax or audit concern..." required></textarea>
              </div>
              <button type="submit" class="btn-submit">Request a Callback</button>
            </form>
          </div>
      </main>

      <aside class="sidebar">
        <section class="sidebar-widget">
          <h3>Related Insights</h3>
          <a href="../blog-details/blog-details16.php" class="related-card">
            <img src="../assets/blog16.jpg">
            <span>Recent Changes in Company Law</span>
          </a>
          <a href="../blog-details/blog-details17.php" class="related-card">
            <img src="../assets/blog17.jpg">
            <span>Key Labour Law Updates</span>
          </a>
        </section>

        <section class="sidebar-widget newsletter" style="background: var(--navy); color: white;">
          <h3 style="color: white; border: none;">Quick Newsletter</h3>
          <input type="email" placeholder="Email Address" style="width: 100%; padding: 10px; margin-bottom: 10px; border-radius: 4px; border: 1px solid #444; background: #0a2347; color: white;">
          <button style="width: 100%; padding: 10px; background: var(--orange); border: none; color: white; font-weight: 700; cursor: pointer; border-radius: 4px;">Join Alerts</button>
        </section>
      </aside>
    </div>
    <!-- WHATSAPP STICKY -->
    <a href="https://wa.me/919097047484" target="_blank" class="side-whatsapp">
      <i class="fab fa-whatsapp"></i>
    </a>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap" rel="stylesheet">



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
        display: flex;
        /* Use flexbox */
        align-items: center;
        /* Center vertically */
        justify-content: center;
        /* Center horizontally */
      }

      /* 2. The Static Inner Dot */
      .inner-dot {
        width: 8px;
        /* Slightly smaller for better pulse gap */
        height: 8px;
        background: #00ff88;
        border-radius: 50%;
        box-shadow: 0 0 8px #00ff88;
        z-index: 2;
        /* Keep it on top */
      }

      /* 3. The Pulsing Outer Ring */
      .outer-pulse {
        position: absolute;
        /* Stay relative to parent center */
        width: 100%;
        height: 100%;
        border: 1.5px solid #00ff88;
        border-radius: 50%;
        animation: status-pulse 2s infinite ease-out;
        pointer-events: none;
        /* Ignore clicks */
      }

      /* 4. The Corrected Animation (Adding scale) */
      @keyframes status-pulse {
        0% {
          transform: scale(1);
          opacity: 1;
        }

        100% {
          transform: scale(3.5);
          /* Grows from the center */
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
            <img src="../assets/ICMAI.png" class="footer-logo-v8" alt="ICMAI Logo">
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
            <li><a href="../index.php">Home</a></li>
            <li><a href="../index.php">Our Firm</a></li>
            <li><a href="../index.php">Services</a></li>
            <li><a href="../blogs.php">Latest Insights</a></li>
            <li><a href="../index.php">Testimonials</a></li>
            <li><a href="../index.php">Join the Team</a></li>
            <li><a href="../index.php">Contact Us</a></li>
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
              <h6><i class="fas fa-building"></i> Kolkata</h6>
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
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
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
        bottom: -10px;
        left: 0;
        width: 35px;
        height: 3px;
        background: var(--accent-orange);
      }

      /* Column 2 - Links */
      .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
      }

      .footer-links li {
        margin-bottom: 15px;
      }

      .footer-links a {
        color: var(--text-gray);
        text-decoration: none;
        font-size: 14px;
        display: flex;
        align-items: center;
        transition: 0.3s ease;
      }

      .footer-links a::before {
        content: "\f054";
        /* FontAwesome Chevron */
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
      .contact-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
      }

      .contact-item {
        display: flex;
        gap: 15px;
        align-items: flex-start;
      }

      .c-icon {
        color: var(--accent-orange);
        font-size: 16px;
        margin-top: 4px;
      }

      .c-text span {
        display: block;
        font-size: 14px;
        margin-bottom: 4px;
        color: #fff;
        font-weight: 500;
      }

      /* Column 4 - Locations */
      .loc-item {
        margin-bottom: 25px;
      }

      .loc-item h6 {
        color: #fff;
        margin: 0 0 8px 0;
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .loc-item h6 i {
        color: var(--accent-orange);
      }

      .loc-item p {
        font-size: 13px;
        line-height: 1.6;
        margin: 0;
        color: #94a3b8;
      }

      /* Social Icons Alignment */
      .footer-social-links {
        display: flex;
        gap: 12px;
        margin-top: 25px;
      }

      .s-icon {
        width: 38px;
        height: 38px;
        background: rgba(255, 255, 255, 0.06);
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: 0.3s;
      }

      .s-icon:hover {
        background: var(--accent-orange);
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(255, 140, 0, 0.3);
      }

      /* Bottom Bar Fix */
      .footer-bottom {
        background: var(--footer-deep);
        padding: 25px 0;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
      }

      .bottom-flex {
        display: flex;
        /* Switched to flex for bar alignment */
        grid-template-columns: none;
        justify-content: space-between;
        align-items: center;
      }

      .bottom-flex p {
        font-size: 13px;
        margin: 0;
        color: #64748b;
      }

      .footer-legal {
        display: flex;
        align-items: center;
        gap: 15px;
      }

      .footer-legal a {
        color: #64748b;
        text-decoration: none;
        font-size: 13px;
        transition: 0.3s;
      }

      .footer-legal a:hover {
        color: var(--accent-orange);
      }

      .sep {
        color: #1e293b;
      }

      /* RESPONSIVE BREAKPOINTS */
      @media (max-width: 1024px) {
        .footer-container {
          grid-template-columns: repeat(2, 1fr);
          gap: 40px;
        }
      }

      @media (max-width: 600px) {
        .footer-container {
          grid-template-columns: 1fr;
          padding-bottom: 40px;
        }

        .bottom-flex {
          flex-direction: column;
          gap: 15px;
          text-align: center;
        }

        .footer-logo-v8 {
          width: 200px;
        }
      }
    </style>

  </body>

</html>