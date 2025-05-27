<?php
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once '../config/db.php'; // Adjust path as needed
file_put_contents('../logs/reached_add_item.log', 'Script reached here.');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemName = $_POST['item_name'] ?? '';
    $categoryId = $_POST['item_category_id'] ?? '';
    $barcode = $_POST['item_barcode'] ?? '';
    $quantity = $_POST['item_quantity'] ?? 0;
    $unit = $_POST['item_unit'] ?? 'pcs';
    $lowStockThreshold = $_POST['item_low_stock_threshold'] ?? 0;
    $description = $_POST['item_description'] ?? '';
    $location = $_POST['item_location'] ?? '';

    // Basic validation
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

    // Prepare an insert statement
    $sql = "INSERT INTO items (name, category_id, barcode, quantity, unit, low_stock_threshold, description, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        // Handle empty barcode by setting it to NULL for DB insertion if it's an empty string
        $barcodeForDb = empty($barcode) ? NULL : $barcode;
        mysqli_stmt_bind_param($stmt, "sisissis", $itemName, $categoryId, $barcodeForDb, $quantity, $unit, $lowStockThreshold, $description, $location);

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

                // Format updated_at for client-side display
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
            error_log($errorMessage . "\n", 3, "../logs/debug.log"); // Log to file
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'Database prepare failed: ' . mysqli_error($conn);
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
