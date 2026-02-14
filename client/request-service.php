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
    <title>Request Service | KKA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style-shared.css"> <style>
        /* Reuse sidebar styles from above */
        :root { --navy: #0b3c74; --orange: #ff8c00; --sidebar: #082d56; --bg: #f8fafc; }
        body { display:flex; margin:0; background:var(--bg); font-family: 'Inter', sans-serif; }
        .sidebar { width:280px; background:var(--sidebar); color:white; height:100vh; position:fixed; padding:30px 20px; box-sizing: border-box; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 22px; color: var(--orange); margin-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .sidebar a { color:rgba(255,255,255,0.7); text-decoration:none; display:flex; align-items:center; gap:12px; padding:14px; margin-bottom:8px; border-radius:12px; }
        .sidebar a.active { background:rgba(255,255,255,0.1); color:white; border-left: 4px solid var(--orange); }
        .logout-link { margin-top: auto; color: #fda4af !important; }

        .main { margin-left:280px; padding:50px; width:calc(100% - 280px); }
        .card { background:white; padding:40px; border-radius:24px; max-width: 600px; box-shadow:0 10px 25px rgba(0,0,0,0.03); }
        input, select, textarea { width:100%; padding:14px; margin:10px 0 20px; border:1.5px solid #e2e8f0; border-radius:12px; font-size:16px; outline:none; }
        button { background:var(--navy); color:white; border:none; padding:18px; width:100%; border-radius:12px; font-weight:700; cursor:pointer; }
        button:hover { background:var(--orange); }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>KKA CLIENT</h2>
     <a href="client-dashboard.php"><i class="fas fa-home"></i> Overview</a>
    <a href="request-service.php"class="active"><i class="fas fa-plus-circle"></i> New Request</a>
    <a href="my-documents.php"><i class="fas fa-folder-open"></i> Document Vault</a>
    <a href="upload-docs.php" ><i class="fas fa-cloud-upload-alt"></i> Document Center</a>
    <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <h1>Create Service Request</h1>
    <?php if(isset($msg)) echo "<div style='background:#dcfce7; color:#166534; padding:15px; border-radius:12px; margin-bottom:20px;'>$msg</div>"; ?>
    
    <div class="card">
        <form method="POST">
            <label style="font-weight:600;">Which service do you need?</label>
            <select name="srv">
                <option>GST Monthly Filing</option>
                <option>Income Tax Return (ITR)</option>
                <option>Balance Sheet Audit</option>
                <option>TDS Returns</option>
            </select>

            <label style="font-weight:600;">Description</label>
            <textarea name="desc" placeholder="E.g. Filing for the month of March 2026..." required rows="5"></textarea>

            <button name="req">Submit to KKA Office</button>
        </form>
    </div>
</div>

</body>
</html>