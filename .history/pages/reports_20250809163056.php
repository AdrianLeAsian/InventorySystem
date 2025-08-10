<?php
require_once 'config/db.php';

$message = '';
$report_data_daily_in_out = [];
$report_date = date('Y-m-d'); // Default to today

// Fetch categories for filter dropdown
$categories = [];
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
if ($result_categories = mysqli_query($conn, $sql_categories)) {
    while ($row = mysqli_fetch_assoc($result_categories)) {
        $categories[] = $row;
    }
    mysqli_free_result($result_categories);
} else {
    $message .= "<p class='error'>Error fetching categories: " . mysqli_error($conn) . "</p>";
}

if (isset($_GET['report_date']) && !empty($_GET['report_date'])) {
    if (DateTime::createFromFormat('Y-m-d', $_GET['report_date']) !== false) {
        $report_date = $_GET['report_date'];
    } else {
        $message .= "<p class='error'>Invalid date format. Please use YYYY-MM-DD.</p>";
    }
}

// Fetch Daily In/Out Report Data
$sql_daily_report = "SELECT 
                        i.name as item_name,
                        SUM(CASE WHEN il.type = 'in' THEN il.quantity_change ELSE 0 END) as total_in,
                        SUM(CASE WHEN il.type = 'out' THEN il.quantity_change ELSE 0 END) as total_out,
                        DATE(il.log_date) as movement_date
                     FROM inventory_log il
                     JOIN items i ON il.item_id = i.id
                     WHERE DATE(il.log_date) = ?
                     GROUP BY i.id, i.name, DATE(il.log_date)
                     ORDER BY i.name ASC";

if ($stmt_daily = mysqli_prepare($conn, $sql_daily_report)) {
    mysqli_stmt_bind_param($stmt_daily, "s", $report_date);
    if (mysqli_stmt_execute($stmt_daily)) {
        $result_daily = mysqli_stmt_get_result($stmt_daily);
        while ($row = mysqli_fetch_assoc($result_daily)) {
            $report_data_daily_in_out[] = $row;
        }
        mysqli_free_result($result_daily);
    } else {
        $message .= "<p class='error'>Error executing daily report query: " . mysqli_error($conn) . "</p>";
    }
    mysqli_stmt_close($stmt_daily);
} else {
    $message .= "<p class='error'>Error preparing daily report query: " . mysqli_error($conn) . "</p>";
}


// Fetch Total Items
$total_items = 0;
$sql_total_items = "SELECT COUNT(*) as total FROM items";
if ($result_total_items = mysqli_query($conn, $sql_total_items)) {
    $row = mysqli_fetch_assoc($result_total_items);
    $total_items = $row['total'];
    mysqli_free_result($result_total_items);
} else {
    $message .= "<p class='error'>Error fetching total items: " . mysqli_error($conn) . "</p>";
}

// Fetch Total Categories
$total_categories = 0;
$sql_total_categories = "SELECT COUNT(*) as total FROM categories";
if ($result_total_categories = mysqli_query($conn, $sql_total_categories)) {
    $row = mysqli_fetch_assoc($result_total_categories);
    $total_categories = $row['total'];
    mysqli_free_result($result_total_categories);
} else {
    $message .= "<p class='error'>Error fetching total categories: " . mysqli_error($conn) . "</p>";
}

// Fetch Total Stock Quantity
$total_stock_quantity = 0;
$sql_total_stock_quantity = "SELECT SUM(quantity) as total FROM items";
if ($result_total_stock_quantity = mysqli_query($conn, $sql_total_stock_quantity)) {
    $row = mysqli_fetch_assoc($result_total_stock_quantity);
    $total_stock_quantity = $row['total'] ? $row['total'] : 0;
    mysqli_free_result($result_total_stock_quantity);
} else {
    $message .= "<p class='error'>Error fetching total stock quantity: " . mysqli_error($conn) . "</p>";
}

?>

