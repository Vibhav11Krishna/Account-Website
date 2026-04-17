<?php
include('../db.php');
header('Content-Type: application/json');

if (isset($_POST['client_id'])) {
    $cid = mysqli_real_escape_string($conn, $_POST['client_id']);

    // Precise calculation: Total Amount - Total Paid = Net Due
    // We use COALESCE to ensure if a value is missing, it counts as 0
    $query = "SELECT 
                SUM(CASE 
                    WHEN invoice_date < DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                    THEN (COALESCE(amount, 0) - COALESCE(paid_amount, 0)) 
                    ELSE 0 END) as previous_dues,
                SUM(CASE 
                    WHEN invoice_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                    THEN (COALESCE(amount, 0) - COALESCE(paid_amount, 0)) 
                    ELSE 0 END) as current_dues,
                SUM(COALESCE(amount, 0) - COALESCE(paid_amount, 0)) as total_dues
              FROM invoices 
              WHERE client_id = '$cid'";

    $res = $conn->query($query);
    $summary = $res->fetch_assoc();

    // Fetch individual pending invoices with clear math
    $inv_query = "SELECT invoice_no, 
                         (COALESCE(amount, 0) - COALESCE(paid_amount, 0)) as pending, 
                         invoice_date 
                  FROM invoices 
                  WHERE client_id = '$cid' 
                  AND (COALESCE(amount, 0) - COALESCE(paid_amount, 0)) > 0.01"; // Ignoring tiny fractions
    
    $inv_res = $conn->query($inv_query);
    
    $details = [];
    while($row = $inv_res->fetch_assoc()) {
        $details[] = $row;
    }

    echo json_encode([
        'summary' => $summary,
        'details' => $details
    ]);
    exit;
}