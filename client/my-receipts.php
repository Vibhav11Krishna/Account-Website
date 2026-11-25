<?php
session_start();
include('../db.php');

// 1. SECURITY CHECK - Ensure only clients access this
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'client') {
    header("Location: ../Login.php");
    exit();
}

$cid =$_SESSION['user']['identifier'];
$clientName =$_SESSION['user']['name'] ?? 'Client';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Receipts | KKA Client</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --coffee-espresso: #1f110c;
            --coffee-roast: #3b2319;
            --coffee-mocha: #5c3a28;
            --coffee-latte: #8c5a3c;
            --coffee-caramel: #b87a4b;
            --coffee-cream: #f8f4f0;
            --coffee-foam: #fdfaf7;
            --coffee-sand: #efe7df;
            --card-bg: #ffffff;
            --text-main: #23140e;
            --text-muted: #6b5349;
            --border: #e6d8cf;
            --border-neutral: #eadfd8;
            --shadow-sm: 0 2px 10px rgba(59, 35, 25, 0.06);
            --shadow-md: 0 12px 28px -6px rgba(59, 35, 25, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            display: flex;
            margin: 0;
            min-height: 100vh;
            background: var(--coffee-cream);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
        }

        /* Sidebar Styles */
        .sidebar {
            width: 285px;
            background: linear-gradient(180deg, var(--coffee-roast) 0%, var(--coffee-espresso) 100%);
            color: #ffffff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            border-right: 4px solid var(--coffee-caramel);
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 0 6px 16px 6px;
            margin-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .sidebar-brand-icons {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 6px 8px;
            border-radius: 11px;
            background: rgba(184, 122, 75, 0.22);
            border: 1px solid rgba(243, 213, 181, 0.35);
            color: #f3d5b5;
            font-size: 14px;
            flex-shrink: 0;
        }

        .sidebar h2 {
            font-size: 14.5px;
            font-weight: 800;
            color: #f3d5b5;
            margin: 0;
            line-height: 1.3;
        }

        /* Sidebar Profile Card */
        .sidebar-profile {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            padding: 16px 14px;
            margin-bottom: 20px;
            text-align: center;
        }

        .sidebar-avatar {
            width: 58px;
            height: 58px;
            margin: 0 auto 10px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--coffee-latte), var(--coffee-caramel));
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.28);
            border: 2px solid rgba(243, 213, 181, 0.55);
        }

        .sidebar-profile-name {
            margin: 0;
            font-size: 14.5px;
            font-weight: 800;
            color: #ffffff;
            word-break: break-word;
        }

        .sidebar-profile-id {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 10px;
            border-radius: 999px;
            background: rgba(184, 122, 75, 0.2);
            border: 1px solid rgba(243, 213, 181, 0.3);
            color: #f3d5b5;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        .nav-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.45);
            margin: 0 0 8px 10px;
        }

        .sidebar a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            margin-bottom: 6px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 600;
            transition: 0.22s ease;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: rgba(184, 122, 75, 0.22);
            color: #ffffff;
            border-left: 4px solid var(--coffee-caramel);
            padding-left: 17px;
        }

        .dropdown-content {
            background: rgba(0, 0, 0, 0.24);
            border-radius: 12px;
            margin: 0 4px 10px;
            padding: 4px 0;
        }

        .dropdown-content a {
            font-size: 13px;
            padding: 10px 14px;
            margin-bottom: 2px;
        }

        .sub-active {
            background: rgba(184, 122, 75, 0.28) !important;
            color: #ffffff !important;
            border-left: 3px solid var(--coffee-caramel);
        }

        .logout-link {
            margin-top: auto;
            color: #f3d5b5 !important;
            background: rgba(0, 0, 0, 0.28) !important;
            border: 1px solid rgba(243, 213, 181, 0.22);
            justify-content: center;
            font-weight: 700 !important;
        }

        .logout-link:hover {
            background: var(--coffee-latte) !important;
            color: #ffffff !important;
            padding-left: 14px !important;
            border-left: 1px solid rgba(243, 213, 181, 0.4) !important;
        }

        .rotate-chevron {
            transform: rotate(180deg);
            transition: transform 0.25s ease;
        }

        /* Main Content Area */
        .main {
            margin-left: 285px;
            padding: 40px 48px;
            width: calc(100% - 285px);
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 26px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--border);
        }

        .page-header h1 {
            margin: 0;
            font-size: 27px;
            font-weight: 800;
            color: var(--coffee-espresso);
            letter-spacing: -0.4px;
        }

        .page-subtitle {
            margin: 4px 0 0;
            font-size: 13.5px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 26px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 22px 24px;
            border-radius: 18px;
            border: 1px solid var(--border);
            border-left: 5px solid var(--coffee-mocha);
            box-shadow: var(--shadow-sm);
            transition: transform 0.22s ease, box-shadow 0.22s ease;
        }

        .stat-card.is-interactive {
            cursor: pointer;
            border-left-color: var(--coffee-caramel);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .stat-label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.6px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-val {
            font-size: 24px;
            font-weight: 800;
            color: var(--coffee-espresso);
        }

        .card {
            background: var(--card-bg);
            padding: 0;
            border-radius: 22px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            border-top: 5px solid var(--coffee-mocha);
            overflow: hidden;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px 18px;
            background: var(--coffee-cream);
            color: var(--coffee-espresso);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border);
            white-space: nowrap;
        }

        td {
            padding: 15px 18px;
            border-bottom: 1px solid var(--border-neutral);
            font-size: 14px;
            vertical-align: middle;
        }

        tbody tr:hover {
            background: var(--coffee-foam);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .btn-view {
            background: var(--coffee-foam);
            color: var(--coffee-roast);
            border: 1px solid var(--border);
            padding: 8px 14px;
            border-radius: 10px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            text-decoration: none;
            font-size: 12.5px;
            transition: 0.22s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .btn-view:hover {
            background: var(--coffee-sand);
            color: var(--coffee-espresso);
            transform: translateY(-1px);
        }

        .badge-paid {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
            padding: 4px 11px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            display: inline-block;
        }

        /* Receipt Modal */
        .receipt-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            inset: 0;
            background: rgba(31, 17, 12, 0.45);
            backdrop-filter: blur(7px);
            -webkit-backdrop-filter: blur(7px);
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .receipt-modal-box {
            background: var(--card-bg);
            padding: 26px 28px;
            border-radius: 20px;
            width: 100%;
            max-width: 450px;
            border: 1px solid var(--border);
            border-top: 5px solid var(--coffee-mocha);
            box-shadow: 0 24px 50px rgba(20, 10, 6, 0.3);
        }

        .receipt-modal-close {
            margin-top: 20px;
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--coffee-roast), var(--coffee-mocha));
            color: #ffffff;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            font-size: 14px;
            transition: 0.2s ease;
        }

        .receipt-modal-close:hover {
            background: linear-gradient(135deg, var(--coffee-espresso), var(--coffee-roast));
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 30px;
            color: var(--coffee-latte);
            margin-bottom: 10px;
            display: block;
        }

        @media (max-width: 1024px) {
            .main {
                padding: 28px 24px;
            }
            .stat-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icons">
                <i class="fas fa-user-tie"></i>
                <i class="fas fa-mug-hot"></i>
            </div>
            <h2>Karunesh Kumar &amp; Associates</h2>
        </div>

        <div class="sidebar-profile">
            <div class="sidebar-avatar">
                <i class="fas fa-user"></i>
            </div>
            <h4 class="sidebar-profile-name"><?php echo htmlspecialchars($clientName); ?></h4>
            <span class="sidebar-profile-id">ID: <?php echo htmlspecialchars($cid); ?></span>
        </div>

        <div class="nav-label">Client Menu</div>
        <a href="client-dashboard.php"><i class="fas fa-chart-line"></i> Overview</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn active" onclick="toggleFinances()">
                <i class="fas fa-wallet"></i> My Finances
                <i class="fas fa-chevron-down rotate-chevron" id="financeChevron" style="margin-left:auto; font-size:12px;"></i>
            </a>
            <div class="dropdown-content" id="financeMenu" style="display:block;">
                <a href="my-quotations.php"><i class="fas fa-file-alt"></i> Quotations</a>
                <a href="my-invoices.php"><i class="fas fa-file-invoice-dollar"></i> Invoices (Pay)</a>
                <a href="my-receipts.php" class="sub-active"><i class="fas fa-receipt"></i> Acknowledgement</a>
            </div>
        </div>

        <a href="my-documents.php"><i class="fas fa-folder-open"></i> Document Vault</a>
        <a href="upload-docs.php"><i class="fas fa-cloud-upload-alt"></i> Upload Center</a>
        <a href="request-service.php"><i class="fas fa-plus-circle"></i> New Request</a>

        <a href="../logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </aside>

    <main class="main">
        <div class="page-header">
            <h1>Payment Receipts</h1>
            <p class="page-subtitle">Download or view your official payment confirmations.</p>
        </div>

        <div class="stat-grid">
            <?php
            $stats = [
                ['label' => 'Total Paid', 'type' => 'date', 'sql' => "SELECT SUM(amount_paid) as val FROM receipts WHERE client_id = '$cid'"],
                ['label' => 'Total Receipts', 'type' => 'mode', 'sql' => "SELECT COUNT(*) as val FROM receipts WHERE client_id = '$cid'"],
                ['label' => 'Latest Payment', 'type' => 'none', 'sql' => "SELECT MAX(amount_paid) as val FROM receipts WHERE client_id = '$cid'"]
            ];

            foreach ($stats as$s) {
                $res =$conn->query($s['sql']);$val = ($res && ($rowVal = $res->fetch_assoc())) ? ($rowVal['val'] ?? 0) : 0;
                $display = ($s['type'] != 'mode') ? "₹" . number_format((float)$val, 2) : (int)$val;

                $is_clickable = ($s['type'] != 'none');$onclick = $is_clickable ? "onclick=\"loadReceiptData('{$s['type']}')\"" : "";
                $cardClass =$is_clickable ? 'stat-card is-interactive' : 'stat-card';
                $hint =$is_clickable ? "<i class='fas fa-up-right-from-square' style='color:var(--coffee-latte);'></i>" : "";
                ?>
                <div class="<?php echo $cardClass; ?>" <?php echo $onclick; ?>>
                    <div class="stat-label">
                        <span><?php echo htmlspecialchars($s['label']); ?></span>
                        <?php echo $hint; ?>
                    </div>
                    <div class="stat-val"><?php echo htmlspecialchars((string)$display); ?></div>
                </div>
                <?php
            }
            ?>
        </div>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Receipt No</th>
                            <th>Invoice ID</th>
                            <th>Date Paid</th>
                            <th>Amount Paid</th>
                            <th>Method</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Querying receipts specifically for this client
                        $query = "SELECT * FROM receipts WHERE client_id = '$cid' ORDER BY id DESC";
                        $receipts = $conn->query($query);

                        if ($receipts &&$receipts->num_rows > 0) {
                            while ($r =$receipts->fetch_assoc()) {
                                echo "<tr>
                                    <td style='font-weight:700; color:var(--coffee-espresso);'>" . htmlspecialchars($r['receipt_no']) . "</td>
                                    <td style='color:var(--text-muted); font-weight:600;'>" . htmlspecialchars($r['invoice_no']) . "</td>
                                    <td>" . date('d M Y', strtotime($r['created_at'])) . "</td>
                                    <td style='font-weight:800; color:var(--coffee-roast);'>₹" . number_format((float)$r['amount_paid'], 2) . "</td>
                                    <td><span class='badge-paid'>" . htmlspecialchars($r['payment_mode']) . "</span></td>
                                    <td>
                                        <a href='../office/view_receipt.php?id=" . urlencode($r['receipt_no']) . "' target='_blank' class='btn-view'>
                                            <i class='fas fa-file-download'></i> View Receipt
                                        </a>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>
                                <div class='empty-state'>
                                    <i class='fas fa-receipt'></i>
                                    No receipts found. Complete an invoice payment to see it here.
                                </div>
                              </td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="receiptModal" class="receipt-modal" onclick="closeReceiptModal()">
        <div class="receipt-modal-box" onclick="event.stopPropagation()">
            <h3 style="margin-top:0; color:var(--coffee-espresso); font-weight:800;"><i class="fas fa-chart-pie" style="color:var(--coffee-latte);"></i> Payment Insights</h3>
            <p style="color:var(--text-muted); font-size:13.5px; margin-bottom: 15px;">Review your transaction breakdown below.</p>

            <!-- Table for dynamic content -->
            <table style="width:100%; border-collapse: collapse;">
                <tbody id="modalContent">
                    <!-- Data will load here -->
                </tbody>
            </table>

            <button type="button" onclick="closeReceiptModal()" class="receipt-modal-close">Close</button>
        </div>
    </div>

    <script>
        function toggleFinances() {
            var menu = document.getElementById("financeMenu");
            var chevron = document.getElementById("financeChevron");
            if (menu.style.display === "none" || menu.style.display === "") {
                menu.style.display = "block";
                chevron.classList.add("rotate-chevron");
            } else {
                menu.style.display = "none";
                chevron.classList.remove("rotate-chevron");
            }
        }

        function loadReceiptData(type) {
            document.getElementById('receiptModal').style.display = 'flex';
            document.getElementById('modalContent').innerHTML = "<tr><td style='padding:20px; text-align:center; color:#6b5349;'>Loading...</td></tr>";

            fetch('get-receipt-breakdown.php?type=' + encodeURIComponent(type))
                .then(function(response) { return response.text(); })
                .then(function(data) { document.getElementById('modalContent').innerHTML = data; });
        }

        function closeReceiptModal() {
            document.getElementById('receiptModal').style.display = 'none';
        }
    </script>
</body>

</html>