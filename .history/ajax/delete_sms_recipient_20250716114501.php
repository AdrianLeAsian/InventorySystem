<?php
/**
 * delete_sms_recipient.php
 *
 * This script handles the AJAX request for deleting an SMS recipient.
 * It performs validation, deletes the recipient from the `sms_recipients` table,
 * and returns a JSON response.
 */

// Disable display errors and set error reporting for production environment
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Include the database configuration file
require_once '../config/db.php';

// Set the content type to JSON for the response
header('Content-Type: application/json');

// Initialize the response array
$response = ['success' => false, 'message' => ''];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve recipient ID
    $recipientId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    // Basic validation
    if ($recipientId === false || $recipientId <= 0) {
        $response['message'] = 'Invalid recipient ID.';
        echo json_encode($response);
        exit();
    }

    // Prepare a delete statement
    $sql = "DELETE FROM sms_recipients WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $recipientId);
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $response['success'] = true;
                $response['message'] = 'Recipient deleted successfully!';
            } else {
                $response['message'] = 'Recipient not found or already deleted.';
            }
        } else {
            $response['message'] = 'Error deleting recipient: ' . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'Database prepare failed: ' . mysqli_error($conn);
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
