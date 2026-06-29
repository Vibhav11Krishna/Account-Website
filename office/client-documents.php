<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Login.php");
    exit();
}

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
    <title>Clients Documents | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --navy: #0b3c74;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
            --danger: #ef4444;
            --success: #22c55e;
        }

        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Inter', sans-serif;
        }

        .sidebar {
    width: 280px;
    background: var(--sidebar);
    color: white;
    height: 100vh; /* Keeps the sidebar full height */
    position: fixed;
    top: 0;
    left: 0;
    padding: 30px 20px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    border-right: 4px solid var(--orange);
    
    /* This makes the scrollbar behave correctly */
    overflow-y: auto;
    scrollbar-width: thin; /* Firefox: makes the scrollbar thin */
    scrollbar-color: var(--orange) var(--sidebar); /* Thumb and track color */
}

/* Chrome, Safari, Edge: Custom Scrollbar Line */
.sidebar::-webkit-scrollbar {
    width: 8px; /* Thickness of the side line */
}

.sidebar::-webkit-scrollbar-track {
    background: #082d56; /* Darker track */
}

.sidebar::-webkit-scrollbar-thumb {
    background-color: var(--orange); /* The "line" you can grab */
    border-radius: 10px;
    border: 2px solid #082d56;
}

        .sidebar h2 {
            color: var(--orange);
            margin-bottom: 40px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 20px;
            font-size: 22px;
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

       .main {
    margin-left: 280px;
    padding: 30px 20px 50px 50px; /* Top is now 0, left/right/bottom stay 50 */
    width: calc(100% - 280px);
}
        /* Billing Dropdown specific styles */
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

        .admin-top-bar {
    padding: 10px 30px; /* Minimal padding */
    display: flex;
    align-items: center;
    color: #0b3c74;
   
}

.admin-top-bar h1 {
    margin: 10px 0; /* Remove default browser margins on the H1 */
    font-size: 30px;
}

        .content-body {
            padding: 40px 50px;
        }

        /* Folders with dynamic colors */
        .folder-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 2fr));
            gap: 20px;
        }

        .folder-card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: 0.2s;
        }

        .folder-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Table & Action Buttons */
        .file-table {
            width: 100%;
            background: white;
            border-radius: 12px;
            border-collapse: collapse;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .file-table th,
        .file-table td {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        .btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            text-decoration: none;
            font-weight: 500;
            margin-right: 5px;
        }

        .btn-view {
            background: #e0e7ff;
            color: #4338ca;
        }

        .btn-down {
            background: #ffedd5;
            color: #c2410c;
        }

        .btn-del {
            background: #fee2e2;
            color: #b91c1c;
        }
        #clientSearch:focus {
    border-color: var(--orange);
    box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.2);
}
.content-body {
    padding: 10px 50px; /* Reduced from 40px to 10px to pull content up */
}
/* General button styling */
.btn {
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    display: inline-block;
    transition: 0.3s;
    border: none;
    cursor: pointer;
}

/* Green Share Button */
.btn-share {
    background: #dcfce7;
    color: #166534;
}
.btn-share:hover {
    background: #166534;
    color: white;
}

/* Blue Shared (Status) Button */
.btn-shared {
    background: #dbeafe;
    color: #1e40af;
}

