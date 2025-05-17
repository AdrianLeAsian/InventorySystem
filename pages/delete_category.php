<?php
// Check if ID is provided in the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $category_id = (int)$_GET['id'];

    // Check if the category has any associated items. If so, prevent deletion or handle accordingly.
    // For now, we'll check and prevent deletion if items exist.
    $sql_check_items = "SELECT COUNT(*) as item_count FROM items WHERE category_id = ?";
    if ($stmt_check = mysqli_prepare($link, $sql_check_items)) {
        mysqli_stmt_bind_param($stmt_check, "i", $category_id);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_bind_result($stmt_check, $item_count);
        mysqli_stmt_fetch($stmt_check);
        mysqli_stmt_close($stmt_check);

        if ($item_count > 0) {
            // Redirect to categories page with an error message
            header("Location: index.php?page=categories&error=has_items&cat_id=" . $category_id);
            exit;
        }
    }

    // Prepare a delete statement if no items are associated
    $sql = "DELETE FROM categories WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $category_id;

        if (mysqli_stmt_execute($stmt)) {
            // Redirect to categories page with success message
            header("Location: index.php?page=categories&status=deleted");
            exit;
        } else {
            // Redirect to categories page with error message
            header("Location: index.php?page=categories&error=delete_failed");
            exit;
        }
        mysqli_stmt_close($stmt);
    } else {
        header("Location: index.php?page=categories&error=prepare_failed");
        exit;
    }
} else {
    // Redirect to categories page if ID is missing or invalid
    header("Location: index.php?page=categories&error=invalid_id_delete");
    exit;
}
?> 