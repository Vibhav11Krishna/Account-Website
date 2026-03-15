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
        :root {
            --navy: #0b3c74;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
            --text: #334155;
            --border: #e2e8f0;
        }

        body { display: flex; margin: 0; background: var(--bg); font-family: 'Inter', sans-serif; color: var(--text); }

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

        .sidebar a:hover, .sidebar a.active { background: rgba(255, 255, 255, 0.1); color: white; border-left: 4px solid var(--orange); }

        .dropdown-content { display: none; background: rgba(0, 0, 0, 0.2); margin: 0 5px; border-radius: 8px; padding-left: 15px; }
        .dropdown-content a { font-size: 14px; padding: 10px; color: rgba(255, 255, 255, 0.6); }
        .show-menu { display: block; }
        .rotate-chevron { transform: rotate(90deg); }

        .main { margin-left: 280px; padding: 50px; width: calc(100% - 280px); box-sizing: border-box; }
        .content-container { max-width: 1300px; margin: 0 auto; }

        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }

        .search-container {
            margin-bottom: 25px;
            display: flex;
            gap: 12px;
            align-items: center;
            background: white;
            padding: 12px 20px;
            border-radius: 15px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }
        .search-container input { width: 100%; border: none; outline: none; font-size: 14px; font-family: inherit; }

        .table-card {
            background: white;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.02);
            border: 1px solid var(--border);
            overflow-x: auto;
        }

        table { width: 100%; border-collapse: collapse; min-width: 1100px; }
        th { text-align: left; padding: 15px; background: #fcfcfd; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: top; }
        tr:hover td { background: #fafbfc; }

        .id-badge { background: #fff7ed; color: var(--orange); font-weight: 700; padding: 5px 10px; border-radius: 8px; font-family: monospace; }
        .firm-name { color: var(--navy); font-weight: 700; font-size: 15px; display: block; }
        .tax-pill { display: inline-block; background: #f1f5f9; padding: 3px 8px; border-radius: 6px; font-size: 10px; margin-top: 4px; border: 1px solid #e2e8f0; font-family: monospace; }
        .doc-link { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; background: #eff6ff; color: #1e40af; border-radius: 8px; font-size: 11px; text-decoration: none; margin: 2px; }
        .action-btn { color: #ef4444; background: #fef2f2; width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; transition: 0.3s; text-decoration: none; }
        .action-btn:hover { background: #ef4444; color: white; }
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
        <a href="manage-clients.php"class="active"><i class="fas fa-users"></i> Manage Clients</a>
        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
        <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <div class="content-container">
            <div class="header-flex">
                <h1>Client Directory</h1>
                <div style="color: #64748b; font-size: 14px;">Total: <b><?php echo $conn->query("SELECT id FROM users WHERE role='client'")->num_rows; ?></b></div>
            </div>

            <div class="search-container">
                <i class="fas fa-search" style="color: #94a3b8;"></i>
                <input type="text" id="clientSearch" onkeyup="filterTable()" placeholder="Search by Firm Name, Owner, or Client ID...">
            </div>

            <div class="table-card">
                <table id="clientTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Firm & Owner</th>
                            <th>Contact Info</th>
                            <th>Details</th>
                            <th>Address</th>
                            <th>Documents</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Optimized SQL - strictly pulls from client_profiles
                        $sql = "SELECT 
                                    u.id, 
                                    u.identifier, 
                                    cp.company_name, 
                                    cp.owner_name, 
                                    cp.phone, 
                                    cp.business_email, 
                                    cp.gst_no, 
                                    cp.pan_no, 
                                    cp.address 
                                FROM users u 
                                LEFT JOIN client_profiles cp ON u.identifier = cp.client_id 
                                WHERE u.role='client'
                                ORDER BY u.id DESC";

                        $res = $conn->query($sql);
                        if ($res && $res->num_rows > 0):
                            while ($row = $res->fetch_assoc()):
                                $client_id = $row['identifier'];
                                
                                // Simplified variables - strictly using business profile data
                                $display_email = !empty($row['business_email']) ? $row['business_email'] : 'No Email';
                                $display_phone = !empty($row['phone']) ? $row['phone'] : 'No Phone';
                                $display_firm  = !empty($row['company_name']) ? $row['company_name'] : 'Unnamed Firm';
                        ?>
                                <tr>
                                    <td><span class="id-badge"><?php echo $client_id; ?></span></td>
                                    <td>
                                        <span class="firm-name"><?php echo htmlspecialchars($display_firm); ?></span>
                                        <span style="font-size:12px; color:#64748b;"><i class="far fa-user"></i> <?php echo htmlspecialchars($row['owner_name'] ?: 'N/A'); ?></span>
                                    </td>
                                    <td>
                                        <div style="font-weight:600; font-size:13px; color:var(--navy);">
                                            <i class="fas fa-phone-alt" style="font-size:10px; opacity:0.7;"></i> <?php echo htmlspecialchars($display_phone); ?>
                                        </div>
                                        <div style="font-size:11px; color:#94a3b8; margin-top:3px;">
                                            <i class="fas fa-envelope" style="font-size:10px; opacity:0.7;"></i> <?php echo htmlspecialchars($display_email); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tax-pill">GST: <?php echo htmlspecialchars($row['gst_no'] ?: '---'); ?></div><br>
                                        <div class="tax-pill">PAN: <?php echo htmlspecialchars($row['pan_no'] ?: '---'); ?></div>
                                    </td>
                                    <td style="max-width: 180px; font-size: 12px; color: #64748b; line-height: 1.4;">
                                        <?php echo htmlspecialchars($row['address'] ?: '---'); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $docs = $conn->query("SELECT * FROM client_documents WHERE client_id='$client_id' LIMIT 2");
                                        if ($docs && $docs->num_rows > 0) {
                                            while ($d = $docs->fetch_assoc()) {
                                                echo "<a href='../documents/{$d['file_path']}' class='doc-link' target='_blank'><i class='fas fa-file-pdf'></i> " . htmlspecialchars($d['category']) . "</a>";
                                            }
                                        } else { echo "<span style='color:#cbd5e1; font-size:12px;'>No Docs</span>"; }
                                        ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="?delete=<?php echo $row['id']; ?>" class="action-btn" onclick="return confirm('Delete this client?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="7" style="text-align:center; padding:50px;">No clients found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function filterTable() {
            let input = document.getElementById("clientSearch");
            let filter = input.value.toUpperCase();
            let tr = document.getElementById("clientTable").getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                let idCol = tr[i].getElementsByTagName("td")[0];
                let nameCol = tr[i].getElementsByTagName("td")[1];
                if (idCol || nameCol) {
                    let text = (idCol.textContent + nameCol.textContent).toUpperCase();
                    tr[i].style.display = text.indexOf(filter) > -1 ? "" : "none";
                }
            }
        }

        function toggleBilling() {
            document.getElementById('billingMenu').classList.toggle('show-menu');
            document.getElementById('chevron').classList.toggle('rotate-chevron');
        }
    </script>
</body>
</html>