<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Live Attendance | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Billing Dropdown Styling */
        .dropdown-content {
            display: none;
            background: rgba(0, 0, 0, 0.2);
            margin: 0 5px;
            border-radius: 8px;
            padding-left: 15px;
            /* Indent sub-items */
        }

        .dropdown-content a {
            font-size: 14px;
            padding: 10px;
            color: rgba(255, 255, 255, 0.6);
        }

        .dropdown-content a:hover {
            color: var(--orange);
            border-left: none;
            /* No border for sub-items */
            background: transparent;
        }

        .dropdown-btn {
            cursor: pointer;
        }

        /* When the dropdown is open */
        .show-menu {
            display: block;
        }

        .rotate-chevron {
            transform: rotate(90deg);
        }

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

        .main {
            margin-left: 280px;
            padding: 50px;
            width: calc(100% - 280px);
        }

        .attendance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        /* Dynamic Card Borders */
        .staff-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            border-top: 5px solid #cbd5e1;
            position: relative;
        }

        .staff-card.online {
            border-top-color: #22c55e;
        }

        /* Green border if online */

        /* Dynamic Badge Colors */
        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-online {
            background: #dcfce7;
            color: #166534;
        }

        .badge-offline {
            background: #f1f5f9;
            color: #64748b;
        }

        .avatar {
            width: 60px;
            height: 60px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--navy);
            margin-bottom: 15px;
        }

        .staff-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--navy);
            margin: 0;
        }

        .staff-email {
            font-size: 14px;
            color: #64748b;
            margin: 5px 0 15px 0;
        }

        .log-box {
            background: #f8fafc;
            padding: 10px;
            border-radius: 10px;
            font-size: 13px;
            color: #475569;
        }
    </style>
</head>

<body>

     <div class="sidebar">
    <h2>Karunesh Kumar & Associates Admin</h2>
    <a href="admin-dashboard.php" ><i class="fas fa-chart-pie"></i> Dashboard</a>

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
        <a href="javascript:void(0)" class="dropdown-btn"class="active" onclick="toggleMenu('reportsMenu', 'repChev')">
            <i class="fas fa-file-contract"></i> Reports
            <i class="fas fa-chevron-down" id="repChev" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
        </a>
        <div class="dropdown-content" id="reportsMenu">
           <a href="dsc-register.php" ></i> DSC Register</a>
           <a href="service-report.php"></i> Service Report</a>
           <a href="revenue-analytics.php"></i> Revenue Analytics</a>
            <a href="attendance.php" class="active"></i> Attendance</a>
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
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
            <div>
                <h1 style="margin:0;">Staff Attendance</h1>
                <p style="color: #64748b; margin: 5px 0 0 0;">Live status for <?php echo date('d M, Y'); ?></p>
            </div>
            <button onclick="window.location.reload();" style="background: var(--navy); color: white; border:none; padding: 10px 20px; border-radius: 10px; cursor: pointer;">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </header>

        <div class="attendance-grid">
            <?php
            // Fetch all staff
            $res = $conn->query("SELECT * FROM users WHERE role='office' ORDER BY name ASC");

            while ($staff = $res->fetch_assoc()) {
                $email = $staff['identifier'];

                // CHECK IF STAFF HAS LOGGED IN TODAY
                // NOTE: This assumes you have a table named 'attendance' with columns 'email', 'log_date', and 'login_time'
                $check = $conn->query("SELECT login_time FROM attendance WHERE email='$email' AND log_date='$today' LIMIT 1");

                $is_online = ($check->num_rows > 0);
                $att_data = $check->fetch_assoc();
            ?>

                <div class="staff-card <?php echo $is_online ? 'online' : ''; ?>">
                    <?php if ($is_online): ?>
                        <span class="status-badge badge-online">PRESENT</span>
                    <?php else: ?>
                        <span class="status-badge badge-offline">ABSENT</span>
                    <?php endif; ?>

                    <div class="avatar">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3 class="staff-name"><?php echo $staff['name']; ?></h3>
                    <p class="staff-email"><?php echo $email; ?></p>

                    <div class="log-box">
                        <?php if ($is_online): ?>
                            <i class="far fa-clock" style="color: #22c55e;"></i>
                            In: <strong><?php echo date('h:i A', strtotime($att_data['login_time'])); ?></strong>
                        <?php else: ?>
                            <i class="fas fa-exclamation-circle" style="color: #94a3b8;"></i>
                            Not checked in yet.
                        <?php endif; ?>
                    </div>
                </div>

            <?php
            }
            ?>
        </div>
        <div class="ledger-card" style="background: white; padding: 30px; border-radius: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-top: 30px;">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom: 20px;">
        <div style="width:4px; height:20px; background:#ff8c00; border-radius:10px;"></div>
        <h2 style="margin:0; font-size: 18px; color: #0b3c74;">Today's Check-in Ledger</h2>
    </div>
    
    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <thead>
            <tr style="background: #f8fafc; color: #64748b; font-size: 13px; text-align: left;">
                <th style="padding: 15px; border-bottom: 2px solid #e2e8f0;">EMPLOYEE NAME</th>
                <th style="padding: 15px; border-bottom: 2px solid #e2e8f0;">IDENTIFIER (EMAIL)</th>
                <th style="padding: 15px; border-bottom: 2px solid #e2e8f0;">DATE</th>
                <th style="padding: 15px; border-bottom: 2px solid #e2e8f0;">CHECK-IN TIME</th>
                <th style="padding: 15px; border-bottom: 2px solid #e2e8f0;">STATUS</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetching only employees who checked in today
            $ledger_sql = "SELECT a.*, u.name 
                           FROM attendance a 
                           JOIN users u ON a.email = u.identifier 
                           WHERE a.log_date = '$today' 
                           ORDER BY a.login_time DESC";
            $ledger_res = $conn->query($ledger_sql);

            if ($ledger_res->num_rows > 0):
                while ($row = $ledger_res->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9; font-size: 14px;">
                        <td style="padding: 15px; font-weight: 700; color: #334155;"><?= $row['name'] ?></td>
                        <td style="padding: 15px; color: #64748b;"><?= $row['email'] ?></td>
                        <td style="padding: 15px;"><?= date('d M, Y', strtotime($row['log_date'])) ?></td>
                        <td style="padding: 15px;">
                            <span style="background: #eff6ff; color: #1d4ed8; padding: 4px 8px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                                <?= date('h:i A', strtotime($row['login_time'])) ?>
                            </span>
                        </td>
                        <td style="padding: 15px;">
                            <span style="color: #059669; font-weight: 700;">
                                <i class="fas fa-check-circle"></i> PRESENT
                            </span>
                        </td>
                    </tr>
                <?php endwhile; 
            else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">
                        <i class="fas fa-user-clock" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                        No check-ins recorded for today yet.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
    </div>
    <script>
       function toggleMenu(menuId, chevronId) {
    const menu = document.getElementById(menuId);
    const chevron = document.getElementById(chevronId);

    // Toggle the specific menu clicked
    menu.classList.toggle('show-menu');

    // Rotate the specific arrow clicked
    chevron.classList.toggle('rotate-chevron');

    // Optional: Close other menus when opening a new one
    const allMenus = document.querySelectorAll('.dropdown-content');
    const allChevrons = document.querySelectorAll('.fa-chevron-down');

    allMenus.forEach((m) => {
        if (m.id !== menuId) m.classList.remove('show-menu');
    });
    
    allChevrons.forEach((c) => {
        if (c.id !== chevronId) c.classList.remove('rotate-chevron');
    });
}
    </script>
</body>

</html>