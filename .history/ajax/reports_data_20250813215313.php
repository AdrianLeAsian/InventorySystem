<?php
session_start();
include_once '../includes/db.php';
include_once '../includes/report_functions.php';

header('Content-Type: application/json');

$user_role = $_SESSION['user_role'] ?? 'guest';
$report_type = $_GET['report_type'] ?? '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = ($page - 1) * $limit;

$response = ['status' => 'error', 'message' => 'Invalid request or unauthorized access.'];

if (!$conn) {
    $response['message'] = 'Database connection failed.';
    echo json_encode($response);
    exit;
}

// Collect filters for detailed-inventory
$filters = [];
if ($report_type === 'detailed-inventory') {
    $filters['search'] = $_GET['search'] ?? '';
    $filters['category_id'] = $_GET['category_id'] ?? '';
    $filters['location_id'] = $_GET['location_id'] ?? '';
    $filters['stock_status'] = $_GET['stock_status'] ?? '';
    $filters['is_perishable'] = $_GET['is_perishable'] ?? '';
    $filters['expiry_date_start'] = $_GET['expiry_date_start'] ?? '';
    $filters['expiry_date_end'] = $_GET['expiry_date_end'] ?? '';
}

switch ($report_type) {
    case 'stock-summary':
        // Stock Summary is visible to Admin & User
        if ($user_role === 'admin' || $user_role === 'user') {
            $data = getStockSummary($conn);
            $response = ['status' => 'success', 'data' => $data];
        } else {
            $response['message'] = 'Unauthorized to view Stock Summary.';
        }
        break;
    case 'detailed-inventory':
        // Detailed Inventory is visible to Admin & User (read-only for user handled in frontend)
        if ($user_role === 'admin' || $user_role === 'user') {
            $data = getDetailedInventory($conn, $filters, 'name', 'ASC', $limit, $offset); // Default sort for now
            $total_records = getTotalRecords($conn, $report_type, $filters);
            $response = ['status' => 'success', 'data' => $data, 'user_role' => $user_role, 'total_records' => $total_records];
        } else {
            $response['message'] = 'Unauthorized to view Detailed Inventory.';
        }
        break;
    case 'transaction-logs':
        // Transaction Logs is visible to Admin only
        if ($user_role === 'admin') {
            $data = getTransactionLogs($conn, $limit, $offset);
            $total_records = getTotalRecords($conn, $report_type);
            $response = ['status' => 'success', 'data' => $data, 'total_records' => $total_records];
        } else {
            $response['message'] = 'Unauthorized to view Transaction Logs.';
        }
        break;
    case 'expiry-calendar':
        // Expiry Calendar is visible to Admin only
        if ($user_role === 'admin') {
            $data = getExpiryCalendar($conn, $limit, $offset);
            $total_records = getTotalRecords($conn, $report_type);
            $response = ['status' => 'success', 'data' => $data, 'total_records' => $total_records];
        } else {
            $response['message'] = 'Unauthorized to view Expiry Calendar.';
        }
        break;
    default:
        $response['message'] = 'Unknown report type.';
        break;
}

echo json_encode($response);
?>
