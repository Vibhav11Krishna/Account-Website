<?php
session_start();
include('../db.php');
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'client') {
    header("Location: ../Register.php");
    exit();
}
$cid = $_SESSION['user']['identifier'];

// Logic to handle Business Profile Submission
if (isset($_POST['save_profile'])) {
    $comp = mysqli_real_escape_string($conn, $_POST['company_name']);
    $owner = mysqli_real_escape_string($conn, $_POST['owner_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']); 
    $email = mysqli_real_escape_string($conn, $_POST['business_email']); 
    $gst = mysqli_real_escape_string($conn, $_POST['gst_no']);
    $pan = mysqli_real_escape_string($conn, $_POST['pan_no']);
    $addr = mysqli_real_escape_string($conn, $_POST['address']);

    $check = $conn->query("SELECT id FROM client_profiles WHERE client_id='$cid'");
    if ($check->num_rows > 0) {
        $conn->query("UPDATE client_profiles SET company_name='$comp', owner_name='$owner', phone='$phone', business_email='$email', gst_no='$gst', pan_no='$pan', address='$addr' WHERE client_id='$cid'");
    } else {
        $conn->query("INSERT INTO client_profiles (client_id, company_name, owner_name, phone, business_email, gst_no, pan_no, address) VALUES ('$cid', '$comp', '$owner', '$phone', '$email', '$gst', '$pan', '$addr')");
    }
    header("Location: client-dashboard.php");
    exit();
}

// Fetch current profile
$profile = $conn->query("SELECT * FROM client_profiles WHERE client_id='$cid'")->fetch_assoc();

// Counts for primary stats
$activeRequestsCount = (int)$conn->query("SELECT id FROM service_requests WHERE client_id='$cid' AND status='Pending'")->num_rows;
$documentsCount = (int)$conn->query("SELECT id FROM client_documents WHERE client_id='$cid'")->num_rows;
$completedCount = (int)$conn->query("SELECT id FROM service_requests WHERE client_id='$cid' AND status='Completed'")->num_rows;

// Financial & quotation metrics for snapshot strip
$invTotalRow = $conn->query("SELECT COALESCE(SUM(amount + cgst_amount + sgst_amount + igst_amount), 0) AS total_billed, COALESCE(SUM(CASE WHEN LOWER(status)='paid' THEN (amount + cgst_amount + sgst_amount + igst_amount) ELSE 0 END), 0) AS total_paid FROM invoices WHERE client_id='$cid'")->fetch_assoc();
$totalBilled = (float)($invTotalRow['total_billed'] ?? 0);
$totalPaid = (float)($invTotalRow['total_paid'] ?? 0);
$pendingDues = max(0, $totalBilled - $totalPaid);
$acceptedQuotesCount = (int)$conn->query("SELECT id FROM quotations WHERE client_id='$cid' AND LOWER(status)='accepted'")->num_rows;

