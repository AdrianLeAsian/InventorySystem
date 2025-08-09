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
$recipients_stmt = $conn->prepare("SELECT email, name FROM notification_recipients WHERE status = 1 ORDER BY name ASC");
$recipients_stmt->execute();
$recipients_result = $recipients_stmt->get_result();
$recipients = $recipients_result->fetch_all(MYSQLI_ASSOC);

if (empty($recipients)) {
    echo "No enabled recipients found. Exiting.\n";
    // Log this as a potential issue or informational event if needed
    $log_stmt = $conn->prepare("INSERT INTO notifications_log (status, sent_to, sent_at) VALUES (?, ?, NOW())");
    $log_stmt->bind_param("ss", $status, $sent_to);
    $status = 'failed';
    $sent_to = 'N/A (no recipients)';
    $log_stmt->execute();
    $log_stmt->close();
    $conn->close();
    exit;
}

// --- Check Inventory Stock Status ---
$low_stock_items = [];
$items_stmt = $conn->prepare("SELECT name, current_stock, low_stock_threshold FROM inventory_items WHERE current_stock <= low_stock_threshold");
$items_stmt->execute();
$items_result = $items_stmt->get_result();
while ($item = $items_result->fetch_assoc()) {
    $low_stock_items[] = $item;
}
$items_stmt->close();

// --- Prepare Email Content ---
$report_date = date('Y-m-d H:i:s');
$email_subject = "Daily Inventory Stock Status Report";
$email_body = "<h2>Inventory Stock Status Report</h2>";
$email_body .= "<p><strong>Date of Report:</strong> " . htmlspecialchars($report_date) . "</p>";

if (empty($low_stock_items)) {
    $email_body .= "<p>All stocks are within safe levels.</p>";
    $overall_status = 'success';
} else {
    $email_body .= "<h3>Low Stock Items:</h3>";
    $email_body .= "<table border='1' cellpadding='5' cellspacing='0'>";
    $email_body .= "<tr><th>Item Name</th><th>Current Stock</th><th>Low Stock Threshold</th></tr>";
    foreach ($low_stock_items as $item) {
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
$recipient_emails_list = [];
$sent_to_string = '';
$email_send_status = 'success'; // Default to success

foreach ($recipients as $recipient) {
    $recipient_emails_list[] = $recipient['email'];
    $sent_to_string .= $recipient['email'] . ',';

    // Send email to each recipient
    // Note: For bulk sending, it might be more efficient to send one email with all recipients in BCC.
    // For simplicity here, we'll send individually. Adjust if performance is an issue.
    $email_sent = $email_service->sendEmail(
        $recipient['email'],
        $recipient['name'], // Recipient Name
        $email_subject,
        $email_body
    );

    if (!$email_sent) {
        $email_send_status = 'failed'; // Mark overall status as failed if any send fails
        // Potentially log individual failures if EmailService provides more granular feedback
    }
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

$log_stmt->close();
$items_stmt->close(); // Close items statement if not already closed
$recipients_stmt->close(); // Close recipients statement if not already closed
$conn->close();

?>
