<?php
session_start();
include('../db.php');

if (isset($_GET['quote_id'])) {
    $quote_id = mysqli_real_escape_string($conn, $_GET['quote_id']);

    // 1. Fetch Quotation Master Data
    $q_res = $conn->query("SELECT * FROM quotations WHERE id = '$quote_id'");
    $q_data = $q_res->fetch_assoc();

    if ($q_data) {
        $client_id = $q_data['client_id'];
        $base_amt = $q_data['amount'];
        $tax_type = $q_data['tax_type'];
        $total_amt = $q_data['total_amount'];
        $today = date('Y-m-d H:i:s');
        $due_date = date('Y-m-d', strtotime('+7 days'));

        // Generate Invoice Number (Matching your INV-YEAR-000 format)
        $count_res = $conn->query("SELECT id FROM invoices");
        $next_id = ($count_res->num_rows > 0) ? $count_res->num_rows + 1 : 1;
        $inv_no = "INV-" . date('Y') . "-" . str_pad($next_id, 3, "0", STR_PAD_LEFT);

        // Calculate Tax Breakdown
        $tax_total = $total_amt - $base_amt;
        $cgst = ($tax_type == 'CGST+SGST') ? ($tax_total / 2) : 0;
        $sgst = ($tax_type == 'CGST+SGST') ? ($tax_total / 2) : 0;
        $igst = ($tax_type == 'IGST') ? $tax_total : 0;

        // 2. Insert into 'invoices' Master Table
        $sql_master = "INSERT INTO invoices (
            invoice_no, client_id, service_name, amount, 
            cgst_amount, sgst_amount, igst_amount, 
            tax_type, due_date, status, created_at
        ) VALUES (
            '$inv_no', '$client_id', 'Multiple Services', '$base_amt', 
            '$cgst', '$sgst', '$igst', 
            '$tax_type', '$due_date', 'Unpaid', '$today'
        )";

        if ($conn->query($sql_master)) {
            $new_invoice_id = $conn->insert_id; // The numeric ID for foreign key

            // 3. Fetch ALL items from quotation_items and move to invoice_items
            $items_res = $conn->query("SELECT * FROM quotation_items WHERE quotation_id = '$quote_id'");
            
            while ($item = $items_res->fetch_assoc()) {
                $s_name = mysqli_real_escape_string($conn, $item['service_name']);
                $s_desc = mysqli_real_escape_string($conn, $item['description'] ?? '');
                $s_amt = $item['amount'];
                $s_rate = $item['tax_rate'];
                $s_val = $item['tax_value'];

                $conn->query("INSERT INTO invoice_items (
                    invoice_id, service_name, description, amount, tax_rate, tax_value
                ) VALUES (
                    '$new_invoice_id', '$s_name', '$s_desc', '$s_amt', '$s_rate', '$s_val'
                )");
            }

            // 4. Mark Quotation as Accepted
            $conn->query("UPDATE quotations SET status = 'Accepted' WHERE id = '$quote_id'");

            // Redirect to the invoices list with success message
            header("Location: invoices.php?msg=Invoice Generated Successfully");
            exit();
        } else {
            echo "Error: " . $conn->error;
        }
    }
}
?>