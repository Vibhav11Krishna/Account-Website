<?php
session_start();
include('../db.php');

if(!isset($_SESSION['user'])) { header("Location: ../Login.php"); exit(); }

$inv_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

// Fetch Invoice AND join with Quotation for tax details
$sql = "SELECT i.*, q.amount as base_price, q.tax_rate 
        FROM invoices i
        LEFT JOIN quotations q ON i.service_name = q.service_name 
        WHERE i.id = '$inv_id' AND i.client_id = (SELECT identifier FROM users WHERE id = '".$_SESSION['user']['id']."')
        LIMIT 1";

$get_data = $conn->query($sql);
$data = $get_data->fetch_assoc();

if(!$data) { die("Payment details not found."); }

// Calculations
$base_amount = (float)$data['base_price'];
$tax_rate = (float)($data['tax_rate'] ?? 18); // Default to 18% if not set in quotation
$tax_amount = ($base_amount * $tax_rate) / 100;
$total_payable = $base_amount + $tax_amount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Secure Payment Gateway | KKA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f1f5f9; font-family: 'Inter', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .pay-card { background: white; width: 420px; padding: 35px; border-radius: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); text-align: center; border: 1px solid #e2e8f0; }
        .logo { color: #0b3c74; font-weight: 800; font-size: 26px; margin-bottom: 25px; letter-spacing: -1px; }
        
        .amount-box { background: #f8fafc; padding: 20px; border-radius: 16px; margin-bottom: 25px; border: 1px solid #e2e8f0; text-align: left; }
        .tax-row { display: flex; justify-content: space-between; font-size: 13px; color: #64748b; margin-bottom: 8px; }
        .total-row { display: flex; justify-content: space-between; margin-top: 10px; padding-top: 10px; border-top: 2px dashed #cbd5e1; color: #0b3c74; font-size: 18px; }
        
        .method-list { text-align: left; margin-bottom: 25px; }
        .method { display: flex; align-items: center; gap: 15px; padding: 14px; border: 1.5px solid #e2e8f0; border-radius: 12px; margin-bottom: 12px; cursor: pointer; transition: 0.2s; font-size: 14px; font-weight: 600; color: #475569; }
        .method:hover { border-color: #ff8c00; background: #fffaf5; }
        .method.active { border-color: #ff8c00; background: #fffaf5; box-shadow: 0 4px 12px rgba(255,140,0,0.1); color: #0b3c74; }

        .btn-complete { background: #22c55e; color: white; border: none; width: 100%; padding: 18px; border-radius: 12px; font-weight: 800; font-size: 16px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.2); }
        .btn-complete:hover { background: #16a34a; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(34, 197, 94, 0.3); }
        .secure-footer { margin-top: 25px; font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; justify-content: center; gap: 8px; }
    </style>
</head>
<body>

<div class="pay-card">
    <div class="logo">KKA <span style="color:#ff8c00;">PAY</span></div>
    <p style="color: #64748b; font-size: 14px; margin-top: -15px;">Secure checkout for Invoice <b>#<?php echo $data['invoice_no']; ?></b></p>
    
    <div class="amount-box">
        <div class="tax-row">
            <span>Base Service Fee</span>
            <span>₹<?php echo number_format($base_amount, 2); ?></span>
        </div>
        <div class="tax-row">
            <span>GST (<?php echo $tax_rate; ?>%)</span>
            <span>₹<?php echo number_format($tax_amount, 2); ?></span>
        </div>
        <div class="total-row">
            <strong>Total Payable</strong>
            <strong>₹<?php echo number_format($total_payable, 2); ?></strong>
        </div>
    </div>

    <div class="method-list">
        <div class="method active"><i class="fas fa-qrcode" style="color:#ff8c00; font-size: 18px;"></i> UPI / GPay / PhonePe</div>
        <div class="method"><i class="fas fa-university" style="color:#0b3c74; font-size: 18px;"></i> Net Banking</div>
        <div class="method"><i class="fas fa-credit-card" style="color:#64748b; font-size: 18px;"></i> Credit / Debit Cards</div>
    </div>

    <form action="complete-payment.php" method="POST">
        <input type="hidden" name="invoice_id" value="<?php echo $data['id']; ?>">
        <input type="hidden" name="final_amount" value="<?php echo $total_payable; ?>">
        
        <button type="submit" class="btn-complete">
            <i class="fas fa-lock"></i> Pay ₹<?php echo number_format($total_payable, 2); ?>
        </button>
    </form>

    <div class="secure-footer">
        <i class="fas fa-shield-alt" style="color:#22c55e;"></i> PCI-DSS Compliant | 256-Bit SSL
    </div>
</div>

</body>
</html>