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
    $srv = mysqli_real_escape_string($conn, $_POST['service']);
    $desc = mysqli_real_escape_string($conn, $_POST['desc']);
    $staff = mysqli_real_escape_string($conn, $_POST['staff_email']);
    $client = mysqli_real_escape_string($conn, $_POST['client_id']);

    $sql = "INSERT INTO service_requests (client_id, service_type, description, assigned_to, status) 
            VALUES ('$client', '$srv', '$desc', '$staff', 'Assigned')";

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
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    // NEW: Capture instruction
    $instruction = mysqli_real_escape_string($conn, $_POST['instruction']);

    $file_name = time() . "_" . basename($_FILES["doc_file"]["name"]);
    $target_dir = "../uploads/center/";

    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (move_uploaded_file($_FILES["doc_file"]["tmp_name"], $target_dir . $file_name)) {
        // SQL UPDATED: Added instruction column
        $sql = "INSERT INTO client_documents (client_id, file_name, assigned_to, status, doc_category, instruction) 
                VALUES ('$client_id', '$file_name', '$staff_email', 'In-Progress', '$category', '$instruction')";

        if ($conn->query($sql)) {
            echo "<script>alert('Document dispatched to Staff Basket!'); window.location='assign-work.php';</script>";
        }
    } else {
        echo "<script>alert('Error: Could not upload file.');</script>";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Assign Work | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Billing Dropdown Styling */
        .dropdown-content {
            display: none;
            background: rgba(0, 0, 0, 0.2);
            margin: 0 5px;
            border-radius: 8px;
            padding-left: 15px;
            /* Indent sub-items */
        }

        .dropdown-content a {
            font-size: 14px;
            padding: 10px;
            color: rgba(255, 255, 255, 0.6);
        }

        .dropdown-content a:hover {
            color: var(--orange);
            border-left: none;
            /* No border for sub-items */
            background: transparent;
        }

        .dropdown-btn {
            cursor: pointer;
        }

        /* When the dropdown is open */
        .show-menu {
            display: block;
        }

        .rotate-chevron {
            transform: rotate(90deg);
        }

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
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <h2>KKA ADMIN</h2>
        <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> Summary</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleBilling()">
                <i class="fas fa-file-invoice-dollar"></i> Billing
                <i class="fas fa-chevron-right" id="chevron" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="billingMenu">
                <a href="quotations.php"><i class="fas fa-file-signature"></i> Quotations</a>
                <a href="invoices.php"><i class="fas fa-receipt"></i> Invoices</a>
                <a href="receipts.php"><i class="fas fa-check-double"></i> Receipts</a>
                <a href="outstanding.php"><i class="fas fa-exclamation-circle"></i> Outstanding</a>
            </div>
        </div>

        <a href="assign-work.php" class="active"><i class="fas fa-tasks"></i> Assign Work</a>
        <a href="admin-review.php"><i class="fas fa-file-signature"></i> Quality Control</a>
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
                        <label>Target Client (ID)</label>
                        <select name="client_id" required>
                            <option value="">-- Select Client ID --</option>
                            <?php
                            $clts = $conn->query("SELECT identifier, name FROM users WHERE role='client'");
                            while ($c = $clts->fetch_assoc()) {
                                echo "<option value='{$c['identifier']}'>{$c['identifier']} - {$c['name']}</option>";
                            }
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
                    <label>Work Instructions</label>
                    <textarea name="instruction" placeholder="Describe the work to be done with this file..." rows="3"></textarea>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div>
                        <label>Category</label>
                        <select name="category">
                            <option value="center">Center</option>
                            <option value="vault">Vault</option>
                        </select>
                    </div>
                    <div>
                        <label>File</label>
                        <input type="file" name="doc_file" required>
                    </div>
                </div>
                <button name="admin_upload_dispatch" class="btn-dispatch">Send to Staff Basket</button>
            </form>
        </div>

        <div class="card">
            <h3><i class="fas fa-plus-circle"></i> Manual Task</h3>
            <form method="POST">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div>
                        <label>Service Type</label>
                        <select name="service" required>
                            <option value="">-- Select Service --</option>
                            <option>GST Filing</option>
                            <option>ITR Filing</option>
                            <option>Audit</option>
                        </select>
                    </div>
                    <div>
                        <label>Select Client</label>
                        <select name="client_id" required>
                            <option value="">-- Select Client (ID - Name) --</option>
                            <?php
                            // Reset the pointer and loop again
                            $clts->data_seek(0);
                            while ($c = $clts->fetch_assoc()) {
                                // This displays "KK/2026/001 - John Doe" but sends "KK/2026/001"
                                echo "<option value='{$c['identifier']}'>{$c['identifier']} - {$c['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div style="margin-top:10px;">
                    <label>Task Description</label>
                    <textarea name="desc" placeholder="Enter specific details about this manual task..." required rows="3"></textarea>
                </div>

                <div style="margin-bottom:15px;">
                    <label>Assign Staff Member</label>
                    <select name="staff_email" required>
                        <option value="">-- Assign Staff --</option>
                        <?php
                        $staff->data_seek(0);
                        while ($s = $staff->fetch_assoc()) echo "<option value='{$s['identifier']}'>{$s['name']}</option>";
                        ?>
                    </select>
                </div>

                <button name="create_task" class="btn-primary">Generate & Assign Task</button>
            </form>
        </div>

        <h2>Incoming Requests</h2>
        <?php
        $res = $conn->query("SELECT * FROM service_requests WHERE status='Pending'");
        if ($res->num_rows > 0) {
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
                            <?php
                            $staff->data_seek(0);
                            while ($s = $staff->fetch_assoc()) echo "<option value='{$s['identifier']}'>{$s['name']}</option>";
                            ?>
                        </select>
                        <button name="assign_existing" style="background:var(--orange); color:white; border:none; padding:10px; border-radius:8px; cursor:pointer;">Assign</button>
                    </form>
                </div>
        <?php
            }
        } else {
            echo "<p style='color:#94a3b8;'>No pending requests.</p>";
        }
        ?>

        <?php
        // Fetch data first to check if we have anything to show
        $tasks = $conn->query("SELECT * FROM service_requests WHERE status='Assigned'");
        $docs = $conn->query("SELECT * FROM client_documents WHERE assigned_to != ''");

        // Total count of all work
        $total_work = $tasks->num_rows + $docs->num_rows;
        ?>

        <?php if ($total_work > 0): ?>
            <h2 style="margin-top:50px;">Live Assignment Monitor</h2>
            <div class="card">
                <table style="width:100%; border-collapse:collapse; text-align:left;">
                    <thead>
                        <tr style="border-bottom:2px solid #f1f5f9; color:var(--navy);">
                            <th style="padding:15px;">Type</th>
                            <th>Client ID</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th>Details/File</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($t = $tasks->fetch_assoc()): ?>
                            <tr style="border-bottom:1px solid #f8fafc;">
                                <td style="padding:15px;"><span style="background:#e0f2fe; color:#0369a1; padding:4px 8px; border-radius:6px; font-size:12px; font-weight:bold;">TASK</span></td>
                                <td><?php echo $t['client_id']; ?></td>
                                <td><b><?php echo $t['assigned_to']; ?></b></td>
                                <td><span style="color:var(--orange); font-weight:bold;"><?php echo $t['status']; ?></span></td>
                                <td style="color:#64748b; font-size:13px;"><?php echo $t['service_type']; ?></td>
                            </tr>
                        <?php endwhile; ?>

                        <?php while ($d = $docs->fetch_assoc()): ?>
                            <tr style="border-bottom:1px solid #f8fafc;">
                                <td style="padding:15px;"><span style="background:#fef3c7; color:#92400e; padding:4px 8px; border-radius:6px; font-size:12px; font-weight:bold;">DOCUMENT</span></td>
                                <td><?php echo $d['client_id']; ?></td>
                                <td><b><?php echo $d['assigned_to']; ?></b></td>
                                <td>
                                    <span style="color:<?php echo ($d['status'] == 'Completed') ? '#22c55e' : '#6366f1'; ?>; font-weight:bold;">
                                        <?php echo $d['status']; ?>
                                    </span>
                                </td>
                                <td><a href="../uploads/center/<?php echo $d['file_name']; ?>" target="_blank" style="color:var(--navy); text-decoration:none;"><i class="fas fa-file-pdf"></i> View File</a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align:center; padding:40px; color:#94a3b8;">
                <i class="fas fa-inbox" style="font-size:40px; margin-bottom:10px;"></i>
                <p>No active assignments found in the system.</p>
            </div>
        <?php endif; ?>
    </div>
    <script>
        function toggleBilling() {
            const menu = document.getElementById('billingMenu');
            const chevron = document.getElementById('chevron');

            menu.classList.toggle('show-menu');
            chevron.classList.toggle('rotate-chevron');
        }
    </script>
</body>

</html>