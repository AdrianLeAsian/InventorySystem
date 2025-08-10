<?php
require_once __DIR__ . '/../config/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS `tenant_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `sender_email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

if ($conn->query($sql) === TRUE) {
    echo "Table 'tenant_emails' created successfully.";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
