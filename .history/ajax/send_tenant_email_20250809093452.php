<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/EmailService.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenant_id = $_POST['tenant_id'] ?? null;
    $to_email = $_POST['to_email'] ?? null;
    $subject = $_POST['subject'] ?? '';
    $body = $_POST['body'] ?? '';

    if (empty($to_email) || empty($subject) || empty($body)) {
        header('Location: ../pages/tenants.php?email=error&msg=' . urlencode('Missing required fields.'));
        exit;
    }

    $emailService = new EmailService($conn);
    
    if ($emailService->sendTenantEmail($tenant_id, $to_email, $subject, $body)) {
        header('Location: ../pages/tenants.php?email=success');
        exit;
    } else {
        header('Location: ../pages/tenants.php?email=error&msg=' . urlencode('Failed to send email.'));
        exit;
    }
} else {
    echo 'Invalid request.';
}
?>
