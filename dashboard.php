<?php
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
                $low_stock_count = get_count($conn, "SELECT COUNT(*) FROM items WHERE stock <= low_stock");
                $now = date('Y-m-d');
                $near_expired_count = get_count($conn, "SELECT COUNT(*) FROM item_batches WHERE expiry_date <= DATE_ADD('$now', INTERVAL 7 DAY) AND expiry_date >= '$now'");
                $expired_count = get_count($conn, "SELECT COUNT(*) FROM item_batches WHERE expiry_date < '$now'");
                $total_near_expired = $near_expired_count + $expired_count;

                // Determine color for Total Items (Green: always normal)
                $total_items_color = '#7D9D8A'; // Green

                // Determine color for Near/Expired Items (Orange: warning, Red: urgent)
                if ($total_near_expired > 10) { // Example threshold for urgent
                    $near_expired_color = '#D33F49'; // Red
                } elseif ($total_near_expired > 0) { // Example threshold for warning
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

                <div class="summary-card" style="background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(52,73,94,0.16);padding:24px 32px;min-width:220px;margin-right:24px;display:inline-block;vertical-align:top;">
                    <div class="card-title" style="font-size:1.1em;font-weight:600;color:#34495E;">Total Items</div>
                    <div class="card-content" style="display:flex;align-items:center;margin-top:8px;">
                        <span class="color-indicator" style="width:18px;height:18px;background:<?php echo $total_items_color; ?>;border-radius:50%;display:inline-block;margin-right:10px;"></span>
                        <span class="counter" style="font-size:2em;font-weight:700;color:#34495E;"><?php echo $total_items; ?></span>
                    </div>
                    <div class="card-description" style="color:<?php echo $total_items_color; ?>;margin-top:6px;">Items in inventory</div>
                </div>

                <div class="summary-card" style="background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(52,73,94,0.16);padding:24px 32px;min-width:220px;margin-right:24px;display:inline-block;vertical-align:top;">
                    <div class="card-title" style="font-size:1.1em;font-weight:600;color:#34495E;">Near/Expired Items</div>
                    <div class="card-content" style="display:flex;align-items:center;margin-top:8px;">
                        <span class="color-indicator" style="width:18px;height:18px;background:<?php echo $near_expired_color; ?>;border-radius:50%;display:inline-block;margin-right:10px;"></span>
                        <span class="counter" style="font-size:2em;font-weight:700;color:#34495E;"><?php echo $total_near_expired; ?></span>
                    </div>
                    <div class="card-description" style="color:<?php echo $near_expired_color; ?>;margin-top:6px;">Items near or past expiry</div>
                </div>

                <div class="summary-card" style="background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(52,73,94,0.16);padding:24px 32px;min-width:220px;display:inline-block;vertical-align:top;">
                    <div class="card-title" style="font-size:1.1em;font-weight:600;color:#34495E;">Low Stock Alerts</div>
                    <div class="card-content" style="display:flex;align-items:center;margin-top:8px;">
                        <span class="color-indicator" style="width:18px;height:18px;background:<?php echo $low_stock_color; ?>;border-radius:50%;display:inline-block;margin-right:10px;"></span>
                        <span class="counter" style="font-size:2em;font-weight:700;color:#34495E;"><?php echo $low_stock_count; ?></span>
                    </div>
                    <div class="card-description" style="color:<?php echo $low_stock_color; ?>;margin-top:6px;">Items need attention</div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/modals.php'; ?>
</body>
</html>
