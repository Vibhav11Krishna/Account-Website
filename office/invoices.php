<?php
session_start();
include('../db.php');

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

// 1. AUTO-FILL LOGIC: Catch data sent from the Quotation "Invoice" button
$auto_cid = isset($_GET['cid']) ? mysqli_real_escape_string($conn, $_GET['cid']) : '';
$auto_amt = isset($_GET['amt']) ? mysqli_real_escape_string($conn, $_GET['amt']) : '';
$auto_svc = isset($_GET['svc']) ? mysqli_real_escape_string($conn, $_GET['svc']) : '';

$status_msg = "";

// 2. HANDLE INVOICE GENERATION (When "Generate" button is clicked)
if(isset($_POST['gen_invoice'])){
    $client_id = mysqli_real_escape_string($conn, $_POST['client_id']);
    $service = mysqli_real_escape_string($conn, $_POST['service']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    $today = date('Y-m-d H:i:s');
    
    // Auto-generate Invoice Number (e.g., INV-2026-001)
    $count_res = $conn->query("SELECT id FROM invoices");
    $inv_no = "INV-" . date('Y') . "-" . str_pad(($count_res->num_rows + 1), 3, "0", STR_PAD_LEFT);

    $sql = "INSERT INTO invoices (invoice_no, client_id, service_name, amount, due_date, status, created_at) 
            VALUES ('$inv_no', '$client_id', '$service', '$amount', '$due_date', 'Unpaid', '$today')";
    
    if($conn->query($sql)) {
        $status_msg = "<div class='alert-success'><i class='fas fa-check-double'></i> Invoice $inv_no Generated & Sent to Client Dashboard!</div>";
    }
}
// 3. HANDLE MARK AS PAID (Automatic Receipt Generation)
if(isset($_GET['pay_inv'])){
    $inv_no = mysqli_real_escape_string($conn, $_GET['pay_inv']);
    
    // Fetch invoice details to copy to receipts
    $res = $conn->query("SELECT * FROM invoices WHERE invoice_no = '$inv_no' AND status = 'Unpaid'");
    $inv = $res->fetch_assoc();
    
    if($inv){
        $amt = $inv['amount'];
        $cid = $inv['client_id'];
        
        // Generate Receipt No
        $rcp_count = $conn->query("SELECT id FROM receipts")->num_rows + 1;
        $rcp_no = "RCP-" . date('Y') . "-" . str_pad($rcp_count, 3, "0", STR_PAD_LEFT);

        // Action: Insert to Receipts AND Update Invoice status
        $sql_rcp = "INSERT INTO receipts (receipt_no, invoice_no, client_id, amount_paid, payment_mode) 
                    VALUES ('$rcp_no', '$inv_no', '$cid', '$amt', 'Cash/Online')";
        
        $sql_upd = "UPDATE invoices SET status = 'Paid' WHERE invoice_no = '$inv_no'";

        if($conn->query($sql_rcp) && $conn->query($sql_upd)) {
            $status_msg = "<div class='alert-success'><i class='fas fa-check-circle'></i> Invoice $inv_no Paid! Receipt $rcp_no generated.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Invoices | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --navy: #0b3c74; --orange: #ff8c00; --sidebar: #082d56; --bg: #f8fafc; --text-light: #64748b; }
        body { display:flex; margin:0; background:var(--bg); font-family: 'Inter', sans-serif; color: #334155; }
        
        /* Sidebar */
        .sidebar { width:280px; background:var(--sidebar); color:white; height:100vh; position:fixed; padding:30px 20px; box-sizing: border-box; display:flex; flex-direction:column; }
        .sidebar h2 { font-size: 22px; color: var(--orange); margin-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .sidebar a { color:rgba(255,255,255,0.7); text-decoration:none; display:flex; align-items:center; gap:12px; padding:14px; margin-bottom:8px; border-radius:12px; transition: 0.3s; }
        .sidebar a:hover, .active { background:rgba(255,255,255,0.1); color:white; border-left: 4px solid var(--orange); }
        .dropdown-content { display: none; background: rgba(0, 0, 0, 0.15); margin: 0 10px; border-radius: 10px; padding-left: 10px; }
        .show-menu { display: block !important; }

        .main { margin-left:280px; padding:50px; width:calc(100% - 280px); box-sizing: border-box; }
        .grid { display: grid; grid-template-columns: 320px 1fr; gap: 30px; align-items: start; }
        .card { background:white; padding:30px; border-radius:24px; box-shadow:0 10px 30px rgba(0,0,0,0.02); border: 1px solid #edf2f7; }
        
        input, select { width:100%; padding:12px; margin:8px 0 15px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; box-sizing: border-box; }
        .btn-main { background:var(--navy); color:white; border:none; padding:15px; width:100%; border-radius:10px; font-weight:700; cursor:pointer; transition: 0.3s; }
        .btn-main:hover { background: var(--orange); }

        table { width:100%; border-collapse:collapse; }
        th { text-align:left; padding:15px; background:#f8fafc; color:var(--text-light); font-size:11px; text-transform:uppercase; border-bottom: 2px solid #edf2f7; }
        td { padding:15px; border-bottom:1px solid #f1f5f9; font-size:13px; }
        
        .status-unpaid { color: #ef4444; background: #fee2e2; padding: 4px 8px; border-radius: 6px; font-weight: bold; font-size: 11px; }
        .status-paid { color: #166534; background: #dcfce7; padding: 4px 8px; border-radius: 6px; font-weight: bold; font-size: 11px; }
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
            <a href="quotations.php" ><i class="fas fa-file-signature"></i> Quotations</a>
            <a href="invoices.php"style="background:rgba(255,255,255,0.1); color:white;"><i class="fas fa-receipt"></i> Invoices</a>
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
    <h1>Invoice Center</h1>
    <?php echo $status_msg; ?>

    <div class="grid">
        <div class="card">
            <h3><i class="fas fa-file-invoice"></i> Create New Bill</h3>
            <form method="POST">
                <label style="font-size:11px; font-weight:700;">SELECT CLIENT</label>
                <select name="client_id" required>
                    <option value="">-- Choose Client --</option>
                    <?php
                    $clients = $conn->query("SELECT identifier, name FROM users WHERE role='client'");
                    while($c = $clients->fetch_assoc()) {
                        $selected = ($c['identifier'] == $auto_cid) ? "selected" : "";
                        echo "<option value='{$c['identifier']}' $selected>{$c['identifier']} - {$c['name']}</option>";
                    }
                    ?>
                </select>

                <label style="font-size:11px; font-weight:700;">SERVICE DESCRIPTION</label>
                <input type="text" name="service" value="<?php echo $auto_svc; ?>" placeholder="write your description" required>

                <label style="font-size:11px; font-weight:700;">BILLING AMOUNT (₹)</label>
                <input type="number" name="amount" value="<?php echo $auto_amt; ?>" placeholder="0.00" required>
                
                <label style="font-size:11px; font-weight:700;">DUE DATE</label>
                <input type="date" name="due_date" required>

                <button name="gen_invoice" class="btn-main">Generate Official Invoice</button>
            </form>
        </div>

        <div class="card" style="padding:0; overflow:hidden;">
            <div style="padding:20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin:0;">Recent Invoices</h3>
                <span style="font-size: 12px; color: var(--text-light);">Total Records: <?php echo $conn->query("SELECT id FROM invoices")->num_rows; ?></span>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Inv Date</th>
                            <th>Invoice #</th>
                            <th>Client</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $invoices = $conn->query("SELECT i.*, u.name FROM invoices i JOIN users u ON i.client_id = u.identifier ORDER BY i.id DESC");
                        if($invoices->num_rows > 0) {
                            while($inv = $invoices->fetch_assoc()){
                                $status_badge = ($inv['status'] == 'Paid') ? 'status-paid' : 'status-unpaid';
                                echo "<tr>
                                    <td>".date('d-m-Y', strtotime($inv['created_at']))."</td>
                                    <td style='font-weight:700; color:var(--navy);'>{$inv['invoice_no']}</td>
                                    <td>{$inv['name']}</td>
                                    <td style='font-weight:700;'>₹".number_format($inv['amount'])."</td>
                                    <td><span class='$status_badge'>{$inv['status']}</span></td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; padding:40px; color:var(--text-light);'>No invoices generated yet.</td></tr>";
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
    }
</script>
</body>
</html>