<?php
session_start();
include('../db.php');

if(!isset($_SESSION['user'])) { header("Location: ../Login.php"); exit(); }

$inv_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

// Fetch Invoice Details for display
$get_inv = $conn->query("SELECT * FROM invoices WHERE id = '$inv_id'");
$inv = $get_inv->fetch_assoc();

if(!$inv) { die("Invoice not found."); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Secure Payment Gateway | KKA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f1f5f9; font-family: 'Inter', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .pay-card { background: white; width: 400px; padding: 30px; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); text-align: center; }
        .logo { color: #0b3c74; font-weight: 800; font-size: 24px; margin-bottom: 20px; }
        .amount-box { background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 25px; border: 1px dashed #cbd5e1; }
        .amount-box h2 { margin: 0; color: #0b3c74; font-size: 28px; }
        .method-list { text-align: left; margin-bottom: 25px; }
        .method { display: flex; align-items: center; gap: 15px; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 10px; cursor: pointer; transition: 0.3s; }
        .method:hover { border-color: #ff8c00; background: #fffaf5; }
        .btn-complete { background: #22c55e; color: white; border: none; width: 100%; padding: 15px; border-radius: 12px; font-weight: bold; font-size: 16px; cursor: pointer; transition: 0.3s; }
        .btn-complete:hover { background: #16a34a; transform: translateY(-2px); }
        .secure-footer { margin-top: 20px; font-size: 12px; color: #94a3b8; display: flex; justify-content: center; align-items: center; gap: 5px; }
    </style>
</head>
<body>

<div class="pay-card">
    <div class="logo">KKA <span style="color:#ff8c00;">PAY</span></div>
    <p style="color: #64748b;">Payment for Invoice <b>#<?php echo $inv['invoice_no']; ?></b></p>
    
    <div class="amount-box">
        <span style="font-size: 12px; color: #64748b;">TOTAL AMOUNT</span>
        <h2>₹<?php echo number_format($inv['amount'], 2); ?></h2>
    </div>

    <div class="method-list">
        <div class="method"><i class="fas fa-university" style="color:#0b3c74;"></i> Net Banking</div>
        <div class="method"><i class="fab fa-google-pay" style="color:#4285F4;"></i> UPI / Google Pay</div>
        <div class="method" style="border-color:#ff8c00; background:#fffaf5;">
            <i class="fas fa-credit-card" style="color:#ff8c00;"></i> Debit / Credit Card
        </div>
    </div>

    <form action="complete-payment.php" method="POST">
        <input type="hidden" name="invoice_id" value="<?php echo $inv['id']; ?>">
        <button type="submit" class="btn-complete">
            <i class="fas fa-shield-alt"></i> Complete Payment
        </button>
    </form>

    <div class="secure-footer">
        <i class="fas fa-lock"></i> SSL Secured | 256-bit Encryption
    </div>
</div>

</body>
</html>