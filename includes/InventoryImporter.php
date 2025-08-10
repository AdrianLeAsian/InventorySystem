<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

class InventoryImporter {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    public function import($filePath, $updateExisting, $fileExtension) {
        set_time_limit(0);

        $response = [
            'success' => false,
            'message' => '',
            'totalProcessed' => 0,
            'itemsAdded' => 0,
            'itemsUpdated' => 0,
            'itemsSkipped' => 0,
            'skippedLogFile' => null,
            'errors' => []
        ];

        $skippedEntries = [];
        $totalProcessed = 0;
        $itemsAdded = 0;
        $itemsUpdated = 0;
        $itemsSkipped = 0;

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            $header = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1', NULL, TRUE, FALSE)[0];
            $header = array_map('trim', $header);

            // Define expected columns
            $expectedColumns = ['Item Name', 'Category', 'Stock Quantity'];
            foreach ($expectedColumns as $col) {
                if (!in_array($col, $header)) {
                    throw new Exception("Missing required column: '{$col}'. Please ensure your file has all required columns.");
                }
            }

            // Prepare statements
            $stmt_check_item_by_barcode = $this->conn->prepare("SELECT id FROM items WHERE barcode = ?");
            $stmt_check_item_by_name = $this->conn->prepare("SELECT id FROM items WHERE name = ?");
            $stmt_update_item = $this->conn->prepare("UPDATE items SET name = ?, category_id = ?, barcode = ?, quantity = ?, unit = ?, low_stock_threshold = ?, min_stock_level = ?, max_stock_level = ?, description = ?, location = ?, updated_at = NOW() WHERE id = ?");
            $stmt_insert_item = $this->conn->prepare("INSERT INTO items (name, category_id, barcode, quantity, unit, low_stock_threshold, min_stock_level, max_stock_level, description, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_check_category = $this->conn->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt_insert_category = $this->conn->prepare("INSERT INTO categories (name) VALUES (?)");

            for ($row = 2; $row <= $highestRow; $row++) {
                $totalProcessed++;
                $rowData = array_combine($header, $sheet->rangeToArray('A' . $row . ':' . $sheet->getHighestColumn() . $row, NULL, TRUE, FALSE)[0]);

                $itemName = trim($rowData['Item Name'] ?? '');
                $categoryName = trim($rowData['Category'] ?? '');
                $quantity = filter_var($rowData['Stock Quantity'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
                
                $barcode = trim($rowData['Barcode'] ?? '');
                $unit = trim($rowData['Unit'] ?? 'pcs');
                $lowStockThreshold = filter_var($rowData['Low Stock Threshold'] ?? 0, FILTER_VALIDATE_INT);
                $minStockLevel = filter_var($rowData['Min Stock Level'] ?? 0, FILTER_VALIDATE_INT);
                $maxStockLevel = filter_var($rowData['Max Stock Level'] ?? 0, FILTER_VALIDATE_INT);
                $description = trim($rowData['Description'] ?? '');
                $location = trim($rowData['Location'] ?? '');

                if (empty($itemName) || empty($categoryName) || $quantity === false) {
                    $skippedEntries[] = ['row' => $row, 'data' => $rowData, 'reason' => 'Missing or invalid required fields (Item Name, Category, Stock Quantity).'];
                    $itemsSkipped++;
                    continue;
                }

                // Get or create category
                $stmt_check_category->bind_param("s", $categoryName);
                $stmt_check_category->execute();
                $result_category = $stmt_check_category->get_result();
                if ($result_category->num_rows > 0) {
                    $categoryId = $result_category->fetch_assoc()['id'];
                } else {
                    $stmt_insert_category->bind_param("s", $categoryName);
                    if ($stmt_insert_category->execute()) {
                        $categoryId = $this->conn->insert_id;
                    } else {
                        $skippedEntries[] = ['row' => $row, 'data' => $rowData, 'reason' => 'Failed to create new category.'];
                        $itemsSkipped++;
                        continue;
                    }
                }

                // Check for existing item
                $itemId = null;
                if (!empty($barcode)) {
                    $stmt_check_item_by_barcode->bind_param("s", $barcode);
                    $stmt_check_item_by_barcode->execute();
                    $result_item = $stmt_check_item_by_barcode->get_result();
                    if ($result_item->num_rows > 0) {
                        $itemId = $result_item->fetch_assoc()['id'];
                    }
                }
                
                if (!$itemId) {
                    $stmt_check_item_by_name->bind_param("s", $itemName);
                    $stmt_check_item_by_name->execute();
                    $result_item = $stmt_check_item_by_name->get_result();
                    if ($result_item->num_rows > 0) {
                        $itemId = $result_item->fetch_assoc()['id'];
                    }
                }

                if ($itemId) { // Item exists
                    if ($updateExisting) {
                        $stmt_update_item->bind_param("sisisiisssi", $itemName, $categoryId, $barcode, $quantity, $unit, $lowStockThreshold, $minStockLevel, $maxStockLevel, $description, $location, $itemId);
                        if ($stmt_update_item->execute()) {
                            $itemsUpdated++;
                        } else {
                            $skippedEntries[] = ['row' => $row, 'data' => $rowData, 'reason' => 'Failed to update existing item.'];
                            $itemsSkipped++;
                        }
                    } else {
                        $skippedEntries[] = ['row' => $row, 'data' => $rowData, 'reason' => 'Item already exists (skipped).'];
                        $itemsSkipped++;
                    }
                } else { // New item
                    $stmt_insert_item->bind_param("sssisiiiss", $itemName, $categoryId, $barcode, $quantity, $unit, $lowStockThreshold, $minStockLevel, $maxStockLevel, $description, $location);
                    if ($stmt_insert_item->execute()) {
                        $itemsAdded++;
                    } else {
                        $skippedEntries[] = ['row' => $row, 'data' => $rowData, 'reason' => 'Failed to insert new item.'];
                        $itemsSkipped++;
                    }
                }
            }

            $stmt_check_item_by_barcode->close();
            $stmt_check_item_by_name->close();
            $stmt_update_item->close();
            $stmt_insert_item->close();
            $stmt_check_category->close();
            $stmt_insert_category->close();

            if (!empty($skippedEntries)) {
                $logFileName = 'skipped_import_log_' . time() . '.csv';
                $logFilePath = '../temp/' . $logFileName;
                if (!is_dir('../temp')) {
                    mkdir('../temp', 0777, true);
                }
                $logFile = fopen($logFilePath, 'w');
                fputcsv($logFile, array_merge(['Row', 'Reason'], $header));
                foreach ($skippedEntries as $entry) {
                    fputcsv($logFile, array_merge([$entry['row'], $entry['reason']], $entry['data']));
                }
                fclose($logFile);
                $response['skippedLogFile'] = 'temp/' . $logFileName;
            }

            $response['success'] = true;
            $response['message'] = 'Import process completed.';
            $response['totalProcessed'] = $totalProcessed;
            $response['itemsAdded'] = $itemsAdded;
            $response['itemsUpdated'] = $itemsUpdated;
            $response['itemsSkipped'] = $itemsSkipped;

        } catch (\Exception $e) {
            $response['message'] = 'An error occurred: ' . $e->getMessage();
            $response['errors'][] = $e->getMessage();
        }

        return $response;
    }
}
