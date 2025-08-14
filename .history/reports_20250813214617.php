<?php
include 'includes/auth.php'; // session_start() is already called in auth.php
$page_title = 'Reports';
include 'includes/db.php';

// Get user role from session
$user_role = $_SESSION['user_role'] ?? 'guest'; // Default to 'guest' if not set

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="js/reports.js" defer></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>
    <div class="main-content">
        <h2>Reports</h2>

        <div class="tabs-container">
            <div class="tabs">
                <button class="tab-button active" data-tab="stock-summary">Stock Summary</button>
                <button class="tab-button" data-tab="detailed-inventory">Detailed Inventory</button>
                <?php if ($user_role === 'admin'): ?>
                    <button class="tab-button" data-tab="transaction-logs">Transaction Logs</button>
                <?php endif; ?>
                <?php if ($user_role === 'admin'): ?>
                    <button class="tab-button" data-tab="expiry-calendar">Expiry Calendar</button>
                <?php endif; ?>
            </div>

            <div class="tab-content active" id="stock-summary">
                <h3>Stock Summary</h3>
                <div class="export-buttons">
                    <button class="btn-primary export-pdf" data-report="stock-summary">Export PDF</button>
                    <button class="btn-primary export-csv" data-report="stock-summary">Export CSV</button>
                </div>
                <div class="report-data" id="stock-summary-data">
                    <!-- Stock Summary data will be loaded here via AJAX -->
                    <p>Loading Stock Summary...</p>
                </div>
            </div>

            <div class="tab-content" id="detailed-inventory">
                <h3>Detailed Inventory</h3>
                <div class="filters-container">
                    <input type="text" id="detailedInventorySearch" placeholder="Search by item name...">
                    <select id="detailedInventoryCategoryFilter">
                        <option value="">All Categories</option>
                        <?php
                        $categories_stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name ASC");
                        $categories_stmt->execute();
                        $categories_result = $categories_stmt->get_result();
                        while ($cat = $categories_result->fetch_assoc()) {
                            echo "<option value=\"{$cat['id']}\">{$cat['name']}</option>";
                        }
                        $categories_stmt->close();
                        ?>
                    </select>
                    <select id="detailedInventoryLocationFilter">
                        <option value="">All Locations</option>
                        <?php
                        $locations_stmt = $conn->prepare("SELECT id, name FROM locations ORDER BY name ASC");
                        $locations_stmt->execute();
                        $locations_result = $locations_stmt->get_result();
                        while ($loc = $locations_result->fetch_assoc()) {
                            echo "<option value=\"{$loc['id']}\">{$loc['name']}</option>";
                        }
                        $locations_stmt->close();
                        ?>
                    </select>
                    <select id="detailedInventoryStockStatusFilter">
                        <option value="">All Stock Status</option>
                        <option value="green">Healthy</option>
                        <option value="orange">Low Stock</option>
                        <option value="red">Out of Stock</option>
                    </select>
                    <select id="detailedInventoryPerishableFilter">
                        <option value="">All Perishable</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                    <label for="detailedInventoryExpiryDateStart">Expiry From:</label>
                    <input type="date" id="detailedInventoryExpiryDateStart">
                    <label for="detailedInventoryExpiryDateEnd">Expiry To:</label>
                    <input type="date" id="detailedInventoryExpiryDateEnd">
                    <button id="applyDetailedInventoryFilters" class="btn-primary">Apply Filters</button>
                </div>
                <div class="export-buttons">
                    <button class="btn-primary export-pdf" data-report="detailed-inventory">Export PDF</button>
                    <button class="btn-primary export-csv" data-report="detailed-inventory">Export CSV</button>
                </div>
                <div class="report-data" id="detailed-inventory-data">
                    <!-- Detailed Inventory data will be loaded here via AJAX -->
                    <p>Loading Detailed Inventory...</p>
                </div>
            </div>

            <?php if ($user_role === 'admin'): ?>
                <div class="tab-content" id="transaction-logs">
                    <h3>Transaction Logs</h3>
                    <div class="export-buttons">
                        <button class="btn-primary export-pdf" data-report="transaction-logs">Export PDF</button>
                        <button class="btn-primary export-csv" data-report="transaction-logs">Export CSV</button>
                    </div>
                    <div class="report-data" id="transaction-logs-data">
                        <!-- Transaction Logs data will be loaded here via AJAX -->
                        <p>Loading Transaction Logs...</p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="tab-content" id="expiry-calendar">
                <h3>Expiry Calendar</h3>
                <div class="export-buttons">
                    <button class="btn-primary export-pdf" data-report="expiry-calendar">Export PDF</button>
                    <button class="btn-primary export-csv" data-report="expiry-calendar">Export CSV</button>
                </div>
                <div class="report-data" id="expiry-calendar-data">
                    <!-- Expiry Calendar data will be loaded here via AJAX -->
                    <p>Loading Expiry Calendar...</p>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/modals.php'; ?>
    <script>
        // Pass user role to JavaScript
        const USER_ROLE = "<?php echo $user_role; ?>";
    </script>
</body>
</html>
