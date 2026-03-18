<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user'])) {
    header("Location: ../Login.php");
    exit();
}

if (!isset($_GET['inv_no'])) { 
    die("Error: Invoice Number is missing."); 
}

$inv_no = mysqli_real_escape_string($conn, $_GET['inv_no']);

// Fetch Detailed Invoice & Client Info
$sql = "SELECT i.*, cp.company_name, cp.address, cp.phone, cp.pan_no, cp.gst_no, cp.business_email as email 
        FROM invoices i 
        JOIN client_profiles cp ON i.client_id = cp.client_id 
        WHERE i.invoice_no = '$inv_no'";

$res = $conn->query($sql);

if (!$res || $res->num_rows === 0) {
    die("Invoice not found.");
}

$data = $res->fetch_assoc();

// --- LOGIC UPDATED TO MATCH NEW STORAGE FORMAT ---

// --- LOGIC UPDATED TO MATCH NEW STORAGE FORMAT ---

// 1. Get the Base Amount
$base      = (float)$data['amount']; 

// 2. Get Tax Amounts directly from DB
$cgst_amt  = (float)($data['cgst_amount'] ?? 0);
$sgst_amt  = (float)($data['sgst_amount'] ?? 0);
$igst_amt  = (float)($data['igst_amount'] ?? 0);
$tax_type  = $data['tax_type'] ?? 'CGST+SGST';

// FIX: If tax_rate is 0 in DB, calculate it on the fly for display
$tax_rate  = (float)($data['tax_rate'] ?? 0);
if ($tax_rate <= 0 && $base > 0) {
    $tax_rate = (($cgst_amt + $sgst_amt + $igst_amt) / $base) * 100;
}

// 3. Calculate Grand Total
$total     = $base + $cgst_amt + $sgst_amt + $igst_amt;

// 4. Calculate Balance
$paid      = (float)($data['paid_amount'] ?? 0); 
$balance   = $total - $paid;

