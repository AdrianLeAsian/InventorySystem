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

<div class="inventory-container">
    <header class="inventory-header">
        <div>
            <h2>Inventory Items</h2>
            <p>Manage and track all your inventory items.</p>
        </div>
        <div class="header-actions">
            <!-- Add New Category button is now with Add Item button -->
            <button class="btn btn-primary" onclick="document.getElementById('addItemModal').style.display='block'">+ Add Item</button>
            <button class="btn btn-secondary" onclick="document.getElementById('addCategoryModal').style.display='block'">+ Add Category</button>
            <button class="btn btn-secondary" id="exportItemsBtn">Export</button> <!-- Placeholder for export -->
        </div>
    </header>

    <?php if (!empty($message)) echo "<div class='message-area'>" . $message . "</div>"; ?>

    <div class="filter-controls">
        <div class="category-tabs">
            <button class="tab-link active" onclick="filterItems('all')">All Items</button>
            <?php foreach ($all_categories as $category): ?>
                <button class="tab-link" onclick="filterItems('<?php echo htmlspecialchars($category['id']); ?>')">
                    <?php echo htmlspecialchars($category['name']); ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="search-bar">
            <select id="categoryFilterSelect" style="display: none;"> <!-- Could be used as an alternative filter -->
                 <option value="all">All Categories</option>
                 <?php foreach ($all_categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="searchItemsInput" placeholder="Search Items...">
        </div>
    </div>

    <div class="items-table-container">
        <table id="inventoryItemsTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Current Stock</th>
                    <th>Min Stock</th>
                    <th>Unit</th>
                    <th>Location</th>
                    <th>Last Activity</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($all_items)): ?>
                    <?php foreach ($all_items as $item): ?>
                        <tr data-category-id="<?php echo htmlspecialchars($item['category_id']); ?>">
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($item['low_stock_threshold']); ?></td>
                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                            <td><?php echo 'N/A'; // Placeholder for Location ?></td>
                            <td><?php echo format_last_activity($item['updated_at']); ?></td>
                            <td>
                                <?php
                                $status = 'OK';
                                $status_class = 'status-ok';
                                if ($item['low_stock_threshold'] > 0 && $item['quantity'] <= $item['low_stock_threshold']) {
                                    $status = 'Low Stock';
                                    $status_class = 'status-low';
                                } elseif ($item['quantity'] == 0) {
                                    // $status = 'Out of Stock'; // Alternative
                                    // $status_class = 'status-empty';
                                }
                                // Add 'Warning' logic if defined based on image
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status; ?></span>
                            </td>
                            <td class="actions-cell">
                                <a href="index.php?page=edit_item&id=<?php echo $item['id']; ?>" class="action-icon edit-icon" title="Edit">&#9998;</a> <!-- Edit icon -->
                                <a href="index.php?page=delete_item&id=<?php echo $item['id']; ?>" class="action-icon delete-icon" title="Delete" onclick="return confirm('Are you sure you want to delete this item? This action cannot be undone.');">&#128465;</a> <!-- Trash icon -->
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align:center;">No items found. Click "+ Add Item" to add one.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <footer class="inventory-summary-footer">
        <div class="summary-card">
            <h4>Total Items</h4>
            <p class="summary-value"><?php echo $total_items_count; ?></p>
            <p class="summary-desc">Across all categories</p>
        </div>
        <div class="summary-card">
            <h4>Low Stock Items</h4>
            <p class="summary-value low-stock-value"><?php echo $low_stock_items_count; ?></p>
            <p class="summary-desc">Needs immediate attention</p>
        </div>
    </footer>

</div> <!-- .inventory-container -->


<!-- Add Category Modal -->
<div id="addCategoryModal" class="modal">
  <div class="modal-content">
    <span class="close-button" onclick="document.getElementById('addCategoryModal').style.display='none'">&times;</span>
    <div class="form-container">
        <h3>Add New Category</h3>
        <form action="index.php?page=inventory" method="post">
            <div>
                <label for="category_name_modal">Category Name:</label>
                <input type="text" id="category_name_modal" name="category_name" value="<?php echo htmlspecialchars($category_name_form); ?>" required>
            </div>
            <div>
                <label for="category_description_modal">Description (Optional):</label>
                <textarea id="category_description_modal" name="category_description" rows="3"><?php echo htmlspecialchars($category_description_form); ?></textarea>
            </div>
            <div>
                <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
            </div>
        </form>
    </div>
  </div>
</div>


