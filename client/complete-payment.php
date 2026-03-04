<?php
session_start();
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['invoice_id'])) {
    $inv_id = mysqli_real_escape_string($conn, $_POST['invoice_id']);
    
    // 1. GET THE INVOICE DATA 
    // We need this to fill the receipt table correctly
    $get_inv = $conn->query("SELECT * FROM invoices WHERE id = '$inv_id'");
    $inv = $get_inv->fetch_assoc();

    if($inv) {
        // Prevent double payment/duplicate receipts
        if($inv['status'] == 'Paid') {
            header("Location: my-invoices.php?msg=Already Paid");
            exit();
        }

        $inv_no = $inv['invoice_no'];
        $amt = $inv['amount'];
        $cid = $inv['client_id'];

        // 2. GENERATE UNIQUE RECEIPT NUMBER
        // Using timestamp + ID to ensure it's always unique
        $rcp_no = "RCP-" . date('Ymd') . "-" . $inv_id; 

        // 3. INSERT INTO RECEIPTS TABLE (Based on your exact column names)
        $sql_rcp = "INSERT INTO receipts (receipt_no, invoice_no, client_id, amount_paid, payment_mode) 
                    VALUES ('$rcp_no', '$inv_no', '$cid', '$amt', 'Online Payment')";
        
        if($conn->query($sql_rcp)) {
            // 4. UPDATE INVOICE STATUS
            $conn->query("UPDATE invoices SET status = 'Paid' WHERE id = '$inv_id'");
            
            // Redirect with success flags
            header("Location: my-invoices.php?payment=success&rcp=$rcp_no");
            exit();
        } else {
            // This will trigger if there's an error in the receipts table specifically
            echo "Receipt Table Error: " . $conn->error;
        }
    } else {
        echo "Error: Invoice not found.";
    }
} else {
    header("Location: my-invoices.php");
}
?>