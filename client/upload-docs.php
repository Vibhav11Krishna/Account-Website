<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'client') {
    header("Location: ../Register.php");
    exit();
}

$cid = $_SESSION['user']['identifier'];$clientName = $_SESSION['user']['name'] ?? 'Client';$status_msg = "";

if (isset($_POST['upload'])) {$category = $_POST['category'];$target_dir = "../documents/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_name = basename($_FILES["doc_file"]["name"]);
    $safe_cid = str_replace('/', '-',$cid);
    $new_filename =$safe_cid . "_" . time() . "_" . str_replace(' ', '_', $file_name);$target_file = $target_dir .$new_filename;

    if (move_uploaded_file($_FILES["doc_file"]["tmp_name"], $target_file)) {
        // Using secure prepared statements to save tracking records to upload_docs table
        $stmt =$conn->prepare("INSERT INTO upload_docs (client_id, category, file_name, file_path) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $cid,$category, $file_name,$new_filename);
        
        if ($stmt->execute()) {$status_msg = "<div class='alert-success'><i class='fas fa-check-circle'></i> File uploaded successfully!</div>";
        } else {
            $status_msg = "<div class='alert-error'><i class='fas fa-exclamation-circle'></i> Database entry error. Please try again.</div>";
        }
        $stmt->close();
    } else {
        $status_msg = "<div class='alert-error'><i class='fas fa-exclamation-circle'></i> Upload failed. Check folder permissions.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Center | KKA Client</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --coffee-espresso: #1f110c;
            --coffee-roast: #3b2319;
            --coffee-mocha: #5c3a28;
            --coffee-latte: #8c5a3c;
            --coffee-caramel: #b87a4b;
            --coffee-cream: #f8f4f0;
            --coffee-foam: #fdfaf7;
            --coffee-sand: #efe7df;
            --card-bg: #ffffff;
            --text-main: #23140e;
            --text-muted: #6b5349;
            --border: #e6d8cf;
            --border-neutral: #eadfd8;
            --shadow-sm: 0 2px 10px rgba(59, 35, 25, 0.06);
            --shadow-md: 0 12px 28px -6px rgba(59, 35, 25, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            display: flex;
            margin: 0;
            min-height: 100vh;
            background: var(--coffee-cream);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
        }

        /* Sidebar Styles */
        .sidebar {
            width: 285px;
            background: linear-gradient(180deg, var(--coffee-roast) 0%, var(--coffee-espresso) 100%);
            color: #ffffff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            border-right: 4px solid var(--coffee-caramel);
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 0 6px 16px 6px;
            margin-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .sidebar-brand-icons {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 6px 8px;
            border-radius: 11px;
            background: rgba(184, 122, 75, 0.22);
            border: 1px solid rgba(243, 213, 181, 0.35);
            color: #f3d5b5;
            font-size: 14px;
            flex-shrink: 0;
        }

        .sidebar h2 {
            font-size: 14.5px;
            font-weight: 800;
            color: #f3d5b5;
            margin: 0;
            line-height: 1.3;
        }

        /* Sidebar Profile Card */
        .sidebar-profile {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            padding: 16px 14px;
            margin-bottom: 20px;
            text-align: center;
        }

        .sidebar-avatar {
            width: 58px;
            height: 58px;
            margin: 0 auto 10px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--coffee-latte), var(--coffee-caramel));
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.28);
            border: 2px solid rgba(243, 213, 181, 0.55);
        }

        .sidebar-profile-name {
            margin: 0;
            font-size: 14.5px;
            font-weight: 800;
            color: #ffffff;
            word-break: break-word;
        }

        .sidebar-profile-id {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 10px;
            border-radius: 999px;
            background: rgba(184, 122, 75, 0.2);
            border: 1px solid rgba(243, 213, 181, 0.3);
            color: #f3d5b5;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        .nav-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.45);
            margin: 0 0 8px 10px;
        }

        .sidebar a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            margin-bottom: 6px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 600;
            transition: 0.22s ease;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: rgba(184, 122, 75, 0.22);
            color: #ffffff;
            border-left: 4px solid var(--coffee-caramel);
            padding-left: 17px;
        }

        .dropdown-content {
            display: none;
            background: rgba(0, 0, 0, 0.24);
            border-radius: 12px;
            margin: 0 4px 10px;
            padding: 4px 0;
        }

        .dropdown-content a {
            font-size: 13px;
            padding: 10px 14px;
            margin-bottom: 2px;
        }

        .logout-link {
            margin-top: auto;
            color: #f3d5b5 !important;
            background: rgba(0, 0, 0, 0.28) !important;
            border: 1px solid rgba(243, 213, 181, 0.22);
            justify-content: center;
            font-weight: 700 !important;
        }

        .logout-link:hover {
            background: var(--coffee-latte) !important;
            color: #ffffff !important;
            padding-left: 14px !important;
            border-left: 1px solid rgba(243, 213, 181, 0.4) !important;
        }

        .rotate-chevron {
            transform: rotate(180deg);
            transition: transform 0.25s ease;
        }

        /* Main Content */
        .main {
            margin-left: 285px;
            padding: 40px 48px;
            width: calc(100% - 285px);
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 28px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--border);
        }

        .page-header h1 {
            margin: 0;
            font-size: 27px;
            font-weight: 800;
            color: var(--coffee-espresso);
            letter-spacing: -0.4px;
        }

        .page-subtitle {
            margin: 4px 0 0;
            font-size: 13.5px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .client-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--coffee-roast);
            border: 1px solid var(--border);
            font-size: 12.5px;
            font-weight: 700;
            box-shadow: var(--shadow-sm);
        }

        .upload-container {
            display: grid;
            grid-template-columns: 1fr 1.45fr;
            gap: 24px;
            align-items: start;
        }

        .card {
            background: var(--card-bg);
            padding: 28px 30px;
            border-radius: 22px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            border-top: 5px solid var(--coffee-mocha);
        }

        .card h3 {
            margin: 0 0 20px;
            font-size: 18px;
            font-weight: 800;
            color: var(--coffee-espresso);
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .card h3 i {
            color: var(--coffee-latte);
        }

        .field-label {
            display: block;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--coffee-roast);
            margin-bottom: 8px;
        }

        input[type="file"],
        select {
            width: 100%;
            padding: 12px 14px;
            margin: 0 0 20px;
            border: 1.5px solid var(--border-neutral);
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
            color: var(--coffee-espresso);
            background: var(--coffee-foam);
            transition: all 0.22s ease;
        }

        input[type="file"]:focus,
        select:focus {
            outline: none;
            border-color: var(--coffee-mocha);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(92, 58, 40, 0.12);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--coffee-roast), var(--coffee-mocha));
            color: #ffffff;
            border: none;
            padding: 14px 18px;
            width: 100%;
            border-radius: 12px;
            font-family: inherit;
            font-size: 14.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: 0.25s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--coffee-espresso), var(--coffee-roast));
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 35, 25, 0.22);
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 13px 14px;
            background: var(--coffee-cream);
            color: var(--coffee-espresso);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border);
        }

        td {
            padding: 14px;
            border-bottom: 1px solid var(--border-neutral);
            font-size: 14px;
            vertical-align: middle;
        }

        tbody tr:hover {
            background: var(--coffee-foam);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 11.5px;
            font-weight: 700;
            background: var(--coffee-foam);
            color: var(--coffee-mocha);
            border: 1px solid var(--border);
            display: inline-block;
        }

        .alert-success {
            color: #166534;
            padding: 14px 18px;
            background: #dcfce7;
            border-radius: 14px;
            margin-bottom: 22px;
            border: 1px solid #bbf7d0;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            color: #991b1b;
            padding: 14px 18px;
            background: #fee2e2;
            border-radius: 14px;
            margin-bottom: 22px;
            border: 1px solid #fecaca;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 1080px) {
            .upload-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1024px) {
            .main {
                padding: 28px 24px;
            }
        }
    </style>
