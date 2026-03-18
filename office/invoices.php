<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Login.php");
    exit();
}

// 1. AUTO-FILL LOGIC
$auto_cid = isset($_GET['cid']) ? mysqli_real_escape_string($conn, $_GET['cid']) : '';
$auto_amt = isset($_GET['amt']) ? mysqli_real_escape_string($conn, $_GET['amt']) : '';
$auto_svc = isset($_GET['svc']) ? mysqli_real_escape_string($conn, $_GET['svc']) : '';
$auto_tax = isset($_GET['tax']) ? mysqli_real_escape_string($conn, $_GET['tax']) : '18';
$qid = isset($_GET['qid']) ? mysqli_real_escape_string($conn, $_GET['qid']) : '';

$status_msg = "";

// 2. HANDLE INVOICE GENERATION & UPDATING
if (isset($_POST['gen_invoice'])) {
    $client_id = mysqli_real_escape_string($conn, $_POST['client_id']);
    $service = mysqli_real_escape_string($conn, $_POST['service']);
    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? ''); // Added Description
    $base_amount = (float)$_POST['amount'];
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    $today = date('Y-m-d H:i:s');
    $edit_id = isset($_POST['edit_inv_no']) ? mysqli_real_escape_string($conn, $_POST['edit_inv_no']) : '';

    $client_state = 'Bihar';
    $tax_rate = (float)$_POST['tax_rate'];

    if (!empty($qid)) {
        $q_res = $conn->query("SELECT client_state, tax_rate FROM quotations WHERE id = '$qid'");
        if ($q_res && $q_res->num_rows > 0) {
            $q_data = $q_res->fetch_assoc();
            $client_state = $q_data['client_state'] ?? 'Bihar';
            if (isset($q_data['tax_rate']) && (float)$q_data['tax_rate'] > 0) {
                $tax_rate = (float)$q_data['tax_rate'];
            }
        }
    }

    $tax_total = ($base_amount * $tax_rate) / 100;

    if (strcasecmp(trim($client_state), 'Bihar') == 0) {
        $tax_type = 'CGST+SGST';
        $cgst = $tax_total / 2;
        $sgst = $tax_total / 2;
        $igst = 0;
    } else {
        $tax_type = 'IGST';
        $cgst = 0;
        $sgst = 0;
        $igst = $tax_total;
    }

    if (!empty($edit_id)) {
        // UPDATE LOGIC
        $sql = "UPDATE invoices SET 
                service_name='$service', description='$description', amount='$base_amount', 
                tax_rate='$tax_rate', tax_type='$tax_type', cgst_amount='$cgst', 
                sgst_amount='$sgst', igst_amount='$igst', due_date='$due_date' 
                WHERE invoice_no = '$edit_id'";
        $op_text = "Updated";
    } else {
        // INSERT LOGIC
        $count_res = $conn->query("SELECT id FROM invoices");
        $next_id = ($count_res->num_rows > 0) ? $count_res->num_rows + 1 : 1;
        $inv_no = "INV-" . date('Y') . "-" . str_pad($next_id, 3, "0", STR_PAD_LEFT);
        
        $sql = "INSERT INTO invoices (invoice_no, client_id, service_name, description, amount, tax_rate, tax_type, cgst_amount, sgst_amount, igst_amount, due_date, status, created_at) 
                VALUES ('$inv_no', '$client_id', '$service', '$description', '$base_amount', '$tax_rate', '$tax_type', '$cgst', '$sgst', '$igst', '$due_date', 'Unpaid', '$today')";
        $op_text = "Created ($inv_no)";
    }

    if ($conn->query($sql)) {
        if (!empty($qid)) { $conn->query("UPDATE quotations SET status = 'Accepted' WHERE id = '$qid'"); }
        $status_msg = "<div class='alert-success'>✅ Success: Invoice $op_text successfully!</div>";
    } else {
        $status_msg = "<div class='alert-danger'>Error: " . $conn->error . "</div>";
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
        .grid { display: grid; grid-template-columns: 350px 1fr; gap: 30px; }
        .card { background: white; padding: 30px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02); }
        input, select, textarea { width: 100%; padding: 12px; margin: 8px 0 15px; border: 1.5px solid #e2e8f0; border-radius: 10px; box-sizing: border-box; font-family: inherit; }
        .btn-main { background: var(--navy); color: white; border: none; padding: 15px; width: 100%; border-radius: 10px; font-weight: 700; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #f8fafc; color: var(--text-light); font-size: 11px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .tax-tag { font-size: 10px; background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
        .status-paid { background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 6px; font-size: 11px; }
        .status-unpaid { background: #fee2e2; color: #ef4444; padding: 4px 8px; border-radius: 6px; font-size: 11px; }
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; }
        .modal-content { background:white; padding:30px; border-radius:20px; width:450px; max-height: 90vh; overflow-y: auto; }
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
                <a href="invoices.php" style="background:rgba(255,255,255,0.1); color:white;"><i class="fas fa-receipt"></i> Invoices</a>
                <a href="receipts.php"><i class="fas fa-check-double"></i> Receipts</a>
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
        <h1>Invoice Center</h1>
        <?php echo $status_msg; ?>

        <div class="grid">
            <div class="card">
                <h3>Create New Bill</h3>
                <form method="POST">
                    <label>Select Client</label>
                    <select name="client_id" required>
                        <option value="">-- Choose Client --</option>
                        <?php
                        $clients = $conn->query("SELECT identifier, name FROM users WHERE role='client'");
                        while ($c = $clients->fetch_assoc()) {
                            $selected = ($c['identifier'] == $auto_cid) ? "selected" : "";
                            echo "<option value='{$c['identifier']}' $selected>{$c['name']}</option>";
                        }
                        ?>
                    </select>

                    <label>Service</label>
                    <input type="text" name="service" value="<?php echo htmlspecialchars($auto_svc); ?>" placeholder="Enter service name" required>

                    <label>Description</label>
                    <textarea name="description" placeholder="Write Your Description..." rows="2"></textarea>

                    <div style="display: flex; gap: 15px;">
                        <div style="flex: 1;">
                            <label>Base Amount</label>
                            <input type="number" step="0.01" name="amount" value="<?php echo $auto_amt; ?>" placeholder="0.00" required>
                        </div>
                        <div style="flex: 1;">
                            <label>Tax Rate %</label>
                            <input type="number" step="0.01" name="tax_rate" value="<?php echo $auto_tax; ?>" placeholder="18" required>
                        </div>
                    </div>

                    <label>Due Date</label>
                    <input type="date" name="due_date" required>

                    <button name="gen_invoice" class="btn-main">Generate Invoice</button>
                </form>
            </div>

            <div class="card" style="padding:0; overflow:hidden;">
                <table>
                    <thead>
                        <tr>
                            <th>Inv Date</th>
                            <th>Invoice #</th>
                            <th>Client & Tax</th>
                            <th>Financials</th>
                            <th>Status & Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $invoices = $conn->query("SELECT i.*, u.name FROM invoices i JOIN users u ON i.client_id = u.identifier ORDER BY i.id DESC");
                        while ($inv = $invoices->fetch_assoc()) {
                            $base = (float)$inv['amount'];
                            $gst = (float)$inv['cgst_amount'] + (float)$inv['sgst_amount'] + (float)$inv['igst_amount'];
                            $grand_total = $base + $gst;
                            $paid = (float)($inv['paid_amount'] ?? 0);
                            $balance = $grand_total - $paid;
                            $status_class = strtolower(str_replace(' ', '', $inv['status']));
                            $inv_json = htmlspecialchars(json_encode($inv), ENT_QUOTES, 'UTF-8');

                            echo "<tr>
                                    <td>" . date('d-m-Y', strtotime($inv['created_at'])) . "</td>
                                    <td style='font-weight:700;'>{$inv['invoice_no']}</td>
                                    <td>
                                        <b>{$inv['name']}</b><br>
                                        <span class='tax-tag'>{$inv['tax_type']} ({$inv['tax_rate']}%)</span>
                                    </td>
                                    <td>
                                        <div>Total: <b>₹" . number_format($grand_total, 2) . "</b></div>
                                        <div style='font-size:11px; color:#ef4444;'>Due: ₹" . number_format($balance, 2) . "</div>
                                    </td>
                                    <td>
                                        <span class='status-$status_class'>{$inv['status']}</span>
                                        <div style='margin-top:10px;'>
                                            <a href='view-invoice.php?inv_no={$inv['invoice_no']}' target='_blank' style='color:#e11d48; margin-right:10px;'><i class='fas fa-file-pdf'></i></a>
                                            <a href='javascript:void(0)' onclick='openEditModal($inv_json)' style='color:var(--navy); margin-right:10px;' title='Edit Invoice'><i class='fas fa-edit'></i></a>
                                            <a href='javascript:void(0)' onclick='openPayModal(\"{$inv['invoice_no']}\", \"$balance\")' style='color:#10b981; margin-right:10px;' title='Pay'><i class='fas fa-coins'></i></a>
                                            <a href='invoices.php?delete_inv={$inv['invoice_no']}' onclick='return confirm(\"Delete?\")' style='color:#ccc;'><i class='fas fa-trash-alt'></i></a>
                                        </div>
                                    </td>
                                </tr>";
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

    <div id="editModal" class="modal">
        <div class="modal-content" style="width: 500px;">
            <h3 id="edit_title">Edit Invoice</h3>
            <form method="POST">
                <input type="hidden" name="edit_inv_no" id="edit_inv_no">
                <input type="hidden" name="client_id" id="edit_client_id">

                <label>Service</label>
                <input type="text" name="service" id="edit_service" required>

                <label>Description</label>
                <textarea name="description" id="edit_description" rows="3"></textarea>

                <div style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <label>Base Amount</label>
                        <input type="number" step="0.01" name="amount" id="edit_amount" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Tax Rate %</label>
                        <input type="number" step="0.01" name="tax_rate" id="edit_tax" required>
                    </div>
                </div>

                <label>Due Date</label>
                <input type="date" name="due_date" id="edit_due_date" required>

                <button name="gen_invoice" class="btn-main">Save Changes</button>
                <button type="button" onclick="closeModal('editModal')" style="width:100%; border:none; background:none; cursor:pointer; margin-top:10px; color:var(--text-light);">Discard</button>
            </form>
        </div>
    </div>

    <script>
        function toggleBilling() {
            document.getElementById('billingMenu').classList.toggle('show-menu');
            document.getElementById('chevron').classList.toggle('rotate-chevron');
        }

        function openPayModal(invNo, balance) {
            document.getElementById('modal_inv_no').value = invNo;
            document.getElementById('modal_pay_amt').value = parseFloat(balance).toFixed(2);
            document.getElementById('payModal').style.display = 'flex';
        }

        function openEditModal(data) {
            document.getElementById('edit_inv_no').value = data.invoice_no;
            document.getElementById('edit_client_id').value = data.client_id;
            document.getElementById('edit_title').innerText = "Edit " + data.invoice_no;
            document.getElementById('edit_service').value = data.service_name;
            document.getElementById('edit_description').value = data.description || '';
            document.getElementById('edit_amount').value = data.amount;
            document.getElementById('edit_tax').value = data.tax_rate;
            document.getElementById('edit_due_date').value = data.due_date;
            document.getElementById('editModal').style.display = 'flex';
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