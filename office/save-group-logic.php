<?php
session_start();
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $group_name = mysqli_real_escape_string($conn, $_POST['group_name']);
    
    if (isset($_POST['client_ids']) && !empty($_POST['client_ids'])) {
        // These are the new IDs from the checkboxes
        $new_ids = array_map(function($id) use ($conn) {
            return mysqli_real_escape_string($conn, $id);
        }, $_POST['client_ids']);

        // 1. Check if the group name already exists
        $check_stmt = $conn->prepare("SELECT id, client_ids FROM client_custom_groups WHERE group_name = ?");
        $check_stmt->bind_param("s", $group_name);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            // GROUP EXISTS - LET'S MERGE
            $row = $result->fetch_assoc();
            $existing_id = $row['id'];
            
            // Convert existing string into an array
            $existing_ids = explode(',', $row['client_ids']);
            
            // Merge existing and new, then remove duplicates
            $merged_ids = array_unique(array_merge($existing_ids, $new_ids));
            
            // Clean up empty values if any
            $merged_ids = array_filter($merged_ids);
            
            $final_ids_string = implode(',', $merged_ids);

            // 2. UPDATE the existing group with the merged list
            $update_stmt = $conn->prepare("UPDATE client_custom_groups SET client_ids = ? WHERE id = ?");
            $update_stmt->bind_param("si", $final_ids_string, $existing_id);
            $update_stmt->execute();
            
            header("Location: client-groups.php?status=updated");
        } else {
            // GROUP IS NEW - JUST INSERT
            $final_ids_string = implode(',', $new_ids);
            $insert_stmt = $conn->prepare("INSERT INTO client_custom_groups (group_name, client_ids, created_at) VALUES (?, ?, NOW())");
            $insert_stmt->bind_param("ss", $group_name, $final_ids_string);
            $insert_stmt->execute();
            
            header("Location: client-groups.php?status=success");
        }
        exit();
        
    } else {
        header("Location: client-groups.php?status=error&msg=No clients selected");
        exit();
    }
}
?>