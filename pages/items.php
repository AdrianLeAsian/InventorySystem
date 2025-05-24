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

// --- FORM HANDLING AND REDIRECTS HAPPEN HERE, BEFORE ANY HTML ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_item'])) {
    $item_name = trim($_POST['item_name']);
    $item_category_id = (int)$_POST['item_category_id'];
    $item_barcode = !empty($_POST['item_barcode']) ? trim($_POST['item_barcode']) : null;
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
                header("Location: index.php?page=items&status=added");
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

<link rel="stylesheet" href="css/main.css">

<div class="container">
    <div class="page">
        <header class="d-flex justify-between align-center mb-4">
            <div>
                <h2 class="card__title">Manage Inventory Items</h2>
                <p class="text-muted">Add, edit, and manage your inventory items.</p>
            </div>
        </header>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo strpos($message, 'success') !== false ? 'alert--success' : 'alert--error'; ?> mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card__header">
                <h2 class="card__title">Add New Item</h2>
            </div>
            <div class="card__body">
                <form action="index.php?page=items" method="post" class="form">
                    <div class="grid grid--2-cols gap-4">
                        <div class="form__group">
                            <label class="form__label">Item Name</label>
                            <input type="text" id="item_name" name="item_name" class="form__input" value="<?php echo htmlspecialchars($item_name); ?>" required>
                        </div>
                        <div class="form__group">
                            <label class="form__label">Category</label>
                            <select id="item_category_id" name="item_category_id" class="form__input" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories_options as $category_opt): ?>
                                    <option value="<?php echo $category_opt['id']; ?>" <?php echo ($item_category_id == $category_opt['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category_opt['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form__group">
                            <label class="form__label">Barcode (Optional)</label>
                            <input type="text" id="item_barcode" name="item_barcode" class="form__input" value="<?php echo htmlspecialchars($item_barcode); ?>">
                        </div>
                        <div class="form__group">
                            <label class="form__label">Initial Quantity</label>
                            <input type="number" id="item_quantity" name="item_quantity" class="form__input" value="<?php echo (int)$item_quantity; ?>" min="0" required>
                        </div>
                        <div class="form__group">
                            <label class="form__label">Unit (e.g., pcs, kg, L)</label>
                            <input type="text" id="item_unit" name="item_unit" class="form__input" value="<?php echo htmlspecialchars($item_unit); ?>" placeholder="pcs" required>
                        </div>
                        <div class="form__group">
                            <label class="form__label">Low Stock Threshold</label>
                            <input type="number" id="item_low_stock_threshold" name="item_low_stock_threshold" class="form__input" value="<?php echo (int)$item_low_stock_threshold; ?>" min="0">
                        </div>
                        <div class="form__group">
                            <label class="form__label">Purchase Price (Optional)</label>
                            <input type="number" step="0.01" id="item_purchase_price" name="item_purchase_price" class="form__input" value="<?php echo htmlspecialchars($item_purchase_price); ?>" min="0">
                        </div>
                        <div class="form__group">
                            <label class="form__label">Selling Price (Optional)</label>
                            <input type="number" step="0.01" id="item_selling_price" name="item_selling_price" class="form__input" value="<?php echo htmlspecialchars($item_selling_price); ?>" min="0">
                        </div>
                        <div class="form__group grid--span-2">
                            <label class="form__label">Description (Optional)</label>
                            <textarea id="item_description" name="item_description" class="form__input" rows="3"><?php echo htmlspecialchars($item_description); ?></textarea>
                        </div>
                        <div class="form__group grid--span-2">
                            <button type="submit" name="add_item" class="btn btn--primary">Add Item</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Existing Items</h2>
            </div>
            <div class="card__body">
                <?php if (!empty($items)): ?>
                    <div class="table">
                        <table class="w-100">
                            <thead>
                                <tr class="table__header">
                                    <th class="table__cell">ID</th>
                                    <th class="table__cell">Name</th>
                                    <th class="table__cell">Category</th>
                                    <th class="table__cell">Barcode</th>
                                    <th class="table__cell">Qty</th>
                                    <th class="table__cell">Unit</th>
                                    <th class="table__cell">Low Stock At</th>
                                    <th class="table__cell">Purchase Price</th>
                                    <th class="table__cell">Selling Price</th>
                                    <th class="table__cell">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr class="table__row">
                                        <td class="table__cell"><?php echo htmlspecialchars($item['id']); ?></td>
                                        <td class="table__cell"><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td class="table__cell"><?php echo htmlspecialchars($item['category_name']); ?></td>
                                        <td class="table__cell"><?php echo htmlspecialchars($item['barcode']); ?></td>
                                        <td class="table__cell"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                        <td class="table__cell"><?php echo htmlspecialchars($item['unit']); ?></td>
                                        <td class="table__cell"><?php echo htmlspecialchars($item['low_stock_threshold']); ?></td>
                                        <td class="table__cell"><?php echo htmlspecialchars(number_format($item['purchase_price'], 2)); ?></td>
                                        <td class="table__cell"><?php echo htmlspecialchars(number_format($item['selling_price'], 2)); ?></td>
                                        <td class="table__cell">
                                            <div class="d-flex gap-2">
                                                <a href="index.php?page=edit_item&id=<?php echo $item['id']; ?>" class="btn btn--secondary btn--sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="index.php?page=delete_item&id=<?php echo $item['id']; ?>" class="btn btn--danger btn--sm" title="Delete" onclick="return confirm('Are you sure you want to delete this item? This action cannot be undone.');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No items found. Please add some using the form above.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// ... existing JavaScript code ...
</script> 