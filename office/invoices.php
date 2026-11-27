<?php
session_start();
require_once __DIR__ . '/../db.php';
global $conn;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FILTER LOGIC ---
$from_date = isset($_GET['from_date']) ? mysqli_real_escape_string($conn, $_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? mysqli_real_escape_string($conn, $_GET['to_date']) : '';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

$adminName = $_SESSION['user']['name'] ?? 'Admin Executive';
$lookupId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
$lookupIdent = trim((string)($_SESSION['user']['identifier'] ?? ($_SESSION['user']['email'] ?? '')));

if (isset($conn) && $conn instanceof mysqli) {
    if ($lookupId > 0) {
        $stmtAdmin = $conn->prepare("SELECT `name` FROM `users` WHERE `id` = ? LIMIT 1");
        if ($stmtAdmin) {
            $stmtAdmin->bind_param("i", $lookupId);
            $stmtAdmin->execute();
            $resAdmin = $stmtAdmin->get_result();
            $rowAdmin = $resAdmin->fetch_assoc();
            if ($rowAdmin && !empty($rowAdmin['name'])) {
                $adminName = $rowAdmin['name'];
            }
            $stmtAdmin->close();
        }
    } elseif ($lookupIdent !== '') {
        $stmtAdmin = $conn->prepare("SELECT `name` FROM `users` WHERE `identifier` = ? LIMIT 1");
        if ($stmtAdmin) {
            $stmtAdmin->bind_param("s", $lookupIdent);
            $stmtAdmin->execute();
            $resAdmin = $stmtAdmin->get_result();
            $rowAdmin = $resAdmin->fetch_assoc();
            if ($rowAdmin && !empty($rowAdmin['name'])) {
                $adminName = $rowAdmin['name'];
            }
            $stmtAdmin->close();
        }
    }
}
$initials = strtoupper(substr(trim((string)$adminName), 0, 1) ?: 'A');

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

    // Use the year from $inv_date
    $inv_year = date('Y', strtotime($inv_date));

    // Safely find the highest existing invoice number for this specific year
    $max_res = $conn->query("SELECT invoice_no FROM invoices WHERE invoice_no LIKE 'INV-$inv_year-%' ORDER BY id DESC LIMIT 1");
    if ($max_row = $max_res->fetch_assoc()) {
        $last_num = (int) substr($max_row['invoice_no'], -3);
        $next_id = $last_num + 1;
    } else {
        $next_id = 1;
    }

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

        if (!empty($qid)) {
            $conn->query("UPDATE quotations SET status = 'Accepted' WHERE id = '$qid'");
        }
        $status_msg = "<div class='alert-success'> Success: Invoice $inv_no Created with multiple items!</div>";
    }
}

