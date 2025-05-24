<?php
// Fetch data for dashboard widgets

// Total Items
$total_items_count = 0;
$sql_total_items = "SELECT COUNT(*) as count FROM items";
if ($result_total = mysqli_query($link, $sql_total_items)) {
    $total_items_count = mysqli_fetch_assoc($result_total)['count'];
    mysqli_free_result($result_total);
}

// Total Categories
$total_categories = 0;
$sql_categories = "SELECT COUNT(*) as count FROM categories";
if ($result_categories = mysqli_query($link, $sql_categories)) {
    $total_categories = mysqli_fetch_assoc($result_categories)['count'];
    mysqli_free_result($result_categories);
}

// Total Stock Value
$total_stock_value = 0;
$sql_stock_value = "SELECT SUM(quantity * purchase_price) as total FROM items";
if ($result_stock = mysqli_query($link, $sql_stock_value)) {
    $total_stock_value = mysqli_fetch_assoc($result_stock)['total'] ?? 0;
    mysqli_free_result($result_stock);
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

// Category Distribution
$category_distribution = [];
$sql_cat_dist = "SELECT c.name, COUNT(i.id) as item_count 
                 FROM categories c 
                 LEFT JOIN items i ON c.id = i.category_id 
                 GROUP BY c.id, c.name 
                 ORDER BY item_count DESC";
if ($result_cat = mysqli_query($link, $sql_cat_dist)) {
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
if($result_activity = mysqli_query($link, $sql_recent_activity)){
    while($row_activity = mysqli_fetch_assoc($result_activity)){
        $recent_activity[] = $row_activity;
    }
    mysqli_free_result($result_activity);
}
?>

<!-- Removed inline dashboard CSS, now in assets/css/dashboard.css -->

<!-- Add CSS link in the head section -->
<link rel="stylesheet" href="css/main.css">

<div class="container">
    <div class="page">
        <div class="grid grid--4-cols">
            <div class="card">
                <div class="card__header">
                    <h2 class="card__title">Total Items</h2>
                </div>
                <div class="card__body">
                    <h3 class="text-center"><?php echo number_format($total_items_count); ?></h3>
                    <p class="text-center text-muted">Items in inventory</p>
                </div>
            </div>

            <div class="card">
                <div class="card__header">
                    <h2 class="card__title">Categories</h2>
                </div>
                <div class="card__body">
                    <h3 class="text-center"><?php echo number_format($total_categories); ?></h3>
                    <p class="text-center text-muted">Active categories</p>
                </div>
            </div>

            <div class="card">
                <div class="card__header">
                    <h2 class="card__title">Total Stock Value</h2>
                </div>
                <div class="card__body">
                    <h3 class="text-center">$<?php echo number_format($total_stock_value, 2); ?></h3>
                    <p class="text-center text-muted">Current inventory value</p>
                </div>
            </div>

            <div class="card <?php echo ($low_stock_count > 0) ? 'alert alert--warning' : ''; ?>">
                <div class="card__header">
                    <h2 class="card__title">Low Stock Alerts</h2>
                </div>
                <div class="card__body">
                    <h3 class="text-center <?php echo ($low_stock_count > 0) ? 'text-danger' : ''; ?>"><?php echo number_format($low_stock_count); ?></h3>
                    <p class="text-center text-muted">Items need attention</p>
                </div>
            </div>
        </div>

        <div class="grid grid--2-cols mt-4">
            <div class="card" id="recent-activity-card">
                <div class="card__header">
                    <h2 class="card__title">Recent Activity</h2>
                </div>
                <div class="card__body">
                    <?php if (!empty($recent_activity)): ?>
                    <div class="table">
                        <table class="w-100">
                            <thead>
                                <tr class="table__header">
                                    <th class="table__cell">Item</th>
                                    <th class="table__cell">Type</th>
                                    <th class="table__cell">Quantity</th>
                                    <th class="table__cell">Reason</th>
                                    <th class="table__cell">Date</th>
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

            <div class="card">
                <div class="card__header">
                    <h2 class="card__title">Category Distribution</h2>
                </div>
                <div class="card__body">
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

<script>
// Function to update recent activities
function updateRecentActivities() {
    fetch('ajax/get_recent_activities.php')
        .then(response => response.text())
        .then(html => {
            const activitiesContainer = document.querySelector('#recent-activity-card .card__body');
            if (activitiesContainer) {
                activitiesContainer.innerHTML = html;
            }
        })
        .catch(error => console.error('Error updating recent activities:', error.message));
}

// Update activities every 30 seconds
setInterval(updateRecentActivities, 30000);

// Initial update when page loads
document.addEventListener('DOMContentLoaded', updateRecentActivities);
</script>
