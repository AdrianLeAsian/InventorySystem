<?php
/**
 * get_latest_item_updates.php
 *
 * This script fetches items that have been updated since a given timestamp.
 * It is designed to be used for AJAX polling to keep the client-side inventory
 * table synchronized with the database in near real-time.
 */

require_once '../config/db.php'; // Adjust path as needed
require_once '../includes/helpers.php'; // Include helper functions

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'items' => [], 'server_time' => date('Y-m-d H:i:s')];

// Get the last_timestamp from the GET request, if provided
$lastTimestamp = $_GET['last_timestamp'] ?? null;

$sql = "SELECT i.id, i.name, i.category_id, i.quantity, i.unit, i.low_stock_threshold, i.min_stock_level, i.max_stock_level, i.description, i.location, i.updated_at, c.name as category_name
        FROM items i
        JOIN categories c ON i.category_id = c.id";

$params = [];
$types = "";

if ($lastTimestamp) {
    // Add condition to fetch items updated AFTER the lastTimestamp
    $sql .= " WHERE i.updated_at > ?";
    $params[] = $lastTimestamp;
    $types .= "s"; // 's' for string (timestamp)
}

$sql .= " ORDER BY i.updated_at ASC"; // Order by updated_at to get the latest first

if ($stmt = mysqli_prepare($conn, $sql)) {
    if ($lastTimestamp) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $updatedItems = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Calculate stock status for client-side rendering
        $quantity = $row['quantity'];
        $low_stock_threshold = $row['low_stock_threshold'];
        $min_stock_level = $row['min_stock_level'];
        $max_stock_level = $row['max_stock_level'];

        $stock_status = 'normal';
        if ($quantity == 0) {
            $stock_status = 'out_of_stock';
        } elseif ($quantity <= $low_stock_threshold || $quantity <= $min_stock_level) {
            $stock_status = 'low_stock';
        } elseif ($max_stock_level > 0 && $quantity >= $max_stock_level) {
            $stock_status = 'surplus';
        }
        $row['stock_status'] = $stock_status;

        // Format updated_at for client-side display
        $row['formatted_updated_at'] = format_last_activity($row['updated_at']);
        
        $updatedItems[] = $row;
    }
    mysqli_stmt_close($stmt);

    $response['success'] = true;
    $response['message'] = 'Successfully fetched updated items.';
    $response['items'] = $updatedItems;
} else {
    $response['message'] = 'Database query preparation failed: ' . mysqli_error($conn);
}

echo json_encode($response);
?>
