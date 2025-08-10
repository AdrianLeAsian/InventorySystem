<?php
include_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['email'])) {
    $stmt = $db->prepare("INSERT INTO email_recipients (name, email) VALUES (?, ?)");
    $stmt->bind_param('ss', $_POST['name'], $_POST['email']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $db->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to add recipient.']);
    }
}
?>
