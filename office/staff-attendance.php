<?php
session_start();
include('../db.php');

// 1. SET THE TIMEZONE TO INDIA
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'office') {
    header("Location: ../Register.php");
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
$today = date('Y-m-d'); // Today's date in IST
$message = "";

// --- LOGIC: Handle the Check-In Button Click ---
if (isset($_POST['mark_attendance'])) {
    // Capture time in IST
    $time = date('H:i:s');

    // Check again to prevent double entries
    $check = $conn->query("SELECT id FROM attendance WHERE email='$email' AND log_date='$today'");
    if ($check->num_rows == 0) {
        $sql = "INSERT INTO attendance (email, log_date, login_time) VALUES ('$email', '$today', '$time')";
        if ($conn->query($sql)) {
            // Refresh to update the UI
            header("Location: staff-attendance.php?success=1");
            exit();
        }
    }
}

// Check if user is already checked in for today
$att_check = $conn->query("SELECT login_time FROM attendance WHERE email='$email' AND log_date='$today'");
$is_present = ($att_check->num_rows > 0);
$login_data = $att_check->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>KKA Staff | Attendance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --navy: #0b3c74;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
        }

        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            color: #334155;
        }

         .sidebar {
    width: 280px;
    background: var(--sidebar);
    color: white;
    height: 100vh;
    position: fixed;
    padding: 30px 20px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    
    /* ADD THIS LINE */
    border-right: 4px solid var(--orange); 
}

        .sidebar h2 {
            font-size: 22px;
            color: var(--orange);
            margin-bottom: 40px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 20px;
        }

        .sidebar a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px;
            margin-bottom: 8px;
            border-radius: 12px;
            transition: 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 4px solid var(--orange);
        }

        .main {
            margin-left: 280px;
            padding: 50px;
            width: calc(100% - 280px);
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
            margin-bottom: 25px;
        }

        .btn-checkin {
            background: var(--navy);
            color: white;
            border: none;
            padding: 18px 35px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 800;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: 0.3s;
            box-shadow: 0 4px 15px rgba(11, 60, 116, 0.2);
        }

        .btn-checkin:hover {
            background: var(--orange);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 140, 0, 0.3);
        }

        .status-box {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 25px;
            border-radius: 20px;
        }

        .status-present {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
            border-left: 10px solid #22c55e;
        }

        .status-absent {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-left: 10px solid #ef4444;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            text-align: left;
            padding: 15px;
            color: #64748b;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f8fafc;
            font-size: 15px;
        }
    </style>
</head>

<body>

  <div class="sidebar">
    <h2 style="font-size: 20px; color: var(--orange); margin-bottom: 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 15px; text-align: center;">
        Karunesh Kumar & Associates Employee
    </h2>
    
    <div style="text-align: center; padding: 5px 0 20px 0; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px;">
        <div style="width: 90px; height: 90px; margin: 0 auto 12px; border-radius: 50%; overflow: hidden;  background: #eee; display: flex; align-items: center; justify-content: center;">
            <img src="../uploads/profile_pics/<?php echo $profile_img; ?>" 
                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($display_name); ?>&background=0b3c74&color=fff';"
                 style="width: 100%; height: 100%; object-fit: cover; display: block;">
        </div>
        
        <h4 style="margin: 0; color: white; font-size: 18px; font-weight: 600; line-height: 1.2;">
            <?php echo htmlspecialchars($display_name); ?>
        </h4>
        <span style="color: var(--orange); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-top: 5px;">
            <i  style="font-size: 9px;"></i> Office Employee
        </span>
    </div>

    <nav style="display: flex; flex-direction: column; gap: 2px;">
        <a href="employee-dashboard.php" ><i class="fas fa-tasks"></i> My Tasks</a>
        <a href="work-basket.php"><i class="fas fa-briefcase"></i> Work Basket</a>
        <a href="employee-payments.php"><i class="fas fa-wallet"></i> Payments</a>
        <a href="Employee_profiles.php"><i class="fas fa-user-circle"></i> Profile</a>
        <a href="staff-attendance.php" class="active"><i class="fas fa-clock"></i> Attendance</a>
    </nav>

    <a href="../logout.php" class="logout-link" style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
    <div class="main">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
            <h1>Daily Attendance Log</h1>
            <div style="text-align:right;">
                <p style="margin:0; font-weight:bold; color:var(--navy);"><?php echo date('l, d M Y'); ?></p>
                <p style="margin:0; font-size:12px; color:#64748b;">Current Time (IST): <?php echo date('h:i A'); ?></p>
            </div>
        </div>

        <div class="card">
            <?php if ($is_present): ?>
                <div class="status-box status-present">
                    <div style="font-size:40px;"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <h2 style="margin:0;">Marked Present</h2>
                        <p style="margin:5px 0; font-size:16px;">System log verified. Your shift started at: <b><?php echo date('h:i A', strtotime($login_data['login_time'])); ?></b></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="status-box status-absent" style="margin-bottom:25px;">
                    <div style="font-size:40px;"><i class="fas fa-fingerprint"></i></div>
                    <div>
                        <h2 style="margin:0;">Not Checked-In</h2>
                        <p style="margin:5px 0; font-size:16px;">Your presence has not been recorded for today yet.</p>
                    </div>
                </div>

                <form method="POST">
                    <button type="submit" name="mark_attendance" class="btn-checkin">
                        <i class="fas fa-sign-in-alt"></i> MARK ATTENDANCE NOW
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3><i class="fas fa-history" style="color:var(--orange);"></i> Attendance History (IST)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Check-in Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $history = $conn->query("SELECT * FROM attendance WHERE email='$email' ORDER BY log_date DESC LIMIT 7");
                    if ($history->num_rows > 0) {
                        while ($row = $history->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td><b>" . date('d M, Y', strtotime($row['log_date'])) . "</b></td>";
                            echo "<td><span style='color:#22c55e; font-weight:bold;'><i class='fas fa-check'></i> Present</span></td>";
                            echo "<td>" . date('h:i A', strtotime($row['login_time'])) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3' style='text-align:center; padding:30px; color:#94a3b8;'>No records found yet.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>