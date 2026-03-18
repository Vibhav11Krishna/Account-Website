<?php
session_start();
include('../db.php');

// --- DELETE QUOTATION ---
if (isset($_GET['delete_id'])) {
    $del_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    if ($conn->query("DELETE FROM quotations WHERE id = '$del_id'")) {
        header("Location: quotations.php?msg=deleted");
        exit();
    }
}

$status_msg = "";
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') {
        $status_msg = "<div class='alert-success' style='background:#fee2e2; border-color:#ef4444; color:#991b1b;'><i class='fas fa-trash'></i> Quotation deleted successfully.</div>";
    }
    if ($_GET['msg'] == 'converted') {
        $status_msg = "<div class='alert-success'><i class='fas fa-check-circle'></i> Quotation successfully converted to Invoice!</div>";
    }
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../Register.php");
    exit();
}

$filter_date = isset($_GET['view_date']) ? mysqli_real_escape_string($conn, $_GET['view_date']) : '';

// Handle New Quotation Submission
if (isset($_POST['add_quote'])) {
    $client_id = mysqli_real_escape_string($conn, $_POST['client_id']);
    $service = mysqli_real_escape_string($conn, $_POST['service']);
    $amount = (float)mysqli_real_escape_string($conn, $_POST['amount']);
    $validity = mysqli_real_escape_string($conn, $_POST['validity']);
    $client_state = mysqli_real_escape_string($conn, $_POST['client_state']);

    // Tax Type Logic
    $tax_type = ($client_state == 'Bihar') ? 'CGST+SGST' : 'IGST';
    $tax_rate = isset($_POST['tax_rate']) ? (float)$_POST['tax_rate'] : 0;
    $tax_amount = ($amount * $tax_rate) / 100;
    $total_amount = $amount + $tax_amount;

    $today = date('Y-m-d H:i:s');
    $count_res = $conn->query("SELECT id FROM quotations");
    $q_no = "QUO/" . str_pad(($count_res->num_rows + 1), 2, "0", STR_PAD_LEFT);

    // Added client_state and tax_type to INSERT
    $sql = "INSERT INTO quotations (quote_no, client_id, service_name, amount, tax_rate, tax_type, total_amount, validity_date, status, created_at, client_state) 
            VALUES ('$q_no', '$client_id', '$service', '$amount', '$tax_rate', '$tax_type', '$total_amount', '$validity', 'Sent', '$today', '$client_state')";

    if ($conn->query($sql)) {
        $status_msg = "<div class='alert-success'><i class='fas fa-check-circle'></i> Quotation $q_no saved! Logic: $tax_type applied.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Quotations | KKA Admin</title>
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

        .sidebar {
            width: 280px;
            background: var(--sidebar);
            color: white;
            height: 100vh;
            position: fixed;
            padding: 30px 20px;
            box-sizing: border-box;
            border-right: 4px solid var(--orange);
        }

        .sidebar h2 {
            font-size: 22px;
            color: var(--orange);
            margin-bottom: 40px;
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
        }

        .sidebar a:hover,
        .active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 4px solid var(--orange);
        }

        .dropdown-content {
            display: none;
            background: rgba(0, 0, 0, 0.15);
            margin: 0 10px;
            border-radius: 10px;
        }

        .show-menu {
            display: block !important;
        }

        .rotate-chevron {
            transform: rotate(180deg);
        }

        .main {
            margin-left: 280px;
            padding: 40px;
            width: calc(100% - 280px);
            box-sizing: border-box;
        }

        .grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        input,
        select {
            width: 100%;
            padding: 12px;
            margin: 8px 0 15px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .tax-box {
            background: #f8fafc;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 1px dashed #cbd5e1;
        }

        .btn-main {
            background: var(--navy);
            color: white;
            border: none;
            padding: 14px;
            width: 100%;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-main:hover {
            background: var(--orange);
        }

        .btn-sm {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-right: 5px;
        }

        .btn-pdf {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-inv {
            background: #e0f2fe;
            color: #0284c7;
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
            border-bottom: 1px solid #edf2f7;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 5px solid #22c55e;
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
                <a href="quotations.php"style="background:rgba(255,255,255,0.1); color:white;"><i class="fas fa-file-signature"></i> Quotations</a>
                <a href="invoices.php" ><i class="fas fa-receipt"></i> Invoices</a>
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
        <h1>Quotation Center</h1>
        <?php echo $status_msg; ?>

        <div class="grid">
            <div class="card">
                <h3><i class="fas fa-plus-circle"></i> New Quotation</h3>
                <form method="POST">
                    <label style="font-size:11px; font-weight:700;">CLIENT</label>
                    <select name="client_id" required>
                        <option value="">Select Client</option>
                        <?php
                        $clients = $conn->query("SELECT identifier, name FROM users WHERE role='client'");
                        while ($c = $clients->fetch_assoc()) echo "<option value='{$c['identifier']}'>{$c['identifier']} - {$c['name']}</option>";
                        ?>
                    </select>

                    <label style="font-size:11px; font-weight:700;">PLACE OF SUPPLY (STATE)</label>
                    <select name="client_state" required>
                        <option value="Bihar" selected>Bihar (Intrastate - CGST+SGST)</option>
                        <option value="Outside Bihar">Outside Bihar (Interstate - IGST)</option>
                    </select>

                    <input type="text" name="service" placeholder="Service Description" required>
                    <input type="number" name="amount" placeholder="Base Amount (₹)" step="0.01" required>

                    <div class="tax-box">
                        <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer;">
                            <input type="checkbox" id="tax_check" onchange="toggleTaxInput()" style="width:auto; margin:0;">
                            Apply GST/Tax Slab?
                        </label>
                        <div id="tax_input_div" style="display:none; margin-top:10px;">
                            <label style="font-size:11px; font-weight:700; color:var(--navy);">SELECT RATE</label>
                            <select name="tax_rate" id="tax_select">
                                <option value="0">0% (Exempted)</option>
                                <option value="5">5% GST</option>
                                <option value="12">12% GST</option>
                                <option value="18" selected>18% GST</option>
                                <option value="28">28% GST</option>
                            </select>
                        </div>
                    </div>

                    <label style="font-size:11px; font-weight:700;">VALID UNTIL</label>
                    <input type="date" name="validity" required value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">

                    <button name="add_quote" class="btn-main">Generate & Save</button>
                </form>
            </div>

            <div class="card" style="padding:0; overflow:hidden;">
                <div style="padding:20px 25px; border-bottom: 1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin:0;">Recent History</h3>
                    <form method="GET" style="display:flex; gap:5px;">
                        <input type="date" name="view_date" value="<?php echo $filter_date; ?>" style="margin:0; width:150px; padding:8px;">
                        <button type="submit" class="btn-main" style="width:40px; padding:8px;"><i class="fas fa-search"></i></button>
                    </form>
                </div>

                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Details</th>
                                <th>State</th>
                                <th>Tax Logic</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT q.*, u.name FROM quotations q JOIN users u ON q.client_id = u.identifier";
                            if ($filter_date) $query .= " WHERE DATE(q.created_at) = '$filter_date'";
                            $query .= " ORDER BY q.id DESC";

                            $res = $conn->query($query);
                            while ($q = $res->fetch_assoc()) {
                            ?>
                                <tr>
                                    <td>
                                        <small><?php echo $q['quote_no']; ?></small><br>
                                        <b><?php echo $q['name']; ?></b><br>
                                        <span style="font-size:11px; color:var(--text-light)"><?php echo $q['service_name']; ?></span>
                                    </td>
                                    <td><span style="font-size:10px; background:#e2e8f0; padding:2px 6px; border-radius:4px;"><?php echo $q['client_state']; ?></span></td>
                                    <td>
                                        <small style="color:<?php echo ($q['tax_type'] == 'CGST+SGST' ? '#16a34a' : '#2563eb'); ?>; font-weight:bold;">
                                            <?php echo $q['tax_type']; ?> (<?php echo $q['tax_rate']; ?>%)
                                        </small>
                                    </td>
                                    <td style="font-weight:700;">₹<?php echo number_format($q['total_amount'], 2); ?></td>
                                    <td>
                                        <a href="view-quotation.php?id=<?php echo $q['id']; ?>" class="btn-sm btn-pdf" target="_blank"><i class="fas fa-file-pdf"></i></a>
                                        <a href="invoices.php?cid=<?php echo $q['client_id']; ?>&amt=<?php echo $q['amount']; ?>&svc=<?php echo urlencode($q['service_name']); ?>&tax=<?php echo $q['tax_rate']; ?>&qid=<?php echo $q['id']; ?>"
                                            class="btn-convert"
                                            style="background: #10b981; color: white; padding: 6px 12px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 12px; display: inline-block;">
                                            <i class="fas fa-file-invoice"></i> Create Invoice
                                        </a>

                                        <a href="?delete_id=<?php echo $q['id']; ?>" class="btn-sm" style="background:#fee2e2; color:#b91c1c;" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php } ?>
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

        function toggleTaxInput() {
            const check = document.getElementById('tax_check');
            const inputDiv = document.getElementById('tax_input_div');
            inputDiv.style.display = check.checked ? 'block' : 'none';
            if (!check.checked) document.getElementById('tax_select').value = "0";
        }
    </script>
</body>

</html>