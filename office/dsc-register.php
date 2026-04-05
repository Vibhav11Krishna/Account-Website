<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

// 1. LOGIC: SAVE / UPDATE
if (isset($_POST['save_dsc'])) {
    $client = mysqli_real_escape_string($conn, $_POST['client_name']);
    $dsc = mysqli_real_escape_string($conn, $_POST['dsc_no']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone_no']);
    $expiry = $_POST['expiry_date'];
    $color = mysqli_real_escape_string($conn, $_POST['color']);
    $box_no = mysqli_real_escape_string($conn, $_POST['box_no']);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    $id = $_POST['dsc_id'];

    if (!empty($id)) {
        $sql = "UPDATE dsc_register SET client_name='$client', dsc_no='$dsc', phone_no='$phone', expiry_date='$expiry', color='$color', box_no='$box_no', remarks='$remarks' WHERE id=$id";
    } else {
        $sql = "INSERT INTO dsc_register (client_name, dsc_no, phone_no, expiry_date, color, box_no, status, remarks) VALUES ('$client', '$dsc', '$phone', '$expiry', '$color', '$box_no', 'In', '$remarks')";
    }
    $conn->query($sql);
    header("Location: dsc-register.php");
    exit();
}

// 2. NEW LOGIC: DELETE ENTRY
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM dsc_register WHERE id = $id");
    header("Location: dsc-register.php");
    exit();
}

// 3. LOGIC: STATUS TOGGLE
if (isset($_POST['toggle_status'])) {
    $id = intval($_POST['dsc_id']);
    $current = $_POST['current_status'];
    $new_status = ($current == 'In') ? 'Out' : 'In';
    $conn->query("UPDATE dsc_register SET status = '$new_status' WHERE id = $id");
    header("Location: dsc-register.php");
    exit();
}

