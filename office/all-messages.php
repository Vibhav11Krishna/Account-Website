<?php
session_start();
include('../db.php');

// Security Check - Matches your 'office' role
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'office') {
    header("Location: ../Login.php");
    exit();
}

$status_msg = "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Web Inbox | KKA Staff</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --navy: #0b3c74; --orange: #ff8c00; --sidebar: #082d56; --bg: #f8fafc; }
        body { display:flex; margin:0; background:var(--bg); font-family: 'Inter', sans-serif; }
        .sidebar { width:280px; background:var(--sidebar); color:white; height:100vh; position:fixed; padding:30px 20px; box-sizing: border-box; display: flex; flex-direction: column; }
        .sidebar h2 { font-size: 22px; color: var(--orange); margin-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .sidebar a { color:rgba(255,255,255,0.7); text-decoration:none; display:flex; align-items:center; gap:12px; padding:14px; margin-bottom:8px; border-radius:12px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background:rgba(255,255,255,0.1); color:white; border-left: 4px solid var(--orange); }
        .logout-link { margin-top: auto; color: #fda4af !important; background: rgba(244, 63, 94, 0.1); }
        .main { margin-left:280px; padding:50px; width:calc(100% - 280px); }
        .tab-container { display: flex; gap: 20px; margin-bottom: 30px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; }
        .tab-btn { padding: 10px 20px; border: none; background: none; font-weight: 600; cursor: pointer; color: #64748b; border-radius: 8px; }
        .tab-btn.active { background: var(--navy); color: white; }
        .card { background:white; padding:30px; border-radius:24px; box-shadow:0 10px 25px rgba(0,0,0,0.03); display: none; }
        .card.active { display: block; }
        table { width:100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #f1f5f9; color: var(--navy); font-size: 13px; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: top; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .badge-contact { background: #e0f2fe; color: #0369a1; }
        .badge-blog { background: #fef3c7; color: #92400e; }
        .badge-career { background: #dcfce7; color: #166534; }
        .btn-view { padding: 6px 12px; background: var(--navy); color: white; text-decoration: none; border-radius: 6px; font-size: 12px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>KKA STAFF</h2>
    <a href="employee-dashboard.php" ><i class="fas fa-tasks"></i> My Tasks</a>
    <a href="work-basket.php"><i class="fas fa-briefcase"></i> Work Basket</a>
    <a href="all-messages.php"class="active"><i class="fas fa-inbox"></i> Web Inbox</a>
    <a href="staff-attendance.php"><i class="fas fa-clock"></i> Attendance</a>
    <a href="../logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <h1>Web Message Center</h1>
    <p style="color: #64748b; margin-top: -10px;">Manage inquiries, blog leads, and career applications in one place.</p>

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
                <th>Subject & Message</th>
                <th>Action</th>
            </tr>
            <?php
            $msgs = $conn->query("SELECT * FROM contact_messages ORDER BY id DESC");
            while($m = $msgs->fetch_assoc()): ?>
                <tr>
                    <td><?= date('d M Y', strtotime($m['created_at'])) ?></td>
                    <td><strong><?= $m['name'] ?></strong><br><small><?= $m['email'] ?></small></td>
                    <td>
                        <span class="badge badge-contact"><?= $m['subject'] ?></span><br>
                        <p style="margin-top:5px; color:#64748b;"><?= $m['message'] ?></p>
                    </td>
                    <td><a href="mailto:<?= $m['email'] ?>" class="btn-view">Reply</a></td>
                </tr>
            <?php endwhile; ?>
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
            // Assuming your blog table is named contact_leads
            $leads = $conn->query("SELECT * FROM contact_leads ORDER BY id DESC");
            while($l = $leads->fetch_assoc()): ?>
                <tr>
                    <td><?= date('d M Y', strtotime($l['created_at'])) ?></td>
                    <td><strong><?= $l['full_name'] ?></strong></td>
                    <td><?= $l['email'] ?></td>
                    <td style="color:#64748b;"><?= $l['query_message'] ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <div id="career-tab" class="card">
        <h3><i class="fas fa-user-tie"></i> Career Applications</h3>
        <table>
            <tr>
                <th>Date</th>
                <th>Applicant</th>
                <th>Position & Exp</th>
                <th>Resume</th>
            </tr>
            <?php
            $apps = $conn->query("SELECT * FROM career_applications ORDER BY id DESC");
            while($a = $apps->fetch_assoc()): ?>
                <tr>
                    <td><?= date('d M Y', strtotime($a['created_at'])) ?></td>
                    <td><strong><?= $a['name'] ?></strong><br><small><?= $a['email'] ?></small></td>
                    <td>
                        <span class="badge badge-career"><?= $a['position'] ?></span><br>
                        <small>Exp: <?= $a['experience'] ?></small>
                    </td>
                    <td>
                        <?php if(!empty($a['resume'])): ?>
                            <a href="../uploads/resumes/<?= $a['resume'] ?>" class="btn-view" target="_blank">
                                <i class="fas fa-file-pdf"></i> View PDF
                            </a>
                        <?php else: ?>
                            <small style="color:red;">No Resume</small>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<script>
function showTab(tabId, btn) {
    document.querySelectorAll('.card').forEach(card => card.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
}
</script>

</body>
</html>