<?php
// DB CONFIG
$host = "localhost";
$user = "root";        // change if needed
$pass = "";            // change if needed
$db   = "accounting_site";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
  die("Database connection failed");
}

// FORM SUBMIT
if (isset($_POST['submit'])) {

  $name    = mysqli_real_escape_string($conn, $_POST['name']);
  $email   = mysqli_real_escape_string($conn, $_POST['email']);
  $subject = mysqli_real_escape_string($conn, $_POST['subject']);
  $message = mysqli_real_escape_string($conn, $_POST['message']);

  $sql = "INSERT INTO contact_messages (name, email, subject, message)
          VALUES ('$name', '$email', '$subject', '$message')";

  if (mysqli_query($conn, $sql)) {
    $success = true;
  } else {
    $error = true;
  }
}
?>
<?php
$conn = mysqli_connect("localhost", "root", "", "accounting_site");

if (!$conn) {
  die("Database connection failed");
}

if (isset($_POST['apply'])) {

  // ---------- FORM DATA ----------
  $name       = mysqli_real_escape_string($conn, $_POST['name']);
  $email      = mysqli_real_escape_string($conn, $_POST['email']);
  $phone      = mysqli_real_escape_string($conn, $_POST['phone']);
  $position   = mysqli_real_escape_string($conn, $_POST['position']);
  $experience = mysqli_real_escape_string($conn, $_POST['experience']);
  $cover      = mysqli_real_escape_string($conn, $_POST['cover']);

  // ---------- FILE DATA ----------
  $resumeName = $_FILES['resume']['name'];
  $resumeTmp  = $_FILES['resume']['tmp_name'];
  $resumeSize = $_FILES['resume']['size'];

  $resumeExt = strtolower(pathinfo($resumeName, PATHINFO_EXTENSION));

  $allowed = ['pdf', 'doc', 'docx'];

  // ---------- UPLOAD DIRECTORY (ABSOLUTE PATH) ----------
  $uploadDir = __DIR__ . "/uploads/resumes/";

  // Create folder if not exists
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
  }

  // ---------- VALIDATION ----------
  if (in_array($resumeExt, $allowed)) {

    if ($resumeSize <= 2 * 1024 * 1024) { // 2MB limit

      $newName = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $resumeName);
      $uploadPath = $uploadDir . $newName;

      if (move_uploaded_file($resumeTmp, $uploadPath)) {

        $sql = "INSERT INTO career_applications
                (name, email, phone, position, experience, resume, cover_letter)
                VALUES
                ('$name', '$email', '$phone', '$position', '$experience', '$newName', '$cover')";

        if (mysqli_query($conn, $sql)) {
          $success = "Application submitted successfully!";
        } else {
          $error = "Database error!";
        }
      } else {
        $error = "File upload failed!";
      }
    } else {
      $error = "Resume must be under 2MB";
    }
  } else {
    $error = "Only PDF, DOC, DOCX files allowed";
  }
}
?>


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
          <li><a href="#home">Home</a></li>
          <li><a href="#about">About</a></li>
          <li><a href="#services">Services</a></li>
          <li><a href="#insights">Insights</a></li>
          <li><a href="#testimonials">Testimonials</a></li>
          <li><a href="#team">Team</a></li>
          <li><a href="#career">Careers</a></li>
          <li><a href="#contact">Contact</a></li>
        </ul>
        <button class="btn-login" onclick="window.location.href='Register.php'">
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
      <button class="drawer-login-btn" onclick="window.location.href='Register.php'">
        Portal Login
      </button>
      <nav class="drawer-nav-list">
        <a href="#home" class="d-link"><i class="fa-solid fa-house"></i> Home</a>
        <a href="#about" class="d-link"><i class="fa-solid fa-building"></i> About</a>
        <a href="#services" class="d-link"><i class="fa-solid fa-gears"></i> Services</a>
        <a href="#insights" class="d-link"><i class="fa-solid fa-chart-line"></i> Insights</a>
        <a href="#testimonials" class="d-link"><i class="fa-solid fa-user-circle"></i>Testimonials</a>
        <a href="#team" class="d-link"><i class="fa-solid fa-users"></i> Team</a>
        <a href="#career" class="d-link"><i class="fa-solid fa-user-tie"></i> Careers</a>
        <a href="#contact" class="d-link"><i class="fa-solid fa-envelope"></i> Contact</a>
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
  </script>
  <section class="premium-hero" id="home">
    <div class="hero-slides">
      <div class="h-slide active" style="background-image: url('assets/Bookkeeping.jpg');"></div>
      <div class="h-slide" style="background-image: url('assets/Budgeting.jpg');"></div>
      <div class="h-slide" style="background-image: url('assets/accounting1.jpg');"></div>
      <div class="h-slide" style="background-image: url('assets/Consulting4.jpg');"></div>
      <div class="h-slide" style="background-image: url('assets/Consulting2.jpg');"></div>
      <div class="h-slide" style="background-image: url('assets/Compilance1.jpg');"></div>
      <div class="h-slide" style="background-image: url('assets/Compilance2.jpg');"></div>
      <div class="h-slide" style="background-image: url('assets/Audit1.jpg');"></div>
    </div>

    <div class="hero-vignette"></div>

    <div class="hero-wrapper">
      <div class="brand-reveal">
        <h2 class="firm-title">KARUNESH KUMAR <span class="orange-text">& ASSOCIATES</span></h2>
        <div class="firm-info">
          <span class="line orange-bg"></span>
          <p>COST ACCOUNTANTS</p>
          <span class="line orange-bg"></span>
        </div>
      </div>

      <div class="content-reveal">
        <h1 class="hero-headline">
          <span class="orange-text">Precision</span> in Accounting,<br>
          Excellence in <span class="orange-text">Audit.</span>
        </h1>

        <div class="hero-features-list">
          <div class="f-item"><i class="fas fa-check"></i> <span>Incorporation</span></div>
          <div class="f-item"><i class="fas fa-check"></i> <span>Accounting</span></div>
          <div class="f-item"><i class="fas fa-check"></i> <span>Auditing</span></div>
          <div class="f-item"><i class="fas fa-check"></i> <span>Taxation</span></div>
          <div class="f-item"><i class="fas fa-check"></i> <span>Compliances</span></div>
          <div class="f-item"><i class="fas fa-check"></i> <span>Startups</span></div>
        </div>

        <div class="button-group">
          <a href="Register.php" class="btn-orange">Start Consultation</a>
          <a href="#services" class="btn-outline">Our Services</a>
        </div>
      </div>
    </div>
  </section>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&family=Playfair+Display:ital,wght@0,700;1,700&display=swap');

    .premium-hero {
      position: relative;
      height: 100vh;
      width: 100%;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #0b3c74;
    }

    /* Background Logic */
    .hero-slides,
    .h-slide {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
    }

    .h-slide {
      background-size: cover;
      background-position: center;
      opacity: 0;
      transition: opacity 2s ease-in-out;
    }

    .h-slide.active {
      opacity: 1;
      animation: zoomEffect 10s infinite alternate;
    }

    @keyframes zoomEffect {
      from {
        transform: scale(1);
      }

      to {
        transform: scale(1.08);
      }
    }

    .hero-vignette {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(to bottom, rgba(11, 60, 116, 0.9), rgba(0, 0, 0, 0.7));
      z-index: 2;
    }

    .hero-wrapper {
      position: relative;
      z-index: 10;
      text-align: center;
      max-width: 1000px;
      padding: 20px;
      margin-top: 70px;
    }

    /* Colors */
    .orange-text {
      color: #ff8c00;
    }

    .orange-bg {
      background: #ff8c00;
    }

    /* Branding */
    .firm-title {
      font-family: 'Montserrat', sans-serif;
      font-size: clamp(18px, 4vw, 26px);
      font-weight: 300;
      letter-spacing: 4px;
      color: #fff;
      margin: 0;
      text-transform: uppercase;
      margin-top: -100px;
    }

    .firm-title span {
      font-weight: 700;
    }

    .firm-info {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 15px;
      margin-top: 10px;
    }

    .firm-info p {
      font-size: clamp(10px, 2vw, 12px);
      letter-spacing: 4px;
      color: rgba(255, 255, 255, 0.7);
      margin: 0;
    }

    .line {
      width: 30px;
      height: 1px;
    }

    /* Headline */
    .hero-headline {
      font-family: 'Playfair Display', serif;
      font-size: clamp(32px, 8vw, 68px);
      line-height: 1.1;
      color: #fff;
      margin: 40px 0;
    }

    /* Clean Feature List (3x2 Desktop, 1col Mobile) */
    .hero-features-list {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 15px 40px;
      max-width: 850px;
      margin: 0 auto 50px;
    }

    .f-item {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      color: #f1f1f1;
      font-family: 'Montserrat', sans-serif;
      font-size: clamp(13px, 2vw, 15px);
      letter-spacing: 0.5px;
    }

    .f-item i {
      color: #ff8c00;
      font-size: 13px;
    }

    /* Buttons */
    .button-group {
      display: flex;
      justify-content: center;
      gap: 20px;
    }

    .btn-orange {
      background: #ff8c00;
      color: #fff;
      padding: clamp(12px, 3vw, 18px) clamp(25px, 5vw, 45px);
      font-family: 'Montserrat', sans-serif;
      font-weight: 700;
      text-decoration: none;
      border-radius: 4px;
      transition: 0.3s;
    }

    .btn-outline {
      border: 1px solid #ffffff;
      color: #ffffff;
      padding: clamp(12px, 3vw, 18px) clamp(25px, 5vw, 45px);
      font-family: 'Montserrat', sans-serif;
      text-decoration: none;
      border-radius: 4px;
      transition: 0.3s;
    }

    .btn-orange:hover {
      background: #e67e00;
      transform: translateY(-3px);
    }

    .btn-outline:hover {
      background: #fff;
      color: #0b3c74;
    }

    /* --- RESPONSIVE GAP ALIGNMENT --- */
    @media (max-width: 850px) {
      .hero-features-list {
        grid-template-columns: repeat(2, 1fr);
        /* 2 per row for tablets */
        gap: 20px;
      }
    }

    @media (max-width: 550px) {
      .hero-features-list {
        grid-template-columns: 1fr;
        /* 1 per row for small phones */
        gap: 12px;
      }

      .button-group {
        flex-direction: column;
        width: 100%;
        max-width: 280px;
        margin: 0 auto;
      }
    }
  </style>

  <script>
    const slides = document.querySelectorAll('.h-slide');
    let currentIdx = 0;
    setInterval(() => {
      slides[currentIdx].classList.remove('active');
      currentIdx = (currentIdx + 1) % slides.length;
      slides[currentIdx].classList.add('active');
    }, 5000);
  </script>
  <section class="about-firm-section" id="about">
    <div class="about-firm-container">

      <div class="about-firm-intro">
        <div class="about-firm-logo-wrapper">
          <img src="assets/CMA.jpg" alt="Firm Logo" class="about-firm-logo">
        </div>

        <div class="about-firm-text-area">
          <h2 class="about-firm-title">About Our <span class="text-orange">Firm</span></h2>
          <div class="about-firm-accent-line"></div>

          <p class="about-firm-para">
            <strong>Karunesh Kumar & Associates</strong> is a leading firm of Cost Accountants and professional advisors, delivering high-quality audit, assurance, taxation, and advisory services to organizations across diverse sectors.
          </p>

          <p class="about-firm-para">
            Founded in 2021 and headquartered in Patna, Bihar, the firm has established itself as a trusted partner for businesses, financial institutions, and government-linked entities by consistently demonstrating professional excellence.
          </p>

          <p class="about-firm-para">
            We combine global best practices with strong local expertise to help our clients navigate complex regulatory environments, manage risk, and create sustainable value.
          </p>
        </div>
      </div>

      <div class="about-firm-boxes-row">

        <div class="about-firm-card">
          <div class="about-firm-icon-circle">
            <i class="fas fa-bullseye"></i>
          </div>
          <h3>Our Purpose</h3>
          <p>To support our clients in building resilient, transparent, and high-performing organizations through expert guidance.</p>
          <div class="card-step-line"></div>
        </div>

        <div class="about-firm-card">
          <div class="about-firm-icon-circle">
            <i class="fas fa-users"></i>
          </div>
          <h3>Our Team</h3>
          <p>Our multidisciplinary team works collaboratively to deliver solutions that are practical, ethical, and forward-looking.</p>
          <div class="card-step-line"></div>
        </div>

        <div class="about-firm-card">
          <div class="about-firm-icon-circle">
            <i class="fas fa-globe"></i>
          </div>
          <h3>Our Reach</h3>
          <p>We are actively engaged in audits, GST advisory, and consulting assignments on a pan-India basis.</p>
          <div class="card-step-line"></div>
        </div>

      </div>
    </div>
  </section>

  <style>
    /* --- UPDATED ABOUT FIRM STYLES --- */
    .about-firm-section {
      padding: 120px 0;
      background: #ffffff;
      width: 100%;
      position: relative;
    }

    .about-firm-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 25px;
    }

    /* Intro Layout */
    .about-firm-intro {
      display: flex;
      align-items: center;
      /* Better alignment for text vs logo */
      gap: 80px;
      margin-bottom: 100px;
    }

    /* Logo Wrapper with Badge */
    .about-firm-logo-wrapper {
      flex: 0 0 350px;
      position: relative;
    }

    .about-firm-logo {
      width: 100%;
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(11, 60, 120, 0.08);
      transition: 0.4s ease;
      border: 1px solid #f0f4f8;
    }



    /* Text Styling */
    .about-firm-text-area {
      flex: 1;
    }

    .about-firm-title {
      font-size: 40px;
      color: #0b3c78;
      font-weight: 700;
      margin-bottom: 15px;
    }

    .text-orange {
      color: #ff8c00;
    }

    .about-firm-accent-line {
      width: 60px;
      height: 5px;
      background: #ff8c00;
      margin-bottom: 35px;
      border-radius: 10px;
    }

    .about-firm-para {
      font-size: 17px;
      line-height: 1.8;
      color: #475569;
      margin-bottom: 22px;
      text-align: justify;
    }

    /* Horizontal Cards */
    .about-firm-boxes-row {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 30px;
    }

    .about-firm-card {
      background: #f8fafc;
      padding: 40px 30px;
      border-radius: 24px;
      text-align: center;
      transition: 0.4s;
      position: relative;
      border: 1px solid transparent;
    }

    .about-firm-card:hover {
      background: #fff;
      border-color: #e2e8f0;
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
    }

    .about-firm-icon-circle {
      width: 70px;
      height: 70px;
      background: #0b3c78;
      color: #fff;
      border-radius: 50%;
      display: flex;
      margin: 0 auto 25px;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      transition: 0.4s;
    }

    .about-firm-card:hover .about-firm-icon-circle {
      background: #ff8c00;
      transform: rotateY(360deg);
    }

    .about-firm-card h3 {
      font-size: 22px;
      color: #0b3c78;
      font-weight: 700;
      margin-bottom: 15px;
    }

    .about-firm-card p {
      font-size: 15px;
      color: #64748b;
      line-height: 1.6;
    }

   @media (max-width: 992px) {
  .about-firm-intro {
    flex-direction: column-reverse; /* 👈 This will push logo below */
    text-align: center;
  }

  .about-firm-logo-wrapper {
    max-width: 320px;
    margin: 30px auto 0; /* add space above logo */
  }

  .about-firm-accent-line {
    margin: 0 auto 30px;
  }

  .about-firm-boxes-row {
    grid-template-columns: 1fr;
  }

  .about-firm-para {
    text-align: center;
  }

  .about-firm-section {
    padding: 80px 0;
  }

  .logo-experience-badge {
    left: 50%;
    transform: translateX(-50%);
    top: -30px;
  }
}

  </style>

  <section class="stats-section">
    <div class="stats-overlay"></div>
    <div class="container-premium stats-grid">

      <div class="stat-card">
        <div class="stat-icon-wrap">
          <i class="fas fa-award"></i>
        </div>
        <div class="stat-info">
          <h3 class="counter-val" data-target="5">0</h3>
          <span class="plus">+</span>
          <p>Years of Excellence</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrap">
          <i class="fas fa-building"></i>
        </div>
        <div class="stat-info">
          <h3 class="counter-val" data-target="120">0</h3>
          <span class="plus">+</span>
          <p>Corporate Clients</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrap">
          <i class="fas fa-file-signature"></i>
        </div>
        <div class="stat-info">
          <h3 class="counter-val" data-target="250">0</h3>
          <span class="plus">+</span>
          <p>Statutory Audits</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrap">
          <i class="fas fa-map-marked-alt"></i>
        </div>
        <div class="stat-info">
          <h3 class="counter-val" data-target="12">0</h3>
          <span class="plus">+</span>
          <p>States Reached</p>
        </div>
      </div>

    </div>
  </section>

  <style>
    /* --- STATS SECTION STYLING --- */
    .stats-section {
      padding: 80px 0;
      background: #0b3c74;
      /* Your Signature Navy */
      background-image: linear-gradient(135deg, #0b3c74 0%, #082d56 100%);
      position: relative;
      overflow: hidden;
      color: #fff;
    }

    /* Subtle background pattern */
    .stats-section::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('https://www.transparenttextures.com/patterns/carbon-fibre.png');
      opacity: 0.1;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 30px;
      position: relative;
      z-index: 2;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .stat-card {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      padding: 40px 20px;
      border-radius: 20px;
      text-align: center;
      transition: 0.4s ease;
    }

    .stat-card:hover {
      background: rgba(255, 255, 255, 0.1);
      transform: translateY(-10px);
      border-color: #ff8c00;
    }

    .stat-icon-wrap {
      width: 60px;
      height: 60px;
      background: #ff8c00;
      /* Your Signature Orange */
      color: #fff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      font-size: 24px;
      box-shadow: 0 10px 20px rgba(255, 140, 0, 0.3);
    }

    .stat-info h3 {
      display: inline-block;
      font-size: 42px;
      font-weight: 800;
      margin: 0;
      color: #fff;
    }

    .plus {
      font-size: 28px;
      font-weight: 800;
      color: #ff8c00;
      margin-left: 2px;
    }

    .stat-info p {
      margin-top: 10px;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      color: #cbd5e1;
      font-weight: 600;
    }

    /* --- RESPONSIVE --- */
    @media (max-width: 992px) {
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 576px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }

      .stat-card {
        padding: 30px 15px;
      }
    }
  </style>

  <script>
    // --- COUNTER SCRIPT ---
    const counters = document.querySelectorAll('.counter-val');
    const speed = 200; // The lower the slower

    const startCounters = () => {
      counters.forEach(counter => {
        const updateCount = () => {
          const target = +counter.getAttribute('data-target');
          const count = +counter.innerText;
          const inc = target / speed;

          if (count < target) {
            counter.innerText = Math.ceil(count + inc);
            setTimeout(updateCount, 15);
          } else {
            counter.innerText = target;
          }
        };
        updateCount();
      });
    };

    // Start when the section is visible
    const observerOptions = {
      threshold: 0.5
    };
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          startCounters();
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);

    observer.observe(document.querySelector('.stats-section'));
  </script>

  <section class="founder">
    <div class="container founder-grid">
      <div class="founder-img">
        <div class="img-frame">
          <img src="assets/person1.jpg" alt="CMA Karunesh Kumar">
        </div>
      </div>

      <div class="founder-content">
        <span class="pre-title">Leadership</span>
        <h2>Founder’s Desk</h2>
        <div class="line"></div>

        <h3>CMA Karunesh Kumar</h3>
        <span class="designation">Founder & Managing Partner</span>

        <p>The firm is led by Mr. Karunesh Kumar, CMA, a highly respected professional with extensive experience in audit, assurance, and regulatory advisory services.</p>

        <ul class="founder-points">
          <li><i class="fas fa-check-circle"></i> <span>Strong technical expertise</span></li>
          <li><i class="fas fa-check-circle"></i> <span>Commitment to professional ethics</span></li>
          <li><i class="fas fa-check-circle"></i> <span>Focus on quality and risk management</span></li>
          <li><i class="fas fa-check-circle"></i> <span>Client-first philosophy</span></li>
        </ul>

        <p>Under his guidance, the firm has developed robust governance frameworks and quality control systems aligned with the highest professional standards.</p>
      </div>
    </div>
  </section>

  <style>
    .founder {
      padding: 80px 0;
      background: #f7f9fc;
      overflow: hidden;
    }

    .founder-grid {
      display: grid;
      /* Changed from fixed 300px to fractional for better fluidity */
      grid-template-columns: 0.8fr 1.2fr;
      gap: 60px;
      align-items: center;
      max-width: 1100px;
      margin: 0 auto;
      padding: 0 20px;
    }

    /* Image Styling */
    .founder-img {
      position: relative;
      z-index: 1;
    }

    .img-frame {
      position: relative;
      padding-bottom: 10px;
    }

    /* Decorative background element for a premium look */
    .img-frame::before {
      content: '';
      position: absolute;
      top: 20px;
      left: -20px;
      width: 100%;
      height: 100%;
      border: 2px solid #0b3c78;
      border-radius: 12px;
      z-index: -1;
    }

    .founder-img img {
      width: 100%;
      height: auto;
      /* Removed fixed 410px for responsiveness */
      aspect-ratio: 3/4;
      /* Maintains professional portrait ratio */
      object-fit: cover;
      border-radius: 12px;
      box-shadow: 20px 20px 60px rgba(0, 0, 0, 0.08);
    }

    /* Content Styling */
    .pre-title {
      color: #ff8c00;
      text-transform: uppercase;
      font-weight: 800;
      font-size: 12px;
      letter-spacing: 2px;
      display: block;
      margin-bottom: 10px;
    }

    .founder-content h2 {
      font-size: clamp(32px, 5vw, 42px);
      /* Responsive font size */
      color: #0b3c78;
      margin-bottom: 10px;
      font-weight: 700;
    }

    .founder-content .line {
      width: 60px;
      height: 4px;
      background: #ff8c00;
      margin-bottom: 30px;
      border-radius: 2px;
    }

    .founder-content h3 {
      font-size: 26px;
      margin-bottom: 5px;
      color: #1a202c;
    }

    .designation {
      display: block;
      font-size: 14px;
      text-transform: uppercase;
      color: #4a5568;
      font-weight: 600;
      margin-bottom: 25px;
      letter-spacing: 1px;
      background: #edf2f7;
      display: inline-block;
      padding: 4px 12px;
      border-radius: 4px;
    }

    .founder-content p {
      font-size: 17px;
      line-height: 1.7;
      color: #4a5568;
      margin-bottom: 20px;
    }

    .founder-points {
      list-style: none;
      padding: 0;
      margin: 25px 0;
      display: grid;
      grid-template-columns: 1fr 1fr;
      /* Two columns for points on desktop */
      gap: 15px;
    }

    .founder-points li {
      display: flex;
      align-items: flex-start;
      font-size: 15px;
      color: #2d3748;
      font-weight: 500;
    }

    .founder-points li i {
      color: #0b3c78;
      margin-right: 12px;
      font-size: 18px;
      margin-top: 2px;
    }

    /* --- TABLET & MOBILE RESPONSIVE --- */

    @media (max-width: 992px) {
      .founder-grid {
        grid-template-columns: 1fr;
        /* Stack vertically */
        gap: 50px;
        text-align: center;
      }

      .founder-img {
        max-width: 350px;
        /* Slightly wider on mobile for impact */
        margin: 0 auto;
      }

      .img-frame::before {
        left: 15px;
        /* Adjust decoration for center alignment */
      }

      .founder-content .line {
        margin: 0 auto 30px;
      }

      .founder-points {
        grid-template-columns: 1fr;
        /* Stack points on small tablets */
        max-width: 400px;
        margin: 25px auto;
        text-align: left;
      }

      .designation {
        margin: 0 auto 25px;
      }
    }

    @media (max-width: 480px) {
      .founder {
        padding: 60px 0;
      }

      .founder-content h2 {
        font-size: 30px;
      }

      .founder-points {
        padding-left: 20px;
        /* Better alignment for centered text */
      }
    }
  </style>
  <section class="mvv-section" id="values">
    <div class="container-premium">
      <div class="mvv-grid">

        <div class="mvv-card">
          <div class="mvv-icon-box">
            <i class="fas fa-bullseye"></i>
          </div>
          <div class="mvv-content">
            <h3>Our Mission</h3>
            <div class="mvv-line"></div>
            <p>To deliver independent, objective, and high-impact professional services that enable our clients to achieve sustainable growth and stakeholder trust.</p>
          </div>
        </div>

        <div class="mvv-card">
          <div class="mvv-icon-box">
            <i class="fas fa-eye"></i>
          </div>
          <div class="mvv-content">
            <h3>Our Vision</h3>
            <div class="mvv-line"></div>
            <p>To be recognized as a premier professional services firm, known for excellence, reliability, and leadership in audit and advisory services.</p>
          </div>
        </div>

        <div class="mvv-card card-highlight">
          <div class="mvv-icon-box">
            <i class="fas fa-gem"></i>
          </div>
          <div class="mvv-content">
            <h3>Our Values</h3>
            <div class="mvv-line"></div>
            <ul class="values-list">
              <li><i class="fas fa-caret-right"></i> Integrity & Ethics</li>
              <li><i class="fas fa-caret-right"></i> Excellence & Accountability</li>
              <li><i class="fas fa-caret-right"></i> Client-First Approach</li>
              <li><i class="fas fa-caret-right"></i> Continuous Innovation</li>
            </ul>
          </div>
        </div>

      </div>
    </div>
  </section>

  <style>
    /* --- MVV SECTION STYLING --- */
    .mvv-section {
      padding: 100px 0;
      background: #f7f9fc;
      font-family: 'Inter', sans-serif;
    }

    .container-premium {
      max-width: 1300px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .mvv-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 40px;
    }

    .mvv-card {
      background: #ffffff;
      padding: 50px 35px;
      border-radius: 25px;
      position: relative;
      box-shadow: 0 15px 35px rgba(11, 60, 116, 0.05);
      transition: all 0.4s ease;
      border: 1px solid #eef2f6;
      display: flex;
      flex-direction: column;
    }

    .mvv-card:hover {
      transform: translateY(-12px);
      box-shadow: 0 25px 50px rgba(11, 60, 116, 0.1);
      border-color: #3b82f6;
    }

    /* Icon Box Styling - Fully Visible */
    .mvv-icon-box {
      width: 70px;
      height: 70px;
      background: #ff8c00;
      /* Your Orange */
      color: #fff;
      border-radius: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      margin-bottom: 25px;
      box-shadow: 0 10px 20px rgba(255, 140, 0, 0.2);
      transition: 0.4s;
    }

    .mvv-card:hover .mvv-icon-box {
      background: #0b3c74;
      /* Flips to Navy on hover */
      transform: scale(1.1);
    }

    .mvv-content h3 {
      font-size: 24px;
      color: #0b3c74;
      margin-bottom: 15px;
      font-weight: 700;
    }

    .mvv-line {
      width: 40px;
      height: 4px;
      background: #ff8c00;
      margin-bottom: 20px;
      border-radius: 10px;
    }

    .mvv-content p {
      color: #64748b;
      line-height: 1.7;
      font-size: 16px;
      margin: 0;
    }

    /* Values List Styling */
    .values-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .values-list li {
      display: flex;
      align-items: center;
      gap: 10px;
      color: #475569;
      font-weight: 600;
      margin-bottom: 10px;
      font-size: 15px;
    }

    .values-list li i {
      color: #ff8c00;
      font-size: 12px;
    }

    /* Highlight Card Style */
    .card-highlight {
      background: linear-gradient(to bottom right, #ffffff, #f0f9ff);
      border: 1px solid #bae6fd;
    }

    /* --- MOBILE RESPONSIVE --- */
    @media (max-width: 992px) {
      .mvv-grid {
        grid-template-columns: 1fr;
        max-width: 600px;
        margin: 0 auto;
      }

      .mvv-card {
        padding: 40px 30px;
        text-align: center;
        align-items: center;
      }

      .mvv-line {
        margin: 0 auto 20px;
      }

      .values-list li {
        justify-content: center;
      }
    }
  </style>

  <section class="why-choose-us" id="why-choose-us">
    <div class="container-premium">

      <div class="section-intro">
        <div class="service-box-tag">
          <span class="s-num">Core Advantage</span>
          <span class="s-name">Strategic Excellence</span>
        </div>
        <h2>Why Choose Our <span class="text-navy">Firm</span></h2>
        <div class="dual-accent">
          <span class="bar-navy"></span>
          <span class="bar-orange"></span>
        </div>
        <p class="desc-text">
          Clients choose Karunesh Kumar & Associates for our unique blend of regulatory precision and growth-focused advisory.
        </p>
      </div>

      <div class="choose-grid">

        <div class="choose-card">
          <div class="choose-img-box">
            <img src="assets/Experienced.jpg" alt="Experienced Team">
            <div class="choose-overlay"></div>
          </div>
          <div class="choose-content">
            <div class="choose-icon-small"><i class="fas fa-history"></i></div>
            <h3>Proven Track Record</h3>
            <p>Proven track record in audit and assurance, helping firms stay compliant for years.</p>
          </div>
        </div>

        <div class="choose-card">
          <div class="choose-img-box">
            <img src="assets/Tax.jpg" alt="Tax Experts">
            <div class="choose-overlay"></div>
          </div>
          <div class="choose-content">
            <div class="choose-icon-small"><i class="fas fa-gavel"></i></div>
            <h3>Regulatory Expertise</h3>
            <p>Deep insight into complex tax and corporate laws to safeguard your business interests.</p>
          </div>
        </div>

        <div class="choose-card">
          <div class="choose-img-box">
            <img src="assets/Transparent.jpg" alt="Transparent Practices">
            <div class="choose-overlay"></div>
          </div>
          <div class="choose-content">
            <div class="choose-icon-small"><i class="fas fa-shield-alt"></i></div>
            <h3>Quality & Risk</h3>
            <p>Structured quality and risk management systems that provide peace of mind.</p>
          </div>
        </div>

        <div class="choose-card">
          <div class="choose-img-box">
            <img src="assets/Trusted.jpg" alt="Trusted Partner">
            <div class="choose-overlay"></div>
          </div>
          <div class="choose-content">
            <div class="choose-icon-small"><i class="fas fa-handshake"></i></div>
            <h3>Relationships</h3>
            <p>Dedicated relationship management, ensuring you always have a direct line to expertise.</p>
          </div>
        </div>

        <div class="choose-card">
          <div class="choose-img-box">
            <img src="assets/Dedicated.jpg" alt="Dedicated Support">
            <div class="choose-overlay"></div>
          </div>
          <div class="choose-content">
            <div class="choose-icon-small"><i class="fas fa-shipping-fast"></i></div>
            <h3>Reliable Delivery</h3>
            <p>Consistent delivery and responsiveness that respects your business timelines.</p>
          </div>
        </div>

        <div class="choose-card">
          <div class="choose-img-box">
            <img src="assets/growth.jpg" alt="Growth Oriented">
            <div class="choose-overlay"></div>
          </div>
          <div class="choose-content">
            <div class="choose-icon-small"><i class="fas fa-rocket"></i></div>
            <h3>Long-Term Partnership</h3>
            <p>We serve as long-term advisors, not just service providers, growing alongside you.</p>
          </div>
        </div>

      </div>
    </div>
  </section>

  <style>
    /* --- WHY CHOOSE US REFINED STYLES --- */
    .why-choose-us {
      padding: 120px 0;
      background: #f7f9fc;
      font-family: 'Inter', sans-serif;
    }

    /* Boxed Tag (Consistent with previous sections) */
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
      font-size: 15px;
    }

    .s-name {
      color: #ff8c00;
      padding: 6px 14px;
      font-weight: 700;
      font-size: 15px;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    /* Header Styling */
    .section-intro {
      text-align: center;
      margin-bottom: 80px;
    }

    .section-intro h2 {
      font-size: 40px;
      color: #0b3c74;
      font-weight: 700;
      margin: 15px 0;
    }

    .text-navy {
      color: #3b82f6;
    }

    .dual-accent {
      display: flex;
      justify-content: center;
      gap: 6px;
      margin: 20px 0;
    }

    .bar-navy {
      width: 40px;
      height: 5px;
      background: #0b3c74;
      border-radius: 10px;
    }

    .bar-orange {
      width: 20px;
      height: 5px;
      background: #ff8c00;
      border-radius: 10px;
    }

    .desc-text {
      color: #64748b;
      font-size: 18px;
      max-width: 600px;
      margin: 0 auto;
      line-height: 1.6;
    }

    /* Grid Layout: 3 Columns Desktop, 1 Column Mobile */
    .choose-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 30px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .choose-card {
      background: #fff;
      border-radius: 20px;
      overflow: hidden;
      border: 1px solid #f1f5f9;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
      transition: all 0.4s ease;
    }

    .choose-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(11, 60, 116, 0.1);
      border-color: #3b82f6;
    }

    /* Image with Overlay */
    .choose-img-box {
      position: relative;
      height: 180px;
      overflow: hidden;
    }

    .choose-img-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: 0.6s;
    }

    .choose-card:hover .choose-img-box img {
      transform: scale(1.1);
    }

    .choose-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(to top, rgba(11, 60, 116, 0.6), transparent);
    }

    /* Content Area */
    .choose-content {
      padding: 30px;
      position: relative;
    }

    .choose-icon-small {
      width: 45px;
      height: 45px;
      background: #ff8c00;
      color: #fff;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      position: absolute;
      top: -22px;
      right: 30px;
      font-size: 18px;
      box-shadow: 0 5px 15px rgba(255, 140, 0, 0.3);
    }

    .choose-content h3 {
      font-size: 20px;
      color: #0b3c74;
      margin-bottom: 12px;
      font-weight: 700;
    }

    .choose-content p {
      font-size: 15px;
      color: #64748b;
      line-height: 1.6;
      margin: 0;
    }

    /* RESPONSIVE */
    @media (max-width: 1024px) {
      .choose-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .choose-grid {
        grid-template-columns: 1fr;
      }

      .why-choose-us {
        padding: 80px 20px;
      }

      .section-intro h2 {
        font-size: 32px;
      }
    }
  </style>
  <section class="how-work" id="how-we-work">
    <div class="container">

      <div class="section-header">
        <span class="pre-title">Our Methodology</span>
        <h2>How We Approach</h2>
        <p>We believe that professional services should go beyond compliance. Our approach is built on a foundation of precision and innovation.</p>
        <div class="line"></div>
      </div>

      <div class="work-row">
        <div class="work-img">
          <img src="assets/Consulting.jpg" alt="Consultation">
        </div>
        <div class="work-content">
          <span class="step">01</span>
          <h3>Industry Expertise</h3>
          <p>Deep industry understanding tailored to your specific business sector and regulatory environment.</p>
        </div>
        <div class="arrow-path"></div>
      </div>

      <div class="work-row reverse">
        <div class="work-img">
          <img src="assets/Planning.jpg" alt="Planning">
        </div>
        <div class="work-content">
          <span class="step">02</span>
          <h3>Analytical Rigor</h3>
          <p>We apply rigorous analytical processes to ensure every financial detail is scrutinized for accuracy.</p>
        </div>
        <div class="arrow-path"></div>
      </div>

      <div class="work-row">
        <div class="work-img">
          <img src="assets/Filling.jpg" alt="Execution">
        </div>
        <div class="work-content">
          <span class="step">03</span>
          <h3>Technology Driven</h3>
          <p>Utilizing state-of-the-art accounting technology to enable seamless execution and transparency.</p>
        </div>
        <div class="arrow-path"></div>
      </div>

      <div class="work-row reverse">
        <div class="work-img">
          <img src="assets/Support.jpg" alt="Support">
        </div>
        <div class="work-content">
          <span class="step">04</span>
          <h3>Dynamic Growth</h3>
          <p>A commitment to continuous learning ensures we bring innovative solutions to your evolving needs.</p>
        </div>
        <div class="arrow-path"></div>
      </div>

      <div class="work-row">
        <div class="work-img">
          <img src="assets/Dedicated.jpg" alt="Value Creation">
        </div>
        <div class="work-content">
          <span class="step">05</span>
          <h3>Value Creation</h3>
          <p>Optimizing business value by identifying opportunities for growth beyond basic bookkeeping.</p>
        </div>
      </div>

    </div>
  </section>

  <style>
    .how-work {
      padding: 100px 0;
      background: #f7f9fc;
      overflow: hidden;
    }

    .section-header {
      text-align: center;
      margin-bottom: 80px;
      padding: 0 20px;
    }

    .pre-title {
      color: #ff8c00;
      font-weight: 800;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 2px;
      display: block;
      margin-bottom: 10px;
    }

    .section-header h2 {
      font-size: clamp(30px, 5vw, 40px);
      color: #0b3c78;
      font-weight: 700;
    }

    .section-header p {
      max-width: 650px;
      margin: 15px auto;
      color: #64748b;
      font-size: 17px;
    }

    .section-header .line {
      width: 60px;
      height: 4px;
      background: #ff8c00;
      margin: 20px auto 0;
      border-radius: 10px;
    }

    /* ROW STRUCTURE */
    .work-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 40px;
      position: relative;
      margin-bottom: 120px;
      max-width: 1100px;
      margin-left: auto;
      margin-right: auto;
      padding: 0 20px;
    }

    /* Handle Zig-Zag */
    .work-row.reverse {
      flex-direction: row-reverse;
    }

    .work-img {
      flex: 1;
      max-width: 500px;
    }

    .work-img img {
      width: 100%;
      height: 320px;
      object-fit: cover;
      border-radius: 24px;
      box-shadow: 0 20px 40px rgba(11, 60, 120, 0.1);
    }

    .work-content {
      flex: 1;
      max-width: 480px;
      padding: 20px;
    }

    .work-content .step {
      font-size: 20px;
      font-weight: 800;
      color: #ff8c00;
      background: #fff1e0;
      padding: 5px 15px;
      border-radius: 50px;
    }

    .work-content h3 {
      font-size: clamp(24px, 4vw, 32px);
      margin: 15px 0;
      color: #0b3c78;
      font-weight: 700;
    }

    .work-content p {
      font-size: 17px;
      color: #475569;
      line-height: 1.7;
    }

    /* THE CONNECTING ARROW (Desktop only) */
    .arrow-path {
      position: absolute;
      bottom: -90px;
      left: 50%;
      transform: translateX(-50%);
      width: 2px;
      height: 70px;
      background: linear-gradient(to bottom, #0b3c78, #ff8c00);
    }

    .arrow-path::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: -4px;
      width: 10px;
      height: 10px;
      border-right: 2px solid #ff8c00;
      border-bottom: 2px solid #ff8c00;
      transform: rotate(45deg);
    }

    /* --- MOBILE RESPONSIVE --- */

    @media (max-width: 992px) {

      .work-row,
      .work-row.reverse {
        flex-direction: column;
        /* Stack vertically on tablet/mobile */
        text-align: center;
        margin-bottom: 80px;
        gap: 30px;
      }

      .work-img,
      .work-content {
        max-width: 100%;
        width: 100%;
      }

      .work-img img {
        height: 260px;
      }

      .arrow-path {
        display: none;
        /* Remove arrows on mobile to keep it clean */
      }

      .work-content .step {
        display: inline-block;
        margin-bottom: 10px;
      }
    }

    @media (max-width: 480px) {
      .how-work {
        padding: 60px 0;
      }

      .work-row {
        margin-bottom: 60px;
      }

      .work-img img {
        height: 200px;
      }
    }
  </style>

  <section class="services-hub" id="services">
    <div class="container-premium">

      <div class="section-intro">
        <div class="service-box-tag">
          <span class="s-num">Solutions</span>
          <span class="s-name">Our Expertise</span>
        </div>
        <h2>Comprehensive <span class="text-orange">Professional</span> Services</h2>
        <div class="dual-accent">
          <span class="bar-navy"></span>
          <span class="bar-orange"></span>
        </div>
        <p class="desc-text">We provide end-to-end financial and legal advisory to ensure your business remains compliant and competitive.</p>
      </div>

      <div class="service-flex-container">
        <a href="Incoporation.php" class="service-card-v2">
          <div class="icon-main"><i class="fas fa-calculator"></i></div>
          <h3>Incorporation</h3>
          <p>Quick and compliant registration of companies, LLPs, and firms with complete legal support.</p>
          <span class="read-more">Learn More <i class="fas fa-arrow-right"></i></span>
        </a>

        <a href="accounting.php" class="service-card-v2">
          <div class="icon-main"><i class="fas fa-file-invoice-dollar"></i></div>
          <h3>Accounting</h3>
          <p>Accurate accounting and financial reporting to strengthen business control and profitability.</p>
          <span class="read-more">Learn More <i class="fas fa-arrow-right"></i></span>
        </a>

        <a href="auditing.php" class="service-card-v2">
          <div class="icon-main"><i class="fas fa-balance-scale"></i></div>
          <h3>Auditing</h3>
          <p>Professional audit services to ensure compliance, transparency, and financial integrity.</p>
          <span class="read-more">Learn More <i class="fas fa-arrow-right"></i></span>
        </a>

        <a href="taxation.php" class="service-card-v2">
          <div class="icon-main"><i class="fas fa-building"></i></div>
          <h3>Taxation</h3>
          <p>Expert GST and Income Tax services for maximum savings and full legal compliance.</p>
          <span class="read-more">Learn More <i class="fas fa-arrow-right"></i></span>
        </a>

        <a href="compilance.php" class="service-card-v2">
          <div class="icon-main"><i class="fas fa-rocket"></i></div>
          <h3>Compliances</h3>
          <p>Complete statutory and regulatory compliance management for hassle-free operations.</p>
          <span class="read-more">Learn More <i class="fas fa-arrow-right"></i></span>
        </a>

        <a href="startups.php" class="service-card-v2">
          <div class="icon-main"><i class="fas fa-user-tie"></i></div>
          <h3>Startups</h3>
          <p>End-to-end advisory and compliance support to help startups grow with confidence.</p>
          <span class="read-more">Learn More <i class="fas fa-arrow-right"></i></span>
        </a>

        <a href="consulting.php" class="service-card-v2">
          <div class="icon-main"><i class="fas fa-lightbulb"></i></div>
          <h3>Advisory & Consulting</h3>
          <p>We provide advisory and consulting services to support informed decisions and sustainable growth.</p>
          <span class="read-more">Learn More <i class="fas fa-arrow-right"></i></span>
        </a>
      </div>
    </div>
  </section>

  <style>
    /* --- UPDATED SERVICES HUB STYLES --- */
    .services-hub {
      padding: 120px 0;
      background-color: #f8fafc;
      text-align: center;
    }

    .service-flex-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 25px;
      max-width: 1400px;
      margin: 0 auto;
    }

    /* Service Card V2 */
    .service-card-v2 {
      flex: 0 1 calc(33.333% - 25px);
      min-width: 320px;
      background: #ffffff;
      padding: 50px 30px;
      border-radius: 20px;
      text-decoration: none;
      transition: all 0.4s ease;
      display: flex;
      flex-direction: column;
      align-items: center;
      border: 1px solid #e2e8f0;
      position: relative;
      overflow: hidden;
    }

    .service-card-v2::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: #ff8c00;
      transform: scaleX(0);
      transition: transform 0.4s ease;
    }

    .service-card-v2:hover {
      transform: translateY(-12px);
      box-shadow: 0 20px 40px rgba(11, 60, 116, 0.08);
      border-color: #ff8c00;
    }

    .service-card-v2:hover::before {
      transform: scaleX(1);
    }

    /* Icon Design */
    .icon-main {
      width: 70px;
      height: 70px;
      background: #f1f5f9;
      color: #0b3c74;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      margin-bottom: 25px;
      transition: 0.4s;
    }

    .service-card-v2:hover .icon-main {
      background: #0b3c74;
      color: #fff;
      transform: rotateY(360deg);
    }

    .service-card-v2 h3 {
      margin-bottom: 15px;
      font-size: 22px;
      font-weight: 700;
      color: #0b3c74;
    }

    .service-card-v2 p {
      font-size: 15px;
      color: #64748b;
      line-height: 1.6;
      margin-bottom: 25px;
    }

    /* Learn More Link */
    .read-more {
      font-size: 14px;
      font-weight: 700;
      color: #ff8c00;
      text-transform: uppercase;
      letter-spacing: 1px;
      display: flex;
      align-items: center;
      gap: 8px;
      opacity: 0.7;
      transition: 0.3s;
    }

    .service-card-v2:hover .read-more {
      opacity: 1;
      gap: 12px;
    }

    /* Mobile Adjustments */
    @media (max-width: 1024px) {
      .service-card-v2 {
        flex: 0 1 calc(50% - 25px);
      }
    }

    @media (max-width: 768px) {
      .service-card-v2 {
        flex: 0 1 100%;
      }

      .services-hub {
        padding: 80px 20px;
      }
    }
  </style>


  <section class="core-practice-section" id="practice-areas">
    <div class="container-premium">

      <div class="section-intro">
        <div class="service-box-tag">
          <span class="s-num">Expertise</span>
          <span class="s-name">Core Practice</span>
        </div>
        <h2>Professional <span class="text-orange">Excellence</span> in Every Domain</h2>
        <div class="dual-accent">
          <span class="bar-navy"></span>
          <span class="bar-orange"></span>
        </div>
        <p class="desc-text">
          Our multidisciplinary approach ensures comprehensive coverage of all your financial, legal, and operational requirements.
        </p>
      </div>

      <div class="practice-flex-layout">

        <div class="practice-grid-wrapper">
          <div class="practice-card-v3">
            <div class="p-number">01</div>
            <div class="p-info">
              <h4>Cost Audit & Maintenance</h4>
              <p>Specialized cost auditing and maintenance of cost records as per statutory norms.</p>
            </div>
          </div>

          <div class="practice-card-v3">
            <div class="p-number">02</div>
            <div class="p-info">
              <h4>Banking & Internal Audit</h4>
              <p>Concurrent audits for banks and robust internal accounting controls for firms.</p>
            </div>
          </div>

          <div class="practice-card-v3">
            <div class="p-number">03</div>
            <div class="p-info">
              <h4>GST & Indirect Tax</h4>
              <p>End-to-end GST audit, periodic compliance filing, and strategic tax advisory.</p>
            </div>
          </div>

          <div class="practice-card-v3">
            <div class="p-number">04</div>
            <div class="p-info">
              <h4>Direct Tax & Assurance</h4>
              <p>Income Tax returns, tax audits, and high-level assurance support services.</p>
            </div>
          </div>

          <div class="practice-card-v3">
            <div class="p-number">05</div>
            <div class="p-info">
              <h4>Financial Reporting</h4>
              <p>Precision accounting, detailed financial reporting, and MIS for management.</p>
            </div>
          </div>

          <div class="practice-card-v3">
            <div class="p-number">06</div>
            <div class="p-info">
              <h4>Corporate Filings</h4>
              <p>Business incorporation support and mandatory ROC filings for all entity types.</p>
            </div>
          </div>

          <div class="practice-card-v3">
            <div class="p-number">07</div>
            <div class="p-info">
              <h4>Regulatory Compliance</h4>
              <p>Staying ahead of the curve with all state and central regulatory requirements.</p>
            </div>
          </div>

          <div class="practice-card-v3">
            <div class="p-number">08</div>
            <div class="p-info">
              <h4>Startup & Consulting</h4>
              <p>Funding assistance, pitch deck support, and strategic business consulting.</p>
            </div>
          </div>
        </div>

        <div class="practice-visual-side">
          <div class="image-stack-container">
            <div class="main-image-frame">
              <img src="assets/Practices.jpg" alt="Our Practice Areas" class="main-practice-img">
              <div class="experience-box">
                <span class="exp-num">5+</span>
                <span class="exp-txt">Years of<br>Expertise</span>
              </div>
            </div>
            <div class="frame-decoration"></div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <style>
    /* --- CORE PRACTICE STYLES --- */
    .core-practice-section {
      padding: 100px 0;
      background: #f7f9fc;
      font-family: 'Inter', sans-serif;
      overflow: hidden;
    }

    .practice-flex-layout {
      display: flex;
      align-items: flex-start;
      gap: 60px;
      margin-top: 50px;
    }

    /* LEFT SIDE: GRID */
    .practice-grid-wrapper {
      flex: 1;
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 25px;
    }

    .practice-card-v3 {
      display: flex;
      gap: 20px;
      padding: 25px;
      background: #f8fafc;
      border-radius: 15px;
      border: 1px solid #e2e8f0;
      transition: 0.3s ease;
    }

    .practice-card-v3:hover {
      background: #fff;
      border-color: #3b82f6;
      transform: translateX(10px);
      box-shadow: 0 10px 30px rgba(11, 60, 116, 0.05);
    }

    .p-number {
      font-size: 20px;
      font-weight: 800;
      color: #ff8c00;
      opacity: 0.4;
      transition: 0.3s;
    }

    .practice-card-v3:hover .p-number {
      opacity: 1;
      transform: scale(1.2);
    }

    .p-info h4 {
      color: #0b3c74;
      font-size: 17px;
      margin-bottom: 8px;
      font-weight: 700;
    }

    .p-info p {
      color: #64748b;
      font-size: 14px;
      line-height: 1.5;
      margin: 0;
    }

    /* RIGHT SIDE: VISUAL */
    .practice-visual-side {
      flex: 0 0 450px;
      position: sticky;
      top: 100px;
    }

    .image-stack-container {
      position: relative;
      padding: 20px;
    }

    .main-image-frame {
      position: relative;
      z-index: 2;
      border-radius: 30px;
      overflow: hidden;
      box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
    }

    .main-practice-img {
      width: 100%;
      height: 550px;
      object-fit: cover;
      display: block;
    }

    .frame-decoration {
      position: absolute;
      top: 0;
      right: 0;
      width: 80%;
      height: 80%;
      border: 10px solid #f1f5f9;
      border-radius: 30px;
      z-index: 1;
    }

    .experience-box {
      position: absolute;
      bottom: 30px;
      left: 30px;
      background: #ff8c00;
      padding: 20px 30px;
      border-radius: 20px;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 15px;
      box-shadow: 0 15px 30px rgba(255, 140, 0, 0.3);
    }

    .exp-num {
      font-size: 36px;
      font-weight: 800;
    }

    .exp-txt {
      font-size: 13px;
      font-weight: 600;
      line-height: 1.2;
      text-transform: uppercase;
    }

    /* --- RESPONSIVE --- */
    @media (max-width: 1200px) {
      .practice-visual-side {
        flex: 0 0 380px;
      }
    }

    @media (max-width: 992px) {
      .practice-flex-layout {
        flex-direction: column-reverse;
      }

      .practice-visual-side {
        flex: 0 0 auto;
        width: 100%;
        position: static;
      }

      .main-practice-img {
        height: 400px;
      }

      .practice-grid-wrapper {
        grid-template-columns: 1fr;
        width: 100%;
      }
    }
  </style>

  <section class="insights-section" id="insights">
    <div class="container-premium">

      <div class="section-intro">
        <div class="service-box-tag">
          <span class="s-num">Knowledge</span>
          <span class="s-name">Latest Updates</span>
        </div>
        <h2>Insights & <span class="text-orange">Expert</span> Opinions</h2>
        <div class="dual-accent">
          <span class="bar-navy"></span>
          <span class="bar-orange"></span>
        </div>
        <p class="desc-text">
          Stay updated with the latest tax regulations, financial trends, and compliance changes curated by our professionals.
        </p>
      </div>

      <div class="insight-grid-v4">

        <div class="insight-card-v4">
          <div class="insight-img-wrap">
            <img src="assets/blog1.jpg" alt="Income Tax Update">
            <div class="insight-date"><span>22</span> Dec</div>
            <span class="category-tag tag-orange">Compliance</span>
          </div>
          <div class="insight-body">
            <h3>GST Compliance Checklist for Growing Businesses</h3>
            <p>Key GST obligations every business must follow to avoid penalties, check and ensure smooth operations.</p>
            <div class="insight-footer">
              <a href="blog-details/blog-details1.php" class="read-link">Read Full Article <i class="fas fa-chevron-right"></i></a>
            </div>
          </div>
        </div>

        <div class="insight-card-v4">
          <div class="insight-img-wrap">
            <img src="assets/blog4.jpg" alt="GST Compliance">
            <div class="insight-date"><span>12</span> Dec</div>
            <span class="category-tag tag-navy">Taxation</span>
          </div>
          <div class="insight-body">
            <h3>Optimizing Tax Liability Under the New Income Tax Regime</h3>
            <p>A practical overview of deductions, optimize, exemptions, and planning strategies to reduce tax outflow</p>
            <div class="insight-footer">
              <a href="blog-details/blog-details4.php" class="read-link">Read Full Article <i class="fas fa-chevron-right"></i></a>
            </div>
          </div>
        </div>

        <div class="insight-card-v4">
          <div class="insight-img-wrap">
            <img src="assets/blog8.jpg" alt="Business Accounting">
            <div class="insight-date"><span>14</span> Jan</div>
            <span class="category-tag tag-green">Audit</span>
          </div>
          <div class="insight-body">
            <h3>Internal Audit as a Tool for Risk Management</h3>
            <p>Exploring the importance of internal audit in identifying risks and strengthening internal controls</p>
            <div class="insight-footer">
              <a href="blog-details/blog-details8.php" class="read-link">Read Full Article <i class="fas fa-chevron-right"></i></a>
            </div>
          </div>
        </div>

      </div>

      <div class="insight-cta-wrap">
        <a href="blogs.php" class="btn-primary-outline">
          Explore Knowledge Base <i class="fas fa-book-open"></i>
        </a>
      </div>

    </div>
  </section>

  <style>
    /* --- INSIGHTS SECTION STYLES --- */
    .insights-section {
      padding: 100px 0;
      background: #f7f9fc;
    }

    .insight-grid-v4 {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 35px;
      margin-bottom: 60px;
    }

    .insight-card-v4 {
      background: #fff;
      border-radius: 20px;
      overflow: hidden;
      border: 1px solid #f1f5f9;
      transition: 0.4s ease;
      display: flex;
      flex-direction: column;
    }

    .insight-card-v4:hover {
      transform: translateY(-12px);
      box-shadow: 0 25px 50px rgba(11, 60, 116, 0.08);
      border-color: #3b82f6;
    }

    /* Image & Date UI */
    .insight-img-wrap {
      position: relative;
      height: 230px;
      overflow: hidden;
    }

    .insight-img-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: 0.8s;
    }

    .insight-card-v4:hover .insight-img-wrap img {
      transform: scale(1.1);
    }

    .insight-date {
      position: absolute;
      top: 20px;
      left: 20px;
      background: #fff;
      padding: 8px 15px;
      border-radius: 12px;
      text-align: center;
      font-size: 12px;
      font-weight: 700;
      color: #64748b;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .insight-date span {
      display: block;
      font-size: 18px;
      color: #0b3c74;
      line-height: 1;
    }

    /* Tags */
    .category-tag {
      position: absolute;
      bottom: 20px;
      right: 20px;
      padding: 6px 15px;
      border-radius: 50px;
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      color: #fff;
    }

    .tag-orange {
      background: #ff8c00;
    }

    .tag-navy {
      background: #0b3c74;
    }

    .tag-green {
      background: #10b981;
    }

    /* Body */
    .insight-body {
      padding: 30px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .insight-body h3 {
      font-size: 20px;
      color: #0b3c74;
      margin-bottom: 15px;
      font-weight: 700;
      line-height: 1.4;
      transition: 0.3s;
    }

    .insight-card-v4:hover .insight-body h3 {
      color: #ff8c00;
    }

    .insight-body p {
      color: #64748b;
      font-size: 15px;
      line-height: 1.6;
      margin-bottom: 20px;
    }

    .insight-footer {
      margin-top: auto;
      border-top: 1px solid #f1f5f9;
      padding-top: 20px;
    }

    .read-link {
      text-decoration: none;
      color: #0b3c74;
      font-weight: 700;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: 0.3s;
    }

    .read-link:hover {
      color: #ff8c00;
      gap: 12px;
    }

    /* CTA Button */
    .insight-cta-wrap {
      text-align: center;
    }

    .btn-primary-outline {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      padding: 18px 40px;
      border: 2px solid #0b3c74;
      color: #0b3c74;
      text-decoration: none;
      border-radius: 50px;
      font-weight: 700;
      transition: 0.3s;
    }

    .btn-primary-outline:hover {
      background: #0b3c74;
      color: #fff;
    }

    /* RESPONSIVE */
    @media (max-width: 1024px) {
      .insight-grid-v4 {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .insight-grid-v4 {
        grid-template-columns: 1fr;
      }

      .insight-card-v4 {
        max-width: 450px;
        margin: 0 auto;
      }
    }
  </style>


  <section class="testimonials-section" id="testimonials">
    <div class="container-premium">

      <div class="section-header">
        <span class="service-pill">Client Trust</span>
      </div>
      <div class="section-intro" style="text-align: center; margin-bottom: 60px;">
        <h2>What Our <span class="text-orange">Clients Say</span></h2>
      </div>

      <div class="testimonial-grid">
        <div class="testi-box">
          <div class="google-icon"><i class="fab fa-google"></i></div>
          <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
          <p>"Their services is very good and fast, The behavior of all the employees here is also very good, I think you can avail its services without any hesitation."</p>
          <div class="client-info">
            <strong>Kap Swift</strong>
            <span>2 reviews</span>
          </div>
        </div>

        <div class="testi-box">
          <div class="google-icon"><i class="fab fa-google"></i></div>
          <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
          <p>"Good Firm. maine apna Company Registration and GST registration karwaya. fully satisfied"</p>
          <div class="client-info">
            <strong>S M INFRA CONSTRUCTION</strong>
            <span>1 review</span>
          </div>
        </div>

        <div class="testi-box">
          <div class="google-icon"><i class="fab fa-google"></i></div>
          <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
          <p>"You have been extremely helpful, professional and efficient during the whole transaction, son would again like to express my gratitude for this. We really do appreciate it."</p>
          <div class="client-info">
            <strong>HEAD TURNERS</strong>
            <span>4 reviews · 3 photos</span>
          </div>
        </div>

        <div class="testi-box">
          <div class="google-icon"><i class="fab fa-google"></i></div>
          <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
          <p>"Very supporting"</p>
          <div class="client-info">
            <strong>vinit sarkar</strong>
            <span>185 reviews · 199 photos</span>
          </div>
        </div>

        <div class="testi-box">
          <div class="google-icon"><i class="fab fa-google"></i></div>
          <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
          <p>"I had an excellent experience with Karunesh Kumar and Associates."</p>
          <div class="client-info">
            <strong>Dilip Kumar</strong>
            <span>3 reviews</span>
          </div>
        </div>

        <div class="testi-box">
          <div class="google-icon"><i class="fab fa-google"></i></div>
          <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
          <p>"Very Fast response and reliable service is provided here"</p>
          <div class="client-info">
            <strong>Arun Kumar</strong>
            <span>244 reviews · 25 photos</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <style>
    .section-header {
      text-align: center;
      position: relative;
      z-index: 10;
      margin-bottom: 30px;
    }

    .service-pill {
      display: inline-flex;
      align-items: center;
      justify-content: center;

      /* Background & Border */
      background: rgba(11, 60, 116, 0.05);
      /* Light Navy tint */
      border: 1px solid rgba(11, 60, 116, 0.15);
      backdrop-filter: blur(5px);
      /* Modern glass effect */

      /* Typography */
      color: #0b3c74;
      font-size: 12px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 2px;

      /* Shape & Sizing */
      padding: 8px 20px;
      border-radius: 100px;

      /* Movement - Pushing it UP */
      transform: translateY(-50%);
      /* Pulls it halfway out of the section */
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);

      transition: all 0.3s ease;
    }

    /* Hover Effect */
    .service-pill:hover {
      background: #0b3c74;
      color: #ffffff;
      border-color: #0b3c74;
      transform: translateY(-55%) scale(1.05);
      box-shadow: 0 8px 20px rgba(11, 60, 116, 0.2);
    }

    .testimonials-section {
      padding: 80px 0;
      background: #f8fafc;
    }

    .testimonial-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 30px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .testi-box {
      background: #fff;
      padding: 40px 30px;
      border-radius: 20px;
      border: 1px solid #e2e8f0;
      position: relative;
      transition: all 0.4s ease;
      cursor: default;
    }

    /* HOVER EFFECT */
    .testi-box:hover {
      transform: translateY(-10px);
      border-color: #ff8c00;
      box-shadow: 0 20px 40px rgba(11, 60, 116, 0.1);
    }

    .google-icon {
      position: absolute;
      top: 25px;
      right: 25px;
      font-size: 20px;
      color: #4285F4;
      /* Google Blue */
    }

    .stars {
      color: #fbbf24;
      /* Star Gold */
      font-size: 14px;
      margin-bottom: 15px;
    }

    .testi-box p {
      font-size: 15px;
      line-height: 1.7;
      color: #475569;
      font-style: italic;
      margin-bottom: 25px;
    }

    .client-info strong {
      display: block;
      color: #0b3c74;
      font-size: 16px;
    }

    .client-info span {
      font-size: 13px;
      color: #94a3b8;
    }

    /* Responsive Grid */
    @media (max-width: 991px) {
      .testimonial-grid {
        grid-template-columns: 1fr 1fr;
      }
    }

    @media (max-width: 768px) {
      .testimonial-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>


  <section class="team-section" id="team">
    <div class="container-premium">

      <div class="section-intro">
        <div class="service-box-tag">
          <span class="s-num">Experts</span>
          <span class="s-name">Our Leadership</span>
        </div>
        <h2>Meet Our <span class="text-orange">Professional</span> Team</h2>
        <div class="dual-accent">
          <span class="bar-navy"></span>
          <span class="bar-orange"></span>
        </div>
        <p class="desc-text">
          A multidisciplinary team of experts dedicated to delivering excellence in audit, taxation, and advisory.
        </p>
      </div>

      <div class="team-grid-v5">

        <div class="team-card-v5">
          <div class="team-img-box">
            <img src="assets/person1.jpg" alt="Neha Verma">
            <div class="team-info-overlay">
              <div class="social-links-v5">
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
              </div>
            </div>
          </div>
          <div class="team-meta">
            <h4>Neha Verma</h4>
            <p>Senior Accountant</p>
          </div>
        </div>

        <div class="team-card-v5">
          <div class="team-img-box">
            <img src="assets/person2.jpg" alt="Amit Gupta">
            <div class="team-info-overlay">
              <div class="social-links-v5">
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
              </div>
            </div>
          </div>
          <div class="team-meta">
            <h4>Amit Gupta</h4>
            <p>Tax Consultant</p>
          </div>
        </div>

        <div class="team-card-v5">
          <div class="team-img-box">
            <img src="assets/person3.jpg" alt="Pooja Sharma">
            <div class="team-info-overlay">
              <div class="social-links-v5">
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
              </div>
            </div>
          </div>
          <div class="team-meta">
            <h4>Pooja Sharma</h4>
            <p>Audit Manager</p>
          </div>
        </div>

      </div>
    </div>
  </section>

  <style>
    /* --- TEAM SECTION STYLES --- */
    .team-section {
      padding: 100px 0;
      background: #f7f9fc;
    }

    .team-grid-v5 {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 40px;
      margin-top: 50px;
    }

    .team-card-v5 {
      background: #fff;
      border-radius: 25px;
      overflow: hidden;
      text-align: center;
      transition: 0.4s ease;
      border: 1px solid #f1f5f9;
    }

    .team-img-box {
      position: relative;
      height: 380px;
      overflow: hidden;
      background: #e2e8f0;
    }

    .team-img-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: 0.5s ease;
    }

    /* Hover Overlay */
    .team-info-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(11, 60, 116, 0.85);
      /* Navy Transparent */
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: 0.4s ease;
    }

    .team-card-v5:hover .team-info-overlay {
      opacity: 1;
    }

    .team-card-v5:hover .team-img-box img {
      transform: scale(1.1);
    }

    /* Social Icons in Overlay */
    .social-links-v5 {
      display: flex;
      gap: 15px;
      transform: translateY(20px);
      transition: 0.4s ease;
    }

    .team-card-v5:hover .social-links-v5 {
      transform: translateY(0);
    }

    .social-links-v5 a {
      width: 45px;
      height: 45px;
      background: #ff8c00;
      color: #fff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      font-size: 18px;
      transition: 0.3s;
    }

    .social-links-v5 a:hover {
      background: #fff;
      color: #0b3c74;
      transform: rotate(360deg);
    }

    /* Name and Title */
    .team-meta {
      padding: 25px;
    }

    .team-meta h4 {
      font-size: 22px;
      color: #0b3c74;
      margin-bottom: 5px;
      font-weight: 800;
    }

    .team-meta p {
      color: #ff8c00;
      font-size: 14px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    /* RESPONSIVE */
    @media (max-width: 992px) {
      .team-grid-v5 {
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
      }
    }

    @media (max-width: 600px) {
      .team-grid-v5 {
        grid-template-columns: 1fr;
      }

      .team-img-box {
        height: 320px;
      }
    }
  </style>

  <section class="career-section" id="career">
    <div class="container-premium">
      <div class="career-grid-v6">

        <div class="career-visual-side">
          <div class="career-img-wrapper">
            <img src="assets/contact.jpg" alt="Career at Our Firm">
            <div class="career-experience-overlay">
              <i class="fas fa-chart-line"></i>
              <span>Grow with the Experts</span>
            </div>
          </div>
          <div class="career-benefits">
            <div class="benefit-item">
              <i class="fas fa-check-circle"></i>
              <p>Exposure to Pan-India Audit Assignments</p>
            </div>
            <div class="benefit-item">
              <i class="fas fa-check-circle"></i>
              <p>Continuous Learning & Professional Growth</p>
            </div>
            <div class="benefit-item">
              <i class="fas fa-check-circle"></i>
              <p>Collaborative Work Environment</p>
            </div>
          </div>
        </div>

        <div class="career-form-box">
          <div class="service-box-tag">
            <span class="s-num">Hiring</span>
            <span class="s-name">Join Our Team</span>
          </div>
          <h2>Build Your <span class="text-orange">Future</span> With Us</h2>
          <div class="dual-accent">
            <span class="bar-navy"></span>
            <span class="bar-orange"></span>
          </div>

          <form method="POST" enctype="multipart/form-data" class="modern-form">
            <div class="form-row">
              <div class="input-group">
                <input type="text" name="name" placeholder="Full Name" required>
              </div>
              <div class="input-group">
                <input type="email" name="email" placeholder="Email Address" required>
              </div>
            </div>

            <div class="form-row">
              <div class="input-group">
                <input type="text" name="phone" placeholder="Phone Number" required>
              </div>
              <div class="input-group">
                <input type="text" name="position" placeholder="Position Applied For" required>
              </div>
            </div>

            <div class="input-group">
              <input type="text" name="experience" placeholder="Years of Experience (e.g. 2+ Years)" required>
            </div>

            <div class="file-upload-wrapper">
              <label for="resume">Upload Resume (PDF, DOC, DOCX)</label>
              <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx" required>
            </div>

            <div class="input-group">
              <textarea name="cover" placeholder="Brief Cover Letter (Optional)"></textarea>
            </div>

            <button type="submit" name="apply" class="btn-submit-career">
              Submit Application <i class="fas fa-paper-plane"></i>
            </button>

            <?php if (isset($success)): ?>
              <div class="msg-box success-msg">✅ Application submitted successfully!</div>
            <?php endif; ?>

            <?php if (isset($fileError)): ?>
              <div class="msg-box error-msg">❌ Only PDF, DOC, DOCX files allowed</div>
            <?php endif; ?>
          </form>
        </div>

      </div>
    </div>
  </section>

  <style>
    /* --- CAREER SECTION STYLING --- */
    .career-section {
      padding: 120px 0;
      background: #f7f9fc;
      overflow: hidden;
    }

    .career-grid-v6 {
      display: grid;
      grid-template-columns: 1fr 1.2fr;
      gap: 80px;
      align-items: center;
    }

    /* Visual Side */
    .career-img-wrapper {
      position: relative;
      border-radius: 30px;
      overflow: hidden;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }

    .career-img-wrapper img {
      width: 100%;
      height: 450px;
      object-fit: cover;
    }

    .career-experience-overlay {
      position: absolute;
      bottom: 30px;
      right: 30px;
      background: #0b3c74;
      color: #fff;
      padding: 20px;
      border-radius: 15px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .career-benefits {
      margin-top: 30px;
    }

    .benefit-item {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 12px;
    }

    .benefit-item i {
      color: #ff8c00;
      font-size: 18px;
    }

    .benefit-item p {
      color: #475569;
      font-weight: 500;
      margin: 0;
    }

    /* Form Side */
    .career-form-box {
      background: #fff;
      padding: 50px;
      border-radius: 30px;
      box-shadow: 0 15px 35px rgba(11, 60, 116, 0.05);
      border: 1px solid #eef2f6;
    }

    .modern-form .form-row {
      display: flex;
      gap: 20px;
      margin-bottom: 20px;
    }

    .input-group {
      flex: 1;
      margin-bottom: 20px;
    }

    .modern-form input,
    .modern-form textarea {
      width: 100%;
      padding: 15px 20px;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      background: #fdfdfd;
      font-size: 15px;
      color: #0b3c74;
      transition: 0.3s;
      outline: none;
    }

    .modern-form input:focus,
    .modern-form textarea:focus {
      border-color: #ff8c00;
      background: #fff;
      box-shadow: 0 5px 15px rgba(255, 140, 0, 0.05);
    }

    /* File Upload UI */
    .file-upload-wrapper {
      margin-bottom: 20px;
    }

    .file-upload-wrapper label {
      display: block;
      font-size: 14px;
      font-weight: 600;
      color: #64748b;
      margin-bottom: 8px;
    }

    .file-upload-wrapper input {
      padding: 10px;
      border: 2px dashed #cbd5e1;
      background: #f8fafc;
      cursor: pointer;
    }

    .btn-submit-career {
      width: 100%;
      padding: 18px;
      background: #0b3c74;
      color: #fff;
      border: none;
      border-radius: 10px;
      font-weight: 700;
      font-size: 16px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      transition: 0.3s;
    }

    .btn-submit-career:hover {
      background: #ff8c00;
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(255, 140, 0, 0.2);
    }

    /* Message Boxes */
    .msg-box {
      padding: 15px;
      border-radius: 10px;
      margin-top: 20px;
      font-weight: 600;
      font-size: 14px;
      text-align: center;
    }

    .success-msg {
      background: #dcfce7;
      color: #166534;
    }

    .error-msg {
      background: #fee2e2;
      color: #991b1b;
    }

    /* MOBILE RESPONSIVE */
    @media (max-width: 992px) {
      .career-grid-v6 {
        grid-template-columns: 1fr;
        gap: 50px;
      }

      .career-form-box {
        padding: 30px;
      }

      .modern-form .form-row {
        flex-direction: column;
        gap: 20px;
      }
    }
  </style>

  <section class="contact-section" id="contact">
    <div class="container-premium">
      <div class="contact-grid-v7">

        <div class="contact-form-card">
          <div class="service-box-tag">
            <span class="s-num">Support</span>
            <span class="s-name">Get In Touch</span>
          </div>
          <h2>Send Us a <span class="text-orange">Message</span></h2>
          <div class="dual-accent">
            <span class="bar-navy"></span>
            <span class="bar-orange"></span>
          </div>
          <p class="contact-subtitle">
            Have questions about taxation or compliance? Our experts are ready to assist you.
          </p>

          <form method="POST" class="modern-contact-form">
            <div class="form-row">
              <div class="input-field">
                <i class="fas fa-user"></i>
                <input type="text" name="name" placeholder="Full Name" required>
              </div>
              <div class="input-field">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email Address" required>
              </div>
            </div>

            <div class="input-field">
              <i class="fas fa-tag"></i>
              <input type="text" name="subject" placeholder="Subject" required>
            </div>

            <div class="input-field">
              <i class="fas fa-pen"></i>
              <textarea name="message" placeholder="How can we help you?" required></textarea>
            </div>

            <button type="submit" name="submit" class="btn-send-message">
              Send Message <i class="fas fa-paper-plane"></i>
            </button>

            <?php if (isset($success)): ?>
              <div class="feedback-msg success-ui">✅ Message sent successfully! We'll contact you soon.</div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
              <div class="feedback-msg error-ui">❌ Something went wrong. Please try again.</div>
            <?php endif; ?>
          </form>
        </div>

        <div class="contact-info-visual">
          <div class="info-content">
            <h3>How We <span class="text-navy">Help You</span></h3>
            <ul class="help-list">
              <li>
                <div class="help-icon"><i class="fas fa-user-tie"></i></div>
                <div>
                  <h5>Expert Guidance</h5>
                  <p>Direct consultation with experienced Cost Accountants.</p>
                </div>
              </li>
              <li>
                <div class="help-icon"><i class="fas fa-shield-alt"></i></div>
                <div>
                  <h5>Full Compliance</h5>
                  <p>GST, Income Tax, and statutory filings managed accurately.</p>
                </div>
              </li>
              <li>
                <div class="help-icon"><i class="fas fa-headset"></i></div>
                <div>
                  <h5>Dedicated Support</h5>
                  <p>Fast response times and dedicated relationship managers.</p>
                </div>
              </li>
            </ul>
          </div>

          <div class="contact-img-frame">
            <img src="assets/accounting.jpg" alt="Contact Us">
            <div class="floating-contact-badge">
              <i class="fas fa-phone-alt"></i>
              <p>Immediate Support?<br><strong>+91-90970 47484</strong></p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <style>
    /* --- CONTACT SECTION STYLES --- */
    .contact-section {
      padding: 120px 0;
      background: #f7f9fc;
    }

    .contact-grid-v7 {
      display: grid;
      grid-template-columns: 1.2fr 1fr;
      gap: 70px;
      align-items: center;
    }

    /* Form Styling */
    .contact-form-card {
      background: #f8fafc;
      padding: 50px;
      border-radius: 30px;
      border: 1px solid #eef2f6;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
    }

    .contact-subtitle {
      color: #64748b;
      margin-bottom: 35px;
      font-size: 16px;
    }

    .modern-contact-form .form-row {
      display: flex;
      gap: 20px;
      margin-bottom: 20px;
    }

    .input-field {
      position: relative;
      margin-bottom: 20px;
      flex: 1;
    }

    .input-field i {
      position: absolute;
      left: 18px;
      top: 18px;
      color: #ff8c00;
      font-size: 16px;
    }

    .input-field input,
    .input-field textarea {
      width: 100%;
      padding: 15px 15px 15px 50px;
      border: 1px solid #cbd5e1;
      border-radius: 12px;
      background: #fff;
      font-size: 15px;
      outline: none;
      transition: 0.3s;
    }

    .input-field textarea {
      height: 120px;
      resize: none;
    }

    .input-field input:focus,
    .input-field textarea:focus {
      border-color: #0b3c74;
      box-shadow: 0 0 0 4px rgba(11, 60, 116, 0.05);
    }

    .btn-send-message {
      width: 100%;
      padding: 18px;
      background: #0b3c74;
      color: #fff;
      border: none;
      border-radius: 12px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      transition: 0.3s;
    }

    .btn-send-message:hover {
      background: #ff8c00;
      transform: translateY(-3px);
    }

    /* Info Side Styling */
    .help-list {
      list-style: none;
      padding: 0;
      margin: 30px 0;
    }

    .help-list li {
      display: flex;
      gap: 20px;
      margin-bottom: 25px;
      align-items: flex-start;
    }

    .help-icon {
      width: 45px;
      height: 45px;
      background: #fff4e6;
      color: #ff8c00;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      font-size: 18px;
    }

    .help-list h5 {
      color: #0b3c74;
      font-size: 17px;
      margin-bottom: 4px;
      font-weight: 700;
    }

    .help-list p {
      color: #64748b;
      font-size: 14px;
      margin: 0;
    }

    .contact-img-frame {
      position: relative;
      border-radius: 20px;
      overflow: hidden;
      margin-top: 40px;
    }

    .contact-img-frame img {
      width: 100%;
      height: 300px;
      object-fit: cover;
    }

    .floating-contact-badge {
      position: absolute;
      bottom: 20px;
      right: 20px;
      background: #fff;
      padding: 15px 25px;
      border-radius: 15px;
      display: flex;
      align-items: center;
      gap: 15px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .floating-contact-badge i {
      font-size: 24px;
      color: #ff8c00;
    }

    .floating-contact-badge p {
      font-size: 12px;
      color: #64748b;
      margin: 0;
    }

    .floating-contact-badge strong {
      display: block;
      font-size: 16px;
      color: #0b3c74;
    }

    /* PHP Feedback UI */
    .feedback-msg {
      padding: 15px;
      border-radius: 10px;
      margin-top: 20px;
      text-align: center;
      font-weight: 600;
    }

    .success-ui {
      background: #dcfce7;
      color: #15803d;
      border: 1px solid #bbf7d0;
    }

    .error-ui {
      background: #fee2e2;
      color: #b91c1c;
      border: 1px solid #fecaca;
    }

    /* RESPONSIVE */
    @media (max-width: 992px) {
      .contact-grid-v7 {
        grid-template-columns: 1fr;
        gap: 50px;
      }

      .contact-form-card {
        padding: 30px;
      }

      .modern-contact-form .form-row {
        flex-direction: column;
      }
    }
  </style>

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

    /* --- Desktop Default (Your existing code adjusted) --- */
    #kk-chatbot {
      position: fixed;
      bottom: 40px;
      right: 30px;
      z-index: 9999;
      font-family: 'Plus Jakarta Sans', sans-serif;
    }

    /* --- MOBILE VERSION FIXES --- */
    @media (max-width: 600px) {
      #kk-chatbot {
        /* Moves the floating button up to avoid browser toolbars/home bars */
        bottom: 90px;
        right: 20px;
      }

      #kk-window {
        /* Makes the window wider for better mobile reading */
        width: calc(100vw - 40px);
        max-height: 70vh;
        /* Keeps it from covering the whole screen */
        bottom: 75px;
        right: 0;
      }

      .msg {
        max-width: 90%;
        /* Let bubbles fill more width on narrow screens */
        font-size: 15px;
        /* Slightly larger text for mobile readability */
      }
    }

    /* --- IPHONE & MODERN ANDROID "HOME BAR" FIX --- */
    @supports (padding-bottom: env(safe-area-inset-bottom)) {
      #kk-chatbot {
        /* Adds extra space only on devices with the "notch" or home swipe bar */
        margin-bottom: env(safe-area-inset-bottom);
      }
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
          <li><a href="#home">Home</a></li>
          <li><a href="#about">Our Firm</a></li>
          <li><a href="#services">Services</a></li>
          <li><a href="blogs.php">Latest Insights</a></li>
          <li><a href="#testimonials">Testimonials</a></li>
          <li><a href="#career">Join the Team</a></li>
          <li><a href="#contact">Contact Us</a></li>
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
          <a href="privacy-policy.php">Privacy Policy</a>
          <span class="sep">|</span>
          <a href="terms-of-service.php">Terms of Service</a>
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