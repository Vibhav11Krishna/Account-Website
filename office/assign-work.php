<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Login.php");
    exit();
}

// --- LOGIC 1: Create a NEW Manual Task ---
if (isset($_POST['create_task'])) {
    // Directly capture the manual input
    $srv = mysqli_real_escape_string($conn, $_POST['service_name']);
    $desc = mysqli_real_escape_string($conn, $_POST['desc']);
    $staff = mysqli_real_escape_string($conn, $_POST['staff_email']);
    $client = mysqli_real_escape_string($conn, $_POST['client_id']);

    $sql = "INSERT INTO service_requests (client_id, service_type, description, assigned_to, status, created_at) 
            VALUES ('$client', '$srv', '$desc', '$staff', 'Assigned', NOW())";

    if ($conn->query($sql)) {
        echo "<script>alert('Internal Task Created!'); window.location='assign-work.php';</script>";
    }
}

// --- LOGIC 2: Assign an EXISTING Client Request ---
if (isset($_POST['assign_existing'])) {
    $rid = mysqli_real_escape_string($conn, $_POST['rid']);
    $staff = mysqli_real_escape_string($conn, $_POST['staff_email']);
    $conn->query("UPDATE service_requests SET assigned_to='$staff', status='Assigned' WHERE id='$rid'");
    echo "<script>alert('Client Request Assigned!'); window.location='assign-work.php';</script>";
}

