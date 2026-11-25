<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'office') {
    header("Location: ../Login.php");
    exit();
}

// 1. Get the email from session
$user_email = $_SESSION['user']['identifier'];

// 2. JOIN using u.id and p.user_id instead of email
$sql = "SELECT u.name, p.profile_pic, p.first_name 
        FROM users u 
        LEFT JOIN employee_profiles p ON u.id = p.user_id 
        WHERE u.identifier = '$user_email'";

$profileRes = $conn->query($sql);

if ($profileRes) {
    $user_data = $profileRes->fetch_assoc();
}

// 3. Fallback logic
$display_name = !empty($user_data['name']) ? $user_data['name'] : "Employee";
$first_name = !empty($user_data['first_name']) ? $user_data['first_name'] : $display_name;
$profile_img = !empty($user_data['profile_pic']) ? $user_data['profile_pic'] : 'default-avatar.png';
$email = $_SESSION['user']['identifier'];
$status_msg = "";
$status_is_error = false;

if (isset($_POST['submit_work'])) {
    $doc_id = mysqli_real_escape_string($conn, $_POST['doc_id']);
    $target_dir = "../uploads/vault/";

    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_name = time() . "_" . basename($_FILES["result_file"]["name"]);
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["result_file"]["tmp_name"], $target_file)) {
        $sql = "UPDATE client_documents SET 
                status='Pending Review', 
                doc_category='vault', 
                result_file='$file_name',
                admin_note=NULL 
                WHERE id='$doc_id'";

        if ($conn->query($sql)) {
            $status_msg = "Submitted! Waiting for Admin approval.";
        }
    } else {
        $status_msg = "Error: System could not write to directory.";
        $status_is_error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KKA Staff | Work Basket</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --green-deep: #052e16;
            --green-forest: #064e3b;
            --green-primary: #15803d;
            --green-vivid: #16a34a;
            --green-soft: #dcfce7;
            --green-tint: #f0fdf4;
            --yellow-accent: #eab308;
            --yellow-warm: #ca8a04;
            --yellow-soft: #fef9c3;
            --bg: #f3f7f4;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #4b5563;
            --danger: #be123c;
            --danger-red: #ef4444;
            --danger-bg: #fef2f2;
            --border: #d1fae5;
            --border-neutral: #e2e8f0;
            --shadow-sm: 0 2px 8px rgba(21, 128, 61, 0.05);
            --shadow-md: 0 10px 25px -5px rgba(21, 128, 61, 0.1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            display: flex;
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* ================= GREEN SIDEBAR ================= */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--green-forest) 0%, var(--green-deep) 100%);
            color: #ffffff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            border-right: 3px solid var(--yellow-accent);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--yellow-accent) var(--green-deep);
            z-index: 100;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: var(--yellow-accent);
            border-radius: 10px;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 6px 18px 6px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            margin-bottom: 18px;
        }

        .brand-logo {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--green-vivid);
            border: 1.5px solid var(--yellow-accent);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .brand-header h2 {
            margin: 0;
            font-size: 14.5px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.3;
        }

        .profile-box {
            text-align: center;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 16px 12px;
            margin-bottom: 20px;
        }

        .avatar-ring {
            width: 82px;
            height: 82px;
            margin: 0 auto 10px;
            border-radius: 50%;
            padding: 3px;
            background: linear-gradient(135deg, var(--green-vivid), var(--yellow-accent));
        }

        .avatar-ring img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            background: #ffffff;
            display: block;
        }

        .profile-box h4 {
            margin: 0;
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
        }

        .profile-role {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 10px;
            border-radius: 999px;
            background: rgba(234, 179, 8, 0.16);
            color: #fef08a;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .nav-section-title {
            font-size: 10px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.45);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 8px 10px;
        }

        .sidebar nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .sidebar nav a {
            color: rgba(255, 255, 255, 0.78);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .sidebar nav a:hover,
        .sidebar nav a.active {
            background: rgba(22, 163, 74, 0.24);
            color: #ffffff;
            border-left: 3px solid var(--yellow-accent);
            padding-left: 17px;
        }

        .logout-link {
            margin-top: auto;
            color: #fca5a5 !important;
            background: rgba(239, 68, 68, 0.14);
            border: 1px solid rgba(239, 68, 68, 0.25);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 11px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .logout-link:hover {
            background: var(--danger-red);
            color: #ffffff !important;
        }

        /* ================= MAIN & ADMIN-STYLE HEADER ================= */
        .main {
            margin-left: 280px;
            width: calc(100% - 280px);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .admin-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            padding: 14px 40px;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 90;
            box-shadow: var(--shadow-sm);
            gap: 16px;
        }

        .brand-section h1 {
            margin: 0;
            font-size: 20px;
            color: var(--green-deep);
            font-weight: 800;
        }

        .welcome-text {
            font-size: 12.5px;
            color: var(--text-muted);
            margin-top: 2px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .header-right-group {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
        }

        .header-clock {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--green-tint);
            padding: 7px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .clock-icon i {
            color: var(--yellow-warm);
            font-size: 15px;
        }

        .clock-display {
            display: flex;
            flex-direction: column;
        }

        .clock-display span {
            font-size: 9px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
        }

        .clock-display strong {
            font-size: 12px;
            color: var(--green-deep);
            font-weight: 700;
        }

        .header-user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            padding: 5px 6px 5px 14px;
            border-radius: 30px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .header-user-name {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--green-primary);
        }

        .header-logout-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--danger-bg);
            color: var(--danger-red);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 12.5px;
            transition: all 0.2s ease;
        }

        .header-logout-icon:hover {
            background: var(--danger-red);
            color: #ffffff;
            transform: scale(1.05);
        }

        .content-body {
            padding: 32px 40px 48px;
            flex: 1;
            width: 100%;
        }

        /* ================= WORK BASKET CARDS ================= */
        .card {
            background: var(--card-bg);
            padding: 28px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
            border: 1px solid var(--border);
            border-left: 5px solid var(--green-primary);
            transition: box-shadow 0.2s ease;
        }

        .card:hover {
            box-shadow: var(--shadow-md);
        }

        .status-pill {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            background: var(--yellow-soft);
            color: #854d0e;
            border: 1px solid #fde047;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .instruction-box {
            background: var(--green-tint);
            border: 1px solid var(--border);
            border-left: 4px solid var(--green-vivid);
            color: var(--green-forest);
            padding: 18px 20px;
            border-radius: 14px;
            margin: 16px 0;
        }

        .rejection-box {
            background: #fff1f2;
            border: 1px solid #fecaca;
            border-left: 4px solid var(--danger);
            color: var(--danger);
            padding: 18px 20px;
            border-radius: 14px;
            margin: 18px 0;
        }

        .download-btn {
            color: var(--green-deep);
            font-weight: 700;
            font-size: 13px;
            text-decoration: none;
            background: var(--green-soft);
            border: 1px solid #86efac;
            padding: 10px 16px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .download-btn:hover {
            background: var(--green-primary);
            color: #ffffff;
            border-color: var(--green-primary);
        }

        .upload-box {
            background: #f8fafc;
            border: 2px dashed #86efac;
            padding: 22px;
            border-radius: 16px;
            margin-top: 20px;
        }

        .btn-submit {
            background: var(--green-primary);
            color: #ffffff;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            cursor: pointer;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-submit:hover {
            background: var(--green-deep);
            transform: translateY(-1px);
        }

        .alert-banner {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 22px;
            font-weight: 700;
            font-size: 13.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-banner.success {
            background: var(--green-soft);
            color: #166534;
            border: 1px solid #86efac;
            border-left: 4px solid var(--green-vivid);
        }

        .alert-banner.error {
            background: var(--danger-bg);
            color: #991b1b;
            border: 1px solid #fecaca;
            border-left: 4px solid var(--danger-red);
        }

        .empty-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-left: 5px solid var(--green-vivid);
            border-radius: 20px;
            padding: 48px 24px;
            text-align: center;
            color: var(--text-muted);
            box-shadow: var(--shadow-sm);
        }

        @media (max-width: 1024px) {
            .admin-top-bar {
                padding: 14px 20px;
            }
            .content-body {
                padding: 24px 20px;
            }
        }
    </style>
</head>

<body>
    <aside class="sidebar">
        <div class="brand-header">
            <div class="brand-logo"><i class="fas fa-leaf"></i></div>
            <h2>Karunesh Kumar &amp; Associates</h2>
        </div>

        <div class="profile-box">
            <div class="avatar-ring">
                <img src="../uploads/profile_pics/<?php echo htmlspecialchars($profile_img); ?>"
                     onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($display_name); ?>&background=15803d&color=fff';"
                     alt="<?php echo htmlspecialchars($display_name); ?>">
            </div>
            <h4><?php echo htmlspecialchars($display_name); ?></h4>
            <span class="profile-role">Office Employee</span>
        </div>

        <div class="nav-section-title">Workspace</div>
        <nav>
            <a href="employee-dashboard.php"><i class="fas fa-tasks"></i> My Tasks</a>
            <a href="work-basket.php" class="active"><i class="fas fa-briefcase"></i> Work Basket</a>
            <a href="employee-payments.php"><i class="fas fa-wallet"></i> Payments</a>
            <a href="Employee_profiles.php"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="staff-attendance.php"><i class="fas fa-clock"></i> Attendance</a>
        </nav>

        <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <div class="main">
        <header class="admin-top-bar">
            <div class="brand-section">
                <h1>Active Work Basket</h1>
                <div class="welcome-text">
                    <span>Employee: <strong><?php echo htmlspecialchars($first_name); ?></strong></span>
                    <span>&bull;</span>
                    <span><i class="fas fa-envelope" style="color:var(--green-primary);"></i> <?php echo htmlspecialchars($email); ?></span>
                </div>
            </div>

            <div class="header-right-group">
                <div class="header-clock">
                    <div class="clock-icon"><i class="fas fa-clock"></i></div>
                    <div class="clock-display">
                        <span id="liveDate">Loading Date...</span>
                        <strong id="liveTime">00:00:00</strong>
                    </div>
                </div>

                <div class="header-user-pill">
                    <span class="header-user-name"><?php echo htmlspecialchars($first_name); ?></span>
                    <a href="../logout.php" class="header-logout-icon" title="Logout">
                        <i class="fas fa-power-off"></i>
                    </a>
                </div>
            </div>
        </header>

        <div class="content-body">
            <?php if ($status_msg): ?>
                <div class="alert-banner <?php echo $status_is_error ? 'error' : 'success'; ?>">
                    <i class="fas <?php echo $status_is_error ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($status_msg); ?></span>
                </div>
            <?php endif; ?>

            <?php
            $docs = $conn->query("SELECT * FROM client_documents WHERE assigned_to='$email' AND status NOT IN ('Pending Review', 'Released')");

            if ($docs && $docs->num_rows > 0) {
                while ($d = $docs->fetch_assoc()) {
            ?>
                    <div class="card">
                        <span class="status-pill"><i class="fas fa-clock"></i> Task Pending</span>
                        <h2 style="margin: 16px 0 6px 0; color: var(--green-deep); font-size: 19px; font-weight: 800;"><?php echo htmlspecialchars($d['file_name']); ?></h2>
                        <p style="color: var(--text-muted); margin-bottom: 12px; font-size: 13px;">Client ID: <strong style="color: var(--green-primary);"><?php echo (!empty($d['client_id'])) ? htmlspecialchars($d['client_id']) : "No ID Assigned"; ?></strong></p>

                        <?php if (!empty($d['instruction'])): ?>
                            <div class="instruction-box">
                                <strong><i class="fas fa-info-circle"></i> WORK INSTRUCTIONS:</strong>
                                <p style="margin: 6px 0 0 0; font-size: 14px; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($d['instruction'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($d['admin_note'])): ?>
                            <div class="rejection-box">
                                <strong><i class="fas fa-exclamation-circle"></i> ADMIN CORRECTION REQUIRED:</strong>
                                <p style="margin: 6px 0 0 0; font-size: 13.5px; line-height: 1.5;"><?php echo htmlspecialchars($d['admin_note']); ?></p>
                            </div>
                        <?php endif; ?>

                        <div style="margin: 18px 0;">
                            <a href="../uploads/center/<?php echo urlencode($d['file_name']); ?>" download class="download-btn">
                                <i class="fas fa-download"></i> Download Original File
                            </a>
                        </div>

                        <div class="upload-box">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="doc_id" value="<?php echo htmlspecialchars($d['id'], ENT_QUOTES); ?>">
                                <label style="display:block; margin-bottom:10px; font-size:13px; font-weight:700; color:var(--green-deep);">Upload Final Result:</label>
                                <input type="file" name="result_file" required style="margin-bottom:16px; display:block; font-family:inherit; font-size:13px;">
                                <button type="submit" name="submit_work" class="btn-submit">
                                    <i class="fas fa-paper-plane"></i> Submit for Review
                                </button>
                            </form>
                        </div>
                    </div>
            <?php
                }
            } else {
            ?>
                <div class="empty-card">
                    <i class="fas fa-seedling" style="font-size: 38px; color: var(--green-vivid); opacity: 0.45; margin-bottom: 12px; display:block;"></i>
                    <p style="margin:0; font-size:14px; font-weight:600;">No active tasks found for <?php echo htmlspecialchars($email); ?>.</p>
                </div>
            <?php
            }
            ?>
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const dateEl = document.getElementById('liveDate');
            const timeEl = document.getElementById('liveTime');
            if (dateEl) {
                dateEl.textContent = now.toLocaleDateString('en-IN', {
                    weekday: 'short',
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
            }
            if (timeEl) {
                timeEl.textContent = now.toLocaleTimeString('en-IN', { hour12: true });
            }
        }
        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>

</html>