<?php
session_start();
include('../db.php');
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'office') {
    header("Location: ../Register.php");
    exit();
}

function ensureWorkBasketSchema(mysqli $conn): void
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS `work_submissions` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `source_table` VARCHAR(32) NOT NULL DEFAULT 'client_documents',
            `source_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `client_id` VARCHAR(100) NOT NULL DEFAULT '',
            `service_or_doc` VARCHAR(255) NOT NULL DEFAULT '',
            `staff_email` VARCHAR(190) NOT NULL DEFAULT '',
            `submission_type` ENUM('document', 'message') NOT NULL DEFAULT 'document',
            `result_file` VARCHAR(255) DEFAULT NULL,
            `staff_message` TEXT DEFAULT NULL,
            `admin_note` TEXT DEFAULT NULL,
            `review_status` ENUM('Pending Review', 'Released', 'Rejected') NOT NULL DEFAULT 'Pending Review',
            `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `reviewed_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_client_status` (`client_id`, `review_status`),
            KEY `idx_staff_status` (`staff_email`, `review_status`),
            KEY `idx_source` (`source_table`, `source_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $colRes = $conn->query("SHOW COLUMNS FROM `client_documents`");
    if ($colRes) {
        $existingCols = [];
        while ($c = $colRes->fetch_assoc()) {
            $existingCols[strtolower((string)$c['Field'])] = true;
        }
        if (!isset($existingCols['result_file'])) {
            $conn->query("ALTER TABLE `client_documents` ADD COLUMN `result_file` VARCHAR(255) DEFAULT NULL");
        }
        if (!isset($existingCols['admin_note'])) {
            $conn->query("ALTER TABLE `client_documents` ADD COLUMN `admin_note` TEXT DEFAULT NULL");
        }
    }
}

$user_email = trim((string)($_SESSION['user']['identifier'] ?? ($_SESSION['user']['email'] ?? '')));
$user_data = [];

if (isset($conn) && $conn instanceof mysqli) {
    ensureWorkBasketSchema($conn);

    if ($user_email !== '') {
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
}

$display_name = !empty($user_data['name']) ? $user_data['name'] : "Employee";
$first_name = !empty($user_data['first_name']) ? $user_data['first_name'] : $display_name;
$profile_img = !empty($user_data['profile_pic']) ? $user_data['profile_pic'] : 'default-avatar.png';
$email = $user_email;
$status_msg = "";
$status_is_error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_work'])) {
    $doc_id = (int)($_POST['doc_id'] ?? 0);
    $sourceTable = ($_POST['source_table'] ?? 'client_documents') === 'service_requests'
        ? 'service_requests'
        : 'client_documents';
    $submissionMode = ($_POST['submission_mode'] ?? 'document') === 'message_only'
        ? 'message_only'
        : 'document';
    $adminMessage = trim((string)($_POST['admin_message'] ?? ''));
    $hasUploadedFile = !empty($_FILES["result_file"]["name"]);

    if ($doc_id <= 0) {
        $status_msg = "Invalid work item selected.";
        $status_is_error = true;
    } elseif ($submissionMode === 'message_only' && $adminMessage === '') {
        $status_msg = "Please type a message for the admin before sending.";
        $status_is_error = true;
    } elseif ($submissionMode === 'document' && !$hasUploadedFile && $adminMessage === '') {
        $status_msg = "Attach a file or enter a message for the admin.";
        $status_is_error = true;
    } else {
        $metaClientId = '';
        $metaTitle = 'Work Item #' . $doc_id;
        $metaInstruction = '';

        if ($sourceTable === 'service_requests') {
            $stmtMeta = $conn->prepare("SELECT `client_id`, `service_type`, `description` FROM `service_requests` WHERE `id` = ? LIMIT 1");
            if ($stmtMeta) {
                $stmtMeta->bind_param("i", $doc_id);
                $stmtMeta->execute();
                $resMeta = $stmtMeta->get_result();
                if ($resMeta && ($m = $resMeta->fetch_assoc())) {
                    $metaClientId = (string)($m['client_id'] ?? '');
                    $metaTitle = (string)($m['service_type'] ?? $metaTitle);
                    $metaInstruction = (string)($m['description'] ?? '');
                }
                $stmtMeta->close();
            }
        } else {
            $stmtMeta = $conn->prepare("SELECT `client_id`, `file_name`, `category`, `instruction` FROM `client_documents` WHERE `id` = ? LIMIT 1");
            if ($stmtMeta) {
                $stmtMeta->bind_param("i", $doc_id);
                $stmtMeta->execute();
                $resMeta = $stmtMeta->get_result();
                if ($resMeta && ($m = $resMeta->fetch_assoc())) {
                    $metaClientId = (string)($m['client_id'] ?? '');
                    $metaTitle = (string)($m['file_name'] ?? ($m['category'] ?? $metaTitle));
                    $metaInstruction = (string)($m['instruction'] ?? '');
                }
                $stmtMeta->close();
            }
        }

        $savedFileName = null;
        $fileUploadOk = true;

        if ($hasUploadedFile) {
            $fileErr = (int)($_FILES["result_file"]["error"] ?? UPLOAD_ERR_NO_FILE);
            $origName = (string)($_FILES["result_file"]["name"] ?? '');
            $tmpName = (string)($_FILES["result_file"]["tmp_name"] ?? '');

            if ($fileErr === UPLOAD_ERR_OK && $origName !== '' && is_uploaded_file($tmpName)) {
                $target_dir = "../uploads/vault/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($origName));
                $savedFileName = time() . "_" . $safeBase;

                if (!move_uploaded_file($tmpName, $target_dir . $savedFileName)) {
                    $status_msg = "Error: Could not write file to ../uploads/vault/.";
                    $status_is_error = true;
                    $fileUploadOk = false;
                }
            } else {
                $status_msg = "Error: Uploaded file was invalid or incomplete.";
                $status_is_error = true;
                $fileUploadOk = false;
            }
        }

        if ($fileUploadOk) {
            $submissionType = ($savedFileName !== null) ? 'document' : 'message';
            $formattedNote = $adminMessage !== '' ? ("[Staff Msg]: " . $adminMessage) : null;
            $msgToStore = $adminMessage !== '' ? $adminMessage : null;

            if ($sourceTable === 'service_requests') {
                $noteSuffix = "";
                if ($savedFileName !== null) {
                    $noteSuffix .= " [Uploaded File: " . $savedFileName . "]";
                }
                if ($adminMessage !== '') {
                    $noteSuffix .= " [Staff Msg]: " . $adminMessage;
                }

                $stmtSrUpd = $conn->prepare(
                    "UPDATE `service_requests`
                     SET `status` = 'Completed',
                         `completed_date` = CURDATE(),
                         `description` = CASE
                             WHEN ? <> '' THEN CONCAT(COALESCE(`description`, ''), '\n', ?)
                             ELSE `description`
                         END
                     WHERE `id` = ? AND LOWER(TRIM(`assigned_to`)) = LOWER(TRIM(?))"
                );
                if ($stmtSrUpd) {
                    $stmtSrUpd->bind_param("ssis", $noteSuffix, $noteSuffix, $doc_id, $email);
                    $stmtSrUpd->execute();
                    $stmtSrUpd->close();
                }

                $docStatus = 'Pending Review';
                $docCat = $submissionType === 'document' ? 'vault' : 'message_task';
                $stmtInsDoc = $conn->prepare(
                    "INSERT INTO `client_documents`
                     (`client_id`, `file_name`, `assigned_to`, `status`, `doc_category`, `category`, `instruction`, `result_file`, `admin_note`, `created_at`)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
                );
                $linkedDocId = 0;
                if ($stmtInsDoc) {
                    $stmtInsDoc->bind_param(
                        "sssssssss",
                        $metaClientId,
                        $metaTitle,
                        $email,
                        $docStatus,
                        $docCat,
                        $metaTitle,
                        $metaInstruction,
                        $savedFileName,
                        $formattedNote
                    );
                    $stmtInsDoc->execute();
                    $linkedDocId = (int)$stmtInsDoc->insert_id;
                    $stmtInsDoc->close();
                }

                $effectiveSourceId = $linkedDocId > 0 ? $linkedDocId : $doc_id;
                $effectiveSourceTable = $linkedDocId > 0 ? 'client_documents' : 'service_requests';

                $stmtLog = $conn->prepare(
                    "INSERT INTO `work_submissions`
                     (`source_table`, `source_id`, `client_id`, `service_or_doc`, `staff_email`, `submission_type`, `result_file`, `staff_message`, `review_status`, `submitted_at`)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending Review', NOW())"
                );
                if ($stmtLog) {
                    $stmtLog->bind_param(
                        "sissssss",
                        $effectiveSourceTable,
                        $effectiveSourceId,
                        $metaClientId,
                        $metaTitle,
                        $email,
                        $submissionType,
                        $savedFileName,
                        $msgToStore
                    );
                    $stmtLog->execute();
                    $stmtLog->close();
                }

                $status_msg = $submissionType === 'document'
                    ? "Document & update sent to Admin Review; task marked Completed."
                    : "Message sent to Admin Review; task marked Completed.";
            } else {
                if ($savedFileName !== null) {
                    $stmtUpdate = $conn->prepare(
                        "UPDATE `client_documents`
                         SET `status` = 'Pending Review',
                             `doc_category` = 'vault',
                             `result_file` = ?,
                             `admin_note` = ?
                         WHERE `id` = ? AND LOWER(TRIM(`assigned_to`)) = LOWER(TRIM(?))"
                    );
                    if ($stmtUpdate) {
                        $stmtUpdate->bind_param("ssis", $savedFileName, $formattedNote, $doc_id, $email);
                        $stmtUpdate->execute();
                        $stmtUpdate->close();
                    }
                } else {
                    $stmtUpdate = $conn->prepare(
                        "UPDATE `client_documents`
                         SET `status` = 'Pending Review',
                             `doc_category` = 'message_task',
                             `result_file` = NULL,
                             `admin_note` = ?
                         WHERE `id` = ? AND LOWER(TRIM(`assigned_to`)) = LOWER(TRIM(?))"
                    );
                    if ($stmtUpdate) {
                        $stmtUpdate->bind_param("sis", $formattedNote, $doc_id, $email);
                        $stmtUpdate->execute();
                        $stmtUpdate->close();
                    }
                }

                $stmtLog = $conn->prepare(
                    "INSERT INTO `work_submissions`
                     (`source_table`, `source_id`, `client_id`, `service_or_doc`, `staff_email`, `submission_type`, `result_file`, `staff_message`, `review_status`, `submitted_at`)
                     VALUES ('client_documents', ?, ?, ?, ?, ?, ?, ?, 'Pending Review', NOW())"
                );
                if ($stmtLog) {
                    $stmtLog->bind_param(
                        "issssss",
                        $doc_id,
                        $metaClientId,
                        $metaTitle,
                        $email,
                        $submissionType,
                        $savedFileName,
                        $msgToStore
                    );
                    $stmtLog->execute();
                    $stmtLog->close();
                }

                $status_msg = $submissionType === 'document'
                    ? "Document sent to Admin Review successfully."
                    : "Message sent to Admin Review successfully.";
            }
        }
    }
}

$activeDocs = [];
$historySubmissions = [];
$notifications = [];

if (isset($conn) && $conn instanceof mysqli && $email !== '') {
    $activeSrKeys = [];
    $stmtSrActive = $conn->prepare(
        "SELECT sr.id, sr.client_id, sr.service_type, sr.description, sr.status, sr.assigned_date, cp.company_name
         FROM `service_requests` sr
         LEFT JOIN `client_profiles` cp ON sr.client_id COLLATE utf8mb4_general_ci = cp.client_id
         WHERE LOWER(TRIM(sr.assigned_to)) = LOWER(TRIM(?))
           AND LOWER(TRIM(COALESCE(sr.status, ''))) = 'assigned'
         ORDER BY sr.id DESC"
    );
    if ($stmtSrActive) {
        $stmtSrActive->bind_param("s", $email);
        $stmtSrActive->execute();
        $resSr = $stmtSrActive->get_result();
        if ($resSr) {
            while ($sr = $resSr->fetch_assoc()) {
                $srClient = strtolower(trim((string)($sr['client_id'] ?? '')));
                $srService = strtolower(trim((string)($sr['service_type'] ?? '')));
                $activeSrKeys[$srClient . '|' . $srService] = true;

                $activeDocs[] = [
                    'id' => (int)$sr['id'],
                    'source_table' => 'service_requests',
                    'file_name' => (string)($sr['service_type'] ?? 'Assigned Task'),
                    'client_id' => (string)($sr['client_id'] ?? 'No ID Assigned'),
                    'company_name' => (string)($sr['company_name'] ?? ''),
                    'instruction' => (string)($sr['description'] ?? ''),
                    'admin_note' => '',
                    'status' => 'Assigned',
                    'has_file' => false,
                    'download_url' => '',
                ];
            }
        }
        $stmtSrActive->close();
    }

    $stmtActive = $conn->prepare(
        "SELECT cd.*, cp.company_name
         FROM `client_documents` cd
         LEFT JOIN `client_profiles` cp ON cd.client_id COLLATE utf8mb4_general_ci = cp.client_id
         WHERE LOWER(TRIM(cd.assigned_to)) = LOWER(TRIM(?))
           AND LOWER(TRIM(COALESCE(cd.status, ''))) = 'assigned'
         ORDER BY cd.id DESC"
    );
    if ($stmtActive) {
        $stmtActive->bind_param("s", $email);
        $stmtActive->execute();
        $resActive = $stmtActive->get_result();
        if ($resActive) {
            while ($row = $resActive->fetch_assoc()) {
                $docClient = strtolower(trim((string)($row['client_id'] ?? '')));
                $docCat = strtolower(trim((string)($row['category'] ?? ($row['file_name'] ?? ''))));
                if (isset($activeSrKeys[$docClient . '|' . $docCat])) {
                    continue;
                }

                $rawFile = (string)($row['file_name'] ?? '');
                $row['source_table'] = 'client_documents';
                $row['has_file'] = ($rawFile !== '' && file_exists("../uploads/center/" . basename($rawFile)));
                $row['download_url'] = $row['has_file'] ? ("../uploads/center/" . rawurlencode(basename($rawFile))) : "";
                $activeDocs[] = $row;
            }
        }
        $stmtActive->close();
    }

    $stmtHist = $conn->prepare(
        "SELECT ws.*, cp.company_name
         FROM `work_submissions` ws
         LEFT JOIN `client_profiles` cp ON ws.client_id COLLATE utf8mb4_general_ci = cp.client_id
         WHERE LOWER(TRIM(ws.staff_email)) = LOWER(TRIM(?))
         ORDER BY ws.id DESC
         LIMIT 50"
    );
    if ($stmtHist) {
        $stmtHist->bind_param("s", $email);
        $stmtHist->execute();
        $resHist = $stmtHist->get_result();
        if ($resHist) {
            while ($hRow = $resHist->fetch_assoc()) {
                $historySubmissions[] = $hRow;
                $revStatus = (string)($hRow['review_status'] ?? 'Pending Review');
                if ($revStatus === 'Rejected' || $revStatus === 'Released' || !empty($hRow['admin_note'])) {
                    $notifications[] = [
                        'title' => (string)($hRow['service_or_doc'] ?? 'Submission'),
                        'status' => $revStatus,
                        'note' => (string)($hRow['admin_note'] ?? ''),
                        'time' => (string)($hRow['reviewed_at'] ?: ($hRow['submitted_at'] ?? '')),
                    ];
                }
            }
        }
        $stmtHist->close();
    }
}

$activeCount = count($activeDocs);
$notifCount = count($notifications) + ($status_msg !== '' ? 1 : 0);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Basket | KKA Employee</title>
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
            --shadow-sm: 0 2px 8px rgba(21, 128, 61, 0.06);
            --shadow-md: 0 12px 26px -6px rgba(21, 128, 61, 0.14);
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

        /* ================= GREEN & YELLOW SIDEBAR ================= */
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
            background: rgba(234, 179, 8, 0.2);
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
            color: rgba(255, 255, 255, 0.82);
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
            background: linear-gradient(90deg, rgba(22, 163, 74, 0.35), rgba(234, 179, 8, 0.14));
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

        /* ================= MAIN & TOP BAR ================= */
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
            margin-top: 3px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .count-chip-yellow {
            background: var(--yellow-soft);
            color: #854d0e;
            border: 1px solid var(--yellow-border);
            padding: 2px 9px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 800;
        }

        .header-right-group {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            position: relative;
        }

        /* ================= GREEN & YELLOW NOTIFICATIONS ================= */
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
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .notif-dropdown {
            display: none;
            position: absolute;
            top: 52px;
            right: 0;
            width: 360px;
            max-width: 92vw;
            background: #ffffff;
            border: 1.5px solid var(--green-soft);
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

        .notif-pill-count {
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

        .notif-item.alert-yellow {
            background: #fffbeb;
            border-color: #fde68a;
            border-left-color: var(--yellow-accent);
        }

        .notif-item:last-child {
            margin-bottom: 0;
        }

        .notif-status-tag {
            display: inline-block;
            background: var(--yellow-soft);
            color: #854d0e;
            border: 1px solid var(--yellow-border);
            padding: 1px 7px;
            border-radius: 999px;
            font-size: 10.5px;
            font-weight: 800;
            margin-left: 4px;
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

        /* ================= WORK BASKET BODY ================= */
        .content-body {
            padding: 32px 40px 48px;
            flex: 1;
            width: 100%;
        }

        .section-heading-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 18px;
        }

        .section-heading-bar h2 {
            margin: 0;
            font-size: 17px;
            font-weight: 800;
            color: var(--green-deep);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-badge-green {
            background: var(--green-soft);
            color: var(--green-primary);
            border: 1px solid #86efac;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 800;
        }

        .alert-banner {
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 22px;
            font-weight: 700;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-banner.ok {
            background: linear-gradient(90deg, var(--green-soft), var(--yellow-soft));
            color: var(--green-deep);
            border: 1px solid #86efac;
            border-left: 5px solid var(--yellow-accent);
        }

        .alert-banner.err {
            background: var(--danger-bg);
            color: #991b1b;
            border: 1px solid #fecaca;
            border-left: 5px solid var(--danger);
        }

        .basket-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .doc-card {
            background: linear-gradient(180deg, #ffffff 0%, var(--green-tint) 100%);
            border: 1px solid var(--border);
            border-left: 5px solid var(--green-vivid);
            border-top: 3px solid var(--yellow-accent);
            border-radius: 18px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .doc-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-left-color: var(--green-deep);
        }

        .doc-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .doc-submit-hint {
            font-size: 11px;
            font-weight: 800;
            color: var(--green-primary);
            background: var(--green-soft);
            padding: 3px 9px;
            border-radius: 999px;
        }

        .doc-card h3 {
            margin: 12px 0 6px 0;
            font-size: 15.5px;
            font-weight: 800;
            color: var(--green-deep);
            word-break: break-word;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 11px;
            border-radius: 999px;
            background: var(--yellow-soft);
            color: #854d0e;
            border: 1px solid var(--yellow-border);
        }

        .pill.doc {
            background: var(--green-soft);
            color: #166534;
            border-color: #86efac;
        }

        .card-panel {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-left: 5px solid var(--green-primary);
            border-top: 3px solid var(--yellow-accent);
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--shadow-sm);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            color: var(--green-deep);
            background: linear-gradient(90deg, var(--green-soft), var(--yellow-soft));
            padding: 12px;
            border-bottom: 2px solid var(--border);
        }

        td {
            padding: 12px;
            font-size: 13px;
            border-bottom: 1px solid #eef2f6;
            vertical-align: top;
        }

        tr:hover td {
            background: var(--green-tint);
        }

        .modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(5, 46, 22, 0.58);
            z-index: 999;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .modal-box {
            background: #ffffff;
            width: 100%;
            max-width: 520px;
            border-radius: 20px;
            padding: 24px;
            border: 1.5px solid var(--border);
            border-top: 5px solid var(--yellow-accent);
            box-shadow: var(--shadow-md);
        }

        .mode-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 14px 0;
        }

        .mode-tab {
            padding: 10px;
            border-radius: 10px;
            border: 1.5px solid var(--border-neutral);
            background: var(--green-tint);
            color: var(--green-deep);
            font-weight: 700;
            font-size: 12.5px;
            cursor: pointer;
            font-family: inherit;
        }

        .mode-tab.active {
            border-color: var(--green-primary);
            background: linear-gradient(90deg, var(--green-soft), var(--yellow-soft));
            color: var(--green-deep);
            box-shadow: 0 0 0 2px rgba(234, 179, 8, 0.25);
        }

        input[type="file"],
        textarea {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1.5px solid var(--border-neutral);
            font-family: inherit;
            font-size: 13px;
            margin-top: 6px;
        }

        input[type="file"]:focus,
        textarea:focus {
            outline: none;
            border-color: var(--green-primary);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--green-primary), var(--green-vivid));
            color: #ffffff;
            border: 1px solid var(--green-deep);
            padding: 11px 18px;
            border-radius: 10px;
            font-weight: 800;
            cursor: pointer;
            font-family: inherit;
        }

        .btn-submit:hover {
            background: var(--green-deep);
            color: #fef08a;
        }

        .btn-cancel {
            background: var(--yellow-soft);
            color: #854d0e;
            border: 1px solid var(--yellow-border);
            padding: 11px 16px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
        }

        @media (max-width: 1024px) {
            .admin-top-bar {
                padding: 14px 20px;
            }
            .content-body {
                padding: 24px 20px;
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
            <a href="work-basket.php" class="active"><i class="fas fa-briefcase"></i> Work Basket (<?php echo $activeCount; ?>)</a>
            <a href="employee-payments.php"><i class="fas fa-wallet"></i> Payments</a>
            <a href="Employee_profiles.php"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="staff-attendance.php"><i class="fas fa-clock"></i> Attendance</a>
        </nav>

        <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <div class="main">
        <header class="admin-top-bar">
            <div class="brand-section">
                <h1><span class="title-dot"></span> Work Basket</h1>
                <div class="welcome-text">
                    <span>Welcome, <strong><?php echo htmlspecialchars($first_name); ?></strong></span>
                    <span>&bull;</span>
                    <span class="count-chip-yellow"><i class="fas fa-briefcase"></i> Assigned Active: <?php echo $activeCount; ?></span>
                </div>
            </div>

            <div class="header-right-group">
                <button type="button" class="notif-btn" id="notifToggleBtn" onclick="toggleNotifDropdown()" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <?php if ($notifCount > 0): ?>
                        <span class="notif-badge"><?php echo $notifCount; ?></span>
                    <?php endif; ?>
                </button>

                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-header">
                        <span><i class="fas fa-bell" style="color:var(--yellow-warm);"></i> Notifications &amp; Alerts</span>
                        <span class="notif-pill-count"><?php echo $notifCount; ?> Updates</span>
                    </div>
                    <?php if ($status_msg !== ''): ?>
                        <div class="notif-item alert-yellow">
                            <strong><?php echo $status_is_error ? 'Alert' : 'Just Sent'; ?>:</strong>
                            <?php echo htmlspecialchars($status_msg); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (empty($notifications) && $status_msg === ''): ?>
                        <div class="notif-item" style="color:var(--text-muted);">
                            No new review or task notifications right now.
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($notifications, 0, 5) as $n): ?>
                            <div class="notif-item <?php echo $n['status'] === 'Rejected' ? 'alert-yellow' : ''; ?>">
                                <strong><?php echo htmlspecialchars($n['title']); ?></strong>
                                <span class="notif-status-tag"><?php echo htmlspecialchars($n['status']); ?></span>
                                <?php if ($n['note'] !== ''): ?>
                                    <div style="color:var(--text-main); margin-top:4px;"><?php echo htmlspecialchars($n['note']); ?></div>
                                <?php endif; ?>
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
            <?php if ($status_msg !== ''): ?>
                <div class="alert-banner <?php echo $status_is_error ? 'err' : 'ok'; ?>">
                    <i class="fas <?php echo $status_is_error ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($status_msg); ?></span>
                </div>
            <?php endif; ?>

            <div class="section-heading-bar">
                <h2><i class="fas fa-layer-group" style="color:var(--green-primary);"></i> Assigned Work Queue</h2>
                <span class="section-badge-green">Click a card to upload or message Admin</span>
            </div>

            <div class="basket-grid">
                <?php if (empty($activeDocs)): ?>
                    <div class="card-panel" style="grid-column: 1 / -1; color:var(--text-muted);">
                        No assigned work items in your basket right now.
                    </div>
                <?php else: ?>
                    <?php foreach ($activeDocs as $doc): ?>
                        <div class="doc-card" onclick='openWorkModal(<?php echo json_encode([
                            "id" => (int)$doc["id"],
                            "source_table" => (string)$doc["source_table"],
                            "title" => (string)($doc["file_name"] ?? "Work Item"),
                            "client_id" => (string)($doc["client_id"] ?? ""),
                            "company_name" => (string)($doc["company_name"] ?? ""),
                            "instruction" => (string)($doc["instruction"] ?? ""),
                            "admin_note" => (string)($doc["admin_note"] ?? ""),
                            "has_file" => (bool)($doc["has_file"] ?? false),
                            "download_url" => (string)($doc["download_url"] ?? ""),
                        ], JSON_UNESCAPED_UNICODE); ?>)'>
                            <div class="doc-card-top">
                                <span class="pill"><i class="fas fa-thumbtack"></i> <?php echo htmlspecialchars((string)($doc['status'] ?? 'Assigned')); ?></span>
                                <span class="doc-submit-hint"><i class="fas fa-paper-plane"></i> Submit</span>
                            </div>
                            <h3><?php echo htmlspecialchars((string)($doc['file_name'] ?? 'Work Item')); ?></h3>
                            <div style="font-size:12px; color:var(--text-muted); margin-bottom:8px;">
                                Client: <strong style="color:var(--green-deep);"><?php echo htmlspecialchars(!empty($doc['company_name']) ? $doc['company_name'] : ($doc['client_id'] ?? '—')); ?></strong>
                            </div>
                            <?php if (!empty($doc['instruction'])): ?>
                                <div style="font-size:12.5px; color:var(--text-main); background:#ffffff; border:1px solid var(--border); border-left:3px solid var(--yellow-accent); padding:8px 10px; border-radius:8px;">
                                    <?php echo htmlspecialchars((string)$doc['instruction']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($doc['admin_note'])): ?>
                                <div style="font-size:12px; color:#854d0e; background:var(--yellow-soft); border:1px solid var(--yellow-border); padding:8px 10px; border-radius:8px; margin-top:8px;">
                                    <?php echo htmlspecialchars((string)$doc['admin_note']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="card-panel">
                <h3 style="margin:0 0 14px 0; font-size:16px; font-weight:800; color:var(--green-deep);">
                    <i class="fas fa-history" style="color:var(--green-primary);"></i> Recent Submissions to Admin Review
                </h3>
                <table>
                    <thead>
                        <tr>
                            <th>Submitted</th>
                            <th>Type</th>
                            <th>Item / Client</th>
                            <th>Message / File</th>
                            <th>Admin Status &amp; Feedback</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historySubmissions)): ?>
                            <tr><td colspan="5" style="color:var(--text-muted);">No submissions logged yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($historySubmissions as $h):
                                $isDoc = (($h['submission_type'] ?? 'document') === 'document');
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)($h['submitted_at'] ?? '—')); ?></td>
                                    <td>
                                        <span class="pill <?php echo $isDoc ? 'doc' : ''; ?>">
                                            <?php echo $isDoc ? 'DOCUMENT' : 'MESSAGE'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string)($h['service_or_doc'] ?? '')); ?></strong><br>
                                        <small style="color:var(--text-muted);"><?php echo htmlspecialchars(!empty($h['company_name']) ? $h['company_name'] : ($h['client_id'] ?? '')); ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($h['staff_message'])): ?>
                                            <div><?php echo nl2br(htmlspecialchars((string)$h['staff_message'])); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($h['result_file'])): ?>
                                            <div style="margin-top:4px;">
                                                <a href="../uploads/vault/<?php echo rawurlencode(basename((string)$h['result_file'])); ?>" target="_blank" style="color:var(--green-primary); font-weight:700;">
                                                    <i class="fas fa-paperclip"></i> <?php echo htmlspecialchars((string)$h['result_file']); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="pill <?php echo (($h['review_status'] ?? '') === 'Released') ? 'doc' : ''; ?>">
                                            <?php echo htmlspecialchars((string)($h['review_status'] ?? 'Pending Review')); ?>
                                        </span>
                                        <?php if (!empty($h['admin_note'])): ?>
                                            <div style="font-size:12px; color:#854d0e; background:var(--yellow-soft); padding:5px 8px; border-radius:6px; margin-top:6px;"><?php echo htmlspecialchars((string)$h['admin_note']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal-backdrop" id="workModal">
        <div class="modal-box">
            <h3 id="modalTitle" style="margin:0 0 4px 0; color:var(--green-deep); font-weight:800;">Submit Work</h3>
            <div id="modalMeta" style="font-size:12px; color:var(--text-muted); margin-bottom:10px;"></div>
            <div id="modalInstructionBox" style="display:none; background:var(--green-tint); border:1px solid var(--border); border-left:3px solid var(--yellow-accent); padding:10px 12px; border-radius:10px; font-size:12.5px; margin-bottom:10px;"></div>
            <div id="modalDownloadBox" style="display:none; margin-bottom:10px;">
                <a id="modalDownloadLink" href="#" target="_blank" style="font-size:12.5px; font-weight:700; color:var(--green-primary);">
                    <i class="fas fa-download"></i> Download Assigned File
                </a>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="doc_id" id="modalDocId">
                <input type="hidden" name="source_table" id="modalSourceTable" value="client_documents">
                <input type="hidden" name="submission_mode" id="submissionModeInput" value="document">

                <div class="mode-tabs">
                    <button type="button" class="mode-tab active" id="tabDoc" onclick="setSubmitMode('document')">
                        <i class="fas fa-file-upload"></i> Send Document (+ Note)
                    </button>
                    <button type="button" class="mode-tab" id="tabMsg" onclick="setSubmitMode('message_only')">
                        <i class="fas fa-comment-dots"></i> Send Message Only
                    </button>
                </div>

                <div id="fileFieldWrap" style="margin-bottom:12px;">
                    <label for="result_file" style="font-size:12px; font-weight:700; color:var(--green-deep);">Attach Completed Document</label>
                    <input type="file" name="result_file" id="result_file">
                </div>

                <div style="margin-bottom:16px;">
                    <label for="admin_message" style="font-size:12px; font-weight:700; color:var(--green-deep);">Message / Update for Admin</label>
                    <textarea name="admin_message" id="admin_message" rows="3" placeholder="Write your message or work notes for Admin..."></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn-cancel" onclick="closeWorkModal()">Cancel</button>
                    <button type="submit" name="submit_work" class="btn-submit"><i class="fas fa-paper-plane"></i> Send to Admin</button>
                </div>
            </form>
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

        function setSubmitMode(mode) {
            document.getElementById('submissionModeInput').value = mode;
            const tabDoc = document.getElementById('tabDoc');
            const tabMsg = document.getElementById('tabMsg');
            const fileWrap = document.getElementById('fileFieldWrap');
            if (mode === 'message_only') {
                tabMsg.classList.add('active');
                tabDoc.classList.remove('active');
                fileWrap.style.display = 'none';
                document.getElementById('result_file').value = '';
            } else {
                tabDoc.classList.add('active');
                tabMsg.classList.remove('active');
                fileWrap.style.display = 'block';
            }
        }

        function openWorkModal(item) {
            document.getElementById('modalDocId').value = item.id;
            document.getElementById('modalSourceTable').value = item.source_table || 'client_documents';
            document.getElementById('modalTitle').textContent = item.title || 'Submit Work';
            const clientLabel = item.company_name ? `${item.company_name} (${item.client_id})` : (item.client_id || 'Client');
            document.getElementById('modalMeta').textContent = `Client: ${clientLabel}`;

            const instBox = document.getElementById('modalInstructionBox');
            if (item.instruction) {
                instBox.textContent = item.instruction;
                instBox.style.display = 'block';
            } else {
                instBox.style.display = 'none';
            }

            const dlBox = document.getElementById('modalDownloadBox');
            const dlLink = document.getElementById('modalDownloadLink');
            if (item.has_file && item.download_url) {
                dlLink.href = item.download_url;
                dlBox.style.display = 'block';
            } else {
                dlBox.style.display = 'none';
            }

            document.getElementById('admin_message').value = '';
            setSubmitMode('document');
            document.getElementById('workModal').style.display = 'flex';
        }

        function closeWorkModal() {
            document.getElementById('workModal').style.display = 'none';
        }
    </script>
</body>

</html>