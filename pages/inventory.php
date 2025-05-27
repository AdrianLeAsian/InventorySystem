<?php
// Ensure $link (mysqli connection) is available, typically from a config file included in index.php
require_once 'config/db.php'; // Adjust path as needed

$message = ''; // Combined message variable

// --- Status/Error Message Handling (from GET parameters) ---
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

// --- CATEGORY LOGIC (Add Category Modal Submission) ---
$category_name_form = ''; // Use distinct variable names for form values
$category_description_form = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    $category_name_form = trim($_POST['category_name']);
    $category_description_form = trim($_POST['category_description']);

    if (!empty($category_name_form)) {
        $sql_cat_insert = "INSERT INTO categories (name, description) VALUES (?, ?)";
        if ($stmt_cat = mysqli_prepare($link, $sql_cat_insert)) {
            mysqli_stmt_bind_param($stmt_cat, "ss", $category_name_form, $category_description_form);
            if (mysqli_stmt_execute($stmt_cat)) {
                // Log the activity
                $last_id = mysqli_insert_id($link);
                $log_sql = "INSERT INTO activity_log (activity_type, entity_type, entity_id, entity_name, reason) VALUES (?, ?, ?, ?, ?)";
                if ($log_stmt = mysqli_prepare($link, $log_sql)) {
                    $activity_type = 'category_added';
                    $entity_type = 'category';
                    $reason = 'New category added';
                    mysqli_stmt_bind_param($log_stmt, "ssiss", $activity_type, $entity_type, $last_id, $category_name_form, $reason);
                    mysqli_stmt_execute($log_stmt);
                    mysqli_stmt_close($log_stmt);
                }
                header("Location: index.php?page=inventory&status=cat_added");
                exit;
            } else {
                $message .= "<p class='error'>Error adding category: " . mysqli_error($link) . "</p>";
            }
            mysqli_stmt_close($stmt_cat);
        } else {
            $message .= "<p class='error'>Error preparing category query: " . mysqli_error($link) . "</p>";
        }
    } else {
        $message .= "<p class='error'>Category name cannot be empty.</p>";
    }
}

// --- ITEM LOGIC (Add Item Modal Submission) ---
$item_name_form = '';
$item_category_id_form = '';
$item_barcode_form = '';
$item_quantity_form = 0;
$item_unit_form = 'pcs';
$item_low_stock_threshold_form = 0;
$item_description_form = '';
// Add new fields for item form if they become part of DB
 $item_location_form = ''; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_item'])) {
    $item_name_form = trim($_POST['item_name']);
    $item_category_id_form = (int)$_POST['item_category_id'];
    $item_barcode_form = trim($_POST['item_barcode']);
    $item_quantity_form = (int)$_POST['item_quantity'];
    $item_unit_form = trim($_POST['item_unit']);
    $item_low_stock_threshold_form = (int)$_POST['item_low_stock_threshold'];
    $item_description_form = trim($_POST['item_description']);
     $item_location_form = trim($_POST['item_location']);  

if (!empty($item_name_form) && $item_category_id_form > 0 && !empty($item_barcode_form)) {
        // Modify SQL if new fields like 'location' are added to 'items' table
        $sql_item_insert = "INSERT INTO items (name, category_id, barcode, quantity, unit, low_stock_threshold, description, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt_item = mysqli_prepare($link, $sql_item_insert)) {
            // Adjust bind_param if new fields are added: e.g., "sisisiddsS" (add S for location)
            mysqli_stmt_bind_param($stmt_item, "sisisiss", 
                $item_name_form, 
                $item_category_id_form, 
                $item_barcode_form, 
                $item_quantity_form, 
                $item_unit_form, 
                $item_low_stock_threshold_form, 
                $item_description_form,
                $item_location_form
            );

            if (mysqli_stmt_execute($stmt_item)) {
                // Log the activity
                $last_id = mysqli_insert_id($link);
                $log_sql = "INSERT INTO activity_log (activity_type, entity_type, entity_id, entity_name, reason) VALUES (?, ?, ?, ?, ?)";
                if ($log_stmt = mysqli_prepare($link, $log_sql)) {
                    $activity_type = 'item_added';
                    $entity_type = 'item';
                    $reason = 'New item added';
                    mysqli_stmt_bind_param($log_stmt, "ssiss", $activity_type, $entity_type, $last_id, $item_name_form, $reason);
                    mysqli_stmt_execute($log_stmt);
                    mysqli_stmt_close($log_stmt);
                }
                header("Location: index.php?page=inventory&status=item_added");
                exit;
            } else {
                $message .= "<p class='error'>Error adding item: " . mysqli_error($link) . "</p>";
            }
            mysqli_stmt_close($stmt_item);
        } else {
            $message .= "<p class='error'>Error preparing item query: " . mysqli_error($link) . "</p>";
        }
    } else {
        $message .= "<p class='error'>Item Name, Category, and Barcode are required.</p>";
    }
}

