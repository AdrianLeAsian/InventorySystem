<?php
/**
 * get_sms_recipients.php
 *
 * This script handles the AJAX request for fetching all SMS recipients
 * from the `sms_recipients` table. It returns a JSON array of recipients.
 */

// Disable display errors and set error reporting for production environment
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Include the database configuration file
require_once '../config/db.php';

// Set the content type to JSON for the response
header('Content-Type: application/json');

// Initialize the response array
$response = ['success' => false, 'message' => '', 'recipients' => []];

// Check if the request method is GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Prepare a select statement
    $sql = "SELECT id, name, phone_number FROM sms_recipients ORDER BY name ASC";
    if ($result = mysqli_query($conn, $sql)) {
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $response['recipients'][] = [
                    'id' => $row['id'],
                    'name' => htmlspecialchars($row['name']),
                    'phone_number' => htmlspecialchars($row['phone_number'])
                ];
            }
            $response['success'] = true;
        } else {
            $response['message'] = 'No recipients found.';
            $response['success'] = true; // Still a success, just no data
        }
        mysqli_free_result($result);
    } else {
        $response['message'] = 'Database query failed: ' . mysqli_error($conn);
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
