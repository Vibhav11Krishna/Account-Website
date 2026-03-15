<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user'])) {
    header("Location: ../Login.php");
    exit();
}

$user = $_SESSION['user'];
$user_role = $user['role'];

if (!isset($_GET['id'])) { 
    die("Error: Quotation ID is missing."); 
}

$quote_id = mysqli_real_escape_string($conn, $_GET['id']);

$client_restriction = ($user_role === 'client') ? " AND q.client_id = '" . $user['identifier'] . "'" : "";

// Corrected query with correct column name alias
$sql = "SELECT q.*, cp.company_name, cp.address, cp.phone, cp.pan_no, cp.gst_no, cp.business_email as email 
        FROM quotations q 
        JOIN client_profiles cp ON q.client_id = cp.client_id 
        WHERE q.id = '$quote_id' $client_restriction";

// FIX: Actually execute the query and assign it to $res
$res = $conn->query($sql);

if (!$res || $res->num_rows === 0) {
    die("Quotation not found.");
}

$data = $res->fetch_assoc();

$base = (float)$data['amount'];
$tax_rate = (float)($data['tax_rate'] ?? 18);
$tax_amt = ($base * $tax_rate) / 100;
$half_tax = $tax_amt / 2; 
$total = $base + $tax_amt;

function getIndianCurrency(float $number) {
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $hundred = null; $digits_length = strlen($no); $i = 0; $str = array();
    $words = array(0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety');
    $digits = array('', 'Hundred','Thousand','Lakh', 'Crore');
    while( $i < $digits_length ) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number].' '. $digits[$counter]. $plural.' '.$hundred:$words[floor($number / 10) * 10].' '.$words[$number % 10].' '.$digits[$counter].$plural.' '.$hundred;
        } else $str[] = null;
    }
    $res = implode('', array_reverse($str));
    return ($res ? $res . 'Rupees Only' : '');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quotation_<?php echo $data['quote_no']; ?></title>
    <style>
        @page { size: A4; margin: 0; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; margin: 0; padding: 0; background: #525659; color: #333; }

        .page { 
            background: white; 
            width: 210mm; 
            height: 297mm; 
            margin: auto; 
            padding: 15mm; 
            box-sizing: border-box; 
            position: relative;
            display: flex;
            flex-direction: column;
        }
        
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .header-left { font-size: 13px; line-height: 1.5; }
        .logo-img { width: 100px; height: auto; }
        .title { text-align: center; font-size: 22px; font-weight: bold; letter-spacing: 2px; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 5px; }

        .billing-grid { width: 100%; border-collapse: collapse; margin-bottom: 25px; table-layout: fixed; }
        .billing-grid th { text-align: left; font-size: 14px; border-bottom: 1px solid #333; padding: 8px 0; }
        .billing-grid td { padding: 12px 0; font-size: 12px; line-height: 1.6; vertical-align: top; }
        
        .label { font-weight: bold; color: #555; width: 75px; display: inline-block; }

        .main-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .main-table th { background: #000; color: #fff; padding: 10px 8px; font-size: 11px; text-align: left; border: 1px solid #333; }
        .main-table td { border: 1px solid #ccc; padding: 12px 8px; font-size: 12px; }

        .summary-container { display: flex; justify-content: flex-end; }
        .summary-table { width: 38%; border-collapse: collapse; }
        .summary-table td { padding: 6px; border-bottom: 1px solid #eee; font-size: 12px; }
        .total-row { background: #f4f4f4; font-weight: bold; font-size: 13px !important; border-top: 2px solid #333 !important; }

        .terms-section { margin-top: auto; font-size: 11px; line-height: 1.5; padding-bottom: 20px; border-top: 1px solid #eee; padding-top: 15px; }
        
        .footer-container { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-end; 
            padding-top: 10px;
        }
        .stamp-area { text-align: left; width: 150px; }
        .signature-area { text-align: center; width: 250px; }
        .footer-img { width: 110px; height: auto; display: block; margin: 5px auto; }
        .stamp-img { width: 90px; height: auto; opacity: 0.9; }

        .print-btn { position: fixed; top: 20px; right: 20px; background: #ff8c00; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-weight: bold; z-index: 100; }
        
        @media print { 
            .print-btn { display: none; } 
            body { background: white; } 
            .page { box-shadow: none; margin: 0; width: 100%; height: 297mm; } 
        }
    </style>
</head>
<body>

<button class="print-btn" onclick="window.print()">Print Quotation</button>

<div class="page">
    <div class="header">
        <div class="header-left">
            <strong>Quotation No:</strong> <?php echo $data['quote_no']; ?><br>
            <strong>Quotation Date:</strong> <?php echo date('d-m-Y', strtotime($data['created_at'])); ?>
        </div>
        <div class="header-right">
            <img src="../assets/Cma.jpg" alt="Logo" class="logo-img">
        </div>
    </div>

    <div class="title">QUOTATION</div>

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
                    <strong style="font-size: 13px; color: #000;">KARUNESH KUMAR & ASSOCIATES</strong><br>
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
                    <strong style="font-size: 13px; color: #000;"><?php echo strtoupper($data['company_name']); ?></strong><br>
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
                <th width="15%">Amount</th>
                <th width="10%">GST Rate</th>
                <th width="15%">Sub Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td align="center">1</td>
                <td><strong><?php echo $data['service_name']; ?></strong></td>
                <td>9982</td>
                <td>₹<?php echo number_format($base, 2); ?></td>
                <td><?php echo $tax_rate; ?>%</td>
                <td align="right"><strong>₹<?php echo number_format($base, 2); ?></strong></td>
            </tr>
        </tbody>
    </table>

    <div class="summary-container">
        <table class="summary-table">
            <tr>
                <td>Sub-Total :</td>
                <td align="right">₹<?php echo number_format($base, 2); ?></td>
            </tr>
            <tr>
                <td>CGST Output :</td>
                <td align="right">₹<?php echo number_format($half_tax, 2); ?></td>
            </tr>
            <tr>
                <td>SGST Output :</td>
                <td align="right">₹<?php echo number_format($half_tax, 2); ?></td>
            </tr>
            <tr>
                <td>Discount :</td>
                <td align="right">₹0.00</td>
            </tr>
            <tr class="total-row">
                <td><strong>Grand Total :</strong></td>
                <td align="right"><strong>₹<?php echo number_format($total, 2); ?></strong></td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 15px; font-size: 12px; font-style: italic;">
        <strong>Amount in words:</strong> <?php echo getIndianCurrency($total); ?>
    </div>

    <div class="terms-section">
        <strong>Terms & Conditions:</strong><br>
        1. All Invoices Are Payable Within 7 Days From The Date Of The Invoice.<br>
        2. Delayed Payments Shall Attract Interest At The Rate Of 1.5% Per Month.<br>
        3. Any Dispute Shall Be Subject To The Jurisdiction Of The Patna Courts Only.
    </div>

    <div class="footer-container">
        <div class="stamp-area">
            <img src="../assets/Cma.jpg" alt="Stamp" class="stamp-img">
            <div style="font-size: 10px; font-weight: bold; margin-top: 5px; color: #777;">OFFICIAL STAMP</div>
        </div>

        <div class="signature-area">
            <span style="font-size: 11px; font-weight: bold;">For KARUNESH KUMAR & ASSOCIATES</span>
            <img src="../assets/Cma.jpg" alt="Signature" class="footer-img">
            <strong style="font-size: 12px; border-top: 1px solid #333; display: block; padding-top: 5px;">Authorized Signatory</strong>
        </div>
    </div>
</div>

</body>
</html>