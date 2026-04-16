<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

// Fetch all clients and group them by tasks/services
$all_clients = $conn->query("SELECT * FROM client_profiles ORDER BY company_name ASC");
$services_map = [];
$total_client_count = 0;

while ($row = $all_clients->fetch_assoc()) {
    $total_client_count++;
    if (!empty($row['task_asked'])) {
        $tasks = array_map('trim', explode(',', $row['task_asked']));
        foreach ($tasks as $t) {
            if (!empty($t)) {
                $services_map[$t][] = $row;
            }
        }
    }
}
ksort($services_map);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Services Explorer | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --navy: #0b3c74;
            --blue-light: #eff6ff;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
            --border: #e2e8f0;
            --text-main: #334155;
            --text-muted: #64748b;
        }

        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
        }

        /* --- SIDEBAR --- */
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
            border-right: 4px solid var(--orange);
            z-index: 100;
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

        .dropdown-content {
            display: none;
            background: rgba(0, 0, 0, 0.15);
            margin: 0 10px;
            border-radius: 10px;
            padding-left: 10px;
        }

        .show-menu {
            display: block !important;
        }

        /* --- LAYOUT --- */
        .main {
            margin-left: 280px;
            width: calc(100% - 260px);
            min-height: 100vh;
            display: flex;
        }

        /* Secondary Sidebar (Service List) */
        .service-nav-panel {
            width: 280px;
            background: white;
            border-right: 1px solid var(--border);
            height: 100vh;
            overflow-y: auto;
            position: sticky;
            top: 0;
        }

        .service-nav-header {
            padding: 30px 20px;
            border-bottom: 1px solid var(--border);
        }

        .service-item {
            padding: 15px 20px;
            cursor: pointer;
            border-bottom: 1px solid #f8fafc;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: 0.2s;
            font-size: 15px;
            font-weight: 500;
            color: var(--text-main);
        }

        .service-item:hover {
            background: #f1f5f9;
            color: var(--navy);
        }

        .service-item.active {
            background: var(--blue-light);
            color: var(--navy);
            border-right: 4px solid var(--navy);
            font-weight: 700;
        }

        .badge {
            background: #e2e8f0;
            color: #475569;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }

        /* --- CONTENT PANEL --- */
        .display-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg);
        }

        /* NEW CLEAN WHITE HEADER */
        .content-header {
            background: white;
            padding: 25px 40px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-header h1 {
            margin: 0;
            font-size: 22px;
            color: var(--navy);
            font-weight: 700;
        }

        .breadcrumb {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .content-body {
            padding: 30px 40px;
        }

        /* STRUCTURED TABLE DESIGN */
        .table-card {
            background: white;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: #f8fafc;
            text-align: left;
            padding: 16px 20px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            border-bottom: 2px solid var(--border);
        }

        tbody tr {
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: #fafbfc;
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }

        .client-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .client-img {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            object-fit: cover;
            background: #f1f5f9;
            border: 1px solid var(--border);
        }

        .id-pill {
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            color: var(--navy);
        }

        .btn-profile {
            color: var(--navy);
            background: var(--blue-light);
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 700;
            font-size: 11px;
            transition: 0.3s;
            border: 1px solid transparent;
        }

        .btn-profile:hover {
            background: var(--navy);
            color: white;
        }

        .hidden {
            display: none;
        }
    </style>
</head>

<body>

    <div class="sidebar">
    <h2>Karunesh Kumar & Associates Admin</h2>
    <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>

    <div class="dropdown-container">
        <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('billingMenu', 'billChev')">
            <i class="fas fa-file-invoice-dollar"></i> Billing
            <i class="fas fa-chevron-down" id="billChev" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
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
            <i class="fas fa-chevron-down" id="vaultChev" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
        </a>
        <div class="dropdown-content" id="vaultMenu">
            <a href="admin-review.php"></i> Quality Control</a>
            <a href="Master-Vault.php"></i> Services</a>
        </div>
    </div>

    <div class="dropdown-container">
        <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('reportsMenu', 'repChev')">
            <i class="fas fa-file-contract"></i> Reports
            <i class="fas fa-chevron-down" id="repChev" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
        </a>
        <div class="dropdown-content" id="reportsMenu">
           <a href="dsc-register.php"></i> DSC Register</a>
           <a href="service-report.php"></i> Service Report</a>
           <a href="revenue-analytics.php"></i> Revenue Analytics</a>
           <a href="Client-Revenue.php"></i>Client Revenue</a>
           <a href="attendance.php"></i> Attendance</a>
        </div>
    </div>

    <a href="assign-work.php"><i class="fas fa-tasks"></i> Assign Work</a>
    <div class="dropdown-container">
    <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('clientMenu', 'clientChev')">
        <i class="fas fa-users"></i> Manage Clients
        <i class="fas fa-chevron-down" id="clientChev" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
    </a>
    <div class="dropdown-content" id="clientMenu">
        <a href="manage-clients.php">Clients</a>
        <a href="client-groups.php">Client Groups</a>
        <a href="client-services.php" class="active">Services</a>
    </div>
</div>
    <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
    <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
    <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

    <div class="main">
        <div class="service-nav-panel">
            <div class="service-nav-header">
                <b style="color: var(--navy); font-size: 25px;">Master Services</b><br>
                <small style="color: var(--text-muted);">Filter clients by task</small>
            </div>
            <div class="service-item active" onclick="filterService('all', this)">
                <span>View All Clients</span>
                <span class="badge"><?php echo $total_client_count; ?></span>
            </div>
            <?php foreach ($services_map as $name => $clients): ?>
                <div class="service-item" onclick="filterService('<?php echo md5($name); ?>', this)">
                    <span><?php echo htmlspecialchars($name); ?></span>
                    <span class="badge"><?php echo count($clients); ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="display-panel">
            <header class="content-header">
                <div>
                    <h1 id="viewTitle">All Clients</h1>
                    <div class="breadcrumb">Firm Management &nbsp;/&nbsp; Services &nbsp;/&nbsp; <span id="breadcrumb-text" style="color: var(--orange);">All</span></div>
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
                            <?php foreach ($services_map as $name => $clients):
                                $sid = md5($name);
                                foreach ($clients as $c): ?>
                                    <tr class="client-row" data-sid="<?php echo $sid; ?>">
                                        <td>
                                            <div class="client-info">
                                                <img src="../uploads/client_pics/<?php echo $c['profile_pic']; ?>" class="client-img" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($c['company_name']); ?>&background=random&size=128'">
                                                <b style="color: var(--navy);"><?php echo $c['company_name']; ?></b>
                                            </div>
                                        </td>
                                        <td><span class="id-pill"><?php echo $c['client_id']; ?></span></td>
                                        <td><?php echo $c['owner_name']; ?></td>
                                        <td style="color: var(--text-muted);">
                                            <i class="fas fa-phone-alt" style="font-size:11px; margin-right:5px;"></i> <?php echo $c['phone']; ?>
                                        </td>
                                        <td style="text-align:right;">
                                            <a href="client-profile.php?id=<?php echo $c['client_id']; ?>" class="btn-profile">VIEW DETAILS</a>
                                        </td>
                                    </tr>
                            <?php endforeach;
                            endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleMenu(id, chev) {
            document.getElementById(id).classList.toggle('show-menu');
            document.getElementById(chev).classList.toggle('rotate-chevron');
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
                if (sid === 'all' || row.getAttribute('data-sid') === sid) {
                    row.classList.remove('hidden');
                } else {
                    row.classList.add('hidden');
                }
            });
        }
    </script>

</body>

</html>