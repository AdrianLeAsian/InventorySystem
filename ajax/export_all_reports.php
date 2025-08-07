<?php
require_once '../config/db.php';

function output_csv($filename, $data, $headers) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8 Excel compatibility (optional, but good for special characters)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add headers
    fputcsv($output, $headers);
    
    // Add data
    foreach ($data as $row) {
        $ordered_row = [];
        foreach ($headers as $header_key) {
            // Use the header key to get the corresponding value from the associative row array
            // Provide an empty string if the key doesn't exist to prevent errors
            $ordered_row[] = $row[$header_key] ?? ''; 
        }
        fputcsv($output, $ordered_row);
    }
    fclose($output);
    exit;
}

if ($conn === false) {
    die("ERROR: Could not connect to database.");
}

$all_data = [];
$headers = [
    'log_id', 'log_date', 'log_type', 'quantity_change',
    'item_id', 'item_name', 'item_barcode', 'item_quantity', 'item_unit', 'item_low_stock_threshold',
    'item_description', 'item_created_at', 'item_updated_at',
    'category_id', 'category_name', 'category_description', 'category_created_at', 'category_updated_at'
];

// Fetch all inventory logs with joined item and category data
$sql_all_logs = "SELECT 
                    il.id AS log_id, 
                    il.log_date, 
                    il.type AS log_type, 
                    il.quantity_change, 
                    i.id AS item_id,
                    i.name AS item_name,
                    i.barcode AS item_barcode,
                    i.quantity AS item_quantity,
                    i.unit AS item_unit,
                    i.low_stock_threshold AS item_low_stock_threshold,
                    i.description AS item_description,
                    i.created_at AS item_created_at,
                    i.updated_at AS item_updated_at,
                    c.id AS category_id,
                    c.name AS category_name,
                    c.description AS category_description,
                    c.created_at AS category_created_at,
                    c.updated_at AS category_updated_at
                 FROM inventory_log il
                 LEFT JOIN items i ON il.item_id = i.id
                 LEFT JOIN categories c ON i.category_id = c.id
                 ORDER BY il.log_date DESC, il.id DESC";

$result_all_logs = mysqli_query($conn, $sql_all_logs);

if ($result_all_logs === false) {
    error_log("Error fetching all reports data from ajax/export_all_reports.php: " . mysqli_error($conn));
    // If query fails, output an empty array to prevent further errors, but log it.
    $all_data = []; 
} else {
    while ($row = mysqli_fetch_assoc($result_all_logs)) {
        $all_data[] = $row;
    }
    mysqli_free_result($result_all_logs);
}

mysqli_close($conn);

output_csv('inventory_all_reports_' . date('Y-m-d_His') . '.csv', $all_data, $headers);
?>
