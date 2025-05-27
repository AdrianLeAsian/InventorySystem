<?php
$category_id = null;
$category_name = '';
$category_description = '';
$message = '';

// Check if ID is provided in the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $category_id = (int)$_GET['id'];
} else {
    // Redirect to inventory page if ID is missing or invalid
    header("Location: index.php?page=inventory&error=invalid_id");
    exit;
}

// Handle form submission for updating the category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_category'])) {
    $category_name = trim($_POST['category_name']);
    $category_description = trim($_POST['category_description']);

    if (!empty($category_name)) {
        $sql = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $param_name, $param_description, $param_id);
            $param_name = $category_name;
            $param_description = $category_description;
            $param_id = $category_id;

            if (mysqli_stmt_execute($stmt)) {
                // Log the activity
                $log_sql = "INSERT INTO activity_log (activity_type, entity_type, entity_id, entity_name, reason) VALUES (?, ?, ?, ?, ?)";
                if ($log_stmt = mysqli_prepare($link, $log_sql)) {
                    $activity_type = 'category_updated';
                    $entity_type = 'category';
                    $reason = 'Category details updated';
                    mysqli_stmt_bind_param($log_stmt, "ssiss", $activity_type, $entity_type, $category_id, $param_name, $reason);
                    mysqli_stmt_execute($log_stmt);
                    mysqli_stmt_close($log_stmt);
                }

                // Redirect to inventory page with success message
                header("Location: index.php?page=inventory&status=cat_updated");
                exit;
            } else {
                $message = "<p class='error'>Error updating category: " . mysqli_error($link) . "</p>";
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = "<p class='error'>Error preparing update query: " . mysqli_error($link) . "</p>";
        }
    } else {
        $message = "<p class='error'>Category name cannot be empty.</p>";
    }
}

// Fetch the category details for pre-filling the form
if ($category_id) {
    $sql_fetch = "SELECT name, description FROM categories WHERE id = ?";
    if ($stmt_fetch = mysqli_prepare($link, $sql_fetch)) {
        mysqli_stmt_bind_param($stmt_fetch, "i", $param_id_fetch);
        $param_id_fetch = $category_id;

        if (mysqli_stmt_execute($stmt_fetch)) {
            mysqli_stmt_store_result($stmt_fetch);
            if (mysqli_stmt_num_rows($stmt_fetch) == 1) {
                mysqli_stmt_bind_result($stmt_fetch, $fetched_name, $fetched_description);
                if (mysqli_stmt_fetch($stmt_fetch)) {
                    $category_name = $fetched_name;
                    $category_description = $fetched_description;
                }
            } else {
                // Redirect to inventory page if not found
                header("Location: index.php?page=inventory&error=cat_notfound");
                exit;
            }
        } else {
            $message = "<p class='error'>Error fetching category details: " . mysqli_error($link) . "</p>";
        }
        mysqli_stmt_close($stmt_fetch);
    } else {
         $message = "<p class='error'>Error preparing fetch query: " . mysqli_error($link) . "</p>";
    }
}

// Add CSS link in the head section
?>
<link rel="stylesheet" href="css/main.css">

<div class="container">
    <div class="page">
        <header class="d-flex justify-between align-center mb-4">
            <div>
                <h2 class="card__title">Edit Category</h2>
                <p class="text-muted">Update category information.</p>
            </div>
            <a href="index.php?page=inventory" class="btn btn--secondary">Back to Inventory</a>
        </header>

        <?php if (!empty($message)): ?>
            <div class="alert alert--error mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Category Details</h2>
            </div>
            <div class="card__body">
                <form method="POST" class="form">
                    <div class="form__group">
                        <label class="form__label">Category Name</label>
                        <input type="text" name="category_name" class="form__input" value="<?php echo htmlspecialchars($category_name); ?>" required>
                    </div>
                    <div class="form__group">
                        <label class="form__label">Description</label>
                        <textarea name="category_description" class="form__input" rows="3"><?php echo htmlspecialchars($category_description); ?></textarea>
                    </div>
                    <div class="d-flex justify-between mt-4">
                        <button type="submit" name="update_category" class="btn btn--primary">Update Category</button>
                        <a href="index.php?page=inventory" class="btn btn--secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
