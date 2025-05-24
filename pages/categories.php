<?php

// Handle form submission for adding a new category
$category_name = '';
$category_description = '';
$message = '';

// Display messages from URL parameters (e.g., after redirect from edit/delete)
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'updated') {
        $message .= "<p class='success'>Category updated successfully!</p>";
    }
    if ($_GET['status'] == 'deleted') {
        $message .= "<p class='success'>Category deleted successfully!</p>";
    }
}
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'notfound') {
        $message .= "<p class='error'>Error: Category not found.</p>";
    }
    if ($_GET['error'] == 'invalid_id') {
        $message .= "<p class='error'>Error: Invalid category ID provided for editing.</p>";
    }
    if ($_GET['error'] == 'delete_failed') {
        $message .= "<p class='error'>Error: Could not delete the category.</p>";
    }
    if ($_GET['error'] == 'prepare_failed') {
        $message .= "<p class='error'>Error: Database operation could not be prepared.</p>";
    }
    if ($_GET['error'] == 'has_items') {
        $cat_id_error = isset($_GET['cat_id']) ? htmlspecialchars($_GET['cat_id']) : '';
        $message .= "<p class='error'>Error: Category (ID: {$cat_id_error}) cannot be deleted because it has associated items. Please delete or reassign items first.</p>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    $category_description = trim($_POST['category_description']);

    if (!empty($category_name)) {
        // Prepare an insert statement
        $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $param_name, $param_description);

            $param_name = $category_name;
            $param_description = $category_description;

            if (mysqli_stmt_execute($stmt)) {
                $message = "<p class='success'>Category added successfully!</p>";
                $category_name = ''; // Clear form fields
                $category_description = '';
            } else {
                $message = "<p class='error'>Error: Could not execute the query: " . mysqli_error($link) . "</p>";
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = "<p class='error'>Error: Could not prepare the query: " . mysqli_error($link) . "</p>";
        }
    } else {
        $message = "<p class='error'>Category name cannot be empty.</p>";
    }
}

// Fetch all categories to display
$categories = [];
$sql_fetch = "SELECT id, name, description, created_at FROM categories ORDER BY name ASC";
if ($result = mysqli_query($link, $sql_fetch)) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
        mysqli_free_result($result);
    } else {
        // $message .= "<p>No categories found.</p>"; // Append to message or handle separately
    }
} else {
    $message .= "<p class='error'>Error: Could not able to execute $sql_fetch. " . mysqli_error($link) . "</p>";
}

// Add CSS link in the head section
?>
<link rel="stylesheet" href="css/main.css">

<div class="container">
    <div class="page">
        <header class="d-flex justify-between align-center mb-4">
            <div>
                <h2 class="card__title">Manage Categories</h2>
                <p class="text-muted">Add, edit, or remove inventory categories.</p>
            </div>
        </header>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo strpos($message, 'success') !== false ? 'alert--success' : 'alert--error'; ?> mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid--2-cols gap-4">
            <div class="card">
                <div class="card__header">
                    <h2 class="card__title">Add New Category</h2>
                </div>
                <div class="card__body">
                    <form method="POST" class="form" action="index.php?page=categories">
                        <div class="form__group">
                            <label class="form__label">Category Name</label>
                            <input type="text" name="category_name" class="form__input" value="<?php echo htmlspecialchars($category_name); ?>" required>
                        </div>
                        <div class="form__group">
                            <label class="form__label">Description</label>
                            <textarea name="category_description" class="form__input" rows="3"><?php echo htmlspecialchars($category_description); ?></textarea>
                        </div>
                        <div class="mt-4">
                            <button type="submit" name="add_category" class="btn btn--primary">Add Category</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card__header">
                    <h2 class="card__title">Existing Categories</h2>
                </div>
                <div class="card__body">
                    <?php if (!empty($categories)): ?>
                        <div class="table">
                            <table class="w-100">
                                <thead>
                                    <tr class="table__header">
                                        <th class="table__cell">ID</th>
                                        <th class="table__cell">Name</th>
                                        <th class="table__cell">Description</th>
                                        <th class="table__cell">Created At</th>
                                        <th class="table__cell">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr class="table__row">
                                            <td class="table__cell"><?php echo htmlspecialchars($category['id']); ?></td>
                                            <td class="table__cell"><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td class="table__cell"><?php echo nl2br(htmlspecialchars($category['description'])); ?></td>
                                            <td class="table__cell"><?php echo htmlspecialchars($category['created_at']); ?></td>
                                            <td class="table__cell">
                                                <div class="d-flex gap-2">
                                                    <a href="index.php?page=edit_category&id=<?php echo $category['id']; ?>" class="btn btn--primary">Edit</a>
                                                    <a href="index.php?page=delete_category&id=<?php echo $category['id']; ?>" 
                                                       class="btn btn--danger" 
                                                       onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">No categories found. Please add some using the form.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
