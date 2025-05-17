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

        // 2. Add entry to inventory_log
        if ($success) {
            $sql_log = "INSERT INTO inventory_log (item_id, type, quantity_change, reason) VALUES (?, ?, ?, ?)";
            if ($stmt_log = mysqli_prepare($link, $sql_log)) {
                mysqli_stmt_bind_param($stmt_log, "isis", $item_id, $log_type, $quantity_change, $reason);
                if (mysqli_stmt_execute($stmt_log)) {
                    // $message .= "<p class='success'>Stock {$log_type} logged successfully!</p>"; // Will be set by commit
                } else {
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

// Fetch recent inventory logs to display
$inventory_logs = [];
$sql_fetch_logs = "SELECT il.id, i.name as item_name, il.type, il.quantity_change, il.reason, DATE_FORMAT(il.log_date, '%Y-%m-%d %H:%i:%s') as log_date 
                     FROM inventory_log il 
                     JOIN items i ON il.item_id = i.id 
                     ORDER BY il.log_date DESC LIMIT 20";

if ($result_logs = mysqli_query($link, $sql_fetch_logs)) {
    if (mysqli_num_rows($result_logs) > 0) {
        while ($row_log = mysqli_fetch_assoc($result_logs)) {
            $inventory_logs[] = $row_log;
        }
        mysqli_free_result($result_logs);
    }
} else {
    $message .= "<p class='error'>Error fetching inventory logs: " . mysqli_error($link) . "</p>";
}

?>

<h2>Inventory Tracking (Stock In/Out)</h2>

<?php echo $message; ?>

<div class="form-container tracking-form">
    <h3>Log Stock Movement</h3>
    
    <div style="margin-bottom: 15px;">
        <label for="barcode_scanner_input">Scan Barcode:</label>
        <input type="text" id="barcode_scanner_input" placeholder="Click here and scan barcode...">
        <span id="barcode_status" style="margin-left: 10px;"></span>
    </div>

    <form action="index.php?page=tracking" method="post" id="stock_movement_form">
        <div>
            <label for="item_id">Select Item:</label>
            <select id="item_id" name="item_id" required>
                <option value="">-- Select Item --</option>
                <?php foreach ($items_options as $item_opt): ?>
                <option value="<?php echo $item_opt['id']; ?>">
                    <?php echo htmlspecialchars($item_opt['name']); ?> (Current Stock: <?php echo htmlspecialchars($item_opt['quantity']); ?> <?php echo htmlspecialchars($item_opt['unit']); ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="quantity_change">Quantity Change:</label>
            <input type="number" id="quantity_change" name="quantity_change" min="1" required>
        </div>
        <div>
            <label for="reason">Reason/Note (e.g., New Shipment, Used for X, Spoilage):</label>
            <input type="text" id="reason" name="reason" required>
        </div>
        <div>
            <button type="submit" name="stock_in" class="stock-in-btn">Stock In</button>
            <button type="submit" name="stock_out" class="stock-out-btn">Stock Out</button>
        </div>
    </form>
</div>

<div class="table-container">
    <h3>Recent Inventory Movements (Last 20)</h3>
    <?php if (!empty($inventory_logs)): ?>
    <table>
        <thead>
            <tr>
                <th>Log ID</th>
                <th>Item Name</th>
                <th>Type</th>
                <th>Qty Change</th>
                <th>Reason</th>
                <!-- <th>User</th> Will be blank if no user system -->
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($inventory_logs as $log): ?>
            <tr>
                <td><?php echo htmlspecialchars($log['id']); ?></td>
                <td><?php echo htmlspecialchars($log['item_name']); ?></td>
                <td class="log-type-<?php echo htmlspecialchars($log['type']); ?>"><?php echo htmlspecialchars(ucfirst($log['type'])); ?></td>
                <td><?php echo htmlspecialchars($log['quantity_change']); ?></td>
                <td><?php echo htmlspecialchars($log['reason']); ?></td>
                <!-- <td><?php echo htmlspecialchars($log['user_name'] ?? 'N/A'); ?></td> -->
                <td><?php echo htmlspecialchars($log['log_date']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>No inventory movements logged yet.</p>
    <?php endif; ?>
</div>

<style>
/* Styles can be moved to a global style.css later */
.form-container, .table-container { margin-bottom: 20px; background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1);}
.tracking-form div { margin-bottom: 10px; }
.tracking-form label { display: block; margin-bottom: 5px; }
.tracking-form input[type="text"],
.tracking-form input[type="number"],
.tracking-form select {
    width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
}
.tracking-form button { padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; color: white; margin-right:10px; }
.stock-in-btn { background-color: #5cb85c; /* Green */ }
.stock-in-btn:hover { background-color: #4cae4c; }
.stock-out-btn { background-color: #d9534f; /* Red */ }
.stock-out-btn:hover { background-color: #c9302c; }

table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
tr:nth-child(even) { background-color: #f2f2f2; }
th { background-color: #333; color: white; }

.log-type-in { color: green; font-weight: bold; }
.log-type-out { color: red; font-weight: bold; }
.log-type-adjustment { color: blue; font-weight: bold; }

.success { color: green; border: 1px solid green; padding: 10px; margin-bottom: 15px; background-color: #e6ffe6; }
.error { color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px; background-color: #ffe6e6; }
</style>

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