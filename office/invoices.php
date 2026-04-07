<?php
session_start();
include('../db.php');

// --- FILTER LOGIC ---
$from_date = isset($_GET['from_date']) ? mysqli_real_escape_string($conn, $_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? mysqli_real_escape_string($conn, $_GET['to_date']) : '';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Login.php");
    exit();
}
// HANDLE UPDATE INVOICE
if (isset($_POST['update_invoice'])) {
    $inv_id = mysqli_real_escape_string($conn, $_POST['edit_invoice_id']); // This is the 'id' from invoices table
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    $client_state = mysqli_real_escape_string($conn, $_POST['client_state']);
    
    $services = $_POST['services']; 
    $descriptions = $_POST['descriptions'];
    $amounts = $_POST['amounts'];   
    $tax_rates = $_POST['tax_rates']; 

    $tax_type = (strcasecmp(trim($client_state), 'Bihar') == 0) ? 'CGST+SGST' : 'IGST';
    
    // 1. Clear existing items to prevent duplicates/messy updates
    $conn->query("DELETE FROM invoice_items WHERE invoice_id = '$inv_id'");

    $total_base_amount = 0;
    $total_tax_amount = 0;

    // 2. Re-insert updated items
    foreach ($services as $key => $val) {
        $s_name = mysqli_real_escape_string($conn, $val);
        $s_desc = mysqli_real_escape_string($conn, $descriptions[$key]);
        $s_amount = (float)$amounts[$key];
        $s_tax_rate = (float)$tax_rates[$key];
        $s_tax_val = ($s_amount * $s_tax_rate) / 100;
        
        $total_base_amount += $s_amount;
        $total_tax_amount += $s_tax_val;

        $conn->query("INSERT INTO invoice_items (invoice_id, service_name, description, amount, tax_rate, tax_value) 
                      VALUES ('$inv_id', '$s_name', '$s_desc', '$s_amount', '$s_tax_rate', '$s_tax_val')");
    }

    // 3. Update Master Totals
    $cgst = ($tax_type == 'CGST+SGST') ? $total_tax_amount / 2 : 0;
    $sgst = ($tax_type == 'CGST+SGST') ? $total_tax_amount / 2 : 0;
    $igst = ($tax_type == 'IGST') ? $total_tax_amount : 0;

    $conn->query("UPDATE invoices SET 
                  due_date = '$due_date',
                  tax_type = '$tax_type',
                  amount = '$total_base_amount', 
                  cgst_amount = '$cgst', 
                  sgst_amount = '$sgst', 
                  igst_amount = '$igst' 
                  WHERE id = '$inv_id'");

    $status_msg = "<div class='alert-success'>✅ Invoice Updated Successfully!</div>";
}
// 1. AUTO-FILL LOGIC (Updated to support initial row)
$auto_cid = isset($_GET['cid']) ? mysqli_real_escape_string($conn, $_GET['cid']) : '';
$auto_amt = isset($_GET['amt']) ? mysqli_real_escape_string($conn, $_GET['amt']) : '';
$auto_svc = isset($_GET['svc']) ? mysqli_real_escape_string($conn, $_GET['svc']) : '';
$auto_tax = isset($_GET['tax']) ? mysqli_real_escape_string($conn, $_GET['tax']) : '18';
$qid = isset($_GET['qid']) ? mysqli_real_escape_string($conn, $_GET['qid']) : '';

$status_msg = "";

// 2. HANDLE MULTI-ITEM INVOICE GENERATION
if (isset($_POST['gen_invoice'])) {
    $client_id = mysqli_real_escape_string($conn, $_POST['client_id']);
    $inv_date = mysqli_real_escape_string($conn, $_POST['invoice_date']);
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    $client_state = mysqli_real_escape_string($conn, $_POST['client_state'] ?? 'Bihar');
    $today = date('Y-m-d H:i:s');

    // Arrays from the multi-item form
    $services = $_POST['services']; 
    $descriptions = $_POST['descriptions'];
    $amounts = $_POST['amounts'];   
    $tax_rates = $_POST['tax_rates']; 

    $tax_type = (strcasecmp(trim($client_state), 'Bihar') == 0) ? 'CGST+SGST' : 'IGST';

   // Generate Invoice Number based on the SELECTED Invoice Date Year
    $count_res = $conn->query("SELECT id FROM invoices");
    $next_id = ($count_res->num_rows > 0) ? $count_res->num_rows + 1 : 1;
    
    // Use the year from $inv_date instead of current year
    $inv_year = date('Y', strtotime($inv_date)); 
    $inv_no = "INV-" . $inv_year . "-" . str_pad($next_id, 3, "0", STR_PAD_LEFT);

    // Step A: Insert Master Record including invoice_date
    $sql_master = "INSERT INTO invoices (invoice_no, client_id, invoice_date, amount, tax_type, due_date, status, created_at) 
                   VALUES ('$inv_no', '$client_id', '$inv_date', 0, '$tax_type', '$due_date', 'Unpaid', '$today')";

    if ($conn->query($sql_master)) {
        $invoice_db_id = $conn->insert_id;
        $total_base_amount = 0;
        $total_tax_amount = 0;

        // Step B: Loop through and Insert Items
        foreach ($services as $key => $val) {
            $s_name = mysqli_real_escape_string($conn, $val);
            $s_desc = mysqli_real_escape_string($conn, $descriptions[$key]);
            $s_amount = (float)$amounts[$key];
            $s_tax_rate = (float)$tax_rates[$key];
            $s_tax_val = ($s_amount * $s_tax_rate) / 100;
            
            $total_base_amount += $s_amount;
            $total_tax_amount += $s_tax_val;

            $conn->query("INSERT INTO invoice_items (invoice_id, service_name, description, amount, tax_rate, tax_value) 
                          VALUES ('$invoice_db_id', '$s_name', '$s_desc', '$s_amount', '$s_tax_rate', '$s_tax_val')");
        }

        // Step C: Update Master with Final Totals
        $cgst = ($tax_type == 'CGST+SGST') ? $total_tax_amount / 2 : 0;
        $sgst = ($tax_type == 'CGST+SGST') ? $total_tax_amount / 2 : 0;
        $igst = ($tax_type == 'IGST') ? $total_tax_amount : 0;

        $conn->query("UPDATE invoices SET 
                      amount = '$total_base_amount', 
                      cgst_amount = '$cgst', 
                      sgst_amount = '$sgst', 
                      igst_amount = '$igst' 
                      WHERE id = '$invoice_db_id'");

        if (!empty($qid)) { $conn->query("UPDATE quotations SET status = 'Accepted' WHERE id = '$qid'"); }
        $status_msg = "<div class='alert-success'>✅ Success: Invoice $inv_no Created with multiple items!</div>";
    }
}

// 3. HANDLE PAYMENT (Unchanged)
if (isset($_POST['submit_payment'])) {
    $inv_no = mysqli_real_escape_string($conn, $_POST['inv_no']);
    $pay_amt = (float)$_POST['pay_amount'];
    $pay_method = mysqli_real_escape_string($conn, $_POST['pay_method']);
    $new_remarks = mysqli_real_escape_string($conn, $_POST['remarks']);

    $res = $conn->query("SELECT amount, cgst_amount, sgst_amount, igst_amount, paid_amount, client_id, remarks FROM invoices WHERE invoice_no = '$inv_no'");
    $inv = $res->fetch_assoc();

    if ($inv) {
        $total_bill = (float)$inv['amount'] + (float)$inv['cgst_amount'] + (float)$inv['sgst_amount'] + (float)$inv['igst_amount'];
        $already_paid = round((float)($inv['paid_amount'] ?? 0), 2);
        $new_total_paid = $already_paid + $pay_amt;
        $status = ($new_total_paid >= round($total_bill, 2)) ? 'Paid' : 'Partially Paid';
        $date = date('d-m-Y');
        $updated_remarks = ($inv['remarks'] ?? "") . "\n[$date]: $new_remarks ($pay_method)";

        $conn->query("UPDATE invoices SET paid_amount = '$new_total_paid', status = '$status', remarks = '$updated_remarks' WHERE invoice_no = '$inv_no'");

        $rcp_res = $conn->query("SELECT id FROM receipts");
        $rcp_no = "RCP-" . date('Y') . "-" . str_pad(($rcp_res->num_rows + 1), 3, "0", STR_PAD_LEFT);
        $conn->query("INSERT INTO receipts (receipt_no, invoice_no, client_id, amount_paid, payment_mode, method) VALUES ('$rcp_no', '$inv_no', '{$inv['client_id']}', '$pay_amt', '$status', '$pay_method')");

        $status_msg = "<div class='alert-success'>✅ Payment Recorded!</div>";
    }
}

// 4. DELETE (Unchanged)
if (isset($_GET['delete_inv'])) {
    $del_no = mysqli_real_escape_string($conn, $_GET['delete_inv']);
    $conn->query("DELETE FROM invoices WHERE invoice_no = '$del_no'");
    header("Location: invoices.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Invoices | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --navy: #0b3c74; --orange: #ff8c00; --sidebar: #082d56; --bg: #f8fafc; --text-light: #64748b; }
        body { display: flex; margin: 0; background: var(--bg); font-family: 'Inter', sans-serif; color: #334155; }
        .sidebar { width: 280px; background: var(--sidebar); color: white; height: 100vh; position: fixed; padding: 30px 20px; box-sizing: border-box; border-right: 4px solid var(--orange); }
        .sidebar h2 { font-size: 22px; color: var(--orange); margin-bottom: 40px; }
        .sidebar a { color: rgba(255, 255, 255, 0.7); text-decoration: none; display: flex; align-items: center; gap: 12px; padding: 14px; margin-bottom: 8px; border-radius: 12px; }
        .sidebar a:hover, .active { background: rgba(255, 255, 255, 0.1); color: white; border-left: 4px solid var(--orange); }
        .dropdown-content { display: none; background: rgba(0, 0, 0, 0.15); margin: 0 10px; border-radius: 10px; }
        .show-menu { display: block !important; }
        .rotate-chevron { transform: rotate(180deg); }
        .main { margin-left: 280px; padding: 40px; width: calc(100% - 280px); box-sizing: border-box; }
        .grid { display: grid; grid-template-columns: 450px 1fr; gap: 30px; }
        .card { background: white; padding: 30px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02); }
        input, select, textarea { width: 100%; padding: 10px; margin: 5px 0 10px; border: 1.5px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-family: inherit; }
        .btn-main { background: var(--navy); color: white; border: none; padding: 15px; width: 100%; border-radius: 10px; font-weight: 700; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #f8fafc; color: var(--text-light); font-size: 11px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .tax-tag { font-size: 10px; background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
        .status-paid { background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 6px; font-size: 11px; }
        .status-unpaid { background: #fee2e2; color: #ef4444; padding: 4px 8px; border-radius: 6px; font-size: 11px; }
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; }
        .modal-content { background:white; padding:30px; border-radius:20px; width:450px; max-height: 90vh; overflow-y: auto; }
        
        /* New Multi-item Styles */
        .item-row { border: 1px solid #f1f5f9; padding: 15px; border-radius: 12px; margin-bottom: 10px; position: relative; background: #fafafa; }
        .remove-btn { position: absolute; top: 10px; right: 10px; color: #ef4444; cursor: pointer; }
        .add-item-btn { background: #e2e8f0; color: #475569; border: none; padding: 10px; border-radius: 8px; width: 100%; cursor: pointer; margin-bottom: 15px; font-weight: 600; }
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
            <a href="invoices.php" class="active">Invoices</a>
            <a href="receipts.php">Receipts</a>
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
        <h1>Invoice Center</h1>
        <?php echo $status_msg; ?>

        <div class="grid">
            <div class="card">
                <h3>Create New Bill</h3>
                <form method="POST" id="invoiceForm">
                    <label>Select Client</label>
                    <select name="client_id" required>
                        <option value="">-- Choose Client --</option>
                        <?php
                                $clients = $conn->query("SELECT identifier, name FROM users WHERE role='client'");
                                while ($c = $clients->fetch_assoc()) echo "<option value='{$c['identifier']}'>{$c['identifier']} - {$c['name']}</option>";
                                ?>
                    </select>

                    <label>Place of Supply (For GST)</label>
                    <select name="client_state" required>
                        <option value="Bihar">Bihar (Intrastate - CGST/SGST)</option>
                        <option value="Outside">Outside Bihar (Interstate - IGST)</option>
                    </select>

                    <hr style="border:0; border-top:1px solid #eee; margin:20px 0;">
                    
                    <div id="itemsContainer">
                        <div class="item-row">
                            <label>Service</label>
                            <input type="text" name="services[]" value="<?php echo htmlspecialchars($auto_svc); ?>" placeholder="Service Name" required>
                            
                            <label>Description</label>
                            <textarea name="descriptions[]" placeholder="Description..." rows="1"></textarea>
                            
                            <div style="display: flex; gap: 10px;">
                                <div style="flex: 1;">
                                    <label>Amount</label>
                                    <input type="number" step="0.01" name="amounts[]" value="<?php echo $auto_amt; ?>" placeholder="0.00" required>
                                </div>
                                <div style="flex: 1;">
                                    <label>Tax %</label>
                                    <input type="number" step="0.01" name="tax_rates[]" value="<?php echo $auto_tax; ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="add-item-btn" onclick="addItem()"><i class="fas fa-plus"></i> Add Another Service</button>

                    <div style="display: flex; gap: 15px; margin-bottom: 15px;">
    <div style="flex: 1;">
        <label style="font-weight: bold; font-size: 13px;">Invoice Date</label>
        <input type="date" name="invoice_date" required value="<?php echo date('Y-m-d'); ?>">
    </div>
    <div style="flex: 1;">
        <label style="font-weight: bold; font-size: 13px;">Due Date</label>
        <input type="date" name="due_date" required value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
    </div>
</div>

                    <button name="gen_invoice" class="btn-main">Generate Invoice</button>
                </form>
            </div>

           <div class="card" style="padding:0; overflow:hidden;">
    <div style="padding:20px 25px; border-bottom: 1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 15px;">
        <h3 style="margin:0;"><i class="fas fa-history"></i> Invoice History</h3>
        
        <form method="GET" style="display:flex; align-items:center; gap:10px; background: #f8fafc; padding: 8px 15px; border-radius: 50px; border: 1px solid #e2e8f0;">
            <div style="display:flex; align-items:center; gap:8px;">
                <label style="font-size:10px; font-weight:800; color:var(--text-light);">FROM</label>
                <input type="date" name="from_date" value="<?php echo $from_date; ?>" style="margin:0; width:135px; padding:5px; border-radius:6px; border:1px solid #cbd5e1; font-size:11px;">
            </div>
            
            <div style="display:flex; align-items:center; gap:8px; border-left: 1px solid #e2e8f0; padding-left:10px;">
                <label style="font-size:10px; font-weight:800; color:var(--text-light);">TO</label>
                <input type="date" name="to_date" value="<?php echo $to_date; ?>" style="margin:0; width:135px; padding:5px; border-radius:6px; border:1px solid #cbd5e1; font-size:11px;">
            </div>

            <button type="submit" class="btn-main" style="width:auto; padding:6px 15px; margin:0; font-size:11px; border-radius:20px;">
                <i class="fas fa-search"></i>
            </button>

            <?php if($from_date || $to_date): ?>
                <a href="invoices.php" style="font-size:18px; color:#ef4444; text-decoration:none;" title="Reset Filter">
                    <i class="fas fa-times-circle"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div style="overflow-x: auto;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="padding:15px 25px;">Inv Date</th>
                    <th>Invoice #</th>
                    <th>Client & Tax</th>
                    <th>Financials</th>
                    <th>Status & Action</th>
                </tr>
            </thead>
           <tbody>
    <?php
    // 1. BUILD FILTERED QUERY - Updated to filter by invoice_date
    $query = "SELECT i.*, u.name FROM invoices i JOIN users u ON i.client_id = u.identifier";
    
    $conditions = [];
    if ($from_date) { $conditions[] = "i.invoice_date >= '$from_date'"; }
    if ($to_date) { $conditions[] = "i.invoice_date <= '$to_date'"; }
    
    if (count($conditions) > 0) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    // 2. SORT BY INVOICE DATE - Most professional for accounting
    $query .= " ORDER BY i.invoice_date DESC, i.id DESC";
    $invoices = $conn->query($query);
    
    if($invoices && $invoices->num_rows > 0) {
        while ($inv = $invoices->fetch_assoc()) {
            $base = (float)$inv['amount'];
            $gst = (float)$inv['cgst_amount'] + (float)$inv['sgst_amount'] + (float)$inv['igst_amount'];
            $grand_total = $base + $gst;
            $paid = (float)($inv['paid_amount'] ?? 0);
            $balance = $grand_total - $paid;
            $status_class = strtolower(str_replace(' ', '', $inv['status']));
    ?>
        <tr>
            <td style="padding:15px 25px;">
                <span style="font-weight:600;"><?php echo date('d-m-Y', strtotime($inv['invoice_date'])); ?></span>
                <div style="font-size:10px; color:var(--text-light); margin-top:4px;">
                    Due: <?php echo date('d-m-Y', strtotime($inv['due_date'])); ?>
                </div>
            </td>
            <td style='font-weight:700; color:var(--navy);'><?php echo $inv['invoice_no']; ?></td>
            <td>
                <b><?php echo $inv['name']; ?></b><br>
                <span class='tax-tag'><?php echo $inv['tax_type']; ?></span>
            </td>
            <td>
                <div>Total: <b>₹<?php echo number_format($grand_total, 2); ?></b></div>
                <div style='font-size:11px; color:#ef4444;'>Balance: ₹<?php echo number_format($balance, 2); ?></div>
            </td>
            <td>
                <span class='status-<?php echo $status_class; ?>'><?php echo $inv['status']; ?></span>
                <div style='margin-top:10px;'>
                    <a href='view-invoice.php?inv_no=<?php echo $inv['invoice_no']; ?>' target='_blank' style='color:#e11d48; margin-right:10px;' title='PDF'><i class='fas fa-file-pdf'></i></a>
                    <a href='javascript:void(0)' onclick='openPayModal("<?php echo $inv['invoice_no']; ?>", "<?php echo $balance; ?>")' style='color:#10b981; margin-right:10px;' title='Record Payment'><i class='fas fa-coins'></i></a>
                    <a href='edit-invoice.php?id=<?php echo $inv['id']; ?>' style='color:#3b82f6; margin-right:10px;' title='Edit'><i class='fas fa-edit'></i></a>
                    <a href='invoices.php?delete_inv=<?php echo $inv['invoice_no']; ?>' onclick='return confirm("Are you sure you want to delete this invoice?")' style='color:#ccc;' title='Delete'><i class='fas fa-trash-alt'></i></a>
                </div>
            </td>
        </tr>
    <?php 
        } 
    } else {
        echo "<tr><td colspan='5' style='text-align:center; padding:50px; color:var(--text-light);'>
                <i class='fas fa-search' style='font-size:30px; opacity:0.3; margin-bottom:10px; display:block;'></i>
                No invoices found for this period.
              </td></tr>";
    }
    ?>
</tbody>
        </table>
    </div>
</div>
    </div>

    <div id="payModal" class="modal">
        <div class="modal-content">
            <h3>Record Payment</h3>
            <form method="POST">
                <input type="hidden" name="inv_no" id="modal_inv_no">
                <label>Amount Paid</label>
                <input type="number" step="0.01" name="pay_amount" id="modal_pay_amt" required>
                <label>Method</label>
                <select name="pay_method" required>
                    <option value="UPI">UPI</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>
                <label>Remarks</label>
                <textarea name="remarks" placeholder="Payment details..." rows="3"></textarea>
                <button name="submit_payment" class="btn-main">Save Payment</button>
                <button type="button" onclick="closeModal('payModal')" style="width:100%; border:none; background:none; cursor:pointer; margin-top:10px;">Cancel</button>
            </form>
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
        function addItem() {
            const container = document.getElementById('itemsContainer');
            const newRow = document.createElement('div');
            newRow.className = 'item-row';
            newRow.innerHTML = `
                <i class="fas fa-times remove-btn" onclick="this.parentElement.remove()"></i>
                <label>Service</label>
                <input type="text" name="services[]" placeholder="Service Name" required>
                <label>Description</label>
                <textarea name="descriptions[]" placeholder="Description..." rows="1"></textarea>
                <div style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <label>Amount</label>
                        <input type="number" step="0.01" name="amounts[]" placeholder="0.00" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Tax %</label>
                        <input type="number" step="0.01" name="tax_rates[]" value="18" required>
                    </div>
                </div>
            `;
            container.appendChild(newRow);
        }

        function openPayModal(invNo, balance) {
            document.getElementById('modal_inv_no').value = invNo;
            document.getElementById('modal_pay_amt').value = parseFloat(balance).toFixed(2);
            document.getElementById('payModal').style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>