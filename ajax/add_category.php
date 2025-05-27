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

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $categoryName, $categoryDescription);

        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = 'Category added successfully!';
        } else {
            $response['message'] = 'Error adding category: ' . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'Database prepare failed: ' . mysqli_error($link);
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
