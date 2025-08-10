<?php
// cron/send_daily_stock_email.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/EmailService.php'; // Assuming EmailService handles PHPMailer

// --- Configuration ---
// Ideally, these would be loaded from a config file or database settings
// For now, hardcoding as per the plan to centralize SMTP settings.
// We'll assume EmailService.php can access these or they are passed to it.
// If EmailService.php needs explicit config, we'll need to adjust.

// --- Check if script has already run today ---
$today = date('Y-m-d');
$check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications_log WHERE DATE(sent_at) = ? AND status = 'success'");
$check_stmt->bind_param("s", $today);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$run_count = $check_result->fetch_assoc()['count'];

if ($run_count > 0) {
    echo "Daily stock status email already sent today. Exiting.\n";
    exit;
}

// --- Get Enabled Recipients ---
$recipients = $email_service->getNotificationRecipients(); // Use the new method

if (empty($recipients)) {
    echo "No enabled recipients found. Exiting.\n";
    // Log this as a potential issue or informational event if needed
    $log_stmt = $conn->prepare("INSERT INTO notifications_log (status, sent_to, sent_at) VALUES (?, ?, NOW())");
    $log_stmt->bind_param("ss", $status, $sent_to);
    $status = 'failed';
    $sent_to = 'N/A (no recipients)';
    $log_stmt->execute();
    $log_stmt->close();
    // $conn->close(); // Keep connection open for potential further operations if needed
    exit;
}

// --- Check Inventory Stock Status ---
$low_stock_items_data = [];
// Fetching item details needed for the email body
$items_stmt = $conn->prepare("SELECT id, name, current_stock, low_stock_threshold FROM inventory_items WHERE current_stock <= low_stock_threshold");
$items_stmt->execute();
$items_result = $items_stmt->get_result();
while ($item = $items_result->fetch_assoc()) {
    $low_stock_items_data[] = $item;
}
$items_stmt->close();

// --- Prepare Email Content ---
$report_date = date('Y-m-d H:i:s');
$email_subject = "Daily Inventory Stock Status Report";
$email_body = "<h2>Inventory Stock Status Report</h2>";
$email_body .= "<p><strong>Date of Report:</strong> " . htmlspecialchars($report_date) . "</p>";

if (empty($low_stock_items_data)) {
    $email_body .= "<p>All stocks are within safe levels.</p>";
    $overall_status = 'success';
} else {
    $email_body .= "<h3>Low Stock Items:</h3>";
    $email_body .= "<table border='1' cellpadding='5' cellspacing='0'>";
    $email_body .= "<tr><th>Item Name</th><th>Current Stock</th><th>Low Stock Threshold</th></tr>";
    foreach ($low_stock_items_data as $item) {
        $email_body .= "<tr>";
        $email_body .= "<td>" . htmlspecialchars($item['name']) . "</td>";
        $email_body .= "<td>" . htmlspecialchars($item['current_stock']) . "</td>";
        $email_body .= "<td>" . htmlspecialchars($item['low_stock_threshold']) . "</td>";
        $email_body .= "</tr>";
    }
    $email_body .= "</table>";
    $overall_status = 'success'; // Still success if we sent the report, even if there are low stocks
}

// --- Send Email ---
$email_service = new EmailService($conn); // Assuming EmailService needs the DB connection for settings

$sent_to_string = '';
$email_send_status = 'success'; // Default to success

// Prepare recipients in the format expected by sendEmail
$formatted_recipients = [];
foreach ($recipients as $recipient) {
    $formatted_recipients[] = [
        'email' => $recipient['email'],
        'name' => $recipient['name']
    ];
    $sent_to_string .= $recipient['email'] . ',';
}

// Send email using the generic sendEmail method
$email_sent = $email_service->sendEmail(
    $formatted_recipients,
    $email_subject,
    $email_body
);

if (!$email_sent) {
    $email_send_status = 'failed'; // Mark overall status as failed if any send fails
}

// Remove trailing comma from sent_to_string
$sent_to_string = rtrim($sent_to_string, ',');

// --- Log the notification ---
$log_stmt = $conn->prepare("INSERT INTO notifications_log (status, sent_to, sent_at) VALUES (?, ?, NOW())");
$log_stmt->bind_param("ss", $email_send_status, $sent_to_string);

if ($log_stmt->execute()) {
    echo "Daily stock status email processed. Status: " . $email_send_status . ". Sent to: " . $sent_to_string . "\n";
} else {
    echo "Error logging notification: " . $conn->error . "\n";
}

// $conn->close(); // Close connection at the very end if no more DB operations are needed.
// It's better to close it once at the end of the script.
$conn->close();
?>
