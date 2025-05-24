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

    if (!empty($category_name_form)) {
        $sql_cat_insert = "INSERT INTO categories (name, description) VALUES (?, ?)";
        if ($stmt_cat = mysqli_prepare($link, $sql_cat_insert)) {
            mysqli_stmt_bind_param($stmt_cat, "ss", $category_name_form, $category_description_form);
            if (mysqli_stmt_execute($stmt_cat)) {
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
$item_purchase_price_form = 0.00;
$item_selling_price_form = 0.00;
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
    $item_purchase_price_form = (float)$_POST['item_purchase_price'];
    // $item_selling_price_form = (float)$_POST['item_selling_price']; // Value field removed
    $item_description_form = trim($_POST['item_description']);
     $item_location_form = trim($_POST['item_location']);  

    if (!empty($item_name_form) && $item_category_id_form > 0) {
        // Modify SQL if new fields like 'location' are added to 'items' table
        $sql_item_insert = "INSERT INTO items (name, category_id, barcode, quantity, unit, low_stock_threshold, description, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt_item = mysqli_prepare($link, $sql_item_insert)) {
            // Adjust bind_param if new fields are added: e.g., "sisisiddsS" (add S for location)
            mysqli_stmt_bind_param($stmt_item, "sisisids", 
                $item_name_form, 
                $item_category_id_form, 
                $item_barcode_form, 
                $item_quantity_form, 
                $item_unit_form, 
                $item_low_stock_threshold_form, 
                $item_purchase_price_form,
                // $item_selling_price_form, // Value field removed
                $item_description_form,
                $item_location_form
            );

            if (mysqli_stmt_execute($stmt_item)) {
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
        $message .= "<p class='error'>Item Name and Category are required.</p>";
    }
}

// Fetch all categories for tabs and item form dropdown
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
$sql_fetch_items = "SELECT i.id, i.name, i.category_id, i.barcode, i.quantity, i.unit, i.low_stock_threshold, i.purchase_price, i.description, i.updated_at, c.name as category_name 
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

        <div class="card mb-4">
            <div class="card__body">
                <div class="d-flex justify-between align-center mb-3">
                    <div class="category-tabs">
                        <button class="btn btn--primary" onclick="filterItems('all')">All Items</button>
                        <?php foreach ($all_categories as $category): ?>
                            <button class="btn btn--secondary" onclick="filterItems('<?php echo htmlspecialchars($category['id']); ?>')">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <select id="categoryFilterSelect" class="form__input">
                            <option value="all">All Categories</option>
                            <?php foreach ($all_categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" id="searchItemsInput" class="form__input" placeholder="Search Items...">
                    </div>
                </div>

                <div class="table">
                    <table class="w-100">
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
                                        <td class="table__cell"><?php echo 'N/A'; ?></td>
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
                        <label class="form__label">Purchase Price</label>
                        <input type="number" name="item_purchase_price" class="form__input" value="0.00" step="0.01" min="0">
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
</style>

<script>
// Get the modals
var addCategoryModal = document.getElementById('addCategoryModal');
var addItemModal = document.getElementById('addItemModal');

// Get the <span> elements that close the modals
var closeButtons = document.getElementsByClassName("close-button");

// When the user clicks on <span> (x), close the modal
for (let i = 0; i < closeButtons.length; i++) {
  closeButtons[i].onclick = function() {
    const modalId = this.closest('.modal').id;
    document.getElementById(modalId).style.display = "none";
  }
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target.classList.contains('modal')) {
    event.target.style.display = "none";
  }
}

// Tab filtering for items
function filterItems(categoryId) {
    var table = document.getElementById("inventoryItemsTable");
    var tr = table.getElementsByTagName("tr");
    var activeTab = document.querySelector('.category-tabs .tab-link.active');
    if(activeTab) activeTab.classList.remove('active');
    
    var newActiveTab = document.querySelector(".category-tabs .tab-link[onclick*=\"filterItems('" + categoryId + "')\"]");
    if(newActiveTab) newActiveTab.classList.add('active');

    for (var i = 1; i < tr.length; i++) { // Start from 1 to skip header row
        var tdCategory = tr[i].getAttribute('data-category-id');
        if (categoryId === 'all' || tdCategory === categoryId) {
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
    var tr = table.getElementsByTagName("tr");
    var currentCategoryFilter = document.querySelector('.category-tabs .tab-link.active');
    var activeCategoryId = 'all';
    if (currentCategoryFilter) {
        // Extract categoryId from the onclick attribute (e.g., "filterItems('1')")
        var match = currentCategoryFilter.getAttribute('onclick').match(/filterItems\('([^']+)'\)/);
        if (match) activeCategoryId = match[1];
    }


    for (var i = 1; i < tr.length; i++) { // Start from 1 to skip header row
        var row = tr[i];
        var nameCell = row.cells[0] ? row.cells[0].textContent.toLowerCase() : '';
        var categoryCell = row.cells[1] ? row.cells[1].textContent.toLowerCase() : '';
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
    alert('Export functionality to be implemented. This would typically export the currently visible items to CSV.');
    // Basic CSV export example (can be expanded)
    // let csvContent = "data:text/csv;charset=utf-8,";
    // const rows = document.querySelectorAll("#inventoryItemsTable tr");
    // rows.forEach(function(row) {
    //     let rowData = [];
    //     row.querySelectorAll("th, td").forEach(function(cell, index) {
    //          // Only include visible columns, skip actions
    //         if (index < row.cells.length -1 && row.style.display !== 'none') {
    //             rowData.push(\`"\${cell.innerText.replace(/"/g, \'\'\'""\'\'\')}"\`);
    //         }
    //     });
    //     if (rowData.length > 0) csvContent += rowData.join(",") + "\\r\\n";
    // });
    // var encodedUri = encodeURI(csvContent);
    // var link = document.createElement("a");
    // link.setAttribute("href", encodedUri);
    // link.setAttribute("download", "inventory_export.csv");
    // document.body.appendChild(link); 
    // link.click();
    // document.body.removeChild(link);
});

// Ensure first tab is active on load if categories exist
window.addEventListener('DOMContentLoaded', (event) => {
    const firstTab = document.querySelector('.category-tabs .tab-link');
    if(firstTab){
        // No, this will be handled by the 'all' tab which is hardcoded and active by default.
        // Let's ensure the "All Items" tab is correctly marked active if it exists.
        const allItemsTab = document.querySelector(".category-tabs .tab-link[onclick*=\"filterItems('all')\"]");
        if(allItemsTab) { // Should always exist
             // It's already set as active in HTML, so this is just a check.
        }
    }
    // Initial filter display if needed, though default is all items visible.
});

</script>
