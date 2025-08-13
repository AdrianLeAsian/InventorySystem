<?php
session_start();

// Function to check if user is logged in and redirect if not
function check_auth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Function to check if user is admin
function check_admin_role() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        // For AJAX requests, send a JSON error response
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            http_response_code(403); // Forbidden
            echo json_encode(['status' => 'error', 'message' => 'Forbidden: Admin access required.']);
            exit;
        } else {
            // For regular page requests, redirect to an unauthorized page or login
            header('Location: login.php?error=unauthorized'); // Or a dedicated unauthorized.php page
            exit;
        }
    }
}

// Call check_auth() by default for pages that include this file
check_auth();
?>
