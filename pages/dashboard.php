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

<style>
/* Dashboard specific styles */
.content-wrapper {
    margin-left: 250px; /* Sidebar width */
    padding: 80px 20px 20px 20px; /* Top padding for header */
    min-height: 100vh;
    background: #f5f6fa;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.two-column-section {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
}

.card.alert {
    border-left: 4px solid var(--danger-color);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px 20px;
    border-bottom: 1px solid var(--border-color);
}

.card-header i {
    color: var(--primary-color);
    font-size: 1.2em;
}

.card-body {
    padding: 20px;
}

.card-body h3 {
    font-size: 2em;
    margin: 0;
    color: var(--primary-color);
}

.card-body p {
    margin: 5px 0 0;
    color: #666;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.activity-item {
    display: flex;
    gap: 15px;
    padding: 10px;
    border-radius: 4px;
    background: var(--light-gray);
}

.activity-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.activity-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.activity-type {
    font-size: 0.9em;
    padding: 2px 8px;
    border-radius: 12px;
    background: var(--light-gray);
}

.activity-type.in {
    background: var(--success-color);
    color: white;
}

.activity-type.out {
    background: var(--danger-color);
    color: white;
}

.activity-type.adjustment {
    background: var(--warning-color);
    color: white;
}

.category-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.category-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: var(--light-gray);
    border-radius: 4px;
}

.category-name {
    font-weight: 500;
}

.category-count {
    color: #666;
}

.text-danger {
    color: var(--danger-color);
}

.text-muted {
    color: #666;
    font-style: italic;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 0.9em;
}

.table-responsive {
    overflow-x: auto;
}

@media (max-width: 1100px) {
    .two-column-section {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .grid {
        grid-template-columns: 1fr !important;
    }
    .two-column-section {
        grid-template-columns: 1fr !important;
    }
    .header-actions {
        display: none;
    }
    .content-wrapper {
        margin-left: 0;
        padding: 80px 5px 5px 5px;
    }
}

.dashboard-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 24px 16px 0 16px;
}
</style>

<div class="content-wrapper">
    <div class="dashboard-container">
        <div class="page-header">
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
            <div class="header-actions">
                <a href="index.php?page=inventory" class="btn btn-primary">
                    <i class="fas fa-box"></i> View Inventory
                </a>
                <a href="index.php?page=items" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Item
                </a>
            </div>
        </div>

        <!-- Quick Stats Cards -->
        <div class="grid">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-boxes"></i>
                    <h2>Total Items</h2>
                </div>
                <div class="card-body">
                    <h3><?php echo number_format($total_items_count); ?></h3>
                    <p>Items in inventory</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-tags"></i>
                    <h2>Categories</h2>
                </div>
                <div class="card-body">
                    <h3><?php echo number_format($total_categories); ?></h3>
                    <p>Active categories</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-dollar-sign"></i>
                    <h2>Total Stock Value</h2>
                </div>
                <div class="card-body">
                    <h3>$<?php echo number_format($total_stock_value, 2); ?></h3>
                    <p>Current inventory value</p>
                </div>
            </div>

            <div class="card <?php echo ($low_stock_count > 0) ? 'alert' : ''; ?>">
                <div class="card-header">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h2>Low Stock Alerts</h2>
                </div>
                <div class="card-body">
                    <h3><?php echo number_format($low_stock_count); ?></h3>
                    <p>Items need attention</p>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="two-column-section">
            <!-- Left Column -->
            <div class="content-column">
                <?php if ($low_stock_count > 0): ?>
                <div class="card alert">
                    <div class="card-header">
                        <i class="fas fa-exclamation-circle"></i>
                        <h2>Low Stock Items</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
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
                                        <td class="<?php echo ($item['quantity'] == 0) ? 'text-danger' : ''; ?>">
                                            <?php echo htmlspecialchars($item['quantity']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                        <td><?php echo htmlspecialchars($item['low_stock_threshold']); ?></td>
                                        <td>
                                            <a href="index.php?page=edit_item&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-history"></i>
                        <h2>Recent Activity</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_activity)): ?>
                        <div class="activity-list">
                            <?php foreach($recent_activity as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-<?php echo $activity['type'] == 'in' ? 'arrow-down' : ($activity['type'] == 'out' ? 'arrow-up' : 'exchange-alt'); ?>"></i>
                                    </div>
                                    <div class="activity-details">
                                        <strong><?php echo htmlspecialchars($activity['item_name']); ?></strong>
                                        <span class="activity-type <?php echo $activity['type']; ?>">
                                            <?php echo htmlspecialchars(ucfirst($activity['type'])); ?>
                                        </span>
                                        <span class="activity-quantity">
                                            Qty: <?php echo htmlspecialchars($activity['quantity_change']); ?>
                                        </span>
                                        <span class="activity-reason">
                                            <?php echo htmlspecialchars($activity['reason'] ?? 'N/A'); ?>
                                        </span>
                                        <span class="activity-date">
                                            <?php echo htmlspecialchars($activity['log_date']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">No recent stock movements.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="content-column">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie"></i>
                        <h2>Category Distribution</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($category_distribution)): ?>
                        <div class="category-list">
                            <?php foreach($category_distribution as $cat): ?>
                                <div class="category-item">
                                    <div class="category-name">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </div>
                                    <div class="category-count">
                                        <?php echo number_format($cat['item_count']); ?> items
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">No categories found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 