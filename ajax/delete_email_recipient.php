<?php
include_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $stmt = $conn->prepare("DELETE FROM email_recipients WHERE id = ?");
    $stmt->bind_param('i', $_POST['id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete recipient.']);
    }
}
?>
