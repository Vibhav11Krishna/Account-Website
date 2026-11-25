<?php
session_start();
include('../db.php');
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'client') {
    header("Location: ../Login.php");
    exit();
}
$cid = $_SESSION['user']['identifier'];
$clientName = $_SESSION['user']['name'] ?? 'Client';

if (isset($_POST['req'])) {
    $srv = mysqli_real_escape_string($conn, $_POST['srv']);
    $desc = mysqli_real_escape_string($conn, $_POST['desc']);
    $conn->query("INSERT INTO service_requests (client_id, service_type, description, status) VALUES ('$cid', '$srv', '$desc', 'Pending')");
    $msg = "Success! Our experts have been notified.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Service | KKA Client</title>
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

        /* Main Content Styles */
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

        .card {
            background: var(--card-bg);
            padding: 32px 34px;
            border-radius: 22px;
            max-width: 680px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            border-top: 5px solid var(--coffee-mocha);
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

        .field-label i {
            color: var(--coffee-latte);
            margin-right: 5px;
        }

        select,
        textarea {
            width: 100%;
            padding: 13px 15px;
            margin: 0 0 22px;
            border: 1.5px solid var(--border-neutral);
            border-radius: 12px;
            font-size: 14.5px;
            font-family: inherit;
            color: var(--coffee-espresso);
            background: var(--coffee-foam);
            outline: none;
            transition: all 0.22s ease;
        }

        select:focus,
        textarea:focus {
            border-color: var(--coffee-mocha);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(92, 58, 40, 0.12);
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--coffee-roast), var(--coffee-mocha));
            color: #ffffff;
            border: none;
            padding: 15px 20px;
            width: 100%;
            border-radius: 12px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            transition: 0.25s ease;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, var(--coffee-espresso), var(--coffee-roast));
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 35, 25, 0.24);
        }

        .success-banner {
            background: #dcfce7;
            color: #166534;
            padding: 15px 18px;
            border-radius: 14px;
            margin-bottom: 24px;
            max-width: 680px;
            border: 1px solid #bbf7d0;
            border-left: 5px solid #16a34a;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
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
                <i class="fas fa-chevron-down rotate-chevron" id="financeChevron" style="margin-left:auto; font-size:12px;"></i>
            </a>
            <div class="dropdown-content" id="financeMenu">
                <a href="my-quotations.php"><i class="fas fa-file-alt"></i> Quotations</a>
                <a href="my-invoices.php"><i class="fas fa-file-invoice-dollar"></i> Invoices (Pay)</a>
                <a href="my-receipts.php"><i class="fas fa-receipt"></i> Acknowledgement</a>
            </div>
        </div>

        <a href="my-documents.php"><i class="fas fa-folder-open"></i> Document Vault</a>
        <a href="upload-docs.php"><i class="fas fa-cloud-upload-alt"></i> Upload Center</a>
        <a href="request-service.php" class="active"><i class="fas fa-plus-circle"></i> New Request</a>

        <a href="../logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </aside>

    <main class="main">
        <div class="page-header">
            <div>
                <h1>Create Service Request</h1>
                <p class="page-subtitle">Submit a new compliance, taxation, or audit request to the KKA team.</p>
            </div>
            <div class="client-chip">
                <i class="fas fa-id-badge" style="color:var(--coffee-latte);"></i>
                <span>Client ID: <strong><?php echo htmlspecialchars($cid); ?></strong></span>
            </div>
        </div>

        <?php if (isset($msg)): ?>
            <div class="success-banner">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                <label class="field-label"><i class="fas fa-briefcase"></i> Which service do you need?</label>
                <select name="srv">
                    <option>GST Monthly Filing</option>
                    <option>Income Tax Return (ITR)</option>
                    <option>Balance Sheet Audit</option>
                    <option>TDS Returns</option>
                    <option>Digital Signature (DSC)</option>
                </select>

                <label class="field-label"><i class="fas fa-align-left"></i> Description</label>
                <textarea name="desc" placeholder="Provide details about your request..." required rows="5"></textarea>

                <button type="submit" name="req" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Submit to KKA Office
                </button>
            </form>
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