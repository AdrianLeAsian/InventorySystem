<?php
include_once __DIR__ . '/../config/db.php';
include_once __DIR__ . '/../includes/EmailService.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $emailService = new EmailService($db);
    if ($emailService->sendTestEmail($_POST['email'], 'Test Recipient')) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to send test email.']);
    }
}
?>
