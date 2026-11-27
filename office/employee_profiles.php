<?php
session_start();
include('../db.php');

// 1. Security & Session Check
// Redirect if not logged in or if the role is not 'office'
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'office') {
    header("Location: ../Register.php");
    exit();
}

$user_email = $_SESSION['user']['identifier'];
$status_msg = "";

// 2. FETCH DATA
// We join 'users' and 'employee_profiles' to get everything (display name, photos, lock status)
$query = "SELECT u.id AS u_id, u.identifier AS email, u.name AS display_name, ep.* FROM users u 
          LEFT JOIN employee_profiles ep ON u.id = ep.user_id 
          WHERE u.identifier = '$user_email'";

$result = $conn->query($query);

// Handle cases where the query fails or the user has no profile record yet
if ($result && $result->num_rows > 0) {
    $userData = $result->fetch_assoc();
} else {
    // Fallback data if profile table doesn't have a row for this user yet
    $userData = [
        'email' => $user_email, 
        'profile_pic' => 'default-avatar.png',
        'is_locked' => 0
    ];
}

$current_user_id = $userData['u_id'] ?? null;
// Evaluate the lock status (0 = Unlocked, 1 = Locked)
$is_locked = (isset($userData['is_locked']) && (int)$userData['is_locked'] === 1);

// 3. Fallback logic for UI display variables
$display_name = !empty($userData['display_name']) ? $userData['display_name'] : "Employee";
$profile_img = !empty($userData['profile_pic']) ? $userData['profile_pic'] : 'default-avatar.png';

