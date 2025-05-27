<?php
$message = '';

// Handle Stock In/Out form submission
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
            $message = "<p class='success'>Stock transaction successful. Quantity updated and logged.</p>";
        } else {
            mysqli_rollback($link);
            $message .= "<p class='error'>Stock transaction failed. No changes were made.</p>";
        }
        mysqli_autocommit($link, true); // End transaction

    } else {
        $message = "<p class='error'>Item, Quantity Change, and Reason are required, and quantity change must be greater than 0.</p>";
    }
}

// Fetch items for the dropdown
$items_options = [];
$sql_items = "SELECT id, name, quantity, unit FROM items ORDER BY name ASC";
if ($result_items_opt = mysqli_query($link, $sql_items)) {
    while ($row_item_opt = mysqli_fetch_assoc($result_items_opt)) {
        $items_options[] = $row_item_opt;
    }
    mysqli_free_result($result_items_opt);
}

// Fetch recent activity logs to display from the new activity_log table
$inventory_logs = []; // Renamed from $inventory_logs to $activity_logs for clarity, but keeping original variable name for minimal changes
$sql_fetch_logs = "SELECT 
                        id, 
                        activity_type as type,  -- Alias activity_type to 'type'
                        entity_type, 
                        entity_id, 
                        entity_name as item_name, -- Alias entity_name to 'item_name'
                        quantity_change, 
                        reason, 
                        DATE_FORMAT(log_date, '%Y-%m-%d %H:%i:%s') as log_date 
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

// Add CSS link in the head section
?>
<link rel="stylesheet" href="css/main.css">

<div class="container">
    <div class="page">
        <header class="d-flex justify-between align-center mb-4">
            <div>
                <h2 class="card__title">Inventory Tracking</h2>
                <p class="text-muted">Manage stock movements and track inventory changes.</p>
            </div>
        </header>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo strpos($message, 'success') !== false ? 'alert--success' : 'alert--error'; ?> mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid--2-cols gap-4">
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

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" name="stock_in" class="btn btn--success">Stock In</button>
                            <button type="submit" name="stock_out" class="btn btn--danger">Stock Out</button>
                        </div>
                    </form>
                </div>
            </div>

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
                                            <td class="table__cell"><?php echo htmlspecialchars($log['item_name']); ?></td>
                                            <td class="table__cell">
                                                <span class="btn btn--<?php echo $log['type'] == 'in' ? 'success' : 'danger'; ?>">
                                                    <?php echo htmlspecialchars(ucfirst($log['type'])); ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const barcodeInput = document.getElementById('barcode_scanner_input');
    const itemSelect = document.getElementById('item_id');
    const quantityInput = document.getElementById('quantity_change');
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
