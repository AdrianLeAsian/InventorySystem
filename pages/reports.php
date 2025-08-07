<?php
require_once 'config/db.php';

$message = '';

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

// Fetch Usage Data based on selected period (focus on 'out' movements for usage)
$usage_data = [];
$sql_usage = "SELECT 
                DATE_FORMAT(il.log_date, '$date_format_sql') as $group_by_sql,
                i.name as item_name,
                SUM(CASE WHEN il.type = 'out' THEN il.quantity_change ELSE 0 END) as total_out_change
             FROM inventory_log il
             JOIN items i ON il.item_id = i.id
             WHERE il.log_date >= DATE_SUB(CURDATE(), $interval_sql) AND il.type = 'out'
             GROUP BY $group_by_sql, i.id, i.name
             ORDER BY $group_by_sql ASC, item_name ASC";

if ($result_usage = mysqli_query($conn, $sql_usage)) {
    while ($row = mysqli_fetch_assoc($result_usage)) {
        $usage_data[] = $row;
    }
    mysqli_free_result($result_usage);
} else {
    $message .= "<p class='error'>Error fetching usage data: " . mysqli_error($conn) . "</p>";
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
        $item_data_by_period[$record['item_name']][$period_label] = (int)$record['total_out_change'];
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<div class="container">
    <div class="page">
        <header class="d-flex justify-between align-center mb-4">
            <h1 class="header__title">Usage Trends Report</h1>
        </header>

        <?php if (!empty($message)): ?>
            <div class="alert alert--error mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Usage Trends Chart with Period Selection Dropdown -->
        <div class="card mb-4">
            <div class="card__header d-flex justify-between align-center">
                <h2 class="card__title">Item Usage Trends</h2>
                <form action="index.php" method="get" class="form d-flex align-center gap-2 m-0">
                    <input type="hidden" name="page" value="reports">
                    <label for="trend_period_select" class="form__label m-0">Period:</label>
                    <select id="trend_period_select" name="trend_period" class="form__input" onchange="this.form.submit()">
                        <option value="daily" <?php echo ($trend_period == 'daily') ? 'selected' : ''; ?>>Daily (Last 30 Days)</option>
                        <option value="weekly" <?php echo ($trend_period == 'weekly') ? 'selected' : ''; ?>>Weekly (Last 12 Weeks)</option>
                        <option value="monthly" <?php echo ($trend_period == 'monthly') ? 'selected' : ''; ?>>Monthly (Last 12 Months)</option>
                        <option value="yearly" <?php echo ($trend_period == 'yearly') ? 'selected' : ''; ?>>Yearly (Last 5 Years)</option>
                    </select>
                </form>
            </div>
            <div class="card__body">
                <?php if (!empty($chart_datasets)): ?>
                    <canvas id="usageTrendChart" width="400" height="200"></canvas>
                <?php else: ?>
                    <p class="text-center text-muted">No 'stock out' data available for the selected period to display usage trends.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Import and Export Data Section -->
        <div class="card mb-4">
            <div class="card__header">
                <h2 class="card__title">Data Management</h2>
                <p class="text-muted">Import new items or export existing data.</p>
            </div>
            <div class="card__body">
                <div class="d-flex gap-2 flex-wrap mb-3">
                    <button type="button" class="btn btn--primary" id="importItemsBtn">Import Items</button>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="ajax/export_all_reports.php" class="btn btn--secondary">Export All Reports (CSV)</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/import_items_modal.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="js/reports.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const usageTrendCtx = document.getElementById('usageTrendChart');
    if (usageTrendCtx && <?php echo !empty($chart_datasets) ? 'true' : 'false'; ?>) {
        const usageTrendChart = new Chart(usageTrendCtx, {
            type: 'line', // or 'bar'
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: <?php echo json_encode($chart_datasets); ?>
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
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

    // Import Items Modal Logic
    const importItemsBtn = document.getElementById('importItemsBtn');
    const importItemsModal = document.getElementById('importItemsModal');
    const closeImportModalBtn = document.getElementById('closeImportModalBtn');
    const importCsvForm = document.getElementById('importCsvForm');
    const importFile = document.getElementById('importFile');
    const importFileName = document.getElementById('importFileName');
    const importProgressBarContainer = document.getElementById('importProgressBarContainer');
    const importProgressBar = document.getElementById('importProgressBar');
    const importProgressText = document.getElementById('importProgressText');
    const importSummary = document.getElementById('importSummary');
    const importSuccessCount = document.getElementById('importSuccessCount');
    const importSkippedCount = document.getElementById('importSkippedCount');
    const importError = document.getElementById('importError');
    const skippedLogLink = document.getElementById('skippedLogLink');

    if (importItemsBtn) {
        importItemsBtn.addEventListener('click', () => {
            importItemsModal.classList.add('modal--active');
            resetImportModal();
        });
    }

    if (closeImportModalBtn) {
        closeImportModalBtn.addEventListener('click', () => {
            importItemsModal.classList.remove('modal--active');
        });
    }

    if (importFile) {
        importFile.addEventListener('change', (event) => {
            if (event.target.files.length > 0) {
                importFileName.textContent = event.target.files[0].name;
            } else {
                importFileName.textContent = 'No file chosen';
            }
        });
    }

    if (importCsvForm) {
        importCsvForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const file = importFile.files[0];
            if (!file) {
                alert('Please select a CSV file to import.');
                return;
            }

            const formData = new FormData();
            formData.append('csv_file', file);

            // Show progress bar and reset summary/error
            importProgressBarContainer.style.display = 'block';
            importProgressBar.style.width = '0%';
            importProgressText.textContent = '0%';
            importSummary.style.display = 'none';
            importError.style.display = 'none';
            skippedLogLink.style.display = 'none';

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'ajax/import_items.php', true);

            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = (e.loaded / e.total) * 100;
                    importProgressBar.style.width = percent.toFixed(0) + '%';
                    importProgressText.textContent = percent.toFixed(0) + '%';
                }
            });

            xhr.onload = function() {
                importProgressBarContainer.style.display = 'none';
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        importSummary.style.display = 'block';
                        importSuccessCount.textContent = response.imported_count;
                        importSkippedCount.textContent = response.skipped_count;
                        if (response.skipped_log_file) {
                            skippedLogLink.href = response.skipped_log_file;
                            skippedLogLink.style.display = 'block';
                        }
                        // Optionally, refresh the page or relevant sections if needed
                        // location.reload(); 
                    } else {
                        importError.style.display = 'block';
                        importError.textContent = response.message || 'An unknown error occurred during import.';
                    }
                } else {
                    importError.style.display = 'block';
                    importError.textContent = 'Server error: ' + xhr.status;
                }
            };

            xhr.onerror = function() {
                importProgressBarContainer.style.display = 'none';
                importError.style.display = 'block';
                importError.textContent = 'Network error during import.';
            };

            xhr.send(formData);
        });
    }

    function resetImportModal() {
        importCsvForm.reset();
        importFileName.textContent = 'No file chosen';
        importProgressBarContainer.style.display = 'none';
        importProgressBar.style.width = '0%';
        importProgressText.textContent = '0%';
        importSummary.style.display = 'none';
        importError.style.display = 'none';
        skippedLogLink.style.display = 'none';
    }
});
</script>
