<?php
/**
 * delete_item.php
 *
 * This script handles the AJAX request for deleting an item from the inventory.
 * It performs validation, checks for existing inventory logs, deletes the item,
 * and logs the activity. It returns a JSON response indicating success or failure.
 */

// Include the database configuration file
require_once '../config/db.php'; // Adjust path as needed

// Set the content type to JSON for the response
header('Content-Type: application/json');

// Initialize the response array
$response = ['success' => false, 'message' => ''];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve input data
    $itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
    $reason = trim($_POST['reason'] ?? '');

    // Basic validation
    if ($itemId === false || $itemId <= 0) {
        $response['message'] = 'Invalid Item ID.';
        echo json_encode($response);
        exit();
    }
    if (empty($reason)) {
        $response['message'] = 'Reason for deletion is required.';
        echo json_encode($response);
        exit();
    }

    // Start a database transaction
    mysqli_begin_transaction($conn);

    try {
        // Fetch item name before deletion for logging
        $item_name_to_delete = '';
        $sql_fetch_name = "SELECT name FROM items WHERE id = ?";
        if (!($stmt_fetch_name = mysqli_prepare($conn, $sql_fetch_name))) {
            throw new Exception("Prepare failed for fetching item name: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt_fetch_name, "i", $itemId);
        mysqli_stmt_execute($stmt_fetch_name);
        mysqli_stmt_bind_result($stmt_fetch_name, $fetched_name);
        mysqli_stmt_fetch($stmt_fetch_name);
        $item_name_to_delete = $fetched_name;
        mysqli_stmt_close($stmt_fetch_name);

        if (empty($item_name_to_delete)) {
            throw new Exception("Item not found.");
        }

        // Check if the item has any inventory log entries.
        // If it does, prevent deletion to maintain historical data integrity.
        $sql_check_logs = "SELECT COUNT(*) as log_count FROM inventory_log WHERE item_id = ?";
        if (!($stmt_check = mysqli_prepare($conn, $sql_check_logs))) {
            throw new Exception("Prepare failed for checking logs: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt_check, "i", $itemId);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_bind_result($stmt_check, $log_count);
        mysqli_stmt_fetch($stmt_check);
        mysqli_stmt_close($stmt_check);

        if ($log_count > 0) {
            throw new Exception("Cannot delete item: It has existing inventory log entries. Consider archiving instead.");
        }

        // Proceed with deletion
        $sql_delete_item = "DELETE FROM items WHERE id = ?";
        if (!($stmt_delete = mysqli_prepare($conn, $sql_delete_item))) {
            throw new Exception("Prepare failed for item deletion: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt_delete, "i", $itemId);
        if (!mysqli_stmt_execute($stmt_delete)) {
            throw new Exception("Execute failed for item deletion: " . mysqli_stmt_error($stmt_delete));
        }
        mysqli_stmt_close($stmt_delete);

        // Log the activity in activity_log
        $log_sql = "INSERT INTO activity_log (activity_type, entity_type, entity_id, entity_name, reason) VALUES (?, ?, ?, ?, ?)";
        if (!($log_stmt = mysqli_prepare($conn, $log_sql))) {
            throw new Exception("Prepare failed for activity log: " . mysqli_error($conn));
        }
        $activity_type = 'item_deleted';
        $entity_type = 'item';
        mysqli_stmt_bind_param($log_stmt, "ssiss", $activity_type, $entity_type, $itemId, $item_name_to_delete, $reason);
        if (!mysqli_stmt_execute($log_stmt)) {
            throw new Exception("Execute failed for activity log: " . mysqli_stmt_error($log_stmt));
        }
        mysqli_stmt_close($log_stmt);

        // Commit the transaction
        mysqli_commit($conn);

        $response['success'] = true;
        $response['message'] = 'Item deleted successfully!';

    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $response['message'] = 'Error deleting item: ' . $e->getMessage();
    }
} else {
    // If the request method is not POST
    $response['message'] = 'Invalid request method.';
}

// Encode the response array to JSON and output it
echo json_encode($response);
?>
