<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Query focusing strictly on Firm, Owner, Contact, and Unpaid breakdown
$query = "SELECT 
            cp.client_id,
            cp.company_name, 
            cp.owner_name, 
            cp.business_email, 
            cp.phone,
            COALESCE(SUM(i.paid_amount), 0) as total_paid,
            SUM(CASE WHEN i.invoice_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND i.status != 'Paid' 
                THEN (i.amount + i.cgst_amount + i.sgst_amount + i.igst_amount) - i.paid_amount ELSE 0 END) as previous_dues,
            SUM(CASE WHEN i.invoice_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND i.status != 'Paid' 
                THEN (i.amount + i.cgst_amount + i.sgst_amount + i.igst_amount) - i.paid_amount ELSE 0 END) as current_dues,
            /* Date-wise formatted string for the popup */
            GROUP_CONCAT(
                CASE WHEN i.status != 'Paid' 
                THEN CONCAT(
                    '<tr>',
                    '<td style=\"padding:12px 8px; border-bottom:1px solid #f1f5f9; color:#64748b;\">', DATE_FORMAT(i.invoice_date, '%d-%b-%y'), '</td>',
                    '<td style=\"padding:12px 8px; border-bottom:1px solid #f1f5f9; font-weight:bold; color:var(--navy);\">', i.invoice_no, '</td>',
                    '<td style=\"padding:12px 8px; border-bottom:1px solid #f1f5f9; color:#ef4444; text-align:right; font-weight:700;\">&#8377;', FORMAT((i.amount + i.cgst_amount + i.sgst_amount + i.igst_amount) - i.paid_amount, 2), '</td>',
                    '</tr>'
                ) 
                ELSE NULL END 
                ORDER BY i.invoice_date DESC
                SEPARATOR ''
            ) as unpaid_breakdown,
            SUM(i.amount + i.cgst_amount + i.sgst_amount + i.igst_amount) as grand_total_with_tax
          FROM client_profiles cp
          LEFT JOIN invoices i ON cp.client_id = i.client_id
          WHERE 1=1 ";
if ($search) {
    $query .= " AND (cp.company_name LIKE '%$search%' OR cp.owner_name LIKE '%$search%')";
}

