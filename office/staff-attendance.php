<?php
session_start();
include('../db.php');

// 1. SET THE TIMEZONE
date_default_timezone_set('Asia/Kolkata');
// 2. SECURITY CHECK
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'office') {
    header("Location: ../Register.php");
    exit();
}

$user_email = $_SESSION['user']['identifier'];
$today = date('Y-m-d');
$current_time = date('H:i:s');

// --- HANDLE CHECK-IN ---
if (isset($_POST['mark_attendance'])) {
    $check = $conn->query("SELECT id FROM attendance WHERE email='$user_email' AND log_date='$today'");
    if ($check->num_rows == 0) {
        $sql = "INSERT INTO attendance (email, log_date, login_time) VALUES ('$user_email', '$today', '$current_time')";
        $conn->query($sql);
    }
    header("Location: staff-attendance.php");
    exit();
}

// --- HANDLE CHECK-OUT & LOGOUT ---
if (isset($_POST['manual_checkout'])) {
    // 1. Update the database
    $sql = "UPDATE attendance SET logout_time='$current_time' 
            WHERE email='$user_email' AND log_date='$today' AND logout_time IS NULL";
    
    if ($conn->query($sql)) {
        // 2. Clear ALL session data
        $_SESSION = array(); 
        
        // 3. Destroy the session on server
        session_destroy();
        
        // 4. Redirect - Use ONE dot if Register.php is in the parent folder
        header("Location: ../Register.php?status=shift_ended");
        exit();
    }
}

// --- FETCH DATA FOR UI ---
$profileRes = $conn->query("SELECT u.name, p.profile_pic FROM users u LEFT JOIN employee_profiles p ON u.id = p.user_id WHERE u.identifier = '$user_email'");
$user_data = $profileRes->fetch_assoc();

$att_res = $conn->query("SELECT * FROM attendance WHERE email='$user_email' AND log_date='$today'");
$row = $att_res->fetch_assoc();

$is_present = ($att_res && $att_res->num_rows > 0);
$is_checked_out = ($row && !empty($row['logout_time']));

$display_name = !empty($user_data['name']) ? $user_data['name'] : "Employee";
$profile_img = !empty($user_data['profile_pic']) ? $user_data['profile_pic'] : 'default-avatar.png';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KKA Staff | Attendance</title>
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
            --danger: #ef4444;
            --danger-dark: #be123c;
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
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
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
            background: var(--danger);
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
            color: var(--danger);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 12.5px;
            transition: all 0.2s ease;
        }

        .header-logout-icon:hover {
            background: var(--danger);
            color: #ffffff;
            transform: scale(1.05);
        }

        .content-body {
            padding: 32px 40px 48px;
            flex: 1;
            width: 100%;
        }

        /* ================= ATTENDANCE CARDS & TABLE ================= */
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

        .btn-checkin {
            background: var(--green-primary);
            color: #ffffff;
            border: none;
            padding: 15px 28px;
            border-radius: 12px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 800;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(21, 128, 61, 0.2);
        }

        .btn-checkin:hover {
            background: var(--green-deep);
            transform: translateY(-2px);
        }

        .status-box {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px 22px;
            border-radius: 16px;
            font-weight: 700;
            font-size: 14px;
        }

        .status-present {
            background: var(--green-soft);
            color: #166534;
            border: 1px solid #86efac;
            border-left: 6px solid var(--green-vivid);
        }

        .status-absent {
            background: var(--danger-bg);
            color: #991b1b;
            border: 1px solid #fecaca;
            border-left: 6px solid var(--danger);
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th {
            text-align: left;
            padding: 13px 16px;
            background: var(--green-tint);
            color: var(--green-forest);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-neutral);
            font-size: 13.5px;
            color: var(--text-main);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: var(--green-tint);
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
            <a href="work-basket.php"><i class="fas fa-briefcase"></i> Work Basket</a>
            <a href="employee-payments.php"><i class="fas fa-wallet"></i> Payments</a>
            <a href="Employee_profiles.php"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="staff-attendance.php" class="active"><i class="fas fa-clock"></i> Attendance</a>
        </nav>

        <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <div class="main">
        <header class="admin-top-bar">
            <div class="brand-section">
                <h1>Daily Attendance Log</h1>
                <div class="welcome-text">
                    <span><?php echo date('l, d M Y'); ?></span>
                    <span>&bull;</span>
                    <span>Server Time (IST): <strong><?php echo date('h:i A'); ?></strong></span>
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
                    <span class="header-user-name"><?php echo htmlspecialchars($display_name); ?></span>
                    <a href="../logout.php" class="header-logout-icon" title="Logout">
                        <i class="fas fa-power-off"></i>
                    </a>
                </div>
            </div>
        </header>

        <div class="content-body">
            <div class="card">
                <?php if (!$is_present): ?>
                    <div class="status-box status-absent" style="margin-bottom:20px;">
                        <i class="fas fa-fingerprint" style="font-size:28px;"></i>
                        <span>You have not checked in yet today.</span>
                    </div>
                    <form method="POST">
                        <button type="submit" name="mark_attendance" class="btn-checkin">
                            <i class="fas fa-sign-in-alt"></i> MARK ATTENDANCE
                        </button>
                    </form>

                <?php elseif (!$is_checked_out): ?>
                    <div class="status-box status-present" style="margin-bottom:20px; background:var(--yellow-soft); color:#854d0e; border-color:#fde047; border-left-color:var(--yellow-accent);">
                        <i class="fas fa-clock" style="font-size:28px; color:var(--yellow-warm);"></i>
                        <span>Logged in at: <b><?php echo date('h:i A', strtotime($row['login_time'])); ?></b></span>
                    </div>
                    <form method="POST" onsubmit="return confirm('Ready to end your shift? You will be logged out.');">
                        <button type="submit" name="manual_checkout" class="btn-checkin" style="background:var(--danger-dark); box-shadow: 0 4px 14px rgba(190, 18, 60, 0.22);">
                            <i class="fas fa-power-off"></i> CHECK-OUT &amp; LOGOUT
                        </button>
                    </form>

                <?php else: ?>
                    <div class="status-box status-present">
                        <i class="fas fa-check-double" style="font-size:26px;"></i>
                        <span>Shift Completed.</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card" style="border-left-color: var(--yellow-accent);">
                <h3 style="margin:0 0 12px 0; color:var(--green-deep); font-size:16px; font-weight:800; display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-history" style="color:var(--green-primary);"></i> Attendance History (IST)
                </h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Check-In</th>
                                <th>Check-Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $history = $conn->query("SELECT * FROM attendance WHERE email='$user_email' ORDER BY log_date DESC LIMIT 10");
                            while ($att = $history->fetch_assoc()): 
                                $status = !empty($att['logout_time']) ? "Completed" : "On-Duty";
                                $color = !empty($att['logout_time']) ? "#15803d" : "#ca8a04";
                            ?>
                            <tr>
                                <td style="font-weight:600;"><?php echo date('d M, Y', strtotime($att['log_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($att['login_time'])); ?></td>
                                <td><?php echo !empty($att['logout_time']) ? date('h:i A', strtotime($att['logout_time'])) : '--:--'; ?></td>
                                <td>
                                    <span style="color: <?php echo $color; ?>; font-weight: 800;">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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