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
            $total_items = $conn->query("SELECT COUNT(*) FROM items")->fetch_row()[0];
            $total_categories = $conn->query("SELECT COUNT(*) FROM categories")->fetch_row()[0];
            $total_locations = $conn->query("SELECT COUNT(*) FROM locations")->fetch_row()[0];
            ?>
            <div>Total Items: <strong><?php echo $total_items; ?></strong></div>
            <div>Total Categories: <strong><?php echo $total_categories; ?></strong></div>
            <div>Total Locations: <strong><?php echo $total_locations; ?></strong></div>
        </div>
    </div>
    <?php include 'includes/modals.php'; ?>
</body>
</html>
