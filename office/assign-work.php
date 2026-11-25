<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

// --- COMBINED MASTER DISPATCH LOGIC ---
if (isset($_POST['master_dispatch'])) {
    $client_id = mysqli_real_escape_string($conn, $_POST['client_id']);
    $staff_email = mysqli_real_escape_string($conn, $_POST['staff_email']);
    $service_name = mysqli_real_escape_string($conn, $_POST['service_name']);
    $instruction = mysqli_real_escape_string($conn, $_POST['instruction']);
    $doc_type = mysqli_real_escape_string($conn, $_POST['doc_type']);

    // 1. Create the Task with Assigned Date (CURDATE())
    $sql_task = "INSERT INTO service_requests (client_id, service_type, description, assigned_to, status, assigned_date, created_at) 
                 VALUES ('$client_id', '$service_name', '$instruction', '$staff_email', 'Assigned', CURDATE(), NOW())";
    $conn->query($sql_task);

    // 2. IF FILES ARE UPLOADED
    if (!empty($_FILES["doc_file"]["name"][0])) {
        $target_dir = "../uploads/center/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $total_files = count($_FILES["doc_file"]["name"]);
        $success_count = 0;

        for ($i = 0; $i < $total_files; $i++) {
            $original_name = $_FILES["doc_file"]["name"][$i];
            $file_tmp = $_FILES["doc_file"]["tmp_name"][$i];

            // Unique filename: time + index + name
            $file_name = time() . "_" . $i . "_" . basename($original_name);

            if (move_uploaded_file($file_tmp, $target_dir . $file_name)) {
                $sql_doc = "INSERT INTO client_documents 
                            (client_id, file_name, assigned_to, status, doc_category, category, instruction, created_at) 
                            VALUES 
                            ('$client_id', '$file_name', '$staff_email', 'In-Progress', '$doc_type', '$service_name', '$instruction', NOW())";
                $conn->query($sql_doc);
                $success_count++;
            }
        }
        echo "<script>alert('$success_count Documents & Task dispatched successfully!'); window.location='assign-work.php';</script>";
    } else {
        echo "<script>alert('Manual Task assigned (No documents attached)'); window.location='assign-work.php';</script>";
    }
}

// --- LOGIC 2: Assign an EXISTING Client Request ---
if (isset($_POST['assign_existing'])) {
    $rid = mysqli_real_escape_string($conn, $_POST['rid']);
    $staff = mysqli_real_escape_string($conn, $_POST['staff_email']);
    // Update includes setting the assigned_date
    $conn->query("UPDATE service_requests SET assigned_to='$staff', status='Assigned', assigned_date=CURDATE() WHERE id='$rid'");
    echo "<script>alert('Client Request Assigned!'); window.location='assign-work.php';</script>";
}

