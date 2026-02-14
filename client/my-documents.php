<?php
session_start();
include('../db.php');

// Security Check
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'client') { 
    header("Location: ../Login.php"); 
    exit(); 
}

// FIX: Get the 'identifier' (KK/2026/001) from the session instead of numeric 'id'
$client_identifier = $_SESSION['user']['identifier']; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Document Vault | KKA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --navy: #0b3c74; --orange: #ff8c00; --sidebar: #082d56; --bg: #f8fafc; }
        body { display:flex; margin:0; background:var(--bg); font-family: 'Inter', sans-serif; }
        .sidebar { width:280px; background:var(--sidebar); color:white; height:100vh; position:fixed; padding:30px 20px; box-sizing: border-box; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 22px; color: var(--orange); margin-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .sidebar a { color:rgba(255,255,255,0.7); text-decoration:none; display:flex; align-items:center; gap:12px; padding:14px; margin-bottom:8px; border-radius:12px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background:rgba(255,255,255,0.1); color:white; border-left: 4px solid var(--orange); }
        .logout-link { margin-top: auto; color: #fda4af !important; background: rgba(244, 63, 94, 0.1); }
        .main { margin-left:280px; padding:50px; width:calc(100% - 280px); }
        .vault-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .doc-card { background: white; padding: 20px; border-radius: 15px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; border: 1px solid #e2e8f0; transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .doc-card:hover { transform: translateX(10px); border-color: var(--orange); box-shadow: 0 10px 15px rgba(0,0,0,0.05); }
        .doc-info { display: flex; align-items: center; gap: 15px; }
        .doc-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .btn-download { color: var(--navy); font-weight: 700; text-decoration: none; padding: 8px 15px; border-radius: 8px; background: #f1f5f9; transition: 0.2s; border: none; cursor: pointer; }
        .btn-download:hover { background: var(--navy); color: white; }
        .empty-state { text-align: center; padding: 50px; color: #94a3b8; width: 100%; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>KKA CLIENT</h2>
    <a href="client-dashboard.php"><i class="fas fa-home"></i> Overview</a>
    <a href="request-service.php"><i class="fas fa-plus-circle"></i> New Request</a>
    <a href="my-documents.php" class="active"><i class="fas fa-shield-alt"></i> Document Vault</a>
    <a href="upload-docs.php"><i class="fas fa-cloud-upload-alt"></i> Document Center</a>
    <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
        // FIX: Using $client_identifier and ensuring status is 'Released'
        $query = "SELECT * FROM client_documents 
                  WHERE client_id = '$client_identifier' 
                  AND doc_category = 'vault' 
                  AND status = 'Released' 
                  ORDER BY id DESC";
        
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            while($doc = $result->fetch_assoc()) {
                $file_to_check = $doc['result_file'] ? $doc['result_file'] : $doc['file_name'];
                $ext = strtolower(pathinfo($file_to_check, PATHINFO_EXTENSION));
                
                $icon = "fa-file-alt";
                $icon_bg = "#f1f5f9";
                $icon_color = "#64748b";

                if($ext == 'pdf') { $icon = "fa-file-pdf"; $icon_bg = "#fef2f2"; $icon_color = "#ef4444"; }
                elseif(in_array($ext, ['xls', 'xlsx'])) { $icon = "fa-file-excel"; $icon_bg = "#ecfdf5"; $icon_color = "#10b981"; }
                ?>
                
                <div class="doc-card">
                    <div class="doc-info">
                        <div class="doc-icon" style="background: <?= $icon_bg ?>; color: <?= $icon_color ?>;">
                            <i class="fas <?= $icon ?>"></i>
                        </div>
                        <div>
                            <h4 style="margin:0;"><?= htmlspecialchars($doc['file_name']) ?></h4>
                            <small style="color:#94a3b8;">
                                <?= date('d M Y', strtotime($doc['created_at'])) ?> • 
                                <?= strtoupper($ext) ?> Record
                            </small>
                        </div>
                    </div>
                    <?php if($doc['result_file']): ?>
                        <a href="../uploads/vault/<?= htmlspecialchars($doc['result_file']) ?>" class="btn-download" download>
                            <i class="fas fa-download"></i> Download Result
                        </a>
                    <?php else: ?>
                        <span style="color:#94a3b8; font-size:12px;">Processing...</span>
                    <?php endif; ?>
                </div>
                <?php
            }
        } else {
            echo '<div class="empty-state">
                    <i class="fas fa-folder-open fa-3x" style="margin-bottom:15px; opacity:0.3;"></i>
                    <p>No permanent records found in your vault yet.</p>
                  </div>';
        }
        ?>
    </div>
</div>

</body>
</html>