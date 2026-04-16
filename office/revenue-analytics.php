<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

$month = isset($_GET['m']) ? $_GET['m'] : date('m');
$year = isset($_GET['y']) ? $_GET['y'] : date('Y');

// --- 1. KPI BOX LOGIC ---
$week_query = "SELECT SUM(amount_paid) as total FROM receipts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$week_earning = $conn->query($week_query)->fetch_assoc()['total'] ?? 0;

$month_query = "SELECT SUM(amount_paid) as total FROM receipts WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = '$year'";
$month_earning = $conn->query($month_query)->fetch_assoc()['total'] ?? 0;

$year_query = "SELECT SUM(amount_paid) as total FROM receipts WHERE YEAR(created_at) = '$year'";
$year_earning = $conn->query($year_query)->fetch_assoc()['total'] ?? 0;

// --- 2. CHART DATA ---
$monthly_query = "SELECT MONTH(created_at) as mon, SUM(amount_paid) as total 
                  FROM receipts WHERE YEAR(created_at) = '$year' 
                  GROUP BY MONTH(created_at) ORDER BY mon ASC";
$monthly_res = $conn->query($monthly_query);
$month_labels = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
$month_values = array_fill(0, 12, 0);
while ($rm = $monthly_res->fetch_assoc()) {
    $month_values[$rm['mon'] - 1] = (float)$rm['total'];
}

// --- 3. CALENDAR DATA ---
$daily_query = "SELECT DATE(created_at) as day, SUM(amount_paid) as total 
                FROM receipts WHERE MONTH(created_at) = '$month' AND YEAR(created_at) = '$year' 
                GROUP BY DATE(created_at)";
$daily_res = $conn->query($daily_query);
$revenue_days = [];
while ($r = $daily_res->fetch_assoc()) {
    $revenue_days[$r['day']] = $r['total'];
}

// --- 4. TABLE DATA ---
$table_query = "SELECT r.*, u.name as client_name 
                FROM receipts r 
                JOIN users u ON r.client_id = u.identifier 
                ORDER BY r.created_at DESC LIMIT 10";
