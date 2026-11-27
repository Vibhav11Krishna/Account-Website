<?php
session_start();
include('../db.php');

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

// Fetch all unique clients
$all_clients = $conn->query("SELECT * FROM client_profiles ORDER BY company_name ASC");
$unique_clients = [];
$services_map_counts = []; // For the sidebar badges
$total_client_count = $all_clients ? $all_clients->num_rows : 0;

if ($all_clients) {
    while ($row = $all_clients->fetch_assoc()) {
        // Store unique client
        $unique_clients[] = $row;

        // Process services for sidebar counts
        if (!empty($row['task_asked'])) {
            $tasks = array_map('trim', explode(',', $row['task_asked']));
            $tasks = array_unique($tasks); // Avoid double counting if user entered same service twice
            foreach ($tasks as $t) {
                if (!empty($t)) {
                    $services_map_counts[$t] = ($services_map_counts[$t] ?? 0) + 1;
                }
            }
        }
    }
}
ksort($services_map_counts);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Explorer | KKA Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --navy-dark: #051b34;
            --navy: #0b3c74;
            --navy-light: #155297;
            --blue-light: #eff6ff;
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
            width: calc(100% - 280px);
            min-height: 100vh;
            display: flex;
        }

        /* Secondary Sidebar (Service List) */
        .service-nav-panel {
            width: 270px;
            background: var(--card-bg);
            border-right: 1px solid var(--border);
            height: 100vh;
            overflow-y: auto;
            position: sticky;
            top: 0;
            flex-shrink: 0;
        }

        .service-nav-header {
            padding: 22px 20px;
            border-bottom: 1px solid var(--border);
        }

        .service-nav-header b {
            color: var(--navy);
            font-size: 18px;
            font-weight: 800;
        }

        .service-item {
            padding: 13px 20px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: 0.2s;
            font-size: 13.5px;
            font-weight: 500;
            color: var(--text-main);
        }

        .service-item:hover {
            background: #f8fafc;
            color: var(--navy);
        }

        .service-item.active {
            background: var(--blue-light);
            color: var(--navy);
            border-right: 3px solid var(--orange);
            font-weight: 700;
        }

        .badge {
            background: #f1f5f9;
            color: var(--text-muted);
            padding: 3px 9px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }

        .service-item.active .badge {
            background: var(--orange-glow);
            color: var(--orange);
        }

        /* --- CONTENT PANEL --- */
        .display-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg);
            min-width: 0;
        }

        .admin-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(12px);
            padding: 14px 32px;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 90;
            box-shadow: var(--shadow-sm);
            gap: 16px;
        }

        .brand-section h1 {
            margin: 0;
            font-size: 19px;
            color: var(--text-main);
            font-weight: 800;
        }

        .breadcrumb {
            font-size: 12px;
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
            padding: 28px 32px;
            flex: 1;
        }

        /* STRUCTURED TABLE DESIGN */
        .table-card {
            background: var(--card-bg);
            border-radius: 18px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: #f8fafc;
            text-align: left;
            padding: 14px 20px;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--navy);
            font-weight: 700;
            border-bottom: 1px solid var(--border);
        }

        tbody tr {
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border);
            font-size: 13.5px;
            vertical-align: middle;
        }

        .client-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .client-img {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            object-fit: cover;
            background: #f1f5f9;
            border: 1px solid var(--border);
            flex-shrink: 0;
        }

        .id-pill {
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
            color: var(--navy);
        }

        .btn-profile {
            color: var(--navy);
            background: var(--blue-light);
            padding: 7px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 11.5px;
            transition: all 0.2s;
            border: 1px solid transparent;
            display: inline-block;
        }

        .btn-profile:hover {
            background: var(--navy);
            color: white;
        }

        .hidden {
            display: none;
        }

        @media (max-width: 1200px) {
            .main {
                flex-direction: column;
            }
            .service-nav-panel {
                width: 100%;
                height: auto;
                position: static;
                border-right: none;
                border-bottom: 1px solid var(--border);
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
            <a href="javascript:void(0)" class="dropdown-btn active" onclick="toggleMenu('clientMenu', 'clientChev')">
                <i class="fas fa-users"></i> Manage Clients
                <i class="fas fa-chevron-down rotate-chevron" id="clientChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content show-menu" id="clientMenu">
                <a href="manage-clients.php">Clients</a>
                <a href="client-groups.php">Client Groups</a>
                <a href="client-services.php" class="active">Services</a>
                <a href="client-upload-docs.php">Clients Uploads</a>
            </div>
        </div>

        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
        <a href="../logout.php" style="margin-top:auto; color:#fca5a5;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <div class="main">
        <div class="service-nav-panel">
            <div class="service-nav-header">
                <b>Master Services</b><br>
                <small style="color: var(--text-muted);">Filter clients by task</small>
            </div>
            <div class="service-item active" onclick="filterService('all', this)">
                <span>View All Clients</span>
                <span class="badge"><?php echo $total_client_count; ?></span>
            </div>
            <?php foreach ($services_map_counts as $name => $count): ?>
                <div class="service-item" onclick="filterService('<?php echo md5($name); ?>', this)">
                    <span><?php echo htmlspecialchars($name); ?></span>
                    <span class="badge"><?php echo $count; ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="display-panel">
            <header class="admin-top-bar">
                <div class="brand-section">
                    <h1 id="viewTitle">All Clients</h1>
                    <div class="breadcrumb">Firm Management &nbsp;/&nbsp; Services &nbsp;/&nbsp; <span id="breadcrumb-text" style="color: var(--orange); font-weight: 700;">All</span></div>
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
                <div class="table-card">
                    <table>
                        <thead>
                            <tr>
                                <th>Client / Company</th>
                                <th>ID</th>
                                <th>Owner</th>
                                <th>Phone Number</th>
                                <th style="text-align:right;">Profile</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unique_clients as $c):
                                // Generate MD5 hashes for all services this client has
                                $client_services = [];
                                if (!empty($c['task_asked'])) {
                                    $tasks = array_map('trim', explode(',', $c['task_asked']));
                                    foreach ($tasks as $t) {
                                        $client_services[] = md5($t);
                                    }
                                }
                                $service_attr = implode(' ', $client_services);
                            ?>
                                <tr class="client-row" data-sids="<?php echo htmlspecialchars($service_attr); ?>">
                                    <td>
                                        <div class="client-info">
                                            <img src="../uploads/client_pics/<?php echo htmlspecialchars($c['profile_pic']); ?>" class="client-img" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($c['company_name']); ?>&background=random&size=128'">
                                            <b style="color: var(--navy);"><?php echo htmlspecialchars($c['company_name']); ?></b>
                                        </div>
                                    </td>
                                    <td><span class="id-pill"><?php echo htmlspecialchars($c['client_id']); ?></span></td>
                                    <td><?php echo htmlspecialchars($c['owner_name']); ?></td>
                                    <td style="color: var(--text-muted);">
                                        <i class="fas fa-phone-alt" style="font-size:11px; margin-right:5px;"></i> <?php echo htmlspecialchars($c['phone']); ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="client-profile.php?id=<?php echo urlencode($c['client_id']); ?>" class="btn-profile">VIEW</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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

        function toggleMenu(menuId, chevronId) {
            const menu = document.getElementById(menuId);
            const chevron = document.getElementById(chevronId);

            menu.classList.toggle('show-menu');
            chevron.classList.toggle('rotate-chevron');

            const allMenus = document.querySelectorAll('.dropdown-content');
            const allChevrons = document.querySelectorAll('.fa-chevron-down');

            allMenus.forEach((m) => {
                if (m.id !== menuId) m.classList.remove('show-menu');
            });

            allChevrons.forEach((c) => {
                if (c.id !== chevronId) c.classList.remove('rotate-chevron');
            });
        }

        function filterService(sid, el) {
            // UI Updates
            document.querySelectorAll('.service-item').forEach(i => i.classList.remove('active'));
            el.classList.add('active');

            const serviceName = el.querySelector('span').innerText;
            document.getElementById('viewTitle').innerText = serviceName;
            document.getElementById('breadcrumb-text').innerText = serviceName;

            // Table Filtering
            document.querySelectorAll('.client-row').forEach(row => {
                const rowServices = row.getAttribute('data-sids').split(' ');

                if (sid === 'all' || rowServices.includes(sid)) {
                    row.classList.remove('hidden');
                } else {
                    row.classList.add('hidden');
                }
            });
        }

        window.onload = function () {
            const urlParams = new URLSearchParams(window.location.search);
            const targetService = urlParams.get('target');

            if (targetService) {
                const items = document.querySelectorAll('.service-item span');
                let found = false;

                items.forEach(span => {
                    if (span.innerText.trim() === targetService) {
                        span.parentElement.click();
                        found = true;
                        span.parentElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });

                if (!found) {
                    console.log("Service not found in sidebar list.");
                }
            }
        };
    </script>

</body>

</html>