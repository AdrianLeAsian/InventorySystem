<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';

    if (empty($id)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required.']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM notification_recipients WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete recipient.']);
    }

    $stmt->close();
    $conn->close();
}
?>
