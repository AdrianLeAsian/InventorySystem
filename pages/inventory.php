<?php
// Ensure $link (mysqli connection) is available, typically from a config file included in index.php
// require_once 'config/db.php'; // Adjust path as needed - This line is likely included in index.php

$message = ''; // Combined message variable

// --- Status/Error Message Handling (from GET parameters) ---
// This section remains as it handles messages passed via URL parameters after redirects from other pages.
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'cat_added') $message .= "<p class='success'>Category added successfully!</p>";
    if ($_GET['status'] == 'cat_updated') $message .= "<p class='success'>Category updated successfully!</p>";
    if ($_GET['status'] == 'cat_deleted') $message .= "<p class='success'>Category deleted successfully!</p>";
    if ($_GET['status'] == 'item_added') $message .= "<p class='success'>Item added successfully!</p>";
    if ($_GET['status'] == 'item_updated') $message .= "<p class='success'>Item updated successfully!</p>";
    if ($_GET['status'] == 'item_deleted') $message .= "<p class='success'>Item deleted successfully!</p>";
    if ($_GET['status'] == 'stock_updated') $message .= "<p class='success'>Stock transaction successful. Quantity updated and logged.</p>";
}
if (isset($_GET['error'])) {
    // Category Errors
    if ($_GET['error'] == 'cat_notfound') $message .= "<p class='error'>Error: Category not found.</p>";
    if ($_GET['error'] == 'cat_invalid_id') $message .= "<p class='error'>Error: Invalid category ID for editing.</p>";
    if ($_GET['error'] == 'cat_invalid_id_delete') $message .= "<p class='error'>Error: Invalid category ID for deletion.</p>";
    if ($_GET['error'] == 'cat_delete_failed') $message .= "<p class='error'>Error: Could not delete category.</p>";
    if ($_GET['error'] == 'cat_prepare_failed') $message .= "<p class='error'>Error: DB operation failed (category).</p>";
    if ($_GET['error'] == 'cat_has_items') {
        $cat_id_error = isset($_GET['cat_id']) ? htmlspecialchars($_GET['cat_id']) : '';
        $message .= "<p class='error'>Error: Category (ID: {$cat_id_error}) has items. Reassign or delete items first.</p>";
    }
    // Item Errors
    if ($_GET['error'] == 'item_notfound') $message .= "<p class='error'>Error: Item not found.</p>";
    if ($_GET['error'] == 'item_invalid_id') $message .= "<p class='error'>Error: Invalid item ID.</p>";
    if ($_GET['error'] == 'item_delete_failed') $message .= "<p class='error'>Error: Could not delete item.</p>";
    if ($_GET['error'] == 'item_prepare_failed') $message .= "<p class='error'>Error: DB operation failed (item).</p>";
    if ($_GET['error'] == 'item_has_logs') $message .= "<p class='error'>Error: Item has inventory logs and cannot be deleted.</p>";
    // Stock Errors
    if ($_GET['error'] == 'stock_failed') $message .= "<p class='error'>Stock transaction failed. No changes were made.</p>";
    if ($_GET['error'] == 'stock_invalid_input') $message .= "<p class='error'>Item, Quantity Change, and Reason are required, and quantity change must be greater than 0.</p>";
}

// The following PHP blocks for handling POST requests (add_category, add_item, stock_in/out)
// are being removed as they should be handled by dedicated processing scripts or AJAX endpoints.
// This page should primarily focus on displaying data.

// Fetch all categories for tabs and item form dropdown
$all_categories = [];
$sql_fetch_categories = "SELECT id, name, description, created_at FROM categories ORDER BY name ASC";
if ($result_categories = mysqli_query($conn, $sql_fetch_categories)) {
    while ($row_cat = mysqli_fetch_assoc($result_categories)) {
        $all_categories[] = $row_cat;
    }
    mysqli_free_result($result_categories);
} else {
    $message .= "<p class='error'>Error fetching categories: " . mysqli_error($conn) . "</p>";
}

