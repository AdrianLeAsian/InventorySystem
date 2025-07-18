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
    $category_reason = trim($_POST['category_reason'] ?? ''); // New reason field

    // Basic validation: Check for valid category ID, non-empty category name, and reason
    if ($category_id === false || $category_id <= 0) {
        $response['message'] = 'Invalid Category ID.';
    } elseif (empty($category_name)) {
        $response['message'] = 'Category Name is required.';
    } elseif (empty($category_reason)) {
        $response['message'] = 'Reason for update is required.';
    } else {
        // Check for duplicate category name, excluding the current category being edited
        $checkSql = "SELECT id FROM categories WHERE name = ? AND id != ?";
        if ($checkStmt = mysqli_prepare($conn, $checkSql)) {
            mysqli_stmt_bind_param($checkStmt, "si", $category_name, $category_id);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_store_result($checkStmt);
            if (mysqli_stmt_num_rows($checkStmt) > 0) {
                $response['message'] = 'Error: A category with this name already exists.';
                echo json_encode($response);
                mysqli_stmt_close($checkStmt);
                exit();
            }
            mysqli_stmt_close($checkStmt);
        } else {
            $response['message'] = 'Database prepare failed for category name check: ' . mysqli_error($conn);
            echo json_encode($response);
            exit();
        }

        // Prepare an update statement for the categories table
        $sql = "UPDATE categories SET name = ?, description = ?, updated_at = NOW() WHERE id = ?";

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
                    // Log the category update in activity_log
                    $logSql = "INSERT INTO activity_log (activity_type, entity_type, entity_id, entity_name, reason) VALUES (?, ?, ?, ?, ?)";
                    if ($logStmt = mysqli_prepare($conn, $logSql)) {
                        $activity_type = 'category_updated';
                        $entity_type = 'category';
                        // Fetch current category name for logging
                        $currentCategoryName = '';
                        $sql_get_current_name = "SELECT name FROM categories WHERE id = ?";
                        if ($stmt_get_name = mysqli_prepare($conn, $sql_get_current_name)) {
                            mysqli_stmt_bind_param($stmt_get_name, "i", $category_id);
                            mysqli_stmt_execute($stmt_get_name);
                            mysqli_stmt_bind_result($stmt_get_name, $currentCategoryName);
                            mysqli_stmt_fetch($stmt_get_name);
                            mysqli_stmt_close($stmt_get_name);
                        }
                        mysqli_stmt_bind_param($logStmt, "ssiss", $activity_type, $entity_type, $category_id, $currentCategoryName, $category_reason);
                        mysqli_stmt_execute($logStmt);
                        mysqli_stmt_close($logStmt);
                    } else {
                        error_log("Error logging category update: " . mysqli_error($conn));
                    }

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
