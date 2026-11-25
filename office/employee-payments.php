<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'office') {
    header("Location: ../Register.php");
    exit();
}

$user_email = $_SESSION['user']['identifier'];
$u_id = $_SESSION['user']['id'];

// 2. JOIN updated to include p.is_locked
$sql = "SELECT u.name, p.profile_pic, p.first_name, p.is_locked 
        FROM users u 
        LEFT JOIN employee_profiles p ON u.id = p.user_id 
        WHERE u.identifier = '$user_email'";

$profileRes = $conn->query($sql);
$user_data = ($profileRes) ? $profileRes->fetch_assoc() : [];

// 3. Set the global lock variable
$is_locked = (isset($user_data['is_locked']) && $user_data['is_locked'] == 1);

// Fallback logic
$display_name = !empty($user_data['name']) ? $user_data['name'] : "Employee";
$first_name = !empty($user_data['first_name']) ? $user_data['first_name'] : $display_name;
$profile_img = !empty($user_data['profile_pic']) ? $user_data['profile_pic'] : 'default-avatar.png';
$message = "";
$messageClass = "";

// Logic: Update Bank Details
if (isset($_POST['save_bank'])) {
    // SECURITY: Prevent update if profile is locked
    if ($is_locked) {
        $message = "Error: This account is locked. Bank details cannot be modified.";
        $messageClass = "alert-error";
    } else {
        $holder = $conn->real_escape_string($_POST['account_holder']);
        $bank = $conn->real_escape_string($_POST['bank_name']);
        $acc = $conn->real_escape_string($_POST['account_number']);
        $ifsc = strtoupper($conn->real_escape_string($_POST['ifsc_code']));
        $upi = $conn->real_escape_string($_POST['upi_id']);

        if (!preg_match("/^[A-Z]{4}0[A-Z0-9]{6}$/", $ifsc)) {
            $message = "Invalid IFSC Code format. Please check and try again.";
            $messageClass = "alert-error";
        } else {
            $sql = "UPDATE users SET 
                    account_holder='$holder', bank_name='$bank', 
                    account_number='$acc', ifsc_code='$ifsc', upi_id='$upi' 
                    WHERE id='$u_id'";

            if ($conn->query($sql)) {
                $message = "Bank details updated successfully!";
                $messageClass = "alert-success";
            }
        }
    }
}