// Fetch items for the dropdown in tracking section
$items_options = [];
$sql_items = "SELECT id, name, quantity, unit FROM items ORDER BY name ASC";
if ($result_items_opt = mysqli_query($conn, $sql_items)) {
    while ($row_item_opt = mysqli_fetch_assoc($result_items_opt)) {
        $items_options[] = $row_item_opt;
    }
    mysqli_free_result($result_items_opt);
}

// Fetch recent activity logs to display from the new activity_log table
$inventory_logs = []; 
$sql_fetch_logs = "SELECT id, activity_type, entity_type, entity_id, entity_name, quantity_change, reason, DATE_FORMAT(log_date, '%Y-%m-%d %H:%i:%s') as log_date 
                     FROM activity_log 
                     WHERE entity_type = 'item' AND (activity_type = 'stock_in' OR activity_type = 'stock_out')
                     ORDER BY log_date DESC LIMIT 20";

if ($result_logs = mysqli_query($conn, $sql_fetch_logs)) {
    if (mysqli_num_rows($result_logs) > 0) {
        while ($row_log = mysqli_fetch_assoc($result_logs)) {
            $inventory_logs[] = $row_log;
        }
        mysqli_free_result($result_logs);
    }
} else {
    $message .= "<p class='error'>Error fetching activity logs: " . mysqli_error($conn) . "</p>";
}

// Fetch all items to display
$all_items = [];
// Fetch `updated_at` for "Last Activity". Add `location` if it gets added to DB
$sql_fetch_items = "SELECT i.id, i.name, i.category_id, i.barcode, i.quantity, i.unit, i.low_stock_threshold, i.description, i.updated_at, c.name as category_name
                    FROM items i
                    JOIN categories c ON i.category_id = c.id 
                    ORDER BY i.name ASC";
if ($result_items = mysqli_query($conn, $sql_fetch_items)) {
    while ($row_item = mysqli_fetch_assoc($result_items)) {
        $all_items[] = $row_item;
    }
    mysqli_free_result($result_items);
} else {
    $message .= "<p class='error'>Error fetching items: " . mysqli_error($conn) . "</p>";
}

// Calculate footer summary data
$total_items_count = count($all_items);
$low_stock_items_count = 0;
foreach ($all_items as $item) {
    if ($item['low_stock_threshold'] > 0 && $item['quantity'] <= $item['low_stock_threshold']) {
        $low_stock_items_count++;
    }
}

// Function to format timestamp for "Last Activity" - simple version
function format_last_activity($timestamp) {
    if (empty($timestamp)) return 'N/A';
    $date = new DateTime($timestamp);
    $now = new DateTime();
    $interval = $now->diff($date);

    if ($interval->d == 0 && $interval->h < 24) { // Today
        return "Today, " . $date->format('g:i A');
    } elseif ($interval->d == 1) { // Yesterday
        return "Yesterday, " . $date->format('g:i A');
    } elseif ($interval->days < 7) { // Within a week
        return $interval->days . " days ago";
    } else {
        return $date->format('M j, Y');
    }
}

?>

<link rel="stylesheet" href="css/main.css">