$query .= " GROUP BY cp.client_id ORDER BY cp.company_name ASC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Ledger | KKA Admin</title>
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

        .search-container {
            display: flex;
            gap: 12px;
            align-items: center;
            background: var(--card-bg);
            padding: 12px 18px;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            max-width: 480px;
        }

        .search-container i {
            color: var(--text-muted);
            font-size: 14px;
        }

        .search-container input {
            flex: 1;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            background: #f8fafc;
            font-family: inherit;
            font-size: 13.5px;
            color: var(--text-main);
            outline: none;
            transition: all 0.2s;
        }

        .search-container input:focus {
            border-color: var(--navy);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(11, 60, 116, 0.1);
        }

        .search-container button {
            padding: 10px 20px;
            background: var(--navy);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            font-size: 13px;
            transition: all 0.2s;
        }

        .search-container button:hover {
            background: var(--navy-light);
        }

        .table-card {
            margin-top: 24px;
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8fafc;
            padding: 15px 20px;
            text-align: left;
            font-size: 11.5px;
            color: var(--navy);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            font-weight: 700;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 18px 20px;
            border-bottom: 1px solid var(--border);
            font-size: 13.5px;
            vertical-align: middle;
            color: var(--text-main);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover td {
            background-color: #f8fafc;
        }

        .paid-amt {
            color: #16a34a;
            font-weight: 700;
        }

        /* Full Screen Overlay */
        #popupOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: 99999;
            justify-content: center;
            align-items: center;
        }

        /* Modal Box */
        #popupContent {
            background: white;
            width: 90%;
            max-width: 550px;
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            padding: 30px;
            position: relative;
            animation: modalSlide 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes modalSlide {
            from {
                opacity: 0;
                transform: scale(0.92) translateY(16px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            border: none;
            background: #f1f5f9;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            color: var(--text-muted);
            transition: 0.2s;
        }

        .close-btn:hover {
            background: var(--danger-bg);
            color: var(--danger);
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
                <a href="revenue-analytics.php">Revenue Analytics</a>
                <a href="Client-Revenue.php" class="active">Client Revenue</a>
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
                <h1>Client Revenue Report</h1>
                <div class="welcome-text">Track billed amounts, collected payments, and pending invoice dues per firm</div>
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
            <form method="GET" class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search firm or owner..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Filter</button>
            </form>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Firm &amp; Contact</th>
                            <th>Total Billed (Inc. GST)</th>
                            <th>Total Paid</th>
                            <th>Balance Due</th>
                            <th>Unpaid Invoice Breakdown</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()):
                                $total_balance = $row['previous_dues'] + $row['current_dues'];
                            ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700; color:var(--navy);"><?php echo htmlspecialchars($row['company_name']); ?></div>
                                        <div style="font-size:11.5px; color:var(--text-muted); margin-top:3px;">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($row['owner_name']); ?> |
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['phone']); ?>
                                        </div>
                                    </td>

                                    <td style="font-weight:600;">&#8377;<?php echo number_format($row['grand_total_with_tax'], 2); ?></td>

                                    <td class="paid-amt">&#8377;<?php echo number_format($row['total_paid'], 2); ?></td>

                                    <td style="font-weight:700; color: <?php echo ($total_balance > 0) ? '#ef4444' : '#16a34a'; ?>;">
                                        &#8377;<?php echo number_format($total_balance, 2); ?>
                                    </td>

                                    <td>
                                        <?php if ($total_balance > 0): ?>
                                            <div style="display:none;" id="data-<?php echo htmlspecialchars($row['client_id']); ?>">
                                                <div style="margin-bottom: 15px;">
                                                    <h2 style="margin:0; color:var(--navy); font-size:21px; font-weight:800;"><?php echo htmlspecialchars($row['company_name']); ?></h2>
                                                    <p style="color:var(--text-muted); margin:5px 0 0 0; font-size:13px;"><i class="fas fa-file-invoice"></i> Pending Invoice Details</p>
                                                </div>

                                                <table style="width: 100%; border-collapse: collapse;">
                                                    <thead>
                                                        <tr style="text-align:left; color:var(--text-muted); font-size:11px; text-transform:uppercase; border-bottom:2px solid #f1f5f9;">
                                                            <th style="padding:10px; background:transparent;">Date</th>
                                                            <th style="padding:10px; background:transparent;">Invoice No</th>
                                                            <th style="padding:10px; background:transparent; text-align:right;">Balance Due</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php echo $row['unpaid_breakdown']; ?>
                                                    </tbody>
                                                </table>

                                                <div style="margin-top:20px; padding-top:15px; border-top:2px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                                                    <span style="font-weight:700; color:var(--text-muted);">Grand Total Outstanding:</span>
                                                    <span style="font-size:22px; font-weight:800; color:#ef4444;">&#8377;<?php echo number_format($total_balance, 2); ?></span>
                                                </div>
                                            </div>

                                            <button type="button" onclick="showBreakdown('<?php echo htmlspecialchars($row['client_id'], ENT_QUOTES); ?>')"
                                                style="background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; padding: 9px 14px; border-radius: 10px; font-family:inherit; font-weight: 700; cursor: pointer; transition: 0.2s; font-size:12px;">
                                                <i class="fas fa-calendar-alt"></i> View Date-wise Details
                                            </button>
                                        <?php else: ?>
                                            <div style="color:#16a34a; font-weight:700; background:#f0fdf4; border:1px solid #bbf7d0; padding:7px 12px; border-radius:8px; display:inline-block; font-size:12px;">
                                                <i class="fas fa-check-circle"></i> Fully Paid
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; color:var(--text-muted); padding:28px;">No records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="popupOverlay" onclick="closeBreakdown()">
        <div id="popupContent" onclick="event.stopPropagation()">
            <button type="button" class="close-btn" onclick="closeBreakdown()"><i class="fas fa-times"></i></button>
            <div id="popupData"></div>
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

        function showBreakdown(clientId) {
            const htmlData = document.getElementById('data-' + clientId).innerHTML;
            document.getElementById('popupData').innerHTML = htmlData;

            document.getElementById('popupOverlay').style.display = 'flex';

            // Blur everything EXCEPT the popup
            document.querySelector('.main').style.filter = 'blur(10px)';
            document.querySelector('.sidebar').style.filter = 'blur(10px)';
            document.body.style.overflow = 'hidden';
        }

        function closeBreakdown() {
            document.getElementById('popupOverlay').style.display = 'none';

            // Remove blur
            document.querySelector('.main').style.filter = 'none';
            document.querySelector('.sidebar').style.filter = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeBreakdown();
        });
    </script>
</body>

</html>