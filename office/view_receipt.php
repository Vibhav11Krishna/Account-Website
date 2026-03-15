<?php
session_start();
include('../db.php');

// 1. SECURITY & DATA CHECK
if(!isset($_SESSION['user'])) { die("Please login first."); }
if(!isset($_GET['id'])) { die("Receipt ID is missing."); }

$rcp_id = mysqli_real_escape_string($conn, $_GET['id']);

// 2. FETCH COMPLETE DATA 
$sql = "SELECT r.*, i.service_name, 
               q.amount as base_amount, q.tax_rate,
               cp.company_name, cp.address, cp.phone, cp.pan_no, cp.gst_no, cp.business_email
        FROM receipts r 
        LEFT JOIN invoices i ON r.invoice_no = i.invoice_no 
        LEFT JOIN quotations q ON i.service_name = q.service_name
        LEFT JOIN client_profiles cp ON r.client_id = cp.client_id 
        WHERE r.receipt_no = '$rcp_id' LIMIT 1";

$result = $conn->query($sql);
$data = $result->fetch_assoc();

if(!$data) { die("Receipt not found."); }

// 3. CALCULATION LOGIC
$base = (float)($data['base_amount'] ?? 0);
$tax_rate = (float)($data['tax_rate'] ?? 18); 
$tax_amt = ($base * $tax_rate) / 100;
$half_tax = $tax_amt / 2; 
$total = $base + $tax_amt;

