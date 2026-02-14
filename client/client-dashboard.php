<?php
session_start();
include('../db.php');
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'client') { header("Location: ../Login.php"); exit(); }
$cid = $_SESSION['user']['identifier'];
// Logic to handle Business Profile Submission
if(isset($_POST['save_profile'])){
    $comp = mysqli_real_escape_string($conn, $_POST['company_name']);
    $owner = mysqli_real_escape_string($conn, $_POST['owner_name']);
    $gst = mysqli_real_escape_string($conn, $_POST['gst_no']);
    $pan = mysqli_real_escape_string($conn, $_POST['pan_no']);
    $addr = mysqli_real_escape_string($conn, $_POST['address']);

    // Check if profile exists, then Insert or Update
    $check = $conn->query("SELECT id FROM client_profiles WHERE client_id='$cid'");
    if($check->num_rows > 0){
        $conn->query("UPDATE client_profiles SET company_name='$comp', owner_name='$owner', gst_no='$gst', pan_no='$pan', address='$addr' WHERE client_id='$cid'");
    } else {
        $conn->query("INSERT INTO client_profiles (client_id, company_name, owner_name, gst_no, pan_no, address) VALUES ('$cid', '$comp', '$owner', '$gst', '$pan', '$addr')");
    }
    header("Location: client-dashboard.php");
    exit();
}

// Fetch current profile if it exists
$profile = $conn->query("SELECT * FROM client_profiles WHERE client_id='$cid'")->fetch_assoc();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard | KKA Client</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --navy: #0b3c74; --orange: #ff8c00; --sidebar: #082d56; --bg: #f8fafc; }
        body { display:flex; margin:0; background:var(--bg); font-family: 'Inter', sans-serif; color: #334155; }
        
        /* Shared Sidebar */
        .sidebar { width:280px; background:var(--sidebar); color:white; height:100vh; position:fixed; padding:30px 20px; box-sizing: border-box; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 22px; color: var(--orange); margin-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .sidebar a { color:rgba(255,255,255,0.7); text-decoration:none; display:flex; align-items:center; gap:12px; padding:14px; margin-bottom:8px; border-radius:12px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background:rgba(255,255,255,0.1); color:white; border-left: 4px solid var(--orange); }
        .logout-link { margin-top: auto; color: #fda4af !important; background: rgba(244, 63, 94, 0.1); }

        .main { margin-left:280px; padding:50px; width:calc(100% - 280px); }
        .stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border-bottom: 4px solid var(--navy); }
        .stat-card h3 { margin: 0; color: #64748b; font-size: 14px; text-transform: uppercase; }
        .stat-card p { margin: 10px 0 0; font-size: 28px; font-weight: 800; color: var(--navy); }
        
        .card { background:white; padding:30px; border-radius:24px; box-shadow:0 10px 25px rgba(0,0,0,0.03); }
        table { width:100%; border-collapse:collapse; margin-top: 20px; }
        th { text-align:left; padding:15px; background:#f1f5f9; color:var(--navy); font-size: 13px; }
        td { padding:15px; border-bottom:1px solid #f1f5f9; font-size: 15px; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .bg-pending { background: #fef9c3; color: #854d0e; }
        .bg-completed { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>KKA CLIENT</h2>
    <a href="client-dashboard.php" class="active"><i class="fas fa-home"></i> Overview</a>
    <a href="request-service.php"><i class="fas fa-plus-circle"></i> New Request</a>
    <a href="my-documents.php"><i class="fas fa-folder-open"></i> Document Vault</a>
    <a href="upload-docs.php" ><i class="fas fa-cloud-upload-alt"></i> Document Center</a>
    <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <h1>Welcome, <?php echo $_SESSION['user']['name']; ?></h1>
    <p style="color: #64748b; margin-top:-15px;">Client ID: <?php echo $cid; ?></p>

    <div class="stat-grid">
        <div class="stat-card">
            <h3>Active Requests</h3>
            <p><?php echo $conn->query("SELECT id FROM service_requests WHERE client_id='$cid' AND status='Pending'")->num_rows; ?></p>
        </div>
     <div class="stat-card" style="border-color: var(--orange);">
    <h3>Documents Shared</h3>
    <p>
        <?php 
        // Real count from the database for this specific client
        echo $conn->query("SELECT id FROM client_documents WHERE client_id='$cid'")->num_rows; 
        ?>
    </p>
</div>


        <div class="stat-card" style="border-color: #22c55e;">
            <h3>Completed Tasks</h3>
            <p><?php echo $conn->query("SELECT id FROM service_requests WHERE client_id='$cid' AND status='Completed'")->num_rows; ?></p>
        </div>
    </div>
<div class="card" style="margin-bottom: 30px; border-top: 5px solid var(--orange);">
        <?php if(!$profile): ?>
            <h3 style="color:var(--navy);"><i class="fas fa-id-card"></i> Setup Your Business Profile</h3>
            <p style="font-size:14px; color:#64748b;">Welcome! Please provide your official firm details to get started.</p>
            <form method="POST" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top:20px;">
                <input type="text" name="company_name" placeholder="Legal Company Name" required style="padding:12px; border:1px solid #ddd; border-radius:10px;">
                <input type="text" name="owner_name" placeholder="Authorized Person/Proprietor" required style="padding:12px; border:1px solid #ddd; border-radius:10px;">
                <input type="text" name="gst_no" placeholder="GST Number (Optional)" style="padding:12px; border:1px solid #ddd; border-radius:10px;">
                <input type="text" name="pan_no" placeholder="PAN Number" style="padding:12px; border:1px solid #ddd; border-radius:10px;">
                <textarea name="address" placeholder="Registered Office Address" style="grid-column: span 2; padding:12px; border:1px solid #ddd; border-radius:10px;"></textarea>
                <button type="submit" name="save_profile" style="grid-column: span 2; background:var(--navy); color:white; border:none; padding:15px; border-radius:10px; cursor:pointer; font-weight:bold;">Initialize Business Account</button>
            </form>
        <?php else: ?>
            <div style="display:flex; justify-content:space-between; align-items:start;">
                <div>
                    <h2 style="margin:0; color:var(--navy);"><?php echo $profile['company_name']; ?></h2>
                    <p style="margin:5px 0; color:var(--orange); font-weight:bold;">Proprietor: <?php echo $profile['owner_name']; ?></p>
                </div>
                <div style="text-align:right; font-size:13px; color:#64748b;">
                    <p style="margin:0;">GSTIN: <b><?php echo $profile['gst_no'] ?: 'N/A'; ?></b></p>
                    <p style="margin:0;">PAN: <b><?php echo $profile['pan_no'] ?: 'N/A'; ?></b></p>
                </div>
            </div>
            <div style="margin-top:15px; font-size:14px; border-top:1px solid #eee; padding-top:10px;">
                <i class="fas fa-map-marker-alt" style="color:#64748b;"></i> <?php echo $profile['address']; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="card">
        <h3 style="margin:0;">Recent Activity</h3>
        <table>
            <tr><th>Service</th><th>Description</th><th>Status</th></tr>
            <?php
            $history = $conn->query("SELECT * FROM service_requests WHERE client_id='$cid' ORDER BY id DESC LIMIT 5");
            while($h = $history->fetch_assoc()){
                $status_class = "bg-" . strtolower($h['status']);
                echo "<tr>
                    <td><strong>{$h['service_type']}</strong></td>
                    <td style='color:#64748b;'>{$h['description']}</td>
                    <td><span class='badge $status_class'>{$h['status']}</span></td>
                </tr>";
            }
            ?>
        </table>
    </div>
</div>

</body>
</html>