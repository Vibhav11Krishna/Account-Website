<?php
session_start();
include('../db.php');

// Security: Only Admin can access this script
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    exit("Unauthorized Access");
}

if (isset($_GET['email']) && isset($_GET['status'])) {
    $email = $conn->real_escape_string($_GET['email']);
    $status = intval($_GET['status']);

    // 1. Find the internal ID using the identifier (Gmail)
    $userQuery = $conn->query("SELECT id FROM users WHERE identifier = '$email' LIMIT 1");
    
    if ($userQuery && $userQuery->num_rows > 0) {
        $user = $userQuery->fetch_assoc();
        $uid = $user['id'];

        // 2. Check if the profile exists in 'employee_profiles'
        $check = $conn->query("SELECT id FROM employee_profiles WHERE user_id = $uid");

        if ($check->num_rows > 0) {
            // Update the existing lock status
            $sql = "UPDATE employee_profiles SET is_locked = $status WHERE user_id = $uid";
        } else {
            // Create a new record if they haven't filled their profile yet
            $sql = "INSERT INTO employee_profiles (user_id, is_locked) VALUES ($uid, $status)";
        }

        if ($conn->query($sql)) {
            // Redirect back to dashboard with a success signal
            header("Location: manage-employees.php?status=updated");
        } else {
            echo "Database Error: " . $conn->error;
        }
    } else {
        echo "Error: No user found with identifier " . htmlspecialchars($email);
    }
    exit();
}
?>