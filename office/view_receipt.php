<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user'])) {
    header("Location: ../Login.php");
    exit();
}

if (!isset($_GET['id'])) { 
    die("Error: Receipt Number is missing."); 
}

$receipt_no = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch Receipt, Invoice Service Name, and Client details
$sql = "SELECT r.*, i.service_name, cp.company_name, cp.address, cp.phone 
        FROM receipts r 
        JOIN invoices i ON r.invoice_no = i.invoice_no 
        JOIN client_profiles cp ON r.client_id = cp.client_id 
        WHERE r.receipt_no = '$receipt_no'";

$res = $conn->query($sql);

if (!$res || $res->num_rows === 0) {
    die("Receipt not found.");
}

$data = $res->fetch_assoc();
$total_received = (float)$data['amount_paid'];

// --- Currency Function ---
function getIndianCurrency(float $number) {
    $no = floor($number);
    $point = round($number - $no, 2) * 100;
    $hundred = null; $digits_1 = strlen($no); $i = 0; $str = array();
    $words = array('0' => '', '1' => 'one', '2' => 'two', '3' => 'three', '4' => 'four', '5' => 'five', '6' => 'six', '7' => 'seven', '8' => 'eight', '9' => 'nine', '10' => 'ten', '11' => 'eleven', '12' => 'twelve', '13' => 'thirteen', '14' => 'fourteen', '15' => 'fifteen', '16' => 'sixteen', '17' => 'seventeen', '18' => 'eighteen', '19' => 'nineteen', '20' => 'twenty', '30' => 'thirty', '40' => 'forty', '50' => 'fifty', '60' => 'sixty', '70' => 'seventy', '80' => 'eighty', '90' => 'ninety');
    $digits = array('', 'hundred', 'thousand', 'lakh', 'crore');
    while ($i < $digits_1) {
        $divider = ($i == 2) ? 10 : 100;
        $num = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($num) {
            $plural = (($counter = count($str)) && $num > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($num < 21) ? $words[$num] . " " . $digits[$counter] . $plural . " " . $hundred : $words[floor($num / 10) * 10] . " " . $words[$num % 10] . " " . $digits[$counter] . $plural . " " . $hundred;
        } else $str[] = null;
    }
    $str = array_reverse($str);
    $result = implode('', $str);
    return ucwords($result) . "Rupees Only";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt_<?php echo $receipt_no; ?></title>
    <style>
        @page { size: A4; margin: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; background: #f4f4f4; color: #333; }
        .receipt-container { background: white; width: 210mm; margin: 20px auto; padding: 20mm; box-sizing: border-box; position: relative; border: 1px solid #ddd; }
        .header-table { width: 100%; margin-bottom: 30px; border-bottom: 2px solid #0b3c74; padding-bottom: 10px; }
        .company-name { font-size: 24px; font-weight: bold; color: #0b3c74; }
        .receipt-label { font-size: 22px; font-weight: bold; color: #ff8c00; text-align: right; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .details-table td { padding: 8px 0; font-size: 14px; border-bottom: 1px solid #f9f9f9; }
        .label { font-weight: bold; color: #666; width: 180px; }
        .amount-section { background: #f8fafc; border: 2px dashed #0b3c74; padding: 20px; text-align: center; margin-top: 20px; }
        .amount-val { font-size: 28px; font-weight: 800; color: #0b3c74; }
        .footer-sig { margin-top: 60px; display: flex; justify-content: space-between; align-items: flex-end; }
        .print-btn { position: fixed; top: 20px; right: 20px; background: #0b3c74; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        @media print { .print-btn { display: none; } body { background: white; } .receipt-container { margin: 0; border: none; } }
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

<button class="print-btn" onclick="window.print()">Print Receipt</button>

<div class="receipt-container">
    <table class="header-table">
        <tr>
            <td>
                <div class="company-name">Karunesh Kumar & Associates</div>
                <div style="font-size: 12px; color: #555;">Patna, Bihar | GSTIN: 10DJZPK5889N1Z3</div>
            </td>
            <td class="receipt-label">PAYMENT RECEIPT</td>
        </tr>
    </table>

    <table class="details-table">
        <tr>
            <td class="label">Receipt Number</td>
            <td><strong>#<?php echo $data['receipt_no']; ?></strong></td>
        </tr>
        <tr>
            <td class="label">Date of Payment</td>
            <td><?php echo date('d F Y', strtotime($data['created_at'])); ?></td>
        </tr>
        <tr>
            <td class="label">Received From</td>
            <td><strong><?php echo strtoupper($data['company_name']); ?></strong></td>
        </tr>
        <tr>
            <td class="label">Against Invoice No.</td>
            <td>#<?php echo $data['invoice_no']; ?> (<?php echo $data['service_name']; ?>)</td>
        </tr>
        <tr>
            <td class="label">Payment Method</td>
            <td><?php echo $data['method']; ?> (<?php echo $data['payment_mode']; ?>)</td>
        </tr>
    </table>

    <div class="amount-section">
        <div style="font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 5px;">Total Amount Received</div>
        <div class="amount-val">₹<?php echo number_format($total_received, 2); ?></div>
        <div style="margin-top: 10px; font-size: 13px; font-style: italic;">
            (<?php echo getIndianCurrency($total_received); ?>)
        </div>
    </div>

    <div style="margin-top: 40px; font-size: 12px; color: #777; line-height: 1.6;">
        <strong>Note:</strong> This is a formal acknowledgement of the payment received. 
        For detailed tax breakdown and HSN/SAC codes, please refer to the original Tax Invoice <strong>#<?php echo $data['invoice_no']; ?></strong>.
    </div>

    <div class="footer-sig">
        <div style="font-size: 11px; color: #999;">
            Issued on: <?php echo date('d-m-Y H:i'); ?>
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
</div>

</body>
</html>