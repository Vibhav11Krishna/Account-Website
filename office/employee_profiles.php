<?php
session_start();
include('../db.php');

// 1. Security & Session Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'office') {
    header("Location: ../Login.php");
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
$user_email = $_SESSION['user']['identifier'];
$status_msg = "";

// 2. FETCH DATA
$query = "SELECT u.id AS u_id, u.identifier AS email, u.name AS display_name, ep.* FROM users u 
          LEFT JOIN employee_profiles ep ON u.id = ep.user_id 
          WHERE u.identifier = '$user_email'";

$result = $conn->query($query);
$userData = $result->fetch_assoc();
$current_user_id = $userData['u_id'];

// 3. HANDLE UPDATE
if (isset($_POST['update_profile'])) {
    $fname   = $conn->real_escape_string($_POST['first_name']);
    $lname   = $conn->real_escape_string($_POST['last_name']);
    $phone   = $conn->real_escape_string($_POST['phone']);
    $dob     = $conn->real_escape_string($_POST['dob']);
    $aadhaar = $conn->real_escape_string($_POST['aadhaar_no']);
    $pan     = $conn->real_escape_string($_POST['pan_no']);
    $doj     = $conn->real_escape_string($_POST['date_of_joining']);
    $emg     = $conn->real_escape_string($_POST['emergency_contact']);
    $address = $conn->real_escape_string($_POST['address']);

    // Ensure directories exist
    if (!is_dir("../uploads/profile_pics/")) mkdir("../uploads/profile_pics/", 0777, true);
    if (!is_dir("../uploads/documents/")) mkdir("../uploads/documents/", 0777, true);

    // Profile Pic Logic
    $profile_pic = $userData['profile_pic'] ?? 'default-avatar.png';
    if (!empty($_FILES['profile_img']['name'])) {
        $ext = pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION);
        $profile_pic = "avatar_" . $current_user_id . "_" . time() . "." . $ext;
        move_uploaded_file($_FILES['profile_img']['tmp_name'], "../uploads/profile_pics/" . $profile_pic);
    }

    // Aadhaar Photo Logic
    $aadhaar_photo = $userData['aadhaar_photo'] ?? ''; 
    if (!empty($_FILES['aadhaar_img']['name'])) {
        $a_ext = pathinfo($_FILES['aadhaar_img']['name'], PATHINFO_EXTENSION);
        $aadhaar_photo = "aadhaar_" . $current_user_id . "_" . time() . "." . $a_ext;
        move_uploaded_file($_FILES['aadhaar_img']['tmp_name'], "../uploads/documents/" . $aadhaar_photo);
    }

    $check = $conn->query("SELECT id FROM employee_profiles WHERE user_id = '$current_user_id'");

    if ($check->num_rows > 0) {
        $sql = "UPDATE employee_profiles SET 
                first_name='$fname', last_name='$lname', phone='$phone', 
                dob='$dob', aadhaar_no='$aadhaar', pan_no='$pan', 
                date_of_joining='$doj', emergency_contact='$emg', 
                profile_pic='$profile_pic', aadhaar_photo='$aadhaar_photo', address='$address' 
                WHERE user_id='$current_user_id'";
    } else {
        $sql = "INSERT INTO employee_profiles (user_id, first_name, last_name, phone, dob, aadhaar_no, pan_no, date_of_joining, emergency_contact, profile_pic, aadhaar_photo, address) 
                VALUES ('$current_user_id', '$fname', '$lname', '$phone', '$dob', '$aadhaar', '$pan', '$doj', '$emg', '$profile_pic', '$aadhaar_photo', '$address')";
    }

    if ($conn->query($sql)) {
        $status_msg = "success";
        header("Refresh:1");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>KKA Staff | Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --navy: #0b3c74;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
        }

        body { display: flex; margin: 0; background: var(--bg); font-family: 'Inter', sans-serif; color: #334155; }

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
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 20px;
        }
        .sidebar a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            display: flex;
            align-items: center; gap: 12px; padding: 14px; margin-bottom: 8px; border-radius: 12px; transition: 0.3s;
        }

        .sidebar a:hover, .sidebar a.active { background: rgba(255, 255, 255, 0.1); color: white; border-left: 4px solid var(--orange); }

        .main { margin-left: 280px; padding: 40px; width: calc(100% - 280px); }

        .card {
            background: white; padding: 30px; border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03); border-left: 5px solid var(--navy); margin-bottom: 30px;
        }

        /* SUMMARY BOX STYLE */
        .info-display-box {
            background: #f1f5f9; border-radius: 15px; padding: 20px;
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; border: 1px dashed #cbd5e1;
        }
        .info-item { display: flex; flex-direction: column; }
        .info-label { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: bold; }
        .info-value { font-size: 14px; color: var(--navy); font-weight: 600; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 15px; }
        label { font-weight: 600; margin-bottom: 8px; color: #64748b; font-size: 12px; text-transform: uppercase; }
        input, textarea { padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; }
        
        .btn-save { background: var(--navy); color: white; border: none; padding: 15px; border-radius: 12px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 10px; transition: 0.3s; }
        .btn-save:hover { background: var(--orange); }

        .avatar-img { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 3px solid var(--orange); }
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
        <a href="employee-payments.php"><i class="fas fa-wallet"></i> Payments</a>
        <a href="Employee_profiles.php" class="active"><i class="fas fa-user-circle"></i> Profile</a>
        <a href="staff-attendance.php"><i class="fas fa-clock"></i> Attendance</a>
    </nav>

    <a href="../logout.php" class="logout-link" style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <h1>Profile Management</h1>
        <div style="background:#fff7ed; color:var(--orange); padding:10px 20px; border-radius:10px; font-weight:bold;">
           <?php 
    // Get the year from joining date, or current year if not set
    $yearCode = !empty($userData['date_of_joining']) ? date("Y", strtotime($userData['date_of_joining'])) : date("Y"); 
    
    // Display only KKA and the Year
    echo "ID: KKA-" . $yearCode; 
?>
        </div>
    </header>

    <!-- UPDATE FORM CARD -->
    <div class="card">
        <h3 style="margin-top:0; color:var(--navy); border-bottom:1px solid #eee; padding-bottom:10px;">Edit Information</h3>
        <form method="POST" enctype="multipart/form-data">
            <div style="display:flex; align-items:center; gap:20px; margin-bottom:20px;">
                <div style="position:relative;">
                    <img src="../uploads/profile_pics/<?= !empty($userData['profile_pic']) ? $userData['profile_pic'] : 'default-avatar.png' ?>" class="avatar-img" id="output">
                    <label for="profile_img" style="position:absolute; bottom:0; right:0; background:var(--orange); color:white; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; border:2px solid white;"><i class="fas fa-camera" style="font-size:10px;"></i></label>
                    <input type="file" name="profile_img" id="profile_img" accept="image/*" style="display:none;" onchange="document.getElementById('output').src = URL.createObjectURL(event.target.files[0])">
                </div>
                <div>
                    <h4 style="margin:0;"><?= $userData['email'] ?></h4>
                    <p style="margin:0; font-size:12px; color:#64748b;">Update your profile picture and personal details.</p>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group"><label>First Name</label><input type="text" name="first_name" value="<?= $userData['first_name'] ?? '' ?>" required></div>
                <div class="form-group"><label>Last Name</label><input type="text" name="last_name" value="<?= $userData['last_name'] ?? '' ?>" required></div>
                <div class="form-group"><label>Aadhaar No</label><input type="text" name="aadhaar_no" value="<?= $userData['aadhaar_no'] ?? '' ?>"></div>
                <div class="form-group">
    <label>Aadhaar No</label>
    <input type="text" name="aadhaar_no" value="<?= $userData['aadhaar_no'] ?? '' ?>">
</div>

<!-- NEW AADHAAR PHOTO INPUT -->
<div class="form-group">
    <label>Upload Aadhaar (Photo/Scan)</label>
    <div style="display:flex; align-items:center; gap:10px;">
        <input type="file" name="aadhaar_img" accept="image/*,application/pdf" style="flex:1;">
        <?php if(!empty($userData['aadhaar_photo'])): ?>
            <a href="../uploads/documents/<?= $userData['aadhaar_photo'] ?>" target="_blank" style="font-size:12px; color:var(--orange); text-decoration:none;">
                <i class="fas fa-eye"></i> View Current
            </a>
        <?php endif; ?>
    </div>
</div>
                <div class="form-group"><label>PAN No</label><input type="text" name="pan_no" value="<?= $userData['pan_no'] ?? '' ?>"></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= $userData['phone'] ?? '' ?>"></div>
                <div class="form-group"><label>Emergency Contact</label><input type="text" name="emergency_contact" value="<?= $userData['emergency_contact'] ?? '' ?>"></div>
                <div class="form-group"><label>DOB</label><input type="date" name="dob" value="<?= $userData['dob'] ?? '' ?>"></div>
                <div class="form-group"><label>Joining Date</label><input type="date" name="date_of_joining" value="<?= $userData['date_of_joining'] ?? '' ?>"></div>
                <div class="form-group" style="grid-column: span 2;"><label>Address</label><textarea name="address" rows="2"><?= $userData['address'] ?? '' ?></textarea></div>
            </div>
            <button type="submit" name="update_profile" class="btn-save">Update Profile</button>
        </form>
    </div>

    <!-- SAVED INFORMATION BOX (READ ONLY) -->
    <div class="card" style="border-left-color: var(--orange);">
        <h3 style="margin-top:0; color:var(--orange);"><i class="fas fa-database"></i> Currently Saved Data</h3>
        <div class="info-display-box">
            <div class="info-item"><span class="info-label">Full Name</span><span class="info-value"><?= ($userData['first_name'] ?? '-') . ' ' . ($userData['last_name'] ?? '') ?></span></div>
            <div class="info-item"><span class="info-label">Phone</span><span class="info-value"><?= $userData['phone'] ?? 'Not Set' ?></span></div>
            <div class="info-item"><span class="info-label">Joining Date</span><span class="info-value"><?= $userData['date_of_joining'] ?? 'Not Set' ?></span></div>
            <div class="info-item"><span class="info-label">Aadhaar</span><span class="info-value"><?= $userData['aadhaar_no'] ?? 'Not Set' ?></span></div>
            <div class="info-item">
    <span class="info-label">Aadhaar Doc</span>
    <span class="info-value">
        <?= !empty($userData['aadhaar_photo']) 
            ? '<i class="fas fa-check-circle" style="color:green;"></i> Uploaded' 
            : '<i class="fas fa-times-circle" style="color:red;"></i> Not Uploaded' ?>
    </span>
</div>
            <div class="info-item"><span class="info-label">PAN</span><span class="info-value"><?= $userData['pan_no'] ?? 'Not Set' ?></span></div>
            <div class="info-item"><span class="info-label">Emergency</span><span class="info-value"><?= $userData['emergency_contact'] ?? 'Not Set' ?></span></div>
            <div class="info-item" style="grid-column: span 3;"><span class="info-label">Residential Address</span><span class="info-value"><?= $userData['address'] ?? 'No address provided' ?></span></div>
        </div>
    </div>
</div>

</body>
</html>