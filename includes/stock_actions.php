<?php
include 'db.php';
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

    if ($is_perishable && $expiry_date) {
        // Add batch for perishable item
        $stmt = $conn->prepare("INSERT INTO item_batches (item_id, expiry_date, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param('isi', $item_id, $expiry_date, $quantity);
        if ($stmt->execute()) {
            // Update total stock in items table
            $conn->query("UPDATE items SET current_stock = current_stock + $quantity WHERE id = $item_id");
            
            // Log the action
            $log_stmt = $conn->prepare("INSERT INTO logs (item_id, action, category) VALUES (?, ?, ?)");
            $log_action = 'Stocked In';
            $log_stmt->bind_param('iss', $item_id, $log_action, $category_name);
            $log_stmt->execute();

            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add batch.']);
        }
        exit;
    } else {
        // Non-perishable: just update stock
        $stmt = $conn->prepare("UPDATE items SET current_stock = current_stock + ? WHERE id = ?");
        $stmt->bind_param('ii', $quantity, $item_id);
        if ($stmt->execute()) {
            // Log the action
            $log_stmt = $conn->prepare("INSERT INTO logs (item_id, action, category) VALUES (?, ?, ?)");
            $log_action = 'Stocked In';
            $log_stmt->bind_param('iss', $item_id, $log_action, $category_name);
            $log_stmt->execute();

            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update stock.']);
        }
        exit;
    }
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

    if ($is_perishable && $expiry_date) {
        // Reduce batch quantity for perishable item
        $stmt = $conn->prepare("UPDATE item_batches SET quantity = quantity - ? WHERE item_id = ? AND expiry_date = ? AND quantity >= ?");
        $stmt->bind_param('iisi', $quantity, $item_id, $expiry_date, $quantity);
        if ($stmt->execute()) {
            // Update total stock in items table
            $conn->query("UPDATE items SET current_stock = current_stock - $quantity WHERE id = $item_id");
            
            // Log the action
            $log_stmt = $conn->prepare("INSERT INTO logs (item_id, action, category) VALUES (?, ?, ?)");
            $log_action = 'Stocked Out';
            $log_stmt->bind_param('iss', $item_id, $log_action, $category_name);
            $log_stmt->execute();

            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to reduce batch.']);
        }
        exit;
    } else {
        // Non-perishable: just reduce stock
        $stmt = $conn->prepare("UPDATE items SET current_stock = current_stock - ? WHERE id = ? AND current_stock >= ?");
        $stmt->bind_param('iii', $quantity, $item_id, $quantity);
        if ($stmt->execute()) {
            // Log the action
            $log_stmt = $conn->prepare("INSERT INTO logs (item_id, action, category) VALUES (?, ?, ?)");
            $log_action = 'Stocked Out';
            $log_stmt->bind_param('iss', $item_id, $log_action, $category_name);
            $log_stmt->execute();

            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to reduce stock.']);
        }
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
exit;
