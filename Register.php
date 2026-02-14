<?php
session_start();
include('db.php');
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $uid = mysqli_real_escape_string($conn, $_POST['uid']);
    $pass = mysqli_real_escape_string($conn, $_POST['pass']);
    $res = $conn->query("SELECT * FROM users WHERE identifier='$uid' AND password='$pass'");
    if($res->num_rows > 0){
        $u = $res->fetch_assoc();
        $_SESSION['user'] = $u;
        if($u['role'] == 'admin') header("Location: office/admin-dashboard.php");
        elseif($u['role'] == 'office') header("Location: office/employee-dashboard.php");
        else header("Location: client/client-dashboard.php");
    } else { $err = "Invalid Credentials. Please try again."; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KKA Portal | Secure Access</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root { --navy: #0b3c74; --orange: #ff8c00; --light-navy: #164e8d; --slate: #64748b; }
        
        * { box-sizing: border-box; }
        body, html { margin: 0; padding: 0; height: 100%; font-family: 'Plus Jakarta Sans', sans-serif; background: #f0f4f8; overflow: hidden; }

        .container { display: flex; height: 100vh; width: 100%; }

        /* Branding Side */
        .image-side {
            flex: 1.4;
            background: linear-gradient(135deg, rgba(11, 60, 116, 0.9), rgba(22, 78, 141, 0.8)), 
                        url('assets/accounting3.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 10%;
            color: white;
            position: relative;
        }

        .image-side h1 { font-size: 56px; line-height: 1.1; margin-bottom: 20px; font-weight: 800; letter-spacing: -1px; }
        .image-side p { font-size: 19px; opacity: 0.85; line-height: 1.7; max-width: 480px; font-weight: 300; }

        /* Form Side */
        .form-side {
            flex: 1;
            background: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
            position: relative;
        }

        .back-home {
            position: absolute;
            top: 30px;
            right: 40px;
            text-decoration: none;
            color: var(--slate);
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }
        .back-home:hover { color: var(--navy); transform: translateX(-5px); }

        .login-box {
            width: 100%;
            max-width: 420px;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .logo-area { margin-bottom: 40px; }
        .logo-area span { color: var(--navy); font-size: 32px; font-weight: 800; letter-spacing: -1px; }
        .logo-area span i { color: var(--orange); font-style: normal; }

        .tab-menu {
            display: flex;
            background: #f1f5f9;
            padding: 6px;
            border-radius: 14px;
            margin-bottom: 35px;
        }

        .tab-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            background: transparent;
            color: var(--slate);
            transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .tab-btn.active { background: white; color: var(--navy); box-shadow: 0 4px 15px rgba(0,0,0,0.08); }

        .input-group { margin-bottom: 22px; position: relative; }
        .input-group label { display: block; margin-bottom: 10px; font-weight: 700; color: var(--navy); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .input-group input {
            width: 100%;
            padding: 16px 16px;
            background: #f8fafc;
            border: 2px solid #f1f5f9;
            border-radius: 12px;
            font-size: 16px;
            transition: 0.3s;
            color: var(--navy);
        }

        .input-group input:focus { border-color: var(--navy); outline: none; background: #fff; box-shadow: 0 0 0 4px rgba(11, 60, 116, 0.05); }

        .forgot-link {
            display: none;
            text-align: right;
            margin-top: -15px;
            margin-bottom: 20px;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn { from { opacity:0; transform: translateY(-5px); } to { opacity:1; transform: translateY(0); } }
        
        .forgot-link a { color: var(--orange); font-size: 13px; text-decoration: none; font-weight: 700; }

        .btn-login {
            width: 100%;
            padding: 18px;
            background: var(--navy);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
            box-shadow: 0 10px 20px rgba(11, 60, 116, 0.15);
        }

        .btn-login:hover { background: var(--orange); transform: translateY(-3px); box-shadow: 0 15px 30px rgba(255, 140, 0, 0.2); }

        .error { color: #e11d48; background: #fff1f2; padding: 14px; border-radius: 12px; margin-bottom: 25px; font-size: 14px; font-weight: 600; border: 1px solid #ffe4e6; text-align: center; }

        @media (max-width: 1024px) { .image-side { flex: 0.8; padding: 0 5%; } .image-side h1 { font-size: 38px; } }
        @media (max-width: 850px) { .image-side { display: none; } }
    </style>
</head>
<body>

    <div class="container">
        <div class="image-side">
            <h1>Expert Finance,<br>Excellence Delivered.</h1>
            <p>Welcome to the Karunesh Kumar & Associates Digital Portal. Access your financial reports and consultancy tools in one secure environment.</p>
        </div>

        <div class="form-side">
            <a href="index.php" class="back-home">
                <i class="fas fa-chevron-left"></i> Home
            </a>

            <div class="login-box">
                <div class="logo-area">
                    <span>KKA<i>.</i>PORTAL</span>
                </div>

                <div class="tab-menu">
                    <button class="tab-btn active" onclick="switchMode('client', this)">Client Portal</button>
                    <button class="tab-btn" onclick="switchMode('office', this)">Office Staff</button>
                </div>

                <?php if(isset($err)) echo "<div class='error'><i class='fas fa-exclamation-circle'></i> $err</div>"; ?>

                <form method="POST">
                    <div class="input-group">
                        <label id="user-label">Unique KK-ID</label>
                        <input type="text" name="uid" id="uid-field" placeholder="enter your ID" required>
                    </div>
                    
                    <div class="input-group">
                        <label>Secure Password</label>
                        <input type="password" name="pass" placeholder="enter your password" required>
                    </div>

                    <div id="forgot-pass-container" class="forgot-link">
                        <a href="forgot-password.php">Forgot office password?</a>
                    </div>

                    <button type="submit" class="btn-login">Secure Sign In</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function switchMode(mode, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const label = document.getElementById('user-label');
            const input = document.getElementById('uid-field');
            const forgot = document.getElementById('forgot-pass-container');

            if(mode === 'office') {
                label.innerText = 'Office Email Address';
                input.placeholder = 'name@kka.com';
                input.type = 'email';
                forgot.style.display = 'block';
            } else {
                label.innerText = 'Unique KK-ID';
                input.placeholder = 'KK/2026/001';
                input.type = 'text';
                forgot.style.display = 'none';
            }
        }
    </script>
</body>
</html>