<?php
// Fetch data for dashboard widgets

// Total Items
$total_items_count = 0;
$sql_total_items = "SELECT COUNT(*) as count FROM items";
if ($result_total = mysqli_query($link, $sql_total_items)) {
    $total_items_count = mysqli_fetch_assoc($result_total)['count'];
    mysqli_free_result($result_total);
}

// Low Stock Items
$low_stock_items = [];
$sql_low_stock = "SELECT id, name, quantity, unit, low_stock_threshold FROM items WHERE quantity <= low_stock_threshold AND low_stock_threshold > 0 ORDER BY name ASC";
if ($result_low_stock = mysqli_query($link, $sql_low_stock)) {
    while ($row_low = mysqli_fetch_assoc($result_low_stock)) {
        $low_stock_items[] = $row_low;
    }
    mysqli_free_result($result_low_stock);
}
$low_stock_count = count($low_stock_items);

// Recent Activity (Placeholder - this would typically come from inventory_log or a dedicated audit log)
$recent_activity = [];
$sql_recent_activity = "SELECT il.id, i.name as item_name, il.type, il.quantity_change, il.reason, DATE_FORMAT(il.log_date, '%Y-%m-%d %H:%i') as log_date 
                        FROM inventory_log il
                        JOIN items i ON il.item_id = i.id
                        ORDER BY il.log_date DESC LIMIT 5";
if($result_activity = mysqli_query($link, $sql_recent_activity)){
    while($row_activity = mysqli_fetch_assoc($result_activity)){
        $recent_activity[] = $row_activity;
    }
    mysqli_free_result($result_activity);
}

?>
<h1>Dashboard</h1>
<p>Welcome to your Inventory System!</p>

<div class="dashboard-widgets">
    <div class="widget">
        <h2>Quick Stats</h2>
        <p>Total Items: <span id="total-items"><?php echo $total_items_count; ?></span></p>
        <p>Low Stock Alerts: <span id="low-stock-count" class="<?php echo ($low_stock_count > 0) ? 'text-danger' : ''; ?>"><?php echo $low_stock_count; ?></span></p>
    </div>
    <div class="widget">
        <h2>Recent Activity (Last 5)</h2>
        <?php if (!empty($recent_activity)): ?>
        <ul id="recent-activity">
            <?php foreach($recent_activity as $activity): ?>
                <li>
                    <strong><?php echo htmlspecialchars($activity['item_name']); ?>:</strong> 
                    <?php echo htmlspecialchars(ucfirst($activity['type'])); ?> 
                    (Qty: <?php echo htmlspecialchars($activity['quantity_change']); ?>) 
                    - Reason: <?php echo htmlspecialchars($activity['reason'] ?? 'N/A'); ?>
                    on <?php echo htmlspecialchars($activity['log_date']); ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p>No recent stock movements.</p>
        <?php endif; ?>
    </div>
</div>

<?php if ($low_stock_count > 0): ?>
<div class="widget low-stock-alert-widget">
    <h2><span class="text-danger">Warning: Low Stock Items!</span></h2>
    <p>The following items are at or below their reorder threshold:</p>
    <table>
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Current Quantity</th>
                <th>Unit</th>
                <th>Low Stock At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($low_stock_items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                <td><?php echo htmlspecialchars($item['unit']); ?></td>
                <td><?php echo htmlspecialchars($item['low_stock_threshold']); ?></td>
                <td><a href="index.php?page=edit_item&id=<?php echo $item['id']; ?>">View/Edit Item</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<style>
/* Additional styles for dashboard, can be moved to style.css */
.text-danger {
    color: #d9534f; /* Red for alerts */
    font-weight: bold;
}
.low-stock-alert-widget {
    border: 2px solid #d9534f;
}
.low-stock-alert-widget h2 {
    color: #d9534f;
}
.dashboard-widgets .widget ul {
    list-style-type: none;
    padding-left: 0;
}
.dashboard-widgets .widget ul li {
    padding: 5px 0;
    border-bottom: 1px solid #eee;
}
.dashboard-widgets .widget ul li:last-child {
    border-bottom: none;
}

</style> 