// --- LOGIC 3: Upload Document & Dispatch to Employee ---
if (isset($_POST['admin_upload_dispatch'])) {
    $client_id = mysqli_real_escape_string($conn, $_POST['client_id']);
    $staff_email = mysqli_real_escape_string($conn, $_POST['staff_email']);

    // Directly capture the manual input
    $service_name = mysqli_real_escape_string($conn, $_POST['service_name']);
    $doc_type = mysqli_real_escape_string($conn, $_POST['doc_type']);
    $instruction = mysqli_real_escape_string($conn, $_POST['instruction']);

    $file_name = time() . "_" . basename($_FILES["doc_file"]["name"]);
    $target_dir = "../uploads/center/";

    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (move_uploaded_file($_FILES["doc_file"]["tmp_name"], $target_dir . $file_name)) {
        $sql = "INSERT INTO client_documents 
                (client_id, file_name, assigned_to, status, doc_category, category, instruction, created_at) 
                VALUES 
                ('$client_id', '$file_name', '$staff_email', 'In-Progress', '$doc_type', '$service_name', '$instruction', NOW())";

        if ($conn->query($sql)) {
            echo "<script>alert('Dispatched to Vault: $service_name'); window.location='assign-work.php';</script>";
        }
    }
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

<!DOCTYPE html>
<html>

<head>
    <title>Assign Work | KKA Admin</title>
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
            border-right: 4px solid var(--orange);
        }

        .sidebar h2 {
            color: var(--orange);
            margin-bottom: 40px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 20px;
            font-size: 20px;
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
            background: rgba(0, 0, 0, 0.2);
            margin: 0 5px;
            border-radius: 8px;
            padding-left: 15px;
        }

        .dropdown-content a {
            font-size: 14px;
            padding: 10px;
            color: rgba(255, 255, 255, 0.6);
            display: block;
            border-left: none !important;
        }

        .show-menu {
            display: block;
        }

        .rotate-chevron {
            transform: rotate(90deg);
        }

        .main {
            margin-left: 280px;
            padding: 50px;
            width: calc(100% - 280px);
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
            margin-bottom: 30px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-sizing: border-box;
            font-family: inherit;
        }

        .btn-dispatch {
            background: #0c3e95;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }

        .btn-primary {
            background: var(--navy);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
        }

        .task-item {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 5px solid var(--orange);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body>
    <datalist id="service_list">
        <option value="GST Monthly Return">
        <option value="GST Annual Return (9/9C)">
        <option value="ITR Filing">
        <option value="TDS Return">
        <option value="Tax Audit">
        <option value="Statutory Audit">
        <option value="Company Incorporation">
        <option value="ROC Compliance">
        <option value="Accounting/Bookkeeping">
        <option value="Payroll Management">
        <option value="Project Report">
    </datalist>

    <div class="sidebar">
        <h2>Karunesh Kumar & Associates Admin</h2>
        <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i>Dashboard</a>
        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleBilling()">
                <i class="fas fa-file-invoice-dollar"></i> Billing
                <i class="fas fa-chevron-right" id="chevron" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="billingMenu">
                <a href="quotations.php">Quotations</a>
                <a href="invoices.php">Invoices</a>
                <a href="receipts.php">Receipts</a>
                <a href="outstanding.php">Outstanding</a>
            </div>
        </div>
        <a href="assign-work.php" class="active"><i class="fas fa-tasks"></i> Assign Work</a>
        <a href="admin-review.php"><i class="fas fa-file-signature"></i> Quality Control</a>
        <a href="Master-Vault.php"><i class="fas fa-file-signature"></i>Master Vault</a>
        <a href="manage-clients.php"><i class="fas fa-users"></i> Manage Clients</a>
        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
        <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <h1>Work Assignment Center</h1>

        <div class="card">
            <h3 style="margin:0 0 20px 0; color:var(--navy);"><i class="fas fa-file-export"></i> Dispatch Document</h3>
            <form method="POST" enctype="multipart/form-data">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div>
                        <label>Target Client</label>
                        <select name="client_id" required>
                            <option value="">-- Select Client --</option>
                            <?php
                            $clts = $conn->query("SELECT identifier, name FROM users WHERE role='client'");
                            while ($c = $clts->fetch_assoc()) echo "<option value='{$c['identifier']}'>{$c['identifier']} - {$c['name']}</option>";
                            ?>
                        </select>
                    </div>
                    <div>
                        <label>Assign To Staff</label>
                        <select name="staff_email" required>
                            <option value="">-- Select Employee --</option>
                            <?php
                            $staff = $conn->query("SELECT name, identifier FROM users WHERE role='office'");
                            while ($s = $staff->fetch_assoc()) echo "<option value='{$s['identifier']}'>{$s['name']}</option>";
                            ?>
                        </select>
                    </div>
                </div>

                <div style="margin-top:10px;">
                    <label>Accounting Service Type (Manual or Select)</label>
                    <input type="text" name="service_name" list="service_list" placeholder="Start typing or choose from list..." required>
                </div>

                <textarea name="instruction" placeholder="Special Work Instructions..." rows="2"></textarea>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <select name="doc_type">
                        <option value="center">Center (Active)</option>
                        <option value="vault">Vault (Permanent)</option>
                    </select>
                    <input type="file" name="doc_file" required>
                </div>
                <button name="admin_upload_dispatch" class="btn-dispatch">Send to Staff Basket</button>
            </form>
        </div>

        <div class="card">
            <h3><i class="fas fa-plus-circle"></i> Manual Task (No Document)</h3>
            <form method="POST">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div>
                        <label>Task Category / Name</label>
                        <input type="text" name="service_name" list="service_list" placeholder="Enter Task Name..." required>
                    </div>
                    <div>
                        <label>Client</label>
                        <select name="client_id" required>
                            <option value="">-- Select Client --</option>
                            <?php $clts->data_seek(0);
                            while ($c = $clts->fetch_assoc()) echo "<option value='{$c['identifier']}'>{$c['identifier']} - {$c['name']}</option>"; ?>
                        </select>
                    </div>
                </div>
                <textarea name="desc" placeholder="Detailed Task Description..." required rows="2"></textarea>
                <select name="staff_email" required>
                    <option value="">-- Assign Staff --</option>
                    <?php $staff->data_seek(0);
                    while ($s = $staff->fetch_assoc()) echo "<option value='{$s['identifier']}'>{$s['name']}</option>"; ?>
                </select>
                <button name="create_task" class="btn-primary">Generate & Assign Task</button>
            </form>
        </div>

        <h2>Incoming Requests</h2>
        <?php
        $res = $conn->query("SELECT * FROM service_requests WHERE status='Pending'");
        if ($res->num_rows == 0) echo "<p style='color:#64748b;'>No pending client requests.</p>";
        while ($row = $res->fetch_assoc()) {
        ?>
            <div class="task-item">
                <div>
                    <b><?php echo $row['service_type']; ?></b>
                    <p style="margin:5px 0; color:#64748b; font-size:13px;"><?php echo $row['description']; ?></p>
                    <small>ID: <?php echo $row['client_id']; ?></small>
                </div>
                <form method="POST" style="display:flex; gap:10px;">
                    <input type="hidden" name="rid" value="<?php echo $row['id']; ?>">
                    <select name="staff_email" required style="margin:0; width:180px;">
                        <?php $staff->data_seek(0);
                        while ($s = $staff->fetch_assoc()) echo "<option value='{$s['identifier']}'>{$s['name']}</option>"; ?>
                    </select>
                    <button name="assign_existing" style="background:var(--orange); color:white; border:none; padding:10px; border-radius:8px; cursor:pointer;">Assign</button>
                </form>
            </div>
        <?php } ?>

        <h2 style="margin-top:50px;">Live Assignment Monitor</h2>
        <div style="display: flex; gap: 15px; margin-bottom: 20px; align-items: flex-end; background: white; padding: 20px; border-radius: 15px; border: 1px solid #e2e8f0;">
            <div style="flex: 2;">
                <label style="font-size: 12px; color: #64748b; font-weight: bold;">Keyword Search</label>
                <input type="text" id="workSearch" placeholder="Search ID, Staff, Status..." onkeyup="filterWork()">
            </div>
            <div style="flex: 1;">
                <label style="font-size: 12px; color: #64748b; font-weight: bold;">From Date</label>
                <input type="date" id="dateFrom" onchange="filterWork()">
            </div>
            <div style="flex: 1;">
                <label style="font-size: 12px; color: #64748b; font-weight: bold;">To Date</label>
                <input type="date" id="dateTo" onchange="filterWork()">
            </div>
            <button onclick="resetFilters()" style="background: #f1f5f9; border: none; padding: 12px 15px; border-radius: 10px; cursor: pointer;"><i class="fas fa-undo"></i></button>
        </div>

        <div class="card" style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; text-align:left;">
                <thead>
                    <tr style="border-bottom:2px solid #f1f5f9; color:var(--navy);">
                        <th style="padding:15px;">Type</th>
                        <th>Client ID</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Details</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="monitorTable">
                    <?php
                    $tasks = $conn->query("SELECT *, 'TASK' as kind FROM service_requests ORDER BY id DESC");
                    $docs = $conn->query("SELECT *, 'DOC' as kind FROM client_documents ORDER BY id DESC");

                    while ($t = $tasks->fetch_assoc()): ?>
                        <tr class="work-row" style="border-bottom:1px solid #f8fafc;" data-date="<?php echo date('Y-m-d', strtotime($t['created_at'] ?? 'now')); ?>">
                            <td style="padding:15px;"><span style="background:#e0f2fe; color:#0369a1; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:bold;">TASK</span></td>
                            <td><?php echo $t['client_id']; ?></td>
                            <td><b><?php echo $t['assigned_to']; ?></b></td>
                            <td><span style="color:var(--orange);"><?php echo $t['status']; ?></span></td>
                            <td style="font-size:13px;"><?php echo $t['service_type']; ?></td>
                            <td><a href="?delete_task=<?php echo $t['id']; ?>" onclick="return confirm('Delete?')" style="color:#ef4444;"><i class="fas fa-trash-alt"></i></a></td>
                        </tr>
                    <?php endwhile; ?>

                    <?php while ($d = $docs->fetch_assoc()): ?>
                        <tr class="work-row" style="border-bottom:1px solid #f8fafc;" data-date="<?php echo date('Y-m-d', strtotime($d['created_at'] ?? 'now')); ?>">
                            <td style="padding:15px;"><span style="background:#fef3c7; color:#92400e; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:bold;">DOC</span></td>
                            <td><?php echo $d['client_id']; ?></td>
                            <td><b><?php echo $d['assigned_to']; ?></b></td>
                            <td><span style="color:#6366f1;"><?php echo $d['status']; ?></span></td>
                            <td style="font-size:13px;">
                                <b style="color:var(--navy);"><?php echo $d['category']; ?></b><br>
                                <a href="../uploads/center/<?php echo $d['file_name']; ?>" target="_blank" style="color:var(--navy);"><i class="fas fa-file-pdf"></i> View File</a>
                            </td>
                            <td><a href="?delete_doc=<?php echo $d['id']; ?>" onclick="return confirm('Delete?')" style="color:#ef4444;"><i class="fas fa-trash-alt"></i></a></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleBilling() {
            document.getElementById('billingMenu').classList.toggle('show-menu');
            document.getElementById('chevron').classList.toggle('rotate-chevron');
        }

        function filterWork() {
            const input = document.getElementById('workSearch').value.toLowerCase();
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            const rows = document.querySelectorAll('.work-row');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const rowDate = row.getAttribute('data-date');
                let matchesSearch = text.includes(input);
                let matchesDate = true;
                if (dateFrom && rowDate < dateFrom) matchesDate = false;
                if (dateTo && rowDate > dateTo) matchesDate = false;
                row.style.display = (matchesSearch && matchesDate) ? "" : "none";
            });
        }

        function resetFilters() {
            document.getElementById('workSearch').value = "";
            document.getElementById('dateFrom').value = "";
            document.getElementById('dateTo').value = "";
            document.querySelectorAll('.work-row').forEach(row => row.style.display = "");
        }
    </script>
</body>

</html>