<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Attendance | KKA Admin</title>
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

        .toolbar-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn-action {
            border: none;
            padding: 10px 18px;
            border-radius: 10px;
            font-family: inherit;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-action:hover {
            transform: translateY(-1px);
        }

        .attendance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        /* Dynamic Card Borders */
        .staff-card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            border-top: 4px solid #cbd5e1;
            position: relative;
            cursor: pointer;
            transition: all 0.2s;
        }

        .staff-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .staff-card.online {
            border-top-color: #22c55e;
        }

        /* Dynamic Badge Colors */
        .status-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .badge-online {
            background: #dcfce7;
            color: #166534;
        }

        .badge-offline {
            background: #f1f5f9;
            color: var(--text-muted);
        }

        .avatar {
            width: 54px;
            height: 54px;
            background: #f1f5f9;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: var(--navy);
            margin-bottom: 14px;
        }

        .staff-name {
            font-size: 16.5px;
            font-weight: 800;
            color: var(--navy);
            margin: 0;
        }

        .staff-email {
            font-size: 13px;
            color: var(--text-muted);
            margin: 4px 0 14px 0;
        }

        .log-box {
            background: #f8fafc;
            border: 1px solid var(--border);
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 12.5px;
            color: var(--text-main);
        }

        .ledger-card {
            background: var(--card-bg);
            padding: 28px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8fafc;
            color: var(--navy);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            text-align: left;
            padding: 14px 15px;
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
                <a href="Client-Revenue.php">Client Revenue</a>
                <a href="attendance.php" class="active">Attendance</a>
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
                <h1>Staff Attendance</h1>
                <div class="welcome-text">Live status for <?php echo date('d M, Y'); ?></div>
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
            <div class="toolbar-row">
                <div style="font-size:13.5px; color:var(--text-muted); font-weight:600;">
                    Click any employee card to inspect attendance history
                </div>
                <div style="display: flex; gap: 10px; flex-wrap:wrap;">
                    <button type="button" class="btn-action" onclick="exportFullHistory()" style="background: #6366f1; color: white;">
                        <i class="fas fa-database"></i> Export Excel
                    </button>

                    <button type="button" class="btn-action" onclick="window.location.reload();" style="background: var(--navy); color: white;">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>

            <div class="attendance-grid">
                <?php
                $res = $conn->query("SELECT * FROM users WHERE role='office' ORDER BY name ASC");
                while ($staff = $res->fetch_assoc()) {
                    $email = $staff['identifier'];
                    $check = $conn->query("SELECT login_time, logout_time FROM attendance WHERE email='$email' AND log_date='$today' LIMIT 1");
                    $is_online = ($check->num_rows > 0);
                    $att_data = $check->fetch_assoc();
                ?>
                    <div class="staff-card <?php echo $is_online ? 'online' : ''; ?>"
                        onclick="openAttendanceModal('<?php echo htmlspecialchars($email, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($staff['name'], ENT_QUOTES); ?>')">

                        <span class="status-badge <?php echo $is_online ? 'badge-online' : 'badge-offline'; ?>">
                            <?php echo $is_online ? 'PRESENT' : 'ABSENT'; ?>
                        </span>

                        <div class="avatar"><i class="fas fa-user-tie"></i></div>
                        <h3 class="staff-name"><?php echo htmlspecialchars($staff['name']); ?></h3>
                        <p class="staff-email"><?php echo htmlspecialchars($email); ?></p>

                        <div class="log-box">
                            <?php if ($is_online): ?>
                                <i class="far fa-clock" style="color: #16a34a;"></i>
                                In: <strong><?php echo date('h:i A', strtotime($att_data['login_time'])); ?></strong>
                            <?php else: ?>
                                <i class="fas fa-exclamation-circle" style="color: #94a3b8;"></i> Not checked in.
                            <?php endif; ?>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 28px;">
                <div class="ledger-card">
                    <h2 style="font-size: 16px; font-weight:800; color: var(--navy); margin:0 0 18px 0;">Daily Presence Ratio</h2>
                    <div style="max-width: 280px; margin: auto;">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
            </div>

            <div id="attModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.5); backdrop-filter: blur(5px);">
                <div style="background:white; margin: 5% auto; padding: 28px; width: 90%; max-width: 720px; border-radius: 20px; box-shadow: var(--shadow-lg); border:1px solid var(--border);">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid var(--border); padding-bottom: 15px; gap:12px;">
                        <h2 id="modalTitle" style="margin:0; color: var(--navy); font-size:20px; font-weight:800;">Employee History</h2>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <button type="button" id="exportIndividualBtn" style="background: #16a34a; color:white; border:none; padding:8px 14px; border-radius:8px; cursor:pointer; font-family:inherit; font-weight:700; font-size:12.5px;">
                                <i class="fas fa-file-excel"></i> Export CSV
                            </button>
                            <button type="button" onclick="closeModal()" style="background:#f1f5f9; border:none; width:34px; height:34px; border-radius:50%; font-size: 20px; cursor:pointer; color:var(--text-muted);">&times;</button>
                        </div>
                    </div>
                    <div id="modalBody" style="margin-top:20px; max-height: 400px; overflow-y: auto;">
                    </div>
                </div>
            </div>

            <div class="ledger-card" style="margin-top: 28px;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom: 18px;">
                    <div style="width:4px; height:20px; background:var(--orange); border-radius:10px;"></div>
                    <h2 style="margin:0; font-size: 16px; font-weight:800; color: var(--navy);">Today's Attendance Ledger</h2>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>EMPLOYEE NAME</th>
                            <th>CHECK-IN</th>
                            <th>CHECK-OUT</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetching employees who checked in today, including logout_time
                        $ledger_sql = "SELECT a.*, u.name 
                                       FROM attendance a 
                                       JOIN users u ON a.email = u.identifier 
                                       WHERE a.log_date = '$today' 
                                       ORDER BY a.login_time DESC";
                        $ledger_res = $conn->query($ledger_sql);

                        if ($ledger_res->num_rows > 0):
                            while ($row = $ledger_res->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($row['name']) ?></div>
                                        <div style="font-size: 11.5px; color: var(--text-muted);"><?= htmlspecialchars($row['email']) ?></div>
                                    </td>
                                    <td>
                                        <span style="background: #ecfdf5; color: #059669; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                            <i class="far fa-clock"></i> <?= date('h:i A', strtotime($row['login_time'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['logout_time']): ?>
                                            <span style="background: #fef2f2; color: #dc2626; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                                <i class="fas fa-sign-out-alt"></i> <?= date('h:i A', strtotime($row['logout_time'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 12px; font-style: italic;">Active Session</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$row['logout_time']): ?>
                                            <span style="color: #16a34a; font-weight: 700; font-size: 12px;">
                                                <i class="fas fa-circle" style="font-size: 8px;"></i> WORKING
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-weight: 700; font-size: 12px;">
                                                <i class="fas fa-check-double"></i> COMPLETED
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <i class="fas fa-user-clock" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                                    No check-ins recorded for today.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        // 1. GLOBAL VARIABLE - Declared once at the top
        let currentEmployeeEmail = '';

        // 2. MODAL LOGIC
        function openAttendanceModal(email, name) {
            currentEmployeeEmail = email;
            document.getElementById('attModal').style.display = 'block';
            document.getElementById('modalTitle').innerText = name + "'s Attendance History";
            document.getElementById('modalBody').innerHTML = '<p style="text-align:center; color:var(--text-muted);">Loading history...</p>';

            fetch('fetch_employee_history.php?email=' + encodeURIComponent(email))
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modalBody').innerHTML = data;
                });
        }

        function closeModal() {
            document.getElementById('attModal').style.display = 'none';
        }

        // 3. INDIVIDUAL EXPORT LOGIC
        document.getElementById('exportIndividualBtn').addEventListener('click', async () => {
            if (!currentEmployeeEmail) {
                alert("No employee selected.");
                return;
            }

            try {
                console.log("Requesting for email:", currentEmployeeEmail);
                const response = await fetch('fetch_individual_attendance.php?email=' + encodeURIComponent(currentEmployeeEmail));
                const data = await response.json();

                console.log("Response received:", data);

                if (data.length > 0) {
                    const ws = XLSX.utils.json_to_sheet(data);
                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, "Attendance");
                    XLSX.writeFile(wb, `Attendance_${currentEmployeeEmail}.xlsx`);
                } else {
                    alert("No records found for this user in the database.");
                }
            } catch (err) {
                console.error("Error:", err);
                alert("An error occurred during export. Check console.");
            }
        });

        // 4. NAVIGATION / UI LOGIC
        function toggleMenu(menuId, chevronId) {
            const menu = document.getElementById(menuId);
            const chevron = document.getElementById(chevronId);
            menu.classList.toggle('show-menu');
            if (chevron) chevron.classList.toggle('rotate-chevron');
        }

        // 5. MASTER EXPORT LOGIC
        async function exportFullHistory() {
            const btn = event.currentTarget;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            btn.disabled = true;

            try {
                const response = await fetch('fetch_all_attendance.php');
                const data = await response.json();

                if (data.length === 0) {
                    alert("No records found to export.");
                    return;
                }
                const ws = XLSX.utils.json_to_sheet(data);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Attendance History");
                XLSX.writeFile(wb, `Attendance_Master_Report_${new Date().toISOString().slice(0, 10)}.xlsx`);
            } catch (error) {
                console.error("Export failed:", error);
                alert("Failed to generate Excel file.");
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        // 6. Chart Initialization
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent'],
                datasets: [{
                    data: [
                        <?php
                        $presentCount = $conn->query("SELECT count(*) as total FROM attendance WHERE log_date='$today'")->fetch_assoc()['total'];
                        $totalStaff = $conn->query("SELECT count(*) as total FROM users WHERE role='office'")->fetch_assoc()['total'];
                        $absentCount = ($totalStaff - $presentCount) > 0 ? ($totalStaff - $presentCount) : 0;
                        echo "$presentCount, $absentCount";
                        ?>
                    ],
                    backgroundColor: ['#22c55e', '#e2e8f0'],
                    borderWidth: 0
                }]
            },
            options: { cutout: '70%', plugins: { legend: { position: 'bottom' } } }
        });
    </script>
</body>

</html>