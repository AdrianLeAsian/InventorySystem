<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class EmailService {
    private $mailer;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->mailer = new PHPMailer(true);
        $this->configureMailer();
    }

    private function configureMailer() {
        $settings = $this->getEmailSettings();
        if (!$settings) {
            // Handle case where settings are not found
            error_log("Email settings not configured in the database.");
            return;
        }

        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host       = $settings['smtp_host'];
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = $settings['smtp_username'];
            $this->mailer->Password   = $settings['smtp_password'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port       = $settings['smtp_port'];

            // Sender
            $this->mailer->setFrom($settings['sender_email'], $settings['sender_name']);

        } catch (Exception $e) {
            error_log("PHPMailer configuration error: {$this->mailer->ErrorInfo}");
        }
    }

    private function getEmailSettings() {
        $stmt = $this->conn->prepare("SELECT * FROM email_settings ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function sendLowStockAlert($item) {
        try {
            $recipients = $this->getRecipients();
            if (empty($recipients)) {
                error_log("No email recipients found for low stock alert.");
                return false;
            }

            foreach ($recipients as $recipient) {
                $this->mailer->addAddress($recipient['email'], $recipient['name']);
            }

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Low Stock Alert: ' . $item['name'];
            $this->mailer->Body    = $this->formatLowStockEmail($item);
            $this->mailer->AltBody = "Low stock alert for {$item['name']}. Current stock: {$item['quantity']}. Threshold: {$item['low_stock_threshold']}.";

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$this->mailer->ErrorInfo}");
            return false;
        }
    }
    
    public function sendTestEmail($recipientEmail, $recipientName) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($recipientEmail, $recipientName);

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Test Email from Inventory System';
            $this->mailer->Body    = 'This is a test email to verify your SMTP settings are configured correctly.';
            $this->mailer->AltBody = 'This is a test email to verify your SMTP settings are configured correctly.';

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Test email could not be sent. Mailer Error: {$this->mailer->ErrorInfo}");
            return false;
        }
    }

    private function getRecipients() {
        $stmt = $this->conn->prepare("SELECT name, email FROM email_recipients");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    private function formatLowStockEmail($item) {
        $inventoryLink = "http://{$_SERVER['HTTP_HOST']}/inventory.php?item_id={$item['id']}";
        return "
            <h1>Low Stock Alert</h1>
            <p>This is an automated notification to inform you that an item in your inventory has fallen below its specified stock threshold.</p>
            <ul>
                <li><strong>Item Name:</strong> {$item['name']}</li>
                <li><strong>Current Stock Level:</strong> {$item['quantity']}</li>
                <li><strong>Low Stock Threshold:</strong> {$item['low_stock_threshold']}</li>
            </ul>
            <p>Please update the inventory as soon as possible.</p>
            <p><a href='{$inventoryLink}'>View Item Details</a></p>
            <br>
            <p>Thank you,</p>
            <p>Inventory Management System</p>
        ";
    }

    public function sendTenantEmail($tenant_id, $to_email, $subject, $body) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to_email);

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = nl2br($body);

            $this->mailer->send();

            // Log to tenant_emails table
            $stmt = $this->conn->prepare("INSERT INTO tenant_emails (tenant_id, subject, body, sender_email) VALUES (?, ?, ?, ?)");
            $sender_email = $this->mailer->From;
            $stmt->bind_param('isss', $tenant_id, $subject, $body, $sender_email);
            $stmt->execute();
            $stmt->close();

            return true;
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$this->mailer->ErrorInfo}");
            return false;
        }
    }
}
