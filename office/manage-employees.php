<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

$today = date('Y-m-d');

// 1. Delete Logic
if (isset($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    $conn->query("DELETE FROM users WHERE id='$id' AND role='office'");
    header("Location: manage-employees.php");
    exit();
}

// 2. Password Update Logic
if (isset($_POST['update_pass'])) {
    $uid = $_POST['user_id'];
    $new_p = mysqli_real_escape_string($conn, $_POST['new_password']);
    $conn->query("UPDATE users SET password='$new_p', reset_requested=0 WHERE id='$uid'");
    echo "<script>alert('Password updated successfully!'); window.location='manage-employees.php';</script>";
}

// 3. Salary Payout Logic
if (isset($_POST['process_salary'])) {
    $uid = $_POST['user_id'];
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $month = mysqli_real_escape_string($conn, $_POST['month_year']);

    // Check if bank details exist
    $check = $conn->query("SELECT bank_name, account_number, upi_id FROM users WHERE id='$uid'")->fetch_assoc();

    if (empty($check['bank_name']) && empty($check['upi_id'])) {
        echo "<script>alert('Error: This employee has not added any payment details!'); window.location='manage-employees.php';</script>";
    } else {
        $trans_id = "KKA-" . strtoupper(bin2hex(random_bytes(4)));
        $now = date('Y-m-d H:i:s');

        $sql = "INSERT INTO salaries (user_id, month_year, amount, status, transaction_id, paid_at) 
                VALUES ('$uid', '$month', '$amount', 'Paid', '$trans_id', '$now')";

        if ($conn->query($sql)) {
            echo "<script>alert('Payment Recorded! Transaction ID: $trans_id'); window.location='manage-employees.php';</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff | KKA Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --navy-dark: #051b34;
            --navy: #0b3c74;
            --navy-light: #155297;
            --orange: #ff8c00;
            --orange-glow: rgba(255, 140, 0, 0.15);
            --sidebar-bg: #062241;
            --sidebar-hover: rgba(255, 255, 255, 0.06);
            --bg: #f3f6fc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --danger: #ef4444;
            --danger-bg: #fef2f2;
            --success: #10b981;
            --success-bg: #ecfdf5;
            --border: #e2e8f0;
            --shadow-sm: 0 2px 8px rgba(11, 60, 116, 0.04);
            --shadow-md: 0 10px 25px -5px rgba(11, 60, 116, 0.08);
            --shadow-lg: 0 20px 30px -10px rgba(11, 60, 116, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
        }

        /* ================= SIDEBAR ================= */
        .sidebar {
            width: 280px;
            background: var(--sidebar-bg);
            color: white;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            border-right: 3px solid var(--orange);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--orange) var(--sidebar-bg);
            z-index: 100;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: var(--orange);
            border-radius: 10px;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 8px 20px 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 20px;
        }

        .brand-logo {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--orange), #d97706);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(255, 140, 0, 0.3);
        }

        .brand-header h2 {
            font-size: 15px;
            color: #ffffff;
            margin: 0;
            font-weight: 700;
            line-height: 1.3;
        }

        .sidebar-profile {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .profile-avatar-wrapper {
            position: relative;
            flex-shrink: 0;
        }

        .sidebar-avatar {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--navy-light), var(--navy));
            border: 2px solid var(--orange);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            font-weight: 700;
        }

        .online-status {
            width: 10px;
            height: 10px;
            background: var(--success);
            border: 2px solid var(--sidebar-bg);
            border-radius: 50%;
            position: absolute;
            bottom: -2px;
            right: -2px;
        }

        .profile-info strong {
            display: block;
            font-size: 13px;
            color: #ffffff;
            font-weight: 600;
        }

        .profile-info span {
            font-size: 11px;
            color: var(--orange);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-nav-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.4);
            margin: 10px 0 8px 10px;
            font-weight: 700;
        }

        .sidebar a {
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            margin-bottom: 4px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: var(--sidebar-hover);
            color: white;
            border-left: 3px solid var(--orange);
            padding-left: 17px;
        }

        .sidebar a.active {
            background: rgba(255, 140, 0, 0.12);
            color: #ffffff;
            font-weight: 600;
        }

        .dropdown-content {
            display: none;
            background: rgba(0, 0, 0, 0.15);
            margin: 2px 0 6px 10px;
            border-radius: 10px;
            padding: 4px 0;
            border-left: 2px solid rgba(255, 140, 0, 0.3);
        }

        .dropdown-content a {
            padding: 9px 14px;
            font-size: 12.5px;
            margin-bottom: 0;
            border-radius: 0;
        }

        .dropdown-content a:hover,
        .dropdown-content a.active {
            border-left: none;
            background: rgba(255, 255, 255, 0.05);
            color: var(--orange);
            padding-left: 14px;
        }

        .show-menu {
            display: block !important;
        }

        .rotate-chevron {
            transform: rotate(180deg);
        }

        /* ================= MAIN WORKSPACE ================= */
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
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            padding: 14px 40px;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 90;
            box-shadow: var(--shadow-sm);
        }

        .brand-section h1 {
            margin: 0;
            font-size: 20px;
            color: var(--text-main);
            font-weight: 800;
        }

        .welcome-text {
            font-size: 12.5px;
            color: var(--text-muted);
            margin-top: 1px;
            font-weight: 500;
        }

        .welcome-text span {
            color: var(--navy);
            font-weight: 700;
        }

        .header-right-group {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-clock {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            padding: 8px 16px;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .clock-icon i {
            color: var(--orange);
            font-size: 16px;
        }

        .clock-display {
            display: flex;
            flex-direction: column;
        }

        .clock-display span {
            font-size: 9.5px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
        }

        .clock-display strong {
            font-size: 12.5px;
            color: var(--text-main);
            font-weight: 700;
        }

        .header-user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            padding: 5px 6px 5px 12px;
            border-radius: 30px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .header-user-name {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--navy);
        }

        .header-logout-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--danger-bg);
            color: var(--danger);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s;
        }

        .header-logout-icon:hover {
            background: var(--danger);
            color: white;
            transform: scale(1.05);
        }

        .content-body {
            padding: 32px 40px;
            flex: 1;
        }

        /* Search Bar Styling */
        .search-container {
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            background: var(--card-bg);
            padding: 12px 20px;
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .search-container i {
            color: var(--text-muted);
            margin-right: 14px;
            font-size: 14px;
        }

        .search-container input {
            border: none;
            outline: none;
            width: 100%;
            font-size: 13.5px;
            font-family: inherit;
            color: var(--text-main);
            background: transparent;
        }

        .table-card {
            background: var(--card-bg);
            padding: 28px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .table-card h3 {
            margin: 0 0 20px 0;
            font-size: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 14px 16px;
            background: #f8fafc;
            color: var(--navy);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11.5px;
            letter-spacing: 0.6px;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 15px 16px;
            border-bottom: 1px solid var(--border);
            color: var(--text-muted);
            font-size: 13.5px;
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .btn-update {
            background: var(--navy);
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 600;
            font-size: 11.5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }

        .btn-update:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        .badge {
            padding: 5px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-active {
            background: var(--success-bg);
            color: #047857;
        }

        .badge-absent {
            background: #f1f5f9;
            color: var(--text-muted);
        }

        /* Profile Modal Details */
        .d-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
            align-items: center;
        }

        .d-lbl {
            color: var(--text-muted);
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .d-val {
            font-weight: 600;
            color: var(--navy);
            font-size: 13px;
            text-align: right;
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

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="brand-header">
            <div class="brand-logo"><i class="fas fa-shield-alt"></i></div>
            <h2>Karunesh Kumar &amp; Associates</h2>
        </div>

        <div class="sidebar-profile">
            <div class="profile-avatar-wrapper">
                <div class="sidebar-avatar"><i class="fas fa-user-shield"></i></div>
                <div class="online-status"></div>
            </div>
            <div class="profile-info">
                <strong>Admin Executive</strong>
                <span>System Administrator</span>
            </div>
        </div>

        <div class="sidebar-nav-title">Main Menu</div>
        <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('billingMenu', 'billChev')">
                <i class="fas fa-file-invoice-dollar"></i> Billing
                <i class="fas fa-chevron-down" id="billChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="billingMenu">
                <a href="quotations.php">Quotations</a>
                <a href="invoices.php">Invoices</a>
                <a href="receipts.php">Receipts</a>
                <a href="outstanding.php">Outstanding</a>
            </div>
        </div>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('vaultMenu', 'vaultChev')">
                <i class="fas fa-folder-open"></i> Documents
                <i class="fas fa-chevron-down" id="vaultChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="vaultMenu">
                <a href="admin-review.php">Quality Control</a>
                <a href="Master-Vault.php">Services</a>
                <a href="client-documents.php">Client Documents</a>
            </div>
        </div>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('reportsMenu', 'repChev')">
                <i class="fas fa-file-contract"></i> Reports
                <i class="fas fa-chevron-down" id="repChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="reportsMenu">
                <a href="dsc-register.php">DSC Register</a>
                <a href="service-report.php">Service Report</a>
                <a href="revenue-analytics.php">Revenue Analytics</a>
                <a href="Client-Revenue.php">Client Revenue</a>
                <a href="attendance.php">Attendance</a>
            </div>
        </div>

        <div class="sidebar-nav-title" style="margin-top:15px;">Management</div>
        <a href="assign-work.php"><i class="fas fa-tasks"></i> Assign Work</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('clientMenu', 'clientChev')">
                <i class="fas fa-users"></i> Manage Clients
                <i class="fas fa-chevron-down" id="clientChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="clientMenu">
                <a href="manage-clients.php">Clients</a>
                <a href="client-groups.php">Client Groups</a>
                <a href="client-services.php">Services</a>
                <a href="client-upload-docs.php">Clients Uploads</a>
            </div>
        </div>

        <a href="manage-employees.php" class="active"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
        <a href="../logout.php" style="margin-top:auto; color:#fca5a5;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <!-- Main Workspace -->
    <div class="main">
        <header class="admin-top-bar">
            <div class="brand-section">
                <h1>Employee Management</h1>
                <div class="welcome-text">Manage staff details, verify bank info, and process payroll</div>
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
                    <span class="header-user-name">Admin</span>
                    <a href="../logout.php" class="header-logout-icon" title="Logout">
                        <i class="fas fa-power-off"></i>
                    </a>
                </div>
            </div>
        </header>

        <div class="content-body">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="employeeSearch" placeholder="Search by employee name or email..." onkeyup="filterEmployees()">
            </div>

            <?php
            // 1. Active Employees Query (tenure_end is NULL or on/after today)
            $sql_active = "SELECT u.*, 
                ep.is_locked, ep.aadhaar_no, ep.aadhaar_photo, 
                ep.pan_no, ep.pan_photo, ep.marksheet_10, ep.marksheet_12, 
                ep.address, ep.dob, ep.emergency_contact, 
                ep.profile_pic, ep.date_of_joining, ep.tenure_start, ep.tenure_end,
                ep.firm_reg_no, ep.prof_qualification, ep.prof_doc_path, 
                (SELECT login_time FROM attendance WHERE email = u.identifier AND log_date = '$today' LIMIT 1) as attendance_time 
                FROM users u 
                LEFT JOIN employee_profiles ep ON u.id = ep.user_id
                WHERE u.role='office' AND (ep.tenure_end IS NULL || ep.tenure_end >= '$today')
                ORDER BY u.name ASC";
            $res_active = $conn->query($sql_active);

            // 2. Tenure Over Employees Query (tenure_end is strictly before today)
            $sql_over = "SELECT u.*, 
                ep.is_locked, ep.aadhaar_no, ep.aadhaar_photo, 
                ep.pan_no, ep.pan_photo, ep.marksheet_10, ep.marksheet_12, 
                ep.address, ep.dob, ep.emergency_contact, 
                ep.profile_pic, ep.date_of_joining, ep.tenure_start, ep.tenure_end,
                ep.firm_reg_no, ep.prof_qualification, ep.prof_doc_path, 
                (SELECT login_time FROM attendance WHERE email = u.identifier AND log_date = '$today' LIMIT 1) as attendance_time 
                FROM users u 
                LEFT JOIN employee_profiles ep ON u.id = ep.user_id
                WHERE u.role='office' AND ep.tenure_end IS NOT NULL AND ep.tenure_end < '$today'
                ORDER BY u.name ASC";
            $res_over = $conn->query($sql_over);
            ?>

            <!-- ================= 1. ACTIVE EMPLOYEES TABLE ================= -->
            <div class="table-card" style="margin-bottom: 32px;">
                <h3 style="color: var(--navy);"><i class="fas fa-users" style="color: var(--orange);"></i> Active Employees</h3>
                <table id="employeeTable">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Start Date</th>
                            <th>Duty Status</th>
                            <th>Account Details</th>
                            <th>Quick Reset</th>
                            <th>Payroll</th>
                            <th>Control</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($res_active && $res_active->num_rows == 0) {
                            echo '<tr><td colspan="7" style="text-align:center; color:var(--text-muted); padding:24px;">No active employees found.</td></tr>';
                        }
                        while ($row = $res_active->fetch_assoc()) {
                            $is_present = !empty($row['attendance_time']);

                            // Bank Preview logic
                            $bank_preview = "---";
                            if (!empty($row['bank_name'])) {
                                $bank_preview = "<b style='color:var(--text-main);'>" . htmlspecialchars($row['bank_name']) . "</b><br><small>" . htmlspecialchars($row['account_number']) . "</small>";
                            } elseif (!empty($row['upi_id'])) {
                                $bank_preview = "<small>UPI: " . htmlspecialchars($row['upi_id']) . "</small>";
                            }

                            $start_date = !empty($row['tenure_start']) ? $row['tenure_start'] : ($row['date_of_joining'] ?? 'Not Set');
                        ?>
                            <tr>
                                <td>
                                    <strong style="color:var(--text-main);"><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                    <span style="font-size:11.5px; color:var(--text-muted);"><?php echo htmlspecialchars($row['identifier']); ?></span>
                                </td>
                                <td><span style="font-weight:600; color:var(--navy);"><?php echo htmlspecialchars($start_date); ?></span></td>
                                <td>
                                    <?php if ($is_present): ?>
                                        <span class="badge badge-active">ACTIVE</span>
                                    <?php else: ?>
                                        <span class="badge badge-absent">ABSENT</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $bank_preview; ?></td>
                                <td>
                                    <form method="POST" class="reset-form" style="display:flex; gap:5px; margin:0;">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <input type="text" name="new_password" placeholder="Pass" style="width:72px; padding:6px 8px; border:1.5px solid var(--border); border-radius:6px; font-size:12px;" required>
                                        <button name="update_pass" class="btn-update">Update</button>
                                    </form>
                                </td>
                                <td>
                                    <button class="btn-update" style="background:var(--success);"
                                        onclick="openSalaryModal(
                                            '<?php echo $row['id']; ?>', 
                                            '<?php echo addslashes($row['name']); ?>', 
                                            '<?php echo addslashes($row['bank_name']); ?>', 
                                            '<?php echo $row['account_number']; ?>', 
                                            '<?php echo $row['ifsc_code']; ?>', 
                                            '<?php echo $row['upi_id']; ?>',
                                            '<?php echo addslashes($row['account_holder']); ?>'
                                        )">
                                        <i class="fas fa-wallet"></i> Pay
                                    </button>
                                </td>
                                <td>
                                    <div style="display:flex; gap:14px; align-items:center; justify-content:center;">
                                        <a href="javascript:void(0)" onclick="viewEmployee(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)" title="View Profile">
                                            <i class="fas fa-eye" style="color: var(--navy); cursor:pointer;"></i>
                                        </a>

                                        <a href="javascript:void(0)" onclick="openTenureModal('<?php echo $row['id']; ?>', '<?php echo addslashes($row['name']); ?>', '<?php echo $row['tenure_start']; ?>', '<?php echo $row['tenure_end']; ?>')" title="Manage Tenure">
                                            <i class="fas fa-calendar-alt" style="color: var(--orange); cursor:pointer;"></i>
                                        </a>

                                        <?php
                                        $locked_status = isset($row['is_locked']) ? (int)$row['is_locked'] : 0;
                                        if ($locked_status === 1): ?>
                                            <a href="toggle_lock.php?email=<?php echo urlencode($row['identifier']); ?>&status=0"
                                                title="Unlock Profile"
                                                onclick="return confirm('Enable editing for this employee?')">
                                                <i class="fas fa-lock" style="color: var(--danger); cursor:pointer;"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="toggle_lock.php?email=<?php echo urlencode($row['identifier']); ?>&status=1"
                                                title="Lock Profile"
                                                onclick="return confirm('Freeze this employee profile?')">
                                                <i class="fas fa-lock-open" style="color: var(--success); cursor:pointer;"></i>
                                            </a>
                                        <?php endif; ?>

                                        <a href="?delete=<?php echo $row['id']; ?>"
                                            title="Delete Employee"
                                            onclick="return confirm('Permanently remove this employee?')">
                                            <i class="fas fa-trash-alt" style="color:var(--danger);"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <!-- ================= 2. TENURE OVER EMPLOYEES TABLE ================= -->
            <div class="table-card" style="border-top: 4px solid var(--danger);">
                <h3 style="color: var(--danger);"><i class="fas fa-user-clock"></i> Tenure Over Employees</h3>
                <table id="tenureOverTable">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Account Details</th>
                            <th>Quick Reset</th>
                            <th>Payroll</th>
                            <th>Control</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($res_over && $res_over->num_rows == 0) {
                            echo '<tr><td colspan="7" style="text-align:center; color:var(--text-muted); padding:24px;">No tenure over employees found.</td></tr>';
                        }
                        while ($row = $res_over->fetch_assoc()) {
                            // Bank Preview logic
                            $bank_preview = "---";
                            if (!empty($row['bank_name'])) {
                                $bank_preview = "<b style='color:var(--text-main);'>" . htmlspecialchars($row['bank_name']) . "</b><br><small>" . htmlspecialchars($row['account_number']) . "</small>";
                            } elseif (!empty($row['upi_id'])) {
                                $bank_preview = "<small>UPI: " . htmlspecialchars($row['upi_id']) . "</small>";
                            }

                            $start_date = $row['tenure_start'] ?? 'Not Set';
                            $end_date = $row['tenure_end'] ?? 'Not Set';
                        ?>
                            <tr>
                                <td>
                                    <strong style="color:var(--text-main);"><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                    <span style="font-size:11.5px; color:var(--text-muted);"><?php echo htmlspecialchars($row['identifier']); ?></span>
                                </td>
                                <td><span style="font-weight:600; color:var(--navy);"><?php echo htmlspecialchars($start_date); ?></span></td>
                                <td><span style="font-weight:600; color:var(--danger);"><?php echo htmlspecialchars($end_date); ?></span></td>
                                <td><?php echo $bank_preview; ?></td>
                                <td>
                                    <form method="POST" class="reset-form" style="display:flex; gap:5px; margin:0;">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <input type="text" name="new_password" placeholder="Pass" style="width:72px; padding:6px 8px; border:1.5px solid var(--border); border-radius:6px; font-size:12px;" required>
                                        <button name="update_pass" class="btn-update">Update</button>
                                    </form>
                                </td>
                                <td>
                                    <button class="btn-update" style="background:var(--success);"
                                        onclick="openSalaryModal(
                                            '<?php echo $row['id']; ?>', 
                                            '<?php echo addslashes($row['name']); ?>', 
                                            '<?php echo addslashes($row['bank_name']); ?>', 
                                            '<?php echo $row['account_number']; ?>', 
                                            '<?php echo $row['ifsc_code']; ?>', 
                                            '<?php echo $row['upi_id']; ?>',
                                            '<?php echo addslashes($row['account_holder']); ?>'
                                        )">
                                        <i class="fas fa-wallet"></i> Pay
                                    </button>
                                </td>
                                <td>
                                    <div style="display:flex; gap:14px; align-items:center; justify-content:center;">
                                        <a href="javascript:void(0)" onclick="viewEmployee(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)" title="View Profile">
                                            <i class="fas fa-eye" style="color: var(--navy); cursor:pointer;"></i>
                                        </a>

                                        <a href="javascript:void(0)" onclick="openTenureModal('<?php echo $row['id']; ?>', '<?php echo addslashes($row['name']); ?>', '<?php echo $row['tenure_start']; ?>', '<?php echo $row['tenure_end']; ?>')" title="Manage Tenure">
                                            <i class="fas fa-calendar-alt" style="color: var(--orange); cursor:pointer;"></i>
                                        </a>

                                        <?php
                                        $locked_status = isset($row['is_locked']) ? (int)$row['is_locked'] : 0;
                                        if ($locked_status === 1): ?>
                                            <a href="toggle_lock.php?email=<?php echo urlencode($row['identifier']); ?>&status=0"
                                                title="Unlock Profile"
                                                onclick="return confirm('Enable editing for this employee?')">
                                                <i class="fas fa-lock" style="color: var(--danger); cursor:pointer;"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="toggle_lock.php?email=<?php echo urlencode($row['identifier']); ?>&status=1"
                                                title="Lock Profile"
                                                onclick="return confirm('Freeze this employee profile?')">
                                                <i class="fas fa-lock-open" style="color: var(--success); cursor:pointer;"></i>
                                            </a>
                                        <?php endif; ?>

                                        <a href="?delete=<?php echo $row['id']; ?>"
                                            title="Delete Employee"
                                            onclick="return confirm('Permanently remove this employee?')">
                                            <i class="fas fa-trash-alt" style="color:var(--danger);"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Salary Modal -->
    <div id="salaryModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.55); z-index:9999; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
        <div style="background:var(--card-bg); padding:30px; border-radius:20px; width:100%; max-width:450px; border-top: 4px solid var(--orange); box-shadow: var(--shadow-lg);">
            <h2 id="modalStaffName" style="color:var(--navy); margin:0 0 12px 0; font-size:18px; font-weight:800;">Process Salary</h2>
            <div id="bankPreview" style="background:#f8fafc; padding:14px; border-radius:12px; margin-bottom:20px; font-size:13px; color:var(--navy); border:1px solid var(--border);"></div>

            <form method="POST">
                <input type="hidden" name="user_id" id="modalUserId">
                <div style="margin-bottom:15px;">
                    <label style="display:block; font-size:11px; font-weight:700; color:var(--text-muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">For Month</label>
                    <input type="month" name="month_year" style="width:100%; padding:11px 14px; border:1.5px solid var(--border); border-radius:10px; font-family:inherit; background:#f8fafc;" required>
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block; font-size:11px; font-weight:700; color:var(--text-muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Amount (₹)</label>
                    <input type="number" name="amount" placeholder="0.00" step="0.01" style="width:100%; padding:11px 14px; border:1.5px solid var(--border); border-radius:10px; font-family:inherit; background:#f8fafc;" required>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="button" onclick="closeSalaryModal()" style="flex:1; padding:12px; background:#f1f5f9; color:var(--text-main); border:none; border-radius:10px; cursor:pointer; font-family:inherit; font-weight:700;">Cancel</button>
                    <button type="submit" name="process_salary" style="flex:1; padding:12px; background:var(--navy); color:white; border:none; border-radius:10px; cursor:pointer; font-family:inherit; font-weight:700;">Confirm Paid</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Staff Profile Modal -->
    <div id="viewModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); z-index:10000; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
        <div style="background:var(--card-bg); width:100%; max-width:560px; border-radius:20px; overflow:hidden; box-shadow:var(--shadow-lg); border:1px solid var(--border);">
            <div style="background:var(--navy); color:white; padding:18px 24px; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-size:16px; font-weight:700;"><i class="fas fa-user-shield" style="color:var(--orange); margin-right:8px;"></i> Detailed Staff Profile</h3>
                <i class="fas fa-times" onclick="closeViewModal()" style="cursor:pointer; font-size:16px;"></i>
            </div>
            <div style="padding:24px; max-height: 82vh; overflow-y: auto;">
                <div style="text-align:center; margin-bottom:20px;">
                    <img id="v_img" src="../uploads/profile_pics/default-avatar.png" style="width:90px; height:90px; border-radius:50%; object-fit:cover; border:3px solid var(--orange); padding:2px; background:white;">
                    <h2 id="v_name" style="margin:10px 0 4px 0; color:var(--navy); font-size:20px; font-weight:800;"></h2>
                    <span id="v_email" style="color:var(--text-muted); font-size:13.5px;"></span>
                </div>

                <div class="d-row"><span class="d-lbl">Joining Date</span><span id="v_join" class="d-val"></span></div>
                <div class="d-row"><span class="d-lbl">Date of Birth</span><span id="v_dob" class="d-val"></span></div>
                <div class="d-row"><span class="d-lbl">Aadhaar No</span><span id="v_aadhaar" class="d-val"></span></div>
                <div class="d-row"><span class="d-lbl">PAN No</span><span id="v_pan" class="d-val"></span></div>
                <div class="d-row"><span class="d-lbl">Emergency Contact</span><span id="v_emg" class="d-val"></span></div>
                <div class="d-row"><span class="d-lbl">Firm Reg No</span><span id="v_firm_reg" class="d-val"></span></div>
                <div class="d-row"><span class="d-lbl">Qualification</span><span id="v_prof_qual" class="d-val"></span></div>
                <div class="d-row" style="flex-direction:column; align-items:flex-start;">
                    <span class="d-lbl" style="margin-bottom:5px;">Residential Address</span>
                    <span id="v_addr" style="font-size:13px; line-height:1.5; color:var(--text-muted);"></span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 20px;">
                    <div style="background:#f8fafc; padding:12px; border-radius:12px; border:1px solid var(--border);">
                        <span class="d-lbl">Aadhaar Card</span>
                        <div style="margin-top:10px;">
                            <img id="v_aadhaar_img" src="" style="display:none;">
                            <div id="v_no_photo"></div>
                        </div>
                    </div>

                    <div style="background:#f8fafc; padding:12px; border-radius:12px; border:1px solid var(--border);">
                        <span class="d-lbl">PAN Card</span>
                        <div style="margin-top:10px;">
                            <img id="v_pan_img" src="" style="display:none;">
                            <div id="v_pan_txt"></div>
                        </div>
                    </div>

                    <div style="background:#f8fafc; padding:12px; border-radius:12px; border:1px solid var(--border);">
                        <span class="d-lbl">10th Marksheet</span>
                        <div style="margin-top:10px;">
                            <img id="v_m10_img" src="" style="display:none;">
                            <div id="v_m10_txt"></div>
                        </div>
                    </div>

                    <div style="background:#f8fafc; padding:12px; border-radius:12px; border:1px solid var(--border);">
                        <span class="d-lbl">12th Marksheet</span>
                        <div style="margin-top:10px;">
                            <img id="v_m12_img" src="" style="display:none;">
                            <div id="v_m12_txt"></div>
                        </div>
                    </div>
                </div>

                <div style="background:#f8fafc; padding:12px; border-radius:12px; border:1px solid var(--border); margin-top:14px;">
                    <span class="d-lbl">Professional Degree</span>
                    <div style="margin-top:10px;">
                        <img id="v_prof_img" src="" style="display:none;">
                        <div id="v_prof_txt"></div>
                    </div>
                </div>

                <button onclick="closeViewModal()" style="width:100%; margin-top:22px; padding:13px; border:none; background:#f1f5f9; color:var(--navy); border-radius:10px; font-family:inherit; font-weight:700; cursor:pointer; transition:0.2s;">Close Profile View</button>
            </div>
        </div>
    </div>

    <!-- Tenure Modal -->
    <div id="tenureModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.55); z-index:9999; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
        <div style="background:var(--card-bg); padding:30px; border-radius:20px; width:100%; max-width:450px; border-top: 4px solid var(--orange); box-shadow: var(--shadow-lg);">
            <h2 id="tenureModalName" style="color:var(--navy); margin-top:0; margin-bottom:6px; font-size:18px; font-weight:800;">Manage Tenure</h2>
            <p style="color:var(--text-muted); font-size:13px; margin-bottom:20px;">Set working dates using the calendar. If the end date is past today, the staff member automatically moves to the <strong>Tenure Over Employees</strong> table.</p>

            <form action="update-tenure.php" method="POST" id="tenureForm">
                <input type="hidden" name="user_id" id="tenureUserId">

                <div style="margin-bottom:15px;">
                    <label style="display:block; font-size:11px; font-weight:700; color:var(--text-muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Start Date</label>
                    <input type="date" name="tenure_start" id="tenureStart" style="width:100%; padding:11px 14px; border:1.5px solid var(--border); border-radius:10px; font-family:inherit; background:#f8fafc;" required>
                </div>

                <div style="margin-bottom:22px;">
                    <label style="display:block; font-size:11px; font-weight:700; color:var(--text-muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">End Date</label>
                    <input type="date" name="tenure_end" id="tenureEnd" style="width:100%; padding:11px 14px; border:1.5px solid var(--border); border-radius:10px; font-family:inherit; background:#f8fafc;">
                </div>

                <div style="display:flex; gap:10px;">
                    <button type="button" onclick="closeTenureModal()" style="flex:1; padding:12px; background:#f1f5f9; color:var(--text-main); border:none; border-radius:10px; cursor:pointer; font-family:inherit; font-weight:700;">Cancel</button>
                    <button type="submit" style="flex:1; padding:12px; background:var(--navy); color:white; border:none; border-radius:10px; cursor:pointer; font-family:inherit; font-weight:700;"><i class="fas fa-save"></i> Save Tenure</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Live Clock
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

        function toggleMenu(menuId, chevronId) {
            const menu = document.getElementById(menuId);
            const chevron = document.getElementById(chevronId);

            // Toggle the specific menu clicked
            menu.classList.toggle('show-menu');

            // Rotate the specific arrow clicked
            chevron.classList.toggle('rotate-chevron');

            // Optional: Close other menus when opening a new one
            const allMenus = document.querySelectorAll('.dropdown-content');
            const allChevrons = document.querySelectorAll('.fa-chevron-down');

            allMenus.forEach((m) => {
                if (m.id !== menuId) m.classList.remove('show-menu');
            });

            allChevrons.forEach((c) => {
                if (c.id !== chevronId) c.classList.remove('rotate-chevron');
            });
        }

        // Search Filter Logic
        function filterEmployees() {
            let input = document.getElementById('employeeSearch');
            let filter = input.value.toLowerCase();
            let tables = ['employeeTable', 'tenureOverTable'];

            tables.forEach(tableId => {
                let table = document.getElementById(tableId);
                if (!table) return;
                let tr = table.getElementsByTagName('tr');

                for (let i = 1; i < tr.length; i++) {
                    let td = tr[i].getElementsByTagName('td')[0];
                    if (td) {
                        let textValue = td.textContent || td.innerText;
                        if (textValue.toLowerCase().indexOf(filter) > -1) {
                            tr[i].style.display = "";
                        } else {
                            tr[i].style.display = "none";
                        }
                    }
                }
            });
        }

        function openSalaryModal(id, name, bank, acc, ifsc, upi, holder) {
            document.getElementById('salaryModal').style.display = 'flex';
            document.getElementById('modalUserId').value = id;
            document.getElementById('modalStaffName').innerText = "Pay: " + name;

            let html = "";
            if (bank && acc) {
                html = `<div style="margin-bottom:8px;"><b>Holder:</b> ${holder || 'N/A'}</div>
                        <div style="margin-bottom:4px;"><i class="fas fa-university"></i> <b>${bank}</b></div>
                        <div style="margin-bottom:4px;"><i class="fas fa-credit-card"></i> ${acc}</div>
                        <div><i class="fas fa-code"></i> IFSC: ${ifsc}</div>`;
            } else if (upi) {
                html = `<div><i class="fas fa-mobile-alt"></i> <b>UPI ID:</b> ${upi}</div>`;
            } else {
                html = `<span style="color:var(--danger); font-weight:bold;">No payment details found!</span>`;
            }
            document.getElementById('bankPreview').innerHTML = html;
        }

        function closeSalaryModal() {
            document.getElementById('salaryModal').style.display = 'none';
        }

        function viewEmployee(data) {
            document.getElementById('viewModal').style.display = 'flex';

            // 1. Basic Info & Profile Fields
            document.getElementById('v_name').innerText = data.name;
            document.getElementById('v_email').innerText = data.identifier;
            document.getElementById('v_join').innerText = data.date_of_joining || 'Not Set';
            document.getElementById('v_dob').innerText = data.dob || 'Not Set';
            document.getElementById('v_aadhaar').innerText = data.aadhaar_no || 'Not Set';
            document.getElementById('v_pan').innerText = data.pan_no || 'Not Set';
            document.getElementById('v_emg').innerText = data.emergency_contact || 'Not Set';
            document.getElementById('v_addr').innerText = data.address || 'Address not provided';
            document.getElementById('v_firm_reg').innerText = data.firm_reg_no || 'Not Set';
            document.getElementById('v_prof_qual').innerText = data.prof_qualification || 'Not Set';

            // 2. Profile Picture
            document.getElementById('v_img').src = data.profile_pic ? "../uploads/profile_pics/" + data.profile_pic : "../uploads/profile_pics/default-avatar.png";

            // 3. Document Processing Helper
            const processDoc = (fileName, imgId, textId, label) => {
                const imgTag = document.getElementById(imgId);
                const textDiv = document.getElementById(textId);

                if (fileName && fileName !== "") {
                    const filePath = "../uploads/documents/" + fileName;
                    const fileExt = fileName.split('.').pop().toLowerCase();

                    if (fileExt === 'pdf') {
                        // PDF Layout
                        imgTag.style.display = "none";
                        textDiv.style.display = "block";
                        textDiv.innerHTML = `
                    <div style="background: #fff; border: 1px solid #e2e8f0; padding: 10px; border-radius: 12px; text-align:center;">
                        <i class="fas fa-file-pdf" style="color: #ef4444; font-size: 24px; margin-bottom: 5px;"></i>
                        <p style="font-size: 11px; font-weight: bold; color: var(--navy); margin:0;">${label} (PDF)</p>
                        <a href="${filePath}" target="_blank" style="background: var(--navy); color: white; padding: 5px 12px; border-radius: 6px; text-decoration: none; font-size: 10px; display: inline-block; margin-top: 8px;">
                            <i class="fas fa-eye"></i> View PDF
                        </a>
                    </div>`;
                    } else {
                        // Image Layout with Click-to-Enlarge
                        imgTag.src = filePath;
                        imgTag.style.display = "block";
                        imgTag.style.width = "100%";
                        imgTag.style.height = "100px";
                        imgTag.style.objectFit = "cover";
                        imgTag.style.borderRadius = "8px";
                        imgTag.style.cursor = "zoom-in";

                        imgTag.onclick = function() {
                            window.open(filePath, '_blank');
                        };

                        textDiv.style.display = "block";
                        textDiv.innerHTML = `<p style="font-size:10px; color:#64748b; text-align:center; margin-top:5px; font-style:italic;">Click to enlarge</p>`;
                    }
                } else {
                    // Not Uploaded State
                    imgTag.style.display = "none";
                    textDiv.style.display = "block";
                    textDiv.innerHTML = `<div style="text-align:center; padding:10px; color:#94a3b8; font-size:12px;">No ${label}</div>`;
                }
            };

            // 4. Execute for all documents
            processDoc(data.aadhaar_photo, 'v_aadhaar_img', 'v_no_photo', 'Aadhaar');
            processDoc(data.pan_photo, 'v_pan_img', 'v_pan_txt', 'PAN Card');
            processDoc(data.marksheet_10, 'v_m10_img', 'v_m10_txt', '10th Marksheet');
            processDoc(data.marksheet_12, 'v_m12_img', 'v_m12_txt', '12th Marksheet');
            processDoc(data.prof_doc_path, 'v_prof_img', 'v_prof_txt', 'Professional Degree');
        }

        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        function openTenureModal(id, name, start, end) {
            document.getElementById('tenureModal').style.display = 'flex';
            document.getElementById('tenureUserId').value = id;
            document.getElementById('tenureModalName').innerText = "Tenure: " + name;
            document.getElementById('tenureStart').value = start || '';
            document.getElementById('tenureEnd').value = end || '';
        }

        function closeTenureModal() {
            document.getElementById('tenureModal').style.display = 'none';
        }

        // Close modal when clicking outside the box
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('tenureModal');
            if (e.target === modal) {
                closeTenureModal();
            }
        });
    </script>
</body>

</html>