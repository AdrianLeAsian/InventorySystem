<?php
require_once '../config/db.php';
require_once '../vendor/autoload.php'; // Include Composer's autoloader

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'totalProcessed' => 0,
    'itemsAdded' => 0,
    'itemsSkipped' => 0,
    'skippedLogFile' => null,
    'errors' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'File upload failed or no file was uploaded.';
        echo json_encode($response);
        exit();
    }

    $fileTmpPath = $_FILES['excelFile']['tmp_name'];
    $fileName = $_FILES['excelFile']['name'];
    $fileSize = $_FILES['excelFile']['size'];
    $fileType = $_FILES['excelFile']['type'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    $allowedfileExtensions = ['xlsx', 'csv'];
    if (!in_array($fileExtension, $allowedfileExtensions)) {
        $response['message'] = 'Invalid file format. Only .xlsx and .csv files are allowed.';
        echo json_encode($response);
        exit();
    }

    $updateExisting = isset($_POST['updateExisting']) && $_POST['updateExisting'] === 'true';

    $skippedEntries = [];
    $totalProcessed = 0;
    $itemsAdded = 0;
    $itemsSkipped = 0;

    try {
        $spreadsheet = IOFactory::load($fileTmpPath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Get header row to map column names to indices
        $header = [];
        foreach ($sheet->getRowIterator(1, 1) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // This loops through all cells, even if a cell value is not set.
            foreach ($cellIterator as $cell) {
                $header[] = trim($cell->getValue());
            }
        }

        // Define required columns and their expected names
        $requiredColumns = [
            'Item Name' => 'name',
            'Quantity' => 'quantity',
            'Category' => 'category_name',
            // Add other required columns as needed, e.g., 'Unit', 'Low Stock Threshold', 'Description', 'Barcode', 'Location'
        ];

        // Map header columns to required columns
        $colMap = [];
        foreach ($requiredColumns as $excelColName => $dbColName) {
            $colIndex = array_search($excelColName, $header);
            if ($colIndex === false) {
                $response['message'] = "Missing required column: '{$excelColName}'. Please ensure your file has all required columns.";
                echo json_encode($response);
                exit();
            }
            $colMap[$dbColName] = $colIndex;
        }

        // Prepare statements outside the loop for efficiency
        $stmt_check_item = $conn->prepare("SELECT id, quantity FROM items WHERE name = ?");
        $stmt_update_item = $conn->prepare("UPDATE items SET quantity = ?, category_id = ?, barcode = ?, unit = ?, low_stock_threshold = ?, min_stock_level = ?, max_stock_level = ?, description = ?, location = ?, updated_at = NOW() WHERE id = ?");
        $stmt_insert_item = $conn->prepare("INSERT INTO items (name, category_id, barcode, quantity, unit, low_stock_threshold, min_stock_level, max_stock_level, description, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_check_category = $conn->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt_insert_category = $conn->prepare("INSERT INTO categories (name) VALUES (?)");

        for ($row = 2; $row <= $highestRow; $row++) { // Start from row 2 to skip header
            $totalProcessed++;
            $rowData = [];
            foreach ($header as $colIndex => $colName) {
                $cellValue = $sheet->getCellByColumnAndRow($colIndex + 1, $row)->getValue();
                $rowData[trim($colName)] = $cellValue;
            }

            $itemName = trim($rowData['Item Name'] ?? '');
            $quantity = filter_var($rowData['Quantity'] ?? '', FILTER_VALIDATE_INT);
            $categoryName = trim($rowData['Category'] ?? '');
            $barcode = trim($rowData['Barcode'] ?? '');
            $unit = trim($rowData['Unit'] ?? '');
            $lowStockThreshold = filter_var($rowData['Low Stock Threshold'] ?? 0, FILTER_VALIDATE_INT);
            $minStockLevel = filter_var($rowData['Min Stock Level'] ?? 0, FILTER_VALIDATE_INT);
            $maxStockLevel = filter_var($rowData['Max Stock Level'] ?? 0, FILTER_VALIDATE_INT);
            $description = trim($rowData['Description'] ?? '');
            $location = trim($rowData['Location'] ?? '');

            // Basic validation for required fields
            if (empty($itemName) || $quantity === false || $quantity < 0 || empty($categoryName)) {
                $skippedEntries[] = [
                    'row' => $row,
                    'data' => $rowData,
                    'reason' => 'Missing required fields (Item Name, Quantity, Category) or invalid Quantity.'
                ];
                $itemsSkipped++;
                continue;
            }

            // Get or create category
            $categoryId = null;
            $stmt_check_category->bind_param("s", $categoryName);
            $stmt_check_category->execute();
            $result_check_category = $stmt_check_category->get_result();
            if ($result_check_category->num_rows > 0) {
                $categoryRow = $result_check_category->fetch_assoc();
                $categoryId = $categoryRow['id'];
            } else {
                // Category does not exist, create it
                $stmt_insert_category->bind_param("s", $categoryName);
                if ($stmt_insert_category->execute()) {
                    $categoryId = $conn->insert_id;
                } else {
                    $skippedEntries[] = [
                        'row' => $row,
                        'data' => $rowData,
                        'reason' => 'Failed to create category: ' . $stmt_insert_category->error
                    ];
                    $itemsSkipped++;
                    continue;
                }
            }

            // Check for duplicate item
            $stmt_check_item->bind_param("s", $itemName);
            $stmt_check_item->execute();
            $result_check_item = $stmt_check_item->get_result();

            if ($result_check_item->num_rows > 0) {
                // Duplicate found
                if ($updateExisting) {
                    $existingItem = $result_check_item->fetch_assoc();
                    $itemIdToUpdate = $existingItem['id'];
                    // Update existing item
                    $stmt_update_item->bind_param("iisiiiiiisi", $quantity, $categoryId, $barcode, $unit, $lowStockThreshold, $minStockLevel, $maxStockLevel, $description, $location, $itemIdToUpdate);
                    if ($stmt_update_item->execute()) {
                        $itemsAdded++; // Count as added because it was processed and updated
                    } else {
                        $skippedEntries[] = [
                            'row' => $row,
                            'data' => $rowData,
                            'reason' => 'Failed to update existing item: ' . $stmt_update_item->error
                        ];
                        $itemsSkipped++;
                    }
                } else {
                    $skippedEntries[] = [
                        'row' => $row,
                        'data' => $rowData,
                        'reason' => 'Duplicate item name - not added.'
                    ];
                    $itemsSkipped++;
                }
            } else {
                // No duplicate, insert new item
                if (empty($barcode)) {
                    // Auto-generate a simple barcode (e.g., timestamp + random number)
                    $barcode = 'INV-' . time() . rand(100, 999);
                }
                $stmt_insert_item->bind_param("sisiiiiiis", $itemName, $categoryId, $barcode, $quantity, $unit, $lowStockThreshold, $minStockLevel, $maxStockLevel, $description, $location);
                if ($stmt_insert_item->execute()) {
                    $itemsAdded++;
                } else {
                    $skippedEntries[] = [
                        'row' => $row,
                        'data' => $rowData,
                        'reason' => 'Failed to insert new item: ' . $stmt_insert_item->error
                    ];
                    $itemsSkipped++;
                }
            }
        }

        $stmt_check_item->close();
        $stmt_update_item->close();
        $stmt_insert_item->close();
        $stmt_check_category->close();
        $stmt_insert_category->close();

        // Generate skipped entries log file if any
        if (!empty($skippedEntries)) {
            $logFileName = 'skipped_import_log_' . time() . '.csv';
            $logFilePath = '../temp/' . $logFileName; // Store in a temporary directory
            if (!is_dir('../temp')) {
                mkdir('../temp', 0777, true);
            }
            $logFile = fopen($logFilePath, 'w');
            fputcsv($logFile, ['Row', 'Reason', 'Item Name', 'Quantity', 'Category', 'Barcode', 'Unit', 'Low Stock Threshold', 'Min Stock Level', 'Max Stock Level', 'Description', 'Location']); // Log header
            foreach ($skippedEntries as $entry) {
                $rowOutput = [
                    $entry['row'],
                    $entry['reason'],
                    $entry['data']['Item Name'] ?? '',
                    $entry['data']['Quantity'] ?? '',
                    $entry['data']['Category'] ?? '',
                    $entry['data']['Barcode'] ?? '',
                    $entry['data']['Unit'] ?? '',
                    $entry['data']['Low Stock Threshold'] ?? '',
                    $entry['data']['Min Stock Level'] ?? '',
                    $entry['data']['Max Stock Level'] ?? '',
                    $entry['data']['Description'] ?? '',
                    $entry['data']['Location'] ?? ''
                ];
                fputcsv($logFile, $rowOutput);
            }
            fclose($logFile);
            $response['skippedLogFile'] = 'temp/' . $logFileName; // Path relative to web root
        }

        $response['success'] = true;
        $response['message'] = 'Import process completed.';
        $response['totalProcessed'] = $totalProcessed;
        $response['itemsAdded'] = $itemsAdded;
        $response['itemsSkipped'] = $itemsSkipped;

    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
        $response['message'] = 'Error reading spreadsheet: ' . $e->getMessage();
        $response['errors'][] = $e->getMessage();
    } catch (\Exception $e) {
        $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
        $response['errors'][] = $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
