<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'client') {
    header("Location: ../Login.php");
    exit();
}

// FIX: Get the 'identifier' (KK/2026/001) from the session instead of numeric 'id'
$client_identifier =$_SESSION['user']['identifier'];
$clientName =$_SESSION['user']['name'] ?? 'Client';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Vault | KKA Client</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --coffee-espresso: #1f110c;
            --coffee-roast: #3b2319;
            --coffee-mocha: #5c3a28;
            --coffee-latte: #8c5a3c;
            --coffee-caramel: #b87a4b;
            --coffee-cream: #f8f4f0;
            --coffee-foam: #fdfaf7;
            --coffee-sand: #efe7df;
            --card-bg: #ffffff;
            --text-main: #23140e;
            --text-muted: #6b5349;
            --border: #e6d8cf;
            --border-neutral: #eadfd8;
            --shadow-sm: 0 2px 10px rgba(59, 35, 25, 0.06);
            --shadow-md: 0 12px 28px -6px rgba(59, 35, 25, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            display: flex;
            margin: 0;
            min-height: 100vh;
            background: var(--coffee-cream);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
        }

        /* Sidebar Styles */
        .sidebar {
            width: 285px;
            background: linear-gradient(180deg, var(--coffee-roast) 0%, var(--coffee-espresso) 100%);
            color: #ffffff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            border-right: 4px solid var(--coffee-caramel);
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 0 6px 16px 6px;
            margin-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .sidebar-brand-icons {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 6px 8px;
            border-radius: 11px;
            background: rgba(184, 122, 75, 0.22);
            border: 1px solid rgba(243, 213, 181, 0.35);
            color: #f3d5b5;
            font-size: 14px;
            flex-shrink: 0;
        }

        .sidebar h2 {
            font-size: 14.5px;
            font-weight: 800;
            color: #f3d5b5;
            margin: 0;
            line-height: 1.3;
        }

        /* Sidebar Profile Card */
        .sidebar-profile {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            padding: 16px 14px;
            margin-bottom: 20px;
            text-align: center;
        }

        .sidebar-avatar {
            width: 58px;
            height: 58px;
            margin: 0 auto 10px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--coffee-latte), var(--coffee-caramel));
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.28);
            border: 2px solid rgba(243, 213, 181, 0.55);
        }

        .sidebar-profile-name {
            margin: 0;
            font-size: 14.5px;
            font-weight: 800;
            color: #ffffff;
            word-break: break-word;
        }

        .sidebar-profile-id {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 10px;
            border-radius: 999px;
            background: rgba(184, 122, 75, 0.2);
            border: 1px solid rgba(243, 213, 181, 0.3);
            color: #f3d5b5;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        .nav-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.45);
            margin: 0 0 8px 10px;
        }

        .sidebar a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            margin-bottom: 6px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 600;
            transition: 0.22s ease;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: rgba(184, 122, 75, 0.22);
            color: #ffffff;
            border-left: 4px solid var(--coffee-caramel);
            padding-left: 17px;
        }

        .dropdown-content {
            display: none;
            background: rgba(0, 0, 0, 0.24);
            border-radius: 12px;
            margin: 0 4px 10px;
            padding: 4px 0;
        }

        .dropdown-content a {
            font-size: 13px;
            padding: 10px 14px;
            margin-bottom: 2px;
        }

        .logout-link {
            margin-top: auto;
            color: #f3d5b5 !important;
            background: rgba(0, 0, 0, 0.28) !important;
            border: 1px solid rgba(243, 213, 181, 0.22);
            justify-content: center;
            font-weight: 700 !important;
        }

        .logout-link:hover {
            background: var(--coffee-latte) !important;
            color: #ffffff !important;
            padding-left: 14px !important;
            border-left: 1px solid rgba(243, 213, 181, 0.4) !important;
        }

        .rotate-chevron {
            transform: rotate(180deg);
            transition: transform 0.25s ease;
        }

        /* Main Content Area */
        .main {
            margin-left: 285px;
            padding: 40px 48px;
            width: calc(100% - 285px);
            min-height: 100vh;
        }

        .vault-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 28px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--border);
        }

        .vault-header h1 {
            margin: 0;
            font-size: 27px;
            font-weight: 800;
            color: var(--coffee-espresso);
            letter-spacing: -0.4px;
        }

        .vault-subtitle {
            margin: 4px 0 0;
            font-size: 13.5px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .vault-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--coffee-roast);
            border: 1px solid var(--border);
            font-size: 12.5px;
            font-weight: 700;
            box-shadow: var(--shadow-sm);
        }

        .folder-header {
            background: var(--card-bg);
            padding: 16px 22px;
            border: 1px solid var(--border);
            border-left: 5px solid var(--coffee-mocha);
            border-radius: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--coffee-espresso);
            box-shadow: var(--shadow-sm);
            transition: all 0.22s ease;
        }

        .folder-header:hover {
            background: var(--coffee-foam);
            border-left-color: var(--coffee-caramel);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .folder-meta {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 12.5px;
            color: var(--text-muted);
            font-weight: 700;
        }

        .folder-count-badge {
            background: var(--coffee-sand);
            color: var(--coffee-roast);
            border: 1px solid var(--border);
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
        }

        .folder-content {
            display: none;
            padding: 6px 0 18px 0;
        }

        .folder-table-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 13px 18px;
            background: var(--coffee-cream);
            color: var(--coffee-espresso);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border);
        }

        td {
            padding: 14px 18px;
            border-bottom: 1px solid var(--border-neutral);
            font-size: 14px;
            vertical-align: middle;
        }

        tbody tr:hover {
            background: var(--coffee-foam);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .category-tag {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 8px;
            background: var(--coffee-foam);
            color: var(--coffee-mocha);
            border: 1px solid var(--border);
            font-size: 12px;
            font-weight: 700;
        }

        .btn-download {
            color: #ffffff;
            font-weight: 700;
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--coffee-roast), var(--coffee-mocha));
            transition: 0.22s ease;
            border: none;
            cursor: pointer;
            font-size: 12.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .btn-download:hover {
            background: linear-gradient(135deg, var(--coffee-espresso), var(--coffee-roast));
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(59, 35, 25, 0.2);
        }

        .empty-state {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            text-align: center;
            padding: 54px 24px;
            color: var(--text-muted);
            box-shadow: var(--shadow-sm);
        }

        .empty-state i {
            font-size: 32px;
            color: var(--coffee-latte);
            margin-bottom: 12px;
            display: block;
        }

        @media (max-width: 1024px) {
            .main {
                padding: 28px 24px;
            }
        }
    </style>
