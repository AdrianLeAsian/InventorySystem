<?php
require_once '../config/db.php';
require_once '../vendor/autoload.php';
require_once '../includes/InventoryImporter.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'An unknown error occurred.',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'File upload failed or no file was uploaded.';
        echo json_encode($response);
        exit();
    }

    $fileTmpPath = $_FILES['excelFile']['tmp_name'];
    $fileName = $_FILES['excelFile']['name'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    $allowedfileExtensions = ['xlsx', 'xls', 'csv'];
    if (!in_array($fileExtension, $allowedfileExtensions)) {
        $response['message'] = 'Invalid file format. Only .xlsx, .xls, and .csv files are allowed.';
        echo json_encode($response);
        exit();
    }

    $updateExisting = isset($_POST['updateExisting']) && $_POST['updateExisting'] === 'true';

    try {
        $importer = new InventoryImporter($conn);
        $response = $importer->import($fileTmpPath, $updateExisting, $fileExtension);
    } catch (Exception $e) {
        $response['message'] = 'An unexpected error occurred: ' . $e->getMessage();
        $response['errors'][] = $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
