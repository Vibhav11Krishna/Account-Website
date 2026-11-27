<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

// Ensure admin_profiles table exists for editable profile fields (phone, address, designation, bio)
$conn->query("CREATE TABLE IF NOT EXISTS `admin_profiles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_identifier` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) DEFAULT '',
    `address` TEXT DEFAULT NULL,
    `designation` VARCHAR(120) DEFAULT 'Managing Partner & Admin',
    `bio` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_user_identifier` (`user_identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Read logged-in admin details from session and load authoritative name/identifier from `users`
$sessionUser = $_SESSION['user'];
$adminId = isset($sessionUser['id']) ? (int)$sessionUser['id'] : 0;
$adminEmailSession = '';
if (isset($sessionUser['identifier']) && trim($sessionUser['identifier']) !== '') {
    $adminEmailSession = trim($sessionUser['identifier']);
} elseif (isset($sessionUser['email']) && trim($sessionUser['email']) !== '') {
    $adminEmailSession = trim($sessionUser['email']);
}

$dbAdmin = [
    'id' => $adminId,
    'name' => $sessionUser['name'] ?? 'Admin Executive',
    'identifier' => $adminEmailSession,
    'password' => '',
    'role' => 'admin'
];

if ($adminId > 0) {
    $stmt = $conn->prepare("SELECT `id`, `name`, `identifier`, `password`, `role` FROM `users` WHERE `id` = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $dbAdmin = $row;
        }
        $stmt->close();
    }
} elseif ($adminEmailSession !== '') {
    $stmt = $conn->prepare("SELECT `id`, `name`, `identifier`, `password`, `role` FROM `users` WHERE `identifier` = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $adminEmailSession);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $dbAdmin = $row;
        }
        $stmt->close();
    }
}

$adminName = $dbAdmin['name'] ?? 'Admin Executive';
$adminEmail = $dbAdmin['identifier'] ?? $adminEmailSession;
$profileKey = $adminEmail !== '' ? $adminEmail : ('admin_' . ($dbAdmin['id'] ?? $adminId));

$profileAlert = '';
$profileAlertType = 'success';
$passwordAlert = '';
$passwordAlertType = 'success';

// Handle Profile Details Update (Phone, Address, Designation, Bio)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_profile') {
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $designation = trim($_POST['designation'] ?? 'Managing Partner & Admin');
    $bio = trim($_POST['bio'] ?? '');

    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{6,25}$/', $phone)) {
        $profileAlert = 'Please enter a valid contact phone number.';
        $profileAlertType = 'error';
    } else {
        $stmt = $conn->prepare("INSERT INTO `admin_profiles` (`user_identifier`, `phone`, `address`, `designation`, `bio`)
                                VALUES (?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE
                                    `phone` = VALUES(`phone`),
                                    `address` = VALUES(`address`),
                                    `designation` = VALUES(`designation`),
                                    `bio` = VALUES(`bio`)");
        if ($stmt) {
            $stmt->bind_param("sssss", $profileKey, $phone, $address, $designation, $bio);
            if ($stmt->execute()) {
                $profileAlert = 'Profile details (phone, address, and about info) saved successfully.';
                $profileAlertType = 'success';
            } else {
                $profileAlert = 'Could not save profile details: ' . htmlspecialchars($stmt->error);
                $profileAlertType = 'error';
            }
            $stmt->close();
        }
    }
}

// Handle Dashboard Password Reset (stores plain password directly in `users.password`)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $storedPassword = $dbAdmin['password'] ?? '';

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $passwordAlert = 'All password fields are required.';
        $passwordAlertType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $passwordAlert = 'New password and confirm password do not match.';
        $passwordAlertType = 'error';
    } elseif (strlen($newPassword) < 6) {
        $passwordAlert = 'New password must be at least 6 characters long.';
        $passwordAlertType = 'error';
    } else {
        $isCurrentValid = false;
        if ($storedPassword !== '') {
            $info = password_get_info($storedPassword);
            if (!empty($info['algo'])) {
                $isCurrentValid = password_verify($currentPassword, $storedPassword);
            } else {
                $isCurrentValid = hash_equals($storedPassword, $currentPassword)
                    || hash_equals($storedPassword, md5($currentPassword))
                    || hash_equals($storedPassword, sha1($currentPassword));
            }
        }

        if (!$isCurrentValid) {
            $passwordAlert = 'Your current dashboard password is incorrect.';
            $passwordAlertType = 'error';
        } else {
            if (!empty($dbAdmin['id'])) {
                $targetId = (int)$dbAdmin['id'];
                $upd = $conn->prepare("UPDATE `users` SET `password` = ?, `reset_requested` = 0 WHERE `id` = ? LIMIT 1");
                $upd->bind_param("si", $newPassword, $targetId);
            } else {
                $upd = $conn->prepare("UPDATE `users` SET `password` = ?, `reset_requested` = 0 WHERE `identifier` = ? LIMIT 1");
                $upd->bind_param("ss", $newPassword, $adminEmail);
            }

            if ($upd && $upd->execute()) {
                $dbAdmin['password'] = $newPassword;
                $passwordAlert = 'Dashboard password updated successfully.';
                $passwordAlertType = 'success';
            } else {
                $passwordAlert = 'Failed to update password in the database.';
                $passwordAlertType = 'error';
            }
            if ($upd) {
                $upd->close();
            }
        }
    }
}