// --- TRACKING LOGIC (Stock In/Out Form Submission) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['stock_in']) || isset($_POST['stock_out']))) {
    $item_id = (int)$_POST['item_id'];
    $quantity_change = (int)$_POST['quantity_change'];
    $reason = trim($_POST['reason']);
    $log_type = isset($_POST['stock_in']) ? 'in' : 'out';

    if ($item_id > 0 && $quantity_change > 0) {
        mysqli_autocommit($link, false); // Start transaction
        $success = true;

        // 1. Update item quantity
        $current_quantity_sql = "SELECT quantity FROM items WHERE id = ? FOR UPDATE"; // Lock row
        $current_quantity = 0;
        if($stmt_curr_qty = mysqli_prepare($link, $current_quantity_sql)){
            mysqli_stmt_bind_param($stmt_curr_qty, "i", $item_id);
            mysqli_stmt_execute($stmt_curr_qty);
            mysqli_stmt_bind_result($stmt_curr_qty, $current_quantity);
            mysqli_stmt_fetch($stmt_curr_qty);
            mysqli_stmt_close($stmt_curr_qty);
        } else {
            $message .= "<p class='error'>Error fetching current quantity.</p>";
            $success = false;
        }

        if ($success) {
            $new_quantity = 0;
            if ($log_type == 'in') {
                $new_quantity = $current_quantity + $quantity_change;
            } else { // 'out'
                if ($current_quantity >= $quantity_change) {
                    $new_quantity = $current_quantity - $quantity_change;
                } else {
                    $message .= "<p class='error'>Error: Not enough stock for 'Stock Out'. Available: {$current_quantity}</p>";
                    $success = false;
                }
            }

            if ($success) {
                $sql_update_item = "UPDATE items SET quantity = ? WHERE id = ?";
                if ($stmt_update = mysqli_prepare($link, $sql_update_item)) {
                    mysqli_stmt_bind_param($stmt_update, "ii", $new_quantity, $item_id);
                    if (!mysqli_stmt_execute($stmt_update)) {
                        $message .= "<p class='error'>Error updating item quantity: " . mysqli_error($link) . "</p>";
                        $success = false;
                    }
                    mysqli_stmt_close($stmt_update);
                } else {
                    $message .= "<p class='error'>Error preparing item update query: " . mysqli_error($link) . "</p>";
                    $success = false;
                }
            }
        }

        // Fetch item name before logging
        $item_name_for_log = '';
        $sql_fetch_item_name = "SELECT name FROM items WHERE id = ?";
        if ($stmt_fetch_item_name = mysqli_prepare($link, $sql_fetch_item_name)) {
            mysqli_stmt_bind_param($stmt_fetch_item_name, "i", $item_id);
            mysqli_stmt_execute($stmt_fetch_item_name);
            mysqli_stmt_bind_result($stmt_fetch_item_name, $fetched_item_name);
            mysqli_stmt_fetch($stmt_fetch_item_name);
            $item_name_for_log = $fetched_item_name;
            mysqli_stmt_close($stmt_fetch_item_name);
        }

        // 2. Add entry to activity_log
        if ($success) {
            $activity_type = 'stock_' . $log_type; // 'stock_in' or 'stock_out'
            $entity_type = 'item';
            $log_sql = "INSERT INTO activity_log (activity_type, entity_type, entity_id, entity_name, quantity_change, reason) VALUES (?, ?, ?, ?, ?, ?)";
            if ($stmt_log = mysqli_prepare($link, $log_sql)) {
                mysqli_stmt_bind_param($stmt_log, "ssisss", $activity_type, $entity_type, $item_id, $item_name_for_log, $quantity_change, $reason);
                if (!mysqli_stmt_execute($stmt_log)) {
                    $message .= "<p class='error'>Error logging stock movement: " . mysqli_error($link) . "</p>";
                    $success = false;
                }
                mysqli_stmt_close($stmt_log);
            } else {
                $message .= "<p class='error'>Error preparing log query: " . mysqli_error($link) . "</p>";
                $success = false;
            }
        }
        
        // Commit or rollback transaction
        if ($success) {
            mysqli_commit($link);
            header("Location: index.php?page=inventory&status=stock_updated");
            exit;
        } else {
            mysqli_rollback($link);
            header("Location: index.php?page=inventory&error=stock_failed");
            exit;
        }
        mysqli_autocommit($link, true); // End transaction

    } else {
        header("Location: index.php?page=inventory&error=stock_invalid_input");
        exit;
    }
}

