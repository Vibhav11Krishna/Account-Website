<?php
session_start();
include('../db.php');

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'client') {
    header("Location: ../Login.php");
    exit();
}

$cid = $_SESSION['user']['identifier'];
$status_msg = "";

if(isset($_POST['upload'])){
    $category = $_POST['category'];
    $target_dir = "../documents/";
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

    $file_name = basename($_FILES["doc_file"]["name"]);
    $safe_cid = str_replace('/', '-', $cid); 
    $new_filename = $safe_cid . "_" . time() . "_" . str_replace(' ', '_', $file_name);
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["doc_file"]["tmp_name"], $target_file)) {
        $conn->query("INSERT INTO client_documents (client_id, file_name, file_path, category) VALUES ('$cid', '$file_name', '$new_filename', '$category')");
        $status_msg = "<div style='color:#166534; padding:15px; background:#dcfce7; border-radius:12px; margin-bottom:20px;'><i class='fas fa-check-circle'></i> File uploaded successfully!</div>";
    } else {
        $status_msg = "<div style='color:#991b1b; padding:15px; background:#fee2e2; border-radius:12px; margin-bottom:20px;'>Upload failed. Check folder permissions.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Document Center | KKA Client</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --navy: #0b3c74; --orange: #ff8c00; --sidebar: #082d56; --bg: #f8fafc; }
        body { display:flex; margin:0; background:var(--bg); font-family: 'Inter', sans-serif; color: #334155; }
        
        /* Shared Sidebar Styles */
        .sidebar { width:280px; background:var(--sidebar); color:white; height:100vh; position:fixed; padding:30px 20px; box-sizing: border-box; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 22px; color: var(--orange); margin-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .sidebar a { color:rgba(255,255,255,0.7); text-decoration:none; display:flex; align-items:center; gap:12px; padding:14px; margin-bottom:8px; border-radius:12px; transition: 0.3s; cursor: pointer; }
        .sidebar a:hover, .sidebar a.active { background:rgba(255,255,255,0.1); color:white; border-left: 4px solid var(--orange); }

        /* Dropdown specific styles */
        .dropdown-content a { color: rgba(255,255,255,0.7) !important; text-decoration: none; display: block; transition: 0.3s; }
        .dropdown-content a:hover { color: white !important; background: rgba(255,255,255,0.1); }
        .rotate-chevron { transform: rotate(180deg); }

        .logout-link { margin-top: auto; color: #fda4af !important; background: rgba(244, 63, 94, 0.1); }

        /* Main Content */
        .main { margin-left:280px; padding:50px; width:calc(100% - 280px); }
        .upload-container { display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; }
        .card { background:white; padding:30px; border-radius:24px; box-shadow:0 10px 25px rgba(0,0,0,0.03); }
        input, select { width:100%; padding:12px; margin:10px 0 20px; border:1.5px solid #e2e8f0; border-radius:10px; box-sizing: border-box; }
        .btn-primary { background:var(--navy); color:white; border:none; padding:15px; width:100%; border-radius:10px; font-weight:700; cursor:pointer; transition: 0.3s; }
        .btn-primary:hover { background: var(--orange); transform: translateY(-2px); }
        
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th { text-align:left; padding:12px; background:#f1f5f9; color:var(--navy); font-size:12px; }
        td { padding:12px; border-bottom:1px solid #f1f5f9; font-size:14px; }
        .badge { padding:4px 8px; border-radius:6px; font-size:11px; background:#e0f2fe; color:#0369a1; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>KKA CLIENT</h2>
        <a href="client-dashboard.php"><i class="fas fa-chart-line"></i> Overview</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleFinances()">
                <i class="fas fa-wallet"></i> My Finances 
                <i class="fas fa-chevron-down" id="financeChevron" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="financeMenu" style="display:none; background:rgba(0,0,0,0.2); border-radius:10px; margin:0 10px;">
                <a href="my-quotations.php" style="padding:10px 15px; font-size:14px;"><i class="fas fa-file-alt"></i> Quotations</a>
                <a href="my-invoices.php" style="padding:10px 15px; font-size:14px;"><i class="fas fa-file-invoice-dollar"></i> Invoices (Pay)</a>
                <a href="my-receipts.php" style="padding:10px 15px; font-size:14px;"><i class="fas fa-receipt"></i> Receipts</a>
            </div>
        </div>

        <a href="my-documents.php"><i class="fas fa-folder-open"></i> Document Vault</a>
        <a href="upload-docs.php" class="active"><i class="fas fa-cloud-upload-alt"></i> Upload Center</a>
        <a href="request-service.php"><i class="fas fa-plus-circle"></i> New Request</a>
        
        <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <h1>Document Center</h1>
        <?php echo $status_msg; ?>

        <div class="upload-container">
            <div class="card">
                <h3>Upload New File</h3>
                <form method="POST" enctype="multipart/form-data">
                   <label style="font-size:14px; font-weight:600;">Document Category</label>
                    <select name="category" required>
                        <optgroup label="Identity & Registration">
                            <option value="KYC Docs">PAN / Aadhar / Passport</option>
                            <option value="Business Registration">GST / MSME / Incorporation</option>
                        </optgroup>
                        <optgroup label="Banking & Finance">
                            <option value="Bank Statement">Bank Statements</option>
                            <option value="Loan Documents">Loan Sanction / Statements</option>
                            <option value="Investments">Mutual Funds / Fixed Deposits</option>
                        </optgroup>
                        <optgroup label="Taxation">
                            <option value="Income Tax">ITR Forms / Form 16 / 26AS</option>
                            <option value="GST Sales">GST Sales Invoices</option>
                            <option value="GST Purchase">GST Purchase Bills</option>
                            <option value="TDS Docs">TDS Certificates / Challans</option>
                        </optgroup>
                        <optgroup label="Others">
                            <option value="Utility Bills">Electricity / Rent Agreements</option>
                            <option value="Miscellaneous">Other Miscellaneous Docs</option>
                        </optgroup>
                    </select>
                    <label style="font-size:14px; font-weight:600;">Select File</label>
                    <input type="file" name="doc_file" required>
                    <button type="submit" name="upload" class="btn-primary">Upload to Office</button>
                </form>
            </div>

            <div class="card">
                <h3>Recently Uploaded</h3>
                <table>
                    <thead>
                        <tr><th>File Name</th><th>Category</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $docs = $conn->query("SELECT * FROM client_documents WHERE client_id='$cid' ORDER BY id DESC LIMIT 5");
                        if($docs->num_rows > 0) {
                            while($d = $docs->fetch_assoc()){
                                echo "<tr>
                                    <td><strong>{$d['file_name']}</strong></td>
                                    <td><span class='badge'>{$d['category']}</span></td>
                                    <td style='color:#64748b;'>".date('d M Y', strtotime($d['upload_date']))."</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' style='text-align:center; padding:20px; color:#94a3b8;'>No uploads yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
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
</body>
</html>