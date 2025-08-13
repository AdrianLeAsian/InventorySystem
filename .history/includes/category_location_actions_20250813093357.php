<?php
session_start(); // Ensure session is started
include 'db.php';
include 'auth.php'; // Include auth.php for check_admin_role()

$action = isset($_POST['action']) ? $_POST['action'] : '';
$type = isset($_POST['type']) ? $_POST['type'] : '';

if ($type == 'category') {
    if ($action == 'add') {
        check_admin_role(); // Enforce admin role for adding categories
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
        check_admin_role(); // Enforce admin role for editing categories
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
        check_admin_role(); // Enforce admin role for deleting categories
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
        check_admin_role(); // Enforce admin role for adding locations
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
        check_admin_role(); // Enforce admin role for editing locations
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
        check_admin_role(); // Enforce admin role for deleting locations
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