</head>

<body>

    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icons">
                <i class="fas fa-user-tie"></i>
                <i class="fas fa-mug-hot"></i>
            </div>
            <h2>Karunesh Kumar &amp; Associates</h2>
        </div>

        <div class="sidebar-profile">
            <div class="sidebar-avatar">
                <i class="fas fa-user"></i>
            </div>
            <h4 class="sidebar-profile-name"><?php echo htmlspecialchars($clientName); ?></h4>
            <span class="sidebar-profile-id">ID: <?php echo htmlspecialchars($client_identifier); ?></span>
        </div>

        <div class="nav-label">Client Menu</div>
        <a href="client-dashboard.php"><i class="fas fa-chart-line"></i> Overview</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" onclick="toggleFinances()">
                <i class="fas fa-wallet"></i> My Finances
                <i class="fas fa-chevron-down rotate-chevron" id="financeChevron" style="margin-left:auto; font-size:12px;"></i>
            </a>
            <div class="dropdown-content" id="financeMenu">
                <a href="my-quotations.php"><i class="fas fa-file-alt"></i> Quotations</a>
                <a href="my-invoices.php"><i class="fas fa-file-invoice-dollar"></i> Invoices (Pay)</a>
                <a href="my-receipts.php"><i class="fas fa-receipt"></i> Acknowledgement</a>
            </div>
        </div>

        <a href="my-documents.php" class="active"><i class="fas fa-folder-open"></i> Document Vault</a>
        <a href="upload-docs.php"><i class="fas fa-cloud-upload-alt"></i> Upload Center</a>
        <a href="request-service.php"><i class="fas fa-plus-circle"></i> New Request</a>

        <a href="../logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </aside>

    <main class="main">
        <div class="vault-header">
            <div>
                <h1><i class="fas fa-lock" style="color:var(--coffee-roast);"></i> Document Vault</h1>
                <p class="vault-subtitle">Permanent records, KYC documents, and issued certificates.</p>
            </div>
            <div class="vault-pill">
                <i class="fas fa-id-badge" style="color:var(--coffee-latte);"></i>
                <span>Client ID: <strong><?php echo htmlspecialchars($client_identifier); ?></strong></span>
            </div>
        </div>

        <div class="vault-container">
            <?php
            // 1. Fetch data using prepared statements for security
            $stmt =$conn->prepare("
                SELECT file_name, result_file AS path, created_at AS date, 'vault' AS source, doc_category AS category 
                FROM client_documents WHERE client_id = ? AND status = 'Released'
                UNION ALL
                SELECT file_name, file_path AS path, upload_date AS date, 'shared' AS source, file_type AS category 
                FROM client_files WHERE client_id = ? AND status = 'Released'
                ORDER BY date DESC
            ");
            $stmt->bind_param("ss", $client_identifier, $client_identifier);$stmt->execute();
            $result =$stmt->get_result();

            $grouped_files = [];
            while ($doc =$result->fetch_assoc()) {
                // Use Y-m-d for internal grouping, display formatted date in HTML
                $dateKey = date('Y-m-d', strtotime($doc['date']));$grouped_files[$dateKey][] =$doc;
            }
            ?>

            <?php if (!empty($grouped_files)): ?>
                <?php foreach ($grouped_files as $dateKey =>$files):
                    $displayDate = date('d M Y', strtotime($dateKey));
                    $folderId = 'folder_' .$dateKey; // Unique ID based on Y-m-d
                    $chevronId = 'chevron_' .$dateKey;
                    $fileCount = count($files);
                ?>
                    <div class="folder-header" onclick="toggleFolder('<?php echo htmlspecialchars($folderId); ?>', '<?php echo htmlspecialchars($chevronId); ?>')">
                        <span>
                            <i class="fas fa-folder" style="color:var(--coffee-caramel); margin-right:10px;"></i>
                            Date: <?php echo htmlspecialchars($displayDate); ?>
                        </span>
                        <div class="folder-meta">
                            <span class="folder-count-badge"><?php echo $fileCount; ?> <?php echo ($fileCount === 1) ? 'file' : 'files'; ?></span>
                            <i class="fas fa-chevron-down" id="<?php echo htmlspecialchars($chevronId); ?>" style="transition: transform 0.22s ease;"></i>
                        </div>
                    </div>

                    <div id="<?php echo htmlspecialchars($folderId); ?>" class="folder-content">
                        <div class="folder-table-card">
                            <table>
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Category</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($files as$doc):
                                        $file_url = ($doc['source'] == 'vault') ? '../uploads/vault/' . $doc['path'] :$doc['path'];
                                    ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-file-lines" style="color:var(--coffee-latte); margin-right:8px;"></i>
                                            <strong style="color:var(--coffee-espresso);"><?php echo htmlspecialchars($doc['file_name']); ?></strong>
                                        </td>
                                        <td><span class="category-tag"><?php echo htmlspecialchars($doc['category']); ?></span></td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($file_url); ?>" class="btn-download" download>
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    No released documents available in your vault yet.
                </div>
            <?php endif; ?>
        </div>
    </main>

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

        function toggleFolder(id, chevronId) {
            var content = document.getElementById(id);
            var chevron = document.getElementById(chevronId);
            if (content.style.display === "block") {
                content.style.display = "none";
                if (chevron) chevron.classList.remove("rotate-chevron");
            } else {
                content.style.display = "block";
                if (chevron) chevron.classList.add("rotate-chevron");
            }
        }
    </script>
</body>

</html>