// 4. HANDLE FORM SUBMISSION (UPDATE/INSERT)
if (isset($_POST['update_profile'])) {
    
    if ($is_locked) {
        echo "<script>alert('Your profile is locked.'); window.location.href='Employee_profiles.php';</script>";
        exit();
    }

    // Sanitize basic inputs
    $fname     = $conn->real_escape_string($_POST['first_name']);
    $lname     = $conn->real_escape_string($_POST['last_name']);
    $phone     = $conn->real_escape_string($_POST['phone']);
    $dob       = $conn->real_escape_string($_POST['dob']);
    $aadhaar   = $conn->real_escape_string($_POST['aadhaar_no']);
    $pan       = $conn->real_escape_string($_POST['pan_no']);
    $doj       = $conn->real_escape_string($_POST['date_of_joining']);
    $emg       = $conn->real_escape_string($_POST['emergency_contact']);
    $address   = $conn->real_escape_string($_POST['address']);
    $firm_reg  = $conn->real_escape_string($_POST['firm_reg_no'] ?? '');
    $prof_qual = $conn->real_escape_string($_POST['prof_qualification'] ?? '');

    $doc_path = "../uploads/documents/";
    if (!is_dir($doc_path)) mkdir($doc_path, 0777, true);

    // Document Logic
    $aadhaar_photo = $userData['aadhaar_photo'] ?? '';
    $pan_photo     = $userData['pan_photo'] ?? '';
    $m10_photo     = $userData['marksheet_10'] ?? '';
    $m12_photo     = $userData['marksheet_12'] ?? '';
    $prof_doc      = $userData['prof_doc'] ?? '';

    $file_map = [
        'aadhaar_img' => ['prefix' => 'aadhaar_', 'target' => &$aadhaar_photo],
        'pan_img'     => ['prefix' => 'pan_doc_', 'target' => &$pan_photo],
        'm10_img'     => ['prefix' => 'm10_',      'target' => &$m10_photo],
        'm12_img'     => ['prefix' => 'm12_',      'target' => &$m12_photo],
        'prof_doc'    => ['prefix' => 'prof_deg_', 'target' => &$prof_doc]
    ];

    foreach ($file_map as $input => &$info) {
        if (!empty($_FILES[$input]['name'])) {
            if (!empty($info['target'])) {
                $old_file = $doc_path . $info['target'];
                if (file_exists($old_file)) unlink($old_file);
            }

            $ext = pathinfo($_FILES[$input]['name'], PATHINFO_EXTENSION);
            $filename = $info['prefix'] . $current_user_id . "_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES[$input]['tmp_name'], $doc_path . $filename)) {
                $info['target'] = $filename;
            }
        }
    }

    // Profile Pic logic
    $profile_pic = !empty($userData['profile_pic']) ? $userData['profile_pic'] : 'default-avatar.png';
    if (!empty($_FILES['profile_img']['name'])) {       
        $ext = pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION);
        $profile_pic = "avatar_" . $current_user_id . "_" . time() . "." . $ext;
        move_uploaded_file($_FILES['profile_img']['tmp_name'], "../uploads/profile_pics/" . $profile_pic);
    }

    // --- UPSERT LOGIC ---
    $check_exists = $conn->query("SELECT user_id FROM employee_profiles WHERE user_id = '$current_user_id'");

    if ($check_exists->num_rows > 0) {
        // UPDATE existing record
        $sql = "UPDATE employee_profiles SET 
        first_name='$fname', last_name='$lname', phone='$phone', 
        dob='$dob', aadhaar_no='$aadhaar', pan_no='$pan', 
        pan_photo='$pan_photo', marksheet_10='$m10_photo', marksheet_12='$m12_photo',
        date_of_joining='$doj', emergency_contact='$emg', 
        profile_pic='$profile_pic', aadhaar_photo='$aadhaar_photo', address='$address',
        firm_reg_no='$firm_reg', prof_qualification='$prof_qual', prof_doc_path='$prof_doc' 
        WHERE user_id='$current_user_id'";
    } else {
        // INSERT new record
        $sql = "INSERT INTO employee_profiles (user_id, first_name, last_name, phone, dob, aadhaar_no, pan_no, pan_photo, marksheet_10, marksheet_12, date_of_joining, emergency_contact, profile_pic, aadhaar_photo, address) 
                VALUES ('$current_user_id', '$fname', '$lname', '$phone', '$dob', '$aadhaar', '$pan', '$pan_photo', '$m10_photo', '$m12_photo', '$doj', '$emg', '$profile_pic', '$aadhaar_photo', '$address')";
    }

    if ($conn->query($sql)) {
        header("Location: Employee_profiles.php?status=success");
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KKA Staff | Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --green-deep: #052e16;
            --green-forest: #064e3b;
            --green-primary: #15803d;
            --green-vivid: #16a34a;
            --green-soft: #dcfce7;
            --green-tint: #f0fdf4;
            --yellow-accent: #eab308;
            --yellow-warm: #ca8a04;
            --yellow-soft: #fef9c3;
            --bg: #f3f7f4;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #4b5563;
            --danger: #ef4444;
            --danger-bg: #fef2f2;
            --border: #d1fae5;
            --border-neutral: #e2e8f0;
            --shadow-sm: 0 2px 8px rgba(21, 128, 61, 0.05);
            --shadow-md: 0 10px 25px -5px rgba(21, 128, 61, 0.1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            display: flex;
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
        }

        /* ================= GREEN SIDEBAR ================= */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--green-forest) 0%, var(--green-deep) 100%);
            color: #ffffff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            border-right: 3px solid var(--yellow-accent);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--yellow-accent) var(--green-deep);
            z-index: 100;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: var(--yellow-accent);
            border-radius: 10px;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 6px 18px 6px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            margin-bottom: 18px;
        }

        .brand-logo {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--green-vivid);
            border: 1.5px solid var(--yellow-accent);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .brand-header h2 {
            margin: 0;
            font-size: 14.5px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.3;
        }

        .profile-box {
            text-align: center;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 16px 12px;
            margin-bottom: 20px;
        }

        .avatar-ring {
            width: 82px;
            height: 82px;
            margin: 0 auto 10px;
            border-radius: 50%;
            padding: 3px;
            background: linear-gradient(135deg, var(--green-vivid), var(--yellow-accent));
        }

        .avatar-ring img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            background: #ffffff;
            display: block;
        }

        .profile-box h4 {
            margin: 0;
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
        }

        .profile-role {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 10px;
            border-radius: 999px;
            background: rgba(234, 179, 8, 0.16);
            color: #fef08a;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .nav-section-title {
            font-size: 10px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.45);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 8px 10px;
        }

        .sidebar nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .sidebar nav a {
            color: rgba(255, 255, 255, 0.78);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .sidebar nav a:hover,
        .sidebar nav a.active {
            background: rgba(22, 163, 74, 0.24);
            color: #ffffff;
            border-left: 3px solid var(--yellow-accent);
            padding-left: 17px;
        }

        .logout-link {
            margin-top: auto;
            color: #fca5a5 !important;
            background: rgba(239, 68, 68, 0.14);
            border: 1px solid rgba(239, 68, 68, 0.25);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 11px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .logout-link:hover {
            background: var(--danger);
            color: #ffffff !important;
        }

        /* ================= MAIN & ADMIN-STYLE HEADER ================= */
        .main {
            margin-left: 280px;
            width: calc(100% - 280px);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .admin-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            padding: 14px 40px;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 90;
            box-shadow: var(--shadow-sm);
            gap: 16px;
        }

        .brand-section h1 {
            margin: 0;
            font-size: 20px;
            color: var(--green-deep);
            font-weight: 800;
        }

        .welcome-text {
            font-size: 12.5px;
            color: var(--text-muted);
            margin-top: 2px;
            font-weight: 500;
        }

        .header-right-group {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            flex-wrap: wrap;
        }

        .staff-id-badge {
            background: var(--yellow-soft);
            color: #854d0e;
            border: 1px solid #fde047;
            padding: 8px 14px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 12px;
            letter-spacing: 0.4px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .header-clock {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--green-tint);
            padding: 7px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .clock-icon i {
            color: var(--yellow-warm);
            font-size: 15px;
        }

        .clock-display {
            display: flex;
            flex-direction: column;
        }

        .clock-display span {
            font-size: 9px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
        }

        .clock-display strong {
            font-size: 12px;
            color: var(--green-deep);
            font-weight: 700;
        }

        .header-user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            padding: 5px 6px 5px 14px;
            border-radius: 30px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .header-user-name {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--green-primary);
        }

        .header-logout-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--danger-bg);
            color: var(--danger);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 12.5px;
            transition: all 0.2s ease;
        }

        .header-logout-icon:hover {
            background: var(--danger);
            color: #ffffff;
            transform: scale(1.05);
        }

        .content-body {
            padding: 32px 40px 48px;
            flex: 1;
            width: 100%;
        }

        /* ================= CARDS & FORM LAYOUT ================= */
        .profile-surface {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-top: 4px solid var(--green-primary);
            border-radius: 22px;
            padding: 28px 32px;
            box-shadow: var(--shadow-sm);
            max-width: 1020px;
            margin: 0 auto 28px;
            transition: box-shadow 0.2s ease;
        }

        .profile-surface:hover {
            box-shadow: var(--shadow-md);
        }

        .profile-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 18px 22px;
            background: var(--green-tint);
            border: 1px solid var(--border);
            border-radius: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .profile-hero-left {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .avatar-img {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--yellow-accent);
            box-shadow: var(--shadow-sm);
        }

        .group-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--green-forest);
            margin: 24px 0 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
        }

        .group-bar i {
            color: var(--green-primary);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px 22px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group label {
            font-size: 11px;
            font-weight: 700;
            color: var(--green-deep);
            text-transform: uppercase;
            letter-spacing: 0.45px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .icon-badge {
            width: 22px;
            height: 22px;
            border-radius: 6px;
            background: var(--green-soft);
            color: var(--green-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            flex-shrink: 0;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group textarea {
            padding: 11px 14px;
            border: 1.5px solid var(--border-neutral);
            background: #f8fafc;
            border-radius: 10px;
            font-family: inherit;
            font-size: 13.5px;
            color: var(--text-main);
            outline: none;
            transition: all 0.2s ease;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="date"]:focus,
        .form-group textarea:focus {
            border-color: var(--green-primary);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.12);
        }

        .form-group input[type="file"] {
            padding: 8px 10px;
            border: 1.5px dashed #86efac;
            background: var(--green-tint);
            border-radius: 10px;
            font-family: inherit;
            font-size: 12.5px;
            color: var(--text-main);
        }

        .doc-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        .doc-view-btn {
            background: var(--green-soft);
            padding: 8px 12px;
            border-radius: 8px;
            color: var(--green-deep);
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid #86efac;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .doc-view-btn:hover {
            background: var(--green-primary);
            color: #ffffff;
        }

        .doc-del-btn {
            background: var(--danger-bg);
            padding: 8px 11px;
            border-radius: 8px;
            color: var(--danger);
            border: 1px solid #fecaca;
            text-decoration: none;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .doc-del-btn:hover {
            background: var(--danger);
            color: #ffffff;
        }

        .btn-save {
            margin-top: 24px;
            background: var(--green-primary);
            color: #ffffff;
            padding: 13px 24px;
            border: none;
            border-radius: 12px;
            font-family: inherit;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-save:hover {
            background: var(--green-deep);
            transform: translateY(-1px);
        }

        /* ================= SUMMARY SECTION ================= */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            background: var(--green-tint);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 18px;
        }

        .summary-tile {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .summary-label {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.45px;
            color: var(--text-muted);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .summary-label i {
            color: var(--green-primary);
        }

        .summary-val {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--green-deep);
        }

        .docs-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-top: 14px;
        }

        @media (max-width: 1024px) {
            .admin-top-bar {
                padding: 14px 20px;
            }
            .content-body {
                padding: 24px 20px;
            }
            .form-grid,
            .summary-grid,
            .docs-grid {
                grid-template-columns: 1fr;
            }
            .summary-tile[style*="span 3"] {
                grid-column: span 1 !important;
            }
            .form-group[style*="span 2"] {
                grid-column: span 1 !important;
            }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="brand-header">
        <div class="brand-logo"><i class="fas fa-leaf"></i></div>
        <h2>Karunesh Kumar &amp; Associates</h2>
    </div>

    <div class="profile-box">
        <div class="avatar-ring">
            <img src="../uploads/profile_pics/<?php echo htmlspecialchars($profile_img); ?>" 
                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($display_name); ?>&background=15803d&color=fff';"
                 alt="<?php echo htmlspecialchars($display_name); ?>">
        </div>
        <h4><?php echo htmlspecialchars($display_name); ?></h4>
        <span class="profile-role">Office Employee</span>
    </div>

    <div class="nav-section-title">Workspace</div>
    <nav>
        <a href="employee-dashboard.php"><i class="fas fa-tasks"></i> My Tasks</a>
        <a href="work-basket.php"><i class="fas fa-briefcase"></i> Work Basket</a>
        <a href="employee-payments.php"><i class="fas fa-wallet"></i> Payments</a>
        <a href="Employee_profiles.php" class="active"><i class="fas fa-user-circle"></i> Profile</a>
        <a href="staff-attendance.php"><i class="fas fa-clock"></i> Attendance</a>
    </nav>

    <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>

<div class="main">
    <header class="admin-top-bar">
        <div class="brand-section">
            <h1>Profile Management</h1>
            <div class="welcome-text">Personal records, government identity proofs, and academic credentials</div>
        </div>

        <div class="header-right-group">
            <div class="staff-id-badge">
                <i class="fas fa-id-card"></i>
                <?php 
                $yearCode = !empty($userData['date_of_joining']) ? date("Y", strtotime($userData['date_of_joining'])) : date("Y"); 
                echo "ID: KKA-" . htmlspecialchars($yearCode); 
                ?>
            </div>

            <div class="header-clock">
                <div class="clock-icon"><i class="fas fa-clock"></i></div>
                <div class="clock-display">
                    <span id="liveDate">Loading Date...</span>
                    <strong id="liveTime">00:00:00</strong>
                </div>
            </div>

            <div class="header-user-pill">
                <span class="header-user-name"><?php echo htmlspecialchars($display_name); ?></span>
                <a href="../logout.php" class="header-logout-icon" title="Logout">
                    <i class="fas fa-power-off"></i>
                </a>
            </div>
        </div>
    </header>

    <div class="content-body">
        <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
            <div style="max-width:1020px; margin:0 auto 22px; background:var(--green-soft); color:#166534; padding:14px 18px; border-radius:12px; border:1px solid #86efac; border-left:4px solid var(--green-vivid); font-weight:700; font-size:13.5px; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-check-circle"></i> Profile details updated successfully!
            </div>
        <?php endif; ?>

        <div class="profile-surface">
            <form method="POST" enctype="multipart/form-data">
                <div class="profile-hero">
                    <div class="profile-hero-left">
                        <div style="position:relative;">
                            <img src="../uploads/profile_pics/<?php echo htmlspecialchars(!empty($userData['profile_pic']) ? $userData['profile_pic'] : 'default-avatar.png'); ?>" class="avatar-img" id="output" alt="Profile Avatar">
                            
                            <?php if(!$is_locked): ?>
                                <label for="profile_img" title="Update Avatar" style="position:absolute; bottom:3px; right:3px; background:var(--green-primary); color:white; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; border:2px solid var(--yellow-accent); box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
                                    <i class="fas fa-camera" style="font-size:12px;"></i>
                                </label>
                                <input type="file" name="profile_img" id="profile_img" accept="image/*" style="display:none;" onchange="document.getElementById('output').src = URL.createObjectURL(event.target.files[0])">
                            <?php endif; ?>
                        </div>
                        <div>
                            <h3 style="margin:0; color:var(--green-deep); font-size:17px; font-weight:800;"><i class="fas fa-envelope" style="color:var(--green-primary); font-size:14px;"></i> <?php echo htmlspecialchars($userData['email']); ?></h3>
                            <p style="margin:6px 0 0 0; font-size:13px; color:var(--text-muted); font-weight:500;">
                                <?php echo $is_locked ? "<i class='fas fa-shield-check' style='color:var(--green-vivid);'></i> Profile Verified &amp; Locked" : "<i class='fas fa-pen-to-square' style='color:var(--yellow-warm);'></i> Keep your personal information, KYC proofs, and qualifications up to date."; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="group-bar" style="margin-top:0;"><i class="fas fa-user-check"></i> Personal &amp; Contact Details</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label><span class="icon-badge"><i class="fas fa-user"></i></span> First Name</label>
                        <input type="text" name="first_name" placeholder="First name" value="<?php echo htmlspecialchars($userData['first_name'] ?? ''); ?>" <?php echo $is_locked ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : 'required'; ?>>
                    </div>

                    <div class="form-group">
                        <label><span class="icon-badge"><i class="fas fa-user-tag"></i></span> Last Name</label>
                        <input type="text" name="last_name" placeholder="Last name" value="<?php echo htmlspecialchars($userData['last_name'] ?? ''); ?>" <?php echo $is_locked ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : 'required'; ?>>
                    </div>

                    <div class="form-group">
                        <label><span class="icon-badge"><i class="fas fa-phone"></i></span> Phone</label>
                        <input type="text" name="phone" placeholder="Primary phone number" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>" <?php echo $is_locked ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : ''; ?>>
                    </div>

                    <div class="form-group">
                        <label><span class="icon-badge"><i class="fas fa-phone-volume"></i></span> Emergency Contact</label>
                        <input type="text" name="emergency_contact" placeholder="Emergency contact number" value="<?php echo htmlspecialchars($userData['emergency_contact'] ?? ''); ?>" <?php echo $is_locked ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : ''; ?>>
                    </div>

                    <div class="form-group">
                        <label><span class="icon-badge"><i class="fas fa-cake-candles"></i></span> Date of Birth</label>
                        <input type="date" name="dob" value="<?php echo htmlspecialchars($userData['dob'] ?? ''); ?>" <?php echo $is_locked ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : ''; ?>>
                    </div>

                    <div class="form-group">
                        <label><span class="icon-badge"><i class="fas fa-calendar-check"></i></span> Joining Date</label>
                        <input type="date" name="date_of_joining" value="<?php echo htmlspecialchars($userData['date_of_joining'] ?? ''); ?>" <?php echo $is_locked ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : ''; ?>>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label><span class="icon-badge"><i class="fas fa-location-dot"></i></span> Full Residential Address</label>
                        <textarea name="address" rows="3" placeholder="Enter full residential address" <?php echo $is_locked ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : ''; ?>><?php echo htmlspecialchars($userData['address'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="group-bar"><i class="fas fa-id-card-clip"></i> Identity &amp; Academic Documents</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label><span class="icon-badge"><i class="fas fa-address-card"></i></span> Aadhaar No</label>
                        <input type="text" name="aadhaar_no" placeholder="12-digit Aadhaar number" value="<?php echo htmlspecialchars($userData['aadhaar_no'] ?? ''); ?>" <?php echo $is_locked ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : ''; ?>>
                    </div>

                    <div class="form-group">
                        <label><span class="icon-badge"><i class="fas fa-file-shield"></i></span> Aadhaar Card (Photo/PDF)</label>
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <input type="file" name="aadhaar_img" accept="image/*,application/pdf" style="flex:1; min-width:170px;" <?php echo $is_locked ? 'disabled' : ''; ?>>
                            <?php if(!empty($userData['aadhaar_photo'])): ?>
                                <div class="doc-actions">
                                    <a href="../uploads/documents/<?php echo urlencode($userData['aadhaar_photo']); ?>" target="_blank" class="doc-view-btn">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if(!$is_locked): ?>
                                        <a href="?delete_doc=aadhaar_photo" onclick="return confirm('Delete this document?')" class="doc-del-btn" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><span class="icon-badge"><i class="fas fa-credit-card"></i></span> PAN No</label>
                        <input type="text" name="pan_no" placeholder="PAN number" value="<?php echo htmlspecialchars($userData['pan_no'] ?? ''); ?>" <?php echo $is_locked ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : ''; ?>>
                    </div>

                    <div class="form-group">
                        <label><span class="icon-badge"><i class="fas fa-file-invoice"></i></span> PAN Card (Photo/PDF)</label>
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <input type="file" name="pan_img" accept="image/*,application/pdf" style="flex:1; min-width:170px;" <?php echo $is_locked ? 'disabled' : ''; ?>>
                            <?php if(!empty($userData['pan_photo'])): ?>
                                <div class="doc-actions">
                                    <a href="../uploads/documents/<?php echo urlencode($userData['pan_photo']); ?>" target="_blank" class="doc-view-btn">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if(!$is_locked): ?>
                                        <a href="?delete_doc=pan_photo" onclick="return confirm('Delete this document?')" class="doc-del-btn" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><span class="icon-badge"><i class="fas fa-graduation-cap"></i></span> 10th Marksheet (Photo/PDF)</label>
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <input type="file" name="m10_img" accept="image/*,application/pdf" style="flex:1; min-width:170px;" <?php echo $is_locked ? 'disabled' : ''; ?>>
                            <?php if(!empty($userData['marksheet_10'])): ?>
                                <div class="doc-actions">
                                    <a href="../uploads/documents/<?php echo urlencode($userData['marksheet_10']); ?>" target="_blank" class="doc-view-btn">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if(!$is_locked): ?>
                                        <a href="?delete_doc=marksheet_10" onclick="return confirm('Delete this document?')" class="doc-del-btn" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><span class="icon-badge"><i class="fas fa-award"></i></span> 12th Marksheet (Photo/PDF)</label>
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <input type="file" name="m12_img" accept="image/*,application/pdf" style="flex:1; min-width:170px;" <?php echo $is_locked ? 'disabled' : ''; ?>>
                            <?php if(!empty($userData['marksheet_12'])): ?>
                                <div class="doc-actions">
                                    <a href="../uploads/documents/<?php echo urlencode($userData['marksheet_12']); ?>" target="_blank" class="doc-view-btn">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if(!$is_locked): ?>
                                        <a href="?delete_doc=marksheet_12" onclick="return confirm('Delete this document?')" class="doc-del-btn" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="group-bar"><i class="fas fa-briefcase"></i> Professional Credentials</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label><span class="icon-badge"><i class="fas fa-building"></i></span> Firm Registration No</label>
                        <input type="text" name="firm_reg_no" placeholder="Firm registration number" value="<?php echo htmlspecialchars($userData['firm_reg_no'] ?? ''); ?>" <?php echo $is_locked ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : ''; ?>>
                    </div>

                    <div class="form-group">
                        <label><span class="icon-badge"><i class="fas fa-certificate"></i></span> Professional Degree (PDF, JPG, PNG)</label>
                        <input type="text" name="prof_qualification" placeholder="Degree Name" value="<?php echo htmlspecialchars($userData['prof_qualification'] ?? ''); ?>" <?php echo $is_locked ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : ''; ?> style="margin-bottom:6px;">
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <input type="file" name="prof_doc" accept="image/*,application/pdf" style="flex:1; min-width:170px;" <?php echo $is_locked ? 'disabled' : ''; ?>>
                            <?php if(!empty($userData['prof_doc_path'])): ?>
                                <div class="doc-actions">
                                    <a href="../uploads/documents/<?php echo urlencode($userData['prof_doc_path']); ?>" target="_blank" class="doc-view-btn">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if(!$is_locked): ?>
                                        <a href="?delete_doc=prof_doc_path" onclick="return confirm('Delete this document?')" class="doc-del-btn" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (!$is_locked): ?>
                    <button type="submit" name="update_profile" class="btn-save">
                        <i class="fas fa-floppy-disk"></i> Save Profile Changes
                    </button>
                <?php else: ?>
                    <div style="margin-top:24px; padding:15px; background:var(--yellow-soft); color:#854d0e; border-radius:12px; border:1px solid #fde047; text-align:center; font-weight:700; font-size:13.5px;">
                        <i class="fas fa-lock"></i> This profile is verified and locked. Contact the Admin panel to enable editing.
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <div class="profile-surface" style="border-top-color: var(--yellow-accent);">
            <h3 style="margin:0 0 18px 0; color:var(--green-deep); font-size:16px; font-weight:800; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-database" style="color:var(--green-primary);"></i> Currently Saved Profile Data
            </h3>

            <div class="summary-grid">
                <div class="summary-tile">
                    <span class="summary-label"><i class="fas fa-user"></i> Full Name</span>
                    <span class="summary-val"><?php echo htmlspecialchars((($userData['first_name'] ?? '-') . ' ' . ($userData['last_name'] ?? ''))); ?></span>
                </div>
                <div class="summary-tile">
                    <span class="summary-label"><i class="fas fa-phone"></i> Phone</span>
                    <span class="summary-val"><?php echo htmlspecialchars($userData['phone'] ?? 'Not Set'); ?></span>
                </div>
                <div class="summary-tile">
                    <span class="summary-label"><i class="fas fa-calendar-check"></i> Joining Date</span>
                    <span class="summary-val"><?php echo htmlspecialchars($userData['date_of_joining'] ?? 'Not Set'); ?></span>
                </div>
                <div class="summary-tile">
                    <span class="summary-label"><i class="fas fa-address-card"></i> Aadhaar No</span>
                    <span class="summary-val"><?php echo htmlspecialchars($userData['aadhaar_no'] ?? 'Not Set'); ?></span>
                </div>
                <div class="summary-tile">
                    <span class="summary-label"><i class="fas fa-credit-card"></i> PAN No</span>
                    <span class="summary-val"><?php echo htmlspecialchars($userData['pan_no'] ?? 'Not Set'); ?></span>
                </div>
                <div class="summary-tile">
                    <span class="summary-label"><i class="fas fa-phone-volume"></i> Emergency Contact</span>
                    <span class="summary-val"><?php echo htmlspecialchars($userData['emergency_contact'] ?? 'Not Set'); ?></span>
                </div>
                <div class="summary-tile">
                    <span class="summary-label"><i class="fas fa-building"></i> Firm Reg No</span>
                    <span class="summary-val"><?php echo htmlspecialchars($userData['firm_reg_no'] ?? 'Not Set'); ?></span>
                </div>
                <div class="summary-tile">
                    <span class="summary-label"><i class="fas fa-graduation-cap"></i> Qualification</span>
                    <span class="summary-val"><?php echo htmlspecialchars($userData['prof_qualification'] ?? 'Not Set'); ?></span>
                </div>
                <div class="summary-tile">
                    <span class="summary-label"><i class="fas fa-certificate"></i> Prof. Degree</span>
                    <span class="summary-val">
                        <?php echo !empty($userData['prof_doc_path']) ? '<span style="color:var(--green-primary);"><i class="fas fa-check-circle"></i> Uploaded</span>' : '<span style="color:var(--danger);"><i class="fas fa-times-circle"></i> Missing</span>'; ?>
                    </span>
                </div>
                <div class="summary-tile" style="grid-column: span 3;">
                    <span class="summary-label"><i class="fas fa-location-dot"></i> Residential Address</span>
                    <span class="summary-val"><?php echo htmlspecialchars($userData['address'] ?? 'No address provided'); ?></span>
                </div>
            </div>

            <div class="group-bar" style="margin-top:22px; margin-bottom:12px;"><i class="fas fa-file-shield"></i> Verification &amp; Documents</div>
            <div class="docs-grid">
                <div class="summary-tile">
                    <span class="summary-label"><i class="fas fa-address-card"></i> Aadhaar Doc</span>
                    <span class="summary-val">
                        <?php echo !empty($userData['aadhaar_photo']) ? '<span style="color:var(--green-primary);"><i class="fas fa-check-circle"></i> Uploaded</span>' : '<span style="color:var(--danger);"><i class="fas fa-times-circle"></i> Missing</span>'; ?>
                    </span>
                </div>
                <div class="summary-tile">
                    <span class="summary-label"><i class="fas fa-file-invoice"></i> PAN Photo</span>
                    <span class="summary-val">
                        <?php echo !empty($userData['pan_photo']) ? '<span style="color:var(--green-primary);"><i class="fas fa-check-circle"></i> Uploaded</span>' : '<span style="color:var(--danger);"><i class="fas fa-times-circle"></i> Missing</span>'; ?>
                    </span>
                </div>
                <div class="summary-tile">
                    <span class="summary-label"><i class="fas fa-graduation-cap"></i> 10th Marksheet</span>
                    <span class="summary-val">
                        <?php echo !empty($userData['marksheet_10']) ? '<span style="color:var(--green-primary);"><i class="fas fa-check-circle"></i> Uploaded</span>' : '<span style="color:var(--danger);"><i class="fas fa-times-circle"></i> Missing</span>'; ?>
                    </span>
                </div>
                <div class="summary-tile">
                    <span class="summary-label"><i class="fas fa-award"></i> 12th Marksheet</span>
                    <span class="summary-val">
                        <?php echo !empty($userData['marksheet_12']) ? '<span style="color:var(--green-primary);"><i class="fas fa-check-circle"></i> Uploaded</span>' : '<span style="color:var(--danger);"><i class="fas fa-times-circle"></i> Missing</span>'; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function updateClock() {
        const now = new Date();
        const dateEl = document.getElementById('liveDate');
        const timeEl = document.getElementById('liveTime');
        if (dateEl) {
            dateEl.textContent = now.toLocaleDateString('en-IN', {
                weekday: 'short',
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        }
        if (timeEl) {
            timeEl.textContent = now.toLocaleTimeString('en-IN', { hour12: true });
        }
    }
    updateClock();
    setInterval(updateClock, 1000);
</script>

</body>
</html>