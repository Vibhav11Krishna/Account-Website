<?php
session_start();
include('../db.php');

// Security Check
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'office') {
    header("Location: ../Login.php");
    exit();
}

$email = $_SESSION['user']['identifier'];
$status_msg = "";

if(isset($_POST['submit_work'])){
    $doc_id = mysqli_real_escape_string($conn, $_POST['doc_id']);
    $target_dir = "../uploads/vault/"; 
    
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
    
    $file_name = time() . "_" . basename($_FILES["result_file"]["name"]);
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["result_file"]["tmp_name"], $target_file)) {
        $sql = "UPDATE client_documents SET 
                status='Pending Review', 
                doc_category='vault', 
                result_file='$file_name',
                admin_note=NULL 
                WHERE id='$doc_id'";
        
        if($conn->query($sql)) {
            $status_msg = "Submitted! Waiting for Admin approval.";
        }
    } else {
        $status_msg = "Error: System could not write to directory.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>KKA Staff | Work Basket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --navy: #0b3c74; --orange: #ff8c00; --sidebar: #082d56; --bg: #f8fafc; --danger: #be123c; --info: #0ea5e9; }
        body { display:flex; margin:0; background:var(--bg); font-family: 'Inter', sans-serif; }
        
        /* Sidebar Flex Layout */
        .sidebar { 
            width:280px; 
            background:var(--sidebar); 
            color:white; 
            height:100vh; 
            position:fixed; 
            padding:30px 20px; 
            box-sizing: border-box; 
            display: flex; 
            flex-direction: column; 
        }
        
        .sidebar h2 { font-size: 22px; color: var(--orange); margin-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .sidebar a { color:rgba(255,255,255,0.7); text-decoration:none; display:flex; align-items:center; gap:12px; padding:14px; margin-bottom:8px; border-radius:12px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background:rgba(255,255,255,0.1); color:white; border-left: 4px solid var(--orange); }
        
        /* Pushes Logout to Bottom */
        .logout-link { margin-top: auto; color: #fda4af !important; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px !important; }

        .main { margin-left:280px; padding:50px; width:calc(100% - 280px); }
        .card { background:white; padding:30px; border-radius:24px; box-shadow:0 10px 25px rgba(0,0,0,0.03); margin-bottom: 30px; border: 1px solid #e2e8f0; }
        .status-pill { padding: 6px 16px; border-radius: 20px; font-size: 11px; font-weight: 800; background: #fff7ed; color: #9a3412; border: 1px solid #ffedd5; text-transform: uppercase; }
        .instruction-box { background: #f0f9ff; border: 1px solid #bae6fd; color: #0369a1; padding: 20px; border-radius: 16px; margin: 15px 0; }
        .rejection-box { background: #fff1f2; border: 1px solid #fecaca; color: var(--danger); padding: 20px; border-radius: 16px; margin: 20px 0; }
        .upload-box { background: #f8fafc; border: 2px dashed #cbd5e1; padding: 25px; border-radius: 18px; margin-top: 20px; }
        .btn-submit { background: var(--navy); color: white; border: none; padding: 12px 30px; border-radius: 10px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>KKA STAFF</h2>
        <a href="employee-dashboard.php"><i class="fas fa-tasks"></i> My Tasks</a>
        <a href="work-basket.php" class="active"><i class="fas fa-briefcase"></i> Work Basket</a>
        <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
        <a href="staff-attendance.php"><i class="fas fa-clock"></i> Attendance</a>
        
        <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <h1>Active Work Basket</h1>
        <?php if($status_msg) echo "<div style='color:green; font-weight:bold; margin-bottom:20px;'>$status_msg</div>"; ?>

        <?php
        $docs = $conn->query("SELECT * FROM client_documents WHERE assigned_to='$email' AND status NOT IN ('Pending Review', 'Released')");
        
        if($docs->num_rows > 0) {
            while($d = $docs->fetch_assoc()){
            ?>
                <div class="card">
                    <span class="status-pill"><i class="fas fa-clock"></i> Task Pending</span>
                    <h2 style="margin: 20px 0 5px 0; color: var(--navy);"><?php echo $d['file_name']; ?></h2>
                    <p style="color: #64748b; margin-bottom: 10px;">Client ID: <strong><?php echo (!empty($d['client_id'])) ? $d['client_id'] : "No ID Assigned"; ?></strong></p>

                    <?php if(!empty($d['instruction'])): ?>
                        <div class="instruction-box">
                            <strong><i class="fas fa-info-circle"></i> WORK INSTRUCTIONS:</strong>
                            <p style="margin: 5px 0 0 0; font-size: 15px;"><?php echo nl2br(htmlspecialchars($d['instruction'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($d['admin_note'])): ?>
                        <div class="rejection-box">
                            <strong><i class="fas fa-exclamation-circle"></i> ADMIN CORRECTION REQUIRED:</strong>
                            <p style="margin: 5px 0 0 0;"><?php echo $d['admin_note']; ?></p>
                        </div>
                    <?php endif; ?>

                    <div style="margin: 20px 0;">
                        <a href="../uploads/center/<?php echo $d['file_name']; ?>" download style="color:var(--navy); font-weight:bold; text-decoration:none; background:#e2e8f0; padding:10px 15px; border-radius:8px;">
                            <i class="fas fa-download"></i> Download Original File
                        </a>
                    </div>
                    
                    <div class="upload-box">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="doc_id" value="<?php echo $d['id']; ?>">
                            <label style="display:block; margin-bottom:10px; font-size:14px; font-weight:600;">Upload Final Result:</label>
                            <input type="file" name="result_file" required style="margin-bottom:15px;">
                            <button name="submit_work" class="btn-submit">Submit for Review</button>
                        </form>
                    </div>
                </div>
            <?php } 
        } else {
            echo "<p>No active tasks found for $email.</p>";
        } ?>
    </div>
</body>
</html>