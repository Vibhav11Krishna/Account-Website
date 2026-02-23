<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Blogs</title>
  <link rel="stylesheet" href="assets/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">


</head>

<body>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <header class="site-header">
    <div class="header-container">

      <a href="#home" class="logo-group">
        <img src="assets/CMA.jpg" alt="Logo" class="main-logo">
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

  <section class="blogs-page" id="insights">
    <div class="container-premium">

        <div class="blog-intro">
            <span class="service-pill">Knowledge Hub</span>
            <h2>Compliance & Financial Insights</h2>
            <p>Expert analysis on GST, Tax, and Audit to help your business stay compliant in an evolving regulatory landscape.</p>
        </div>

        <div class="blog-toolbar">
            <div class="search-box-v2">
                <i class="fas fa-search"></i>
                <input type="text" id="blogSearch" placeholder="Search insights (e.g. 'Filing', 'GST')...">
            </div>

            <div class="filter-wrapper">
                <div class="filter-group-v2">
                    <button class="filter-btn-v2 active" data-filter="all">All Articles</button>
                    <button class="filter-btn-v2" data-filter="compliance">Compliance</button>
                    <button class="filter-btn-v2" data-filter="tax">Taxation</button>
                    <button class="filter-btn-v2" data-filter="audit">Audit & Assurance</button>
                    <button class="filter-btn-v2" data-filter="startups">Startups</button>
                    <button class="filter-btn-v2" data-filter="accounting">Accounting</button>
                    <button class="filter-btn-v2" data-filter="legal">Legal Updates</button>
                </div>
            </div>
        </div>

        <div class="blogs-grid" id="blogGrid">

            <div class="blog-card-v2" data-category="compliance">
                <div class="card-image-v2">
                    <img src="assets/blog1.jpg" alt="GST Analysis">
                    <div class="category-badge">Compliance</div>
                </div>
                <div class="blog-content-v2">
                    <div class="meta-row">
                        <span><i class="far fa-calendar-alt"></i> Dec 22, 2025 </span>
                        <span><i class="far fa-clock"></i> 5 min read</span>
                    </div>
                    <h3 class="blog-title-v2">GST Compliance Checklist for Growing Businesses</h3>
                    <p>Key GST obligations every business must follow to avoid penalties and ensure smooth operations.</p>
                    <div class="card-footer-v2">
                        <a href="./blog-details/blog-details1.php" class="read-more-btn">Read Analysis <i class="fas fa-chevron-right"></i></a>
                         <div class="blog-social-share">
        <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" title="Share on LinkedIn" class="share-icon lnk"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://x.com/Karunesh_CMA" title="Share on Twitter" class="share-icon twt"><i class="fa-brands fa-x-twitter"></i></a>
       
    </div>
                    </div>
                </div>
            </div>

            <div class="blog-card-v2" data-category="compliance">
                <div class="card-image-v2">
                    <img src="assets/blog2.jpg" alt="GST Analysis">
                    <div class="category-badge">Compliance</div>
                </div>
                <div class="blog-content-v2">
                    <div class="meta-row">
                        <span><i class="far fa-calendar-alt"></i> Jan 20, 2026 </span>
                        <span><i class="far fa-clock"></i> 6 min read</span>
                    </div>
                    <h3 class="blog-title-v2">Understanding ROC Filings and Annual Compliance Requirements</h3>
                    <p>An overview of mandatory MCA filings applicable to companies and LLPs.</p>
                    <div class="card-footer-v2">
                        <a href="./blog-details/blog-details2.php" class="read-more-btn">Read Analysis <i class="fas fa-chevron-right"></i></a>
                         <div class="blog-social-share">
        <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" title="Share on LinkedIn" class="share-icon lnk"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://x.com/Karunesh_CMA" title="Share on Twitter" class="share-icon twt"><i class="fa-brands fa-x-twitter"></i></a>
        
    </div>
                    </div>
                </div>
            </div>

            <div class="blog-card-v2" data-category="compliance">
                <div class="card-image-v2">
                    <img src="assets/blog3.jpg" alt="GST Analysis">
                    <div class="category-badge">Compliance</div>
                </div>
                <div class="blog-content-v2">
                    <div class="meta-row">
                        <span><i class="far fa-calendar-alt"></i> Feb 10, 2026 </span>
                        <span><i class="far fa-clock"></i> 5 min read</span>
                    </div>
                    <h3 class="blog-title-v2">Consequences of Non-Compliance Under Indian Regulatory Laws</h3>
                    <p>Insights into penalties, legal exposure, and reputational risks arising from non-compliance</p>
                    <div class="card-footer-v2">
                        <a href="./blog-details/blog-details3.php" class="read-more-btn">Read Analysis <i class="fas fa-chevron-right"></i></a>
                         <div class="blog-social-share">
      <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" title="Share on LinkedIn" class="share-icon lnk"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://x.com/Karunesh_CMA" title="Share on Twitter" class="share-icon twt"><i class="fa-brands fa-x-twitter"></i></a>
       
    </div>
                    </div>
                </div>
            </div>

            <div class="blog-card-v2" data-category="tax">
                <div class="card-image-v2">
                    <img src="assets/blog4.jpg" alt="Tax Planning">
                    <div class="category-badge blue">Taxation</div>
                </div>
                <div class="blog-content-v2">
                    <div class="meta-row">
                        <span><i class="far fa-calendar-alt"></i> Dec 12, 2025 </span>
                        <span><i class="far fa-clock"></i> 6 min read</span>
                    </div>
                    <h3 class="blog-title-v2">Optimizing Tax Liability Under the New Income Tax Regime</h3>
                    <p>A practical overview of deductions, exemptions, and planning strategies to reduce tax outflow </p>
                    <div class="card-footer-v2">
                        <a href="./blog-details/blog-details4.php" class="read-more-btn">Read Analysis <i class="fas fa-chevron-right"></i></a>
                         <div class="blog-social-share">
       <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" title="Share on LinkedIn" class="share-icon lnk"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://x.com/Karunesh_CMA" title="Share on Twitter" class="share-icon twt"><i class="fa-brands fa-x-twitter"></i></a>
    </div>
                    </div>
                </div>
            </div>

              <div class="blog-card-v2" data-category="tax">
                <div class="card-image-v2">
                    <img src="assets/blog5.jpg" alt="Tax Planning">
                    <div class="category-badge blue">Taxation</div>
                </div>
                <div class="blog-content-v2">
                    <div class="meta-row">
                        <span><i class="far fa-calendar-alt"></i> Jan 08, 2026</span>
                        <span><i class="far fa-clock"></i> 8 min read</span>
                    </div>
                    <h3 class="blog-title-v2">Capital Gains Tax: Businesses & Investors</h3>
                    <p>Understanding short-term and long-term capital gains implications </p>
                    <div class="card-footer-v2">
                        <a href="./blog-details/blog-details5.php" class="read-more-btn">Read Analysis <i class="fas fa-chevron-right"></i></a>
                         <div class="blog-social-share">
       <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" title="Share on LinkedIn" class="share-icon lnk"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://x.com/Karunesh_CMA" title="Share on Twitter" class="share-icon twt"><i class="fa-brands fa-x-twitter"></i></a>
        
    </div>
                    </div>
                </div>
            </div>

              <div class="blog-card-v2" data-category="tax">
                <div class="card-image-v2">
                    <img src="assets/blog6.jpg" alt="Tax Planning">
                    <div class="category-badge blue">Taxation</div>
                </div>
                <div class="blog-content-v2">
                    <div class="meta-row">
                        <span><i class="far fa-calendar-alt"></i> Feb 02, 2026</span>
                        <span><i class="far fa-clock"></i> 7 min read</span>
                    </div>
                    <h3 class="blog-title-v2">Common Tax Filing Mistakes That Trigger Notices</h3>
                    <p>An analysis of frequent errors in income tax returns and preventive measures to avoid scrutiny</p>
                    <div class="card-footer-v2">
                        <a href="./blog-details/blog-details6.php" class="read-more-btn">Read Analysis <i class="fas fa-chevron-right"></i></a>
                         <div class="blog-social-share">
        <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" title="Share on LinkedIn" class="share-icon lnk"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://x.com/Karunesh_CMA" title="Share on Twitter" class="share-icon twt"><i class="fa-brands fa-x-twitter"></i></a>
       
    </div>
                    </div>
                </div>
            </div>

            <div class="blog-card-v2" data-category="audit">
                <div class="card-image-v2">
                    <img src="assets/blog7.jpg" alt="Audit Assurance">
                    <div class="category-badge green">Audit</div>
                </div>
                <div class="blog-content-v2">
                    <div class="meta-row">
                        <span><i class="far fa-calendar-alt"></i> Dec 18, 2025 </span>
                        <span><i class="far fa-clock"></i> 6 min read</span>
                    </div>
                    <h3 class="blog-title-v2">The Role of Statutory Audit in Strengthening Financial Credibility</h3>
                    <p>How statutory audits improve transparency, compliance, and stakeholder confidence.</p>
                    <div class="card-footer-v2">
                        <a href="./blog-details/blog-details7.php" class="read-more-btn">Read Analysis <i class="fas fa-chevron-right"></i></a>
                        <div class="blog-social-share">
       <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" title="Share on LinkedIn" class="share-icon lnk"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://x.com/Karunesh_CMA" title="Share on Twitter" class="share-icon twt"><i class="fa-brands fa-x-twitter"></i></a>
        
    </div>
                    </div>
                </div>
            </div>

             <div class="blog-card-v2" data-category="audit">
                <div class="card-image-v2">
                    <img src="assets/blog8.jpg" alt="Audit Assurance">
                    <div class="category-badge green">Audit</div>
                </div>
                <div class="blog-content-v2">
                    <div class="meta-row">
                        <span><i class="far fa-calendar-alt"></i> Jan 14, 2026</span>
                        <span><i class="far fa-clock"></i> 6 min read</span>
                    </div>
                    <h3 class="blog-title-v2">Internal Audit as a Tool for Risk Management</h3>
                    <p>Exploring the importance of internal audit in identifying risks and strengthening internal controls</p>
                    <div class="card-footer-v2">
                        <a href="./blog-details/blog-details8.php" class="read-more-btn">Read Analysis <i class="fas fa-chevron-right"></i></a>
                        <div class="blog-social-share">
        <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" title="Share on LinkedIn" class="share-icon lnk"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://x.com/Karunesh_CMA" title="Share on Twitter" class="share-icon twt"><i class="fa-brands fa-x-twitter"></i></a>
        
    </div>
                    </div>
                </div>
            </div>


             <div class="blog-card-v2" data-category="audit">
                <div class="card-image-v2">
                    <img src="assets/blog9.jpg" alt="Audit Assurance">
                    <div class="category-badge green">Audit</div>
                </div>
                <div class="blog-content-v2">
                    <div class="meta-row">
                        <span><i class="far fa-calendar-alt"></i> Feb 06, 2026</span>
                        <span><i class="far fa-clock"></i> 7 min read</span>
                    </div>
                    <h3 class="blog-title-v2">Audit Readiness: Preparing Your Business for Year-End Audits</h3>
                    <p>A practical checklist to ensure smooth audit processes and timely completion</p>
                    <div class="card-footer-v2">
                        <a href="./blog-details/blog-details9.php" class="read-more-btn">Read Analysis <i class="fas fa-chevron-right"></i></a>
                        <div class="blog-social-share">
       <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" title="Share on LinkedIn" class="share-icon lnk"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://x.com/Karunesh_CMA" title="Share on Twitter" class="share-icon twt"><i class="fa-brands fa-x-twitter"></i></a>
       
    </div>
                    </div>
                </div>
            </div>

              <div class="blog-card-v2" data-category="startups">
                <div class="card-image-v2">
                    <img src="assets/blog10.jpg" alt="Audit Assurance">
                    <div class="category-badge brown">Startups</div>
                </div>
                <div class="blog-content-v2">
                    <div class="meta-row">
                        <span><i class="far fa-calendar-alt"></i> Dec 10, 2025</span>
                        <span><i class="far fa-clock"></i> 6 min read</span>
                    </div>
                    <h3 class="blog-title-v2">Legal and Tax Compliance Essentials for Startups in India</h3>
                    <p>A startup-focused guide covering registrations, filings, and regulatory obligations</p>
                    <div class="card-footer-v2">
                        <a href="./blog-details/blog-details10.php" class="read-more-btn">Read Analysis <i class="fas fa-chevron-right"></i></a>
                        <div class="blog-social-share">
       <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" title="Share on LinkedIn" class="share-icon lnk"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://x.com/Karunesh_CMA" title="Share on Twitter" class="share-icon twt"><i class="fa-brands fa-x-twitter"></i></a>
       
    </div>
                    </div>
                </div>
            </div>

             <div class="blog-card-v2" data-category="startups">
                <div class="card-image-v2">
                    <img src="assets/blog11.jpg" alt="Audit Assurance">
                    <div class="category-badge brown">Startups</div>
                </div>
                <div class="blog-content-v2">
                    <div class="meta-row">
                        <span><i class="far fa-calendar-alt"></i> Jan 25, 2026</span>
                        <span><i class="far fa-clock"></i> 7 min read</span>
                    </div>
                    <h3 class="blog-title-v2">Funding Readiness: Financial Due Diligence for Startups</h3>
                    <p>How startups can prepare financial records and compliance documents before raising funds.</p>
                    <div class="card-footer-v2">
                        <a href="./blog-details/blog-details11.php" class="read-more-btn">Read Analysis <i class="fas fa-chevron-right"></i></a>
                        <div class="blog-social-share">
       <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" title="Share on LinkedIn" class="share-icon lnk"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://x.com/Karunesh_CMA" title="Share on Twitter" class="share-icon twt"><i class="fa-brands fa-x-twitter"></i></a>
       
    </div>
                    </div>
                </div>
            </div>

             <div class="blog-card-v2" data-category="startups">
                <div class="card-image-v2">
                    <img src="assets/blog12.jpg" alt="Audit Assurance">
                    <div class="category-badge brown">Startups</div>
                </div>
                <div class="blog-content-v2">
                    <div class="meta-row">
                        <span><i class="far fa-calendar-alt"></i> Feb 12, 2026</span>
                        <span><i class="far fa-clock"></i> 5 min read</span>
                    </div>
                    <h3 class="blog-title-v2">Choosing the Right Business Structure for Your Startup</h3>
                    <p>A comparison of Pvt Ltd, LLP, and Proprietorship structures from tax and compliance perspectives</p>
                    <div class="card-footer-v2">
                        <a href="./blog-details/blog-details12.php" class="read-more-btn">Read Analysis <i class="fas fa-chevron-right"></i></a>
                        <div class="blog-social-share">
        <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" title="Share on LinkedIn" class="share-icon lnk"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://x.com/Karunesh_CMA" title="Share on Twitter" class="share-icon twt"><i class="fa-brands fa-x-twitter"></i></a>
        
    </div>
                    </div>
                </div>
            </div>

             <div class="blog-card-v2" data-category="accounting">
                <div class="card-image-v2">
                    <img src="assets/blog13.jpg" alt="Audit Assurance">
                    <div class="category-badge purple">Accounting</div>
                </div>
                <div class="blog-content-v2">
                    <div class="meta-row">
                        <span><i class="far fa-calendar-alt"></i> Dec 15, 2025</span>
                        <span><i class="far fa-clock"></i> 6 min read</span>
                    </div>
                    <h3 class="blog-title-v2">Importance of Accurate Bookkeeping for Business Growth</h3>
                    <p>Why maintaining accurate books is critical for financial stability and growth.</p>
                    <div class="card-footer-v2">
                        <a href="./blog-details/blog-details13.php" class="read-more-btn">Read Analysis <i class="fas fa-chevron-right"></i></a>
                        <div class="blog-social-share">
        <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" title="Share on LinkedIn" class="share-icon lnk"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://x.com/Karunesh_CMA" title="Share on Twitter" class="share-icon twt"><i class="fa-brands fa-x-twitter"></i></a>
       
    </div>
                    </div>
                </div>
            </div>

             <div class="blog-card-v2" data-category="accounting">
                <div class="card-image-v2">
                    <img src="assets/blog14.jpg" alt="Audit Assurance">
                    <div class="category-badge purple">Accounting</div>
                </div>
                <div class="blog-content-v2">
                    <div class="meta-row">
                        <span><i class="far fa-calendar-alt"></i> Jan 05, 2026</span>
                        <span><i class="far fa-clock"></i> 5 min read</span>
                    </div>
                    <h3 class="blog-title-v2">Accounting Standards Every Business Owner Should Know</h3>
                    <p>A simplified explanation of key accounting standards applicable to businesses</p>
                    <div class="card-footer-v2">
                        <a href="./blog-details/blog-details14.php" class="read-more-btn">Read Analysis <i class="fas fa-chevron-right"></i></a>
                        <div class="blog-social-share">
        <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" title="Share on LinkedIn" class="share-icon lnk"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://x.com/Karunesh_CMA" title="Share on Twitter" class="share-icon twt"><i class="fa-brands fa-x-twitter"></i></a>
    </div>
                    </div>
                </div>
            </div>

             <div class="blog-card-v2" data-category="accounting">
                <div class="card-image-v2">
                    <img src="assets/blog15.jpg" alt="Audit Assurance">
                    <div class="category-badge purple">Accounting</div>
                </div>
                <div class="blog-content-v2">
                    <div class="meta-row">
                        <span><i class="far fa-calendar-alt"></i> Feb 08, 2026</span>
                        <span><i class="far fa-clock"></i> 6 min read</span>
                    </div>
                    <h3 class="blog-title-v2">MIS Reporting: Turning Financial Data into Business Insights</h3>
                    <p>How management information systems support informed decision-making</p>
                    <div class="card-footer-v2">
                        <a href="./blog-details/blog-details15.php" class="read-more-btn">Read Analysis <i class="fas fa-chevron-right"></i></a>
                        <div class="blog-social-share">
       <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" title="Share on LinkedIn" class="share-icon lnk"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://x.com/Karunesh_CMA" title="Share on Twitter" class="share-icon twt"><i class="fa-brands fa-x-twitter"></i></a>
        
    </div>
                    </div>
                </div>
            </div>

             <div class="blog-card-v2" data-category="legal">
                <div class="card-image-v2">
                    <img src="assets/blog16.jpg" alt="Audit Assurance">
                    <div class="category-badge red">Legal Updates</div>
                </div>
                <div class="blog-content-v2">
                    <div class="meta-row">
                        <span><i class="far fa-calendar-alt"></i> Dec 28, 2025</span>
                        <span><i class="far fa-clock"></i> 5 min read</span>
                    </div>
                    <h3 class="blog-title-v2">Recent Changes in Company Law Impacting Businesses</h3>
                    <p>A summary of key amendments and notifications under the Companies Act</p>
                    <div class="card-footer-v2">
                        <a href="./blog-details/blog-details16.php" class="read-more-btn">Read Analysis <i class="fas fa-chevron-right"></i></a>
                        <div class="blog-social-share">
        <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" title="Share on LinkedIn" class="share-icon lnk"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://x.com/Karunesh_CMA" title="Share on Twitter" class="share-icon twt"><i class="fa-brands fa-x-twitter"></i></a>
