<?php
$message = '';
$report_data_daily_in_out = [];
$report_date = date('Y-m-d'); // Default to today

if (isset($_GET['report_date']) && !empty($_GET['report_date'])) {
    // Basic validation for date format, can be more robust
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

if ($stmt_daily = mysqli_prepare($link, $sql_daily_report)) {
    mysqli_stmt_bind_param($stmt_daily, "s", $report_date);
    if (mysqli_stmt_execute($stmt_daily)) {
        $result_daily = mysqli_stmt_get_result($stmt_daily);
        while ($row = mysqli_fetch_assoc($result_daily)) {
            $report_data_daily_in_out[] = $row;
        }
        mysqli_free_result($result_daily);
    } else {
        $message .= "<p class='error'>Error executing daily report query: " . mysqli_error($link) . "</p>";
    }
    mysqli_stmt_close($stmt_daily);
} else {
    $message .= "<p class='error'>Error preparing daily report query: " . mysqli_error($link) . "</p>";
}

// Determine trend period
$trend_period = isset($_GET['trend_period']) ? $_GET['trend_period'] : 'monthly'; // Default to monthly

$date_format_sql = '%Y-%m'; // Default for monthly
$group_by_sql = 'month_year';
$interval_sql = 'INTERVAL 12 MONTH';
$chart_x_axis_label = 'Month';

switch ($trend_period) {
    case 'daily':
        $date_format_sql = '%Y-%m-%d';
        $group_by_sql = 'movement_date';
        $interval_sql = 'INTERVAL 30 DAY'; // Last 30 days for daily
        $chart_x_axis_label = 'Day';
        break;
    case 'weekly':
        $date_format_sql = '%Y-%u'; // Year-Week number
        $group_by_sql = 'week_year';
        $interval_sql = 'INTERVAL 12 WEEK'; // Last 12 weeks for weekly
        $chart_x_axis_label = 'Week';
        break;
    case 'yearly':
        $date_format_sql = '%Y';
        $group_by_sql = 'year';
        $interval_sql = 'INTERVAL 5 YEAR'; // Last 5 years for yearly
        $chart_x_axis_label = 'Year';
        break;
    case 'monthly':
    default:
        $date_format_sql = '%Y-%m';
        $group_by_sql = 'month_year';
        $interval_sql = 'INTERVAL 12 MONTH';
        $chart_x_axis_label = 'Month';
        break;
}

// Fetch Usage Data based on selected period
$usage_data = [];
$sql_usage = "SELECT 
                DATE_FORMAT(il.log_date, '$date_format_sql') as $group_by_sql,
                i.name as item_name,
                SUM(CASE WHEN il.type = 'in' THEN il.quantity_change ELSE -il.quantity_change END) as total_net_change
             FROM inventory_log il
             JOIN items i ON il.item_id = i.id
             WHERE il.log_date >= DATE_SUB(CURDATE(), $interval_sql)
             GROUP BY $group_by_sql, i.id, i.name
             ORDER BY $group_by_sql ASC, item_name ASC";

if ($result_usage = mysqli_query($link, $sql_usage)) {
    while ($row = mysqli_fetch_assoc($result_usage)) {
        $usage_data[] = $row;
    }
    mysqli_free_result($result_usage);
} else {
    $message .= "<p class='error'>Error fetching usage data: " . mysqli_error($link) . "</p>";
}

// Prepare data for Chart.js
$chart_labels = [];
$chart_datasets = [];

if (!empty($usage_data)) {
    $item_data_by_period = [];
    foreach ($usage_data as $record) {
        $period_label = $record[$group_by_sql];
        if (!in_array($period_label, $chart_labels)) {
            $chart_labels[] = $period_label;
        }
        $item_data_by_period[$record['item_name']][$period_label] = (int)$record['total_net_change'];
    }
    sort($chart_labels); // Ensure periods are sorted

    $colors = ['rgba(255, 99, 132, 0.8)', 'rgba(54, 162, 235, 0.8)', 'rgba(255, 206, 86, 0.8)', 'rgba(75, 192, 192, 0.8)', 'rgba(153, 102, 255, 0.8)', 'rgba(255, 159, 64, 0.8)'];
    $color_index = 0;

    foreach ($item_data_by_period as $item_name => $periods) {
        $data_points = [];
        foreach ($chart_labels as $label_period) {
            $data_points[] = isset($periods[$label_period]) ? $periods[$label_period] : 0;
        }
        $chart_datasets[] = [
            'label' => $item_name,
            'data' => $data_points,
            'borderColor' => $colors[$color_index % count($colors)],
            'backgroundColor' => $colors[$color_index % count($colors)],
            'fill' => false,
            'tension' => 0.1
        ];
        $color_index++;
    }
}

?>

<link rel="stylesheet" href="css/main.css">

<div class="container">
    <div class="page">
        <header class="d-flex justify-between align-center mb-4">
            <div>
                <h2 class="card__title">Inventory Reports</h2>
                <p class="text-muted">View and analyze inventory data and trends.</p>
            </div>
        </header>

        <?php if (!empty($message)): ?>
            <div class="alert alert--error mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid--2-cols gap-4 mb-4">
            <div class="card">
                <div class="card__header">
                    <h2 class="card__title">Daily Items In/Out Report</h2>
                </div>
                <div class="card__body">
                    <form action="index.php" method="get" class="form">
                        <input type="hidden" name="page" value="reports">
                        <div class="form__group">
                            <label class="form__label">Select Date</label>
                            <div class="d-flex gap-2">
                                <input type="date" id="report_date" name="report_date" class="form__input" value="<?php echo htmlspecialchars($report_date); ?>">
                                <button type="submit" class="btn btn--primary">View Report</button>
                            </div>
                        </div>
                    </form>

                    <?php if (!empty($report_data_daily_in_out)): ?>
                        <div class="d-flex justify-between align-center mt-4 mb-2">
                            <h3 class="card__title">Report for: <?php echo htmlspecialchars($report_date); ?></h3>
                            <a href="export.php?type=daily_in_out_csv&date=<?php echo htmlspecialchars($report_date); ?>" class="btn btn--secondary">Export CSV</a>
                        </div>
                        <div class="table">
                            <table class="w-100">
                                <thead>
                                    <tr class="table__header">
                                        <th class="table__cell">Item Name</th>
                                        <th class="table__cell">Total Stock In</th>
                                        <th class="table__cell">Total Stock Out</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data_daily_in_out as $record): ?>
                                        <tr class="table__row">
                                            <td class="table__cell"><?php echo htmlspecialchars($record['item_name']); ?></td>
                                            <td class="table__cell"><?php echo htmlspecialchars($record['total_in']); ?></td>
                                            <td class="table__cell"><?php echo htmlspecialchars($record['total_out']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif (isset($_GET['report_date'])): ?>
                        <p class="text-center text-muted mt-4">No stock movements found for <?php echo htmlspecialchars($report_date); ?>.</p>
                    <?php else: ?>
                        <p class="text-center text-muted mt-4">Select a date to view the report.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card__header d-flex justify-between align-center">
                    <h2 class="card__title">Usage Trends</h2>
                    <form action="index.php" method="get" class="form d-flex gap-2 align-center">
                        <input type="hidden" name="page" value="reports">
                        <label for="trend_period" class="form__label mb-0">Period:</label>
                        <select id="trend_period" name="trend_period" class="form__input" onchange="this.form.submit()">
                            <option value="daily" <?php echo ($trend_period == 'daily') ? 'selected' : ''; ?>>Daily (Last 30 Days)</option>
                            <option value="weekly" <?php echo ($trend_period == 'weekly') ? 'selected' : ''; ?>>Weekly (Last 12 Weeks)</option>
                            <option value="monthly" <?php echo ($trend_period == 'monthly') ? 'selected' : ''; ?>>Monthly (Last 12 Months)</option>
                            <option value="yearly" <?php echo ($trend_period == 'yearly') ? 'selected' : ''; ?>>Yearly (Last 5 Years)</option>
                        </select>
                    </form>
                </div>
                <div class="card__body">
                    <?php if (!empty($chart_datasets)): ?>
                        <canvas id="monthlyUsageChart" width="400" height="200"></canvas>
                    <?php else: ?>
                        <p class="text-center text-muted">No 'stock out' data available for the selected period to display usage trends.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- New Sections for Reports -->
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
                    if ($result_stock_overview = mysqli_query($link, $sql_stock_overview)) {
                        while ($row = mysqli_fetch_assoc($result_stock_overview)) {
                            $stock_overview_data[] = $row;
                        }
                        mysqli_free_result($result_stock_overview);
                    } else {
                        $message .= "<p class='error'>Error fetching stock overview data: " . mysqli_error($link) . "</p>";
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
                    if ($result_low_stock = mysqli_query($link, $sql_low_stock)) {
                        while ($row = mysqli_fetch_assoc($result_low_stock)) {
                            $low_stock_data[] = $row;
                        }
                        mysqli_free_result($result_low_stock);
                    } else {
                        $message .= "<p class='error'>Error fetching low stock data: " . mysqli_error($link) . "</p>";
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
                if ($result_category_summary = mysqli_query($link, $sql_category_summary)) {
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthlyUsageCtx = document.getElementById('monthlyUsageChart');
    if (monthlyUsageCtx && <?php echo !empty($chart_datasets) ? 'true' : 'false'; ?>) {
        const monthlyUsageChart = new Chart(monthlyUsageCtx, {
            type: 'line', // or 'bar'
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: <?php echo json_encode($chart_datasets); ?>
            },
            options: {
                responsive: true,
                maintainAspectRatio: true, // Adjust as needed, false can make it fill container better
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Quantity Out'
                        }
                    },
                    x: {
                         title: {
                            display: true,
                            text: '<?php echo $chart_x_axis_label; ?>'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Item Usage (Stock Out)'
                    }
                }
            }
        });
    }
});
</script>
