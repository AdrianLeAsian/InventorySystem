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
    if ($_GET['error'] == 'invalid_id_delete') {
        $message .= "<p class='error'>Error: Invalid category ID provided for deletion.</p>";
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

?>

<h2>Manage Categories</h2>

<?php echo $message; // Display success or error messages ?>

<div class="form-container">
    <h3>Add New Category</h3>
    <form action="index.php?page=categories" method="post">
        <div>
            <label for="category_name">Category Name:</label>
            <input type="text" id="category_name" name="category_name" value="<?php echo htmlspecialchars($category_name); ?>" required>
        </div>
        <div>
            <label for="category_description">Description (Optional):</label>
            <textarea id="category_description" name="category_description" rows="3"><?php echo htmlspecialchars($category_description); ?></textarea>
        </div>
        <div>
            <button type="submit" name="add_category">Add Category</button>
        </div>
    </form>
</div>

<div class="table-container">
    <h3>Existing Categories</h3>
    <?php if (!empty($categories)): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $category): ?>
            <tr>
                <td><?php echo htmlspecialchars($category['id']); ?></td>
                <td><?php echo htmlspecialchars($category['name']); ?></td>
                <td><?php echo nl2br(htmlspecialchars($category['description'])); ?></td>
                <td><?php echo htmlspecialchars($category['created_at']); ?></td>
                <td>
                    <a href="index.php?page=edit_category&id=<?php echo $category['id']; ?>">Edit</a> <!-- We will create edit_category.php later -->
                    <a href="index.php?page=delete_category&id=<?php echo $category['id']; ?>" onclick="return confirm('Are you sure you want to delete this category?');">Delete</a> <!-- We will create delete_category.php later -->
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>No categories found. Please add some using the form above.</p>
    <?php endif; ?>
</div>

<style>
.form-container, .table-container {
    background-color: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.form-container h3, .table-container h3 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.form-container div {
    margin-bottom: 10px;
}

.form-container label {
    display: block;
    margin-bottom: 5px;
}

.form-container input[type="text"],
.form-container textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box; /* Important to include padding and border in the element's total width and height */
}

.form-container button {
    background-color: #5cb85c;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.form-container button:hover {
    background-color: #4cae4c;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}

tr:nth-child(even) {background-color: #f2f2f2;}

th {
    background-color: #333;
    color: white;
}

.success {
    color: green;
    border: 1px solid green;
    padding: 10px;
    margin-bottom: 15px;
    background-color: #e6ffe6;
}

.error {
    color: red;
    border: 1px solid red;
    padding: 10px;
    margin-bottom: 15px;
    background-color: #ffe6e6;
}

</style> 