<?php
session_start();
include('../db.php');

// 1. Security Check
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

// 2. Create User Logic (Handles both Clients and Staff)
if(isset($_POST['create'])){
    $n = mysqli_real_escape_string($conn, $_POST['name']);
    $r = $_POST['role']; 
    $p = $_POST['pass'];
    
    if($r == 'client'){
        // Auto-generate KK-ID for clients
        $c = $conn->query("SELECT id FROM users WHERE role='client'")->num_rows + 1;
        $id = "KK/2026/".str_pad($c, 3, "0", STR_PAD_LEFT);
    } else { 
        // Use Email for office staff
        $id = mysqli_real_escape_string($conn, $_POST['email']); 
    }
    
    $sql = "INSERT INTO users (name, identifier, password, role) VALUES ('$n', '$id', '$p', '$r')";
    if($conn->query($sql)) {
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
    display: none; /* Hidden by default */
    background: rgba(0, 0, 0, 0.15);
    margin: 0 10px;
    border-radius: 10px;
    padding-left: 10px;
}

.dropdown-content a {
    font-size: 14px;
    padding: 10px 14px;
    margin-bottom: 2px;
    border-left: none !important; /* Remove the orange border from sub-items */
}

.dropdown-content a:hover {
    background: rgba(255, 255, 255, 0.05);
    color: var(--orange);
}

.show-menu {
    display: block !important;
}

.rotate-chevron {
    transform: rotate(180deg);
}
        :root { --navy: #0b3c74; --orange: #ff8c00; --sidebar: #082d56; --bg: #f8fafc; --danger: #ef4444; }
        body { display:flex; margin:0; background:var(--bg); font-family: 'Inter', sans-serif; color: #334155; }
        
        /* Sidebar */
        .sidebar { width:280px; background:var(--sidebar); color:white; height:100vh; position:fixed; padding:30px 20px; box-sizing: border-box; display:flex; flex-direction:column; }
        .sidebar h2 { font-size: 22px; color: var(--orange); margin-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .sidebar a { color:rgba(255,255,255,0.7); text-decoration:none; display:flex; align-items:center; gap:12px; padding:14px; margin-bottom:8px; border-radius:12px; transition: 0.3s; }
        .sidebar a:hover, .active { background:rgba(255,255,255,0.1); color:white; border-left: 4px solid var(--orange); }
        
        /* Main Area */
        .main { margin-left:280px; padding:50px; width:calc(100% - 280px); }
        header { display:flex; justify-content:space-between; align-items:center; margin-bottom:40px; }
        
        /* Reset Notification Card */
        .alert-card { background: #fff1f2; border: 1px solid #fecaca; padding: 20px; border-radius: 16px; margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between; animation: slideDown 0.5s ease; }
        @keyframes slideDown { from { opacity:0; transform: translateY(-10px); } to { opacity:1; transform: translateY(0); } }

        /* Stats */
        .stat-grid { display:grid; grid-template-columns: repeat(3, 1fr); gap:25px; margin-bottom:40px; }
        .stat { background:white; padding:30px; border-radius:20px; box-shadow:0 10px 25px rgba(0,0,0,0.03); }
        .stat i { color: var(--orange); font-size: 24px; margin-bottom: 10px; }
        .stat h3 { margin:0; font-size:14px; color:#64748b; text-transform:uppercase; }
        .stat h2 { margin:10px 0 0; font-size:32px; color:var(--navy); }

        /* Form */
        .card { background:white; padding:40px; border-radius:24px; box-shadow:0 20px 40px rgba(0,0,0,0.04); max-width:600px; }
        input, select { width:100%; padding:14px; margin:10px 0; border:1.5px solid #e2e8f0; border-radius:12px; font-size:16px; outline:none; }
        button { background:var(--navy); color:white; border:none; padding:16px; width:100%; border-radius:12px; font-weight:700; cursor:pointer; transition:0.3s; }
        button:hover { background:var(--orange); transform: translateY(-2px); }
    </style>
</head>
<body>
 <div class="sidebar">
    <h2>KKA ADMIN</h2>
    <a href="admin-dashboard.php" class="active"><i class="fas fa-chart-pie"></i> Summary</a>

    <div class="dropdown-container">
        <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleBilling()">
            <i class="fas fa-file-invoice-dollar"></i> Billing 
            <i class="fas fa-chevron-down" id="chevron" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
        </a>
        <div class="dropdown-content" id="billingMenu">
            <a href="quotations.php"><i class="fas fa-file-signature"></i> Quotations</a>
            <a href="invoices.php"><i class="fas fa-receipt"></i> Invoices</a>
            <a href="receipts.php"><i class="fas fa-check-double"></i> Receipts</a>
            <a href="outstanding.php"><i class="fas fa-exclamation-circle"></i> Outstanding</a>
        </div>
    </div>

    <a href="assign-work.php"><i class="fas fa-tasks"></i> Assign Work</a>
    <a href="admin-review.php"><i class="fas fa-file-signature"></i> Quality Control</a>
    <a href="manage-clients.php"><i class="fas fa-users"></i> Manage Clients</a>
    <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
    <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
    <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
    <div class="main">
        <header>
            <h1>Firm Overview</h1>
            <div class="user-info"><b>Welcome, Admin</b> <i class="fas fa-circle" style="color:#22c55e; font-size:10px; margin-left:5px;"></i></div>
        </header>

        <?php
        $resets = $conn->query("SELECT id FROM users WHERE reset_requested = 1");
        if($resets->num_rows > 0):
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

        <div class="stat-grid">
    <a href="manage-clients.php" style="text-decoration: none; color: inherit;">
        <div class="stat" style="cursor: pointer; transition: 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
            <i class="fas fa-user-friends"></i>
            <h3>Total Clients</h3>
            <h2><?php echo $conn->query("SELECT id FROM users WHERE role='client'")->num_rows; ?></h2>
        </div>
    </a>

    <a href="manage-employees.php" style="text-decoration: none; color: inherit;">
        <div class="stat" style="cursor: pointer; transition: 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
            <i class="fas fa-briefcase"></i>
            <h3>Total Staff</h3>
            <h2><?php echo $conn->query("SELECT id FROM users WHERE role='office'")->num_rows; ?></h2>
        </div>
    </a>

    <a href="assign-work.php" style="text-decoration: none; color: inherit;">
        <div class="stat" style="cursor: pointer; transition: 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
            <i class="fas fa-clock"></i>
            <h3>Open Requests</h3>
            <h2><?php echo $conn->query("SELECT id FROM service_requests WHERE status='Pending'")->num_rows; ?></h2>
        </div>
    </a>
</div>

        <div class="card">
            <h3 style="margin-top:0;"><i class="fas fa-user-plus"></i> Onboard New User</h3>
            <form method="POST">
                <input type="text" name="name" placeholder="Full Name" required>
                
                <select name="role" id="roleSelect" onchange="toggleEmailField()">
                    <option value="client">Client (System assigns KK-ID)</option>
                    <option value="office">Employee (Needs Office Email)</option>
                </select>

                <div id="emailWrapper" style="display:none;">
                    <input type="email" name="email" id="emailInput" placeholder="staff.name@kka.com">
                </div>

                <input type="text" name="pass" placeholder="Set Initial Password" required>
                <button name="create">Generate Profile & Notify</button>
            </form>
        </div>
    </div>

    <script>
        function toggleEmailField() {
            const role = document.getElementById('roleSelect').value;
            const wrapper = document.getElementById('emailWrapper');
            const input = document.getElementById('emailInput');
            
            if(role === 'office') {
                wrapper.style.display = 'block';
                input.setAttribute('required', 'required');
            } else {
                wrapper.style.display = 'none';
                input.removeAttribute('required');
            }
        }
        function toggleBilling() {
    const menu = document.getElementById('billingMenu');
    const chevron = document.getElementById('chevron');
    
    // Toggle the visibility of the menu
    menu.classList.toggle('show-menu');
    
    // Rotate the arrow icon
    chevron.classList.toggle('rotate-chevron');
}
    </script>
</body>
</html>