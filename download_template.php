<?php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=inventory_template.csv');

$output = fopen('php://output', 'w');

// Output the CSV header
fputcsv($output, array('ID', 'Name', 'Category', 'Location', 'Current Stock', 'Unit', 'Low Stock', 'Max Stock', 'Is Perishable', 'Expiry Date'));

fclose($output);
exit();
?>