// --- LOGIC 4: Delete Records ---
if (isset($_GET['delete_task'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete_task']);
    $conn->query("DELETE FROM service_requests WHERE id='$id'");
    echo "<script>window.location='assign-work.php';</script>";
}

if (isset($_GET['delete_doc'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete_doc']);
    $conn->query("DELETE FROM client_documents WHERE id='$id'");
    echo "<script>window.location='assign-work.php';</script>";
}
?>
<?php
// Fetch clients grouped by service
$map_query = $conn->query("SELECT u.identifier, cp.company_name, cp.task_asked 
                           FROM users u 
                           JOIN client_profiles cp ON u.identifier = cp.client_id 
                           WHERE u.role='client'");
$serviceMap = [];
while ($row = $map_query->fetch_assoc()) {
    $displayName = !empty($row['company_name']) ? $row['company_name'] : $row['identifier'];
    $services = explode(', ', $row['task_asked']);
    foreach ($services as $s) {
        $s = trim($s);
        if ($s != "") {
            $serviceMap[$s][] = ['id' => $row['identifier'], 'name' => $displayName];
        }
    }
}
?>
<script>const serviceMap = <?php echo json_encode($serviceMap); ?>;</script>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Work | KKA Admin</title>
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

        .section-heading {
            margin: 32px 0 16px 0;
            color: var(--navy);
            font-size: 18px;
            font-weight: 800;
        }

        .card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }

        input, select, textarea {
            width: 100%;
            padding: 11px 14px;
            margin: 6px 0;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            box-sizing: border-box;
            font-family: inherit;
            font-size: 13.5px;
            color: var(--text-main);
            background: #f8fafc;
            transition: all 0.2s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--navy);
            background: #ffffff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(11, 60, 116, 0.1);
        }

        .btn-dispatch {
            background: var(--navy);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-family: inherit;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
        }

        .btn-dispatch:hover {
            background: var(--orange);
            transform: translateY(-1px);
        }

        .task-item {
            background: var(--card-bg);
            padding: 16px 20px;
            border-radius: 14px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            border: 1px solid var(--border);
            border-left: 4px solid var(--orange);
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
            font-size: 13px;
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
    <datalist id="service_list">
        <option value="GST Monthly Return">
        <option value="ITR Filing">
        <option value="TDS Return">
        <option value="Accounting/Bookkeeping">
        <option value="Company Incorporation">
    </datalist>

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
        <a href="assign-work.php" class="active"><i class="fas fa-tasks"></i> Assign Work</a>

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
                <h1>Work Assignment Center</h1>
                <div class="welcome-text">Dispatch tasks &amp; files to staff, assign pending client requests, and monitor live assignments</div>
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
            <div class="card">
                <h3 style="margin:0 0 20px 0; color:var(--navy); font-weight:800;"><i class="fas fa-paper-plane" style="color:var(--orange); margin-right:6px;"></i> Master Work Dispatch</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                            <div>
                                <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Services</label>
                                <select name="service_name" id="service_selector" onchange="filterClients()" required>
                                    <option value="">-- Select Service --</option>
                                    <?php foreach (array_keys($serviceMap) as $sName): ?>
                                        <option value="<?php echo htmlspecialchars($sName); ?>"><?php echo htmlspecialchars($sName); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Target Client</label>
                                <select name="client_id" id="client_selector" required>
                                    <option value="">-- Select Service First --</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Assign To Staff</label>
                            <select name="staff_email" required>
                                <option value="">-- Select Employee --</option>
                                <?php
                                $staff_query = $conn->query("SELECT name, identifier FROM users WHERE role='office'");
                                while ($s = $staff_query->fetch_assoc()) echo "<option value='" . htmlspecialchars($s['identifier']) . "'>" . htmlspecialchars($s['name']) . "</option>";
                                ?>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top:12px;">
                        <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Service Category</label>
                        <input type="text" name="service_name" list="service_list" placeholder="e.g. GST Filing..." required>
                    </div>

                    <div style="margin-top:12px;">
                        <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Instructions</label>
                        <textarea name="instruction" placeholder="Special Work Instructions..." rows="2" required></textarea>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; background: #f8fafc; padding: 16px; border-radius: 12px; border:1px solid var(--border); margin-top:12px;">
                        <div>
                            <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Upload Document (Optional)</label>
                            <input type="file" name="doc_file[]" multiple style="background:#ffffff;">
                        </div>
                        <div>
                            <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Type</label>
                            <select name="doc_type" style="background:#ffffff;">
                                <option value="center">Center (Active)</option>
                                <option value="vault">Vault (Permanent)</option>
                            </select>
                        </div>
                    </div>
                    <button name="master_dispatch" class="btn-dispatch" style="margin-top:18px;">Dispatch Work</button>
                </form>
            </div>

            <h2 class="section-heading">Incoming Client Requests</h2>
            <?php
            $res = $conn->query("SELECT t.*, cp.company_name 
                         FROM service_requests t 
                         LEFT JOIN client_profiles cp ON t.client_id = cp.client_id 
                         WHERE t.status='Pending'");
            if ($res->num_rows == 0) echo "<div class='card' style='color:var(--text-muted); font-size:13.5px;'>No pending client requests.</div>";
            while ($row = $res->fetch_assoc()): ?>
                <div class="task-item">
                    <div>
                        <b style="color:var(--navy); font-size:14.5px;"><?php echo htmlspecialchars($row['service_type']); ?></b>
                        <p style="margin:5px 0; color:var(--text-muted); font-size:13px;"><?php echo htmlspecialchars($row['description']); ?></p>
                        <small style="color:var(--text-muted); font-weight:600;">Client: <?php echo htmlspecialchars(!empty($row['company_name']) ? $row['company_name'] : $row['client_id']); ?></small>
                    </div>
                    <form method="POST" style="display:flex; gap:10px; align-items:center;">
                        <input type="hidden" name="rid" value="<?php echo htmlspecialchars($row['id']); ?>">
                        <select name="staff_email" required style="margin:0; width:170px; background:#ffffff;">
                            <?php $staff_query->data_seek(0);
                            while ($s = $staff_query->fetch_assoc()) echo "<option value='" . htmlspecialchars($s['identifier']) . "'>" . htmlspecialchars($s['name']) . "</option>"; ?>
                        </select>
                        <button name="assign_existing" style="background:var(--orange); color:white; border:none; padding:10px 18px; border-radius:10px; cursor:pointer; font-family:inherit; font-weight:700; font-size:13px;">Assign</button>
                    </form>
                </div>
            <?php endwhile; ?>

            <h2 class="section-heading">Live Assignment Monitor</h2>

            <div class="card" style="margin-bottom: 16px; padding: 14px 18px;">
                <div style="display: flex; gap: 14px; align-items: center;">
                    <div style="flex: 1; position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 13px; top: 50%; transform:translateY(-50%); color: #94a3b8;"></i>
                        <input type="text" id="assignmentSearch" onkeyup="filterAssignments()"
                            placeholder="Search by Client ID, Employee Name, or Service..."
                            style="padding-left: 36px; margin: 0;">
                    </div>
                    <button onclick="resetSearch()" style="background: #f1f5f9; border: 1px solid var(--border); padding: 10px 16px; border-radius: 10px; cursor: pointer; color: var(--navy); font-family:inherit; font-weight:600; font-size:13px;">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </div>

            <div class="card" style="overflow-x:auto; padding:20px;">
                <table id="assignmentTable">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Client</th>
                            <th>Assigned To</th>
                            <th>Assigned Date</th>
                            <th>Completed Date</th>
                            <th>Status</th>
                            <th>Details</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tasks = $conn->query("SELECT t.*, cp.company_name 
                             FROM service_requests t
                             LEFT JOIN client_profiles cp ON t.client_id = cp.client_id 
                             ORDER BY t.id DESC LIMIT 20");
                        while ($t = $tasks->fetch_assoc()): ?>
                            <tr class="table-row">
                                <td><span style="background:#e0f2fe; color:#0369a1; padding:4px 9px; border-radius:6px; font-size:11px; font-weight:700;">TASK</span></td>
                                <td class="client-cell">
                                    <b><?php echo htmlspecialchars(!empty($t['company_name']) ? $t['company_name'] : $t['client_id']); ?></b>
                                    <br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($t['client_id']); ?></small>
                                </td>
                                <td class="staff-cell"><b><?php echo htmlspecialchars($t['assigned_to']); ?></b></td>
                                <td><?php echo htmlspecialchars($t['assigned_date']); ?></td>
                                <td><?php echo htmlspecialchars($t['completed_date']); ?></td>
                                <td><span style="color:var(--orange); font-weight:700;"><?php echo htmlspecialchars($t['status']); ?></span></td>
                                <td class="service-cell"><?php echo htmlspecialchars($t['service_type']); ?></td>
                                <td><a href="?delete_task=<?php echo urlencode($t['id']); ?>" onclick="return confirm('Delete this assignment?')" style="color:var(--danger);"><i class="fas fa-trash"></i></a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
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

        function filterAssignments() {
            const input = document.getElementById('assignmentSearch').value.toLowerCase();
            const rows = document.querySelectorAll('.table-row');
            rows.forEach(row => {
                const clientId = row.querySelector('.client-cell').textContent.toLowerCase();
                const staffName = row.querySelector('.staff-cell').textContent.toLowerCase();
                const service = row.querySelector('.service-cell').textContent.toLowerCase();
                if (clientId.includes(input) || staffName.includes(input) || service.includes(input)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        function resetSearch() {
            document.getElementById('assignmentSearch').value = "";
            filterAssignments();
        }

        function filterClients() {
            const service = document.getElementById('service_selector').value;
            const clientDropdown = document.getElementById('client_selector');

            // Clear existing options
            clientDropdown.innerHTML = '<option value="">-- Select Client --</option>';

            if (service && serviceMap[service]) {
                serviceMap[service].forEach(client => {
                    const opt = document.createElement('option');
                    opt.value = client.id;
                    opt.textContent = `${client.name} (${client.id})`;
                    clientDropdown.appendChild(opt);
                });
            } else {
                const opt = document.createElement('option');
                opt.textContent = "No clients for this service";
                opt.disabled = true;
                clientDropdown.appendChild(opt);
            }
        }
    </script>
</body>
</html>