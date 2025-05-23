<?php
require_once 'config/db.php';

function output_csv($filename, $data, $headers) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8 Excel compatibility (optional, but good for special characters)
    // fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add headers
    fputcsv($output, $headers);
    
    // Add data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

if (isset($_GET['type'])) {
    $type = $_GET['type'];

    if ($type == 'items_csv') {
        $sql = "SELECT i.id, i.name, c.name as category_name, i.barcode, i.quantity, i.unit, i.low_stock_threshold, i.purchase_price, i.selling_price, i.description, i.created_at, i.updated_at 
                FROM items i 
                JOIN categories c ON i.category_id = c.id 
                ORDER BY i.name ASC";
        $result = mysqli_query($link, $sql);
        $items_data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $items_data[] = $row;
            }
            mysqli_free_result($result);
        }
        $headers = ['ID', 'Item Name', 'Category', 'Barcode', 'Quantity', 'Unit', 'Low Stock At', 'Purchase Price', 'Selling Price', 'Description', 'Created At', 'Updated At'];
        output_csv('inventory_items_' . date('Y-m-d') . '.csv', $items_data, $headers);

    } elseif ($type == 'categories_csv') {
        $sql = "SELECT id, name, description, created_at, updated_at FROM categories ORDER BY name ASC";
        $result = mysqli_query($link, $sql);
        $categories_data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $categories_data[] = $row;
            }
            mysqli_free_result($result);
        }
        $headers = ['ID', 'Category Name', 'Description', 'Created At', 'Updated At'];
        output_csv('inventory_categories_' . date('Y-m-d') . '.csv', $categories_data, $headers);
    
    } elseif ($type == 'daily_in_out_csv' && isset($_GET['date'])) {
        $report_date = $_GET['date'];
        // Validate date format YYYY-MM-DD
        if (DateTime::createFromFormat('Y-m-d', $report_date) === false) {
            die('Invalid date format for export.');
        }

        $sql_daily_report = "SELECT 
                                i.name as item_name,
                                SUM(CASE WHEN il.type = 'in' THEN il.quantity_change ELSE 0 END) as total_in,
                                SUM(CASE WHEN il.type = 'out' THEN il.quantity_change ELSE 0 END) as total_out
                             FROM inventory_log il
                             JOIN items i ON il.item_id = i.id
                             WHERE DATE(il.log_date) = ?
                             GROUP BY i.id, i.name
                             ORDER BY i.name ASC";
        $daily_data = [];
        if ($stmt_daily = mysqli_prepare($link, $sql_daily_report)) {
            mysqli_stmt_bind_param($stmt_daily, "s", $report_date);
            mysqli_stmt_execute($stmt_daily);
            $result_daily_export = mysqli_stmt_get_result($stmt_daily);
            while ($row_export = mysqli_fetch_assoc($result_daily_export)) {
                $daily_data[] = $row_export;
            }
            mysqli_free_result($result_daily_export);
            mysqli_stmt_close($stmt_daily);
        }
        $headers = ['Item Name', 'Total Stock In on ' . $report_date, 'Total Stock Out on ' . $report_date];
        output_csv('daily_in_out_' . $report_date . '.csv', $daily_data, $headers);

    } else {
        echo "Invalid export type or missing parameters.";
    }
} else {
    echo "No export type specified.";
}
?> 