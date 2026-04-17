<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Query focusing strictly on Firm, Owner, Contact, and Unpaid breakdown
$query = "SELECT 
            cp.company_name, 
            cp.owner_name, 
            cp.business_email, 
            cp.phone,
            COALESCE(SUM(i.paid_amount), 0) as total_paid,
            GROUP_CONCAT(
                CASE WHEN (i.amount - i.paid_amount) > 0 
                THEN CONCAT(i.invoice_no, ' (₹', FORMAT(i.amount - i.paid_amount, 2), ')') 
                ELSE NULL END 
                SEPARATOR '<br>'
            ) as unpaid_breakdown
          FROM client_profiles cp
          LEFT JOIN invoices i ON cp.client_id = i.client_id
          WHERE 1=1 ";

if ($search) {
    $query .= " AND (cp.company_name LIKE '%$search%' OR cp.owner_name LIKE '%$search%')";
}

$query .= " GROUP BY cp.client_id ORDER BY cp.company_name ASC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Revenue Ledger | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        /* Main Area */
        .main {
            margin-left: 280px;
            padding: 50px;
            width: calc(100% - 280px);
        }
        

        /* Ensure the main container allows for a sticky header */
.main {
    margin-left: 280px;
    width: calc(100% - 280px);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.admin-top-bar h2 {
            margin: 0;
            font-size: 30px;
            font-weight: 700;
        }

/* Add this to give the content some breathing room below the sticky bar */
.content-body {
    padding: 40px 60px;
    flex: 1;
}

       .search-container input {
            padding: 12px 15px;
            width: 320px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            outline: none;
        }

        .search-container button {
            padding: 10px 20px;
            background: var(--navy);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        * Spacious Table Styling */
        .table-card {
            background: white;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        /* 1. Add extra margin to the container to push the table down */
.table-card {
    margin-top: 40px; /* This pushes the table down from the search bar */
    background: white;
    border-radius: 12px;
    border: 1px solid var(--border);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); /* Softer shadow */
    overflow: hidden;
}

/* 2. Adjust the Header to feel lighter and "Upper" */


/* 3. Improved Table Spacing */
table {
    width: 100%;
    border-collapse: separate; /* Changed to separate for better radius control */
    border-spacing: 0;
}

th {
    background: #f8fafc;
    padding: 22px 20px; /* Taller headers */
    text-align: left;
    font-size: 11px;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    border-bottom: 2px solid #edf2f7;
}

td {
    padding: 25px 20px; /* Very spacious rows */
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
    vertical-align: middle;
    color: #334155;
}

/* 4. Row Hover Effect */
tr:hover td {
    background-color: #f8fbff;
    transition: background 0.2s ease;
}

        .unpaid-list {
            color: #ef4444;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.6;
        }

        .paid-amt {
            color: #22c55e;
            font-weight: 700;
        }
         /* Billing Dropdown specific styles */
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
        <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('reportsMenu', 'repChev')">
            <i class="fas fa-file-contract"></i> Reports
            <i class="fas fa-chevron-down" id="repChev" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
        </a>
        <div class="dropdown-content" id="reportsMenu">
           <a href="dsc-register.php"></i> DSC Register</a>
           <a href="service-report.php"></i> Service Report</a>
           <a href="revenue-analytics.php"></i> Revenue Analytics</a>
           <a href="Client-Revenue.php" class="active"></i>Client Revenue</a>
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
            <h2>Client Revenue Report</h2>
        </header>

        <div class="content-body">
            <form method="GET" class="search-container">
                <input type="text" name="search" placeholder="Search firm..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" style="padding:10px; background:var(--navy); color:white; border:none; border-radius:8px; cursor:pointer;">Filter</button>
            </form>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Firm Name</th>
                            <th>Client Name</th>
                            <th>Contact Information</th>
                            <th>Paid Amount</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-weight:700; color:var(--navy);"><?php echo $row['company_name']; ?></td>
                                    <td><?php echo $row['owner_name']; ?></td>
                                    <td>
                                        <div style="font-size:12px;">
                                            <i class="fas fa-envelope"></i> <?php echo $row['business_email']; ?><br>
                                            <i class="fas fa-phone"></i> <?php echo $row['phone']; ?>
                                        </div>
                                    </td>
                                    <td class="paid-amt">₹<?php echo number_format($row['total_paid'], 2); ?></td>
                                    
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center;">No records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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