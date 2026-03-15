<?php
session_start();
include('../db.php');
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'client') {
    header("Location: ../Login.php");
    exit();
}
$cid = $_SESSION['user']['identifier'];

// Logic to handle Business Profile Submission
if (isset($_POST['save_profile'])) {
    $comp = mysqli_real_escape_string($conn, $_POST['company_name']);
    $owner = mysqli_real_escape_string($conn, $_POST['owner_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']); 
    $email = mysqli_real_escape_string($conn, $_POST['business_email']); 
    $gst = mysqli_real_escape_string($conn, $_POST['gst_no']);
    $pan = mysqli_real_escape_string($conn, $_POST['pan_no']);
    $addr = mysqli_real_escape_string($conn, $_POST['address']);

    $check = $conn->query("SELECT id FROM client_profiles WHERE client_id='$cid'");
    if ($check->num_rows > 0) {
        $conn->query("UPDATE client_profiles SET company_name='$comp', owner_name='$owner', phone='$phone', business_email='$email', gst_no='$gst', pan_no='$pan', address='$addr' WHERE client_id='$cid'");
    } else {
        $conn->query("INSERT INTO client_profiles (client_id, company_name, owner_name, phone, business_email, gst_no, pan_no, address) VALUES ('$cid', '$comp', '$owner', '$phone', '$email', '$gst', '$pan', '$addr')");
    }
    header("Location: client-dashboard.php");
    exit();
}

