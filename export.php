<?php
session_start();
include_once 'includes/db.php';
include_once 'includes/report_functions.php';

$user_role = $_SESSION['user_role'] ?? 'guest';
$report_type = $_GET['report_type'] ?? '';
$format = $_GET['format'] ?? 'csv'; // 'csv' or 'pdf'

if (!$conn) {
    die("Database connection failed.");
}

$data = [];
$filename = "report_" . $report_type . "_" . date('Ymd_His');
$report_title = '';

// Collect filters, sort, and pagination parameters from GET
$filters = [];
$sort_by = $_GET['sort_by'] ?? 'name';
$sort_order = $_GET['sort_order'] ?? 'ASC';
$limit = -1; // No limit for export
$offset = 0; // No offset for export

if ($report_type === 'detailed-inventory') {
    $filters['search'] = $_GET['search'] ?? '';
    $filters['category_id'] = $_GET['category_id'] ?? '';
    $filters['location_id'] = $_GET['location_id'] ?? '';
    $filters['stock_status'] = $_GET['stock_status'] ?? '';
    $filters['is_perishable'] = $_GET['is_perishable'] ?? '';
    $filters['expiry_date_start'] = $_GET['expiry_date_start'] ?? '';
    $filters['expiry_date_end'] = $_GET['expiry_date_end'] ?? '';
}

// Role-based access control for export
$has_permission = false;
switch ($report_type) {
    case 'stock-summary':
        if ($user_role === 'admin' || $user_role === 'user') {
            $data = getStockSummary($conn);
            $report_title = 'Stock Summary Report';
            $has_permission = true;
        }
        break;
    case 'detailed-inventory':
        if ($user_role === 'admin' || $user_role === 'user') {
            $data = getDetailedInventory($conn, $filters, $sort_by, $sort_order, $limit, $offset);
            $report_title = 'Detailed Inventory Report';
            $has_permission = true;
        }
        break;
    case 'transaction-logs':
        if ($user_role === 'admin') {
            $data = getTransactionLogs($conn, $limit, $offset);
            $report_title = 'Transaction Logs Report';
            $has_permission = true;
        }
        break;
    case 'expiry-calendar':
        if ($user_role === 'admin') { // Only admin can export Expiry Calendar
            $data = getExpiryCalendar($conn, $limit, $offset); // Pass limit/offset, but getExpiryCalendar handles merging/sorting internally
            $report_title = 'Expiry Calendar Report';
            $has_permission = true;
        }
        break;
    default:
        die("Invalid report type.");
}

if (!$has_permission) {
    die("Unauthorized to export this report.");
}

// Clean data: remove 'stock_status_color' and 'expiry_status_color' before export
if (!empty($data) && ($report_type === 'detailed-inventory' || $report_type === 'expiry-calendar')) {
    foreach ($data as &$row) {
        if (isset($row['stock_status_color'])) {
            unset($row['stock_status_color']);
        }
        if (isset($row['expiry_status_color'])) {
            unset($row['expiry_status_color']);
        }
    }
    unset($row); // Break the reference with the last element
}


if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

    $output = fopen('php://output', 'w');

    if (!empty($data)) {
        // For summary reports, data is an associative array, convert to a printable format
        if ($report_type === 'stock-summary') {
            fputcsv($output, ['Metric', 'Value']);
            foreach ($data as $key => $value) {
                fputcsv($output, [ucwords(str_replace('_', ' ', $key)), $value]);
            }
        } else {
            // For tabular reports, use array keys as headers
            fputcsv($output, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
    } else {
        fputcsv($output, ['No data available for this report.']);
    }

    fclose($output);
    exit;

} elseif ($format === 'pdf') {
    // For PDF, we'll generate a simple HTML page and suggest printing to PDF
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($report_title) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #333; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .note { margin-top: 30px; font-style: italic; color: #555; }
        </style>
    </head>
    <body>
        <h1>' . htmlspecialchars($report_title) . '</h1>';

    if (!empty($data)) {
        echo '<table>';
        if ($report_type === 'stock-summary') {
            echo '<thead><tr><th>Metric</th><th>Value</th></tr></thead><tbody>';
            foreach ($data as $key => $value) {
                echo '<tr><td>' . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . '</td><td>' . htmlspecialchars($value) . '</td></tr>';
            }
            echo '</tbody>';
        } else {
            // For tabular reports, dynamically generate headers from the first row's keys
            echo '<thead><tr>';
            foreach (array_keys($data[0]) as $header) {
                echo '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $header))) . '</th>';
            }
            echo '</tr></thead><tbody>';
            foreach ($data as $row) {
                echo '<tr>';
                foreach ($row as $cell) {
                    echo '<td>' . htmlspecialchars($cell) . '</td>';
                }
                echo '</tr>';
            }
            echo '</tbody>';
        }
        echo '</table>';
    } else {
        echo '<p>No data available for this report.</p>';
    }

    echo '<p class="note">Please use your browser\'s print function (Ctrl+P or Cmd+P) and select "Save as PDF" to download this report.</p>';
    echo '</body></html>';
    exit;
} else {
    die("Invalid format specified.");
}
?>
