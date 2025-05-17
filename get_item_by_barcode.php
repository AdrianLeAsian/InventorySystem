<?php
require_once 'config/db.php'; // Ensure database connection is available

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'Barcode not provided.',
    'item' => null
];

if (isset($_GET['barcode'])) {
    $barcode = trim($_GET['barcode']);

    if (!empty($barcode)) {
        $sql = "SELECT id, name, quantity, unit, category_id FROM items WHERE barcode = ? LIMIT 1";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $barcode);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $name, $quantity, $unit, $category_id);
                    mysqli_stmt_fetch($stmt);
                    $response['success'] = true;
                    $response['message'] = 'Item found.';
                    $response['item'] = [
                        'id' => $id,
                        'name' => $name,
                        'quantity' => $quantity,
                        'unit' => $unit,
                        'category_id' => $category_id
                        // Add any other item details you might need on the client-side
                    ];
                } else {
                    $response['message'] = 'No item found with this barcode.';
                }
            } else {
                $response['message'] = 'Query execution failed: ' . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = 'Database query preparation failed: ' . mysqli_error($link);
        }
    } else {
        $response['message'] = 'Barcode cannot be empty.';
    }
} 

echo json_encode($response);
?> 