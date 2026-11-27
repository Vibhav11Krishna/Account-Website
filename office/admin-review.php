<?php
session_start();
include('../db.php');
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header("Location: ../Register.php");
    exit();
}

function ensureAdminReviewSchema(mysqli $conn): void
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
        $existing = [];
        while ($c = $colRes->fetch_assoc()) {
            $existing[strtolower((string)$c['Field'])] = true;
        }
        if (!isset($existing['result_file'])) {
            $conn->query("ALTER TABLE `client_documents` ADD COLUMN `result_file` VARCHAR(255) DEFAULT NULL");
        }
        if (!isset($existing['admin_note'])) {
            $conn->query("ALTER TABLE `client_documents` ADD COLUMN `admin_note` TEXT DEFAULT NULL");
        }
        if (!isset($existing['shared_with_client'])) {
            $conn->query("ALTER TABLE `client_documents` ADD COLUMN `shared_with_client` TINYINT(1) NOT NULL DEFAULT 0");
        }
    }
}

function syncDocumentToClientDashboard(mysqli $conn, string $clientId, string $docTitle, string $fileName, string $note = '', string $senderEmail = 'admin'): void
{
    $clientId = trim($clientId);
    if ($clientId === '' || $fileName === '') {
        return;
    }

    // Ensure client_documents has a visible 'Released' row for client dashboard queries
    $chkStmt = $conn->prepare(
        "SELECT `id` FROM `client_documents`
         WHERE `client_id` COLLATE utf8mb4_general_ci = ?
           AND (`result_file` = ? OR `file_name` = ?)
         ORDER BY `id` DESC LIMIT 1"
    );
    if ($chkStmt) {
        $chkStmt->bind_param("sss", $clientId, $fileName, $fileName);
        $chkStmt->execute();
        $chkRes = $chkStmt->get_result();
        $existingRow = $chkRes ? $chkRes->fetch_assoc() : null;
        $chkStmt->close();

        if ($existingRow) {
            $eid = (int)$existingRow['id'];
            $upd = $conn->prepare(
                "UPDATE `client_documents`
                 SET `status` = 'Released',
                     `doc_category` = 'vault',
                     `result_file` = COALESCE(NULLIF(`result_file`, ''), ?),
                     `shared_with_client` = 1
                 WHERE `id` = ?"
            );
            if ($upd) {
                $upd->bind_param("si", $fileName, $eid);
                $upd->execute();
                $upd->close();
            }
        } else {
            $statusReleased = 'Released';
            $docCat = 'vault';
            $sharedFlag = 1;
            $ins = $conn->prepare(
                "INSERT INTO `client_documents`
                 (`client_id`, `file_name`, `assigned_to`, `status`, `doc_category`, `category`, `instruction`, `result_file`, `admin_note`, `shared_with_client`, `created_at`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            if ($ins) {
                $noteStored = $note !== '' ? ("[Admin Direct Send]: " . $note) : "[Admin Direct Send]: Shared to client dashboard";
                $ins->bind_param(
                    "sssssssssi",
                    $clientId,
                    $docTitle,
                    $senderEmail,
                    $statusReleased,
                    $docCat,
                    $docTitle,
                    $note,
                    $fileName,
                    $noteStored,
                    $sharedFlag
                );
                $ins->execute();
                $ins->close();
            }
        }
    }

    // If a dedicated client_vault_documents table exists in your DB, mirror the shared file there too
    $tblCheck = $conn->query("SHOW TABLES LIKE 'client_vault_documents'");
    if ($tblCheck && $tblCheck->num_rows > 0) {
        $vStmt = $conn->prepare(
            "INSERT INTO `client_vault_documents` (`client_id`, `title`, `file_name`, `notes`, `uploaded_by`, `created_at`)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        if ($vStmt) {
            $vStmt->bind_param("sssss", $clientId, $docTitle, $fileName, $note, $senderEmail);
            $vStmt->execute();
            $vStmt->close();
        }
    }
}

$adminName = $_SESSION['user']['name'] ?? 'Admin Executive';
$lookupId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
$lookupIdent = trim((string)($_SESSION['user']['identifier'] ?? ($_SESSION['user']['email'] ?? '')));

if (isset($conn) && $conn instanceof mysqli) {
    ensureAdminReviewSchema($conn);

    if ($lookupId > 0) {
        $stmtAdmin = $conn->prepare("SELECT `name` FROM `users` WHERE `id` = ? LIMIT 1");
        if ($stmtAdmin) {
            $stmtAdmin->bind_param("i", $lookupId);
            $stmtAdmin->execute();
            $resAdmin = $stmtAdmin->get_result();
            if ($resAdmin && ($rowAdmin = $resAdmin->fetch_assoc()) && !empty($rowAdmin['name'])) {
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
            if ($resAdmin && ($rowAdmin = $resAdmin->fetch_assoc()) && !empty($rowAdmin['name'])) {
                $adminName = $rowAdmin['name'];
            }
            $stmtAdmin->close();
        }
    }
}
$initials = strtoupper(substr(trim((string)$adminName), 0, 1) ?: 'A');

$status_msg = "";
$status_is_error = false;
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'released') $status_msg = "Document approved and sent to the Client Dashboard!";
    if ($_GET['msg'] === 'rejected') $status_msg = "Submission sent back to staff with feedback.";
    if ($_GET['msg'] === 'direct_sent') $status_msg = "Document directly sent to the Client Dashboard!";
    if ($_GET['msg'] === 'direct_err') {
        $status_msg = "Please select a client and attach a valid file to send directly.";
        $status_is_error = true;
    }
}

// 1. Approve & Release employee document/message to Client Dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['release_file'])) {
    $id = (int)($_POST['doc_id'] ?? 0);
    if ($id > 0) {
        $docRow = null;
        $getDoc = $conn->prepare("SELECT `client_id`, `file_name`, `category`, `result_file`, `admin_note` FROM `client_documents` WHERE `id` = ? LIMIT 1");
        if ($getDoc) {
            $getDoc->bind_param("i", $id);
            $getDoc->execute();
            $gRes = $getDoc->get_result();
            if ($gRes) {
                $docRow = $gRes->fetch_assoc();
            }
            $getDoc->close();
        }

        $stmtRel = $conn->prepare(
            "UPDATE `client_documents`
             SET `status` = 'Released', `shared_with_client` = 1, `admin_note` = NULL
             WHERE `id` = ?"
        );
        if ($stmtRel) {
            $stmtRel->bind_param("i", $id);
            $stmtRel->execute();
            $stmtRel->close();
        }

        $stmtLogRel = $conn->prepare(
            "UPDATE `work_submissions`
             SET `review_status` = 'Released', `admin_note` = NULL, `reviewed_at` = NOW()
             WHERE `source_table` = 'client_documents' AND `source_id` = ? AND `review_status` = 'Pending Review'"
        );
        if ($stmtLogRel) {
            $stmtLogRel->bind_param("i", $id);
            $stmtLogRel->execute();
            $stmtLogRel->close();
        }

        if ($docRow && !empty($docRow['client_id']) && !empty($docRow['result_file'])) {
            $title = (string)($docRow['file_name'] ?: ($docRow['category'] ?: 'Client Document'));
            syncDocumentToClientDashboard(
                $conn,
                (string)$docRow['client_id'],
                $title,
                (string)$docRow['result_file'],
                '',
                $lookupIdent !== '' ? $lookupIdent : 'admin'
            );
        }

        header("Location: admin-review.php?msg=released");
        exit();
    }
}