</div>
                    </div>
                </div>
            </div>

            <div class="blog-card-v2" data-category="legal">
                <div class="card-image-v2">
                    <img src="assets/blog17.jpg" alt="Audit Assurance">
                    <div class="category-badge red">Legal Updates</div>
                </div>
                <div class="blog-content-v2">
                    <div class="meta-row">
                        <span><i class="far fa-calendar-alt"></i> Jan 18, 2026</span>
                        <span><i class="far fa-clock"></i> 6 min read</span>
                    </div>
                    <h3 class="blog-title-v2">Key Labour Law Updates Employers Must Comply </h3>
                    <p>Understanding recent labour law changes and their implications for employers</p>
                    <div class="card-footer-v2">
                        <a href="./blog-details/blog-details17.php" class="read-more-btn">Read Analysis <i class="fas fa-chevron-right"></i></a>
                        <div class="blog-social-share">
        <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" title="Share on LinkedIn" class="share-icon lnk"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://x.com/Karunesh_CMA" title="Share on Twitter" class="share-icon twt"><i class="fa-brands fa-x-twitter"></i></a>
        
    </div>
                    </div>
                </div>
            </div>

            <div class="blog-card-v2" data-category="legal">
                <div class="card-image-v2">
                    <img src="assets/blog18.jpg" alt="Audit Assurance">
                    <div class="category-badge red">Legal Updates</div>
                </div>
                <div class="blog-content-v2">
                    <div class="meta-row">
                        <span><i class="far fa-calendar-alt"></i> Feb 14, 2026</span>
                        <span><i class="far fa-clock"></i> 7 min read</span>
                    </div>
                    <h3 class="blog-title-v2">Regulatory Updates Every Business Should Watch This Year</h3>
                    <p>A curated roundup of important legal and regulatory developments affecting businesses</p>
                    <div class="card-footer-v2">
                        <a href="./blog-details/blog-details18.php" class="read-more-btn">Read Analysis <i class="fas fa-chevron-right"></i></a>
                        <div class="blog-social-share">
        <a href="https://www.linkedin.com/in/karunesh-kumar-05a142173?utm_source=share_via&utm_content=profile&utm_medium=member_android" title="Share on LinkedIn" class="share-icon lnk"><i class="fab fa-linkedin-in"></i></a>
        <a href="https://x.com/Karunesh_CMA" title="Share on Twitter" class="share-icon twt"><i class="fa-brands fa-x-twitter"></i></a>
        
    </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="noResults" class="no-results-box">
            <div class="no-results-content">
                <i class="fas fa-search"></i>
                <p>No articles found matching your criteria.</p>
                <button onclick="resetFilters()" class="reset-btn">View All Articles</button>
            </div>
        </div>

    </div>
