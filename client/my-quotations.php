<?php
session_start();
// include DB with an absolute path to ensure $conn is available
require_once dirname(__DIR__) . '/db.php';

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'client') {
    header("Location: ../Login.php");
    exit();
}

$cid = $_SESSION['user']['identifier'];
$clientName = $_SESSION['user']['name'] ?? 'Client';
$status_msg = "";

// Logic: When Client Accepts a Quotation
if (isset($_GET['action']) && $_GET['action'] == 'accept' && isset($_GET['id'])) {
    // Ensure quote id is an integer to avoid dependency on $conn for escaping
    $quote_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // Explicitly update status to 'Accepted'
    $update = $conn->query("UPDATE quotations SET status = 'Accepted' WHERE id = '$quote_id' AND client_id = '$cid'");

    if ($update) {
        $status_msg = "<div class='alert-success'><i class='fas fa-check-double'></i> Quotation Accepted! The price is now fixed. Proceed to Invoices to pay.</div>";
    }
}

// Fetch all quotes once to compute quick counts and render table cleanly
$quotes = $conn->query("SELECT * FROM quotations WHERE client_id = '$cid' ORDER BY id DESC");
$totalQuotesCount = $quotes ? (int)$quotes->num_rows : 0;
$acceptedQuotesCount = 0;
$pendingQuotesCount = 0;

$quoteRows = [];
if ($quotes && $quotes->num_rows > 0) {
    while ($row = $quotes->fetch_assoc()) {
        $quoteRows[] = $row;
        if (strtolower(trim($row['status'])) === 'accepted') {
            $acceptedQuotesCount++;
        } else {
            $pendingQuotesCount++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Quotations | KKA Client</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
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

        .summary-pills {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .summary-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 14px;
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--coffee-roast);
            border: 1px solid var(--border);
            font-size: 12.5px;
            font-weight: 700;
            box-shadow: var(--shadow-sm);
        }

        .card {
            background: var(--card-bg);
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
            padding: 16px 18px;
            border-bottom: 1px solid var(--border-neutral);
            font-size: 14px;
            color: var(--text-main);
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
            padding: 9px 12px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border);
            transition: 0.22s ease;
        }

        .btn-view:hover {
            background: var(--coffee-sand);
            color: var(--coffee-espresso);
            transform: translateY(-1px);
        }

        .btn-accept {
            background: linear-gradient(135deg, var(--coffee-roast), var(--coffee-mocha));
            color: #ffffff;
            padding: 9px 15px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: 0.22s ease;
            border: none;
            white-space: nowrap;
        }

        .btn-accept:hover {
            background: linear-gradient(135deg, var(--coffee-espresso), var(--coffee-roast));
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(59, 35, 25, 0.22);
        }

        .btn-fixed {
            background: var(--coffee-cream);
            color: var(--text-muted);
            padding: 9px 15px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            border: 1px solid var(--border);
            cursor: not-allowed;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            white-space: nowrap;
        }

        .badge {
            padding: 5px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            display: inline-block;
        }

        .bg-pending {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .bg-accepted {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-success {
            color: #166534;
            padding: 15px 18px;
            background: #dcfce7;
            border-radius: 14px;
            margin-bottom: 22px;
            border: 1px solid #bbf7d0;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 52px 20px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 28px;
            color: var(--coffee-latte);
            margin-bottom: 10px;
            display: block;
        }

        @media (max-width: 1024px) {
            .main {
                padding: 28px 24px;
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
                <a href="my-quotations.php" class="sub-active"><i class="fas fa-file-alt"></i> Quotations</a>
                <a href="my-invoices.php"><i class="fas fa-file-invoice-dollar"></i> Invoices (Pay)</a>
                <a href="my-receipts.php"><i class="fas fa-receipt"></i> Acknowledgement</a>
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
            <div>
                <h1>Service Quotations</h1>
                <p class="page-subtitle">Review and accept pricing for your requested services.</p>
            </div>
            <div class="summary-pills">
                <span class="summary-pill"><i class="fas fa-file-alt" style="color:var(--coffee-latte);"></i> Total: <?php echo $totalQuotesCount; ?></span>
                <span class="summary-pill"><i class="fas fa-hourglass-half" style="color:#b45309;"></i> Pending: <?php echo $pendingQuotesCount; ?></span>
                <span class="summary-pill"><i class="fas fa-check-circle" style="color:#15803d;"></i> Accepted: <?php echo $acceptedQuotesCount; ?></span>
            </div>
        </div>

        <?php echo $status_msg; ?>

        <div class="card">
            <div class="table-wrap">
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
                        <?php if (!empty($quoteRows)): ?>
                            <?php foreach ($quoteRows as $q):
                                $q_id = intval($q['id']);

                                // 1. Fetch sum of amounts/taxes AND combine all service names into one string
                                $item_data = $conn->query("SELECT 
                                                            SUM(amount) as total_base, 
                                                            SUM(tax_value) as total_tax,
                                                            GROUP_CONCAT(service_name SEPARATOR ', ') as services
                                                           FROM quotation_items 
                                                           WHERE quotation_id = '$q_id'");
                                $items = $item_data ? $item_data->fetch_assoc() : [];

                                $base_amt = $items['total_base'] ?? 0;
                                $tax_amt = $items['total_tax'] ?? 0;
                                $total = $base_amt + $tax_amt;

                                // Use the combined service names from items, fallback to main if empty
                                $display_services = !empty($items['services']) ? $items['services'] : $q['service_name'];

                                $tax_rate = ($base_amt > 0) ? ($tax_amt / $base_amt) * 100 : 0;

                                $raw_status = strtolower(trim($q['status']));
                                $is_accepted = ($raw_status === 'accepted');
                                $display_status = $is_accepted ? 'Accepted' : 'Pending';
                                $status_class = $is_accepted ? 'bg-accepted' : 'bg-pending';
                            ?>
                            <tr>
                                <td><?php echo date('d-m-Y', strtotime($q['created_at'])); ?></td>
                                <td><strong style="color:var(--coffee-espresso);"><?php echo htmlspecialchars($display_services); ?></strong></td>
                                <td>₹<?php echo number_format($base_amt, 2); ?></td>
                                <td><span style="color:var(--text-muted); font-weight:600;"><?php echo round($tax_rate, 2); ?>%</span></td>
                                <td style="color:var(--coffee-roast); font-weight:800;">₹<?php echo number_format($total, 2); ?></td>
                                <td><span class="badge <?php echo $status_class; ?>"><?php echo $display_status; ?></span></td>
                                <td>
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <a href="../office/view-quotation.php?id=<?php echo $q_id; ?>" class="btn-view" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <?php if (!$is_accepted): ?>
                                            <a href="my-quotations.php?action=accept&id=<?php echo $q_id; ?>" 
                                               class="btn-accept" 
                                               onclick="return confirm('Are you sure you want to accept this quotation?');">
                                                <i class="fas fa-check"></i> Accept
                                            </a>
                                        <?php else: ?>
                                            <button type="button" class="btn-fixed" disabled>
                                                <i class="fas fa-lock"></i> Accepted
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fas fa-file-invoice"></i>
                                        No quotations found.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

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
    </script>

</body>

</html>