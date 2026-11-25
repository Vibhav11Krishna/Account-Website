<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);
$res = $conn->query("SELECT * FROM client_profiles WHERE client_id = '$id'");
$client = $res->fetch_assoc();

if (!$client) {
    die("Client profile not found in database.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | <?php echo htmlspecialchars($client['company_name'] ?: $id); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --navy-dark: #051b34;
            --navy: #0b3c74;
            --navy-light: #155297;
            --orange: #ff8c00;
            --orange-glow: rgba(255, 140, 0, 0.15);
            --sidebar-bg: #062241;
            --sidebar-hover: rgba(255, 255, 255, 0.06);
            --bg: #f3f6fc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --danger: #ef4444;
            --danger-bg: #fef2f2;
            --success: #10b981;
            --border: #e2e8f0;
            --shadow-sm: 0 2px 8px rgba(11, 60, 116, 0.04);
            --shadow-md: 0 10px 25px -5px rgba(11, 60, 116, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            display: flex;
            margin: 0;
            background: var(--bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
        }

        /* ================= SIDEBAR ================= */
        .sidebar {
            width: 280px;
            background: var(--sidebar-bg);
            color: white;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            border-right: 3px solid var(--orange);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--orange) var(--sidebar-bg);
            z-index: 100;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: var(--orange);
            border-radius: 10px;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 8px 20px 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 20px;
        }

        .brand-logo {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--orange), #d97706);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(255, 140, 0, 0.3);
        }

        .brand-header h2 {
            font-size: 15px;
            color: #ffffff;
            margin: 0;
            font-weight: 700;
            line-height: 1.3;
        }

        .sidebar-profile {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .profile-avatar-wrapper {
            position: relative;
            flex-shrink: 0;
        }

        .sidebar-avatar {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--navy-light), var(--navy));
            border: 2px solid var(--orange);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            font-weight: 700;
        }

        .online-status {
            width: 10px;
            height: 10px;
            background: var(--success);
            border: 2px solid var(--sidebar-bg);
            border-radius: 50%;
            position: absolute;
            bottom: -2px;
            right: -2px;
        }

        .profile-info strong {
            display: block;
            font-size: 13px;
            color: #ffffff;
            font-weight: 600;
        }

        .profile-info span {
            font-size: 11px;
            color: var(--orange);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-nav-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.4);
            margin: 10px 0 8px 10px;
            font-weight: 700;
        }

        .sidebar a {
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            margin-bottom: 4px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: var(--sidebar-hover);
            color: white;
            border-left: 3px solid var(--orange);
            padding-left: 17px;
        }

        .sidebar a.active {
            background: rgba(255, 140, 0, 0.12);
            color: #ffffff;
            font-weight: 600;
        }

        .dropdown-content {
            display: none;
            background: rgba(0, 0, 0, 0.15);
            margin: 2px 0 6px 10px;
            border-radius: 10px;
            padding: 4px 0;
            border-left: 2px solid rgba(255, 140, 0, 0.3);
        }

        .dropdown-content a {
            padding: 9px 14px;
            font-size: 12.5px;
            margin-bottom: 0;
            border-radius: 0;
        }

        .dropdown-content a:hover,
        .dropdown-content a.active {
            border-left: none;
            background: rgba(255, 255, 255, 0.05);
            color: var(--orange);
            padding-left: 14px;
        }

        .show-menu {
            display: block !important;
        }

        .rotate-chevron {
            transform: rotate(180deg);
        }

        /* ================= MAIN WORKSPACE ================= */
        .main {
            margin-left: 280px;
            width: calc(100% - 280px);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .admin-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(12px);
            padding: 14px 40px;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 90;
            box-shadow: var(--shadow-sm);
            gap: 16px;
        }

        .brand-section h1 {
            margin: 0;
            font-size: 20px;
            color: var(--text-main);
            font-weight: 800;
        }

        .welcome-text {
            font-size: 12.5px;
            color: var(--text-muted);
            margin-top: 1px;
            font-weight: 500;
        }

        .header-right-group {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
        }

        .header-clock {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            padding: 7px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .clock-icon i {
            color: var(--orange);
            font-size: 15px;
        }

        .clock-display {
            display: flex;
            flex-direction: column;
        }

        .clock-display span {
            font-size: 9px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
        }

        .clock-display strong {
            font-size: 12px;
            color: var(--text-main);
            font-weight: 700;
        }

        .header-user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            padding: 5px 6px 5px 12px;
            border-radius: 30px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .header-user-name {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--navy);
        }

        .header-logout-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--danger-bg);
            color: var(--danger);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 12.5px;
            transition: all 0.2s;
        }

        .header-logout-icon:hover {
            background: var(--danger);
            color: white;
            transform: scale(1.05);
        }

        .content-body {
            padding: 32px 40px;
            flex: 1;
            width: 100%;
        }

        .container {
            width: 100%;
            max-width: 100%;
            background: var(--card-bg);
            padding: 36px;
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 18px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .id-pill-badge {
            background: var(--orange-glow);
            color: #c2410c;
            padding: 9px 18px;
            border-radius: 10px;
            font-weight: 800;
            font-family: monospace;
            font-size: 13.5px;
            border: 1px solid #ffedd5;
        }

        .logo-box {
            width: 140px;
            height: 100px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid var(--orange);
            background: #fff;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input,
        textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 13.5px;
            font-family: inherit;
            color: var(--text-main);
            background: #f8fafc;
            transition: all 0.2s;
        }

        input:focus,
        textarea:focus {
            border-color: var(--navy);
            background: #ffffff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(11, 60, 116, 0.1);
        }

        .btn-save {
            background: var(--navy);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 12px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            width: 100%;
            font-size: 14.5px;
            transition: all 0.2s;
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-save:hover {
            background: var(--orange);
            transform: translateY(-1px);
        }

        .services-wrapper {
            background: #f8fafc;
            border: 1px solid var(--border);
            padding: 20px;
            border-radius: 14px;
        }

        .add-service-row {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
            align-items: center;
        }

        .add-service-row input {
            flex: 1;
            margin-bottom: 0;
            background: #ffffff;
        }

        .btn-add-service {
            background: var(--orange);
            color: white;
            border: none;
            padding: 11px 18px;
            border-radius: 10px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            font-size: 13px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .btn-add-service:hover {
            background: var(--navy);
        }

        .added-services-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .service-tag {
            background: #ffffff;
            color: var(--navy);
            padding: 8px 14px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .service-tag i {
            color: var(--danger);
            cursor: pointer;
            transition: 0.2s;
        }

        .service-tag i:hover {
            transform: scale(1.15);
        }

        @media (max-width: 1024px) {
            .admin-top-bar {
                padding: 14px 20px;
            }
            .content-body {
                padding: 24px 20px;
            }
            .container {
                padding: 24px 20px;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="brand-header">
            <div class="brand-logo"><i class="fas fa-shield-alt"></i></div>
            <h2>Karunesh Kumar &amp; Associates</h2>
        </div>

        <div class="sidebar-profile">
            <div class="profile-avatar-wrapper">
                <div class="sidebar-avatar"><i class="fas fa-user-shield"></i></div>
                <div class="online-status"></div>
            </div>
            <div class="profile-info">
                <strong>Admin Executive</strong>
                <span>System Administrator</span>
            </div>
        </div>

        <div class="sidebar-nav-title">Main Menu</div>
        <a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('billingMenu', 'billChev')">
                <i class="fas fa-file-invoice-dollar"></i> Billing
                <i class="fas fa-chevron-down" id="billChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
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
                <i class="fas fa-chevron-down" id="vaultChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="vaultMenu">
                <a href="admin-review.php">Quality Control</a>
                <a href="Master-Vault.php">Services</a>
                <a href="client-documents.php">Client Documents</a>
            </div>
        </div>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('reportsMenu', 'repChev')">
                <i class="fas fa-file-contract"></i> Reports
                <i class="fas fa-chevron-down" id="repChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="reportsMenu">
                <a href="dsc-register.php">DSC Register</a>
                <a href="service-report.php">Service Report</a>
                <a href="revenue-analytics.php">Revenue Analytics</a>
                <a href="Client-Revenue.php">Client Revenue</a>
                <a href="attendance.php">Attendance</a>
            </div>
        </div>

        <div class="sidebar-nav-title" style="margin-top:15px;">Management</div>
        <a href="assign-work.php"><i class="fas fa-tasks"></i> Assign Work</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn active" onclick="toggleMenu('clientMenu', 'clientChev')">
                <i class="fas fa-users"></i> Manage Clients
                <i class="fas fa-chevron-down rotate-chevron" id="clientChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content show-menu" id="clientMenu">
                <a href="manage-clients.php" class="active">Clients</a>
                <a href="client-groups.php">Client Groups</a>
                <a href="client-services.php">Services</a>
                <a href="client-upload-docs.php">Clients Uploads</a>
            </div>
        </div>

        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="all-messages.php"><i class="fas fa-inbox"></i> Web Inbox</a>
        <a href="../logout.php" style="margin-top:auto; color:#fca5a5;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <!-- Main Workspace -->
    <div class="main">
        <header class="admin-top-bar">
            <div class="brand-section">
                <h1>Edit Client Profile</h1>
                <div class="welcome-text">Update firm details, tax registrations, custom fields, and requested services</div>
            </div>

            <div class="header-right-group">
                <div class="header-clock">
                    <div class="clock-icon"><i class="fas fa-clock"></i></div>
                    <div class="clock-display">
                        <span id="liveDate">Loading Date...</span>
                        <strong id="liveTime">00:00:00</strong>
                    </div>
                </div>

                <div class="header-user-pill">
                    <span class="header-user-name">Admin</span>
                    <a href="../logout.php" class="header-logout-icon" title="Logout">
                        <i class="fas fa-power-off"></i>
                    </a>
                </div>
            </div>
        </header>

        <div class="content-body">
            <div class="container">
                <div class="header-flex">
                    <div>
                        <h2 style="margin:0; color:var(--navy); font-weight:800;">Client Profile Editor</h2>
                        <p style="margin:4px 0 0 0; color:var(--text-muted); font-size:13.5px;">Update details for <?php echo htmlspecialchars($client['owner_name']); ?></p>
                    </div>
                    <span class="id-pill-badge">
                        ID: <?php echo htmlspecialchars($id); ?>
                    </span>
                </div>

                <form action="manage-clients.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="identifier" value="<?php echo htmlspecialchars($id); ?>">

                    <div class="form-group" style="text-align: center; margin-bottom: 28px;">
                        <label>Client / Company Logo</label>
                        <div style="margin-top: 10px; display:flex; flex-direction:column; align-items:center; gap:10px;">
                            <img src="../uploads/client_pics/<?php echo htmlspecialchars($client['profile_pic'] ?: 'default-company.png'); ?>"
                                class="logo-box">
                            <input type="file" name="profile_pic" accept="image/*" style="max-width: 280px; font-size: 12px; border: 1px solid var(--border); background:#fff;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Company / Firm Name</label>
                        <input type="text" name="company_name" value="<?php echo htmlspecialchars($client['company_name']); ?>" required>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Owner Name</label>
                            <input type="text" name="owner_name" value="<?php echo htmlspecialchars($client['owner_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($client['phone']); ?>" required>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fa-solid fa-briefcase"></i> Nature of Business</label>
                            <input type="text" name="business_nature" value="<?php echo htmlspecialchars($client['business_nature']); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-id-card"></i> Aadhaar Number</label>
                            <input type="text" name="aadhaar_no" value="<?php echo htmlspecialchars($client['aadhaar_no']); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Business Email Address</label>
                        <input type="email" name="business_email" value="<?php echo htmlspecialchars($client['business_email']); ?>">
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>GST Number</label>
                            <input type="text" name="gst_no" value="<?php echo htmlspecialchars($client['gst_no']); ?>">
                        </div>
                        <div class="form-group">
                            <label>PAN Number</label>
                            <input type="text" name="pan_no" value="<?php echo htmlspecialchars($client['pan_no']); ?>">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>TAN Number</label>
                            <input type="text" name="tan_no" value="<?php echo htmlspecialchars($client['tan_no']); ?>" placeholder="For TDS Returns">
                        </div>
                        <div class="form-group">
                            <label>CIN Number</label>
                            <input type="text" name="cin_no" value="<?php echo htmlspecialchars($client['cin_no']); ?>" placeholder="For Company Audit">
                        </div>
                    </div>

                    <div class="form-group full-width" style="margin-top: 12px;">
                        <label style="color: var(--navy); margin-bottom: 12px; font-size: 12px;">
                            <i class="fa-solid fa-folder-plus"></i> Additional Information (Custom Boxes)
                        </label>

                        <div class="services-wrapper" style="border-left: 4px solid var(--navy);">
                            <div id="dynamicBoxesContainer">
                                <?php
                                $custom_data = json_decode($client['custom_fields'] ?? '{}', true);
                                if (!empty($custom_data)) {
                                    foreach ($custom_data as $label => $value) {
                                        echo '
                                        <div class="add-service-row custom-field-row">
                                            <input type="text" name="custom_labels[]" value="' . htmlspecialchars($label) . '" placeholder="Box Name (e.g. GST Status)">
                                            <input type="text" name="custom_values[]" value="' . htmlspecialchars($value) . '" placeholder="Value">
                                            <button type="button" class="btn-add-service" style="background:var(--danger);" onclick="this.parentElement.remove()">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>';
                                    }
                                }
                                ?>
                            </div>

                            <button type="button" class="btn-add-service" onclick="addNewBox()" style="margin-top: 8px;">
                                <i class="fas fa-plus"></i> Add New Box
                            </button>
                        </div>

                        <small style="color: var(--text-muted); display: block; margin-top: 8px;">
                            <i class="fa-solid fa-circle-info"></i> Click "+" to add a custom box. Admin can fill the value and it will be saved to this client.
                        </small>
                    </div>

                    <div class="form-group full-width">
                        <label style="color: var(--orange); margin-bottom: 12px; font-size: 12px;">
                            <i class="fa-solid fa-clipboard-list"></i> Services / Tasks Requested
                        </label>

                        <div class="services-wrapper" style="border-left: 4px solid var(--orange);">
                            <div class="add-service-row">
                                <input type="text" id="newServiceInput" placeholder="Enter service name (e.g. GST Filing)">
                                <button type="button" class="btn-add-service" onclick="addService()">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </div>

                            <div class="added-services-container" id="servicesList"></div>

                            <input type="hidden" name="task_asked" id="finalServiceInput">
                        </div>

                        <small style="color: var(--text-muted); display: block; margin-top: 8px;">
                            <i class="fa-solid fa-circle-info"></i> Type a service and click Add. These will be saved to the client profile.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Office Address</label>
                        <textarea name="address" rows="3"><?php echo htmlspecialchars($client['address']); ?></textarea>
                    </div>

                    <button type="submit" name="update_client" class="btn-save">
                        <i class="fas fa-save"></i> Update Client Profile
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Live Clock
        function updateClock() {
            const now = new Date();
            const dateEl = document.getElementById('liveDate');
            const timeEl = document.getElementById('liveTime');
            if (dateEl) {
                dateEl.textContent = now.toLocaleDateString('en-IN', {
                    weekday: 'short',
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
            }
            if (timeEl) {
                timeEl.textContent = now.toLocaleTimeString('en-IN', { hour12: true });
            }
        }
        updateClock();
        setInterval(updateClock, 1000);

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

        // 1. Get existing services from PHP
        let servicesArray = <?php
            $current = array_filter(array_map('trim', explode(', ', $client['task_asked'] ?? '')));
            echo json_encode(array_values($current));
        ?>;

        const servicesList = document.getElementById('servicesList');
        const newServiceInput = document.getElementById('newServiceInput');
        const finalInput = document.getElementById('finalServiceInput');

        // 2. Initial Render
        function renderServices() {
            servicesList.innerHTML = '';

            servicesArray.forEach((service, index) => {
                const div = document.createElement('div');
                div.className = 'service-tag';
                div.innerHTML = `
                    <span>${service}</span>
                    <i class="fas fa-times-circle" onclick="removeService(${index})"></i>
                `;
                servicesList.appendChild(div);
            });

            // Keep the hidden input updated for PHP form submission
            finalInput.value = servicesArray.join(', ');
        }

        // 3. Function to Add Service
        function addService() {
            const val = newServiceInput.value.trim();

            if (val === "") {
                alert("Please enter a service name.");
                return;
            }

            if (servicesArray.includes(val)) {
                alert("This service is already added.");
                return;
            }

            servicesArray.push(val);
            newServiceInput.value = '';
            renderServices();
        }

        // 4. Function to Remove Service
        function removeService(index) {
            servicesArray.splice(index, 1);
            renderServices();
        }

        // Allow pressing "Enter" to add service
        newServiceInput.addEventListener("keypress", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                addService();
            }
        });

        // Run on page load
        renderServices();

        function addNewBox() {
            const container = document.getElementById('dynamicBoxesContainer');
            const div = document.createElement('div');
            div.className = 'add-service-row custom-field-row';
            div.style.marginTop = '10px';

            div.innerHTML = `
                <input type="text" name="custom_labels[]" placeholder="Box Name (e.g. Passport No)">
                <input type="text" name="custom_values[]" placeholder="Value">
                <button type="button" class="btn-add-service" style="background:var(--danger);" onclick="this.parentElement.remove()">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(div);
        }
    </script>
</body>

</html>