<?php
// You can run this script via CLI: php migrations/add_notification_tables.php
// Or, you can manually run the SQL queries in your database management tool.

require_once __DIR__ . '/../config/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS `notification_recipients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=enabled, 0=disabled',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notifications_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(255) NOT NULL,
  `sent_to` text,
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->multi_query($sql)) {
    echo "Tables 'notification_recipients' and 'notifications_log' created successfully or already exist.\n";
    // It's important to clear the results of a multi_query
    while ($conn->next_result()) {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    }
} else {
    echo "Error creating tables: " . $conn->error . "\n";
}

$conn->close();
?>
