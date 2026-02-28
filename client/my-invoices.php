<?php
session_start();
include('../db.php');

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'client') {
    header("Location: ../Login.php");
    exit();
}

$cid = $_SESSION['user']['identifier'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Invoices | KKA Client</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --navy: #0b3c74; --orange: #ff8c00; --sidebar: #082d56; --bg: #f8fafc; --text-light: #64748b; }
        body { display:flex; margin:0; background:var(--bg); font-family: 'Inter', sans-serif; color: #334155; }
        
        .sidebar { width:280px; background:var(--sidebar); color:white; height:100vh; position:fixed; padding:30px 20px; box-sizing: border-box; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 22px; color: var(--orange); margin-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .sidebar a { color:rgba(255,255,255,0.7); text-decoration:none; display:flex; align-items:center; gap:12px; padding:14px; margin-bottom:8px; border-radius:12px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background:rgba(255,255,255,0.1); color:white; border-left: 4px solid var(--orange); }

        .main { margin-left:280px; padding:50px; width:calc(100% - 280px); box-sizing: border-box; }
        .card { background:white; padding:30px; border-radius:24px; box-shadow:0 10px 30px rgba(0,0,0,0.03); border: 1px solid #edf2f7; }
        
        table { width:100%; border-collapse:collapse; margin-top: 20px; }
        th { text-align:left; padding:15px; background:#f1f5f9; color:var(--navy); font-size: 13px; }
        td { padding:15px; border-bottom:1px solid #f1f5f9; font-size: 15px; }

        .btn-pay { 
            background: var(--navy); color: white; border: none; padding: 10px 20px; 
            border-radius: 10px; cursor: pointer; font-weight: bold; text-decoration:none;
            display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; 
        }
        .btn-pay:hover { background: var(--orange); transform: translateY(-2px); }
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .unpaid { background: #fee2e2; color: #ef4444; }
        .paid { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>KKA CLIENT</h2>
    <a href="client-dashboard.php"><i class="fas fa-chart-line"></i> Overview</a>

    <div class="dropdown-container">
        <a href="javascript:void(0)" class="dropdown-btn active" onclick="toggleFinances()">
            <i class="fas fa-wallet"></i> My Finances 
            <i class="fas fa-chevron-down rotate-chevron" id="financeChevron" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
        </a>
        <div class="dropdown-content" id="financeMenu" style="display:block; background:rgba(0,0,0,0.2); border-radius:10px; margin:0 10px;">
            <a href="my-quotations.php" ><i class="fas fa-file-alt"></i> Quotations</a>
            <a href="my-invoices.php"style="background:rgba(255,255,255,0.1); color:white !important;"><i class="fas fa-file-invoice-dollar"></i> Invoices (Pay)</a>
            <a href="my-receipts.php"><i class="fas fa-receipt"></i> Receipts</a>
        </div>
    </div>

    <a href="my-documents.php"><i class="fas fa-folder-open"></i> Document Vault</a>
    <a href="upload-docs.php"><i class="fas fa-cloud-upload-alt"></i> Upload Center</a>
    <a href="request-service.php"><i class="fas fa-plus-circle"></i> New Request</a>
    
    <a href="../logout.php" style="margin-top:auto; color:#fda4af !important; background: rgba(244, 63, 94, 0.1); padding:14px; border-radius:12px; text-decoration:none; display:flex; align-items:center; gap:12px;">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<div class="main">
    <h1>Invoices</h1>
    <p style="color:var(--text-light); margin-top:-10px;">Select an invoice to complete payment.</p>

    <div class="card" style="padding:0; overflow:hidden;">
        <table>
            <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>Service</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $invoices = $conn->query("SELECT * FROM invoices WHERE client_id = '$cid' ORDER BY id DESC");
                while($inv = $invoices->fetch_assoc()) {
                    $is_unpaid = (strtolower($inv['status']) == 'unpaid');
                    echo "<tr>
                        <td>#{$inv['invoice_no']}</td>
                        <td>{$inv['service_name']}</td>
                        <td style='font-weight:700;'>₹" . number_format($inv['amount'], 2) . "</td>
                        <td><span class='badge " . ($is_unpaid ? 'unpaid' : 'paid') . "'>{$inv['status']}</span></td>
                        <td>";
                    
                    if($is_unpaid) {
                        // This link goes to our fake payment processor
                        echo "<a href='process-test-pay.php?id={$inv['id']}' class='btn-pay'>
                                <i class='fas fa-credit-card'></i> Pay Now
                              </a>";
                    } else {
                        echo "<span style='color:var(--text-light); font-size:12px;'><i class='fas fa-check-circle'></i> Payment Done</span>";
                    }
                    echo "</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>