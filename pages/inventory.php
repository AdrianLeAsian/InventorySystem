<?php
// Ensure $link (mysqli connection) is available, typically from a config file included in index.php
// Example: require_once '../config/db.php'; // Adjust path as needed

$message = ''; // Combined message variable

// --- Status/Error Message Handling (from GET parameters) ---
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'cat_added') $message .= "<p class='success'>Category added successfully!</p>";
    if ($_GET['status'] == 'cat_updated') $message .= "<p class='success'>Category updated successfully!</p>";
    if ($_GET['status'] == 'cat_deleted') $message .= "<p class='success'>Category deleted successfully!</p>";
    if ($_GET['status'] == 'item_added') $message .= "<p class='success'>Item added successfully!</p>";
    if ($_GET['status'] == 'item_updated') $message .= "<p class='success'>Item updated successfully!</p>";
    if ($_GET['status'] == 'item_deleted') $message .= "<p class='success'>Item deleted successfully!</p>";
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
}

// --- CATEGORY LOGIC (Add Category Modal Submission) ---
$category_name_form = ''; // Use distinct variable names for form values
$category_description_form = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    $category_name_form = trim($_POST['category_name']);
    $category_description_form = trim($_POST['category_description']);

    if (empty($category_name_form)) {
        $message .= "<p class='error'>Category name cannot be empty.</p>";
        goto end_category_logic;
    }

    $sql_cat_insert = "INSERT INTO categories (name, description) VALUES (?, ?)";
    if ($stmt_cat = mysqli_prepare($link, $sql_cat_insert)) {
        mysqli_stmt_bind_param($stmt_cat, "ss", $category_name_form, $category_description_form);
        if (mysqli_stmt_execute($stmt_cat)) {
            // Log the activity - Copied from categories.php add logic
            $last_id = mysqli_insert_id($link);
            $log_sql = "INSERT INTO activity_log (activity_type, entity_type, entity_id, entity_name, reason) VALUES (?, ?, ?, ?, ?)";
            if ($log_stmt = mysqli_prepare($link, $log_sql)) {
                $activity_type = 'category_added';
                $entity_type = 'category';
                $reason = 'New category added via Inventory page';
                mysqli_stmt_bind_param($log_stmt, "ssiss", $activity_type, $entity_type, $last_id, $category_name_form, $reason);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);
            }
            // End Log

            header("Location: index.php?page=inventory&status=cat_added");
            exit;
        } else {
            $message .= "<p class='error'>Error adding category: " . mysqli_error($link) . "</p>";
        }
        mysqli_stmt_close($stmt_cat);
    } else {
        $message .= "<p class='error'>Error preparing category query: " . mysqli_error($link) . "</p>";
    }
    end_category_logic:;
}