// 2. Reject / Send back to staff with feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_file'])) {
    $id = (int)($_POST['doc_id'] ?? 0);
    $note = trim((string)($_POST['reject_note'] ?? ''));
    if ($id > 0 && $note !== '') {
        $formattedFeedback = "[Admin Feedback]: " . $note;
        $stmtRej = $conn->prepare("UPDATE `client_documents` SET `status` = 'Assigned', `admin_note` = ? WHERE `id` = ?");
        if ($stmtRej) {
            $stmtRej->bind_param("si", $formattedFeedback, $id);
            $stmtRej->execute();
            $stmtRej->close();
        }
        $stmtLogRej = $conn->prepare(
            "UPDATE `work_submissions`
             SET `review_status` = 'Rejected', `admin_note` = ?, `reviewed_at` = NOW()
             WHERE `source_table` = 'client_documents' AND `source_id` = ? AND `review_status` = 'Pending Review'"
        );
        if ($stmtLogRej) {
            $stmtLogRej->bind_param("si", $formattedFeedback, $id);
            $stmtLogRej->execute();
            $stmtLogRej->close();
        }
        header("Location: admin-review.php?msg=rejected");
        exit();
    }
}

// 3. Direct Admin -> Client Dashboard Document Send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['direct_send_to_client'])) {
    $targetClientId = trim((string)($_POST['direct_client_id'] ?? ''));
    $customDocTitle = trim((string)($_POST['direct_doc_title'] ?? ''));
    $directNote = trim((string)($_POST['direct_note'] ?? ''));
    $hasDirectFile = !empty($_FILES['direct_doc_file']['name']);

    if ($targetClientId === '' || !$hasDirectFile) {
        header("Location: admin-review.php?msg=direct_err");
        exit();
    }

    $fileErr = (int)($_FILES['direct_doc_file']['error'] ?? UPLOAD_ERR_NO_FILE);
    $origName = (string)($_FILES['direct_doc_file']['name'] ?? '');
    $tmpName = (string)($_FILES['direct_doc_file']['tmp_name'] ?? '');

    if ($fileErr === UPLOAD_ERR_OK && $origName !== '' && is_uploaded_file($tmpName)) {
        $vaultDir = "../uploads/vault/";
        if (!is_dir($vaultDir)) {
            mkdir($vaultDir, 0777, true);
        }
        $safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($origName));
        $savedName = time() . "_client_" . $safeBase;

        if (move_uploaded_file($tmpName, $vaultDir . $savedName)) {
            $docLabel = $customDocTitle !== '' ? $customDocTitle : pathinfo($origName, PATHINFO_FILENAME);
            syncDocumentToClientDashboard(
                $conn,
                $targetClientId,
                $docLabel,
                $savedName,
                $directNote,
                $lookupIdent !== '' ? $lookupIdent : 'admin'
            );
            header("Location: admin-review.php?msg=direct_sent");
            exit();
        }
    }

    header("Location: admin-review.php?msg=direct_err");
    exit();
}

