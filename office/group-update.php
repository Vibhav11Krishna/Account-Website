<?php
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $gid = intval($_POST['group_id']);
    $name = mysqli_real_escape_string($conn, $_POST['group_name']);
    $ids = isset($_POST['client_ids']) ? implode(',', $_POST['client_ids']) : '';

    $sql = "UPDATE client_custom_groups SET group_name = '$name', client_ids = '$ids' WHERE id = $gid";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: client-groups.php?group_id=$gid&status=updated");
    } else {
        echo "Update Failed: " . mysqli_error($conn);
    }
    exit();
}
?>