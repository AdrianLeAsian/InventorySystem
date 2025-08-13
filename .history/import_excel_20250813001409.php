<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/ExcelImporter.php';
require_once 'vendor/autoload.php'; // Composer autoloader

header('Content-Type: application/json');

// Check if user is logged in and has admin role
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Admin role required.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excelFile'])) {
    $file = $_FILES['excelFile'];

    // Validate file type and extension
    $allowedMimeTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/excel',
        'application/msexcel',
        'application/x-excel',
        'application/x-msexcel'
    ];
    $allowedExtensions = ['xlsx'];

    $fileMimeType = mime_content_type($file['tmp_name']);
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);

    if (!in_array($fileMimeType, $allowedMimeTypes) || !in_array($fileExtension, $allowedExtensions)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only .xlsx files are allowed.']);
        exit();
    }

    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $uploadFilePath = $uploadDir . basename($file['name']);

    if (move_uploaded_file($file['tmp_name'], $uploadFilePath)) {
        $importer = new ExcelImporter($conn);
        $userId = $_SESSION['user_id'] ?? null; // Get logged-in user ID

        $importSummary = $importer->import($uploadFilePath, $file['name'], $userId);

        // Clean up the uploaded file
        unlink($uploadFilePath);

        echo json_encode($importSummary);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload file.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
