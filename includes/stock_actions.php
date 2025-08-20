<?php
session_start(); // Start the session to access user role
include 'db.php';

// Check if user is logged in and has the 'admin' role for write operations
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Access denied. Only administrators can perform stock operations.']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

if ($action == 'update_stock') {
    $quantity = intval($_POST['quantity']);
    $is_perishable = isset($_POST['is_perishable']) ? intval($_POST['is_perishable']) : 0;
    $expiry_date = isset($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

    // Get item's current category for logging
    $item_category_stmt = $conn->prepare("SELECT c.name FROM items i JOIN categories c ON i.category_id = c.id WHERE i.id = ?");
    $item_category_stmt->bind_param('i', $item_id);
    $item_category_stmt->execute();
    $item_category_result = $item_category_stmt->get_result();
    $item_category_row = $item_category_result->fetch_assoc();
    $category_name = $item_category_row['name'];

    // Always add to item_batches for stock in
    $stmt = $conn->prepare("INSERT INTO item_batches (item_id, expiry_date, quantity) VALUES (?, ?, ?)");
    $stmt->bind_param('isi', $item_id, $expiry_date, $quantity);
    if ($stmt->execute()) {
        // Update total stock in items table
        $update_stock_stmt = $conn->prepare("UPDATE items SET current_stock = current_stock + ? WHERE id = ?");
        $update_stock_stmt->bind_param('ii', $quantity, $item_id);
        $update_stock_stmt->execute();
        $update_stock_stmt->close();
        
        // Log the action
        $log_stmt = $conn->prepare("INSERT INTO logs (item_id, action) VALUES (?, ?)");
        $log_action = 'Stocked In';
        $log_stmt->bind_param('is', $item_id, $log_action);
        $log_stmt->execute();

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add batch.']);
    }
    exit;
}

if ($action == 'reduce_stock') {
    $quantity = intval($_POST['quantity']);
    $is_perishable = isset($_POST['is_perishable']) ? intval($_POST['is_perishable']) : 0;
    $expiry_date = isset($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

    // Get item's current category for logging
    $item_category_stmt = $conn->prepare("SELECT c.name FROM items i JOIN categories c ON i.category_id = c.id WHERE i.id = ?");
    $item_category_stmt->bind_param('i', $item_id);
    $item_category_stmt->execute();
    $item_category_result = $item_category_stmt->get_result();
    $item_category_row = $item_category_result->fetch_assoc();
    $category_name = $item_category_row['name'];

    // For reducing stock, we need to consider if it's perishable or not.
    // If perishable, we should reduce from the oldest batch first (FIFO).
    // If non-perishable, we just reduce from the total stock.
    if ($is_perishable) {
        // Reduce from batches using FIFO logic
        $remaining_quantity = $quantity;
        $batches_to_update = [];

        // Get all batches for the item, ordered by expiry date (FIFO)
        $get_batches_stmt = $conn->prepare("SELECT id, quantity FROM item_batches WHERE item_id = ? ORDER BY expiry_date ASC");
        $get_batches_stmt->bind_param('i', $item_id);
        $get_batches_stmt->execute();
        $batches_result = $get_batches_stmt->get_result();

        while ($batch = $batches_result->fetch_assoc()) {
            if ($remaining_quantity <= 0) break;

            $batch_id = $batch['id'];
            $batch_quantity = $batch['quantity'];

            if ($remaining_quantity >= $batch_quantity) {
                // Consume entire batch
                $batches_to_update[] = ['id' => $batch_id, 'new_quantity' => 0];
                $remaining_quantity -= $batch_quantity;
            } else {
                // Consume part of the batch
                $batches_to_update[] = ['id' => $batch_id, 'new_quantity' => $batch_quantity - $remaining_quantity];
                $remaining_quantity = 0;
            }
        }
        $get_batches_stmt->close();

        if ($remaining_quantity > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Not enough stock in batches to reduce.']);
            exit;
        }

        $conn->begin_transaction();
        try {
            foreach ($batches_to_update as $batch_data) {
                if ($batch_data['new_quantity'] <= 0) {
                    $delete_stmt = $conn->prepare("DELETE FROM item_batches WHERE id = ?");
                    $delete_stmt->bind_param('i', $batch_data['id']);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                } else {
                    $update_batch_stmt = $conn->prepare("UPDATE item_batches SET quantity = ? WHERE id = ?");
                    $update_batch_stmt->bind_param('ii', $batch_data['new_quantity'], $batch_data['id']);
                    $update_batch_stmt->execute();
                    $update_batch_stmt->close();
                }
            }

            // Update total stock in items table
            $update_stock_stmt = $conn->prepare("UPDATE items SET current_stock = current_stock - ? WHERE id = ?");
            $update_stock_stmt->bind_param('ii', $quantity, $item_id);
            $update_stock_stmt->execute();
            $update_stock_stmt->close();

            // Log the action
            $log_stmt = $conn->prepare("INSERT INTO logs (item_id, action) VALUES (?, ?)");
            $log_action = 'Stocked Out';
            $log_stmt->bind_param('is', $item_id, $log_action);
            $log_stmt->execute();

            $conn->commit();
            echo json_encode(['status' => 'success']);
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $exception->getMessage()]);
        }
        exit;
    } else {
        // Non-perishable: just reduce stock from the main item quantity
        $stmt = $conn->prepare("UPDATE items SET current_stock = current_stock - ? WHERE id = ? AND current_stock >= ?");
        $stmt->bind_param('iii', $quantity, $item_id, $quantity);
        if ($stmt->execute()) {
            // Log the action
            $log_stmt = $conn->prepare("INSERT INTO logs (item_id, action) VALUES (?, ?)");
            $log_action = 'Stocked Out';
            $log_stmt->bind_param('is', $item_id, $log_action);
            $log_stmt->execute();

            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to reduce stock or not enough stock.']);
        }
        exit;
    }
}