// Function for Currency to Words
function getIndianCurrency(float $number) {
    $no = floor($number);
    $point = round($number - $no, 2) * 100;
    $hundred = null;
    $digits_1 = strlen($no);
    $i = 0;
    $str = array();
    $words = array('0' => '', '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six', '7' => 'Seven', '8' => 'Eight', '9' => 'Nine', '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve', '13' => 'Thirteen', '14' => 'Fourteen', '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen', '18' => 'Eighteen', '19' => 'Nineteen', '20' => 'Twenty', '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty', '60' => 'Sixty', '70' => 'Seventy', '80' => 'Eighty', '90' => 'Ninety');
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    while ($i < $digits_1) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number] . " " . $digits[$counter] . $plural . " " . $hundred : $words[floor($number / 10) * 10] . " " . $words[$number % 10] . " " . $digits[$counter] . $plural . " " . $hundred;
        } else $str[] = null;
    }
    $str = array_reverse($str);
    $result = implode('', $str);
    return ($result ? $result . 'Rupees Only' : 'Zero Rupees Only');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt_<?php echo $rcp_id; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; padding: 20px; color: #1e293b; }
        .receipt-card { background: white; max-width: 850px; margin: auto; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); position: relative; border: 1px solid #e2e8f0; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #166534; padding-bottom: 20px; margin-bottom: 30px; }
        .brand h1 { color: #166534; margin: 0; font-size: 24px; text-transform: uppercase; }
        .brand p { margin: 2px 0; font-size: 12px; color: #64748b; }
        .title-bar { text-align: center; background: #f0fdf4; color: #166534; padding: 10px; font-weight: bold; font-size: 18px; margin-bottom: 30px; border-radius: 4px; border: 1px solid #bbf7d0; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 30px; font-size: 13px; }
        .info-box h4 { margin: 0 0 10px 0; color: #166534; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; text-transform: uppercase; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #166534; color: white; padding: 12px; text-align: left; font-size: 12px; }
        td { padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .summary-wrapper { display: flex; justify-content: flex-end; margin-top: 20px; }
        .summary-table { width: 300px; }
        .summary-table td { padding: 5px 12px; border: none; }
        .total-row { background: #166534; color: white; font-weight: bold; border-radius: 4px; }
        .total-row td { padding: 10px 12px; }
        .paid-stamp { position: absolute; top: 150px; right: 80px; border: 5px double #166534; color: #166534; padding: 10px 30px; font-weight: 900; font-size: 40px; transform: rotate(-20deg); border-radius: 12px; opacity: 0.1; pointer-events: none; }
        .no-print-btn { background: #166534; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; margin-bottom: 20px; display: block; margin-left: auto; margin-right: auto; }
        @media print { .no-print-btn { display: none; } body { padding: 0; background: white; } .receipt-card { box-shadow: none; border: none; width: 100%; max-width: 100%; } }
    </style>
</head>
<body>

    <button class="no-print-btn" onclick="window.print()">
        <i class="fas fa-print"></i> Print Official Receipt
    </button>

    <div class="receipt-card">
        <div class="paid-stamp">PAID</div>
        
        <div class="header">
            <div class="brand">
                <h1>KARUNESH KUMAR & ASSOCIATES</h1>
                <p>2nd Floor, Shyam Market, Patna, Bihar, 800014</p>
                <p>GSTIN: 10DJZPK5889N1Z3 | PAN: DJZPK5889N</p>
            </div>
            <div style="text-align: right; font-size: 13px;">
                <p><strong>Receipt #:</strong> <?php echo $data['receipt_no']; ?></p>
                <p><strong>Date:</strong> <?php echo date('d-m-Y', strtotime($data['created_at'])); ?></p>
                <p><strong>Invoice Ref:</strong> #<?php echo $data['invoice_no']; ?></p>
            </div>
        </div>

        <div class="title-bar">PAYMENT RECEIPT</div>

        <div class="info-grid">
            <div class="info-box">
                <h4>Received From</h4>
                <strong><?php echo strtoupper($data['company_name'] ?? 'Walk-in Client'); ?></strong><br>
                <?php echo $data['address'] ?? 'N/A'; ?><br>
                GSTIN: <?php echo $data['gst_no'] ?: 'N/A'; ?>
            </div>
            <div class="info-box">
                <h4>Payment Details</h4>
                <p><strong>Payment Mode:</strong> <?php echo $data['payment_mode'] ?? 'N/A'; ?></p>
                <p><strong>Transaction ID:</strong> <?php echo $data['transaction_id'] ?? 'N/A'; ?></p>
                <p><strong>Status:</strong> Success / Completed</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: center;">HSN</th>
                    <th style="text-align: right;">Base Amount</th>
                    <th style="text-align: right;">GST (<?php echo $tax_rate; ?>%)</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?php echo $data['service_name'] ?? 'Professional Services'; ?></strong></td>
                    <td style="text-align: center;">9982</td>
                    <td style="text-align: right;">₹<?php echo number_format($base, 2); ?></td>
                    <td style="text-align: right;">₹<?php echo number_format($tax_amt, 2); ?></td>
                    <td style="text-align: right;"><strong>₹<?php echo number_format($total, 2); ?></strong></td>
                </tr>
            </tbody>
        </table>

        <div class="summary-wrapper">
            <table class="summary-table">
                <tr>
                    <td>Sub-Total:</td>
                    <td style="text-align: right;">₹<?php echo number_format($base, 2); ?></td>
                </tr>
                <tr>
                    <td>CGST (<?php echo $tax_rate/2; ?>%):</td>
                    <td style="text-align: right;">₹<?php echo number_format($half_tax, 2); ?></td>
                </tr>
                <tr>
                    <td>SGST (<?php echo $tax_rate/2; ?>%):</td>
                    <td style="text-align: right;">₹<?php echo number_format($half_tax, 2); ?></td>
                </tr>
                <tr class="total-row">
                    <td><strong>Total Paid:</strong></td>
                    <td style="text-align: right;"><strong>₹<?php echo number_format($total, 2); ?></strong></td>
                </tr>
            </table>
        </div>

        <div style="margin-top: 20px; font-size: 12px; font-style: italic; color: #475569;">
            <strong>Amount in words:</strong> <?php echo getIndianCurrency($total); ?>
        </div>

        <div style="margin-top: 50px; display: flex; justify-content: space-between; align-items: flex-end;">
            <div style="font-size: 11px; color: #94a3b8;">
                * This is a computer generated receipt. No signature required.
            </div>
            <div style="text-align: center;">
                <div style="height: 40px;"></div>
                <div style="border-top: 1px solid #1e293b; width: 200px; font-size: 12px; padding-top: 5px; font-weight: bold;">
                    Authorized Signatory
                </div>
            </div>
        </div>
    </div>
</body>
</html>