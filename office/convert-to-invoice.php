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

        // --- IMPROVED INVOICE NUMBER LOGIC ---
        $year = date('Y');
        $prefix = "INV-$year-";
        
        // Find the highest existing numeric suffix for the current year
        $check_max = $conn->query("SELECT invoice_no FROM invoices 
                                   WHERE invoice_no LIKE '$prefix%' 
                                   ORDER BY id DESC LIMIT 1");
        
        if ($check_max && $check_max->num_rows > 0) {
            $last_inv = $check_max->fetch_assoc()['invoice_no'];
            // Extract number from 'INV-2026-006' -> 6
            $last_num = (int)substr($last_inv, strrpos($last_inv, '-') + 1);
            $next_num = $last_num + 1;
        } else {
            $next_num = 1; // Start at 1 if no invoices exist this year
        }

        $inv_no = $prefix . str_pad($next_num, 3, "0", STR_PAD_LEFT);

        // Safety Loop: If the number exists (due to a deletion gap), skip to next
        while($conn->query("SELECT id FROM invoices WHERE invoice_no = '$inv_no'")->num_rows > 0) {
            $next_num++;
            $inv_no = $prefix . str_pad($next_num, 3, "0", STR_PAD_LEFT);
        }
        // --- END IMPROVED LOGIC ---

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
            $new_invoice_id = $conn->insert_id;

            // 3. Move items to invoice_items
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

            header("Location: invoices.php?msg=updated");
            exit();
        } else {
            // This will now catch other SQL errors instead of the Duplicate Entry one
            die("Database Error: " . $conn->error);
        }
    }
}
?>