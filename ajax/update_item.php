<?php
require_once '../config/db.php'; // Adjust path as needed

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
    $item_name = trim($_POST['item_name'] ?? '');
    $item_category_id = filter_input(INPUT_POST, 'item_category_id', FILTER_VALIDATE_INT);
    $item_barcode = trim($_POST['item_barcode'] ?? '');
    $item_quantity = filter_input(INPUT_POST, 'item_quantity', FILTER_VALIDATE_INT);
    $item_unit = trim($_POST['item_unit'] ?? '');
    $item_low_stock_threshold = filter_input(INPUT_POST, 'item_low_stock_threshold', FILTER_VALIDATE_INT);
    $item_description = trim($_POST['item_description'] ?? '');
    $item_location = trim($_POST['item_location'] ?? '');

    // Basic validation
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
    } else {
        // Check if category exists
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

        // Update item in database
        $sql = "UPDATE items SET name = ?, category_id = ?, barcode = ?, quantity = ?, unit = ?, low_stock_threshold = ?, description = ?, location = ?, updated_at = NOW() WHERE id = ?";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sisiiissi", 
                $item_name, 
                $item_category_id, 
                $item_barcode, 
                $item_quantity, 
                $item_unit, 
                $item_low_stock_threshold, 
                $item_description, 
                $item_location, 
                $item_id
            );

            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $response['success'] = true;
                    $response['message'] = 'Item updated successfully!';
                    
                    // Fetch updated item data to send back to client for table refresh
                    $sql_fetch_updated = "SELECT i.id, i.name, i.category_id, i.barcode, i.quantity, i.unit, i.low_stock_threshold, i.description, i.location, i.updated_at, c.name as category_name
                                          FROM items i
                                          JOIN categories c ON i.category_id = c.id
                                          WHERE i.id = ?";
                    if ($stmt_fetch = mysqli_prepare($conn, $sql_fetch_updated)) {
                        mysqli_stmt_bind_param($stmt_fetch, "i", $item_id);
                        mysqli_stmt_execute($stmt_fetch);
                        $result_fetch = mysqli_stmt_get_result($stmt_fetch);
                        if ($updated_item = mysqli_fetch_assoc($result_fetch)) {
                            $response['item'] = $updated_item;
                            $response['item']['formatted_updated_at'] = format_last_activity($updated_item['updated_at']);
                        }
                        mysqli_stmt_close($stmt_fetch);
                    }
                } else {
                    $response['message'] = 'No changes made to item or item not found.';
                }
            } else {
                $response['message'] = 'Error updating item: ' . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = 'Database query preparation failed: ' . mysqli_error($conn);
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);

// Function to format timestamp for "Last Activity" - simple version
function format_last_activity($timestamp) {
    if (empty($timestamp)) return 'N/A';
    $date = new DateTime($timestamp);
    $now = new DateTime();
    $interval = $now->diff($date);

    if ($interval->d == 0 && $interval->h < 24) { // Today
        return "Today, " . $date->format('g:i A');
    } elseif ($interval->d == 1) { // Yesterday
        return "Yesterday, " . $date->format('g:i A');
    } elseif ($interval->days < 7) { // Within a week
        return $interval->days . " days ago";
    } else {
        return $date->format('M j, Y');
    }
}
?>
