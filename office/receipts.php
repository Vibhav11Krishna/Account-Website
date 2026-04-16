<?php
session_start();
include('../db.php');
// 2. HANDLE DELETE REQUEST
if (isset($_GET['delete_receipt'])) {
    $receipt_no = mysqli_real_escape_string($conn, $_GET['delete_receipt']);
    $del_sql = "DELETE FROM receipts WHERE receipt_no = '$receipt_no'";
    
    if ($conn->query($del_sql)) {
        header("Location: receipts.php?msg=deleted");
        exit();
    } else {
        die("Error deleting record: " . $conn->error);
    }
}
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
// Using LEFT JOIN with client_profiles to get the company_name
$query = "SELECT r.*, cp.company_name 
          FROM receipts r 
          LEFT JOIN client_profiles cp ON r.client_id = cp.client_id 
          WHERE 1=1";

// Filter by Search Text (Updated to search company_name)
if (!empty($search)) {
    $query .= " AND (cp.company_name LIKE '%$search%' OR r.receipt_no LIKE '%$search%' OR r.invoice_no LIKE '%$search%')";
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
    <a href="admin-dashboard.php" ><i class="fas fa-chart-pie"></i> Dashboard</a>

    <div class="dropdown-container">
        <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('billingMenu', 'billChev')">
            <i class="fas fa-file-invoice-dollar"></i> Billing
            <i class="fas fa-chevron-down" id="billChev" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
        </a>
        <div class="dropdown-content" id="billingMenu">
            <a href="quotations.php">Quotations</a>
            <a href="invoices.php">Invoices</a>
            <a href="receipts.php" class="active">Receipts</a>
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
    </div>
</div>
    <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
     <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
    <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

    <div class="main">
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
    <div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; border: 1px solid #fecaca;">
        <i class="fas fa-check-circle"></i> Receipt has been deleted successfully.
    </div>
<?php endif; ?>
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
           // UPDATED: Use company_name from client_profiles
            $c_name = !empty($r['company_name']) 
                      ? "<b style='color:#0f172a; text-transform: uppercase;'>" . htmlspecialchars($r['company_name']) . "</b>" 
                      : "<span style='color:#94a3b8;'>ID: {$r['client_id']}</span>";

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
                    <div style='display: flex; gap: 8px;'>
                        <a href='view_receipt.php?id={$r['receipt_no']}' target='_blank' class='btn-view' title='View Bill'>
                            <i class='fas fa-file-invoice'></i>
                        </a>
                        <a href='javascript:void(0)' 
                           onclick='confirmDelete(\"{$r['receipt_no']}\")' 
                           class='btn-view' 
                           style='color:#e11d48; border-color:#fecdd3;' 
                           title='Delete Receipt'>
                            <i class='fas fa-trash-alt'></i>
                        </a>
                    </div>
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
        function confirmDelete(receiptNo) {
    if (confirm("Are you sure you want to delete Receipt #" + receiptNo + "? This action cannot be undone.")) {
        window.location.href = "receipts.php?delete_receipt=" + receiptNo;
    }
}
    </script>
</body>

</html>