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
$auto_cid = isset($_GET['client_id']) ? mysqli_real_escape_string($conn, $_GET['client_id']) : '';

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotations | KKA Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --navy-dark: #051b34;
            --navy: #0b3c74;
            --navy-light: #155297;
            --orange: #ff8c00;
            --orange-glow: rgba(255, 140, 0, 0.15);
            --sidebar-bg: #062241;
            --sidebar-hover: rgba(255, 255, 255, 0.06);
            --bg: #f3f6fc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --danger: #ef4444;
            --danger-bg: #fef2f2;
            --success: #10b981;
            --border: #e2e8f0;
            --shadow-sm: 0 2px 8px rgba(11, 60, 116, 0.04);
            --shadow-md: 0 10px 25px -5px rgba(11, 60, 116, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
        }

        /* ================= SIDEBAR ================= */
        .sidebar {
            width: 280px;
            background: var(--sidebar-bg);
            color: white;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            border-right: 3px solid var(--orange);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--orange) var(--sidebar-bg);
            z-index: 100;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: var(--orange);
            border-radius: 10px;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 8px 20px 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 20px;
        }

        .brand-logo {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--orange), #d97706);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(255, 140, 0, 0.3);
        }

        .brand-header h2 {
            font-size: 15px;
            color: #ffffff;
            margin: 0;
            font-weight: 700;
            line-height: 1.3;
        }

        .sidebar-profile {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .profile-avatar-wrapper {
            position: relative;
            flex-shrink: 0;
        }

        .sidebar-avatar {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--navy-light), var(--navy));
            border: 2px solid var(--orange);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            font-weight: 700;
        }

        .online-status {
            width: 10px;
            height: 10px;
            background: var(--success);
            border: 2px solid var(--sidebar-bg);
            border-radius: 50%;
            position: absolute;
            bottom: -2px;
            right: -2px;
        }

        .profile-info strong {
            display: block;
            font-size: 13px;
            color: #ffffff;
            font-weight: 600;
        }

        .profile-info span {
            font-size: 11px;
            color: var(--orange);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-nav-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.4);
            margin: 10px 0 8px 10px;
            font-weight: 700;
        }

        .sidebar a {
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            margin-bottom: 4px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: var(--sidebar-hover);
            color: white;
            border-left: 3px solid var(--orange);
            padding-left: 17px;
        }

        .sidebar a.active {
            background: rgba(255, 140, 0, 0.12);
            color: #ffffff;
            font-weight: 600;
        }

        .dropdown-content {
            display: none;
            background: rgba(0, 0, 0, 0.15);
            margin: 2px 0 6px 10px;
            border-radius: 10px;
            padding: 4px 0;
            border-left: 2px solid rgba(255, 140, 0, 0.3);
        }

        .dropdown-content a {
            padding: 9px 14px;
            font-size: 12.5px;
            margin-bottom: 0;
            border-radius: 0;
        }

        .dropdown-content a:hover,
        .dropdown-content a.active {
            border-left: none;
            background: rgba(255, 255, 255, 0.05);
            color: var(--orange);
            padding-left: 14px;
        }

        .show-menu {
            display: block !important;
        }

        .rotate-chevron {
            transform: rotate(180deg);
        }

        /* ================= MAIN WORKSPACE ================= */
        .main {
            margin-left: 280px;
            padding: 0;
            width: calc(100% - 280px);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .admin-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(12px);
            padding: 14px 40px;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 90;
            box-shadow: var(--shadow-sm);
            gap: 16px;
        }

        .brand-section h1 {
            margin: 0;
            font-size: 20px;
            color: var(--text-main);
            font-weight: 800;
        }

        .welcome-text {
            font-size: 12.5px;
            color: var(--text-muted);
            margin-top: 1px;
            font-weight: 500;
        }

        .header-right-group {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
        }

        .header-clock {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            padding: 7px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .clock-icon i {
            color: var(--orange);
            font-size: 15px;
        }

        .clock-display {
            display: flex;
            flex-direction: column;
        }

        .clock-display span {
            font-size: 9px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
        }

        .clock-display strong {
            font-size: 12px;
            color: var(--text-main);
            font-weight: 700;
        }

        .header-user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            padding: 5px 6px 5px 12px;
            border-radius: 30px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .header-user-name {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--navy);
        }

        .header-logout-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--danger-bg);
            color: var(--danger);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 12.5px;
            transition: all 0.2s;
        }

        .header-logout-icon:hover {
            background: var(--danger);
            color: white;
            transform: scale(1.05);
        }

        .content-body {
            padding: 32px 40px;
            flex: 1;
            width: 100%;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        .card {
            background: var(--card-bg);
            padding: 26px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            margin-bottom: 20px;
        }

        input,
        select {
            width: 100%;
            padding: 11px 14px;
            margin: 6px 0;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            background: #f8fafc;
            font-family: inherit;
            font-size: 13.5px;
            color: var(--text-main);
            box-sizing: border-box;
            outline: none;
            transition: all 0.2s;
        }

        input:focus,
        select:focus {
            border-color: var(--navy);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(11, 60, 116, 0.1);
        }

        .btn-main {
            background: var(--navy);
            color: white;
            border: none;
            padding: 13px 20px;
            width: 100%;
            border-radius: 10px;
            font-family: inherit;
            font-weight: 700;
            font-size: 13.5px;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 18px;
        }

        .btn-main:hover {
            background: var(--orange);
        }

        .btn-sm {
            padding: 6px 11px;
            border-radius: 7px;
            font-size: 11.5px;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }

        .btn-pdf {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-convert {
            background: #dcfce7;
            color: #166534;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 14px 16px;
            background: #f8fafc;
            color: var(--navy);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 15px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 13.5px;
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 22px;
            border-left: 4px solid #16a34a;
            font-weight: 700;
            font-size: 13.5px;
        }

        .item-row input,
        .item-row select {
            margin: 0;
        }

        @media (max-width: 1024px) {
            .admin-top-bar {
                padding: 14px 20px;
            }
            .content-body {
                padding: 24px 20px;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="brand-header">
            <div class="brand-logo"><i class="fas fa-shield-alt"></i></div>
            <h2>Karunesh Kumar &amp; Associates</h2>
        </div>

        <div class="sidebar-profile">
            <div class="profile-avatar-wrapper">
                <div class="sidebar-avatar"><i class="fas fa-user-shield"></i></div>
                <div class="online-status"></div>
            </div>
            <div class="profile-info">
                <strong>Admin Executive</strong>
                <span>System Administrator</span>
            </div>
        </div>

        <div class="sidebar-nav-title">Main Menu</div>
        <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn active" onclick="toggleMenu('billingMenu', 'billChev')">
                <i class="fas fa-file-invoice-dollar"></i> Billing
                <i class="fas fa-chevron-down rotate-chevron" id="billChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content show-menu" id="billingMenu">
                <a href="quotations.php" class="active">Quotations</a>
                <a href="invoices.php">Invoices</a>
                <a href="receipts.php">Receipts</a>
                <a href="outstanding.php">Outstanding</a>
            </div>
        </div>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('vaultMenu', 'vaultChev')">
                <i class="fas fa-folder-open"></i> Documents
                <i class="fas fa-chevron-down" id="vaultChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="vaultMenu">
                <a href="admin-review.php">Quality Control</a>
                <a href="Master-Vault.php">Services</a>
                <a href="client-documents.php">Client Documents</a>
            </div>
        </div>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('reportsMenu', 'repChev')">
                <i class="fas fa-file-contract"></i> Reports
                <i class="fas fa-chevron-down" id="repChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="reportsMenu">
                <a href="dsc-register.php">DSC Register</a>
                <a href="service-report.php">Service Report</a>
                <a href="revenue-analytics.php">Revenue Analytics</a>
                <a href="Client-Revenue.php">Client Revenue</a>
                <a href="attendance.php">Attendance</a>
            </div>
        </div>

        <div class="sidebar-nav-title" style="margin-top:15px;">Management</div>
        <a href="assign-work.php"><i class="fas fa-tasks"></i> Assign Work</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('clientMenu', 'clientChev')">
                <i class="fas fa-users"></i> Manage Clients
                <i class="fas fa-chevron-down" id="clientChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="clientMenu">
                <a href="manage-clients.php">Clients</a>
                <a href="client-groups.php">Client Groups</a>
                <a href="client-services.php">Services</a>
                <a href="client-upload-docs.php">Clients Uploads</a>
            </div>
        </div>

        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
        <a href="../logout.php" style="margin-top:auto; color:#fca5a5;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <!-- Main Workspace -->
    <div class="main">
        <header class="admin-top-bar">
            <div class="brand-section">
                <h1>Quotation Center</h1>
                <div class="welcome-text">Create multi-item client quotations and manage conversion history</div>
            </div>

            <div class="header-right-group">
                <div class="header-clock">
                    <div class="clock-icon"><i class="fas fa-clock"></i></div>
                    <div class="clock-display">
                        <span id="liveDate">Loading Date...</span>
                        <strong id="liveTime">00:00:00</strong>
                    </div>
                </div>

                <div class="header-user-pill">
                    <span class="header-user-name">Admin</span>
                    <a href="../logout.php" class="header-logout-icon" title="Logout">
                        <i class="fas fa-power-off"></i>
                    </a>
                </div>
            </div>
        </header>

        <div class="content-body">
            <?php echo $status_msg; ?>

            <div class="grid">
                <div class="card">
                    <h3 style="margin:0 0 18px 0; color:var(--navy); font-size:16px; font-weight:800;"><i class="fas fa-plus-circle" style="color:var(--orange);"></i> New Quotation</h3>
                    <form method="POST">
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px;">
                            <div>
                                <label style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">CLIENT</label>
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
                                            $displayName = htmlspecialchars($row['company_name']) . " [" . htmlspecialchars($row['client_id']) . "]";

                                            echo "<option value='" . htmlspecialchars($row['client_id'], ENT_QUOTES) . "' $selected>{$displayName}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">PLACE OF SUPPLY</label>
                                <select name="client_state" required>
                                    <option value="Bihar" selected>Bihar (Intrastate)</option>
                                    <option value="Outside Bihar">Outside Bihar (Interstate)</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">VALID UNTIL</label>
                                <input type="date" name="validity" required value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                            </div>
                        </div>

                        <h4 style="margin:26px 0 10px 0; color:var(--navy); font-size:14px; font-weight:800; border-bottom:2px solid var(--border); padding-bottom:10px;">Service Items</h4>
                        <table>
                            <thead>
                                <tr>
                                    <th width="50%">Description</th>
                                    <th width="20%">Amount (&#8377;)</th>
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

                        <button type="button" onclick="addItem()" style="margin-top:12px; background:#f1f5f9; color:var(--navy); border:1px solid var(--border); padding:8px 15px; border-radius:8px; cursor:pointer; font-family:inherit; font-size:12px; font-weight:700;">
                            <i class="fas fa-plus"></i> Add Item
                        </button>

                        <button type="submit" name="add_quote" class="btn-main">Generate &amp; Save Quotation</button>
                    </form>
                </div>

                <div class="card" style="padding:0; overflow:hidden;">
                    <div style="padding:20px 25px; border-bottom: 1px solid var(--border); display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 15px;">
                        <h3 style="margin:0; color:var(--navy); font-size:16px; font-weight:800;"><i class="fas fa-history" style="color:var(--orange);"></i> History Ledger</h3>

                        <form method="GET" style="display:flex; align-items:center; gap:10px; background: #f8fafc; padding: 8px 15px; border-radius: 50px; border: 1px solid var(--border); flex-wrap:wrap;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <label style="font-size:10px; font-weight:800; color:var(--text-muted);">CLIENT</label>
                                <input type="text" name="client_name" value="<?php echo htmlspecialchars($_GET['client_name'] ?? ''); ?>"
                                    placeholder="Search Client..." style="margin:0; width:130px; padding:5px 8px; border-radius:8px; border:1px solid var(--border); background:#ffffff; font-size:12px;">
                            </div>
                            <div style="display:flex; align-items:center; gap:8px; border-left: 1px solid var(--border); padding-left:10px;">
                                <label style="font-size:10px; font-weight:800; color:var(--text-muted);">FROM</label>
                                <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>" style="margin:0; width:135px; padding:5px 8px; border-radius:8px; border:1px solid var(--border); background:#ffffff; font-size:12px;">
                            </div>

                            <div style="display:flex; align-items:center; gap:8px; border-left: 1px solid var(--border); padding-left:10px;">
                                <label style="font-size:10px; font-weight:800; color:var(--text-muted);">TO</label>
                                <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>" style="margin:0; width:135px; padding:5px 8px; border-radius:8px; border:1px solid var(--border); background:#ffffff; font-size:12px;">
                            </div>

                            <button type="submit" class="btn-main" style="width:auto; padding:6px 14px; margin:0; font-size:11.5px; border-radius:20px;">
                                <i class="fas fa-filter"></i>
                            </button>

                            <?php if ($from_date || $to_date || !empty($_GET['client_name'])): ?>
                                <a href="quotations.php" style="font-size:18px; color:#ef4444; text-decoration:none;" title="Reset Filter">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th style="padding:14px 24px;">Date</th>
                                    <th>Quotation #</th>
                                    <th>Client &amp; Service</th>
                                    <th>Tax Type</th>
                                    <th style="text-align:right;">Grand Total</th>
                                    <th style="text-align:center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Capture the new filter
                                $client_search = isset($_GET['client_name']) ? mysqli_real_escape_string($conn, $_GET['client_name']) : '';

                                // Start building the query
                                $query = "SELECT q.*, cp.company_name 
                                          FROM quotations q 
                                          JOIN client_profiles cp ON q.client_id = cp.client_id";

                                $where_clauses = [];

                                // Add Date Filters
                                if ($from_date && $to_date) {
                                    $where_clauses[] = "DATE(q.created_at) BETWEEN '$from_date' AND '$to_date'";
                                } elseif ($from_date) {
                                    $where_clauses[] = "DATE(q.created_at) >= '$from_date'";
                                } elseif ($to_date) {
                                    $where_clauses[] = "DATE(q.created_at) <= '$to_date'";
                                }

                                // Add Client Name Filter
                                if ($client_search) {
                                    $where_clauses[] = "cp.company_name LIKE '%$client_search%'";
                                }

                                // Append WHERE clauses if any exist
                                if (count($where_clauses) > 0) {
                                    $query .= " WHERE " . implode(" AND ", $where_clauses);
                                }

                                $query .= " ORDER BY q.id DESC";
                                $res = $conn->query($query);

                                if ($res && $res->num_rows > 0) {
                                    while ($q = $res->fetch_assoc()) {
                                ?>
                                        <tr>
                                            <td style="padding:15px 24px;">
                                                <span style="font-weight:600; color:var(--text-main);"><?php echo date('d M, Y', strtotime($q['created_at'])); ?></span><br>
                                            </td>
                                            <td>
                                                <span style="background:#f1f5f9; border:1px solid var(--border); padding:4px 8px; border-radius:6px; font-family:monospace; font-weight:700; color:var(--navy);">
                                                    <?php echo htmlspecialchars($q['quote_no']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <b style="color:var(--text-main); text-transform: uppercase;">
                                                    <?php echo htmlspecialchars($q['company_name']); ?>
                                                </b><br>
                                                <span style="font-size:12px; color:var(--text-muted);"><?php echo htmlspecialchars($q['service_name']); ?></span>
                                            </td>
                                            <td>
                                                <span style="font-size:10.5px; background:<?php echo ($q['tax_type'] == 'CGST+SGST' ? '#dcfce7' : '#dbeafe'); ?>; color:<?php echo ($q['tax_type'] == 'CGST+SGST' ? '#166534' : '#1e40af'); ?>; padding:3px 9px; border-radius:12px; font-weight:700;">
                                                    <?php echo htmlspecialchars($q['tax_type']); ?>
                                                </span>
                                            </td>
                                            <td style="text-align:right; font-weight:800; color:var(--navy); font-size:14.5px;">
                                                &#8377;<?php echo number_format($q['total_amount'], 2); ?>
                                            </td>
                                            <td style="text-align:center;">
                                                <div style="display:flex; justify-content:center; gap:6px;">
                                                    <a href="view-quotation.php?id=<?php echo urlencode($q['id']); ?>" class="btn-sm btn-pdf" target="_blank" title="View PDF"><i class="fas fa-file-pdf"></i></a>
                                                    <a href="edit-quotation.php?id=<?php echo urlencode($q['id']); ?>" class="btn-sm" style="background:#fef9c3; color:#854d0e; border:1px solid #fde047;" title="Edit"><i class="fas fa-edit"></i></a>
                                                    <a href="convert-to-invoice.php?quote_id=<?php echo urlencode($q['id']); ?>" class="btn-sm btn-convert" onclick="return confirm('Convert to Invoice?')" title="Convert"><i class="fas fa-exchange-alt"></i></a>
                                                    <a href="?delete_id=<?php echo urlencode($q['id']); ?>" class="btn-sm" style="background:#fee2e2; color:#b91c1c;" onclick="return confirm('Delete?')" title="Delete"><i class="fas fa-trash"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='6' style='text-align:center; padding:50px; color:var(--text-muted);'>
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
    </div>

    <script>
        // Live Clock
        function updateClock() {
            const now = new Date();
            const dateEl = document.getElementById('liveDate');
            const timeEl = document.getElementById('liveTime');
            if (dateEl) {
                dateEl.textContent = now.toLocaleDateString('en-IN', {
                    weekday: 'short',
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
            }
            if (timeEl) {
                timeEl.textContent = now.toLocaleTimeString('en-IN', { hour12: true });
            }
        }
        updateClock();
        setInterval(updateClock, 1000);

        function toggleMenu(menuId, chevronId) {
            const menu = document.getElementById(menuId);
            const chevron = document.getElementById(chevronId);

            // Toggle the specific menu clicked
            menu.classList.toggle('show-menu');

            // Rotate the specific arrow clicked
            if (chevron) chevron.classList.toggle('rotate-chevron');

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
                <td><button type="button" onclick="this.parentElement.parentElement.remove()" style="border:none; background:none; color:#ef4444; cursor:pointer; font-size:15px;"><i class="fas fa-times"></i></button></td>
            `;
            container.appendChild(row);
        }
    </script>
</body>

</html>