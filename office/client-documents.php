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

if (isset($_POST['upload_doc'])) {
    $c_id = mysqli_real_escape_string($conn, $_POST['client_id']);
    $type = mysqli_real_escape_string($conn, $_POST['doc_type']);
    $target_dir = "../storage/clients/" . $c_id . "/";

    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

    // Loop through the uploaded files array
    foreach ($_FILES['files']['name'] as $key => $file_name) {
        $file_name = basename($file_name);
        $target_file = $target_dir . time() . "_" . $file_name;

        if (move_uploaded_file($_FILES['files']['tmp_name'][$key], $target_file)) {
            $stmt = $conn->prepare("INSERT INTO client_files (client_id, file_name, file_type, file_path) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $c_id, $file_name, $type, $target_file);
            $stmt->execute();
        }
    }
    header("Location: client-documents.php?cid=" . $c_id);
    exit();
}

// 2. Backend: Delete File Logic
if (isset($_GET['delete']) && isset($_GET['cid'])) {
    $file_id = intval($_GET['delete']);
    $c_id = intval($_GET['cid']);

    $result = $conn->query("SELECT file_path FROM client_files WHERE id = $file_id");
    if ($row = $result->fetch_assoc()) {
        if (file_exists($row['file_path'])) unlink($row['file_path']);
        $conn->query("DELETE FROM client_files WHERE id = $file_id");
    }
    header("Location: client-documents.php?cid=" . $c_id);
    exit();
}
if (isset($_GET['action']) && isset($_GET['file_id']) && isset($_GET['cid'])) {
    $file_id = intval($_GET['file_id']);
    $c_id = intval($_GET['cid']);
    $action = $_GET['action'];

    if ($action == 'share') {
        $stmt = $conn->prepare("UPDATE client_files SET status = 'Released' WHERE id = ?");
        $stmt->bind_param("i", $file_id);
        $stmt->execute();
    } elseif ($action == 'unshare') {
        $stmt = $conn->prepare("UPDATE client_files SET status = 'Internal' WHERE id = ?");
        $stmt->bind_param("i", $file_id);
        $stmt->execute();
    } elseif ($action == 'delete') {
        // Get path and delete file
        $res = $conn->query("SELECT file_path FROM client_files WHERE id = $file_id");
        if ($row = $res->fetch_assoc()) {
            if (file_exists($row['file_path'])) unlink($row['file_path']);
            $conn->query("DELETE FROM client_files WHERE id = $file_id");
        }
    }
    header("Location: client-documents.php?cid=" . $c_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients Documents | KKA Admin</title>
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

        /* Folders with dynamic colors */
        .folder-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 20px;
        }

        .folder-card {
            background: var(--card-bg);
            padding: 22px 18px;
            border-radius: 18px;
            text-align: center;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: all 0.2s;
        }

        .folder-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--orange);
        }

        /* Table & Action Buttons */
        .file-table {
            width: 100%;
            background: var(--card-bg);
            border-radius: 20px;
            border-collapse: collapse;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .file-table th {
            padding: 15px 18px;
            background: #f8fafc;
            color: var(--navy);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px solid var(--border);
            text-align: left;
        }

        .file-table td {
            padding: 16px 18px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            font-size: 13.5px;
            vertical-align: middle;
        }

        .file-table tbody tr:last-child td {
            border-bottom: none;
        }

        .file-table tbody tr:hover {
            background: #f8fafc;
        }

        .btn {
            padding: 7px 12px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 12.5px;
            text-decoration: none;
            font-weight: 600;
            margin-right: 6px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-view {
            background: #e0e7ff;
            color: #4338ca;
        }

        .btn-view:hover {
            background: #4338ca;
            color: white;
        }

        .btn-down {
            background: #ffedd5;
            color: #c2410c;
        }

        .btn-del {
            background: #fee2e2;
            color: #b91c1c;
        }

        .btn-del:hover {
            background: #b91c1c;
            color: white;
        }

        .btn-share {
            background: #dcfce7;
            color: #166534;
        }

        .btn-share:hover {
            background: #166534;
            color: white;
        }

        .btn-shared {
            background: #dbeafe;
            color: #1e40af;
        }

        #clientSearch {
            width: 100%;
            max-width: 500px;
            padding: 11px 18px;
            border-radius: 12px;
            border: 1.5px solid var(--border);
            font-family: inherit;
            font-size: 13.5px;
            color: var(--text-main);
            outline: none;
            transition: all 0.2s;
            background: var(--card-bg);
            box-shadow: var(--shadow-sm);
        }

        #clientSearch:focus {
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
            <a href="javascript:void(0)" class="dropdown-btn active" onclick="toggleMenu('vaultMenu', 'vaultChev')">
                <i class="fas fa-folder-open"></i> Documents
                <i class="fas fa-chevron-down rotate-chevron" id="vaultChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content show-menu" id="vaultMenu">
                <a href="admin-review.php">Quality Control</a>
                <a href="Master-Vault.php">Services</a>
                <a href="client-documents.php" class="active">Client Documents</a>
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
                <h1>Client Document Vault</h1>
                <div class="welcome-text">Manage and access all client files securely in one place</div>
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
            <div style="display: flex; justify-content: center; margin-bottom: 24px;">
                <input type="text" id="clientSearch" onkeyup="filterClients()" placeholder="Search company name...">
            </div>

            <?php if (!isset($_GET['cid'])): ?>
                <div class="folder-grid">
                    <?php
                    // Updated: Selecting 'company_name' instead of 'owner_name'
                    $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
                    $clients = $conn->query("SELECT client_id, company_name FROM client_profiles");
                    $i = 0;
                    while ($c = $clients->fetch_assoc()):
                    ?>
                        <div class="folder-card" onclick="location.href='?cid=<?php echo urlencode($c['client_id']); ?>'">
                            <i class="fas fa-folder" style="font-size: 40px; color: <?php echo $colors[$i++ % 5]; ?>;"></i>
                            <div style="margin-top:12px; font-weight:700; color:var(--navy); font-size:14px;"><?php echo htmlspecialchars($c['company_name']); ?></div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else:
                $cid = $_GET['cid'];
                $cid_safe = mysqli_real_escape_string($conn, $cid);
                $files = $conn->query("SELECT * FROM client_files WHERE client_id='$cid_safe' ORDER BY id DESC");
            ?>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; gap:12px; flex-wrap:wrap;">
                    <a href="client-documents.php" style="
                        display: inline-flex; 
                        align-items: center; 
                        gap: 8px; 
                        padding: 10px 18px; 
                        background: #ffffff; 
                        color: var(--navy); 
                        text-decoration: none; 
                        border-radius: 10px; 
                        font-weight: 700; 
                        font-size: 13px; 
                        border: 1px solid var(--border);
                        box-shadow: var(--shadow-sm);
                        transition: all 0.2s;">
                        <i class="fas fa-arrow-left"></i> Back to Folders
                    </a>
                    <button type="button" onclick="openUploadModal('<?php echo htmlspecialchars($cid, ENT_QUOTES); ?>')" style="padding:10px 20px; background:var(--navy); color:white; border:none; border-radius:10px; font-family:inherit; font-weight:700; font-size:13px; cursor:pointer; box-shadow:var(--shadow-sm);">
                        <i class="fas fa-plus"></i> Upload File
                    </button>
                </div>
                <table class="file-table">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Category</th>
                            <th>Date Uploaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($f = $files->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight:600; color:var(--navy);"><?php echo htmlspecialchars($f['file_name']); ?></td>
                                <td><?php echo htmlspecialchars($f['file_type']); ?></td>
                                <td><?php echo date('d M Y', strtotime($f['upload_date'])); ?></td>
                                <td>
                                    <?php if ($f['status'] == 'Internal'): ?>
                                        <a href="#" onclick="confirmAction('share', '<?= intval($f['id']) ?>', '<?= htmlspecialchars($cid, ENT_QUOTES) ?>')" class="btn btn-share">
                                            <i class="fas fa-share-square"></i> Share
                                        </a>
                                    <?php else: ?>
                                        <a href="#" onclick="confirmAction('unshare', '<?= intval($f['id']) ?>', '<?= htmlspecialchars($cid, ENT_QUOTES) ?>')" class="btn btn-shared" style="background:#f59e0b; color:white;">
                                            <i class="fas fa-eye-slash"></i> Recall
                                        </a>
                                    <?php endif; ?>

                                    <a href="<?= htmlspecialchars($f['file_path']) ?>" target="_blank" class="btn btn-view">View</a>

                                    <a href="#" onclick="confirmAction('delete', '<?= intval($f['id']) ?>', '<?= htmlspecialchars($cid, ENT_QUOTES) ?>')" class="btn btn-del">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div id="uploadModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.5); backdrop-filter:blur(4px);">
        <div class="modal-content" style="background:white; margin:10% auto; padding:28px; width:90%; max-width:420px; border-radius:20px; box-shadow: var(--shadow-lg); border:1px solid var(--border);">
            <h3 style="margin-top:0; color:var(--navy); font-weight:800;">Upload New Files</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="client_id" id="modal_client_id">
                <div style="margin-bottom:15px;">
                    <label style="display:block; font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Document Category:</label>
                    <input type="text" name="doc_type" placeholder="e.g. GST, ITR, PAN" required style="width:100%; padding:10px 12px; margin-top:6px; border:1.5px solid var(--border); border-radius:10px; background:#f8fafc; font-family:inherit; box-sizing:border-box;">
                </div>
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Select Files:</label>
                    <input type="file" name="files[]" multiple required style="width:100%; padding:8px 0; margin-top:4px; font-family:inherit;">
                </div>
                <button type="submit" name="upload_doc" style="background:var(--navy); color:white; border:none; padding:12px; width:100%; border-radius:10px; font-family:inherit; font-weight:700; cursor:pointer;">Save Files to Vault</button>
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

        function filterClients() {
            let input = document.getElementById('clientSearch');
            let filter = input.value.toUpperCase();
            let grid = document.querySelector('.folder-grid');
            if (!grid) return;
            let cards = grid.getElementsByClassName('folder-card');

            for (let i = 0; i < cards.length; i++) {
                let name = cards[i].innerText || cards[i].textContent;
                if (name.toUpperCase().indexOf(filter) > -1) {
                    cards[i].style.display = "";
                } else {
                    cards[i].style.display = "none";
                }
            }
        }

        function openUploadModal(id) {
            document.getElementById('modal_client_id').value = id;
            document.getElementById('uploadModal').style.display = 'block';
        }

        // Close modal if user clicks the background overlay
        window.onclick = function(event) {
            const modal = document.getElementById('uploadModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

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

        function confirmAction(action, fileId, clientId) {
            let messages = {
                'share': "Are you sure you want to release this document to the client?",
                'unshare': "Are you sure you want to recall this document? It will no longer be visible to the client.",
                'delete': "Are you sure you want to PERMANENTLY delete this file?"
            };

            if (confirm(messages[action])) {
                window.location.href = `?cid=${encodeURIComponent(clientId)}&file_id=${encodeURIComponent(fileId)}&action=${encodeURIComponent(action)}`;
            }
        }
    </script>
</body>

</html>