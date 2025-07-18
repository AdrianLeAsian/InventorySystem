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

    // Ensure low_stock_threshold is at least 1 if it's 0 or not provided
    if ($lowStockThreshold <= 0) {
        $lowStockThreshold = 10; // Set a default low stock threshold
    }
    $description = $_POST['item_description'] ?? '';
    $location = $_POST['item_location'] ?? '';

    // Basic validation: Check if item name and category are provided
    if (empty($itemName) || empty($categoryId)) {
        $response['message'] = 'Item Name and Category are required.';
        echo json_encode($response);
        exit();
    }

    // Check for duplicate barcode if provided
    if (!empty($barcode)) {
        $checkSql = "SELECT id FROM items WHERE barcode = ?";
        if ($checkStmt = mysqli_prepare($conn, $checkSql)) {
            mysqli_stmt_bind_param($checkStmt, "s", $barcode);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_store_result($checkStmt);
            if (mysqli_stmt_num_rows($checkStmt) > 0) {
                $response['message'] = 'Error: An item with this barcode already exists.';
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
    }

    // Prepare an insert statement to add the new item
    $sql = "INSERT INTO items (name, category_id, barcode, quantity, unit, low_stock_threshold, description, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        // Handle empty barcode by setting it to NULL for DB insertion if it's an empty string
        $barcodeForDb = empty($barcode) ? NULL : $barcode;
        // Bind parameters to the prepared statement
        mysqli_stmt_bind_param($stmt, "sisissis", $itemName, $categoryId, $barcodeForDb, $quantity, $unit, $lowStockThreshold, $description, $location);

        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            $newItemId = mysqli_insert_id($conn); // Get the ID of the newly inserted item

            // Fetch the newly added item's full details, including category name
            $sql_fetch_new_item = "SELECT i.id, i.name, i.category_id, i.barcode, i.quantity, i.unit, i.low_stock_threshold, i.description, i.updated_at, c.name as category_name, i.location
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
