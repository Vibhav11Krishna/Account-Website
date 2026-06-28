<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'client') {
    header("Location: ../Login.php");
    exit();
}

// FIX: Get the 'identifier' (KK/2026/001) from the session instead of numeric 'id'
$client_identifier = $_SESSION['user']['identifier'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Document Vault | KKA Client</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Styling to match your Admin Sidebar */
      

        .rotate-chevron {
            transform: rotate(180deg);
        }

        :root {
            --navy: #0b3c74;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
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
    
    /* ADD THIS LINE */
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
            align-items: center;
            gap: 12px;
            padding: 14px;
            margin-bottom: 8px;
            border-radius: 12px;
            transition: 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 4px solid var(--orange);
        }

        .logout-link {
            margin-top: auto;
            color: #fda4af !important;
            background: rgba(244, 63, 94, 0.1);
        }

        .main {
            margin-left: 280px;
            padding: 50px;
            width: calc(100% - 280px);
        }

        .vault-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .doc-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            transition: 0.3s;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        }

        .doc-card:hover {
            transform: translateX(10px);
            border-color: var(--orange);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.05);
        }

        .doc-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .doc-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .btn-download {
            color: var(--navy);
            font-weight: 700;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 8px;
            background: #f1f5f9;
            transition: 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-download:hover {
            background: var(--navy);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 50px;
            color: #94a3b8;
            width: 100%;
        }
        .folder-header {
    background: #ffffff;
    padding: 15px 20px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 5px;
    font-weight: 600;
    color: var(--navy);
}
.folder-content {
    display: none; /* Hidden by default */
    padding: 10px;
    background: #f8fafc;
    border-bottom-left-radius: 10px;
    border-bottom-right-radius: 10px;
    margin-bottom: 20px;
}
    </style>
</head>

<body>

   <div class="sidebar">
        <h2>Karunesh Kumar & Associates Client</h2>
        <a href="client-dashboard.php" ><i class="fas fa-chart-line"></i> Overview</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" onclick="toggleFinances()">
                <i class="fas fa-wallet"></i> My Finances
                <i class="fas fa-chevron-down rotate-chevron" id="financeChevron" style="margin-left:auto; font-size:12px;"></i>
            </a>
            <div class="dropdown-content" id="financeMenu" style="display:none; background:rgba(0,0,0,0.2); border-radius:10px; margin:0 10px;">
                <a href="my-quotations.php"><i class="fas fa-file-alt"></i> Quotations</a>
                <a href="my-invoices.php"><i class="fas fa-file-invoice-dollar"></i> Invoices (Pay)</a>
                <a href="my-receipts.php"><i class="fas fa-receipt"></i>Acknowledgement</a>
            </div>
        </div>

        <a href="my-documents.php" style="background:rgba(255,255,255,0.1); color:white !important;"><i class="fas fa-folder-open"></i> Document Vault</a>
        <a href="upload-docs.php"><i class="fas fa-cloud-upload-alt"></i> Upload Center</a>
        <a href="request-service.php"><i class="fas fa-plus-circle"></i> New Request</a>

        <a href="../logout.php" style="margin-top:auto; color:#fda4af !important; background: rgba(244, 63, 94, 0.1); padding:14px; border-radius:12px; text-decoration:none; display:flex; align-items:center; gap:12px;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <div class="main">
        <div class="vault-header">
            <div>
                <h1><i class="fas fa-lock" style="color:var(--navy);"></i> Document Vault</h1>
                <p style="color: #64748b;">Permanent records, KYC documents, and issued certificates.</p>
            </div>
        </div>
<div class="vault-container">
    <?php
    // 1. Fetch data using prepared statements for security
    $stmt = $conn->prepare("
        SELECT file_name, result_file AS path, created_at AS date, 'vault' AS source, doc_category AS category 
        FROM client_documents WHERE client_id = ? AND status = 'Released'
        UNION ALL
        SELECT file_name, file_path AS path, upload_date AS date, 'shared' AS source, file_type AS category 
        FROM client_files WHERE client_id = ? AND status = 'Released'
        ORDER BY date DESC
    ");
    $stmt->bind_param("ss", $client_identifier, $client_identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    $grouped_files = [];
    while ($doc = $result->fetch_assoc()) {
        // Use Y-m-d for internal grouping, display formatted date in HTML
        $dateKey = date('Y-m-d', strtotime($doc['date']));
        $grouped_files[$dateKey][] = $doc;
    }

    foreach ($grouped_files as $dateKey => $files) {
        $displayDate = date('d M Y', strtotime($dateKey));
        $folderId = 'folder_' . $dateKey; // Unique ID based on Y-m-d
    ?>
        <div class="folder-header" onclick="toggleFolder('<?= $folderId ?>')" style="cursor:pointer; padding:15px; background:#f8fafc; border-radius:8px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
            <span><i class="fas fa-folder" style="color:var(--orange); margin-right:10px;"></i> Date: <?= $displayDate ?></span>
            <i class="fas fa-chevron-down"></i>
        </div>
        
        <div id="<?= $folderId ?>" class="folder-content" style="display:none; margin-bottom:20px;">
            <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden;">
                <tr style="background:#edf2f7; text-align:left;">
                    <th style="padding:12px;">File Name</th>
                    <th style="padding:12px;">Category</th>
                    <th style="padding:12px;">Action</th>
                </tr>
                <?php foreach ($files as $doc) { 
                    $file_url = ($doc['source'] == 'vault') ? '../uploads/vault/' . $doc['path'] : $doc['path'];
                ?>
                <tr>
                    <td style="padding:12px; border-bottom:1px solid #e2e8f0;"><?= htmlspecialchars($doc['file_name']) ?></td>
                    <td style="padding:12px; border-bottom:1px solid #e2e8f0;"><?= htmlspecialchars($doc['category']) ?></td>
                    <td style="padding:12px; border-bottom:1px solid #e2e8f0;">
                        <a href="<?= htmlspecialchars($file_url) ?>" class="btn-download" style="color:var(--navy); font-weight:bold; text-decoration:none;" download>Download</a>
                    </td>
                </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>
</div>
</div>
    <script>
        function toggleFinances() {
            var menu = document.getElementById("financeMenu");
            var chevron = document.getElementById("financeChevron");
            if (menu.style.display === "none" || menu.style.display === "") {
                menu.style.display = "block";
                chevron.classList.add("rotate-chevron");
            } else {
                menu.style.display = "none";
                chevron.classList.remove("rotate-chevron");
            }
        }
    </script>
    <script>
function toggleFolder(id) {
    var content = document.getElementById(id);
    if (content.style.display === "block") {
        content.style.display = "none";
    } else {
        content.style.display = "block";
    }
}
</script>
</body>

</html>