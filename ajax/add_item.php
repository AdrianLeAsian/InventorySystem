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

    // Prepare an insert statement
    $sql = "INSERT INTO items (name, category_id, barcode, quantity, unit, low_stock_threshold, description, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "sisissis", $itemName, $categoryId, $barcode, $quantity, $unit, $lowStockThreshold, $description, $location);

        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = 'Item added successfully!';
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
