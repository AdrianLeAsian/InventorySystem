<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'data' => [], 'total_records' => 0, 'message' => ''];

// Pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

// Filtering parameters
$category_id = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;
$start_date = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? $_GET['end_date'] : null;
$min_stock = isset($_GET['min_stock']) && $_GET['min_stock'] !== '' ? (int)$_GET['min_stock'] : null;
$max_stock = isset($_GET['max_stock']) && $_GET['max_stock'] !== '' ? (int)$_GET['max_stock'] : null;
$item_status = isset($_GET['item_status']) && $_GET['item_status'] !== '' ? $_GET['item_status'] : null;
$search_query = isset($_GET['search']) && $_GET['search'] !== '' ? $_GET['search'] : null;

$where_clauses = [];
$params = [];
$param_types = '';

// Build WHERE clauses based on filters
if ($category_id !== null) {
    $where_clauses[] = "i.category_id = ?";
    $params[] = $category_id;
    $param_types .= 'i';
}
if ($start_date !== null) {
    $where_clauses[] = "i.created_at >= ?";
    $params[] = $start_date . ' 00:00:00';
    $param_types .= 's';
}
if ($end_date !== null) {
    $where_clauses[] = "i.created_at <= ?";
    $params[] = $end_date . ' 23:59:59';
    $param_types .= 's';
}
if ($min_stock !== null) {
    $where_clauses[] = "i.quantity >= ?";
    $params[] = $min_stock;
    $param_types .= 'i';
}
if ($max_stock !== null) {
    $where_clauses[] = "i.quantity <= ?";
    $params[] = $max_stock;
    $param_types .= 'i';
}
if ($item_status !== null) {
    if ($item_status === 'low_stock') {
        $where_clauses[] = "i.quantity <= i.low_stock_threshold";
    } elseif ($item_status === 'out_of_stock') {
        $where_clauses[] = "i.quantity = 0";
    } elseif ($item_status === 'in_stock') {
        $where_clauses[] = "i.quantity > i.low_stock_threshold";
    }
}
if ($search_query !== null) {
    $where_clauses[] = "(i.name LIKE ? OR i.barcode LIKE ?)";
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
    $param_types .= 'ss';
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Get total records for pagination
$sql_count = "SELECT COUNT(*) as total FROM items i " . $where_sql;
$stmt_count = mysqli_prepare($conn, $sql_count);

if ($stmt_count) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt_count, $param_types, ...$params);
    }
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $total_records = mysqli_fetch_assoc($result_count)['total'];
    mysqli_stmt_close($stmt_count);
} else {
    $response['message'] = "Error preparing count query: " . mysqli_error($conn);
    echo json_encode($response);
    exit;
}

// Fetch filtered items
$sql_items = "SELECT 
                i.id, 
                i.name, 
                c.name as category_name, 
                i.barcode, 
                i.quantity, 
                i.unit, 
                i.low_stock_threshold, 
                i.purchase_price, 
                i.selling_price, 
                i.description, 
                i.created_at, 
                i.updated_at 
              FROM items i 
              JOIN categories c ON i.category_id = c.id 
              $where_sql
              ORDER BY i.name ASC
              LIMIT ? OFFSET ?";

$stmt_items = mysqli_prepare($conn, $sql_items);

if ($stmt_items) {
    $params[] = $limit;
    $params[] = $offset;
    $param_types .= 'ii'; // Add types for limit and offset

    mysqli_stmt_bind_param($stmt_items, $param_types, ...$params);
    
    if (mysqli_stmt_execute($stmt_items)) {
        $result_items = mysqli_stmt_get_result($stmt_items);
        $items_data = [];
        while ($row = mysqli_fetch_assoc($result_items)) {
            $items_data[] = $row;
        }
        mysqli_free_result($result_items);
        $response['success'] = true;
        $response['data'] = $items_data;
        $response['total_records'] = $total_records;
    } else {
        $response['message'] = "Error executing items query: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt_items);
} else {
    $response['message'] = "Error preparing items query: " . mysqli_error($conn);
}

echo json_encode($response);
?>
