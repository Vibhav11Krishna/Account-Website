<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Login.php");
    exit();
}

$status_msg = "";
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'released') $status_msg = "Document released to Client Vault successfully!";
    if ($_GET['msg'] == 'rejected') $status_msg = "Document sent back to staff with feedback.";
}

// Logic for Release/Reject stays the same
if (isset($_POST['release_file'])) {
    $id = mysqli_real_escape_string($conn, $_POST['doc_id']);
    $sql = "UPDATE client_documents SET status='Released', admin_note=NULL WHERE id='$id'";
    if ($conn->query($sql)) {
        header("Location: admin-review.php?msg=released");
        exit();
    }
}

if (isset($_POST['reject_file'])) {
    $id = mysqli_real_escape_string($conn, $_POST['doc_id']);
    $note = mysqli_real_escape_string($conn, $_POST['reject_note']);
    $sql = "UPDATE client_documents SET status='Assigned', admin_note='$note' WHERE id='$id'";
    if ($conn->query($sql)) {
        header("Location: admin-review.php?msg=rejected");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review &amp; Release | KKA Admin</title>
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

        /* Segmented Tab Styling */
        .tab-wrapper {
            display: flex;
            background: #e2e8f0;
            padding: 5px;
            border-radius: 14px;
            width: fit-content;
            margin-bottom: 24px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
            flex-wrap: wrap;
        }

        .tab-trigger {
            padding: 10px 22px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            font-size: 13px;
            color: var(--text-muted);
            background: transparent;
            transition: all 0.2s ease;
        }

        .tab-trigger.active-tab {
            background: var(--navy);
            color: white;
            box-shadow: 0 4px 10px rgba(11, 60, 116, 0.2);
        }

        .section-content {
            display: none;
        }

        .section-content.active-section {
            display: block;
        }

        /* Folder & Card UI */
        .folder-group {
            background: var(--card-bg);
            margin-bottom: 14px;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .folder-header {
            background: #f8fafc;
            padding: 16px 22px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            transition: background 0.2s;
        }

        .folder-header:hover {
            background: #f1f5f9;
        }

        .folder-content {
            display: none;
            padding: 20px;
            background: #ffffff;
        }

        .review-card {
            background: #ffffff;
            padding: 18px 20px;
            border-radius: 14px;
            border: 1px solid var(--border);
            margin-bottom: 14px;
            border-left: 4px solid var(--navy);
            gap: 20px;
            flex-wrap: wrap;
        }

        .review-card:last-child {
            margin-bottom: 0;
        }

        .btn {
            padding: 9px 16px;
            border-radius: 10px;
            font-family: inherit;
            font-weight: 700;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 12.5px;
            transition: all 0.2s;
        }

        .btn-approve {
            background: #16a34a;
            color: white;
            width: 100%;
        }

        .btn-approve:hover {
            background: #15803d;
        }

        .btn-reject {
            background: var(--danger);
            color: white;
            width: 100%;
        }

        .btn-reject:hover {
            background: #dc2626;
        }

        .btn-view {
            background: #f1f5f9;
            color: var(--navy);
            text-decoration: none;
            border: 1px solid var(--border);
        }

        .btn-view:hover {
            background: #e2e8f0;
        }

        #globalSearch {
            width: 100%;
            padding: 12px 18px;
            border-radius: 12px;
            border: 1.5px solid var(--border);
            background: var(--card-bg);
            box-shadow: var(--shadow-sm);
            font-family: inherit;
            font-size: 13.5px;
            color: var(--text-main);
            margin-bottom: 20px;
            outline: none;
            transition: all 0.2s;
        }

        #globalSearch:focus {
            border-color: var(--navy);
            box-shadow: 0 0 0 3px rgba(11, 60, 116, 0.1);
        }

        .status-msg {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 700;
            font-size: 13.5px;
        }

        textarea {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            background: #f8fafc;
            font-family: inherit;
            font-size: 12.5px;
            margin: 8px 0;
            resize: none;
            outline: none;
        }

        textarea:focus {
            border-color: var(--navy);
            background: #ffffff;
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
                <a href="admin-review.php" class="active">Quality Control</a>
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
                <h1>Quality Control</h1>
                <div class="welcome-text">Manage documents grouped by Client Profile</div>
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
            <?php if ($status_msg) echo "<div class='status-msg'>" . htmlspecialchars($status_msg) . "</div>"; ?>

            <input type="text" id="globalSearch" onkeyup="filterEverything()" placeholder="Search clients, companies or files...">

            <div class="tab-wrapper">
                <button type="button" class="tab-trigger active-tab" onclick="openSection(this, 'pending-review')">Pending Review</button>
                <button type="button" class="tab-trigger" onclick="openSection(this, 'rejected-saved')">Saved (Rejected)</button>
                <button type="button" class="tab-trigger" onclick="openSection(this, 'released-vault')">Released Vault</button>
            </div>

            <div id="pending-review" class="section-content active-section">
                <?php renderGroupedSections($conn, "cd.status = 'Pending Review'", "pending"); ?>
            </div>

            <div id="rejected-saved" class="section-content">
                <?php renderGroupedSections($conn, "cd.status = 'Assigned' AND cd.admin_note IS NOT NULL", "saved"); ?>
            </div>

            <div id="released-vault" class="section-content">
                <?php renderGroupedSections($conn, "cd.status = 'Released'", "released"); ?>
            </div>
        </div>
    </div>

    <?php
    function renderGroupedSections($conn, $condition, $section_key)
    {
        $sql = "SELECT cd.*, cp.company_name, cp.owner_name FROM client_documents cd 
            LEFT JOIN client_profiles cp ON cd.client_id = cp.client_id 
            WHERE $condition ORDER BY cd.id DESC";
        $result = $conn->query($sql);
        $grouped = [];
        while ($row = $result->fetch_assoc()) {
            $grouped[$row['client_id']]['info'] = ['name' => $row['owner_name'], 'firm' => $row['company_name']];
            $grouped[$row['client_id']]['docs'][] = $row;
        }

        if (empty($grouped)) {
            echo "<p style='color:var(--text-muted); padding:24px; background:var(--card-bg); border-radius:16px; border:1px dashed var(--border); font-size:13.5px;'>No items found in this category.</p>";
            return;
        }

        foreach ($grouped as $cid => $data) {
            $folderId = $section_key . "_" . $cid;
    ?>
            <div class="folder-group">
                <div class="folder-header" onclick="toggleFolder('<?php echo htmlspecialchars($folderId, ENT_QUOTES); ?>')">
                    <span>
                        <i class="fas fa-folder" style="color:#fbbf24; margin-right:12px;"></i>
                        <strong style="color:var(--navy);"><?php echo htmlspecialchars($data['info']['name']); ?></strong>
                        <small style="color:var(--text-muted); margin-left:6px;">(<?php echo htmlspecialchars($data['info']['firm']); ?>)</small>
                    </span>
                    <i class="fas fa-chevron-down" id="icon_<?php echo htmlspecialchars($folderId, ENT_QUOTES); ?>" style="color:var(--text-muted); transition:0.25s;"></i>
                </div>
                <div class="folder-content" id="<?php echo htmlspecialchars($folderId, ENT_QUOTES); ?>">
                    <?php foreach ($data['docs'] as $doc) { ?>
                        <div class="review-card" style="display:flex; justify-content:space-between; align-items:start;">
                            <div>
                                <h3 style="margin:0 0 6px 0; font-size:15px; font-weight:700; color:var(--navy);"><i class="fas fa-file-pdf" style="color:#ef4444; margin-right:6px;"></i> <?php echo htmlspecialchars($doc['result_file']); ?></h3>
                                <p style="font-size:12.5px; color:var(--text-muted); margin:0 0 14px 0;">Staff: <?php echo htmlspecialchars($doc['assigned_to']); ?></p>
                                <a href="../uploads/vault/<?php echo htmlspecialchars($doc['result_file']); ?>" target="_blank" class="btn btn-view"><i class="fas fa-eye"></i> View</a>
                            </div>
                            <?php if ($section_key == 'pending') { ?>
                                <div style="width:260px; max-width:100%;">
                                    <form method="POST">
                                        <input type="hidden" name="doc_id" value="<?php echo htmlspecialchars($doc['id']); ?>">
                                        <button type="submit" name="release_file" class="btn btn-approve"><i class="fas fa-check"></i> Release</button>
                                    </form>
                                    <form method="POST" style="margin-top:10px;">
                                        <input type="hidden" name="doc_id" value="<?php echo htmlspecialchars($doc['id']); ?>">
                                        <textarea name="reject_note" placeholder="Feedback..." required rows="2"></textarea>
                                        <button type="submit" name="reject_file" class="btn btn-reject"><i class="fas fa-times"></i> Reject</button>
                                    </form>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
    <?php }
    }
    ?>

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

        function openSection(btn, sectionId) {
            document.querySelectorAll('.tab-trigger').forEach(t => t.classList.remove('active-tab'));
            document.querySelectorAll('.section-content').forEach(s => s.classList.remove('active-section'));
            btn.classList.add('active-tab');
            document.getElementById(sectionId).classList.add('active-section');
        }

        function toggleFolder(id) {
            const content = document.getElementById(id);
            const icon = document.getElementById('icon_' + id);
            content.style.display = (content.style.display === "block") ? "none" : "block";
            if (icon) {
                icon.style.transform = (content.style.display === "block") ? "rotate(180deg)" : "rotate(0deg)";
            }
        }

        function filterEverything() {
            let input = document.getElementById('globalSearch').value.toLowerCase();
            document.querySelectorAll('.folder-group').forEach(folder => {
                folder.style.display = folder.innerText.toLowerCase().includes(input) ? "block" : "none";
            });
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