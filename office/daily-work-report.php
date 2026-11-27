<?php
session_start();
include('../db.php');
date_default_timezone_set('Asia/Kolkata');

// Security Check - Matches your 'admin' role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

$adminName = $_SESSION['user']['name'] ?? 'Admin Executive';
$lookupId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
$lookupIdent = trim((string)($_SESSION['user']['identifier'] ?? ($_SESSION['user']['email'] ?? '')));

$selectedDate = isset($_GET['date']) ? trim((string)$_GET['date']) : '';
$selectedEmployee = isset($_GET['employee']) ? trim((string)$_GET['employee']) : '';
$searchQuery = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

if ($selectedDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = '';
}

$employeeList = [];
$empNameMap = [];
$workRows = [];
$groupedByDate = [];
$employeeWorkModalMap = [];
$dailyAssignedCounts = [];
$dailyCompletedCounts = [];
$statusBreakdown = [
    'Completed' => 0,
    'In Progress' => 0,
    'Pending' => 0
];

if (isset($conn) && $conn instanceof mysqli) {
    if ($lookupId > 0) {
        $stmtAdmin = $conn->prepare("SELECT `name` FROM `users` WHERE `id` = ? LIMIT 1");
        if ($stmtAdmin) {
            $stmtAdmin->bind_param("i", $lookupId);
            $stmtAdmin->execute();
            $resAdmin = $stmtAdmin->get_result();
            if (($rowAdmin = $resAdmin->fetch_assoc()) && !empty($rowAdmin['name'])) {
                $adminName = $rowAdmin['name'];
            }
            $stmtAdmin->close();
        }
    } elseif ($lookupIdent !== '') {
        $stmtAdmin = $conn->prepare("SELECT `name` FROM `users` WHERE `identifier` = ? LIMIT 1");
        if ($stmtAdmin) {
            $stmtAdmin->bind_param("s", $lookupIdent);
            $stmtAdmin->execute();
            $resAdmin = $stmtAdmin->get_result();
            if (($rowAdmin = $resAdmin->fetch_assoc()) && !empty($rowAdmin['name'])) {
                $adminName = $rowAdmin['name'];
            }
            $stmtAdmin->close();
        }
    }

    // Load ALL employees from `users` (`role = 'office'`)
    $uRes = $conn->query(
        "SELECT u.id, u.identifier, u.name, p.first_name
         FROM `users` u
         LEFT JOIN `employee_profiles` p ON u.id = p.user_id
         WHERE LOWER(TRIM(COALESCE(u.role, ''))) = 'office'
           AND u.identifier IS NOT NULL AND TRIM(u.identifier) <> ''
         ORDER BY COALESCE(NULLIF(TRIM(u.name), ''), NULLIF(TRIM(p.first_name), ''), u.identifier) ASC"
    );
    if ($uRes) {
        while ($uRow = $uRes->fetch_assoc()) {
            $ident = trim((string)($uRow['identifier'] ?? ''));
            if ($ident === '') {
                continue;
            }
            $fullName = trim((string)($uRow['name'] ?? ''));
            $firstName = trim((string)($uRow['first_name'] ?? ''));
            $displayLabel = $fullName !== '' ? $fullName : ($firstName !== '' ? $firstName : $ident);

            $employeeList[$ident] = $displayLabel;
            $empNameMap[strtolower($ident)] = $displayLabel;
        }
    }

    // Include any historical assigned_to identifiers not in `users`
    $extraRes = $conn->query(
        "SELECT DISTINCT TRIM(`assigned_to`) AS emp_ident
         FROM `service_requests`
         WHERE `assigned_to` IS NOT NULL AND TRIM(`assigned_to`) <> ''
         ORDER BY emp_ident ASC"
    );
    if ($extraRes) {
        while ($exRow = $extraRes->fetch_assoc()) {
            $exIdent = trim((string)($exRow['emp_ident'] ?? ''));
            if ($exIdent !== '' && !isset($empNameMap[strtolower($exIdent)])) {
                $employeeList[$exIdent] = $exIdent;
                $empNameMap[strtolower($exIdent)] = $exIdent;
            }
        }
    }

    // Initialize modal data buckets for EVERY employee
    foreach ($employeeList as $eIdent => $eLabel) {
        $key = strtolower($eIdent);
        $employeeWorkModalMap[$key] = [
            'employee_name' => $eLabel,
            'employee_ident' => $eIdent,
            'total_assigned' => 0,
            'total_completed' => 0,
            'items' => []
        ];
    }

    // Query assigned & completed service_requests + client_profiles
    $sql = "SELECT
                sr.id AS record_id,
                sr.assigned_to AS employee_ident,
                sr.service_type AS work_title,
                sr.status AS work_status,
                sr.assigned_date,
                sr.completed_date,
                sr.created_at,
                sr.client_id,
                cp.company_name,
                cp.owner_name
            FROM `service_requests` sr
            LEFT JOIN `client_profiles` cp ON sr.client_id COLLATE utf8mb4_general_ci = cp.client_id COLLATE utf8mb4_general_ci
            WHERE sr.assigned_to IS NOT NULL AND TRIM(sr.assigned_to) <> ''
            ORDER BY COALESCE(sr.completed_date, sr.assigned_date, DATE(sr.created_at)) DESC, sr.id DESC";

    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rawStatus = strtolower(trim((string)($row['work_status'] ?? 'pending')));
            $isCompleted = in_array($rawStatus, ['completed', 'released', 'approved'], true);
            $statusLabel = $isCompleted
                ? 'Completed'
                : (in_array($rawStatus, ['in progress', 'working', 'processing'], true) ? 'In Progress' : 'Assigned');

            $assignedRaw = !empty($row['assigned_date']) ? (string)$row['assigned_date'] : (string)($row['created_at'] ?? date('Y-m-d'));
            $completedRaw = !empty($row['completed_date']) ? (string)$row['completed_date'] : '';

            $assignedYmd = date('Y-m-d', strtotime($assignedRaw));
            $completedYmd = ($isCompleted && $completedRaw !== '')
                ? date('Y-m-d', strtotime($completedRaw))
                : ($isCompleted ? $assignedYmd : '');

            $reportDateYmd = $isCompleted && $completedYmd !== '' ? $completedYmd : $assignedYmd;
            $empIdent = trim((string)($row['employee_ident'] ?? ''));
            $empKey = strtolower($empIdent);
            $empDisplay = $empNameMap[$empKey] ?? $empIdent;

            if (!isset($employeeWorkModalMap[$empKey])) {
                $employeeWorkModalMap[$empKey] = [
                    'employee_name' => $empDisplay,
                    'employee_ident' => $empIdent,
                    'total_assigned' => 0,
                    'total_completed' => 0,
                    'items' => []
                ];
            }

            $clientId = trim((string)($row['client_id'] ?? ''));
            $clientCompany = trim((string)($row['company_name'] ?? ''));
            $clientOwner = trim((string)($row['owner_name'] ?? ''));
            $clientName = $clientCompany !== '' ? $clientCompany : ($clientOwner !== '' ? $clientOwner : ($clientId !== '' ? $clientId : 'Unassigned Client'));

            $item = [
                'id' => (int)($row['record_id'] ?? 0),
                'work_date' => $reportDateYmd,
                'display_date' => date('d M Y (D)', strtotime($reportDateYmd)),
                'employee_name' => $empDisplay,
                'employee_ident' => $empIdent,
                'work_title' => (string)($row['work_title'] ?? 'Task'),
                'work_status' => $statusLabel,
                'is_completed' => $isCompleted,
                'assigned_date' => date('d-m-Y', strtotime($assignedYmd)),
                'completed_date' => $isCompleted && $completedYmd !== '' ? date('d-m-Y', strtotime($completedYmd)) : 'Not Completed',
                'client_name' => $clientName,
                'client_id' => $clientId
            ];

            // Store concise work + assigned/completed date + client in employee modal list
            $employeeWorkModalMap[$empKey]['total_assigned']++;
            if ($isCompleted) {
                $employeeWorkModalMap[$empKey]['total_completed']++;
            }
            $employeeWorkModalMap[$empKey]['items'][] = [
                'work_title' => $item['work_title'],
                'assigned_date' => $item['assigned_date'],
                'completed_date' => $item['completed_date'],
                'client_name' => $item['client_name'],
                'client_id' => $item['client_id'],
                'work_status' => $item['work_status'],
                'is_completed' => $item['is_completed']
            ];

            // Apply report filters for main table & charts
            if ($selectedDate !== '' && $reportDateYmd !== $selectedDate) {
                continue;
            }
            if ($selectedEmployee !== '' && strcasecmp($empIdent, $selectedEmployee) !== 0) {
                continue;
            }
            if ($searchQuery !== '') {
                $haystack = strtolower(
                    $reportDateYmd . ' ' . $empDisplay . ' ' . $empIdent . ' ' .
                    $item['work_title'] . ' ' . $clientName . ' ' . $clientId . ' ' . $statusLabel
                );
                if (strpos($haystack, strtolower($searchQuery)) === false) {
                    continue;
                }
            }

            $workRows[] = $item;
            $groupedByDate[$reportDateYmd][] = $item;

            $dailyAssignedCounts[$assignedYmd] = ($dailyAssignedCounts[$assignedYmd] ?? 0) + 1;
            if ($isCompleted && $completedYmd !== '') {
                $dailyCompletedCounts[$completedYmd] = ($dailyCompletedCounts[$completedYmd] ?? 0) + 1;
            }

            if ($isCompleted) {
                $statusBreakdown['Completed']++;
            } elseif ($statusLabel === 'In Progress') {
                $statusBreakdown['In Progress']++;
            } else {
                $statusBreakdown['Pending']++;
            }
        }
    }
}

