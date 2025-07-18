<?php
/**
 * update_item.php
 *
 * This script handles the AJAX request for updating an existing item's details
 * in the inventory system. It performs validation, checks for category existence,
 * updates the item in the database, and returns a JSON response indicating
 * success or failure, along with the updated item's details.
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
    $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
    $item_name = trim($_POST['item_name'] ?? '');
    $item_category_id = filter_input(INPUT_POST, 'item_category_id', FILTER_VALIDATE_INT);
    $item_barcode = trim($_POST['item_barcode'] ?? '');
    $item_quantity = filter_input(INPUT_POST, 'item_quantity', FILTER_VALIDATE_INT);
    $item_unit = trim($_POST['item_unit'] ?? '');
    $item_low_stock_threshold = filter_input(INPUT_POST, 'item_low_stock_threshold', FILTER_VALIDATE_INT);
    $item_min_stock_level = filter_input(INPUT_POST, 'item_min_stock_level', FILTER_VALIDATE_INT); // New field
    $item_max_stock_level = filter_input(INPUT_POST, 'item_max_stock_level', FILTER_VALIDATE_INT); // New field
    $item_description = trim($_POST['item_description'] ?? '');
    $item_location = trim($_POST['item_location'] ?? '');
    $item_reason = trim($_POST['item_reason'] ?? ''); // New reason field

    // Basic validation for all input fields
    if ($item_id === false || $item_id <= 0) {
        $response['message'] = 'Invalid Item ID.';
    } elseif (empty($item_name)) {
        $response['message'] = 'Item Name is required.';
    } elseif ($item_category_id === false || $item_category_id <= 0) {
        $response['message'] = 'Invalid Category.';
    } elseif ($item_quantity === false || $item_quantity < 0) {
        $response['message'] = 'Quantity must be a non-negative number.';
    } elseif ($item_low_stock_threshold === false || $item_low_stock_threshold < 0) {
        $response['message'] = 'Low Stock Threshold must be a non-negative number.';
    } elseif ($item_min_stock_level === false || $item_min_stock_level < 0) {
        $response['message'] = 'Min Stock Level must be a non-negative number.';
    } elseif ($item_max_stock_level === false || $item_max_stock_level < 0) {
        $response['message'] = 'Max Stock Level must be a non-negative number.';
    } elseif (empty($item_reason)) { // Validate reason field
        $response['message'] = 'Reason for update is required.';
    } else {
        // Check if the selected category exists in the database
        $sql_check_category = "SELECT id FROM categories WHERE id = ?";
        if ($stmt_check_cat = mysqli_prepare($conn, $sql_check_category)) {
            mysqli_stmt_bind_param($stmt_check_cat, "i", $item_category_id);
            mysqli_stmt_execute($stmt_check_cat);
            mysqli_stmt_store_result($stmt_check_cat);
            if (mysqli_stmt_num_rows($stmt_check_cat) == 0) {
                $response['message'] = 'Selected category does not exist.';
                echo json_encode($response);
                exit();
            }
            mysqli_stmt_close($stmt_check_cat);
        } else {
            $response['message'] = 'Database error checking category: ' . mysqli_error($conn);
            echo json_encode($response);
            exit();
        }

        // Prepare an update statement for the items table
        $sql = "UPDATE items SET name = ?, category_id = ?, barcode = ?, quantity = ?, unit = ?, low_stock_threshold = ?, min_stock_level = ?, max_stock_level = ?, description = ?, location = ?, updated_at = NOW() WHERE id = ?";

        // Prepare the statement to prevent SQL injection
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Bind parameters to the prepared statement
            mysqli_stmt_bind_param($stmt, "sisiiissssi", 
                $item_name, 
                $item_category_id, 
                $item_barcode, 
                $item_quantity, 
                $item_unit, 
                $item_low_stock_threshold, 
                $item_min_stock_level, // New parameter
                $item_max_stock_level, // New parameter
                $item_description, 
                $item_location, 
                $item_id
            );

            // Execute the statement
            if (mysqli_stmt_execute($stmt)) {
                // Check if any rows were affected (i.e., if the update actually changed something)
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    // Log the item update in inventory_log
                    $logSql = "INSERT INTO inventory_log (item_id, type, quantity_change, reason) VALUES (?, 'adjustment', 0, ?)"; // Type 'adjustment' for updates, quantity_change 0 as it's not a stock movement
                    if ($logStmt = mysqli_prepare($conn, $logSql)) {
                        mysqli_stmt_bind_param($logStmt, "is", $item_id, $item_reason);
                        mysqli_stmt_execute($logStmt);
                        mysqli_stmt_close($logStmt);
                    } else {
                        error_log("Error logging item update: " . mysqli_error($conn));
                    // Log the item update in activity_log
                    $activityLogSql = "INSERT INTO activity_log (activity_type, entity_type, entity_id, entity_name, reason) VALUES (?, ?, ?, ?, ?)";
                    if ($activityLogStmt = mysqli_prepare($conn, $activityLogSql)) {
                        $activityType = 'item_updated';
                        $entityType = 'item';
                        // Fetch item name for logging
                        $itemNameQuery = "SELECT name FROM items WHERE id = ?";
                        $itemNameStmt = mysqli_prepare($conn, $itemNameQuery);
                        mysqli_stmt_bind_param($itemNameStmt, "i", $item_id);
                        mysqli_stmt_execute($itemNameStmt);
                        mysqli_stmt_bind_result($itemNameStmt, $current_item_name);
                        mysqli_stmt_fetch($itemNameStmt);
                        mysqli_stmt_close($itemNameStmt);

                        mysqli_stmt_bind_param($activityLogStmt, "ssiss", $activityType, $entityType, $item_id, $current_item_name, $item_reason);
                        mysqli_stmt_execute($activityLogStmt);
                        mysqli_stmt_close($activityLogStmt);
                    } else {
                        error_log("Error logging item update to activity_log: " . mysqli_error($conn));
                    }

                    $response['success'] = true;
                    $response['message'] = 'Item updated successfully!';
                    
                    // Fetch updated item data to send back to client for table refresh
                    $sql_fetch_updated = "SELECT i.id, i.name, i.category_id, i.barcode, i.quantity, i.unit, i.low_stock_threshold, i.min_stock_level, i.max_stock_level, i.description, i.location, i.updated_at, c.name as category_name
                                          FROM items i
                                          JOIN categories c ON i.category_id = c.id
                                          WHERE i.id = ?";
                    if ($stmt_fetch = mysqli_prepare($conn, $sql_fetch_updated)) {
                        mysqli_stmt_bind_param($stmt_fetch, "i", $item_id);
                        mysqli_stmt_execute($stmt_fetch);
                        $result_fetch = mysqli_stmt_get_result($stmt_fetch);
                        if ($updated_item = mysqli_fetch_assoc($result_fetch)) {
                            $response['item'] = $updated_item;
                            // Format updated_at for consistency with client-side display functions
                            $response['item']['formatted_updated_at'] = format_last_activity($updated_item['updated_at']);
                        }
                        mysqli_stmt_close($stmt_fetch); // Close the fetch statement
                    }
                } else {
                    $response['message'] = 'No changes made to item or item not found.';
                }
            } else {
                $response['message'] = 'Error updating item: ' . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt); // Close the update statement
        } else {
            $response['message'] = 'Database query preparation failed: ' . mysqli_error($conn);
        }
    }
} else {
    // If the request method is not POST
    $response['message'] = 'Invalid request method.';
}

// Encode the response array to JSON and output it
echo json_encode($response);

/**
 * format_last_activity
 *
 * Formats a given timestamp into a human-readable string, indicating
 * "Today", "Yesterday", "X days ago", or a specific date.
 * This function is duplicated in get_item.php and update_item.php for convenience.
 * In a larger application, this would typically be in a shared utility file.
 *
 * @param string $timestamp The timestamp string to format.
 * @return string The formatted date string.
 */
function format_last_activity($timestamp) {
    if (empty($timestamp)) return 'N/A'; // Return 'N/A' if timestamp is empty

    $date = new DateTime($timestamp); // Create DateTime object from timestamp
    $now = new DateTime(); // Create DateTime object for current time
    $interval = $now->diff($date); // Calculate the difference between now and the timestamp

    // Check if the activity was today
    if ($interval->d == 0 && $interval->h < 24) {
        return "Today, " . $date->format('g:i A'); // Format as "Today, 10:30 AM"
    } elseif ($interval->d == 1) { // Check if the activity was yesterday
        return "Yesterday, " . $date->format('g:i A'); // Format as "Yesterday, 10:30 AM"
    } elseif ($interval->days < 7) { // Check if the activity was within a week
        return $interval->days . " days ago"; // Format as "X days ago"
    } else {
        return $date->format('M j, Y'); // Format as "Jan 1, 2023" for older dates
    }
}
?>
