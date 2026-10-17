<?php
session_start();
include('../db.php');

// 1. Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

// Ensure Schedule Table Exists
$conn->query("CREATE TABLE IF NOT EXISTS admin_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    task_date DATE NOT NULL,
    task_time TIME NOT NULL,
    category VARCHAR(50) DEFAULT 'General',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Calculate total revenue from receipts
$revenue_res = $conn->query("SELECT SUM(amount_paid) as total FROM receipts");
$revenue_data = $revenue_res ? $revenue_res->fetch_assoc() : null;
$total_revenue = $revenue_data['total'] ?? 0;

// 2. Create User Logic (Handles Staff Onboarding)
if (isset($_POST['create'])) {
    $n = mysqli_real_escape_string($conn, $_POST['name']);
    $id = mysqli_real_escape_string($conn, $_POST['email']);
    $p = $_POST['pass'];
    $r = 'office'; // Hardcode role to office/staff

    $sql = "INSERT INTO users (name, identifier, password, role) VALUES ('$n', '$id', '$p', '$r')";
    if ($conn->query($sql)) {
        echo "<script>alert('Staff Account Created Successfully! ID: $id'); window.location='admin-dashboard.php';</script>";
    }
}

// 3. Add Schedule Logic
if (isset($_POST['add_schedule'])) {
    $title = mysqli_real_escape_string($conn, $_POST['task_title']);
    $task_date = mysqli_real_escape_string($conn, $_POST['task_date']);
    $task_time = mysqli_real_escape_string($conn, $_POST['task_time']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);

    $conn->query("INSERT INTO admin_schedules (title, task_date, task_time, category) VALUES ('$title', '$task_date', '$task_time', '$category')");
    echo "<script>window.location='admin-dashboard.php';</script>";
}

// 4. Delete Schedule Logic
if (isset($_GET['del_schedule'])) {
    $del_id = (int)$_GET['del_schedule'];
    $conn->query("DELETE FROM admin_schedules WHERE id = $del_id");
    echo "<script>window.location='admin-dashboard.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KKA Admin | Executive Suite</title>
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

        * { box-sizing: border-box; }

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

        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-thumb { background-color: var(--orange); border-radius: 10px; }

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
            position: relative;
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

        .profile-info strong { display: block; font-size: 13px; color: #ffffff; font-weight: 600; }
        .profile-info span { font-size: 11px; color: var(--orange); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

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

        .sidebar a:hover, .sidebar a.active {
            background: var(--sidebar-hover);
            color: white;
            border-left: 3px solid var(--orange);
            padding-left: 17px;
        }

        .sidebar a.active { background: rgba(255, 140, 0, 0.12); color: #ffffff; font-weight: 600; }

        .dropdown-content {
            display: none;
            background: rgba(0, 0, 0, 0.15);
            margin: 2px 0 6px 10px;
            border-radius: 10px;
            padding: 4px 0;
            border-left: 2px solid rgba(255, 140, 0, 0.3);
        }

        .dropdown-content a { padding: 9px 14px; font-size: 12.5px; margin-bottom: 0; border-radius: 0; }
        .dropdown-content a:hover { border-left: none; background: rgba(255, 255, 255, 0.05); color: var(--orange); }

        .show-menu { display: block !important; }
        .rotate-chevron { transform: rotate(180deg); }

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

        .brand-section h1 { margin: 0; font-size: 20px; color: var(--text-main); font-weight: 800; }
        .welcome-text { font-size: 12.5px; color: var(--text-muted); margin-top: 1px; font-weight: 500; }
        .welcome-text span { color: var(--navy); font-weight: 700; }

        .header-right-group { display: flex; align-items: center; gap: 16px; }

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

        .clock-icon i { color: var(--orange); font-size: 16px; }
        .clock-display { display: flex; flex-direction: column; }
        .clock-display span { font-size: 9.5px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; }
        .clock-display strong { font-size: 12.5px; color: var(--text-main); font-weight: 700; }

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

        .header-user-name { font-size: 12.5px; font-weight: 700; color: var(--navy); }

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

        .header-logout-icon:hover { background: var(--danger); color: white; transform: scale(1.05); }

        .content-body { padding: 32px 40px; flex: 1; }

        /* ALERT CARD */
        .alert-card {
            background: linear-gradient(135deg, #fff5f5, #fee2e2);
            border: 1px solid #fca5a5;
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.08);
        }

        /* METRICS CARDS GRID */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat {
            background: var(--card-bg);
            padding: 22px 24px;
            border-radius: 18px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--navy);
        }

        .stat.revenue-card::before { background: var(--orange); }

        .stat:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: #cbd5e1; }

        .stat-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }

        .stat-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(11, 60, 116, 0.08);
            color: var(--navy);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .revenue-card .stat-icon-box { background: var(--orange-glow); color: var(--orange); }

        .stat h3 { margin: 0; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; font-weight: 700; }
        .stat h2 { margin: 0; font-size: 26px; color: var(--text-main); font-weight: 800; letter-spacing: -0.5px; }

        /* ================= EXECUTIVE DASHBOARD WIDGETS ================= */
        .dashboard-two-col {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .widget-card {
            background: var(--card-bg);
            padding: 26px;
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .widget-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .widget-header h3 {
            margin: 0;
            font-size: 16px;
            color: var(--navy);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .widget-header i { color: var(--orange); }

        /* System Health Widget */
        .system-pulse-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            padding: 16px 20px;
            border-radius: 14px;
            border: 1px solid var(--border);
            margin-bottom: 20px;
        }

        .pulse-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 700;
            color: var(--success);
        }

        .pulse-dot {
            width: 10px;
            height: 10px;
            background-color: var(--success);
            border-radius: 50%;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-green 1.8s infinite;
        }

        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Quick Actions Grid */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .action-btn {
            background: #f8fafc;
            border: 1.5px solid var(--border);
            padding: 14px;
            border-radius: 12px;
            text-decoration: none;
            color: var(--text-main);
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .action-btn i {
            color: var(--navy);
            font-size: 15px;
            background: rgba(11, 60, 116, 0.08);
            padding: 8px;
            border-radius: 8px;
        }

        .action-btn:hover {
            border-color: var(--orange);
            background: #fff;
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
            color: var(--orange);
        }

        .action-btn:hover i { background: var(--orange-glow); color: var(--orange); }

        /* Schedule Manager List */
        .schedule-list {
            max-height: 280px;
            overflow-y: auto;
            padding-right: 5px;
        }

        .schedule-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 14px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 10px;
        }

        .schedule-date-box {
            background: var(--navy);
            color: white;
            padding: 6px 10px;
            border-radius: 8px;
            text-align: center;
            min-width: 75px;
        }

        .schedule-date-box small { display: block; font-size: 9px; text-transform: uppercase; opacity: 0.8; }
        .schedule-date-box strong { font-size: 12px; font-weight: 800; }

        .schedule-details {
            flex: 1;
            margin: 0 15px;
        }

        .schedule-details strong { display: block; font-size: 13px; color: var(--text-main); }
        .schedule-details span { font-size: 11px; color: var(--text-muted); font-weight: 600; }

        .cat-tag {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 12px;
            background: var(--orange-glow);
            color: var(--orange);
            font-weight: 700;
            display: inline-block;
            margin-top: 2px;
        }

        .del-sched-btn {
            color: var(--danger);
            background: var(--danger-bg);
            border: none;
            padding: 6px 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            transition: 0.2s;
        }

        .del-sched-btn:hover { background: var(--danger); color: white; }

        /* Inline Form for Adding Schedule */
        .add-schedule-form {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1fr auto;
            gap: 8px;
            margin-bottom: 16px;
            background: #f1f5f9;
            padding: 12px;
            border-radius: 12px;
        }

        .add-schedule-form input, .add-schedule-form select {
            padding: 8px 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 12px;
            outline: none;
            background: #fff;
        }

        .add-schedule-form button {
            background: var(--navy);
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
        }

        .add-schedule-form button:hover { background: var(--orange); }

        /* ================= MODAL STYLING ================= */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-card {
            background: var(--card-bg);
            width: 100%;
            max-width: 480px;
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            position: relative;
            animation: modalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes modalPop {
            0% { transform: scale(0.95); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .modal-header h3 {
            margin: 0;
            font-size: 17px;
            color: var(--navy);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close-modal-btn {
            background: #f1f5f9;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .close-modal-btn:hover { background: var(--danger-bg); color: var(--danger); }

        .form-group { margin-bottom: 16px; }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modal-card input {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 13.5px;
            outline: none;
            background: #f8fafc;
            font-family: inherit;
            transition: all 0.2s;
        }

        .modal-card input:focus {
            border-color: var(--navy);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(11, 60, 116, 0.1);
        }

        button.submit-btn {
            background: var(--navy);
            color: white;
            border: none;
            padding: 13px;
            width: 100%;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(11, 60, 116, 0.2);
            margin-top: 8px;
        }

        button.submit-btn:hover { background: var(--navy-light); transform: translateY(-1px); }

        @media (max-width: 1200px) {
            .stat-grid { grid-template-columns: repeat(2, 1fr); }
            .dashboard-two-col { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="brand-header">
            <div class="brand-logo"><i class="fas fa-shield-alt"></i></div>
            <h2>Karunesh Kumar & Associates</h2>
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
        <a href="admin-dashboard.php" class="active"><i class="fas fa-chart-pie"></i> Dashboard</a>

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

        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
        <a href="../logout.php" style="margin-top:auto; color:#fca5a5;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Workspace -->
    <div class="main">
        <header class="admin-top-bar">
            <div class="brand-section">
                <h1>Firm Overview</h1>
                <div class="welcome-text">Welcome back, <span>Administrator</span></div>
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
            <?php
            // Password Reset Alert
            $resets = $conn->query("SELECT id, identifier FROM users WHERE reset_requested = 1");
            if ($resets && $resets->num_rows > 0):
                $request = $resets->fetch_assoc();
            ?>
                <div class="alert-card">
                    <div style="display:flex; align-items:center; gap:14px;">
                        <i class="fas fa-exclamation-triangle" style="color:var(--danger); font-size:22px;"></i>
                        <div>
                            <strong style="color:#991b1b; font-size:14px;">Action Required</strong>
                            <p style="margin:2px 0 0; color:#7f1d1d; font-size:13px;">
                                Staff member <b><?php echo htmlspecialchars($request['identifier']); ?></b> requested a password reset.
                            </p>
                        </div>
                    </div>
                    <a href="manage-employees.php?reset_email=<?php echo urlencode($request['identifier']); ?>" 
                       style="color:white; background:var(--danger); padding:8px 16px; border-radius:8px; text-decoration:none; font-size:13px; font-weight:700;">
                        Handle Now
                    </a>
                </div>
            <?php endif; ?>

            <!-- Metric Cards -->
            <div class="stat-grid">
                <a href="manage-clients.php" style="text-decoration: none;">
                    <div class="stat">
                        <div class="stat-header">
                            <h3>Total Clients</h3>
                            <div class="stat-icon-box"><i class="fas fa-user-friends"></i></div>
                        </div>
                        <h2><?php echo $conn->query("SELECT id FROM users WHERE role='client'")->num_rows; ?></h2>
                    </div>
                </a>

                <a href="manage-employees.php" style="text-decoration: none;">
                    <div class="stat">
                        <div class="stat-header">
                            <h3>Total Staff</h3>
                            <div class="stat-icon-box"><i class="fas fa-user-tie"></i></div>
                        </div>
                        <h2><?php echo $conn->query("SELECT id FROM users WHERE role='office'")->num_rows; ?></h2>
                    </div>
                </a>

                <a href="assign-work.php" style="text-decoration: none;">
                    <div class="stat">
                        <div class="stat-header">
                            <h3>Open Requests</h3>
                            <div class="stat-icon-box"><i class="fas fa-tasks"></i></div>
                        </div>
                        <h2><?php echo $conn->query("SELECT id FROM service_requests WHERE status='Pending'")->num_rows; ?></h2>
                    </div>
                </a>

                <a href="receipts.php" style="text-decoration: none;">
                    <div class="stat revenue-card">
                        <div class="stat-header">
                            <h3>Total Revenue</h3>
                            <div class="stat-icon-box"><i class="fas fa-wallet"></i></div>
                        </div>
                        <h2>₹<?php echo number_format($total_revenue, 2); ?></h2>
                    </div>
                </a>
            </div>

            <!-- DASHBOARD WIDGETS SECTION 1 -->
            <div class="dashboard-two-col">
                <!-- Left Panel: Quick Actions & System Health -->
                <div class="widget-card">
                    <div class="widget-header">
                        <h3><i class="fas fa-server"></i> System Health & Quick Actions</h3>
                        <span style="font-size:11px; color:var(--text-muted); font-weight:700;">Live Feed</span>
                    </div>

                    <div class="system-pulse-container">
                        <div>
                            <strong style="font-size:13px; display:block;">Database Connection</strong>
                            <span style="font-size:11px; color:var(--text-muted);">MySQL Engine Active</span>
                        </div>
                        <div class="pulse-badge">
                            <div class="pulse-dot"></div> Operational
                        </div>
                    </div>

                    <div style="margin-top:10px;">
                        <h4 style="margin:0 0 12px; font-size:13px; color:var(--navy);">Management Shortcuts</h4>
                        <div class="actions-grid">
                            <button class="action-btn" onclick="openOnboardModal()">
                                <i class="fas fa-user-plus"></i> Onboard Staff
                            </button>
                            <a href="manage-clients.php" class="action-btn">
                                <i class="fas fa-users-cog"></i> Manage Clients
                            </a>
                            <a href="assign-work.php" class="action-btn">
                                <i class="fas fa-tasks"></i> Review Work
                            </a>
                            <a href="receipts.php" class="action-btn">
                                <i class="fas fa-receipt"></i> Receipts
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Right Panel: Key Compliance Dates & Date/Time Schedule Manager -->
                <div class="widget-card">
                    <div class="widget-header">
                        <h3><i class="fas fa-calendar-alt"></i> Compliance Schedule & Deadlines</h3>
                        <span style="font-size:11px; color:var(--text-muted); font-weight:700;">Date & Time Wise</span>
                    </div>

                    <!-- Form to add new Date & Time Schedule -->
                    <form method="POST" class="add-schedule-form">
                        <input type="text" name="task_title" placeholder="Task/Deadline Name" required>
                        <input type="date" name="task_date" required>
                        <input type="time" name="task_time" required>
                        <select name="category" required>
    <option value="Urgent">  Urgent</option>
    <option value="In Progress"> In Progress</option>
    <option value="Pending"> Pending </option>
    <option value="Completed"> Completed</option>
</select>
                        <button type="submit" name="add_schedule"><i class="fas fa-plus"></i> Add</button>
                    </form>

                    <!-- Date & Time Wise Schedule List -->
                    <div class="schedule-list">
                        <?php
                        $schedules = $conn->query("SELECT * FROM admin_schedules ORDER BY task_date ASC, task_time ASC");
                        if ($schedules && $schedules->num_rows > 0):
                            while ($sch = $schedules->fetch_assoc()):
                        ?>
                            <div class="schedule-item">
                                <div class="schedule-date-box">
                                    <small><?php echo date('M Y', strtotime($sch['task_date'])); ?></small>
                                    <strong><?php echo date('d D', strtotime($sch['task_date'])); ?></strong>
                                </div>
                                <div class="schedule-details">
                                    <strong><?php echo htmlspecialchars($sch['title']); ?></strong>
                                    <span><i class="far fa-clock"></i> <?php echo date('h:i A', strtotime($sch['task_time'])); ?></span>
                                    <span class="cat-tag"><?php echo htmlspecialchars($sch['category']); ?></span>
                                </div>
                                <a href="admin-dashboard.php?del_schedule=<?php echo $sch['id']; ?>" class="del-sched-btn" onclick="return confirm('Delete this scheduled item?');" title="Delete Task">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <div style="text-align:center; padding:20px; color:var(--text-muted); font-size:12px;">
                                No schedules added yet. Add your compliance deadlines above!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- DASHBOARD WIDGETS SECTION 2: WEB INBOX PREVIEW -->
            <div class="widget-card" style="margin-bottom:20px;">
                <div class="widget-header">
                    <h3><i class="fas fa-inbox"></i> Web Contact Inbox Overview</h3>
                    <a href="all-messages.php" style="font-size:12px; font-weight:700; color:var(--navy); text-decoration:none;">View Web Messages →</a>
                </div>

                <div style="display:flex; align-items:center; justify-content:space-between; background:#f8fafc; padding:16px 20px; border-radius:14px; border:1px solid var(--border);">
                    <div style="display:flex; align-items:center; gap:14px;">
                        <i class="fas fa-envelope-open-text" style="font-size:24px; color:var(--orange);"></i>
                        <div>
                            <strong style="font-size:14px; color:var(--text-main);">Client Consultations & Inquiries</strong>
                            <p style="margin:2px 0 0; font-size:12px; color:var(--text-muted);">Check messages received from firm website visitors directly in your inbox.</p>
                        </div>
                    </div>
                    <a href="all-messages.php" class="action-btn" style="background:#fff;">Open Inbox</a>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal for Onboarding Staff -->
    <div class="modal-overlay" id="onboardModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus" style="color: var(--orange);"></i> Onboard New Staff</h3>
                <button class="close-modal-btn" onclick="closeOnboardModal()"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="Enter full name" required>
                </div>

                <div class="form-group">
                    <label>Email / Identifier</label>
                    <input type="email" name="email" placeholder="staff.name@kka.com" required>
                </div>

                <div class="form-group">
                    <label>Initial Password</label>
                    <input type="text" name="pass" placeholder="Set temporary password" required>
                </div>

                <button type="submit" name="create" class="submit-btn">Generate Staff Profile</button>
            </form>
        </div>
    </div>

    <script>
        function toggleMenu(menuId, chevronId) {
            const menu = document.getElementById(menuId);
            const chevron = document.getElementById(chevronId);

            menu.classList.toggle('show-menu');
            chevron.classList.toggle('rotate-chevron');

            document.querySelectorAll('.dropdown-content').forEach((m) => {
                if (m.id !== menuId) m.classList.remove('show-menu');
            });
            
            document.querySelectorAll('.fa-chevron-down').forEach((c) => {
                if (c.id !== chevronId) c.classList.remove('rotate-chevron');
            });
        }

        function openOnboardModal() {
            document.getElementById('onboardModal').style.display = 'flex';
        }

        function closeOnboardModal() {
            document.getElementById('onboardModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('onboardModal');
            if (event.target === modal) {
                closeOnboardModal();
            }
        }

        function updateClock() {
            const now = new Date();
            const dateString = now.toLocaleDateString('en-GB', { weekday: 'long', year: 'numeric', month: 'short', day: '2-digit' });
            const timeString = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });

            document.getElementById('liveDate').textContent = dateString;
            document.getElementById('liveTime').textContent = timeString;
        }

        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>