function getIndianCurrency(float $number) {
    $no = floor($number);
    $point = round($number - $no, 2) * 100;
    $hundred = null;
    $digits_1 = strlen($no);
    $i = 0;
    $str = array();
    $words = array('0' => '', '1' => 'one', '2' => 'two',
    '3' => 'three', '4' => 'four', '5' => 'five', '6' => 'six',
    '7' => 'seven', '8' => 'eight', '9' => 'nine',
    '10' => 'ten', '11' => 'eleven', '12' => 'twelve',
    '13' => 'thirteen', '14' => 'fourteen',
    '15' => 'fifteen', '16' => 'sixteen', '17' => 'seventeen',
    '18' => 'eighteen', '19' => 'nineteen', '20' => 'twenty',
    '30' => 'thirty', '40' => 'forty', '50' => 'fifty',
    '60' => 'sixty', '70' => 'seventy',
    '80' => 'eighty', '90' => 'ninety');
    $digits = array('', 'hundred', 'thousand', 'lakh', 'crore');
    while ($i < $digits_1) {
     $divider = ($i == 2) ? 10 : 100;
     $number = floor($no % $divider);
     $no = floor($no / $divider);
     $i += ($divider == 10) ? 1 : 2;
     if ($number) {
        $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
        $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
        $str [] = ($number < 21) ? $words[$number] .
            " " . $digits[$counter] . $plural . " " . $hundred
            :
            $words[floor($number / 10) * 10]
            . " " . $words[$number % 10] . " "
            . $digits[$counter] . $plural . " " . $hundred;
     } else $str[] = null;
    }
    $str = array_reverse($str);
    $result = implode('', $str);
    $points = ($point) ?
    "." . $words[$point / 10] . " " . 
          $words[$point = $point % 10] : '';
    return ucwords($result) . "Rupees " . ($points ? "and " . $points . " Paise" : "") . " Only";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice_<?php echo $data['invoice_no']; ?></title>
    <style>
        .balance-row { color: #e11d48; font-weight: bold; }
        .remarks-box { 
            width: 50%; 
            font-size: 11px; 
            color: #555; 
            background: #fcfcfc; 
            padding: 10px; 
            border: 1px dashed #ccc; 
            border-radius: 5px; 
            text-align: left;
        }
        @page { size: A4; margin: 0; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; margin: 0; padding: 0; background: #525659; color: #333; }
        .page { background: white; width: 210mm; height: 297mm; margin: auto; padding: 15mm; box-sizing: border-box; display: flex; flex-direction: column; position: relative; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .header-left { font-size: 13px; line-height: 1.5; }
        .logo-img { width: 120px; height: auto; }
        .title { text-align: center; font-size: 22px; font-weight: bold; letter-spacing: 2px; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 5px; }
        .billing-grid { width: 100%; border-collapse: collapse; margin-bottom: 25px; table-layout: fixed; }
        .billing-grid th { text-align: left; font-size: 14px; border-bottom: 1px solid #333; padding: 8px 0; }
        .billing-grid td { padding: 12px 0; font-size: 12px; line-height: 1.6; vertical-align: top; }
        .label { font-weight: bold; color: #555; width: 75px; display: inline-block; }
        .main-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .main-table th { background: #000; color: #fff; padding: 10px 8px; font-size: 11px; text-align: left; border: 1px solid #333; }
        .main-table td { border: 1px solid #ccc; padding: 12px 8px; font-size: 12px; }
        .summary-container { display: flex; justify-content: space-between; align-items: flex-start; }
        .summary-table { width: 45%; border-collapse: collapse; }
        .summary-table td { padding: 6px; border-bottom: 1px solid #eee; font-size: 12px; }
        .total-row { background: #f4f4f4; font-weight: bold; font-size: 13px !important; border-top: 2px solid #333 !important; }
        .print-btn { position: fixed; top: 20px; right: 20px; background: #ff8c00; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-weight: bold; z-index: 100; }
        @media print { .print-btn { display: none; } body { background: white; } .page { box-shadow: none; margin: 0; width: 100%; height: 297mm; } }
        .footer-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 40px;
            margin-top: auto;
        }

        .footer-img {
            width: 110px;
            height: auto;
            display: block;
            margin: 5px auto;
        }

        .stamp-img {
            width: 90px;
            height: auto;
            opacity: 0.9;
        }

    </style>
</head>
<body>

<button class="print-btn" onclick="window.print()">Print Invoice</button>

<div class="page">
    <div class="header">
        <div class="header-left">
            <strong>Invoice No:</strong> <?php echo $data['invoice_no']; ?><br>
            <strong>Invoice Date:</strong> <?php echo date('d-m-Y', strtotime($data['created_at'])); ?><br>
            <strong>Status:</strong> <span style="color: <?php echo ($data['status'] == 'Paid') ? '#16a34a' : '#e11d48'; ?>"><?php echo strtoupper($data['status']); ?></span>
        </div>
        <div class="header-right">
            <img src="../assets/Cma.jpg" alt="Logo" class="logo-img">
        </div>
    </div>

    <div class="title">TAX INVOICE</div>

    <table class="billing-grid">
        <thead>
            <tr>
                <th width="50%">ISSUED BY</th>
                <th width="50%">BILLED TO</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>KARUNESH KUMAR & ASSOCIATES</strong><br>
                    2nd Floor, Shyam Market, Near Pillar No. 75,<br>
                    Bailey Road Sheikhpura, Patna, Bihar, 800014<br>
                    <div style="margin-top: 8px;">
                        <span class="label">Phone No.</span> +91 9097047484<br>
                        <span class="label">Email ID</span> karunesh.cma@outlook.com<br>
                        <span class="label">GST IN</span> 10DJZPK5889N1Z3<br>
                        <span class="label">PAN</span> DJZPK5889N
                    </div>
                </td>
                <td>
                    <strong><?php echo strtoupper($data['company_name']); ?></strong><br>
                    <?php echo $data['address']; ?><br>
                    <div style="margin-top: 8px;">
                        <span class="label">Phone No.</span> +91 <?php echo $data['phone']; ?><br>
                        <span class="label">Email ID</span> <?php echo !empty($data['email']) ? $data['email'] : 'N/A'; ?><br>
                        <span class="label">PAN</span> <?php echo !empty($data['pan_no']) ? $data['pan_no'] : 'N/A'; ?><br>
                        <span class="label">GST IN</span> <?php echo !empty($data['gst_no']) ? $data['gst_no'] : 'N/A'; ?>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th width="6%">#</th>
                <th width="44%">Service Description</th>
                <th width="10%">HSN</th>
                <th width="15%">Basic Amount</th>
                <th width="10%">GST Rate</th>
                <th width="15%">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td align="center">1</td>
                <td><strong><?php echo $data['service_name']; ?></strong></td>
                <td>9982</td>
                <td>₹<?php echo number_format($base, 2); ?></td>
                <td><?php echo $tax_rate; ?>%</td>
                <td align="right"><strong>₹<?php echo number_format($total, 2); ?></strong></td>
            </tr>
        </tbody>
    </table>

    <div class="summary-container">
        <div class="remarks-box">
            <strong>Payment History/Notes:</strong><br>
            <div style="white-space: pre-wrap; margin-top: 5px;">
                <?php echo !empty($data['remarks']) ? htmlspecialchars($data['remarks']) : 'No notes available.'; ?>
            </div>
        </div>

        <table class="summary-table">
            <tr>
                <td>Taxable Value:</td>
                <td align="right">₹<?php echo number_format($base, 2); ?></td>
            </tr>
            <?php if ($tax_type == 'IGST'): ?>
                <tr>
                    <td>IGST (<?php echo $tax_rate; ?>%):</td>
                    <td align="right">₹<?php echo number_format($igst_amt, 2); ?></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td>CGST (<?php echo $tax_rate/2; ?>%):</td>
                    <td align="right">₹<?php echo number_format($cgst_amt, 2); ?></td>
                </tr>
                <tr>
                    <td>SGST (<?php echo $tax_rate/2; ?>%):</td>
                    <td align="right">₹<?php echo number_format($sgst_amt, 2); ?></td>
                </tr>
            <?php endif; ?>
            <tr class="total-row">
                <td>Grand Total:</td>
                <td align="right">₹<?php echo number_format($total, 2); ?></td>
            </tr>
            <tr>
                <td style="color: #16a34a;">Amount Paid:</td>
                <td align="right" style="color: #16a34a;">₹<?php echo number_format($paid, 2); ?></td>
            </tr>
            <tr class="balance-row">
                <td>Balance Due:</td>
                <td align="right">₹<?php echo number_format($balance, 2); ?></td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 20px; font-size: 11px;">
        <?php if ($total > 0): ?>
            <div style="font-style: italic;">
                <strong>Grand Total (In Words):</strong> <?php echo getIndianCurrency($total); ?>
            </div>
        <?php endif; ?>
    </div>

     <div class="footer-container">
        <div class="stamp-area">
            <img src="../assets/kkstamp.png" alt="Stamp" class="stamp-img">
            <div style="font-size: 10px; font-weight: bold; margin-top: 5px; color: #777;">OFFICIAL STAMP</div>
        </div>

        <div class="signature-area" style="text-align: center; width: 250px;">
            <span style="font-size: 11px; font-weight: bold;"> KARUNESH KUMAR & ASSOCIATES</span>
            <img src="../assets/kksign.png" alt="Signature" class="footer-img">
            <strong style="font-size: 12px; border-top: 1px solid #333; display: block; padding-top: 5px;">Authorized Signatory</strong>
        </div>
    </div>
</div>
</body>
</html>