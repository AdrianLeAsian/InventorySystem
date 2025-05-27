<?php
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $item_id = (int)$_GET['id'];

    // Before deleting an item, check if it has any inventory log entries.
    // If it does, it might be better to prevent deletion to maintain historical data integrity.
    // Alternatively, you could implement a soft delete (archiving) feature later.
    $sql_check_logs = "SELECT COUNT(*) as log_count FROM inventory_log WHERE item_id = ?";
    if ($stmt_check = mysqli_prepare($link, $sql_check_logs)) {
        mysqli_stmt_bind_param($stmt_check, "i", $item_id);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_bind_result($stmt_check, $log_count);
        mysqli_stmt_fetch($stmt_check);
        mysqli_stmt_close($stmt_check);

        if ($log_count > 0) {
            header("Location: index.php?page=inventory&error=item_has_logs");
            exit;
        }
    }

    // Fetch item name before deletion for logging
    $item_name_to_delete = '';
    $sql_fetch_name = "SELECT name FROM items WHERE id = ?";
    if ($stmt_fetch_name = mysqli_prepare($link, $sql_fetch_name)) {
        mysqli_stmt_bind_param($stmt_fetch_name, "i", $item_id);
        mysqli_stmt_execute($stmt_fetch_name);
        mysqli_stmt_bind_result($stmt_fetch_name, $fetched_name);
        mysqli_stmt_fetch($stmt_fetch_name);
        $item_name_to_delete = $fetched_name;
        mysqli_stmt_close($stmt_fetch_name);
    }

    // Proceed with deletion if no logs are found
    $sql = "DELETE FROM items WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $item_id);
        if (mysqli_stmt_execute($stmt)) {
            // Log the activity
            $log_sql = "INSERT INTO activity_log (activity_type, entity_type, entity_id, entity_name, reason) VALUES (?, ?, ?, ?, ?)";
            if ($log_stmt = mysqli_prepare($link, $log_sql)) {
                $activity_type = 'item_deleted';
                $entity_type = 'item';
                $reason = 'Item deleted';
                mysqli_stmt_bind_param($log_stmt, "ssiss", $activity_type, $entity_type, $item_id, $item_name_to_delete, $reason);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);
            }
            header("Location: index.php?page=inventory&status=item_deleted");
            exit;
        } else {
            header("Location: index.php?page=inventory&error=item_delete_failed");
            exit;
        }
        mysqli_stmt_close($stmt);
    } else {
        header("Location: index.php?page=inventory&error=item_prepare_failed");
        exit;
    }
} else {
    header("Location: index.php?page=inventory&error=item_invalid_id");
    exit;
}
?>
