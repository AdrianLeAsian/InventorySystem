<?php
/**
 * update_category.php
 *
 * This script handles the AJAX request for updating an existing category's
 * name and description in the inventory system. It validates the input,
 * updates the database, and returns a JSON response indicating success or failure,
 * along with the updated category's details.
 */

// Include the database configuration file
require_once '../config/db.php'; // Adjust path as needed

// Set the content type to JSON for the response
header('Content-Type: application/json');

// Initialize the response array
$response = ['success' => false, 'message' => ''];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve input data from the POST request
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $category_name = trim($_POST['category_name'] ?? '');
    $category_description = trim($_POST['category_description'] ?? '');

    // Basic validation: Check for valid category ID and non-empty category name
    if ($category_id === false || $category_id <= 0) {
        $response['message'] = 'Invalid Category ID.';
    } elseif (empty($category_name)) {
        $response['message'] = 'Category Name is required.';
    } else {
        // Prepare an update statement for the categories table
        $sql = "UPDATE categories SET name = ?, description = ? WHERE id = ?";

        // Prepare the statement to prevent SQL injection
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Bind parameters to the prepared statement
            mysqli_stmt_bind_param($stmt, "ssi", 
                $category_name, 
                $category_description, 
                $category_id
            );

            // Execute the statement
            if (mysqli_stmt_execute($stmt)) {
                // Check if any rows were affected (i.e., if the update actually changed something)
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $response['success'] = true;
                    $response['message'] = 'Category updated successfully!';
                    
                    // Fetch updated category data to send back to client for table refresh
                    $sql_fetch_updated = "SELECT id, name, description, created_at FROM categories WHERE id = ?";
                    if ($stmt_fetch = mysqli_prepare($conn, $sql_fetch_updated)) {
                        mysqli_stmt_bind_param($stmt_fetch, "i", $category_id);
                        mysqli_stmt_execute($stmt_fetch);
                        $result_fetch = mysqli_stmt_get_result($stmt_fetch);
                        if ($updated_category = mysqli_fetch_assoc($result_fetch)) {
                            $response['category'] = $updated_category; // Include the updated category data
                        }
                        mysqli_stmt_close($stmt_fetch); // Close the fetch statement
                    }
                } else {
                    $response['message'] = 'No changes made to category or category not found.';
                }
            } else {
                $response['message'] = 'Error updating category: ' . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt); // Close the update statement
        } else {
            $response['message'] = 'Database query preparation failed: ' . mysqli_error($conn);
        }
    }
} else {
    // If the request method is not POST
    $response['message'] = 'Invalid request method.';
}

// Encode the response array to JSON and output it
echo json_encode($response);
?>