// Fetch current details for the form
$user = $conn->query("SELECT * FROM users WHERE id='$u_id'")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KKA Staff | Payments</title>
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

        /* ================= GRID & CARDS ================= */
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .card {
            background: var(--card-bg);
            padding: 28px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            border-left: 5px solid var(--green-primary);
            transition: box-shadow 0.2s ease;
        }

        .card:hover {
            box-shadow: var(--shadow-md);
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: var(--green-deep);
            margin-bottom: 6px;
        }

        input {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--border-neutral);
            background: #f8fafc;
            border-radius: 10px;
            box-sizing: border-box;
            font-family: inherit;
            font-size: 13.5px;
            color: var(--text-main);
            outline: none;
            transition: all 0.2s ease;
        }

        input:focus {
            border-color: var(--green-primary);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.12);
        }

        .btn-save {
            background: var(--green-primary);
            color: #ffffff;
            border: none;
            padding: 13px 18px;
            border-radius: 10px;
            cursor: pointer;
            width: 100%;
            font-family: inherit;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .btn-save:hover {
            background: var(--green-deep);
            transform: translateY(-1px);
        }

        .alert-success {
            background: var(--green-soft);
            color: #166534;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 22px;
            border: 1px solid #86efac;
            border-left: 4px solid var(--green-vivid);
            font-weight: 700;
            font-size: 13.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: var(--danger-bg);
            color: #991b1b;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 22px;
            border: 1px solid #fecaca;
            border-left: 4px solid var(--danger);
            font-weight: 700;
            font-size: 13.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .salary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--border);
        }

        .salary-row:last-child {
            border-bottom: none;
        }

        @media (max-width: 1024px) {
            .admin-top-bar {
                padding: 14px 20px;
            }
            .content-body {
                padding: 24px 20px;
            }
            .grid {
                grid-template-columns: 1fr;
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
            <a href="employee-payments.php" class="active"><i class="fas fa-wallet"></i> Payments</a>
            <a href="Employee_profiles.php"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="staff-attendance.php"><i class="fas fa-clock"></i> Attendance</a>
        </nav>

        <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <div class="main">
        <header class="admin-top-bar">
            <div class="brand-section">
                <h1>Payments &amp; Payouts</h1>
                <div class="welcome-text">Update your bank details and view your monthly salary notifications</div>
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
            <?php if ($message): ?>
                <div class="<?php echo htmlspecialchars($messageClass); ?>">
                    <i class="fas <?php echo ($messageClass == 'alert-success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <div class="grid">
                <div class="card" style="border-left-color: var(--yellow-accent);">
                    <h3 style="margin-top:0; color:var(--green-deep); font-size:16px; font-weight:800;"><i class="fas fa-university" style="color:var(--green-primary);"></i> Bank Account Information</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label>Account Holder Name (As per Bank)</label>
                            <input type="text" name="account_holder" value="<?php echo htmlspecialchars($user['account_holder'] ?? ''); ?>"
                                <?php echo $is_locked ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : 'required'; ?>>
                        </div>

                        <div class="form-group">
                            <label>Bank Name</label>
                            <input type="text" name="bank_name" value="<?php echo htmlspecialchars($user['bank_name'] ?? ''); ?>"
                                <?php echo $is_locked ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : 'required'; ?>>
                        </div>

                        <div style="display:flex; gap:15px; flex-wrap:wrap;">
                            <div class="form-group" style="flex:2; min-width:160px;">
                                <label>Account Number</label>
                                <input type="text" name="account_number" value="<?php echo htmlspecialchars($user['account_number'] ?? ''); ?>"
                                    <?php echo $is_locked ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : 'required'; ?>>
                            </div>
                            <div class="form-group" style="flex:1; min-width:130px;">
                                <label>IFSC Code</label>
                                <input type="text" name="ifsc_code" value="<?php echo htmlspecialchars($user['ifsc_code'] ?? ''); ?>"
                                    <?php echo $is_locked ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : 'required'; ?>>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>UPI ID (For Fast Payouts)</label>
                            <input type="text" name="upi_id" value="<?php echo htmlspecialchars($user['upi_id'] ?? ''); ?>"
                                <?php echo $is_locked ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : ''; ?>>
                        </div>

                        <?php if (!$is_locked): ?>
                            <button type="submit" name="save_bank" class="btn-save">
                                Update Payout Profile
                            </button>
                        <?php else: ?>
                            <div style="margin-top:15px; padding:12px; background:var(--yellow-soft); color:#854d0e; border-radius:10px; border:1px solid #fde047; text-align:center; font-size:13px; font-weight:700;">
                                <i class="fas fa-lock"></i> Bank Details Verified &amp; Locked.
                            </div>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="card">
                    <h3 style="margin-top:0; color:var(--green-deep); font-size:16px; font-weight:800;"><i class="fas fa-receipt" style="color:var(--green-primary);"></i> Payout Notifications</h3>
                    <div style="margin-top: 10px;">
                        <?php
                        $history = $conn->query("SELECT * FROM salaries WHERE user_id='$u_id' ORDER BY created_at DESC");
                        if ($history && $history->num_rows > 0) {
                            while ($row = $history->fetch_assoc()) {
                        ?>
                                <div class="salary-row">
                                    <div>
                                        <div style="font-weight:700; color:var(--green-deep);"><?php echo htmlspecialchars($row['month_year']); ?></div>
                                        <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px;">Ref: <?php echo htmlspecialchars($row['transaction_id']); ?></div>
                                    </div>
                                    <div style="text-align:right;">
                                        <div style="color:var(--green-primary); font-weight:800; font-size:16px;">&#8377;<?php echo number_format((float)$row['amount'], 2); ?></div>
                                        <div style="font-size:10.5px; text-transform:uppercase; color:var(--text-muted); font-weight:700; margin-top:2px;">
                                            <i class="fas fa-check" style="color:var(--green-vivid);"></i> <?php echo htmlspecialchars($row['status']); ?>
                                        </div>
                                    </div>
                                </div>
                        <?php
                            }
                        } else {
                            echo "<div style='text-align:center; padding-top:40px; color:var(--text-muted);'>
                                    <i class='fas fa-info-circle' style='font-size:30px; color:var(--green-vivid); opacity:0.5; margin-bottom:10px; display:block;'></i>
                                    No salary history recorded yet.
                                  </div>";
                        }
                        ?>
                    </div>
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