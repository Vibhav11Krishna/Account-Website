<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'office') {
    header("Location: ../Register.php"); 
    exit();
}
// 1. Get the email from session
$user_email = $_SESSION['user']['identifier'];

// 2. JOIN using u.id and p.user_id instead of email
$sql = "SELECT u.name, p.profile_pic, p.first_name 
        FROM users u 
        LEFT JOIN employee_profiles p ON u.id = p.user_id 
        WHERE u.identifier = '$user_email'";

$profileRes = $conn->query($sql);

if ($profileRes) {
    $user_data = $profileRes->fetch_assoc();
}

// 3. Fallback logic
$display_name = !empty($user_data['name']) ? $user_data['name'] : "Employee";
$first_name = !empty($user_data['first_name']) ? $user_data['first_name'] : $display_name;
$profile_img = !empty($user_data['profile_pic']) ? $user_data['profile_pic'] : 'default-avatar.png';
$email = $_SESSION['user']['identifier'];
$u_id = $_SESSION['user']['id'];
$message = "";
$messageClass = "";

// Logic: Update Bank Details
if (isset($_POST['save_bank'])) {
    $holder = $conn->real_escape_string($_POST['account_holder']);
    $bank = $conn->real_escape_string($_POST['bank_name']);
    $acc = $conn->real_escape_string($_POST['account_number']);
    $ifsc = strtoupper($conn->real_escape_string($_POST['ifsc_code']));
    $upi = $conn->real_escape_string($_POST['upi_id']);

    // IFSC Validation (Standard Indian Format: 4 Alpha, 0, 6 Alphanumeric)
    if (!preg_match("/^[A-Z]{4}0[A-Z0-9]{6}$/", $ifsc)) {
        $message = "Invalid IFSC Code format. Please check and try again.";
        $messageClass = "alert-error";
    } else {
        $sql = "UPDATE users SET 
                account_holder='$holder', bank_name='$bank', 
                account_number='$acc', ifsc_code='$ifsc', upi_id='$upi' 
                WHERE id='$u_id'";
        
        if ($conn->query($sql)) {
            $message = "Bank details updated successfully!";
            $messageClass = "alert-success";
        }
    }
}

