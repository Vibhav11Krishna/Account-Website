<?php
session_start();
include('../db.php');
date_default_timezone_set('Asia/Kolkata');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'office') {
    header("Location: ../Register.php");
    exit();
}

// 1. Get the email from session
$user_email = trim((string)($_SESSION['user']['identifier'] ?? ($_SESSION['user']['email'] ?? '')));

// 2. JOIN using u.id and p.user_id instead of email
$user_data = [];
if (isset($conn) && $conn instanceof mysqli && $user_email !== '') {
    $stmtProfile = $conn->prepare(
        "SELECT u.name, p.profile_pic, p.first_name
         FROM `users` u
         LEFT JOIN `employee_profiles` p ON u.id = p.user_id
         WHERE LOWER(TRIM(u.identifier)) = LOWER(TRIM(?))
         LIMIT 1"
    );
    if ($stmtProfile) {
        $stmtProfile->bind_param("s", $user_email);
        $stmtProfile->execute();
        $profileRes = $stmtProfile->get_result();
        if ($profileRes) {
            $user_data = $profileRes->fetch_assoc() ?: [];
        }
        $stmtProfile->close();
    }
}

// 3. Fallback logic
$display_name = !empty($user_data['name']) ? $user_data['name'] : "Employee";
$first_name = !empty($user_data['first_name']) ? $user_data['first_name'] : $display_name;
$profile_img = !empty($user_data['profile_pic']) ? $user_data['profile_pic'] : 'default-avatar.png';
$email = $user_email;
$updateSuccess = false;

// Logic to complete a task
if (isset($_POST['complete_task']) && isset($conn) && $conn instanceof mysqli) {
    $tid = (int)($_POST['task_id'] ?? 0);
    if ($tid > 0 && $email !== '') {
        $stmtUpd = $conn->prepare(
            "UPDATE `service_requests`
             SET `status` = 'Completed',
                 `completed_date` = CURDATE()
             WHERE `id` = ? AND LOWER(TRIM(`assigned_to`)) = LOWER(TRIM(?))"
        );
        if ($stmtUpd) {
            $stmtUpd->bind_param("is", $tid, $email);
            if ($stmtUpd->execute() && $stmtUpd->affected_rows > 0) {
                $updateSuccess = true;
            }
            $stmtUpd->close();
        }
    }
}

// Fetch Counts for the Buttons & Incoming Work Notifications
$pendingCount = 0;
$completedCount = 0;
$incomingWorkNotifications = [];

