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

// Fetch Master Quotation Data
$sql = "SELECT q.*, cp.company_name, cp.address, cp.phone, cp.pan_no, cp.gst_no, cp.business_email as email 
        FROM quotations q 
        JOIN client_profiles cp ON q.client_id = cp.client_id 
        WHERE q.id = '$quote_id' $client_restriction";

$res = $conn->query($sql);

if (!$res || $res->num_rows === 0) {
    die("Quotation not found.");
}

$data = $res->fetch_assoc();

// --- NEW: Fetch Multiple Items ---
$items_res = $conn->query("SELECT * FROM quotation_items WHERE quotation_id = '$quote_id'");
$items = [];
if ($items_res && $items_res->num_rows > 0) {
    while ($row = $items_res->fetch_assoc()) {
        $items[] = $row;
    }
} else {
    // Fallback for old single-service quotations
    $items[] = [
        'service_name' => $data['service_name'],
        'amount' => $data['amount'],
        'tax_rate' => $data['tax_rate'] ?? 0,
        'tax_value' => ($data['amount'] * ($data['tax_rate'] ?? 0)) / 100
    ];
}

// --- Totals Calculation ---
$total_taxable = 0;
$total_tax_amt = 0;
foreach ($items as $item) {
    $total_taxable += (float)$item['amount'];
    $total_tax_amt += (float)$item['tax_value'];
}
$grand_total = $total_taxable + $total_tax_amt;

// Determine Tax Type
$is_interstate = (isset($data['client_state']) && $data['client_state'] !== 'Bihar');
$tax_type = $is_interstate ? 'IGST' : 'CGST+SGST';

// --- Currency Function ---
function getIndianCurrency(float $number)
{
    $no = floor($number);
    $point = round($number - $no, 2) * 100;
    $hundred = null;
    $digits_length = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
        20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
    );
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    while ($i < $digits_length) {
        $divider = ($i == 2) ? 10 : 100;
        $num = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($num) {
            $plural = (($counter = count($str)) && $num > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str[] = ($num < 21) ? $words[$num] . ' ' . $digits[$counter] . $plural . ' ' . $hundred : $words[floor($num / 10) * 10] . ' ' . $words[$num % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
        } else $str[] = null;
    }
    $main_string = implode('', array_reverse($str));
    $paise_string = ($point > 0) ? " and " . ($words[floor($point / 10) * 10] . " " . $words[$point % 10]) . " Paise" : "";
    return ($main_string ? $main_string . 'Rupees' : '') . $paise_string . ' Only';
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
        .page { background: white; width: 210mm; min-height: 297mm; margin: auto; padding: 15mm; box-sizing: border-box; position: relative; display: flex; flex-direction: column; }
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
        .summary-table { width: 50%; border-collapse: collapse; }
        .summary-table td { padding: 8px; border-bottom: 1px solid #eee; font-size: 12px; }
        .total-row { background: #f4f4f4; font-weight: bold; font-size: 14px !important; border-top: 2px solid #333 !important; }
        .terms-section { margin-top: 30px; font-size: 11px; line-height: 1.5; border-top: 1px solid #eee; padding-top: 15px; }
        .footer-container { display: flex; justify-content: space-between; align-items: flex-end; padding-top: 40px; margin-top: auto; }
        .footer-img { width: 110px; height: auto; display: block; margin: 5px auto; }
        .stamp-img { width: 90px; height: auto; opacity: 0.9; }
        .print-btn { position: fixed; top: 20px; right: 20px; background: #ff8c00; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-weight: bold; z-index: 100; }
        @media print { .print-btn { display: none; } body { background: white; } .page { box-shadow: none; margin: 0; width: 100%; } }
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
                            <span class="label">GST IN</span> <?php echo !empty($data['gst_no']) ? $data['gst_no'] : 'N/A'; ?><br>
                            <span class="label">State</span> <?php echo $data['client_state'] ?? 'N/A'; ?>
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
                    <th width="10%">HSN/SAC</th>
                    <th width="15%">Taxable Amt</th>
                    <th width="10%">GST Rate</th>
                    <th width="15%">Sub Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $index => $item): 
                    $line_total = (float)$item['amount'] + (float)$item['tax_value'];
                ?>
                <tr>
                    <td align="center"><?php echo $index + 1; ?></td>
                    <td><strong><?php echo $item['service_name']; ?></strong></td>
                    <td>9982</td>
                    <td>₹<?php echo number_format($item['amount'], 2); ?></td>
                    <td><?php echo (float)$item['tax_rate']; ?>%</td>
                    <td align="right"><strong>₹<?php echo number_format($line_total, 2); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="summary-container">
            <table class="summary-table">
                <tr>
                    <td>Taxable Value (Sub-Total) :</td>
                    <td align="right">₹<?php echo number_format($total_taxable, 2); ?></td>
                </tr>

                <?php if ($total_tax_amt > 0): ?>
                    <?php if (!$is_interstate): ?>
                        <tr>
                            <td>CGST Output :</td>
                            <td align="right">₹<?php echo number_format($total_tax_amt / 2, 2); ?></td>
                        </tr>
                        <tr>
                            <td>SGST Output :</td>
                            <td align="right">₹<?php echo number_format($total_tax_amt / 2, 2); ?></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td>IGST Output :</td>
                            <td align="right">₹<?php echo number_format($total_tax_amt, 2); ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endif; ?>

                <tr class="total-row">
                    <td><strong>Grand Total (Inc. Tax) :</strong></td>
                    <td align="right"><strong>₹<?php echo number_format($grand_total, 2); ?></strong></td>
                </tr>
            </table>
        </div>

        <div style="margin-top: 15px; font-size: 12px; border-left: 3px solid #333; padding-left: 10px;">
            <strong>Amount in words:</strong><br>
            <span style="text-transform: uppercase;"><?php echo getIndianCurrency($grand_total); ?></span>
        </div>

        <div class="terms-section">
            <strong>Terms & Conditions:</strong><br>
            1. Validity: This quotation is valid for 30 days from the date of issue.<br>
            2. Payment: All Invoices are payable within 7 days from the date of the invoice.<br>
            3. Jurisdiction: Any dispute shall be subject to the jurisdiction of the Patna Courts only.
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