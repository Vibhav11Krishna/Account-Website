<?php
session_start();
require_once __DIR__ . '/../db.php';
if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "accounting_site");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'client') {
    header("Location: ../Login.php");
    exit();
}

$cid = $_SESSION['user']['identifier'];
$clientName = $_SESSION['user']['name'] ?? 'Client';

// Handle Search Query
$search = "";
if (isset($_GET['search'])) {
    // Use mysqli_real_escape_string if DB connection exists, otherwise fall back to addslashes
    if (isset($conn)) {
        $search = mysqli_real_escape_string($conn, $_GET['search']);
    } else {
        $search = addslashes($_GET['search']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices | KKA Client</title>
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
            margin-bottom: 24px;
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

        .search-container {
            margin-bottom: 24px;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input {
            padding: 12px 18px;
            border-radius: 12px;
            border: 1.5px solid var(--border-neutral);
            width: 310px;
            max-width: 100%;
            font-size: 14px;
            font-family: inherit;
            color: var(--coffee-espresso);
            background: var(--card-bg);
            outline: none;
            transition: 0.25s ease;
        }

        .search-input:focus {
            border-color: var(--coffee-mocha);
            box-shadow: 0 0 0 4px rgba(92, 58, 40, 0.12);
        }

        .btn-search {
            background: linear-gradient(135deg, var(--coffee-roast), var(--coffee-mocha));
            color: #ffffff;
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            font-size: 13.5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.22s ease;
        }

        .btn-search:hover {
            background: linear-gradient(135deg, var(--coffee-espresso), var(--coffee-roast));
            transform: translateY(-1px);
        }

        .clear-link {
            display: inline-flex;
            align-items: center;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 700;
            padding: 8px 12px;
            border-radius: 10px;
            transition: 0.2s ease;
        }

        .clear-link:hover {
            background: var(--coffee-sand);
            color: var(--coffee-espresso);
        }

        .period-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 26px;
        }

        .period-card {
            background: var(--card-bg);
            padding: 22px 24px;
            border-radius: 18px;
            border: 1px solid var(--border);
            border-left: 5px solid var(--coffee-mocha);
            box-shadow: var(--shadow-sm);
            transition: transform 0.22s ease, box-shadow 0.22s ease;
        }

        .period-card.is-interactive {
            cursor: pointer;
            border-left-color: var(--coffee-caramel);
        }

        .period-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .period-label {
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

        .period-amount {
            font-size: 24px;
            font-weight: 800;
            color: var(--coffee-espresso);
        }

        /* Modal */
        .date-modal {
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

        .date-modal-box {
            background: var(--card-bg);
            padding: 26px 28px;
            border-radius: 20px;
            width: 100%;
            max-width: 440px;
            border: 1px solid var(--border);
            border-top: 5px solid var(--coffee-mocha);
            box-shadow: 0 24px 50px rgba(20, 10, 6, 0.3);
        }

        .date-modal-close {
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

        .date-modal-close:hover {
            background: linear-gradient(135deg, var(--coffee-espresso), var(--coffee-roast));
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
            padding: 16px 18px;
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
            font-size: 14.5px;
            vertical-align: middle;
        }

        tbody tr:hover {
            background: var(--coffee-foam);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .btn-pay {
            background: linear-gradient(135deg, var(--coffee-roast), var(--coffee-mocha));
            color: #ffffff;
            border: none;
            padding: 10px 18px;
            border-radius: 10px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.22s ease;
            white-space: nowrap;
        }

        .btn-pay:hover {
            background: linear-gradient(135deg, var(--coffee-espresso), var(--coffee-roast));
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(59, 35, 25, 0.22);
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            display: inline-block;
        }

        .unpaid {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .paid {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        @media (max-width: 1024px) {
            .main {
                padding: 28px 24px;
            }
            .period-grid {
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
                <a href="my-invoices.php" class="sub-active"><i class="fas fa-file-invoice-dollar"></i> Invoices (Pay)</a>
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
            <h1>Invoices</h1>
            <p class="page-subtitle">Review your billing details and complete payments.</p>
        </div>

        <form method="GET" action="" class="search-container">
            <input type="text" name="search" class="search-input" placeholder="Search by Invoice # or Service..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn-search"><i class="fas fa-search"></i> Search</button>
            <?php if ($search != ""): ?>
                <a href="my-invoices.php" class="clear-link"><i class="fas fa-times"></i> Clear</a>
            <?php endif; ?>
        </form>

        <div class="period-grid">
            <?php
            $periods = [
                ['label' => 'Today', 'sql' => "DATE(invoice_date) = CURDATE()", 'type' => 'static'],
                ['label' => 'Last 7 Days', 'sql' => "invoice_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)", 'type' => '7days'],
                ['label' => 'Monthly View', 'sql' => "1=1", 'type' => 'monthly_breakdown']
            ];

            foreach ($periods as $p) {
                $sql_sum = "SELECT SUM(amount + cgst_amount + sgst_amount + igst_amount) as total FROM invoices WHERE client_id = '$cid' AND " . $p['sql'];
                $res_sum = $conn->query($sql_sum);
                $total = ($res_sum && ($rowSum = $res_sum->fetch_assoc())) ? ($rowSum['total'] ?? 0) : 0;

                $is_clickable = ($p['type'] != 'static');
                $extraClass = $is_clickable ? 'period-card is-interactive' : 'period-card';
                $onclick = $is_clickable ? "onclick=\"loadData('{$p['type']}')\"" : "";
                $hint = $is_clickable ? "<i class='fas fa-up-right-from-square' style='color:var(--coffee-latte);'></i>" : "";
                ?>
                <div class="<?php echo $extraClass; ?>" <?php echo $onclick; ?>>
                    <div class="period-label">
                        <span><?php echo htmlspecialchars($p['label']); ?></span>
                        <?php echo $hint; ?>
                    </div>
                    <div class="period-amount">₹<?php echo number_format((float)$total, 2); ?></div>
                </div>
                <?php
            }
            ?>
        </div>

        <!-- Modal -->
        <div id="dateWiseModal" class="date-modal" onclick="closeDateModal()">
            <div class="date-modal-box" onclick="event.stopPropagation()">
                <h3 id="modalTitle" style="margin-top:0; color:var(--coffee-espresso); font-weight:800;">Activity</h3>
                <table style="width:100%; border-collapse:collapse; margin-top:14px;">
                    <tbody id="dateWiseContent">
                        <tr><td style="padding:20px; text-align:center; color:var(--text-muted);">Loading...</td></tr>
                    </tbody>
                </table>
                <button type="button" onclick="closeDateModal()" class="date-modal-close">Close View</button>
            </div>
        </div>

        <script>
            function loadData(type) {
                var titleEl = document.getElementById('modalTitle');
                titleEl.textContent = (type === 'monthly_breakdown') ? 'Monthly Invoice Breakdown' : 'Last 7 Days Activity';
                document.getElementById('dateWiseModal').style.display = 'flex';
                document.getElementById('dateWiseContent').innerHTML = "<tr><td style='padding:20px; text-align:center; color:#6b5349;'>Loading...</td></tr>";

                fetch('get-datewise-data.php?type=' + encodeURIComponent(type))
                    .then(function(response) { return response.text(); })
                    .then(function(data) { document.getElementById('dateWiseContent').innerHTML = data; });
            }

            function closeDateModal() {
                document.getElementById('dateWiseModal').style.display = 'none';
            }
        </script>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Invoice No</th>
                            <th>Service</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Updated SQL to group both service_name and description
                        $sql = "SELECT i.*, 
                                GROUP_CONCAT(ii.service_name SEPARATOR ', ') as services_list,
                                GROUP_CONCAT(ii.description SEPARATOR ' | ') as descriptions_list,
                                SUM(ii.amount) as total_base,
                                SUM(ii.tax_value) as total_tax
                                FROM invoices i
                                LEFT JOIN invoice_items ii ON i.id = ii.invoice_id
                                WHERE i.client_id = '$cid'";

                        if ($search != "") {
                            $search = mysqli_real_escape_string($conn, $search);
                            $sql .= " AND (i.invoice_no LIKE '%$search%' OR ii.service_name LIKE '%$search%' OR ii.description LIKE '%$search%')";
                        }

                        $sql .= " GROUP BY i.id ORDER BY i.id DESC";

                        $invoices = $conn->query($sql);

                        if ($invoices && $invoices->num_rows > 0) {
                            while ($inv = $invoices->fetch_assoc()) {
                                $is_unpaid = (strtolower($inv['status']) == 'unpaid');

                                $base = (float)$inv['total_base'];
                                $tax = (float)$inv['total_tax'];
                                $grand_total = $base + $tax;

                                $inv_date = !empty($inv['invoice_date']) ? date('d M, Y', strtotime($inv['invoice_date'])) : 'N/A';

                                echo "<tr>
                                        <td style='color:var(--text-muted); font-size:13.5px; font-weight:600;'>" . htmlspecialchars($inv_date) . "</td>
                                        <td><strong style='color:var(--coffee-espresso);'>#" . htmlspecialchars($inv['invoice_no']) . "</strong></td>
                                        <td>
                                            <strong style='color:var(--coffee-espresso);'>" . htmlspecialchars($inv['services_list'] ?? 'No Service') . "</strong>
                                            <div style='font-size:12px; color:var(--text-muted); margin-top:4px;'>" .
                                            htmlspecialchars($inv['descriptions_list'] ?? '') .
                                            "</div>
                                        </td>
                                        <td style='font-weight:800; color:var(--coffee-roast);'>
                                            ₹" . number_format($grand_total, 2) . "
                                            <div style='font-size:11px; color:var(--text-muted); font-weight:500;'>
                                                (Base: ₹" . number_format($base, 2) . " + Tax: ₹" . number_format($tax, 2) . ")
                                            </div>
                                        </td>
                                        <td><span class='badge " . ($is_unpaid ? 'unpaid' : 'paid') . "'>" . htmlspecialchars($inv['status']) . "</span></td>
                                        <td>";

                                if ($is_unpaid) {
                                    echo "<a href='process-test-pay.php?id=" . intval($inv['id']) . "' class='btn-pay'>
                                            <i class='fas fa-credit-card'></i> Pay Now
                                          </a>";
                                } else {
                                    echo "<span style='color:#166534; font-size:12.5px; font-weight:700;'><i class='fas fa-check-circle'></i> Paid</span>";
                                }
                                echo "</td></tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding:44px; color:var(--text-muted);'>No invoices found.</td></tr>";
                        }
                        ?>
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