// 4. METRICS CALCULATION
$total_res = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN expiry_date < CURDATE() THEN 1 ELSE 0 END) as expired,
    SUM(CASE WHEN expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring,
    SUM(CASE WHEN expiry_date > DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as active
    FROM dsc_register")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>DSC Inventory | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        :root {
            --navy: #0b3c74;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
            --red: #ef4444;
            --green: #22c55e;
        }

        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Inter', sans-serif;
        }

       /* Sidebar */
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

        .main {
            margin-left: 280px;
            padding: 50px;
            width: calc(100% - 100px);
            box-sizing: border-box;
        }
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

        .header-action {
            padding: 30px 50px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* SEARCH BAR & EXPORT */
        .search-container {
            padding: 20px 50px;
            background: white;
            display: flex;
            gap: 15px;
        }

        .search-input {
            flex-grow: 1;
            padding: 12px 20px;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            outline: none;
            transition: 0.3s;
        }

        .btn-export {
            background: #166534;
            color: white;
            border: none;
            padding: 0 20px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* METRIC BOXES */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            padding: 0 50px 30px;
        }

        .metric-card {
            padding: 20px;
            border-radius: 15px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .bg-red {
            background: linear-gradient(135deg, #f87171, #ef4444);
        }

        .bg-orange {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
        }

        .bg-green {
            background: linear-gradient(135deg, #34d399, #10b981);
        }

        /* TABLE */
        .content-body {
            padding: 0 50px 50px;
        }

        .card-table {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 18px;
            background: #f1f5f9;
            color: var(--navy);
            font-size: 11px;
            text-transform: uppercase;
        }

        td {
            padding: 18px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13.5px;
        }

        /* MODAL & FORM */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(8, 45, 86, 0.6);
            backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            width: 650px;
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: bold;
            color: #64748b;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        input,
        select,
        textarea {
            width: 90%;
            padding: 12px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-family: inherit;
        }

        textarea {
            grid-column: span 2;
            height: 80px;
        }

        .btn-save {
            background: var(--navy);
            color: white;
            padding: 15px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            width: 100%;
            font-size: 16px;
            margin-top: 10px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            border: none;
            font-weight: bold;
            font-size: 10px;
            cursor: pointer;
            color: white;
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
            <a href="Master-Vault.php"></i>Services</a>
        </div>
    </div>

    <div class="dropdown-container">
        <a href="javascript:void(0)" class="dropdown-btn"class="active" onclick="toggleMenu('reportsMenu', 'repChev')">
            <i class="fas fa-file-contract"></i> Reports
            <i class="fas fa-chevron-down" id="repChev" style="margin-left:auto; font-size:12px; transition:0.3s;"></i>
        </a>
        <div class="dropdown-content" id="reportsMenu">
           <a href="dsc-register.php" class="active"></i> DSC Register</a>
           <a href="service-report.php"></i> Service Report</a>
           <a href="attendance.php"></i> Attendance</a>
        </div>
    </div>

    <a href="assign-work.php"><i class="fas fa-tasks"></i> Assign Work</a>
    <a href="manage-clients.php"><i class="fas fa-users"></i> Manage Clients</a>
    <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
     <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
    <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
    <div class="main">
        <header class="header-action">
            <h2 style="margin:0; color:var(--navy);">DSC Inventory Register</h2>
            <button class="btn-save" onclick="openModal()" style="width:auto; margin:0; padding:12px 25px;">
                <i class="fas fa-plus"></i> Add New Entry
            </button>
        </header>

        <div class="search-container">
            <input type="text" id="tableSearch" class="search-input" placeholder="Search Client, DSC No, or Box Number...">
            <button onclick="exportToExcel()" class="btn-export">
                <i class="fas fa-file-excel"></i> Export Excel
            </button>
        </div>

        <div class="metrics-grid">
            <div class="metric-card bg-red"><span>EXPIRED</span>
                <h2><?php echo $total_res['expired'] ?? 0; ?></h2>
            </div>
            <div class="metric-card bg-orange"><span>EXPIRING (30D)</span>
                <h2><?php echo $total_res['expiring'] ?? 0; ?></h2>
            </div>
            <div class="metric-card bg-green"><span>ACTIVE</span>
                <h2><?php echo $total_res['active'] ?? 0; ?></h2>
            </div>
        </div>

        <div class="content-body">
            <div class="card-table">
                <table id="dscTable">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>Color</th>
                            <th>DSC Number</th>
                            <th>Box No</th>
                            <th>Expiry</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $res = $conn->query("SELECT * FROM dsc_register ORDER BY id DESC");
                        while ($row = $res->fetch_assoc()):
                            $expiry_ts = strtotime($row['expiry_date']);
                            $diff = ($expiry_ts - time()) / (60 * 60 * 24);
                            $date_color = ($diff < 0) ? 'var(--red)' : (($diff <= 30) ? 'var(--orange)' : 'inherit');
                        ?>
                            <tr>
                                <td><strong><?php echo $row['client_name']; ?></strong></td>
                                <td><span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:<?php echo $row['color']; ?>; margin-right:5px;"></span><?php echo ucfirst($row['color']); ?></td>
                                <td><code><?php echo $row['dsc_no']; ?></code></td>
                                <td><span style="background:#f1f5f9; padding:5px 10px; border-radius:6px;"><?php echo $row['box_no']; ?></span></td>
                                <td style="color:<?php echo $date_color; ?>; font-weight:bold;"><?php echo date('d-m-Y', $expiry_ts); ?></td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="dsc_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $row['status']; ?>">
                                        <button type="submit" name="toggle_status" class="status-badge" style="background:<?php echo ($row['status'] == 'In') ? 'var(--green)' : 'var(--red)'; ?>;">
                                            <?php echo ($row['status'] == 'In') ? 'IN OFFICE' : 'WITH CLIENT'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <a href="javascript:void(0)" onclick='editDSC(<?php echo json_encode($row); ?>)' style="color:var(--navy);"><i class="fas fa-edit"></i></a>
                                    <a href="?delete=<?php echo $row['id']; ?>" style="color:var(--red); margin-left:15px;" onclick="return confirm('Permanently delete this record?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="dscModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle" style="margin:0; color:var(--navy);">New DSC Registration</h3>
                <span onclick="closeModal()" style="cursor:pointer; font-size:24px; color:#94a3b8;">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="dsc_id" id="dsc_id">
                <div class="form-grid">
                    <div class="form-group"><label>Client Full Name</label><input type="text" name="client_name" id="client_name" required></div>
                    <div class="form-group"><label>DSC Serial Number</label><input type="text" name="dsc_no" id="dsc_no" required></div>
                    <div class="form-group">
                        <label>Folder/Tag Color</label>
                        <select name="color" id="color">
                            <option value="red">Red</option>
                            <option value="blue">Blue</option>
                            <option value="green">Green</option>
                            <option value="yellow">Yellow</option>
                            <option value="black">Black</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Box Number</label><input type="text" name="box_no" id="box_no" placeholder="e.g. B-101"></div>
                    <div class="form-group"><label>Expiry Date</label><input type="date" name="expiry_date" id="expiry_date" required></div>
                    <div class="form-group"><label>Phone Number</label><input type="text" name="phone_no" id="phone_no"></div>
                    <textarea name="remarks" id="remarks" placeholder="Internal notes or special instructions..."></textarea>
                </div>
                <button type="submit" name="save_dsc" class="btn-save">Confirm & Save Information</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('dscModal');

        // 1. EXPORT TO EXCEL LOGIC
        function exportToExcel() {
            const table = document.getElementById("dscTable");
            const wb = XLSX.utils.table_to_book(table, {
                sheet: "DSC Inventory"
            });
            XLSX.writeFile(wb, "DSC_Inventory_Report.xlsx");
        }

        // 2. LIVE SEARCH LOGIC
        document.getElementById('tableSearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll('#dscTable tbody tr').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
            });
        });

        function openModal() {
            document.getElementById('modalTitle').innerText = "New DSC Registration";
            document.getElementById('dsc_id').value = "";
            document.querySelector('form').reset();
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        function editDSC(data) {
            document.getElementById('modalTitle').innerText = "Update DSC Entry";
            document.getElementById('dsc_id').value = data.id;
            document.getElementById('client_name').value = data.client_name;
            document.getElementById('dsc_no').value = data.dsc_no;
            document.getElementById('expiry_date').value = data.expiry_date;
            document.getElementById('color').value = data.color;
            document.getElementById('box_no').value = data.box_no;
            document.getElementById('phone_no').value = data.phone_no;
            document.getElementById('remarks').value = data.remarks;
            modal.style.display = 'flex';
        }

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
    </script>
</body>

</html>