<div class="container">
    <div class="page">
        <header class="d-flex justify-between align-center mb-4">
            <div>
                <h2 class="card__title">Inventory Items</h2>
                <p class="text-muted">Manage and track all your inventory items.</p>
            </div>
            <div class="d-flex gap-2">
            </div>
        </header>

        <div id="gui-message-container" class="mb-4" style="display: none;">
            <div id="gui-message" class="alert">
                <span id="gui-message-text"></span>
                <button class="alert__close-btn" onclick="this.parentElement.style.display='none';">&times;</button>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card__body">
                <div class="d-flex justify-between align-center mb-3">
                    <div class="tabs main-tabs">
                        <button class="tab-button active" data-tab-content="items-content">Inventory Items</button>
                        <button class="tab-button" data-tab-content="categories-content">Categories</button>
                        <button class="tab-button" data-tab-content="tracking-content">Tracking</button>
                    </div>
                    <div class="d-flex gap-2" id="tab-buttons-container">
                        <div id="items-buttons" class="tab-buttons">
                            <button class="btn btn--primary" id="addItemBtn" onclick="toggleModal('addItemModal', true)">+ Add Item</button>
                        </div>
                        <div id="categories-buttons" class="tab-buttons" style="display: none;">
                            <button class="btn btn--secondary" id="addCategoryBtn" onclick="toggleModal('addCategoryModal', true)">+ Add Category</button>
                        </div>
                        <div id="tracking-buttons" class="tab-buttons" style="display: none;">
                            <button class="btn btn--primary" id="logStockBtn">+ Log Stock Movement</button>
                        </div>
                    </div>
                </div>

                <div id="items-content" class="tab-content active">
                    <div class="d-flex justify-between align-center mb-3">
                        <div class="d-flex gap-2 align-center">
                            <select id="categoryFilterSelect" class="form__input">
                                <option value="all">All Categories</option>
                                <?php foreach ($all_categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" id="searchItemsInput" class="form__input" placeholder="Search Items...">
                        </div>
                    </div>

                    <div class="table" id="inventoryItemsTable">
                        <table class="w-100">
                            <colgroup>
                                <col style="width: 15%;"> <!-- Name -->
                                <col style="width: 15%;"> <!-- Category -->
                                <col style="width: 10%;"> <!-- Current Stock -->
                                <col style="width: 10%;"> <!-- Min Stock -->
                                <col style="width: 8%;">  <!-- Unit -->
                                <col style="width: 12%;"> <!-- Location -->
                                <col style="width: 15%;"> <!-- Last Activity -->
                                <col style="width: 8%;">  <!-- Status -->
                                <col style="width: 7%;">  <!-- Actions -->
                            </colgroup>
                            <thead>
                                <tr class="table__header">
                                    <th class="table__cell">Name</th>
                                    <th class="table__cell">Category</th>
                                    <th class="table__cell">Current Stock</th>
                                    <th class="table__cell">Min Stock</th>
                                    <th class="table__cell">Unit</th>
                                    <th class="table__cell">Location</th>
                                    <th class="table__cell">Last Activity</th>
                                    <th class="table__cell">Status</th>
                                    <th class="table__cell">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($all_items)): ?>
                                    <?php foreach ($all_items as $item): ?>
                                        <?php
                                            $isLowStock = ($item['low_stock_threshold'] > 0 && $item['quantity'] <= $item['low_stock_threshold']);
                                            $isOutStock = ($item['quantity'] == 0);
                                            $rowClass = $isLowStock ? 'alert alert--warning' : '';
                                            if ($isOutStock) $rowClass = 'alert alert--error';
                                        ?>
                                        <tr data-item-id="<?php echo htmlspecialchars($item['id']); ?>" data-category-id="<?php echo htmlspecialchars($item['category_id']); ?>" class="table__row <?php echo $rowClass; ?>">
                                            <td class="table__cell" title="<?php echo htmlspecialchars($item['name']); ?>">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </td>
                                            <td class="table__cell"><?php echo htmlspecialchars($item['category_name']); ?></td>
                                            <td class="table__cell"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                            <td class="table__cell"><?php echo htmlspecialchars($item['low_stock_threshold']); ?></td>
                                            <td class="table__cell"><?php echo htmlspecialchars($item['unit']); ?></td>
                                            <td class="table__cell"><?php echo htmlspecialchars($item['location'] ?? 'N/A'); ?></td>
                                            <td class="table__cell"><?php echo format_last_activity($item['updated_at']); ?></td>
                                            <td class="table__cell">
                                                <?php
                                                $status = 'OK';
                                                $status_class = 'btn btn--success';
                                                if ($isLowStock) {
                                                    $status = 'Low Stock';
                                                    $status_class = 'btn btn--warning';
                                                }
                                                if ($isOutStock) {
                                                    $status = 'Out of Stock';
                                                    $status_class = 'btn btn--danger';
                                                }
                                                ?>
                                                <span class="<?php echo $status_class; ?>"><?php echo $status; ?></span>
                                            </td>
                                            <td class="table__cell">
                                                <div class="d-flex gap-2">
                                                    <a href="index.php?page=edit_item&id=<?php echo $item['id']; ?>" class="btn btn--primary">Edit</a>
                                                    <a href="index.php?page=delete_item&id=<?php echo $item['id']; ?>" class="btn btn--danger" onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr class="table__row">
                                        <td colspan="9" class="table__cell text-center">No items found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="categories-content" class="tab-content" style="display: none;">
                    <div class="card">
                        <div class="card__header">
                            <h2 class="card__title">Existing Categories</h2>
                        </div>
                        <div class="card__body">
                            <?php if (!empty($all_categories)): ?>
                                <div class="table">
                                    <table class="w-100" id="inventoryCategoriesTable">
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
                                            <?php foreach ($all_categories as $category): ?>
                                                <tr class="table__row" data-category-id="<?php echo htmlspecialchars($category['id']); ?>">
                                                    <td class="table__cell"><?php echo htmlspecialchars($category['id']); ?></td>
                                                    <td class="table__cell"><?php echo htmlspecialchars($category['name']); ?></td>
                                                    <td class="table__cell"><?php echo nl2br(htmlspecialchars($category['description'] ?? '')); ?></td>
                                            <td class="table__cell"><?php echo htmlspecialchars($category['created_at'] ?? 'N/A'); ?></td>
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

                <div id="tracking-content" class="tab-content" style="display: none;">
                    <div class="card">
                        <div class="card__header">
                            <h2 class="card__title">Recent Inventory Movements</h2>
                            <p class="text-muted">Last 20 movements</p>
                        </div>
                        <div class="card__body">
                            <?php if (!empty($inventory_logs)): ?>
                                <div class="table">
                                    <table class="w-100" id="inventoryTrackingTable">
                                        <thead>
                                            <tr class="table__header">
                                                <th class="table__cell">Log ID</th>
                                                <th class="table__cell">Item Name</th>
                                                <th class="table__cell">Type</th>
                                                <th class="table__cell">Qty Change</th>
                                                <th class="table__cell">Reason</th>
                                                <th class="table__cell">Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($inventory_logs as $log): ?>
                                                <tr class="table__row" data-log-id="<?php echo htmlspecialchars($log['id']); ?>">
                                                    <td class="table__cell"><?php echo htmlspecialchars($log['id']); ?></td>
                                                    <td class="table__cell"><?php echo htmlspecialchars($log['entity_name'] ?? 'N/A'); ?></td>
                                                    <td class="table__cell">
                                                        <span class="btn btn--<?php echo ($log['activity_type'] ?? '') == 'stock_in' ? 'success' : 'danger'; ?>">
                                                            <?php echo htmlspecialchars(ucfirst(str_replace('stock_', '', $log['activity_type'] ?? 'N/A'))); ?>
                                                        </span>
                                                    </td>
                                                    <td class="table__cell"><?php echo htmlspecialchars($log['quantity_change']); ?></td>
                                                    <td class="table__cell"><?php echo htmlspecialchars($log['reason']); ?></td>
                                                    <td class="table__cell"><?php echo htmlspecialchars($log['log_date']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-center text-muted">No inventory movements logged yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/add_item_modal.php'; ?>
<?php include 'includes/add_category_modal.php'; ?>
<?php include 'includes/log_stock_modal.php'; ?>

<script src="js/inventory.js"></script>
<script src="js/inventory_tabs.js"></script>
