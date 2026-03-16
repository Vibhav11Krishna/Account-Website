<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'office') {
    header("Location: ../Register.php"); 
    exit();
}

$email = $_SESSION['user']['identifier'];
$updateSuccess = false;

// Logic to complete a task
if (isset($_POST['complete_task'])) {
    $tid = $conn->real_escape_string($_POST['task_id']); 
    $sql = "UPDATE service_requests SET status='Completed' WHERE id='$tid' AND assigned_to='$email'";
    if ($conn->query($sql)) {
        $updateSuccess = true;
    }
}

// Fetch Counts for the Buttons
$pendingRes = $conn->query("SELECT COUNT(*) as total FROM service_requests WHERE assigned_to='$email' AND status='Assigned'");
$pendingCount = $pendingRes->fetch_assoc()['total'];

$completedRes = $conn->query("SELECT COUNT(*) as total FROM service_requests WHERE assigned_to='$email' AND status='Completed'");
$completedCount = $completedRes->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>KKA Staff | Workspace</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
            color: #334155;
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

        .sidebar a:hover,
        .sidebar a.active {
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

        /* Stat Buttons Styling */
        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-btn {
            flex: 1;
            padding: 20px;
            border-radius: 15px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }

        .stat-btn.pending {
            background: white;
            border-left: 5px solid var(--orange);
        }

        .stat-btn.completed {
            background: white;
            border-left: 5px solid #22c55e;
        }

        .stat-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.05);
        }

        .stat-info h3 { margin: 0; font-size: 14px; color: #64748b; text-transform: uppercase; }
        .stat-info h2 { margin: 5px 0 0; font-size: 24px; color: var(--navy); }
        .stat-icon { font-size: 24px; opacity: 0.2; }

        .card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
            margin-bottom: 25px;
            border-left: 5px solid var(--navy);
        }

        .status-pill {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            background: #fff7ed;
            color: var(--orange);
        }

        .btn-done {
            background: #22c55e;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.2s;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #bbf7d0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>Karunesh Kumar & Associates Employee</h2>
        <a href="employee-dashboard.php" class="active"><i class="fas fa-tasks"></i> My Tasks</a>
        <a href="work-basket.php"><i class="fas fa-briefcase"></i> Work Basket</a>
        <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
        <a href="staff-attendance.php"><i class="fas fa-clock"></i> Attendance</a>
        <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
            <h1>Employee Workspace</h1>
            <div style="background:#dcfce7; color:#166534; padding:10px 20px; border-radius:10px; font-weight:bold;">
                <i class="fas fa-check-circle"></i> Active Session
            </div>
        </header>

        <div class="stats-container">
            <button class="stat-btn pending" onclick="location.href='employee-dashboard.php'">
                <div class="stat-info">
                    <h3>Pending Tasks</h3>
                    <h2><?php echo $pendingCount; ?></h2>
                </div>
                <i class="fas fa-clock stat-icon" style="color: var(--orange);"></i>
            </button>

            <button class="stat-btn completed" onclick="location.href='work-basket.php'">
                <div class="stat-info">
                    <h3>Completed</h3>
                    <h2><?php echo $completedCount; ?></h2>
                </div>
                <i class="fas fa-check-circle stat-icon" style="color: #22c55e;"></i>
            </button>
        </div>

        <?php if ($updateSuccess): ?>
            <div class="alert-success">
                <i class="fas fa-thumbs-up"></i> Task successfully moved to completed!
            </div>
        <?php endif; ?>

        <?php
        $tasks = $conn->query("SELECT * FROM service_requests WHERE assigned_to='$email' AND status='Assigned' ORDER BY created_at DESC");
        
        if ($tasks->num_rows > 0) {
            while ($t = $tasks->fetch_assoc()) {
        ?>
                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:start;">
                        <div>
                            <span class="status-pill"><i class="fas fa-hourglass-half"></i> Work in Progress</span>
                            <h3 style="margin:15px 0 5px 0;"><?php echo htmlspecialchars($t['service_type']); ?></h3>
                            <p style="color:#64748b; margin-bottom:15px;">
                                Client ID: <strong><?php echo htmlspecialchars($t['client_id']); ?></strong> 
                                <span style="margin: 0 10px; color: #cbd5e1;">|</span>
                                Date: <?php echo date('d-m-Y', strtotime($t['created_at'])); ?>
                            </p>
                            <p style="background:#f1f5f9; padding:15px; border-radius:10px; font-style:italic; color: #475569;">
                                "<?php echo nl2br(htmlspecialchars($t['description'])); ?>"
                            </p>
                        </div>
                        <form method="POST" onsubmit="return confirm('Mark this task as finished?');">
                            <input type="hidden" name="task_id" value="<?php echo $t['id']; ?>">
                            <button name="complete_task" class="btn-done">
                                <i class="fas fa-check"></i> Mark as Completed
                            </button>
                        </form>
                    </div>
                </div>
        <?php
            }
        } else {
            echo "
            <div class='card' style='text-align:center; border:none; padding: 50px;'>
                <i class='fas fa-coffee' style='font-size: 40px; color: #cbd5e1; margin-bottom: 20px;'></i>
                <h3 style='color: #64748b;'>All caught up! No pending tasks.</h3>
            </div>";
        }
        ?>
    </div>
</body>

</html>