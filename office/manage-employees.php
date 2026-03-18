<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

$today = date('Y-m-d');

// 1. Delete Logic
if (isset($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    $conn->query("DELETE FROM users WHERE id='$id' AND role='office'");
    header("Location: manage-employees.php");
}

// 2. Password Update Logic
if (isset($_POST['update_pass'])) {
    $uid = $_POST['user_id'];
    $new_p = mysqli_real_escape_string($conn, $_POST['new_password']);
    $conn->query("UPDATE users SET password='$new_p', reset_requested=0 WHERE id='$uid'");
    echo "<script>alert('Password updated successfully!'); window.location='manage-employees.php';</script>";
}

// 3. Salary Payout Logic
if (isset($_POST['process_salary'])) {
    $uid = $_POST['user_id'];
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $month = mysqli_real_escape_string($conn, $_POST['month_year']);
    
    // Check if bank details exist
    $check = $conn->query("SELECT bank_name, account_number, upi_id FROM users WHERE id='$uid'")->fetch_assoc();
    
    if (empty($check['bank_name']) && empty($check['upi_id'])) {
        echo "<script>alert('Error: This employee has not added any payment details!'); window.location='manage-employees.php';</script>";
    } else {
        $trans_id = "KKA-" . strtoupper(bin2hex(random_bytes(4)));
        $now = date('Y-m-d H:i:s');

        $sql = "INSERT INTO salaries (user_id, month_year, amount, status, transaction_id, paid_at) 
                VALUES ('$uid', '$month', '$amount', 'Paid', '$trans_id', '$now')";
        
        if ($conn->query($sql)) {
            echo "<script>alert('Payment Recorded! Transaction ID: $trans_id'); window.location='manage-employees.php';</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Staff | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --navy: #0b3c74;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
            --danger: #ef4444;
            --success: #22c55e;
        }

        body { display: flex; margin: 0; background: var(--bg); font-family: 'Inter', sans-serif; }

        .sidebar { width: 280px; background: var(--sidebar); color: white; height: 100vh; position: fixed; padding: 30px 20px; box-sizing: border-box; display: flex; flex-direction: column; border-right: 4px solid var(--orange); }
        .sidebar h2 { color: var(--orange); margin-bottom: 40px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 20px; font-size: 22px; }
        .sidebar a { color: rgba(255, 255, 255, 0.7); text-decoration: none; display: flex; align-items: center; gap: 12px; padding: 14px; margin-bottom: 8px; border-radius: 12px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255, 255, 255, 0.1); color: white; border-left: 4px solid var(--orange); }

        .main { margin-left: 280px; padding: 50px; width: calc(100% - 280px); }
        
        /* Search Bar Styling */
        .search-container {
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            background: white;
            padding: 12px 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .search-container i { color: #94a3b8; margin-right: 15px; }
        .search-container input {
            border: none;
            outline: none;
            width: 100%;
            font-size: 15px;
            color: var(--navy);
        }

        .table-card { background: white; padding: 30px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03); border: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 18px; background: #f8fafc; color: var(--navy); font-weight: 700; text-transform: uppercase; font-size: 11px; }
        td { padding: 18px; border-bottom: 1px solid #f1f5f9; color: #475569; font-size: 13px; }

        .btn-update { background: var(--navy); color: white; border: none; padding: 8px 15px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 11px; display: inline-flex; align-items: center; gap: 5px; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; gap: 5px; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-absent { background: #f1f5f9; color: #64748b; }

        /* Dropdown Styles */
        .dropdown-content { display: none; background: rgba(0, 0, 0, 0.2); margin: 0 5px; border-radius: 8px; padding-left: 15px; }
        .dropdown-content a { font-size: 14px; padding: 10px; color: rgba(255, 255, 255, 0.6); }
        .show-menu { display: block; }
        .rotate-chevron { transform: rotate(90deg); }
    </style>
</head>

<body>
    <div class="sidebar">
        <h2>Karunesh Kumar & Associates Admin</h2>
         <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i>Dashboard</a>

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
        <a href="Master-Vault.php" ><i class="fas fa-file-signature"></i>Master Vault</a>
        <a href="manage-clients.php"><i class="fas fa-users"></i> Manage Clients</a>
        <a href="manage-employees.php"class="active"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
        <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <h1 style="color: var(--navy); margin-bottom: 10px;">Employee Management</h1>
        <p style="color: #64748b; margin-bottom: 30px;">Manage staff details, verify bank info, and process payroll.</p>

        <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" id="employeeSearch" placeholder="Search by employee name or email..." onkeyup="filterEmployees()">
        </div>

        <div class="table-card">
            <table id="employeeTable">
                <thead>
                    <tr>
                        <th>Staff Member</th>
                        <th>Duty Status</th>
                        <th>Account Details</th>
                        <th>Quick Reset</th>
                        <th>Payroll</th>
                        <th>Control</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT u.*, 
                            (SELECT login_time FROM attendance WHERE email = u.identifier AND log_date = '$today' LIMIT 1) as attendance_time 
                            FROM users u 
                            WHERE u.role='office' 
                            ORDER BY u.name ASC";

                    $res = $conn->query($sql);
                    while ($row = $res->fetch_assoc()) {
                        $is_present = !empty($row['attendance_time']);
                        
                        // Bank Preview logic
                        $bank_preview = "---";
                        if(!empty($row['bank_name'])) {
                            $bank_preview = "<b>".$row['bank_name']."</b><br><small>".$row['account_number']."</small>";
                        } elseif(!empty($row['upi_id'])) {
                            $bank_preview = "<small>UPI: ".$row['upi_id']."</small>";
                        }
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo $row['name']; ?></strong><br>
                                <span style="font-size:11px; color:#94a3b8;"><?php echo $row['identifier']; ?></span>
                            </td>
                            <td>
                                <?php if ($is_present): ?>
                                    <span class="badge badge-active">ACTIVE</span>
                                <?php else: ?>
                                    <span class="badge badge-absent">ABSENT</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $bank_preview; ?></td>
                            <td>
                                <form method="POST" class="reset-form" style="display:flex; gap:5px;">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <input type="text" name="new_password" placeholder="Pass" style="width:70px; padding:5px; border:1px solid #ddd; border-radius:5px;" required>
                                    <button name="update_pass" class="btn-update">Update</button>
                                </form>
                            </td>
                            <td>
                                <button class="btn-update" style="background:var(--success);" 
                                    onclick="openSalaryModal(
                                        '<?php echo $row['id']; ?>', 
                                        '<?php echo addslashes($row['name']); ?>', 
                                        '<?php echo addslashes($row['bank_name']); ?>', 
                                        '<?php echo $row['account_number']; ?>', 
                                        '<?php echo $row['ifsc_code']; ?>', 
                                        '<?php echo $row['upi_id']; ?>',
                                        '<?php echo addslashes($row['account_holder']); ?>'
                                    )">
                                    <i class="fas fa-wallet"></i> Pay
                                </button>
                            </td>
                            <td>
                                <a href="?delete=<?php echo $row['id']; ?>" class="delete-link" onclick="return confirm('Remove employee?')">
                                    <i class="fas fa-trash-alt" style="color:var(--danger);"></i>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="salaryModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(8, 45, 86, 0.7); z-index:9999; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
        <div style="background:white; padding:35px; border-radius:24px; width:450px; border-top: 8px solid var(--orange);">
            <h2 id="modalStaffName" style="color:var(--navy); margin-bottom:5px;">Process Salary</h2>
            <div id="bankPreview" style="background:#f1f5f9; padding:15px; border-radius:12px; margin-bottom:20px; font-size:13px; color:var(--navy); border:1px solid #e2e8f0;"></div>

            <form method="POST">
                <input type="hidden" name="user_id" id="modalUserId">
                <div style="margin-bottom:15px;">
                    <label style="display:block; font-size:11px; font-weight:800; color:#64748b; margin-bottom:5px; text-transform:uppercase;">For Month</label>
                    <input type="month" name="month_year" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:10px; box-sizing:border-box;" required>
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block; font-size:11px; font-weight:800; color:#64748b; margin-bottom:5px; text-transform:uppercase;">Amount (₹)</label>
                    <input type="number" name="amount" placeholder="0.00" step="0.01" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:10px; box-sizing:border-box;" required>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="button" onclick="closeSalaryModal()" style="flex:1; padding:12px; background:#f1f5f9; border:none; border-radius:10px; cursor:pointer; font-weight:bold;">Cancel</button>
                    <button type="submit" name="process_salary" style="flex:1; padding:12px; background:var(--navy); color:white; border:none; border-radius:10px; cursor:pointer; font-weight:bold;">Confirm Paid</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleBilling() {
            document.getElementById('billingMenu').classList.toggle('show-menu');
            document.getElementById('chevron').classList.toggle('rotate-chevron');
        }

        // Search Filter Logic
        function filterEmployees() {
            let input = document.getElementById('employeeSearch');
            let filter = input.value.toLowerCase();
            let table = document.getElementById('employeeTable');
            let tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let td = tr[i].getElementsByTagName('td')[0];
                if (td) {
                    let textValue = td.textContent || td.innerText;
                    if (textValue.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }

        function openSalaryModal(id, name, bank, acc, ifsc, upi, holder) {
            document.getElementById('salaryModal').style.display = 'flex';
            document.getElementById('modalUserId').value = id;
            document.getElementById('modalStaffName').innerText = "Pay: " + name;
            
            let html = "";
            if(bank && acc) {
                html = `<div style="margin-bottom:8px;"><b>Holder:</b> ${holder || 'N/A'}</div>
                        <div style="margin-bottom:4px;"><i class="fas fa-university"></i> <b>${bank}</b></div>
                        <div style="margin-bottom:4px;"><i class="fas fa-credit-card"></i> ${acc}</div>
                        <div><i class="fas fa-code"></i> IFSC: ${ifsc}</div>`;
            } else if (upi) {
                html = `<div><i class="fas fa-mobile-alt"></i> <b>UPI ID:</b> ${upi}</div>`;
            } else {
                html = `<span style="color:var(--danger); font-weight:bold;">No payment details found!</span>`;
            }
            document.getElementById('bankPreview').innerHTML = html;
        }

        function closeSalaryModal() {
            document.getElementById('salaryModal').style.display = 'none';
        }
    </script>
</body>
</html>