<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Login.php");
    exit();
}

// --- NEW UPDATE LOGIC ADDED HERE ---
if (isset($_POST['update_client'])) {
    $identifier = mysqli_real_escape_string($conn, $_POST['identifier']);
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $owner_name = mysqli_real_escape_string($conn, $_POST['owner_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['business_email']); 
    $gst = mysqli_real_escape_string($conn, $_POST['gst_no']);
    $pan = mysqli_real_escape_string($conn, $_POST['pan_no']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    $update_sql = "UPDATE client_profiles SET 
                   company_name = '$company_name', 
                   owner_name = '$owner_name', 
                   phone = '$phone', 
                   business_email = '$email', 
                   gst_no = '$gst', 
                   pan_no = '$pan', 
                   address = '$address' 
                   WHERE client_id = '$identifier'";

    if ($conn->query($update_sql)) {
        // Sync name in users table
        $conn->query("UPDATE users SET name = '$owner_name' WHERE identifier = '$identifier'");
        header("Location: manage-clients.php?msg=updated");
        exit();
    } else {
        die("Update Error: " . $conn->error);
    }
}

// Delete Logic
if (isset($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    $conn->query("DELETE FROM client_profiles WHERE client_id='$id'");
    $conn->query("DELETE FROM users WHERE identifier='$id' AND role='client'");
    header("Location: manage-clients.php?msg=deleted");
    exit();
}

// Quick Create Client Logic
if (isset($_POST['quick_create'])) {
    $n = mysqli_real_escape_string($conn, $_POST['name']);
    $p = mysqli_real_escape_string($conn, $_POST['pass']);
    $role = 'client';

    $year = date("Y");
    $count_res = $conn->query("SELECT id FROM users WHERE role='client'");
    $count = $count_res->num_rows + 1;
    $id = "KK/$year/" . str_pad($count, 3, "0", STR_PAD_LEFT);

    $sql = "INSERT INTO users (name, identifier, password, role) VALUES ('$n', '$id', '$p', '$role')";
    
    if ($conn->query($sql)) {
        $conn->query("INSERT INTO client_profiles (client_id, owner_name) VALUES ('$id', '$n')");
        echo "<script>alert('Client Created! ID: $id'); window.location='manage-clients.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Clients | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --navy: #0b3c74;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
            --danger: #ef4444;
            --border: #e2e8f0;
        }

        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            color: #334155;
        }

        /* SIDEBAR */
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

        .sidebar a:hover, .active {
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

        .show-menu { display: block !important; }
        .rotate-chevron { transform: rotate(180deg); }

        /* Main Area */
        .main {
            margin-left: 280px;
            padding: 50px;
            width: calc(100% - 280px);
            box-sizing: border-box;
        }

        h1 { color: var(--navy); margin-top: 0; }

        .search-container {
            margin-bottom: 25px;
            display: flex;
            gap: 12px;
            align-items: center;
            background: white;
            padding: 15px 20px;
            border-radius: 15px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }

        .search-container input { width: 100%; border: none; outline: none; font-size: 15px; }

        .table-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.02);
            border: 1px solid var(--border);
        }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        
        .id-badge { background: #fff7ed; color: var(--orange); font-weight: 700; padding: 6px 12px; border-radius: 8px; font-family: monospace; }
        
        .action-btn { width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; text-decoration: none; transition: 0.2s; }
        .btn-edit { color: #3b82f6; background: #eff6ff; }
        .btn-delete { color: #ef4444; background: #fef2f2; }

        .add-client-form { display: none; background: white; padding: 25px; border-radius: 20px; border: 1.5px solid var(--orange); margin-bottom: 30px; }

        .btn-primary {
            background: var(--navy);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .msg-alert {
            padding: 15px;
            background: #dcfce7;
            color: #166534;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
        }
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
        <a href="admin-review.php"><i class="fas fa-file-signature"></i> Quality Control</a>
        <a href="Master-Vault.php"><i class="fas fa-vault"></i>Master Vault</a>
        <a href="manage-clients.php"class="active"><i class="fas fa-users"></i> Manage Clients</a>
        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
        <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
            <div class="msg-alert"><i class="fas fa-check-circle"></i> Profile updated successfully!</div>
        <?php endif; ?>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
            <h1>Client Directory</h1>
            <button onclick="toggleAddForm()" class="btn-primary">
                <i class="fas fa-user-plus"></i> Add New Client
            </button>
        </div>

        <div id="addClientForm" class="add-client-form">
            <h3 style="margin-top:0;">Quick Onboard</h3>
            <form method="POST" style="display: flex; gap: 15px;">
                <input type="text" name="name" placeholder="Client Name" required style="flex:2; padding:12px; border:1px solid #ddd; border-radius:10px;">
                <input type="text" name="pass" placeholder="Password" required style="flex:1; padding:12px; border:1px solid #ddd; border-radius:10px;">
                <button name="quick_create" class="btn-primary" style="background:var(--orange)">Save Client</button>
            </form>
        </div>

        <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" id="clientSearch" onkeyup="filterTable()" placeholder="Search clients...">
        </div>

        <div class="table-card">
            <table id="clientTable">
                <thead>
                    <tr>
                        <th>Client ID</th>
                        <th>Firm Details</th>
                        <th>Contact</th>
                        <th>Tax Info</th>
                        <th style="text-align:center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT u.identifier, cp.company_name, cp.owner_name, cp.phone, cp.business_email, cp.gst_no, cp.pan_no 
                            FROM users u 
                            LEFT JOIN client_profiles cp ON u.identifier = cp.client_id 
                            WHERE u.role='client' ORDER BY u.id DESC";
                    $res = $conn->query($sql);
                    while ($row = $res->fetch_assoc()):
                    ?>
                    <tr>
                        <td><span class="id-badge"><?php echo $row['identifier']; ?></span></td>
                        <td>
                            <b><?php echo htmlspecialchars($row['company_name'] ?: 'Not Set'); ?></b><br>
                            <small><?php echo htmlspecialchars($row['owner_name']); ?></small>
                        </td>
                        <td><?php echo $row['phone'] ?: '-'; ?><br><small><?php echo $row['business_email'] ?: '-'; ?></small></td>
                        <td><small>GST: <?php echo $row['gst_no'] ?: '-'; ?><br>PAN: <?php echo $row['pan_no'] ?: '-'; ?></small></td>
                        <td style="text-align: center;">
                            <a href="client-profile.php?id=<?php echo $row['identifier']; ?>" class="action-btn btn-edit"><i class="fas fa-pencil-alt"></i></a>
                            <a href="?delete=<?php echo $row['identifier']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete client?')"><i class="fas fa-trash-alt"></i></a>
                        </td>
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
    </script>
</body>
</html>