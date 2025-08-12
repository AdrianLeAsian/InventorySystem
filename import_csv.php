<?php
session_start(); // Start the session at the very beginning

include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');

    if ($handle) {
        // Skip the header row
        fgetcsv($handle);

        $imported_count = 0;
        $skipped_count = 0;
        $errors = [];

        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            // Ensure row has enough columns
            if (count($data) < 10) {
                $skipped_count++;
                $errors[] = "Skipped row due to insufficient columns (expected 10, got " . count($data) . "): " . implode(",", $data);
                continue;
            }

            // Assuming CSV columns are: ID, Name, Category, Location, Current Stock, Unit, Low Stock, Max Stock, Is Perishable, Expiry Date
            // We skip ID (data[0]) as it's auto-incremented and not used for matching from CSV
            $name = trim($data[1]);
            $category_name = trim($data[2]);
            $location_name = trim($data[3]);
            $current_stock = (int)$data[4];
            $unit = trim($data[5]);
            $low_stock = (int)$data[6];
            $max_stock = (int)$data[7];
            $is_perishable = (strtolower(trim($data[8])) === 'yes') ? 1 : 0;
            $expiry_date = !empty(trim($data[9])) ? trim($data[9]) : null; // Allow null for non-perishable

            // Validate expiry date format if perishable
            if ($is_perishable && $expiry_date && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $expiry_date)) {
                $skipped_count++;
                $errors[] = "Skipped row for item '{$name}' due to invalid expiry date format (YYYY-MM-DD required): " . implode(",", $data);
                continue;
            }

            // Validate stock quantities
            if ($current_stock < 0 || $low_stock < 0 || $max_stock < 0) {
                $skipped_count++;
                $errors[] = "Skipped row for item '{$name}' due to negative stock value: " . implode(",", $data);
                error_log("Skipped row for item '{$name}' due to negative stock value.");
                continue;
            }

            // Get or create category_id
            $catStmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
            $catStmt->bind_param('s', $category_name);
            $catStmt->execute();
            $catResult = $catStmt->get_result();
            $category_id = $catResult->fetch_assoc()['id'] ?? null;
            $catStmt->close();

            if (!$category_id) {
                $insertCatStmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
                $insertCatStmt->bind_param('s', $category_name);
                if ($insertCatStmt->execute()) {
                    $category_id = $conn->insert_id;
                    error_log("New category '{$category_name}' added with ID: {$category_id}");
                } else {
                    $errors[] = "Failed to add new category '{$category_name}' (DB error): " . $insertCatStmt->error;
                    error_log("Failed to add new category: " . $insertCatStmt->error . " - Category: " . $category_name);
                    $skipped_count++;
                    continue; // Skip row if category cannot be added
                }
                $insertCatStmt->close();
            }

            // Get or create location_id
            $locStmt = $conn->prepare("SELECT id FROM locations WHERE name = ?");
            $locStmt->bind_param('s', $location_name);
            $locStmt->execute();
            $locResult = $locStmt->get_result();
            $location_id = $locResult->fetch_assoc()['id'] ?? null;
            $locStmt->close();

            if (!$location_id) {
                $insertLocStmt = $conn->prepare("INSERT INTO locations (name) VALUES (?)");
                $insertLocStmt->bind_param('s', $location_name);
                if ($insertLocStmt->execute()) {
                    $location_id = $conn->insert_id;
                    error_log("New location '{$location_name}' added with ID: {$location_id}");
                } else {
                    $errors[] = "Failed to add new location '{$location_name}' (DB error): " . $insertLocStmt->error;
                    error_log("Failed to add new location: " . $insertLocStmt->error . " - Location: " . $location_name);
                    $skipped_count++;
                    continue; // Skip row if location cannot be added
                }
                $insertLocStmt->close();
            }

            error_log("Processing row: " . implode(",", $data));
            error_log("Category Name: {$category_name}, Resolved ID: {$category_id}");
            error_log("Location Name: {$location_name}, Resolved ID: {$location_id}");

            // Check if item already exists by name
            $checkStmt = $conn->prepare("SELECT id FROM items WHERE name = ?");
            $checkStmt->bind_param('s', $name);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $existing_item = $checkResult->fetch_assoc();
            $checkStmt->close();

                if ($existing_item) {
                    error_log("Item '{$name}' found, attempting UPDATE.");
                    // Item exists, perform UPDATE
                    $item_id = $existing_item['id'];
                    $updateStmt = $conn->prepare("UPDATE items SET category_id=?, location_id=?, current_stock=?, unit=?, low_stock=?, max_stock=?, is_perishable=?, expiry_date=? WHERE id=?");
                    // Debugging: Log parameters before binding
                    error_log("UPDATE Params: cat_id={$category_id}, loc_id={$location_id}, stock={$current_stock}, unit={$unit}, low={$low_stock}, max={$max_stock}, perishable={$is_perishable}, expiry={$expiry_date}, id={$item_id}");
                    $updateStmt->bind_param('iiisiiisi', $category_id, $location_id, $current_stock, $unit, $low_stock, $max_stock, $is_perishable, $expiry_date, $item_id);
                    if ($updateStmt->execute()) {
                        $imported_count++;
                        $imported_count++;
                        error_log("Item '{$name}' updated successfully.");

                        // Log the update for auditing purposes
                        $logAction = "Updated from CSV";
                        $logCategory = "Stock Update";
                        $logDateTime = date('Y-m-d H:i:s'); // Get current timestamp

                        $logStmt = $conn->prepare("INSERT INTO logs (item_id, action, category, date_time) VALUES (?, ?, ?, ?)");
                        $logStmt->bind_param('isss', $item_id, $logAction, $logCategory, $logDateTime);
                        if (!$logStmt->execute()) {
                            // Log any error in logging the update itself
                            error_log("Failed to log update for item '{$name}' (DB error): " . $logStmt->error);
                        }
                        $logStmt->close();

                        // If perishable, update/add to item_batches
                        if ($is_perishable) {
                            error_log("Item '{$name}' is perishable, updating item_batches.");
                            // For simplicity, delete existing batches and insert new one from CSV
                            $deleteBatchesStmt = $conn->prepare("DELETE FROM item_batches WHERE item_id = ?");
                            $deleteBatchesStmt->bind_param('i', $item_id);
                            if (!$deleteBatchesStmt->execute()) {
                                $errors[] = "Failed to delete old item_batches for '{$name}' (DB error): " . $deleteBatchesStmt->error;
                                error_log("Failed to delete old item_batches: " . $deleteBatchesStmt->error . " - Item: " . $name);
                            }
                            $deleteBatchesStmt->close();

                            $insertBatchStmt = $conn->prepare("INSERT INTO item_batches (item_id, quantity, expiry_date) VALUES (?, ?, ?)");
                            $insertBatchStmt->bind_param('iis', $item_id, $current_stock, $expiry_date);
                            if (!$insertBatchStmt->execute()) {
                                $errors[] = "Failed to insert new item_batches for '{$name}' (DB error): " . $insertBatchStmt->error;
                                error_log("Failed to insert new item_batches: " . $insertBatchStmt->error . " - Item: " . $name);
                            }
                            $insertBatchStmt->close();
                        }
                    } else {
                        $skipped_count++;
                        $errors[] = "Failed to update row for '{$name}' (DB error): " . $updateStmt->error . " - Data: " . implode(",", $data);
                        error_log("Failed to update row: " . $updateStmt->error . " - Data: " . implode(",", $data));
                    }
                    $updateStmt->close();
                } else {
                    error_log("Item '{$name}' not found, attempting INSERT.");
                    // Item does not exist, perform INSERT
                    $insertStmt = $conn->prepare("INSERT INTO items (name, category_id, location_id, current_stock, unit, low_stock, max_stock, is_perishable, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    // Debugging: Log parameters before binding
                    error_log("INSERT Params: name={$name}, cat_id={$category_id}, loc_id={$location_id}, stock={$current_stock}, unit={$unit}, low={$low_stock}, max={$max_stock}, perishable={$is_perishable}, expiry={$expiry_date}");
                    $insertStmt->bind_param('siiisiiis', $name, $category_id, $location_id, $current_stock, $unit, $low_stock, $max_stock, $is_perishable, $expiry_date);
                    if ($insertStmt->execute()) {
                        $imported_count++;
                        $new_item_id = $conn->insert_id;
                        error_log("Item '{$name}' inserted successfully with ID: {$new_item_id}.");
                        // If perishable, add to item_batches
                        if ($is_perishable) {
                            error_log("Item '{$name}' is perishable, inserting into item_batches.");
                            $insertBatchStmt = $conn->prepare("INSERT INTO item_batches (item_id, quantity, expiry_date) VALUES (?, ?, ?)");
                            $insertBatchStmt->bind_param('iis', $new_item_id, $current_stock, $expiry_date);
                            if (!$insertBatchStmt->execute()) {
                                $errors[] = "Failed to insert item_batches for '{$name}' (DB error): " . $insertBatchStmt->error;
                                error_log("Failed to insert item_batches: " . $insertBatchStmt->error . " - Item: " . $name);
                            }
                            $insertBatchStmt->close();
                        }
                    } else {
                        $skipped_count++;
                        $errors[] = "Failed to insert row for '{$name}' (DB error): " . $insertStmt->error . " - Data: " . implode(",", $data);
                        error_log("Failed to insert row: " . $insertStmt->error . " - Data: " . implode(",", $data));
                    }
                    $insertStmt->close();
                }
            } else {
                $skipped_count++;
                $error_msg = "Skipped row for item '{$name}' due to missing category ('{$category_name}') or location ('{$location_name}'): " . implode(",", $data);
                $errors[] = $error_msg;
                error_log($error_msg);
            }
        }
        fclose($handle);

        $message = "CSV import complete. Imported: {$imported_count} rows. Skipped: {$skipped_count} rows.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode("; ", $errors);
            echo json_encode(['status' => 'warning', 'message' => $message, 'errors' => $errors]);
        } else {
            echo json_encode(['status' => 'success', 'message' => $message]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error opening CSV file.']);
    }
    exit();
}
?>
