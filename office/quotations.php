<?php
session_start();
include('../db.php');

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

// Check if a specific date is being filtered
$filter_date = isset($_GET['view_date']) ? mysqli_real_escape_string($conn, $_GET['view_date']) : '';

$status_msg = "";

// Handle New Quotation Submission
if(isset($_POST['add_quote'])){
    $client_id = mysqli_real_escape_string($conn, $_POST['client_id']);
    $service = mysqli_real_escape_string($conn, $_POST['service']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $validity = mysqli_real_escape_string($conn, $_POST['validity']);
    $today = date('Y-m-d H:i:s'); // Store full timestamp
    
    $count_res = $conn->query("SELECT id FROM quotations");
    $q_no = "Q-" . date('Y') . "-" . str_pad(($count_res->num_rows + 1), 3, "0", STR_PAD_LEFT);

    $sql = "INSERT INTO quotations (quote_no, client_id, service_name, amount, validity_date, status, created_at) 
            VALUES ('$q_no', '$client_id', '$service', '$amount', '$validity', 'Sent', '$today')";
    
    if($conn->query($sql)) {
        $status_msg = "<div class='alert-success'><i class='fas fa-check-circle'></i> Quotation $q_no generated successfully!</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Quotations | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --navy: #0b3c74; --orange: #ff8c00; --sidebar: #082d56; --bg: #f8fafc; --text-light: #64748b; }
        body { display:flex; margin:0; background:var(--bg); font-family: 'Inter', sans-serif; color: #334155; }
        
        /* Sidebar Styles (Matching Admin Dashboard) */
        .sidebar { width:280px; background:var(--sidebar); color:white; height:100vh; position:fixed; padding:30px 20px; box-sizing: border-box; display:flex; flex-direction:column; z-index: 1000; }
        .sidebar h2 { font-size: 22px; color: var(--orange); margin-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .sidebar a { color:rgba(255,255,255,0.7); text-decoration:none; display:flex; align-items:center; gap:12px; padding:14px; margin-bottom:8px; border-radius:12px; transition: 0.3s; }
        .sidebar a:hover, .active { background:rgba(255,255,255,0.1); color:white; border-left: 4px solid var(--orange); }
        .dropdown-content { display: none; background: rgba(0, 0, 0, 0.15); margin: 0 10px; border-radius: 10px; padding-left: 10px; }
        .show-menu { display: block !important; }
        .rotate-chevron { transform: rotate(180deg); }

        .main { margin-left:280px; padding:50px; width:calc(100% - 280px); box-sizing: border-box; }
        .grid { display: grid; grid-template-columns: 320px 1fr; gap: 30px; align-items: start; }
        .card { background:white; padding:30px; border-radius:24px; box-shadow:0 10px 30px rgba(0,0,0,0.02); border: 1px solid #edf2f7; }
        
        input, select { width:100%; padding:12px; margin:8px 0 15px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; box-sizing: border-box; }
        .btn-main { background:var(--navy); color:white; border:none; padding:15px; width:100%; border-radius:10px; font-weight:700; cursor:pointer; transition: 0.3s; }
        .btn-main:hover { background: var(--orange); }
        
        .filter-bar { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; background: #f1f5f9; padding: 10px 15px; border-radius: 12px; }
        
        table { width:100%; border-collapse:collapse; table-layout: fixed; }
        th { text-align:left; padding:15px; background:#f8fafc; color:var(--text-light); font-size:11px; text-transform:uppercase; border-bottom: 2px solid #edf2f7; }
        td { padding:15px; border-bottom:1px solid #f1f5f9; font-size:13px; word-wrap: break-word; }
        
        .col-date { width: 100px; }
        .col-no { width: 100px; }
        .col-amt { width: 110px; }
        .col-action { width: 140px; }

        .btn-invoice { background: var(--orange); color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 11px; font-weight: bold; display: inline-block; }
        .alert-success { color:#166534; padding:15px; background:#dcfce7; border-radius:12px; margin-bottom:25px; border: 1px solid #bbf7d0; }
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
            <a href="quotations.php" style="background:rgba(255,255,255,0.1); color:white;"><i class="fas fa-file-signature"></i> Quotations</a>
            <a href="invoices.php"><i class="fas fa-receipt"></i> Invoices</a>
            <a href="receipts.php"><i class="fas fa-check-double"></i> Receipts</a>
            <a href="outstanding.php"><i class="fas fa-exclamation-circle"></i> Outstanding</a>
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
    <h1>Quotation Center</h1>
    <?php echo $status_msg; ?>

    <div class="grid">
        <div class="card">
            <h3><i class="fas fa-plus-circle"></i> New Entry</h3>
            <form method="POST">
                <select name="client_id" required>
                    <option value="">Select Client</option>
                    <?php
                    $clients = $conn->query("SELECT identifier, name FROM users WHERE role='client'");
                    while($c = $clients->fetch_assoc()) echo "<option value='{$c['identifier']}'>{$c['identifier']} - {$c['name']}</option>";
                    ?>
                </select>
                <input type="text" name="service" placeholder="Service Name" required>
                <input type="number" name="amount" placeholder="Amount (₹)" required>
                <label style="font-size:11px; font-weight: bold; color: var(--navy);">VALID UNTIL</label>
                <input type="date" name="validity" required>
                <button name="add_quote" class="btn-main">Generate Quote</button>
            </form>
        </div>

        <div class="card" style="padding:0; overflow:hidden;">
            <div style="padding:20px 25px; border-bottom: 1px solid #f1f5f9;">
                <h3 style="margin:0 0 15px 0;">Records History</h3>
                
                <form method="GET" class="filter-bar">
                    <span style="font-size:13px; font-weight:600; color:var(--navy);">Filter by Create Date:</span>
                    <input type="date" name="view_date" value="<?php echo $filter_date; ?>" style="margin:0; width:180px; padding:8px;">
                    <button type="submit" style="background:var(--navy); color:white; border:none; padding:8px 15px; border-radius:8px; cursor:pointer;"><i class="fas fa-search"></i></button>
                    <?php if($filter_date): ?>
                        <a href="quotations.php" style="font-size:12px; color:#ef4444; text-decoration:none; margin-left:10px;">Clear Filter</a>
                    <?php endif; ?>
                </form>
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th class="col-date">Created</th>
                            <th class="col-no">Quote #</th>
                            <th>Client / Service</th>
                            <th class="col-amt">Amount</th>
                            <th class="col-action">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // FIX: Use DATE() to compare only the YYYY-MM-DD part of created_at
                        $query = "SELECT q.*, u.name FROM quotations q JOIN users u ON q.client_id = u.identifier";
                        if($filter_date) {
                            $query .= " WHERE DATE(q.created_at) = '$filter_date'";
                        }
                        $query .= " ORDER BY q.id DESC";
                        
                        $quotes = $conn->query($query);
                        if($quotes->num_rows > 0) {
                            while($q = $quotes->fetch_assoc()){
                                echo "<tr>
                                    <td class='col-date'>".date('d-m-Y', strtotime($q['created_at']))."</td>
                                    <td class='col-no' style='font-weight:700; color:var(--navy);'>{$q['quote_no']}</td>
                                    <td>
                                        <b>{$q['name']}</b><br>
                                        <small style='color:var(--text-light)'>{$q['service_name']}</small>
                                    </td>
                                    <td class='col-amt' style='font-weight:700;'>₹".number_format($q['amount'])."</td>
                                    <td class='col-action'>
                                        <a href='invoices.php?quote_ref={$q['quote_no']}&cid={$q['client_id']}&amt={$q['amount']}&svc=".urlencode($q['service_name'])."' class='btn-invoice'>
                                            <i class='fas fa-file-invoice'></i> Invoice
                                        </a>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; padding:40px; color:var(--text-light);'>No records found for this date.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleBilling() {
        document.getElementById('billingMenu').classList.toggle('show-menu');
        document.getElementById('chevron').classList.toggle('rotate-chevron');
    }
</script>
</body>
</html>