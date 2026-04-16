<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Login.php");
    exit();
}

// --- UPDATE LOGIC ---
if (isset($_POST['update_client'])) {
    $identifier = mysqli_real_escape_string($conn, $_POST['identifier']);
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $owner_name = mysqli_real_escape_string($conn, $_POST['owner_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['business_email']); 
    $gst = mysqli_real_escape_string($conn, $_POST['gst_no']);
    $pan = mysqli_real_escape_string($conn, $_POST['pan_no']);
    
    // ADD THESE THREE LINES:
    $tan = mysqli_real_escape_string($conn, $_POST['tan_no']);
    $cin = mysqli_real_escape_string($conn, $_POST['cin_no']);
    $tin = mysqli_real_escape_string($conn, $_POST['tin_no']);

    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $nature = mysqli_real_escape_string($conn, $_POST['business_nature']);
    $aadhaar = mysqli_real_escape_string($conn, $_POST['aadhaar_no']);
  // --- SMART TASK HANDLER ---
if (isset($_POST['task_asked']) && !empty($_POST['task_asked'])) {
    
    if (is_array($_POST['task_asked'])) {
        // If it comes from old checkboxes (array)
        $task_list = implode(', ', $_POST['task_asked']);
    } else {
        // If it comes from our new "Add Service" tags (string)
        $task_list = $_POST['task_asked'];
    }
    
    $task = mysqli_real_escape_string($conn, $task_list);
} else {
    $task = "";
}

    // Photo logic remains the same...
    $photo_query = "";
    if (!empty($_FILES['profile_pic']['name'])) {
        $target_dir = "../uploads/client_pics/";
        $file_ext = pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION);
        $new_filename = "client_" . $identifier . "_" . time() . "." . $file_ext;
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_dir . $new_filename)) {
            $photo_query = ", profile_pic = '$new_filename'";
        }
    }

    // UPDATE THE SQL TO INCLUDE THE NEW COLUMNS:
    $update_sql = "UPDATE client_profiles SET 
                   company_name = '$company_name', 
                   owner_name = '$owner_name', 
                   phone = '$phone', 
                   business_email = '$email', 
                   gst_no = '$gst', 
                   pan_no = '$pan', 
                   tan_no = '$tan', 
                   cin_no = '$cin', 
                   tin_no = '$tin', 
                   address = '$address',
                   business_nature = '$nature', 
                   aadhaar_no = '$aadhaar', 
                   task_asked = '$task' 
                   $photo_query 
                   WHERE client_id = '$identifier'";

    if ($conn->query($update_sql)) {
        $conn->query("UPDATE users SET name = '$owner_name' WHERE identifier = '$identifier'");
        header("Location: manage-clients.php?msg=updated");
        exit();
    } else {
        die("Update Error: " . $conn->error);
    }
}
// --- DELETE LOGIC ---
if (isset($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Remove from both tables to maintain integrity
    $del_profile = $conn->query("DELETE FROM client_profiles WHERE client_id='$id'");
    $del_user = $conn->query("DELETE FROM users WHERE identifier='$id' AND role='client'");
    
    if ($del_profile && $del_user) {
        header("Location: manage-clients.php?msg=deleted");
    } else {
        header("Location: manage-clients.php?msg=error");
    }
    exit();
}

// --- QUICK CREATE LOGIC (Format: 501801, 501802...) ---
if (isset($_POST['quick_create'])) {
    $n = mysqli_real_escape_string($conn, $_POST['name']);
    $p = mysqli_real_escape_string($conn, $_POST['pass']);
    $role = 'client';

    // Get current client count
    $count_res = $conn->query("SELECT id FROM users WHERE role='client'");
    $count = $count_res->num_rows + 1;
    
    // Logic: Base 5018 + Padded sequence (01, 02...)
    $base_id = "5018";
    $sequence = str_pad($count, 2, "0", STR_PAD_LEFT);
    $final_id = $base_id . $sequence; // Results in 501801, 501802, etc.

    $sql = "INSERT INTO users (name, identifier, password, role) VALUES ('$n', '$final_id', '$p', '$role')";
    
    if ($conn->query($sql)) {
        $conn->query("INSERT INTO client_profiles (client_id, owner_name) VALUES ('$final_id', '$n')");
        echo "<script>alert('Client Created! ID: $final_id'); window.location='manage-clients.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Clients | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --navy: #0b3c74;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
            --danger: #ef4444;
            --border: #e2e8f0;
        }

        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            color: #334155;
        }

        /* Sidebar */
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
    
    /* ADD THIS LINE */
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
        .active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 4px solid var(--orange);
        }

        /* Main Area */
        .main {
            margin-left: 280px;
            padding: 50px;
            width: calc(100% - 280px);
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .dropdown-content {
            display: none;
            background: rgba(0, 0, 0, 0.15);
            margin: 0 10px;
            border-radius: 10px;
            padding-left: 10px;
        }

        .show-menu { display: block !important; }
        .rotate-chevron { transform: rotate(180deg); }

        /* Main Area */
        .main {
            margin-left: 280px;
            padding: 50px;
            width: calc(100% - 280px);
            box-sizing: border-box;
        }

        h1 { color: var(--navy); margin-top: 0; }

        .search-container {
            margin-bottom: 25px;
            display: flex;
            gap: 12px;
            align-items: center;
            background: white;
            padding: 15px 20px;
            border-radius: 15px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }

        .search-container input { width: 100%; border: none; outline: none; font-size: 15px; }

        .table-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.02);
            border: 1px solid var(--border);
        }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        
        .id-badge { background: #fff7ed; color: var(--orange); font-weight: 700; padding: 6px 12px; border-radius: 8px; font-family: monospace; }
        
        .action-btn { width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; text-decoration: none; transition: 0.2s; }
        .btn-edit { color: #3b82f6; background: #eff6ff; }
        .btn-delete { color: #ef4444; background: #fef2f2; border: none; cursor: pointer; }

        .add-client-form { display: none; background: white; padding: 25px; border-radius: 20px; border: 1.5px solid var(--orange); margin-bottom: 30px; }

        .btn-primary {
            background: var(--navy);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .msg-alert {
            padding: 15px;
            background: #dcfce7;
            color: #166534;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        .msg-danger {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
 <div class="sidebar">
    <h2>Karunesh Kumar & Associates Admin</h2>
    <a href="admin-dashboard.php" ><i class="fas fa-chart-pie"></i> Dashboard</a>

    <div class="dropdown-container">
        <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('billingMenu', 'billChev')">
            <i class="fas fa-file-invoice-dollar"></i> Billing
            <i class="fas fa-chevron-down" id="billChev" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
        </a>
        <div class="dropdown-content" id="billingMenu">
            <a href="quotations.php">Quotations</a>
            <a href="invoices.php">Invoices</a>
            <a href="receipts.php">Receipts</a>
            <a href="outstanding.php">Outstanding</a>
        </div>
    </div>

    <div class="dropdown-container">
        <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('vaultMenu', 'vaultChev')">
            <i class="fas fa-folder-open"></i> Documents
            <i class="fas fa-chevron-down" id="vaultChev" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
        </a>
        <div class="dropdown-content" id="vaultMenu">
            <a href="admin-review.php"></i> Quality Control</a>
            <a href="Master-Vault.php"></i> Services</a>
        </div>
    </div>

    <div class="dropdown-container">
        <a href="javascript:void(0)" class="dropdown-btn"class="active" onclick="toggleMenu('reportsMenu', 'repChev')">
            <i class="fas fa-file-contract"></i> Reports
            <i class="fas fa-chevron-down" id="repChev" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
        </a>
        <div class="dropdown-content" id="reportsMenu">
           <a href="dsc-register.php"></i> DSC Register</a>
           <a href="service-report.php"></i> Service Report</a>
           <a href="revenue-analytics.php"></i> Revenue Analytics</a>
           <a href="Client-Revenue.php"></i>Client Revenue</a>
           <a href="attendance.php"></i> Attendance</a>
        </div>
    </div>

    <a href="assign-work.php"><i class="fas fa-tasks"></i> Assign Work</a>
    <div class="dropdown-container">
    <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('clientMenu', 'clientChev')">
        <i class="fas fa-users"></i> Manage Clients
        <i class="fas fa-chevron-down" id="clientChev" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
    </a>
    <div class="dropdown-content" id="clientMenu">
        <a href="manage-clients.php" class="active">Clients</a>
        <a href="client-groups.php">Client Groups</a>
        <a href="client-services.php">Services</a>
    </div>
</div>
    <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
    <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
    <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
    

    <div class="main">
        <?php if(isset($_GET['msg'])): ?>
            <?php if($_GET['msg'] == 'updated'): ?>
                <div class="msg-alert"><i class="fas fa-check-circle"></i> Profile updated successfully!</div>
            <?php elseif($_GET['msg'] == 'deleted'): ?>
                <div class="msg-alert msg-danger"><i class="fas fa-trash-alt"></i> Client and all profile records deleted.</div>
            <?php endif; ?>
        <?php endif; ?>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
            <h1>Client Directory</h1>
            <button onclick="toggleAddForm()" class="btn-primary">
                <i class="fas fa-user-plus"></i> Add New Client
            </button>
        </div>

        <div id="addClientForm" class="add-client-form">
            <h3 style="margin-top:0;">Quick Onboard</h3>
            <form method="POST" style="display: flex; gap: 15px;">
                <input type="text" name="name" placeholder="Client Name" required style="flex:2; padding:12px; border:1px solid #ddd; border-radius:10px;">
                <input type="text" name="pass" placeholder="Password" required style="flex:1; padding:12px; border:1px solid #ddd; border-radius:10px;">
                <button name="quick_create" class="btn-primary" style="background:var(--orange)">Save Client</button>
            </form>
        </div>

        <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" id="clientSearch" onkeyup="filterTable()" placeholder="Search clients...">
        </div>

        <div class="table-card">
            <table id="clientTable">
                <thead>
                    <tr>
                        <th>Client ID</th>
                        <th>Firm Details</th>
                        <th>Contact</th>
                        <th>Tax Info</th>
                        <th style="text-align:center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                  <?php
// Change the query to select everything from client_profiles
$sql = "SELECT u.identifier, cp.* FROM users u 
        LEFT JOIN client_profiles cp ON u.identifier = cp.client_id 
        WHERE u.role='client' ORDER BY u.id DESC";

$res = $conn->query($sql);
while ($row = $res->fetch_assoc()):
?>
                    <tr>
                        <td><span class="id-badge"><?php echo $row['identifier']; ?></span></td>
                        <td>
                            <b><?php echo htmlspecialchars($row['company_name'] ?: 'Not Set'); ?></b><br>
                            <small><?php echo htmlspecialchars($row['owner_name']); ?></small>
                        </td>
                        <td><?php echo $row['phone'] ?: '-'; ?><br><small><?php echo $row['business_email'] ?: '-'; ?></small></td>
                        <td><small>GST: <?php echo $row['gst_no'] ?: '-'; ?><br>PAN: <?php echo $row['pan_no'] ?: '-'; ?></small></td>
                       <td style="text-align: center;">
    <?php 
        // We convert the whole row to JSON so the "Eye" can read it instantly
        $clientJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); 
    ?>
    <a href="javascript:void(0)" class="action-btn btn-view" onclick='openViewModal(<?php echo $clientJson; ?>)'>
        <i class="fas fa-eye"></i>
    </a>

    <a href="client-profile.php?id=<?php echo $row['identifier']; ?>" class="action-btn btn-edit">
        <i class="fas fa-pencil-alt"></i>
    </a>

    <a href="?delete=<?php echo $row['identifier']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this client?')">
        <i class="fas fa-trash-alt"></i>
    </a>
</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
       function toggleMenu(menuId, chevronId) {
    const menu = document.getElementById(menuId);
    const chevron = document.getElementById(chevronId);

    // Toggle the specific menu clicked
    menu.classList.toggle('show-menu');

    // Rotate the specific arrow clicked
    chevron.classList.toggle('rotate-chevron');

    // Optional: Close other menus when opening a new one
    const allMenus = document.querySelectorAll('.dropdown-content');
    const allChevrons = document.querySelectorAll('.fa-chevron-down');

    allMenus.forEach((m) => {
        if (m.id !== menuId) m.classList.remove('show-menu');
    });
    
    allChevrons.forEach((c) => {
        if (c.id !== chevronId) c.classList.remove('rotate-chevron');
    });
}
        function toggleAddForm() {
            const form = document.getElementById('addClientForm');
            form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
        }
        function filterTable() {
            let filter = document.getElementById("clientSearch").value.toUpperCase();
            let tr = document.getElementById("clientTable").getElementsByTagName("tr");
            for (let i = 1; i < tr.length; i++) {
                tr[i].style.display = tr[i].textContent.toUpperCase().indexOf(filter) > -1 ? "" : "none";
            }
        }
        function openViewModal(data) {
    // Fill the image
   // Change this line:
document.getElementById('v_photo').src = data.profile_pic ? '../uploads/client_pics/' + data.profile_pic : '../uploads/client_pics/default-company.png';
    
    // Fill text fields
    document.getElementById('v_company_title').innerText = data.company_name || 'Individual Client';
    document.getElementById('v_id_badge').innerText = 'ID: ' + data.identifier;
    document.getElementById('v_owner').innerText = data.owner_name || 'N/A';
    document.getElementById('v_nature').innerText = data.business_nature || 'Not Specified';
    document.getElementById('v_aadhaar').innerText = data.aadhaar_no || 'Not Provided';
    document.getElementById('v_phone').innerText = data.phone || 'N/A';
    document.getElementById('v_gst').innerText = data.gst_no || 'N/A';
    document.getElementById('v_pan').innerText = data.pan_no || 'N/A';
    document.getElementById('v_task').innerText = data.task_asked || 'No pending tasks recorded.';
    document.getElementById('v_address').innerText = data.address || 'No address provided.';
    document.getElementById('v_tan').innerText = data.tan_no || 'N/A';
    document.getElementById('v_cin').innerText = data.cin_no || 'N/A';
    document.getElementById('v_tin').innerText = data.tin_no || 'N/A';

    // Show modal
    document.getElementById('viewModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('viewModal').style.display = 'none';
}

// Close modal if user clicks outside the white box
window.onclick = function(event) {
    let modal = document.getElementById('viewModal');
    if (event.target == modal) {
        closeModal();
    }
}
    </script>
    <div id="viewModal" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.6); backdrop-filter:blur(5px);">
    <div class="modal-content" style="background:white; margin:2% auto; padding:30px; width:600px; border-radius:20px; position:relative; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
        <span onclick="closeModal()" style="position:absolute; right:25px; top:20px; font-size:28px; cursor:pointer; color:#94a3b8;">&times;</span>
        
        <div style="text-align:center; margin-bottom:20px;">
            <img id="v_photo" src="" style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid #ff8c00; margin-bottom:10px;">
            <h2 id="v_company_title" style="margin:0; color:#0b3c74;"></h2>
            <span id="v_id_badge" class="id-badge"></span>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; border-top:1px solid #f1f5f9; padding-top:20px;">
            <div>
                <label style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase;">Owner Name</label>
                <p id="v_owner" style="margin:5px 0 15px 0; font-weight:600;"></p>
            </div>
            <div>
                <label style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase;">Phone</label>
                <p id="v_phone" style="margin:5px 0 15px 0; font-weight:600;"></p>
            </div>
            <div>
                <label style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase;">Nature of Business</label>
                <p id="v_nature" style="margin:5px 0 15px 0; font-weight:600;"></p>
            </div>
             <div>
                <label style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase;">Aadhaar No</label>
                <p id="v_aadhaar" style="margin:5px 0 15px 0; font-weight:600;"></p>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:10px; background: #f1f5f9; padding: 15px; border-radius: 12px; margin-top: 5px;">
            <div>
                <label style="font-size:9px; font-weight:800; color:#64748b; text-transform:uppercase;">GST No</label>
                <p id="v_gst" style="margin:2px 0 0 0; font-size:13px; font-weight:700; color:var(--navy);"></p>
            </div>
            <div>
                <label style="font-size:9px; font-weight:800; color:#64748b; text-transform:uppercase;">PAN No</label>
                <p id="v_pan" style="margin:2px 0 0 0; font-size:13px; font-weight:700; color:var(--navy);"></p>
            </div>
            <div>
                <label style="font-size:9px; font-weight:800; color:#64748b; text-transform:uppercase;">TAN No</label>
                <p id="v_tan" style="margin:2px 0 0 0; font-size:13px; font-weight:700; color:var(--navy);"></p>
            </div>
            <div style="margin-top:10px;">
                <label style="font-size:9px; font-weight:800; color:#64748b; text-transform:uppercase;">CIN No</label>
                <p id="v_cin" style="margin:2px 0 0 0; font-size:13px; font-weight:700; color:var(--navy);"></p>
            </div>
            <div style="margin-top:10px;">
                <label style="font-size:9px; font-weight:800; color:#64748b; text-transform:uppercase;">TIN / VAT</label>
                <p id="v_tin" style="margin:2px 0 0 0; font-size:13px; font-weight:700; color:var(--navy);"></p>
            </div>
        </div>

        <div style="margin-top:15px; padding:15px; background:#fff7ed; border-radius:12px; border:1px solid #ffedd5;">
            <label style="font-size:10px; font-weight:800; color:var(--orange); text-transform:uppercase;">Task / Service Requested</label>
            <p id="v_task" style="margin:5px 0 0 0; font-size:14px; color:#1e293b; line-height:1.5; font-weight:500;"></p>
        </div>

        <div style="margin-top:15px;">
            <label style="font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase;">Office Address</label>
            <p id="v_address" style="margin:5px 0 0 0; font-size:13px; color:#475569;"></p>
        </div>
    </div>
</div>
</body>
</html>