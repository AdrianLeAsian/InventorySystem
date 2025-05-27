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

// Fetch Current Stock Levels
$current_stock_data = [];
$sql_current_stock = "SELECT id, name, quantity, barcode FROM items ORDER BY name ASC";
if ($result_current_stock = mysqli_query($link, $sql_current_stock)) {
    while ($row = mysqli_fetch_assoc($result_current_stock)) {
        $current_stock_data[] = $row;
    }
    mysqli_free_result($result_current_stock);
} else {
    $message .= "<p class='error'>Error fetching current stock data: " . mysqli_error($link) . "</p>";
}

// Prepare data for Chart.js (Usage Trends - based on current stock distribution)
$chart_labels = [];
$chart_data_quantities = [];
$chart_background_colors = [];

if (!empty($current_stock_data)) {
    $colors = [
        'rgba(255, 99, 132, 0.8)', 'rgba(54, 162, 235, 0.8)', 'rgba(255, 206, 86, 0.8)',
        'rgba(75, 192, 192, 0.8)', 'rgba(153, 102, 255, 0.8)', 'rgba(255, 159, 64, 0.8)',
        'rgba(199, 199, 199, 0.8)', 'rgba(83, 102, 255, 0.8)', 'rgba(40, 159, 64, 0.8)',
        'rgba(210, 99, 132, 0.8)', 'rgba(100, 162, 235, 0.8)', 'rgba(200, 206, 86, 0.8)'
    ];
    $color_index = 0;

    foreach ($current_stock_data as $item) {
        if ($item['quantity'] > 0) { // Only include items with quantity > 0 in the pie chart
            $chart_labels[] = htmlspecialchars($item['name']);
            $chart_data_quantities[] = (int)$item['quantity'];
            $chart_background_colors[] = $colors[$color_index % count($colors)];
            $color_index++;
        }
    }
    
    // If no items have quantity > 0, ensure datasets is empty
    if (empty($chart_labels)) {
        $chart_datasets = [];
    } else {
        $chart_datasets = [
            [
                'label' => 'Current Stock Quantity',
                'data' => $chart_data_quantities,
                'backgroundColor' => $chart_background_colors,
                'hoverOffset' => 4
            ]
        ];
    }
}

?>

<link rel="stylesheet" href="css/main.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* General adjustments for Flatpickr to fit better */
    #calendar-container {
        display: flex;
        justify-content: center;
        padding: 10px; /* Add some padding around the calendar */
    }

    #calendar-container .flatpickr-calendar {
        width: 100%;
        max-width: 350px; /* Further reduced max-width for a very compact calendar */
        box-shadow: none;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        box-sizing: border-box;
        margin: 0 auto; /* Center the calendar horizontally */
    }

    /* Adjust day grid */
    .flatpickr-day {
        height: 25px; /* Further reduced height for smaller day cells */
        line-height: 25px; /* Center text vertically */
        margin: 1px; /* Further reduced margin between days */
        border-radius: 4px;
    }

    .flatpickr-current-month {
        flex-grow: 1;
        text-align: center;
    }

    .flatpickr-monthDropdown-months {
        background: none; /* Remove background to blend with header */
        border: none; /* Remove border */
        font-size: inherit; /* Inherit font size */
        font-weight: bold;
        padding: 0 5px;
    }

    .numInputWrapper {
        display: inline-block; /* Keep year input inline */
    }

    /* Adjust day grid */
    .flatpickr-weekdays {
        background: #f8f8f8; /* Light background for weekdays header */
        border-bottom: 1px solid #eee;
    }

    .flatpickr-weekday {
        font-weight: bold;
        color: #555;
    }

    .flatpickr-days {
        padding: 5px 0;
    }

    .flatpickr-day {
        height: 36px; /* Increased height for bigger day cells */
        line-height: 36px; /* Center text vertically */
        margin: 3px; /* Increased margin between days */
        border-radius: 4px;
    }

    .flatpickr-day.selected,
    .flatpickr-day.selected:hover {
        background: #007bff; /* Primary color for selected date */
        color: white;
        border-color: #007bff;
    }

    .flatpickr-day.today {
        border: 1px solid #007bff; /* Highlight today's date */
        color: #007bff;
    }

    .flatpickr-day.today:hover {
        background: #007bff;
        color: white;
    }

    /* Navigation arrows */
    .flatpickr-prev-month,
    .flatpickr-next-month {
        color: #007bff;
        border: none;
        background: none;
        cursor: pointer;
        font-size: 1.5em;
        line-height: 1;
        padding: 0 5px;
    }
    .flatpickr-prev-month:hover,
    .flatpickr-next-month:hover {
        opacity: 0.7;
    }

    .form__group {
        width: 100%;
        margin: 0 auto;
        text-align: center;
    }

    .card__body {
        text-align: center; /* Attempt to center content within the card body */
        display: flex;
        flex-direction: column;
        align-items: center;
    }
</style>

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

        <div class="grid grid--2-cols gap-4" style="align-items: stretch;">
            <div class="card">
                <div class="card__header">
                    <h2 class="card__title">Daily Items In/Out Report</h2>
                </div>
                <div class="card__body">
                    <form id="dailyReportForm" action="index.php" method="get" class="form" style="display: flex; flex-direction: column; align-items: center;">
                        <input type="hidden" name="page" value="reports">
                        <div class="form__group">
                            <label class="form__label">Select Date</label>
                            <input type="hidden" id="report_date_hidden" name="report_date" value="<?php echo htmlspecialchars($report_date); ?>">
                            <div id="calendar-container"></div>
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
                    <h2 class="card__title">Usage Trends</h2>
                </div>
                <div class="card__body" style="display: flex; flex-direction: column; align-items: center;">
                    <?php if (!empty($chart_datasets) && !empty($chart_labels)): ?>
                        <canvas id="usageTrendsChart" width="400" height="200"></canvas>
                    <?php else: ?>
                        <p class="text-center text-muted">No stock data available to display usage trends.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card__header">
                <h2 class="card__title">Current Stock Levels</h2>
            </div>
            <div class="card__body">
                <?php if (!empty($current_stock_data)): ?>

                    <div class="table">
                        <table class="w-100">
                            <thead>
                                <tr class="table__header">
                                    <th class="table__cell">Item Name</th>
                                    <th class="table__cell">Barcode</th>
                                    <th class="table__cell">Current Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($current_stock_data as $item): ?>
                                    <tr class="table__row">
                                        <td class="table__cell"><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td class="table__cell"><?php echo htmlspecialchars($item['barcode']); ?></td>
                                        <td class="table__cell"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No items found in inventory.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card__header">
                <h2 class="card__title">Export Data</h2>
            </div>
            <div class="card__body">
                <div class="d-flex gap-2">
                    <a href="export.php?type=all_data" class="btn btn-primary">Export All Inventory Data (CSV)</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const usageTrendsCtx = document.getElementById('usageTrendsChart');
    if (usageTrendsCtx && <?php echo !empty($chart_datasets) ? 'true' : 'false'; ?>) {
        const usageTrendsChart = new Chart(usageTrendsCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: <?php echo json_encode($chart_datasets); ?>
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Current Stock Distribution'
                    }
                }
            }
        });
    }

    flatpickr("#calendar-container", {
        inline: true,
        defaultDate: "<?php echo htmlspecialchars($report_date); ?>",
        onChange: function(selectedDates, dateStr, instance) {
            document.getElementById('report_date_hidden').value = dateStr;
            document.getElementById('dailyReportForm').submit();
        }
    });
});
</script>
