<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Login.php");
    exit();
}

// Fetch all unique owners
$owner_query = "SELECT owner_name, business_email, COUNT(*) as count 
                FROM client_profiles 
                WHERE owner_name != ''
                GROUP BY owner_name 
                ORDER BY owner_name ASC";
$owner_res = $conn->query($owner_query);

$selected_owner = isset($_GET['owner']) ? mysqli_real_escape_string($conn, $_GET['owner']) : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Client Groups | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --navy: #0b3c74;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
            --border: #e2e8f0;
        }

        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            color: #334155;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar Styles */
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
        .active {
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

        .rotate-chevron {
            transform: rotate(180deg);
        }

        /* Layout */
        .content-area {
            margin-left: 280px;
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .top-header {
            background: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
        }

        .top-header h1 {
            margin: 0;
            font-size: 25px;
            color: var(--navy);
        }

        .search-bar {
            width: 350px;
            position: relative;
        }

        .search-bar input {
            width: 100%;
            padding: 10px 15px 10px 35px;
            border: 1px solid var(--border);
            border-radius: 10px;
            outline: none;
        }

        .search-bar i {
            position: absolute;
            left: 12px;
            top: 13px;
            color: #94a3b8;
        }

        .group-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .owner-column {
            width: 320px;
            background: white;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
        }

        .owner-list {
            flex: 1;
            overflow-y: auto;
        }

        .owner-item {
            padding: 15px 25px;
            cursor: pointer;
            border-right: 4px solid transparent;
            /* Changed from border-left */
            transition: 0.2s;
            display: block;
            text-decoration: none;
            color: inherit;
            border-bottom: 1px solid #f8fafc;
        }

        .owner-item:hover {
            background: #f8fafc;
        }

        .owner-item.active {
            background: #eff6ff;
            border-right-color: var(--orange);
            /* Changed from border-left-color */
            border-left: none;
            /* Ensure no left border remains */
        }

        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .table-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 12px;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 2px solid #f1f5f9;
        }

        td {
            padding: 15px 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13.5px;
        }

        .id-badge {
            background: #fff7ed;
            color: var(--orange);
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            font-family: monospace;
        }

        .btn-action {
            padding: 8px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: 0.2s;
        }

        .btn-view {
            background: #eff6ff;
            color: var(--navy);
        }

        .btn-edit {
            background: #fff7ed;
            color: var(--orange);
            margin-right: 5px;
        }

        /* 1. Ensure the list has scrolling enabled */
        .owner-list {
            flex: 1;
            overflow-y: auto;
            /* Enables vertical scrolling */
            overflow-x: hidden;
            /* Prevents horizontal shifting */
            padding-right: 5px;
            /* Space for the scrollbar */
        }

        /* 2. Custom Scrollbar Styling (Chrome, Edge, Safari) */
        .owner-list::-webkit-scrollbar {
            width: 6px;
        }

        .owner-list::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        .owner-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            /* Subtle gray */
            border-radius: 10px;
            transition: 0.3s;
        }

        .owner-list::-webkit-scrollbar-thumb:hover {
            background: var(--orange);
            /* Turns orange when hovering over the list area */
        }

        /* 3. Firefox Support */
        .owner-list {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
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
                <a href="client-groups.php" class="active">Client Groups</a>
                <a href="client-services.php">Services</a>
            </div>
        </div>
        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
        <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="content-area">
        <div class="top-header">
            <h1>Client Groups</h1>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="groupSearch" onkeyup="filterContent()" placeholder="Search Name, Email or Firm...">
            </div>
        </div>

        <div class="group-container">
            <div class="owner-column">
                <div class="owner-list">
                    <?php while ($owner = $owner_res->fetch_assoc()): ?>
                        <a href="?owner=<?php echo urlencode($owner['owner_name']); ?>"
                            class="owner-item <?php echo ($selected_owner == $owner['owner_name']) ? 'active' : ''; ?>"
                            data-search="<?php echo htmlspecialchars($owner['owner_name'] . ' ' . $owner['business_email']); ?>">

                            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                                <div style="display:flex; align-items:center; gap:12px; flex: 1; min-width: 0;">
                                    <div style="width:38px; height:38px; flex-shrink:0; background:<?php echo ($selected_owner == $owner['owner_name']) ? 'var(--orange)' : '#f1f5f9'; ?>; color:<?php echo ($selected_owner == $owner['owner_name']) ? 'white' : 'var(--navy)'; ?>; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:14px; transition: 0.3s;">
                                        <i class="fas fa-user-tie"></i>
                                    </div>

                                    <div style="flex: 1; min-width: 0;">
                                        <b style="font-size:16px; display:block; color:var(--navy); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height:1.2;">
                                            <?php echo $owner['owner_name']; ?>
                                        </b>
                                        <div style="display:flex; align-items:center; gap:6px; margin-top:3px;">
                                            <i class="fas fa-envelope" style="font-size:12px; color:#94a3b8;"></i>
                                            <small style="color:#64748b; font-size:11.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?php echo $owner['business_email'] ?: 'No Email'; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <span style="background:<?php echo ($selected_owner == $owner['owner_name']) ? 'rgba(255,140,0,0.15)' : '#f1f5f9'; ?>; color:<?php echo ($selected_owner == $owner['owner_name']) ? 'var(--orange)' : '#64748b'; ?>; padding:4px 10px; border-radius:8px; font-size:11px; font-weight:700; white-space:nowrap;">
                                    <?php echo $owner['count']; ?>
                                </span>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="main-content">
                <?php if ($selected_owner): ?>
                    <div class="table-card">
                        <table id="firmTable">
    <thead>
        <tr>
            <th>Firm Name</th>
            <th>Client ID</th>
            <th>Phone Number</th> <th>Tax Info</th>
            <th style="text-align:center">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $biz_sql = "SELECT * FROM client_profiles WHERE owner_name = '$selected_owner'";
        $biz_res = $conn->query($biz_sql);
        while ($row = $biz_res->fetch_assoc()):
            $clientJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
        ?>
            <tr class="firm-row" data-firm="<?php echo htmlspecialchars($row['company_name']); ?>">
                <td><b><?php echo $row['company_name'] ?: 'N/A'; ?></b></td>
                <td><span class="id-badge"><?php echo $row['client_id']; ?></span></td>
                
                <td style="color: var(--navy); font-weight: 500;">
                    <i class="fas fa-phone-alt" style="font-size: 11px; color: #94a3b8; margin-right: 5px;"></i>
                    <?php echo $row['phone'] ?: 'N/A'; ?>
                </td>

                <td><small>GST: <?php echo $row['gst_no'] ?: '-'; ?><br>PAN: <?php echo $row['pan_no'] ?: '-'; ?></small></td>
                <td style="text-align:center;">
                    <a href="client-profile.php?id=<?php echo $row['client_id']; ?>" class="btn-action btn-edit">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button onclick='openViewModal(<?php echo $clientJson; ?>)' class="btn-action btn-view">
                        <i class="fas fa-eye"></i> View
                    </button>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; margin-top:150px; color:#cbd5e1;">
                        <i class="fas fa-users-cog" style="font-size:60px; margin-bottom:20px;"></i>
                        <h2>Select an enterprise owner to view details</h2>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="viewModal" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.6); backdrop-filter:blur(5px);">
        <div class="modal-content" style="background:white; margin:2% auto; padding:30px; width:600px; border-radius:20px; position:relative; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
            <span onclick="closeModal()" style="position:absolute; right:25px; top:20px; font-size:28px; cursor:pointer; color:#94a3b8;">&times;</span>

            <div style="text-align:center; margin-bottom:20px;">
                <img id="v_photo" src="" style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid #ff8c00; margin-bottom:10px;">
                <h2 id="v_company_title" style="margin:0; color:#0b3c74;"></h2>
                <span id="v_id_badge" class="id-badge"></span>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; border-top:1px solid #f1f5f9; padding-top:20px;">
                <div>
                    <label style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase;">Owner Name</label>
                    <p id="v_owner" style="margin:5px 0 15px 0; font-weight:600;"></p>
                </div>
                <div>
                    <label style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase;">Phone</label>
                    <p id="v_phone" style="margin:5px 0 15px 0; font-weight:600;"></p>
                </div>
                <div>
                    <label style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase;">Nature of Business</label>
                    <p id="v_nature" style="margin:5px 0 15px 0; font-weight:600;"></p>
                </div>
                <div>
                    <label style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase;">Aadhaar No</label>
                    <p id="v_aadhaar" style="margin:5px 0 15px 0; font-weight:600;"></p>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:10px; background: #f1f5f9; padding: 15px; border-radius: 12px; margin-top: 5px;">
                <div>
                    <label style="font-size:9px; font-weight:800; color:#64748b; text-transform:uppercase;">GST No</label>
                    <p id="v_gst" style="margin:2px 0 0 0; font-size:13px; font-weight:700; color:var(--navy);"></p>
                </div>
                <div>
                    <label style="font-size:9px; font-weight:800; color:#64748b; text-transform:uppercase;">PAN No</label>
                    <p id="v_pan" style="margin:2px 0 0 0; font-size:13px; font-weight:700; color:var(--navy);"></p>
                </div>
                <div>
                    <label style="font-size:9px; font-weight:800; color:#64748b; text-transform:uppercase;">TAN No</label>
                    <p id="v_tan" style="margin:2px 0 0 0; font-size:13px; font-weight:700; color:var(--navy);"></p>
                </div>
                <div style="margin-top:10px;">
                    <label style="font-size:9px; font-weight:800; color:#64748b; text-transform:uppercase;">CIN No</label>
                    <p id="v_cin" style="margin:2px 0 0 0; font-size:13px; font-weight:700; color:var(--navy);"></p>
                </div>
                <div style="margin-top:10px;">
                    <label style="font-size:9px; font-weight:800; color:#64748b; text-transform:uppercase;">TIN / VAT</label>
                    <p id="v_tin" style="margin:2px 0 0 0; font-size:13px; font-weight:700; color:var(--navy);"></p>
                </div>
            </div>

            <div style="margin-top:15px; padding:15px; background:#fff7ed; border-radius:12px; border:1px solid #ffedd5;">
                <label style="font-size:10px; font-weight:800; color:var(--orange); text-transform:uppercase;">Task / Service Requested</label>
                <p id="v_task" style="margin:5px 0 0 0; font-size:14px; color:#1e293b; line-height:1.5; font-weight:500;"></p>
            </div>

            <div style="margin-top:15px;">
                <label style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase;">Office Address</label>
                <p id="v_address" style="margin:5px 0 0 0; font-size:13px; color:#475569;"></p>
            </div>
        </div>
    </div>

    <script>
        function toggleMenu(menuId, chevronId) {
            document.getElementById(menuId).classList.toggle('show-menu');
            document.getElementById(chevronId).classList.toggle('rotate-chevron');
        }

        function filterContent() {
            let filter = document.getElementById("groupSearch").value.toUpperCase();

            // Filter Owners
            let items = document.getElementsByClassName("owner-item");
            for (let i = 0; i < items.length; i++) {
                let text = items[i].getAttribute('data-search').toUpperCase();
                items[i].style.display = (text.indexOf(filter) > -1) ? "" : "none";
            }

            // Filter Table Rows
            let rows = document.querySelectorAll(".firm-row");
            rows.forEach(row => {
                let firm = row.getAttribute('data-firm').toUpperCase();
                row.style.display = (firm.indexOf(filter) > -1) ? "" : "none";
            });
        }

        function openViewModal(data) {
            document.getElementById('v_photo').src = data.profile_pic ? '../uploads/client_pics/' + data.profile_pic : '../uploads/client_pics/default-company.png';
            document.getElementById('v_company_title').innerText = data.company_name || 'Individual Client';
            document.getElementById('v_id_badge').innerText = 'ID: ' + (data.client_id || data.identifier);
            document.getElementById('v_owner').innerText = data.owner_name || 'N/A';
            document.getElementById('v_phone').innerText = data.phone || 'N/A';
            document.getElementById('v_nature').innerText = data.business_nature || 'Not Specified';
            document.getElementById('v_aadhaar').innerText = data.aadhaar_no || 'Not Provided';
            document.getElementById('v_gst').innerText = data.gst_no || 'N/A';
            document.getElementById('v_pan').innerText = data.pan_no || 'N/A';
            document.getElementById('v_tan').innerText = data.tan_no || 'N/A';
            document.getElementById('v_cin').innerText = data.cin_no || 'N/A';
            document.getElementById('v_tin').innerText = data.tin_no || 'N/A';
            document.getElementById('v_task').innerText = data.task_asked || 'No pending tasks recorded.';
            document.getElementById('v_address').innerText = data.address || 'No address provided.';
            document.getElementById('viewModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('viewModal').style.display = 'none';
        }
        window.onclick = function(e) {
            if (e.target == document.getElementById('viewModal')) closeModal();
        }
    </script>
</body>

</html>