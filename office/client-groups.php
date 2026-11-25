<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

// Fetch Custom Groups
$custom_group_res = $conn->query("SELECT * FROM client_custom_groups ORDER BY created_at DESC");

$selected_group = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Groups | KKA Admin</title>
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
            height: 100vh;
            overflow: hidden;
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
        .content-area {
            margin-left: 280px;
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        .admin-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(12px);
            padding: 14px 32px;
            border-bottom: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            gap: 16px;
            flex-shrink: 0;
        }

        .search-container {
            position: relative;
            flex: 1;
            max-width: 380px;
        }

        .search-container input {
            width: 100%;
            padding: 10px 14px 10px 38px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-family: inherit;
            font-size: 13px;
            background: #f8fafc;
            outline: none;
            transition: all 0.2s;
        }

        .search-container i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 13px;
        }

        .search-container input:focus {
            border-color: var(--navy);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(11, 60, 116, 0.1);
        }

        .btn-create-group {
            background: var(--orange);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 10px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(255, 140, 0, 0.25);
            white-space: nowrap;
        }

        .btn-create-group:hover {
            background: #e67e00;
            transform: translateY(-1px);
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

        .group-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .group-sidebar {
            width: 300px;
            background: var(--card-bg);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .group-list {
            flex: 1;
            overflow-y: auto;
        }

        .group-item-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-right: 14px;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s;
        }

        .group-item-container:hover {
            background: #f8fafc;
        }

        .group-item-container.active {
            background: #eff6ff;
            border-right: 3px solid var(--orange);
        }

        .group-item {
            padding: 14px 18px;
            cursor: pointer;
            transition: 0.2s;
            display: block;
            text-decoration: none;
            color: var(--text-main);
            font-size: 13.5px;
            flex: 1;
        }

        .main-content {
            flex: 1;
            padding: 28px 32px;
            overflow-y: auto;
        }

        .table-card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 18px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 13px 14px;
            background: #f8fafc;
            color: var(--navy);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 14px;
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

        .id-badge {
            background: var(--orange-glow);
            color: #c2410c;
            font-weight: 700;
            padding: 4px 9px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 12px;
        }

        .btn-view {
            background: var(--navy);
            color: white;
            border: none;
            padding: 7px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.2s;
        }

        .btn-view:hover {
            background: var(--navy-light);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(4px);
        }

        .selectable-client-item.selected {
            background: #fff7ed !important;
            border-left: 4px solid var(--orange);
        }

        .selectable-client-item.selected .check-indicator {
            background: var(--orange) !important;
            border-color: var(--orange) !important;
        }

        .selectable-client-item.selected .check-indicator i {
            display: block !important;
        }

        .selectable-client-item.selected .fa-plus-circle {
            color: var(--orange) !important;
            transform: rotate(45deg);
        }

        .update-client-item:hover {
            border-color: #e2e8f0 !important;
            background: #f1f5f9 !important;
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
            <a href="javascript:void(0)" class="dropdown-btn active" onclick="toggleMenu('clientMenu', 'clientChev')">
                <i class="fas fa-users"></i> Manage Clients
                <i class="fas fa-chevron-down rotate-chevron" id="clientChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content show-menu" id="clientMenu">
                <a href="manage-clients.php">Clients</a>
                <a href="client-groups.php" class="active">Client Groups</a>
                <a href="client-services.php">Services</a>
                <a href="client-upload-docs.php">Clients Uploads</a>
            </div>
        </div>

        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
        <a href="../logout.php" style="margin-top:auto; color:#fca5a5;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <!-- Main Workspace -->
    <div class="content-area">
        <header class="admin-top-bar">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="tableSearch" onkeyup="searchTable()" placeholder="Search firms in this group...">
            </div>

            <button onclick="openGroupModal()" class="btn-create-group">
                <i class="fas fa-plus"></i> Create New Group
            </button>

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

        <div class="group-container">
            <div class="group-sidebar">
                <div style="padding:14px 18px; background:#f8fafc; font-size:11px; font-weight:700; color:var(--navy); text-transform:uppercase; letter-spacing:0.6px; border-bottom:1px solid var(--border);">Custom Groups</div>
                <div class="group-list">
                    <?php if ($custom_group_res && $custom_group_res->num_rows > 0):
                        while ($cg = $custom_group_res->fetch_assoc()): ?>
                            <div class="group-item-container <?php echo ($selected_group == $cg['id']) ? 'active' : ''; ?>">
                                <a href="?group_id=<?php echo $cg['id']; ?>" class="group-item">
                                    <i class="fas fa-folder" style="color:var(--orange); margin-right:10px;"></i>
                                    <b><?php echo htmlspecialchars($cg['group_name']); ?></b>
                                </a>
                                <a href="delete-group.php?id=<?php echo $cg['id']; ?>"
                                    onclick="return confirm('Are you sure you want to delete this group? The clients inside will NOT be deleted.')"
                                    style="color:#94a3b8; font-size:12px; transition:0.2s;"
                                    onmouseover="this.style.color='var(--danger)'" onmouseout="this.style.color='#94a3b8'">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        <?php endwhile;
                    else: ?>
                        <div style="padding:24px; text-align:center; color:var(--text-muted); font-size:13px;">No custom groups created yet.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="main-content">
                <?php if ($selected_group > 0):
                    $stmt = $conn->prepare("SELECT * FROM client_custom_groups WHERE id = ?");
                    $stmt->bind_param("i", $selected_group);
                    $stmt->execute();
                    $group = $stmt->get_result()->fetch_assoc();

                    if ($group):
                        if (!empty($group['client_ids'])) {
                            $ids_array = explode(',', $group['client_ids']);
                            $quoted_ids = array_map(function ($id) use ($conn) {
                                return "'" . mysqli_real_escape_string($conn, trim($id)) . "'";
                            }, $ids_array);
                            $final_ids = implode(',', $quoted_ids);

                            $clients = $conn->query("SELECT * FROM client_profiles WHERE client_id IN ($final_ids)");
                        } else {
                            $clients = false;
                        }
                ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-bottom: 22px;">
                            <h2 style="margin: 0; color: var(--navy); font-size: 22px; font-weight: 800;">
                                <?php echo htmlspecialchars($group['group_name']); ?>
                            </h2>

                            <div style="display: flex; gap: 12px;">
                                <button onclick='openUpdateSpecific(<?php echo htmlspecialchars(json_encode($group), ENT_QUOTES, 'UTF-8'); ?>, "name")'
                                    style="background: #ffffff; color: var(--navy); border: 1px solid var(--border); padding: 9px 16px; border-radius: 10px; cursor: pointer; font-family: inherit; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: var(--shadow-sm);"
                                    onmouseover="this.style.background='#f8fafc'; this.style.borderColor='var(--navy)';"
                                    onmouseout="this.style.background='#ffffff'; this.style.borderColor='var(--border)';">
                                    <i class="fas fa-edit" style="color:var(--orange);"></i>
                                    Edit Group
                                </button>
                            </div>
                        </div>

                        <div class="table-card">
                            <table id="clientTable">
                                <thead>
                                    <tr>
                                        <th>Firm / Company</th>
                                        <th>Client ID</th>
                                        <th>Phone</th>
                                        <th>Owner</th>
                                        <th style="text-align:center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($clients && $clients->num_rows > 0): ?>
                                        <?php while ($row = $clients->fetch_assoc()):
                                            $json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                        ?>
                                            <tr class="client-row">
                                                <td><b><?php echo htmlspecialchars($row['company_name']); ?></b></td>
                                                <td><span class="id-badge"><?php echo htmlspecialchars($row['client_id']); ?></span></td>
                                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                                <td><?php echo htmlspecialchars($row['owner_name']); ?></td>
                                                <td style="text-align:center; display:flex; gap:8px; justify-content:center;">
                                                    <button onclick='openViewModal(<?php echo $json; ?>)' class="btn-view" style="padding: 7px 14px; border-radius:8px;">View</button>

                                                    <a href="remove-client-from-group.php?group_id=<?php echo $selected_group; ?>&client_id=<?php echo urlencode($row['client_id']); ?>"
                                                        onclick="return confirm('Remove this client?')"
                                                        style="background:var(--danger-bg); color:var(--danger); border:1px solid #fecaca; width:34px; height:34px; display:flex; align-items:center; justify-content:center; text-decoration:none; border-radius:8px;">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" style="text-align:center; padding:50px; color:var(--text-muted);">
                                                <i class="fas fa-users-slash" style="font-size:38px; margin-bottom:10px; display:block; color:#cbd5e1;"></i>
                                                No clients found in this group.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif;
                    if (isset($stmt)) {
                        $stmt->close();
                    }
                    ?>
                <?php else: ?>
                    <div style="text-align:center; margin-top:130px; color:#94a3b8;">
                        <i class="fas fa-layer-group" style="font-size:68px; color:#cbd5e1; margin-bottom:14px; display:block;"></i>
                        <h3 style="color:var(--text-muted); font-weight:600;">Select a group from the left to manage clients</h3>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- View Client Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content" style="background:white; margin:5vh auto; width:100%; max-width:850px; border-radius:18px; overflow:hidden; box-shadow:var(--shadow-lg); border:1px solid var(--border);">
            <div style="background:var(--navy); padding:18px 24px; color:white; display:flex; justify-content:space-between; align-items:center;">
                <h3 id="v_title" style="margin:0; font-size:17px; font-weight:700;"></h3>
                <span onclick="closeModal()" style="cursor:pointer; font-size:24px; line-height:1;">&times;</span>
            </div>
            <div style="padding:28px; display:grid; grid-template-columns: 1fr 1fr; gap:28px;">
                <div>
                    <h4 style="color:var(--orange); border-bottom:1px solid var(--border); padding-bottom:10px; margin-top:0;">Basic Details</h4>
                    <p><b>Client ID:</b> <span id="v_id" class="id-badge"></span></p>
                    <p><b>Owner:</b> <span id="v_owner"></span></p>
                    <p><b>Phone:</b> <span id="v_phone"></span></p>
                    <p><b>Email:</b> <span id="v_email"></span></p>
                    <p><b>Nature:</b> <span id="v_nature"></span></p>
                    <p><b>Address:</b> <br><small id="v_address" style="color:var(--text-muted);"></small></p>
                </div>
                <div>
                    <h4 style="color:var(--orange); border-bottom:1px solid var(--border); padding-bottom:10px; margin-top:0;">Tax &amp; Registration</h4>
                    <p><b>GST No:</b> <span id="v_gst"></span></p>
                    <p><b>PAN No:</b> <span id="v_pan"></span></p>
                    <p><b>TAN No:</b> <span id="v_tan"></span></p>
                    <p><b>CIN No:</b> <span id="v_cin"></span></p>
                    <p><b>Aadhaar:</b> <span id="v_aadhaar"></span></p>
                    <div style="background:#f8fafc; padding:12px; border-radius:10px; margin-top:14px; border:1px solid var(--border);">
                        <b>Last Task Asked:</b><br>
                        <small id="v_task" style="color:var(--text-muted);"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Custom Group Modal -->
    <div id="groupModal" class="modal">
        <div class="modal-content" style="background:white; margin:4vh auto; width:100%; max-width:600px; border-radius:18px; overflow:hidden; box-shadow: var(--shadow-lg); border:1px solid var(--border);">
            <div style="background:var(--navy); padding:18px 24px; color:white; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-size:17px; font-weight:700;">Create Custom Group</h3>
                <span onclick="closeGroupModal()" style="cursor:pointer; font-size:24px; line-height:1;">&times;</span>
            </div>

            <form action="save-group-logic.php" method="POST" style="padding:24px;">
                <label style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.6px;">Group Name</label>
                <input type="text" name="group_name" required placeholder="e.g. Monthly GST Group"
                    style="width:100%; padding:11px 14px; border:1.5px solid var(--border); border-radius:10px; margin:8px 0 18px 0; outline:none; font-family:inherit; background:#f8fafc;">

                <label style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.6px;">Search &amp; Click to Add Clients</label>
                <div style="position:relative; margin:8px 0 12px 0;">
                    <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8;"></i>
                    <input type="text" id="modalClientSearch" onkeyup="filterModalClients()" placeholder="Type firm name or ID..."
                        style="width:100%; padding:10px 12px 10px 36px; border:1.5px solid var(--border); border-radius:10px; outline:none; font-size:13px; font-family:inherit; background:#f8fafc;">
                </div>

                <div id="modalClientList" style="max-height:280px; overflow-y:auto; border:1px solid var(--border); border-radius:12px; background:#fcfcfc;">
                    <?php
                    $cl_list = $conn->query("SELECT client_id, company_name, owner_name FROM client_profiles ORDER BY company_name ASC");
                    if ($cl_list):
                        while ($cl = $cl_list->fetch_assoc()): ?>

                            <label class="selectable-client-item" style="display:flex; align-items:center; gap:12px; padding:12px; border-bottom:1px solid #f1f5f9; cursor:pointer; transition:0.2s;">
                                <input type="checkbox" name="client_ids[]" value="<?php echo htmlspecialchars($cl['client_id']); ?>" style="display:none;" onchange="toggleSelectionUI(this)">

                                <div class="check-indicator" style="width:20px; height:20px; border:2px solid #cbd5e1; border-radius:50%; display:flex; align-items:center; justify-content:center; background:white;">
                                    <i class="fas fa-check" style="font-size:10px; color:white; display:none;"></i>
                                </div>

                                <div style="flex:1;">
                                    <div class="firm-title" style="font-size:13.5px; font-weight:700; color:var(--navy);"><?php echo htmlspecialchars($cl['company_name']); ?></div>
                                    <div style="font-size:11px; color:var(--text-muted);">ID: <b><?php echo htmlspecialchars($cl['client_id']); ?></b> | Owner: <?php echo htmlspecialchars($cl['owner_name']); ?></div>
                                </div>

                                <i class="fas fa-plus-circle action-icon" style="color:#cbd5e1; font-size:16px; transition:0.2s;"></i>
                            </label>

                    <?php endwhile;
                    endif; ?>
                </div>

                <button type="submit" style="width:100%; margin-top:20px; background:var(--orange); color:white; border:none; padding:13px; border-radius:10px; font-family:inherit; font-weight:700; cursor:pointer; font-size:14px;">
                    Create Group with Selected Clients
                </button>
            </form>
        </div>
    </div>

    <!-- Update Group Modal -->
    <div id="updateGroupModal" class="modal">
        <div class="modal-content" style="background:#fff; width:100%; max-width:490px; margin:5vh auto; border-radius:18px; overflow:hidden; box-shadow:var(--shadow-lg); border:1px solid var(--border);">
            <div style="background:var(--navy); padding:18px 22px; color:white; display:flex; justify-content:space-between; align-items:center;">
                <h3 id="updateModalTitle" style="margin:0; font-size:17px; font-weight:700; letter-spacing:0.3px;">Update Group</h3>
                <span onclick="closeUpdateModal()" style="cursor:pointer; font-size:22px; opacity:0.85; transition:0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.85'">&times;</span>
            </div>

            <form action="group-update.php" method="POST" style="padding:24px;">
                <input type="hidden" name="group_id" id="update_group_id">

                <label style="font-size:11px; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.6px;">Group Name</label>
                <input type="text" name="group_name" id="update_group_name" required
                    style="width:100%; padding:11px 14px; border:1.5px solid var(--border); border-radius:10px; margin:8px 0 20px 0; outline:none; font-size:14px; font-family:inherit; color:var(--navy); background:#f8fafc;"
                    onfocus="this.style.borderColor='var(--orange)'; this.style.background='#fff';">

                <label style="font-size:11px; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.6px;">Members Selection</label>
                <div style="max-height:260px; overflow-y:auto; border:1px solid var(--border); padding:8px; border-radius:12px; margin-top:8px; background:#f8fafc;">
                    <?php
                    $clients_modal = $conn->query("SELECT client_id, company_name, owner_name FROM client_profiles ORDER BY company_name ASC");
                    if ($clients_modal):
                        while ($c = $clients_modal->fetch_assoc()): ?>
                            <label class="update-client-item" style="display:flex; align-items:center; padding:11px; margin-bottom:6px; cursor:pointer; background:white; border-radius:8px; border:1px solid transparent; transition:0.2s;">
                                <input type="checkbox" name="client_ids[]" value="<?php echo htmlspecialchars($c['client_id']); ?>" class="update-cb"
                                    style="width:17px; height:17px; accent-color:var(--orange);">

                                <div style="margin-left:12px;">
                                    <div style="font-size:13.5px; font-weight:700; color:var(--navy); line-height:1.2;">
                                        <?php echo htmlspecialchars($c['company_name']); ?>
                                    </div>
                                    <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">
                                        <span style="color:var(--orange); font-weight:700;"><?php echo htmlspecialchars($c['client_id']); ?></span>
                                        <span style="margin:0 5px;">•</span>
                                        <?php echo htmlspecialchars($c['owner_name']); ?>
                                    </div>
                                </div>
                            </label>
                    <?php endwhile;
                    endif; ?>
                </div>

                <button type="submit" style="width:100%; margin-top:22px; background:var(--orange); color:white; border:none; padding:13px; border-radius:10px; font-family:inherit; font-weight:700; cursor:pointer; font-size:14px; transition:0.2s;"
                    onmouseover="this.style.transform='translateY(-1px)'; this.style.opacity='0.96';"
                    onmouseout="this.style.transform='translateY(0)'; this.style.opacity='1';">
                    Save Changes
                </button>
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

        // 1. Logic to filter clients inside the modal search bar
        function filterModalClients() {
            let input = document.getElementById("modalClientSearch").value.toUpperCase();
            let items = document.querySelectorAll(".selectable-client-item");

            items.forEach(item => {
                let text = item.innerText.toUpperCase();
                item.style.display = text.includes(input) ? "flex" : "none";
            });
        }

        // 2. Logic to handle the "Click to Add" visual change
        function toggleSelectionUI(checkbox) {
            let parentLabel = checkbox.closest('.selectable-client-item');
            if (checkbox.checked) {
                parentLabel.classList.add('selected');
            } else {
                parentLabel.classList.remove('selected');
            }
        }

        function openViewModal(data) {
            document.getElementById('v_title').innerText = data.company_name;
            document.getElementById('v_id').innerText = data.client_id;
            document.getElementById('v_owner').innerText = data.owner_name;
            document.getElementById('v_phone').innerText = data.phone;
            document.getElementById('v_email').innerText = data.business_email || 'N/A';
            document.getElementById('v_nature').innerText = data.business_nature || 'N/A';
            document.getElementById('v_address').innerText = data.address || 'N/A';
            document.getElementById('v_gst').innerText = data.gst_no || '-';
            document.getElementById('v_pan').innerText = data.pan_no || '-';
            document.getElementById('v_tan').innerText = data.tan_no || '-';
            document.getElementById('v_cin').innerText = data.cin_no || '-';
            document.getElementById('v_aadhaar').innerText = data.aadhaar_no || '-';
            document.getElementById('v_task').innerText = data.task_asked || 'No specific tasks mentioned.';
            document.getElementById('viewModal').style.display = 'block';
        }

        function searchTable() {
            let input = document.getElementById("tableSearch").value.toUpperCase();
            let rows = document.querySelectorAll(".client-row");
            rows.forEach(row => {
                let text = row.innerText.toUpperCase();
                row.style.display = text.includes(input) ? "" : "none";
            });
        }

        function closeModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        function openGroupModal() {
            document.getElementById('groupModal').style.display = 'block';
        }

        function closeGroupModal() {
            document.getElementById('groupModal').style.display = 'none';
        }

        function openUpdateSpecific(data, focusMode) {
            // 1. Show the specific update modal
            document.getElementById('updateGroupModal').style.display = 'block';

            // 2. Fill the hidden ID and the name input
            document.getElementById('update_group_id').value = data.id;
            document.getElementById('update_group_name').value = data.group_name;

            // 3. Set the Title based on button clicked
            document.getElementById('updateModalTitle').innerText = (focusMode === 'name') ? "Rename Group" : "Add Clients to Group";

            // 4. Pre-check clients already in this group
            let existingIds = data.client_ids ? data.client_ids.split(',').map(id => id.trim()) : [];
            document.querySelectorAll('.update-cb').forEach(cb => {
                cb.checked = existingIds.includes(cb.value.trim());
            });

            // 5. Auto-focus
            if (focusMode === 'name') {
                document.getElementById('update_group_name').focus();
            }
        }

        function closeUpdateModal() {
            document.getElementById('updateGroupModal').style.display = 'none';
        }

        window.onclick = function(e) {
            if (e.target.classList && e.target.classList.contains('modal')) {
                closeModal();
                closeGroupModal();
                closeUpdateModal();
            }
        };

        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        if (status === 'deleted') {
            alert("Group deleted successfully!");
        } else if (status === 'success') {
            alert("Group created successfully!");
        } else if (status === 'client_removed') {
            alert("Client removed from this group.");
        }

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
    </script>

</body>
</html>