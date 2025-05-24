<?php
session_start();
require_once 'config/db.php';
require_once 'includes/header.php';

// Simple router
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$page_path = "pages/{$page}.php";

if (file_exists($page_path)) {
    require_once $page_path;
} else {
    // You can create a 404 page later
    echo "<div class='container'><p>Page not found.</p></div>";
}
?>
</body>
</html>
