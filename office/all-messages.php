<?php
session_start();
include('../db.php');

// Security Check - Matches your 'admin' role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

$status_msg = "";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Inbox | KKA Admin</title>
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

        .dropdown-content a:hover {
            border-left: none;
            background: rgba(255, 255, 255, 0.05);
            color: var(--orange);
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
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            padding: 14px 40px;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 90;
            box-shadow: var(--shadow-sm);
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

        .welcome-text span {
            color: var(--navy);
            font-weight: 700;
        }

        .header-right-group {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-clock {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            padding: 8px 16px;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .clock-icon i {
            color: var(--orange);
            font-size: 16px;
        }

        .clock-display {
            display: flex;
            flex-direction: column;
        }

        .clock-display span {
            font-size: 9.5px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
        }

        .clock-display strong {
            font-size: 12.5px;
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
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--danger-bg);
            color: var(--danger);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 13px;
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
        }

        /* ================= INBOX CONTENT ================= */
        .tab-container {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 14px;
        }

        .tab-btn {
            padding: 10px 20px;
            border: 1px solid var(--border);
            background: var(--card-bg);
            font-weight: 700;
            font-size: 13px;
            font-family: inherit;
            cursor: pointer;
            color: var(--text-muted);
            border-radius: 10px;
            transition: all 0.2s;
            box-shadow: var(--shadow-sm);
        }

        .tab-btn:hover {
            border-color: var(--orange);
            color: var(--navy);
        }

        .tab-btn.active {
            background: var(--navy);
            color: white;
            border-color: var(--navy);
        }

        .card {
            background: var(--card-bg);
            padding: 28px;
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            display: none;
        }

        .card.active {
            display: block;
        }

        .card h3 {
            margin: 0 0 20px 0;
            font-size: 16px;
            color: var(--navy);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border);
        }

        .card h3 i {
            color: var(--orange);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 13px 16px;
            background: #f8fafc;
            color: var(--navy);
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            font-weight: 700;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 15px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 13.5px;
            vertical-align: top;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
        }

        .badge-contact {
            background: #e0f2fe;
            color: #0369a1;
        }

        .badge-blog {
            background: var(--orange-glow);
            color: #c2410c;
        }

        .badge-career {
            background: #dcfce7;
            color: #166534;
        }

        .btn-view {
            padding: 7px 14px;
            background: var(--navy);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-view:hover {
            background: var(--orange);
        }

        @media (max-width: 1024px) {
            .admin-top-bar {
                padding: 14px 20px;
            }
            .content-body {
                padding: 24px 20px;
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
            <a href="javascript:void(0)" class="dropdown-btn" onclick="toggleMenu('clientMenu', 'clientChev')">
                <i class="fas fa-users"></i> Manage Clients
                <i class="fas fa-chevron-down" id="clientChev" style="margin-left:auto; font-size:11px; transition:0.3s;"></i>
            </a>
            <div class="dropdown-content" id="clientMenu">
                <a href="manage-clients.php">Clients</a>
                <a href="client-groups.php">Client Groups</a>
                <a href="client-services.php">Services</a>
                <a href="client-upload-docs.php">Clients Uploads</a>
            </div>
        </div>

        <a href="manage-employees.php"><i class="fas fa-user-tie"></i> Manage Employees</a>
        <a href="all-messages.php" class="active"><i class="fas fa-inbox"></i> Web Inbox</a>
        <a href="../logout.php" style="margin-top:auto; color:#fca5a5;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <!-- Main Workspace -->
    <div class="main">
        <header class="admin-top-bar">
            <div class="brand-section">
                <h1>Web Message Center</h1>
                <div class="welcome-text">Manage inquiries, blog leads, and career applications in one place</div>
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
            <div class="tab-container">
                <button class="tab-btn active" onclick="showTab('contact-tab', this)">Contact Inquiries</button>
                <button class="tab-btn" onclick="showTab('blog-tab', this)">Blog Leads</button>
                <button class="tab-btn" onclick="showTab('career-tab', this)">Career Apps</button>
            </div>

            <div id="contact-tab" class="card active">
                <h3><i class="fas fa-envelope"></i> Website Contact Messages</h3>
                <table>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Subject &amp; Message</th>
                        <th>Action</th>
                    </tr>
                    <?php
                    $msgs = $conn->query("SELECT * FROM contact_messages ORDER BY id DESC");
                    if ($msgs && $msgs->num_rows > 0):
                        while ($m = $msgs->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($m['created_at'])) ?></td>
                                <td><strong><?= htmlspecialchars($m['name']) ?></strong><br><small style="color:var(--text-muted);"><?= htmlspecialchars($m['email']) ?></small></td>
                                <td>
                                    <span class="badge badge-contact"><?= htmlspecialchars($m['subject']) ?></span><br>
                                    <p style="margin:6px 0 0; color:var(--text-muted);"><?= htmlspecialchars($m['message']) ?></p>
                                </td>
                                <td><a href="mailto:<?= htmlspecialchars($m['email']) ?>" class="btn-view"><i class="fas fa-reply"></i> Reply</a></td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr><td colspan="4" style="text-align:center; padding:20px; color:var(--text-muted);">No contact messages found.</td></tr>
                    <?php endif; ?>
                </table>
            </div>

            <div id="blog-tab" class="card">
                <h3><i class="fas fa-blog"></i> Blog Summary Leads</h3>
                <table>
                    <tr>
                        <th>Date</th>
                        <th>Reader Name</th>
                        <th>Email</th>
                        <th>Message/Query</th>
                    </tr>
                    <?php
                    $leads = $conn->query("SELECT * FROM contact_leads ORDER BY id DESC");
                    if ($leads && $leads->num_rows > 0):
                        while ($l = $leads->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($l['created_at'])) ?></td>
                                <td><strong><?= htmlspecialchars($l['full_name']) ?></strong></td>
                                <td><?= htmlspecialchars($l['email']) ?></td>
                                <td style="color:var(--text-muted);"><?= htmlspecialchars($l['query_message']) ?></td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr><td colspan="4" style="text-align:center; padding:20px; color:var(--text-muted);">No blog leads found.</td></tr>
                    <?php endif; ?>
                </table>
            </div>

            <div id="career-tab" class="card">
                <h3><i class="fas fa-user-tie"></i> Career Applications</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Applicant</th>
                            <th>Contact Info</th>
                            <th>Position &amp; Exp</th>
                            <th>Resume</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $apps = $conn->query("SELECT * FROM career_applications ORDER BY id DESC");
                        if ($apps && $apps->num_rows > 0):
                            while ($a = $apps->fetch_assoc()):
                                $app_date = !empty($a['created_at']) ? date('d M Y', strtotime($a['created_at'])) : 'N/A';
                        ?>
                            <tr>
                                <td><?= $app_date ?></td>
                                <td><strong><?= htmlspecialchars($a['name']) ?></strong></td>
                                <td>
                                    <i class="fas fa-envelope" style="font-size: 11px; color: var(--text-muted);"></i> <?= htmlspecialchars($a['email']) ?><br>
                                    <i class="fas fa-phone" style="font-size: 11px; color: var(--text-muted);"></i>
                                    <a href="tel:<?= htmlspecialchars($a['phone']) ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($a['phone']) ?></a>
                                    <a href="https://wa.me/91<?= preg_replace('/[^0-9]/', '', $a['phone']) ?>" target="_blank" style="color:#25D366; margin-left:5px;">
                                        <i class="fab fa-whatsapp"></i>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge badge-career"><?= htmlspecialchars($a['position'] ?? 'N/A') ?></span><br>
                                    <small style="color:var(--text-muted);">Experience: <?= htmlspecialchars($a['experience'] ?? 'N/A') ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($a['resume'])): ?>
                                        <a href="../uploads/resumes/<?= htmlspecialchars($a['resume']) ?>" class="btn-view" target="_blank">
                                            <i class="fas fa-file-pdf"></i> View PDF
                                        </a>
                                    <?php else: ?>
                                        <small style="color:var(--danger); font-weight:600;">No Resume</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                            <tr><td colspan="5" style="text-align:center; padding:20px; color:var(--text-muted);">No applications found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
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

        function showTab(tabId, btn) {
            document.querySelectorAll('.card').forEach(card => card.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            btn.classList.add('active');
        }

        function toggleMenu(menuId, chevronId) {
            const menu = document.getElementById(menuId);
            const chevron = document.getElementById(chevronId);

            menu.classList.toggle('show-menu');
            chevron.classList.toggle('rotate-chevron');

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