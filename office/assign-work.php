<?php
session_start();
include('../db.php');

// Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Login.php");
    exit();
}

// --- COMBINED MASTER DISPATCH LOGIC ---
if (isset($_POST['master_dispatch'])) {
    $client_id = mysqli_real_escape_string($conn, $_POST['client_id']);
    $staff_email = mysqli_real_escape_string($conn, $_POST['staff_email']);
    $service_name = mysqli_real_escape_string($conn, $_POST['service_name']);
    $instruction = mysqli_real_escape_string($conn, $_POST['instruction']);
    $doc_type = mysqli_real_escape_string($conn, $_POST['doc_type']);

    // 1. Create the Task with Assigned Date (CURDATE())
    $sql_task = "INSERT INTO service_requests (client_id, service_type, description, assigned_to, status, assigned_date, created_at) 
                 VALUES ('$client_id', '$service_name', '$instruction', '$staff_email', 'Assigned', CURDATE(), NOW())";
    $conn->query($sql_task);

   // 2. IF FILES ARE UPLOADED
    if (!empty($_FILES["doc_file"]["name"][0])) {
        $target_dir = "../uploads/center/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

        $total_files = count($_FILES["doc_file"]["name"]);
        $success_count = 0;

        for ($i = 0; $i < $total_files; $i++) {
            $original_name = $_FILES["doc_file"]["name"][$i];
            $file_tmp = $_FILES["doc_file"]["tmp_name"][$i];
            
            // Unique filename: time + index + name
            $file_name = time() . "_" . $i . "_" . basename($original_name);

            if (move_uploaded_file($file_tmp, $target_dir . $file_name)) {
                $sql_doc = "INSERT INTO client_documents 
                            (client_id, file_name, assigned_to, status, doc_category, category, instruction, created_at) 
                            VALUES 
                            ('$client_id', '$file_name', '$staff_email', 'In-Progress', '$doc_type', '$service_name', '$instruction', NOW())";
                $conn->query($sql_doc);
                $success_count++;
            }
        }
        echo "<script>alert('$success_count Documents & Task dispatched successfully!'); window.location='assign-work.php';</script>";
    } else {
        echo "<script>alert('Manual Task assigned (No documents attached)'); window.location='assign-work.php';</script>";
    }
}

// --- LOGIC 2: Assign an EXISTING Client Request ---
if (isset($_POST['assign_existing'])) {
    $rid = mysqli_real_escape_string($conn, $_POST['rid']);
    $staff = mysqli_real_escape_string($conn, $_POST['staff_email']);
    // Update includes setting the assigned_date
    $conn->query("UPDATE service_requests SET assigned_to='$staff', status='Assigned', assigned_date=CURDATE() WHERE id='$rid'");
    echo "<script>alert('Client Request Assigned!'); window.location='assign-work.php';</script>";
}

