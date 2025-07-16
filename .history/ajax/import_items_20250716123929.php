<?php
require_once '../config/db.php'; // Adjust path as needed

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.', 'imported_count' => 0, 'skipped_count' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'No file uploaded or an upload error occurred.';
        echo json_encode($response);
        exit();
    }

    $fileTmpPath = $_FILES['excelFile']['tmp_name'];
    $fileName = $_FILES['excelFile']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($fileExtension === 'csv') {
        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];

        if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {
            // Get header row
            $header = fgetcsv($handle, 1000, ",");
            if ($header === FALSE) {
                $response['message'] = 'Could not read CSV header.';
                echo json_encode($response);
                fclose($handle);
                exit();
            }

            // Map header columns to expected database fields
            $columnMap = [
                'name' => array_search('name', $header),
                'category' => array_search('category', $header), // Assuming category name
                'barcode' => array_search('barcode', $header),
                'quantity' => array_search('quantity', $header),
                'unit' => array_search('unit', $header),
                'low_stock_threshold' => array_search('low_stock_threshold', $header),
                'description' => array_search('description', $header),
                'location' => array_search('location', $header)
            ];

            // Check for required columns
            if ($columnMap['name'] === false || $columnMap['category'] === false || $columnMap['quantity'] === false) {
                $response['message'] = 'CSV file must contain "name", "category", and "quantity" columns.';
                echo json_encode($response);
                fclose($handle);
                exit();
            }

            // Prepare statement for inserting items
            $stmt = $conn->prepare("INSERT INTO items (name, category_id, barcode, quantity, unit, low_stock_threshold, description, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                $response['message'] = 'Database prepare failed: ' . $conn->error;
                echo json_encode($response);
                fclose($handle);
                exit();
            }

            // Prepare statement for checking duplicates
            $check_duplicate_stmt = $conn->prepare("SELECT id FROM items WHERE name = ?");
            if (!$check_duplicate_stmt) {
                $response['message'] = 'Database prepare failed for duplicate check: ' . $conn->error;
                echo json_encode($response);
                fclose($handle);
                exit();
            }

            // Prepare statement for getting category ID by name
            $get_category_id_stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
            if (!$get_category_id_stmt) {
                $response['message'] = 'Database prepare failed for category lookup: ' . $conn->error;
                echo json_encode($response);
                fclose($handle);
                exit();
            }

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $itemName = trim($data[$columnMap['name']]);
                $categoryName = trim($data[$columnMap['category']]);
                $quantity = (int)($data[$columnMap['quantity']] ?? 0);
                $barcode = trim($data[$columnMap['barcode']] ?? '');
                $unit = trim($data[$columnMap['unit']] ?? 'pcs');
                $lowStockThreshold = (int)($data[$columnMap['low_stock_threshold']] ?? 0);
                $description = trim($data[$columnMap['description']] ?? '');
                $location = trim($data[$columnMap['location']] ?? '');

                // Skip if item name or category name is empty
                if (empty($itemName) || empty($categoryName)) {
                    $skippedCount++;
                    $errors[] = "Skipped row due to empty item name or category: " . implode(', ', $data);
                    continue;
                }

                // Check for duplicate item name
                $check_duplicate_stmt->bind_param("s", $itemName);
                $check_duplicate_stmt->execute();
                $check_duplicate_stmt->store_result();
                if ($check_duplicate_stmt->num_rows > 0) {
                    $skippedCount++;
                    $errors[] = "Skipped duplicate item: " . htmlspecialchars($itemName);
                    continue;
                }

                // Get category ID
                $categoryId = null;
                $get_category_id_stmt->bind_param("s", $categoryName);
                $get_category_id_stmt->execute();
                $get_category_id_stmt->bind_result($categoryId);
                $get_category_id_stmt->fetch();
                $get_category_id_stmt->reset(); // Reset for next iteration

                if ($categoryId === null) {
                    // Category not found, skip item or create category (for now, skip)
                    $skippedCount++;
                    $errors[] = "Skipped item '" . htmlspecialchars($itemName) . "' because category '" . htmlspecialchars($categoryName) . "' does not exist.";
                    continue;
                }

                // Insert item
                $stmt->bind_param("sisiiiss", $itemName, $categoryId, $barcode, $quantity, $unit, $lowStockThreshold, $description, $location);
                if ($stmt->execute()) {
                    $importedCount++;
                } else {
                    $skippedCount++;
                    $errors[] = "Failed to import item '" . htmlspecialchars($itemName) . "': " . $stmt->error;
                }
            }
            fclose($handle);

            $stmt->close();
            $check_duplicate_stmt->close();
            $get_category_id_stmt->close();

            $response['success'] = true;
            $response['message'] = 'Import process completed.';
            $response['imported_count'] = $importedCount;
            $response['skipped_count'] = $skippedCount;
            if (!empty($errors)) {
                $response['message'] .= ' Some items were skipped.';
                $response['skipped_details'] = $errors;
            }
        } else {
            $response['message'] = 'Could not open uploaded CSV file.';
        }
    } else if ($fileExtension === 'xlsx') {
        $response['message'] = 'Excel (.xlsx) file support requires additional PHP libraries (e.g., PhpSpreadsheet) which are not currently integrated. Please upload a .csv file.';
    } else {
        $response['message'] = 'Unsupported file type. Please upload a .xlsx or .csv file.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
