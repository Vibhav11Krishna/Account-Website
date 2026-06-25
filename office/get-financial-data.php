<?php
// Make sure this path points to your actual database connection file
include('../db.php'); 

$type = $_GET['type'] ?? 'daily';

if ($type == 'daily') {
    $sql = "SELECT invoice_date as label, SUM(amount + cgst_amount + sgst_amount + igst_amount) as total 
            FROM invoices GROUP BY invoice_date ORDER BY invoice_date DESC LIMIT 30";
} elseif ($type == 'weekly') {
    $sql = "SELECT CONCAT('Week ', WEEK(invoice_date), ', ', YEAR(invoice_date)) as label, 
            SUM(amount + cgst_amount + sgst_amount + igst_amount) as total 
            FROM invoices GROUP BY YEAR(invoice_date), WEEK(invoice_date) ORDER BY invoice_date DESC";
} else {
    $sql = "SELECT DATE_FORMAT(invoice_date, '%M %Y') as label, 
            SUM(amount + cgst_amount + sgst_amount + igst_amount) as total 
            FROM invoices GROUP BY YEAR(invoice_date), MONTH(invoice_date) ORDER BY invoice_date DESC";
}

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
   while($row = $result->fetch_assoc()) {
    echo "<tr style='transition: 0.2s;'>
            <td style='padding:12px 15px; border-bottom:1px solid #f1f5f9; color:#f97316; font-weight:700;'>
                {$row['label']}
            </td>
            <td style='padding:12px 15px; border-bottom:1px solid #f1f5f9; text-align:right; color:#1e40af; font-weight:700;'>
                ₹" . number_format($row['total'], 2) . "
            </td>
          </tr>";
}
} else {
    echo "<tr><td colspan='2' style='padding:20px; text-align:center;'>No data found.</td></tr>";
}
?>