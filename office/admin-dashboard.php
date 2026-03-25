<?php
session_start();
include('../db.php');

// 1. Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}
// Calculate total revenue from the receipts table
$revenue_res = $conn->query("SELECT SUM(amount_paid) as total FROM receipts");
$revenue_data = $revenue_res->fetch_assoc();
$total_revenue = $revenue_data['total'] ?? 0;
// 2. Create User Logic (Handles both Clients and Staff)
if (isset($_POST['create'])) {
    $n = mysqli_real_escape_string($conn, $_POST['name']);
    $r = $_POST['role'];
    $p = $_POST['pass'];

   $id = mysqli_real_escape_string($conn, $_POST['email']);
$r = 'office'; // Hardcode the role to office/staff

    $sql = "INSERT INTO users (name, identifier, password, role) VALUES ('$n', '$id', '$p', '$r')";
    if ($conn->query($sql)) {
        echo "<script>alert('Account Created Successfully! ID: $id'); window.location='admin-dashboard.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>KKA Admin | Executive Suite</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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

        .rotate-chevron {
            transform: rotate(180deg);
        }

        :root {
            --navy: #0b3c74;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
            --danger: #ef4444;
        }

        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            color: #334155;
        }

      /* 1. Remove top and side padding so the header can touch the edges */
.main { 
    margin-left: 280px; 
    padding: 0; /* Changed from 50px to 0 to shift header to the very top */
    width: calc(100% - 280px); 
    min-height: 100vh;
}

/* 2. Transform into a stretched rectangle at the top */
.admin-top-bar { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    background: var(--sidebar); 
    padding: 20px 50px; 
    color: white; 
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    
    /* RECTANGLE SETTINGS */
    border-radius: 0;      /* Removes rounded corners */
    margin-bottom: 40px;   /* Space between header and content below */
    width: 100%;           /* Stretch to full width */
    box-sizing: border-box; 
    border-bottom: 3px solid var(--orange); /* Clean accent line */
}

/* 3. Re-apply padding ONLY to the body content so stats/forms aren't squashed */
.content-body {
    padding: 0 50px 50px 50px;
}

        .brand-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .brand-section h1 {
            margin: 0;
            font-size: 20px;
            letter-spacing: 0.5px;
        }

        .welcome-text {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
        }

        .welcome-text span {
            color: var(--orange);
            /* Orange Accent */
            font-weight: 700;
        }

        /* Clock Styling */
        .header-clock {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .clock-icon i {
            color: var(--orange);
            font-size: 20px;
        }

        .clock-display {
            display: flex;
            flex-direction: column;
        }

        .clock-display span {
            font-size: 11px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
        }

        .clock-display strong {
            font-size: 18px;
            color: white;
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

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        /* Reset Notification Card */
        .alert-card {
            background: #fff1f2;
            border: 1px solid #fecaca;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Stats */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
        }

        .stat i {
            color: var(--orange);
            font-size: 24px;
            margin-bottom: 10px;
        }

        .stat h3 {
            margin: 0;
            font-size: 14px;
            color: #64748b;
            text-transform: uppercase;
        }

        .stat h2 {
            margin: 10px 0 0;
            font-size: 32px;
            color: var(--navy);
        }

        /* Form */
        .card {
            background: white;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.04);
            max-width: 600px;
        }

        input,
        select {
            width: 100%;
            padding: 14px;
            margin: 10px 0;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            outline: none;
        }

        button {
            background: var(--navy);
            color: white;
            border: none;
            padding: 16px;
            width: 100%;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: var(--orange);
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <div class="sidebar">
    <h2>Karunesh Kumar & Associates Admin</h2>
    <a href="admin-dashboard.php" class="active"><i class="fas fa-chart-pie"></i> Dashboard</a>

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
        <header class="admin-top-bar">
            <div class="brand-section">
                <i class="fas fa-shield-alt" style="color: var(--orange); font-size: 24px;"></i>
                <div>
                    <h1>Firm Overview</h1>
                    <div class="welcome-text">
                        Welcome, <span>Admin Executive</span>
                        <i class="fas fa-circle" style="color:#22c55e; font-size:9px; margin-left:5px;"></i>
                    </div>
                </div>
            </div>

            <div class="header-clock">
                <div class="clock-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="clock-display">
                    <span id="liveDate">Loading Date...</span>
                    <strong id="liveTime">00:00:00</strong>
                </div>
            </div>
        </header>
        <?php
        $resets = $conn->query("SELECT id FROM users WHERE reset_requested = 1");
        if ($resets->num_rows > 0):
        ?>
            <div class="alert-card">
                <div style="display:flex; align-items:center; gap:15px;">
                    <i class="fas fa-exclamation-triangle" style="color:var(--danger); font-size:24px;"></i>
                    <div>
                        <strong style="color:#991b1b;">Action Required</strong>
                        <p style="margin:2px 0 0; color:#7f1d1d; font-size:14px;">There are <b><?php echo $resets->num_rows; ?></b> staff password reset requests pending.</p>
                    </div>
                </div>
                <a href="manage-employees.php" style="color:white; background:var(--danger); padding:8px 16px; border-radius:8px; text-decoration:none; font-size:13px; font-weight:bold;">Handle Now</a>
            </div>
        <?php endif; ?>
        <div class="stat-grid" style="grid-template-columns: repeat(4, 1fr);">
            <a href="manage-clients.php" style="text-decoration: none; color: inherit;">
                <div class="stat" style="cursor: pointer; transition: 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                    <i class="fas fa-user-friends"></i>
                    <h3>Total Clients</h3>
                    <h2><?php echo $conn->query("SELECT id FROM users WHERE role='client'")->num_rows; ?></h2>
                </div>
            </a>

            <a href="manage-employees.php" style="text-decoration: none; color: inherit;">
                <div class="stat" style="cursor: pointer; transition: 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                    <i class="fas fa-user-tie"></i>
                    <h3>Total Staff</h3>
                    <h2><?php echo $conn->query("SELECT id FROM users WHERE role='office'")->num_rows; ?></h2>
                </div>
            </a>

            <a href="assign-work.php" style="text-decoration: none; color: inherit;">
                <div class="stat" style="cursor: pointer; transition: 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                    <i class="fas fa-tasks"></i>
                    <h3>Open Requests</h3>
                    <h2><?php echo $conn->query("SELECT id FROM service_requests WHERE status='Pending'")->num_rows; ?></h2>
                </div>
            </a>

            <a href="receipts.php" style="text-decoration: none; color: inherit;">
                <div class="stat" style="cursor: pointer; transition: 0.3s; border-bottom: 4px solid transparent; border-radius:20px;" onmouseover="this.style.transform='translateY(-5px)'; this.style.borderColor='var(--orange)'" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='transparent'">
                    <i class="fas fa-wallet" style="color: var(--orange);"></i>
                    <h3>Total Payments</h3>
                    <h2>₹<?php echo number_format($total_revenue, 2); ?></h2>
                </div>
            </a>
        </div>
        <div class="card">
            <h3 style="margin-top:0;"><i class="fas fa-user-plus"></i> Onboard New Staff</h3>
<form method="POST">
    <input type="text" name="name" placeholder="Full Name" required>

    <input type="email" name="email" placeholder="staff.name@kka.com" required>

    <input type="text" name="pass" placeholder="Set Initial Password" required>

    <input type="hidden" name="role" value="office">

    <button name="create">Generate Staff Profile</button>
</form>
        </div>
    </div>

    <script>
        function toggleEmailField() {
            const role = document.getElementById('roleSelect').value;
            const wrapper = document.getElementById('emailWrapper');
            const input = document.getElementById('emailInput');

            if (role === 'office') {
                wrapper.style.display = 'block';
                input.setAttribute('required', 'required');
            } else {
                wrapper.style.display = 'none';
                input.removeAttribute('required');
            }
        }

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

        function updateClock() {
            const now = new Date();

            // Format Date: e.g., Wednesday, 04 Mar 2026
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'short',
                day: '2-digit'
            };
            const dateString = now.toLocaleDateString('en-GB', options);

            // Format Time: e.g., 02:45:10 PM
            const timeString = now.toLocaleTimeString('en-GB', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });

            document.getElementById('liveDate').textContent = dateString;
            document.getElementById('liveTime').textContent = timeString;
        }

        // Update the clock every 1 second
        setInterval(updateClock, 1000);
        updateClock(); // Run immediately on load
    </script>
</body>

</html>