// --- ITEM LOGIC (Add Item Modal Submission) ---
$item_name_form = '';
$item_category_id_form = '';
$item_barcode_form = '';
$item_quantity_form = 0;
$item_unit_form = 'pcs';
$item_low_stock_threshold_form = 0;
$item_description_form = '';
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

    // Basic validation
    if (empty($item_name_form)) {
        $message .= "<p class='error'>Item Name is required.</p>";
        goto end_item_logic;
    }
    if ($item_category_id_form <= 0) {
        $message .= "<p class='error'>Please select a valid Category.</p>";
        goto end_item_logic;
    }
    if (!is_numeric($item_quantity_form) || $item_quantity_form < 0) {
        $message .= "<p class='error'>Quantity must be a non-negative number.</p>";
        goto end_item_logic;
    }
    if (!is_numeric($item_low_stock_threshold_form) || $item_low_stock_threshold_form < 0) {
        $message .= "<p class='error'>Low Stock Threshold must be a non-negative number.</p>";
        goto end_item_logic;
    }

    // Check for barcode uniqueness if barcode is provided
    if (!empty($item_barcode_form)) {
        $sql_check_barcode = "SELECT id FROM items WHERE barcode = ?";
        if ($stmt_check_barcode = mysqli_prepare($link, $sql_check_barcode)) {
            mysqli_stmt_bind_param($stmt_check_barcode, "s", $item_barcode_form);
            mysqli_stmt_execute($stmt_check_barcode);
            mysqli_stmt_store_result($stmt_check_barcode);
            if (mysqli_stmt_num_rows($stmt_check_barcode) > 0) {
                $message .= "<p class='error'>Error: Barcode '{$item_barcode_form}' already exists. Please use a unique barcode.</p>";
                mysqli_stmt_close($stmt_check_barcode);
                // Do not proceed with item insertion
                goto end_item_logic;
            }
            mysqli_stmt_close($stmt_check_barcode);
        } else {
            $message .= "<p class='error'>Error preparing barcode check query: " . mysqli_error($link) . "</p>";
            goto end_item_logic;
        }
    }

    // SQL query has 9 placeholders: name, category_id, barcode, quantity, unit, low_stock_threshold, description, location
    $sql_item_insert = "INSERT INTO items (name, category_id, barcode, quantity, unit, low_stock_threshold, description, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    if ($stmt_item = mysqli_prepare($link, $sql_item_insert)) {
        // Corrected bind_param type string ("sisisidss") and variable list to match 9 placeholders/variables.
        // Types: s (name), i (category_id), s (barcode), i (quantity), s (unit), i (low_stock_threshold), s (description), s (location)
        mysqli_stmt_bind_param($stmt_item, "sisisiss",
            $item_name_form,          // s
            $item_category_id_form,   // i
            $item_barcode_form,       // s
            $item_quantity_form,      // i
            $item_unit_form,          // s
            $item_low_stock_threshold_form, // i
            $item_description_form,   // s
            $item_location_form       // s
        );

        if (mysqli_stmt_execute($stmt_item)) {
             // Log the activity - Copied from items.php add logic
            $last_id = mysqli_insert_id($link);
            $log_sql = "INSERT INTO activity_log (activity_type, entity_type, entity_id, entity_name, reason) VALUES (?, ?, ?, ?, ?)";
            if ($log_stmt = mysqli_prepare($link, $log_sql)) {
                $activity_type = 'item_added';
                $entity_type = 'item';
                $reason = 'New item added via Inventory page';
                mysqli_stmt_bind_param($log_stmt, "ssiss", $activity_type, $entity_type, $last_id, $item_name_form, $reason);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);
            }
            // End Log

            header("Location: index.php?page=inventory&status=item_added");
            exit;
        } else {
            $message .= "<p class='error'>Error adding item: " . mysqli_error($link) . "</p>";
        }
        mysqli_stmt_close($stmt_item);
    } else {
        $message .= "<p class='error'>Error preparing item query: " . mysqli_error($link) . "</p>";
    }
    end_item_logic:; // Label for goto statement
}

// Fetch all categories for tabs, item form dropdown, and category table
$all_categories = [];
$sql_fetch_categories = "SELECT id, name FROM categories ORDER BY name ASC";
if ($result_categories = mysqli_query($link, $sql_fetch_categories)) {
    while ($row_cat = mysqli_fetch_assoc($result_categories)) {
        $all_categories[] = $row_cat;
    }
    mysqli_free_result($result_categories);
} else {
    $message .= "<p class='error'>Error fetching categories: " . mysqli_error($link) . "</p>";
}

// Fetch all items to display
$all_items = [];
// Fetch `updated_at` for "Last Activity". Add `location` if it gets added to DB
$sql_fetch_items = "SELECT i.id, i.name, i.category_id, i.barcode, i.quantity, i.unit, i.low_stock_threshold, i.description, i.updated_at, i.location, c.name as category_name 
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

