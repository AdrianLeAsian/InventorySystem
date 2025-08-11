<?php
// Database connection for db_inventory
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'db_inventory';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
?>
