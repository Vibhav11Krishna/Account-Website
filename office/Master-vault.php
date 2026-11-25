<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Login.php");
    exit();
}

// --- HANDLE MANUAL CATEGORY CREATION ---
if (isset($_POST['create_folder'])) {
    $folderName = mysqli_real_escape_string($conn, trim($_POST['folder_name']));
    $folderColor = mysqli_real_escape_string($conn, $_POST['folder_color']);

    $check = $conn->query("SELECT id FROM service_categories WHERE UPPER(category_name) = UPPER('$folderName')");
    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO service_categories (category_name, color_code) VALUES ('$folderName', '$folderColor')");
        header("Location: Master-Vault.php");
        exit();
    }
}

// --- HANDLE FOLDER DELETION ---
if (isset($_GET['delete_folder'])) {
    $folderName = mysqli_real_escape_string($conn, $_GET['delete_folder']);
    $conn->query("DELETE FROM service_categories WHERE category_name = '$folderName'");
    header("Location: Master-Vault.php");
    exit();
}

// --- HANDLE DOCUMENT RECORD DELETION (FROM VAULT VIEW) ---
if (isset($_GET['delete_doc'])) {
    $docId = mysqli_real_escape_string($conn, $_GET['delete_doc']);
    // Update status to 'Archived' so it leaves the Master Vault but stays in DB
    $conn->query("UPDATE client_documents SET status = 'Archived' WHERE id = '$docId'");
    header("Location: Master-Vault.php");
    exit();
}

/**
 * Logic: Fetch folders
 */
function getManualFolders($conn)
{
    $folders = [];
    $res = $conn->query("SELECT category_name, color_code FROM service_categories ORDER BY category_name ASC");
    while ($row = $res->fetch_assoc()) {
        $folders[$row['category_name']] = $row['color_code'];
    }
    return $folders;
}

/**
 * Logic: Fetch documents
 */
function getMatchingFiles($conn, $folderName, $searchTerm = '')
{
    $safeFolder = $conn->real_escape_string($folderName);
    $safeSearch = $conn->real_escape_string($searchTerm);

    $sql = "SELECT cd.*, cd.created_at as assigned_at, cp.company_name, cp.owner_name 
            FROM client_documents cd 
            LEFT JOIN client_profiles cp ON cd.client_id = cp.client_id 
            WHERE cd.status = 'Released' 
            AND (cd.category LIKE '%$safeFolder%' OR cd.doc_category LIKE '%$safeFolder%')";

    if (!empty($safeSearch)) {
        $sql .= " AND (cp.company_name LIKE '%$safeSearch%' OR cp.owner_name LIKE '%$safeSearch%' OR cd.client_id LIKE '%$safeSearch%')";
    }

    $sql .= " ORDER BY cd.created_at DESC";
    return $conn->query($sql);
}