// Fetch categories for the category list table display - Copied from categories.php fetch logic
$categories_list = [];
$sql_fetch_categories_list = "SELECT id, name, description, created_at FROM categories ORDER BY name ASC";
if ($result_categories_list = mysqli_query($link, $sql_fetch_categories_list)) {
    while ($row_cat_list = mysqli_fetch_assoc($result_categories_list)) {
        $categories_list[] = $row_cat_list;
    }
    mysqli_free_result($result_categories_list);
} else {
    $message .= "<p class='error'>Error fetching categories for list: " . mysqli_error($link) . "</p>";
}
// End Fetch categories for list

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
<!-- Add Font Awesome for icons - Copied from items.php/categories.php -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="container">
    <div class="page">
        <header class="d-flex justify-between align-center mb-4">
            <div>
                <h2 class="card__title">Inventory Overview</h2>
                <p class="text-muted">Manage your inventory items and categories.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn--primary" onclick="document.getElementById('addItemModal').style.display='block'">+ Add Item</button>
                <button class="btn btn--secondary" onclick="document.getElementById('addCategoryModal').style.display='block'">+ Add Category</button>
                <button class="btn btn--secondary" id="exportItemsBtn">Export</button>
            </div>
        </header>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo strpos($message, 'success') !== false ? 'alert--success' : 'alert--error'; ?> mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Items Section -->
        <div class="card mb-4">
            <div class="card__body">
                 <h3 class="card__title mb-3">Inventory Items</h3>
                <div class="d-flex justify-between align-center mb-3">
                    <div class="category-tabs">
                        <button class="btn btn--primary tab-link active" onclick="filterItems('all')">All Items</button>
                        <?php foreach ($all_categories as $category): ?>
                            <button class="btn btn--secondary tab-link" onclick="filterItems('<?php echo htmlspecialchars($category['id']); ?>')">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <input type="text" id="searchItemsInput" class="form__input" placeholder="Search Items...">
                    </div>
                </div>

                <div class="table">
                    <table id="inventoryItemsTable" class="w-100">
                        <thead>
                            <tr class="table__header">
                                 <th class="table__cell">Barcode</th>
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
                                        $rowClass = '';
                                        if ($isOutStock) {
                                            $rowClass = 'alert alert--danger';
                                        } elseif ($isLowStock) {
                                            $rowClass = 'alert alert--warning';
                                        }
                                    ?>
                                    <tr data-category-id="<?php echo htmlspecialchars($item['category_id']); ?>" class="table__row <?php echo $rowClass; ?>">
                                         <td class="table__cell"><?php echo htmlspecialchars($item['barcode'] ?? 'N/A'); ?></td>
                                        <td class="table__cell" title="<?php echo htmlspecialchars($item['name']); ?>">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </td>
                                        <td class="table__cell"><?php echo htmlspecialchars($item['category_name']); ?></td>
                                        <td class="table__cell"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                        <td class="table__cell"><?php echo htmlspecialchars($item['low_stock_threshold']); ?></td>
                                        <td class="table__cell"><?php echo htmlspecialchars($item['unit']); ?></td>
                                        <!-- Display Location -->
                                        <td class="table__cell"><?php echo htmlspecialchars($item['location'] ?? 'N/A'); ?></td>
                                        <td class="table__cell"><?php echo format_last_activity($item['updated_at']); ?></td>
                                        <td class="table__cell">
                                            <?php
                                            $status = 'OK';
                                            $status_class = 'btn btn--success btn--sm'; // Added btn--sm for smaller status buttons
                                            if ($isOutStock) {
                                                $status = 'Out of Stock';
                                                $status_class = 'btn btn--danger btn--sm';
                                            } elseif ($isLowStock) {
                                                $status = 'Low Stock';
                                                $status_class = 'btn btn--warning btn--sm';
                                            }
                                            ?>
                                            <span class="<?php echo $status_class; ?>"><?php echo $status; ?></span>
                                        </td>
                                        <td class="table__cell">
                                            <div class="d-flex gap-2">
                                                <a href="index.php?page=edit_item&id=<?php echo $item['id']; ?>" class="btn btn--secondary btn--sm" title="Edit Item">
                                                     <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="index.php?page=delete_item&id=<?php echo $item['id']; ?>" class="btn btn--danger btn--sm" title="Delete Item" onclick="return confirm('Are you sure you want to delete this item? This action cannot be undone.');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="table__row">
                                    <td colspan="10" class="table__cell text-center">No items found.</td> <!-- Adjusted colspan -->
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Categories Section - Copied from categories.php -->
        <div class="card">
             <div class="card__body">
                <h3 class="card__title mb-3">Inventory Categories</h3>
                <?php if (!empty($categories_list)): ?>
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
                                <?php foreach ($categories_list as $category): ?>
                                    <tr class="table__row">
                                        <td class="table__cell"><?php echo htmlspecialchars($category['id']); ?></td>
                                        <td class="table__cell"><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td class="table__cell"><?php echo nl2br(htmlspecialchars($category['description'])); ?></td>
                                        <td class="table__cell"><?php echo htmlspecialchars($category['created_at']); ?></td>
                                        <td class="table__cell">
                                            <div class="d-flex gap-2">
                                                <a href="index.php?page=edit_category&id=<?php echo $category['id']; ?>" class="btn btn--secondary btn--sm" title="Edit Category">
                                                     <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="index.php?page=delete_category&id=<?php echo $category['id']; ?>"
                                                   class="btn btn--danger btn--sm"
                                                   title="Delete Category"
                                                   onclick="return confirm('Are you sure you want to delete this category? Make sure no items are assigned to it first.');">
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
                    <p class="text-center text-muted">No categories found. Please add some.</p>
                <?php endif; ?>
            </div>
        </div>
        <!-- End Categories Section -->
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
                        <label class="form__label">Description</label>
                        <textarea name="item_description" class="form__input"></textarea>
                    </div>
                    <div class="form__group">
                        <label class="form__label">Location</label>
                        <input type="text" name="item_location" class="form__input">
                    </div>
                    <div class="d-flex justify-between mt-4">
                        <button type="submit" name="add_item" class="btn btn--primary">Add Item</button>
                        <button type="button" class="btn btn--secondary" onclick="document.getElementById('addItemModal').style.display='none'">Cancel</button>
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
                        <button type="button" class="btn btn--secondary" onclick="document.getElementById('addCategoryModal').style.display='none'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto; /* Added scroll if content overflows */
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px;
    border-radius: 8px;
}


