<?php
session_start(); // Start the session to access user role
include 'db.php';

// Check if user is logged in and has the 'admin' role for write operations
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Access denied. Only administrators can manage categories and locations.']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$type = isset($_POST['type']) ? $_POST['type'] : '';

if ($type == 'category') {
    if ($action == 'add') {
        $name = trim($_POST['name']);
        $check = $conn->prepare("SELECT id FROM categories WHERE name = ?");
        $check->bind_param('s', $name);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Category name already exists.']);
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param('s', $name);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add category.']);
        }
        exit;
    }
    if ($action == 'edit') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $check = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
        $check->bind_param('si', $name, $id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Category name already exists.']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE categories SET name=? WHERE id=?");
        $stmt->bind_param('si', $name, $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update category.']);
        }
        exit;
    }
    if ($action == 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete category.']);
        }
        exit;
    }
}

if ($type == 'location') {
    if ($action == 'add') {
        $name = trim($_POST['name']);
        $check = $conn->prepare("SELECT id FROM locations WHERE name = ?");
        $check->bind_param('s', $name);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Location name already exists.']);
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO locations (name) VALUES (?)");
        $stmt->bind_param('s', $name);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add location.']);
        }
        exit;
    }
    if ($action == 'edit') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $check = $conn->prepare("SELECT id FROM locations WHERE name = ? AND id != ?");
        $check->bind_param('si', $name, $id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Location name already exists.']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE locations SET name=? WHERE id=?");
        $stmt->bind_param('si', $name, $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update location.']);
        }
        exit;
    }
    if ($action == 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM locations WHERE id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete location.']);
        }
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action or type.']);
exit;