</head>

<body>
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icons">
                <i class="fas fa-user-tie"></i>
                <i class="fas fa-mug-hot"></i>
            </div>
            <h2>Karunesh Kumar &amp; Associates</h2>
        </div>

        <div class="sidebar-profile">
            <div class="sidebar-avatar">
                <i class="fas fa-user"></i>
            </div>
            <h4 class="sidebar-profile-name"><?php echo htmlspecialchars($clientName); ?></h4>
            <span class="sidebar-profile-id">ID: <?php echo htmlspecialchars($cid); ?></span>
        </div>

        <div class="nav-label">Client Menu</div>
        <a href="client-dashboard.php"><i class="fas fa-chart-line"></i> Overview</a>

        <div class="dropdown-container">
            <a href="javascript:void(0)" onclick="toggleFinances()">
                <i class="fas fa-wallet"></i> My Finances
                <i class="fas fa-chevron-down" id="financeChevron" style="margin-left:auto; font-size:12px;"></i>
            </a>
            <div class="dropdown-content" id="financeMenu">
                <a href="my-quotations.php"><i class="fas fa-file-alt"></i> Quotations</a>
                <a href="my-invoices.php"><i class="fas fa-file-invoice-dollar"></i> Invoices (Pay)</a>
                <a href="my-receipts.php"><i class="fas fa-receipt"></i> Acknowledgement</a>
            </div>
        </div>

        <a href="my-documents.php"><i class="fas fa-folder-open"></i> Document Vault</a>
        <a href="upload-docs.php" class="active"><i class="fas fa-cloud-upload-alt"></i> Upload Center</a>
        <a href="request-service.php"><i class="fas fa-plus-circle"></i> New Request</a>

        <a href="../logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </aside>

    <main class="main">
        <div class="page-header">
            <div>
                <h1>Document Center</h1>
                <p class="page-subtitle">Upload KYC, statutory, banking, and tax documents securely to the office.</p>
            </div>
            <div class="client-chip">
                <i class="fas fa-id-badge" style="color:var(--coffee-latte);"></i>
                <span>Client ID: <strong><?php echo htmlspecialchars($cid); ?></strong></span>
            </div>
        </div>

        <?php echo $status_msg; ?>

        <div class="upload-container">
            <div class="card">
                <h3><i class="fas fa-cloud-upload-alt"></i> Upload New File</h3>
                <form method="POST" enctype="multipart/form-data">
                    <label class="field-label">Document Category</label>
                    <select name="category" required>
                        <optgroup label="Core Identity &amp; Compliance (KYC)">
                            <option value="Aadhaar">Aadhaar Card (Front &amp; Back)</option>
                            <option value="PAN">PAN Card</option>
                            <option value="Photos">Passport Size Photograph</option>
                            <option value="Digital Signature">Digital Signature (DSC) Details</option>
                            <option value="Address Proof">Address Proof (Voter ID / Driving License / Passport)</option>
                        </optgroup>

                        <optgroup label="Legal &amp; Statutory">
                            <option value="Incorporation Docs">Certificate of Incorporation / MOA / AOA</option>
                            <option value="Partnership Deed">Partnership Deed / LLP Agreement</option>
                            <option value="Board Resolution">Board Resolutions / Minutes of Meetings</option>
                            <option value="Licenses">Shop &amp; Establishment License / Trade License</option>
                        </optgroup>

                        <optgroup label="Banking &amp; Finance">
                            <option value="Bank Statement">Bank Statements (Last 6-12 Months)</option>
                            <option value="Cancelled Cheque">Cancelled Cheque (For Bank Verification)</option>
                            <option value="Loan Docs">Loan Sanction Letters / Repayment Schedules</option>
                            <option value="Investment Docs">Insurance Policies / Demat / Mutual Fund Statements</option>
                        </optgroup>

                        <optgroup label="Taxation &amp; Accounting">
                            <option value="ITR">ITR Acknowledgments</option>
                            <option value="Form 16">Form 16 / 26AS / AIS / TIS</option>
                            <option value="GST Returns">GST Return Filings (GSTR-1, 3B, etc.)</option>
                            <option value="Audit Reports">Audited Financial Statements</option>
                        </optgroup>

                        <optgroup label="Business Operations">
                            <option value="Agreements">Vendor Contracts / Rent Agreements</option>
                            <option value="Utility Bills">Electricity / Water / Internet Bills</option>
                            <option value="Payroll">Payroll / Salary Sheets</option>
                        </optgroup>
                    </select>

                    <label class="field-label">Select File</label>
                    <input type="file" name="doc_file" required>

                    <button type="submit" name="upload" class="btn-primary">
                        <i class="fas fa-paper-plane"></i> Upload to Office
                    </button>
                </form>
            </div>

            <div class="card">
                <h3><i class="fas fa-clock-rotate-left"></i> Recently Uploaded</h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Category</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Secure data fetching tied to the upload_docs table structure
                            $stmt_view =$conn->prepare("SELECT file_name, category, uploaded_at FROM upload_docs WHERE client_id = ? ORDER BY id DESC LIMIT 5");
                            $stmt_view->bind_param("s", $cid);$stmt_view->execute();
                            $docs =$stmt_view->get_result();

                            if ($docs->num_rows > 0) {
                                while ($d =$docs->fetch_assoc()) {
                                    echo "<tr>
                                        <td><strong style='color:var(--coffee-espresso);'>" . htmlspecialchars($d['file_name']) . "</strong></td>
                                        <td><span class='badge'>" . htmlspecialchars($d['category']) . "</span></td>
                                        <td style='color:var(--text-muted); font-weight:600;'>" . date('d M Y', strtotime($d['uploaded_at'])) . "</td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' style='text-align:center; padding:32px; color:var(--text-muted);'>No uploads yet.</td></tr>";
                            }
                            $stmt_view->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleFinances() {
            var menu = document.getElementById("financeMenu");
            var chevron = document.getElementById("financeChevron");
            if (menu.style.display === "none" || menu.style.display === "") {
                menu.style.display = "block";
                chevron.classList.add("rotate-chevron");
            } else {
                menu.style.display = "none";
                chevron.classList.remove("rotate-chevron");
            }
        }
    </script>
</body>
</html>