/* Additional Utility Classes */
.gap-2 {
    gap: 0.5rem;
}

.text-muted {
    color: #6c757d;
}

/* Category Tabs */
.category-tabs {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

/* Added style for smaller buttons in tables */
.btn--sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Added grid styles for layout if needed, copied from items.php/categories.php */
.grid {
    display: grid;
    grid-template-columns: 1fr;
}
.grid--2-cols {
    grid-template-columns: repeat(2, 1fr);
}
.grid--span-2 {
    grid-column: span 2;
}

</style>

<script>
// Get the modals
var addCategoryModal = document.getElementById('addCategoryModal');
var addItemModal = document.getElementById('addItemModal');

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target.classList.contains('modal')) {
    event.target.style.display = "none";
  }
}

// Tab filtering for items
function filterItems(categoryId) {
    // Get the item table explicitly by its ID
    var table = document.getElementById("inventoryItemsTable");
    if (!table) return; // Added a check in case the table isn't found
    var tr = table.getElementsByTagName("tr");

    // Update active tab class and button styles
    var tabs = document.querySelectorAll('.category-tabs .tab-link');
    tabs.forEach(tab => {
        tab.classList.remove('active', 'btn--primary');
        tab.classList.add('btn--secondary');
    });

    var activeTab = document.querySelector(".category-tabs .tab-link[onclick*=\"filterItems('" + categoryId + "')\"]");
    if(activeTab) {
        activeTab.classList.add('active', 'btn--primary');
        activeTab.classList.remove('btn--secondary');
    }



    for (var i = 1; i < tr.length; i++) { // Start from 1 to skip header row
        var tdCategory = tr[i].getAttribute('data-category-id');
        var matchesCategory = (categoryId === 'all' || tdCategory === categoryId);

        // Also consider the current search filter when applying category filter
        var searchInput = document.getElementById('searchItemsInput');
        var searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        var row = tr[i];
        var nameCell = row.cells[1] ? row.cells[1].textContent.toLowerCase() : '';
        var categoryCell = row.cells[2] ? row.cells[2].textContent.toLowerCase() : '';
        var matchesSearch = nameCell.includes(searchTerm) || categoryCell.includes(searchTerm);

        if (matchesSearch && matchesCategory) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}



// Search items (simple client-side search by name and category for now)
document.getElementById('searchItemsInput').addEventListener('keyup', function() {
    var searchTerm = this.value.toLowerCase();
    var table = document.getElementById("inventoryItemsTable");
     if (!table) return; // Added a check
    var tr = table.getElementsByTagName("tr");

    // The category filter is now solely managed by the tabs
    var activeCategoryId = document.querySelector('.category-tabs .tab-link.active').getAttribute('onclick').match(/filterItems\('([^']+)'\)/)[1];

    for (var i = 1; i < tr.length; i++) { // Start from 1 to skip header row
        var row = tr[i];
        var nameCell = row.cells[1] ? row.cells[1].textContent.toLowerCase() : '';
        var categoryCell = row.cells[2] ? row.cells[2].textContent.toLowerCase() : '';
        var itemCategoryId = row.getAttribute('data-category-id');

        var matchesSearch = nameCell.includes(searchTerm) || categoryCell.includes(searchTerm);
        var matchesCategory = (activeCategoryId === 'all' || itemCategoryId === activeCategoryId);

        if (matchesSearch && matchesCategory) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    }
});

// Placeholder for Export button functionality
document.getElementById('exportItemsBtn').addEventListener('click', function() {
    // Redirect to export.php to download the CSV
    window.location.href = 'export.php?type=items_csv';
});

// Ensure "All Items" tab/select is active and filter is applied on load
window.addEventListener('DOMContentLoaded', (event) => {
    // Find and activate the "All Items" tab
    const allItemsTab = document.querySelector(".category-tabs .tab-link[onclick*=\"filterItems('all')\"]");
    if(allItemsTab) {
        allItemsTab.classList.add('active');
    }
    // Explicitly call filterItems('all') to ensure initial display consistency
    filterItems('all');
});

</script>