// --- LOGIC 4: Delete Records ---
if (isset($_GET['delete_task'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete_task']);
    $conn->query("DELETE FROM service_requests WHERE id='$id'");
    echo "<script>window.location='assign-work.php';</script>";
}

if (isset($_GET['delete_doc'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete_doc']);
    $conn->query("DELETE FROM client_documents WHERE id='$id'");
    echo "<script>window.location='assign-work.php';</script>";
}
?>
<?php
// Fetch clients grouped by service
$map_query = $conn->query("SELECT u.identifier, cp.company_name, cp.task_asked 
                           FROM users u 
                           JOIN client_profiles cp ON u.identifier = cp.client_id 
                           WHERE u.role='client'");
$serviceMap = [];
while ($row = $map_query->fetch_assoc()) {
    $displayName = !empty($row['company_name']) ? $row['company_name'] : $row['identifier'];
    $services = explode(', ', $row['task_asked']);
    foreach ($services as $s) {
        $s = trim($s);
        if ($s != "") {
            $serviceMap[$s][] = ['id' => $row['identifier'], 'name' => $displayName];
        }
    }
}
?>
<script>const serviceMap = <?php echo json_encode($serviceMap); ?>;</script>
<!DOCTYPE html>
<html>
<head>
    <title>Assign Work | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
       .dropdown-content {
            display: none;
            background: rgba(0, 0, 0, 0.15);
            margin: 0 10px;
            border-radius: 10px;
            padding-left: 10px;
        }

        .show-menu {
            display: block !important;
        }

        .rotate-chevron {
            transform: rotate(180deg);
        }

        :root {
            --navy: #0b3c74;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
            --danger: #ef4444;
        }
        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            color: #334155;
        }
        .main { 
            margin-left: 280px; 
            padding: 0; 
            width: calc(100% - 280px); 
            min-height: 100vh;
        }
       .sidebar {
    width: 280px;
    background: var(--sidebar);
    color: white;
    height: 100vh; /* Keeps the sidebar full height */
    position: fixed;
    top: 0;
    left: 0;
    padding: 30px 20px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    border-right: 4px solid var(--orange);
    
    /* This makes the scrollbar behave correctly */
    overflow-y: auto;
    scrollbar-width: thin; /* Firefox: makes the scrollbar thin */
    scrollbar-color: var(--orange) var(--sidebar); /* Thumb and track color */
}

/* Chrome, Safari, Edge: Custom Scrollbar Line */
.sidebar::-webkit-scrollbar {
    width: 8px; /* Thickness of the side line */
}

.sidebar::-webkit-scrollbar-track {
    background: #082d56; /* Darker track */
}

.sidebar::-webkit-scrollbar-thumb {
    background-color: var(--orange); /* The "line" you can grab */
    border-radius: 10px;
    border: 2px solid #082d56;
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
        .sidebar a:hover, .active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 4px solid var(--orange);
        }
        .card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        input, select, textarea {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-sizing: border-box;
        }
        .btn-dispatch {
            background: var(--navy);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
        }
        .task-item {
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 5px solid var(--orange);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
    </style>
</head>

<body>
    <datalist id="service_list">
        <option value="GST Monthly Return">
        <option value="ITR Filing">
        <option value="TDS Return">
        <option value="Accounting/Bookkeeping">
        <option value="Company Incorporation">
    </datalist>

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
            <a href ="client-documents.php">Client Documents</a>
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

    <a href="assign-work.php" class="active"><i class="fas fa-tasks"></i> Assign Work</a>
    <div class="dropdown-container">
    <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('clientMenu', 'clientChev')">
        <i class="fas fa-users"></i> Manage Clients
        <i class="fas fa-chevron-down" id="clientChev" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
    </a>
    <div class="dropdown-content" id="clientMenu">
        <a href="manage-clients.php">Clients</a>
        <a href="client-groups.php">Client Groups</a>
        <a href="client-services.php">Services</a>
    </div>
</div>
    <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
     <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
    <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

    <div class="main" style="padding:50px;">
        <h1>Work Assignment Center</h1>

        <div class="card">
            <h3 style="margin:0 0 20px 0; color:var(--navy);"><i class="fas fa-paper-plane"></i> Master Work Dispatch</h3>
            <form method="POST" enctype="multipart/form-data">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
    <div>
        <label style="font-size:12px; font-weight:bold;">Services</label>
        <select name="service_name" id="service_selector" onchange="filterClients()" required>
            <option value="">-- Select Service --</option>
            <?php foreach (array_keys($serviceMap) as $sName): ?>
                <option value="<?php echo htmlspecialchars($sName); ?>"><?php echo htmlspecialchars($sName); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label style="font-size:12px; font-weight:bold;">Target Client</label>
        <select name="client_id" id="client_selector" required>
            <option value="">-- Select Service First --</option>
        </select>
    </div>
</div>
                    <div>
                        <label style="font-size:12px; font-weight:bold;">Assign To Staff</label>
                        <select name="staff_email" required>
                            <option value="">-- Select Employee --</option>
                            <?php
                            $staff_query = $conn->query("SELECT name, identifier FROM users WHERE role='office'");
                            while ($s = $staff_query->fetch_assoc()) echo "<option value='{$s['identifier']}'>{$s['name']}</option>";
                            ?>
                        </select>
                    </div>
                </div>

                <div style="margin-top:10px;">
                    <label style="font-size:12px; font-weight:bold;">Service Category</label>
                    <input type="text" name="service_name" list="service_list" placeholder="e.g. GST Filing..." required>
                </div>

                <div style="margin-top:10px;">
                    <label style="font-size:12px; font-weight:bold;">Instructions</label>
                    <textarea name="instruction" placeholder="Special Work Instructions..." rows="2" required></textarea>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; background: #f1f5f9; padding: 15px; border-radius: 12px; margin-top:10px;">
                    <div>
    <label style="font-size:12px; font-weight:bold;">Upload Document (Optional)</label>
    <input type="file" name="doc_file[]" multiple>
</div>
                    <div>
                        <label style="font-size:12px; font-weight:bold;">Type</label>
                        <select name="doc_type">
                            <option value="center">Center (Active)</option>
                            <option value="vault">Vault (Permanent)</option>
                        </select>
                    </div>
                </div>
                <button name="master_dispatch" class="btn-dispatch" style="margin-top:15px;">Dispatch Work</button>
            </form>
        </div>

        <h2>Incoming Client Requests</h2>
        <?php
        $res = $conn->query("SELECT t.*, cp.company_name 
                     FROM service_requests t 
                     LEFT JOIN client_profiles cp ON t.client_id = cp.client_id 
                     WHERE t.status='Pending'");
        if ($res->num_rows == 0) echo "<p style='color:#64748b;'>No pending client requests.</p>";
        while ($row = $res->fetch_assoc()): ?>
            <div class="task-item">
                <div>
                    <b><?php echo $row['service_type']; ?></b>
                    <p style="margin:5px 0; color:#64748b; font-size:13px;"><?php echo $row['description']; ?></p>
                    <small>Client: <?php echo !empty($row['company_name']) ? $row['company_name'] : $row['client_id']; ?></small>
                </div>
                <form method="POST" style="display:flex; gap:10px;">
                    <input type="hidden" name="rid" value="<?php echo $row['id']; ?>">
                    <select name="staff_email" required style="margin:0; width:150px;">
                        <?php $staff_query->data_seek(0);
                        while ($s = $staff_query->fetch_assoc()) echo "<option value='{$s['identifier']}'>{$s['name']}</option>"; ?>
                    </select>
                    <button name="assign_existing" style="background:var(--orange); color:white; border:none; padding:5px 15px; border-radius:8px; cursor:pointer;">Assign</button>
                </form>
            </div>
        <?php endwhile; ?>

        <h2 style="margin-top:40px;">Live Assignment Monitor</h2>
        
        <div class="card" style="margin-bottom: 15px; padding: 15px;">
            <div style="display: flex; gap: 15px; align-items: center;">
                <div style="flex: 1; position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 12px; color: #94a3b8;"></i>
                    <input type="text" id="assignmentSearch" onkeyup="filterAssignments()" 
                           placeholder="Search by Client ID, Employee Name, or Service..." 
                           style="padding-left: 35px; margin: 0;">
                </div>
                <button onclick="resetSearch()" style="background: #f1f5f9; border: none; padding: 10px 15px; border-radius: 10px; cursor: pointer; color: #475569;">
                    <i class="fas fa-undo"></i> Reset
                </button>
            </div>
        </div>

        <div class="card" style="overflow-x:auto;">
            <table style="width:115%; border-collapse:collapse;" id="assignmentTable">
                <thead>
                    <tr style="border-bottom:2px solid #f1f5f9; text-align:left;">
                        <th style="padding:12px;">Type</th>
                        <th>Client</th>
                        <th>Assigned To</th>
                        <th>Assigned Date</th>
                        <th>Completed Date</th>
                        <th>Status</th>
                        <th>Details</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                  $tasks = $conn->query("SELECT t.*, cp.company_name 
                       FROM service_requests t
                       LEFT JOIN client_profiles cp ON t.client_id = cp.client_id 
                       ORDER BY t.id DESC LIMIT 20");
                    while ($t = $tasks->fetch_assoc()): ?>
                        <tr class="table-row" style="border-bottom:1px solid #f8fafc;">
                            <td style="padding:12px;"><span style="background:#e0f2fe; color:#0369a1; padding:3px 8px; border-radius:5px; font-size:11px;">TASK</span></td>
                           <td class="client-cell">
    <b><?php echo !empty($t['company_name']) ? $t['company_name'] : $t['client_id']; ?></b>
    <br><small style="color:#94a3b8;"><?php echo $t['client_id']; ?></small>
</td>
                            <td class="staff-cell"><b><?php echo $t['assigned_to']; ?></b></td>
                            <td><?php echo $t['assigned_date']; ?></td>
                            <td><?php echo $t['completed_date']; ?></td>
                            <td><span style="color:var(--orange);"><?php echo $t['status']; ?></span></td>
                            <td class="service-cell"><?php echo $t['service_type']; ?></td>
                            <td><a href="?delete_task=<?php echo $t['id']; ?>" onclick="return confirm('Delete this assignment?')" style="color:red;"><i class="fas fa-trash"></i></a></td>
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
        function filterAssignments() {
            const input = document.getElementById('assignmentSearch').value.toLowerCase();
            const rows = document.querySelectorAll('.table-row');
            rows.forEach(row => {
                const clientId = row.querySelector('.client-cell').textContent.toLowerCase();
                const staffName = row.querySelector('.staff-cell').textContent.toLowerCase();
                const service = row.querySelector('.service-cell').textContent.toLowerCase();
                if (clientId.includes(input) || staffName.includes(input) || service.includes(input)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        function resetSearch() {
            document.getElementById('assignmentSearch').value = "";
            filterAssignments();
        }
        function filterClients() {
    const service = document.getElementById('service_selector').value;
    const clientDropdown = document.getElementById('client_selector');
    
    // Clear existing options
    clientDropdown.innerHTML = '<option value="">-- Select Client --</option>';
    
    if (service && serviceMap[service]) {
        serviceMap[service].forEach(client => {
            const opt = document.createElement('option');
            opt.value = client.id;
            opt.textContent = `${client.name} (${client.id})`;
            clientDropdown.appendChild(opt);
        });
    } else {
        const opt = document.createElement('option');
        opt.textContent = "No clients for this service";
        opt.disabled = true;
        clientDropdown.appendChild(opt);
    }
}
    </script>
</body>
</html>