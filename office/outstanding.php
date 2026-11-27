<?php
session_start();
include('../db.php');

// 1. SECURITY CHECK
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}


$adminName = $_SESSION['user']['name'] ?? 'Admin Executive';
$lookupId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
$lookupIdent = trim((string)($_SESSION['user']['identifier'] ?? ($_SESSION['user']['email'] ?? '')));

if (isset($conn) && $conn instanceof mysqli) {
    if ($lookupId > 0) {
        $stmtAdmin = $conn->prepare("SELECT `name` FROM `users` WHERE `id` = ? LIMIT 1");
        if ($stmtAdmin) {
            $stmtAdmin->bind_param("i", $lookupId);
            $stmtAdmin->execute();
            $resAdmin = $stmtAdmin->get_result();
            $rowAdmin = $resAdmin->fetch_assoc();
            if ($rowAdmin && !empty($rowAdmin['name'])) {
                $adminName = $rowAdmin['name'];
            }
            $stmtAdmin->close();
        }
    } elseif ($lookupIdent !== '') {
        $stmtAdmin = $conn->prepare("SELECT `name` FROM `users` WHERE `identifier` = ? LIMIT 1");
        if ($stmtAdmin) {
            $stmtAdmin->bind_param("s", $lookupIdent);
            $stmtAdmin->execute();
            $resAdmin = $stmtAdmin->get_result();
            $rowAdmin = $resAdmin->fetch_assoc();
            if ($rowAdmin && !empty($rowAdmin['name'])) {
                $adminName = $rowAdmin['name'];
            }
            $stmtAdmin->close();
        }
    }
}
$initials = strtoupper(substr(trim((string)$adminName), 0, 1) ?: 'A');

// 2. GET FILTERS (Yearly & Search)
// Default to the current financial year end
$selected_year = isset($_GET['filter_year']) ? mysqli_real_escape_string($conn, $_GET['filter_year']) : (date('m') > 3 ? date('Y') + 1 : date('Y'));
$search_pending = isset($_GET['search_pending']) ? mysqli_real_escape_string($conn, $_GET['search_pending']) : '';
$search_received = isset($_GET['search_received']) ? mysqli_real_escape_string($conn, $_GET['search_received']) : '';

// Financial Year Date Range Logic
$fy_start = ($selected_year - 1) . "-04-01";
$fy_end = $selected_year . "-03-31";