// Fetch current profile
$profile = $conn->query("SELECT * FROM client_profiles WHERE client_id='$cid'")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | KKA Client</title>
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

        /* Sidebar Styles */
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
            font-size: 20px;
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

        .sidebar a:hover, .sidebar a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 4px solid var(--orange);
        }

        .logout-link {
            margin-top: auto;
            color: #fda4af !important;
            background: rgba(244, 63, 94, 0.1);
        }

        .rotate-chevron { transform: rotate(180deg); }

        /* Main Content Area */
        .main {
            margin-left: 280px;
            padding: 50px;
            width: calc(100% - 280px);
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            border-bottom: 4px solid var(--navy);
        }

        .stat-card p {
            margin: 10px 0 0;
            font-size: 28px;
            font-weight: 800;
            color: var(--navy);
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
        }

        /* Form-specific styling */
        .form-label {
            display: block; 
            font-size: 11px; 
            font-weight: 700; 
            margin-bottom: 8px; 
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-label i {
            color: var(--navy);
            margin-right: 4px;
            width: 14px;
        }

        .styled-input {
            width: 100%; 
            padding: 12px 15px; 
            border: 1px solid #e2e8f0; 
            border-radius: 12px;
            font-size: 14px;
            color: var(--navy);
            transition: all 0.3s ease;
            box-sizing: border-box;
            background: #fcfdfe;
        }

        .styled-input:focus {
            outline: none;
            border-color: var(--orange);
            background: white;
            box-shadow: 0 0 0 4px rgba(255, 140, 0, 0.1);
        }

        .save-btn {
            flex: 2; 
            background: var(--navy); 
            color: white; 
            border: none; 
            padding: 14px; 
            border-radius: 12px; 
            cursor: pointer; 
            font-weight: 700;
            font-size: 15px;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .save-btn:hover {
            background: #052a54;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(11, 60, 116, 0.2);
        }

        .cancel-btn {
            flex: 1; 
            background: #f1f5f9; 
            color: #64748b;
            border: none; 
            padding: 14px; 
            border-radius: 12px; 
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }

        .edit-btn {
            background: #f1f5f9;
            color: var(--navy);
            border: 1px solid #e2e8f0;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: 0.3s;
        }

        .edit-btn:hover { background: #e2e8f0; }

        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            text-align: left;
            padding: 15px;
            background: #f1f5f9;
            color: var(--navy);
            font-size: 13px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 15px;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .bg-pending { background: #fef9c3; color: #854d0e; }
        .bg-completed { background: #dcfce7; color: #166534; }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>Karunesh Kumar & Associates Client</h2>
        <a href="client-dashboard.php" class="active"><i class="fas fa-chart-line"></i> Overview</a>
        <div class="dropdown-container">
            <a href="javascript:void(0)" onclick="toggleFinances()"><i class="fas fa-wallet"></i> My Finances <i class="fas fa-chevron-down" id="financeChevron" style="margin-left:auto; font-size:12px;"></i></a>
            <div id="financeMenu" style="display:none; background:rgba(0,0,0,0.2); border-radius:10px; margin:0 10px;">
                <a href="my-quotations.php" style="padding:10px 15px; font-size:14px;">Quotations</a>
                <a href="my-invoices.php" style="padding:10px 15px; font-size:14px;">Invoices</a>
                <a href="my-receipts.php" style="padding:10px 15px; font-size:14px;">Acknowledgement</a>
            </div>
        </div>
        <a href="my-documents.php"><i class="fas fa-folder-open"></i> Document Vault</a>
        <a href="upload-docs.php"><i class="fas fa-cloud-upload-alt"></i> Upload Center</a>
        <a href="request-service.php"><i class="fas fa-plus-circle"></i> New Request</a>
        <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></h1>
        <p style="color: #64748b; margin-top:-15px;">Client ID: <?php echo $cid; ?></p>

        <div class="stat-grid">
            <div class="stat-card"><h3>Active Requests</h3><p><?php echo $conn->query("SELECT id FROM service_requests WHERE client_id='$cid' AND status='Pending'")->num_rows; ?></p></div>
            <div class="stat-card" style="border-color: var(--orange);"><h3>Documents</h3><p><?php echo $conn->query("SELECT id FROM client_documents WHERE client_id='$cid'")->num_rows; ?></p></div>
            <div class="stat-card" style="border-color: #22c55e;"><h3>Completed</h3><p><?php echo $conn->query("SELECT id FROM service_requests WHERE client_id='$cid' AND status='Completed'")->num_rows; ?></p></div>
        </div>

        <div class="card" style="margin-bottom: 30px; border-top: 5px solid var(--orange); position: relative; overflow: hidden;">
            
            <?php if(!$profile): ?>
            <i class="fas fa-building" style="position: absolute; right: -20px; bottom: -20px; font-size: 150px; color: #f8fafc; z-index: 0;"></i>
            <?php endif; ?>

            <div style="position: relative; z-index: 1;">
                <div id="profileView" style="<?php echo (!$profile) ? 'display:none;' : 'display:block;'; ?>">
                    <?php if($profile): ?>
                    <div style="display:flex; justify-content:space-between; align-items:start;">
                        <div>
                            <h2 style="margin:0; color:var(--navy); letter-spacing: -0.5px;"><?php echo htmlspecialchars($profile['company_name']); ?></h2>
                            <div style="display: flex; gap: 20px; margin-top: 12px; flex-wrap: wrap;">
                                <span style="font-size: 14px; color:var(--orange); font-weight:600;"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($profile['owner_name']); ?></span>
                                <span style="font-size: 14px; color:#64748b;"><i class="fas fa-phone-alt"></i> +91 <?php echo htmlspecialchars($profile['phone']); ?></span>
                                <span style="font-size: 14px; color:#64748b;"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($profile['business_email']); ?></span>
                            </div>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:10px; align-items:flex-end;">
                            <button class="edit-btn" onclick="toggleEdit()"><i class="fas fa-pen-to-square"></i> Edit Profile</button>
                            <div style="text-align:right; font-size:12px; background: #fff7ed; padding: 10px 15px; border-radius: 12px; border: 1px solid #ffedd5;">
                                <p style="margin:0; color:#9a3412;">GST: <b style="font-family: monospace; font-size:14px;"><?php echo $profile['gst_no'] ?: 'NOT PROVIDED'; ?></b></p>
                                <p style="margin:4px 0 0; color:#9a3412;">PAN: <b style="font-family: monospace; font-size:14px;"><?php echo htmlspecialchars($profile['pan_no']); ?></b></p>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top:20px; padding-top:15px; border-top:1px solid #f1f5f9; font-size:14px; color: #475569;">
                        <i class="fas fa-map-location-dot" style="color:var(--orange); margin-right:8px;"></i> <?php echo htmlspecialchars($profile['address']); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div id="profileForm" style="<?php echo (!$profile) ? 'display:block;' : 'display:none;'; ?>">
                    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                        <h3 style="color:var(--navy); margin:0;"><i class="fas fa-briefcase" style="color:var(--orange);"></i> <?php echo $profile ? 'Update Business Profile' : 'Complete Your Profile'; ?></h3>
                        <?php if(!$profile): ?>
                            <span style="font-size: 12px; background: #fee2e2; color: #b91c1c; padding: 4px 12px; border-radius: 20px; font-weight: 600;">Required to generate invoices</span>
                        <?php endif; ?>
                    </div>

                    <form method="POST" style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 20px;">
                        <div style="grid-column: span 3;">
                            <label class="form-label"><i class="fas fa-building"></i> Company Name</label>
                            <input type="text" name="company_name" class="styled-input" placeholder="e.g. KKA Solutions Pvt Ltd" value="<?php echo $profile['company_name'] ?? ''; ?>" required>
                        </div>
                        <div style="grid-column: span 3;">
                            <label class="form-label"><i class="fas fa-user-circle"></i> Authorized Owner Name</label>
                            <input type="text" name="owner_name" class="styled-input" placeholder="Name as per PAN" value="<?php echo $profile['owner_name'] ?? ''; ?>" required>
                        </div>

                        <div style="grid-column: span 3;">
                            <label class="form-label"><i class="fas fa-envelope-open-text"></i> Business Email</label>
                            <input type="email" name="business_email" class="styled-input" placeholder="billing@company.com" value="<?php echo $profile['business_email'] ?? ''; ?>" required>
                        </div>
                        <div style="grid-column: span 3;">
                            <label class="form-label"><i class="fas fa-mobile-screen"></i> Contact Phone</label>
                            <input type="text" name="phone" class="styled-input" placeholder="10-digit mobile number" value="<?php echo $profile['phone'] ?? ''; ?>" required>
                        </div>

                        <div style="grid-column: span 3;">
                            <label class="form-label"><i class="fas fa-fingerprint"></i> GST Number (Optional)</label>
                            <input type="text" name="gst_no" class="styled-input" placeholder="22AAAAA0000A1Z5" value="<?php echo $profile['gst_no'] ?? ''; ?>">
                        </div>
                        <div style="grid-column: span 3;">
                            <label class="form-label"><i class="fas fa-address-card"></i> PAN Number</label>
                            <input type="text" name="pan_no" class="styled-input" placeholder="ABCDE1234F" value="<?php echo $profile['pan_no'] ?? ''; ?>" required>
                        </div>

                        <div style="grid-column: span 6;">
                            <label class="form-label"><i class="fas fa-map-marker-alt"></i> Full Registered Address</label>
                            <textarea name="address" class="styled-input" rows="2" placeholder="Office No, Building, Street, City, State - PIN" required style="resize: none;"><?php echo $profile['address'] ?? ''; ?></textarea>
                        </div>

                        <div style="grid-column: span 6; display:flex; gap:12px; margin-top: 10px;">
                            <button type="submit" name="save_profile" class="save-btn">
                                <i class="fas fa-check-circle"></i> Save Business Profile
                            </button>
                            <?php if($profile): ?>
                                <button type="button" onclick="toggleEdit()" class="cancel-btn">Cancel</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="color: var(--navy); margin-top:0;">Recent Activity</h3>
            <table>
                <thead>
                    <tr><th>Service</th><th>Description</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php
                    $history = $conn->query("SELECT * FROM service_requests WHERE client_id='$cid' ORDER BY id DESC LIMIT 5");
                    if ($history->num_rows > 0) {
                        while ($h = $history->fetch_assoc()) {
                            $status_class = "bg-" . strtolower($h['status']);
                            echo "<tr>
                                    <td><strong>" . htmlspecialchars($h['service_type']) . "</strong></td>
                                    <td style='color:#64748b;'>" . htmlspecialchars($h['description']) . "</td>
                                    <td><span class='badge $status_class'>" . htmlspecialchars($h['status']) . "</span></td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3' style='text-align:center; color:#94a3b8; padding:30px;'>No recent activity found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleFinances() {
            var menu = document.getElementById("financeMenu");
            var chevron = document.getElementById("financeChevron");
            menu.style.display = (menu.style.display === "none") ? "block" : "none";
            chevron.classList.toggle("rotate-chevron");
        }

        function toggleEdit() {
            var view = document.getElementById("profileView");
            var form = document.getElementById("profileForm");
            if (view.style.display === "none") {
                view.style.display = "block";
                form.style.display = "none";
            } else {
                view.style.display = "none";
                form.style.display = "block";
            }
        }
    </script>
</body>
</html>