<!-- Add Item Modal -->
<div id="addItemModal" class="modal">
  <div class="modal-content">
    <span class="close-button" onclick="document.getElementById('addItemModal').style.display='none'">&times;</span>
    <div class="form-container item-form">
        <h3>Add New Item</h3>
        <form action="index.php?page=inventory" method="post">
            <div>
                <label for="item_name_modal">Item Name:</label>
                <input type="text" id="item_name_modal" name="item_name" value="<?php echo htmlspecialchars($item_name_form); ?>" required>
            </div>
            <div>
                <label for="item_category_id_modal">Category:</label>
                <select id="item_category_id_modal" name="item_category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($all_categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo ($item_category_id_form == $category['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="item_barcode_modal">Barcode (Optional):</label>
                <input type="text" id="item_barcode_modal" name="item_barcode" value="<?php echo htmlspecialchars($item_barcode_form); ?>">
            </div>
            <div>
                <label for="item_quantity_modal">Initial Quantity:</label>
                <input type="number" id="item_quantity_modal" name="item_quantity" value="<?php echo (int)$item_quantity_form; ?>" min="0" required>
            </div>
            <div>
                <label for="item_unit_modal">Unit (e.g., pcs, kg, L):</label>
                <input type="text" id="item_unit_modal" name="item_unit" value="<?php echo htmlspecialchars($item_unit_form); ?>" placeholder="pcs" required>
            </div>
            <div>
                <label for="item_low_stock_threshold_modal">Low Stock Threshold:</label>
                <input type="number" id="item_low_stock_threshold_modal" name="item_low_stock_threshold" value="<?php echo (int)$item_low_stock_threshold_form; ?>" min="0">
            </div>
            <div>
                <label for="item_purchase_price_modal">Purchase Price (Optional):</label>
                <input type="number" step="0.01" id="item_purchase_price_modal" name="item_purchase_price" value="<?php echo htmlspecialchars($item_purchase_price_form); ?>" min="0">
            </div>
            <!-- Selling price field removed as per request -->
            <!-- <div>
                <label for="item_selling_price_modal">Selling Price (Optional):</label>
                <input type="number" step="0.01" id="item_selling_price_modal" name="item_selling_price" value="<?php echo htmlspecialchars($item_selling_price_form); ?>" min="0">
            </div> -->
            <div>
                <label for="item_description_modal">Description (Optional):</label>
                <textarea id="item_description_modal" name="item_description" rows="3"><?php echo htmlspecialchars($item_description_form); ?></textarea>
            </div>
            <div>
                <label for="item_location_modal">Location (Optional):</label>
                <input type="text" id="item_location_modal" name="item_location" value="<?php echo htmlspecialchars($item_location_form); ?>">
            </div>
            <div>
                <button type="submit" name="add_item" class="btn btn-primary">Add Item</button>
            </div>
        </form>
    </div>
  </div>
</div>

<style>
/* General Page & Theming */
body { /* Assuming styles might be global, or scope to .inventory-container if needed */
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    background-color: #f4f7fc; /* Light blue-gray background */
    color: #333;
    margin: 0;
    padding: 20px; /* Add some padding around the whole page */
}

.inventory-container {
    background-color: #fff; /* White background for the main content block */
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.inventory-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}
.inventory-header h2 {
    margin: 0 0 5px 0;
    font-size: 24px;
    font-weight: 600;
    color: #1a202c; /* Darker text for heading */
}
.inventory-header p {
    margin: 0;
    font-size: 14px;
    color: #718096; /* Gray text for subheading */
}
.header-actions .btn {
    margin-left: 10px;
}

/* Buttons */
.btn {
    padding: 10px 18px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: background-color 0.2s ease-in-out, box-shadow 0.2s ease;
}
.btn-primary {
    background-color: #4a90e2; /* Primary blue */
    color: white;
}
.btn-primary:hover {
    background-color: #357abd;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.btn-secondary {
    background-color: #e2e8f0; /* Light gray for secondary actions */
    color: #2d3748; /* Darker gray text */
    border: 1px solid #cbd5e0;
}
.btn-secondary:hover {
    background-color: #cbd5e0;
    border-color: #a0aec0;
}


/* Filter Controls */
.filter-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 10px;
    background-color: #f8f9fa; /* Slight off-white for filter bar */
    border-radius: 6px;
}
.category-tabs .tab-link {
    padding: 8px 15px;
    margin-right: 8px;
    border: 1px solid transparent;
    border-bottom: 2px solid transparent; /* For active state underline */
    border-radius: 4px 4px 0 0; /* Slight rounding for tabs */
    cursor: pointer;
    background-color: transparent;
    color: #4a5568; /* Grayish text for tabs */
    font-weight: 500;
    transition: color 0.2s ease, border-color 0.2s ease;
}
.category-tabs .tab-link:hover {
    color: #2d3748; /* Darker on hover */
}
.category-tabs .tab-link.active {
    color: #4a90e2; /* Blue for active tab */
    border-bottom-color: #4a90e2; /* Blue underline */
    font-weight: 600;
}
.search-bar input[type="text"] {
    padding: 8px 12px;
    border: 1px solid #cbd5e0;
    border-radius: 4px;
    min-width: 250px; /* Decent width for search */
    font-size: 14px;
}

