<?php
require_once '../config/db.php'; // Adjust path as needed

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $category_name = trim($_POST['category_name'] ?? '');
    $category_description = trim($_POST['category_description'] ?? '');

    // Basic validation
    if ($category_id === false || $category_id <= 0) {
        $response['message'] = 'Invalid Category ID.';
    } elseif (empty($category_name)) {
        $response['message'] = 'Category Name is required.';
    } else {
        // Update category in database
        $sql = "UPDATE categories SET name = ?, description = ? WHERE id = ?";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", 
                $category_name, 
                $category_description, 
                $category_id
            );

            if (mysqli_stmt_execute($stmt)) {
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
                            $response['category'] = $updated_category;
                        }
                        mysqli_stmt_close($stmt_fetch);
                    }
                } else {
                    $response['message'] = 'No changes made to category or category not found.';
                }
            } else {
                $response['message'] = 'Error updating category: ' . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = 'Database query preparation failed: ' . mysqli_error($conn);
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
