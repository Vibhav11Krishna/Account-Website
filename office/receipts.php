<?php
session_start();
include('../db.php');

// 1. SECURITY CHECK
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

// 2. INITIALIZE VARIABLES (Prevents "Undefined Index" Errors)
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$from_date = isset($_GET['from_date']) ? mysqli_real_escape_string($conn, $_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? mysqli_real_escape_string($conn, $_GET['to_date']) : '';

// 3. BUILD DYNAMIC QUERY
// Using LEFT JOIN so receipts show even if the client was deleted
$query = "SELECT r.*, u.name 
          FROM receipts r 
          LEFT JOIN users u ON r.client_id = u.identifier 
          WHERE 1=1";

// Filter by Search Text
if (!empty($search)) {
    $query .= " AND (u.name LIKE '%$search%' OR r.receipt_no LIKE '%$search%' OR r.invoice_no LIKE '%$search%')";
}

// Filter by Date Range (Correctly handles the created_at timestamp)
if (!empty($from_date) && !empty($to_date)) {
    $query .= " AND DATE(r.created_at) BETWEEN '$from_date' AND '$to_date'";
}

$query .= " ORDER BY r.id DESC"; // Order by most recent
$receipts = $conn->query($query);

// 4. CHECK FOR SQL ERRORS
if (!$receipts) {
    die("Database Error: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Receipts Ledger | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --navy: #0b3c74;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
            --text-light: #64748b;
        }

        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            color: #334155;
        }

        /* Sidebar */
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

        /* Search Bar */
        .search-area {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
            border: 1px solid #edf2f7;
            margin-bottom: 30px;
        }

        .search-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto auto;
            gap: 15px;
            align-items: flex-end;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .input-group label {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-light);
            text-transform: uppercase;
        }

        .input-group input {
            padding: 12px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            outline: none;
            font-size: 14px;
        }

        .btn-filter {
            background: var(--navy);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-reset {
            background: #f1f5f9;
            color: var(--text-light);
            padding: 12px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        /* Table */
        .table-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
            overflow: hidden;
            border: 1px solid #edf2f7;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 18px;
            background: #f8fafc;
            color: var(--text-light);
            font-size: 11px;
            text-transform: uppercase;
            border-bottom: 2px solid #edf2f7;
        }

        td {
            padding: 18px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }

        .amount {
            font-weight: 800;
            color: #166534;
        }

        .receipt-pill {
            background: #f1f5f9;
            color: var(--navy);
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
        }

        .btn-view {
            color: var(--navy);
            text-decoration: none;
            font-size: 11px;
            border: 1px solid #e2e8f0;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-view:hover {
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
        <h2>Karunesh Kumar & Associates Admin</h2>
        <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i>Dashboard</a>
        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn active" onclick="toggleBilling()">
                <i class="fas fa-file-invoice-dollar"></i> Billing
                <i class="fas fa-chevron-down rotate-chevron" id="chevron" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content show-menu" id="billingMenu">
                <a href="quotations.php"><i class="fas fa-file-signature"></i> Quotations</a>
                <a href="invoices.php" ><i class="fas fa-receipt"></i> Invoices</a>
                <a href="receipts.php"style="background:rgba(255,255,255,0.1); color:white;"><i class="fas fa-check-double"></i> Receipts</a>
                <a href="outstanding.php"><i class="fas fa-exclamation-circle"></i> Outstanding</a>
            </div>
        </div>
        <a href="assign-work.php"><i class="fas fa-tasks"></i> Assign Work</a>
        <a href="admin-review.php"><i class="fas fa-file-signature"></i> Quality Control</a>
        <a href="Master-Vault.php"><i class="fas fa-file-signature"></i>Master Vault</a>
        <a href="manage-clients.php"><i class="fas fa-users"></i> Manage Clients</a>
        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
        <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <h1>Payment History</h1>

        <div class="search-area">
            <form method="GET" class="search-grid">
                <div class="input-group">
                    <label>Search Client or Receipt #</label>
                    <input type="text" name="search" placeholder="Type here..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="input-group">
                    <label>From Date</label>
                    <input type="date" name="from_date" value="<?php echo $from_date; ?>">
                </div>
                <div class="input-group">
                    <label>To Date</label>
                    <input type="date" name="to_date" value="<?php echo $to_date; ?>">
                </div>
                <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Apply</button>
                <a href="receipts.php" class="btn-reset">Reset</a>
            </form>
        </div>

        <div class="table-card">
            <table>
                <thead>
    <tr>
        <th>Date Paid</th>
        <th>Receipt Number</th>
        <th>Client Name</th>
        <th>Invoice #</th>
        <th>Amount Paid</th>
        <th>Status</th> <th>Method</th> <th>Action</th>
    </tr>
</thead>
                <tbody>
    <?php
    if ($receipts->num_rows > 0) {
        while ($r = $receipts->fetch_assoc()) {
            $date = date('d M Y', strtotime($r['created_at']));
            $c_name = !empty($r['name']) ? $r['name'] : "<span style='color:#94a3b8;'>ID: {$r['client_id']}</span>";

            // Determine Icon and Color for the Method
            $method = !empty($r['method']) ? $r['method'] : 'Not Specified';
            $method_icon = "";
            
            switch($method) {
                case 'UPI':
                    $method_icon = "<i class='fas fa-mobile-alt' style='color:#6366f1;'></i> ";
                    break;
                case 'Cash':
                    $method_icon = "<i class='fas fa-money-bill-wave' style='color:#22c55e;'></i> ";
                    break;
                case 'Bank Transfer':
                    $method_icon = "<i class='fas fa-university' style='color:#0b3c74;'></i> ";
                    break;
                case 'Card':
                    $method_icon = "<i class='fas fa-credit-card' style='color:#f59e0b;'></i> ";
                    break;
                default:
                    $method_icon = "<i class='fas fa-receipt' style='color:gray;'></i> ";
            }

            echo "<tr>
                <td>$date</td>
                <td><span class='receipt-pill'>{$r['receipt_no']}</span></td>
                <td>$c_name</td>
                <td style='color:var(--text-light);'>#{$r['invoice_no']}</td>
                <td class='amount'>₹" . number_format($r['amount_paid'], 2) . "</td>
                
                <td><small style='font-weight:600; color:var(--text-light);'>{$r['payment_mode']}</small></td>
                
                <td style='font-weight:700;'>$method_icon $method</td>
                
                <td>
                    <a href='view_receipt.php?id={$r['receipt_no']}' target='_blank' class='btn-view'>
                        <i class='fas fa-file-invoice'></i> View Bill
                    </a>
                </td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='8' style='text-align:center; padding:60px; color:var(--text-light);'>
            <i class='fas fa-search' style='font-size:30px; margin-bottom:10px; display:block;'></i>
            No records found for the selected filters.
          </td></tr>";
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