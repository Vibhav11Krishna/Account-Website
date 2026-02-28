<?php
session_start();
include('../db.php');
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'client') { header("Location: ../Login.php"); exit(); }
$cid = $_SESSION['user']['identifier'];

if(isset($_POST['req'])){
    $srv = mysqli_real_escape_string($conn, $_POST['srv']); 
    $desc = mysqli_real_escape_string($conn, $_POST['desc']);
    $conn->query("INSERT INTO service_requests (client_id, service_type, description, status) VALUES ('$cid', '$srv', '$desc', 'Pending')");
    $msg = "Success! Our experts have been notified.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Request Service | KKA Client</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --navy: #0b3c74; --orange: #ff8c00; --sidebar: #082d56; --bg: #f8fafc; }
        body { display:flex; margin:0; background:var(--bg); font-family: 'Inter', sans-serif; color: #334155; }
        
        /* Dropdown Sidebar Styles */
        .dropdown-content a { color: rgba(255,255,255,0.7) !important; text-decoration: none; display: block; transition: 0.3s; }
        .dropdown-content a:hover { color: white !important; background: rgba(255,255,255,0.1); }
        .rotate-chevron { transform: rotate(180deg); }

        /* Shared Sidebar */
        .sidebar { width:280px; background:var(--sidebar); color:white; height:100vh; position:fixed; padding:30px 20px; box-sizing: border-box; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 22px; color: var(--orange); margin-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .sidebar a { color:rgba(255,255,255,0.7); text-decoration:none; display:flex; align-items:center; gap:12px; padding:14px; margin-bottom:8px; border-radius:12px; transition: 0.3s; cursor: pointer; }
        .sidebar a:hover, .sidebar a.active { background:rgba(255,255,255,0.1); color:white; border-left: 4px solid var(--orange); }
        .logout-link { margin-top: auto; color: #fda4af !important; background: rgba(244, 63, 94, 0.1); }

        /* Main Content Styles */
        .main { margin-left:280px; padding:50px; width:calc(100% - 280px); }
        .card { background:white; padding:40px; border-radius:24px; max-width: 600px; box-shadow:0 10px 25px rgba(0,0,0,0.03); }
        input, select, textarea { width:100%; padding:14px; margin:10px 0 20px; border:1.5px solid #e2e8f0; border-radius:12px; font-size:16px; outline:none; box-sizing: border-box; }
        button { background:var(--navy); color:white; border:none; padding:18px; width:100%; border-radius:12px; font-weight:700; cursor:pointer; transition: 0.3s; }
        button:hover { background:var(--orange); }
        .success-banner { background:#dcfce7; color:#166534; padding:15px; border-radius:12px; margin-bottom:20px; border-left: 5px solid #22c55e; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>KKA CLIENT</h2>
    
    <a href="client-dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'client-dashboard.php' ? 'active' : ''; ?>">
        <i class="fas fa-chart-line"></i> Overview
    </a>

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
    <a href="upload-docs.php"><i class="fas fa-cloud-upload-alt"></i> Upload Center</a>
    
    <a href="request-service.php" class="active">
        <i class="fas fa-plus-circle"></i> New Request
    </a>
    
    <a href="../logout.php" class="logout-link" style="margin-top:auto; color:#fda4af !important;">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<div class="main">
    <h1>Create Service Request</h1>
    
    <?php if(isset($msg)): ?>
        <div class="success-banner">
            <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <form method="POST">
            <label style="font-weight:600;">Which service do you need?</label>
            <select name="srv">
                <option>GST Monthly Filing</option>
                <option>Income Tax Return (ITR)</option>
                <option>Balance Sheet Audit</option>
                <option>TDS Returns</option>
                <option>Digital Signature (DSC)</option>
            </select>

            <label style="font-weight:600;">Description</label>
            <textarea name="desc" placeholder="Provide details about your request..." required rows="5"></textarea>

            <button name="req">Submit to KKA Office</button>
        </form>
    </div>
</div>

<script>
// Exact same function as dashboard.php
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