<?php
$category_id = null;
$category_name = '';
$category_description = '';
$message = '';

// Check if ID is provided in the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $category_id = (int)$_GET['id'];
} else {
    // Redirect to categories page if ID is missing or invalid
    header("Location: index.php?page=categories&error=invalid_id");
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
                // Redirect to categories page with success message
                header("Location: index.php?page=categories&status=updated");
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
                // Redirect if category not found
                header("Location: index.php?page=categories&error=notfound");
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

?>

<h2>Edit Category</h2>

<?php echo $message; // Display error messages if any ?>

<div class="form-container">
    <form action="index.php?page=edit_category&id=<?php echo $category_id; ?>" method="post">
        <div>
            <label for="category_name">Category Name:</label>
            <input type="text" id="category_name" name="category_name" value="<?php echo htmlspecialchars($category_name); ?>" required>
        </div>
        <div>
            <label for="category_description">Description (Optional):</label>
            <textarea id="category_description" name="category_description" rows="3"><?php echo htmlspecialchars($category_description); ?></textarea>
        </div>
        <div>
            <button type="submit" name="update_category">Update Category</button>
            <a href="index.php?page=categories" class="button-like-link">Cancel</a>
        </div>
    </form>
</div>

<style>
/* Re-using some styles from categories.php for consistency, ideally these would be in a global CSS file */
.form-container {
    background-color: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    max-width: 600px; /* Limit width for edit form */
    margin-left: auto;
    margin-right: auto;
}

.form-container h3 {
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
    box-sizing: border-box;
}

.form-container button, .button-like-link {
    background-color: #5cb85c; /* Green for submit */
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    margin-right: 10px;
}

.button-like-link {
    background-color: #f0ad4e; /* Orange for cancel/back */
}

.form-container button:hover {
    background-color: #4cae4c;
}
.button-like-link:hover {
    background-color: #ec971f;
}

.error {
    color: red;
    border: 1px solid red;
    padding: 10px;
    margin-bottom: 15px;
    background-color: #ffe6e6;
}
.success {
    color: green;
    border: 1px solid green;
    padding: 10px;
    margin-bottom: 15px;
    background-color: #e6ffe6;
}

</style> 