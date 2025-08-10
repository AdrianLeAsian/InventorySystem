<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $status = $_POST['status'] ?? '';

    if (empty($id) || !is_numeric($status)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID and status are required.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE notification_recipients SET status = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update recipient status.']);
    }

    $stmt->close();
    $conn->close();
}
?>
