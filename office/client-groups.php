<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Login.php");
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
    
    /* ADD THIS LINE */
    border-right: 4px solid var(--orange); 
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


        /* Main Layout */
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

        .search-container {
            position: relative;
            width: 350px;
        }

        .search-container input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 1px solid var(--border);
            border-radius: 12px;
            outline: none;
            transition: 0.3s;
        }

        .search-container i {
            position: absolute;
            left: 15px;
            top: 15px;
            color: #94a3b8;
        }

        .search-container input:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.1);
        }

        .group-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .group-sidebar {
            width: 320px;
            background: white;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
        }

        .group-list {
            flex: 1;
            overflow-y: auto;
        }

        .group-item {
            padding: 15px 20px;
            cursor: pointer;
            transition: 0.2s;
            display: block;
            text-decoration: none;
            color: inherit;
            border-bottom: 1px solid #f8fafc;
        }

        .group-item.active {
            background: #eff6ff;
            border-right: 4px solid var(--orange);
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
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
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
            font-size: 13px;
        }

        .id-badge {
            background: #fff7ed;
            color: var(--orange);
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 6px;
            font-family: monospace;
        }

        .btn-view {
            background: var(--navy);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
        }

        /* Select Client Item UI */
        .client-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border: 1px solid #eee;
            border-radius: 10px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: 0.2s;
        }

        .client-card:hover {
            border-color: var(--orange);
            background: #fffaf5;
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
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="tableSearch" onkeyup="searchTable()" placeholder="Search firms in this group...">
            </div>
            <button onclick="openGroupModal()" style="background:var(--orange); color:white; border:none; padding:12px 25px; border-radius:10px; cursor:pointer; font-weight:700;">
                <i class="fas fa-plus"></i> Create New Group
            </button>
        </div>

        <div class="group-container">
            <div class="group-sidebar">
                <div style="padding:15px; background:#f1f5f9; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Custom Groups</div>
                <div class="group-list">
                    <?php while ($cg = $custom_group_res->fetch_assoc()): ?>
                        <div class="group-item-container" style="display:flex; align-items:center; justify-content:space-between; padding-right:15px; border-bottom:1px solid #f8fafc;" class="<?php echo ($selected_group == $cg['id']) ? 'active' : ''; ?>">
                            <a href="?group_id=<?php echo $cg['id']; ?>" class="group-item" style="flex:1; border:none;">
                                <i class="fas fa-folder" style="color:var(--orange); margin-right:10px;"></i>
                                <b><?php echo htmlspecialchars($cg['group_name']); ?></b>
                            </a>
                            <a href="delete-group.php?id=<?php echo $cg['id']; ?>"
                                onclick="return confirm('Are you sure you want to delete this group? The clients inside will NOT be deleted.')"
                                style="color:#fda4af; font-size:12px; transition:0.3s;"
                                onmouseover="this.style.color='#f43f5e'" onmouseout="this.style.color='#fda4af'">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

           <div class="main-content">
    <?php if ($selected_group > 0):
        $stmt = $conn->prepare("SELECT * FROM client_custom_groups WHERE id = ?");
        $stmt->bind_param("i", $selected_group);
        $stmt->execute();
        $group = $stmt->get_result()->fetch_assoc();

        if ($group):
            // FIX: Handle empty groups and quote string IDs
            if (!empty($group['client_ids'])) {
                // Convert KKA001,KKA002 into 'KKA001','KKA002'
                $ids_array = explode(',', $group['client_ids']);
                $quoted_ids = array_map(function($id) use ($conn) {
                    return "'" . mysqli_real_escape_string($conn, trim($id)) . "'";
                }, $ids_array);
                $final_ids = implode(',', $quoted_ids);

                $clients = $conn->query("SELECT * FROM client_profiles WHERE client_id IN ($final_ids)");
            } else {
                $clients = false; // Group exists but has no clients
            }
    ?>
                
                        <h2 style="margin-bottom:20px; color:var(--navy);"><?php echo htmlspecialchars($group['group_name']); ?></h2>
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
                <td><span class="id-badge"><?php echo $row['client_id']; ?></span></td>
                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                <td><?php echo htmlspecialchars($row['owner_name']); ?></td>
                <td style="text-align:center; display:flex; gap:8px; justify-content:center;">
                    <button onclick='openViewModal(<?php echo $json; ?>)' class="btn-view" style="padding: 7px 12px; border-radius:7px;">View</button>
                    
                    <a href="remove-client-from-group.php?group_id=<?php echo $selected_group; ?>&client_id=<?php echo $row['client_id']; ?>" 
                       onclick="return confirm('Remove this client?')"
                       style="background:#fee2e2; color:#ef4444; border:1px solid #fecaca; width:35px; height:35px; display:flex; align-items:center; justify-content:center; text-decoration:none; border-radius:7px;">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;">
                <i class="fas fa-users-slash" style="font-size:40px; margin-bottom:10px; display:block;"></i>
                No clients found in this group.
            </td>
        </tr>
    <?php endif; ?>
</tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="text-align:center; margin-top:150px; color:#cbd5e1;">
                        <i class="fas fa-layer-group" style="font-size:80px;"></i>
                        <h3>Select a group from the left to manage clients</h3>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="viewModal" class="modal">
        <div class="modal-content" style="background:white; margin:5vh auto; width:850px; border-radius:15px; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,0.3);">
            <div style="background:var(--navy); padding:20px; color:white; display:flex; justify-content:space-between; align-items:center;">
                <h3 id="v_title" style="margin:0;"></h3>
                <span onclick="closeModal()" style="cursor:pointer; font-size:28px;">&times;</span>
            </div>
            <div style="padding:30px; display:grid; grid-template-columns: 1fr 1fr; gap:30px;">
                <div>
                    <h4 style="color:var(--orange); border-bottom:1px solid #eee; padding-bottom:10px;">Basic Details</h4>
                    <p><b>Client ID:</b> <span id="v_id" class="id-badge"></span></p>
                    <p><b>Owner:</b> <span id="v_owner"></span></p>
                    <p><b>Phone:</b> <span id="v_phone"></span></p>
                    <p><b>Email:</b> <span id="v_email"></span></p>
                    <p><b>Nature:</b> <span id="v_nature"></span></p>
                    <p><b>Address:</b> <br><small id="v_address"></small></p>
                </div>
                <div>
                    <h4 style="color:var(--orange); border-bottom:1px solid #eee; padding-bottom:10px;">Tax & Registration</h4>
                    <p><b>GST No:</b> <span id="v_gst"></span></p>
                    <p><b>PAN No:</b> <span id="v_pan"></span></p>
                    <p><b>TAN No:</b> <span id="v_tan"></span></p>
                    <p><b>CIN No:</b> <span id="v_cin"></span></p>
                    <p><b>Aadhaar:</b> <span id="v_aadhaar"></span></p>
                    <div style="background:#f1f5f9; padding:10px; border-radius:8px; margin-top:15px;">
                        <b>Last Task Asked:</b><br>
                        <small id="v_task"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="groupModal" class="modal">
        <div class="modal-content" style="background:white; margin:3vh auto; width:600px; border-radius:15px; overflow:hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
            <div style="background:var(--navy); padding:20px; color:white; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-size:18px;">Create Custom Group</h3>
                <span onclick="closeGroupModal()" style="cursor:pointer; font-size:24px;">&times;</span>
            </div>

            <form action="save-group-logic.php" method="POST" style="padding:25px;">
                <label style="font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase;">Group Name</label>
                <input type="text" name="group_name" required placeholder="e.g. Monthly GST Group"
                    style="width:100%; padding:12px; border:1px solid #e2e8f0; border-radius:10px; margin:8px 0 20px 0; outline:none;">

                <label style="font-size:11px; font-weight:800; color:#94a3b8; text-transform:uppercase;">Search & Click to Add Clients</label>
                <div style="position:relative; margin:8px 0;">
                    <i class="fas fa-search" style="position:absolute; left:12px; top:13px; color:#cbd5e1;"></i>
                    <input type="text" id="modalClientSearch" onkeyup="filterModalClients()" placeholder="Type firm name or ID..."
                        style="width:100%; padding:10px 10px 10px 35px; border:1px solid #e2e8f0; border-radius:8px; outline:none; font-size:13px;">
                </div>

                <div id="modalClientList" style="max-height:300px; overflow-y:auto; border:1px solid #f1f5f9; border-radius:12px; background:#fcfcfc;">
                    <?php
                    $cl_list = $conn->query("SELECT client_id, company_name, owner_name FROM client_profiles ORDER BY company_name ASC");
                    while ($cl = $cl_list->fetch_assoc()): ?>

                        <label class="selectable-client-item" style="display:flex; align-items:center; gap:12px; padding:12px; border-bottom:1px solid #f1f5f9; cursor:pointer; transition:0.2s;">
                            <input type="checkbox" name="client_ids[]" value="<?php echo $cl['client_id']; ?>" style="display:none;" onchange="toggleSelectionUI(this)">

                            <div class="check-indicator" style="width:20px; height:20px; border:2px solid #cbd5e1; border-radius:50%; display:flex; align-items:center; justify-content:center; background:white;">
                                <i class="fas fa-check" style="font-size:10px; color:white; display:none;"></i>
                            </div>

                            <div style="flex:1;">
                                <div class="firm-title" style="font-size:14px; font-weight:700; color:var(--navy);"><?php echo htmlspecialchars($cl['company_name']); ?></div>
                                <div style="font-size:11px; color:#64748b;">ID: <b><?php echo $cl['client_id']; ?></b> | Owner: <?php echo htmlspecialchars($cl['owner_name']); ?></div>
                            </div>

                            <i class="fas fa-plus-circle" class="action-icon" style="color:#cbd5e1; font-size:16px;"></i>
                        </label>

                    <?php endwhile; ?>
                </div>

                <button type="submit" style="width:100%; margin-top:25px; background:var(--orange); color:white; border:none; padding:15px; border-radius:10px; font-weight:700; cursor:pointer; font-size:16px;">
                    Create Group with Selected Clients
                </button>
            </form>
        </div>
    </div>

    <style>
        /* Styling for the selected state */
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
            /* Optional: turn plus into a cross or just highlight */
        }
    </style>

    <script>
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
    </script>

    <script>
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
        window.onclick = function(e) {
            if (e.target.className === 'modal') {
                closeModal();
                document.getElementById('groupModal').style.display = 'none';
            }
        }


        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('status') === 'deleted') {
            alert("Group deleted successfully!");
        } else if (urlParams.get('status') === 'success') {
            alert("Group created successfully!");
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
        if(status === 'client_removed') alert('Client removed from this group.');
    </script>
</body>

</html>