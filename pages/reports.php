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

<h2>Inventory Reports</h2>

<?php echo $message; ?>

<div class="report-section form-container">
    <h3>Daily Items In/Out Report</h3>
    <form action="index.php" method="get">
        <input type="hidden" name="page" value="reports">
        <div>
            <label for="report_date">Select Date:</label>
            <input type="date" id="report_date" name="report_date" value="<?php echo htmlspecialchars($report_date); ?>">
            <button type="submit">View Report</button>
        </div>
    </form>

    <?php if (!empty($report_data_daily_in_out)): ?>
    <h4>Report for: <?php echo htmlspecialchars($report_date); ?> 
        <a href="export.php?type=daily_in_out_csv&date=<?php echo htmlspecialchars($report_date); ?>" class="button-like-link export-link" style="font-size: 0.8em; padding: 5px 8px; margin-left:10px;">Export CSV</a>
    </h4>
    <table class="table-container">
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Total Stock In</th>
                <th>Total Stock Out</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($report_data_daily_in_out as $record): ?>
            <tr>
                <td><?php echo htmlspecialchars($record['item_name']); ?></td>
                <td><?php echo htmlspecialchars($record['total_in']); ?></td>
                <td><?php echo htmlspecialchars($record['total_out']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php elseif (isset($_GET['report_date'])): // Only show if a date was specifically queried (not initial load without data) ?>
    <p>No stock movements found for <?php echo htmlspecialchars($report_date); ?>.</p>
    <?php else: ?>
    <p>Select a date to view the report.</p>
    <?php endif; ?>
</div>

<div class="report-section">
    <h3>Monthly Usage Trends (Last 12 Months - Stock Out)</h3>
    <?php if (!empty($chart_datasets)): ?>
        <canvas id="monthlyUsageChart" width="400" height="200"></canvas>
    <?php else: ?>
        <p>No 'stock out' data available for the last 12 months to display usage trends.</p>
    <?php endif; ?>
</div>

<div class="report-section">
    <h3>Export Data</h3>
    <p><em>Export to Excel (CSV) / PDF options will be added here.</em></p>
    <p><a href="export.php?type=items_csv" class="button-like-link">Export All Items (CSV)</a></p>
    <p><a href="export.php?type=categories_csv" class="button-like-link">Export All Categories (CSV)</a></p>
     <!-- Add more export options as needed -->
</div>


<style>
/* Styles specific to reports, can be moved */
.report-section { background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
.report-section h3, .report-section h4 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
.report-section form div { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
.report-section input[type="date"] { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
.report-section button, .button-like-link  { 
    background-color: #007bff; color: white; padding: 8px 12px; 
    border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block;
}
.report-section button:hover, .button-like-link:hover { background-color: #0056b3; }

table.table-container { width: 100%; border-collapse: collapse; margin-top:15px; }
table.table-container th, table.table-container td { border: 1px solid #ddd; padding: 8px; text-align: left; }
table.table-container tr:nth-child(even) { background-color: #f2f2f2; }
table.table-container th { background-color: #333; color: white; }

.success { color: green; border: 1px solid green; padding: 10px; margin-bottom: 15px; background-color: #e6ffe6; }
.error { color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px; background-color: #ffe6e6; }
</style>

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