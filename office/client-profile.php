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
    <title>Edit Profile | <?php echo htmlspecialchars($client['company_name'] ?: $id); ?></title>
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
        }

        /* EXACT SIDEBAR STYLE FROM MANAGE CLIENTS */
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
            border-radius: 12px;
            transition: 0.3s;
            margin-bottom: 8px;
        }

        .sidebar a:hover,
        .sidebar a.active {
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

        .rotate-chevron {
            transform: rotate(180deg);
        }

        .main {
            margin-left: 280px;
            padding: 50px;
            width: calc(100% - 280px);
            box-sizing: border-box;
        }

        .container {
            max-width: 900px;
            background: white;
            padding: 40px;
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
        }

        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 20px;
        }

       /* Update these parts in your <style> */
.form-grid { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 20px; /* Increased gap for better breathing room */
}

/* Add this to ensure full-width rows stay full-width inside a grid if needed */
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
            color: #64748b;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input,
        textarea {
            width: 95%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            transition: 0.3s;
        }

        input:focus {
            border-color: var(--orange);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.1);
        }

        .btn-save {
            background: var(--navy);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            width: 100%;
            font-size: 15px;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-save:hover {
            background: var(--orange);
            transform: translateY(-2px);
        }
        /* Service Box Styling */
.services-wrapper {
    background: #fff;
    border: 1px solid var(--border);
    padding: 20px;
    border-radius: 12px;
}

.add-service-row {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.add-service-row input {
    flex: 1;
    margin-bottom: 0; /* Override default margin */
}

.btn-add-service {
    background: var(--orange);
    color: white;
    border: none;
    padding: 0 20px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 700;
    transition: 0.3s;
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
    background: #f1f5f9;
    color: var(--navy);
    padding: 8px 15px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    font-weight: 600;
    border: 1px solid #e2e8f0;
}

.service-tag i {
    color: #ef4444;
    cursor: pointer;
    transition: 0.2s;
}

.service-tag i:hover {
    transform: scale(1.2);
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
            </div>
        </div>

        <div class="dropdown-container">
            <a href="javascript:void(0)" class="dropdown-btn" class="active" onclick="toggleMenu('reportsMenu', 'repChev')">
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
        <i class="fas fa-users" class="active"></i> Manage Clients
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


    <div class="main">
        <div class="container">
            <div class="header-flex">
                <div>
                    <h2 style="margin:0; color:var(--navy);">Edit Client Profile</h2>
                    <p style="margin:5px 0 0 0; color:#94a3b8; font-size:14px;">Update details for <?php echo htmlspecialchars($client['owner_name']); ?></p>
                </div>
                <span style="background: #fff7ed; color: var(--orange); padding: 10px 20px; border-radius: 10px; font-weight: 800; font-family: monospace; border: 1px solid #ffedd5;">
                    ID: <?php echo $id; ?>
                </span>
            </div>

            <form action="manage-clients.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="identifier" value="<?php echo $id; ?>">

                <div class="form-group" style="text-align: center; margin-bottom: 30px;">
                    <label>Client / Company Logo</label>
                    <div style="margin-top: 10px;">
                        <img src="../uploads/client_pics/<?php echo $client['profile_pic'] ?: 'default-company.png'; ?>"
                            style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid var(--orange); margin-bottom: 10px;">
                        <input type="file" name="profile_pic" accept="image/*" style="font-size: 12px; border: none;">
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

                

                <div class="form-group full-width" style="margin-top: 30px;">
    <label style="color: var(--navy); margin-bottom: 15px; display: block; font-size: 14px;">
        <i class="fa-solid fa-folder-plus"></i> Additional Information (Custom Boxes)
    </label>
    
    <div class="services-wrapper" style="border-left: 4px solid var(--navy);">
        <div id="dynamicBoxesContainer">
            <?php
            // Decode existing custom fields from the database
            $custom_data = json_decode($client['custom_fields'] ?? '{}', true);
            if (!empty($custom_data)) {
                foreach ($custom_data as $label => $value) {
                    echo '
                    <div class="add-service-row custom-field-row">
                        <input type="text" name="custom_labels[]" value="'.htmlspecialchars($label).'" placeholder="Box Name (e.g. GST Status)">
                        <input type="text" name="custom_values[]" value="'.htmlspecialchars($value).'" placeholder="Value">
                        <button type="button" class="btn-add-service" style="background:#ef4444;" onclick="this.parentElement.remove()">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>';
                }
            }
            ?>
        </div>

        <button type="button" class="btn-add-service" onclick="addNewBox()" style="margin-top: 10px;">
            <i class="fas fa-plus"></i> Add New Box
        </button>
    </div>
    
    <small style="color: #94a3b8; display: block; margin-top: 8px;">
        <i class="fa-solid fa-circle-info"></i> Click "+" to add a custom box. Admin can fill the value and it will be saved to this client.
    </small>
</div>
              <div class="form-group full-width">
    <label style="color: var(--orange); margin-bottom: 15px; display: block;">
        <i class="fa-solid fa-clipboard-list"></i> Services / Tasks Requested
    </label>
    
    <div class="services-wrapper">
        <div class="add-service-row">
            <input type="text" id="newServiceInput" placeholder="Enter service name (e.g. GST Filing)">
            <button type="button" class="btn-add-service" onclick="addService()">
                <i class="fas fa-plus"></i> Add
            </button>
        </div>

        <div class="added-services-container" id="servicesList">
            </div>

        <input type="hidden" name="task_asked" id="finalServiceInput">
    </div>
    
    <small style="color: #94a3b8; display: block; margin-top: 8px;">
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
        // 1. Get existing services from PHP
let servicesArray = <?php 
    // Clean the string and convert to JS array
    $current = array_filter(array_map('trim', explode(', ', $client['task_asked'])));
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
    newServiceInput.value = ''; // Clear input
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
        <button type="button" class="btn-add-service" style="background:#ef4444;" onclick="this.parentElement.remove()">
            <i class="fas fa-trash"></i>
        </button>
    `;
    container.appendChild(div);
}
    </script>
</body>

</html>