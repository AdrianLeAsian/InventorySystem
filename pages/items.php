<?php
// --- Form Submission Handling (Add Item) ---
$item_name = '';
$item_category_id = '';
$item_barcode = '';
$item_quantity = 0;
$item_unit = 'pcs';
$item_low_stock_threshold = 0;
$item_purchase_price = 0.00;
$item_selling_price = 0.00;
$item_description = '';
$message = '';

// Display messages from URL parameters (e.g., after redirect from edit/delete)
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'added') $message .= "<p class='success'>Item added successfully!</p>";
    if ($_GET['status'] == 'updated') $message .= "<p class='success'>Item updated successfully!</p>";
    if ($_GET['status'] == 'deleted') $message .= "<p class='success'>Item deleted successfully!</p>";
}
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'notfound') $message .= "<p class='error'>Error: Item not found.</p>";
    if ($_GET['error'] == 'invalid_id') $message .= "<p class='error'>Error: Invalid item ID provided.</p>";
    if ($_GET['error'] == 'delete_failed') $message .= "<p class='error'>Error: Could not delete the item. It might have inventory logs.</p>";
    if ($_GET['error'] == 'prepare_failed') $message .= "<p class='error'>Error: Database operation could not be prepared.</p>";
    if ($_GET['error'] == 'has_logs') $message .= "<p class='error'>Error: Item cannot be deleted as it has inventory log entries. Please clear logs first or archive the item (feature not yet implemented).</p>";
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_item'])) {
    $item_name = trim($_POST['item_name']);
    $item_category_id = (int)$_POST['item_category_id'];
    $item_barcode = trim($_POST['item_barcode']);
    $item_quantity = (int)$_POST['item_quantity'];
    $item_unit = trim($_POST['item_unit']);
    $item_low_stock_threshold = (int)$_POST['item_low_stock_threshold'];
    $item_purchase_price = (float)$_POST['item_purchase_price'];
    $item_selling_price = (float)$_POST['item_selling_price'];
    $item_description = trim($_POST['item_description']);

    if (!empty($item_name) && $item_category_id > 0) {
        $sql = "INSERT INTO items (name, category_id, barcode, quantity, unit, low_stock_threshold, purchase_price, selling_price, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sisisidds", 
                $item_name, 
                $item_category_id, 
                $item_barcode, 
                $item_quantity, 
                $item_unit, 
                $item_low_stock_threshold, 
                $item_purchase_price, 
                $item_selling_price, 
                $item_description
            );

            if (mysqli_stmt_execute($stmt)) {
                $message = "<p class='success'>Item added successfully!</p>";
                // Clear form fields after successful submission
                $item_name = $item_barcode = $item_unit = $item_description = '';
                $item_category_id = $item_quantity = $item_low_stock_threshold = 0;
                $item_purchase_price = $item_selling_price = 0.00;
                header("Location: index.php?page=items&status=added"); // Redirect to avoid form resubmission
                exit;
            } else {
                $message = "<p class='error'>Error adding item: " . mysqli_error($link) . "</p>";
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = "<p class='error'>Error preparing query: " . mysqli_error($link) . "</p>";
        }
    } else {
        $message = "<p class='error'>Item Name and Category are required.</p>";
    }
}

// --- Fetch Data for Display ---
// Fetch categories for the dropdown
$categories_options = [];
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
if ($result_cat = mysqli_query($link, $sql_categories)) {
    while ($row_cat = mysqli_fetch_assoc($result_cat)) {
        $categories_options[] = $row_cat;
    }
    mysqli_free_result($result_cat);
}

// Fetch all items to display
$items = [];
$sql_fetch_items = "SELECT i.*, c.name as category_name FROM items i JOIN categories c ON i.category_id = c.id ORDER BY i.name ASC";
if ($result_items = mysqli_query($link, $sql_fetch_items)) {
    if (mysqli_num_rows($result_items) > 0) {
        while ($row_item = mysqli_fetch_assoc($result_items)) {
            $items[] = $row_item;
        }
        mysqli_free_result($result_items);
    }
} else {
    $message .= "<p class='error'>Error fetching items: " . mysqli_error($link) . "</p>";
}
?>

<h2>Manage Inventory Items</h2>

<?php echo $message; // Display success or error messages ?>

<div class="form-container item-form">
    <h3>Add New Item</h3>
    <form action="index.php?page=items" method="post">
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
            <label for="item_quantity">Initial Quantity:</label>
            <input type="number" id="item_quantity" name="item_quantity" value="<?php echo (int)$item_quantity; ?>" min="0" required>
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
            <button type="submit" name="add_item">Add Item</button>
        </div>
    </form>
</div>

<div class="table-container">
    <h3>Existing Items</h3>
    <?php if (!empty($items)): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Category</th>
                <th>Barcode</th>
                <th>Qty</th>
                <th>Unit</th>
                <th>Low Stock At</th>
                <th>Purchase Price</th>
                <th>Selling Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['id']); ?></td>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                <td><?php echo htmlspecialchars($item['barcode']); ?></td>
                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                <td><?php echo htmlspecialchars($item['unit']); ?></td>
                <td><?php echo htmlspecialchars($item['low_stock_threshold']); ?></td>
                <td><?php echo htmlspecialchars(number_format($item['purchase_price'], 2)); ?></td>
                <td><?php echo htmlspecialchars(number_format($item['selling_price'], 2)); ?></td>
                <td>
                    <a href="index.php?page=edit_item&id=<?php echo $item['id']; ?>">Edit</a>
                    <a href="index.php?page=delete_item&id=<?php echo $item['id']; ?>" onclick="return confirm('Are you sure you want to delete this item? This action cannot be undone.');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>No items found. Please add some using the form above.</p>
    <?php endif; ?>
</div>

<style>
/* Styles can be moved to a global style.css later */
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
.item-form button {
    background-color: #5cb85c;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
.item-form button:hover { background-color: #4cae4c; }

table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
tr:nth-child(even) { background-color: #f2f2f2; }
th { background-color: #333; color: white; }

.success { color: green; border: 1px solid green; padding: 10px; margin-bottom: 15px; background-color: #e6ffe6; }
.error { color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px; background-color: #ffe6e6; }
</style> 