</section>

<style>

  /* Social Share Styles */
.card-footer-v2 {
    margin-top: auto;
    padding-top: 20px;
    border-top: 1px solid #f1f5f9;
    display: flex; /* Added flex to align link and social side-by-side */
    justify-content: space-between;
    align-items: center;
}

.blog-social-share {
    display: flex;
    gap: 12px;
}

.share-icon {
    font-size: 14px;
    color: #94a3b8;
    transition: all 0.3s ease;
    text-decoration: none;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #f8fafc;
}

.share-icon:hover {
    transform: translateY(-3px);
}

.share-icon.lnk:hover { color: #0077b5; background: rgba(0, 119, 181, 0.1); }
.share-icon.twt:hover { color: #1da1f2; background: rgba(29, 161, 242, 0.1); }
.share-icon.copy:hover { color: #ff8c00; background: rgba(255, 140, 0, 0.1); }

/* Ensure the button doesn't shrink */
.read-more-btn {
    flex-shrink: 0;
}

/* Responsive adjustment */
@media (max-width: 400px) {
    .card-footer-v2 {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
}
    /* Section Root */
    .blogs-page {
        padding: 120px 0;
        background: #f8fafc; /* Lighter, cleaner background */
    }

    .blog-intro {
        text-align: center;
        max-width: 800px;
        margin: 0 auto 60px;
    }
.service-pill {
    display: inline-block;
    background: rgba(11, 60, 116, 0.08);
    color: #0b3c74;
    padding: 8px 24px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 2px;
    
    /* MOVES IT UP */
    margin-top: -90px;      /* Pulls it towards the top of the section */
  
    
    border: 1px solid rgba(11, 60, 116, 0.1);
    box-shadow: 0 4px 10px rgba(0,0,0,0.02);
}

.section-intro {
    padding-top: 40px; /* Provides space so the pill doesn't hit the section above */
    text-align: center;
}

    .blog-intro h2 {
        font-size: 42px;
        color: #0b3c74;
        font-weight: 700;
        margin: 20px 0;
    }

    /* Toolbar Redesign */
    .blog-toolbar {
        max-width: 1200px;
        margin: 0 auto 50px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        padding: 0 20px;
    }

    .search-box-v2 {
        position: relative;
        flex: 1;
        max-width: 450px;
    }

    .search-box-v2 i {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }

    .search-box-v2 input {
        width: 100%;
        padding: 16px 20px 16px 55px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        background: #fff;
        font-size: 15px;
        transition: all 0.3s ease;
    }

    .search-box-v2 input:focus {
        border-color: #0b3c74;
        box-shadow: 0 10px 25px rgba(11, 60, 116, 0.05);
        outline: none;
    }

    .filter-group-v2 {
        display: flex;
        gap: 8px;
        background: #edf2f7;
        padding: 6px;
        border-radius: 12px;
    }

    .filter-btn-v2 {
        padding: 10px 22px;
        border: none;
        background: transparent;
        color: #64748b;
        font-weight: 700;
        border-radius: 8px;
        cursor: pointer;
        transition: 0.3s;
        font-size: 14px;
    }

    .filter-btn-v2.active {
        background: #fff;
        color: #0b3c74;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    /* Card Redesign */
    .blogs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 35px;
        max-width: 1370px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .blog-card-v2 {
        background: #fff;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid rgba(226, 232, 240, 0.7);
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .blog-card-v2:hover {
        transform: translateY(-12px);
        box-shadow: 0 30px 60px rgba(11, 60, 116, 0.12);
        border-color: #0b3c74;
    }

    .card-image-v2 {
        position: relative;
        height: 240px;
        overflow: hidden;
    }

    .card-image-v2 img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s ease;
    }

    .blog-card-v2:hover .card-image-v2 img {
        transform: scale(1.1);
    }

    .category-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        background: #ff8c00;
        color: #fff;
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .category-badge.blue { background: #0b3c74; }
    .category-badge.green { background: #10b981; }
     .category-badge.brown { background: #bb7813; }
      .category-badge.purple { background: #881bc2; }
       .category-badge.red { background: #df1d1d; }

    .blog-content-v2 {
        padding: 30px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .meta-row {
        display: flex;
        gap: 15px;
        font-size: 12px;
        color: #94a3b8;
        font-weight: 600;
        margin-bottom: 15px;
    }

    .blog-title-v2 {
        font-size: 22px;
        color: #0b3c74;
        font-weight: 700;
        line-height: 1.3;
        margin-bottom: 15px;
        transition: 0.3s;
    }

    .blog-card-v2:hover .blog-title-v2 {
        color: #ff8c00;
    }

    .blog-content-v2 p {
        color: #64748b;
        font-size: 15px;
        line-height: 1.6;
        margin-bottom: 25px;
    }

    .card-footer-v2 {
        margin-top: auto;
        padding-top: 20px;
        border-top: 1px solid #f1f5f9;
    }

    .read-more-btn {
        text-decoration: none;
        color: #0b3c74;
        font-weight: 700;
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: 0.3s;
    }

    .read-more-btn:hover {
        color: #ff8c00;
    }

    .read-more-btn i {
        font-size: 12px;
        transition: 0.3s;
    }

    .read-more-btn:hover i {
        transform: translateX(5px);
    }

    /* No Results Box */
    .no-results-box {
        display: none;
        text-align: center;
        padding: 80px 20px;
    }

    .no-results-content i {
        font-size: 50px;
        color: #cbd5e1;
        margin-bottom: 20px;
    }

    .reset-btn {
        margin-top: 20px;
        padding: 12px 25px;
        background: #0b3c74;
        color: #fff;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .blog-toolbar { flex-direction: column; align-items: stretch; }
        .search-box-v2 { max-width: 100%; }
        .filter-group-v2 { overflow-x: auto; padding-bottom: 10px; }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.querySelector('#blogSearch');
    const filterBtns = document.querySelectorAll('.filter-btn-v2');
    const cards = document.querySelectorAll('.blog-card-v2');
    const noResults = document.querySelector('#noResults');

    /**
     * Core Filter Function
     */
    function filterBlogs() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const activeFilter = document
            .querySelector('.filter-btn-v2.active')
            .getAttribute('data-filter');

        let visibleCount = 0;

        cards.forEach(card => {
            const title = card.querySelector('.blog-title-v2')?.innerText.toLowerCase() || '';
            const description = card.querySelector('.blog-content-v2 p')?.innerText.toLowerCase() || '';
            const category = card.getAttribute('data-category')?.toLowerCase() || '';
            const badge = card.querySelector('.category-badge')?.innerText.toLowerCase() || '';

            // SEARCH MATCH
            const matchesSearch =
                title.includes(searchTerm) ||
                description.includes(searchTerm) ||
                category.includes(searchTerm) ||
                badge.includes(searchTerm);

            // CATEGORY FILTER MATCH
            const matchesFilter =
                activeFilter === 'all' || category === activeFilter;

            if (matchesSearch && matchesFilter) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // No Results Message
        if (visibleCount === 0) {
            noResults.style.display = 'block';
        } else {
            noResults.style.display = 'none';
        }
    }

    /**
     * Event Listeners
     */

    // Category Buttons
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            filterBlogs();
        });
    });

    // Live Search
    searchInput.addEventListener('input', filterBlogs);

    /**
     * Reset Filters
     */
    window.resetFilters = function () {
        searchInput.value = '';
        filterBtns.forEach(b => b.classList.remove('active'));
        filterBtns[0].classList.add('active'); // "All Articles"
        filterBlogs();
    };
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
        const response = await fetch('chat_proxy.php', {
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
                <a href="../Account-Website/privacy-policy.php">Privacy Policy</a>
                <span class="sep">|</span>
                <a href="../Account-Website/terms-of-service.php">Terms of Service</a>
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