$clientName = $_SESSION['user']['name'] ?? 'Client';
$profileComplete = !empty($profile['company_name']) && !empty($profile['pan_no']) && !empty($profile['address']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | KKA Client</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --coffee-espresso: #1f110c;
            --coffee-roast: #3b2319;
            --coffee-mocha: #5c3a28;
            --coffee-latte: #8c5a3c;
            --coffee-caramel: #b87a4b;
            --coffee-cream: #f8f4f0;
            --coffee-foam: #fdfaf7;
            --coffee-sand: #efe7df;
            --card-bg: #ffffff;
            --text-main: #23140e;
            --text-muted: #6b5349;
            --border: #e6d8cf;
            --border-neutral: #eadfd8;
            --shadow-sm: 0 2px 10px rgba(59, 35, 25, 0.06);
            --shadow-md: 0 12px 28px -6px rgba(59, 35, 25, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            display: flex;
            margin: 0;
            min-height: 100vh;
            background: var(--coffee-cream);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
        }

        body.modal-open {
            overflow: hidden;
        }

        .dashboard-shell {
            display: flex;
            width: 100%;
            min-height: 100vh;
            transition: filter 0.25s ease;
        }

        body.modal-open .dashboard-shell {
            filter: blur(7px);
            pointer-events: none;
            user-select: none;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 285px;
            background: linear-gradient(180deg, var(--coffee-roast) 0%, var(--coffee-espresso) 100%);
            color: #ffffff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            border-right: 4px solid var(--coffee-caramel);
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 0 6px 16px 6px;
            margin-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .sidebar-brand-icons {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 6px 8px;
            border-radius: 11px;
            background: rgba(184, 122, 75, 0.22);
            border: 1px solid rgba(243, 213, 181, 0.35);
            color: #f3d5b5;
            font-size: 14px;
            flex-shrink: 0;
        }

        .sidebar h2 {
            font-size: 14.5px;
            font-weight: 800;
            color: #f3d5b5;
            margin: 0;
            line-height: 1.3;
        }

        /* Sidebar Profile Card */
        .sidebar-profile {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            padding: 16px 14px;
            margin-bottom: 20px;
            text-align: center;
        }

        .sidebar-avatar {
            width: 58px;
            height: 58px;
            margin: 0 auto 10px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--coffee-latte), var(--coffee-caramel));
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.28);
            border: 2px solid rgba(243, 213, 181, 0.55);
        }

        .sidebar-profile-name {
            margin: 0;
            font-size: 14.5px;
            font-weight: 800;
            color: #ffffff;
            word-break: break-word;
        }

        .sidebar-profile-id {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 10px;
            border-radius: 999px;
            background: rgba(184, 122, 75, 0.2);
            border: 1px solid rgba(243, 213, 181, 0.3);
            color: #f3d5b5;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        .nav-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.45);
            margin: 0 0 8px 10px;
        }

        .sidebar a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            margin-bottom: 6px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 600;
            transition: 0.22s ease;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: rgba(184, 122, 75, 0.22);
            color: #ffffff;
            border-left: 4px solid var(--coffee-caramel);
            padding-left: 17px;
        }

        .dropdown-content {
            display: none;
            background: rgba(0, 0, 0, 0.24);
            border-radius: 12px;
            margin: 0 4px 10px;
            padding: 4px 0;
        }

        .dropdown-content a {
            font-size: 13px;
            padding: 10px 14px;
            margin-bottom: 2px;
        }

        .logout-link {
            margin-top: auto;
            color: #f3d5b5 !important;
            background: rgba(0, 0, 0, 0.28) !important;
            border: 1px solid rgba(243, 213, 181, 0.22);
            justify-content: center;
            font-weight: 700 !important;
        }

        .logout-link:hover {
            background: var(--coffee-latte) !important;
            color: #ffffff !important;
            padding-left: 14px !important;
            border-left: 1px solid rgba(243, 213, 181, 0.4) !important;
        }

        .rotate-chevron {
            transform: rotate(180deg);
            transition: transform 0.25s ease;
        }

        /* Main Content Area */
        .main {
            margin-left: 285px;
            padding: 36px 44px;
            width: calc(100% - 285px);
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 26px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--border);
        }

        .page-header h1 {
            margin: 0;
            font-size: 27px;
            font-weight: 800;
            color: var(--coffee-espresso);
            letter-spacing: -0.4px;
        }

        .page-subtitle {
            margin: 4px 0 0;
            font-size: 13.5px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .date-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--coffee-roast);
            border: 1px solid var(--border);
            font-size: 12.5px;
            font-weight: 700;
            box-shadow: var(--shadow-sm);
        }

        .btn-outline-coffee {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 15px;
            border-radius: 12px;
            background: var(--coffee-foam);
            color: var(--coffee-roast);
            border: 1px solid var(--border);
            text-decoration: none;
            font-size: 12.5px;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .btn-outline-coffee:hover {
            background: var(--coffee-sand);
            color: var(--coffee-espresso);
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 22px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 22px 24px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            border-bottom: 4px solid var(--coffee-mocha);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-card h3 {
            margin: 0;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: var(--text-muted);
            font-weight: 700;
        }

        .stat-card p {
            margin: 8px 0 0;
            font-size: 28px;
            font-weight: 800;
            color: var(--coffee-espresso);
        }

        .stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 13px;
            background: var(--coffee-sand);
            color: var(--coffee-roast);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
        }

        /* Financial & Compliance Strip */
        .finance-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 26px;
        }

        .mini-metric {
            background: var(--coffee-foam);
            border: 1px solid var(--border);
            border-left: 4px solid var(--coffee-caramel);
            border-radius: 16px;
            padding: 15px 18px;
            box-shadow: var(--shadow-sm);
        }

        .mini-metric-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-muted);
            font-weight: 800;
        }

        .mini-metric-value {
            margin-top: 5px;
            font-size: 19px;
            font-weight: 800;
            color: var(--coffee-espresso);
        }

        /* Quick Actions Grid */
        .section-heading {
            margin: 0 0 14px;
            font-size: 16px;
            font-weight: 800;
            color: var(--coffee-espresso);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-heading i {
            color: var(--coffee-latte);
        }

        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 26px;
        }

        .quick-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 18px;
            text-decoration: none;
            color: var(--text-main);
            box-shadow: var(--shadow-sm);
            transition: all 0.22s ease;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .quick-card:hover {
            transform: translateY(-3px);
            border-color: var(--coffee-caramel);
            box-shadow: var(--shadow-md);
            background: var(--coffee-foam);
        }

        .quick-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .quick-badge-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            background: var(--coffee-sand);
            color: var(--coffee-roast);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
        }

        .quick-card-title {
            font-size: 14.5px;
            font-weight: 800;
            color: var(--coffee-espresso);
        }

        .quick-card-desc {
            font-size: 12.5px;
            color: var(--text-muted);
            line-height: 1.45;
        }

        .card {
            background: var(--card-bg);
            padding: 26px 28px;
            border-radius: 22px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .profile-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 11px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            margin-left: 10px;
            vertical-align: middle;
        }

        .status-verified {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .status-incomplete {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        /* Business Profile Action Button */
        .profile-action-btn {
            background: linear-gradient(135deg, var(--coffee-roast), var(--coffee-mocha));
            color: #ffffff;
            border: none;
            padding: 10px 18px;
            border-radius: 12px;
            cursor: pointer;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
            box-shadow: 0 4px 12px rgba(59, 35, 25, 0.16);
        }

        .profile-action-btn:hover {
            background: linear-gradient(135deg, var(--coffee-espresso), var(--coffee-roast));
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(59, 35, 25, 0.24);
        }

        .tax-pill-box {
            text-align: right;
            font-size: 12px;
            background: var(--coffee-foam);
            padding: 10px 15px;
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .empty-profile-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            padding: 10px 0;
        }

        .two-col-grid {
            display: grid;
            grid-template-columns: 1.35fr 1fr;
            gap: 22px;
            margin-top: 26px;
        }

        /* Modal Backdrop & Dialog */
        .modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 999;
            background: rgba(31, 17, 12, 0.45);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow-y: auto;
        }

        .modal-backdrop.is-open {
            display: flex;
        }

        .modal-dialog {
            width: 100%;
            max-width: 720px;
            background: var(--card-bg);
            border-radius: 24px;
            padding: 30px 32px;
            border: 1px solid var(--border);
            border-top: 5px solid var(--coffee-mocha);
            box-shadow: 0 25px 60px rgba(20, 10, 6, 0.35);
            max-height: 90vh;
            overflow-y: auto;
            animation: modalPop 0.22s ease-out;
        }

        @keyframes modalPop {
            from {
                opacity: 0;
                transform: translateY(12px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border-neutral);
        }

        .modal-header h3 {
            color: var(--coffee-espresso);
            font-weight: 800;
            margin: 0;
            font-size: 19px;
        }

        .modal-close-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--coffee-foam);
            color: var(--coffee-roast);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            transition: 0.2s ease;
        }

        .modal-close-btn:hover {
            background: var(--coffee-sand);
            color: var(--coffee-espresso);
        }

        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--coffee-roast);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-label i {
            color: var(--coffee-latte);
            margin-right: 4px;
            width: 14px;
        }

        .styled-input {
            width: 100%;
            padding: 12px 15px;
            border: 1.5px solid var(--border-neutral);
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
            color: var(--coffee-espresso);
            transition: all 0.25s ease;
            background: var(--coffee-foam);
        }

        .styled-input:focus {
            outline: none;
            border-color: var(--coffee-mocha);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(92, 58, 40, 0.12);
        }

        .save-btn {
            flex: 2;
            background: linear-gradient(135deg, var(--coffee-roast), var(--coffee-mocha));
            color: #ffffff;
            border: none;
            padding: 14px;
            border-radius: 12px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            font-size: 15px;
            transition: 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .save-btn:hover {
            background: linear-gradient(135deg, var(--coffee-espresso), var(--coffee-roast));
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 35, 25, 0.24);
        }

        .cancel-btn {
            flex: 1;
            background: var(--coffee-cream);
            color: var(--coffee-roast);
            border: 1px solid var(--border);
            padding: 14px;
            border-radius: 12px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            transition: 0.25s ease;
        }

        .cancel-btn:hover {
            background: var(--coffee-sand);
        }

        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th {
            text-align: left;
            padding: 12px 14px;
            background: var(--coffee-cream);
            color: var(--coffee-espresso);
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border);
        }

        td {
            padding: 13px 14px;
            border-bottom: 1px solid var(--border-neutral);
            font-size: 13.5px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            display: inline-block;
        }

        .bg-pending, .bg-unpaid {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .bg-completed, .bg-paid {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        @media (max-width: 1200px) {
            .finance-strip,
            .quick-actions-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .two-col-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1024px) {
            .main {
                padding: 28px 24px;
            }
            .stat-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="dashboard-shell" id="dashboardShell">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="sidebar-brand-icons">
                    <i class="fas fa-user-tie"></i>
                    <i class="fas fa-mug-hot"></i>
                </div>
                <h2>Karunesh Kumar &amp; Associates</h2>
            </div>

            <div class="sidebar-profile">
                <div class="sidebar-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h4 class="sidebar-profile-name"><?php echo htmlspecialchars($clientName); ?></h4>
                <span class="sidebar-profile-id">ID: <?php echo htmlspecialchars($cid); ?></span>
            </div>

            <div class="nav-label">Client Menu</div>
            <a href="client-dashboard.php" class="active"><i class="fas fa-chart-line"></i> Overview</a>

            <div class="dropdown-container">
                <a href="javascript:void(0)" onclick="toggleFinances()">
                    <i class="fas fa-wallet"></i> My Finances
                    <i class="fas fa-chevron-down rotate-chevron" id="financeChevron" style="margin-left:auto; font-size:12px;"></i>
                </a>
                <div class="dropdown-content" id="financeMenu">
                    <a href="my-quotations.php"><i class="fas fa-file-alt"></i> Quotations</a>
                    <a href="my-invoices.php"><i class="fas fa-file-invoice-dollar"></i> Invoices (Pay)</a>
                    <a href="my-receipts.php"><i class="fas fa-receipt"></i> Acknowledgement</a>
                </div>
            </div>

            <a href="my-documents.php"><i class="fas fa-folder-open"></i> Document Vault</a>
            <a href="upload-docs.php"><i class="fas fa-cloud-upload-alt"></i> Upload Center</a>
            <a href="request-service.php"><i class="fas fa-plus-circle"></i> New Request</a>

            <a href="../logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </aside>

        <div class="main">
            <div class="page-header">
                <div>
                    <h1>Welcome, <?php echo htmlspecialchars($clientName); ?></h1>
                    <p class="page-subtitle">Manage your business profile, service requests, billing, and compliance documents.</p>
                </div>
                <div class="header-actions">
                    <div class="date-pill">
                        <i class="far fa-calendar-alt" style="color:var(--coffee-latte);"></i>
                        <span><?php echo date('d M, Y'); ?></span>
                    </div>
                    <a href="request-service.php" class="btn-outline-coffee">
                        <i class="fas fa-plus-circle" style="color:var(--coffee-latte);"></i> New Request
                    </a>
                </div>
            </div>

            <div class="stat-grid">
                <div class="stat-card">
                    <div>
                        <h3>Active Requests</h3>
                        <p><?php echo $activeRequestsCount; ?></p>
                    </div>
                    <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                </div>
                <div class="stat-card" style="border-color: var(--coffee-latte);">
                    <div>
                        <h3>Documents</h3>
                        <p><?php echo $documentsCount; ?></p>
                    </div>
                    <div class="stat-icon"><i class="fas fa-folder-open"></i></div>
                </div>
                <div class="stat-card" style="border-color: var(--coffee-espresso);">
                    <div>
                        <h3>Completed</h3>
                        <p><?php echo $completedCount; ?></p>
                    </div>
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>

            <!-- Financial & Quotation Snapshot Strip -->
            <div class="finance-strip">
                <div class="mini-metric">
                    <div class="mini-metric-label">Total Billed</div>
                    <div class="mini-metric-value">₹<?php echo number_format($totalBilled, 2); ?></div>
                </div>
                <div class="mini-metric">
                    <div class="mini-metric-label">Amount Paid</div>
                    <div class="mini-metric-value" style="color:#166534;">₹<?php echo number_format($totalPaid, 2); ?></div>
                </div>
                <div class="mini-metric">
                    <div class="mini-metric-label">Pending Dues</div>
                    <div class="mini-metric-value" style="color:#92400e;">₹<?php echo number_format($pendingDues, 2); ?></div>
                </div>
                <div class="mini-metric">
                    <div class="mini-metric-label">Accepted Quotes</div>
                    <div class="mini-metric-value"><?php echo $acceptedQuotesCount; ?></div>
                </div>
            </div>

            <!-- Quick Actions -->
            <h3 class="section-heading"><i class="fas fa-bolt"></i> Quick Actions</h3>
            <div class="quick-actions-grid">
                <a href="request-service.php" class="quick-card">
                    <div class="quick-card-top">
                        <span class="quick-badge-icon"><i class="fas fa-plus-circle"></i></span>
                        <i class="fas fa-arrow-right" style="color:var(--coffee-latte); font-size:12px;"></i>
                    </div>
                    <div class="quick-card-title">Request Service</div>
                    <div class="quick-card-desc">Submit a new GST, ITR, audit, or compliance request.</div>
                </a>

                <a href="upload-docs.php" class="quick-card">
                    <div class="quick-card-top">
                        <span class="quick-badge-icon"><i class="fas fa-cloud-upload-alt"></i></span>
                        <i class="fas fa-arrow-right" style="color:var(--coffee-latte); font-size:12px;"></i>
                    </div>
                    <div class="quick-card-title">Upload Documents</div>
                    <div class="quick-card-desc">Share KYC, bank statements, or tax files with the office.</div>
                </a>

                <a href="my-invoices.php" class="quick-card">
                    <div class="quick-card-top">
                        <span class="quick-badge-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                        <i class="fas fa-arrow-right" style="color:var(--coffee-latte); font-size:12px;"></i>
                    </div>
                    <div class="quick-card-title">Pay Invoices</div>
                    <div class="quick-card-desc">Review open invoices and clear pending balances.</div>
                </a>

                <a href="my-documents.php" class="quick-card">
                    <div class="quick-card-top">
                        <span class="quick-badge-icon"><i class="fas fa-folder-open"></i></span>
                        <i class="fas fa-arrow-right" style="color:var(--coffee-latte); font-size:12px;"></i>
                    </div>
                    <div class="quick-card-title">Document Vault</div>
                    <div class="quick-card-desc">Access released certificates and filed records by date.</div>
                </a>
            </div>

            <!-- Business Profile Card -->
            <div class="card" style="border-top: 5px solid var(--coffee-mocha);">
                <?php if($profile): ?>
                <div style="display:flex; justify-content:space-between; align-items:start; gap:16px; flex-wrap:wrap;">
                    <div>
                        <div style="display:flex; align-items:center; flex-wrap:wrap; gap:6px;">
                            <h2 style="margin:0; color:var(--coffee-espresso); font-weight:800; letter-spacing: -0.4px;"><?php echo htmlspecialchars($profile['company_name']); ?></h2>
                            <span class="profile-status-pill <?php echo $profileComplete ? 'status-verified' : 'status-incomplete'; ?>">
                                <i class="fas <?php echo $profileComplete ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                                <?php echo $profileComplete ? 'Profile Ready' : 'Needs Details'; ?>
                            </span>
                        </div>
                        <div style="display: flex; gap: 20px; margin-top: 12px; flex-wrap: wrap;">
                            <span style="font-size: 14px; color:var(--coffee-mocha); font-weight:700;"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($profile['owner_name']); ?></span>
                            <span style="font-size: 14px; color:var(--text-muted); font-weight:600;"><i class="fas fa-phone-alt" style="color:var(--coffee-latte);"></i> +91 <?php echo htmlspecialchars($profile['phone']); ?></span>
                            <span style="font-size: 14px; color:var(--text-muted); font-weight:600;"><i class="fas fa-envelope" style="color:var(--coffee-latte);"></i> <?php echo htmlspecialchars($profile['business_email']); ?></span>
                        </div>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:10px; align-items:flex-end;">
                        <button type="button" class="profile-action-btn" onclick="openProfileModal()">
                            <i class="fas fa-pen-to-square"></i> Edit Business Profile
                        </button>
                        <div class="tax-pill-box">
                            <p style="margin:0; color:var(--coffee-roast); font-weight:700;">GST: <b style="font-family: monospace; font-size:14px; color:var(--coffee-espresso);"><?php echo htmlspecialchars($profile['gst_no'] ?: 'NOT PROVIDED'); ?></b></p>
                            <p style="margin:4px 0 0; color:var(--coffee-roast); font-weight:700;">PAN: <b style="font-family: monospace; font-size:14px; color:var(--coffee-espresso);"><?php echo htmlspecialchars($profile['pan_no']); ?></b></p>
                        </div>
                    </div>
                </div>
                <div style="margin-top:20px; padding-top:15px; border-top:1px solid var(--border-neutral); font-size:14px; color: var(--text-muted); font-weight:500;">
                    <i class="fas fa-map-location-dot" style="color:var(--coffee-latte); margin-right:8px;"></i> <?php echo htmlspecialchars($profile['address']); ?>
                </div>
                <?php else: ?>
                <div class="empty-profile-box">
                    <div>
                        <h3 style="margin:0; color:var(--coffee-espresso); font-weight:800;"><i class="fas fa-briefcase" style="color:var(--coffee-latte);"></i> Business Profile Not Configured</h3>
                        <p style="margin:6px 0 0; font-size:13.5px; color:var(--text-muted);">Add your company, GST, PAN, and registered address details to generate invoices.</p>
                    </div>
                    <button type="button" class="profile-action-btn" onclick="openProfileModal()">
                        <i class="fas fa-plus-circle"></i> Complete Business Profile
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recent Activity + Recent Invoices Split -->
            <div class="two-col-grid">
                <div class="card" style="border-left: 5px solid var(--coffee-roast);">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
                        <h3 style="color: var(--coffee-espresso); font-weight:800; margin:0; font-size:16px;"><i class="fas fa-clock-rotate-left" style="color:var(--coffee-latte);"></i> Recent Activity</h3>
                        <a href="request-service.php" style="font-size:12px; font-weight:700; color:var(--coffee-mocha); text-decoration:none;">+ Request</a>
                    </div>
                    <table>
                        <thead>
                            <tr><th>Service</th><th>Description</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $history = $conn->query("SELECT * FROM service_requests WHERE client_id='$cid' ORDER BY id DESC LIMIT 5");
                            if ($history && $history->num_rows > 0) {
                                while ($h = $history->fetch_assoc()) {
                                    $status_class = "bg-" . strtolower($h['status']);
                                    echo "<tr>
                                            <td><strong style='color:var(--coffee-espresso);'>" . htmlspecialchars($h['service_type']) . "</strong></td>
                                            <td style='color:var(--text-muted);'>" . htmlspecialchars($h['description']) . "</td>
                                            <td><span class='badge $status_class'>" . htmlspecialchars($h['status']) . "</span></td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' style='text-align:center; color:var(--text-muted); padding:28px;'>No recent activity found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="card" style="border-left: 5px solid var(--coffee-caramel);">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
                        <h3 style="color: var(--coffee-espresso); font-weight:800; margin:0; font-size:16px;"><i class="fas fa-file-invoice" style="color:var(--coffee-latte);"></i> Recent Invoices</h3>
                        <a href="my-invoices.php" style="font-size:12px; font-weight:700; color:var(--coffee-mocha); text-decoration:none;">View All</a>
                    </div>
                    <table>
                        <thead>
                            <tr><th>Invoice</th><th>Amount</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $recentInv = $conn->query("SELECT id, invoice_no, (amount + cgst_amount + sgst_amount + igst_amount) AS net_amt, status FROM invoices WHERE client_id='$cid' ORDER BY id DESC LIMIT 4");
                            if ($recentInv && $recentInv->num_rows > 0) {
                                while ($ri = $recentInv->fetch_assoc()) {
                                    $invStatusClass = (strtolower($ri['status']) === 'paid') ? 'bg-paid' : 'bg-unpaid';
                                    echo "<tr>
                                            <td><strong style='color:var(--coffee-espresso);'>#" . htmlspecialchars($ri['invoice_no']) . "</strong></td>
                                            <td style='font-weight:700; color:var(--coffee-roast);'>₹" . number_format((float)$ri['net_amt'], 2) . "</td>
                                            <td><span class='badge $invStatusClass'>" . htmlspecialchars($ri['status']) . "</span></td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' style='text-align:center; color:var(--text-muted); padding:28px;'>No invoices yet.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit / Complete Business Profile Modal -->
    <div class="modal-backdrop" id="profileModal" role="dialog" aria-modal="true" aria-labelledby="profileModalTitle">
        <div class="modal-dialog" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 id="profileModalTitle">
                    <i class="fas fa-briefcase" style="color:var(--coffee-latte);"></i>
                    <?php echo $profile ? 'Update Business Profile' : 'Complete Your Business Profile'; ?>
                </h3>
                <button type="button" class="modal-close-btn" onclick="closeProfileModal()" aria-label="Close modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 18px;">
                <div style="grid-column: span 3;">
                    <label class="form-label"><i class="fas fa-building"></i> Company Name</label>
                    <input type="text" name="company_name" class="styled-input" placeholder="e.g. KKA Solutions Pvt Ltd" value="<?php echo htmlspecialchars($profile['company_name'] ?? ''); ?>" required>
                </div>
                <div style="grid-column: span 3;">
                    <label class="form-label"><i class="fas fa-user-circle"></i> Authorized Owner Name</label>
                    <input type="text" name="owner_name" class="styled-input" placeholder="Name as per PAN" value="<?php echo htmlspecialchars($profile['owner_name'] ?? ''); ?>" required>
                </div>

                <div style="grid-column: span 3;">
                    <label class="form-label"><i class="fas fa-envelope-open-text"></i> Business Email</label>
                    <input type="email" name="business_email" class="styled-input" placeholder="billing@company.com" value="<?php echo htmlspecialchars($profile['business_email'] ?? ''); ?>" required>
                </div>
                <div style="grid-column: span 3;">
                    <label class="form-label"><i class="fas fa-mobile-screen"></i> Contact Phone</label>
                    <input type="text" name="phone" class="styled-input" placeholder="10-digit mobile number" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" required>
                </div>

                <div style="grid-column: span 3;">
                    <label class="form-label"><i class="fas fa-fingerprint"></i> GST Number (Optional)</label>
                    <input type="text" name="gst_no" class="styled-input" placeholder="22AAAAA0000A1Z5" value="<?php echo htmlspecialchars($profile['gst_no'] ?? ''); ?>">
                </div>
                <div style="grid-column: span 3;">
                    <label class="form-label"><i class="fas fa-address-card"></i> PAN Number</label>
                    <input type="text" name="pan_no" class="styled-input" placeholder="ABCDE1234F" value="<?php echo htmlspecialchars($profile['pan_no'] ?? ''); ?>" required>
                </div>

                <div style="grid-column: span 6;">
                    <label class="form-label"><i class="fas fa-map-marker-alt"></i> Full Registered Address</label>
                    <textarea name="address" class="styled-input" rows="2" placeholder="Office No, Building, Street, City, State - PIN" required style="resize: none;"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                </div>

                <div style="grid-column: span 6; display:flex; gap:12px; margin-top: 8px;">
                    <button type="submit" name="save_profile" class="save-btn">
                        <i class="fas fa-check-circle"></i> Save Business Profile
                    </button>
                    <button type="button" onclick="closeProfileModal()" class="cancel-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleFinances() {
            var menu = document.getElementById("financeMenu");
            var chevron = document.getElementById("financeChevron");
            menu.style.display = (menu.style.display === "none" || menu.style.display === "") ? "block" : "none";
            chevron.classList.toggle("rotate-chevron");
        }

        function openProfileModal() {
            var modal = document.getElementById("profileModal");
            modal.classList.add("is-open");
            document.body.classList.add("modal-open");
        }

        function closeProfileModal() {
            var modal = document.getElementById("profileModal");
            modal.classList.remove("is-open");
            document.body.classList.remove("modal-open");
        }

        document.getElementById("profileModal").addEventListener("click", function () {
            closeProfileModal();
        });

        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") {
                closeProfileModal();
            }
        });
    </script>
    <?php
// Trigger once right after login (set $_SESSION['just_logged_in'] = true; on successful login)
$show_client_anim = !empty($_SESSION['just_logged_in']);
if ($show_client_anim) {
    unset($_SESSION['just_logged_in']);
}
$client_name = $display_name ?? ($_SESSION['user']['name'] ?? 'Client');
$client_id = $_SESSION['user']['identifier'] ?? '';
?>
<?php if ($show_client_anim): ?>
<div id="kkaClientSplash" class="kka-client-splash" aria-hidden="true">
    <div class="kka-client-glow kka-client-glow-1"></div>
    <div class="kka-client-glow kka-client-glow-2"></div>

    <div class="kka-client-card">
        <div class="kka-client-ring">
            <div class="kka-client-emblem">
                <i class="fas fa-mug-hot"></i>
            </div>
        </div>

        <div class="kka-client-tag">
            <i class="fas fa-user-check"></i> Client Portal Access
        </div>

        <h2 class="kka-client-title">Karunesh Kumar &amp; Associates</h2>
        <p class="kka-client-sub">Welcome back, <strong><?php echo htmlspecialchars($client_name); ?></strong></p>
        <?php if (!empty($client_id)): ?>
            <div class="kka-client-id-pill">Client ID: <?php echo htmlspecialchars($client_id); ?></div>
        <?php endif; ?>

        <div class="kka-client-stage" id="kkaClientStageText">Verifying client session &amp; security token...</div>

        <div class="kka-client-bar">
            <div class="kka-client-bar-fill"></div>
        </div>

        <div class="kka-client-footer">
            <span id="kkaClientCountdown">Entering client portal in 15s</span>
            <button type="button" id="kkaClientSkipBtn" class="kka-client-skip">
                Skip <i class="fas fa-forward"></i>
            </button>
        </div>
    </div>
</div>

<style>
    .kka-client-splash {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        overflow: hidden;
        background: radial-gradient(circle at top left, rgba(92, 58, 40, 0.96), rgba(59, 35, 25, 0.98) 60%, #1f110c 100%);
        backdrop-filter: blur(10px);
        font-family: 'Plus Jakarta Sans', sans-serif;
        opacity: 1;
        transition: opacity 0.6s ease, visibility 0.6s ease;
    }

    .kka-client-splash.is-hiding {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    .kka-client-glow {
        position: absolute;
        border-radius: 50%;
        filter: blur(75px);
        pointer-events: none;
        opacity: 0.34;
    }

    .kka-client-glow-1 {
        width: 340px;
        height: 340px;
        background: #b87a4b;
        top: -90px;
        right: -70px;
        animation: kkaClientDriftA 15s ease-in-out infinite;
    }

    .kka-client-glow-2 {
        width: 310px;
        height: 310px;
        background: #8c5a3c;
        bottom: -95px;
        left: -60px;
        animation: kkaClientDriftB 15s ease-in-out infinite;
    }

    .kka-client-card {
        position: relative;
        z-index: 2;
        text-align: center;
        max-width: 490px;
        width: 100%;
        padding: 38px 30px;
        border-radius: 24px;
        background: rgba(253, 250, 247, 0.08);
        border: 1px solid rgba(243, 213, 181, 0.22);
        box-shadow: 0 24px 60px rgba(20, 10, 6, 0.52);
        animation: kkaClientCardIn 0.8s cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    .kka-client-ring {
        width: 84px;
        height: 84px;
        margin: 0 auto 18px;
        border-radius: 24px;
        padding: 3px;
        background: linear-gradient(135deg, #b87a4b, #f3d5b5, #8c5a3c);
        animation: kkaClientPulse 2.5s ease-in-out infinite;
    }

    .kka-client-emblem {
        width: 100%;
        height: 100%;
        border-radius: 21px;
        background: #2b1810;
        color: #f3d5b5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        animation: kkaClientIconBob 3s ease-in-out infinite;
    }

    .kka-client-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 999px;
        background: rgba(184, 122, 75, 0.22);
        border: 1px solid rgba(243, 213, 181, 0.38);
        color: #f3d5b5;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.7px;
        text-transform: uppercase;
        margin-bottom: 14px;
    }

    .kka-client-title {
        margin: 0;
        font-size: 25px;
        font-weight: 800;
        letter-spacing: -0.3px;
        background: linear-gradient(90deg, #fdfaf7 0%, #f3d5b5 50%, #d49b6a 100%);
        background-size: 200% auto;
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        animation: kkaClientTextSlide 0.9s cubic-bezier(0.22, 1, 0.36, 1) both, kkaClientShimmer 5s linear infinite;
    }

    .kka-client-sub {
        margin: 10px 0 8px;
        font-size: 14px;
        color: rgba(253, 250, 247, 0.9);
        font-weight: 500;
    }

    .kka-client-sub strong {
        color: #ffffff;
        font-weight: 700;
    }

    .kka-client-id-pill {
        display: inline-block;
        margin-bottom: 14px;
        padding: 3px 12px;
        border-radius: 999px;
        background: rgba(31, 17, 12, 0.45);
        border: 1px solid rgba(243, 213, 181, 0.25);
        color: #f3d5b5;
        font-size: 11.5px;
        font-weight: 700;
        letter-spacing: 0.4px;
    }

    .kka-client-stage {
        font-size: 12px;
        color: #f3d5b5;
        font-weight: 600;
        margin-bottom: 14px;
        min-height: 18px;
    }

    .kka-client-bar {
        width: 100%;
        height: 5px;
        border-radius: 999px;
        background: rgba(253, 250, 247, 0.16);
        overflow: hidden;
    }

    .kka-client-bar-fill {
        height: 100%;
        width: 0%;
        border-radius: 999px;
        background: linear-gradient(90deg, #8c5a3c, #b87a4b, #f3d5b5);
        animation: kkaClientProgress15s 15s linear forwards;
    }

    .kka-client-footer {
        margin-top: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        font-size: 11.5px;
        color: rgba(253, 250, 247, 0.78);
        font-weight: 600;
    }

    .kka-client-skip {
        background: rgba(253, 250, 247, 0.1);
        color: #f3d5b5;
        border: 1px solid rgba(243, 213, 181, 0.4);
        padding: 5px 12px;
        border-radius: 999px;
        font-family: inherit;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.2s ease;
    }

    .kka-client-skip:hover {
        background: #b87a4b;
        color: #ffffff;
    }

    @keyframes kkaClientCardIn {
        from { opacity: 0; transform: translateY(20px) scale(0.96); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes kkaClientTextSlide {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes kkaClientShimmer {
        0% { background-position: 0% center; }
        100% { background-position: 200% center; }
    }

    @keyframes kkaClientPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    @keyframes kkaClientIconBob {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-3px); }
    }

    @keyframes kkaClientDriftA {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(-45px, 35px); }
    }

    @keyframes kkaClientDriftB {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(45px, -35px); }
    }

    @keyframes kkaClientProgress15s {
        from { width: 0%; }
        to { width: 100%; }
    }
</style>

<script>
    (function () {
        const splash = document.getElementById('kkaClientSplash');
        if (!splash) return;

        const stageEl = document.getElementById('kkaClientStageText');
        const countEl = document.getElementById('kkaClientCountdown');
        const skipBtn = document.getElementById('kkaClientSkipBtn');

        const stages = [
            { at: 0,  text: 'Verifying client session & security token...' },
            { at: 4,  text: 'Syncing your business profile, invoices & requests...' },
            { at: 9,  text: 'Preparing your document vault & compliance summary...' },
            { at: 13, text: 'Opening Karunesh Kumar & Associates client dashboard...' }
        ];

        let remaining = 15;
        let dismissed = false;

        function dismissSplash() {
            if (dismissed) return;
            dismissed = true;
            clearInterval(timer);
            splash.classList.add('is-hiding');
            setTimeout(() => splash.remove(), 650);
        }

        function syncText() {
            const elapsed = 15 - remaining;
            let currentStage = stages[0].text;
            for (const s of stages) {
                if (elapsed >= s.at) currentStage = s.text;
            }
            if (stageEl) stageEl.textContent = currentStage;
            if (countEl) countEl.textContent = 'Entering client portal in ' + Math.max(0, remaining) + 's';
        }

        syncText();

        const timer = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
                if (countEl) countEl.textContent = 'Entering client portal in 0s';
                dismissSplash();
            } else {
                syncText();
            }
        }, 1000);

        if (skipBtn) {
            skipBtn.addEventListener('click', dismissSplash);
        }
    })();
</script>
<?php endif; ?>
</body>
</html>