// Fetch all categories for tabs and item form dropdown
$all_categories = [];
$sql_fetch_categories = "SELECT id, name, description, created_at FROM categories ORDER BY name ASC";
if ($result_categories = mysqli_query($link, $sql_fetch_categories)) {
    while ($row_cat = mysqli_fetch_assoc($result_categories)) {
        $all_categories[] = $row_cat;
    }
    mysqli_free_result($result_categories);
} else {
    $message .= "<p class='error'>Error fetching categories: " . mysqli_error($link) . "</p>";
}

// Fetch items for the dropdown in tracking section
$items_options = [];
$sql_items = "SELECT id, name, quantity, unit FROM items ORDER BY name ASC";
if ($result_items_opt = mysqli_query($link, $sql_items)) {
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

if ($result_logs = mysqli_query($link, $sql_fetch_logs)) {
    if (mysqli_num_rows($result_logs) > 0) {
        while ($row_log = mysqli_fetch_assoc($result_logs)) {
            $inventory_logs[] = $row_log;
        }
        mysqli_free_result($result_logs);
    }
} else {
    $message .= "<p class='error'>Error fetching activity logs: " . mysqli_error($link) . "</p>";
}

// Fetch all items to display
$all_items = [];
// Fetch `updated_at` for "Last Activity". Add `location` if it gets added to DB
$sql_fetch_items = "SELECT i.id, i.name, i.category_id, i.barcode, i.quantity, i.unit, i.low_stock_threshold, i.description, i.updated_at, c.name as category_name
                    FROM items i
                    JOIN categories c ON i.category_id = c.id 
                    ORDER BY i.name ASC";
if ($result_items = mysqli_query($link, $sql_fetch_items)) {
    while ($row_item = mysqli_fetch_assoc($result_items)) {
        $all_items[] = $row_item;
    }
    mysqli_free_result($result_items);
} else {
    $message .= "<p class='error'>Error fetching items: " . mysqli_error($link) . "</p>";
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

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo strpos($message, 'success') !== false ? 'alert--success' : 'alert--error'; ?> mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card__body">
                <div class="d-flex justify-between align-center mb-3">
                    <div class="tabs main-tabs">
                        <button class="tab-button active" data-tab-content="items-content">Inventory Items</button>
                        <button class="tab-button" data-tab-content="categories-content">Categories</button>
                        <button class="tab-button" data-tab-content="tracking-content">Tracking</button>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn--primary" id="addItemBtn">+ Add Item</button>
                        <button class="btn btn--primary" id="logStockBtn">+ Log Stock Movement</button>
                        <button class="btn btn--secondary" id="addCategoryBtn">+ Add Category</button>
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
                                        <tr data-category-id="<?php echo htmlspecialchars($item['category_id']); ?>" class="table__row <?php echo $rowClass; ?>">
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
                                            <?php foreach ($all_categories as $category): ?>
                                                <tr class="table__row">
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
                                    <table class="w-100">
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
                                                <tr class="table__row">
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

<!-- Add Item Modal -->
<div id="addItemModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Add New Item</h2>
            </div>
            <div class="card__body">
                <form method="POST" class="form">
                    <div class="form__group">
                        <label class="form__label">Item Name</label>
                        <input type="text" name="item_name" class="form__input" required>
                    </div>
                    <div class="form__group">
                        <label class="form__label">Category</label>
                        <select name="item_category_id" class="form__input" required>
                            <option value="">Select Category</option>
                            <?php foreach ($all_categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form__group">
                        <label class="form__label">Barcode</label>
                        <input type="text" name="item_barcode" class="form__input">
                    </div>
                    <div class="form__group">
                        <label class="form__label">Quantity</label>
                        <input type="number" name="item_quantity" class="form__input" value="0" min="0">
                    </div>
                    <div class="form__group">
                        <label class="form__label">Unit</label>
                        <input type="text" name="item_unit" class="form__input" value="pcs">
                    </div>
                    <div class="form__group">
                        <label class="form__label">Low Stock Threshold</label>
                        <input type="number" name="item_low_stock_threshold" class="form__input" value="0" min="0">
                    </div>
                    <div class="form__group">
                    <div class="form__group">
                        <label class="form__label">Description</label>
                        <textarea name="item_description" class="form__input"></textarea>
                    </div>
                    <div class="form__group">
                        <label class="form__label">Location</label>
                        <input type="text" name="item_location" class="form__input">
                    </div>
                    <div class="d-flex justify-between mt-4">
                        <button type="submit" name="add_item" class="btn btn--primary">Add Item</button>
                        <button type="button" class="btn btn--secondary cancel-modal-btn" data-modal-id="addItemModal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div id="addCategoryModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Add New Category</h2>
            </div>
            <div class="card__body">
                <form method="POST" class="form">
                    <div class="form__group">
                        <label class="form__label">Category Name</label>
                        <input type="text" name="category_name" class="form__input" required>
                    </div>
                    <div class="form__group">
                        <label class="form__label">Description</label>
                        <textarea name="category_description" class="form__input"></textarea>
                    </div>
                    <div class="d-flex justify-between mt-4">
                        <button type="submit" name="add_category" class="btn btn--primary">Add Category</button>
                        <button type="button" class="btn btn--secondary cancel-modal-btn" data-modal-id="addCategoryModal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Log Stock Modal -->
<div id="logStockModal" class="modal is-hidden">
    <div class="modal-content">
        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Log Stock Movement</h2>
            </div>
            <div class="card__body">
                <div class="form__group mb-4">
                    <label class="form__label">Scan Barcode</label>
                    <div class="d-flex gap-2">
                        <input type="text" id="barcode_scanner_input" class="form__input" placeholder="Click here and scan barcode...">
                        <span id="barcode_status" class="text-muted"></span>
                    </div>
                </div>

                <form method="POST" class="form">
                    <div class="form__group">
                        <label class="form__label">Select Item</label>
                        <select name="item_id" class="form__input" required>
                            <option value="">-- Select Item --</option>
                            <?php foreach ($items_options as $item_opt): ?>
                                <option value="<?php echo $item_opt['id']; ?>">
                                    <?php echo htmlspecialchars($item_opt['name']); ?> 
                                    (Current Stock: <?php echo htmlspecialchars($item_opt['quantity']); ?> <?php echo htmlspecialchars($item_opt['unit']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form__group">
                        <label class="form__label">Quantity Change</label>
                        <input type="number" name="quantity_change" class="form__input" min="1" required>
                    </div>

                    <div class="form__group">
                        <label class="form__label">Reason/Note</label>
                        <input type="text" name="reason" class="form__input" placeholder="e.g., New Shipment, Used for X, Spoilage" required>
                    </div>

                    <div class="d-flex justify-between mt-4">
                        <button type="submit" name="stock_in" class="btn btn--success">Stock In</button>
                        <button type="submit" name="stock_out" class="btn btn--danger">Stock Out</button>
                        <button type="button" class="btn btn--secondary cancel-modal-btn" data-modal-id="logStockModal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
// Function to toggle modal visibility using the 'is-hidden' class
function toggleModal(modalId, show) {
    const modal = document.getElementById(modalId);
    if (modal) {
        console.log(`Toggling modal: ${modalId}, show: ${show}`); // Debug log
        if (show) {
            modal.classList.remove('is-hidden');
            modal.style.display = 'block'; // Ensure display is block
        } else {
            modal.classList.add('is-hidden');
            modal.style.display = 'none'; // Ensure display is none
        }
    }
}

// Main tab switching logic
function showTab(tabId) {
    console.log(`Attempting to show tab: ${tabId}`); // Debug log

    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.style.display = 'none';
        console.log(`Hiding content: ${content.id}`); // Debug log
    });

    // Deactivate all tab buttons
    document.querySelectorAll('.main-tabs .tab-button').forEach(button => {
        button.classList.remove('active');
        console.log(`Deactivating button: ${button.dataset.tabContent}`); // Debug log
    });

    // Show the selected tab content
    const selectedTabContent = document.getElementById(tabId);
    if (selectedTabContent) {
        selectedTabContent.style.display = 'block';
        console.log(`Showing content: ${selectedTabContent.id}`); // Debug log
    } else {
        console.log(`Error: Tab content with ID ${tabId} not found.`); // Debug log
    }

    // Activate the clicked tab button
    const clickedTabButton = document.querySelector(`.main-tabs .tab-button[data-tab-content="${tabId}"]`);
    if (clickedTabButton) {
        clickedTabButton.classList.add('active');
        console.log(`Activating button: ${clickedTabButton.dataset.tabContent}`); // Debug log
    } else {
        console.log(`Error: Tab button with data-tab-content ${tabId} not found.`); // Debug log
    }

    // If switching to items tab, ensure "All Items" sub-tab is active and filter is applied
    if (tabId === 'items-content') {
        const categoryTabs = document.querySelectorAll('.category-tabs .tab-button');
        if (categoryTabs.length > 0) {
            categoryTabs.forEach(btn => btn.classList.remove('active'));
            const allItemsTab = document.querySelector(`.category-tabs .tab-button[data-tab="all"]`);
            if (allItemsTab) {
                allItemsTab.classList.add('active');
            }
        }
        filterItems('all'); // Re-apply filter to show all items
    }
}

// Tab filtering for items (within the items-content tab)
function filterItems(categoryId) {
    var table = document.getElementById("inventoryItemsTable");
    if (!table) return; // Ensure table exists before trying to access it

    var tr = table.getElementsByTagName("tr");
    
    for (var i = 1; i < tr.length; i++) { // Start from 1 to skip header row
        var tdCategory = tr[i].getAttribute('data-category-id');
        if (categoryId === 'all' || tdCategory === categoryId) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}

// Event listener for category filter select
const categoryFilterSelect = document.getElementById('categoryFilterSelect');
if (categoryFilterSelect) {
    categoryFilterSelect.addEventListener('change', function() {
        filterItems(this.value);
        // Also re-apply search filter if there's a search term
        const searchItemsInput = document.getElementById('searchItemsInput');
        if (searchItemsInput && searchItemsInput.value !== '') {
            searchItemsInput.dispatchEvent(new Event('keyup'));
        }
    });
}

// Search items (simple client-side search by name and category for now)
const searchItemsInput = document.getElementById('searchItemsInput');
if (searchItemsInput) {
    searchItemsInput.addEventListener('keyup', function() {
        var searchTerm = this.value.toLowerCase();
        var table = document.getElementById("inventoryItemsTable");
        if (!table) return; // Ensure table exists

        var tr = table.getElementsByTagName("tr");
        // Get the currently selected category from the dropdown
        const currentCategoryId = categoryFilterSelect ? categoryFilterSelect.value : 'all';

        for (var i = 1; i < tr.length; i++) { // Start from 1 to skip header row
            var row = tr[i];
            var nameCell = row.cells[0] ? row.cells[0].textContent.toLowerCase() : '';
            var categoryCell = row.cells[1] ? row.cells[1].textContent.toLowerCase() : '';
            var itemCategoryId = row.getAttribute('data-category-id');

            var matchesSearch = nameCell.includes(searchTerm) || categoryCell.includes(searchTerm);
            var matchesCategory = (currentCategoryId === 'all' || itemCategoryId === currentCategoryId);

            if (matchesSearch && matchesCategory) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        }
    });
}

// Placeholder for Export button functionality
const exportItemsBtn = document.getElementById('exportItemsBtn');
if (exportItemsBtn) {
    exportItemsBtn.addEventListener('click', function() {
        alert('Export functionality to be implemented. This would typically export the currently visible items to CSV.');
    });
}

// Initial setup on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded fired in inventory.php'); // Debug log

    // Set the default active tab to "Inventory Items"
    showTab('items-content');

    // Attach event listeners for main tab buttons
    document.querySelectorAll('.main-tabs .tab-button').forEach(button => {
        console.log(`Attaching click listener to tab button: ${button.dataset.tabContent}`); // Debug log
        button.addEventListener('click', function() {
            showTab(this.dataset.tabContent);
        });
    });

    // Attach event listeners for modal toggle buttons
    const addItemBtn = document.getElementById('addItemBtn');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', function() {
            toggleModal('addItemModal', true);
        });
    } else {
        console.log('Error: addItemBtn not found.');
    }

    const logStockBtn = document.getElementById('logStockBtn');
    if (logStockBtn) {
        logStockBtn.addEventListener('click', function() {
            toggleModal('logStockModal', true);
        });
    } else {
        console.log('Error: logStockBtn not found.');
    }

    const addCategoryBtn = document.getElementById('addCategoryBtn');
    if (addCategoryBtn) {
        addCategoryBtn.addEventListener('click', function() {
            toggleModal('addCategoryModal', true);
        });
    } else {
        console.log('Error: addCategoryBtn not found.');
    }

    // Attach event listeners for modal cancel buttons
    document.querySelectorAll('.cancel-modal-btn').forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.dataset.modalId;
            toggleModal(modalId, false);
        });
    });

    // When the user clicks anywhere outside of the modal, close it
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.add('is-hidden');
        }
    });

    // Barcode scanner logic
    const barcodeInput = document.getElementById('barcode_scanner_input');
    const itemSelect = document.querySelector('select[name="item_id"]'); // Select by name as ID might conflict if multiple forms
    const quantityInput = document.querySelector('input[name="quantity_change"]');
    const barcodeStatus = document.getElementById('barcode_status');

    if (barcodeInput) {
        barcodeInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter' || event.keyCode === 13) {
                event.preventDefault(); // Prevent form submission if it's part of one
                const barcode = barcodeInput.value.trim();
                if (barcode === '') {
                    barcodeStatus.textContent = 'Please enter a barcode.';
                    barcodeStatus.style.color = 'red';
                    return;
                }
                barcodeStatus.textContent = 'Searching...';
                barcodeStatus.style.color = 'orange';

                fetch('get_item_by_barcode.php?barcode=' + encodeURIComponent(barcode))
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.item) {
                            const item = data.item;
                            let itemFoundInSelect = false;
                            for (let i = 0; i < itemSelect.options.length; i++) {
                                if (itemSelect.options[i].value == item.id) {
                                    itemSelect.value = item.id;
                                    itemFoundInSelect = true;
                                    break;
                                }
                            }
                            if (itemFoundInSelect) {
                                barcodeStatus.textContent = 'Item found: ' + item.name;
                                barcodeStatus.style.color = 'green';
                                quantityInput.focus(); // Focus on quantity field
                                barcodeInput.value = ''; // Clear barcode input
                            } else {
                                barcodeStatus.textContent = 'Item found but not in dropdown (refresh page?)';
                                barcodeStatus.style.color = 'red';
                            }
                        } else {
                            barcodeStatus.textContent = data.message || 'Item not found or error.';
                            barcodeStatus.style.color = 'red';
                            itemSelect.value = ''; // Clear selection
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        barcodeStatus.textContent = 'Error fetching item. See console.';
                        barcodeStatus.style.color = 'red';
                    });
            }
        });
    }
});
</script>
