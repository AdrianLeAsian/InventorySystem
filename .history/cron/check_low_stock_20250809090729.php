<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/EmailService.php';

// Fetch all items that are below their low stock threshold
$stmt = $conn->prepare("SELECT * FROM items WHERE quantity < low_stock_threshold AND low_stock_threshold > 0");
$stmt->execute();
$low_stock_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($low_stock_items)) {
    echo "No low stock items found.\n";
    exit;
}

$emailService = new EmailService($conn);

foreach ($low_stock_items as $item) {
    echo "Processing low stock for item: {$item['name']}...\n";
    if ($emailService->sendLowStockAlert($item)) {
        echo "Email alert sent for {$item['name']}.\n";
    } else {
        echo "Failed to send email alert for {$item['name']}.\n";
    }
}

echo "Low stock check complete.\n";
?>
