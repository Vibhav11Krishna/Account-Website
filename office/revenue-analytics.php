<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

$month = isset($_GET['m']) ? $_GET['m'] : date('m');
$year = isset($_GET['y']) ? $_GET['y'] : date('Y');

// --- 1. KPI BOX LOGIC ---
$week_query = "SELECT SUM(amount_paid) as total FROM receipts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$week_earning = $conn->query($week_query)->fetch_assoc()['total'] ?? 0;

$month_query = "SELECT SUM(amount_paid) as total FROM receipts WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = '$year'";
$month_earning = $conn->query($month_query)->fetch_assoc()['total'] ?? 0;

$year_query = "SELECT SUM(amount_paid) as total FROM receipts WHERE YEAR(created_at) = '$year'";
$year_earning = $conn->query($year_query)->fetch_assoc()['total'] ?? 0;

// --- 2. CHART DATA ---
$monthly_query = "SELECT MONTH(created_at) as mon, SUM(amount_paid) as total 
                  FROM receipts WHERE YEAR(created_at) = '$year' 
                  GROUP BY MONTH(created_at) ORDER BY mon ASC";
$monthly_res = $conn->query($monthly_query);
$month_labels = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
$month_values = array_fill(0, 12, 0);
while ($rm = $monthly_res->fetch_assoc()) {
    $month_values[$rm['mon'] - 1] = (float)$rm['total'];
}

// --- 3. CALENDAR DATA ---
$daily_query = "SELECT DATE(created_at) as day, SUM(amount_paid) as total 
                FROM receipts WHERE MONTH(created_at) = '$month' AND YEAR(created_at) = '$year' 
                GROUP BY DATE(created_at)";
$daily_res = $conn->query($daily_query);
$revenue_days = [];
while ($r = $daily_res->fetch_assoc()) {
    $revenue_days[$r['day']] = $r['total'];
}

// --- 4. TABLE DATA UPDATED (FETCHING FROM CLIENT_PROFILES) ---
$table_query = "SELECT r.*, cp.company_name 
                FROM receipts r 
                LEFT JOIN client_profiles cp ON r.client_id = cp.client_id 
                ORDER BY r.created_at DESC LIMIT 50";
