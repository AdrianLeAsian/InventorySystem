<?php
require_once '../config/db.php'; // Adjust path as needed

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = $_POST['item_id'] ?? '';
    $quantityChange = $_POST['quantity_change'] ?? 0;
    $reason = $_POST['reason'] ?? '';
    $stockType = $_POST['stock_type'] ?? ''; // 'stock_in' or 'stock_out'

    // Basic validation
    if (empty($itemId) || empty($quantityChange) || empty($reason) || !in_array($stockType, ['stock_in', 'stock_out'])) {
        $response['message'] = 'Item, Quantity Change, Reason, and Stock Type are required.';
        echo json_encode($response);
        exit();
    }

    $quantityChange = (int)$quantityChange; // Ensure integer

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // 1. Get current item quantity and name
        $sql_select_item = "SELECT name, quantity, low_stock_threshold, unit, description, location, category_id FROM items WHERE id = ?";
        if (!($stmt_select = mysqli_prepare($conn, $sql_select_item))) {
            throw new Exception("Prepare failed: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt_select, "i", $itemId);
        mysqli_stmt_execute($stmt_select);
        $result_select = mysqli_stmt_get_result($stmt_select);
        $item = mysqli_fetch_assoc($result_select);
        mysqli_stmt_close($stmt_select);

        if (!$item) {
            throw new Exception("Item not found.");
        }

        $currentQuantity = $item['quantity'];
        $itemName = $item['name'];
        $newQuantity = $currentQuantity;

        if ($stockType === 'stock_in') {
            $newQuantity += $quantityChange;
        } elseif ($stockType === 'stock_out') {
            if ($currentQuantity < $quantityChange) {
                throw new Exception("Not enough stock for this transaction. Current: {$currentQuantity}, Attempted to remove: {$quantityChange}");
            }
            $newQuantity -= $quantityChange;
        }

        // 2. Update item quantity
        $sql_update_item = "UPDATE items SET quantity = ?, updated_at = NOW() WHERE id = ?";
        if (!($stmt_update = mysqli_prepare($conn, $sql_update_item))) {
            throw new Exception("Prepare failed: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt_update, "ii", $newQuantity, $itemId);
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception("Execute failed: " . mysqli_stmt_error($stmt_update));
        }
        mysqli_stmt_close($stmt_update);

        // 3. Log the activity
        $sql_log_activity = "INSERT INTO activity_log (activity_type, entity_type, entity_id, entity_name, quantity_change, reason) VALUES (?, ?, ?, ?, ?, ?)";
        if (!($stmt_log = mysqli_prepare($conn, $sql_log_activity))) {
            throw new Exception("Prepare failed: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt_log, "ssisss", $stockType, $entity_type, $itemId, $itemName, $quantityChange, $reason);
        $entity_type = 'item'; // Define entity_type for binding
        if (!mysqli_stmt_execute($stmt_log)) {
            throw new Exception("Execute failed: " . mysqli_stmt_error($stmt_log));
        }
        $newLogId = mysqli_insert_id($conn); // Get the ID of the new log entry
        mysqli_stmt_close($stmt_log);

        // Commit transaction
        mysqli_commit($conn);

        // Fetch updated item details for client-side update
        $sql_fetch_updated_item = "SELECT i.id, i.name, i.category_id, i.barcode, i.quantity, i.unit, i.low_stock_threshold, i.description, i.updated_at, c.name as category_name, i.location
                                   FROM items i
                                   JOIN categories c ON i.category_id = c.id
                                   WHERE i.id = ?";
        if ($stmt_fetch_item = mysqli_prepare($conn, $sql_fetch_updated_item)) {
            mysqli_stmt_bind_param($stmt_fetch_item, "i", $itemId);
            mysqli_stmt_execute($stmt_fetch_item);
            $result_fetch_item = mysqli_stmt_get_result($stmt_fetch_item);
            $updatedItem = mysqli_fetch_assoc($result_fetch_item);
            mysqli_stmt_close($stmt_fetch_item);

            // Format updated_at for client-side display
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

        // Fetch new log entry details for client-side update
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

        $response['success'] = true;
        $response['message'] = 'Stock transaction successful. Quantity updated and logged.';
        $response['updated_item'] = $updatedItem;
        $response['new_log'] = $newLog;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $response['message'] = 'Stock transaction failed: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