if (isset($conn) && $conn instanceof mysqli && $email !== '') {
    $stmtPend = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM `service_requests`
         WHERE LOWER(TRIM(`assigned_to`)) = LOWER(TRIM(?))
           AND LOWER(TRIM(COALESCE(`status`, ''))) = 'assigned'"
    );
    if ($stmtPend) {
        $stmtPend->bind_param("s", $email);
        $stmtPend->execute();
        $pRes = $stmtPend->get_result();
        if ($pRes && ($pRow = $pRes->fetch_assoc())) {
            $pendingCount = (int)($pRow['total'] ?? 0);
        }
        $stmtPend->close();
    }

    $stmtComp = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM `service_requests`
         WHERE LOWER(TRIM(`assigned_to`)) = LOWER(TRIM(?))
           AND LOWER(TRIM(COALESCE(`status`, ''))) = 'completed'"
    );
    if ($stmtComp) {
        $stmtComp->bind_param("s", $email);
        $stmtComp->execute();
        $cRes = $stmtComp->get_result();
        if ($cRes && ($cRow = $cRes->fetch_assoc())) {
            $completedCount = (int)($cRow['total'] ?? 0);
        }
        $stmtComp->close();
    }

    $incomingSrKeys = [];
    $stmtNotifSr = $conn->prepare(
        "SELECT sr.id, sr.client_id, sr.service_type, sr.assigned_date, sr.created_at, cp.company_name
         FROM `service_requests` sr
         LEFT JOIN `client_profiles` cp ON sr.client_id COLLATE utf8mb4_general_ci = cp.client_id
         WHERE LOWER(TRIM(sr.assigned_to)) = LOWER(TRIM(?))
           AND LOWER(TRIM(COALESCE(sr.status, ''))) = 'assigned'
         ORDER BY sr.id DESC
         LIMIT 10"
    );
    if ($stmtNotifSr) {
        $stmtNotifSr->bind_param("s", $email);
        $stmtNotifSr->execute();
        $nSrRes = $stmtNotifSr->get_result();
        if ($nSrRes) {
            while ($srRow = $nSrRes->fetch_assoc()) {
                $kClient = strtolower(trim((string)($srRow['client_id'] ?? '')));
                $kService = strtolower(trim((string)($srRow['service_type'] ?? '')));
                $incomingSrKeys[$kClient . '|' . $kService] = true;

                $incomingWorkNotifications[] = [
                    'title' => (string)($srRow['service_type'] ?? 'New Assigned Task'),
                    'client' => !empty($srRow['company_name']) ? (string)$srRow['company_name'] : (string)($srRow['client_id'] ?? 'Client'),
                    'time' => (string)($srRow['created_at'] ?: ($srRow['assigned_date'] ?? '')),
                ];
            }
        }
        $stmtNotifSr->close();
    }

    $stmtNotifDoc = $conn->prepare(
        "SELECT cd.id, cd.client_id, cd.file_name, cd.category, cd.created_at, cp.company_name
         FROM `client_documents` cd
         LEFT JOIN `client_profiles` cp ON cd.client_id COLLATE utf8mb4_general_ci = cp.client_id
         WHERE LOWER(TRIM(cd.assigned_to)) = LOWER(TRIM(?))
           AND LOWER(TRIM(COALESCE(cd.status, ''))) = 'assigned'
         ORDER BY cd.id DESC
         LIMIT 10"
    );
    if ($stmtNotifDoc) {
        $stmtNotifDoc->bind_param("s", $email);
        $stmtNotifDoc->execute();
        $nDocRes = $stmtNotifDoc->get_result();
        if ($nDocRes) {
            while ($docRow = $nDocRes->fetch_assoc()) {
                $kClient = strtolower(trim((string)($docRow['client_id'] ?? '')));
                $kCat = strtolower(trim((string)($docRow['category'] ?? ($docRow['file_name'] ?? ''))));
                if (isset($incomingSrKeys[$kClient . '|' . $kCat])) {
                    continue;
                }
                $incomingWorkNotifications[] = [
                    'title' => (string)($docRow['file_name'] ?: ($docRow['category'] ?? 'New Assigned Document')),
                    'client' => !empty($docRow['company_name']) ? (string)$docRow['company_name'] : (string)($docRow['client_id'] ?? 'Client'),
                    'time' => (string)($docRow['created_at'] ?? ''),
                ];
            }
        }
        $stmtNotifDoc->close();
    }
}

