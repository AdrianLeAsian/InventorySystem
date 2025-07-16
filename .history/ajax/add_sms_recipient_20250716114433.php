<?php
/**
 * add_sms_recipient.php
 *
 * This script handles the AJAX request for adding a new SMS recipient.
 * It performs validation, checks for duplicate phone numbers, inserts the recipient
 * into the `sms_recipients` table, and returns a JSON response.
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
    // Sanitize and retrieve input data
    $name = trim($_POST['name'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');

    // Basic validation
    if (empty($name)) {
        $response['message'] = 'Recipient Name is required.';
        echo json_encode($response);
        exit();
    }
    if (empty($phoneNumber) || !preg_match('/^\+?[0-9]{10,15}$/', $phoneNumber)) {
        $response['message'] = 'Invalid phone number format. Please include country code (e.g., +1234567890).';
        echo json_encode($response);
        exit();
    }

    // Check for duplicate phone number
    $checkSql = "SELECT id FROM sms_recipients WHERE phone_number = ?";
    if ($checkStmt = mysqli_prepare($conn, $checkSql)) {
        mysqli_stmt_bind_param($checkStmt, "s", $phoneNumber);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);
        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            $response['message'] = 'Error: A recipient with this phone number already exists.';
            echo json_encode($response);
            mysqli_stmt_close($checkStmt);
            exit();
        }
        mysqli_stmt_close($checkStmt);
    } else {
        $response['message'] = 'Database prepare failed for phone number check: ' . mysqli_error($conn);
        echo json_encode($response);
        exit();
    }

    // Prepare an insert statement
    $sql = "INSERT INTO sms_recipients (name, phone_number) VALUES (?, ?)";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $name, $phoneNumber);
        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = 'Recipient added successfully!';
            $response['recipient'] = [
                'id' => mysqli_insert_id($conn),
                'name' => htmlspecialchars($name),
                'phone_number' => htmlspecialchars($phoneNumber)
            ];
        } else {
            $response['message'] = 'Error adding recipient: ' . mysqli_stmt_error($stmt);
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
