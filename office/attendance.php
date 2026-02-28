<?php
session_start();
include('../db.php');

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
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
        :root { --navy: #0b3c74; --orange: #ff8c00; --sidebar: #082d56; --bg: #f8fafc; }
        body { display:flex; margin:0; background:var(--bg); font-family: 'Inter', sans-serif; color: #334155; }
        
        .sidebar { width:280px; background:var(--sidebar); color:white; height:100vh; position:fixed; padding:30px 20px; box-sizing: border-box; display:flex; flex-direction:column; }
        .sidebar h2 { font-size: 22px; color: var(--orange); margin-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .sidebar a { color:rgba(255,255,255,0.7); text-decoration:none; display:flex; align-items:center; gap:12px; padding:14px; margin-bottom:8px; border-radius:12px; transition: 0.3s; }
        .sidebar a:hover, .active { background:rgba(255,255,255,0.1); color:white; border-left: 4px solid var(--orange); }
        
        .main { margin-left:280px; padding:50px; width:calc(100% - 280px); }
        
        .attendance-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        
        /* Dynamic Card Borders */
        .staff-card { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-top: 5px solid #cbd5e1; position: relative; }
        .staff-card.online { border-top-color: #22c55e; } /* Green border if online */

        /* Dynamic Badge Colors */
        .status-badge { position: absolute; top: 15px; right: 15px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-online { background: #dcfce7; color: #166534; }
        .badge-offline { background: #f1f5f9; color: #64748b; }
        
        .avatar { width: 60px; height: 60px; background: #f1f5f9; border-radius: 50%; display: flex; align-items:center; justify-content: center; font-size: 24px; color: var(--navy); margin-bottom: 15px; }
        .staff-name { font-size: 18px; font-weight: 700; color: var(--navy); margin: 0; }
        .staff-email { font-size: 14px; color: #64748b; margin: 5px 0 15px 0; }
        
        .log-box { background: #f8fafc; padding: 10px; border-radius: 10px; font-size: 13px; color: #475569; }
    </style>
</head>
<body>

     <div class="sidebar">
        <h2>KKA ADMIN</h2>
        <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> Summary</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleBilling()">
                <i class="fas fa-file-invoice-dollar"></i> Billing
                <i class="fas fa-chevron-right" id="chevron" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="billingMenu">
                <a href="quotations.php"><i class="fas fa-file-signature"></i> Quotations</a>
                <a href="invoices.php"><i class="fas fa-receipt"></i> Invoices</a>
                <a href="receipts.php"><i class="fas fa-check-double"></i> Receipts</a>
                <a href="outstanding.php"><i class="fas fa-exclamation-circle"></i> Outstanding</a>
            </div>
        </div>

        <a href="assign-work.php" ><i class="fas fa-tasks"></i> Assign Work</a>
        <a href="admin-review.php"><i class="fas fa-file-signature"></i> Quality Control</a>
        <a href="manage-clients.php"><i class="fas fa-users"></i> Manage Clients</a>
        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="attendance.php"class="active"><i class="fas fa-calendar-check"></i> Attendance</a>
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
            
            while($staff = $res->fetch_assoc()) {
                $email = $staff['identifier'];
                
                // CHECK IF STAFF HAS LOGGED IN TODAY
                // NOTE: This assumes you have a table named 'attendance' with columns 'email', 'log_date', and 'login_time'
                $check = $conn->query("SELECT login_time FROM attendance WHERE email='$email' AND log_date='$today' LIMIT 1");
                
                $is_online = ($check->num_rows > 0);
                $att_data = $check->fetch_assoc();
                ?>
                
                <div class="staff-card <?php echo $is_online ? 'online' : ''; ?>">
                    <?php if($is_online): ?>
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
                        <?php if($is_online): ?>
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
    </div>
    <script>
        function toggleBilling() {
            const menu = document.getElementById('billingMenu');
            const chevron = document.getElementById('chevron');

            menu.classList.toggle('show-menu');
            chevron.classList.toggle('rotate-chevron');
        }
    </script>
</body>
</html>