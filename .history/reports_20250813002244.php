<?php
include 'includes/auth.php';
$page_title = 'Reports';
include 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/reports.css"> <!-- New CSS for reports -->
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>
    <div class="main-content">
        <h2>Reports</h2>

        <div class="tabs">
            <button class="tab-button active" data-tab="stock-summary">Stock Summary</button>
            <button class="tab-button" data-tab="detailed-inventory">Detailed Inventory</button>
            <button class="tab-button" data-tab="transaction-logs">Transaction Logs</button>
            <button class="tab-button" data-tab="expiry-calendar">Expiry Calendar</button>
        </div>

        <div id="stock-summary" class="tab-content active">
            <h3>Stock Summary</h3>
            <div class="summary-cards-container">
                <!-- Stock Summary Cards will be loaded here by JS -->
            </div>
        </div>

        <div id="detailed-inventory" class="tab-content">
            <h3>Detailed Inventory</h3>
            <div class="filters-container">
                <input type="text" id="detailedInventorySearch" placeholder="Search by Item Name">
                <select id="detailedInventoryCategoryFilter">
                    <option value="">All Categories</option>
                </select>
                <select id="detailedInventoryLocationFilter">
                    <option value="">All Locations</option>
                </select>
                <select id="detailedInventoryStockStatusFilter">
                    <option value="">All Stock Status</option>
                    <option value="in_stock">In Stock</option>
                    <option value="low">Low Stock</option>
                    <option value="out">Out of Stock</option>
                </select>
                <select id="detailedInventoryPerishableFilter">
                    <option value="">All Types</option>
                    <option value="1">Perishable</option>
                    <option value="0">Non-Perishable</option>
                </select>
                <label for="detailedInventoryExpiryStart">Expiry Start:</label>
                <input type="date" id="detailedInventoryExpiryStart">
                <label for="detailedInventoryExpiryEnd">Expiry End:</label>
                <input type="date" id="detailedInventoryExpiryEnd">
                <button id="applyDetailedInventoryFilters">Apply Filters</button>
                <button id="resetDetailedInventoryFilters">Reset Filters</button>
                <button id="exportDetailedInventory">Export</button>
            </div>
            <div class="table-responsive">
                <table id="detailedInventoryTable" class="data-table">
                    <thead>
                        <tr>
                            <th data-sort="name">Item Name</th>
                            <th data-sort="category_name">Category</th>
                            <th data-sort="location_name">Location</th>
                            <th data-sort="current_stock">Current Stock</th>
                            <th>Unit</th>
                            <th data-sort="low_stock" class="admin-staff-only">Low Stock Threshold</th>
                            <th data-sort="max_stock" class="admin-staff-only">Max Stock</th>
                            <th data-sort="is_perishable">Perishable?</th>
                            <th data-sort="expiry_date">Expiry Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Detailed Inventory data will be loaded here by JS -->
                    </tbody>
                </table>
            </div>
            <div class="pagination" id="detailedInventoryPagination">
                <!-- Pagination controls will be loaded here by JS -->
            </div>
        </div>

        <div id="transaction-logs" class="tab-content">
            <h3>Transaction Logs</h3>
            <div class="filters-container">
                <label for="logDateStart">Date Start:</label>
                <input type="date" id="logDateStart">
                <label for="logDateEnd">Date End:</label>
                <input type="date" id="logDateEnd">
                <select id="logActionTypeFilter">
                    <option value="">All Actions</option>
                    <option value="added">Added</option>
                    <option value="updated">Updated</option>
                    <option value="removed">Removed</option>
                    <option value="stock_added">Stock Added</option>
                    <option value="stock_reduced">Stock Reduced</option>
                    <option value="deleted">Deleted</option>
                </select>
                <select id="logCategoryFilter">
                    <option value="">All Categories</option>
                </select>
                <select id="logItemFilter">
                    <option value="">All Items</option>
                </select>
                <button id="applyTransactionLogsFilters">Apply Filters</button>
                <button id="resetTransactionLogsFilters">Reset Filters</button>
                <button id="exportTransactionLogs">Export</button>
            </div>
            <div class="table-responsive">
                <table id="transactionLogsTable" class="data-table">
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>Item Name</th>
                            <th>Action</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Transaction Logs data will be loaded here by JS -->
                    </tbody>
                </table>
            </div>
            <div class="pagination" id="transactionLogsPagination">
                <!-- Pagination controls will be loaded here by JS -->
            </div>
        </div>

        <div id="expiry-calendar" class="tab-content">
            <h3>Expiry Calendar</h3>
            <div class="filters-container">
                <select id="calendarCategoryFilter">
                    <option value="">All Categories</option>
                </select>
                <select id="calendarLocationFilter">
                    <option value="">All Locations</option>
                </select>
                <button id="applyCalendarFilters">Apply Filters</button>
                <button id="resetCalendarFilters">Reset Filters</button>
                <button id="exportCalendarList">Export List</button>
            </div>
            <div id="calendar"></div>
        </div>
    </div>

    <?php include 'includes/modals.php'; ?>

    <!-- Expiry Details Modal -->
    <div id="expiryDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3>Expiry Details</h3>
            <p><strong>Item Name:</strong> <span id="modalItemName"></span></p>
            <p><strong>Category:</strong> <span id="modalCategory"></span></p>
            <p><strong>Location:</strong> <span id="modalLocation"></span></p>
            <p><strong>Batch Quantity:</strong> <span id="modalBatchQuantity"></span></p>
            <p><strong>Days Until Expiry:</strong> <span id="modalDaysUntilExpiry"></span></p>
        </div>
    </div>

    <input type="hidden" id="userRole" value="<?php echo htmlspecialchars($_SESSION['role'] ?? 'viewer'); ?>">

    <!-- FullCalendar JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales-all.min.js'></script>
    <script src="js/reports.js"></script> <!-- New JS for reports -->
</body>
</html>
