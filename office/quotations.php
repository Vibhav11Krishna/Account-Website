<?php
session_start();
include('../db.php');
// 1. UPDATE FILTER LOGIC (Place this near your other $_GET logic)
$from_date = isset($_GET['from_date']) ? mysqli_real_escape_string($conn, $_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? mysqli_real_escape_string($conn, $_GET['to_date']) : '';
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
    if ($_GET['msg'] == 'updated') {
        $status_msg = "<div class='alert-success'><i class='fas fa-check-circle'></i> Quotation updated successfully!</div>";
    }
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../Register.php");
    exit();
}

$filter_date = isset($_GET['view_date']) ? mysqli_real_escape_string($conn, $_GET['view_date']) : '';

// Handle Multi-Service Quotation Submission
if (isset($_POST['add_quote'])) {
    $client_id = mysqli_real_escape_string($conn, $_POST['client_id']);
    $validity = mysqli_real_escape_string($conn, $_POST['validity']);
    $client_state = mysqli_real_escape_string($conn, $_POST['client_state']);

    // Arrays from form
    $services = $_POST['services'];
    $amounts = $_POST['amounts'];
    $tax_rates = $_POST['tax_rates'];

    $tax_type = ($client_state == 'Bihar') ? 'CGST+SGST' : 'IGST';
    $today = date('Y-m-d H:i:s');

    $count_res = $conn->query("SELECT id FROM quotations");
    $q_no = "QUO/" . str_pad(($count_res->num_rows + 1), 2, "0", STR_PAD_LEFT);

    // 1. Insert Master Record
    $sql_master = "INSERT INTO quotations (quote_no, client_id, service_name, amount, tax_type, validity_date, status, created_at, client_state) 
                   VALUES ('$q_no', '$client_id', 'Multiple Services', 0, '$tax_type', '$validity', 'Sent', '$today', '$client_state')";

    if ($conn->query($sql_master)) {
        $quotation_id = $conn->insert_id;
        $grand_total = 0;
        $base_total = 0;

        // 2. Insert Multiple Items into quotation_items table
        foreach ($services as $key => $val) {
            $s_name = mysqli_real_escape_string($conn, $val);
            $s_amount = (float)$amounts[$key];
            $s_tax_rate = (float)$tax_rates[$key];
            $s_tax_value = ($s_amount * $s_tax_rate) / 100;

            $base_total += $s_amount;
            $grand_total += ($s_amount + $s_tax_value);

            $conn->query("INSERT INTO quotation_items (quotation_id, service_name, amount, tax_rate, tax_value) 
                          VALUES ('$quotation_id', '$s_name', '$s_amount', '$s_tax_rate', '$s_tax_value')");
        }

        // 3. Update Master with Final Totals
        $conn->query("UPDATE quotations SET total_amount = '$grand_total', amount = '$base_total' WHERE id = '$quotation_id'");
        $status_msg = "<div class='alert-success'><i class='fas fa-check-circle'></i> Quotation $q_no saved with multiple items!</div>";
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
            grid-template-columns: 1fr;
            gap: 30px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        input,
        select {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            box-sizing: border-box;
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
            margin-top: 20px;
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

        .btn-convert {
            background: #dcfce7;
            color: #166534;
        }

        /* Green style for conversion */
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

        .item-row input,
        .item-row select {
            margin: 0;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>Karunesh Kumar & Associates Admin</h2>
        <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('billingMenu', 'billChev')">
                <i class="fas fa-file-invoice-dollar"></i> Billing
                <i class="fas fa-chevron-down" id="billChev" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="billingMenu">
                <a href="quotations.php" class="active">Quotations</a>
                <a href="invoices.php">Invoices</a>
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
            <a href="javascript:void(0)" class="dropdown-btn" class="active" onclick="toggleMenu('reportsMenu', 'repChev')">
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
        <h1>Quotation Center</h1>
        <?php echo $status_msg; ?>

        <div class="grid">
            <div class="card">
                <h3><i class="fas fa-plus-circle"></i> New Quotation</h3>
                <form method="POST">
                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:20px;">
                        <div>
                            <label style="font-size:11px; font-weight:700;">CLIENT</label>
                           <select name="client_id" required>
    <option value="">-- Choose Client (Name + ID) --</option>
    <?php
    // Fetching company names and IDs directly from client_profiles
    $client_query = "SELECT client_id, company_name FROM client_profiles ORDER BY company_name ASC";
    $client_result = $conn->query($client_query);

    if ($client_result && $client_result->num_rows > 0) {
        while ($row = $client_result->fetch_assoc()) {
            // Check if this is the auto-filled client from GET parameters
            $selected = ($auto_cid == $row['client_id']) ? 'selected' : '';
            
            // Format the display string: Company Name [ID]
            $displayName = htmlspecialchars($row['company_name']) . " [" . $row['client_id'] . "]";
            
            echo "<option value='{$row['client_id']}' $selected>{$displayName}</option>";
        }
    }
    ?>
</select>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700;">PLACE OF SUPPLY</label>
                            <select name="client_state" required>
                                <option value="Bihar" selected>Bihar (Intrastate)</option>
                                <option value="Outside Bihar">Outside Bihar (Interstate)</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700;">VALID UNTIL</label>
                            <input type="date" name="validity" required value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                        </div>
                    </div>

                    <h4 style="margin-top:30px; border-bottom:2px solid #f1f5f9; padding-bottom:10px;">Service Items</h4>
                    <table style="margin-top:10px;">
                        <thead>
                            <tr>
                                <th width="50%">Description</th>
                                <th width="20%">Amount (₹)</th>
                                <th width="20%">Tax Rate</th>
                                <th width="10%"></th>
                            </tr>
                        </thead>
                        <tbody id="serviceItems">
                            <tr class="item-row">
                                <td><input type="text" name="services[]" placeholder="Service Description" required></td>
                                <td><input type="number" name="amounts[]" placeholder="0.00" step="0.01" required></td>
                                <td>
                                    <select name="tax_rates[]">
                                        <option value="0">0%</option>
                                        <option value="5">5%</option>
                                        <option value="12">12%</option>
                                        <option value="18" selected>18%</option>
                                        <option value="28">28%</option>
                                    </select>
                                </td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>

                    <button type="button" onclick="addItem()" style="margin-top:10px; background:#e2e8f0; border:none; padding:8px 15px; border-radius:8px; cursor:pointer; font-size:12px; font-weight:bold;">
                        <i class="fas fa-plus"></i> Add Item
                    </button>

                    <button name="add_quote" class="btn-main">Generate & Save Quotation</button>
                </form>
            </div>

            <div class="card" style="padding:0; overflow:hidden;">
                <div style="padding:20px 25px; border-bottom: 1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 15px;">
                    <h3 style="margin:0;"><i class="fas fa-history"></i> History Ledger</h3>

                    <form method="GET" style="display:flex; align-items:center; gap:10px; background: #f8fafc; padding: 8px 15px; border-radius: 50px; border: 1px solid #e2e8f0;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <label style="font-size:10px; font-weight:800; color:var(--text-light);">FROM</label>
                            <input type="date" name="from_date" value="<?php echo $from_date; ?>" style="margin:0; width:135px; padding:5px; border-radius:6px; border:1px solid #cbd5e1; font-size:12px;">
                        </div>

                        <div style="display:flex; align-items:center; gap:8px; border-left: 1px solid #e2e8f0; padding-left:10px;">
                            <label style="font-size:10px; font-weight:800; color:var(--text-light);">TO</label>
                            <input type="date" name="to_date" value="<?php echo $to_date; ?>" style="margin:0; width:135px; padding:5px; border-radius:6px; border:1px solid #cbd5e1; font-size:12px;">
                        </div>

                        <button type="submit" class="btn-main" style="width:auto; padding:6px 15px; margin:0; font-size:11px; border-radius:20px;">
                            <i class="fas fa-filter"></i>
                        </button>

                        <?php if ($from_date || $to_date): ?>
                            <a href="quotations.php" style="font-size:18px; color:#ef4444; text-decoration:none;" title="Reset Filter">
                                <i class="fas fa-times-circle"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div style="overflow-x: auto;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th style="padding:15px 25px;">Date</th>
                                <th>Quotation #</th>
                                <th>Client & Service</th>
                                <th>Tax Type</th>
                                <th style="text-align:right;">Grand Total</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                      <tbody>
    <?php
    // The query is already correctly joining cp (client_profiles)
    $query = "SELECT q.*, cp.company_name 
              FROM quotations q 
              JOIN client_profiles cp ON q.client_id = cp.client_id";

    if ($from_date && $to_date) {
        $query .= " WHERE DATE(q.created_at) BETWEEN '$from_date' AND '$to_date'";
    } elseif ($from_date) {
        $query .= " WHERE DATE(q.created_at) >= '$from_date'";
    } elseif ($to_date) {
        $query .= " WHERE DATE(q.created_at) <= '$to_date'";
    }

    $query .= " ORDER BY q.id DESC";
    $res = $conn->query($query);

    if ($res && $res->num_rows > 0) {
        while ($q = $res->fetch_assoc()) {
    ?>
            <tr>
                <td style="padding:15px 25px;">
                    <span style="font-weight:600; color:#1e293b;"><?php echo date('d M, Y', strtotime($q['created_at'])); ?></span><br>
                </td>
                <td>
                    <span style="background:#f1f5f9; padding:4px 8px; border-radius:5px; font-family:monospace; font-weight:700; color:var(--navy);">
                        <?php echo $q['quote_no']; ?>
                    </span>
                </td>
                <td>
                    <b style="color:#0f172a; text-transform: uppercase;">
                        <?php echo htmlspecialchars($q['company_name']); ?>
                    </b><br>
                    <span style="font-size:12px; color:var(--text-light);"><?php echo htmlspecialchars($q['service_name']); ?></span>
                </td>
                <td>
                    <span style="font-size:10px; background:<?php echo ($q['tax_type'] == 'CGST+SGST' ? '#dcfce7' : '#dbeafe'); ?>; color:<?php echo ($q['tax_type'] == 'CGST+SGST' ? '#166534' : '#1e40af'); ?>; padding:3px 8px; border-radius:12px; font-weight:bold;">
                        <?php echo $q['tax_type']; ?>
                    </span>
                </td>
                <td style="text-align:right; font-weight:800; color:#0f172a; font-size:15px;">
                    ₹<?php echo number_format($q['total_amount'], 2); ?>
                </td>
                <td style="text-align:center;">
                    <div style="display:flex; justify-content:center; gap:5px;">
                        <a href="view-quotation.php?id=<?php echo $q['id']; ?>" class="btn-sm btn-pdf" target="_blank" title="View PDF"><i class="fas fa-file-pdf"></i></a>
                        <a href="edit-quotation.php?id=<?php echo $q['id']; ?>" class="btn-sm" style="background:#fef9c3; color:#854d0e; border:1px solid #fde047;" title="Edit"><i class="fas fa-edit"></i></a>
                        <a href="convert-to-invoice.php?quote_id=<?php echo $q['id']; ?>" class="btn-sm btn-convert" onclick="return confirm('Convert to Invoice?')" title="Convert"><i class="fas fa-exchange-alt"></i></a>
                        <a href="?delete_id=<?php echo $q['id']; ?>" class="btn-sm" style="background:#fee2e2; color:#b91c1c;" onclick="return confirm('Delete?')" title="Delete"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
    <?php
        }
    } else {
        echo "<tr><td colspan='6' style='text-align:center; padding:50px; color:var(--text-light);'>
                <i class='fas fa-search' style='font-size:30px; opacity:0.3; margin-bottom:10px; display:block;'></i>
                No quotations found for the selected range.
              </td></tr>";
    }
    ?>
</tbody>
                    </table>
                </div>
            </div>
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
            const container = document.getElementById('serviceItems');
            const row = document.createElement('tr');
            row.className = 'item-row';
            row.innerHTML = `
                <td><input type="text" name="services[]" placeholder="Service Description" required></td>
                <td><input type="number" name="amounts[]" placeholder="0.00" step="0.01" required></td>
                <td>
                    <select name="tax_rates[]">
                        <option value="0">0%</option>
                        <option value="5">5%</option>
                        <option value="12">12%</option>
                        <option value="18" selected>18%</option>
                        <option value="28">28%</option>
                    </select>
                </td>
                <td><button type="button" onclick="this.parentElement.parentElement.remove()" style="border:none; background:none; color:#ef4444; cursor:pointer;"><i class="fas fa-times"></i></button></td>
            `;
            container.appendChild(row);
        }
    </script>
</body>

</html>