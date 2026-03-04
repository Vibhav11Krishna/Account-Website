<?php
session_start();
include('../db.php');

// 1. SECURITY CHECK - Ensure only clients access this
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'client') {
    header("Location: ../Login.php");
    exit();
}

$cid = $_SESSION['user']['identifier'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Receipts | KKA Client</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --navy: #0b3c74; --orange: #ff8c00; --sidebar: #082d56; --bg: #f8fafc; --text-light: #64748b; }
        body { display:flex; margin:0; background:var(--bg); font-family: 'Inter', sans-serif; color: #334155; }
        
        /* Sidebar Styles (Matching your Invoices page) */
        .sidebar { width:280px; background:var(--sidebar); color:white; height:100vh; position:fixed; padding:30px 20px; box-sizing: border-box; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 22px; color: var(--orange); margin-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .sidebar a { color:rgba(255,255,255,0.7); text-decoration:none; display:flex; align-items:center; gap:12px; padding:14px; margin-bottom:8px; border-radius:12px; transition: 0.3s; }
        .sidebar a:hover, .active-link { background:rgba(255,255,255,0.1); color:white; border-left: 4px solid var(--orange); }

        .main { margin-left:280px; padding:50px; width:calc(100% - 280px); box-sizing: border-box; }
        .card { background:white; padding:30px; border-radius:24px; box-shadow:0 10px 30px rgba(0,0,0,0.03); border: 1px solid #edf2f7; }
        
        table { width:100%; border-collapse:collapse; margin-top: 20px; }
        th { text-align:left; padding:15px; background:#f1f5f9; color:var(--navy); font-size: 13px; text-transform: uppercase; }
        td { padding:15px; border-bottom:1px solid #f1f5f9; font-size: 14px; }

        .btn-view { 
            background: white; color: var(--navy); border: 1px solid #e2e8f0; padding: 8px 16px; 
            border-radius: 8px; cursor: pointer; font-weight: bold; text-decoration:none;
            font-size: 12px; transition: 0.3s; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-view:hover { background: #f8fafc; border-color: var(--navy); }
        
        .badge-paid { background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; }
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
            <a href="my-invoices.php"><i class="fas fa-file-invoice-dollar"></i> Invoices (Pay)</a>
            <a href="my-receipts.php"style="background:rgba(255,255,255,0.1); color:white !important;"><i class="fas fa-receipt"></i> Receipts</a>
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
    <h1>Payment Receipts</h1>
    <p style="color:var(--text-light); margin-top:-10px;">Download or view your official payment confirmations.</p>

    <div class="card" style="padding:0; overflow:hidden;">
        <table>
            <thead>
                <tr>
                    <th>Receipt No</th>
                    <th>Invoice ID</th>
                    <th>Date Paid</th>
                    <th>Amount Paid</th>
                    <th>Method</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Querying receipts specifically for this client
                $query = "SELECT * FROM receipts WHERE client_id = '$cid' ORDER BY id DESC";
                $receipts = $conn->query($query);

                if($receipts && $receipts->num_rows > 0) {
                    while($r = $receipts->fetch_assoc()) {
                        // We use the common view_receipt.php file
                        // Note: adjusting path if view_receipt is in admin folder
                        echo "<tr>
                            <td style='font-weight:700;'>{$r['receipt_no']}</td>
                            <td style='color:var(--text-light);'>{$r['invoice_no']}</td>
                            <td>" . date('d M Y', strtotime($r['created_at'])) . "</td>
                            <td style='font-weight:700; color:#166534;'>₹" . number_format($r['amount_paid'], 2) . "</td>
                            <td><span class='badge-paid'>{$r['payment_mode']}</span></td>
                            <td>
                                <a href='../office/view_receipt.php?id={$r['receipt_no']}' target='_blank' class='btn-view'>
                                    <i class='fas fa-file-download'></i> View Receipt
                                </a>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align:center; padding:50px; color:var(--text-light);'>
                            <i class='fas fa-receipt' style='font-size:30px; margin-bottom:10px;'></i><br>
                            No receipts found. Complete an invoice payment to see it here.
                          </td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function toggleFinances() {
    var menu = document.getElementById("financeMenu");
    var chevron = document.getElementById("financeChevron");
    if (menu.style.display === "none") {
        menu.style.display = "block";
        chevron.classList.add("rotate-chevron");
    } else {
        menu.style.display = "none";
        chevron.classList.remove("rotate-chevron");
    }
}
</script>
</body>
</html>