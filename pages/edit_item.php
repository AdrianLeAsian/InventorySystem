<?php
$item_id = null;
$item_name = '';
$item_category_id = '';
$item_barcode = '';
$item_quantity = 0; // Not typically edited here, but fetched for info. Stock changes via tracking.
$item_unit = 'pcs';
$item_low_stock_threshold = 0;
$item_purchase_price = 0.00;
$item_selling_price = 0.00;
$item_description = '';
$message = '';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $item_id = (int)$_GET['id'];
} else {
    header("Location: index.php?page=items&error=invalid_id");
    exit;
}

// Handle form submission for updating the item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_item'])) {
    $item_name = trim($_POST['item_name']);
    $item_category_id = (int)$_POST['item_category_id'];
    $item_barcode = trim($_POST['item_barcode']);
    // $item_quantity = (int)$_POST['item_quantity']; // Quantity is usually updated via stock movements, not direct edit of item properties
    $item_unit = trim($_POST['item_unit']);
    $item_low_stock_threshold = (int)$_POST['item_low_stock_threshold'];
    $item_purchase_price = (float)$_POST['item_purchase_price'];
    $item_selling_price = (float)$_POST['item_selling_price'];
    $item_description = trim($_POST['item_description']);

    if (!empty($item_name) && $item_category_id > 0) {
        $sql = "UPDATE items SET name = ?, category_id = ?, barcode = ?, unit = ?, low_stock_threshold = ?, purchase_price = ?, selling_price = ?, description = ? WHERE id = ?";
        // Note: Quantity is not updated here intentionally.
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sisidddsi", 
                $item_name, 
                $item_category_id, 
                $item_barcode, 
                // $item_quantity, 
                $item_unit, 
                $item_low_stock_threshold, 
                $item_purchase_price, 
                $item_selling_price, 
                $item_description,
                $item_id
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: index.php?page=items&status=updated");
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
    $sql_fetch = "SELECT name, category_id, barcode, quantity, unit, low_stock_threshold, purchase_price, selling_price, description FROM items WHERE id = ?";
    if ($stmt_fetch = mysqli_prepare($link, $sql_fetch)) {
        mysqli_stmt_bind_param($stmt_fetch, "i", $item_id);
        if (mysqli_stmt_execute($stmt_fetch)) {
            mysqli_stmt_store_result($stmt_fetch);
            if (mysqli_stmt_num_rows($stmt_fetch) == 1) {
                mysqli_stmt_bind_result($stmt_fetch, $fetched_name, $fetched_cat_id, $fetched_barcode, $fetched_qty, $fetched_unit, $fetched_low_stock, $fetched_purchase, $fetched_selling, $fetched_desc);
                if (mysqli_stmt_fetch($stmt_fetch)) {
                    $item_name = $fetched_name;
                    $item_category_id = $fetched_cat_id;
                    $item_barcode = $fetched_barcode;
                    $item_quantity = $fetched_qty; // Fetched for display, not for direct edit in this form
                    $item_unit = $fetched_unit;
                    $item_low_stock_threshold = $fetched_low_stock;
                    $item_purchase_price = $fetched_purchase;
                    $item_selling_price = $fetched_selling;
                    $item_description = $fetched_desc;
                }
            } else {
                header("Location: index.php?page=items&error=notfound");
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
?>

<h2>Edit Item</h2>

<?php echo $message; // Display error messages if any ?>

<div class="form-container item-form">
    <form action="index.php?page=edit_item&id=<?php echo $item_id; ?>" method="post">
        <p><strong>Current Quantity:</strong> <?php echo htmlspecialchars($item_quantity); ?> <?php echo htmlspecialchars($item_unit); ?> (Quantity is changed via Stock In/Out operations)</p>
        
        <div>
            <label for="item_name">Item Name:</label>
            <input type="text" id="item_name" name="item_name" value="<?php echo htmlspecialchars($item_name); ?>" required>
        </div>
        <div>
            <label for="item_category_id">Category:</label>
            <select id="item_category_id" name="item_category_id" required>
                <option value="">Select Category</option>
                <?php foreach ($categories_options as $category_opt): ?>
                <option value="<?php echo $category_opt['id']; ?>" <?php echo ($item_category_id == $category_opt['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category_opt['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="item_barcode">Barcode (Optional):</label>
            <input type="text" id="item_barcode" name="item_barcode" value="<?php echo htmlspecialchars($item_barcode); ?>">
        </div>
        <div>
            <label for="item_unit">Unit (e.g., pcs, kg, L):</label>
            <input type="text" id="item_unit" name="item_unit" value="<?php echo htmlspecialchars($item_unit); ?>" placeholder="pcs" required>
        </div>
        <div>
            <label for="item_low_stock_threshold">Low Stock Threshold:</label>
            <input type="number" id="item_low_stock_threshold" name="item_low_stock_threshold" value="<?php echo (int)$item_low_stock_threshold; ?>" min="0">
        </div>
        <div>
            <label for="item_purchase_price">Purchase Price (Optional):</label>
            <input type="number" step="0.01" id="item_purchase_price" name="item_purchase_price" value="<?php echo htmlspecialchars($item_purchase_price); ?>" min="0">
        </div>
        <div>
            <label for="item_selling_price">Selling Price (Optional):</label>
            <input type="number" step="0.01" id="item_selling_price" name="item_selling_price" value="<?php echo htmlspecialchars($item_selling_price); ?>" min="0">
        </div>
        <div>
            <label for="item_description">Description (Optional):</label>
            <textarea id="item_description" name="item_description" rows="3"><?php echo htmlspecialchars($item_description); ?></textarea>
        </div>
        <div>
            <button type="submit" name="update_item">Update Item</button>
            <a href="index.php?page=items" class="button-like-link">Cancel</a>
        </div>
    </form>
</div>

<style>
/* Re-using styles, ideally from a global CSS */
.form-container, .table-container {
    background-color: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}
.item-form div {
    margin-bottom: 10px;
}
.item-form label {
    display: block;
    margin-bottom: 5px;
}
.item-form input[type="text"],
.item-form input[type="number"],
.item-form select,
.item-form textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
}
.item-form button, .button-like-link {
    background-color: #5cb85c; 
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    margin-right: 10px;
}
.button-like-link { background-color: #f0ad4e; }
.item-form button:hover { background-color: #4cae4c; }
.button-like-link:hover { background-color: #ec971f; }
.error { color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px; background-color: #ffe6e6; }
.success { color: green; border: 1px solid green; padding: 10px; margin-bottom: 15px; background-color: #e6ffe6; }
</style> 