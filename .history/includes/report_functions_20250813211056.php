<?php
// Ensure db.php is included for database connection
if (file_exists('includes/db.php')) {
    include_once 'includes/db.php';
} elseif (file_exists('../includes/db.php')) {
    include_once '../includes/db.php';
} else {
    // Handle error: db.php not found
    error_log("Error: db.php not found in report_functions.php");
    // You might want to throw an exception or redirect here
}

function getStockSummary($conn) {
    $summary = [];

    // Total Items
    $stmt = $conn->prepare("SELECT COUNT(id) AS total_items FROM items");
    $stmt->execute();
    $result = $stmt->get_result();
    $summary['total_items'] = $result->fetch_assoc()['total_items'];
    $stmt->close();

    // Total Stock Quantity
    $stmt = $conn->prepare("SELECT SUM(current_stock) AS total_stock_quantity FROM items");
    $stmt->execute();
    $result = $stmt->get_result();
    $summary['total_stock_quantity'] = $result->fetch_assoc()['total_stock_quantity'];
    $stmt->close();

    // Low Stock Items
    $stmt = $conn->prepare("SELECT COUNT(id) AS low_stock_items FROM items WHERE current_stock <= low_stock AND current_stock > 0");
    $stmt->execute();
    $result = $stmt->get_result();
    $summary['low_stock_items'] = $result->fetch_assoc()['low_stock_items'];
    $stmt->close();

    // Out of Stock Items
    $stmt = $conn->prepare("SELECT COUNT(id) AS out_of_stock_items FROM items WHERE current_stock = 0");
    $stmt->execute();
    $result = $stmt->get_result();
    $summary['out_of_stock_items'] = $result->fetch_assoc()['out_of_stock_items'];
    $stmt->close();

    // Perishable Items
    $stmt = $conn->prepare("SELECT COUNT(id) AS perishable_items FROM items WHERE is_perishable = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $summary['perishable_items'] = $result->fetch_assoc()['perishable_items'];
    $stmt->close();

    return $summary;
}

function getDetailedInventory($conn) {
    $items = [];
    $sql = "SELECT items.*, categories.name AS category_name, locations.name AS location_name
            FROM items
            JOIN categories ON items.category_id = categories.id
            JOIN locations ON items.location_id = locations.id
            ORDER BY items.name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();
    return $items;
}

function getTransactionLogs($conn) {
    $logs = [];
    $sql = "SELECT tl.*, i.name AS item_name, u.username AS user_name
            FROM transaction_logs tl
            JOIN items i ON tl.item_id = i.id
            JOIN users u ON tl.user_id = u.id
            ORDER BY tl.transaction_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    $stmt->close();
    return $logs;
}

function getExpiryCalendar($conn) {
    $expiry_items = [];
    $sql = "SELECT ib.id AS batch_id, i.name AS item_name, ib.expiry_date, ib.quantity
            FROM item_batches ib
            JOIN items i ON ib.item_id = i.id
            WHERE i.is_perishable = 1 AND ib.expiry_date IS NOT NULL
            ORDER BY ib.expiry_date ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $expiry_items[] = $row;
    }
    $stmt->close();
    return $expiry_items;
}
?>
