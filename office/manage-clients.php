<?php
session_start();
include('../db.php');

// Security Check
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

// Delete Logic
if(isset($_GET['delete'])){
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    $conn->query("DELETE FROM users WHERE id='$id' AND role='client'");
    header("Location: manage-clients.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Clients | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --navy: #0b3c74; 
            --orange: #ff8c00; 
            --sidebar: #082d56; 
            --bg: #f8fafc; 
            --text: #334155;
            --border: #e2e8f0;
        }

        body { 
            display:flex; 
            margin:0; 
            background:var(--bg); 
            font-family: 'Inter', sans-serif; 
            color: var(--text); 
        }

        /* Sidebar Navigation */
        .sidebar { 
            width:280px; 
            background:var(--sidebar); 
            color:white; 
            height:100vh; 
            position:fixed; 
            padding:30px 20px; 
            box-sizing: border-box; 
            display:flex; 
            flex-direction:column; 
        }
        .sidebar h2 { 
            color: var(--orange); 
            font-size: 22px; 
            margin-bottom: 40px; 
            border-bottom: 1px solid rgba(255,255,255,0.1); 
            padding-bottom: 20px; 
        }
        .sidebar a { 
            color:rgba(255,255,255,0.7); 
            text-decoration:none; 
            display:flex; 
            align-items:center; 
            gap:12px; 
            padding:16px; 
            margin-bottom:8px; 
            border-radius:12px; 
            transition: 0.3s; 
        }
        .sidebar a:hover, .sidebar a.active { 
            background:rgba(255,255,255,0.1); 
            color:white; 
            border-left: 4px solid var(--orange); 
        }

        /* Main Content Area */
        .main { 
            margin-left:280px; 
            padding:50px; 
            width:calc(100% - 280px); 
            box-sizing: border-box;
        }

        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        /* Enhanced Table Design */
        .table-card { 
            background:white; 
            padding:10px; 
            border-radius:20px; 
            box-shadow:0 10px 25px rgba(0,0,0,0.02); 
            border: 1px solid var(--border);
        }

        table { width:100%; border-collapse:collapse; }
        
        th { 
            text-align:left; 
            padding:20px; 
            background:#fcfcfd; 
            color:#64748b; 
            font-size: 12px; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 0.05em;
            border-bottom: 2px solid #f1f5f9;
        }

        td { 
            padding:25px 20px; 
            border-bottom:1px solid #f1f5f9; 
            vertical-align: middle; 
            font-size: 15px; 
        }

        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafbfc; }

        /* Typography & Components */
        .id-badge {
            background: #fff7ed;
            color: var(--orange);
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 14px;
        }

        .firm-name { 
            color: var(--navy); 
            font-weight: 700; 
            font-size: 17px; 
            display: block; 
            margin-bottom: 4px; 
        }

        .contact-info { color: #64748b; font-size: 13px; }

        .tax-pill {
            display: inline-block;
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            margin-top: 5px;
            border: 1px solid #e2e8f0;
        }

        .doc-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #dbeafe;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            margin: 3px;
            transition: 0.2s;
        }
        .doc-link:hover { background: #dbeafe; transform: translateY(-2px); }

        .action-btn {
            color: #ef4444;
            background: #fef2f2;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            transition: 0.3s;
            text-decoration: none;
        }
        .action-btn:hover { background: #ef4444; color: white; }

        .empty-state { text-align: center; padding: 40px; color: #94a3b8; font-style: italic; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>KKA ADMIN</h2>
          <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> Summary</a>
        <a href="assign-work.php"><i class="fas fa-tasks"></i> Assign Work</a>
        <a href="admin-review.php"><i class="fas fa-file-signature"></i> Quality Control</a>
       <a href="manage-clients.php" class="active"><i class="fas fa-users"></i> Manage Clients</a>
        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
        <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <div class="header-flex">
            <h1>Client Directory</h1>
            <div style="color: #64748b; font-size: 14px;">Total Registered: <b><?php echo $conn->query("SELECT id FROM users WHERE role='client'")->num_rows; ?></b></div>
        </div>
        
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Firm & Business Details</th>
                        <th>Tax Info</th>
                        <th>Documents</th>
                        <th style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // JOIN query to get basic user data + their business profile
                    $sql = "SELECT users.*, client_profiles.company_name, client_profiles.gst_no, client_profiles.pan_no, client_profiles.owner_name 
                            FROM users 
                            LEFT JOIN client_profiles ON users.identifier = client_profiles.client_id 
                            WHERE users.role='client'
                            ORDER BY users.id DESC";
                    
                    $res = $conn->query($sql);
                    
                    if($res->num_rows > 0):
                        while($row = $res->fetch_assoc()):
                            $client_id = $row['identifier'];
                    ?>
                        <tr>
                            <td><span class="id-badge"><?php echo $client_id; ?></span></td>
                            <td>
                                <span class="firm-name"><?php echo $row['company_name'] ?: $row['name']; ?></span>
                                <span class="contact-info"><i class="far fa-user"></i> <?php echo $row['owner_name'] ?: 'No contact name'; ?></span>
                            </td>
                            <td>
                                <div class="tax-pill"><b>GST:</b> <?php echo $row['gst_no'] ?: '---'; ?></div><br>
                                <div class="tax-pill"><b>PAN:</b> <?php echo $row['pan_no'] ?: '---'; ?></div>
                            </td>
                            <td>
                                <div style="display:flex; flex-wrap:wrap; max-width: 300px;">
                                    <?php
                                    $docs = $conn->query("SELECT * FROM client_documents WHERE client_id='$client_id' LIMIT 4");
                                    if($docs->num_rows > 0) {
                                        while($d = $docs->fetch_assoc()) {
                                            echo "<a href='../documents/{$d['file_path']}' class='doc-link' target='_blank'><i class='fas fa-file-pdf'></i> {$d['category']}</a>";
                                        }
                                    } else {
                                        echo "<span style='font-size:12px; color:#cbd5e1;'>No uploads yet</span>";
                                    }
                                    ?>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <a href="?delete=<?php echo $row['id']; ?>" class="action-btn" onclick="return confirm('Warning: This will delete the client and all their records. Proceed?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr><td colspan="5" class="empty-state">No clients found in the system.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>