<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Login.php");
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

// --- UPDATE LOGIC ---
if (isset($_POST['update_client'])) {
    $identifier = mysqli_real_escape_string($conn, $_POST['identifier']);
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $owner_name = mysqli_real_escape_string($conn, $_POST['owner_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['business_email']);
    $gst = mysqli_real_escape_string($conn, $_POST['gst_no']);
    $pan = mysqli_real_escape_string($conn, $_POST['pan_no']);

    // ADD THESE THREE LINES:
    $tan = mysqli_real_escape_string($conn, $_POST['tan_no']);
    $cin = mysqli_real_escape_string($conn, $_POST['cin_no']);

    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $nature = mysqli_real_escape_string($conn, $_POST['business_nature']);
    $aadhaar = mysqli_real_escape_string($conn, $_POST['aadhaar_no']);

    // --- SMART TASK HANDLER ---
    if (isset($_POST['task_asked']) && !empty($_POST['task_asked'])) {
        if (is_array($_POST['task_asked'])) {
            // If it comes from old checkboxes (array)
            $task_list = implode(', ', $_POST['task_asked']);
        } else {
            // If it comes from our new "Add Service" tags (string)
            $task_list = $_POST['task_asked'];
        }
        $task = mysqli_real_escape_string($conn, $task_list);
    } else {
        $task = "";
    }

    // --- CUSTOM FIELDS HANDLER ---
    $custom_fields_array = [];
    if (isset($_POST['custom_labels']) && isset($_POST['custom_values'])) {
        foreach ($_POST['custom_labels'] as $index => $label) {
            $label = trim($label);
            $val = trim($_POST['custom_values'][$index]);

            // Only save if the label (Box Name) is actually filled
            if (!empty($label)) {
                $custom_fields_array[$label] = $val;
            }
        }
    }
    // Convert to JSON and escape for SQL safety
    $json_fields = mysqli_real_escape_string($conn, json_encode($custom_fields_array));

    // Photo logic remains the same...
    $photo_query = "";
    if (!empty($_FILES['profile_pic']['name'])) {
        $target_dir = "../uploads/client_pics/";
        $file_ext = pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION);
        $new_filename = "client_" . $identifier . "_" . time() . "." . $file_ext;
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_dir . $new_filename)) {
            $photo_query = ", profile_pic = '$new_filename'";
        }
    }

    // UPDATE THE SQL TO INCLUDE THE NEW COLUMNS:
    $update_sql = "UPDATE client_profiles SET 
                   company_name = '$company_name', 
                   owner_name = '$owner_name', 
                   phone = '$phone', 
                   business_email = '$email', 
                   gst_no = '$gst', 
                   pan_no = '$pan', 
                   tan_no = '$tan', 
                   cin_no = '$cin', 
                   address = '$address',
                   business_nature = '$nature', 
                   aadhaar_no = '$aadhaar', 
                   custom_fields = '$json_fields',
                   task_asked = '$task' 
                   $photo_query 
                   WHERE client_id = '$identifier'";

    if ($conn->query($update_sql)) {
        $conn->query("UPDATE users SET name = '$owner_name' WHERE identifier = '$identifier'");
        header("Location: manage-clients.php?msg=updated");
        exit();
    } else {
        die("Update Error: " . $conn->error);
    }
}

// --- DELETE LOGIC ---
if (isset($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);

    // Remove from both tables to maintain integrity
    $del_profile = $conn->query("DELETE FROM client_profiles WHERE client_id='$id'");
    $del_user = $conn->query("DELETE FROM users WHERE identifier='$id' AND role='client'");

    if ($del_profile && $del_user) {
        header("Location: manage-clients.php?msg=deleted");
    } else {
        header("Location: manage-clients.php?msg=error");
    }
    exit();
}

