<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Error: Invoice ID is missing.");
}

$invoice_id = mysqli_real_escape_string($conn, $_GET['id']);

// 1. FETCH EXISTING DATA
$master_res = $conn->query("SELECT * FROM invoices WHERE id = '$invoice_id'");
$inv_data = $master_res->fetch_assoc();

if (!$inv_data) { die("Invoice not found."); }

// Fetch items to display in the form
$items_res = $conn->query("SELECT * FROM invoice_items WHERE invoice_id = '$invoice_id'");

// 2. UPDATE LOGIC
if (isset($_POST['update_invoice'])) {
    // Safety: Check if at least one service exists
    if (!isset($_POST['services']) || empty($_POST['services'])) {
        die("Error: You must have at least one service item.");
    }

    // New: Capture Invoice Date
    $invoice_date = mysqli_real_escape_string($conn, $_POST['invoice_date']);
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    $client_state = mysqli_real_escape_string($conn, $_POST['client_state']);
    
    $services     = $_POST['services']; 
    $descriptions = $_POST['descriptions'];
    $amounts      = $_POST['amounts'];   
    $tax_rates    = $_POST['tax_rates']; 

    // Determine Tax Type based on state
    $tax_type = (strcasecmp(trim($client_state), 'Bihar') == 0) ? 'CGST+SGST' : 'IGST';

    // A. Remove old items to prevent duplicates or "ghost" rows
    $conn->query("DELETE FROM invoice_items WHERE invoice_id = '$invoice_id'");

    // B. Re-insert updated items and calculate totals
    $total_base = 0;
    $total_tax = 0;

    foreach ($services as $key => $val) {
        $s_name  = mysqli_real_escape_string($conn, $val);
        $s_desc  = mysqli_real_escape_string($conn, $descriptions[$key]);
        $s_amt   = (float)$amounts[$key];
        $s_tax_r = (float)$tax_rates[$key];
        $s_tax_v = ($s_amt * $s_tax_r) / 100;

        $total_base += $s_amt;
        $total_tax  += $s_tax_v;

        $conn->query("INSERT INTO invoice_items (invoice_id, service_name, description, amount, tax_rate, tax_value) 
                      VALUES ('$invoice_id', '$s_name', '$s_desc', '$s_amt', '$s_tax_r', '$s_tax_v')");
    }

    // C. Calculate Tax Splits
    $cgst = ($tax_type == 'CGST+SGST') ? $total_tax / 2 : 0;
    $sgst = ($tax_type == 'CGST+SGST') ? $total_tax / 2 : 0;
    $igst = ($tax_type == 'IGST') ? $total_tax : 0;

    // D. Update Master Totals (Including invoice_date)
    $update_sql = "UPDATE invoices SET 
                   invoice_date = '$invoice_date',
                   due_date = '$due_date', 
                   tax_type = '$tax_type', 
                   amount = '$total_base', 
                   cgst_amount = '$cgst', 
                   sgst_amount = '$sgst', 
                   igst_amount = '$igst' 
                   WHERE id = '$invoice_id'";

    if ($conn->query($update_sql)) {
        header("Location: invoices.php?msg=updated");
        exit();
    } else {
        $error = "Update failed: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Invoice | <?php echo $inv_data['invoice_no']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --navy: #0b3c74; --orange: #ff8c00; --bg: #f8fafc; --danger: #ef4444; }
        body { background: var(--bg); font-family: 'Inter', sans-serif; padding: 20px; color: #1e293b; }
        .card { background: white; padding: 30px; border-radius: 20px; max-width: 900px; margin: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; }
        
        label { font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block; }
        input, select, textarea { width: 100%; padding: 12px; margin-bottom: 15px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; transition: border 0.3s; }
        input:focus { border-color: var(--navy); outline: none; }

        .item-row { border: 1px solid #f1f5f9; padding: 20px; border-radius: 15px; margin-bottom: 15px; position: relative; background: #fafafa; border-left: 4px solid var(--navy); }
        .remove-btn { position: absolute; top: 15px; right: 15px; color: var(--danger); cursor: pointer; font-size: 18px; transition: transform 0.2s; }
        .remove-btn:hover { transform: scale(1.2); }

        .btn-update { background: var(--navy); color: white; border: none; padding: 16px; width: 100%; border-radius: 12px; font-weight: 700; cursor: pointer; font-size: 16px; margin-top: 10px; }
        .btn-update:hover { background: #082d56; }
        
        .add-btn { background: #f1f5f9; color: #475569; padding: 12px; border: 2px dashed #cbd5e1; border-radius: 12px; width: 100%; cursor: pointer; margin-bottom: 25px; font-weight: 600; transition: all 0.3s; }
        .add-btn:hover { background: #e2e8f0; border-color: #94a3b8; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        /* Added for the 3-column header layout */
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
    </style>
</head>
<body>

<div class="card">
    <div class="header-flex">
        <div>
            <h2 style="margin:0;">Edit Invoice</h2>
            <small style="color:var(--orange); font-weight:bold;"><?php echo $inv_data['invoice_no']; ?></small>
        </div>
        <a href="invoices.php" style="color: #64748b; text-decoration: none; font-size: 14px;"><i class="fas fa-arrow-left"></i> Back to List</a>
    </div>

    <?php if(isset($error)): ?>
        <div style="background:#fee2e2; color:var(--danger); padding:10px; border-radius:10px; margin-bottom:20px;"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" id="invoiceForm">
        <div class="grid-3">
            <div>
                <label>Place of Supply</label>
                <select name="client_state" required>
                    <option value="Bihar" <?php echo ($inv_data['tax_type'] != 'IGST') ? 'selected' : ''; ?>>Bihar (CGST+SGST)</option>
                    <option value="Outside" <?php echo ($inv_data['tax_type'] == 'IGST') ? 'selected' : ''; ?>>Outside (IGST)</option>
                </select>
            </div>
            <div>
                <label>Invoice Date</label>
                <input type="date" name="invoice_date" value="<?php echo date('Y-m-d', strtotime($inv_data['invoice_date'])); ?>" required>
            </div>
            <div>
                <label>Due Date</label>
                <input type="date" name="due_date" value="<?php echo date('Y-m-d', strtotime($inv_data['due_date'])); ?>" required>
            </div>
        </div>

        <h3 style="font-size: 16px; margin: 20px 0 10px;">Service Items</h3>
        <div id="itemsContainer">
            <?php 
            $items_res->data_seek(0); // Reset pointer
            while($item = $items_res->fetch_assoc()): 
            ?>
            <div class="item-row">
                <i class="fas fa-times-circle remove-btn" onclick="removeItem(this)"></i>
                <label>Service Name</label>
                <input type="text" name="services[]" value="<?php echo htmlspecialchars($item['service_name']); ?>" required>
                
                <label>Description</label>
                <textarea name="descriptions[]" rows="2" placeholder="Specific details..."><?php echo htmlspecialchars($item['description']); ?></textarea>
                
                <div class="grid-2">
                    <div>
                        <label>Amount (Basic)</label>
                        <input type="number" step="0.01" name="amounts[]" value="<?php echo $item['amount']; ?>" required>
                    </div>
                    <div>
                        <label>GST Rate (%)</label>
                        <input type="number" step="0.01" name="tax_rates[]" value="<?php echo $item['tax_rate']; ?>" required>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <button type="button" class="add-btn" onclick="addItem()"><i class="fas fa-plus-circle"></i> Add Another Service Line</button>

        <button type="submit" name="update_invoice" class="btn-update">
            <i class="fas fa-save"></i> Save Changes & Recalculate
        </button>
    </form>
</div>

<script>
    function addItem() {
        const container = document.getElementById('itemsContainer');
        const newRow = document.createElement('div');
        newRow.className = 'item-row';
        newRow.innerHTML = `
            <i class="fas fa-times-circle remove-btn" onclick="removeItem(this)"></i>
            <label>Service Name</label>
            <input type="text" name="services[]" placeholder="Enter Service" required>
            <label>Description</label>
            <textarea name="descriptions[]" rows="2" placeholder="Details..."></textarea>
            <div class="grid-2">
                <div>
                    <label>Amount (Basic)</label>
                    <input type="number" step="0.01" name="amounts[]" placeholder="0.00" required>
                </div>
                <div>
                    <label>GST Rate (%)</label>
                    <input type="number" step="0.01" name="tax_rates[]" value="18" required>
                </div>
            </div>`;
        container.appendChild(newRow);
    }

    function removeItem(btn) {
        const rows = document.getElementsByClassName('item-row');
        if (rows.length > 1) {
            btn.parentElement.remove();
        } else {
            alert("An invoice must have at least one item.");
        }
    }
</script>

</body>
</html>