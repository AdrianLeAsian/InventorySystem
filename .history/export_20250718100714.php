<?php
<?php
require_once 'config/db.php';
require_once 'vendor/autoload.php'; // For Dompdf

use Dompdf\Dompdf;
use Dompdf\Options;

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
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

function generate_pdf($filename, $html_content) {
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    
    $dompdf->loadHtml($html_content);
    $dompdf->setPaper('A4', 'landscape'); // or 'portrait'
    $dompdf->render();
    $dompdf->stream($filename, array("Attachment" => 1));
    exit;
}

if (isset($_GET['type'])) {
    $type = $_GET['type'];

    // Common filtering parameters for filtered reports
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

    } elseif ($type == 'stock_overview_csv') {
        $sql = "SELECT name, quantity, barcode FROM items ORDER BY name ASC";
        $result = mysqli_query($link, $sql);
        $stock_overview_data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $stock_overview_data[] = $row;
            }
            mysqli_free_result($result);
        }
        $headers = ['Item Name', 'Current Stock', 'Barcode'];
        output_csv('stock_overview_' . date('Y-m-d') . '.csv', $stock_overview_data, $headers);

    } elseif ($type == 'low_stock_csv') {
        $sql = "SELECT name, quantity, low_stock_threshold, barcode FROM items WHERE quantity <= low_stock_threshold ORDER BY name ASC";
        $result = mysqli_query($link, $sql);
        $low_stock_data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $low_stock_data[] = $row;
            }
            mysqli_free_result($result);
        }
        $headers = ['Item Name', 'Current Stock', 'Low Stock Threshold', 'Barcode'];
        output_csv('low_stock_items_' . date('Y-m-d') . '.csv', $low_stock_data, $headers);

    } elseif ($type == 'category_summary_csv') {
        $sql = "SELECT 
                    c.name as category_name,
                    SUM(i.quantity) as total_quantity
                 FROM categories c
                 JOIN items i ON c.id = i.category_id
                 GROUP BY c.name
                 ORDER BY c.name ASC";
        $result = mysqli_query($link, $sql);
        $category_summary_data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $category_summary_data[] = $row;
            }
            mysqli_free_result($result);
        }
        $headers = ['Category Name', 'Total Stock'];
        output_csv('category_stock_summary_' . date('Y-m-d') . '.csv', $category_summary_data, $headers);

    } elseif ($type == 'all_logs_csv') {
        $sql = "SELECT 
                    il.id, 
                    i.name as item_name, 
                    il.type, 
                    il.quantity_change, 
                    il.current_quantity, 
                    il.log_date, 
                    il.remarks 
                FROM inventory_log il
                JOIN items i ON il.item_id = i.id
                ORDER BY il.log_date DESC";
        $result = mysqli_query($link, $sql);
        $all_logs_data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $all_logs_data[] = $row;
            }
            mysqli_free_result($result);
        }
        $headers = ['Log ID', 'Item Name', 'Type', 'Quantity Change', 'Current Quantity', 'Log Date', 'Remarks'];
        output_csv('all_inventory_logs_' . date('Y-m-d') . '.csv', $all_logs_data, $headers);

    } elseif ($type == 'filtered_items_csv') {
        $sql_filtered_items = "SELECT 
                                i.name as item_name, 
                                c.name as category_name, 
                                i.barcode, 
                                i.quantity, 
                                i.low_stock_threshold, 
                                i.created_at 
                            FROM items i 
                            JOIN categories c ON i.category_id = c.id 
                            $where_sql
                            ORDER BY i.name ASC";
        
        $stmt_filtered_items = mysqli_prepare($link, $sql_filtered_items);
        if ($stmt_filtered_items) {
            if (!empty($params)) {
                mysqli_stmt_bind_param($stmt_filtered_items, $param_types, ...$params);
            }
            mysqli_stmt_execute($stmt_filtered_items);
            $result_filtered_items = mysqli_stmt_get_result($stmt_filtered_items);
            $filtered_items_data = [];
            while ($row = mysqli_fetch_assoc($result_filtered_items)) {
                $filtered_items_data[] = $row;
            }
            mysqli_free_result($result_filtered_items);
            mysqli_stmt_close($stmt_filtered_items);
        }
        $headers = ['Item Name', 'Category', 'Barcode', 'Quantity', 'Low Stock Threshold', 'Created At'];
        output_csv('filtered_items_' . date('Y-m-d') . '.csv', $filtered_items_data, $headers);

    } elseif ($type == 'filtered_items_pdf') {
        $sql_filtered_items = "SELECT 
                                i.name as item_name, 
                                c.name as category_name, 
                                i.barcode, 
                                i.quantity, 
                                i.low_stock_threshold, 
                                i.created_at 
                            FROM items i 
                            JOIN categories c ON i.category_id = c.id 
                            $where_sql
                            ORDER BY i.name ASC";
        
        $stmt_filtered_items = mysqli_prepare($link, $sql_filtered_items);
        if ($stmt_filtered_items) {
            if (!empty($params)) {
                mysqli_stmt_bind_param($stmt_filtered_items, $param_types, ...$params);
            }
            mysqli_stmt_execute($stmt_filtered_items);
            $result_filtered_items = mysqli_stmt_get_result($stmt_filtered_items);
            $filtered_items_data = [];
            while ($row = mysqli_fetch_assoc($result_filtered_items)) {
                $filtered_items_data[] = $row;
            }
            mysqli_free_result($result_filtered_items);
            mysqli_stmt_close($stmt_filtered_items);
        }

        $html = '<h1>Filtered Items Report</h1>';
        $html .= '<table border="1" cellspacing="0" cellpadding="5" width="100%">';
        $html .= '<thead><tr>';
        $html .= '<th>Item Name</th><th>Category</th><th>Barcode</th><th>Quantity</th><th>Low Stock Threshold</th><th>Created At</th>';
        $html .= '</tr></thead><tbody>';
        foreach ($filtered_items_data as $row) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['item_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['category_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['barcode']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['quantity']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['low_stock_threshold']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['created_at']) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        generate_pdf('filtered_items_' . date('Y-m-d') . '.pdf', $html);

    } else {
        echo "Invalid export type or missing parameters.";
    }
} else {
    echo "No export type specified.";
}
?>
