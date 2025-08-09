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

    /**
     * Sends a generic email to specified recipients.
     *
     * @param array $to An array of recipient details, e.g., [['email' => '...', 'name' => '...']]
     * @param string $subject The email subject.
     * @param string $body The email body (HTML).
     * @param string $altBody The alternative plain text body.
     * @return bool True if the email was sent successfully, false otherwise.
     */
    public function sendEmail(array $to, string $subject, string $body, string $altBody = ''): bool {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            foreach ($to as $recipient) {
                $this->mailer->addAddress($recipient['email'], $recipient['name'] ?? '');
            }

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $body;
            $this->mailer->AltBody = $altBody ?: strip_tags(str_replace(['<br>', '<br />'], "\n", $body));

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$this->mailer->ErrorInfo}");
            return false;
        }
    }

    // The sendLowStockAlert method can now use the generic sendEmail method
    public function sendLowStockAlert($item) {
        $recipients = $this->getNotificationRecipients(); // Use the correct method name
        if (empty($recipients)) {
            error_log("No enabled notification recipients found for low stock alert.");
            return false;
        }

        $email_body = $this->formatLowStockEmail($item);
        $alt_body = "Low stock alert for {$item['name']}. Current stock: {$item['quantity']}. Threshold: {$item['low_stock_threshold']}.";

        return $this->sendEmail($recipients, 'Low Stock Alert: ' . $item['name'], $email_body, $alt_body);
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

    // Renamed from getRecipients to be more specific and use the new table
    private function getNotificationRecipients() {
        $stmt = $this->conn->prepare("SELECT name, email FROM notification_recipients WHERE status = 1");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    private function formatLowStockEmail($item) {
        // Ensure $item contains 'id', 'name', 'quantity', 'low_stock_threshold'
        // The cron job needs to fetch these details correctly.
        $inventoryLink = "http://{$_SERVER['HTTP_HOST']}/pages/inventory.php"; // Link to inventory page, not a specific item if item ID is not readily available here
        if (isset($item['id'])) {
            $inventoryLink .= "?item_id=" . $item['id'];
        }
        
        return "
            <h1>Low Stock Alert</h1>
            <p>This is an automated notification to inform you that an item in your inventory has fallen below its specified stock threshold.</p>
            <ul>
                <li><strong>Item Name:</strong> " . htmlspecialchars($item['name']) . "</li>
                <li><strong>Current Stock Level:</strong> " . htmlspecialchars($item['current_stock']) . "</li>
                <li><strong>Low Stock Threshold:</strong> " . htmlspecialchars($item['low_stock_threshold']) . "</li>
            </ul>
            <p>Please update the inventory as soon as possible.</p>
            <p><a href='{$inventoryLink}'>View Inventory</a></p>
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
