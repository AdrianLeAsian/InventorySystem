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

// Helper function to determine stock status color
function getStockStatusColor($current_stock, $low_stock, $max_stock) {
    if ($current_stock == 0) {
        return 'red'; // Out of stock
    } elseif ($current_stock <= $low_stock) {
        return 'orange'; // Low stock
    } else {
        return 'green'; // Healthy stock
    }
}

function getDetailedInventory($conn, $filters = [], $sort_by = 'name', $sort_order = 'ASC', $limit = 10, $offset = 0) {
    $items = [];
    $where_clauses = [];
    $params = [];
    $param_types = '';

    // Build WHERE clauses based on filters
    if (!empty($filters['search'])) {
        $where_clauses[] = "items.name LIKE ?";
        $params[] = '%' . $filters['search'] . '%';
        $param_types .= 's';
    }
    if (!empty($filters['category_id'])) {
        $where_clauses[] = "items.category_id = ?";
        $params[] = $filters['category_id'];
        $param_types .= 'i';
    }
    if (!empty($filters['location_id'])) {
        $where_clauses[] = "items.location_id = ?";
        $params[] = $filters['location_id'];
        $param_types .= 'i';
    }
    if (!empty($filters['stock_status'])) {
        if ($filters['stock_status'] === 'red') { // Out of Stock
            $where_clauses[] = "items.current_stock = 0";
        } elseif ($filters['stock_status'] === 'orange') { // Low Stock
            $where_clauses[] = "items.current_stock <= items.low_stock AND items.current_stock > 0";
        } elseif ($filters['stock_status'] === 'green') { // Healthy Stock
            $where_clauses[] = "items.current_stock > items.low_stock";
        }
    }
    if (isset($filters['is_perishable']) && $filters['is_perishable'] !== '') {
        $where_clauses[] = "items.is_perishable = ?";
        $params[] = $filters['is_perishable'];
        $param_types .= 'i';
    }
    if (!empty($filters['expiry_date_start'])) {
        $where_clauses[] = "items.expiry_date >= ?";
        $params[] = $filters['expiry_date_start'];
        $param_types .= 's';
    }
    if (!empty($filters['expiry_date_end'])) {
        $where_clauses[] = "items.expiry_date <= ?";
        $params[] = $filters['expiry_date_end'];
        $param_types .= 's';
    }

    $where_sql = '';
    if (!empty($where_clauses)) {
        $where_sql = " WHERE " . implode(" AND ", $where_clauses);
    }

    // Validate sort_by and sort_order to prevent SQL injection
    $allowed_sort_by = ['name', 'category_name', 'location_name', 'current_stock', 'expiry_date'];
    if (!in_array($sort_by, $allowed_sort_by)) {
        $sort_by = 'name';
    }
    $sort_order = (strtoupper($sort_order) === 'DESC') ? 'DESC' : 'ASC';

    $sql = "SELECT items.*, categories.name AS category_name, locations.name AS location_name
            FROM items
            JOIN categories ON items.category_id = categories.id
            JOIN locations ON items.location_id = locations.id
            {$where_sql}
            ORDER BY {$sort_by} {$sort_order}
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        return [];
    }

    // Bind parameters for filters and pagination
    $param_types .= 'ii'; // Add types for limit and offset
    $params[] = $limit;
    $params[] = $offset;

    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['stock_status_color'] = getStockStatusColor($row['current_stock'], $row['low_stock'], $row['max_stock']);
        $items[] = $row;
    }
    $stmt->close();
    return $items;
}

function getTransactionLogs($conn) {
    $logs = [];
    // Assuming 'logs' table has item_id, action, date_time.
    // We need to join with 'items' to get item_name.
    // The 'users' join is not possible with the current 'logs' table schema as it doesn't have user_id.
    // If user_id is needed, the 'logs' table schema needs to be updated.
    // For now, we will only fetch item_name and action.
    $sql = "SELECT l.log_id, l.item_id, i.name AS item_name, l.action, l.date_time
            FROM logs l
            JOIN items i ON l.item_id = i.id
            ORDER BY l.date_time DESC";
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
    $today = date('Y-m-d');
    $seven_days_from_now = date('Y-m-d', strtotime('+7 days'));

    // Get expiry dates from item_batches
    $sql_batches = "SELECT i.name AS item_name, ib.expiry_date, ib.quantity
                    FROM item_batches ib
                    JOIN items i ON ib.item_id = i.id
                    WHERE i.is_perishable = 1 AND ib.expiry_date IS NOT NULL AND ib.quantity > 0";
    $stmt_batches = $conn->prepare($sql_batches);
    $stmt_batches->execute();
    $result_batches = $stmt_batches->get_result();
    while ($row = $result_batches->fetch_assoc()) {
        $expiry_items[] = $row;
    }
    $stmt_batches->close();

    // Get expiry dates directly from items table (for items that might not have batches but have an expiry_date)
    $sql_items = "SELECT name AS item_name, expiry_date, current_stock AS quantity
                  FROM items
                  WHERE is_perishable = 1 AND expiry_date IS NOT NULL AND current_stock > 0";
    $stmt_items = $conn->prepare($sql_items);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    while ($row = $result_items->fetch_assoc()) {
        $expiry_items[] = $row;
    }
    $stmt_items->close();

    // Merge and remove duplicates (if an item appears in both with same expiry date)
    // For simplicity, we'll use item_name and expiry_date as a unique key for merging
    $unique_expiry_items = [];
    foreach ($expiry_items as $item) {
        $key = $item['item_name'] . '|' . $item['expiry_date'];
        if (!isset($unique_expiry_items[$key])) {
            $unique_expiry_items[$key] = $item;
        } else {
            // If duplicate, sum quantities (assuming same item, same expiry date means same batch conceptually)
            $unique_expiry_items[$key]['quantity'] += $item['quantity'];
        }
    }
    $expiry_items = array_values($unique_expiry_items);

    // Sort by expiry date
    usort($expiry_items, function($a, $b) {
        return strtotime($a['expiry_date']) - strtotime($b['expiry_date']);
    });

    // Add color coding
    foreach ($expiry_items as &$item) {
        $expiry_date_ts = strtotime($item['expiry_date']);
        $today_ts = strtotime($today);
        $seven_days_ts = strtotime($seven_days_from_now);

        if ($expiry_date_ts < $today_ts) {
            $item['expiry_status_color'] = 'red'; // Expired
        } elseif ($expiry_date_ts <= $seven_days_ts) {
            $item['expiry_status_color'] = 'yellow'; // Expiring within 7 days
        } else {
            $item['expiry_status_color'] = 'green'; // Later than 7 days
        }
    }
    unset($item); // Break the reference with the last element

    return $expiry_items;
}
?>
