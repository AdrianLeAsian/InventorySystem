<?php
include_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if a record exists
    $check_stmt = $db->prepare("SELECT id FROM email_settings");
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing record
        $stmt = $db->prepare("UPDATE email_settings SET smtp_host = ?, smtp_port = ?, smtp_username = ?, smtp_password = ?, sender_email = ?, sender_name = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param('sissss', $_POST['smtp_host'], $_POST['smtp_port'], $_POST['smtp_username'], $_POST['smtp_password'], $_POST['sender_email'], $_POST['sender_name']);
    } else {
        // Insert new record
        $stmt = $db->prepare("INSERT INTO email_settings (smtp_host, smtp_port, smtp_username, smtp_password, sender_email, sender_name) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sissss', $_POST['smtp_host'], $_POST['smtp_port'], $_POST['smtp_username'], $_POST['smtp_password'], $_POST['sender_email'], $_POST['sender_name']);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save settings.']);
    }
}
?>
