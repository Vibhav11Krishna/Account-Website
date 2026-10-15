<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Register.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $tenure_start = !empty($_POST['tenure_start']) ? "'" . mysqli_real_escape_string($conn, $_POST['tenure_start']) . "'" : "NULL";
    $tenure_end = !empty($_POST['tenure_end']) ? "'" . mysqli_real_escape_string($conn, $_POST['tenure_end']) . "'" : "NULL";

    $check = $conn->query("SELECT user_id FROM employee_profiles WHERE user_id = '$user_id'");
    if ($check && $check->num_rows > 0) {
        $sql = "UPDATE employee_profiles SET tenure_start = $tenure_start, tenure_end = $tenure_end WHERE user_id = '$user_id'";
    } else {
        $sql = "INSERT INTO employee_profiles (user_id, tenure_start, tenure_end) VALUES ('$user_id', $tenure_start, $tenure_end)";
    }

    if ($conn->query($sql)) {
        header("Location: manage-employees.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>