<?php
/**
 * get_latest_activity_logs.php
 *
 * This script fetches activity logs that have occurred since a given timestamp.
 * It is designed to be used for AJAX polling to keep the client-side tracking
 * table synchronized with the database in near real-time.
 */

require_once '../config/db.php'; // Adjust path as needed
require_once '../includes/helpers.php'; // Include helper functions

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'logs' => [], 'server_time' => date('Y-m-d H:i:s')];

// Get the last_timestamp from the GET request, if provided
$lastTimestamp = $_GET['last_timestamp'] ?? null;

$sql = "SELECT id, activity_type, entity_type, entity_id, entity_name, quantity_change, reason, DATE_FORMAT(log_date, '%Y-%m-%d %H:%i:%s') as log_date
        FROM activity_log
        WHERE entity_type = 'item' AND (activity_type = 'stock_in' OR activity_type = 'stock_out')";

$params = [];
$types = "";

if ($lastTimestamp) {
    // Add condition to fetch logs created AFTER the lastTimestamp
    $sql .= " AND log_date > ?";
    $params[] = $lastTimestamp;
    $types .= "s"; // 's' for string (timestamp)
}

$sql .= " ORDER BY log_date DESC LIMIT 20"; // Fetch recent logs, order by most recent first

if ($stmt = mysqli_prepare($conn, $sql)) {
    if ($lastTimestamp) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $updatedLogs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $updatedLogs[] = $row;
    }
    mysqli_stmt_close($stmt);

    $response['success'] = true;
    $response['message'] = 'Successfully fetched updated activity logs.';
    $response['logs'] = $updatedLogs;
} else {
    $response['message'] = 'Database query preparation failed: ' . mysqli_error($conn);
}

echo json_encode($response);
?>
