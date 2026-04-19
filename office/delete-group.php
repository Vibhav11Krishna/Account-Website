<?php
session_start();
include('../db.php');

// 1. Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Login.php");
    exit();
}

// 2. Validate ID
if (isset($_GET['id'])) {
    $group_id = intval($_GET['id']);

    // 3. Delete using Prepared Statement
    $stmt = $conn->prepare("DELETE FROM client_custom_groups WHERE id = ?");
    $stmt->bind_param("i", $group_id);

    if ($stmt->execute()) {
        // Success: Redirect back to the main groups page
        header("Location: client-groups.php?status=deleted");
    } else {
        // Error: Database failure
        header("Location: client-groups.php?status=error&msg=" . urlencode($conn->error));
    }
    $stmt->close();
} else {
    header("Location: client-groups.php");
}
exit();
?>