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
    <title>Review & Release | KKA Admin</title>
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

        body { display: flex; margin: 0; background: var(--bg); font-family: 'Inter', sans-serif; color: #334155; }

        /* Sidebar Styling (Untouched) */
        .sidebar {
            width: 280px; background: var(--sidebar); color: white; height: 100vh;
            position: fixed; padding: 30px 20px; box-sizing: border-box;
            display: flex; flex-direction: column; border-right: 4px solid var(--orange);
        }
        .sidebar h2 { font-size: 22px; color: var(--orange); margin-bottom: 40px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 20px; }
        .sidebar a {
            color: rgba(255, 255, 255, 0.7); text-decoration: none; display: flex;
            align-items: center; gap: 12px; padding: 14px; margin-bottom: 8px;
            border-radius: 12px; transition: 0.3s;
        }
        .sidebar a:hover, .active { background: rgba(255, 255, 255, 0.1); color: white; border-left: 4px solid var(--orange); }

        .dropdown-content { display: none; background: rgba(0, 0, 0, 0.2); margin: 0 5px; border-radius: 8px; padding-left: 15px; }
        .dropdown-content a { font-size: 14px; padding: 10px; color: rgba(255, 255, 255, 0.6); }
        .show-menu { display: block; }
        .rotate-chevron { transform: rotate(90deg); }

        .main { margin-left: 280px; padding: 50px; width: calc(100% - 280px); }

        /* NEW SEGMENTED TAB STYLING (Based on your image) */
        .tab-wrapper {
            display: flex; background: #e2e8f0; padding: 6px; border-radius: 14px;
            width: fit-content; margin-bottom: 35px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
        }
        .tab-trigger {
            padding: 10px 24px; border-radius: 10px; border: none; cursor: pointer;
            font-weight: 600; font-size: 14px; color: #64748b; background: transparent;
            transition: all 0.3s ease;
        }
        .tab-trigger.active-tab {
            background: var(--navy); color: white; box-shadow: 0 4px 6px rgba(11, 60, 116, 0.2);
        }

        .section-content { display: none; }
        .section-content.active-section { display: block; }

        /* Folder & Card UI */
        .folder-group { background: white; margin-bottom: 15px; border-radius: 15px; border: 1px solid #e2e8f0; overflow: hidden; }
        .folder-header { 
            background: #f8fafc; padding: 18px 25px; cursor: pointer; display: flex; 
            justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0;
        }
        .folder-content { display: none; padding: 20px; background: #fff; }

        .review-card {
            background: #fff; padding: 20px; border-radius: 12px; 
            border: 1px solid #edf2f7; margin-bottom: 15px; border-left: 5px solid var(--navy);
        }

        .btn { padding: 10px 18px; border-radius: 10px; font-weight: 700; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 8px; font-size: 13px; transition: 0.3s; }
        .btn-approve { background: var(--success); color: white; width: 100%; }
        .btn-reject { background: var(--danger); color: white; width: 100%; }
        .btn-view { background: #f1f5f9; color: var(--navy); text-decoration: none; }

        #globalSearch { width: 100%; padding: 15px 20px; border-radius: 12px; border: 1px solid #ddd; margin-bottom: 30px; }
        .status-msg { background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: bold; }
        textarea { width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #e2e8f0; margin: 10px 0; resize: none; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>Karunesh Kumar & Associates Admin</h2>
     <a href="admin-dashboard.php" ><i class="fas fa-chart-pie"></i>Dashboard</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleBilling()">
                <i class="fas fa-file-invoice-dollar"></i> Billing
                <i class="fas fa-chevron-down" id="chevron" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="billingMenu">
                <a href="quotations.php"><i class="fas fa-file-signature"></i> Quotations</a>
                <a href="invoices.php"><i class="fas fa-receipt"></i> Invoices</a>
                <a href="receipts.php"><i class="fas fa-check-double"></i> Receipts</a>
                <a href="outstanding.php"><i class="fas fa-exclamation-circle"></i> Outstanding</a>
            </div>
        </div>

        <a href="assign-work.php"><i class="fas fa-tasks"></i> Assign Work</a>
        <a href="admin-review.php"class="active"><i class="fas fa-file-signature"></i> Quality Control</a>
        <a href="Master-Vault.php"><i class="fas fa-file-signature"></i>Master Vault</a>
        <a href="manage-clients.php"><i class="fas fa-users"></i> Manage Clients</a>
        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
        <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <h1>Quality Control</h1>
        <p>Manage documents grouped by Client Profile.</p>

        <?php if ($status_msg) echo "<div class='status-msg'>$status_msg</div>"; ?>

        <input type="text" id="globalSearch" onkeyup="filterEverything()" placeholder="Search clients, companies or files...">

        <div class="tab-wrapper">
            <button class="tab-trigger active-tab" onclick="openSection(this, 'pending-review')">Pending Review</button>
            <button class="tab-trigger" onclick="openSection(this, 'rejected-saved')">Saved (Rejected)</button>
            <button class="tab-trigger" onclick="openSection(this, 'released-vault')">Released Vault</button>
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

<?php
function renderGroupedSections($conn, $condition, $section_key) {
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
        echo "<p style='color:#94a3b8; padding:20px; background:white; border-radius:15px; border:1px dashed #cbd5e1;'>No items found in this category.</p>";
        return;
    }

    foreach ($grouped as $cid => $data) {
        $folderId = $section_key . "_" . $cid;
        ?>
        <div class="folder-group">
            <div class="folder-header" onclick="toggleFolder('<?php echo $folderId; ?>')">
                <span>
                    <i class="fas fa-folder" style="color:#fbbf24; margin-right:12px;"></i>
                    <strong><?php echo $data['info']['name']; ?></strong> 
                    <small style="color:#64748b; margin-left:5px;">(<?php echo $data['info']['firm']; ?>)</small>
                </span>
                <i class="fas fa-chevron-down" id="icon_<?php echo $folderId; ?>"></i>
            </div>
            <div class="folder-content" id="<?php echo $folderId; ?>">
                <?php foreach ($data['docs'] as $doc) { ?>
                    <div class="review-card" style="display:flex; justify-content:space-between; align-items:start;">
                        <div>
                            <h3 style="margin:0 0 5px 0; font-size:16px;"><i class="fas fa-file-pdf" style="color:#ef4444;"></i> <?php echo $doc['result_file']; ?></h3>
                            <p style="font-size:12px; color:#64748b; margin-bottom:15px;">Staff: <?php echo $doc['assigned_to']; ?></p>
                            <a href="../uploads/vault/<?php echo $doc['result_file']; ?>" target="_blank" class="btn btn-view"><i class="fas fa-eye"></i> View</a>
                        </div>
                        <?php if ($section_key == 'pending') { ?>
                        <div style="width:250px;">
                            <form method="POST">
                                <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                <button name="release_file" class="btn btn-approve"><i class="fas fa-check"></i> Release</button>
                            </form>
                            <form method="POST" style="margin-top:10px;">
                                <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                <textarea name="reject_note" placeholder="Feedback..." required rows="2"></textarea>
                                <button name="reject_file" class="btn btn-reject"><i class="fas fa-times"></i> Reject</button>
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
            icon.style.transform = (content.style.display === "block") ? "rotate(180deg)" : "rotate(0deg)";
        }

        function filterEverything() {
            let input = document.getElementById('globalSearch').value.toLowerCase();
            document.querySelectorAll('.folder-group').forEach(folder => {
                folder.style.display = folder.innerText.toLowerCase().includes(input) ? "block" : "none";
            });
        }

        function toggleBilling() {
            document.getElementById('billingMenu').classList.toggle('show-menu');
            document.getElementById('chevron').classList.toggle('rotate-chevron');
        }
    </script>
</body>
</html>