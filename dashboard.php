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
            $low_stock = get_count($conn, "SELECT COUNT(*) FROM items WHERE stock <= low_stock");
            $now = date('Y-m-d');
            $near_expired = get_count($conn, "SELECT COUNT(*) FROM item_batches WHERE expiry_date <= DATE_ADD('$now', INTERVAL 7 DAY) AND expiry_date >= '$now'");
            $expired = get_count($conn, "SELECT COUNT(*) FROM item_batches WHERE expiry_date < '$now'");
            ?>
            <div style="background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(52,73,94,0.16);padding:24px 32px;min-width:220px;margin-right:24px;display:inline-block;vertical-align:top;">
                <div style="font-size:1.1em;font-weight:600;color:#34495E;">Total Items</div>
                <div style="display:flex;align-items:center;margin-top:8px;">
                    <span style="width:18px;height:18px;background:#7D9D8A;border-radius:50%;display:inline-block;margin-right:10px;"></span>
                    <span style="font-size:2em;font-weight:700;color:#34495E;"><?php echo $total_items; ?></span>
                </div>
                <div style="color:#7D9D8A;margin-top:6px;">Items in inventory</div>
            </div>
            <div style="background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(52,73,94,0.16);padding:24px 32px;min-width:220px;margin-right:24px;display:inline-block;vertical-align:top;">
                <div style="font-size:1.1em;font-weight:600;color:#34495E;">Near/Expired Items</div>
                <div style="display:flex;align-items:center;margin-top:8px;">
                    <span style="width:18px;height:18px;background:#FFA500;border-radius:50%;display:inline-block;margin-right:10px;"></span>
                    <span style="font-size:2em;font-weight:700;color:#34495E;"><?php echo $near_expired + $expired; ?></span>
                </div>
                <div style="color:#FFA500;margin-top:6px;">Items near or past expiry</div>
            </div>
            <div style="background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(52,73,94,0.16);padding:24px 32px;min-width:220px;display:inline-block;vertical-align:top;">
                <div style="font-size:1.1em;font-weight:600;color:#34495E;">Low Stock Alerts</div>
                <div style="display:flex;align-items:center;margin-top:8px;">
                    <span style="width:18px;height:18px;background:#D33F49;border-radius:50%;display:inline-block;margin-right:10px;"></span>
                    <span style="font-size:2em;font-weight:700;color:#34495E;"><?php echo $low_stock; ?></span>
                </div>
                <div style="color:#D33F49;margin-top:6px;">Items need attention</div>
            </div>
        </div>
    </div>
    <?php include 'includes/modals.php'; ?>
</body>
</html>
