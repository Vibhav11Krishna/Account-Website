<?php
session_start();
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['invoice_id'])) {
    $inv_id = mysqli_real_escape_string($conn, $_POST['invoice_id']);
    
    // 1. Update Invoice status to 'Paid'
    $update_inv = $conn->query("UPDATE invoices SET status = 'Paid' WHERE id = '$inv_id'");
    
    if($update_inv) {
        // Optional: Create a Receipt entry here if you have a receipts table
        // $conn->query("INSERT INTO receipts ...");

        // Redirect with a success flag
        header("Location: my-invoices.php?payment=success");
        exit();
    } else {
        echo "Error updating payment: " . $conn->error;
    }
} else {
    header("Location: my-invoices.php");
}
?>