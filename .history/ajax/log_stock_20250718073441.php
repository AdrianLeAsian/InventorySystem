<?php
/**
 * log_stock.php
 *
 * This script handles the AJAX request for logging stock changes (stock in/out)
 * for an item in the inventory system. It performs quantity updates, logs the activity,
 * and ensures data consistency using database transactions.
 * It returns a JSON response indicating success or failure, along with updated item
 * details and the new log entry.
 */

// Include the database configuration file
require_once '../config/db.php'; // Adjust path as needed

// Set the content type to JSON for the response
header('Content-Type: application/json');

// Initialize the response array
$response = ['success' => false, 'message' => ''];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve input data from the POST request
    $itemId = $_POST['item_id'] ?? '';
    $quantityChange = $_POST['quantity_change'] ?? 0;
    $reason = $_POST['reason'] ?? '';
    $stockType = $_POST['stock_type'] ?? ''; // Expected values: 'stock_in' or 'stock_out'

    // Basic validation: Check if required fields are provided and stock type is valid
    if (empty($itemId) || empty($reason) || !in_array($stockType, ['stock_in', 'stock_out'])) {
        $response['message'] = 'Item, Quantity Change, Reason, and Stock Type are required.';
        echo json_encode($response);
        exit();
    }

    // Ensure quantity change is a positive integer
    $quantityChange = (int)$quantityChange;
    if ($quantityChange <= 0) {
        $response['message'] = 'Quantity Change must be a positive number.';
        echo json_encode($response);
        exit();
    }

    // Start a database transaction to ensure atomicity of operations
    mysqli_begin_transaction($conn);

    try {
        // 1. Get current item quantity and name for the specified item
        $sql_select_item = "SELECT name, quantity, low_stock_threshold, unit, description, location, category_id FROM items WHERE id = ?";
        if (!($stmt_select = mysqli_prepare($conn, $sql_select_item))) {
            throw new Exception("Prepare failed for item selection: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt_select, "i", $itemId);
        mysqli_stmt_execute($stmt_select);
        $result_select = mysqli_stmt_get_result($stmt_select);
        $item = mysqli_fetch_assoc($result_select);
        mysqli_stmt_close($stmt_select);

        // Check if the item exists
        if (!$item) {
            throw new Exception("Item not found.");
        }

        $currentQuantity = $item['quantity'];
        $itemName = $item['name'];
        $newQuantity = $currentQuantity;

        // Calculate the new quantity based on stock type
        if ($stockType === 'stock_in') {
            $newQuantity += $quantityChange;
        } elseif ($stockType === 'stock_out') {
            // Prevent stock from going negative
            if ($currentQuantity < $quantityChange) {
                throw new Exception("Not enough stock for this transaction. Current: {$currentQuantity}, Attempted to remove: {$quantityChange}");
            }
            $newQuantity -= $quantityChange;
        }

        // 2. Update item quantity in the database
        $sql_update_item = "UPDATE items SET quantity = ?, updated_at = NOW() WHERE id = ?";
        if (!($stmt_update = mysqli_prepare($conn, $sql_update_item))) {
            throw new Exception("Prepare failed for item update: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt_update, "ii", $newQuantity, $itemId);
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception("Execute failed for item update: " . mysqli_stmt_error($stmt_update));
        }
        mysqli_stmt_close($stmt_update);

        // 3. Log the activity in the activity_log table
        $sql_log_activity = "INSERT INTO activity_log (activity_type, entity_type, entity_id, entity_name, quantity_change, reason) VALUES (?, ?, ?, ?, ?, ?)";
        if (!($stmt_log = mysqli_prepare($conn, $sql_log_activity))) {
            throw new Exception("Prepare failed for activity log: " . mysqli_error($conn));
        }
        $entity_type = 'item'; // Define entity_type for binding
        mysqli_stmt_bind_param($stmt_log, "ssisss", $stockType, $entity_type, $itemId, $itemName, $quantityChange, $reason);
        if (!mysqli_stmt_execute($stmt_log)) {
            throw new Exception("Execute failed for activity log: " . mysqli_stmt_error($stmt_log));
        }
        $newLogId = mysqli_insert_id($conn); // Get the ID of the newly inserted log entry
        mysqli_stmt_close($stmt_log);

        // If all operations are successful, commit the transaction
        mysqli_commit($conn);

        // Fetch updated item details for client-side display
        $sql_fetch_updated_item = "SELECT i.id, i.name, i.category_id, i.barcode, i.quantity, i.unit, i.low_stock_threshold, i.min_stock_level, i.max_stock_level, i.description, i.updated_at, c.name as category_name, i.location
                                   FROM items i
                                   JOIN categories c ON i.category_id = c.id
                                   WHERE i.id = ?";
        if ($stmt_fetch_item = mysqli_prepare($conn, $sql_fetch_updated_item)) {
            mysqli_stmt_bind_param($stmt_fetch_item, "i", $itemId);
            mysqli_stmt_execute($stmt_fetch_item);
            $result_fetch_item = mysqli_stmt_get_result($stmt_fetch_item);
            $updatedItem = mysqli_fetch_assoc($result_fetch_item);
            mysqli_stmt_close($stmt_fetch_item);

            // Format updated_at for client-side display (e.g., "Today, 10:30 AM", "Yesterday, "3 days ago")
            if ($updatedItem && isset($updatedItem['updated_at'])) {
                $date = new DateTime($updatedItem['updated_at']);
                $now = new DateTime();
                $interval = $now->diff($date);

                if ($interval->d == 0 && $interval->h < 24) { // Today
                    $updatedItem['formatted_updated_at'] = "Today, " . $date->format('g:i A');
                } elseif ($interval->d == 1) { // Yesterday
                    $updatedItem['formatted_updated_at'] = "Yesterday, " . $date->format('g:i A');
                } elseif ($interval->days < 7) { // Within a week
                    $updatedItem['formatted_updated_at'] = $interval->days . " days ago";
                } else {
                    $updatedItem['formatted_updated_at'] = $date->format('M j, Y');
                }
            } else {
                $updatedItem['formatted_updated_at'] = 'N/A';
            }
        } else {
            throw new Exception("Error fetching updated item details: " . mysqli_error($conn));
        }

        // Fetch new log entry details for client-side display
        $sql_fetch_new_log = "SELECT id, activity_type, entity_type, entity_id, entity_name, quantity_change, reason, DATE_FORMAT(log_date, '%Y-%m-%d %H:%i:%s') as log_date
                              FROM activity_log
                              WHERE id = ?";
        if ($stmt_fetch_log = mysqli_prepare($conn, $sql_fetch_new_log)) {
            mysqli_stmt_bind_param($stmt_fetch_log, "i", $newLogId);
            mysqli_stmt_execute($stmt_fetch_log);
            $result_fetch_log = mysqli_stmt_get_result($stmt_fetch_log);
            $newLog = mysqli_fetch_assoc($result_fetch_log);
            mysqli_stmt_close($stmt_fetch_log);
        } else {
            throw new Exception("Error fetching new log details: " . mysqli_error($conn));
        }

        // Set success response and include updated item and new log data
        $response['success'] = true;
        $response['message'] = 'Stock transaction successful. Quantity updated and logged.';
        $response['updated_item'] = $updatedItem;
        $response['new_log'] = $newLog;

    } catch (Exception $e) {
        // If any error occurs, rollback the transaction
        mysqli_rollback($conn);
        $response['message'] = 'Stock transaction failed: ' . $e->getMessage();
    }
} else {
    // If the request method is not POST
    $response['message'] = 'Invalid request method.';
}

// Encode the response array to JSON and output it
echo json_encode($response);
?>
