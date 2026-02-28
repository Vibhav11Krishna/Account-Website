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
?>
<!DOCTYPE html>
<html>

<head>
    <title>Manage Staff | KKA Admin</title>
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
            --danger: #ef4444;
            --success: #22c55e;
        }

        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Inter', sans-serif;
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
        }

        .sidebar h2 {
            color: var(--orange);
            margin-bottom: 40px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 20px;
            font-size: 22px;
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
        .sidebar a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 4px solid var(--orange);
        }

        .main {
            margin-left: 280px;
            padding: 50px;
            width: calc(100% - 280px);
        }

        .table-card {
            background: white;
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            text-align: left;
            padding: 18px;
            background: #f8fafc;
            color: var(--navy);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        td {
            padding: 18px;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
            font-size: 14px;
        }

        /* Status Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-active {
            background: #dcfce7;
            color: #166534;
        }

        .badge-absent {
            background: #f1f5f9;
            color: #64748b;
        }

        .badge-reset {
            background: #fee2e2;
            color: #991b1b;
        }

        .reset-form {
            display: flex;
            gap: 8px;
        }

        .reset-form input {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            outline: none;
            transition: 0.3s;
            width: 140px;
        }

        .reset-form input:focus {
            border-color: var(--navy);
        }

        .btn-update {
            background: var(--navy);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
        }

        .delete-link {
            color: var(--danger);
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .delete-link:hover {
            opacity: 0.7;
        }
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
        <a href="manage-employees.php"class="active"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
        <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <h1 style="color: var(--navy); margin-bottom: 30px;">Employee Management</h1>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Staff Member</th>
                        <th>Email / ID</th>
                        <th>Duty Status</th>
                        <th>Quick Reset</th>
                        <th>Control</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // SQL JOIN: Select user details and check if they have an attendance record for TODAY
                    $sql = "SELECT u.*, 
                            (SELECT login_time FROM attendance WHERE email = u.identifier AND log_date = '$today' LIMIT 1) as attendance_time 
                            FROM users u 
                            WHERE u.role='office' 
                            ORDER BY u.name ASC";

                    $res = $conn->query($sql);

                    while ($row = $res->fetch_assoc()) {
                        $is_present = !empty($row['attendance_time']);
                        $needs_reset = ($row['reset_requested'] == 1);
                    ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="width:35px; height:35px; background:#e0e7ff; border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--navy);">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <strong><?php echo $row['name']; ?></strong>
                                </div>
                            </td>
                            <td><?php echo $row['identifier']; ?></td>
                            <td>
                                <?php if ($is_present): ?>
                                    <span class="badge badge-active"><i class="fas fa-circle" style="font-size:8px;"></i> ACTIVE</span>
                                    <div style="font-size:11px; color:#94a3b8; margin-top:4px;">In: <?php echo date('h:i A', strtotime($row['attendance_time'])); ?></div>
                                <?php else: ?>
                                    <span class="badge badge-absent"><i class="fas fa-moon"></i> ABSENT</span>
                                <?php endif; ?>

                                <?php if ($needs_reset): ?>
                                    <br><span class="badge badge-reset" style="margin-top:5px;"><i class="fas fa-key"></i> RESET REQ</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="reset-form">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <input type="text" name="new_password" placeholder="New Pass" required>
                                    <button name="update_pass" class="btn-update">Update</button>
                                </form>
                            </td>
                            <td>
                                <a href="?delete=<?php echo $row['id']; ?>"
                                    class="delete-link"
                                    onclick="return confirm('Remove this employee permanently?')">
                                    <i class="fas fa-user-minus"></i>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
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