$myServices = getManualFolders($conn);
$search = isset($_GET['q']) ? $_GET['q'] : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Vault | KKA Admin</title>
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

        .toolbar-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn-plus {
            background: var(--navy);
            color: white;
            border: none;
            padding: 11px 18px;
            border-radius: 12px;
            font-family: inherit;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-sm);
        }

        .btn-plus:hover {
            background: var(--orange);
            transform: translateY(-1px);
        }

        .search-box {
            display: flex;
            align-items: center;
            background: var(--card-bg);
            padding: 4px 14px;
            border-radius: 12px;
            border: 1.5px solid var(--border);
            width: 350px;
            max-width: 100%;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s;
        }

        .search-box:focus-within {
            border-color: var(--navy);
            box-shadow: 0 0 0 3px rgba(11, 60, 116, 0.1);
        }

        .search-box input {
            border: none;
            background: transparent;
            padding: 9px 6px;
            width: 100%;
            font-family: inherit;
            font-size: 13.5px;
            color: var(--text-main);
            outline: none;
        }

        /* Folder System */
        .folder-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }

        .folder-wrapper.expanded {
            grid-column: 1 / -1;
        }

        .folder-card {
            position: relative;
            height: 110px;
            border-radius: 0 16px 16px 16px;
            color: white;
            cursor: pointer;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            padding: 0 24px;
            box-shadow: var(--shadow-sm);
        }

        .folder-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .folder-tab {
            position: absolute;
            top: -10px;
            left: 0;
            width: 75px;
            height: 12px;
            border-radius: 8px 8px 0 0;
        }

        .folder-drawer {
            background: var(--card-bg);
            border-radius: 18px;
            margin-top: 14px;
            display: none;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .drawer-header {
            padding: 13px 20px;
            background: #fef2f2;
            border-bottom: 1px solid #fee2e2;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 14px 16px;
            background: #f8fafc;
            font-size: 11px;
            color: var(--navy);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 14px 16px;
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

        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 28px;
            border-radius: 20px;
            width: 90%;
            max-width: 380px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
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
            <a href="javascript:void(0)" class="dropdown-btn active" onclick="toggleMenu('vaultMenu', 'vaultChev')">
                <i class="fas fa-folder-open"></i> Documents
                <i class="fas fa-chevron-down rotate-chevron" id="vaultChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content show-menu" id="vaultMenu">
                <a href="admin-review.php">Quality Control</a>
                <a href="Master-Vault.php" class="active">Services</a>
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
                <h1>Master Vault</h1>
                <div class="welcome-text">Browse released client documents organized by service category</div>
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
                <form action="" method="GET" class="search-box">
                    <input type="text" name="q" placeholder="Search client, firm, or ID..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" style="background:none; border:none; cursor:pointer; color:var(--navy);"><i class="fas fa-search"></i></button>
                </form>

                <button type="button" class="btn-plus" onclick="document.getElementById('addModal').style.display='block'">
                    <i class="fas fa-plus"></i> New Category
                </button>
            </div>

            <div class="folder-grid">
                <?php foreach ($myServices as $name => $color) :
                    $id = md5($name);
                    $files = getMatchingFiles($conn, $name, $search);
                ?>
                    <div class="folder-wrapper" id="wrap_<?php echo $id; ?>">
                        <div class="folder-card" onclick="toggleFolder('<?php echo $id; ?>')" style="background: <?php echo htmlspecialchars($color); ?>;">
                            <div class="folder-tab" style="background: <?php echo htmlspecialchars($color); ?>;"></div>
                            <div style="z-index:1;">
                                <h3 style="margin:0; font-size:15px; font-weight:800;"><?php echo htmlspecialchars(strtoupper($name)); ?></h3>
                                <p style="margin:5px 0 0; opacity:0.88; font-size:11.5px; font-weight:600;"><?php echo $files->num_rows; ?> Files</p>
                            </div>
                            <i class="fas fa-folder-open" style="position:absolute; right:20px; font-size:40px; opacity:0.2;"></i>
                        </div>

                        <div class="folder-drawer" id="<?php echo $id; ?>">
                            <div class="drawer-header">
                                <span style="font-size: 11px; color: #b91c1c; font-weight: 700; letter-spacing:0.4px;">CATEGORY: <?php echo htmlspecialchars(strtoupper($name)); ?></span>
                                <a href="?delete_folder=<?php echo urlencode($name); ?>"
                                    onclick="return confirm('Delete this entire folder category?')"
                                    style="color: #ef4444; text-decoration: none; font-size: 11px; font-weight: 700;">
                                    <i class="fas fa-trash"></i> DELETE FOLDER
                                </a>
                            </div>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Assigned At</th>
                                        <th>Client Details</th>
                                        <th>Folder Category</th>
                                        <th style="text-align:right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($files->num_rows > 0) : while ($row = $files->fetch_assoc()) : ?>
                                            <tr>
                                                <td><strong><?php echo date('d M Y', strtotime($row['assigned_at'])); ?></strong></td>
                                                <td><strong><?php echo htmlspecialchars($row['owner_name']); ?></strong><br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($row['company_name']); ?></small></td>
                                                <td><span style="background:#e2e8f0; color:var(--navy); font-weight:600; padding:4px 9px; border-radius:6px; font-size:11px;"><?php echo htmlspecialchars($row['category']); ?></span></td>
                                                <td style="text-align:right">
                                                    <div style="display:flex; gap:8px; justify-content:flex-end;">
                                                        <a href="../uploads/vault/<?php echo htmlspecialchars($row['result_file']); ?>" target="_blank" style="background:var(--navy); color:white; padding:6px 12px; border-radius:7px; text-decoration:none; font-size:11px; font-weight:700;">VIEW</a>
                                                        <a href="?delete_doc=<?php echo urlencode($row['id']); ?>" onclick="return confirm('Archive this file?')" style="background:#fee2e2; color:#ef4444; padding:6px 12px; border-radius:7px; text-decoration:none; font-size:11px; font-weight:700;"><i class="fas fa-times"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile;
                                    else : ?>
                                        <tr>
                                            <td colspan="4" style="text-align:center; padding:30px; color:var(--text-muted);">No documents found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div id="addModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-top:0; color:var(--navy); font-weight:800;">New Vault Category</h3>
            <form method="POST">
                <input type="text" name="folder_name" placeholder="e.g. Income Tax" required style="width:100%; padding:11px 14px; margin-bottom:14px; border:1.5px solid var(--border); background:#f8fafc; border-radius:10px; box-sizing:border-box; font-family:inherit;">
                <select name="folder_color" style="width:100%; padding:11px 14px; margin-bottom:16px; border:1.5px solid var(--border); background:#f8fafc; border-radius:10px; font-family:inherit;">
                    <option value="#3b82f6">Blue</option>
                    <option value="#10b981">Green</option>
                    <option value="#f97316">Orange</option>
                    <option value="#f43f5e">Red</option>
                    <option value="#8b5cf6">Purple</option>
                    <option value="#06b6d4">Cyan</option>
                    <option value="#14b8a6">Teal</option>
                    <option value="#6366f1">Indigo</option>
                    <option value="#ec4899">Pink</option>
                    <option value="#eab308">Amber</option>
                    <option value="#1e293b">Dark Slate</option>
                    <option value="#be185d">Rose</option>
                    <option value="#7c3aed">Violet</option>
                    <option value="#0f766e">Forest Green</option>
                    <option value="#475569">Cool Grey</option>
                </select>
                <button type="submit" name="create_folder" style="width:100%; background:var(--navy); color:white; border:none; padding:12px; border-radius:10px; cursor:pointer; font-family:inherit; font-weight:700;">Create Folder</button>
                <button type="button" onclick="document.getElementById('addModal').style.display='none'" style="width:100%; background:none; border:none; margin-top:10px; cursor:pointer; color:var(--text-muted); font-family:inherit; font-weight:600;">Cancel</button>
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

        function toggleFolder(id) {
            let drawer = document.getElementById(id);
            let wrap = document.getElementById('wrap_' + id);
            drawer.style.display = drawer.style.display === "block" ? "none" : "block";
            wrap.classList.toggle('expanded');
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
    </script>
</body>

</html>