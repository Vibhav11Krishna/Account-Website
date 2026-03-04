<?php
session_start();
include('../db.php');

// 1. SECURITY & DATA CHECK
if(!isset($_SESSION['user'])) { die("Please login first."); }
if(!isset($_GET['id'])) { die("Receipt ID is missing."); }

$rcp_id = mysqli_real_escape_string($conn, $_GET['id']);

// 2. FETCH COMPLETE DATA (Removed u.email as it doesn't exist in your table)
$sql = "SELECT r.*, i.service_name, i.amount as inv_total, u.name 
        FROM receipts r 
        LEFT JOIN invoices i ON r.invoice_no = i.invoice_no 
        LEFT JOIN users u ON r.client_id = u.identifier 
        WHERE r.receipt_no = '$rcp_id'";

$result = $conn->query($sql);
$data = $result->fetch_assoc();

if(!$data) { die("Receipt not found in our records. Please check the Receipt Number."); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt_<?php echo $rcp_id; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; padding: 40px; color: #1e293b; }
        .receipt-card { background: white; max-width: 800px; margin: auto; padding: 50px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); position: relative; }
        .header { display: flex; justify-content: space-between; border-bottom: 3px solid #0b3c74; padding-bottom: 20px; margin-bottom: 30px; }
        .brand h1 { color: #0b3c74; margin: 0; font-size: 28px; letter-spacing: -1px; }
        .brand p { margin: 5px 0; color: #64748b; font-size: 14px; }
        .details { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .details h4 { text-transform: uppercase; font-size: 11px; color: #94a3b8; margin-bottom: 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f8fafc; text-align: left; padding: 15px; font-size: 13px; color: #64748b; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .total-section { margin-top: 30px; text-align: right; }
        .total-row { display: inline-block; width: 250px; padding: 10px 0; font-size: 18px; font-weight: 700; color: #166534; border-top: 2px solid #e2e8f0; }
        .paid-stamp { position: absolute; top: 150px; right: 50px; border: 4px solid #166534; color: #166534; padding: 10px 20px; font-weight: 900; font-size: 24px; transform: rotate(-15deg); border-radius: 8px; opacity: 0.1; pointer-events: none; }
        .no-print-btn { background: #0b3c74; color: white; padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; margin-bottom: 20px; transition: 0.3s; }
        .no-print-btn:hover { background: #ff8c00; }
        @media print { .no-print-btn { display: none; } body { padding: 0; background: white; } .receipt-card { box-shadow: none; border: none; } }
    </style>
</head>
<body>

    <div style="text-align: center;">
        <button class="no-print-btn" onclick="window.print()">
            <i class="fas fa-print"></i> Print Official Receipt
        </button>
    </div>

    <div class="receipt-card">
        <div class="paid-stamp">PAID</div>
        
        <div class="header">
            <div class="brand">
                <h1>KKA ACCOUNTING</h1>
                <p>Digital Payment Confirmation</p>
            </div>
            <div style="text-align: right;">
                <p><strong>Receipt #:</strong> <?php echo $data['receipt_no']; ?></p>
                <p><strong>Date:</strong> <?php echo date('d M, Y', strtotime($data['created_at'])); ?></p>
            </div>
        </div>

        <div class="details">
            <div>
                <h4>Customer Details</h4>
                <strong><?php echo $data['name']; ?></strong><br>
                Client ID: <?php echo $data['client_id']; ?>
            </div>
            <div>
                <h4>Payment Info</h4>
                Invoice Ref: #<?php echo $data['invoice_no']; ?><br>
                Method: <?php echo $data['payment_mode']; ?><br>
                Status: Completed
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Service Description</th>
                    <th style="text-align: right;">Amount Paid</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $data['service_name']; ?></td>
                    <td style="text-align: right;">₹<?php echo number_format($data['amount_paid'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-row">
                Amount Paid: ₹<?php echo number_format($data['amount_paid'], 2); ?>
            </div>
        </div>

        <div style="margin-top: 60px; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 20px; font-size: 12px; color: #94a3b8;">
            Thank you for your business. This is an electronic receipt and requires no signature.<br>
            KKA Accounting Services &copy; <?php echo date('Y'); ?>
        </div>
    </div>

</body>
</html>