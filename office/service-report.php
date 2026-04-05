<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Login.php");
    exit();
}

// 1. Fetch ALL clients who have services assigned
$query = "SELECT task_asked FROM client_profiles WHERE task_asked IS NOT NULL AND task_asked != ''";
$result = $conn->query($query);

$service_counts = [];

while ($row = $result->fetch_assoc()) {
    $individual_services = explode(', ', $row['task_asked']);
    foreach ($individual_services as $service) {
        $service = trim($service);
        if ($service != "") {
            if (!isset($service_counts[$service])) {
                $service_counts[$service] = 0;
            }
            $service_counts[$service]++;
        }
    }
}
arsort($service_counts);

$services = array_keys($service_counts);
$counts = array_values($service_counts);
$js_labels = json_encode($services);
$js_data = json_encode($counts);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Service Analytics | KKA Admin</title>
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
        }

        /* Sidebar */
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
        .active {
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
            padding: 40px;
            box-sizing: border-box;
        }

        .report-container {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.02);
            border: 1px solid #e2e8f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            text-align: left;
            background: #f1f5f9;
            color: #64748b;
            padding: 15px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            font-weight: 500;
        }

        .badge-count {
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 700;
        }
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
                <a href="service-report.php" class="active"></i> Service Report</a>
                <a href="revenue-analytics.php"></i> Revenue Analytics</a>
                <a href="attendance.php"></i> Attendance</a>
            </div>
        </div>

        <a href="assign-work.php"><i class="fas fa-tasks"></i> Assign Work</a>
        <a href="manage-clients.php"><i class="fas fa-users"></i> Manage Clients</a>
        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
        <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <header style="margin-bottom: 30px;">
            <h1 style="color: var(--navy); margin: 0;">Service Analytics Report</h1>
            <p style="color: #64748b;">Client distribution across various firm services</p>
        </header>

        <div class="report-container">
            <div class="card">
                <h3 style="margin-top:0; color:var(--navy);"><i class="fas fa-list-ul" style="color:var(--orange);"></i> Service Breakdown</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Sr. No</th>
                            <th>Service Description</th>
                            <th style="text-align:center;">No. of Clients</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sr_no = 1;
                        foreach ($service_counts as $service_name => $client_count): ?>
                            <tr>
                                <td><?php echo $sr_no++; ?></td>
                                <td><?php echo htmlspecialchars($service_name); ?></td>
                                <td style="text-align:center;">
                                    <span class="badge-count"><?php echo $client_count; ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <div class="chart-header" style="text-align: center; margin-bottom: 20px;">
                    <h3 style="margin:0; color:var(--navy);">Service Share</h3>
                    <p style="font-size:12px; color:#94a3b8;">Percentage distribution</p>
                </div>
                <div style="position: relative; margin: auto; height:300px; width:300px;">
                    <canvas id="serviceChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // DROP-DOWN LOGIC
        function toggleMenu(menuId, chevronId) {
            const menu = document.getElementById(menuId);
            const chevron = document.getElementById(chevronId);
            menu.classList.toggle('show-menu');
            chevron.classList.toggle('rotate-chevron');

            const allMenus = document.querySelectorAll('.dropdown-content');
            const allChevrons = document.querySelectorAll('.fa-chevron-down');
            allMenus.forEach((m) => {
                if (m.id !== menuId) m.classList.remove('show-menu');
            });
            allChevrons.forEach((c) => {
                if (c.id !== chevronId) c.classList.remove('rotate-chevron');
            });
        }

        // CHART LOGIC
        const ctx = document.getElementById('serviceChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?php echo $js_labels; ?>,
                datasets: [{
                    data: <?php echo $js_data; ?>,
                    backgroundColor: ['#818cf8', '#fbbf24', '#34d399', '#f87171', '#60a5fa', '#f472b6', '#fb923c', '#a78bfa'],
                    hoverOffset: 15,
                    borderWidth: 5,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 12,
                                weight: '600'
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    </script>

</body>

</html>