if ($action == 'edit_batch') {
    $batch_id = intval($_POST['batch_id']);
    $new_expiry_date = $_POST['expiry_date'];
    $new_quantity = intval($_POST['quantity']);

    // Get current batch details to calculate stock difference
    $current_batch_stmt = $conn->prepare("SELECT item_id, quantity FROM item_batches WHERE id = ?");
    $current_batch_stmt->bind_param('i', $batch_id);
    $current_batch_stmt->execute();
    $current_batch_result = $current_batch_stmt->get_result();
    $current_batch = $current_batch_result->fetch_assoc();
    $current_batch_stmt->close();

    if ($current_batch) {
        $item_id = $current_batch['item_id'];
        $old_quantity = $current_batch['quantity'];
        $quantity_difference = $new_quantity - $old_quantity;

        // Update the batch
        $stmt = $conn->prepare("UPDATE item_batches SET expiry_date = ?, quantity = ? WHERE id = ?");
        $stmt->bind_param('sii', $new_expiry_date, $new_quantity, $batch_id);
        if ($stmt->execute()) {
            // Update total stock in items table based on quantity difference
            $update_stock_stmt = $conn->prepare("UPDATE items SET current_stock = current_stock + ? WHERE id = ?");
            $update_stock_stmt->bind_param('ii', $quantity_difference, $item_id);
            $update_stock_stmt->execute();
            $update_stock_stmt->close();

            // Log the action
            $log_stmt = $conn->prepare("INSERT INTO logs (item_id, action) VALUES (?, ?)");
            $log_action = 'Batch Edited';
            $log_stmt->bind_param('is', $item_id, $log_action);
            $log_stmt->execute();

            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update batch.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Batch not found.']);
    }
    exit;
}

if ($action == 'delete_batch') {
    $batch_id = intval($_POST['batch_id']);

    // Get batch details to reduce total stock
    $batch_stmt = $conn->prepare("SELECT item_id, quantity FROM item_batches WHERE id = ?");
    $batch_stmt->bind_param('i', $batch_id);
    $batch_stmt->execute();
    $batch_result = $batch_stmt->get_result();
    $batch_row = $batch_result->fetch_assoc();
    $batch_stmt->close();

    if ($batch_row) {
        $item_id = $batch_row['item_id'];
        $quantity_to_remove = $batch_row['quantity'];

        // Delete the batch
        $stmt = $conn->prepare("DELETE FROM item_batches WHERE id = ?");
        $stmt->bind_param('i', $batch_id);
        if ($stmt->execute()) {
            // Reduce total stock in items table
            $update_stock_stmt = $conn->prepare("UPDATE items SET current_stock = current_stock - ? WHERE id = ?");
            $update_stock_stmt->bind_param('ii', $quantity_to_remove, $item_id);
            $update_stock_stmt->execute();
            $update_stock_stmt->close();

            // Log the action
            $log_stmt = $conn->prepare("INSERT INTO logs (item_id, action) VALUES (?, ?)");
            $log_action = 'Batch Deleted';
            $log_stmt->bind_param('is', $item_id, $log_action);
            $log_stmt->execute();

            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete batch.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Batch not found.']);
    }
    exit;
}

if ($action == 'stock_out_batch') {
    $batch_id = intval($_POST['batch_id']);
    $quantity_to_stock_out = intval($_POST['quantity']);

    // Get current batch details
    $batch_stmt = $conn->prepare("SELECT item_id, quantity FROM item_batches WHERE id = ?");
    $batch_stmt->bind_param('i', $batch_id);
    $batch_stmt->execute();
    $batch_result = $batch_stmt->get_result();
    $batch_row = $batch_result->fetch_assoc();
    $batch_stmt->close();

    if ($batch_row) {
        $item_id = $batch_row['item_id'];
        $current_batch_quantity = $batch_row['quantity'];

        if ($quantity_to_stock_out > $current_batch_quantity) {
            echo json_encode(['status' => 'error', 'message' => 'Quantity to stock out exceeds batch quantity.']);
            exit;
        }

        // Update batch quantity
        $stmt = $conn->prepare("UPDATE item_batches SET quantity = quantity - ? WHERE id = ?");
        $stmt->bind_param('ii', $quantity_to_stock_out, $batch_id);
        if ($stmt->execute()) {
            // Update total stock in items table
            $update_stock_stmt = $conn->prepare("UPDATE items SET current_stock = current_stock - ? WHERE id = ?");
            $update_stock_stmt->bind_param('ii', $quantity_to_stock_out, $item_id);
            $update_stock_stmt->execute();
            $update_stock_stmt->close();

            // Delete batch if quantity becomes 0
            if (($current_batch_quantity - $quantity_to_stock_out) <= 0) {
                $delete_stmt = $conn->prepare("DELETE FROM item_batches WHERE id = ?");
                $delete_stmt->bind_param('i', $batch_id);
                $delete_stmt->execute();
                $delete_stmt->close();
            }

            // Log the action
            $log_stmt = $conn->prepare("INSERT INTO logs (item_id, action) VALUES (?, ?)");
            $log_action = 'Batch Stocked Out';
            $log_stmt->bind_param('is', $item_id, $log_action);
            $log_stmt->execute();

            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to stock out batch.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Batch not found.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
exit;
