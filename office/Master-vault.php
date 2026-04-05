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
    <title>Master Vault | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --navy: #0b3c74;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
        }

        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            color: #334155;
        }

        /* Sidebar Styling */
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
        }

        .sidebar h2 {
            color: var(--orange);
            font-size: 22px;
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

        .rotate-chevron {
            transform: rotate(180deg);
        }

        .main {
            margin-left: 280px;
            padding: 50px;
            width: calc(100% - 280px);
            box-sizing: border-box;
        }

        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .btn-plus {
            background: var(--navy);
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-plus:hover {
            background: var(--orange);
            transform: scale(1.05);
        }

        .search-box {
            display: flex;
            background: white;
            padding: 5px 15px;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            width: 350px;
            margin-bottom: 40px;
        }

        .search-box input {
            border: none;
            padding: 10px;
            width: 100%;
            outline: none;
        }

        /* Folder System */
        .folder-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .folder-wrapper.expanded {
            grid-column: 1 / -1;
        }

        .folder-card {
            position: relative;
            height: 110px;
            border-radius: 0 15px 15px 15px;
            color: white;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            padding: 0 25px;
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
            background: white;
            border-radius: 15px;
            margin-top: 15px;
            display: none;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .drawer-header {
            padding: 12px 20px;
            background: #fff5f5;
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
            padding: 15px;
            background: #f8fafc;
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background: white;
            margin: 12% auto;
            padding: 25px;
            border-radius: 20px;
            width: 350px;
        }
    </style>
</head>

<body>

   <div class="sidebar">
    <h2>Karunesh Kumar & Associates Admin</h2>
    <a href="admin-dashboard.php" ><i class="fas fa-chart-pie"></i> Dashboard</a>

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
            <a href="Master-Vault.php" class="active"></i> Services</a>
        </div>
    </div>

    <div class="dropdown-container">
        <a href="javascript:void(0)" class="dropdown-btn"class="active" onclick="toggleMenu('reportsMenu', 'repChev')">
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
    <a href="manage-clients.php"><i class="fas fa-users"></i> Manage Clients</a>
    <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
    <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
    <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

    <div class="main">
        <div class="header-flex">
            <h1>Master Vault</h1>
            <button class="btn-plus" onclick="document.getElementById('addModal').style.display='block'">
                <i class="fas fa-plus"></i>
            </button>
        </div>

        <form action="" method="GET" class="search-box">
            <input type="text" name="q" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" style="background:none; border:none; cursor:pointer;"><i class="fas fa-search"></i></button>
        </form>

        <div class="folder-grid">
            <?php foreach ($myServices as $name => $color) :
                $id = md5($name);
                $files = getMatchingFiles($conn, $name, $search);
            ?>
                <div class="folder-wrapper" id="wrap_<?php echo $id; ?>">
                    <div class="folder-card" onclick="toggleFolder('<?php echo $id; ?>')" style="background: <?php echo $color; ?>;">
                        <div class="folder-tab" style="background: <?php echo $color; ?>;"></div>
                        <div style="z-index:1;">
                            <h3 style="margin:0; font-size:15px;"><?php echo strtoupper($name); ?></h3>
                            <p style="margin:5px 0 0; opacity:0.8; font-size:11px;"><?php echo $files->num_rows; ?> Files</p>
                        </div>
                        <i class="fas fa-folder-open" style="position:absolute; right:20px; font-size:40px; opacity:0.2;"></i>
                    </div>

                    <div class="folder-drawer" id="<?php echo $id; ?>">
                        <div class="drawer-header">
                            <span style="font-size: 11px; color: #b91c1c; font-weight: 600;">CATEGORY: <?php echo strtoupper($name); ?></span>
                            <a href="?delete_folder=<?php echo urlencode($name); ?>"
                                onclick="return confirm('Delete this entire folder category?')"
                                style="color: #ef4444; text-decoration: none; font-size: 11px; font-weight: bold;">
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
                                            <td><strong><?php echo $row['owner_name']; ?></strong><br><small><?php echo $row['company_name']; ?></small></td>
                                            <td><span style="background:#e2e8f0; padding:4px 8px; border-radius:6px; font-size:11px;"><?php echo $row['category']; ?></span></td>
                                            <td style="text-align:right">
                                                <div style="display:flex; gap:10px; justify-content:flex-end;">
                                                    <a href="../uploads/vault/<?php echo $row['result_file']; ?>" target="_blank" style="background:var(--navy); color:white; padding:6px 12px; border-radius:6px; text-decoration:none; font-size:11px;">VIEW</a>
                                                    <a href="?delete_doc=<?php echo $row['id']; ?>" onclick="return confirm('Archive this file?')" style="background:#fee2e2; color:#ef4444; padding:6px 12px; border-radius:6px; text-decoration:none; font-size:11px;"><i class="fas fa-times"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile;
                                else : ?>
                                    <tr>
                                        <td colspan="4" style="text-align:center; padding:30px; color:#94a3b8;">No documents found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="addModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-top:0;">New Vault Category</h3>
            <form method="POST">
                <input type="text" name="folder_name" placeholder="e.g. Income Tax" required style="width:100%; padding:12px; margin-bottom:15px; border:1px solid #ddd; border-radius:10px; box-sizing:border-box;">
                <select name="folder_color" style="width:100%; padding:12px; margin-bottom:15px; border:1px solid #ddd; border-radius:10px;">
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
                <button type="submit" name="create_folder" style="width:100%; background:var(--navy); color:white; border:none; padding:12px; border-radius:10px; cursor:pointer; font-weight:bold;">Create Folder</button>
                <button type="button" onclick="document.getElementById('addModal').style.display='none'" style="width:100%; background:none; border:none; margin-top:10px; cursor:pointer; color:#64748b;">Cancel</button>
            </form>
        </div>
    </div>

    <script>
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