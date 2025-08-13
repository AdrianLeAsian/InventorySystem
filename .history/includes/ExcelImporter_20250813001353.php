<?php
require_once 'db.php';
require_once 'auth.php';
require_once '../vendor/autoload.php'; // Composer autoloader

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ExcelImporter {
    private $conn;
    private $import_summary = [
        'locations' => ['imported' => 0, 'skipped' => 0, 'errors' => []],
        'items' => ['imported' => 0, 'skipped' => 0, 'errors' => []],
        'logs' => ['imported' => 0, 'skipped' => 0, 'errors' => []],
        'batches' => ['imported' => 0, 'skipped' => 0, 'errors' => []],
        'total_skipped_rows' => 0,
        'total_imported_rows' => 0,
        'overall_status' => 'success',
        'overall_message' => ''
    ];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function import($filePath, $fileName, $userId) {
        $spreadsheet = IOFactory::load($filePath);

        $this->conn->begin_transaction();
        try {
            $this->processLocationsSheet($spreadsheet);
            $this->processItemsSheet($spreadsheet);
            $this->processLogsSheet($spreadsheet);
            $this->processBatchesSheet($spreadsheet);

            $this->conn->commit();
            $this->import_summary['overall_status'] = 'success';
            $this->import_summary['overall_message'] = 'Excel import completed successfully.';
        } catch (Exception $e) {
            $this->conn->rollback();
            $this->import_summary['overall_status'] = 'failure';
            $this->import_summary['overall_message'] = 'Excel import failed: ' . $e->getMessage();
            error_log("Excel Import Error: " . $e->getMessage());
        } finally {
            $this->logImportHistory($userId, $fileName);
        }

        return $this->import_summary;
    }

    private function processLocationsSheet($spreadsheet) {
        if (!$spreadsheet->sheetNameExists('Locations')) {
            return;
        }
        $sheet = $spreadsheet->getSheetByName('Locations');
        $highestRow = $sheet->getHighestRow();
        $headers = $this->getRowData($sheet, 1); // Assuming headers are in the first row

        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = $this->getRowData($sheet, $row);
            $locationData = array_combine($headers, $rowData);

            $name = trim($locationData['name'] ?? '');

            if (empty($name)) {
                $this->import_summary['locations']['skipped']++;
                $this->import_summary['locations']['errors'][] = "Row {$row}: Location name is empty.";
                $this->import_summary['total_skipped_rows']++;
                continue;
            }

            // Check if location exists (UPSERT logic)
            $locationId = $this->getLocationId($name);

            if ($locationId) {
                // Location exists, skip or update if needed (for now, just skip as name is unique)
                $this->import_summary['locations']['skipped']++;
                $this->import_summary['locations']['errors'][] = "Row {$row}: Location '{$name}' already exists. Skipped.";
                $this->import_summary['total_skipped_rows']++;
            } else {
                // Insert new location
                $stmt = $this->conn->prepare("INSERT INTO locations (name) VALUES (?)");
                $stmt->bind_param("s", $name);
                if ($stmt->execute()) {
                    $this->import_summary['locations']['imported']++;
                    $this->import_summary['total_imported_rows']++;
                } else {
                    $this->import_summary['locations']['skipped']++;
                    $this->import_summary['locations']['errors'][] = "Row {$row}: Failed to insert location '{$name}'. Error: " . $stmt->error;
                    $this->import_summary['total_skipped_rows']++;
                }
                $stmt->close();
            }
        }
    }

    private function processItemsSheet($spreadsheet) {
        if (!$spreadsheet->sheetNameExists('Items')) {
            return;
        }
        $sheet = $spreadsheet->getSheetByName('Items');
        $highestRow = $sheet->getHighestRow();
        $headers = $this->getRowData($sheet, 1);

        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = $this->getRowData($sheet, $row);
            $itemData = array_combine($headers, $rowData);

            $name = trim($itemData['name'] ?? '');
            $categoryName = trim($itemData['category'] ?? '');
            $locationName = trim($itemData['location'] ?? '');
            $currentStock = (int)($itemData['current_stock'] ?? 0);
            $unit = trim($itemData['unit'] ?? '');
            $lowStock = (int)($itemData['low_stock'] ?? 0);
            $maxStock = (int)($itemData['max_stock'] ?? 0);
            $isPerishable = filter_var($itemData['is_perishable'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $expiryDate = null;

            if ($isPerishable && !empty($itemData['expiry_date'])) {
                try {
                    if (is_numeric($itemData['expiry_date'])) {
                        $expiryDate = Date::excelToDateTimeObject($itemData['expiry_date'])->format('Y-m-d');
                    } else {
                        $expiryDate = date('Y-m-d', strtotime($itemData['expiry_date']));
                    }
                } catch (Exception $e) {
                    $this->import_summary['items']['skipped']++;
                    $this->import_summary['items']['errors'][] = "Row {$row}: Invalid expiry date format for item '{$name}'. Expected YYYY-MM-DD. Skipped.";
                    $this->import_summary['total_skipped_rows']++;
                    continue;
                }
            }

            if (empty($name) || empty($categoryName) || empty($locationName) || empty($unit)) {
                $this->import_summary['items']['skipped']++;
                $this->import_summary['items']['errors'][] = "Row {$row}: Missing required fields (name, category, location, unit) for item '{$name}'. Skipped.";
                $this->import_summary['total_skipped_rows']++;
                continue;
            }

            // Get or create category
            $categoryId = $this->getOrCreateCategory($categoryName);
            if (!$categoryId) {
                $this->import_summary['items']['skipped']++;
                $this->import_summary['items']['errors'][] = "Row {$row}: Failed to get or create category '{$categoryName}' for item '{$name}'. Skipped.";
                $this->import_summary['total_skipped_rows']++;
                continue;
            }

            // Get or create location
            $locationId = $this->getOrCreateLocation($locationName);
            if (!$locationId) {
                $this->import_summary['items']['skipped']++;
                $this->import_summary['items']['errors'][] = "Row {$row}: Failed to get or create location '{$locationName}' for item '{$name}'. Skipped.";
                $this->import_summary['total_skipped_rows']++;
                continue;
            }

            // UPSERT logic for items
            $itemId = $this->getItemId($name);

            if ($itemId) {
                // Update existing item
                $stmt = $this->conn->prepare("UPDATE items SET category_id=?, location_id=?, current_stock=?, unit=?, low_stock=?, max_stock=?, is_perishable=?, expiry_date=? WHERE id=?");
                $stmt->bind_param("iiiissssi", $categoryId, $locationId, $currentStock, $unit, $lowStock, $maxStock, $isPerishable, $expiryDate, $itemId);
                if ($stmt->execute()) {
                    $this->import_summary['items']['imported']++;
                    $this->import_summary['total_imported_rows']++;
                    // If item is perishable and updated, clear existing batches to re-import from Batches sheet
                    if ($isPerishable) {
                        $this->clearItemBatches($itemId);
                    }
                } else {
                    $this->import_summary['items']['skipped']++;
                    $this->import_summary['items']['errors'][] = "Row {$row}: Failed to update item '{$name}'. Error: " . $stmt->error;
                    $this->import_summary['total_skipped_rows']++;
                }
                $stmt->close();
            } else {
                // Insert new item
                $stmt = $this->conn->prepare("INSERT INTO items (name, category_id, location_id, current_stock, unit, low_stock, max_stock, is_perishable, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("siiiisssi", $name, $categoryId, $locationId, $currentStock, $unit, $lowStock, $maxStock, $isPerishable, $expiryDate);
                if ($stmt->execute()) {
                    $this->import_summary['items']['imported']++;
                    $this->import_summary['total_imported_rows']++;
                } else {
                    $this->import_summary['items']['skipped']++;
                    $this->import_summary['items']['errors'][] = "Row {$row}: Failed to insert item '{$name}'. Error: " . $stmt->error;
                    $this->import_summary['total_skipped_rows']++;
                }
                $stmt->close();
            }
        }
    }

    private function processLogsSheet($spreadsheet) {
        if (!$spreadsheet->sheetNameExists('Logs')) {
            return;
        }
        $sheet = $spreadsheet->getSheetByName('Logs');
        $highestRow = $sheet->getHighestRow();
        $headers = $this->getRowData($sheet, 1);

        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = $this->getRowData($sheet, $row);
            $logData = array_combine($headers, $rowData);

            $itemName = trim($logData['item_name'] ?? '');
            $action = trim($logData['action'] ?? '');
            $category = trim($logData['category'] ?? '');
            $dateTime = null;

            if (!empty($logData['date_time'])) {
                try {
                    if (is_numeric($logData['date_time'])) {
                        $dateTime = Date::excelToDateTimeObject($logData['date_time'])->format('Y-m-d H:i:s');
                    } else {
                        $dateTime = date('Y-m-d H:i:s', strtotime($logData['date_time']));
                    }
                } catch (Exception $e) {
                    $this->import_summary['logs']['skipped']++;
                    $this->import_summary['logs']['errors'][] = "Row {$row}: Invalid date_time format for log. Expected YYYY-MM-DD HH:MM:SS. Skipped.";
                    $this->import_summary['total_skipped_rows']++;
                    continue;
                }
            } else {
                $dateTime = date('Y-m-d H:i:s'); // Default to current time if not provided
            }

            if (empty($itemName) || empty($action) || empty($category)) {
                $this->import_summary['logs']['skipped']++;
                $this->import_summary['logs']['errors'][] = "Row {$row}: Missing required fields (item_name, action, category) for log. Skipped.";
                $this->import_summary['total_skipped_rows']++;
                continue;
            }

            $itemId = $this->getItemId($itemName);
            if (!$itemId) {
                $this->import_summary['logs']['skipped']++;
                $this->import_summary['logs']['errors'][] = "Row {$row}: Item '{$itemName}' not found for log. Skipped.";
                $this->import_summary['total_skipped_rows']++;
                continue;
            }

            $stmt = $this->conn->prepare("INSERT INTO logs (item_id, action, category, date_time) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $itemId, $action, $category, $dateTime);
            if ($stmt->execute()) {
                $this->import_summary['logs']['imported']++;
                $this->import_summary['total_imported_rows']++;
            } else {
                $this->import_summary['logs']['skipped']++;
                $this->import_summary['logs']['errors'][] = "Row {$row}: Failed to insert log for item '{$itemName}'. Error: " . $stmt->error;
                $this->import_summary['total_skipped_rows']++;
            }
            $stmt->close();
        }
    }

    private function processBatchesSheet($spreadsheet) {
        if (!$spreadsheet->sheetNameExists('Batches')) {
            return;
        }
        $sheet = $spreadsheet->getSheetByName('Batches');
        $highestRow = $sheet->getHighestRow();
        $headers = $this->getRowData($sheet, 1);

        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = $this->getRowData($sheet, $row);
            $batchData = array_combine($headers, $rowData);

            $itemName = trim($batchData['item_name'] ?? '');
            $expiryDate = null;
            $quantity = (int)($batchData['quantity'] ?? 0);

            if (!empty($batchData['expiry_date'])) {
                try {
                    if (is_numeric($batchData['expiry_date'])) {
                        $expiryDate = Date::excelToDateTimeObject($batchData['expiry_date'])->format('Y-m-d');
                    } else {
                        $expiryDate = date('Y-m-d', strtotime($batchData['expiry_date']));
                    }
                } catch (Exception $e) {
                    $this->import_summary['batches']['skipped']++;
                    $this->import_summary['batches']['errors'][] = "Row {$row}: Invalid expiry date format for batch. Expected YYYY-MM-DD. Skipped.";
                    $this->import_summary['total_skipped_rows']++;
                    continue;
                }
            }

            if (empty($itemName) || empty($expiryDate) || $quantity <= 0) {
                $this->import_summary['batches']['skipped']++;
                $this->import_summary['batches']['errors'][] = "Row {$row}: Missing required fields (item_name, expiry_date, quantity > 0) for batch. Skipped.";
                $this->import_summary['total_skipped_rows']++;
                continue;
            }

            $itemId = $this->getItemId($itemName);
            if (!$itemId) {
                $this->import_summary['batches']['skipped']++;
                $this->import_summary['batches']['errors'][] = "Row {$row}: Item '{$itemName}' not found for batch. Skipped.";
                $this->import_summary['total_skipped_rows']++;
                continue;
            }

            // Check if item is perishable
            $isPerishable = $this->isItemPerishable($itemId);
            if (!$isPerishable) {
                $this->import_summary['batches']['skipped']++;
                $this->import_summary['batches']['errors'][] = "Row {$row}: Item '{$itemName}' is not marked as perishable. Skipped batch.";
                $this->import_summary['total_skipped_rows']++;
                continue;
            }

            $stmt = $this->conn->prepare("INSERT INTO item_batches (item_id, expiry_date, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $itemId, $expiryDate, $quantity);
            if ($stmt->execute()) {
                $this->import_summary['batches']['imported']++;
                $this->import_summary['total_imported_rows']++;
            } else {
                $this->import_summary['batches']['skipped']++;
                $this->import_summary['batches']['errors'][] = "Row {$row}: Failed to insert batch for item '{$itemName}'. Error: " . $stmt->error;
                $this->import_summary['total_skipped_rows']++;
            }
            $stmt->close();
        }
    }

    private function getRowData($sheet, $row) {
        $rowData = [];
        $highestColumn = $sheet->getHighestColumn();
        for ($col = 'A'; $col <= $highestColumn; $col++) {
            $cellValue = $sheet->getCell($col . $row)->getValue();
            $rowData[] = $cellValue;
        }
        return $rowData;
    }

    private function getLocationId($name) {
        $stmt = $this->conn->prepare("SELECT id FROM locations WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ? $row['id'] : null;
    }

    private function getOrCreateCategory($name) {
        $categoryId = null;
        $stmt = $this->conn->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $categoryId = $row['id'];
        } else {
            $stmt->close();
            $stmt = $this->conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                $categoryId = $this->conn->insert_id;
            } else {
                error_log("Failed to create category '{$name}': " . $stmt->error);
            }
        }
        $stmt->close();
        return $categoryId;
    }

    private function getOrCreateLocation($name) {
        $locationId = null;
        $stmt = $this->conn->prepare("SELECT id FROM locations WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $locationId = $row['id'];
        } else {
            $stmt->close();
            $stmt = $this->conn->prepare("INSERT INTO locations (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                $locationId = $this->conn->insert_id;
            } else {
                error_log("Failed to create location '{$name}': " . $stmt->error);
            }
        }
        $stmt->close();
        return $locationId;
    }

    private function getItemId($name) {
        $stmt = $this->conn->prepare("SELECT id FROM items WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ? $row['id'] : null;
    }

    private function isItemPerishable($itemId) {
        $stmt = $this->conn->prepare("SELECT is_perishable FROM items WHERE id = ?");
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ? (bool)$row['is_perishable'] : false;
    }

    private function clearItemBatches($itemId) {
        $stmt = $this->conn->prepare("DELETE FROM item_batches WHERE item_id = ?");
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $stmt->close();
    }

    private function logImportHistory($userId, $fileName) {
        $status = $this->import_summary['overall_status'];
        $summary = json_encode($this->import_summary); // Store full summary as JSON
        $errors = json_encode(array_merge(
            $this->import_summary['locations']['errors'],
            $this->import_summary['items']['errors'],
            $this->import_summary['logs']['errors'],
            $this->import_summary['batches']['errors']
        ));

        $stmt = $this->conn->prepare("INSERT INTO import_history (user_id, file_name, status, summary, errors) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $userId, $fileName, $status, $summary, $errors);
        $stmt->execute();
        $stmt->close();
    }
}
