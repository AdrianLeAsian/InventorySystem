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

// Ensure $link (mysqli connection) is available
// Assuming this is included from index.php or another config file

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $item_id = (int)$_GET['id'];
} else {
    // Redirect to the main inventory page if ID is invalid
    header("Location: index.php?page=inventory&error=item_invalid_id");
    exit;
}

// Handle form submission for updating the item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_item'])) {
    // *** Get item_id from POST data submitted by the hidden field ***
    if (isset($_POST['item_id']) && is_numeric($_POST['item_id'])) {
        $item_id = (int)$_POST['item_id'];
    } else {
        // If item_id is not in POST or invalid, set an error message and stop processing
        $message = "<p class='error'>Error: Invalid item ID submitted with the form.</p>";
        // We can return or exit here as we cannot perform the update without a valid ID
         // Optional: re-fetch item details based on GET ID if needed to repopulate form after error
         // include 'path/to/fetch_item_details_logic.php'; // Or duplicate the fetch logic
         // For simplicity, we'll just show the error message.
         goto end_post_processing; // Jump to the end to display the form with the error message
    }
    // *** End get item_id from POST ***

    $item_name = trim($_POST['item_name']);
    $item_category_id = (int)$_POST['item_category_id'];
    $item_barcode = trim($_POST['item_barcode']);
    $item_unit = trim($_POST['item_unit']);
    $item_low_stock_threshold = (int)$_POST['item_low_stock_threshold'];
    $item_description = trim($_POST['item_description']);
    $item_location = trim($_POST['item_location']); // Fetch location from form

    // Ensure item_id is valid after form submission too
     if ($item_id <= 0) {
        $message = "<p class='error'>Error: Invalid item ID received for update.</p>";
    } elseif (!empty($item_name) && $item_category_id > 0) {
        // Updated SQL to include location field
        // It should now update 7 fields + WHERE clause ID = 8 placeholders.
        $sql = "UPDATE items SET name = ?, category_id = ?, barcode = ?, unit = ?, low_stock_threshold = ?, description = ?, location = ? WHERE id = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Corrected bind_param types and order to match the SQL query and variable types (8 parameters)
            // Types: s (name), i (category_id), s (barcode), s (unit), i (low_stock_threshold), s (description), s (location), i (id)
            mysqli_stmt_bind_param($stmt, "sisissis",
                $item_name,
                $item_category_id,
                $item_barcode,
                $item_unit,
                $item_low_stock_threshold,
                $item_description,
                $item_location, // Added location variable
                $item_id // This is the WHERE clause parameter
            );

            if (mysqli_stmt_execute($stmt)) {
                 // *** Added affected rows check ***
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    // Log the activity
                    $log_sql = "INSERT INTO activity_log (activity_type, entity_type, entity_id, entity_name, reason) VALUES (?, ?, ?, ?, ?)";
                    if ($log_stmt = mysqli_prepare($link, $log_sql)) {
                        $activity_type = 'item_updated';
                        $entity_type = 'item';
                        $reason = 'Item details updated';
                        mysqli_stmt_bind_param($log_stmt, "ssiss", $activity_type, $entity_type, $item_id, $item_name, $reason);
                        mysqli_stmt_execute($log_stmt);
                        mysqli_stmt_close($log_stmt);
                    }

                    // Redirect to the main inventory page with status
                    header("Location: index.php?page=inventory&status=item_updated");
                    exit;
                } else {
                     // No rows affected - item ID might not exist or no changes were made
                     $message = "<p class='alert alert--info'>Item with ID " . htmlspecialchars($item_id) . " details submitted, but no changes were detected or the item was not found.</p>";
                }
                 // *** End affected rows check ***

            } else {
                // *** Added detailed error reporting here ***
                $error_message = mysqli_stmt_error($stmt);
                if (empty($error_message)) {
                    $error_message = mysqli_error($link); // Fallback to connection error
                }
                $message = "<p class='error'>Error updating item: " . htmlspecialchars($error_message) . "</p>";

                 // Optional: log the error to a file as well
                 // error_log("Edit Item Update Error: " . $error_message . " SQL: " . $sql . " Item ID: " . $item_id);
                // *** End added error reporting ***
            }
            mysqli_stmt_close($stmt);
        } else {
             // *** Added detailed error reporting here ***
             $error_message = mysqli_error($link);
             $message .= "<p class='error'>Error preparing update query: " . htmlspecialchars($error_message) . "</p>";
              // Optional: log the error to a file as well
              // error_log("Edit Item Prepare Error: " . $error_message . " SQL: " . $sql);
             // *** End added error reporting ***
        }
    } else {
        $message = "<p class='error'>Item Name and Category are required.</p>";
    }

    // Label for the goto statement
    end_post_processing:;
}

