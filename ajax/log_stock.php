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
    mysqli_begin_transaction($link);

    try {
        // 1. Get current item quantity and name
        $sql_select_item = "SELECT name, quantity FROM items WHERE id = ?";
        if (!($stmt_select = mysqli_prepare($link, $sql_select_item))) {
            throw new Exception("Prepare failed: " . mysqli_error($link));
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
        if (!($stmt_update = mysqli_prepare($link, $sql_update_item))) {
            throw new Exception("Prepare failed: " . mysqli_error($link));
        }
        mysqli_stmt_bind_param($stmt_update, "ii", $newQuantity, $itemId);
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception("Execute failed: " . mysqli_stmt_error($stmt_update));
        }
        mysqli_stmt_close($stmt_update);

        // 3. Log the activity
        $sql_log_activity = "INSERT INTO activity_log (activity_type, entity_type, entity_id, entity_name, quantity_change, reason) VALUES (?, ?, ?, ?, ?, ?)";
        if (!($stmt_log = mysqli_prepare($link, $sql_log_activity))) {
            throw new Exception("Prepare failed: " . mysqli_error($link));
        }
        mysqli_stmt_bind_param($stmt_log, "ssisss", $stockType, $entity_type, $itemId, $itemName, $quantityChange, $reason);
        $entity_type = 'item'; // Define entity_type for binding
        if (!mysqli_stmt_execute($stmt_log)) {
            throw new Exception("Execute failed: " . mysqli_stmt_error($stmt_log));
        }
        mysqli_stmt_close($stmt_log);

        // Commit transaction
        mysqli_commit($link);
        $response['success'] = true;
        $response['message'] = 'Stock transaction successful. Quantity updated and logged.';
    } catch (Exception $e) {
        mysqli_rollback($link);
        $response['message'] = 'Stock transaction failed: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
