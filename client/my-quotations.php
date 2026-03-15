<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'client') {
    header("Location: ../Login.php");
    exit();
}

$cid = $_SESSION['user']['identifier'];
$status_msg = "";

// Logic: When Client Accepts a Quotation
if (isset($_GET['action']) && $_GET['action'] == 'accept' && isset($_GET['id'])) {
    $quote_id = mysqli_real_escape_string($conn, $_GET['id']);

    // Explicitly update status to 'Accepted'
    $update = $conn->query("UPDATE quotations SET status = 'Accepted' WHERE id = '$quote_id' AND client_id = '$cid'");

    if ($update) {
        $status_msg = "<div class='alert-success'><i class='fas fa-check-double'></i> Quotation Accepted! The price is now fixed. Proceed to Invoices to pay.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>My Quotations | KKA Client</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --navy: #0b3c74;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
            --text-light: #64748b;
        }

        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            color: #334155;
        }

        .sidebar {
    width: 280px;
    background: var(--sidebar);
    color: white;
    height: 100vh;
    position: fixed;
    padding: 30px 20px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    
    /* ADD THIS LINE */
    border-right: 4px solid var(--orange); 
}

        .sidebar h2 {
            font-size: 22px;
            color: var(--orange);
            margin-bottom: 40px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 20px;
        }

        .sidebar a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px;
            margin-bottom: 8px;
            border-radius: 12px;
            transition: 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 4px solid var(--orange);
        }

        .dropdown-content a {
            color: rgba(255, 255, 255, 0.7) !important;
            padding: 10px 15px !important;
            font-size: 14px;
        }

        .rotate-chevron {
            transform: rotate(180deg);
        }

        .main {
            margin-left: 280px;
            padding: 50px;
            width: calc(100% - 280px);
            box-sizing: border-box;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
            border: 1px solid #edf2f7;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            text-align: left;
            padding: 15px;
            background: #f1f5f9;
            color: var(--navy);
            font-size: 13px;
            border-bottom: 2px solid #edf2f7;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 15px;
        }

        .btn-accept {
            background: #22c55e;
            color: white;
            padding: 10px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
            border: none;
        }

        .btn-accept:hover {
            background: #16a34a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }

        .btn-fixed {
            background: #f1f5f9;
            color: #64748b;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: bold;
            border: 1px solid #cbd5e1;
            cursor: not-allowed;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .bg-pending {
            background: #fef9c3;
            color: #854d0e;
        }

        .bg-accepted {
            background: #dcfce7;
            color: #166534;
        }

        .alert-success {
            color: #166534;
            padding: 15px;
            background: #dcfce7;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #bbf7d0;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>Karunesh Kumar & Associates Client</h2>
        <a href="client-dashboard.php"><i class="fas fa-chart-line"></i> Overview</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn active" onclick="toggleFinances()">
                <i class="fas fa-wallet"></i> My Finances
                <i class="fas fa-chevron-down rotate-chevron" id="financeChevron" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="financeMenu" style="display:block; background:rgba(0,0,0,0.2); border-radius:10px; margin:0 10px;">
                <a href="my-quotations.php" style="background:rgba(255,255,255,0.1); color:white !important;"><i class="fas fa-file-alt"></i> Quotations</a>
                <a href="my-invoices.php"><i class="fas fa-file-invoice-dollar"></i> Invoices (Pay)</a>
                <a href="my-receipts.php"><i class="fas fa-receipt"></i>Acknowledgement</a>
            </div>
        </div>

        <a href="my-documents.php"><i class="fas fa-folder-open"></i> Document Vault</a>
        <a href="upload-docs.php"><i class="fas fa-cloud-upload-alt"></i> Upload Center</a>
        <a href="request-service.php"><i class="fas fa-plus-circle"></i> New Request</a>

        <a href="../logout.php" style="margin-top:auto; color:#fda4af !important; background: rgba(244, 63, 94, 0.1); padding:14px; border-radius:12px; text-decoration:none; display:flex; align-items:center; gap:12px;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <div class="main">
        <h1>Service Quotations</h1>
        <p style="color: #64748b; margin-top:-10px;">Review and accept pricing for your services.</p>

        <?php echo $status_msg; ?>
<div class="card" style="padding:0; overflow:hidden;">
    <table>
        <thead>
            <tr>
                <th>Date Sent</th>
                <th>Service Name</th>
                <th>Base Amount</th>
                <th>Tax (%)</th>
                <th>Total (Incl. Tax)</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $quotes = $conn->query("SELECT * FROM quotations WHERE client_id = '$cid' ORDER BY id DESC");

            if ($quotes->num_rows > 0) {
                while ($q = $quotes->fetch_assoc()) {
                    $raw_status = strtolower(trim($q['status']));
                    $is_accepted = ($raw_status === 'accepted');

                    $display_status = $is_accepted ? 'Accepted' : 'Pending';
                    $status_class = $is_accepted ? 'bg-accepted' : 'bg-pending';
                    
                    // Fallback logic if total_amount isn't in your DB yet
                    $base_amt = $q['amount'];
                    $tax_rate = isset($q['tax_rate']) ? $q['tax_rate'] : 18; // Default 18% if not set
                    $total = isset($q['total_amount']) && $q['total_amount'] > 0 ? $q['total_amount'] : ($base_amt * (1 + ($tax_rate / 100)));
                    ?>
                    <tr>
                        <td><?php echo date('d-m-Y', strtotime($q['created_at'])); ?></td>
                        <td><strong><?php echo $q['service_name']; ?></strong></td>
                        <td>₹<?php echo number_format($base_amt, 2); ?></td>
                        <td><small><?php echo $tax_rate; ?>%</small></td>
                        <td style='color:var(--navy); font-weight:800;'>₹<?php echo number_format($total, 2); ?></td>
                        <td><span class='badge <?php echo $status_class; ?>'><?php echo $display_status; ?></span></td>
                        <td style="display: flex; gap: 8px; align-items: center;">
                          <a href="../office/view-quotation.php?id=<?php echo $q['id']; ?>" class="btn-accept" style="background:#64748b;" title="View Details">
    <i class="fas fa-eye"></i>
</a>

                            <?php if (!$is_accepted): ?>
                                
                            <?php else: ?>
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <button class='btn-fixed' disabled style="padding: 6px 12px; font-size: 11px;">
                                        <i class="fas fa-lock"></i> Fixed
                                    </button>
                                    <a href='my-invoices.php' style='font-size:10px; color:var(--orange); margin-top:4px; text-decoration:none; font-weight:600;'>
                                        Pay <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo "<tr><td colspan='7' style='text-align:center; padding:50px; color:#64748b;'>No quotations found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

    <script>
        function toggleFinances() {
            var menu = document.getElementById("financeMenu");
            var chevron = document.getElementById("financeChevron");
            if (menu.style.display === "none") {
                menu.style.display = "block";
                chevron.classList.add("rotate-chevron");
            } else {
                menu.style.display = "none";
                chevron.classList.remove("rotate-chevron");
            }
        }
    </script>

</body>

</html>