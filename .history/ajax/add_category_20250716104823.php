<?php
/**
 * add_category.php
 *
 * This script handles the AJAX request for adding a new category to the inventory system.
 * It performs validation, inserts the category into the database, and returns a JSON response
 * indicating success or failure, along with the newly added category's details.
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
    $categoryName = $_POST['category_name'] ?? '';
    $categoryDescription = $_POST['category_description'] ?? '';

    // Basic validation: Check if category name is provided
    if (empty($categoryName)) {
        $response['message'] = 'Category Name is required.';
        echo json_encode($response);
        exit();
    }

    // Prepare an insert statement to add the new category
    $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        // Bind parameters to the prepared statement
        mysqli_stmt_bind_param($stmt, "ss", $categoryName, $categoryDescription);

        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            $newCategoryId = mysqli_insert_id($conn); // Get the ID of the newly inserted category

            // Fetch the newly added category's full details
            $sql_fetch_new_category = "SELECT id, name, description, created_at FROM categories WHERE id = ?";
            if ($stmt_fetch = mysqli_prepare($conn, $sql_fetch_new_category)) {
                mysqli_stmt_bind_param($stmt_fetch, "i", $newCategoryId);
                mysqli_stmt_execute($stmt_fetch);
                $result_fetch = mysqli_stmt_get_result($stmt_fetch);
                $newCategory = mysqli_fetch_assoc($result_fetch);
                mysqli_stmt_close($stmt_fetch);

                $response['success'] = true;
                $response['message'] = 'Category added successfully!';
                $response['category'] = $newCategory; // Include the new category data in the response
            } else {
                $response['message'] = 'Error fetching new category details: ' . mysqli_error($conn);
            }
        } else {
            $response['message'] = 'Error adding category: ' . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt); // Close the insert statement
    } else {
        $response['message'] = 'Database prepare failed: ' . mysqli_error($conn);
    }
} else {
    // If the request method is not POST
    $response['message'] = 'Invalid request method.';
}

// Encode the response array to JSON and output it
echo json_encode($response);
?>
