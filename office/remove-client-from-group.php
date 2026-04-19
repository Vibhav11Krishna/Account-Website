<?php
session_start();
include('../db.php');

// 1. Security Check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../Login.php");
    exit();
}

if (isset($_GET['group_id']) && isset($_GET['client_id'])) {
    $group_id = intval($_GET['group_id']);
    $client_to_remove = mysqli_real_escape_string($conn, $_GET['client_id']);

    // 2. Get current list of IDs for this group
    $stmt = $conn->prepare("SELECT client_ids FROM client_custom_groups WHERE id = ?");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $current_ids = explode(',', $row['client_ids']);
        
        // 3. Filter out the specific client ID
        $updated_ids = array_filter($current_ids, function($id) use ($client_to_remove) {
            return trim($id) !== $client_to_remove;
        });

        // 4. Convert back to string
        $final_string = implode(',', $updated_ids);

        // 5. Update Database
        $update_stmt = $conn->prepare("UPDATE client_custom_groups SET client_ids = ? WHERE id = ?");
        $update_stmt->bind_param("si", $final_string, $group_id);
        
        if ($update_stmt->execute()) {
            header("Location: client-groups.php?group_id=$group_id&status=client_removed");
        } else {
            header("Location: client-groups.php?group_id=$group_id&status=error");
        }
    }
} else {
    header("Location: client-groups.php");
}
exit();
?>