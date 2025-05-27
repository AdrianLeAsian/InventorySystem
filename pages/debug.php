<?php
// Function to get database connection status
// This function might need to be moved to a utility file or db.php if it's used elsewhere
// For now, assuming $conn is available from index.php's includes
function getDbStatus($conn) {
    if ($conn) {
        return "<span style='color: green;'>Connected</span>";
    } else {
        return "<span style='color: red;'>Disconnected</span>";
    }
}

// Check if $conn is set, otherwise assume disconnected for debug purposes
$db_status = isset($conn) ? getDbStatus($conn) : "<span style='color: red;'>Disconnected (Connection variable not found)</span>";

?>
<h1>Debug Information</h1>

<h2>PHP Information</h2>
<p>PHP Version: <?php echo phpversion(); ?></p>

<h2>Database Connection Status</h2>
<p>Status: <?php echo $db_status; ?><br>
<?php if (!isset($conn)) echo "Note: Database connection variable (\$conn) was not found. This page might be accessed directly, not through index.php."; ?></p>

<h2>Server Variables (<?php echo count($_SERVER); ?>)</h2>
<pre><?php print_r($_SERVER); ?></pre>

<h2>Session Variables (<?php echo count($_SESSION); ?>)</h2>
<pre><?php print_r($_SESSION); ?></pre>

<h2>GET Variables (<?php echo count($_GET); ?>)</h2>
<pre><?php print_r($_GET); ?></pre>

<h2>POST Variables (<?php echo count($_POST); ?>)</h2>
<pre><?php print_r($_POST); ?></pre>

<h2>Application Log (logs/debug.log)</h2>
<?php
$logFilePath = '../logs/debug.log';
if (file_exists($logFilePath)) {
    $logContent = file_get_contents($logFilePath);
    if (!empty($logContent)) {
        echo '<pre>' . htmlspecialchars($logContent) . '</pre>';
    } else {
        echo '<p>Log file is empty.</p>';
    }
} else {
    echo '<p>Log file (<code>' . htmlspecialchars($logFilePath) . '</code>) not found.</p>';
}
?>