// Calculate Total Outstanding including all GST types
$stats_query = $conn->query("SELECT 
    SUM((amount + cgst_amount + sgst_amount + igst_amount) - paid_amount) as total_unpaid, 
    COUNT(id) as count_unpaid 
    FROM invoices 
    WHERE (paid_amount < (amount + cgst_amount + sgst_amount + igst_amount)) 
    AND created_at BETWEEN '$fy_start' AND '$fy_end'");

$stats = $stats_query->fetch_assoc();

$paid_stats_query = $conn->query("SELECT SUM(amount_paid) as total_received FROM receipts WHERE created_at BETWEEN '$fy_start' AND '$fy_end'");
$paid_stats = $paid_stats_query->fetch_assoc();
$total_received = $paid_stats['total_received'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outstanding &amp; Paid Ledger | KKA Admin</title>
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
            --border: #e2e8f0;
            --shadow-sm: 0 2px 8px rgba(11, 60, 116, 0.04);
            --shadow-md: 0 10px 25px -5px rgba(11, 60, 116, 0.08);
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
            padding: 0;
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

        .fy-banner {
            background: var(--card-bg);
            padding: 20px 24px;
            border-radius: 18px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            gap: 16px;
            flex-wrap: wrap;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 22px 24px;
            border-radius: 18px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .stat-card h3 {
            margin: 0;
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.7px;
            font-weight: 700;
        }

        .stat-card p {
            margin: 10px 0 0;
            font-size: 24px;
            font-weight: 800;
            color: var(--navy);
        }

        .table-card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            border: 1px solid var(--border);
            margin-bottom: 32px;
        }

        .table-header {
            padding: 18px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            gap: 12px;
            flex-wrap: wrap;
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
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 15px 16px;
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

        .aging-pill {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
        }

        .aging-new { background: #dcfce7; color: #166534; }
        .aging-mid { background: #fef9c3; color: #854d0e; }
        .aging-old { background: #fee2e2; color: #991b1b; }

        .btn-remind {
            color: var(--navy);
            background: #ffffff;
            border: 1px solid var(--border);
            padding: 7px 13px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
            transition: all 0.2s;
        }

        .btn-remind:hover {
            background: var(--navy);
            color: white;
            border-color: var(--navy);
        }

        .search-input {
            padding: 8px 16px;
            border-radius: 50px;
            border: 1.5px solid var(--border);
            background: #ffffff;
            font-family: inherit;
            font-size: 12.5px;
            color: var(--text-main);
            outline: none;
            transition: all 0.2s;
        }

        .search-input:focus {
            border-color: var(--navy);
            box-shadow: 0 0 0 3px rgba(11, 60, 116, 0.1);
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
    <div class="sidebar-avatar"><?php echo htmlspecialchars($initials); ?></div>
    <div class="online-status"></div>
</div>
<div class="profile-info">
    <strong><?php echo htmlspecialchars($adminName); ?></strong>
    <span>System Administrator</span>
</div>
        </div>

        <div class="sidebar-nav-title">Main Menu</div>
        <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="admin-profile.php"><i class="fas fa-user-cog"></i> Admin Profile</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn active" onclick="toggleMenu('billingMenu', 'billChev')">
                <i class="fas fa-file-invoice-dollar"></i> Billing
                <i class="fas fa-chevron-down rotate-chevron" id="billChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content show-menu" id="billingMenu">
                <a href="quotations.php">Quotations</a>
                <a href="invoices.php">Invoices</a>
                <a href="receipts.php">Receipts</a>
                <a href="outstanding.php" class="active">Outstanding</a>
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
    </aside>

    <!-- Main Workspace -->
    <div class="main">
        <header class="admin-top-bar">
            <div class="brand-section">
                <h1>Financial Overview</h1>
                <div class="welcome-text">Monitor pending invoice balances, aging buckets, and received receipts</div>
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
    <span class="header-user-name"><?php echo htmlspecialchars($adminName); ?></span>
    <a href="../logout.php" class="header-logout-icon" title="Logout">
        <i class="fas fa-power-off"></i>
    </a>
</div>
            </div>
        </header>

        <div class="content-body">
            <div class="fy-banner">
                <div>
                    <h2 style="margin:0; font-size:17px; font-weight:800; color:var(--navy);">Financial Year: <?php echo htmlspecialchars(($selected_year - 1) . "-" . substr($selected_year, -2)); ?></h2>
                    <p style="margin:3px 0 0; font-size:12px; color:var(--text-muted);">Showing analytics and records from <?php echo date('d M Y', strtotime($fy_start)); ?> to <?php echo date('d M Y', strtotime($fy_end)); ?></p>
                </div>

                <form method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap:wrap;">
                    <i class="fas fa-calendar-alt" style="color:var(--orange);"></i>
                    <select name="filter_year" onchange="this.form.submit()" style="padding: 9px 18px; border-radius: 50px; border: 1.5px solid var(--border); font-family:inherit; font-weight: 700; font-size:13px; color: var(--navy); cursor: pointer; background: #f8fafc; outline:none;">
                        <?php
                        $start_year = 2025;
                        $current_yr = date('m') > 3 ? date('Y') + 1 : date('Y');
                        for ($i = $current_yr; $i >= $start_year; $i--) {
                            $sel = ($selected_year == $i) ? 'selected' : '';
                            $display_fy = ($i - 1) . "-" . substr($i, -2);
                            echo "<option value='$i' $sel>$display_fy</option>";
                        }
                        ?>
                    </select>
                    <a href="outstanding.php" style="text-decoration:none; font-size:12px; font-weight:700; color:var(--danger); margin-left:6px;">Reset Filters</a>
                </form>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3><i class="fas fa-hand-holding-dollar"></i> Total Outstanding (FY <?php echo htmlspecialchars(substr($selected_year, -2)); ?>)</h3>
                    <p style="color:var(--danger);">&#8377;<?php echo number_format($stats['total_unpaid'] ?? 0, 2); ?></p>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-file-invoice"></i> Pending Bills</h3>
                    <p><?php echo intval($stats['count_unpaid'] ?? 0); ?></p>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-wallet"></i> Total Paid (Received)</h3>
                    <p style="color:#16a34a;">&#8377;<?php echo number_format($total_received, 2); ?></p>
                </div>
            </div>

            <div class="table-card">
                <div class="table-header" style="background: #fff1f2;">
                    <h3 style="margin:0; color:#991b1b; font-size:13.5px; font-weight:800;"><i class="fas fa-exclamation-triangle"></i> Pending Payments</h3>
                    <form method="GET">
                        <input type="hidden" name="filter_year" value="<?php echo htmlspecialchars($selected_year); ?>">
                        <input type="text" name="search_pending" value="<?php echo htmlspecialchars($search_pending); ?>" class="search-input" placeholder="Search Client or Invoice...">
                    </form>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Invoice No</th>
                            <th>Company Name</th>
                            <th>Issue Date</th>
                            <th>Aging</th>
                            <th>Balance Due</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $p_sql = "SELECT i.*, cp.company_name, 
                                  (i.amount + i.cgst_amount + i.sgst_amount + i.igst_amount) as grand_total,
                                  ((i.amount + i.cgst_amount + i.sgst_amount + i.igst_amount) - i.paid_amount) as balance_due, 
                                  DATEDIFF(NOW(), i.created_at) as days_old 
                                  FROM invoices i 
                                  LEFT JOIN client_profiles cp ON i.client_id = cp.client_id 
                                  WHERE i.paid_amount < (i.amount + i.cgst_amount + i.sgst_amount + i.igst_amount) 
                                  AND i.created_at BETWEEN '$fy_start' AND '$fy_end'";

                        if ($search_pending) {
                            $p_sql .= " AND (cp.company_name LIKE '%$search_pending%' OR i.invoice_no LIKE '%$search_pending%')";
                        }

                        $p_sql .= " ORDER BY days_old DESC";
                        $result = $conn->query($p_sql);

                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $age = $row['days_old'];
                                $class = ($age > 15) ? "aging-old" : (($age > 7) ? "aging-mid" : "aging-new");

                                $tax_details = ($row['igst_amount'] > 0) ? "IGST: ₹" . $row['igst_amount'] : "CGST+SGST: ₹" . ($row['cgst_amount'] + $row['sgst_amount']);

                                echo "<tr>
                                        <td>
                                            <div style='font-weight:700; color:var(--navy);'>#" . htmlspecialchars($row['invoice_no']) . "</div>
                                            <span style='font-size:10px; font-weight:600; color:var(--text-muted);'>" . htmlspecialchars($row['tax_type']) . "</span>
                                        </td>
                                        <td>" . htmlspecialchars($row['company_name'] ?? 'N/A') . "</td>
                                        <td>" . date('d M Y', strtotime($row['created_at'])) . "</td>
                                        <td><span class='aging-pill $class'>" . intval($age) . " Days Old</span></td>
                                        <td>
                                            <div style='font-weight:800; color:var(--danger);'>&#8377;" . number_format($row['balance_due'], 2) . "</div>
                                            <div style='font-size:11px; color:var(--text-muted);' title='" . htmlspecialchars($tax_details, ENT_QUOTES) . "'>
                                                Total: &#8377;" . number_format($row['grand_total'], 2) . "
                                            </div>
                                        </td>
                                        <td><a href='invoices.php' class='btn-remind'>Record Payment</a></td>
                                    </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding:30px; color:var(--text-muted);'>No pending records found for this period.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="table-card">
                <div class="table-header" style="background: #f0fdf4;">
                    <h3 style="margin:0; color:#166534; font-size:13.5px; font-weight:800;"><i class="fas fa-check-circle"></i> Payments Received (FY <?php echo htmlspecialchars(($selected_year - 1) . "-" . substr($selected_year, -2)); ?>)</h3>
                    <form method="GET">
                        <input type="hidden" name="filter_year" value="<?php echo htmlspecialchars($selected_year); ?>">
                        <input type="text" name="search_received" value="<?php echo htmlspecialchars($search_received); ?>" class="search-input" placeholder="Search Receipt/Client...">
                    </form>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Receipt No</th>
                            <th>Invoice No</th>
                            <th>Company Name</th>
                            <th>Paid On</th>
                            <th>Amount Received</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $r_sql = "SELECT r.*, cp.company_name 
                                  FROM receipts r 
                                  LEFT JOIN client_profiles cp ON r.client_id = cp.client_id 
                                  WHERE r.created_at BETWEEN '$fy_start' AND '$fy_end'";

                        if ($search_received) {
                            $r_sql .= " AND (cp.company_name LIKE '%$search_received%' OR r.receipt_no LIKE '%$search_received%' OR r.invoice_no LIKE '%$search_received%')";
                        }

                        $r_sql .= " ORDER BY r.created_at DESC";
                        $paid_res = $conn->query($r_sql);

                        if ($paid_res && $paid_res->num_rows > 0) {
                            while ($p = $paid_res->fetch_assoc()) {
                                echo "<tr>
                                        <td style='font-weight:700; color:var(--navy);'>" . htmlspecialchars($p['receipt_no']) . "</td>
                                        <td>#" . htmlspecialchars($p['invoice_no']) . "</td>
                                        <td>" . htmlspecialchars($p['company_name'] ?? 'N/A') . "</td>
                                        <td>" . date('d M Y', strtotime($p['created_at'])) . "</td>
                                        <td style='font-weight:800; color:#16a34a;'>&#8377;" . number_format($p['amount_paid'], 2) . "</td>
                                    </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; padding:30px; color:var(--text-muted);'>No payment history for this selection.</td></tr>";
                        }
                        ?>
                    </tbody>
                    <tfoot style="background: #f8fafc; border-top: 2px solid var(--border);">
                        <tr>
                            <td colspan="4" style="text-align:right; font-weight:800; color:var(--navy); padding:15px;">TOTAL RECEIVED (FY):</td>
                            <td style="font-weight:800; color:#16a34a; font-size:15px; padding:15px;">&#8377;<?php echo number_format($total_received, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
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
            if (chevron) chevron.classList.toggle('rotate-chevron');

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
    </script>
</body>
</html>