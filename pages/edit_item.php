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
    // Purchase and Selling price removed
    $item_description = trim($_POST['item_description']);
    $item_location = trim($_POST['item_location']); // Get location from form

    if (!empty($item_name) && $item_category_id > 0) {
        // Updated SQL to include location and remove purchase/selling prices
        $sql = "UPDATE items SET name = ?, category_id = ?, barcode = ?, unit = ?, low_stock_threshold = ?, description = ?, location = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Adjusted bind_param: s (name), i (cat_id), s (barcode), s (unit), i (low_stock), s (description), s (location), i (id)
            mysqli_stmt_bind_param($stmt, "sisisssi", 
                $item_name, 
                $item_category_id, 
                $item_barcode, 
                $item_unit, 
                $item_low_stock_threshold, 
                $item_description,
                $item_location,
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
    // Updated SQL to fetch location and remove purchase/selling prices
    $sql_fetch = "SELECT name, category_id, barcode, quantity, unit, low_stock_threshold, description, location FROM items WHERE id = ?";
    if ($stmt_fetch = mysqli_prepare($link, $sql_fetch)) {
        mysqli_stmt_bind_param($stmt_fetch, "i", $item_id);
        if (mysqli_stmt_execute($stmt_fetch)) {
            mysqli_stmt_store_result($stmt_fetch);
            if (mysqli_stmt_num_rows($stmt_fetch) == 1) {
                // Adjusted bind_result
                mysqli_stmt_bind_result($stmt_fetch, $fetched_name, $fetched_cat_id, $fetched_barcode, $fetched_qty, $fetched_unit, $fetched_low_stock, $fetched_desc, $fetched_location);
                if (mysqli_stmt_fetch($stmt_fetch)) {
                    $item_name = $fetched_name;
                    $item_category_id = $fetched_cat_id;
                    $item_barcode = $fetched_barcode;
                    $item_quantity = $fetched_qty; 
                    $item_unit = $fetched_unit;
                    $item_low_stock_threshold = $fetched_low_stock;
                    // Purchase and Selling price removed
                    $item_description = $fetched_desc;
                    $item_location = $fetched_location;
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
            <label for="item_location">Location (Optional):</label>
            <input type="text" id="item_location" name="item_location" value="<?php echo htmlspecialchars($item_location); ?>">
        </div>
        <!-- Purchase Price and Selling Price fields removed -->
        <div>
            <label for="item_description">Description (Optional):</label>
            <textarea id="item_description" name="item_description" rows="3"><?php echo htmlspecialchars($item_description); ?></textarea>
        </div>
        <div>
            <button type="submit" name="update_item">Update Item</button>
            <a href="index.php?page=inventory" class="button-like-link">Cancel</a>
        </div>
    </form>
</div>

<style>
/* Re-using styles, ideally from a global CSS file or the main inventory page styles */
.form-container {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px; /* Match inventory page style */
    box-shadow: 0 4px 12px rgba(0,0,0,0.08); /* Match inventory page style */
    margin-bottom: 20px;
    max-width: 700px; /* Control form width */
    margin-left: auto;
    margin-right: auto;
}

.form-container h2 { /* Assuming h2 is used for title, if not, adjust */
    font-size: 20px;
    font-weight: 600;
    margin-top: 0;
    margin-bottom: 25px;
    color: #1a202c;
    padding-bottom: 15px;
    border-bottom: 1px solid #e0e0e0;
}

.item-form div {
    margin-bottom: 15px; /* Consistent spacing */
}
.item-form label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    font-size: 14px;
    color: #4a5568;
}
.item-form input[type="text"],
.item-form input[type="number"],
.item-form select,
.item-form textarea {
    width: 100%;
    padding: 10px 12px; /* Match inventory page modal style */
    border: 1px solid #cbd5e0;
    border-radius: 4px;
    box-sizing: border-box;
    font-size: 14px;
}
.item-form textarea {
    min-height: 80px;
}

.item-form button, .button-like-link {
    padding: 10px 18px; /* Match inventory page btn style */
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
    margin-right: 10px;
    transition: background-color 0.2s ease-in-out, box-shadow 0.2s ease; /* Match */
}

.item-form button {
    background-color: #4a90e2; /* Primary blue - Match inventory page */
    color: white;
}
.item-form button:hover {
    background-color: #357abd; /* Darker blue - Match */
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.button-like-link { 
    background-color: #e2e8f0; /* Light gray - Match inventory page secondary */
    color: #2d3748;
    border: 1px solid #cbd5e0;
}
.button-like-link:hover { 
    background-color: #cbd5e0;
    border-color: #a0aec0;
}

.error, .success { /* Match inventory page message style */
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 4px;
    font-size: 14px;
}
.error { 
    color: #c53030; /* Darker red text */
    border: 1px solid #fed7d7; /* Lighter red border */
    background-color: #fff5f5; /* Light red background */
}
.success { 
    color: #2c7a7b; /* Darker green text */
    border: 1px solid #c6f6d5; /* Lighter green border */
    background-color: #e6fffa; /* Light green background */
}

/* General body styling (if this page is standalone and not part of a template with global styles) */
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    background-color: #f4f7fc;
    color: #333;
    padding: 20px;
    margin:0;
}
</style> 