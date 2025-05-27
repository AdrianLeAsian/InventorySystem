<?php
require_once 'config/db.php';

function output_csv($filename, $data, $headers) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');
    
    // Add headers
    fputcsv($output, $headers);
    
    // Add data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit; // Important: Exit after sending the CSV to prevent further HTML output
}

if (isset($_GET['type'])) {
    if ($_GET['type'] === 'all_data') {
        // Fetch all data from the 'items' table, joining with categories for category name
        // and formatting dates for readability.
        $sql = "SELECT 
                    i.id, 
                    c.name AS category_name, 
                    i.barcode, 
                    i.quantity, 
                    i.unit, 
                    i.description, 
                    DATE_FORMAT(i.created_at, '%Y-%m-%d %H:%i:%s') AS created_at, 
                    DATE_FORMAT(i.updated_at, '%Y-%m-%d %H:%i:%s') AS updated_at, 
                    i.location 
                FROM items i 
                LEFT JOIN categories c ON i.category_id = c.id"; // Use LEFT JOIN to include items without a category

        $result = $link->query($sql);

        if ($result->num_rows > 0) {
            $data = [];
            // Explicitly define headers to ensure correct names and order
            $headers = [
                'ID', 
                'Category Name', 
                'Barcode', 
                'Quantity', 
                'Unit', 
                'Description', 
                'Created At', 
                'Updated At', 
                'Location'
            ];

            while ($row = $result->fetch_assoc()) {
                // Ensure the order of values matches the defined headers
                $ordered_row = [
                    $row['id'],
                    $row['category_name'],
                    $row['barcode'],
                    $row['quantity'],
                    $row['unit'],
                    $row['description'],
                    $row['created_at'], // Already formatted by SQL
                    $row['updated_at'], // Already formatted by SQL
                    $row['location']
                ];
                $data[] = $ordered_row;
            }
            output_csv('all_inventory_data.csv', $data, $headers);
        } else {
            // If no data, still exit after message to prevent HTML output
            echo "No data found to export.";
            exit; 
        }
    } else {
        echo "Invalid export type specified.";
        exit; // Exit for invalid type as well
    }
} else {
    // Only include header and display HTML form if not exporting CSV
    include_once 'includes/header.php'; 
    ?>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                Export Data
            </div>
            <div class="card-body">
                <form action="export.php" method="GET">
                    <input type="hidden" name="type" value="all_data">
                    <button type="submit" class="btn btn-primary">Export All Data as CSV</button>
                </form>
            </div>
        </div>
    </div>
    <?php
}
?>
