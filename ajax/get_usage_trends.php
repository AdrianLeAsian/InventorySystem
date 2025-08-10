<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'data' => [], 'chart_x_axis_label' => '', 'message' => ''];

$view_type = isset($_GET['view_type']) ? $_GET['view_type'] : 'daily'; // Default to daily
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

$where_clauses = []; // Consider both 'in' and 'out' movements
$params = [];
$param_types = '';

$date_format_sql = '';
$group_by_sql = '';
$chart_x_axis_label = '';

switch ($view_type) {
    case 'daily':
        $date_format_sql = '%Y-%m-%d';
        $group_by_sql = 'DATE(il.log_date)';
        $chart_x_axis_label = 'Day';
        // Default to last 30 days for daily view
        if ($start_date === null && $end_date === null) {
            $where_clauses[] = "il.log_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        }
        break;
    case 'weekly':
        $date_format_sql = '%Y-%u'; // Year-Week number (MySQL %u is Sunday as first day, %v is Monday)
        $group_by_sql = 'YEARWEEK(il.log_date, 1)'; // 1 for Monday as first day of week
        $chart_x_axis_label = 'Week';
        // Default to last 12 weeks for weekly view
        if ($start_date === null && $end_date === null) {
            $where_clauses[] = "il.log_date >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)";
        }
        break;
    case 'monthly':
        $date_format_sql = '%Y-%m';
        $group_by_sql = 'DATE_FORMAT(il.log_date, "%Y-%m")';
        $chart_x_axis_label = 'Month';
        // Default to last 12 months for monthly view
        if ($start_date === null && $end_date === null) {
            $where_clauses[] = "il.log_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        }
        break;
    case 'yearly':
        $date_format_sql = '%Y';
        $group_by_sql = 'YEAR(il.log_date)';
        $chart_x_axis_label = 'Year';
        // Default to last 5 years for yearly view
        if ($start_date === null && $end_date === null) {
            $where_clauses[] = "il.log_date >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)";
        }
        break;
    case 'custom':
        $chart_x_axis_label = 'Date';
        if ($start_date !== null) {
            $where_clauses[] = "il.log_date >= ?";
            $params[] = $start_date . ' 00:00:00';
            $param_types .= 's';
        }
        if ($end_date !== null) {
            $where_clauses[] = "il.log_date <= ?";
            $params[] = $end_date . ' 23:59:59';
            $param_types .= 's';
        }
        // For custom, default to daily grouping if no specific format is requested
        $date_format_sql = '%Y-%m-%d';
        $group_by_sql = 'DATE(il.log_date)';
        break;
    default:
        // Fallback to daily if view_type is invalid
        $date_format_sql = '%Y-%m-%d';
        $group_by_sql = 'DATE(il.log_date)';
        $chart_x_axis_label = 'Day';
        if ($start_date === null && $end_date === null) {
            $where_clauses[] = "il.log_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        }
        break;
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

$sql_usage = "SELECT 
                DATE_FORMAT(il.log_date, '$date_format_sql') as date_label,
                i.name as item_name,
                SUM(CASE WHEN il.type = 'in' THEN il.quantity_change ELSE 0 END) as total_quantity_in,
                SUM(CASE WHEN il.type = 'out' THEN il.quantity_change ELSE 0 END) as total_quantity_out
             FROM inventory_log il
             JOIN items i ON il.item_id = i.id
             $where_sql
             GROUP BY date_label, i.id, i.name
             ORDER BY date_label ASC, item_name ASC";

if ($stmt = mysqli_prepare($conn, $sql_usage)) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $usage_data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $usage_data[] = $row;
        }
        mysqli_free_result($result);
        $response['success'] = true;
        $response['data'] = $usage_data;
        $response['chart_x_axis_label'] = $chart_x_axis_label;
    } else {
        $response['message'] = "Error executing usage trends query: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
} else {
    $response['message'] = "Error preparing usage trends query: " . mysqli_error($conn);
}

echo json_encode($response);
?>
