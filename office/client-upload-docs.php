<?php
session_start();
include('../db.php');

// Optional: Add your admin authentication check here
// if (!isset($_SESSION['admin'])) { header("Location: ../admin-login.php"); exit(); }

// Check if admin is searching for a specific client
$search_client = isset($_GET['client_id']) ? trim($_GET['client_id']) : '';

if ($search_client != '') {
    // Secure search using prepared statements targeted at upload_docs
    $stmt = $conn->prepare("SELECT * FROM upload_docs WHERE client_id = ? ORDER BY id DESC");
    $stmt->bind_param("s", $search_client);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Fetch all documents across the enterprise
    $result = $conn->query("SELECT * FROM upload_docs ORDER BY id DESC");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Client Directory | KKA Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --navy: #0b3c74;
            --orange: #ff8c00;
            --sidebar: #082d56;
            --bg: #f8fafc;
            --border: #e2e8f0;
        }

        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            color: #334155;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar Styles */
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

        .sidebar a:hover,
        .active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 4px solid var(--orange);
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

        /* Main Content */
        .content-area {
            margin-left: 280px;
            flex: 1;
            height: 100vh;
            overflow-y: auto;
            padding: 40px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            text-align: left;
            padding: 15px;
            background: #f1f5f9;
            color: var(--navy);
            font-size: 13px;
            text-transform: uppercase;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }

        .badge {
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12px;
            background: #e0f2fe;
            color: #0369a1;
            font-weight: bold;
        }

        .client-id {
            background: #fef08a;
            color: #854d0e;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-weight: bold;
        }

        .btn-search {
            background: var(--navy);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-view {
            background: #10b981;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }
    </style>
</head>

<body>

    <div class="sidebar">
    <h2>Karunesh Kumar & Associates Admin</h2>
    <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>

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
        <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('reportsMenu', 'repChev')">
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
        <a href="manage-clients.php">Clients</a>
        <a href="client-groups.php">Client Groups</a>
        <a href="client-services.php">Services</a>
        <a href="client-upload-docs.php" class="active">Clients Uploads</a>
    </div>
</div>
    <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
    <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
    <a href="../logout.php" style="margin-top:auto; color:#fda4af;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

    <div class="content-area">
        <div class="header-section" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 style="color: var(--navy); margin: 0;"><i class="fas fa-folder-open"></i> Client Document Directory</h1>
            <form method="GET" class="search-bar" style="display: flex; gap: 10px;">
                <input type="text" name="client_id" placeholder="Enter Client ID" value="<?php echo htmlspecialchars($search_client); ?>" style="padding: 10px 15px; border: 1.5px solid #e2e8f0; border-radius: 8px; outline: none;">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i> Search</button>
                <?php if ($search_client != ''): ?>
                    <a href="../office/client-upload-docs.php" class="btn-search" style="background:#ef4444; text-decoration:none;">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Client ID</th>
                        <th>Original File Name</th>
                        <th>Category</th>
                        <th>Upload Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $file_path = "../documents/" . htmlspecialchars($row['file_path']);
                            $upload_date = isset($row['uploaded_at']) ? date('d M Y, h:i A', strtotime($row['uploaded_at'])) : 'N/A';
                            echo "<tr>
                                    <td><span class='client-id'><i class='fas fa-user'></i> {$row['client_id']}</span></td>
                                    <td><strong>{$row['file_name']}</strong></td>
                                    <td><span class='badge'>{$row['category']}</span></td>
                                    <td style='color:#64748b;'>{$upload_date}</td>
                                    <td>
                                        <a href='{$file_path}' target='_blank' class='btn-view'><i class='fas fa-eye'></i> View File</a>
                                        <a href='delete-doc.php?id={$row['id']}' 
       onclick='return confirm(\"Are you sure you want to delete this file?\");' 
       style='background:#f43f5e; color:white; padding:8px 12px; border-radius:6px; text-decoration:none; margin-left:5px; font-size:12px;'>
       <i class='fas fa-trash'></i>
    </a>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding:30px; color:#94a3b8;'>No documents found.</td></tr>";
                    }
                    if (isset($stmt)) {
                        $stmt->close();
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleMenu(id, chev) {
            document.getElementById(id).classList.toggle('show-menu');
            document.getElementById(chev).classList.toggle('rotate-chevron');
        }
    </script>
</body>

</html>