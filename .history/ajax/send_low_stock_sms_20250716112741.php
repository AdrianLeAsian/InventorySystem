<?php
/**
 * send_low_stock_sms.php
 *
 * This script handles the AJAX request to send an SMS notification
 * for items that are low on stock. It queries the database for items
 * where quantity is below their defined low_stock_threshold, formats
 * the message, and sends it via the Textbelt API.
 */

// Disable display errors and set error reporting for production environment
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Include the database configuration file
require_once '../config/db.php'; // Adjust path as needed

// Set the content type to JSON for the response
header('Content-Type: application/json');

// Initialize the response array
$response = ['success' => false, 'message' => ''];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve recipient phone number from the POST request
    $recipientPhoneNumber = $_POST['recipient_phone_number'] ?? '';

    // Validate recipient phone number
    if (empty($recipientPhoneNumber) || !preg_match('/^\+?[0-9]{10,15}$/', $recipientPhoneNumber)) {
        $response['message'] = 'Invalid recipient phone number format. Please include country code (e.g., +1234567890).';
        echo json_encode($response);
        exit();
    }

    // --- Fetch Low Stock Items ---
    $lowStockItems = [];
    $sql = "SELECT name, quantity, low_stock_threshold FROM items WHERE quantity < low_stock_threshold AND low_stock_threshold > 0";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $lowStockItems[] = $row;
            }
        } else {
            $response['message'] = 'No items are currently low on stock.';
            echo json_encode($response);
            exit();
        }
    } else {
        $response['message'] = 'Database query failed: ' . mysqli_error($conn);
        echo json_encode($response);
        exit();
    }

    // --- Format SMS Message ---
    $message = "Low Stock Alert for InventorySystem:\n";
    foreach ($lowStockItems as $item) {
        $message .= "- " . htmlspecialchars($item['name']) . " (Qty: " . $item['quantity'] . ", Threshold: " . $item['low_stock_threshold'] . ")\n";
    }
    $message .= "\nPlease replenish these items.";

    // --- Send SMS via Textbelt API ---
    // IMPORTANT: Replace 'textbelt_api_key' with your actual Textbelt API key
    $textbeltApiKey = 'textbelt_api_key'; // Placeholder for your API key

    $ch = curl_init('https://textbelt.com/text');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'phone' => $recipientPhoneNumber,
        'message' => $message,
        'key' => $textbeltApiKey,
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $textbeltResponse = curl_exec($ch);
    curl_close($ch);

    $textbeltResult = json_decode($textbeltResponse, true);

    if ($textbeltResult && $textbeltResult['success']) {
        $response['success'] = true;
        $response['message'] = 'SMS sent successfully!';
    } else {
        $response['message'] = 'Failed to send SMS: ' . ($textbeltResult['error'] ?? 'Unknown error from Textbelt.');
    }

} else {
    // If the request method is not POST
    $response['message'] = 'Invalid request method.';
}

// Encode the response array to JSON and output it
echo json_encode($response);
?>