/* Your View Button (Ensure you have this) */
.btn-view {
    background: #f1f5f9;
    color: #475569;
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
                <a href="Master-Vault.php"></i> Services</a>
                <a href="client-documents.php" class="active">Client Documents</a>
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
                <a href="client-services.php">Services</a>
                <a href="client-upload-docs.php">Clients Uploads</a>
            </div>
        </div>
        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
        <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    <div class="main">
       <header class="admin-top-bar">
    <div>
        <h1>Client Document Vault</h1>
        <p style="margin: 4px 0 0 0; color: #64748b; font-size: 14px; font-weight: 500;">
            Manage and access all client files securely in one place
        </p>
    </div>
</header>
        <div class="content-body">
          <div style="display: flex; justify-content: center; margin-bottom: 15px; margin-top: 0;">
    <input type="text" id="clientSearch" onkeyup="filterClients()" placeholder="Search company name..." 
           style="width: 100%; max-width: 500px; padding: 12px 20px; border-radius: 12px; border: 2px solid #e2e8f0; font-size: 15px; outline: none; transition: 0.3s; background: white;">
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
                        <div class="folder-card" onclick="location.href='?cid=<?php echo $c['client_id']; ?>'">
                            <i class="fas fa-folder" style="font-size: 40px; color: <?php echo $colors[$i++ % 5]; ?>;"></i>
                            <div style="margin-top:12px; font-weight:600;"><?php echo $c['company_name']; ?></div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else:
                $cid = $_GET['cid'];
                $files = $conn->query("SELECT * FROM client_files WHERE client_id='$cid' ORDER BY id DESC");
            ?>
                <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                    <a href="client-documents.php" style="
    display: inline-flex; 
    align-items: center; 
    gap: 8px; 
    padding: 10px 20px; 
    background: #ffffff; 
    color: var(--navy); 
    text-decoration: none; 
    border-radius: 8px; 
    font-weight: 600; 
    font-size: 14px; 
    border: 1px solid var(--navy);
    transition: 0.3s;">
                        <i class="fas fa-arrow-left"></i> Back to Folders
                    </a>
                    <button onclick="openUploadModal('<?php echo $cid; ?>')" style="padding:10px 20px; background:var(--navy); color:white; border:none; border-radius:8px;">+ Upload File</button>
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
                                <td><?php echo $f['file_name']; ?></td>
                                <td><?php echo $f['file_type']; ?></td>
                                <td><?php echo date('d M Y', strtotime($f['created_at'] ?? 'now')); ?></td>
            <td>
    <?php if ($f['status'] == 'Internal'): ?>
        <a href="#" onclick="confirmAction('share', '<?= $f['id'] ?>', '<?= $cid ?>')" class="btn btn-share">
            <i class="fas fa-share-square"></i> Share
        </a>
    <?php else: ?>
        <a href="#" onclick="confirmAction('unshare', '<?= $f['id'] ?>', '<?= $cid ?>')" class="btn btn-shared" style="background:#f59e0b; color:white;">
            <i class="fas fa-eye-slash"></i> Recall
        </a>
    <?php endif; ?>

    <a href="<?= $f['file_path'] ?>" target="_blank" class="btn btn-view">View</a>
    
    <a href="#" onclick="confirmAction('delete', '<?= $f['id'] ?>', '<?= $cid ?>')" class="btn btn-del">
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
    <div id="uploadModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:white; margin:10% auto; padding:30px; width:400px; border-radius:20px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <h3 style="margin-top:0;">Upload New Files</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="client_id" id="modal_client_id">
            <div style="margin-bottom:15px;">
                <label>Document Category:</label>
                <input type="text" name="doc_type" placeholder="e.g. GST, ITR, PAN" required style="width:100%; padding:10px; margin-top:5px; border:1px solid #ccc; border-radius:8px; box-sizing:border-box;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Select Files:</label>
                <input type="file" name="files[]" multiple required style="width:100%; padding:10px; margin-top:5px;">
            </div>
            <button type="submit" name="upload_doc" style="background:var(--navy); color:white; border:none; padding:12px; width:100%; border-radius:8px; font-weight:bold; cursor:pointer;">Save Files to Vault</button>
        </form>
    </div>
</div>

    <script>
        function filterClients() {
    let input = document.getElementById('clientSearch');
    let filter = input.value.toUpperCase();
    let grid = document.querySelector('.folder-grid');
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
        function confirmAction(action, fileId, clientId) {
    let messages = {
        'share': "Are you sure you want to release this document to the client?",
        'unshare': "Are you sure you want to recall this document? It will no longer be visible to the client.",
        'delete': "Are you sure you want to PERMANENTLY delete this file?"
    };
    
    if (confirm(messages[action])) {
        window.location.href = `?cid=${clientId}&file_id=${fileId}&action=${action}`;
    }
}
    </script>
</body>

</html>