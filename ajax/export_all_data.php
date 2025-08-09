<?php
require_once '../config/db.php';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="inventory_data_' . date('Y-m-d_H-i-s') . '.csv"');

$output = fopen('php://output', 'w');

// Fetch table names from the database
$table_names = [];
$sql_tables = "SHOW TABLES";
if ($result_tables = mysqli_query($conn, $sql_tables)) {
    while ($row = mysqli_fetch_row($result_tables)) {
        $table_names[] = $row[0];
    }
    mysqli_free_result($result_tables);
} else {
    // Handle error, though for export, we might just output an empty file or error message
    fputcsv($output, ['Error fetching table names: ' . mysqli_error($conn)]);
    fclose($output);
    exit();
}

foreach ($table_names as $table) {
    // Write table name as a header
    fputcsv($output, ["--- Table: $table ---"]);

    // Fetch column names
    $columns = [];
    $sql_columns = "SHOW COLUMNS FROM `$table`";
    if ($result_columns = mysqli_query($conn, $sql_columns)) {
        while ($row = mysqli_fetch_assoc($result_columns)) {
            $columns[] = $row['Field'];
        }
        mysqli_free_result($result_columns);
        fputcsv($output, $columns); // Write column headers
    } else {
        fputcsv($output, ['Error fetching columns for table ' . $table . ': ' . mysqli_error($conn)]);
        continue; // Skip to next table
    }

    // Fetch data
    $sql_data = "SELECT * FROM `$table`";
    if ($result_data = mysqli_query($conn, $sql_data)) {
        while ($row = mysqli_fetch_assoc($result_data)) {
            // Ensure data matches column order
            $rowData = [];
            foreach ($columns as $col) {
                $rowData[] = $row[$col];
            }
            fputcsv($output, $rowData);
        }
        mysqli_free_result($result_data);
    } else {
        fputcsv($output, ['Error fetching data for table ' . $table . ': ' . mysqli_error($conn)]);
    }
    fputcsv($output, []); // Add an empty row for separation between tables
}

fclose($output);
mysqli_close($conn);
?>