$table_res = $conn->query($table_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Revenue Analytics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --navy: #0b3c74;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
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

        .sidebar a:hover, .active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 4px solid var(--orange);
        }

        .dropdown-content {
            display: none;
            background: rgba(0, 0, 0, 0.15);
            margin: 0 10px;
            border-radius: 10px;
            padding-left: 10px;
        }

        .show-menu {
            display: block !important;
        }

        .rotate-chevron {
            transform: rotate(180deg);
        }

        .main {
            margin-left: 280px;
            width: calc(100% - 280px);
            min-height: 100vh;
        }

        .admin-top-bar { padding: 30px 50px; }
        .content-body { padding: 30px 50px; }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-left: 5px solid var(--navy);
        }

        .stat-card .amt {
            font-size: 24px;
            font-weight: 800;
            color: var(--navy);
            margin: 10px 0;
        }

        .stat-card.orange { border-left-color: var(--orange); }

        .analytics-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-top: 15px;
        }

        .day {
            aspect-ratio: 1;
            border-radius: 10px;
            border: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            transition: 0.2s;
        }

        .has-revenue {
            background: #f0fdf4;
            border-color: #86efac;
            color: #166534;
            font-weight: 700;
            cursor: pointer;
        }

        .has-revenue:hover {
            background: #dcfce7;
            transform: scale(1.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            text-align: left;
            padding: 18px 15px;
            background: #f8fafc;
            color: #475569;
            font-size: 13px;
            font-weight: 700;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 16px 15px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }

        .amt-tag { font-weight: 800; color: var(--navy); }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>Karunesh Kumar & Associates Admin</h2>
        <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('billingMenu', 'billChev')">
                <i class="fas fa-file-invoice-dollar"></i> Billing
                <i class="fas fa-chevron-down" id="billChev" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="billingMenu">
                <a href="quotations.php">Quotations</a>
                <a href="invoices.php">Invoices</a>
                <a href="receipts.php">Receipts</a>
                <a href="outstanding.php">Outstanding</a>
            </div>
        </div>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('vaultMenu', 'vaultChev')">
                <i class="fas fa-folder-open"></i> Documents
                <i class="fas fa-chevron-down" id="vaultChev" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="vaultMenu">
                <a href="admin-review.php"></i> Quality Control</a>
                <a href="Master-Vault.php"></i> Services</a>
            </div>
        </div>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('reportsMenu', 'repChev')">
                <i class="fas fa-file-contract"></i> Reports
                <i class="fas fa-chevron-down" id="repChev" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="reportsMenu">
                <a href="dsc-register.php"></i> DSC Register</a>
                <a href="service-report.php" ></i> Service Report</a>
                <a href="revenue-analytics.php"class="active"></i> Revenue Analytics</a>
                <a href="Client-Revenue.php"></i>Client Revenue</a>
                <a href="attendance.php"></i> Attendance</a>
            </div>
        </div>

        <a href="assign-work.php"><i class="fas fa-tasks"></i> Assign Work</a>
         <div class="dropdown-container">
    <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('clientMenu', 'clientChev')">
        <i class="fas fa-users"></i> Manage Clients
        <i class="fas fa-chevron-down" id="clientChev" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
    </a>
    <div class="dropdown-content" id="clientMenu">
        <a href="manage-clients.php">Clients</a>
        <a href="client-groups.php">Client Groups</a>
        <a href="client-services.php">Services</a>
    </div>
</div>
        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
        <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <header class="admin-top-bar">
            <h1 style="color: var(--navy); margin: 0; font-size: 30px;">Revenue Analytics</h1>
            <p style="color: #64748b; margin: 5px 0 0 0;">Comprehensive financial performance tracking</p>
        </header>

        <div class="content-body">
            <div class="stats-row">
                <div class="stat-card">
                    <h4>Week Earnings</h4>
                    <div class="amt">₹<?= number_format($week_earning, 2) ?></div>
                    <small style="color:#22c55e"><i class="fas fa-arrow-up"></i> Last 7 days</small>
                </div>
                <div class="stat-card orange">
                    <h4>Month Earnings</h4>
                    <div class="amt">₹<?= number_format($month_earning, 2) ?></div>
                    <small style="color:var(--orange)">Current Month</small>
                </div>
                <div class="stat-card">
                    <h4>Year Earnings</h4>
                    <div class="amt">₹<?= number_format($year_earning, 2) ?></div>
                    <small style="color:var(--navy)">Annual Total (<?= $year ?>)</small>
                </div>
            </div>

            <div class="analytics-grid">
                <div class="card">
                    <h3 style="margin:0 0 20px 0; font-size:16px;">Monthly Comparison</h3>
                    <div style="height: 300px;"><canvas id="revenueBarChart"></canvas></div>
                </div>

                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h3 style="margin:0; font-size:16px;">Daily Tracking</h3>
                        <div style="font-size:13px;">
                            <a href="?m=<?= ($month == 1 ? 12 : $month - 1) ?>&y=<?= ($month == 1 ? $year - 1 : $year) ?>"><i class="fas fa-chevron-left"></i></a>
                            <span style="margin:0 10px; font-weight:700;"><?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?></span>
                            <a href="?m=<?= ($month == 12 ? 1 : $month + 1) ?>&y=<?= ($month == 12 ? $year + 1 : $year) ?>"><i class="fas fa-chevron-right"></i></a>
                        </div>
                    </div>
                    <div class="calendar-grid">
                        <?php
                        $h = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
                        foreach ($h as $day_h) echo "<div style='text-align:center; font-size:11px; font-weight:800; color:#94a3b8;'>$day_h</div>";
                        $f = date('w', mktime(0, 0, 0, $month, 1, $year));
                        $t = date('t', mktime(0, 0, 0, $month, 1, $year));
                        for ($i = 0; $i < $f; $i++) echo "<div></div>";
                        for ($d = 1; $d <= $t; $d++) {
                            $curr = sprintf("%04d-%02d-%02d", $year, $month, $d);
                            $amt = $revenue_days[$curr] ?? 0;
                            $cls = $amt > 0 ? 'has-revenue' : '';
                            $click = $amt > 0 ? "onclick=\"showDayDetails('$curr')\"" : "";
                            echo "<div class='day $cls' $click>$d</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="table-card">
                <h3 style="margin:0 0 15px 0; font-size:16px;">Detailed Revenue Ledger</h3>
                <table>
                    <thead>
                        <tr>
                            <th>TRANSACTION DATE</th>
                            <th>RECEIPT NUMBER</th>
                            <th>CLIENT NAME</th>
                            <th style="text-align:right;">NET AMOUNT PAID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($table_res->num_rows > 0): ?>
                            <?php while ($row = $table_res->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('d M, Y', strtotime($row['created_at'])) ?></td>
                                    <td style="font-weight:700; color:var(--orange);"><?= $row['receipt_no'] ?></td>
                                    <td><?= $row['client_name'] ?></td>
                                    <td style="text-align:right;" class="amt-tag">₹<?= number_format($row['amount_paid'], 2) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">No recent transactions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="revenueModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
        <div style="background:white; padding:30px; border-radius:20px; width:90%; max-width:500px; position:relative; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <span onclick="closeModal()" style="position:absolute; right:20px; top:15px; font-size:24px; cursor:pointer; color:#94a3b8;">&times;</span>
            <div id="modalContent">Loading...</div>
        </div>
    </div>

    <script>
        function toggleMenu(id) {
            document.getElementById(id).classList.toggle('show-menu');
        }

        // --- AJAX Modal Logic ---
        function showDayDetails(date) {
            const modal = document.getElementById('revenueModal');
            const content = document.getElementById('modalContent');
            modal.style.display = 'flex';
            content.innerHTML = '<div style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin"></i> Fetching details...</div>';

            fetch('get_day_revenue.php?date=' + date)
                .then(res => res.text())
                .then(html => { content.innerHTML = html; })
                .catch(() => { content.innerHTML = "Error loading data."; });
        }

        function closeModal() {
            document.getElementById('revenueModal').style.display = 'none';
        }

        window.onclick = function(e) {
            if (e.target == document.getElementById('revenueModal')) closeModal();
        }

        // --- Chart Logic ---
        const ctx = document.getElementById('revenueBarChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($month_labels) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode($month_values) ?>,
                    backgroundColor: '#0b3c74',
                    borderRadius: 8,
                    hoverBackgroundColor: '#ff8c00'
                }]
            },
           // FIND THIS SECTION IN YOUR CODE AND REPLACE IT:
options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { 
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: function(context) {
                    // This makes the pop-up show full currency format: ₹1,50,000.00
                    return 'Revenue: ' + new Intl.NumberFormat('en-IN', { 
                        style: 'currency', 
                        currency: 'INR' 
                    }).format(context.parsed.y);
                }
            }
        }
    },
    scales: {
        y: { 
            beginAtZero: true,
            // suggestedMax ensures the graph always looks "tall" 
            // even if you only have ₹4,000 in data.
            suggestedMax: 10000, 
            grid: { color: '#f1f5f9' },
            ticks: {
                // This converts 1,00,000 to "₹1 L" and 5,000 to "₹5k"
                callback: function(value) {
                    if (value >= 100000) return '₹' + (value / 100000).toFixed(1) + ' L';
                    if (value >= 1000) return '₹' + (value / 1000) + 'k';
                    return '₹' + value;
                },
                font: { weight: '600' },
                color: '#64748b'
            }
        },
        x: { 
            grid: { display: false },
            ticks: { color: '#64748b' }
        }
    }
}
        });
    </script>
</body>
</html>