<link rel="stylesheet" href="css/main.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<div class="container">
    <div class="page">
        <header class="d-flex justify-between align-center mb-4">
            <h1 class="header__title">Reports & Statistics</h1>
        </header>

        <?php if (!empty($message)): ?>
            <div class="alert alert--error mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Reports Overview Section -->
        <div class="grid grid--3-cols gap-4 mb-4">
            <div class="card card--metric">
                <div class="card__body">
                    <span class="metric-indicator metric-indicator--green"></span>
                    <div>
                        <h3 class="metric-title">Total Items</h3>
                        <p class="metric-value"><?php echo htmlspecialchars($total_items); ?></p>
                        <p class="metric-description">Number of unique items in inventory</p>
                    </div>
                </div>
            </div>
            <div class="card card--metric">
                <div class="card__body">
                    <span class="metric-indicator metric-indicator--blue"></span>
                    <div>
                        <h3 class="metric-title">Total Categories</h3>
                        <p class="metric-value"><?php echo htmlspecialchars($total_categories); ?></p>
                        <p class="metric-description">Number of item categories</p>
                    </div>
                </div>
            </div>
            <div class="card card--metric">
                <div class="card__body">
                    <span class="metric-indicator metric-indicator--red"></span>
                    <div>
                        <h3 class="metric-title">Total Stock Quantity</h3>
                        <p class="metric-value"><?php echo htmlspecialchars($total_stock_quantity); ?></p>
                        <p class="metric-description">Sum of all item quantities</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Existing Sections for Other Reports -->
        <div class="grid grid--2-cols gap-4 mb-4">
            <div class="card">
                <div class="card__header">
                    <h2 class="card__title">Current Stock Levels Overview</h2>
                    <p class="text-muted">All items and their current quantities.</p>
                </div>
                <div class="card__body">
                    <?php
                    $stock_overview_data = [];
                    $sql_stock_overview = "SELECT name, quantity, barcode FROM items ORDER BY name ASC";
                    if ($result_stock_overview = mysqli_query($conn, $sql_stock_overview)) {
                        while ($row = mysqli_fetch_assoc($result_stock_overview)) {
                            $stock_overview_data[] = $row;
                        }
                        mysqli_free_result($result_stock_overview);
                    } else {
                        $message .= "<p class='error'>Error fetching stock overview data: " . mysqli_error($conn) . "</p>";
                    }
                    ?>
                    <?php if (!empty($stock_overview_data)): ?>
                        <div class="table">
                            <table class="w-100">
                                <thead>
                                    <tr class="table__header">
                                        <th class="table__cell">Item Name</th>
                                        <th class="table__cell">Barcode</th>
                                        <th class="table__cell">Current Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stock_overview_data as $record): ?>
                                        <tr class="table__row">
                                            <td class="table__cell"><?php echo htmlspecialchars($record['name']); ?></td>
                                            <td class="table__cell"><?php echo htmlspecialchars($record['barcode']); ?></td>
                                            <td class="table__cell"><?php echo htmlspecialchars($record['quantity']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-end mt-3">
                            <a href="export.php?type=stock_overview_csv" class="btn btn--secondary">Export CSV</a>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">No items found in inventory.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card__header">
                    <h2 class="card__title">Low Stock Items</h2>
                    <p class="text-muted">Items nearing their reorder level.</p>
                </div>
                <div class="card__body">
                    <?php
                    $low_stock_data = [];
                    $sql_low_stock = "SELECT name, quantity, low_stock_threshold, barcode FROM items WHERE quantity <= low_stock_threshold ORDER BY name ASC";
                    if ($result_low_stock = mysqli_query($conn, $sql_low_stock)) {
                        while ($row = mysqli_fetch_assoc($result_low_stock)) {
                            $low_stock_data[] = $row;
                        }
                        mysqli_free_result($result_low_stock);
                    } else {
                        $message .= "<p class='error'>Error fetching low stock data: " . mysqli_error($conn) . "</p>";
                    }
                    ?>
                    <?php if (!empty($low_stock_data)): ?>
                        <div class="table">
                            <table class="w-100">
                                <thead>
                                    <tr class="table__header">
                                        <th class="table__cell">Item Name</th>
                                        <th class="table__cell">Barcode</th>
                                        <th class="table__cell">Current Stock</th>
                                        <th class="table__cell">Low Stock Threshold</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($low_stock_data as $record): ?>
                                        <tr class="table__row">
                                            <td class="table__cell"><?php echo htmlspecialchars($record['name']); ?></td>
                                            <td class="table__cell"><?php echo htmlspecialchars($record['barcode']); ?></td>
                                            <td class="table__cell"><?php echo htmlspecialchars($record['quantity']); ?></td>
                                            <td class="table__cell"><?php echo htmlspecialchars($record['low_stock_threshold']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-end mt-3">
                            <a href="export.php?type=low_stock_csv" class="btn btn--secondary">Export CSV</a>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">No items currently in low stock.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card__header">
                <h2 class="card__title">Category-wise Stock Summary</h2>
                <p class="text-muted">Total stock quantity per category.</p>
            </div>
            <div class="card__body">
                <?php
                $category_summary_data = [];
                $sql_category_summary = "SELECT 
                                            c.name as category_name,
                                            SUM(i.quantity) as total_quantity
                                         FROM categories c
                                         JOIN items i ON c.id = i.category_id
                                         GROUP BY c.name
                                         ORDER BY c.name ASC";
                if ($result_category_summary = mysqli_query($conn, $sql_category_summary)) {
                    while ($row = mysqli_fetch_assoc($result_category_summary)) {
                        $category_summary_data[] = $row;
                    }
                    mysqli_free_result($result_category_summary);
                } else {
                    $message .= "<p class='error'>Error fetching category-wise stock summary: " . mysqli_error($link) . "</p>";
                }
                ?>
                <?php if (!empty($category_summary_data)): ?>
                    <div class="table">
                        <table class="w-100">
                            <thead>
                                <tr class="table__header">
                                    <th class="table__cell">Category Name</th>
                                    <th class="table__cell">Total Stock</th>
                                </tr>
                            </thead>
                                <tbody>
                                    <?php foreach ($category_summary_data as $record): ?>
                                        <tr class="table__row">
                                            <td class="table__cell"><?php echo htmlspecialchars($record['category_name']); ?></td>
                                            <td class="table__cell"><?php echo htmlspecialchars($record['total_quantity']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-end mt-3">
                            <a href="export.php?type=category_summary_csv" class="btn btn--secondary">Export CSV</a>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">No categories found or no stock in categories.</p>
                    <?php endif; ?>
                </div>
            </div>

        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Export All Data</h2>
                <p class="text-muted">Download comprehensive reports.</p>
            </div>
            <div class="card__body">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="export.php?type=items_csv" class="btn btn--secondary">Export All Items (CSV)</a>
                    <a href="export.php?type=categories_csv" class="btn btn--secondary">Export All Categories (CSV)</a>
                    <a href="export.php?type=all_logs_csv" class="btn btn--secondary">Export All Inventory Logs (CSV)</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="js/reports.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Flatpickr for the daily report date input
    flatpickr("#report_date", {
        dateFormat: "Y-m-d",
        defaultDate: "<?php echo htmlspecialchars($report_date); ?>",
        inline: true // Display the calendar inline
    });

    // Initialize Flatpickr for the new filter date inputs
    flatpickr(".date-picker", {
        dateFormat: "Y-m-d",
        allowInput: true
    });

    // The Chart.js initialization will be moved to js/reports.js
});
</script>
