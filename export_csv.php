<?php
include 'includes/auth.php';
include 'includes/db.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=inventory_export.csv');

$output = fopen('php://output', 'w');

// Output the CSV header
fputcsv($output, array('ID', 'Name', 'Category', 'Location', 'Current Stock', 'Unit', 'Low Stock', 'Max Stock', 'Is Perishable', 'Expiry Date'));

// Fetch data from the database
$sql = "SELECT items.*, categories.name AS category_name, locations.name AS location_name FROM items JOIN categories ON items.category_id = categories.id JOIN locations ON items.location_id = locations.id";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, array(
        $row['id'],
        $row['name'],
        $row['category_name'],
        $row['location_name'],
        $row['current_stock'],
        $row['unit'],
        $row['low_stock'],
        $row['max_stock'],
        $row['is_perishable'] ? 'Yes' : 'No',
        $row['expiry_date']
    ));
}

fclose($output);
exit();
?>
