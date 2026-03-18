<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Login.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);
$res = $conn->query("SELECT * FROM client_profiles WHERE client_id = '$id'");
$client = $res->fetch_assoc();

if (!$client) { die("Client profile not found in database."); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Profile | <?php echo htmlspecialchars($client['company_name'] ?: $id); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { 
            --navy: #0b3c74; 
            --orange: #ff8c00; 
            --sidebar: #082d56; 
            --bg: #f8fafc; 
            --border: #e2e8f0; 
        }
        body { display: flex; margin: 0; background: var(--bg); font-family: 'Inter', sans-serif; }
        
        /* EXACT SIDEBAR STYLE FROM MANAGE CLIENTS */
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
            border-bottom: 1px solid rgba(255,255,255,0.1); 
            padding-bottom: 20px; 
        }
        .sidebar a { 
            color: rgba(255,255,255,0.7); 
            text-decoration: none; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            padding: 14px; 
            border-radius: 12px; 
            transition: 0.3s; 
            margin-bottom: 8px; 
        }
        .sidebar a:hover, .sidebar a.active { 
            background: rgba(255,255,255,0.1); 
            color: white; 
            border-left: 4px solid var(--orange); 
        }

        /* Dropdown specific styles */
        .dropdown-content {
            display: none;
            background: rgba(0, 0, 0, 0.15);
            margin: 0 10px;
            border-radius: 10px;
            padding-left: 10px;
        }
        .dropdown-content a { font-size: 14px; padding: 10px 14px; border-left: none !important; }
        .show-menu { display: block !important; }
        .rotate-chevron { transform: rotate(180deg); }

        .main { margin-left: 280px; padding: 50px; width: calc(100% - 280px); box-sizing: border-box; }
        .container { max-width: 900px; background: white; padding: 40px; border-radius: 20px; border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        input, textarea { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 10px; font-size: 14px; transition: 0.3s; }
        input:focus { border-color: var(--orange); outline: none; box-shadow: 0 0 0 3px rgba(255,140,0,0.1); }
        
        .btn-save { background: var(--navy); color: white; border: none; padding: 16px; border-radius: 12px; cursor: pointer; font-weight: 700; width: 100%; font-size: 15px; transition: 0.3s; margin-top: 10px; }
        .btn-save:hover { background: var(--orange); transform: translateY(-2px); }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>Karunesh Kumar & Associates Admin</h2>
        <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>

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
        <a href="Master-Vault.php"><i class="fas fa-vault"></i> Master Vault</a>
        <a href="manage-clients.php" class="active"><i class="fas fa-users"></i> Manage Clients</a>
        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
        
        <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <div class="container">
            <div class="header-flex">
                <div>
                    <h2 style="margin:0; color:var(--navy);">Edit Client Profile</h2>
                    <p style="margin:5px 0 0 0; color:#94a3b8; font-size:14px;">Update details for <?php echo htmlspecialchars($client['owner_name']); ?></p>
                </div>
                <span style="background: #fff7ed; color: var(--orange); padding: 10px 20px; border-radius: 10px; font-weight: 800; font-family: monospace; border: 1px solid #ffedd5;">
                    ID: <?php echo $id; ?>
                </span>
            </div>

            <form action="manage-clients.php" method="POST">
                <input type="hidden" name="identifier" value="<?php echo $id; ?>">
                
                <div class="form-group">
                    <label>Company / Firm Name</label>
                    <input type="text" name="company_name" value="<?php echo htmlspecialchars($client['company_name']); ?>" required placeholder="Enter firm name">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Owner Name</label>
                        <input type="text" name="owner_name" value="<?php echo htmlspecialchars($client['owner_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($client['phone']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Business Email Address</label>
                    <input type="email" name="business_email" value="<?php echo htmlspecialchars($client['business_email']); ?>" placeholder="email@example.com">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>GST Number</label>
                        <input type="text" name="gst_no" value="<?php echo htmlspecialchars($client['gst_no']); ?>" placeholder="22AAAAA0000A1Z5">
                    </div>
                    <div class="form-group">
                        <label>PAN Number</label>
                        <input type="text" name="pan_no" value="<?php echo htmlspecialchars($client['pan_no']); ?>" placeholder="ABCDE1234F">
                    </div>
                </div>

                <div class="form-group">
                    <label>Office Address</label>
                    <textarea name="address" rows="3" placeholder="Enter full business address"><?php echo htmlspecialchars($client['address']); ?></textarea>
                </div>

                <button type="submit" name="update_client" class="btn-save">
                    <i class="fas fa-save" style="margin-right:8px;"></i> Save Changes & Update Directory
                </button>
            </form>
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