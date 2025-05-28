<?php
// Fetch data for dashboard widgets

// Check database connection status
$db_connected = false;
if (isset($conn) && $conn) {
    $db_connected = mysqli_ping($conn);
}

// Total Items
$total_items_count = 0;
$sql_total_items = "SELECT COUNT(*) as count FROM items";
if ($result_total = mysqli_query($conn, $sql_total_items)) {
    $total_items_count = mysqli_fetch_assoc($result_total)['count'];
    mysqli_free_result($result_total);
}

// Total Categories
$total_categories = 0;
$sql_categories = "SELECT COUNT(*) as count FROM categories";
if ($result_categories = mysqli_query($conn, $sql_categories)) {
    $total_categories = mysqli_fetch_assoc($result_categories)['count'];
    mysqli_free_result($result_categories);
}


// Low Stock Items
$low_stock_items = [];
$sql_low_stock = "SELECT id, name, quantity, unit, low_stock_threshold FROM items WHERE quantity <= low_stock_threshold AND low_stock_threshold > 0 ORDER BY name ASC";
if ($result_low_stock = mysqli_query($conn, $sql_low_stock)) {
    while ($row_low = mysqli_fetch_assoc($result_low_stock)) {
        $low_stock_items[] = $row_low;
    }
    mysqli_free_result($result_low_stock);
}
$low_stock_count = count($low_stock_items);

// Category Distribution
$category_distribution = [];
$sql_cat_dist = "SELECT c.name, COUNT(i.id) as item_count 
                 FROM categories c 
                 LEFT JOIN items i ON c.id = i.category_id 
                 GROUP BY c.id, c.name 
                 ORDER BY item_count DESC";
if ($result_cat = mysqli_query($conn, $sql_cat_dist)) {
    while ($row_cat = mysqli_fetch_assoc($result_cat)) {
        $category_distribution[] = $row_cat;
    }
    mysqli_free_result($result_cat);
}

// Recent Activity
$recent_activity = [];
$sql_recent_activity = "SELECT il.id, i.name as item_name, il.type, il.quantity_change, il.reason, DATE_FORMAT(il.log_date, '%Y-%m-%d %H:%i') as log_date 
                        FROM inventory_log il
                        JOIN items i ON il.item_id = i.id
                        ORDER BY il.log_date DESC LIMIT 5";
if($result_activity = mysqli_query($conn, $sql_recent_activity)){
    while($row_activity = mysqli_fetch_assoc($result_activity)){
        $recent_activity[] = $row_activity;
    }
    mysqli_free_result($result_activity);
}


?>

<!-- Removed inline dashboard CSS, now in assets/css/dashboard.css -->

<!-- Add CSS link in the head section -->
<link rel="stylesheet" href="../css/main.css">

<div class="container">
    <div class="page">
        <div class="grid grid--3-cols">
            <div class="card card--metric">
                <div class="card__body">
                    <div class="metric-indicator metric-indicator--green"></div>
                    <div>
                        <h2 class="metric-title">Total Items</h2>
                        <p class="metric-value"><?php echo number_format($total_items_count); ?></p>
                        <p class="metric-description text-muted">Items in inventory</p>
                    </div>
                </div>
            </div>

            <div class="card card--metric">
                <div class="card__body">
                    <div class="metric-indicator <?php echo $db_connected ? 'metric-indicator--green' : 'metric-indicator--red'; ?>"></div>
                    <div>
                        <h2 class="metric-title">Categories</h2>
                        <p class="metric-value"><?php echo number_format($total_categories); ?></p>
                        <p class="metric-description text-muted">Active categories</p>
                    </div>
                </div>
            </div>

            <div class="card card--metric <?php echo ($low_stock_count > 0) ? 'card--warning' : ''; ?>">
                <div class="card__body">
                    <div class="metric-indicator <?php echo ($low_stock_count > 0) ? 'metric-indicator--red' : 'metric-indicator--green'; ?>"></div>
                    <div>
                        <h2 class="metric-title">Low Stock Alerts</h2>
                        <p class="metric-value"><?php echo number_format($low_stock_count); ?></p>
                        <p class="metric-description text-muted">Items need attention</p>
            </div>
        </div>
    </div>
</div>

<script src="js/dashboard_tabs.js"></script>

        <div class="tabs-container mt-4">
            <div class="tabs">
                <button class="tab-button active" data-tab="recent-activity">Recent Activity</button>
                <button class="tab-button" data-tab="low-stock-items">Low Stock Items</button>
                <button class="tab-button" data-tab="category-distribution">Category Distribution</button>
            </div>

            <div id="recent-activity" class="tab-content active card">
                <div class="card__header">
                    <h2 class="card__title">Recent Activity</h2>
                </div>
                <div class="card__body card__body--scrollable">
                    <?php if (!empty($recent_activity)): ?>
                    <div class="table">
                        <table class="w-100">
                            <thead>
                                <tr class="table__header">
                                    <th class="table__cell">Item</th>
                                    <th class="table__cell">Type</th>
                                    <th class="table__cell">Quantity</th>
                                    <th class="table__cell">Reason</th>
                                    <th class__cell">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_activity as $activity): ?>
                                <tr class="table__row">
                                    <td class="table__cell"><?php echo htmlspecialchars($activity['item_name']); ?></td>
                                    <td class="table__cell">
                                        <span class="btn btn--<?php echo $activity['type'] == 'in' ? 'success' : ($activity['type'] == 'out' ? 'danger' : 'primary'); ?>">
                                            <?php echo htmlspecialchars(ucfirst($activity['type'])); ?>
                                        </span>
                                    </td>
                                    <td class="table__cell"><?php echo htmlspecialchars($activity['quantity_change']); ?></td>
                                    <td class="table__cell"><?php echo htmlspecialchars($activity['reason'] ?? 'N/A'); ?></td>
                                    <td class="table__cell"><?php echo htmlspecialchars($activity['log_date']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-center text-muted">No recent stock movements.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div id="low-stock-items" class="tab-content card">
                <div class="card__header">
                    <h2 class="card__title">Low Stock Items</h2>
                </div>
                <div class="card__body card__body--scrollable">
                    <?php if (!empty($low_stock_items)): ?>
                    <div class="table">
                        <table class="w-100">
                            <thead>
                                <tr class="table__header">
                                    <th class="table__cell">Item Name</th>
                                    <th class="table__cell">Quantity</th>
                                    <th class="table__cell">Threshold</th>
                                    <th class="table__cell">Unit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($low_stock_items as $item): ?>
                                <tr class="table__row">
                                    <td class="table__cell"><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td class="table__cell"><?php echo number_format($item['quantity']); ?></td>
                                    <td class="table__cell"><?php echo number_format($item['low_stock_threshold']); ?></td>
                                    <td class="table__cell"><?php echo htmlspecialchars($item['unit']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-center text-muted">No items currently low in stock.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div id="category-distribution" class="tab-content card">
                <div class="card__header">
                    <h2 class="card__title">Category Distribution</h2>
                </div>
                <div class="card__body card__body--scrollable">
                    <?php if (!empty($category_distribution)): ?>
                    <div class="table">
                        <table class="w-100">
                            <thead>
                                <tr class="table__header">
                                    <th class="table__cell">Category</th>
                                    <th class="table__cell">Items</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($category_distribution as $cat): ?>
                                <tr class="table__row">
                                    <td class="table__cell"><?php echo htmlspecialchars($cat['name']); ?></td>
                                    <td class="table__cell"><?php echo number_format($cat['item_count']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-center text-muted">No categories found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
