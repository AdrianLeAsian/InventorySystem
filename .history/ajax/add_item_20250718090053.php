<?php
/**
 * add_item.php
 *
 * This script handles the AJAX request for adding a new item to the inventory.
 * It performs validation, checks for duplicate barcodes, inserts the item into the database,
 * and returns a JSON response indicating success or failure, along with the newly added item's details.
 */

// Disable display errors and set error reporting for production environment
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Include the database configuration file
require_once '../config/db.php'; // Adjust path as needed

// Log that the script has been reached (for debugging purposes)
file_put_contents('../logs/reached_add_item.log', 'Script reached here.');

// Set the content type to JSON for the response
header('Content-Type: application/json');

// Initialize the response array
$response = ['success' => false, 'message' => ''];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve input data from the POST request
    $itemName = $_POST['item_name'] ?? '';
    $categoryId = $_POST['item_category_id'] ?? '';
    $barcode = $_POST['item_barcode'] ?? '';
    $quantity = $_POST['item_quantity'] ?? 0;
    $unit = $_POST['item_unit'] ?? 'pcs';
    $lowStockThreshold = $_POST['item_low_stock_threshold'] ?? 0;
    $minStockLevel = $_POST['item_min_stock_level'] ?? 0;
    $maxStockLevel = $_POST['item_max_stock_level'] ?? 0;
    $description = $_POST['item_description'] ?? '';
    $location = $_POST['item_location'] ?? '';
    $reason = $_POST['item_reason'] ?? ''; // New reason field

    // Ensure low_stock_threshold is at least 1 if it's 0 or not provided
    if ($lowStockThreshold <= 0) {
        $lowStockThreshold = 10; // Set a default low stock threshold
    }

    // Basic validation: Check if item name, category, quantity, and reason are provided
    if (empty($itemName) || empty($categoryId) || $quantity === '' || empty($reason)) {
        $response['message'] = 'Item Name, Category, Quantity, and Reason are required.';
        echo json_encode($response);
        exit();
    }

    // Generate barcode if not provided
    if (empty($barcode)) {
        // Generate a simple unique ID (e.g., UUID v4)
        // For a more robust solution, consider a dedicated barcode generation library
        $barcode = uniqid('ITEM-', true); 
    }

    // Check for duplicate barcode
    $checkSql = "SELECT id FROM items WHERE barcode = ?";
    if ($checkStmt = mysqli_prepare($conn, $checkSql)) {
        mysqli_stmt_bind_param($checkStmt, "s", $barcode);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);
        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            $response['message'] = 'Error: An item with this barcode already exists. Please provide a unique barcode or leave it empty to auto-generate.';
            echo json_encode($response);
            mysqli_stmt_close($checkStmt);
            exit();
        }
        mysqli_stmt_close($checkStmt);
    } else {
        $response['message'] = 'Database prepare failed for barcode check: ' . mysqli_error($conn);
        echo json_encode($response);
        exit();
    }

    // Prepare an insert statement to add the new item
    $sql = "INSERT INTO items (name, category_id, barcode, quantity, unit, low_stock_threshold, min_stock_level, max_stock_level, description, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        // Bind parameters to the prepared statement
        mysqli_stmt_bind_param($stmt, "sisissiiis", $itemName, $categoryId, $barcode, $quantity, $unit, $lowStockThreshold, $minStockLevel, $maxStockLevel, $description, $location);

        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            $newItemId = mysqli_insert_id($conn); // Get the ID of the newly inserted item

            // Log the item addition in inventory_log
            $logSql = "INSERT INTO inventory_log (item_id, type, quantity_change, reason) VALUES (?, 'in', ?, ?)";
            if ($logStmt = mysqli_prepare($conn, $logSql)) {
                mysqli_stmt_bind_param($logStmt, "iis", $newItemId, $quantity, $reason);
                mysqli_stmt_execute($logStmt);
                mysqli_stmt_close($logStmt);
            } else {
                // Log error but don't stop the main process
                error_log("Error logging item addition to inventory_log: " . mysqli_error($conn));
            }

            // Log the item addition in activity_log
            $activityLogSql = "INSERT INTO activity_log (activity_type, entity_type, entity_id, entity_name, reason) VALUES (?, ?, ?, ?, ?)";
            if ($activityLogStmt = mysqli_prepare($conn, $activityLogSql)) {
                $activityType = 'item_added';
                $entityType = 'item';
                mysqli_stmt_bind_param($activityLogStmt, "ssiss", $activityType, $entityType, $newItemId, $itemName, $reason);
                mysqli_stmt_execute($activityLogStmt);
                mysqli_stmt_close($activityLogStmt);
            } else {
                error_log("Error logging item addition to activity_log: " . mysqli_error($conn));
            }

            // Fetch the newly added item's full details, including category name
            $sql_fetch_new_item = "SELECT i.id, i.name, i.category_id, i.barcode, i.quantity, i.unit, i.low_stock_threshold, i.min_stock_level, i.max_stock_level, i.description, i.updated_at, c.name as category_name, i.location
                                   FROM items i
                                   JOIN categories c ON i.category_id = c.id
                                   WHERE i.id = ?";
            if ($stmt_fetch = mysqli_prepare($conn, $sql_fetch_new_item)) {
                mysqli_stmt_bind_param($stmt_fetch, "i", $newItemId);
                mysqli_stmt_execute($stmt_fetch);
                $result_fetch = mysqli_stmt_get_result($stmt_fetch);
                $newItem = mysqli_fetch_assoc($result_fetch);
                mysqli_stmt_close($stmt_fetch);

                // Format updated_at for client-side display (e.g., "Today, 10:30 AM", "Yesterday", "3 days ago")
                if ($newItem && isset($newItem['updated_at'])) {
                    $date = new DateTime($newItem['updated_at']);
                    $now = new DateTime();
                    $interval = $now->diff($date);

                    if ($interval->d == 0 && $interval->h < 24) { // Today
                        $newItem['formatted_updated_at'] = "Today, " . $date->format('g:i A');
                    } elseif ($interval->d == 1) { // Yesterday
                        $newItem['formatted_updated_at'] = "Yesterday, " . $date->format('g:i A');
                    } elseif ($interval->days < 7) { // Within a week
                        $newItem['formatted_updated_at'] = $interval->days . " days ago";
                    } else {
                        $newItem['formatted_updated_at'] = $date->format('M j, Y');
                    }
                } else {
                    $newItem['formatted_updated_at'] = 'N/A';
                }

                $response['success'] = true;
                $response['message'] = 'Item added successfully!';
                $response['item'] = $newItem; // Include the new item data in the response
            } else {
                $response['message'] = 'Error fetching new item details: ' . mysqli_error($conn);
            }
        } else {
            $errorMessage = 'Error adding item: ' . mysqli_stmt_error($stmt);
            $response['message'] = $errorMessage;
        }
        mysqli_stmt_close($stmt); // Close the insert statement
    } else {
        $response['message'] = 'Database prepare failed: ' . mysqli_error($conn);
    }
} else {
    // If the request method is not POST
    $response['message'] = 'Invalid request method.';
}

// Encode the response array to JSON and output it
echo json_encode($response);
?>