// --- QUICK CREATE LOGIC (Format: 501801, 501802...) ---
if (isset($_POST['quick_create'])) {
    $n = mysqli_real_escape_string($conn, $_POST['name']);
    $p = mysqli_real_escape_string($conn, $_POST['pass']);
    $role = 'client';

    // Get current client count
    $count_res = $conn->query("SELECT id FROM users WHERE role='client'");
    $count = $count_res->num_rows + 1;

    // Logic: Base 5018 + Padded sequence (01, 02...)
    $base_id = "5018";
    $sequence = str_pad($count, 2, "0", STR_PAD_LEFT);
    $final_id = $base_id . $sequence; // Results in 501801, 501802, etc.

    $sql = "INSERT INTO users (name, identifier, password, role) VALUES ('$n', '$final_id', '$p', '$role')";

    if ($conn->query($sql)) {
        $conn->query("INSERT INTO client_profiles (client_id, owner_name) VALUES ('$final_id', '$n')");
        echo "<script>alert('Client Created! ID: $final_id'); window.location='manage-clients.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clients | KKA Admin</title>
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
        }

        .page-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .page-toolbar h2 {
            margin: 0;
            color: var(--navy);
            font-size: 20px;
            font-weight: 800;
        }

        .search-container {
            margin-bottom: 22px;
            display: flex;
            gap: 12px;
            align-items: center;
            background: var(--card-bg);
            padding: 12px 18px;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .search-container i {
            color: var(--text-muted);
            font-size: 14px;
        }

        .search-container input {
            width: 100%;
            border: none;
            outline: none;
            font-size: 13.5px;
            font-family: inherit;
            color: var(--text-main);
            background: transparent;
        }

        .table-card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 14px 15px;
            background: #f8fafc;
            color: var(--navy);
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 15px;
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
            padding: 5px 11px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            display: inline-block;
        }

        .action-btn {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 13px;
        }

        .btn-view {
            color: var(--navy);
            background: #eff6ff;
        }

        .btn-view:hover {
            background: var(--navy);
            color: white;
        }

        .btn-edit {
            color: #2563eb;
            background: #eff6ff;
        }

        .btn-edit:hover {
            background: #2563eb;
            color: white;
        }

        .btn-delete {
            color: var(--danger);
            background: var(--danger-bg);
            border: none;
            cursor: pointer;
        }

        .btn-delete:hover {
            background: var(--danger);
            color: white;
        }

        .add-client-form {
            display: none;
            background: var(--card-bg);
            padding: 24px;
            border-radius: 20px;
            border: 1.5px solid var(--orange);
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary {
            background: var(--navy);
            color: white;
            padding: 11px 20px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background: var(--navy-light);
            transform: translateY(-1px);
        }

        .msg-alert {
            padding: 14px 18px;
            background: var(--success-bg);
            color: #047857;
            border: 1px solid #a7f3d0;
            border-radius: 12px;
            margin-bottom: 22px;
            font-weight: 600;
            font-size: 13.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .msg-danger {
            background: var(--danger-bg);
            color: #b91c1c;
            border-color: #fecaca;
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
                <a href="manage-clients.php" class="active">Clients</a>
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
                <h1>Client Directory</h1>
                <div class="welcome-text">Onboard new clients, inspect tax credentials, and manage firm profiles</div>
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
            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] == 'updated'): ?>
                    <div class="msg-alert"><i class="fas fa-check-circle"></i> Profile updated successfully!</div>
                <?php elseif ($_GET['msg'] == 'deleted'): ?>
                    <div class="msg-alert msg-danger"><i class="fas fa-trash-alt"></i> Client and all profile records deleted.</div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="page-toolbar">
                <h2>Registered Firms &amp; Clients</h2>
                <button onclick="toggleAddForm()" class="btn-primary">
                    <i class="fas fa-user-plus"></i> Add New Client
                </button>
            </div>

            <div id="addClientForm" class="add-client-form">
                <h3 style="margin-top:0; margin-bottom:14px; color:var(--navy); font-size:16px; font-weight:700;">Quick Onboard</h3>
                <form method="POST" style="display: flex; gap: 14px; flex-wrap:wrap;">
                    <input type="text" name="name" placeholder="Client Name" required style="flex:2; min-width:200px; padding:11px 14px; border:1.5px solid var(--border); border-radius:10px; font-family:inherit; background:#f8fafc;">
                    <input type="text" name="pass" placeholder="Password" required style="flex:1; min-width:150px; padding:11px 14px; border:1.5px solid var(--border); border-radius:10px; font-family:inherit; background:#f8fafc;">
                    <button name="quick_create" class="btn-primary" style="background:var(--orange)">Save Client</button>
                </form>
            </div>

            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="clientSearch" onkeyup="filterTable()" placeholder="Search clients by ID, firm name, owner, phone, GST, or PAN...">
            </div>

            <div class="table-card">
                <table id="clientTable">
                    <thead>
                        <tr>
                            <th>Client ID</th>
                            <th>Login Details</th>
                            <th>Firm Details</th>
                            <th>Contact</th>
                            <th>Tax Info</th>
                            <th style="text-align:center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT u.identifier, u.password, cp.* FROM users u 
                                LEFT JOIN client_profiles cp ON u.identifier = cp.client_id 
                                WHERE u.role='client' ORDER BY u.id DESC";

                        $res = $conn->query($sql);
                        while ($row = $res->fetch_assoc()):
                        ?>
                            <tr>
                                <td><span class="id-badge"><?php echo htmlspecialchars($row['identifier']); ?></span></td>

                                <td style="font-size: 12px; line-height: 1.5;">
                                    <i class="fas fa-user" style="color:var(--navy);"></i> <b><?php echo htmlspecialchars($row['identifier']); ?></b><br>
                                    <i class="fas fa-lock" style="color:var(--orange);"></i> <?php echo htmlspecialchars($row['password']); ?>
                                </td>

                                <td>
                                    <b style="color:var(--text-main);"><?php echo htmlspecialchars($row['company_name'] ?: 'Not Set'); ?></b><br>
                                    <small style="color:var(--text-muted);"><?php echo htmlspecialchars($row['owner_name']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['phone'] ?: '-'); ?><br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($row['business_email'] ?: '-'); ?></small></td>
                                <td><small style="color:var(--text-muted);">GST: <b style="color:var(--text-main);"><?php echo htmlspecialchars($row['gst_no'] ?: '-'); ?></b><br>PAN: <b style="color:var(--text-main);"><?php echo htmlspecialchars($row['pan_no'] ?: '-'); ?></b></small></td>

                                <td style="text-align: center; white-space:nowrap;">
                                    <?php
                                    $clientJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <a href="javascript:void(0)" class="action-btn btn-view" onclick='openViewModal(<?php echo $clientJson; ?>)' title="View Profile">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <a href="client-profile.php?id=<?php echo urlencode($row['identifier']); ?>" class="action-btn btn-edit" title="Edit Profile">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>

                                    <a href="?delete=<?php echo urlencode($row['identifier']); ?>" class="action-btn btn-delete" onclick="return confirm('Delete this client?')" title="Delete Client">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- View Client Modal -->
    <div id="viewModal" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); backdrop-filter:blur(4px);">
        <div class="modal-content" style="background:white; margin:4vh auto; width:100%; max-width:820px; border-radius:18px; position:relative; box-shadow:var(--shadow-lg); overflow:hidden; border:1px solid var(--border);">

            <div style="background: var(--navy); padding: 20px 24px; color: white; display: flex; align-items: center; gap: 18px;">
                <img id="v_photo" src="" style="width:68px; height:68px; border-radius:12px; object-fit:cover; border:2px solid var(--orange); background:white;">
                <div>
                    <h2 id="v_company_title" style="margin:0; font-size: 19px; font-weight:800;"></h2>
                    <span id="v_id_badge" style="background:rgba(255,255,255,0.16); color:white; padding:3px 9px; border-radius:6px; font-size:12px; font-family:monospace; display:inline-block; margin-top:4px;"></span>
                </div>
                <span onclick="closeModal()" style="margin-left:auto; font-size:26px; cursor:pointer; color:rgba(255,255,255,0.75); line-height:1;">&times;</span>
            </div>

            <div style="padding: 24px; display: flex; gap: 24px; flex-wrap:wrap;">

                <div style="flex: 1.5; min-width:280px; border-right: 1px solid var(--border); padding-right: 24px;">
                    <h4 style="margin: 0 0 14px 0; color: var(--orange); text-transform: uppercase; font-size: 11px; letter-spacing: 1px; font-weight:800;">Basic Information</h4>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div>
                            <label style="font-size:10px; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Owner Name</label>
                            <p id="v_owner" style="margin:4px 0; font-weight:600; color:var(--text-main); font-size:13.5px;"></p>
                        </div>
                        <div>
                            <label style="font-size:10px; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Phone</label>
                            <p id="v_phone" style="margin:4px 0; font-weight:600; color:var(--text-main); font-size:13.5px;"></p>
                        </div>
                        <div>
                            <label style="font-size:10px; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Business Nature</label>
                            <p id="v_nature" style="margin:4px 0; font-weight:600; color:var(--text-main); font-size:13.5px;"></p>
                        </div>
                        <div>
                            <label style="font-size:10px; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Aadhaar No</label>
                            <p id="v_aadhaar" style="margin:4px 0; font-weight:600; color:var(--text-main); font-size:13.5px;"></p>
                        </div>
                    </div>

                    <div style="margin-top: 18px;">
                        <label style="font-size:10px; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Office Address</label>
                        <p id="v_address" style="margin:5px 0 0 0; font-size:13px; color:var(--text-muted); line-height: 1.45;"></p>
                    </div>

                    <div id="v_custom_fields_container" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top: 18px;">
                    </div>
                </div>

                <div style="flex: 1; min-width:240px;">
                    <h4 style="margin: 0 0 14px 0; color: var(--orange); text-transform: uppercase; font-size: 11px; letter-spacing: 1px; font-weight:800;">Tax Credentials</h4>

                    <div style="background: #f8fafc; padding: 14px; border-radius: 12px; display: grid; grid-template-columns: 1fr; gap: 11px; border: 1px solid var(--border);">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-size:11px; color:var(--text-muted); font-weight:700;">GSTIN</span>
                            <span id="v_gst" style="font-family:monospace; font-weight:700; color:var(--navy);"></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-size:11px; color:var(--text-muted); font-weight:700;">PAN</span>
                            <span id="v_pan" style="font-family:monospace; font-weight:700; color:var(--navy);"></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-size:11px; color:var(--text-muted); font-weight:700;">TAN</span>
                            <span id="v_tan" style="font-family:monospace; font-weight:700; color:var(--navy);"></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-size:11px; color:var(--text-muted); font-weight:700;">CIN</span>
                            <span id="v_cin" style="font-family:monospace; font-weight:700; color:var(--navy);"></span>
                        </div>
                    </div>

                    <div style="margin-top: 20px;">
                        <h4 style="margin: 0 0 10px 0; color: var(--orange); text-transform: uppercase; font-size: 11px; letter-spacing: 1px; font-weight:800;">Requested Task</h4>
                        <div style="background:#fff7ed; padding:12px; border-radius:10px; border:1px solid #ffedd5; min-height: 80px;">
                            <p id="v_task" style="margin:0; font-size:13px; color:#7c2d12; line-height:1.5; font-weight:500;"></p>
                        </div>
                    </div>
                </div>

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

        function toggleAddForm() {
            const form = document.getElementById('addClientForm');
            form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
        }

        function filterTable() {
            let filter = document.getElementById("clientSearch").value.toUpperCase();
            let tr = document.getElementById("clientTable").getElementsByTagName("tr");
            for (let i = 1; i < tr.length; i++) {
                tr[i].style.display = tr[i].textContent.toUpperCase().indexOf(filter) > -1 ? "" : "none";
            }
        }

        function openViewModal(data) {
            document.getElementById('v_photo').src = data.profile_pic ? '../uploads/client_pics/' + data.profile_pic : '../uploads/client_pics/default-company.png';

            document.getElementById('v_company_title').innerText = data.company_name || 'Individual Client';
            document.getElementById('v_id_badge').innerText = 'ID: ' + data.identifier;
            document.getElementById('v_owner').innerText = data.owner_name || 'N/A';
            document.getElementById('v_nature').innerText = data.business_nature || 'Not Specified';
            document.getElementById('v_aadhaar').innerText = data.aadhaar_no || 'Not Provided';
            document.getElementById('v_phone').innerText = data.phone || 'N/A';
            document.getElementById('v_gst').innerText = data.gst_no || 'N/A';
            document.getElementById('v_pan').innerText = data.pan_no || 'N/A';
            document.getElementById('v_task').innerText = data.task_asked || 'No pending tasks recorded.';
            document.getElementById('v_address').innerText = data.address || 'No address provided.';
            document.getElementById('v_tan').innerText = data.tan_no || 'N/A';
            document.getElementById('v_cin').innerText = data.cin_no || 'N/A';

            // --- RENDER CUSTOM BOXES IN MODAL ---
            const customContainer = document.getElementById('v_custom_fields_container');
            customContainer.innerHTML = '';

            if (data.custom_fields) {
                try {
                    const fields = JSON.parse(data.custom_fields);
                    for (const [label, value] of Object.entries(fields)) {
                        customContainer.innerHTML += `
                            <div style="background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
                                <label style="font-size:9px; font-weight:800; color:var(--text-muted); text-transform:uppercase;">${label}</label>
                                <p style="margin:2px 0 0 0; font-size:13px; font-weight:700; color:var(--navy);">${value || 'N/A'}</p>
                            </div>`;
                    }
                } catch (e) {
                    console.error("JSON Parse Error", e);
                }
            }

            document.getElementById('viewModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        window.onclick = function(event) {
            let modal = document.getElementById('viewModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>