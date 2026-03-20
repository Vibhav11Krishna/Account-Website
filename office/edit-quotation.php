<?php
session_start();
include('../db.php');

// 1. SECURITY & ID CHECK
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

$quote_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';
if (!$quote_id) { header("Location: quotations.php"); exit(); }

// 2. FETCH EXISTING DATA
$quote_res = $conn->query("SELECT q.*, u.name as client_name FROM quotations q JOIN users u ON q.client_id = u.identifier WHERE q.id = '$quote_id'");
$quote = $quote_res->fetch_assoc();

$items_res = $conn->query("SELECT * FROM quotation_items WHERE quotation_id = '$quote_id'");

// 3. UPDATE LOGIC
if (isset($_POST['update_quote'])) {
    $client_id = mysqli_real_escape_string($conn, $_POST['client_id']);
    $validity = mysqli_real_escape_string($conn, $_POST['validity']);
    $client_state = mysqli_real_escape_string($conn, $_POST['client_state']);
    
    $services = $_POST['services']; 
    $amounts = $_POST['amounts'];   
    $tax_rates = $_POST['tax_rates']; 

    $tax_type = ($client_state == 'Bihar') ? 'CGST+SGST' : 'IGST';
    
    $conn->query("DELETE FROM quotation_items WHERE quotation_id = '$quote_id'");

    $grand_total = 0;
    $base_total = 0;

    foreach ($services as $key => $val) {
        $s_name = mysqli_real_escape_string($conn, $val);
        $s_amount = (float)$amounts[$key];
        $s_tax_rate = (float)$tax_rates[$key];
        $s_tax_value = ($s_amount * $s_tax_rate) / 100;
        
        $base_total += $s_amount;
        $grand_total += ($s_amount + $s_tax_value);

        $conn->query("INSERT INTO quotation_items (quotation_id, service_name, amount, tax_rate, tax_value) 
                      VALUES ('$quote_id', '$s_name', '$s_amount', '$s_tax_rate', '$s_tax_value')");
    }

    $update_master = "UPDATE quotations SET 
                      client_id = '$client_id', 
                      amount = '$base_total', 
                      total_amount = '$grand_total', 
                      tax_type = '$tax_type', 
                      validity_date = '$validity', 
                      client_state = '$client_state' 
                      WHERE id = '$quote_id'";

    if ($conn->query($update_master)) {
        header("Location: quotations.php?msg=updated");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Quotation | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --navy: #0b3c74; --orange: #ff8c00; --sidebar: #082d56; --bg: #f8fafc; --text-gray: #64748b; }
        
        body { margin: 0; background: var(--bg); font-family: 'Inter', sans-serif; color: #334155; }
        
        /* Centering Container */
        .main { 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center; /* Horizontal Center */
            justify-content: flex-start; /* Start from top */
            padding: 60px 20px; 
            box-sizing: border-box; 
        }

        .card { 
            background: white; 
            padding: 40px; 
            border-radius: 16px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
            width: 100%;
            max-width: 900px; /* Limits width to keep it centered and readable */
        }
        
        .header-area {
            width: 100%;
            max-width: 900px;
            margin-bottom: 20px;
            text-align: left;
        }
        
        /* Form Layout */
        .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .field-group { display: flex; flex-direction: column; }
        label { font-size: 11px; font-weight: 700; color: var(--text-gray); text-transform: uppercase; margin-bottom: 8px; }
        
        input, select { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; box-sizing: border-box; background-color: #fff; }
        input:focus { border-color: var(--navy); outline: none; }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 12px; background: #f8fafc; color: var(--text-gray); font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #edf2f7; }
        td { padding: 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        
        .btn-add { background: #e2e8f0; color: #475569; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 700; cursor: pointer; margin-top: 15px; font-size: 12px; transition: 0.2s; }
        .btn-add:hover { background: #d1d5db; }

        .btn-update { background: var(--navy); color: white; border: none; padding: 16px; width: 100%; border-radius: 12px; font-weight: bold; cursor: pointer; margin-top: 30px; font-size: 16px; transition: 0.3s; }
        .btn-update:hover { background: var(--orange); transform: translateY(-2px); }
        
        .back-btn { text-decoration: none; color: var(--navy); font-weight: 700; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 10px; }
    </style>
</head>
<body>

    <div class="main">
        <div class="header-area">
            <a href="quotations.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Ledger</a>
            <h1 style="margin: 10px 0;">Edit Quotation <span style="color: var(--orange);"><?php echo $quote['quote_no']; ?></span></h1>
        </div>

        <div class="card">
            <form method="POST">
                <div class="form-grid">
                    <div class="field-group">
                        <label>Client</label>
                        <select name="client_id" required>
                            <?php
                            $clients = $conn->query("SELECT identifier, name FROM users WHERE role='client'");
                            while ($c = $clients->fetch_assoc()) {
                                $sel = ($c['identifier'] == $quote['client_id']) ? 'selected' : '';
                                echo "<option value='{$c['identifier']}' $sel>{$c['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="field-group">
                        <label>Place of Supply</label>
                        <select name="client_state" required>
                            <option value="Bihar" <?php if($quote['client_state'] == 'Bihar') echo 'selected'; ?>>Bihar (Intrastate)</option>
                            <option value="Outside Bihar" <?php if($quote['client_state'] != 'Bihar') echo 'selected'; ?>>Outside Bihar (Interstate)</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label>Valid Until</label>
                        <input type="date" name="validity" value="<?php echo $quote['validity_date']; ?>" required>
                    </div>
                </div>

                <h4 style="margin: 40px 0 10px 0; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">Service Items</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th width="180">Amount (₹)</th>
                            <th width="120">Tax %</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody id="editServiceItems">
                        <?php while ($item = $items_res->fetch_assoc()): ?>
                        <tr class="item-row">
                            <td><input type="text" name="services[]" value="<?php echo $item['service_name']; ?>" required></td>
                            <td><input type="number" name="amounts[]" value="<?php echo $item['amount']; ?>" step="0.01" required></td>
                            <td>
                                <select name="tax_rates[]">
                                    <?php 
                                    $rates = [0, 5, 12, 18, 28];
                                    foreach($rates as $r) {
                                        $sel = ($item['tax_rate'] == $r) ? 'selected' : '';
                                        echo "<option value='$r' $sel>$r%</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" onclick="this.parentElement.parentElement.remove()" style="color:#ef4444; border:none; background:none; cursor:pointer;">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <button type="button" onclick="addRow()" class="btn-add">
                    <i class="fas fa-plus"></i> Add Item
                </button>

                <button name="update_quote" class="btn-update">
                    Update Quotation Details
                </button>
            </form>
        </div>
    </div>

    <script>
        function addRow() {
            const container = document.getElementById('editServiceItems');
            const row = document.createElement('tr');
            row.className = 'item-row';
            row.innerHTML = `
                <td><input type="text" name="services[]" placeholder="Description" required></td>
                <td><input type="number" name="amounts[]" placeholder="0.00" step="0.01" required></td>
                <td>
                    <select name="tax_rates[]">
                        <option value="0">0%</option>
                        <option value="5">5%</option>
                        <option value="12">12%</option>
                        <option value="18" selected>18%</option>
                        <option value="28">28%</option>
                    </select>
                </td>
                <td style="text-align: center;">
                    <button type="button" onclick="this.parentElement.parentElement.remove()" style="color:#ef4444; border:none; background:none; cursor:pointer;">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            `;
            container.appendChild(row);
        }
    </script>
</body>
</html>