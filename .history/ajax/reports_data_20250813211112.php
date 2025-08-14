<?php
session_start();
include_once '../includes/db.php';
include_once '../includes/report_functions.php';

header('Content-Type: application/json');

$user_role = $_SESSION['user_role'] ?? 'guest';
$report_type = $_GET['report_type'] ?? '';

$response = ['status' => 'error', 'message' => 'Invalid request or unauthorized access.'];

if (!$conn) {
    $response['message'] = 'Database connection failed.';
    echo json_encode($response);
    exit;
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
        // Detailed Inventory is visible to Admin & User
        if ($user_role === 'admin' || $user_role === 'user') {
            $data = getDetailedInventory($conn);
            $response = ['status' => 'success', 'data' => $data, 'user_role' => $user_role];
        } else {
            $response['message'] = 'Unauthorized to view Detailed Inventory.';
        }
        break;
    case 'transaction-logs':
        // Transaction Logs is visible to Admin only
        if ($user_role === 'admin') {
            $data = getTransactionLogs($conn);
            $response = ['status' => 'success', 'data' => $data];
        } else {
            $response['message'] = 'Unauthorized to view Transaction Logs.';
        }
        break;
    case 'expiry-calendar':
        // Expiry Calendar is visible to Admin & User
        if ($user_role === 'admin' || $user_role === 'user') {
            $data = getExpiryCalendar($conn);
            $response = ['status' => 'success', 'data' => $data];
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
