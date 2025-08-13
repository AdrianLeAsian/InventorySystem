<?php
// export.php

include 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/report_functions.php';

// Check if user is logged in and has a role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    die('Unauthorized access.');
}

$user_role = $_SESSION['role'];
$report_type = $_GET['report_type'] ?? '';
$format = strtoupper($_GET['format'] ?? 'CSV'); // Default to CSV

$data = [];
$headers = [];
$filename = 'report';

switch ($report_type) {
    case 'detailed_inventory':
        if (!in_array($user_role, ['viewer', 'staff', 'admin'])) {
            die('Permission denied.');
        }
        $filters = [
            'category_id' => $_GET['category_id'] ?? '',
            'location_id' => $_GET['location_id'] ?? '',
            'stock_status' => $_GET['stock_status'] ?? '',
            'is_perishable' => $_GET['is_perishable'] ?? '',
            'expiry_start' => $_GET['expiry_start'] ?? '',
            'expiry_end' => $_GET['expiry_end'] ?? ''
        ];
        $search_term = $_GET['search_term'] ?? '';
        $sort_by = $_GET['sort_by'] ?? 'name';
        $sort_order = $_GET['sort_order'] ?? 'ASC';
        // Fetch all data, not paginated for export
        $inventory_data = get_detailed_inventory_data($filters, $search_term, $sort_by, $sort_order, PHP_INT_MAX, 0, $user_role);
        $data = $inventory_data['data'];

        $headers = ['Item Name', 'Category', 'Location', 'Current Stock', 'Unit', 'Perishable?', 'Expiry Date'];
        if ($user_role === 'admin' || $user_role === 'staff') {
            array_splice($headers, 5, 0, ['Low Stock Threshold', 'Max Stock']);
        }
        $filename = 'detailed_inventory_report';
        break;

    case 'transaction_logs':
        if (!in_array($user_role, ['staff', 'admin'])) {
            die('Permission denied.');
        }
        $filters = [
            'date_start' => $_GET['date_start'] ?? '',
            'date_end' => $_GET['date_end'] ?? '',
            'action_type' => $_GET['action_type'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'item_id' => $_GET['item_id'] ?? ''
        ];
        // Fetch all data, not paginated for export
        $logs_data = get_transaction_logs_data($filters, PHP_INT_MAX, 0, $user_role);
        $data = $logs_data['data'];
        $headers = ['Date/Time', 'Item Name', 'Action', 'Category'];
        $filename = 'transaction_logs_report';
        break;

    case 'expiry_calendar_list':
        if (!in_array($user_role, ['staff', 'admin'])) {
            die('Permission denied.');
        }
        $filters = [
            'category_id' => $_GET['category_id'] ?? '',
            'location_id' => $_GET['location_id'] ?? ''
        ];
        $calendar_events = get_expiry_calendar_events($filters, $user_role);
        // Transform calendar events into a flat array for export
        foreach ($calendar_events as $event) {
            $data[] = [
                'Item Name' => $event['title'],
                'Expiry Date' => $event['start'],
                'Category' => $event['extendedProps']['category'],
                'Location' => $event['extendedProps']['location'],
                'Batch Quantity' => $event['extendedProps']['batch_quantity'],
                'Days Until Expiry' => $event['extendedProps']['days_until_expiry'],
                'Status' => ucfirst($event['color']) // Red, Yellow, Green
            ];
        }
        $headers = ['Item Name', 'Expiry Date', 'Category', 'Location', 'Batch Quantity', 'Days Until Expiry', 'Status'];
        $filename = 'expiry_calendar_list';
        break;

    default:
        die('Invalid report type for export.');
}

if (empty($data)) {
    die('No data to export.');
}

// Only CSV export is supported now
if ($format === 'CSV') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    foreach ($data as $row) {
        // Ensure the order of values matches headers
        $ordered_row = [];
        foreach ($headers as $header) {
            // Handle cases where header name might not directly match array key (e.g., 'Item Name' vs 'name')
            // This requires careful mapping or ensuring consistent keys.
            // For now, assuming keys match headers for simplicity.
            $key = array_search($header, $headers); // Get index of header
            if ($report_type == 'detailed_inventory') {
                // Special handling for detailed inventory to match column names
                switch ($header) {
                    case 'Item Name': $ordered_row[] = $row['name']; break;
                    case 'Category': $ordered_row[] = $row['category_name']; break;
                    case 'Location': $ordered_row[] = $row['location_name']; break;
                    case 'Current Stock': $ordered_row[] = $row['current_stock']; break;
                    case 'Unit': $ordered_row[] = $row['unit']; break;
                    case 'Low Stock Threshold': $ordered_row[] = $row['low_stock'] ?? 'N/A'; break;
                    case 'Max Stock': $ordered_row[] = $row['max_stock'] ?? 'N/A'; break;
                    case 'Perishable?': $ordered_row[] = $row['is_perishable'] == 1 ? 'Yes' : 'No'; break;
                    case 'Expiry Date': $ordered_row[] = $row['expiry_date'] ?? 'N/A'; break;
                    default: $ordered_row[] = ''; break;
                }
            } elseif ($report_type == 'transaction_logs') {
                switch ($header) {
                    case 'Date/Time': $ordered_row[] = $row['date_time']; break;
                    case 'Item Name': $ordered_row[] = $row['item_name']; break;
                    case 'Action': $ordered_row[] = $row['action']; break;
                    case 'Category': $ordered_row[] = $row['item_category']; break;
                    default: $ordered_row[] = ''; break;
                }
            } elseif ($report_type == 'expiry_calendar_list') {
                // For calendar list, data is already transformed to match headers
                $ordered_row[] = $row[$header];
            }
        }
        fputcsv($output, $ordered_row);
    }
    fclose($output);
} else {
    die('Unsupported export format.');
}
?>
