<?php
/**
 * get_category.php
 *
 * This script handles the AJAX request for fetching details of a single category
 * from the inventory system based on its ID. It validates the input ID,
 * queries the database, and returns a JSON response with the category data
 * or an error message.
 */

// Include the database configuration file
require_once '../config/db.php'; // Adjust path as needed

// Set the content type to JSON for the response
header('Content-Type: application/json');

// Initialize the response array
$response = ['success' => false, 'message' => ''];

// Check if the 'id' parameter is set and not empty in the GET request
if (isset($_GET['id']) && !empty($_GET['id'])) {
    // Sanitize and validate the category ID to ensure it's an integer
    $category_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    // If the category ID is not a valid integer, return an error
    if ($category_id === false) {
        $response['message'] = 'Invalid category ID.';
        echo json_encode($response);
        exit();
    }

    // Prepare a SQL statement to select category details by ID
    $sql = "SELECT id, name, description, created_at FROM categories WHERE id = ?";

    // Prepare the statement to prevent SQL injection
    if ($stmt = mysqli_prepare($conn, $sql)) {
        // Bind the category ID parameter to the prepared statement
        mysqli_stmt_bind_param($stmt, "i", $category_id);
        // Execute the prepared statement
        mysqli_stmt_execute($stmt);
        // Get the result set from the executed statement
        $result = mysqli_stmt_get_result($stmt);

        // Fetch the category data as an associative array
        if ($category = mysqli_fetch_assoc($result)) {
            $response['success'] = true;
            $response['category'] = $category; // Include the category data in the response
        } else {
            // If no category is found with the given ID
            $response['message'] = 'Category not found.';
        }
        mysqli_stmt_close($stmt); // Close the statement
    } else {
        // If database query preparation fails
        $response['message'] = 'Database query preparation failed: ' . mysqli_error($conn);
    }
} else {
    // If no category ID is provided in the request
    $response['message'] = 'No category ID provided.';
}

// Encode the response array to JSON and output it
echo json_encode($response);
?>
