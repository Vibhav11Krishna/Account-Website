<?php
session_start();
include('../db.php');

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Login.php");
    exit();
}

$status_msg = "";
if(isset($_GET['msg'])) {
    if($_GET['msg'] == 'released') $status_msg = "Document released to Client Vault successfully!";
    if($_GET['msg'] == 'rejected') $status_msg = "Document sent back to staff with feedback.";
}

// Release Logic
if(isset($_POST['release_file'])){
    $id = mysqli_real_escape_string($conn, $_POST['doc_id']);
    // Clear any old notes on approval
    $sql = "UPDATE client_documents SET status='Released', admin_note=NULL WHERE id='$id'";
    if($conn->query($sql)) {
        header("Location: admin-review.php?msg=released");
        exit();
    }
}

// Reject Logic with Reason
if(isset($_POST['reject_file'])){
    $id = mysqli_real_escape_string($conn, $_POST['doc_id']);
    $note = mysqli_real_escape_string($conn, $_POST['reject_note']);
    
    $sql = "UPDATE client_documents SET status='Assigned', admin_note='$note' WHERE id='$id'";
    if($conn->query($sql)) {
        header("Location: admin-review.php?msg=rejected");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Review & Release | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
                /* Billing Dropdown Styling */
.dropdown-content {
    display: none;
    background: rgba(0, 0, 0, 0.2);
    margin: 0 5px;
    border-radius: 8px;
    padding-left: 15px; /* Indent sub-items */
}

.dropdown-content a {
    font-size: 14px;
    padding: 10px;
    color: rgba(255, 255, 255, 0.6);
}

.dropdown-content a:hover {
    color: var(--orange);
    border-left: none; /* No border for sub-items */
    background: transparent;
}

.dropdown-btn {
    cursor: pointer;
}

/* When the dropdown is open */
.show-menu {
    display: block;
}

.rotate-chevron {
    transform: rotate(90deg);
}
        :root { --navy: #0b3c74; --orange: #ff8c00; --sidebar: #082d56; --bg: #f8fafc; --danger: #ef4444; --success: #22c55e; }
        body { display:flex; margin:0; background:var(--bg); font-family: 'Inter', sans-serif; color: #334155; }
        .sidebar { width:280px; background:var(--sidebar); color:white; height:100vh; position:fixed; padding:30px 20px; box-sizing: border-box; display:flex; flex-direction:column; }
        .sidebar h2 { font-size: 22px; color: var(--orange); margin-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .sidebar a { color:rgba(255,255,255,0.7); text-decoration:none; display:flex; align-items:center; gap:12px; padding:14px; margin-bottom:8px; border-radius:12px; transition: 0.3s; }
        .sidebar a:hover, .active { background:rgba(255,255,255,0.1); color:white; border-left: 4px solid var(--orange); }
        .main { margin-left:280px; padding:50px; width:calc(100% - 280px); }
        .review-card { background:white; padding:25px; border-radius:20px; box-shadow:0 10px 25px rgba(0,0,0,0.03); margin-bottom: 25px; border-left: 5px solid var(--navy); }
        .action-group { display: flex; flex-direction: column; gap: 15px; width: 350px; }
        .btn { padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; border: none; transition: 0.3s; display: flex; align-items: center; gap: 8px; justify-content: center; font-size: 14px; text-decoration: none; }
        .btn-view { background: #f1f5f9; color: var(--navy); }
        .btn-approve { background: var(--success); color: white; width: 100%; }
        .btn-reject { background: var(--danger); color: white; width: 100%; }
        textarea { width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #e2e8f0; font-family: inherit; resize: none; }
        .status-msg { background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="sidebar">
    <h2>KKA ADMIN</h2>
    <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> Summary</a>
    
    <div class="dropdown-container">
        <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleBilling()">
            <i class="fas fa-file-invoice-dollar"></i> Billing 
            <i class="fas fa-chevron-right" id="chevron" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
        </a>
        <div class="dropdown-content" id="billingMenu">
            <a href="quotations.php"><i class="fas fa-file-signature"></i> Quotations</a>
            <a href="invoices.php"><i class="fas fa-receipt"></i> Invoices</a>
            <a href="receipts.php"><i class="fas fa-check-double"></i> Receipts</a>
            <a href="outstanding.php"><i class="fas fa-exclamation-circle"></i> Outstanding</a>
        </div>
    </div>

    <a href="assign-work.php" ><i class="fas fa-tasks"></i> Assign Work</a>
    <a href="admin-review.php"class="active"><i class="fas fa-file-signature"></i> Quality Control</a>
    <a href="manage-clients.php"><i class="fas fa-users"></i> Manage Clients</a>
    <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
    <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
    <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

    <div class="main">
        <header>
            <h1>Quality Control</h1>
            <p>Approve documents or send them back with feedback.</p>
        </header>

        <?php if($status_msg) echo "<div class='status-msg'>$status_msg</div>"; ?>

        <?php
        $result = $conn->query("SELECT * FROM client_documents WHERE status = 'Pending Review' ORDER BY id DESC");
        if($result->num_rows > 0) {
            while($doc = $result->fetch_assoc()) { ?>
                <div class="review-card" style="display:flex; justify-content:space-between; align-items:start;">
                    <div class="doc-details">
                        <p><small>CLIENT ID: <?php echo $doc['client_id']; ?></small></p>
                        <h3 style="margin-bottom:15px;"><i class="fas fa-file-pdf"></i> <?php echo $doc['result_file']; ?></h3>
                        <p>Staff: <b><?php echo $doc['assigned_to']; ?></b></p>
                        <br>
                        <a href="../uploads/vault/<?php echo $doc['result_file']; ?>" target="_blank" class="btn btn-view" style="display:inline-flex;">
                            <i class="fas fa-eye"></i> Preview Document
                        </a>
                    </div>

                    <div class="action-group">
                        <form method="POST">
                            <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                            <button name="release_file" class="btn btn-approve">
                                <i class="fas fa-check-circle"></i> Release to Client
                            </button>
                        </form>

                        <hr style="width:100%; border:0; border-top:1px solid #eee;">

                        <form method="POST">
                            <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                            <textarea name="reject_note" placeholder="Reason for rejection (e.g. GST error, missing info)" required rows="3"></textarea>
                            <button name="reject_file" class="btn btn-reject" style="margin-top:10px;">
                                <i class="fas fa-times-circle"></i> Reject & Notify Staff
                            </button>
                        </form>
                    </div>
                </div>
        <?php } } else { echo "<div class='empty-state'><h3>No documents pending review.</h3></div>"; } ?>
    </div>
    <script>
        function toggleBilling() {
    const menu = document.getElementById('billingMenu');
    const chevron = document.getElementById('chevron');
    
    menu.classList.toggle('show-menu');
    chevron.classList.toggle('rotate-chevron');
}
    </script>
</body>
</html>