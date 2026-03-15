<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'client') {
    header("Location: ../Login.php");
    exit();
}

$cid = $_SESSION['user']['identifier'];

// Handle Search Query
$search = "";
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Invoices | KKA Client</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --navy: #0b3c74;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
            --text-light: #64748b;
        }

        body { display: flex; margin: 0; background: var(--bg); font-family: 'Inter', sans-serif; color: #334155; }

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
            border-right: 4px solid var(--orange); 
        }

        .sidebar h2 {
            font-size: 20px;
            color: var(--orange);
            margin-bottom: 40px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 20px;
            line-height: 1.4;
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

        .sidebar a:hover, .sidebar a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 4px solid var(--orange);
        }

        .main {
            margin-left: 280px;
            padding: 50px;
            width: calc(100% - 280px);
            box-sizing: border-box;
        }

        .search-container {
            margin-bottom: 25px;
            display: flex;
            gap: 10px;
        }

        .search-input {
            padding: 12px 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            width: 300px;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }

        .search-input:focus { border-color: var(--navy); box-shadow: 0 0 0 3px rgba(11, 60, 116, 0.1); }

        .btn-search {
            background: var(--navy);
            color: white;
            border: none;
            padding: 0 20px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
        }

        .card {
            background: white;
            padding: 0;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
            border: 1px solid #edf2f7;
            overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left;
            padding: 18px 15px;
            background: #f1f5f9;
            color: var(--navy);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; font-size: 15px; }

        .btn-pay {
            background: var(--navy);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }

        .btn-pay:hover { background: var(--orange); transform: translateY(-2px); }

        .badge { padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .unpaid { background: #fee2e2; color: #ef4444; }
        .paid { background: #dcfce7; color: #166534; }
        .rotate-chevron { transform: rotate(180deg); transition: 0.3s; }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>Karunesh Kumar & Associates Client</h2>
        <a href="client-dashboard.php"><i class="fas fa-chart-line"></i> Overview</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn active" onclick="toggleFinances()">
                <i class="fas fa-wallet"></i> My Finances
                <i class="fas fa-chevron-down rotate-chevron" id="financeChevron" style="margin-left:auto; font-size:12px;"></i>
            </a>
            <div class="dropdown-content" id="financeMenu" style="display:block; background:rgba(0,0,0,0.2); border-radius:10px; margin:0 10px;">
                <a href="my-quotations.php"><i class="fas fa-file-alt"></i> Quotations</a>
                <a href="my-invoices.php" style="background:rgba(255,255,255,0.1); color:white !important;"><i class="fas fa-file-invoice-dollar"></i> Invoices (Pay)</a>
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
        <h1>Invoices</h1>
        <p style="color:var(--text-light); margin-top:-10px;">Review your billing details and complete payments.</p>

        <form method="GET" action="" class="search-container">
            <input type="text" name="search" class="search-input" placeholder="Search by Invoice # or Service..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn-search"><i class="fas fa-search"></i> Search</button>
            <?php if ($search != ""): ?>
                <a href="my-invoices.php" style="display:flex; align-items:center; color:var(--text-light); text-decoration:none; font-size:14px; margin-left:10px;">Clear</a>
            <?php endif; ?>
        </form>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Service</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Dynamic query based on search
                    $sql = "SELECT * FROM invoices WHERE client_id = '$cid'";
                    
                    if ($search != "") {
                        $sql .= " AND (invoice_no LIKE '%$search%' OR service_name LIKE '%$search%')";
                    }
                    
                    $sql .= " ORDER BY id DESC";
                    
                    $invoices = $conn->query($sql);

                    if ($invoices && $invoices->num_rows > 0) {
                        while ($inv = $invoices->fetch_assoc()) {
                            $is_unpaid = (strtolower($inv['status']) == 'unpaid');
                            
                            // Directly pulling the amount from the invoice table
                            $amount = (float)$inv['amount'];

                            echo "<tr>
                                    <td>#{$inv['invoice_no']}</td>
                                    <td><strong>{$inv['service_name']}</strong></td>
                                    <td style='font-weight:800; color:var(--navy);'>₹" . number_format($amount, 2) . "</td>
                                    <td><span class='badge " . ($is_unpaid ? 'unpaid' : 'paid') . "'>{$inv['status']}</span></td>
                                    <td>";

                            if ($is_unpaid) {
                                echo "<a href='process-test-pay.php?id={$inv['id']}' class='btn-pay'>
                                        <i class='fas fa-credit-card'></i> Pay Now
                                      </a>";
                            } else {
                                echo "<span style='color:var(--text-light); font-size:12px;'><i class='fas fa-check-circle'></i> Paid</span>";
                            }
                            echo "</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding:40px; color:var(--text-light);'>No invoices found matching your search.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
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