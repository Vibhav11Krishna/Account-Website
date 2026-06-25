<?php
session_start();
require_once('../db.php');
$cid = $_SESSION['user']['identifier'];
$type = $_GET['type'] ?? '7days';

if ($type == 'monthly_breakdown') {
    // Groups by month and year (e.g., Jan 2026, Feb 2026)
    $sql = "SELECT DATE_FORMAT(invoice_date, '%M %Y') as month_label, 
            SUM(amount + cgst_amount + sgst_amount + igst_amount) as total 
            FROM invoices 
            WHERE client_id = '$cid' 
            GROUP BY YEAR(invoice_date), MONTH(invoice_date) 
            ORDER BY invoice_date DESC";
} else {
    // Keep your existing 7-day logic
    $sql = "SELECT invoice_date as month_label, SUM(amount + cgst_amount + sgst_amount + igst_amount) as total 
            FROM invoices WHERE client_id = '$cid' 
            AND invoice_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
            GROUP BY invoice_date ORDER BY invoice_date DESC";
}

$res = $conn->query($sql);

if ($res && $res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        // If it's monthly, display the month name, otherwise display the date
        $label = ($type == 'monthly_breakdown') ? $row['month_label'] : date('d M, Y', strtotime($row['month_label']));
        
        echo "<tr>
                <td style='padding:12px; border-bottom:1px solid #f1f5f9; color:#0b3c74; font-weight:700;'>".$label."</td>
                <td style='padding:12px; border-bottom:1px solid #f1f5f9; text-align:right; font-weight:800; color:#ff8c00;'>₹".number_format($row['total'], 2)."</td>
              </tr>";
    }
}
?>