// Fetch saved profile details (phone, address, designation, bio)
$profileData = [
    'phone' => '',
    'address' => '',
    'designation' => 'Managing Partner & Admin',
    'bio' => ''
];

$pStmt = $conn->prepare("SELECT `phone`, `address`, `designation`, `bio` FROM `admin_profiles` WHERE `user_identifier` = ? LIMIT 1");
if ($pStmt) {
    $pStmt->bind_param("s", $profileKey);
    $pStmt->execute();
    $pRes = $pStmt->get_result();
    if ($pRow = $pRes->fetch_assoc()) {
        $profileData = array_merge($profileData, $pRow);
    }
    $pStmt->close();
}

$initials = strtoupper(substr(trim($adminName), 0, 1) ?: 'A');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile | KKA Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --navy-dark: #051b34;
            --navy: #0b3c74;
            --navy-light: #155297;
            --orange: #ff8c00;
            --orange-glow: rgba(255, 140, 0, 0.15);
            --sidebar-bg: #062241;
            --sidebar-hover: rgba(255, 255, 255, 0.06);
            --bg: #f3f6fc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --danger: #ef4444;
            --danger-bg: #fef2f2;
            --success: #10b981;
            --success-bg: #f0fdf4;
            --border: #e2e8f0;
            --shadow-sm: 0 2px 8px rgba(11, 60, 116, 0.04);
            --shadow-md: 0 10px 25px -5px rgba(11, 60, 116, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
        }

        /* ================= SIDEBAR ================= */
        .sidebar {
            width: 280px;
            background: var(--sidebar-bg);
            color: white;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            border-right: 3px solid var(--orange);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--orange) var(--sidebar-bg);
            z-index: 100;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 8px 20px 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 20px;
        }

        .brand-logo {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--orange), #d97706);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(255, 140, 0, 0.3);
        }

        .brand-header h2 {
            font-size: 15px;
            color: #ffffff;
            margin: 0;
            font-weight: 700;
            line-height: 1.3;
        }

        .sidebar-profile {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .profile-avatar-wrapper {
            position: relative;
            flex-shrink: 0;
        }

        .sidebar-avatar {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--navy-light), var(--navy));
            border: 2px solid var(--orange);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            font-weight: 700;
        }

        .online-status {
            width: 10px;
            height: 10px;
            background: var(--success);
            border: 2px solid var(--sidebar-bg);
            border-radius: 50%;
            position: absolute;
            bottom: -2px;
            right: -2px;
        }

        .profile-info strong {
            display: block;
            font-size: 13px;
            color: #ffffff;
            font-weight: 600;
        }

        .profile-info span {
            font-size: 11px;
            color: var(--orange);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-nav-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.4);
            margin: 10px 0 8px 10px;
            font-weight: 700;
        }

        .sidebar a {
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            margin-bottom: 4px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: var(--sidebar-hover);
            color: white;
            border-left: 3px solid var(--orange);
            padding-left: 17px;
        }

        .sidebar a.active {
            background: rgba(255, 140, 0, 0.12);
            color: #ffffff;
            font-weight: 600;
        }

        .dropdown-content {
            display: none;
            background: rgba(0, 0, 0, 0.15);
            margin: 2px 0 6px 10px;
            border-radius: 10px;
            padding: 4px 0;
            border-left: 2px solid rgba(255, 140, 0, 0.3);
        }

        .dropdown-content a {
            padding: 9px 14px;
            font-size: 12.5px;
            margin-bottom: 0;
            border-radius: 0;
        }

        .dropdown-content a:hover,
        .dropdown-content a.active {
            border-left: none;
            background: rgba(255, 255, 255, 0.05);
            color: var(--orange);
            padding-left: 14px;
        }

        .show-menu {
            display: block !important;
        }

        .rotate-chevron {
            transform: rotate(180deg);
        }

        /* ================= MAIN WORKSPACE ================= */
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
            background: rgba(255, 255, 255, 0.88);
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
            color: var(--text-main);
            font-weight: 800;
        }

        .welcome-text {
            font-size: 12.5px;
            color: var(--text-muted);
            margin-top: 1px;
            font-weight: 500;
        }

        .header-right-group {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
        }

        .header-clock {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            padding: 7px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .clock-icon i {
            color: var(--orange);
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
            color: var(--text-main);
            font-weight: 700;
        }

        .header-user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            padding: 5px 6px 5px 12px;
            border-radius: 30px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .header-user-name {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--navy);
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
            transition: all 0.2s;
        }

        .header-logout-icon:hover {
            background: var(--danger);
            color: white;
            transform: scale(1.05);
        }

        .content-body {
            padding: 32px 40px;
            flex: 1;
            width: 100%;
            max-width: 1240px;
        }

        /* ================= SMALL PROFILE CARD (ABOUT HIM) ================= */
        .mini-profile-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid var(--border);
            border-left: 4px solid var(--orange);
            border-radius: 20px;
            padding: 20px 24px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
        }

        .mini-profile-left {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .mini-avatar {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--navy), var(--navy-light));
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 800;
            flex-shrink: 0;
            box-shadow: 0 8px 18px rgba(11, 60, 116, 0.18);
        }

        .mini-profile-title {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: var(--navy);
        }

        .mini-profile-role {
            font-size: 12px;
            font-weight: 700;
            color: var(--orange);
            margin-top: 2px;
        }

        .mini-profile-bio {
            font-size: 13px;
            color: var(--text-muted);
            margin: 6px 0 0 0;
            max-width: 620px;
            line-height: 1.45;
        }

        .mini-profile-meta {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 12.5px;
            color: var(--text-main);
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 12px 16px;
            min-width: 250px;
        }

        .mini-profile-meta div {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mini-profile-meta i {
            color: var(--navy);
            width: 14px;
            text-align: center;
        }

        /* ================= FORMS GRID ================= */
        .profile-grid {
            display: grid;
            grid-template-columns: 1.25fr 1fr;
            gap: 24px;
            align-items: start;
        }

        .panel-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px 26px;
            box-shadow: var(--shadow-sm);
        }

        .panel-card h3 {
            margin: 0 0 6px 0;
            font-size: 16px;
            font-weight: 800;
            color: var(--navy);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .panel-subtitle {
            margin: 0 0 20px 0;
            font-size: 12.5px;
            color: var(--text-muted);
        }

        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .password-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-control {
            width: 100%;
            padding: 11px 14px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            background: #f8fafc;
            font-family: inherit;
            font-size: 13.5px;
            color: var(--text-main);
            outline: none;
            transition: all 0.2s;
        }

        .password-input-wrap .form-control {
            padding-right: 42px;
        }

        .eye-toggle-btn {
            position: absolute;
            right: 10px;
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px 6px;
            font-size: 14px;
            line-height: 1;
            transition: color 0.2s;
        }

        .eye-toggle-btn:hover {
            color: var(--navy);
        }

        .form-control:focus {
            border-color: var(--navy);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(11, 60, 116, 0.1);
        }

        .form-control[readonly] {
            background: #eef2f6;
            color: #475569;
            cursor: not-allowed;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 78px;
        }

        .btn-primary {
            background: var(--navy);
            color: #ffffff;
            border: none;
            padding: 11px 20px;
            border-radius: 10px;
            font-family: inherit;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background: var(--navy-light);
            transform: translateY(-1px);
        }

        .btn-orange {
            background: var(--orange);
            color: #ffffff;
            border: none;
            padding: 11px 20px;
            border-radius: 10px;
            font-family: inherit;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-orange:hover {
            background: #e67e00;
            transform: translateY(-1px);
        }

        .alert-box {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: var(--success-bg);
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: var(--danger-bg);
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        @media (max-width: 1024px) {
            .admin-top-bar {
                padding: 14px 20px;
            }
            .content-body {
                padding: 24px 20px;
            }
            .profile-grid {
                grid-template-columns: 1fr;
            }
            .mini-profile-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .mini-profile-meta {
                width: 100%;
            }
        }

        @media (max-width: 640px) {
            .form-row-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="brand-header">
            <div class="brand-logo"><i class="fas fa-shield-alt"></i></div>
            <h2>Karunesh Kumar &amp; Associates</h2>
        </div>

        <div class="sidebar-profile">
            <div class="profile-avatar-wrapper">
                <div class="sidebar-avatar"><?php echo htmlspecialchars($initials); ?></div>
                <div class="online-status"></div>
            </div>
            <div class="profile-info">
                <strong><?php echo htmlspecialchars($adminName); ?></strong>
                <span>System Administrator</span>
            </div>
        </div>

        <div class="sidebar-nav-title">Main Menu</div>
        <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="admin-profile.php" class="active"><i class="fas fa-user-cog"></i> Admin Profile</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('billingMenu', 'billChev')">
                <i class="fas fa-file-invoice-dollar"></i> Billing
                <i class="fas fa-chevron-down" id="billChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
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
                <i class="fas fa-chevron-down" id="vaultChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="vaultMenu">
                <a href="admin-review.php">Quality Control</a>
                <a href="Master-Vault.php">Services</a>
                <a href="client-documents.php">Client Documents</a>
            </div>
        </div>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('reportsMenu', 'repChev')">
                <i class="fas fa-file-contract"></i> Reports
                <i class="fas fa-chevron-down" id="repChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="reportsMenu">
                <a href="dsc-register.php">DSC Register</a>
                <a href="service-report.php">Service Report</a>
                <a href="revenue-analytics.php">Revenue Analytics</a>
                <a href="Client-Revenue.php">Client Revenue</a>
                <a href="attendance.php">Attendance</a>
            </div>
        </div>

        <div class="sidebar-nav-title" style="margin-top:15px;">Management</div>
        <a href="assign-work.php"><i class="fas fa-tasks"></i> Assign Work</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('clientMenu', 'clientChev')">
                <i class="fas fa-users"></i> Manage Clients
                <i class="fas fa-chevron-down" id="clientChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="clientMenu">
                <a href="manage-clients.php">Clients</a>
                <a href="client-groups.php">Client Groups</a>
                <a href="client-services.php">Services</a>
                <a href="client-upload-docs.php">Clients Uploads</a>
            </div>
        </div>

        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
        <a href="../logout.php" style="margin-top:auto; color:#fca5a5;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <!-- Main Workspace -->
    <div class="main">
        <header class="admin-top-bar">
            <div class="brand-section">
                <h1>Admin Profile</h1>
                <div class="welcome-text">Manage your contact details, about card, and dashboard password</div>
            </div>

            <div class="header-right-group">
                <div class="header-clock">
                    <div class="clock-icon"><i class="fas fa-clock"></i></div>
                    <div class="clock-display">
                        <span id="liveDate">Loading Date...</span>
                        <strong id="liveTime">00:00:00</strong>
                    </div>
                </div>

                <div class="header-user-pill">
                    <span class="header-user-name"><?php echo htmlspecialchars($adminName); ?></span>
                    <a href="../logout.php" class="header-logout-icon" title="Logout">
                        <i class="fas fa-power-off"></i>
                    </a>
                </div>
            </div>
        </header>

        <div class="content-body">

            <!-- Small Profile Card About Him -->
            <section class="mini-profile-card" aria-label="About Admin">
                <div class="mini-profile-left">
                    <div class="mini-avatar"><?php echo htmlspecialchars($initials); ?></div>
                    <div>
                        <h2 class="mini-profile-title"><?php echo htmlspecialchars($adminName); ?></h2>
                        <div class="mini-profile-role">
                            <?php echo htmlspecialchars($profileData['designation'] ?: 'Managing Partner & Admin'); ?>
                        </div>
                        <p class="mini-profile-bio">
                            <?php
                            echo htmlspecialchars(
                                $profileData['bio'] !== ''
                                    ? $profileData['bio']
                                    : 'Add a short summary about yourself in the profile form below.'
                            );
                            ?>
                        </p>
                    </div>
                </div>

                <div class="mini-profile-meta">
                    <div>
                        <i class="fas fa-envelope"></i>
                        <span><?php echo htmlspecialchars($adminEmail ?: 'Not set in DB'); ?></span>
                    </div>
                    <div>
                        <i class="fas fa-phone"></i>
                        <span><?php echo htmlspecialchars($profileData['phone'] !== '' ? $profileData['phone'] : 'Phone not added yet'); ?></span>
                    </div>
                    <div>
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($profileData['address'] !== '' ? $profileData['address'] : 'Address not added yet'); ?></span>
                    </div>
                </div>
            </section>

            <!-- Profile Details & Dashboard Password Reset -->
            <div class="profile-grid">

                <!-- Editable Profile Information -->
                <div class="panel-card">
                    <h3><i class="fas fa-id-card"></i> Profile Details</h3>
                    <p class="panel-subtitle">Name and login identifier/email are loaded from <code>users</code>; add or update your phone, address, and about info.</p>

                    <?php if ($profileAlert !== ''): ?>
                        <div class="alert-box <?php echo $profileAlertType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                            <i class="fas <?php echo $profileAlertType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                            <span><?php echo $profileAlert; ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="save_profile">

                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="admin_name">Full Name (From Database)</label>
                                <input type="text" id="admin_name" class="form-control" value="<?php echo htmlspecialchars($adminName); ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label for="admin_email">Email / Identifier (From Database)</label>
                                <input type="text" id="admin_email" class="form-control" value="<?php echo htmlspecialchars($adminEmail); ?>" readonly>
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" id="phone" name="phone" class="form-control" placeholder="+91 98765 43210" value="<?php echo htmlspecialchars($profileData['phone']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="designation">Role / Designation</label>
                                <input type="text" id="designation" name="designation" class="form-control" placeholder="Managing Partner & Admin" value="<?php echo htmlspecialchars($profileData['designation']); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" class="form-control" placeholder="Enter office or residential address..."><?php echo htmlspecialchars($profileData['address']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="bio">About (Shown in Profile Card)</label>
                            <textarea id="bio" name="bio" class="form-control" placeholder="Write a brief note about yourself..."><?php echo htmlspecialchars($profileData['bio']); ?></textarea>
                        </div>

                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Save Profile Details
                        </button>
                    </form>
                </div>

                <!-- Dashboard Password Reset -->
                <div class="panel-card">
                    <h3><i class="fas fa-key"></i> Reset Dashboard Password</h3>
                    <p class="panel-subtitle">Update your password directly in <code>users.password</code> (for example, <code>passo3rd</code>).</p>

                    <?php if ($passwordAlert !== ''): ?>
                        <div class="alert-box <?php echo $passwordAlertType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                            <i class="fas <?php echo $passwordAlertType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                            <span><?php echo $passwordAlert; ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="reset_password">

                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <div class="password-input-wrap">
                                <input type="password" id="current_password" name="current_password" class="form-control" required autocomplete="current-password">
                                <button type="button" class="eye-toggle-btn" onclick="togglePasswordVisibility('current_password', this)" aria-label="Show or hide current password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password (e.g. passo3rd)</label>
                            <div class="password-input-wrap">
                                <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6" autocomplete="new-password">
                                <button type="button" class="eye-toggle-btn" onclick="togglePasswordVisibility('new_password', this)" aria-label="Show or hide new password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <div class="password-input-wrap">
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6" autocomplete="new-password">
                                <button type="button" class="eye-toggle-btn" onclick="togglePasswordVisibility('confirm_password', this)" aria-label="Show or hide confirm password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-orange">
                            <i class="fas fa-sync-alt"></i> Update Password
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(inputId, btnEl) {
            const input = document.getElementById(inputId);
            const icon = btnEl ? btnEl.querySelector('i') : null;
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                if (icon) {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            } else {
                input.type = 'password';
                if (icon) {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        }

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

        function toggleMenu(menuId, chevronId) {
            const menu = document.getElementById(menuId);
            const chevron = document.getElementById(chevronId);

            menu.classList.toggle('show-menu');
            if (chevron) chevron.classList.toggle('rotate-chevron');

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