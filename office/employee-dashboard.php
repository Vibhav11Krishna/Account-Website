<?php
session_start();
include('../db.php');

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'office') {
    header("Location: ../Register.php");
    exit();
}

$email = $_SESSION['user']['identifier'];

// Logic to complete a task
if(isset($_POST['complete_task'])){
    $tid = $_POST['task_id'];
    $conn->query("UPDATE service_requests SET status='Completed' WHERE id='$tid'");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>KKA Staff | Workspace</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --navy: #0b3c74; --orange: #ff8c00; --sidebar: #082d56; --bg: #f8fafc; }
        body { display:flex; margin:0; background:var(--bg); font-family: 'Inter', sans-serif; color: #334155; }
        
        .sidebar { width:280px; background:var(--sidebar); color:white; height:100vh; position:fixed; padding:30px 20px; box-sizing: border-box; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 22px; color: var(--orange); margin-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .sidebar a { color:rgba(255,255,255,0.7); text-decoration:none; display:flex; align-items:center; gap:12px; padding:14px; margin-bottom:8px; border-radius:12px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background:rgba(255,255,255,0.1); color:white; border-left: 4px solid var(--orange); }
        
        .logout-link { margin-top: auto; color: #fda4af !important; background: rgba(244, 63, 94, 0.1); }
        .logout-link:hover { background: #e11d48 !important; color: white !important; }

        .main { margin-left:280px; padding:50px; width:calc(100% - 280px); }
        .card { background:white; padding:25px; border-radius:20px; box-shadow:0 10px 25px rgba(0,0,0,0.03); margin-bottom: 25px; border-left: 5px solid var(--navy); }
        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; background: #fff7ed; color: var(--orange); }
        .btn-done { background: #22c55e; color: white; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>KKA STAFF</h2>
    <a href="employee-dashboard.php" class="active"><i class="fas fa-tasks"></i> My Tasks</a>
    <a href="work-basket.php"><i class="fas fa-briefcase"></i> Work Basket</a>
    <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
    <a href="staff-attendance.php"><i class="fas fa-clock"></i> Attendance</a>
    <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
        <h1>My Assigned Tasks</h1>
        <div style="background:#dcfce7; color:#166534; padding:10px 20px; border-radius:10px; font-weight:bold;">
            <i class="fas fa-check-circle"></i> Active Session
        </div>
    </header>

    <?php
    $tasks = $conn->query("SELECT * FROM service_requests WHERE assigned_to='$email' AND status='Assigned'");
    if($tasks->num_rows > 0) {
        while($t = $tasks->fetch_assoc()){
            ?>
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:start;">
                    <div>
                        <span class="status-pill">Work in Progress</span>
                        <h3 style="margin:15px 0 5px 0;"><?php echo $t['service_type']; ?></h3>
                        <p style="color:#64748b; margin-bottom:15px;">Client ID: <strong><?php echo $t['client_id']; ?></strong></p>
                        <p style="background:#f1f5f9; padding:15px; border-radius:10px; font-style:italic;">"<?php echo $t['description']; ?>"</p>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="task_id" value="<?php echo $t['id']; ?>">
                        <button name="complete_task" class="btn-done">Mark as Completed</button>
                    </form>
                </div>
            </div>
            <?php
        }
    } else {
        echo "<div class='card' style='text-align:center; border:none;'><h3>🎉 All caught up! No pending tasks.</h3></div>";
    }
    ?>
</div>
</body>
</html>