<?php
session_start();
include 'includes/auth.php'; // Include authentication check
include 'includes/db.php'; // Include database connection

// Restrict access to admin only
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php'); // Redirect non-admins
    exit;
}

include 'includes/header.php'; // Include header
include 'includes/sidebar.php'; // Include sidebar
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data - Inventory System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/import.css"> <!-- New CSS for import page -->
</head>
<body>
    <div class="main-content">
        <h2>Import Data</h2>

        <div class="import-section">
            <h3>Upload CSV File</h3>
            <form action="includes/ImportHandler.php" method="post" enctype="multipart/form-data">
                <input type="file" name="csv_file" accept=".csv" required>
                <button type="submit" name="import_action" value="import_csv">Import CSV</button>
            </form>
        </div>

        <div class="import-history-section">
            <h3>Import History</h3>
            <table id="importHistoryTable">
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

    <script src="js/jquery-3.6.0.min.js"></script> <!-- Assuming jQuery is available -->
    <script src="js/import.js"></script> <!-- New JS for import page -->
</body>
</html>

<?php include 'includes/footer.php'; // Include footer ?>
