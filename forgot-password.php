<?php
session_start();
include('db.php');

$message = "";
$status = "";

if (isset($_POST['request_reset'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Check if the email exists and belongs to the 'office' or 'admin' role
    $check = $conn->query("SELECT * FROM users WHERE identifier='$email' AND (role='office' OR role='admin')");

    if ($check->num_rows > 0) {
        // Flag the account for a reset
        $conn->query("UPDATE users SET reset_requested = 1 WHERE identifier='$email'");
        $status = "success";
        $message = "Request sent! Please contact the Administrator to receive your temporary password.";
    } else {
        $status = "error";
        $message = "No office account found with that email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Office Password Reset | KKA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --navy: #0b3c74; --orange: #ff8c00; --bg: #f8fafc; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        
        .reset-box { background: white; padding: 40px; border-radius: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.05); width: 100%; max-width: 400px; text-align: center; }
        
        .icon-circle { width: 80px; height: 80px; background: #fff7ed; color: var(--orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 30px; }
        
        h2 { color: var(--navy); margin-bottom: 10px; font-weight: 800; }
        p { color: #64748b; font-size: 14px; line-height: 1.5; margin-bottom: 30px; }
        
        input { width: 100%; padding: 16px; border: 2px solid #f1f5f9; border-radius: 12px; font-size: 16px; margin-bottom: 20px; box-sizing: border-box; }
        input:focus { outline: none; border-color: var(--navy); }
        
        .btn-reset { width: 100%; padding: 16px; background: var(--navy); color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-reset:hover { background: var(--orange); transform: translateY(-2px); }
        
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 600; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        
        .back-link { display: block; margin-top: 25px; color: var(--navy); text-decoration: none; font-size: 14px; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="reset-box">
    <div class="icon-circle">
        <i class="fas fa-key"></i>
    </div>
    <h2>Staff Reset</h2>
    <p>Enter your office email address. An administrator will verify your identity and reset your password.</p>

    <?php if($message): ?>
        <div class="alert alert-<?php echo $status; ?>">
            <i class="fas <?php echo ($status == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="name@kka.com" required>
        <button type="submit" name="request_reset" class="btn-reset">Send Reset Request</button>
    </form>

    <a href="Register.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Register</a>
</div>

</body>
</html>