// Load clients list for Direct Send form
$clientOptions = [];
if (isset($conn) && $conn instanceof mysqli) {
    $cRes = $conn->query("SELECT `client_id`, `company_name`, `owner_name` FROM `client_profiles` ORDER BY `company_name` ASC, `client_id` ASC");
    if ($cRes) {
        while ($cRow = $cRes->fetch_assoc()) {
            $clientOptions[] = $cRow;
        }
    }
}

function renderGroupedSections(mysqli $conn, string $condition, string $section_key): void
{
    $sql = "SELECT cd.*,
                   cp.company_name,
                   cp.owner_name,
                   cp.phone AS client_phone,
                   cp.business_email AS client_email,
                   ws.submission_type AS logged_type,
                   ws.staff_message AS logged_message,
                   ws.submitted_at AS logged_submitted_at,
                   ws.reviewed_at AS logged_reviewed_at
            FROM `client_documents` cd
            LEFT JOIN `client_profiles` cp ON cd.client_id COLLATE utf8mb4_general_ci = cp.client_id
            LEFT JOIN `work_submissions` ws ON ws.id = (
                SELECT MAX(ws2.id)
                FROM `work_submissions` ws2
                WHERE ws2.source_table = 'client_documents' AND ws2.source_id = cd.id
            )
            WHERE $condition
            ORDER BY cd.id DESC";

    $result = $conn->query($sql);
    $grouped = [];
    while ($result && ($row = $result->fetch_assoc())) {
        $cid = (string)($row['client_id'] ?? 'unassigned');
        $grouped[$cid]['info'] = [
            'name' => $row['owner_name'] ?: $cid,
            'firm' => $row['company_name'] ?: 'Client Profile',
            'phone' => (string)($row['client_phone'] ?? ''),
            'email' => (string)($row['client_email'] ?? ''),
        ];
        $grouped[$cid]['docs'][] = $row;
    }

    if (empty($grouped)) {
        echo "<p style='color:var(--text-muted); padding:24px; background:var(--card-bg); border-radius:16px; border:1px dashed var(--border); font-size:13.5px;'>No items found in this category.</p>";
        return;
    }

    foreach ($grouped as $cid => $data) {
        $folderId = $section_key . "_" . preg_replace('/[^A-Za-z0-9_-]/', '_', $cid);
        ?>
        <div class="folder-group">
            <div class="folder-header" onclick="toggleFolder('<?php echo htmlspecialchars($folderId, ENT_QUOTES); ?>')">
                <span>
                    <i class="fas fa-folder" style="color:#fbbf24; margin-right:12px;"></i>
                    <strong style="color:var(--navy);"><?php echo htmlspecialchars($data['info']['name']); ?></strong>
                    <small style="color:var(--text-muted); margin-left:6px;">
                        (<?php echo htmlspecialchars($data['info']['firm']); ?> &bull; <?php echo htmlspecialchars($cid); ?>)
                    </small>
                </span>
                <span style="display:flex; align-items:center; gap:10px;">
                    <span style="font-size:11.5px; font-weight:700; background:#eff6ff; color:var(--navy); padding:3px 10px; border-radius:999px;">
                        <?php echo count($data['docs']); ?> item(s)
                    </span>
                    <i class="fas fa-chevron-down" id="icon_<?php echo htmlspecialchars($folderId, ENT_QUOTES); ?>" style="color:var(--text-muted); transition:0.25s;"></i>
                </span>
            </div>
            <div class="folder-content" id="<?php echo htmlspecialchars($folderId, ENT_QUOTES); ?>">
                <?php foreach ($data['docs'] as $doc) {
                    $hasResultFile = !empty($doc['result_file']);
                    $rawStaffMsg = trim((string)($doc['logged_message'] ?? ''));
                    if ($rawStaffMsg === '' && !empty($doc['admin_note']) && strpos((string)$doc['admin_note'], '[Staff Msg]:') === 0) {
                        $rawStaffMsg = trim(substr((string)$doc['admin_note'], strlen('[Staff Msg]:')));
                    }
                    $isMessageOnly = !$hasResultFile;
                    $typeBadgeLabel = $isMessageOnly ? 'MESSAGE SUBMISSION' : 'DOCUMENT SUBMISSION';
                    $typeBadgeBg = $isMessageOnly ? '#fef3c7' : '#dcfce7';
                    $typeBadgeColor = $isMessageOnly ? '#92400e' : '#166534';
                    $isReleased = in_array(strtolower(trim((string)($doc['status'] ?? ''))), ['released', 'approved'], true);
                ?>
                    <div class="review-card">
                        <div style="flex:1; min-width:260px;">
                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:8px;">
                                <span style="background:<?php echo $typeBadgeBg; ?>; color:<?php echo $typeBadgeColor; ?>; font-size:10.5px; font-weight:800; padding:3px 10px; border-radius:999px;">
                                    <i class="fas <?php echo $isMessageOnly ? 'fa-comment-dots' : 'fa-file-alt'; ?>"></i> <?php echo $typeBadgeLabel; ?>
                                </span>
                                <?php if ($isReleased): ?>
                                    <span style="background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; font-size:10.5px; font-weight:800; padding:3px 10px; border-radius:999px;">
                                        <i class="fas fa-user-check"></i> Visible on Client Dashboard
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($doc['logged_submitted_at'])): ?>
                                    <span style="font-size:11.5px; color:var(--text-muted); font-weight:600;">
                                        <i class="far fa-clock"></i> Submitted: <?php echo htmlspecialchars((string)$doc['logged_submitted_at']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($doc['logged_reviewed_at'])): ?>
                                    <span style="font-size:11.5px; color:#047857; font-weight:600;">
                                        <i class="fas fa-check-double"></i> Reviewed: <?php echo htmlspecialchars((string)$doc['logged_reviewed_at']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($hasResultFile): ?>
                                <h3 style="margin:0 0 6px 0; font-size:15px; font-weight:700; color:var(--navy);">
                                    <i class="fas fa-file-pdf" style="color:#ef4444; margin-right:6px;"></i>
                                    <?php echo htmlspecialchars((string)$doc['result_file']); ?>
                                </h3>
                            <?php else: ?>
                                <h3 style="margin:0 0 6px 0; font-size:15px; font-weight:700; color:var(--navy);">
                                    <i class="fas fa-envelope-open-text" style="color:var(--orange); margin-right:6px;"></i>
                                    Message on: <?php echo htmlspecialchars((string)($doc['file_name'] ?? $doc['category'] ?? 'Assigned Work')); ?>
                                </h3>
                            <?php endif; ?>

                            <p style="font-size:12.5px; color:var(--text-muted); margin:0 0 10px 0;">
                                Staff: <strong><?php echo htmlspecialchars((string)($doc['assigned_to'] ?? '')); ?></strong>
                                <?php if (!empty($doc['category'])): ?>
                                    &bull; Service: <strong><?php echo htmlspecialchars((string)$doc['category']); ?></strong>
                                <?php endif; ?>
                                <?php if (!empty($data['info']['phone'])): ?>
                                    &bull; Phone: <strong><?php echo htmlspecialchars($data['info']['phone']); ?></strong>
                                <?php endif; ?>
                            </p>

                            <?php if (!empty($doc['instruction'])): ?>
                                <div style="background:#f8fafc; border:1px solid var(--border); padding:9px 12px; border-radius:9px; margin-bottom:10px; font-size:12.5px; color:var(--text-muted);">
                                    <strong style="color:var(--navy);">Original Instruction:</strong> <?php echo nl2br(htmlspecialchars((string)$doc['instruction'])); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($rawStaffMsg !== ''): ?>
                                <div style="background:#fffbeb; border:1px solid #fde68a; border-left:3px solid var(--orange); padding:11px 14px; border-radius:10px; margin-bottom:12px; font-size:13px; color:var(--text-main);">
                                    <strong style="color:var(--navy); display:block; font-size:11px; text-transform:uppercase; margin-bottom:4px;">
                                        Employee Message to Admin
                                    </strong>
                                    <?php echo nl2br(htmlspecialchars($rawStaffMsg)); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($doc['admin_note']) && strpos((string)$doc['admin_note'], '[Staff Msg]:') !== 0): ?>
                                <div style="background:#fef2f2; border:1px solid #fecaca; border-left:3px solid var(--danger); padding:10px 14px; border-radius:10px; margin-bottom:12px; font-size:12.5px; color:#991b1b;">
                                    <?php echo nl2br(htmlspecialchars((string)$doc['admin_note'])); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($hasResultFile): ?>
                                <a href="../uploads/vault/<?php echo rawurlencode(basename((string)$doc['result_file'])); ?>" target="_blank" class="btn btn-view">
                                    <i class="fas fa-eye"></i> View Uploaded File
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if ($section_key === 'pending'): ?>
                            <div style="width:270px; max-width:100%;">
                                <form method="POST">
                                    <input type="hidden" name="doc_id" value="<?php echo (int)$doc['id']; ?>">
                                    <button type="submit" name="release_file" class="btn btn-approve">
                                        <i class="fas fa-share-square"></i> <?php echo $hasResultFile ? 'Send to Client Dashboard' : 'Approve & Mark Reviewed'; ?>
                                    </button>
                                </form>
                                <form method="POST" style="margin-top:10px;">
                                    <input type="hidden" name="doc_id" value="<?php echo (int)$doc['id']; ?>">
                                    <textarea name="reject_note" placeholder="Reply or rejection feedback for staff..." required rows="2"></textarea>
                                    <button type="submit" name="reject_file" class="btn btn-reject">
                                        <i class="fas fa-times"></i> Reject / Send Back
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php } ?>
            </div>
        </div>
        <?php
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quality Control | KKA Admin</title>
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
            --border: #e2e8f0;
            --shadow-sm: 0 2px 8px rgba(11, 60, 116, 0.04);
            --shadow-md: 0 10px 25px -5px rgba(11, 60, 116, 0.08);
            --shadow-lg: 0 20px 30px -10px rgba(11, 60, 116, 0.12);
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
            padding: 32px 40px 48px;
            flex: 1;
            width: 100%;
        }

        .direct-send-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-left: 4px solid var(--orange);
            border-radius: 16px;
            padding: 20px 22px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }

        .direct-send-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
            margin-top: 14px;
            align-items: end;
        }

        .field-label {
            display: block;
            font-size: 11.5px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 5px;
        }

        .field-input {
            width: 100%;
            padding: 9px 11px;
            border-radius: 9px;
            border: 1.5px solid var(--border);
            font-family: inherit;
            font-size: 12.5px;
            background: #ffffff;
        }

        .btn-direct {
            background: var(--navy);
            color: #ffffff;
            padding: 10px 16px;
            border-radius: 9px;
            border: none;
            font-family: inherit;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            width: 100%;
        }

        .btn-direct:hover {
            background: var(--navy-light);
        }

        .section-heading {
            margin: 24px 0 12px 0;
            font-size: 17px;
            font-weight: 800;
            color: var(--navy);
        }

        .section-heading:first-of-type {
            margin-top: 0;
        }

        .folder-group {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            margin-bottom: 14px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .folder-header {
            padding: 14px 18px;
            background: #f8fafc;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        .folder-content {
            display: block;
            padding: 16px 18px;
        }

        .review-card {
            display: flex;
            justify-content: space-between;
            align-items: start;
            gap: 20px;
            flex-wrap: wrap;
            padding: 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 12px;
            background: #ffffff;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px 14px;
            border-radius: 9px;
            font-weight: 700;
            font-size: 12.5px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-family: inherit;
            width: 100%;
        }

        .btn-view {
            background: #eff6ff;
            color: var(--navy);
            width: auto;
        }

        .btn-approve {
            background: var(--success);
            color: #ffffff;
        }

        .btn-reject {
            background: var(--danger);
            color: #ffffff;
            margin-top: 8px;
        }

        textarea {
            width: 100%;
            padding: 9px 11px;
            border-radius: 9px;
            border: 1.5px solid var(--border);
            font-family: inherit;
            font-size: 12.5px;
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
            <a href="javascript:void(0)" class="dropdown-btn active" onclick="toggleMenu('vaultMenu', 'vaultChev')">
                <i class="fas fa-folder-open"></i> Documents
                <i class="fas fa-chevron-down rotate-chevron" id="vaultChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content show-menu" id="vaultMenu">
                <a href="admin-review.php" class="active">Quality Control</a>
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

    <div class="main">
        <header class="admin-top-bar">
            <div class="brand-section">
                <h1>Quality Control &amp; Client Delivery</h1>
                <div class="welcome-text">Approve employee work for the client dashboard or send a document directly to a client</div>
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
            <?php if ($status_msg !== ''): ?>
                <div style="background:<?php echo $status_is_error ? '#fef2f2' : '#dcfce7'; ?>; color:<?php echo $status_is_error ? '#991b1b' : '#166534'; ?>; padding:12px 16px; border-radius:12px; font-weight:700; font-size:13px; margin-bottom:18px;">
                    <?php echo htmlspecialchars($status_msg); ?>
                </div>
            <?php endif; ?>

            <div class="direct-send-card">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                    <h2 style="margin:0; font-size:15.5px; font-weight:800; color:var(--navy);">
                        <i class="fas fa-paper-plane" style="color:var(--orange);"></i> Direct Send Document to Client Dashboard
                    </h2>
                    <span style="font-size:12px; color:var(--text-muted);">Send a file straight to a client when you already have it</span>
                </div>

                <form method="POST" enctype="multipart/form-data" class="direct-send-grid">
                    <div>
                        <label class="field-label" for="direct_client_id">Select Client</label>
                        <select name="direct_client_id" id="direct_client_id" class="field-input" required>
                            <option value="">-- Choose Client --</option>
                            <?php foreach ($clientOptions as $cOpt):
                                $cidVal = (string)($cOpt['client_id'] ?? '');
                                $cnameVal = (string)($cOpt['company_name'] ?: ($cOpt['owner_name'] ?: $cidVal));
                            ?>
                                <option value="<?php echo htmlspecialchars($cidVal, ENT_QUOTES); ?>">
                                    <?php echo htmlspecialchars($cnameVal . " (" . $cidVal . ")"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="direct_doc_title">Document Title (Optional)</label>
                        <input type="text" name="direct_doc_title" id="direct_doc_title" class="field-input" placeholder="e.g., GST Return Copy">
                    </div>

                    <div>
                        <label class="field-label" for="direct_doc_file">Attach Document</label>
                        <input type="file" name="direct_doc_file" id="direct_doc_file" class="field-input" required>
                    </div>

                    <div>
                        <label class="field-label" for="direct_note">Note for Client (Optional)</label>
                        <input type="text" name="direct_note" id="direct_note" class="field-input" placeholder="Short note for client...">
                    </div>

                    <div>
                        <button type="submit" name="direct_send_to_client" class="btn-direct">
                            <i class="fas fa-upload"></i> Send to Client
                        </button>
                    </div>
                </form>
            </div>

            <h2 class="section-heading">Pending Review (Approve to Send to Client Dashboard)</h2>
            <?php renderGroupedSections($conn, "LOWER(TRIM(COALESCE(cd.status, ''))) = 'pending review'", "pending"); ?>

            <h2 class="section-heading">Released / Shared With Client Dashboard</h2>
            <?php renderGroupedSections($conn, "LOWER(TRIM(COALESCE(cd.status, ''))) IN ('released', 'approved')", "released"); ?>

            <h2 class="section-heading">Sent Back / In-Progress With Feedback</h2>
            <?php renderGroupedSections($conn, "LOWER(TRIM(COALESCE(cd.status, ''))) IN ('assigned', 'in-progress') AND cd.admin_note IS NOT NULL AND cd.admin_note <> ''", "feedback"); ?>
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

        function toggleMenu(menuId, chevId) {
            const menu = document.getElementById(menuId);
            const chev = document.getElementById(chevId);
            if (menu) {
                menu.classList.toggle('show-menu');
            }
            if (chev) {
                chev.classList.toggle('rotate-chevron');
            }
        }

        function toggleFolder(id) {
            const el = document.getElementById(id);
            const icon = document.getElementById('icon_' + id);
            if (!el) return;
            const hidden = (el.style.display === 'none');
            el.style.display = hidden ? 'block' : 'none';
            if (icon) {
                icon.style.transform = hidden ? 'rotate(0deg)' : 'rotate(-90deg)';
            }
        }
    </script>
</body>

</html>