// Fetch current details
$user = $conn->query("SELECT * FROM users WHERE id='$u_id'")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>KKA Staff | Payments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --navy: #0b3c74; --orange: #ff8c00; --sidebar: #082d56; --bg: #f8fafc; }
        body { display: flex; margin: 0; background: var(--bg); font-family: 'Inter', sans-serif; color: #334155; }
        
        /* Sidebar matches your dashboard */
        .sidebar { width: 280px; background: var(--sidebar); color: white; height: 100vh; position: fixed; padding: 30px 20px; box-sizing: border-box; display: flex; flex-direction: column; border-right: 4px solid var(--orange); }
        .sidebar h2 { font-size: 22px; color: var(--orange); margin-bottom: 40px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 20px; }
        .sidebar a { color: rgba(255, 255, 255, 0.7); text-decoration: none; display: flex; align-items: center; gap: 12px; padding: 14px; margin-bottom: 8px; border-radius: 12px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255, 255, 255, 0.1); color: white; border-left: 4px solid var(--orange); }
        .logout-link { margin-top: auto; color: #fda4af !important; background: rgba(244, 63, 94, 0.1); }

        .main { margin-left: 280px; padding: 50px; width: calc(100% - 280px); }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        
        .card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.03); border-left: 5px solid var(--navy); }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 12px; font-weight: bold; color: var(--navy); margin-bottom: 5px; }
        input { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; box-sizing: border-box; outline: none; transition: 0.2s; }
        input:focus { border-color: var(--orange); }
        
        .btn-save { background: var(--navy); color: white; border: none; padding: 14px; border-radius: 10px; cursor: pointer; width: 100%; font-weight: bold; font-size: 15px; transition: 0.3s; }
        .btn-save:hover { background: #062a52; transform: translateY(-2px); }

        /* Notification Styling */
        .alert-success { background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 20px; border-left: 5px solid #22c55e; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 20px; border-left: 5px solid #ef4444; }

        .salary-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #f1f5f9; }
        .salary-row:last-child { border-bottom: none; }
    </style>
</head>
<body>

    <div class="sidebar">
    <h2 style="font-size: 20px; color: var(--orange); margin-bottom: 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 15px; text-align: center;">
        Karunesh Kumar & Associates Employee
    </h2>
    
    <div style="text-align: center; padding: 5px 0 20px 0; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px;">
        <div style="width: 90px; height: 90px; margin: 0 auto 12px; border-radius: 50%; overflow: hidden;  background: #eee; display: flex; align-items: center; justify-content: center;">
            <img src="../uploads/profile_pics/<?php echo $profile_img; ?>" 
                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($display_name); ?>&background=0b3c74&color=fff';"
                 style="width: 100%; height: 100%; object-fit: cover; display: block;">
        </div>
        
        <h4 style="margin: 0; color: white; font-size: 18px; font-weight: 600; line-height: 1.2;">
            <?php echo htmlspecialchars($display_name); ?>
        </h4>
        <span style="color: var(--orange); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-top: 5px;">
            <i  style="font-size: 9px;"></i> Office Employee
        </span>
    </div>

    <nav style="display: flex; flex-direction: column; gap: 2px;">
        <a href="employee-dashboard.php"><i class="fas fa-tasks"></i> My Tasks</a>
        <a href="work-basket.php"><i class="fas fa-briefcase"></i> Work Basket</a>
        <a href="employee-payments.php" class="active"><i class="fas fa-wallet"></i> Payments</a>
        <a href="Employee_profiles.php"><i class="fas fa-user-circle"></i> Profile</a>
        <a href="staff-attendance.php"><i class="fas fa-clock"></i> Attendance</a>
    </nav>

    <a href="../logout.php" class="logout-link" style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

    <div class="main">
        <header style="margin-bottom: 30px;">
            <h1 style="margin:0;">Payments & Payouts</h1>
            <p style="color: #64748b;">Update your bank details and view your monthly salary notifications.</p>
        </header>

        <?php if ($message): ?>
            <div class="<?php echo $messageClass; ?>">
                <i class="fas <?php echo ($messageClass == 'alert-success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> 
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="grid">
            <div class="card" style="border-left-color: var(--orange);">
                <h3 style="margin-top:0;"><i class="fas fa-university"></i> Bank Account Information</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Account Holder Name (As per Bank)</label>
                        <input type="text" name="account_holder" value="<?php echo $user['account_holder']; ?>" placeholder="e.g. Pulkit Krishna" required>
                    </div>
                    <div class="form-group">
                        <label>Bank Name</label>
                        <input type="text" name="bank_name" value="<?php echo $user['bank_name']; ?>" placeholder="e.g. HDFC Bank" required>
                    </div>
                    <div style="display:flex; gap:15px;">
                        <div class="form-group" style="flex:2;">
                            <label>Account Number</label>
                            <input type="text" name="account_number" value="<?php echo $user['account_number']; ?>" required>
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>IFSC Code</label>
                            <input type="text" name="ifsc_code" value="<?php echo $user['ifsc_code']; ?>" maxlength="11" placeholder="HDFC0001234" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>UPI ID (For Fast Payouts)</label>
                        <input type="text" name="upi_id" value="<?php echo $user['upi_id']; ?>" placeholder="username@okaxis">
                    </div>
                    <button type="submit" name="save_bank" class="btn-save">
                        Update Payout Profile
                    </button>
                </form>
            </div>

            <div class="card">
                <h3 style="margin-top:0;"><i class="fas fa-receipt"></i> Payout Notifications</h3>
                <div style="margin-top: 10px;">
                    <?php
                    $history = $conn->query("SELECT * FROM salaries WHERE user_id='$u_id' ORDER BY created_at DESC");
                    if ($history->num_rows > 0) {
                        while ($row = $history->fetch_assoc()) {
                    ?>
                        <div class="salary-row">
                            <div>
                                <div style="font-weight:bold; color:var(--navy);"><?php echo $row['month_year']; ?></div>
                                <div style="font-size:11px; color:#94a3b8;">Ref: <?php echo $row['transaction_id']; ?></div>
                            </div>
                            <div style="text-align:right;">
                                <div style="color:#22c55e; font-weight:bold; font-size:16px;">₹<?php echo number_format($row['amount'], 2); ?></div>
                                <div style="font-size:10px; text-transform:uppercase; color:#64748b; font-weight:bold;">
                                    <i class="fas fa-check"></i> <?php echo $row['status']; ?>
                                </div>
                            </div>
                        </div>
                    <?php 
                        }
                    } else {
                        echo "<div style='text-align:center; padding-top:40px; color:#94a3b8;'>
                                <i class='fas fa-info-circle' style='font-size:30px; margin-bottom:10px;'></i><br>
                                No salary history recorded yet.
                              </div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>