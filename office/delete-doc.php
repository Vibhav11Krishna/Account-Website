<?php
session_start();
include('../db.php');

// Add your admin authentication check here
// if (!isset($_SESSION['admin'])) { header("Location: ../admin-login.php"); exit(); }

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // 1. Get file path from DB before deleting record
    $stmt = $conn->prepare("SELECT file_path FROM upload_docs WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $file = $result->fetch_assoc();

    if ($file) {
        // 2. Delete physical file
        $filepath = "../documents/" . $file['file_path'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        // 3. Delete database record
        $del_stmt = $conn->prepare("DELETE FROM upload_docs WHERE id = ?");
        $del_stmt->bind_param("i", $id);
        $del_stmt->execute();
    }
}

header("Location: client-upload-docs.php");
exit();
?>