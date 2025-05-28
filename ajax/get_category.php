<?php
require_once '../config/db.php'; // Adjust path as needed

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $category_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if ($category_id === false) {
        $response['message'] = 'Invalid category ID.';
        echo json_encode($response);
        exit();
    }

    $sql = "SELECT id, name, description, created_at FROM categories WHERE id = ?";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $category_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($category = mysqli_fetch_assoc($result)) {
            $response['success'] = true;
            $response['category'] = $category;
        } else {
            $response['message'] = 'Category not found.';
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'Database query preparation failed: ' . mysqli_error($conn);
    }
} else {
    $response['message'] = 'No category ID provided.';
}

echo json_encode($response);
?>
