<?php
require_once '../config/db.php'; // Adjust path as needed

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = $_POST['category_name'] ?? '';
    $categoryDescription = $_POST['category_description'] ?? '';

    // Basic validation
    if (empty($categoryName)) {
        $response['message'] = 'Category Name is required.';
        echo json_encode($response);
        exit();
    }

    // Prepare an insert statement
    $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $categoryName, $categoryDescription);

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
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'Database prepare failed: ' . mysqli_error($conn);
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
