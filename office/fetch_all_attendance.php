<?php
include('../db.php');
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    exit(json_encode([]));
}

$sql = "SELECT u.name, a.email, a.log_date, a.login_time, a.logout_time 
        FROM attendance a 
        JOIN users u ON a.email = u.identifier 
        ORDER BY a.log_date DESC";

$result = $conn->query($sql);
$data = [];

while($row = $result->fetch_assoc()) {
    $data[] = [
        'Employee Name' => $row['name'],
        'Email'         => $row['email'],
        'Date'          => date('d-M-Y', strtotime($row['log_date'])),
        'Check-In'      => date('h:i A', strtotime($row['login_time'])),
        'Check-Out'     => $row['logout_time'] ? date('h:i A', strtotime($row['logout_time'])) : 'Active Session'
    ];
}

header('Content-Type: application/json');
echo json_encode($data);