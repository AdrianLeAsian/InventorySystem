<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';

    if (empty($name) || empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Name and email are required.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO notification_recipients (name, email) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $email);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add recipient.']);
    }

    $stmt->close();
    $conn->close();
}
?>
