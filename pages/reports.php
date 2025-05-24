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

// Fetch Monthly Usage Data (last 12 months for 'out' transactions)
$monthly_usage_data = [];
$sql_monthly_usage = "SELECT 
                        DATE_FORMAT(il.log_date, '%Y-%m') as month_year,
                        i.name as item_name,
                        SUM(il.quantity_change) as total_out_quantity
                     FROM inventory_log il
                     JOIN items i ON il.item_id = i.id
                     WHERE il.type = 'out' AND il.log_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                     GROUP BY month_year, i.id, i.name
                     ORDER BY month_year ASC, item_name ASC";

if ($result_monthly = mysqli_query($link, $sql_monthly_usage)) {
    while ($row = mysqli_fetch_assoc($result_monthly)) {
        $monthly_usage_data[] = $row;
    }
    mysqli_free_result($result_monthly);
} else {
    $message .= "<p class='error'>Error fetching monthly usage data: " . mysqli_error($link) . "</p>";
}
// Prepare data for Chart.js
$chart_labels = [];
$chart_datasets = [];

if (!empty($monthly_usage_data)) {
    $item_monthly_data = [];
    foreach ($monthly_usage_data as $record) {
        if (!in_array($record['month_year'], $chart_labels)) {
            $chart_labels[] = $record['month_year'];
        }
        $item_monthly_data[$record['item_name']][$record['month_year']] = (int)$record['total_out_quantity'];
    }
    sort($chart_labels); // Ensure months are sorted

    $colors = ['rgba(255, 99, 132, 0.8)', 'rgba(54, 162, 235, 0.8)', 'rgba(255, 206, 86, 0.8)', 'rgba(75, 192, 192, 0.8)', 'rgba(153, 102, 255, 0.8)', 'rgba(255, 159, 64, 0.8)'];
    $color_index = 0;

    foreach ($item_monthly_data as $item_name => $months) {
        $data_points = [];
        foreach ($chart_labels as $label_month) {
            $data_points[] = isset($months[$label_month]) ? $months[$label_month] : 0;
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

        <div class="grid grid--2-cols gap-4">
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
                <div class="card__header">
                    <h2 class="card__title">Monthly Usage Trends</h2>
                    <p class="text-muted">Last 12 Months - Stock Out</p>
                </div>
                <div class="card__body">
                    <?php if (!empty($chart_datasets)): ?>
                        <canvas id="monthlyUsageChart" width="400" height="200"></canvas>
                    <?php else: ?>
                        <p class="text-center text-muted">No 'stock out' data available for the last 12 months to display usage trends.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card__header">
                <h2 class="card__title">Export Data</h2>
            </div>
            <div class="card__body">
                <div class="d-flex gap-2">
                    <a href="export.php?type=items_csv" class="btn btn--secondary">Export All Items (CSV)</a>
                    <a href="export.php?type=categories_csv" class="btn btn--secondary">Export All Categories (CSV)</a>
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
                            text: 'Month'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Monthly Item Usage (Stock Out)'
                    }
                }
            }
        });
    }
});
</script> 