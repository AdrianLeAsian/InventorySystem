<?php
// ajax/reports_data.php

include '../includes/auth.php'; // For session and role check
require_once '../includes/db.php';
require_once '../includes/report_functions.php';

header('Content-Type: application/json');

// Check if user is logged in and has a role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$user_role = $_SESSION['role'];
$report_type = $_POST['report_type'] ?? '';

$response = ['success' => false, 'message' => 'Invalid report type.'];

switch ($report_type) {
    case 'stock_summary':
        // Role permission: Viewer, Staff, Admin can access
        if (in_array($user_role, ['viewer', 'staff', 'admin'])) {
            $summary_data = get_stock_summary_data($user_role);
            $response = ['success' => true, 'data' => $summary_data];
        } else {
            $response = ['success' => false, 'message' => 'Permission denied for stock summary.'];
        }
        break;

    case 'detailed_inventory':
        // Role permission: Viewer, Staff, Admin can access
        if (in_array($user_role, ['viewer', 'staff', 'admin'])) {
            $filters = [
                'category_id' => $_POST['category_id'] ?? '',
                'location_id' => $_POST['location_id'] ?? '',
                'stock_status' => $_POST['stock_status'] ?? '',
                'is_perishable' => $_POST['is_perishable'] ?? '',
                'expiry_start' => $_POST['expiry_start'] ?? '',
                'expiry_end' => $_POST['expiry_end'] ?? ''
            ];
            $search_term = $_POST['search_term'] ?? '';
            $sort_by = $_POST['sort_by'] ?? 'name';
            $sort_order = $_POST['sort_order'] ?? 'ASC';
            $limit = (int)($_POST['limit'] ?? 10);
            $offset = (int)($_POST['offset'] ?? 0);

            $inventory_data = get_detailed_inventory_data($filters, $search_term, $sort_by, $sort_order, $limit, $offset, $user_role);
            $response = ['success' => true, 'data' => $inventory_data['data'], 'total_records' => $inventory_data['total_records']];
        } else {
            $response = ['success' => false, 'message' => 'Permission denied for detailed inventory.'];
        }
        break;

    case 'transaction_logs':
        // Role permission: Staff, Admin can access
        if (in_array($user_role, ['staff', 'admin'])) {
            $filters = [
                'date_start' => $_POST['date_start'] ?? '',
                'date_end' => $_POST['date_end'] ?? '',
                'action_type' => $_POST['action_type'] ?? '',
                'category_id' => $_POST['category_id'] ?? '',
                'item_id' => $_POST['item_id'] ?? ''
            ];
            $limit = (int)($_POST['limit'] ?? 10);
            $offset = (int)($_POST['offset'] ?? 0);

            $logs_data = get_transaction_logs_data($filters, $limit, $offset, $user_role);
            $response = ['success' => true, 'data' => $logs_data['data'], 'total_records' => $logs_data['total_records']];
        } else {
            $response = ['success' => false, 'message' => 'Permission denied for transaction logs.'];
        }
        break;

    case 'expiry_calendar_events':
        // Role permission: Staff, Admin can access
        if (in_array($user_role, ['staff', 'admin'])) {
            $filters = [
                'category_id' => $_POST['category_id'] ?? '',
                'location_id' => $_POST['location_id'] ?? ''
            ];
            $calendar_events = get_expiry_calendar_events($filters, $user_role);
            $response = ['success' => true, 'data' => $calendar_events];
        } else {
            $response = ['success' => false, 'message' => 'Permission denied for expiry calendar.'];
        }
        break;

    case 'get_filters_data':
        // Role permission: All roles might need this for dropdowns
        if (in_array($user_role, ['viewer', 'staff', 'admin'])) {
            $categories = get_categories();
            $locations = get_locations();
            $items = get_items_for_filter();
            $response = ['success' => true, 'categories' => $categories, 'locations' => $locations, 'items' => $items];
        } else {
            $response = ['success' => false, 'message' => 'Permission denied for filter data.'];
        }
        break;

    default:
        $response = ['success' => false, 'message' => 'Unknown report type.'];
        break;
}

echo json_encode($response);
?>
