<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

// 1. LOGIC: SAVE / UPDATE
if (isset($_POST['save_dsc'])) {
    $client = mysqli_real_escape_string($conn, $_POST['client_name']);
    $dsc = mysqli_real_escape_string($conn, $_POST['dsc_no']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone_no']);
    $expiry = $_POST['expiry_date'];
    $color = mysqli_real_escape_string($conn, $_POST['color']);
    $box_no = mysqli_real_escape_string($conn, $_POST['box_no']);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    $id = $_POST['dsc_id'];

    if (!empty($id)) {
        $sql = "UPDATE dsc_register SET client_name='$client', dsc_no='$dsc', phone_no='$phone', expiry_date='$expiry', color='$color', box_no='$box_no', remarks='$remarks' WHERE id=" . intval($id);
    } else {
        $sql = "INSERT INTO dsc_register (client_name, dsc_no, phone_no, expiry_date, color, box_no, status, remarks) VALUES ('$client', '$dsc', '$phone', '$expiry', '$color', '$box_no', 'In', '$remarks')";
    }
    $conn->query($sql);
    header("Location: dsc-register.php");
    exit();
}

// 2. LOGIC: DELETE ENTRY
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM dsc_register WHERE id = $id");
    header("Location: dsc-register.php");
    exit();
}

// 3. LOGIC: STATUS TOGGLE
if (isset($_POST['toggle_status'])) {
    $id = intval($_POST['dsc_id']);
    $current = $_POST['current_status'];
    $new_status = ($current == 'In') ? 'Out' : 'In';
    $conn->query("UPDATE dsc_register SET status = '$new_status' WHERE id = $id");
    header("Location: dsc-register.php");
    exit();
}

