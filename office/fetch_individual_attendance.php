<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') exit();

$email = $_GET['email'] ?? '';

// Using a prepared statement for safety and precision
$stmt = $conn->prepare("SELECT log_date, login_time, logout_time, status FROM attendance WHERE email = ? ORDER BY log_date DESC");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>