/* Table Styles */
.items-table-container {
    overflow-x: auto; /* For responsive tables */
}
#inventoryItemsTable {
    width: 100%;
    border-collapse: collapse;
    margin-top: 0;
}
#inventoryItemsTable th, #inventoryItemsTable td {
    border: 1px solid #e2e8f0; /* Lighter borders */
    padding: 12px 15px; /* More padding */
    text-align: left;
    font-size: 14px;
}
#inventoryItemsTable th {
    background-color: #f8f9fa; /* Very light gray for headers */
    font-weight: 600;
    color: #4a5568; /* Header text color */
}
#inventoryItemsTable tr:nth-child(even) {
    background-color: #fdfdfe; /* Very subtle striping */
}
#inventoryItemsTable tr:hover {
    background-color: #f1f5f9; /* Light hover effect */
}

/* Status Badges */
.status-badge {
    padding: 4px 10px;
    border-radius: 12px; /* Pill shape */
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.status-ok {
    background-color: #e6fffa; /* Light green */
    color: #2c7a7b; /* Darker green text */
}
.status-low {
    background-color: #fff5f5; /* Light red */
    color: #c53030; /* Darker red text */
}
.status-warning { /* Example if you add warning */
    background-color: #fffaf0; /* Light orange/yellow */
    color: #dd6b20; /* Darker orange text */
}

/* Action Icons */
.actions-cell a {
    margin: 0 5px;
    text-decoration: none;
    color: #718096; /* Gray for icons */
    font-size: 18px; /* Slightly larger icons */
}
.actions-cell a:hover {
    color: #4a90e2; /* Blue on hover */
}


/* Footer Summary */
.inventory-summary-footer {
    display: flex;
    justify-content: space-around;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
}
.summary-card {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 6px;
    text-align: center;
    flex-basis: 30%; /* Distribute space */
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.summary-card h4 {
    margin: 0 0 8px 0;
    font-size: 14px;
    color: #718096; /* Gray for title */
    font-weight: 500;
}
.summary-card .summary-value {
    margin: 0 0 5px 0;
    font-size: 28px;
    font-weight: 600;
    color: #2d3748; /* Dark text for value */
}
.summary-card .summary-value.low-stock-value {
    color: #c53030; /* Red for low stock count */
}
.summary-card .summary-desc {
    margin: 0;
    font-size: 12px;
    color: #a0aec0; /* Lighter gray for description */
}


/* Modal Styles (retained and slightly harmonized) */
.modal {
    display: none; 
    position: fixed; 
    z-index: 1000; 
    left: 0;
    top: 0;
    width: 100%; 
    height: 100%; 
    overflow: auto; 
    background-color: rgba(0,0,0,0.5); 
    padding-top: 50px;
}
.modal-content {
    background-color: #fff;
    margin: 5% auto; 
    padding: 25px 30px;
    border: none;
    width: 50%; 
    max-width: 600px; /* Max width for modals */
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    position: relative;
}
.close-button {
    color: #aaa;
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 28px;
    font-weight: bold;
    line-height: 1;
}
.close-button:hover,
.close-button:focus {
    color: #333;
    text-decoration: none;
    cursor: pointer;
}
.modal .form-container h3 { /* Modal title */
    font-size: 20px;
    font-weight: 600;
    margin-top: 0;
    margin-bottom: 25px;
    color: #1a202c;
    padding-bottom: 15px;
    border-bottom: 1px solid #e0e0e0;
}
.modal .form-container div {
    margin-bottom: 15px;
}
.modal .form-container label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    font-size: 14px;
    color: #4a5568;
}
.modal .form-container input[type="text"],
.modal .form-container input[type="number"],
.modal .form-container select,
.modal .form-container textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #cbd5e0;
    border-radius: 4px;
    box-sizing: border-box; 
    font-size: 14px;
}
.modal .form-container textarea {
    min-height: 80px;
}
.modal .form-container button { /* Submit button in modal */
    width: 100%; /* Full width button */
    padding: 12px;
    font-size: 15px;
}

/* Message Area for status/errors */
.message-area {
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 4px;
    font-size: 14px;
}
.message-area .success { /* These were classes on <p> tags, ensure they are styled */
    color: green;
    border: 1px solid green;
    padding: 10px;
    background-color: #e6ffe6;
}
.message-area .error {
    color: red;
    border: 1px solid red;
    padding: 10px;
    background-color: #ffe6e6;
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
