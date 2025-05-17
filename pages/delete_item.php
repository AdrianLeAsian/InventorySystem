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
            header("Location: index.php?page=items&error=has_logs&item_id=" . $item_id);
            exit;
        }
    }

    // Proceed with deletion if no logs are found
    $sql = "DELETE FROM items WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $item_id);
        if (mysqli_stmt_execute($stmt)) {
            header("Location: index.php?page=items&status=deleted");
            exit;
        } else {
            header("Location: index.php?page=items&error=delete_failed");
            exit;
        }
        mysqli_stmt_close($stmt);
    } else {
        header("Location: index.php?page=items&error=prepare_failed");
        exit;
    }
} else {
    header("Location: index.php?page=items&error=invalid_id");
    exit;
}
?> 