// 3. HANDLE PAYMENT (Updated for Custom Payment Date)
if (isset($_POST['submit_payment'])) {
    $inv_no = mysqli_real_escape_string($conn, $_POST['inv_no']);
    $pay_amt = (float)$_POST['pay_amount'];
    $pay_method = mysqli_real_escape_string($conn, $_POST['pay_method']);
    $payment_date = mysqli_real_escape_string($conn, $_POST['payment_date']); // Custom Date from modal
    $new_remarks = mysqli_real_escape_string($conn, $_POST['remarks']);

    $res = $conn->query("SELECT amount, cgst_amount, sgst_amount, igst_amount, paid_amount, client_id, remarks FROM invoices WHERE invoice_no = '$inv_no'");
    $inv = $res->fetch_assoc();

    if ($inv) {
        $total_bill = (float)$inv['amount'] + (float)$inv['cgst_amount'] + (float)$inv['sgst_amount'] + (float)$inv['igst_amount'];
        $already_paid = round((float)($inv['paid_amount'] ?? 0), 2);
        $new_total_paid = $already_paid + $pay_amt;
        $status = ($new_total_paid >= round($total_bill, 2)) ? 'Paid' : 'Partially Paid';
        
        // Format the custom selected date for remarks log
        $formatted_date = date('d-m-Y', strtotime($payment_date));
        $updated_remarks = ($inv['remarks'] ?? "") . "\n[$formatted_date]: $new_remarks ($pay_method)";

        // Update invoices table with paid amount and status
        $conn->query("UPDATE invoices SET paid_amount = '$new_total_paid', status = '$status', remarks = '$updated_remarks' WHERE invoice_no = '$inv_no'");

        $rcp_res = $conn->query("SELECT id FROM receipts");
        $rcp_no = "RCP-" . date('Y', strtotime($payment_date)) . "-" . str_pad(($rcp_res->num_rows + 1), 3, "0", STR_PAD_LEFT);
        
        // Insert into receipts table including the custom payment date
        $conn->query("INSERT INTO receipts (receipt_no, invoice_no, client_id, amount_paid, payment_mode, method, payment_date) VALUES ('$rcp_no', '$inv_no', '{$inv['client_id']}', '$pay_amt', '$status', '$pay_method', '$payment_date')");

        $status_msg = "<div class='alert-success'>✅ Payment Recorded for $formatted_date!</div>";
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices | KKA Admin</title>
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
            --shadow-lg: 0 20px 30px -10px rgba(11, 60, 116, 0.12);
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

        /* Vertical stack layout */
        .grid {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            display: block;
            box-sizing: border-box;
        }

        #invoiceForm {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        #itemsContainer,
        .add-item-btn,
        .btn-main,
        hr,
        h3 {
            grid-column: span 2;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px 12px;
            margin: 5px 0 10px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            background: #f8fafc;
            box-sizing: border-box;
            font-family: inherit;
            font-size: 13.5px;
            color: var(--text-main);
            outline: none;
            transition: all 0.2s;
        }

        input:focus,
        select:focus,
        textarea:focus {
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
        }

        .btn-main:hover {
            background: var(--orange);
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

        .tax-tag {
            font-size: 10px;
            background: #e2e8f0;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 700;
        }

        .status-paid {
            background: #dcfce7;
            color: #166534;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
        }

        .status-unpaid,
        .status-pending,
        .status-partiallypaid {
            background: #fee2e2;
            color: #ef4444;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(4px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--card-bg);
            padding: 28px;
            border-radius: 20px;
            width: 92%;
            max-width: 450px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
        }

        /* Multi-item Styles */
        .item-row {
            border: 1px solid var(--border);
            padding: 16px;
            border-radius: 14px;
            margin-bottom: 12px;
            position: relative;
            background: #f8fafc;
        }

        .remove-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            color: var(--danger);
            cursor: pointer;
        }

        .add-item-btn {
            background: #f1f5f9;
            color: var(--navy);
            border: 1px solid var(--border);
            padding: 10px;
            border-radius: 10px;
            width: 100%;
            cursor: pointer;
            margin-bottom: 12px;
            font-family: inherit;
            font-weight: 700;
            font-size: 13px;
            transition: all 0.2s;
        }

        .add-item-btn:hover {
            background: #e2e8f0;
        }

        #reportTable tr:hover {
            background-color: #fff7ed;
        }

        #reportSearch {
            border: 1.5px solid var(--orange) !important;
            border-radius: 10px;
            padding: 9px 12px;
        }

        .tab-btn {
            padding: 10px;
            flex: 1;
            border: none;
            background: none;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            font-size: 12.5px;
            color: var(--text-muted);
            transition: all 0.2s;
        }

        .tab-btn:hover {
            border-bottom: 2px solid var(--orange);
            color: var(--navy);
        }

        #reportTable td {
            padding: 9px 4px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
        }

        @media (max-width: 1024px) {
            .admin-top-bar {
                padding: 14px 20px;
            }
            .content-body {
                padding: 24px 20px;
            }
            #invoiceForm {
                grid-template-columns: 1fr;
            }
            #itemsContainer,
            .add-item-btn,
            .btn-main,
            hr,
            h3 {
                grid-column: span 1;
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
    <div class="sidebar-avatar"><?php echo htmlspecialchars($initials); ?></div>
    <div class="online-status"></div>
</div>
<div class="profile-info">
    <strong><?php echo htmlspecialchars($adminName); ?></strong>
    <span>System Administrator</span>
</div>
        </div>

        <div class="sidebar-nav-title">Main Menu</div>
        <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="admin-profile.php"><i class="fas fa-user-cog"></i> Admin Profile</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn active" onclick="toggleMenu('billingMenu', 'billChev')">
                <i class="fas fa-file-invoice-dollar"></i> Billing
                <i class="fas fa-chevron-down rotate-chevron" id="billChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content show-menu" id="billingMenu">
                <a href="quotations.php">Quotations</a>
                <a href="invoices.php" class="active">Invoices</a>
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
                <h1>Invoice Center</h1>
                <div class="welcome-text">Generate client bills, track payment balances, and review financial summaries</div>
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
    <span class="header-user-name"><?php echo htmlspecialchars($adminName); ?></span>
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
                    <h3 style="margin:0 0 8px 0; color:var(--navy); font-size:17px; font-weight:800;"><i class="fas fa-plus-circle" style="color:var(--orange);"></i> Create New Bill</h3>
                    <form method="POST" id="invoiceForm">
                        <div>
                            <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Select Client</label>
                            <select name="client_id" required>
                                <option value="">-- Choose Client (Name + ID) --</option>
                                <?php
                                $client_query = "SELECT client_id, company_name FROM client_profiles ORDER BY company_name ASC";
                                $client_result = $conn->query($client_query);

                                if ($client_result && $client_result->num_rows > 0) {
                                    while ($row = $client_result->fetch_assoc()) {
                                        $selected = ($auto_cid == $row['client_id']) ? 'selected' : '';
                                        $displayName = htmlspecialchars($row['company_name']) . " [" . htmlspecialchars($row['client_id']) . "]";
                                        echo "<option value='" . htmlspecialchars($row['client_id'], ENT_QUOTES) . "' $selected>{$displayName}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div>
                            <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Place of Supply (For GST)</label>
                            <select name="client_state" required>
                                <option value="Bihar">Bihar (Intrastate - CGST/SGST)</option>
                                <option value="Outside">Outside Bihar (Interstate - IGST)</option>
                            </select>
                        </div>

                        <hr style="border:0; border-top:1px solid var(--border); margin:6px 0;">

                        <div id="itemsContainer">
                            <div class="item-row">
                                <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Service</label>
                                <input type="text" name="services[]" value="<?php echo htmlspecialchars($auto_svc); ?>" placeholder="Service Name" required>

                                <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Description</label>
                                <textarea name="descriptions[]" placeholder="Description..." rows="1"></textarea>

                                <div style="display: flex; gap: 12px;">
                                    <div style="flex: 1;">
                                        <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Amount</label>
                                        <input type="number" step="0.01" name="amounts[]" value="<?php echo htmlspecialchars($auto_amt); ?>" placeholder="0.00" required>
                                    </div>
                                    <div style="flex: 1;">
                                        <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Tax %</label>
                                        <input type="number" step="0.01" name="tax_rates[]" value="<?php echo htmlspecialchars($auto_tax); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="add-item-btn" onclick="addItem()"><i class="fas fa-plus"></i> Add Another Service</button>

                        <div style="grid-column: span 2; display: flex; gap: 15px; margin-bottom: 6px; flex-wrap:wrap;">
                            <div style="flex: 1; min-width:180px;">
                                <label style="font-weight: 700; font-size: 11.5px; color:var(--text-muted); text-transform:uppercase;">Invoice Date</label>
                                <input type="date" name="invoice_date" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div style="flex: 1; min-width:180px;">
                                <label style="font-weight: 700; font-size: 11.5px; color:var(--text-muted); text-transform:uppercase;">Due Date</label>
                                <input type="date" name="due_date" required value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                            </div>
                        </div>

                        <button type="submit" name="gen_invoice" class="btn-main">Generate Invoice</button>
                    </form>
                </div>

                <div class="card" style="padding:0; overflow:hidden;">
                    <div style="padding:20px 25px; border-bottom: 1px solid var(--border); display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 15px;">
                        <h3 style="margin:0; color:var(--navy); font-size:16px; font-weight:800;"><i class="fas fa-history" style="color:var(--orange);"></i> Invoice History</h3>

                        <button type="button"
                            onclick="document.getElementById('reportModal').style.display='flex'; loadData('daily');"
                            style="padding: 9px 15px;
                                   font-size: 12px;
                                   font-weight: 700;
                                   border-radius: 10px;
                                   background: var(--navy);
                                   color: white;
                                   border: none;
                                   cursor: pointer;
                                   display: inline-flex;
                                   align-items: center;
                                   gap: 6px;
                                   flex-shrink: 0;
                                   width: fit-content;
                                   box-shadow: var(--shadow-sm);">
                            <i class="fas fa-chart-line"></i> Summary
                        </button>

                        <form method="GET" style="display:flex; align-items:center; gap:10px; background: #f8fafc; padding: 8px 15px; border-radius: 50px; border: 1px solid var(--border); flex-wrap:wrap;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <input type="text" name="company_search" placeholder="Search Company..."
                                    value="<?php echo htmlspecialchars($_GET['company_search'] ?? ''); ?>"
                                    style="margin:0; width:125px; padding:5px 8px; border-radius:8px; border:1px solid var(--border); background:#ffffff; font-size:11.5px;">
                            </div>

                            <div style="display:flex; align-items:center; gap:8px; border-left: 1px solid var(--border); padding-left:10px;">
                                <label style="font-size:10px; font-weight:800; color:var(--text-muted);">FROM</label>
                                <input type="date" name="from_date" value="<?php echo htmlspecialchars($_GET['from_date'] ?? ''); ?>"
                                    style="margin:0; width:125px; padding:5px 8px; border-radius:8px; border:1px solid var(--border); background:#ffffff; font-size:11.5px;">
                            </div>

                            <div style="display:flex; align-items:center; gap:8px; border-left: 1px solid var(--border); padding-left:10px;">
                                <label style="font-size:10px; font-weight:800; color:var(--text-muted);">TO</label>
                                <input type="date" name="to_date" value="<?php echo htmlspecialchars($_GET['to_date'] ?? ''); ?>"
                                    style="margin:0; width:125px; padding:5px 8px; border-radius:8px; border:1px solid var(--border); background:#ffffff; font-size:11.5px;">
                            </div>

                            <button type="submit" class="btn-main" style="width:auto; padding:6px 14px; margin:0; font-size:11.5px; border-radius:20px;">
                                <i class="fas fa-search"></i>
                            </button>

                            <?php if (!empty($_GET['from_date']) || !empty($_GET['to_date']) || !empty($_GET['company_search'])): ?>
                                <a href="invoices.php" style="font-size:18px; color:#ef4444; text-decoration:none;" title="Reset Filter">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div style="overflow-x: auto;">
                        <table style="width:100%; border-collapse:collapse;">
                            <colgroup>
                                <col style="width: 14%;">
                                <col style="width: 14%;">
                                <col style="width: 36%;">
                                <col style="width: 18%;">
                                <col style="width: 18%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th style="padding:14px 24px;">Inv Date</th>
                                    <th>Invoice #</th>
                                    <th>Client &amp; Description</th>
                                    <th>Financials</th>
                                    <th>Status &amp; Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // 1. BUILD FILTERED QUERY
                                $query = "SELECT i.*, cp.company_name 
                                          FROM invoices i 
                                          JOIN client_profiles cp ON i.client_id = cp.client_id";

                                $conditions = [];

                                // Company Search
                                if (!empty($_GET['company_search'])) {
                                    $search = mysqli_real_escape_string($conn, $_GET['company_search']);
                                    $conditions[] = "cp.company_name LIKE '%$search%'";
                                }

                                // From Date
                                if (!empty($_GET['from_date'])) {
                                    $from = mysqli_real_escape_string($conn, $_GET['from_date']);
                                    $conditions[] = "i.invoice_date >= '$from'";
                                }

                                // To Date
                                if (!empty($_GET['to_date'])) {
                                    $to = mysqli_real_escape_string($conn, $_GET['to_date']);
                                    $conditions[] = "i.invoice_date <= '$to'";
                                }

                                // Apply conditions
                                if (count($conditions) > 0) {
                                    $query .= " WHERE " . implode(" AND ", $conditions);
                                }

                                // 2. SORTING
                                $query .= " ORDER BY i.invoice_date DESC, i.id DESC";
                                $invoices = $conn->query($query);
                                if ($invoices && $invoices->num_rows > 0) {
                                    while ($inv = $invoices->fetch_assoc()) {
                                        $base = (float)$inv['amount'];
                                        $gst = (float)$inv['cgst_amount'] + (float)$inv['sgst_amount'] + (float)$inv['igst_amount'];
                                        $grand_total = $base + $gst;
                                        $paid = (float)($inv['paid_amount'] ?? 0);
                                        $balance = $grand_total - $paid;
                                        $status_class = strtolower(str_replace(' ', '', $inv['status']));

                                        // Fetch the first description for this invoice to display in the table
                                        $inv_id_db = $inv['id'];
                                        $desc_res = $conn->query("SELECT description FROM invoice_items WHERE invoice_id = '$inv_id_db' LIMIT 1");
                                        $desc_row = ($desc_res && $desc_res->num_rows > 0) ? $desc_res->fetch_assoc() : null;
                                        $invoice_desc = $desc_row ? $desc_row['description'] : '';
                                ?>
                                        <tr>
                                            <td style="padding:15px 24px;">
                                                <span style="font-weight:600; color:var(--text-main);"><?php echo date('d-m-Y', strtotime($inv['invoice_date'])); ?></span>
                                                <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">
                                                    Due: <?php echo date('d-m-Y', strtotime($inv['due_date'])); ?>
                                                </div>
                                            </td>
                                            <td style='font-weight:700; color:var(--navy);'><?php echo htmlspecialchars($inv['invoice_no']); ?></td>
                                            <td>
                                                <b style="color:var(--text-main);"><?php echo htmlspecialchars($inv['company_name']); ?></b><br>
                                                <span style='font-size:12px; color:var(--text-muted);'><?php echo htmlspecialchars($invoice_desc); ?></span>
                                            </td>
                                            <td>
                                                <div>Total: <b>&#8377;<?php echo number_format($grand_total, 2); ?></b></div>
                                                <div style='font-size:11.5px; font-weight:600; color:#ef4444;'>Balance: &#8377;<?php echo number_format($balance, 2); ?></div>
                                            </td>
                                            <td>
                                                <span class='status-<?php echo htmlspecialchars($status_class); ?>'><?php echo htmlspecialchars($inv['status']); ?></span>
                                                <div style='margin-top:10px; display:flex; align-items:center; gap:10px;'>
                                                    <a href='view-invoice.php?inv_no=<?php echo urlencode($inv['invoice_no']); ?>' target='_blank' style='color:#e11d48;' title='PDF'><i class='fas fa-file-pdf'></i></a>
                                                    <a href='javascript:void(0)' onclick='openPayModal("<?php echo htmlspecialchars($inv['invoice_no'], ENT_QUOTES); ?>", "<?php echo htmlspecialchars($balance, ENT_QUOTES); ?>")' style='color:#16a34a;' title='Record Payment'><i class='fas fa-coins'></i></a>
                                                    <a href='edit-invoice.php?id=<?php echo urlencode($inv['id']); ?>' style='color:#2563eb;' title='Edit'><i class='fas fa-edit'></i></a>
                                                    <a href='invoices.php?delete_inv=<?php echo urlencode($inv['invoice_no']); ?>' onclick='return confirm("Are you sure you want to delete this invoice?")' style='color:#94a3b8;' title='Delete'><i class='fas fa-trash-alt'></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='5' style='text-align:center; padding:50px; color:var(--text-muted);'>
                                        <i class='fas fa-search' style='font-size:30px; opacity:0.35; margin-bottom:10px; display:block;'></i>
                                        No invoices found for this period.
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

    <div id="payModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-top:0; color:var(--navy); font-weight:800;">Record Payment</h3>
            <form method="POST">
                <input type="hidden" name="inv_no" id="modal_inv_no">

                <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Amount Paid</label>
                <input type="number" step="0.01" name="pay_amount" id="modal_pay_amt" required>

                <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Method</label>
                <select name="pay_method" required>
                    <option value="UPI">UPI</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>

                <!-- Date Paid Calendar Field -->
                <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Date Paid</label>
                <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>

                <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Remarks</label>
                <textarea name="remarks" placeholder="Payment details..." rows="3"></textarea>

                <button type="submit" name="submit_payment" class="btn-main">Save Payment</button>
                <button type="button" onclick="closeModal('payModal')" style="width:100%; border:none; background:none; cursor:pointer; margin-top:10px; color:var(--text-muted); font-family:inherit; font-weight:600;">Cancel</button>
            </form>
        </div>
    </div>

    <div id="reportModal" class="modal">
        <div style="background:var(--card-bg); padding:24px; border-radius:20px; width:92%; max-width:450px; height:500px; display:flex; flex-direction:column; box-shadow:var(--shadow-lg); border:1px solid var(--border);">
            <h3 style="margin:0 0 12px 0; color:var(--navy); font-weight:800;">Financial Report</h3>

            <input type="text" id="reportSearch" placeholder="Search date/month..." onkeyup="filterReport()"
                style="width:100%; margin-bottom:14px; box-sizing:border-box;">

            <div style="display:flex; margin-bottom:6px; border-bottom:2px solid var(--border);">
                <button type="button" class="tab-btn" onclick="loadData('daily')">Daily</button>
                <button type="button" class="tab-btn" onclick="loadData('weekly')">Weekly</button>
                <button type="button" class="tab-btn" onclick="loadData('monthly')">Monthly</button>
            </div>

            <div style="flex-grow:1; overflow-y:auto; padding-top:10px;">
                <table id="reportTable" style="width:100%; border-collapse:collapse;">
                    <tbody id="tabContent"></tbody>
                </table>
            </div>

            <button type="button" onclick="document.getElementById('reportModal').style.display='none'" style="margin-top:15px; padding:11px; width:100%; background:var(--navy); color:white; border:none; border-radius:10px; cursor:pointer; font-family:inherit; font-weight:700;">Close</button>
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
            const container = document.getElementById('itemsContainer');
            const newRow = document.createElement('div');
            newRow.className = 'item-row';
            newRow.innerHTML = `
                <i class="fas fa-times remove-btn" onclick="this.parentElement.remove()"></i>
                <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Service</label>
                <input type="text" name="services[]" placeholder="Service Name" required>
                <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Description</label>
                <textarea name="descriptions[]" placeholder="Description..." rows="1"></textarea>
                <div style="display: flex; gap: 12px;">
                    <div style="flex: 1;">
                        <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Amount</label>
                        <input type="number" step="0.01" name="amounts[]" placeholder="0.00" required>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size:11.5px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Tax %</label>
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
            if (event.target.classList && event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        function loadData(type) {
            document.getElementById('tabContent').innerHTML = "<tr><td style='padding:20px; text-align:center;'>Loading...</td></tr>";
            const xhr = new XMLHttpRequest();
            xhr.open("GET", "get-financial-data.php?type=" + encodeURIComponent(type), true);
            xhr.onload = function() {
                document.getElementById('tabContent').innerHTML = this.responseText;
            };
            xhr.send();
        }

        function filterReport() {
            let input = document.getElementById("reportSearch").value.toUpperCase();
            let rows = document.getElementById("reportTable").getElementsByTagName("tr");
            for (let i = 0; i < rows.length; i++) {
                rows[i].style.display = rows[i].innerText.toUpperCase().indexOf(input) > -1 ? "" : "none";
            }
        }
    </script>
</body>

</html>