<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';

    if (empty($id) || empty($name) || empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID, name, and email are required.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE notification_recipients SET name = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $email, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update recipient.']);
    }

    $stmt->close();
    $conn->close();
}
?>
