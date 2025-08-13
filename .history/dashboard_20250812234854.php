<?php
include 'includes/auth.php';
$page_title = 'Dashboard';
include 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>
    <div class="main-content">
        <div class="dashboard-section">
            <h2>Inventory Overview</h2>
            <div class="dashboard-container">
                <div class="dashboard-summary">
                    <?php
                    // Get summary counts
                    function get_count($conn, $sql) {
                        $result = $conn->query($sql);
                        if ($result && $row = $result->fetch_row()) {
                            return $row[0];
                        }
                        return 0;
                    }

                    $total_items = get_count($conn, "SELECT COUNT(*) FROM items");
                    $low_stock_count = get_count($conn, "SELECT COUNT(*) FROM items WHERE current_stock <= low_stock");
                    $now = date('Y-m-d');
                    $near_expired_count = get_count($conn, "SELECT COUNT(*) FROM item_batches WHERE expiry_date <= DATE_ADD('$now', INTERVAL 7 DAY) AND expiry_date >= '$now'");
                    $expired_count = get_count($conn, "SELECT COUNT(*) FROM item_batches WHERE expiry_date < '$now'");
                    $total_near_expired = $near_expired_count + $expired_count;

                    // Determine color for Total Items (Green: always normal)
                    $total_items_color = '#7D9D8A'; // Green

                    // Determine color for Near/Expired Items (Orange: warning, Red: urgent)
                    if ($expired_count > 0) { // Prioritize red if any items are expired
                        $near_expired_color = '#D33F49'; // Red
                    } elseif ($total_near_expired > 0) { // Orange if there are near-expired items but no expired ones
                        $near_expired_color = '#FFA500'; // Orange
                    } else {
                        $near_expired_color = '#7D9D8A'; // Green (normal)
                    }

                    // Determine color for Low Stock Alerts (Orange: warning, Red: urgent)
                    if ($low_stock_count > 5) { // Example threshold for urgent
                        $low_stock_color = '#D33F49'; // Red
                    } elseif ($low_stock_count > 0) { // Example threshold for warning
                        $low_stock_color = '#FFA500'; // Orange
                    } else {
                        $low_stock_color = '#7D9D8A'; // Green (normal)
                    }
                    ?>

                    <div class="summary-card">
                        <div class="card-title">Total Items</div>
                        <div class="card-content">
                            <svg class="color-indicator" width="20" height="20">
                                <circle cx="10" cy="10" r="10" fill="<?php echo $total_items_color; ?>"/>
                            </svg>
                            <span class="counter"><?php echo $total_items; ?></span>
                        </div>
                        <div class="card-description">Items in inventory</div>
                    </div>

                    <div class="summary-card">
                        <div class="card-title">Near/Expired Items</div>
                        <div class="card-content">
                            <svg class="color-indicator" width="20" height="20">
                                <circle cx="10" cy="10" r="10" fill="<?php echo $near_expired_color; ?>"/>
                            </svg>
                            <span class="counter"><?php echo $total_near_expired; ?></span>
                        </div>
                        <div class="card-description">Items near or past expiry</div>
                    </div>

                    <div class="summary-card">
                        <div class="card-title">Low Stock Alerts</div>
                        <div class="card-content">
                            <svg class="color-indicator" width="20" height="20">
                                <circle cx="10" cy="10" r="10" fill="<?php echo $low_stock_color; ?>"/>
                            </svg>
                            <span class="counter"><?php echo $low_stock_count; ?></span>
                        </div>
                        <div class="card-description">Items need attention</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="recent-activities-container">
            <h3>Recent Activities</h3>
            <div class="activity-list">
                <?php
                $logs_query = "SELECT l.action, l.date_time, i.name AS item_name, c.name AS category_name, i.expiry_date
                               FROM logs l
                               JOIN items i ON l.item_id = i.id
                               JOIN categories c ON i.category_id = c.id
                               ORDER BY l.date_time DESC
                               LIMIT 10"; // Fetch the 10 most recent activities
                $logs_result = $conn->query($logs_query);

                if ($logs_result->num_rows > 0) {
                    echo '<table>';
                    echo '<thead><tr><th>Item Name</th><th>Category</th><th>Action</th><th>Date & Time</th><th>Expiry Status</th></tr></thead>';
                    echo '<tbody>';
                    while ($row = $logs_result->fetch_assoc()) {
                        $expiry_status = $row['expiry_date'] ? date('M d, Y', strtotime($row['expiry_date'])) : 'NO EXPIRY';
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['item_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['category_name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['action']) . '</td>';
                        echo '<td>' . htmlspecialchars(date('M d, Y H:i:s', strtotime($row['date_time']))) . '</td>';
                        echo '<td>' . htmlspecialchars($expiry_status) . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody>';
                    echo '</table>';
                } else {
                    echo '<p>No recent activities.</p>';
                }
                ?>
            </div>
        </div>
    </div>
    <?php include 'includes/modals.php'; ?>
</body>
</html>
