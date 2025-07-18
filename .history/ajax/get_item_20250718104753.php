<?php
/**
 * get_item.php
 *
 * This script handles the AJAX request for fetching details of a single item
 * from the inventory system based on its ID. It validates the input ID,
 * queries the database (joining with categories to get category name),
 * and returns a JSON response with the item data or an error message.
 */

// Include the database configuration file
require_once '../config/db.php'; // Adjust path as needed

// Set the content type to JSON for the response
header('Content-Type: application/json');

// Initialize the response array
$response = ['success' => false, 'message' => ''];

// Check if the 'id' parameter is set and not empty in the GET request
if (isset($_GET['id']) && !empty($_GET['id'])) {
    // Sanitize and validate the item ID to ensure it's an integer
    $item_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    // If the item ID is not a valid integer, return an error
    if ($item_id === false) {
        $response['message'] = 'Invalid item ID.';
        echo json_encode($response);
        exit();
    }

    // Prepare a SQL statement to select item details by ID, joining with the categories table
    $sql = "SELECT i.id, i.name, i.category_id, i.barcode, i.quantity, i.unit, i.low_stock_threshold, i.min_stock_level, i.max_stock_level, i.description, i.location, i.updated_at, c.name as category_name
            FROM items i
            JOIN categories c ON i.category_id = c.id
            WHERE i.id = ?";

    // Prepare the statement to prevent SQL injection
    if ($stmt = mysqli_prepare($conn, $sql)) {
        // Bind the item ID parameter to the prepared statement
        mysqli_stmt_bind_param($stmt, "i", $item_id);
        // Execute the prepared statement
        mysqli_stmt_execute($stmt);
        // Get the result set from the executed statement
        $result = mysqli_stmt_get_result($stmt);

        // Fetch the item data as an associative array
        if ($item = mysqli_fetch_assoc($result)) {
            $response['success'] = true;
            // Calculate stock status
            $quantity = $item['quantity'];
            $low_stock_threshold = $item['low_stock_threshold'];
            $min_stock_level = $item['min_stock_level'];
            $max_stock_level = $item['max_stock_level'];

            $stock_status = 'normal'; // Default to normal

            if ($quantity == 0) {
                $stock_status = 'out_of_stock';
            } elseif ($quantity <= $low_stock_threshold || $quantity <= $min_stock_level) {
                $stock_status = 'low_stock';
            } elseif ($max_stock_level > 0 && $quantity >= $max_stock_level) {
                $stock_status = 'surplus';
            }
            
            $response['item'] = $item; // Include the item data in the response
            $response['item']['stock_status'] = $stock_status; // Add the calculated stock status
            // Add formatted_updated_at for consistency with client-side display functions
            $response['item']['formatted_updated_at'] = format_last_activity($item['updated_at']);
        } else {
            // If no item is found with the given ID
            $response['message'] = 'Item not found.';
        }
        mysqli_stmt_close($stmt); // Close the statement
    } else {
        // If database query preparation fails
        $response['message'] = 'Database query preparation failed: ' . mysqli_error($conn);
    }
} else {
    // If no item ID is provided in the request
    $response['message'] = 'No item ID provided.';
}

// Encode the response array to JSON and output it
echo json_encode($response);

/**
 * format_last_activity
 *
 * Formats a given timestamp into a human-readable string, indicating
 * "Today", "Yesterday", "X days ago", or a specific date.
 *
 * @param string $timestamp The timestamp string to format.
 * @return string The formatted date string.
 */
function format_last_activity($timestamp) {
    if (empty($timestamp)) return 'N/A'; // Return 'N/A' if timestamp is empty

    $date = new DateTime($timestamp); // Create DateTime object from timestamp
    $now = new DateTime(); // Create DateTime object for current time
    $interval = $now->diff($date); // Calculate the difference between now and the timestamp

    // Check if the activity was today
    if ($interval->d == 0 && $interval->h < 24) {
        return "Today, " . $date->format('g:i A'); // Format as "Today, 10:30 AM"
    } elseif ($interval->d == 1) { // Check if the activity was yesterday
        return "Yesterday, " . $date->format('g:i A'); // Format as "Yesterday, 10:30 AM"
    } elseif ($interval->days < 7) { // Check if the activity was within a week
        return $interval->days . " days ago"; // Format as "X days ago"
    } else {
        return $date->format('M j, Y'); // Format as "Jan 1, 2023" for older dates
    }
}
?>
