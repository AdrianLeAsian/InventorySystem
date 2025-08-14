<?php
session_start();
include 'includes/auth.php'; // Include authentication check
include 'includes/db.php'; // Include database connection

// Restrict access to admin only
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php'); // Redirect non-admins
    exit;
}
include 'includes/sidebar.php'; // Include sidebar
include 'includes/header.php'; // Include header

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data - Inventory System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="main-content" id="mainContent">
        <div class="dashboard-section container">
            <h2>Import Data</h2>

            <div class="import-section">
                <h3>Upload CSV File</h3>
                <form id="importForm" action="includes/ImportHandler.php" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <input type="file" name="csv_file" accept=".csv" required>
                    </div>
                    <button type="submit" name="import_action" value="import_csv" class="btn-primary">Import CSV</button>
                </form>
                <div class="template-download-section" style="margin-top: 20px;">
                    <a href="includes/DownloadTemplate.php" class="btn-secondary" download="inventory_template.csv">Download Empty Template</a>
                </div>
            </div>

            <div class="import-history-section">
                <h3>Import History</h3>
                <table id="importHistoryTable" class="table">
                    <thead>
                        <tr>
                            <th>Import Date</th>
                            <th>User</th>
                            <th>File Name</th>
                            <th>Status</th>
                            <th>Summary</th>
                            <th>Errors</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Import history will be loaded here via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="js/jquery-3.6.0.min.js"></script> <!-- Assuming jQuery is available -->
    <script src="js/import.js"></script> <!-- New JS for import page -->

    <!-- Error Modal -->
    <div id="errorModal" class="modal">
        <div class="modal-content">
            <span class="close" id="errorModalCloseButton">&times;</span>
            <h4>Import Errors</h4>
            <div id="errorModalContent" class="error-details">
                <!-- Error details will be loaded here -->
            </div>
        </div>
    </div>
</body>
</html>

<?php include 'includes/footer.php'; // Include footer ?>