$table_res = $conn->query($table_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Analytics | KKA Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            background: rgba(255, 255, 255, 0.88);
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
            color: var(--text-main);
            font-weight: 800;
        }

        .welcome-text {
            font-size: 12.5px;
            color: var(--text-muted);
            margin-top: 1px;
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
            background: #ffffff;
            padding: 7px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .clock-icon i {
            color: var(--orange);
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
            width: 100%;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 22px 24px;
            border-radius: 18px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            border-left: 4px solid var(--navy);
        }

        .stat-card h4 {
            margin: 0;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-muted);
            font-weight: 700;
        }

        .stat-card .amt {
            font-size: 24px;
            font-weight: 800;
            color: var(--navy);
            margin: 8px 0 6px 0;
        }

        .stat-card.orange {
            border-left-color: var(--orange);
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .card {
            background: var(--card-bg);
            padding: 26px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .calendar-nav a {
            color: var(--navy);
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .calendar-nav a:hover {
            background: #f1f5f9;
            color: var(--orange);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-top: 16px;
        }

        .day {
            aspect-ratio: 1;
            border-radius: 10px;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            background: #f8fafc;
            transition: all 0.2s;
        }

        .has-revenue {
            background: #f0fdf4;
            border-color: #86efac;
            color: #166534;
            font-weight: 700;
            cursor: pointer;
        }

        .has-revenue:hover {
            background: #dcfce7;
            transform: scale(1.06);
        }

        .table-card {
            background: var(--card-bg);
            padding: 26px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th {
            text-align: left;
            padding: 14px 15px;
            background: #f8fafc;
            color: var(--navy);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 14px 15px;
            border-bottom: 1px solid var(--border);
            font-size: 13.5px;
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .amt-tag {
            font-weight: 800;
            color: var(--navy);
        }

        @media (max-width: 1100px) {
            .stats-row,
            .analytics-grid {
                grid-template-columns: 1fr;
            }
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
            <a href="javascript:void(0)" class="dropdown-btn active" onclick="toggleMenu('reportsMenu', 'repChev')">
                <i class="fas fa-file-contract"></i> Reports
                <i class="fas fa-chevron-down rotate-chevron" id="repChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content show-menu" id="reportsMenu">
                <a href="dsc-register.php">DSC Register</a>
                <a href="service-report.php">Service Report</a>
                <a href="revenue-analytics.php" class="active">Revenue Analytics</a>
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
    </aside>

    <!-- Main Workspace -->
    <div class="main">
        <header class="admin-top-bar">
            <div class="brand-section">
                <h1>Revenue Analytics</h1>
                <div class="welcome-text">Comprehensive financial performance tracking</div>
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
            <div class="stats-row">
                <div class="stat-card">
                    <h4>Week Earnings</h4>
                    <div class="amt">&#8377;<?= number_format($week_earning, 2) ?></div>
                    <small style="color:#16a34a; font-weight:600;"><i class="fas fa-arrow-up"></i> Last 7 days</small>
                </div>
                <div class="stat-card orange">
                    <h4>Month Earnings</h4>
                    <div class="amt">&#8377;<?= number_format($month_earning, 2) ?></div>
                    <small style="color:var(--orange); font-weight:600;">Current Month</small>
                </div>
                <div class="stat-card">
                    <h4>Year Earnings</h4>
                    <div class="amt">&#8377;<?= number_format($year_earning, 2) ?></div>
                    <small style="color:var(--navy); font-weight:600;">Annual Total (<?= htmlspecialchars($year) ?>)</small>
                </div>
            </div>

            <div class="analytics-grid">
                <div class="card">
                    <h3 style="margin:0 0 20px 0; font-size:16px; font-weight:800; color:var(--navy);">Monthly Comparison</h3>
                    <div style="height: 300px;"><canvas id="revenueBarChart"></canvas></div>
                </div>

                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
                        <h3 style="margin:0; font-size:16px; font-weight:800; color:var(--navy);">Daily Tracking</h3>
                        <div class="calendar-nav" style="font-size:13px;">
                            <a href="?m=<?= ($month == 1 ? 12 : $month - 1) ?>&y=<?= ($month == 1 ? $year - 1 : $year) ?>"><i class="fas fa-chevron-left"></i></a>
                            <span style="margin:0 8px; font-weight:700; color:var(--text-main);"><?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?></span>
                            <a href="?m=<?= ($month == 12 ? 1 : $month + 1) ?>&y=<?= ($month == 12 ? $year + 1 : $year) ?>"><i class="fas fa-chevron-right"></i></a>
                        </div>
                    </div>
                    <div class="calendar-grid">
                        <?php
                        $h = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
                        foreach ($h as $day_h) echo "<div style='text-align:center; font-size:11px; font-weight:800; color:var(--text-muted);'>$day_h</div>";
                        $f = date('w', mktime(0, 0, 0, $month, 1, $year));
                        $t = date('t', mktime(0, 0, 0, $month, 1, $year));
                        for ($i = 0; $i < $f; $i++) echo "<div></div>";
                        for ($d = 1; $d <= $t; $d++) {
                            $curr = sprintf("%04d-%02d-%02d", $year, $month, $d);
                            $amt = $revenue_days[$curr] ?? 0;
                            $cls = $amt > 0 ? 'has-revenue' : '';
                            $click = $amt > 0 ? "onclick=\"showDayDetails('$curr')\"" : "";
                            echo "<div class='day $cls' $click>$d</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="table-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; gap:16px; flex-wrap:wrap;">
                    <h3 style="margin:0; font-size:16px; font-weight:800; color:var(--navy);">Detailed Revenue Ledger</h3>

                    <!-- Search Bar -->
                    <div style="position: relative; width: 320px; max-width: 100%;">
                        <i class="fas fa-search" style="position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                        <input type="text" id="receiptSearch" onkeyup="filterTable()" placeholder="Search Company or Receipt..."
                            style="width: 100%; padding: 10px 14px 10px 38px; border-radius: 10px; border: 1.5px solid var(--border); background:#f8fafc; outline: none; font-family:inherit; font-size: 13.5px; color:var(--text-main);">
                    </div>
                </div>

                <table id="receiptTable">
                    <thead>
                        <tr>
                            <th>TRANSACTION DATE</th>
                            <th>RECEIPT NUMBER</th>
                            <th>COMPANY NAME</th>
                            <th style="text-align:right;">NET AMOUNT PAID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($table_res->num_rows > 0): ?>
                            <?php while ($row = $table_res->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('d M, Y', strtotime($row['created_at'])) ?></td>
                                    <td style="font-weight:700; color:var(--orange);"><?= htmlspecialchars($row['receipt_no']) ?></td>
                                    <td><?= htmlspecialchars($row['company_name'] ?? 'N/A') ?></td>
                                    <td style="text-align:right;" class="amt-tag">&#8377;<?= number_format($row['amount_paid'], 2) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; color:var(--text-muted); padding:28px;">No recent transactions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="revenueModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.55); backdrop-filter:blur(4px); align-items:center; justify-content:center;">
        <div style="background:white; padding:28px; border-radius:20px; width:90%; max-width:500px; position:relative; box-shadow: var(--shadow-lg); border:1px solid var(--border);">
            <span onclick="closeModal()" style="position:absolute; right:20px; top:14px; font-size:24px; cursor:pointer; color:#94a3b8;">&times;</span>
            <div id="modalContent">Loading...</div>
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
            if (!menu) return;
            menu.classList.toggle('show-menu');
            if (chevronId) {
                const chevron = document.getElementById(chevronId);
                if (chevron) chevron.classList.toggle('rotate-chevron');
            }
        }

        // --- AJAX Modal Logic ---
        function showDayDetails(date) {
            const modal = document.getElementById('revenueModal');
            const content = document.getElementById('modalContent');
            modal.style.display = 'flex';
            content.innerHTML = '<div style="text-align:center; padding:20px; color:var(--text-muted);"><i class="fas fa-spinner fa-spin"></i> Fetching details...</div>';

            fetch('get_day_revenue.php?date=' + date)
                .then(res => res.text())
                .then(html => { content.innerHTML = html; })
                .catch(() => { content.innerHTML = "Error loading data."; });
        }

        function closeModal() {
            document.getElementById('revenueModal').style.display = 'none';
        }

        window.onclick = function(e) {
            if (e.target == document.getElementById('revenueModal')) closeModal();
        }

        // --- Chart Logic ---
        const ctx = document.getElementById('revenueBarChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($month_labels) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode($month_values) ?>,
                    backgroundColor: '#0b3c74',
                    borderRadius: 8,
                    hoverBackgroundColor: '#ff8c00'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: ' + new Intl.NumberFormat('en-IN', {
                                    style: 'currency',
                                    currency: 'INR'
                                }).format(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        suggestedMax: 10000,
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            callback: function(value) {
                                if (value >= 100000) return '₹' + (value / 100000).toFixed(1) + ' L';
                                if (value >= 1000) return '₹' + (value / 1000) + 'k';
                                return '₹' + value;
                            },
                            font: { weight: '600' },
                            color: '#64748b'
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b' }
                    }
                }
            }
        });

        function filterTable() {
            let input = document.getElementById("receiptSearch");
            let filter = input.value.toUpperCase();
            let table = document.getElementById("receiptTable");
            let tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                let receiptCol = tr[i].getElementsByTagName("td")[1];
                let companyCol = tr[i].getElementsByTagName("td")[2];

                if (receiptCol || companyCol) {
                    let txtValue1 = receiptCol ? (receiptCol.textContent || receiptCol.innerText) : '';
                    let txtValue2 = companyCol ? (companyCol.textContent || companyCol.innerText) : '';

                    if (txtValue1.toUpperCase().indexOf(filter) > -1 ||
                        txtValue2.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    </script>
</body>
</html>