<?php
session_start();
require_once('../db.php');
$cid = $_SESSION['user']['identifier'];
$type = $_GET['type'] ?? 'date';

if ($type == 'mode') {
    // Breakdown by payment mode (e.g., UPI, Cash)
    $sql = "SELECT payment_mode as label, SUM(amount_paid) as total FROM receipts WHERE client_id = '$cid' GROUP BY payment_mode";
} else {
    // Breakdown by Date
    $sql = "SELECT created_at as label, amount_paid as total FROM receipts WHERE client_id = '$cid' ORDER BY created_at DESC";
}

$res = $conn->query($sql);
while($row = $res->fetch_assoc()) {
    $label = ($type == 'date') ? date('d M Y', strtotime($row['label'])) : $row['label'];
    echo "<tr>
            <td style='padding:10px; border-bottom:1px solid #f1f5f9;'>{$label}</td>
            <td style='padding:10px; border-bottom:1px solid #f1f5f9; text-align:right; font-weight:700;'>₹".number_format($row['total'], 2)."</td>
          </tr>";
}
?>