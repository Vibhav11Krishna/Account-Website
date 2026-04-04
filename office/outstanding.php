<?php
session_start();
include('../db.php');

// 1. SECURITY CHECK
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

// 2. GET FILTERS (Yearly & Search)
// Default to the current financial year end
$selected_year = isset($_GET['filter_year']) ? mysqli_real_escape_string($conn, $_GET['filter_year']) : (date('m') > 3 ? date('Y') + 1 : date('Y'));
$search_pending = isset($_GET['search_pending']) ? mysqli_real_escape_string($conn, $_GET['search_pending']) : '';
$search_received = isset($_GET['search_received']) ? mysqli_real_escape_string($conn, $_GET['search_received']) : '';

// Financial Year Date Range Logic
$fy_start = ($selected_year - 1) . "-04-01";
$fy_end = $selected_year . "-03-31";

// 3. FETCH TOTALS FOR COUNTER CARDS (Filtered by FY Range)
$stats_query = $conn->query("SELECT SUM(amount - paid_amount) as total_unpaid, COUNT(id) as count_unpaid 
                             FROM invoices 
                             WHERE paid_amount < amount AND created_at BETWEEN '$fy_start' AND '$fy_end'");
$stats = $stats_query->fetch_assoc();

$paid_stats_query = $conn->query("SELECT SUM(amount_paid) as total_received FROM receipts WHERE created_at BETWEEN '$fy_start' AND '$fy_end'");
$paid_stats = $paid_stats_query->fetch_assoc();
$total_received = $paid_stats['total_received'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Outstanding & Paid Ledger | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --navy: #0b3c74;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
            --text-light: #64748b;
            --danger: #ef4444;
            --success: #22c55e;
        }

        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            color: #334155;
        }

        /* Sidebar */
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

        .main {
            margin-left: 280px;
            padding: 50px;
            width: calc(100% - 280px);
            box-sizing: border-box;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
            border: 1px solid #edf2f7;
        }

        .stat-card h3 {
            margin: 0;
            font-size: 11px;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card p {
            margin: 10px 0 0;
            font-size: 24px;
            font-weight: 800;
            color: var(--navy);
        }

        .table-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
            overflow: hidden;
            border: 1px solid #edf2f7;
            margin-bottom: 40px;
        }

        .table-header {
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px;
            background: #f8fafc;
            color: var(--text-light);
            font-size: 11px;
            text-transform: uppercase;
            border-bottom: 2px solid #edf2f7;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }

        .aging-pill {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .aging-new { background: #dcfce7; color: #166534; }
        .aging-mid { background: #fef9c3; color: #854d0e; }
        .aging-old { background: #fee2e2; color: #991b1b; }

        .btn-remind {
            color: var(--navy);
            border: 1px solid #e2e8f0;
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 11px;
            transition: 0.2s;
        }

        .btn-remind:hover {
            background: var(--navy);
            color: white;
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

        .search-input {
            padding: 8px 15px;
            border-radius: 50px;
            border: 1px solid #cbd5e1;
            font-size: 12px;
            outline: none;
            transition: 0.3s;
        }
        .search-input:focus { border-color: var(--orange); }
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
            <a href="outstanding.php" class="active">Outstanding</a>
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
        <a href="javascript:void(0)" class="dropdown-btn"class="active" onclick="toggleMenu('reportsMenu', 'repChev')">
            <i class="fas fa-file-contract"></i> Reports
            <i class="fas fa-chevron-down" id="repChev" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
        </a>
        <div class="dropdown-content" id="reportsMenu">
           <a href="dsc-register.php"></i> DSC Register</a>
           <a href="service-report.php"></i> Service Report</a>
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
        <h1>Financial Overview</h1>

        <div style="background: white; padding: 20px; border-radius: 20px; margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between; border: 1px solid #edf2f7; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
            <div>
                <h2 style="margin:0; font-size:18px; color:var(--navy);">Financial Year: <?php echo ($selected_year - 1) . "-" . substr($selected_year, -2); ?></h2>
                <p style="margin:0; font-size:12px; color:var(--text-light);">Showing analytics and records from <?php echo date('d M Y', strtotime($fy_start)); ?> to <?php echo date('d M Y', strtotime($fy_end)); ?></p>
            </div>

            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <i class="fas fa-calendar-alt" style="color:var(--orange);"></i>
                <select name="filter_year" onchange="this.form.submit()" style="padding: 10px 20px; border-radius: 50px; border: 1px solid #cbd5e1; font-weight: bold; color: var(--navy); cursor: pointer; background: white;">
                    <?php
                    $start_year = 2025; 
                    $current_yr = date('m') > 3 ? date('Y') + 1 : date('Y');
                    for ($i = $current_yr; $i >= $start_year; $i--) {
                        $sel = ($selected_year == $i) ? 'selected' : '';
                        $display_fy = ($i - 1) . "-" . substr($i, -2);
                        echo "<option value='$i' $sel>$display_fy</option>";
                    }
                    ?>
                </select>
                <a href="outstanding.php" style="text-decoration:none; font-size:12px; color:var(--danger); margin-left:10px;">Reset Filters</a>
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-hand-holding-dollar"></i> Total Outstanding (FY <?php echo substr($selected_year, -2); ?>)</h3>
                <p style="color:var(--danger);">₹<?php echo number_format($stats['total_unpaid'], 2); ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-file-invoice"></i> Pending Bills</h3>
                <p><?php echo $stats['count_unpaid']; ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-wallet"></i> Total Paid (Received)</h3>
                <p style="color:var(--success);">₹<?php echo number_format($total_received, 2); ?></p>
            </div>
        </div>

        <div class="table-card">
            <div class="table-header" style="background: #fff1f2;">
                <h3 style="margin:0; color:#991b1b; font-size:14px;"><i class="fas fa-exclamation-triangle"></i> Pending Payments</h3>
                <form method="GET">
                    <input type="hidden" name="filter_year" value="<?php echo $selected_year; ?>">
                    <input type="text" name="search_pending" value="<?php echo $search_pending; ?>" class="search-input" placeholder="Search Client or Invoice...">
                </form>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Client Name</th>
                        <th>Issue Date</th>
                        <th>Aging</th>
                        <th>Balance Due</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $p_sql = "SELECT i.*, u.name, (i.amount - i.paid_amount) as balance_due, DATEDIFF(NOW(), i.created_at) as days_old 
                              FROM invoices i 
                              JOIN users u ON i.client_id = u.identifier 
                              WHERE i.paid_amount < i.amount AND i.created_at BETWEEN '$fy_start' AND '$fy_end'";

                    if ($search_pending) {
                        $p_sql .= " AND (u.name LIKE '%$search_pending%' OR i.invoice_no LIKE '%$search_pending%')";
                    }

                    $p_sql .= " ORDER BY days_old DESC";
                    $result = $conn->query($p_sql);

                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $age = $row['days_old'];
                            $class = ($age > 15) ? "aging-old" : (($age > 7) ? "aging-mid" : "aging-new");
                            $is_partial = ($row['paid_amount'] > 0);
                            $status_label = $is_partial ? "Partially Paid" : "Unpaid";
                            $status_style = $is_partial ? "background:#fef9c3; color:#854d0e; border:1px solid #fde047;" : "background:#fee2e2; color:#991b1b; border:1px solid #fecaca;";

                            echo "<tr>
                                <td>
                                    <div style='font-weight:700;'>#{$row['invoice_no']}</div>
                                    <span style='font-size:9px; padding:2px 5px; border-radius:4px; text-transform:uppercase; font-weight:bold; $status_style'>$status_label</span>
                                </td>
                                <td>{$row['name']}</td>
                                <td>" . date('d M Y', strtotime($row['created_at'])) . "</td>
                                <td><span class='aging-pill $class'>$age Days Old</span></td>
                                <td>
                                    <div style='font-weight:700; color:var(--danger);'>₹" . number_format($row['balance_due'], 2) . "</div>
                                    <div style='font-size:10px; color:var(--text-light);'>Total: ₹" . number_format($row['amount'], 2) . "</div>
                                </td>
                                <td><a href='invoices.php' class='btn-remind'>Record Payment</a></td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center; padding:30px;'>No pending records found for this period.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="table-card">
            <div class="table-header" style="background: #f0fdf4;">
                <h3 style="margin:0; color:#166534; font-size:14px;"><i class="fas fa-check-circle"></i> Payments Received (FY <?php echo ($selected_year - 1) . "-" . substr($selected_year, -2); ?>)</h3>
                <form method="GET">
                    <input type="hidden" name="filter_year" value="<?php echo $selected_year; ?>">
                    <input type="text" name="search_received" value="<?php echo $search_received; ?>" class="search-input" placeholder="Search Receipt/Client...">
                </form>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Receipt No</th>
                        <th>Invoice No</th>
                        <th>Client Name</th>
                        <th>Paid On</th>
                        <th>Amount Received</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $r_sql = "SELECT r.*, u.name FROM receipts r 
                              JOIN users u ON r.client_id = u.identifier 
                              WHERE r.created_at BETWEEN '$fy_start' AND '$fy_end'";

                    if ($search_received) {
                        $r_sql .= " AND (u.name LIKE '%$search_received%' OR r.receipt_no LIKE '%$search_received%' OR r.invoice_no LIKE '%$search_received%')";
                    }

                    $r_sql .= " ORDER BY r.created_at DESC";
                    $paid_res = $conn->query($r_sql);

                    if ($paid_res && $paid_res->num_rows > 0) {
                        while ($p = $paid_res->fetch_assoc()) {
                            echo "<tr>
                                <td style='font-weight:700;'>{$p['receipt_no']}</td>
                                <td>#{$p['invoice_no']}</td>
                                <td>{$p['name']}</td>
                                <td>" . date('d M Y', strtotime($p['created_at'])) . "</td>
                                <td style='font-weight:700; color:var(--success);'>₹" . number_format($p['amount_paid'], 2) . "</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding:30px;'>No payment history for this selection.</td></tr>";
                    }
                    ?>
                </tbody>
                <tfoot style="background: #f8fafc; border-top: 2px solid #edf2f7;">
                    <tr>
                        <td colspan="4" style="text-align:right; font-weight:800; padding:15px;">TOTAL RECEIVED (FY):</td>
                        <td style="font-weight:800; color:var(--success); font-size:16px;">₹<?php echo number_format($total_received, 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <script>
       
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