// 4. METRICS CALCULATION
$total_res = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN expiry_date < CURDATE() THEN 1 ELSE 0 END) as expired,
    SUM(CASE WHEN expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring,
    SUM(CASE WHEN expiry_date > DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as active
    FROM dsc_register")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DSC Inventory | KKA Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

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
            --success-bg: #ecfdf5;
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
            width: calc(100% - 280px);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .admin-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            padding: 14px 40px;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 90;
            box-shadow: var(--shadow-sm);
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
            gap: 16px;
        }

        .header-clock {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            padding: 8px 16px;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .clock-icon i {
            color: var(--orange);
            font-size: 16px;
        }

        .clock-display {
            display: flex;
            flex-direction: column;
        }

        .clock-display span {
            font-size: 9.5px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
        }

        .clock-display strong {
            font-size: 12.5px;
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
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--danger-bg);
            color: var(--danger);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 13px;
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
        }

        /* ================= METRICS CARDS GRID ================= */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 26px;
        }

        .metric-card {
            background: var(--card-bg);
            padding: 22px 24px;
            border-radius: 18px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }

        .metric-card.card-expired::before { background: var(--danger); }
        .metric-card.card-expiring::before { background: var(--orange); }
        .metric-card.card-active::before { background: var(--success); }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: #cbd5e1;
        }

        .metric-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .metric-card span {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 700;
        }

        .metric-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }

        .card-expired .metric-icon-box { background: var(--danger-bg); color: var(--danger); }
        .card-expiring .metric-icon-box { background: var(--orange-glow); color: var(--orange); }
        .card-active .metric-icon-box { background: var(--success-bg); color: var(--success); }

        .metric-card h2 {
            margin: 0;
            font-size: 28px;
            color: var(--text-main);
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        /* ================= SEARCH & ACTION BAR ================= */
        .toolbar-card {
            background: var(--card-bg);
            padding: 16px 20px;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .search-wrapper {
            position: relative;
            flex-grow: 1;
        }

        .search-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 14px;
        }

        .search-input {
            width: 100%;
            padding: 11px 16px 11px 42px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            background: #f8fafc;
            font-family: inherit;
            font-size: 13.5px;
            outline: none;
            transition: all 0.2s;
        }

        .search-input:focus {
            border-color: var(--navy);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(11, 60, 116, 0.1);
        }

        .btn-export {
            background: var(--success-bg);
            color: #047857;
            border: 1.5px solid #a7f3d0;
            padding: 11px 18px;
            border-radius: 10px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-export:hover {
            background: var(--success);
            color: white;
            border-color: var(--success);
        }

        .btn-add-primary {
            background: var(--navy);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(11, 60, 116, 0.2);
            white-space: nowrap;
        }

        .btn-add-primary:hover {
            background: var(--orange);
            transform: translateY(-1px);
        }

        /* ================= TABLE CARD ================= */
        .card-table {
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px 18px;
            background: #f8fafc;
            color: var(--navy);
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            font-weight: 700;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 16px 18px;
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

        .dsc-code {
            background: #f1f5f9;
            color: var(--navy);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 600;
        }

        .box-badge {
            background: #f1f5f9;
            color: var(--text-main);
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            border: none;
            font-family: inherit;
            font-weight: 700;
            font-size: 10.5px;
            letter-spacing: 0.4px;
            cursor: pointer;
            color: white;
            transition: transform 0.15s;
        }

        .status-badge:hover {
            transform: scale(1.04);
        }

        .action-icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s;
        }

        .edit-btn {
            background: rgba(11, 60, 116, 0.08);
            color: var(--navy);
        }

        .edit-btn:hover {
            background: var(--navy);
            color: white;
        }

        .delete-btn {
            background: var(--danger-bg);
            color: var(--danger);
            margin-left: 6px;
        }

        .delete-btn:hover {
            background: var(--danger);
            color: white;
        }

        /* ================= MODAL & FORM ================= */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: var(--card-bg);
            width: 100%;
            max-width: 620px;
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            animation: modalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes modalPop {
            0% { transform: scale(0.95); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .modal-header h3 {
            margin: 0;
            font-size: 17px;
            color: var(--navy);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close-modal-btn {
            background: #f1f5f9;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .close-modal-btn:hover {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group label {
            display: block;
            font-size: 11.5px;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modal-content input,
        .modal-content select,
        .modal-content textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 13.5px;
            outline: none;
            background: #f8fafc;
            font-family: inherit;
            transition: all 0.2s;
        }

        .modal-content input:focus,
        .modal-content select:focus,
        .modal-content textarea:focus {
            border-color: var(--navy);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(11, 60, 116, 0.1);
        }

        .modal-content textarea {
            grid-column: span 2;
            height: 80px;
            resize: vertical;
        }

        .btn-save {
            background: var(--navy);
            color: white;
            padding: 13px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            width: 100%;
            font-size: 14px;
            margin-top: 18px;
            box-shadow: 0 4px 12px rgba(11, 60, 116, 0.2);
            transition: all 0.2s;
        }

        .btn-save:hover {
            background: var(--navy-light);
            transform: translateY(-1px);
        }

        @media (max-width: 1100px) {
            .metrics-grid {
                grid-template-columns: 1fr;
            }
            .toolbar-card {
                flex-wrap: wrap;
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
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('billingMenu', 'billChev')">
                <i class="fas fa-file-invoice-dollar"></i> Billing
                <i class="fas fa-chevron-down" id="billChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="billingMenu">
                <a href="quotations.php">Quotations</a>
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
            <a href="javascript:void(0)" class="dropdown-btn active" onclick="toggleMenu('reportsMenu', 'repChev')">
                <i class="fas fa-file-contract"></i> Reports
                <i class="fas fa-chevron-down rotate-chevron" id="repChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content show-menu" id="reportsMenu">
                <a href="dsc-register.php" class="active">DSC Register</a>
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
                <h1>DSC Inventory Register</h1>
                <div class="welcome-text">Track Digital Signature Certificates, physical storage boxes, and expiry alerts</div>
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
            <!-- Metrics Grid -->
            <div class="metrics-grid">
                <div class="metric-card card-expired">
                    <div class="metric-header">
                        <span>Expired DSCs</span>
                        <div class="metric-icon-box"><i class="fas fa-exclamation-circle"></i></div>
                    </div>
                    <h2><?php echo $total_res['expired'] ?? 0; ?></h2>
                </div>

                <div class="metric-card card-expiring">
                    <div class="metric-header">
                        <span>Expiring (30 Days)</span>
                        <div class="metric-icon-box"><i class="fas fa-hourglass-half"></i></div>
                    </div>
                    <h2><?php echo $total_res['expiring'] ?? 0; ?></h2>
                </div>

                <div class="metric-card card-active">
                    <div class="metric-header">
                        <span>Active DSCs</span>
                        <div class="metric-icon-box"><i class="fas fa-check-circle"></i></div>
                    </div>
                    <h2><?php echo $total_res['active'] ?? 0; ?></h2>
                </div>
            </div>

            <!-- Search & Actions Toolbar -->
            <div class="toolbar-card">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="tableSearch" class="search-input" placeholder="Search Client, DSC No, or Box Number...">
                </div>
                <button onclick="exportToExcel()" class="btn-export">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
                <button class="btn-add-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i> Add New Entry
                </button>
            </div>

            <!-- Inventory Table -->
            <div class="card-table">
                <table id="dscTable">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>Color</th>
                            <th>DSC Number</th>
                            <th>Box No</th>
                            <th>Expiry</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $res = $conn->query("SELECT * FROM dsc_register ORDER BY id DESC");
                        if ($res && $res->num_rows > 0):
                            while ($row = $res->fetch_assoc()):
                                $expiry_ts = strtotime($row['expiry_date']);
                                $diff = ($expiry_ts - time()) / (60 * 60 * 24);
                                $date_color = ($diff < 0) ? 'var(--danger)' : (($diff <= 30) ? 'var(--orange)' : 'inherit');
                        ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['client_name']); ?></strong></td>
                                    <td>
                                        <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:<?php echo htmlspecialchars($row['color']); ?>; margin-right:6px;"></span>
                                        <?php echo ucfirst(htmlspecialchars($row['color'])); ?>
                                    </td>
                                    <td><code class="dsc-code"><?php echo htmlspecialchars($row['dsc_no']); ?></code></td>
                                    <td><span class="box-badge"><?php echo htmlspecialchars($row['box_no']); ?></span></td>
                                    <td style="color:<?php echo $date_color; ?>; font-weight:700;"><?php echo date('d-m-Y', $expiry_ts); ?></td>
                                    <td>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="dsc_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($row['status']); ?>">
                                            <button type="submit" name="toggle_status" class="status-badge" style="background:<?php echo ($row['status'] == 'In') ? 'var(--success)' : 'var(--danger)'; ?>;">
                                                <?php echo ($row['status'] == 'In') ? 'IN OFFICE' : 'WITH CLIENT'; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <a href="javascript:void(0)" onclick='editDSC(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)' class="action-icon-btn edit-btn" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="action-icon-btn delete-btn" title="Delete" onclick="return confirm('Permanently delete this record?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:24px; color:var(--text-muted);">No DSC records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit DSC Modal -->
    <div class="modal-overlay" id="dscModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-key" style="color:var(--orange);"></i> <span id="modalTitle">New DSC Registration</span></h3>
                <button type="button" class="close-modal-btn" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" id="dscForm">
                <input type="hidden" name="dsc_id" id="dsc_id">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Client Full Name</label>
                        <input type="text" name="client_name" id="client_name" required>
                    </div>
                    <div class="form-group">
                        <label>DSC Serial Number</label>
                        <input type="text" name="dsc_no" id="dsc_no" required>
                    </div>
                    <div class="form-group">
                        <label>Folder/Tag Color</label>
                        <select name="color" id="color">
                            <option value="red">Red</option>
                            <option value="blue">Blue</option>
                            <option value="green">Green</option>
                            <option value="yellow">Yellow</option>
                            <option value="black">Black</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Box Number</label>
                        <input type="text" name="box_no" id="box_no" placeholder="e.g. B-101">
                    </div>
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="date" name="expiry_date" id="expiry_date" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone_no" id="phone_no">
                    </div>
                    <textarea name="remarks" id="remarks" placeholder="Internal notes or special instructions..."></textarea>
                </div>
                <button type="submit" name="save_dsc" class="btn-save">Confirm &amp; Save Information</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('dscModal');

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

        // 1. EXPORT TO EXCEL LOGIC
        function exportToExcel() {
            const table = document.getElementById("dscTable");
            const wb = XLSX.utils.table_to_book(table, {
                sheet: "DSC Inventory"
            });
            XLSX.writeFile(wb, "DSC_Inventory_Report.xlsx");
        }

        // 2. LIVE SEARCH LOGIC
        document.getElementById('tableSearch').addEventListener('keyup', function () {
            let filter = this.value.toLowerCase();
            document.querySelectorAll('#dscTable tbody tr').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
            });
        });

        function openModal() {
            document.getElementById('modalTitle').innerText = "New DSC Registration";
            document.getElementById('dsc_id').value = "";
            document.getElementById('dscForm').reset();
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        function editDSC(data) {
            document.getElementById('modalTitle').innerText = "Update DSC Entry";
            document.getElementById('dsc_id').value = data.id;
            document.getElementById('client_name').value = data.client_name;
            document.getElementById('dsc_no').value = data.dsc_no;
            document.getElementById('expiry_date').value = data.expiry_date;
            document.getElementById('color').value = data.color;
            document.getElementById('box_no').value = data.box_no;
            document.getElementById('phone_no').value = data.phone_no;
            document.getElementById('remarks').value = data.remarks;
            modal.style.display = 'flex';
        }

        function toggleMenu(menuId, chevronId) {
            const menu = document.getElementById(menuId);
            const chevron = document.getElementById(chevronId);

            menu.classList.toggle('show-menu');
            chevron.classList.toggle('rotate-chevron');

            const allMenus = document.querySelectorAll('.dropdown-content');
            const allChevrons = document.querySelectorAll('.fa-chevron-down');

            allMenus.forEach((m) => {
                if (m.id !== menuId) m.classList.remove('show-menu');
            });

            allChevrons.forEach((c) => {
                if (c.id !== chevronId) c.classList.remove('rotate-chevron');
            });
        }
    </script>
</body>

</html>