$totalTasks = $pendingCount + $completedCount;
$completionPct = $totalTasks > 0 ? round(($completedCount / $totalTasks) * 100) : 0;
$incomingNotifCount = count($incomingWorkNotifications);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KKA Staff | Workspace</title>
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
            --yellow-border: #facc15;
            --bg: #f3f7f4;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #4b5563;
            --danger: #ef4444;
            --danger-bg: #fef2f2;
            --border: #d1fae5;
            --border-neutral: #e2e8f0;
            --shadow-sm: 0 2px 8px rgba(21, 128, 61, 0.05);
            --shadow-md: 0 10px 25px -5px rgba(21, 128, 61, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            display: flex;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
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
            border-bottom: 1px solid rgba(250, 204, 21, 0.25);
            margin-bottom: 18px;
        }

        .brand-logo {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--green-vivid);
            border: 1.5px solid var(--yellow-accent);
            color: #fef08a;
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
            border: 1px solid rgba(250, 204, 21, 0.22);
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
            background: rgba(234, 179, 8, 0.18);
            border: 1px solid rgba(250, 204, 21, 0.45);
            color: #fef08a;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .nav-section-title {
            font-size: 10px;
            font-weight: 700;
            color: #fde047;
            opacity: 0.75;
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
            background: linear-gradient(90deg, rgba(22, 163, 74, 0.34), rgba(234, 179, 8, 0.14));
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
            background: linear-gradient(90deg, #ffffff 0%, var(--green-tint) 100%);
            backdrop-filter: blur(12px);
            padding: 14px 40px;
            border-bottom: 2px solid var(--green-soft);
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
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-section h1 .title-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--yellow-accent);
            box-shadow: 0 0 0 3px var(--yellow-soft);
            display: inline-block;
        }

        .welcome-text {
            font-size: 12.5px;
            color: var(--text-muted);
            margin-top: 2px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .header-right-group {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            position: relative;
        }

        /* ================= GREEN & YELLOW NEW WORK NOTIFICATION ================= */
        .notif-btn {
            position: relative;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            border: 1.5px solid var(--yellow-border);
            background: linear-gradient(135deg, var(--green-tint), var(--yellow-soft));
            color: var(--green-deep);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
        }

        .notif-btn:hover {
            background: var(--green-primary);
            color: #fef08a;
            border-color: var(--green-primary);
            transform: translateY(-1px);
        }

        .notif-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            min-width: 20px;
            height: 20px;
            padding: 0 5px;
            border-radius: 999px;
            background: var(--yellow-accent);
            color: var(--green-deep);
            font-size: 10.5px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--green-primary);
        }

        .notif-dropdown {
            display: none;
            position: absolute;
            top: 52px;
            right: 0;
            width: 350px;
            max-width: 92vw;
            background: #ffffff;
            border: 1.5px solid var(--border);
            border-top: 4px solid var(--yellow-accent);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            padding: 14px;
            z-index: 200;
        }

        .notif-dropdown.open {
            display: block;
        }

        .notif-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 10px;
            margin-bottom: 10px;
            border-bottom: 1px dashed #bbf7d0;
            font-size: 12.5px;
            font-weight: 800;
            color: var(--green-deep);
        }

        .notif-pill-yellow {
            background: var(--yellow-soft);
            color: #854d0e;
            border: 1px solid var(--yellow-border);
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10.5px;
            font-weight: 800;
        }

        .notif-item {
            padding: 10px 12px;
            border-radius: 12px;
            background: var(--green-tint);
            border: 1px solid var(--border);
            border-left: 4px solid var(--green-vivid);
            margin-bottom: 8px;
            font-size: 12px;
        }

        .notif-item:last-child {
            margin-bottom: 0;
        }

        .notif-item-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-top: 4px;
            font-size: 11px;
            color: var(--text-muted);
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

        /* ================= STAT BUTTONS & PROGRESS ================= */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 16px;
        }

        .stat-btn {
            padding: 22px 24px;
            border-radius: 18px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            text-align: left;
            font-family: inherit;
            transition: all 0.25s ease;
            box-shadow: var(--shadow-sm);
        }

        .stat-btn.pending {
            border-left: 5px solid var(--yellow-accent);
        }

        .stat-btn.completed {
            border-left: 5px solid var(--green-vivid);
        }

        .stat-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .stat-info h3 {
            margin: 0;
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.7px;
            font-weight: 700;
        }

        .stat-info h2 {
            margin: 6px 0 0;
            font-size: 28px;
            color: var(--green-deep);
            font-weight: 800;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .stat-btn.pending .stat-icon {
            background: var(--yellow-soft);
            color: var(--yellow-warm);
        }

        .stat-btn.completed .stat-icon {
            background: var(--green-soft);
            color: var(--green-primary);
        }

        .progress-strip {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 12px 18px;
            margin-bottom: 22px;
            box-shadow: var(--shadow-sm);
        }

        .progress-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .progress-track {
            height: 8px;
            background: var(--green-soft);
            border-radius: 999px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--green-primary), var(--green-vivid), var(--yellow-accent));
            border-radius: 999px;
            transition: width 0.3s ease;
        }

        /* ================= TOOLBAR ================= */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .search-box {
            position: relative;
            flex: 1;
            min-width: 240px;
            max-width: 420px;
        }

        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--green-primary);
            font-size: 13px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 14px 10px 38px;
            border: 1.5px solid var(--border-neutral);
            background: var(--card-bg);
            border-radius: 12px;
            font-family: inherit;
            font-size: 13px;
            color: var(--text-main);
            outline: none;
            transition: all 0.2s ease;
        }

        .search-box input:focus {
            border-color: var(--green-primary);
            box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.12);
        }

        .filter-pills {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-pill {
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--card-bg);
            color: var(--text-muted);
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-pill.active,
        .filter-pill:hover {
            background: var(--green-primary);
            color: #ffffff;
            border-color: var(--green-primary);
        }

        /* ================= TASK CARDS ================= */
        .card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 18px;
            border: 1px solid var(--border);
            border-left: 5px solid var(--green-primary);
            border-top: 3px solid var(--yellow-accent);
            transition: box-shadow 0.2s ease;
        }

        .card:hover {
            box-shadow: var(--shadow-md);
        }

        .status-pill {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 700;
            background: var(--yellow-soft);
            color: #854d0e;
            border: 1px solid var(--yellow-border);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .meta-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .meta-chip {
            background: #f8fafc;
            border: 1px solid var(--border-neutral);
            padding: 5px 11px;
            border-radius: 9px;
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .meta-chip strong {
            color: var(--green-deep);
        }

        .desc-box {
            background: var(--green-tint);
            border: 1px solid var(--border);
            border-left: 3px solid var(--green-vivid);
            padding: 13px 16px;
            border-radius: 12px;
            color: var(--text-main);
            margin: 0;
            font-size: 13.5px;
            line-height: 1.55;
        }

        .btn-done {
            background: linear-gradient(135deg, var(--green-primary), var(--green-vivid));
            color: #ffffff;
            border: none;
            padding: 11px 18px;
            border-radius: 10px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-done:hover {
            background: var(--green-deep);
            color: #fef08a;
            transform: translateY(-1px);
        }

        .btn-secondary-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 16px;
            padding: 10px 16px;
            border-radius: 10px;
            background: var(--green-soft);
            color: var(--green-deep);
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            border: 1px solid #86efac;
            transition: all 0.2s ease;
        }

        .btn-secondary-link:hover {
            background: var(--green-primary);
            color: #ffffff;
        }

        .alert-success {
            background: linear-gradient(90deg, var(--green-soft), var(--yellow-soft));
            color: #166534;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 22px;
            border: 1px solid #86efac;
            border-left: 4px solid var(--yellow-accent);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 13.5px;
        }

        @media (max-width: 1024px) {
            .admin-top-bar {
                padding: 14px 20px;
            }
            .content-body {
                padding: 24px 20px;
            }
            .stats-container {
                grid-template-columns: 1fr;
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
            <a href="employee-dashboard.php" class="active"><i class="fas fa-tasks"></i> My Tasks</a>
            <a href="work-basket.php"><i class="fas fa-briefcase"></i> Work Basket</a>
            <a href="employee-payments.php"><i class="fas fa-wallet"></i> Payments</a>
            <a href="Employee_profiles.php"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="staff-attendance.php"><i class="fas fa-clock"></i> Attendance</a>
        </nav>

        <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <div class="main">
        <header class="admin-top-bar">
            <div class="brand-section">
                <h1><span class="title-dot"></span> Employee Workspace</h1>
                <div class="welcome-text">
                    <span>Welcome, <strong><?php echo htmlspecialchars($first_name); ?></strong></span>
                    <span>&bull;</span>
                    <span><i class="fas fa-envelope" style="color:var(--green-primary);"></i> <?php echo htmlspecialchars($email); ?></span>
                </div>
            </div>

            <div class="header-right-group">
                <button type="button" class="notif-btn" id="notifToggleBtn" onclick="toggleNotifDropdown()" title="Incoming Work Notifications">
                    <i class="fas fa-bell"></i>
                    <?php if ($incomingNotifCount > 0): ?>
                        <span class="notif-badge"><?php echo $incomingNotifCount; ?></span>
                    <?php endif; ?>
                </button>

                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-header">
                        <span><i class="fas fa-inbox" style="color:var(--yellow-warm);"></i> Incoming Work</span>
                        <span class="notif-pill-yellow"><?php echo $incomingNotifCount; ?> New</span>
                    </div>
                    <?php if (empty($incomingWorkNotifications)): ?>
                        <div class="notif-item" style="color:var(--text-muted);">
                            No new incoming work assigned right now.
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($incomingWorkNotifications, 0, 6) as $nw): ?>
                            <div class="notif-item">
                                <strong style="color:var(--green-deep);"><?php echo htmlspecialchars($nw['title']); ?></strong>
                                <div class="notif-item-meta">
                                    <span><i class="fas fa-building" style="color:var(--green-primary);"></i> <?php echo htmlspecialchars($nw['client']); ?></span>
                                    <?php if ($nw['time'] !== ''): ?>
                                        <span class="notif-pill-yellow"><?php echo htmlspecialchars($nw['time']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="header-clock">
                    <div class="clock-icon"><i class="fas fa-clock"></i></div>
                    <div class="clock-display">
                        <span id="liveDate">Loading Date...</span>
                        <strong id="liveTime">00:00:00</strong>
                    </div>
                </div>

                <div class="header-user-pill">
                    <span class="header-user-name"><?php echo htmlspecialchars($first_name); ?></span>
                    <a href="../logout.php" class="header-logout-icon" title="Logout">
                        <i class="fas fa-power-off"></i>
                    </a>
                </div>
            </div>
        </header>

        <div class="content-body">
            <div class="stats-container">
                <button type="button" class="stat-btn pending" onclick="location.href='employee-dashboard.php'">
                    <div class="stat-info">
                        <h3>Pending Tasks</h3>
                        <h2><?php echo $pendingCount; ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </button>

                <button type="button" class="stat-btn completed" onclick="location.href='work-basket.php'">
                    <div class="stat-info">
                        <h3>Completed</h3>
                        <h2><?php echo $completedCount; ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </button>
            </div>

            <div class="progress-strip">
                <div class="progress-meta">
                    <span><i class="fas fa-chart-line" style="color:var(--green-primary);"></i> Overall Task Completion</span>
                    <span style="color:var(--green-deep);"><?php echo $completedCount; ?> / <?php echo $totalTasks; ?> Done (<?php echo $completionPct; ?>%)</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" style="width: <?php echo $completionPct; ?>%;"></div>
                </div>
            </div>

            <?php if ($updateSuccess): ?>
                <div class="alert-success">
                    <i class="fas fa-thumbs-up"></i> Task successfully moved to completed!
                </div>
            <?php endif; ?>

            <?php
            $stmtTasks = $conn->prepare(
                "SELECT t.*, cp.company_name
                 FROM `service_requests` t
                 LEFT JOIN `client_profiles` cp ON t.client_id COLLATE utf8mb4_general_ci = cp.client_id
                 WHERE LOWER(TRIM(t.assigned_to)) = LOWER(TRIM(?))
                   AND LOWER(TRIM(COALESCE(t.status, ''))) = 'assigned'
                 ORDER BY t.created_at DESC"
            );
            $tasks = false;
            if ($stmtTasks) {
                $stmtTasks->bind_param("s", $email);
                $stmtTasks->execute();
                $tasks = $stmtTasks->get_result();
            }

            if ($tasks && $tasks->num_rows > 0) {
            ?>
                <div class="toolbar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="taskSearch" placeholder="Search by client, service type, or notes..." oninput="filterTasks()">
                    </div>
                    <div class="filter-pills">
                        <button type="button" class="filter-pill active" data-filter="all" onclick="setFilter('all', this)">All Pending</button>
                        <button type="button" class="filter-pill" data-filter="today" onclick="setFilter('today', this)">Assigned Today</button>
                        <button type="button" class="filter-pill" data-filter="older" onclick="setFilter('older', this)">Earlier</button>
                    </div>
                </div>

                <div id="tasksContainer">
                <?php
                $todayStr = date('Y-m-d');
                while ($t = $tasks->fetch_assoc()) {
                    $assignedYmd = !empty($t['assigned_date']) ? date('Y-m-d', strtotime($t['assigned_date'])) : '';
                    $isToday = ($assignedYmd === $todayStr) ? 'today' : 'older';
                    $daysOpen = !empty($t['assigned_date']) ? max(0, (int)floor((time() - strtotime($assignedYmd)) / 86400)) : 0;
                    $searchBlob = strtolower(($t['service_type'] ?? '') . ' ' . ($t['company_name'] ?? '') . ' ' . ($t['client_id'] ?? '') . ' ' . ($t['description'] ?? ''));
                ?>
                    <div class="card task-card" data-age="<?php echo $isToday; ?>" data-search="<?php echo htmlspecialchars($searchBlob, ENT_QUOTES); ?>">
                        <div style="display:flex; justify-content:space-between; align-items:start; gap:18px; flex-wrap:wrap;">
                            <div style="flex:1; min-width:260px;">
                                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                                    <span class="status-pill"><i class="fas fa-hourglass-half"></i> Work in Progress</span>
                                    <span class="meta-chip" style="margin:0;"><i class="fas fa-calendar-day" style="color:var(--green-primary);"></i> <?php echo $daysOpen === 0 ? 'Assigned Today' : $daysOpen . 'd open'; ?></span>
                                </div>

                                <h3 style="margin:12px 0 10px 0; color:var(--green-deep); font-size:18px; font-weight:800;"><?php echo htmlspecialchars($t['service_type']); ?></h3>

                                <div class="meta-row">
                                    <span class="meta-chip">
                                        <i class="fas fa-building" style="color:var(--green-primary);"></i>
                                        Client: <strong><?php echo !empty($t['company_name']) ? htmlspecialchars($t['company_name']) : 'N/A'; ?></strong>
                                        <span>(#<?php echo htmlspecialchars($t['client_id']); ?>)</span>
                                    </span>
                                    <span class="meta-chip">
                                        <i class="fas fa-clock" style="color:var(--yellow-warm);"></i>
                                        Assigned: <strong><?php echo !empty($t['assigned_date']) ? date('d-m-Y', strtotime($t['assigned_date'])) : '—'; ?></strong>
                                    </span>
                                </div>

                                <p class="desc-box"><?php echo nl2br(htmlspecialchars($t['description'])); ?></p>
                            </div>

                            <form method="POST" onsubmit="return handleCompleteSubmit(this);">
                                <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($t['id'], ENT_QUOTES); ?>">
                                <button type="submit" name="complete_task" class="btn-done">
                                    <i class="fas fa-check"></i> Mark as Completed
                                </button>
                            </form>
                        </div>
                    </div>
                <?php
                }
                ?>
                </div>

                <div id="noFilterMatch" class="card" style="display:none; text-align:center; padding:32px; color:var(--text-muted);">
                    <i class="fas fa-filter" style="font-size:24px; color:var(--green-primary); margin-bottom:8px; display:block;"></i>
                    <strong>No tasks match your current search or filter.</strong>
                </div>
            <?php
            } else {
                echo "
                <div class='card' style='text-align:center; border:1px solid var(--border); border-left:5px solid var(--green-vivid); padding: 50px;'>
                    <i class='fas fa-seedling' style='font-size: 40px; color: var(--green-vivid); opacity: 0.55; margin-bottom: 14px; display:block;'></i>
                    <h3 style='color: var(--green-deep); margin:0 0 6px 0;'>All caught up! No pending tasks.</h3>
                    <p style='color: var(--text-muted); margin:0; font-size:13px;'>Review finished items in your Work Basket or log your shift checkout.</p>
                    <div style='display:flex; justify-content:center; gap:10px; flex-wrap:wrap;'>
                        <a href='work-basket.php' class='btn-secondary-link'><i class='fas fa-briefcase'></i> View Work Basket</a>
                        <a href='staff-attendance.php' class='btn-secondary-link' style='background:var(--yellow-soft); color:#854d0e; border-color:#fde047;'><i class='fas fa-clock'></i> Attendance</a>
                    </div>
                </div>";
            }
            if ($stmtTasks) {
                $stmtTasks->close();
            }
            ?>
        </div>
    </div>

    <script>
        let activeAgeFilter = 'all';

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

        function toggleNotifDropdown() {
            const dd = document.getElementById('notifDropdown');
            if (dd) dd.classList.toggle('open');
        }

        document.addEventListener('click', function (e) {
            const btn = document.getElementById('notifToggleBtn');
            const dd = document.getElementById('notifDropdown');
            if (dd && btn && !dd.contains(e.target) && !btn.contains(e.target)) {
                dd.classList.remove('open');
            }
        });

        function setFilter(filterVal, btnEl) {
            activeAgeFilter = filterVal;
            document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
            if (btnEl) btnEl.classList.add('active');
            filterTasks();
        }

        function filterTasks() {
            const input = document.getElementById('taskSearch');
            const query = input ? input.value.trim().toLowerCase() : '';
            const cards = document.querySelectorAll('.task-card');
            const noMatch = document.getElementById('noFilterMatch');
            let visibleCount = 0;

            cards.forEach(card => {
                const age = card.getAttribute('data-age');
                const haystack = card.getAttribute('data-search') || '';
                const matchesAge = (activeAgeFilter === 'all') || (age === activeAgeFilter);
                const matchesText = !query || haystack.includes(query);

                if (matchesAge && matchesText) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (noMatch) {
                noMatch.style.display = (visibleCount === 0 && cards.length > 0) ? 'block' : 'none';
            }
        }

        function handleCompleteSubmit(formEl) {
            const btn = formEl.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Completing...';
            }
            return true;
        }
    </script>
     <?php
// Trigger once right after login (set $_SESSION['just_logged_in'] = true; in Register.php on successful login)
$show_login_anim = !empty($_SESSION['just_logged_in']);
if ($show_login_anim) {
    unset($_SESSION['just_logged_in']);
}
?>
<?php if ($show_login_anim): ?>
<div id="kkaLoginSplash" class="kka-splash" aria-hidden="true">
    <div class="kka-orb kka-orb-1"></div>
    <div class="kka-orb kka-orb-2"></div>

    <div class="kka-splash-card">
        <div class="kka-splash-ring">
            <div class="kka-splash-emblem">
                <i class="fas fa-leaf"></i>
            </div>
        </div>

        <div class="kka-splash-tag">
            <i class="fas fa-sparkles"></i> Employee Workspace Suite
        </div>

        <h2 class="kka-splash-title">Karunesh Kumar &amp; Associates</h2>
        <p class="kka-splash-sub">Welcome back, <strong><?php echo htmlspecialchars($display_name ?? 'Employee'); ?></strong></p>

        <div class="kka-stage-text" id="kkaStageText">Initializing session &amp; security checks...</div>

        <div class="kka-splash-bar">
            <div class="kka-splash-bar-fill"></div>
        </div>

        <div class="kka-splash-footer">
            <span id="kkaCountdown">Entering workspace in 15s</span>
            <button type="button" id="kkaSkipBtn" class="kka-skip-btn">
                Skip <i class="fas fa-forward"></i>
            </button>
        </div>
    </div>
</div>

<style>
    .kka-splash {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        overflow: hidden;
        background: radial-gradient(circle at top, rgba(6, 78, 59, 0.95), rgba(5, 46, 22, 0.98));
        backdrop-filter: blur(10px);
        opacity: 1;
        transition: opacity 0.6s ease, visibility 0.6s ease;
    }

    .kka-splash.is-hiding {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    .kka-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(70px);
        pointer-events: none;
        opacity: 0.35;
    }

    .kka-orb-1 {
        width: 340px;
        height: 340px;
        background: #16a34a;
        top: -80px;
        left: -60px;
        animation: kkaFloatA 15s ease-in-out infinite;
    }

    .kka-orb-2 {
        width: 300px;
        height: 300px;
        background: #eab308;
        bottom: -90px;
        right: -50px;
        animation: kkaFloatB 15s ease-in-out infinite;
    }

    .kka-splash-card {
        position: relative;
        z-index: 2;
        text-align: center;
        max-width: 490px;
        width: 100%;
        padding: 38px 30px;
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.14);
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.42);
        animation: kkaCardIn 0.8s cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    .kka-splash-ring {
        width: 84px;
        height: 84px;
        margin: 0 auto 18px;
        border-radius: 24px;
        padding: 3px;
        background: linear-gradient(135deg, #16a34a, #eab308);
        animation: kkaPulse 2.5s ease-in-out infinite;
    }

    .kka-splash-emblem {
        width: 100%;
        height: 100%;
        border-radius: 21px;
        background: #064e3b;
        color: #fef08a;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        animation: kkaIconBob 3s ease-in-out infinite;
    }

    .kka-splash-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 999px;
        background: rgba(234, 179, 8, 0.16);
        border: 1px solid rgba(234, 179, 8, 0.35);
        color: #fef08a;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.7px;
        text-transform: uppercase;
        margin-bottom: 14px;
    }

    .kka-splash-title {
        margin: 0;
        font-size: 25px;
        font-weight: 800;
        letter-spacing: -0.3px;
        background: linear-gradient(90deg, #ffffff 0%, #dcfce7 50%, #fef08a 100%);
        background-size: 200% auto;
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        animation: kkaTextSlide 0.9s cubic-bezier(0.22, 1, 0.36, 1) both, kkaShimmer 5s linear infinite;
    }

    .kka-splash-sub {
        margin: 10px 0 14px;
        font-size: 14px;
        color: rgba(255, 255, 255, 0.86);
        font-weight: 500;
    }

    .kka-splash-sub strong {
        color: #ffffff;
        font-weight: 700;
    }

    .kka-stage-text {
        font-size: 12px;
        color: #bbf7d0;
        font-weight: 600;
        margin-bottom: 14px;
        min-height: 18px;
        transition: opacity 0.3s ease;
    }

    .kka-splash-bar {
        width: 100%;
        height: 5px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.14);
        overflow: hidden;
    }

    .kka-splash-bar-fill {
        height: 100%;
        width: 0%;
        border-radius: 999px;
        background: linear-gradient(90deg, #16a34a, #eab308);
        animation: kkaProgress15s 15s linear forwards;
    }

    .kka-splash-footer {
        margin-top: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        font-size: 11.5px;
        color: rgba(255, 255, 255, 0.72);
        font-weight: 600;
    }

    .kka-skip-btn {
        background: rgba(255, 255, 255, 0.1);
        color: #fef08a;
        border: 1px solid rgba(234, 179, 8, 0.35);
        padding: 5px 12px;
        border-radius: 999px;
        font-family: inherit;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.2s ease;
    }

    .kka-skip-btn:hover {
        background: #eab308;
        color: #052e16;
    }

    @keyframes kkaCardIn {
        from { opacity: 0; transform: translateY(20px) scale(0.96); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes kkaTextSlide {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes kkaShimmer {
        0% { background-position: 0% center; }
        100% { background-position: 200% center; }
    }

    @keyframes kkaPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    @keyframes kkaIconBob {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-3px); }
    }

    @keyframes kkaFloatA {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(45px, 35px); }
    }

    @keyframes kkaFloatB {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(-45px, -35px); }
    }

    @keyframes kkaProgress15s {
        from { width: 0%; }
        to { width: 100%; }
    }
</style>

<script>
    (function () {
        const splash = document.getElementById('kkaLoginSplash');
        if (!splash) return;

        const stageEl = document.getElementById('kkaStageText');
        const countEl = document.getElementById('kkaCountdown');
        const skipBtn = document.getElementById('kkaSkipBtn');

        const stages = [
            { at: 0,  text: 'Initializing session & security checks...' },
            { at: 4,  text: 'Loading assigned client tasks & filings...' },
            { at: 9,  text: 'Syncing attendance & workspace metrics...' },
            { at: 13, text: 'Preparing Karunesh Kumar & Associates workspace...' }
        ];

        let remaining = 15;
        let dismissed = false;

        function dismissSplash() {
            if (dismissed) return;
            dismissed = true;
            clearInterval(timer);
            splash.classList.add('is-hiding');
            setTimeout(() => splash.remove(), 650);
        }

        function syncText() {
            const elapsed = 15 - remaining;
            let currentStage = stages[0].text;
            for (const s of stages) {
                if (elapsed >= s.at) currentStage = s.text;
            }
            if (stageEl) stageEl.textContent = currentStage;
            if (countEl) countEl.textContent = 'Entering workspace in ' + Math.max(0, remaining) + 's';
        }

        syncText();

        const timer = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
                if (countEl) countEl.textContent = 'Entering workspace in 0s';
                dismissSplash();
            } else {
                syncText();
            }
        }, 1000);

        if (skipBtn) {
            skipBtn.addEventListener('click', dismissSplash);
        }
    })();
</script>
<?php endif; ?>
</body>

</html>