// Fetch categories for the dropdown
$categories_options = [];
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
if ($result_cat = mysqli_query($link, $sql_categories)) {
    while ($row_cat = mysqli_fetch_assoc($result_cat)) {
        $categories_options[] = $row_cat;
    }
    mysqli_free_result($result_cat);
} else {
     $message .= "<p class='error'>Error fetching categories for form: " . mysqli_error($link) . "</p>";
}

// Fetch the item details for pre-filling the form
if ($item_id > 0) { // Ensure $item_id is valid before fetching
    $sql_fetch = "SELECT name, category_id, barcode, quantity, unit, low_stock_threshold, description, location FROM items WHERE id = ?";
    if ($stmt_fetch = mysqli_prepare($link, $sql_fetch)) {
        mysqli_stmt_bind_param($stmt_fetch, "i", $item_id);
        if (mysqli_stmt_execute($stmt_fetch)) {
            mysqli_stmt_store_result($stmt_fetch);
            if (mysqli_stmt_num_rows($stmt_fetch) == 1) {
                mysqli_stmt_bind_result($stmt_fetch, $fetched_name, $fetched_cat_id, $fetched_barcode, $fetched_qty, $fetched_unit, $fetched_low_stock, $fetched_desc, $fetched_location);
                if (mysqli_stmt_fetch($stmt_fetch)) {
                    // Populate variables with fetched data for form
                    $item_name = $fetched_name;
                    $item_category_id = $fetched_cat_id;
                    $item_barcode = $fetched_barcode;
                    $item_quantity = $fetched_qty;
                    $item_unit = $fetched_unit;
                    $item_low_stock_threshold = $fetched_low_stock;
                    $item_description = $fetched_desc;
                    $item_location = $fetched_location;
                }
            } else {
                // Redirect to the main inventory page if item not found during fetch
                header("Location: index.php?page=inventory&error=item_notfound");
                exit;
            }
        } else {
             $error_message = mysqli_stmt_error($stmt_fetch);
             if (empty($error_message)) { $error_message = mysqli_error($link); }
            $message = "<p class='error'>Error fetching item details: " . htmlspecialchars($error_message) . "</p>";
        }
        mysqli_stmt_close($stmt_fetch);
    } else {
         $error_message = mysqli_error($link);
         $message .= "<p class='error'>Error preparing item fetch query: " . htmlspecialchars($error_message) . "</p>";
    }
} else {
     // This else is reached if item_id was not valid initially or after POST error
     $message = "<p class='error'>Invalid item ID provided for editing.</p>";
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
            <div class="alert <?php echo strpos($message, 'success') !== false ? 'alert--success' : (strpos($message, 'alert--info') !== false ? 'alert--info' : 'alert--error'); ?> mb-4">
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
                     <!-- Add a hidden field for item_id so it's available on POST -->
                    <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item_id); ?>">

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
                        <label class="form__label">Location</label>
                        <input type="text" name="item_location" class="form__input" value="<?php echo htmlspecialchars($item_location); ?>">
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
