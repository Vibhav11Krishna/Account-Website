<?php
session_start();
include('db.php'); // Path to your database connection

// 1. Set the correct timezone for India
date_default_timezone_set('Asia/Kolkata');

// 2. Only run the update if it's an EMPLOYEE logging out
if (isset($_SESSION['user']) && $_SESSION['user']['role'] == 'office') {
    
    $email = $_SESSION['user']['identifier'];
    $today = date('Y-m-d');
    $time = date('H:i:s');

    // Record the checkout time for today's entry
    $update_sql = "UPDATE attendance 
                   SET logout_time = '$time' 
                   WHERE email = '$email' 
                   AND log_date = '$today' 
                   AND logout_time IS NULL";
    
    $conn->query($update_sql);
}

// 3. Standard logout for everyone (Employee, Admin, and Client)
session_unset();
session_destroy();
header("Location: Register.php");
exit();
?>