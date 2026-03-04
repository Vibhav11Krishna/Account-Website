<?php
session_start();
include('../db.php');

// 1. SECURITY CHECK
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

// 2. FETCH TOTALS FOR COUNTER CARDS
// Unpaid Stats
$stats_query = $conn->query("SELECT SUM(amount) as total_unpaid, COUNT(id) as count_unpaid FROM invoices WHERE status = 'Unpaid'");
$stats = $stats_query->fetch_assoc();

// Paid Stats (New Addition)
$paid_stats_query = $conn->query("SELECT SUM(amount_paid) as total_received FROM receipts");
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

        .main {
            margin-left: 280px;
            padding: 50px;
            width: calc(100% - 280px);
            box-sizing: border-box;
        }

        /* Stat Cards */
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

        /* Table Style */
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

        .aging-new {
            background: #dcfce7;
            color: #166534;
        }

        .aging-mid {
            background: #fef9c3;
            color: #854d0e;
        }

        .aging-old {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-paid {
            background: #e0f2fe;
            color: #0369a1;
        }

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
        <h2>KKA ADMIN</h2>
        <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> Summary</a>
        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn active" onclick="toggleBilling()">
                <i class="fas fa-file-invoice-dollar"></i> Billing
                <i class="fas fa-chevron-down rotate-chevron" id="chevron" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content show-menu" id="billingMenu">
                <a href="quotations.php"><i class="fas fa-file-signature"></i> Quotations</a>
                <a href="invoices.php" ><i class="fas fa-receipt"></i> Invoices</a>
                <a href="receipts.php"><i class="fas fa-check-double"></i> Receipts</a>
                <a href="outstanding.php"style="background:rgba(255,255,255,0.1); color:white;"><i class="fas fa-exclamation-circle"></i> Outstanding</a>
            </div>
        </div>
        <a href="assign-work.php"><i class="fas fa-tasks"></i> Assign Work</a>
        <a href="admin-review.php"><i class="fas fa-file-signature"></i> Quality Control</a>
        <a href="manage-clients.php"><i class="fas fa-users"></i> Manage Clients</a>
        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
        <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <h1>Financial Overview</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-hand-holding-dollar"></i> Total Outstanding</h3>
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
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Client Name</th>
                        <th>Issue Date</th>
                        <th>Aging</th>
                        <th>Amount Due</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT i.*, u.name, DATEDIFF(NOW(), i.created_at) as days_old 
                          FROM invoices i 
                          JOIN users u ON i.client_id = u.identifier 
                          WHERE i.status = 'Unpaid' 
                          ORDER BY days_old DESC";

                    $result = $conn->query($query);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $age = $row['days_old'];
                            $class = ($age > 15) ? "aging-old" : (($age > 7) ? "aging-mid" : "aging-new");

                            echo "<tr>
                            <td style='font-weight:700;'>#{$row['invoice_no']}</td>
                            <td>{$row['name']}</td>
                            <td>" . date('d M Y', strtotime($row['created_at'])) . "</td>
                            <td><span class='aging-pill $class'>$age Days Old</span></td>
                            <td style='font-weight:700; color:var(--danger);'>₹" . number_format($row['amount'], 2) . "</td>
                            <td><a href='receipts.php' class='btn-remind'>Record Payment</a></td>
                        </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center; padding:30px;'>No pending payments.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="table-card">
            <div class="table-header" style="background: #f0fdf4;">
                <h3 style="margin:0; color:#166534; font-size:14px;"><i class="fas fa-check-circle"></i> Recently Received Payments</h3>
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
                    // Fetch the latest 5 paid receipts
                    $paid_query = "SELECT r.*, u.name 
                               FROM receipts r 
                               JOIN users u ON r.client_id = u.identifier 
                               ORDER BY r.created_at DESC LIMIT 5";
                    $paid_res = $conn->query($paid_query);

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
                        echo "<tr><td colspan='5' style='text-align:center; padding:30px;'>No payment history yet.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
<script>
        function toggleBilling() {
            document.getElementById('billingMenu').classList.toggle('show-menu');
        }
    </script>
</body>

</html>