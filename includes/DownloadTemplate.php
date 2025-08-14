<?php
// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="inventory_template.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Define CSV headers
$headers = [
    'Item Name',
    'Category Name',
    'Location Name',
    'Current Stock',
    'Unit',
    'Low Stock Threshold',
    'Max Stock Capacity',
    'Is Perishable (TRUE/FALSE)',
    'Expiry Date (YYYY-MM-DD)'
];

// Write headers to CSV
fputcsv($output, $headers);

// Close the output stream
fclose($output);
exit;
?>
