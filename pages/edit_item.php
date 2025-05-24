<?php
$item_id = null;
$item_name = '';
$item_category_id = '';
$item_barcode = '';
$item_quantity = 0; // Fetched for info; not typically edited directly here.
$item_unit = 'pcs';
$item_low_stock_threshold = 0;
// $item_purchase_price = 0.00; // Removed
// $item_selling_price = 0.00; // Removed
$item_description = '';
$item_location = ''; // Added Location field
$message = '';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $item_id = (int)$_GET['id'];
} else {
    // Redirect to the main inventory page if ID is invalid
    header("Location: index.php?page=inventory&error=item_invalid_id");
    exit;
}

// Handle form submission for updating the item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_item'])) {
    $item_name = trim($_POST['item_name']);
    $item_category_id = (int)$_POST['item_category_id'];
    $item_barcode = trim($_POST['item_barcode']);
    $item_unit = trim($_POST['item_unit']);
    $item_low_stock_threshold = (int)$_POST['item_low_stock_threshold'];
    $item_description = trim($_POST['item_description']);

    if (!empty($item_name) && $item_category_id > 0) {
        // Updated SQL to remove location field
        $sql = "UPDATE items SET name = ?, category_id = ?, barcode = ?, unit = ?, low_stock_threshold = ?, description = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Adjusted bind_param to remove location
            mysqli_stmt_bind_param($stmt, "sisissi", 
                $item_name, 
                $item_category_id, 
                $item_barcode, 
                $item_unit, 
                $item_low_stock_threshold, 
                $item_description,
                $item_id
            );

            if (mysqli_stmt_execute($stmt)) {
                // Redirect to the main inventory page with status
                header("Location: index.php?page=inventory&status=item_updated");
                exit;
            } else {
                $message = "<p class='error'>Error updating item: " . mysqli_error($link) . "</p>";
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = "<p class='error'>Error preparing update query: " . mysqli_error($link) . "</p>";
        }
    } else {
        $message = "<p class='error'>Item Name and Category are required.</p>";
    }
}

// Fetch categories for the dropdown
$categories_options = [];
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
if ($result_cat = mysqli_query($link, $sql_categories)) {
    while ($row_cat = mysqli_fetch_assoc($result_cat)) {
        $categories_options[] = $row_cat;
    }
    mysqli_free_result($result_cat);
}

// Fetch the item details for pre-filling the form
if ($item_id) {
    // Updated SQL to remove location field
    $sql_fetch = "SELECT name, category_id, barcode, quantity, unit, low_stock_threshold, description FROM items WHERE id = ?";
    if ($stmt_fetch = mysqli_prepare($link, $sql_fetch)) {
        mysqli_stmt_bind_param($stmt_fetch, "i", $item_id);
        if (mysqli_stmt_execute($stmt_fetch)) {
            mysqli_stmt_store_result($stmt_fetch);
            if (mysqli_stmt_num_rows($stmt_fetch) == 1) {
                // Adjusted bind_result to remove location
                mysqli_stmt_bind_result($stmt_fetch, $fetched_name, $fetched_cat_id, $fetched_barcode, $fetched_qty, $fetched_unit, $fetched_low_stock, $fetched_desc);
                if (mysqli_stmt_fetch($stmt_fetch)) {
                    $item_name = $fetched_name;
                    $item_category_id = $fetched_cat_id;
                    $item_barcode = $fetched_barcode;
                    $item_quantity = $fetched_qty; 
                    $item_unit = $fetched_unit;
                    $item_low_stock_threshold = $fetched_low_stock;
                    $item_description = $fetched_desc;
                }
            } else {
                // Redirect to the main inventory page if item not found
                header("Location: index.php?page=inventory&error=item_notfound");
                exit;
            }
        } else {
            $message = "<p class='error'>Error fetching item details: " . mysqli_error($link) . "</p>";
        }
        mysqli_stmt_close($stmt_fetch);
    } else {
         $message = "<p class='error'>Error preparing item fetch query: " . mysqli_error($link) . "</p>";
    }
}

// Add CSS link in the head section
?>
<link rel="stylesheet" href="css/main.css">

<div class="container">
    <div class="page">
        <header class="d-flex justify-between align-center mb-4">
            <div>
                <h2 class="card__title">Edit Item</h2>
                <p class="text-muted">Update item information.</p>
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
                <h2 class="card__title">Item Details</h2>
            </div>
            <div class="card__body">
                <div class="alert alert--info mb-4">
                    <strong>Current Quantity:</strong> <?php echo htmlspecialchars($item_quantity); ?> <?php echo htmlspecialchars($item_unit); ?> 
                    <span class="text-muted">(Quantity is changed via Stock In/Out operations)</span>
                </div>

                <form method="POST" class="form">
                    <div class="form__group">
                        <label class="form__label">Item Name</label>
                        <input type="text" name="item_name" class="form__input" value="<?php echo htmlspecialchars($item_name); ?>" required>
                    </div>

                    <div class="form__group">
                        <label class="form__label">Category</label>
                        <select name="item_category_id" class="form__input" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories_options as $category_opt): ?>
                                <option value="<?php echo $category_opt['id']; ?>" <?php echo ($item_category_id == $category_opt['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category_opt['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form__group">
                        <label class="form__label">Barcode</label>
                        <input type="text" name="item_barcode" class="form__input" value="<?php echo htmlspecialchars($item_barcode); ?>">
                    </div>

                    <div class="form__group">
                        <label class="form__label">Unit</label>
                        <input type="text" name="item_unit" class="form__input" value="<?php echo htmlspecialchars($item_unit); ?>" placeholder="pcs" required>
                    </div>

                    <div class="form__group">
                        <label class="form__label">Low Stock Threshold</label>
                        <input type="number" name="item_low_stock_threshold" class="form__input" value="<?php echo (int)$item_low_stock_threshold; ?>" min="0">
                    </div>

                    <div class="form__group">
                        <label class="form__label">Description</label>
                        <textarea name="item_description" class="form__input" rows="3"><?php echo htmlspecialchars($item_description); ?></textarea>
                    </div>

                    <div class="d-flex justify-between mt-4">
                        <button type="submit" name="update_item" class="btn btn--primary">Update Item</button>
                        <a href="index.php?page=inventory" class="btn btn--secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
