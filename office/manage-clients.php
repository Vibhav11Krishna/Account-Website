<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

// Delete Logic
if (isset($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    $conn->query("DELETE FROM users WHERE id='$id' AND role='client'");
    header("Location: manage-clients.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manage Clients | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
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
            --text: #334155;
            --border: #e2e8f0;
        }

        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            color: var(--text);
        }

        /* Sidebar Navigation */
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
            z-index: 1000;
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



        /* Main Content Area */
        .main {
            margin-left: 280px;
            padding: 50px;
            width: calc(100% - 280px);
            box-sizing: border-box;
        }

        .content-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Prevents table stretching too wide */

        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        /* Table Design */
        .table-card {
            background: white;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.02);
            border: 1px solid var(--border);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th {
            text-align: left;
            padding: 15px;
            background: #fcfcfd;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 2px solid #f1f5f9;
        }

        td {
            padding: 20px 15px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }

        tr:hover td {
            background: #fafbfc;
        }

        /* Components */
        .id-badge {
            background: #fff7ed;
            color: var(--orange);
            font-weight: 700;
            padding: 5px 10px;
            border-radius: 8px;
            font-family: monospace;
        }

        .firm-name {
            color: var(--navy);
            font-weight: 700;
            font-size: 16px;
            display: block;
        }

        .tax-pill {
            display: inline-block;
            background: #f1f5f9;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            margin-top: 4px;
            border: 1px solid #e2e8f0;
        }

        .doc-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            background: #eff6ff;
            color: #1e40af;
            border-radius: 8px;
            font-size: 11px;
            text-decoration: none;
            margin: 2px;
        }

        .action-btn {
            color: #ef4444;
            background: #fef2f2;
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            transition: 0.3s;
        }

        .action-btn:hover {
            background: #ef4444;
            color: white;
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

        <a href="assign-work.php"><i class="fas fa-tasks"></i> Assign Work</a>
        <a href="admin-review.php"><i class="fas fa-file-signature"></i> Quality Control</a>
        <a href="manage-clients.php" class="active"><i class="fas fa-users"></i> Manage Clients</a>
        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
        <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <div class="content-container">
            <div class="header-flex">
                <h1>Client Directory</h1>
                <div style="color: #64748b; font-size: 14px;">Total Registered: <b><?php echo $conn->query("SELECT id FROM users WHERE role='client'")->num_rows; ?></b></div>
            </div>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Firm Details</th>
                            <th>Tax Information</th>
                            <th>Recent Docs</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT users.*, client_profiles.company_name, client_profiles.gst_no, client_profiles.pan_no, client_profiles.owner_name 
                                FROM users 
                                LEFT JOIN client_profiles ON users.identifier = client_profiles.client_id 
                                WHERE users.role='client'
                                ORDER BY users.id DESC";

                        $res = $conn->query($sql);
                        if ($res->num_rows > 0):
                            while ($row = $res->fetch_assoc()):
                                $client_id = $row['identifier'];
                        ?>
                                <tr>
                                    <td><span class="id-badge"><?php echo $client_id; ?></span></td>
                                    <td>
                                        <span class="firm-name"><?php echo $row['company_name'] ?: $row['name']; ?></span>
                                        <span style="font-size:12px; color:#64748b;"><i class="far fa-user"></i> <?php echo $row['owner_name'] ?: 'N/A'; ?></span>
                                    </td>
                                    <td>
                                        <div class="tax-pill">GST: <?php echo $row['gst_no'] ?: '---'; ?></div><br>
                                        <div class="tax-pill">PAN: <?php echo $row['pan_no'] ?: '---'; ?></div>
                                    </td>
                                    <td>
                                        <?php
                                        $docs = $conn->query("SELECT * FROM client_documents WHERE client_id='$client_id' LIMIT 2");
                                        if ($docs->num_rows > 0) {
                                            while ($d = $docs->fetch_assoc()) {
                                                echo "<a href='../documents/{$d['file_path']}' class='doc-link' target='_blank'><i class='fas fa-file-pdf'></i> {$d['category']}</a>";
                                            }
                                        } else {
                                            echo "<span style='font-size:12px; color:#cbd5e1;'>Empty</span>";
                                        }
                                        ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="?delete=<?php echo $row['id']; ?>" class="action-btn" onclick="return confirm('Delete this client?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding:40px; color:#94a3b8;">No clients found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
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