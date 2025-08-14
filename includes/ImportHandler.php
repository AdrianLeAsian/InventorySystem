<?php
session_start();
include 'db.php';

// Ensure only admin can access this handler
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Access denied. Only administrators can perform imports.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_action'])) {
    $import_type = $_POST['import_action'];
    $user_id = $_SESSION['user_id'];
    $file_name = $_FILES['csv_file']['name'];
    $upload_status = 'failure';
    $summary_text = '';
    $errors_json = json_encode([]);

    if ($import_type === 'import_csv' && isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        $csv_file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($csv_file, "r");

        if ($handle !== FALSE) {
            $row_count = 0;
            $imported_items = 0;
            $imported_categories = 0;
            $imported_locations = 0;
            $skipped_rows = 0;
            $errors = [];

            // Skip header row
            fgetcsv($handle);

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row_count++;
                // Expected CSV columns: name, category_name, location_name, current_stock, unit, low_stock, max_stock, is_perishable, expiry_date
                if (count($data) < 9) {
                    $errors[] = ['row' => $row_count + 1, 'message' => 'Missing data fields.'];
                    $skipped_rows++;
                    continue;
                }

                $item_name = trim($data[0]);
                $category_name = trim($data[1]);
                $location_name = trim($data[2]);
                $current_stock = intval($data[3]);
                $unit = trim($data[4]);
                $low_stock = intval($data[5]);
                $max_stock = intval($data[6]);
                $is_perishable = (strtolower(trim($data[7])) === 'true' || $data[7] === '1') ? 1 : 0;
                $expiry_date = !empty(trim($data[8])) ? trim($data[8]) : NULL;

                // --- Validation ---
                $row_errors = [];

                // Validate category_id
                $category_id = null;
                $stmt_cat = $conn->prepare("SELECT id FROM categories WHERE name = ?");
                $stmt_cat->bind_param('s', $category_name);
                $stmt_cat->execute();
                $result_cat = $stmt_cat->get_result();
                if ($result_cat->num_rows > 0) {
                    $category_id = $result_cat->fetch_assoc()['id'];
                } else {
                    // Category does not exist, try to add it
                    $stmt_add_cat = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
                    $stmt_add_cat->bind_param('s', $category_name);
                    if ($stmt_add_cat->execute()) {
                        $category_id = $conn->insert_id;
                        $imported_categories++;
                    } else {
                        $row_errors[] = 'Category "' . $category_name . '" could not be added.';
                    }
                }

                // Validate location_id
                $location_id = null;
                $stmt_loc = $conn->prepare("SELECT id FROM locations WHERE name = ?");
                $stmt_loc->bind_param('s', $location_name);
                $stmt_loc->execute();
                $result_loc = $stmt_loc->get_result();
                if ($result_loc->num_rows > 0) {
                    $location_id = $result_loc->fetch_assoc()['id'];
                } else {
                    // Location does not exist, try to add it
                    $stmt_add_loc = $conn->prepare("INSERT INTO locations (name) VALUES (?)");
                    $stmt_add_loc->bind_param('s', $location_name);
                    if ($stmt_add_loc->execute()) {
                        $location_id = $conn->insert_id;
                        $imported_locations++;
                    } else {
                        $row_errors[] = 'Location "' . $location_name . '" could not be added.';
                    }
                }

                // Perishable item requires expiry_date
                if ($is_perishable && empty($expiry_date)) {
                    $row_errors[] = 'Perishable item requires an expiry date.';
                } elseif ($is_perishable && !empty($expiry_date) && !strtotime($expiry_date)) {
                    $row_errors[] = 'Invalid expiry date format.';
                }

                // Prevent duplicate name entries in items (already handled by UNIQUE constraint, but check for graceful error)
                $check_item_name = $conn->prepare("SELECT id FROM items WHERE name = ?");
                $check_item_name->bind_param('s', $item_name);
                $check_item_name->execute();
                $check_item_name->store_result();
                if ($check_item_name->num_rows > 0) {
                    $row_errors[] = 'Item name "' . $item_name . '" already exists.';
                }

                if (!empty($row_errors)) {
                    $errors[] = ['row' => $row_count + 1, 'messages' => $row_errors];
                    $skipped_rows++;
                    continue; // Skip to next row if validation fails
                }

                // --- Insert/Update Item ---
                if ($category_id && $location_id) {
                    $stmt_insert_item = $conn->prepare("INSERT INTO items (name, category_id, location_id, current_stock, unit, low_stock, max_stock, is_perishable, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt_insert_item->bind_param('sissiiiis', $item_name, $category_id, $location_id, $current_stock, $unit, $low_stock, $max_stock, $is_perishable, $expiry_date);

                    if ($stmt_insert_item->execute()) {
                        $imported_items++;
                    } else {
                        $errors[] = ['row' => $row_count + 1, 'message' => 'Failed to insert item: ' . $conn->error];
                        $skipped_rows++;
                    }
                } else {
                    $errors[] = ['row' => $row_count + 1, 'message' => 'Category or Location ID missing due to previous errors.'];
                    $skipped_rows++;
                }
            }
            fclose($handle);

            $total_processed = $row_count;
            $success_count = $imported_items;
            $failure_count = $skipped_rows;

            if ($success_count == $total_processed && $failure_count == 0) {
                $upload_status = 'success';
                $summary_text = "Successfully imported {$imported_items} items. Added {$imported_categories} new categories and {$imported_locations} new locations.";
            } elseif ($success_count > 0 && $failure_count > 0) {
                $upload_status = 'partial_success';
                $summary_text = "Partially imported. Imported {$imported_items} items, skipped {$skipped_rows} rows due to errors. Added {$imported_categories} new categories and {$imported_locations} new locations.";
            } else {
                $upload_status = 'failure';
                $summary_text = "Import failed. Skipped {$skipped_rows} rows due to errors. No items imported.";
            }
            $errors_json = json_encode($errors);

        } else {
            $summary_text = "Failed to open CSV file.";
        }
    } else {
        $summary_text = "No file uploaded or invalid file.";
    }

    // Log import history
    $stmt_log_history = $conn->prepare("INSERT INTO import_history (user_id, file_name, status, summary, errors) VALUES (?, ?, ?, ?, ?)");
    $stmt_log_history->bind_param('issss', $user_id, $file_name, $upload_status, $summary_text, $errors_json);
    $stmt_log_history->execute();
    $stmt_log_history->close();

    // Redirect back to import page with status
    header('Location: ../import.php?status=' . $upload_status . '&summary=' . urlencode($summary_text));
    exit;
}

// Handle AJAX request for import history
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_import_history') {
    $history = [];
    $stmt = $conn->prepare("SELECT ih.import_date, u.username, ih.file_name, ih.status, ih.summary, ih.errors FROM import_history ih JOIN users u ON ih.user_id = u.id ORDER BY ih.import_date DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    echo json_encode($history);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
exit;
?>
