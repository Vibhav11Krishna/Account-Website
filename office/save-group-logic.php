<?php
session_start();
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the ID from the hidden input. 0 means New Group.
    $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
    $group_name = mysqli_real_escape_string($conn, $_POST['group_name']);
    
    // Collect selected client IDs
    $new_ids = [];
    if (isset($_POST['client_ids']) && is_array($_POST['client_ids'])) {
        foreach ($_POST['client_ids'] as $id) {
            $new_ids[] = mysqli_real_escape_string($conn, $id);
        }
    }
    $final_ids_string = implode(',', $new_ids);

    if ($group_id > 0) {
        // ACTION: EDIT EXISTING GROUP
        // This updates the name AND replaces the client list with your current selection
        $stmt = $conn->prepare("UPDATE client_custom_groups SET group_name = ?, client_ids = ? WHERE id = ?");
        $stmt->bind_param("ssi", $group_name, $final_ids_string, $group_id);
        $stmt->execute();
        
        header("Location: client-groups.php?group_id=$group_id&status=updated");
    } else {
        // ACTION: CREATE NEW GROUP
        if (empty($new_ids)) {
            header("Location: client-groups.php?status=error&msg=No clients selected");
            exit();
        }
        
        $stmt = $conn->prepare("INSERT INTO client_custom_groups (group_name, client_ids, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ss", $group_name, $final_ids_string);
        $stmt->execute();
        
        header("Location: client-groups.php?status=success");
    }
    exit();
}
?>