$chartDatesRaw = array_unique(array_merge(array_keys($dailyAssignedCounts), array_keys($dailyCompletedCounts)));
sort($chartDatesRaw);
$chartDatesRaw = array_slice($chartDatesRaw, -10);

$chartLabels = array_map(fn($d) => date('d M', strtotime($d)), $chartDatesRaw);
$chartAssignedSeries = array_map(fn($d) => $dailyAssignedCounts[$d] ?? 0, $chartDatesRaw);
$chartCompletedSeries = array_map(fn($d) => $dailyCompletedCounts[$d] ?? 0, $chartDatesRaw);

$empChartLabels = [];
$empAssignedSeries = [];
$empCompletedSeries = [];
foreach ($employeeWorkModalMap as $empInfo) {
    if ($selectedEmployee !== '' && strcasecmp($empInfo['employee_ident'], $selectedEmployee) !== 0) {
        continue;
    }
    $empChartLabels[] = $empInfo['employee_name'];
    $empAssignedSeries[] = $empInfo['total_assigned'];
    $empCompletedSeries[] = $empInfo['total_completed'];
}

$initials = strtoupper(substr(trim((string)$adminName), 0, 1) ?: 'A');
$totalFilteredItems = count($workRows);
$totalCompletedCount = $statusBreakdown['Completed'];
$totalAssignedCount = $totalFilteredItems;
$totalEmployeesCount = count($employeeList);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Work Report | KKA Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
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

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: var(--orange);
            border-radius: 10px;
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
            font-size: 18px;
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
            background: rgba(255, 255, 255, 0.85);
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
            gap: 16px;
        }

        .header-clock {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            padding: 8px 16px;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .clock-icon i {
            color: var(--orange);
            font-size: 16px;
        }

        .clock-display {
            display: flex;
            flex-direction: column;
        }

        .clock-display span {
            font-size: 9.5px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
        }

        .clock-display strong {
            font-size: 12.5px;
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
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--danger-bg);
            color: var(--danger);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s;
        }

        .header-logout-icon:hover {
            background: var(--danger);
            color: white;
            transform: scale(1.05);
        }

        /* ================= ALIGNED REPORT BODY ================= */
        .content-body {
            padding: 32px 40px;
            flex: 1;
            width: 100%;
            max-width: 1360px;
            margin: 0 auto;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
            align-items: stretch;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-top: 4px solid var(--navy);
            border-radius: 16px;
            padding: 18px 20px;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .stat-card.orange {
            border-top-color: var(--orange);
        }

        .stat-card small {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-muted);
            font-weight: 700;
        }

        .stat-card strong {
            font-size: 24px;
            font-weight: 800;
            color: var(--navy);
            margin-top: 6px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 24px;
            align-items: stretch;
        }

        .chart-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
        }

        .chart-card h3 {
            margin: 0 0 14px 0;
            font-size: 14.5px;
            color: var(--navy);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }

        .chart-card h3 i {
            color: var(--orange);
        }

        .chart-box {
            position: relative;
            height: 220px;
            width: 100%;
        }

        .filter-card {
            background: var(--card-bg);
            padding: 20px 24px;
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr)) auto;
            gap: 14px;
            align-items: end;
        }

        .field-label {
            display: block;
            font-size: 11.5px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 6px;
        }

        .field-input {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            font-family: inherit;
            font-size: 13px;
            background: #ffffff;
            color: var(--text-main);
        }

        .btn-view {
            padding: 9px 14px;
            background: var(--navy);
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: background 0.2s;
            white-space: nowrap;
        }

        .btn-view:hover {
            background: var(--orange);
        }

        .btn-reset {
            padding: 9px 12px;
            background: #f8fafc;
            color: var(--text-main);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            background: var(--card-bg);
            padding: 24px 28px;
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }

        .card h3 {
            margin: 0 0 18px 0;
            font-size: 16px;
            color: var(--navy);
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border);
        }

        .card h3 i {
            color: var(--orange);
        }

        .emp-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 12px;
        }

        .emp-card {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .emp-card strong {
            display: block;
            font-size: 13px;
            color: var(--navy);
        }

        .emp-card small {
            font-size: 11px;
            color: var(--text-muted);
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 13px 16px;
            background: #f8fafc;
            color: var(--navy);
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            font-weight: 700;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 13.5px;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
        }

        .badge-done {
            background: #dcfce7;
            color: #166534;
        }

        .badge-pending {
            background: var(--orange-glow);
            color: #c2410c;
        }

        /* ================= EMPLOYEE MODAL ================= */
        .modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(5, 27, 52, 0.55);
            z-index: 500;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-backdrop.open {
            display: flex;
        }

        .modal-box {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-top: 4px solid var(--orange);
            border-radius: 18px;
            width: 100%;
            max-width: 680px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: var(--shadow-md);
        }

        .modal-header {
            padding: 18px 22px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: var(--navy);
        }

        .modal-close {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: #f8fafc;
            color: var(--text-main);
            cursor: pointer;
            font-size: 14px;
        }

        .modal-body {
            padding: 20px 22px;
        }

        .modal-work-item {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 12px;
            background: #f8fafc;
        }

        .modal-work-item:last-child {
            margin-bottom: 0;
        }

        .modal-work-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .modal-work-meta {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            font-size: 12.5px;
            color: var(--text-main);
        }

        .modal-work-meta span {
            display: block;
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
        }

        @media (max-width: 1180px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            .filter-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .admin-top-bar {
                padding: 14px 20px;
            }
            .content-body {
                padding: 24px 20px;
            }
        }

        @media (max-width: 640px) {
            .stats-grid,
            .filter-grid,
            .modal-work-meta {
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
        <a href="admin-profile.php"><i class="fas fa-user-cog"></i> Admin Profile</a>

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
            <a href="javascript:void(0)" class="dropdown-btn active" onclick="toggleMenu('reportsMenu', 'repChev')">
                <i class="fas fa-file-contract"></i> Reports
                <i class="fas fa-chevron-down rotate-chevron" id="repChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content show-menu" id="reportsMenu">
                <a href="dsc-register.php">DSC Register</a>
                <a href="daily-work-report.php" class="active">Daily Work Report</a>
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
                <h1>Daily Work Report</h1>
                <div class="welcome-text">Track assigned and completed employee work by date and client</div>
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
            <!-- Summary Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <small>Employees (Users)</small>
                    <strong><?php echo $totalEmployeesCount; ?></strong>
                </div>
                <div class="stat-card orange">
                    <small>Work Items</small>
                    <strong><?php echo $totalAssignedCount; ?></strong>
                </div>
                <div class="stat-card">
                    <small>Completed</small>
                    <strong><?php echo $totalCompletedCount; ?></strong>
                </div>
                <div class="stat-card orange">
                    <small>Pending / Active</small>
                    <strong><?php echo max(0, $totalAssignedCount - $totalCompletedCount); ?></strong>
                </div>
            </div>

            <!-- Charts -->
            <div class="charts-grid">
                <div class="chart-card">
                    <h3><span><i class="fas fa-chart-pie"></i> Status Pie Chart</span></h3>
                    <div class="chart-box">
                        <canvas id="statusPieChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <h3><span><i class="fas fa-chart-bar"></i> Daily Assigned vs Completed</span></h3>
                    <div class="chart-box">
                        <canvas id="dailyWorkChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <h3><span><i class="fas fa-users"></i> Employee Workload</span></h3>
                    <div class="chart-box">
                        <canvas id="employeeWorkChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <form method="GET" class="filter-card">
                <div class="filter-grid">
                    <div>
                        <label class="field-label" for="filterDate">Date</label>
                        <input type="date" id="filterDate" name="date" class="field-input" value="<?php echo htmlspecialchars($selectedDate, ENT_QUOTES); ?>">
                    </div>

                    <div>
                        <label class="field-label" for="filterEmployee">Employee (<?php echo $totalEmployeesCount; ?>)</label>
                        <select id="filterEmployee" name="employee" class="field-input">
                            <option value="">All Employees</option>
                            <?php foreach ($employeeList as $eIdent => $eLabel): ?>
                                <option value="<?php echo htmlspecialchars($eIdent, ENT_QUOTES); ?>" <?php echo strcasecmp($selectedEmployee, $eIdent) === 0 ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($eLabel . ($eLabel !== $eIdent ? " ({$eIdent})" : "")); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="filterSearch">Search Work / Client</label>
                        <input type="text" id="filterSearch" name="q" class="field-input" placeholder="Work or client..." value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES); ?>">
                    </div>

                    <div style="display:flex; gap:8px;">
                        <button type="submit" class="btn-view"><i class="fas fa-filter"></i> Apply</button>
                        <a href="daily-work-report.php" class="btn-reset" title="Reset"><i class="fas fa-rotate-left"></i></a>
                    </div>
                </div>
            </form>

            <!-- All Employees Modal Directory -->
            <div class="card">
                <h3>
                    <span><i class="fas fa-user-check"></i> All Employees Work Modal</span>
                    <span style="font-size:12px; font-weight:600; color:var(--text-muted);"><?php echo $totalEmployeesCount; ?> Employees</span>
                </h3>
                <div class="emp-grid">
                    <?php foreach ($employeeWorkModalMap as $empData):
                        $empJson = htmlspecialchars(json_encode($empData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                    ?>
                        <div class="emp-card">
                            <div>
                                <strong><?php echo htmlspecialchars($empData['employee_name']); ?></strong>
                                <small>Assigned: <?php echo (int)$empData['total_assigned']; ?> · Completed: <?php echo (int)$empData['total_completed']; ?></small>
                            </div>
                            <button type="button" class="btn-view" onclick="openEmployeeModal(this)" data-employee="<?php echo $empJson; ?>">
                                <i class="fas fa-eye"></i> Work
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Date-Grouped Work Report -->
            <?php if (empty($groupedByDate)): ?>
                <div class="card" style="text-align:center; color:var(--text-muted); padding:32px;">
                    No work records found for the selected filter.
                </div>
            <?php else: ?>
                <?php foreach ($groupedByDate as $ymd => $dayItems): ?>
                    <div class="card">
                        <h3>
                            <span><i class="fas fa-calendar-day"></i> <?php echo htmlspecialchars($dayItems[0]['display_date']); ?></span>
                            <span class="badge badge-pending"><?php echo count($dayItems); ?> Item(s)</span>
                        </h3>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Work</th>
                                        <th>Client</th>
                                        <th>Assigned</th>
                                        <th>Completed</th>
                                        <th>Status</th>
                                        <th>Modal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dayItems as $row):
                                        $empKey = strtolower($row['employee_ident']);
                                        $empBundle = $employeeWorkModalMap[$empKey] ?? [
                                            'employee_name' => $row['employee_name'],
                                            'employee_ident' => $row['employee_ident'],
                                            'total_assigned' => 1,
                                            'total_completed' => $row['is_completed'] ? 1 : 0,
                                            'items' => []
                                        ];
                                        $bundleJson = htmlspecialchars(json_encode($empBundle, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                                    ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($row['employee_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['work_title']); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($row['client_name']); ?></strong>
                                                <?php if ($row['client_id'] !== ''): ?>
                                                    <br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($row['client_id']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['assigned_date']); ?></td>
                                            <td><?php echo htmlspecialchars($row['completed_date']); ?></td>
                                            <td>
                                                <?php if ($row['is_completed']): ?>
                                                    <span class="badge badge-done">Completed</span>
                                                <?php else: ?>
                                                    <span class="badge badge-pending"><?php echo htmlspecialchars($row['work_status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn-view" onclick="openEmployeeModal(this)" data-employee="<?php echo $bundleJson; ?>">
                                                    <i class="fas fa-up-right-from-square"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Employee Work Modal (Work, Assigned Date, Completed Date, Client Only) -->
    <div class="modal-backdrop" id="employeeWorkModal" onclick="handleBackdropClick(event)">
        <div class="modal-box" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 id="empModalTitle">Employee Work</h3>
                <button type="button" class="modal-close" onclick="closeEmployeeModal()" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="empModalBody"></div>
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

        function toggleMenu(menuId, chevronId) {
            const menu = document.getElementById(menuId);
            const chevron = document.getElementById(chevronId);

            menu.classList.toggle('show-menu');
            chevron.classList.toggle('rotate-chevron');

            const allMenus = document.querySelectorAll('.dropdown-content');
            const allChevrons = document.querySelectorAll('.fa-chevron-down');

            allMenus.forEach((m) => {
                if (m.id !== menuId) m.classList.remove('show-menu');
            });

            allChevrons.forEach((c) => {
                if (c.id !== chevronId) c.classList.remove('rotate-chevron');
            });
        }

        // Charts
        const pieCtx = document.getElementById('statusPieChart');
        if (pieCtx && window.Chart) {
            new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: ['Completed', 'In Progress', 'Pending'],
                    datasets: [{
                        data: [
                            <?php echo (int)$statusBreakdown['Completed']; ?>,
                            <?php echo (int)$statusBreakdown['In Progress']; ?>,
                            <?php echo (int)$statusBreakdown['Pending']; ?>
                        ],
                        backgroundColor: ['#10b981', '#0b3c74', '#ff8c00'],
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        const dailyCtx = document.getElementById('dailyWorkChart');
        if (dailyCtx && window.Chart) {
            new Chart(dailyCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($chartLabels); ?>,
                    datasets: [
                        {
                            label: 'Assigned',
                            data: <?php echo json_encode($chartAssignedSeries); ?>,
                            backgroundColor: '#0b3c74',
                            borderRadius: 6
                        },
                        {
                            label: 'Completed',
                            data: <?php echo json_encode($chartCompletedSeries); ?>,
                            backgroundColor: '#ff8c00',
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        const empCtx = document.getElementById('employeeWorkChart');
        if (empCtx && window.Chart) {
            new Chart(empCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($empChartLabels); ?>,
                    datasets: [
                        {
                            label: 'Assigned',
                            data: <?php echo json_encode($empAssignedSeries); ?>,
                            backgroundColor: '#155297',
                            borderRadius: 6
                        },
                        {
                            label: 'Completed',
                            data: <?php echo json_encode($empCompletedSeries); ?>,
                            backgroundColor: '#10b981',
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function openEmployeeModal(btn) {
            const raw = btn.getAttribute('data-employee');
            if (!raw) return;
            const emp = JSON.parse(raw);

            document.getElementById('empModalTitle').textContent = emp.employee_name || 'Employee Work';

            const bodyEl = document.getElementById('empModalBody');
            const items = Array.isArray(emp.items) ? emp.items : [];

            if (items.length === 0) {
                bodyEl.innerHTML = '<div style="text-align:center; color:#64748b; padding:18px;">No work records found for this employee.</div>';
            } else {
                bodyEl.innerHTML = items.map((w) => {
                    const badgeClass = w.is_completed ? 'badge badge-done' : 'badge badge-pending';
                    const clientText = w.client_id
                        ? `${escapeHtml(w.client_name)} (${escapeHtml(w.client_id)})`
                        : escapeHtml(w.client_name || '—');
                    return `
                        <div class="modal-work-item">
                            <div class="modal-work-title">
                                <span>${escapeHtml(w.work_title)}</span>
                                <span class="${badgeClass}">${escapeHtml(w.work_status)}</span>
                            </div>
                            <div class="modal-work-meta">
                                <div><span>Assigned</span><strong>${escapeHtml(w.assigned_date)}</strong></div>
                                <div><span>Completed</span><strong>${escapeHtml(w.completed_date)}</strong></div>
                                <div><span>Client</span><strong>${clientText}</strong></div>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            document.getElementById('employeeWorkModal').classList.add('open');
        }

        function closeEmployeeModal() {
            document.getElementById('employeeWorkModal').classList.remove('open');
        }

        function handleBackdropClick(e) {
            if (e.target.id === 'employeeWorkModal') {
                closeEmployeeModal();
            }
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeEmployeeModal();
            }
        });
    </script>

</body>

</html>